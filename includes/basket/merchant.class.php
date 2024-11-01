<?php
/**
 * Класс оплаты. Это базовый класс оплаты, все торговые файлы, использующие новый API должны расширить этот класс.
 */
class USAM_Merchant 
{
	protected $api_version = '2.0';
	protected $type_operation = 'c';
	protected $error;	
	protected $content_type = 'get';		
	
	protected $payment;
	protected $gateway_data;
	protected $purchase_log;
	protected $gateway_system = array();	
	protected $data_sending_gateway = array();	
	
	protected $payment_number = null;
	protected $payment_type = 'cart'; // Тип оплаты

	protected $notification_url;
	protected $url_cancel_return = '';
	protected $url_return = '';
	protected $ipn = false;
		
	protected $payment_link = '';
	protected $test_payment_link = '';
	protected $user_account_url = '';
	
	function __construct( $gateway ) 
	{ 
		$this->gateway_system = $gateway;
		if ( !empty($this->gateway_system['id']) )
		{
			$metas = usam_get_payment_gateway_metadata( $this->gateway_system['id'] );
			foreach($metas as $metadata )
				$this->gateway_system[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );
		}
		$options = $this->get_options();
		foreach($options as $option )
			if ( !isset($this->gateway_system[$option['code']]) )
				$this->gateway_system[$option['code']] = $option['default'];
		if ( !$this->gateway_system['bank_account_id'] )
			$this->gateway_system['bank_account_id'] = get_option( 'usam_shop_company', 0 );			
		if ( isset($this->gateway_system['id']) )
			$this->notification_url = usam_get_url_system_page('transaction-results').'/notification/'.$this->gateway_system['id'];
	}	
	
	public function get_options( ) 
	{
		return [];
	}
	
	public function get_type_operation( ) 
	{
		return $this->type_operation;	
	}		
	
	function get_document_pdf_link( $form, $id ) 
	{	
		$time = time();
		return usam_url_action( 'printed_form_to_pdf', ['form' => $form, 'id' => $id, 'time' => $time]);
	}
		
	// Сохранить информацию о транзакции при успешном платеже
	protected function update_payment_document( $payment ) 
	{
		if ( !usam_check_object_is_completed($this->purchase_log->get('status'), 'order') )
		{
			if ( isset($payment['status']) )
			{
				$status = '';
				switch( $payment['status'] ) 
				{
					case 1:
						$status = 'received';
					break;				
					case 3:
						$status = 'accepted_payment';
						if ( empty($payment['date_payed']) )
							$payment['date_payed'] = date( "Y-m-d H:i:s");	
							
						$order_id = $this->purchase_log->get('id');	
						if ( $order_id )
							usam_delete_cart(['order_id' => $order_id]);
					break;
					case 4:
						$status = 'job_dispatched';
					break;					
				}				
				if ( $status )
				{
					$this->set_purchase_processed_by_purchid( $status );
				}
			}
			$payment['number'] = $this->payment_number;			
			usam_update_payment_document($this->payment_number, $payment, 'number');
		}
	}
	
	function is_debug( ) 
	{
		return $this->gateway_system['debug']?true:false;
	}	
	
	function get_user_account_url( ) 
	{
		return $this->user_account_url;
	}
	
	function get_ipn( ) 
	{
		return $this->ipn;
	}
	
	function get_currency_list( ) 
	{
		return array();
	}	
			
	public function get_customer_data_by_type( $type ) 
	{				
		$order_id = $this->purchase_log->get('id');	
		$list_properties = usam_get_properties(['type' => 'order', 'fields' => 'code=>data']);			
		$result = '';
		foreach ($list_properties as $code => $data) 
		{
			if ( $data->field_type == $type )
			{
				$value = usam_get_order_metadata( $order_id, $code );
				if ( $value )
				{
					$result = $value;
					break;
				}
			}
		}
		return $result;
	}
	
	protected function redirect_to_checkout( ) 
	{
		wp_redirect( usam_get_url_system_page( 'checkout' ) );
		exit;
	}

	function go_to_transaction_results( $result = 1 ) 
	{	
		$url = $result?$this->url_return:$this->url_cancel_return;
		wp_redirect( $url );		
		exit();
	}
	
