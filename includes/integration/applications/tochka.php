<?php
/*
	Name: Банк Точка
	Description: Загружает платежки в платформу, привязывает к контрагентам. Отчеты по расходам-доходам.
	Group: bank
	Price: paid
	Icon: tochka
*/
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_Tochka extends USAM_Application
{	
	protected $API_URL = "https://enter.tochka.com";
	/*
    "accountId": "Счет/БИК банка",
    "startDateTime": "Дата окончания срока выписки, формат даты ГГГГ-ММ-ДД",
    "endDateTime": "Дата начала срока выписки, формат даты ГГГГ-ММ-ДД"
	*/
	public function request_creation_statement( $args )
	{ 	
		$args = $this->get_args( 'POST', ['Data' => ['Statement' => $args]] );
		$result = $this->send_request( "uapi/open-banking/v1.0/statements", $args );			
		if ( isset($result['Data']) && isset($result['Data']['Statement']) )
			return $result['Data']['Statement'];
		return false;
	}	
	
	public function get_statement_result( $account_id, $statement_id )
	{ 	
		$args = $this->get_args( 'GET' );
		$result = $this->send_request( "uapi/open-banking/v1.0/accounts/$account_id/statements/".$statement_id, $args );
		return $result;
	}
	
	public function cron_upload_bank_payments( )
	{
		$this->upload_bank_payments();
	}
	
/*
0 Начисление процентов (автомат)
1 Платёжное поручение
2 Платёжное требование
3 Денежный чек
4 Объявление на взнос наличными
5 Требование-поручение
6 Инкассовое поручение
7 Расчётный чек
8 Аккредитив
9 Мемориальный ордер
10 Документ по погашению кредита
11 Документ по выдаче кредита
12 Авизо
13 Документ расчёта по банковской карте
16 Платёжный ордер
17 Банковский ордер
887 СПОД*/
	public function upload_bank_payments( $args = [] )
	{
		require_once(USAM_FILE_PATH.'/includes/crm/bank_accounts_query.class.php');	
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
		$companies = usam_get_companies(['type' => 'own', 'fields' => 'id']);
		if ( empty($companies) )
			return false;
	
		$bank_accounts = usam_get_bank_accounts(['company' => $companies, 'bic' => ['044525104', '044525999']]);			
		if ( empty($bank_accounts) )
			return false;

		$args['startDateTime'] = usam_get_application_metadata( $this->option['id'], 'last_request_date' );
		if ( empty($args['startDateTime']) )
			$args['startDateTime'] = get_date_from_gmt( date("Y-m-d H:i:s", strtotime( '-160 days' ) ), "Y-m-d");	
		$args['endDateTime'] = get_date_from_gmt(date("Y-m-d"), "Y-m-d" );		
		$statements = array();	
		foreach ( $bank_accounts as $bank_account )
		{			
			$args['accountId'] = "$bank_account->number/$bank_account->bic";
			$statement = $this->request_creation_statement( $args );	
			if ( $statement )
				$statements[$statement['accountId']][] = $statement['statementId'];			
		}	
		if ( empty($statements) )
			return false;
		sleep(10);
		foreach ( $statements as $account_id => $statement_ids )
		{
			foreach ( $statement_ids as $statement_id )
			{			
				$results = $this->get_statement_result( $account_id, $statement_id );			
				if ( isset($results['Data']) && !empty($results['Data']['Statement']) )
				{
					foreach ( $results['Data']['Statement'] as $statements )
					{											
						$payment_ids = array();
						foreach ( $statements['Transaction'] as $transaction )			
						{
							$payment_ids[] = $transaction['transactionId'];					
						}		
						if ( !empty($payment_ids) )
							$documents = usam_get_documents(['fields' => 'meta_value', 'meta_query' => [['key' => 'tochka_id', 'value' => $payment_ids, 'compare' => 'IN']], 'type' => ['payment_received', 'payment_order']]);
						foreach( $statements['Transaction'] as $transaction )			
						{
							if ( !empty($documents) && in_array($transaction['transactionId'], $documents) )
								continue;
							
							$s = explode('/', $statements['accountId']);							
							$bank_account_id = '';
							foreach ( $bank_accounts as $bank_account )
							{
								if ( $s[0] == $bank_account->number )
									$bank_account_id = $bank_account->number;
							}
							$new_document = ['status' => 'approved', 'customer_id' => 0, 'bank_account_id' => $bank_account_id];	
							if ( !empty($transaction['DebtorAccount']) )
							{
								$bank_account = usam_get_bank_accounts(['account_number' => $transaction['DebtorAccount']['identification'], 'bic' => $transaction['DebtorAgent']['identification'], 'number' => 1]);
								if ( !empty($bank_account) )
									$new_document['customer_id'] = $bank_account['company_id'];
							}
							if ( empty($new_document['customer_id']) )
								$new_document['customer_id'] = usam_get_companies( ['meta_query' => [['key' => 'inn', 'value' => $transaction['DebtorParty']['inn'], 'compare' => '='], ['key' => 'ppc', 'value' => $transaction['DebtorParty']['kpp'], 'compare' => '=']], 'fields' => 'id', 'number' => 1] );
								
							if ( empty($new_document['customer_id']) )
							{						
								$new_document['customer_id'] = usam_insert_company(['name' => $transaction['DebtorParty']['name']], ['inn' => $transaction['DebtorParty']['inn'], 'ppc' => $transaction['DebtorParty']['kpp']], [['name' => $transaction['DebtorAccount']['identification'], 'bic' => $transaction['DebtorAgent']['identification']]]);
							}
							if ( !empty($transaction['DebtorParty']) )
								$new_document['type'] = 'payment_received';
							else
								$new_document['type'] = 'payment_order';
												
							$new_document['customer_type'] = 'company';
							$new_document['totalprice'] = abs($transaction['Amount']['amount']);
							$new_document['name'] = $transaction['description'];						
							$new_document['date_insert'] = date("Y-m-d H:i:s", strtotime($transaction['documentProcessDate'].' '.date("H").':00:00'));	
							$document_id = usam_insert_document( $new_document );	
							if ( $document_id )
							{
								usam_update_document_metadata($document_id, 'tochka_id', $transaction['transactionId'] );
									
							//	usam_update_document_metadata($document_id, 'counterparty_account_number', $transaction['counterparty_account_number'] );
							//	usam_update_document_metadata($document_id, 'counterparty_bank_bic', $transaction['counterparty_bank_bic'] );				
								
								usam_update_document_metadata($document_id, 'payment_number', $transaction['transactionId'] );
								if ( !empty($transaction['TaxFields']) )
								{
									usam_update_document_metadata($document_id, 'kbk', $transaction['TaxFields']['kbk'] );																
									usam_update_document_metadata($document_id, 'okato', $transaction['TaxFields']['oktmo'] );
									usam_update_document_metadata($document_id, 'payer_status', $transaction['TaxFields']['originatorStatus'] );
								}								
							}
						}
					}					
				}
			}
		}	
		usam_update_application_metadata( $this->id, 'last_request_date', date("Y-m-d") ); 
	}

	protected function get_args( $method, $params = [] )
	{ 
		$headers["Accept"] = 'application/json';		
		$headers["Authorization"] = "Bearer $this->token";
		if ( !empty($params) )
			$headers["Content-type"] = 'application/json';
		$args = [
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
		];	
		if ( !empty($params) )
			$args['body'] = json_encode($params);
		return $args;
	}
		
	function display_form( ) 
	{ 
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_login'>Client id:</label></div>
				<div class ="option">
					<input type="text" id='option_login' name="login" value="<?php echo $this->option['login']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='messenger_secret_key'><?php esc_html_e( 'Токен', 'usam'); ?>:</label></div>
				<div class ="option">
					<?php usam_get_password_input( $this->option['access_token'], ['name' => 'access_token', 'id' => 'messenger_secret_key']); ?>
				</div>
			</div>	
		</div>
		<?php		
	}
	
	public function rest_api( $request ) 
	{		
		$params = $request->get_json_params();	
		
		
			$Log = new USAM_Log_File( 'rest_api' ); 
	$Log->fwrite_array( $params );
		
	}
	
	public function register_routes( ) 
	{ 
		register_rest_route( $this->namespace, $this->route, [
			[
				'permission_callback' => false,
				'methods'  => 'POST',
				'callback' => 'usam_application_rest_api',	
				'args' => array()
			],	
			[
				'permission_callback' => false,
				'methods'  => 'GET',
				'callback' => 'usam_application_rest_api',	
				'args' => array()
			],			
		]);	
	}
	
	public function save_form( ) 
	{	
		$this->remove_hook( 'documents' );			
		if ( $this->is_token() )
		{
			$args = $this->get_args( 'DELETE' );
			$result = $this->send_request( "uapi/webhook/v1.0/".$this->option['login'], $args );	
			if ( $this->option['active'] )
			{
				$url = get_rest_url(null,$this->namespace.'/tochka/'.$this->id);
				$args = $this->get_args('PUT', ["url" => $url, "webhooksList" => ['incomingPayment', 'incomingSbpPayment']]);
				$result = $this->send_request( "uapi/webhook/v1.0/".$this->option['login'], $args );		
				
				$this->add_hook_ten_minutes('documents');
			}
		}
	}	
	
	public function filter_reconciliation_documents( $document_type )	
	{
		return 'payment_received';
	}
	
	public function service_load( ) 
	{		
		add_action('usam_application_documents_schedule_'.$this->option['service_code'],  [$this, 'cron_upload_bank_payments']);		
		add_filter('usam_reconciliation_documents', [$this,'filter_reconciliation_documents']);
	}
}
?>