	/**
	 * Помогает изменить статус заказа по его номеру
	 */
	function set_purchase_processed_by_purchid( $status = 'waiting_payment' ) 
	{				
		$order_id = $this->purchase_log->get('id');	
		$result = usam_update_order( $order_id, ['status' => $status]);
		if ( $result )
			do_action('usam_document_order_save', $order_id);
		return $result;
	}
	
	public function add_new_user( $order_id ) 
	{
		$contact_id = usam_get_contact_id();		
		$contact = usam_get_contact( $contact_id );
		if ( !empty($contact['user_id']) )
			return false;
		
		$anonymous_function = function($a) { return false; };	
		add_filter( 'usam_enable_insert_user', $anonymous_function);	
		
		$mobilephone = usam_get_order_customerdata( $order_id, 'mobilephone' );
		if ( !$mobilephone || username_exists($mobilephone) )
		{
			$user_email = usam_get_order_customerdata( $order_id, 'email' );
			if ( email_exists($user_email) )
				return true;
			$strings = explode('@',$user_email);
			$user_login = $strings[0];	
			$i = 1;
			do 
			{
				if ( !username_exists( $user_login ) )
					break;
				$user_login = $strings[0]."$i";
			} 
			while ( true );
		}		
		else
			$user_login = $mobilephone;
		$user_pass = wp_generate_password( 7 );
		$user_id = wp_create_user( $user_login, $user_pass, $user_email );
		if ( !$user_id ) 
			return false;
		
		if ( $mobilephone )
			update_user_meta( $user_id, 'user_management_phone', $mobilephone );
		$credentials = array( 'user_login' => $user_login, 'user_password' => $user_pass, 'remember' => true );
		$user = wp_signon( $credentials );
		$domain = parse_url(get_bloginfo('url'), PHP_URL_HOST);
		$message = "<h3 style='margin-bottom:30px'>".sprintf(__("Добро пожаловать на сайт %s","usam"), $domain)."</h3>";
		$message .= "<table><tr><td style='padding:10px'>".__("Ваш логин","usam").":</td><td style='padding:10px'>$user_login</td></tr>";
		$message .= "<tr><td style='padding:10px'>".__("Ваш пароль","usam").":</td><td style='padding:10px'>$user_pass</td></tr></table>";
		$message .= "<p style='margin-top:30px'><a href='".usam_get_url_system_page('login')."?login=$user_login&user_pass=$user_pass'>".__("Войти на сайт","usam")."</a></p>";
		$email_sent = usam_send_mail_by_id(['message' => $message, 'title' => sprintf( __("Вы зарегистрировались на сайте %s","usam"), $domain), 'email' => $user_email], [], [['object_id' => $order_id, 'object_type' => 'order']]);				
		usam_update_contact( $contact_id, array('user_id' => $user_id) );	
	}
	
	// Подготовить данные заказа и создать кнопку отправки данных на шлюз
	public function send_gateway_parameters( $payment ) 
	{					
		$this->purchase_log = new USAM_Order( $payment['document_id'] );		
		$order = $this->purchase_log->get_data();				
		$payment['bank_account_id'] = $this->gateway_system['bank_account_id'];
		if ( empty($payment['sum']) )
		{
			$payment_sum = $this->purchase_log->get_payment_status_sum();
			$payment['sum'] = $payment_sum['payment_required'];
		}
		$payment_id = usam_insert_payment_document( $payment, ['document_id' => $order['id'], 'document_type' => 'order'] );		
		if ( !$payment_id )
			$this->redirect_to_checkout();	
		$this->payment = usam_get_payment_document( $payment_id );
		$this->payment_number = $this->payment['number'];	

		if ( $this->payment['bank_account_id'] != $order['bank_account_id'] )
		{			
			$this->purchase_log->set(['bank_account_id' => $this->payment['bank_account_id']]);	
			$this->purchase_log->save( ); 
			do_action('usam_document_order_save', $payment['document_id']);
		}				
		$this->url_cancel_return = add_query_arg( array('payment_number' => $this->payment_number), usam_get_url_system_page('transaction-results').'/fail/'.$this->gateway_system['id']);
		$this->url_return = add_query_arg( array('payment_number' => $this->payment_number), usam_get_url_system_page('transaction-results').'/success/'.$this->gateway_system['id']);	
	
		$order_status = usam_get_object_status_by_code( $order['status'], 'order' );
		if ( !$order_status['pay'] )	
		{ 
			$this->set_error( __("Заказ в статусе, который оплачивать нельзя","usam") );
			$this->go_to_transaction_results( );					
		}
	}
	
	function get_country_code( )
	{
		$country = usam_get_country_location( );
		return isset($country['code'])?$country['code']:'ru';
	}
	
	function get_country_language( )
	{
		$country = usam_get_country_location();
		return isset($country['language'])?$country['language']:'';
	}
	
	function get_gateway_currency_code( )
	{
		if ( !empty($this->payment['bank_account_id']) )
		{
			$acc_number = usam_get_bank_account( $this->payment['bank_account_id'] );
			return $acc_number['currency'];
		}
		else
		{
			$local_currency_code = usam_get_currency_price_by_code();	
			return $local_currency_code;
		}
	}
	
	function convert( $price )
	{		
		return $price;
		
		$local_currency_code = usam_get_currency_price_by_code();		
		$acc_number = usam_get_bank_account( $this->payment['bank_account_id'] );		
		return usam_format_convert_price($price, $local_currency_code, $acc_number['currency'] );
	}	

	protected function get_url( ) 
	{
		if ( $this->is_debug() && $this->test_payment_link )
			return $this->test_payment_link;
		elseif ( !empty($this->payment_link) )
			return $this->payment_link;
		else
			return $this->url_return;
	}
	
	// Параметры для отправки в платежный шлюз
	protected function get_vars( $aggregate ) 
	{
		return array();
	}
	
	// Отправить запрос
	function send_request( $url, $args )
	{	
		$data = wp_remote_post( $url, $args );			
		if ( is_wp_error($data) )
		{
			$this->set_error( $data->get_error_message() );	
			return false;
		} 
		$resp = json_decode($data['body'], true);		
		if ( isset($resp['error'] ) ) 
		{			
			$this->set_error( $resp['error'] );	
			return false;
		}		
		return $resp;		
	}
	
	function get_url_submit( $aggregate = false ) 
	{				
		$url = $this->get_url();		
		$this->data_sending_gateway = $this->get_vars( $aggregate );

		if ( empty($this->data_sending_gateway)) 
			return $url;
		
		$name_value_pairs = array();
		foreach ( $this->data_sending_gateway as $key => $value)
			$name_value_pairs[] = $key . '=' . urlencode($value);
			
		$gateway_values =  implode('&', $name_value_pairs);
		return $url."?".$gateway_values;				
	}
	
	/**
	 * отправить данные в торговый шлюз, продлен в торговых файлы
	 */
	public function submit() 
	{
		if( !empty($this->gateway_system['handler']) )
		{ 				
			$this->set_purchase_processed_by_purchid();
			$redirect = $this->get_url_submit();	
			if( strlen($redirect) > 2000 ) 		
				$redirect = $this->get_url_submit( true );		
			
			if( $this->is_debug() )
			{
				echo "<h1>".__("Режим отладки","usam")."</h1>";
				echo "<p>".sprintf(__("Хотите отключить этот режим? Перейдите в %s и отключите режим отладки","usam"),"<a href='".admin_url("admin.php?page=orders&tab=orders&table=payment_gateway&view=form&form=edit&form_name=payment_gateway&id=".$this->gateway_system['id'])."'>".__("настройки платежного шлюза","usam")."</a>").".</p>";
				echo "<h2>".__("Нажмите на ссылку, чтобы продолжить","usam")."</h2>";
				echo "<a href='".esc_url($redirect)."' target='_blank'>".$redirect."</a>";
				echo "<pre>".print_r($this->data_sending_gateway,true)."</pre>";
			} 
			else
			{
				wp_redirect( $redirect );
				exit;
			}
		}
		else
		{  
			$result = $this->set_purchase_processed_by_purchid( 'received' );			
			$this->go_to_transaction_results( );			
		} 
	}
	
	protected function get_payment_number( ) 
	{
		return $this->get_standard_payment_number();
	}
	
	private function get_standard_payment_number( ) 
	{
		if (isset($this->gateway_data['payment_number']))
			return $this->gateway_data['payment_number'];
		else
			return false;
	}
	
	/**
	 * разобрать уведомление шлюза, принимает и преобразует уведомления в массив, если это возможно, расширен в торговых файлы
	 */
	protected function parse_gateway_notification() { }	
	
	/**
	 * Процесс шлюза уведомления, проверяет и решает, что делать с данными шлюза, выданных в торговых файлы
	 */
	public function process_gateway_notification() 
	{	
		if( $this->load_gateway_parameters($this->content_type) ) 
		{ 
			if ( !$this->gateway_system['ipn'] )
				return false;		
			
			$this->parse_gateway_notification();
		}		
		else
			$this->set_error( __('Не удается загрузить данные для уведомления платежа.','usam') ); 		
		exit();			
	}	
		
	// Вывести на странице транзакции
	protected function display_the_page_transaction() {	 }
	
	// Обрабатывает результаты ответа платежного шлюза. Оповещение об успешных или не успешных платежах в автоматическом режиме
	protected function result_transaction( $type_display ) 
	{	
		$message = usam_get_payment_gateway_message( 'fail' );		
		
		if ( isset($this->payment['status']) && $this->payment['status'] == 3 )
			$type_display = 'success'; //Если платеж был оплачен поменять статус.				
		switch( $type_display )
		{
			case 'success':		 // Платеж успешно прошел				
				$message = usam_get_payment_gateway_metadata( $this->gateway_system['id'], 'message_completed' );
			break;					   
			case 'fail':		 // Ошибка оплаты						
				if ( isset($_REQUEST['result']) )
				{ 
					switch( $_REQUEST['result'] )
					{							
						case 3: // Платеж не прошел. Старая транзакция							
							$message = usam_get_payment_gateway_message( 'old_transaction' );		
						break;
						case 4: // Платеж не прошел. Заказ уже оплачен							
							$message = usam_get_payment_gateway_message( 'order_paid' );
						break;
						case 5: // Платеж не прошел. Неактивный платежный шлюз								
							$message = usam_get_payment_gateway_message( 'gateway' );				
						break;							
						case 2: // Платеж не прошел			
						default:
							$message = usam_get_payment_gateway_metadata( $this->gateway_system['id'], 'message_fail' );							
						break;	
					}						
				}
				elseif( empty($this->payment) ) 
					$message = usam_get_payment_gateway_message('unknown_transaction');	
				else
					$message = usam_get_payment_gateway_metadata( $this->gateway_system['id'], 'message_fail' );
			break;
		}
		return $message;
	}
	
	public function load_payment_document( ) 
	{
		$this->payment = usam_get_payment_document($this->payment_number, 'number');		
		if ( !empty($this->payment) )
		{ 
			$this->purchase_log = new USAM_Order( $this->payment['document_id'] );			
			return true;
		}		
		return false;
	}
	
	/**
	 * Загрузить параметры
	 */
	private function load_gateway_parameters( $content_type = '' ) 
	{		
		switch( $content_type ) 
		{
			case 'json':
				$callback = file_get_contents('php://input'); 
				$this->gateway_data = (array)json_decode($callback);		
			break;
			case 'xml':
				$callback = file_get_contents('php://input');
				$this->gateway_data = simplexml_load_string($callback);
			break;	
			case 'post':
				$this->gateway_data = stripslashes_deep($_POST);
			break;
			case 'postget':
				$this->gateway_data = stripslashes_deep($_REQUEST);	
			break;
			case 'get':			
			default:			
				$this->gateway_data = stripslashes_deep($_GET); 
			break;
		}	
		$this->payment_number = $this->get_payment_number(); 		
		if ( !$this->payment_number )
			$this->payment_number = $this->get_standard_payment_number(); 		
		if ( $this->payment_number )
		{				
			if ( $this->load_payment_document() )
				return true;
		}		
		return false;
	}
	
	public function display_transaction_theme( $type_display ) 
	{					
		if( $this->load_gateway_parameters() ) 
		{ 				
			$message = $this->result_transaction( $type_display );							
			// показывать результаты транзакции						
			if ( $this->purchase_log->get('status') == 'incomplete_sale' )
				$this->set_purchase_processed_by_purchid( 'received' );					
			
			$order_shortcode = new USAM_Order_Shortcode( $this->purchase_log );				
			$message = $order_shortcode->get_html( $message );					
		}		
		else
			$message = $this->result_transaction( 'fail' );
		$message .= $this->display_the_page_transaction();
		return $message;				
	}
	
	public function get_form()
	{
		return '';
	}

	protected function notification_errors( $error )
	{	
		switch( $error )
		{								
			case 'transactid':
				$this->set_error( __("Номер транзакции не совпадает с номером в платежном документе","usam") );
			break;
			case 'signature':
				$this->set_error( __("Подпись не совпадает","usam") );
			break;								
			case 'renouncement':
				$this->set_error( __("Отказ в исполнении","usam") );				
			break;
		}
	}
	
	protected function set_error( $error )
	{			
		$this->error = sprintf( __('Платежные шлюзы. Оплата %s. Ошибка: %s'), $this->payment_number, $error );
		$this->set_log_file();
	}
	
	public function set_log_file()
	{
		usam_log_file( $this->error, 'merchant_gateway' );
		$this->error = '';
	}
	
	public function process_success()
	{
		$cart = USAM_CART::instance();		
		$cart->empty_cart( );	
	}	
	
	public function get_button_onclick( $args ) 
	{
		return '';
	}
}

// Подключить платежный класс
function usam_get_merchant_class( $gateway )
{
	if ( !$gateway )
		return false;
	if ( is_numeric($gateway) )
	{
		$gateway = usam_get_payment_gateway( $gateway );
		if ( !$gateway )
			return false;
	}	
	$merchant_class = 'USAM_Merchant';	
	if ( !empty($gateway['handler']) )
	{		
		$file =  USAM_APPLICATION_PATH . '/merchants/'.$gateway['handler'].'.php';
		if ( file_exists($file) )
		{
			require_once( $file );
			$merchant_class = 'USAM_Merchant_'.$gateway['handler'];				
		}		
	}	
	return new $merchant_class( $gateway );	
}

// Обработка завершения транзакции.
function usam_get_transaction_theme( )
{
	global $wp_query;
	
	$display = 'fail';	
	if ( isset($wp_query->query['tabs']) )
		$display = $wp_query->query['tabs'];
	
	$gateway_id = isset($wp_query->query['gateway_id'])?$wp_query->query['gateway_id']:'';					
	ob_start();
	
	$merchant_instance = usam_get_merchant_class( $gateway_id );		
	if ( $merchant_instance ) 
	{
		usam_change_block( admin_url("admin.php?page=orders&tab=orders&table=payment_gateway&view=settings&form=edit&form_name=payment_gateway&id={$gateway_id}#usam_payment_message_completed" ), __("Изменить результаты покупки", "usam") );		
		echo $merchant_instance->display_transaction_theme( $display );	
	}
	else
	{		
		usam_change_block( admin_url( "admin.php?page=orders&tab=orders&view=settings&section=transaction_results" ), __("Изменить результаты покупки", "usam") );
		echo usam_get_payment_gateway_message('unknown_transaction');	
	}
	return ob_get_clean();		
}

// Обратные ответы от платежных шлюзов
function usam_gateway_notification( )
{
	global $wp_query;

	if ( isset($wp_query->query['tabs']) )
	{ 
		$gateway_id = isset($wp_query->query['gateway_id'])?$wp_query->query['gateway_id']:'';							
		if ( is_numeric($gateway_id)  )
		{ 	
			$merchant_instance = usam_get_merchant_class( $gateway_id );	
			if ( $merchant_instance )
			{
				if ( $wp_query->query['tabs'] == 'notification' )
				{ 					
					$merchant_instance->process_gateway_notification( );
				}	
				elseif ( $wp_query->query['tabs'] == 'success' )
				{ 
					$merchant_instance->process_success( );						
				}
			}
		}
	} 
}
add_action('template_redirect','usam_gateway_notification', 22);


// Стандартные сообщения
function usam_get_payment_gateway_message( $key ) 
{		
	$transaction_results = get_option( 'usam_page_transaction_results' );		
	if ( !empty($transaction_results[$key]) )
		$text = $transaction_results[$key];
	else
	{
		$message = usam_get_message_transaction();			
		$text = !empty($message[$key]['text'])?$message[$key]['text']:'';
	}					
	return $text;		
}
?>