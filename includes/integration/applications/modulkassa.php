<?php
/**
 	Name: Модуль Касса
	Description: Отправка чеков в Модуль Касса
	Group: cashbox
	Price: paid
	Icon: modulkassa
	Url: https://modulkassa.ru
 */
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_modulkassa extends USAM_Application
{	
	protected $version = '1';	
	protected $API_URL = "https://service.modulpos.ru/api/fn";		
	protected $test_url = "https://demo-fn.avanpos.com/fn";		
	
	//Инициализация (связка) интернет-магазина с розничной точкой
	private function get_associate( )
	{ 	
		$result = true;		
		if ( empty($this->option['login2']) )
		{
			$result = false;
			$point = !empty($this->option['point-uuid'])?$this->option['point-uuid']:'';
			if ( $point )
			{			
			//	$headers["Accept"] = 'application/json';
			//	$headers["Content-type"] = 'application/json';	
				$headers["Authorization"] = 'Basic '.base64_encode($this->option['login'].':'.$this->option['pass']);				
				$args = array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking' => true,
					'headers' => $headers,
				);							
				$result = $this->send_request( "associate/{$point}", $args );	
				if ( !empty($result['userName']) )
				{
					$this->option['login2'] = $result['userName'];
					$this->option['pass2'] = $result['password'];
					usam_update_application_metadata($this->id, 'login2', $result['userName']);
					usam_update_application_metadata($this->id, 'pass2', $result['password']);											
					$result = true;
				}				
			}			
		} 				
		return $result;
	}	
	
	public function preparation_request( $params ) 
	{
		$headers["Authorization"] = 'Basic '.base64_encode($this->option['login2'].':'.$this->option['pass2']);			
		$headers["Cache-Control"] = 'no-cache';	
		$headers["Content-type"] = 'application/json';			
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => json_encode($params),
		);	
		$result = $this->send_request( "doc", $args );		
		return $result;
	}		
		
	protected function get_data_refund( $id )
	{			
		$document = usam_get_document( $id );
		$products = usam_get_products_document( $id );				
	
		$data = array( 
			'id' => mt_rand( 100, 999 ) . time(), 
			'checkoutDateTime' => usam_local_date($document['date_insert'],'c'), 
			'docNum' => $document['id'], 
			'docType' => "RETURN",
			'printReceipt' => false,
			'userName' => !empty($this->option['login2'])?$this->option['login2']:'',
			'password' => !empty($this->option['pass2'])?$this->option['pass2']:'',
			'responseURL' => '',			//URL для подтверждения успешной фискализации на стороне Интернет-магазина
		);		
		$data['inventPositions'] = array();
		foreach ( $products as $product ) 
		{
			$sum = $product->price * $product->quantity;
			$discount = $product->old_price - $product->price;
			$barcode = usam_get_product_meta( $product->product_id, 'barcode' );			
			$data['inventPositions'][] = array( 				
				"barcode" => $barcode,
				"discSum" => $discount,
				"name" => $product->name,
				"vatTag" => $this->option['nds'],				
				"price" => usam_string_to_float( $product->price ),			
			);
		}
		require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
		$payments = usam_get_payments(['order' => 'DESC', 'document_id' => $document['id'], 'status' => 3]);	
		$data['moneyPositions'] = array();
		foreach ( $payments as $payment ) 
		{
			$payment_type = $payment->payment_type == 'card'?'CARD':'CASH';	
			$data['moneyPositions'][] = ["paymentType" => $payment_type, "sum" => $payment->sum];
		}
	//	$data['phone'] = 
	//	$data['email'] = 
		return $data;
	}		
	
	protected function get_data_sell( $order_id )
	{
		$order_data = usam_get_order( $order_id );
		$order_products = usam_get_products_order( $order_id );	
		
		$data = [ 
			'id' => mt_rand( 100, 999 ) . time(), 
			'checkoutDateTime' => usam_local_date($order_data['date_insert'],'c'), 
			'docNum' => $order_id, 
			'docType' => "SALE",
			'printReceipt' => false,		
			'responseURL' => '',			//URL для подтверждения успешной фискализации на стороне Интернет-магазина
		];
		$data['inventPositions'] = [];
		foreach ( $order_products as $product ) 
		{
			$sum = $product->price * $product->quantity;
			$discount = $product->old_price - $product->price;
			$barcode = usam_get_product_meta( $product->product_id, 'barcode' );			
			$data['inventPositions'][] = [ 				
				"barcode" => $barcode,
				"discSum" => $discount,
				"name" => $product->name,
				"vatTag" => $this->option['nds'],				
				"price" => usam_string_to_float( $product->price ),
				"quantity" => $product->quantity,
			];
		}	
		require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
		$payments = usam_get_payments(['order' => 'DESC', 'document_id' => $order_id, 'status' => 3]);	
		$data['moneyPositions'] = [];
		foreach ( $payments as $payment ) 
		{
			$gateway = usam_get_payment_gateway( $payment->gateway_id );		
			$payment_type = $gateway['handler'] == 'payment_on_receipt'?'CASH':'CARD';			
			$data['moneyPositions'][] = ["paymentType" => $payment_type, "sum" => $payment->sum];
		}		
		if ( usam_is_type_payer_company( $order_data['type_payer'] ) )
		{
			$email = usam_get_order_metadata( $order_id, 'company_email' );
			$phone = usam_get_order_metadata( $order_id, 'company_phone' );
		}
		else
		{
			$email = usam_get_order_metadata( $order_id, 'billingemail' );
			$phone = usam_get_order_metadata( $order_id, 'billingphone' );			
		}		
		if ( $email )
			$data['email'] = $email;
		elseif ( $phone ) 
			$data['phone'] = $phone;	
		return $data;
	}
	
	protected function get_send_data( $data, $payment_type )
	{		
		return $data;
	}
	
	protected function get_default_option( ) 
	{
		return ['login2' => '', 'pass2' => '', 'nds' => '1107', 'point-uuid' => ''];
	}
		
	public function display_form() 
	{	
		?>			
		<div class="edit_form" > 
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_login'><?php esc_html_e( 'Логин', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_login' name="login" value="<?php echo $this->option['login']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_pass'><?php esc_html_e( 'Пароль', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option"><?php usam_get_password_input( $this->option['password'], ['name' => 'password', 'id' => 'option_pass']); ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_login'><?php esc_html_e( 'Номер торговой точки', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_login' name="point-uuid" value="<?php echo $this->option['point-uuid']; ?>">
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_nds'><?php esc_html_e( 'Тег НДС согласно ФЗ-54', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_nds' name="nds" value="<?php echo $this->option['nds']; ?>">
				</div>
			</div>	
		</div>
		<?php
	}
	
	public function save_form( ) 
	{ 
		$metas = [];				
		$metas['point-uuid'] = isset($_POST['point-uuid'])?sanitize_text_field($_POST['point-uuid']):'';		
		$metas['nds'] = isset($_POST['nds'])?sanitize_text_field($_POST['nds']):'';		
		foreach( $metas as $meta_key => $meta_value)
		{	
			usam_update_application_metadata($this->id, $meta_key, $meta_value);
		}	
	}
	
	public function order_paid( $purchase_log ) 
	{
		$order_id = $purchase_log->get( 'id' );
		if ( !$this->get_associate( ) )
			return false;		
			
		$params = $this->get_data_sell( $order_id );						
		$this->preparation_request( $params );
	}
	
	public function sell_refund( $document_id, $current_status, $previous_status, $t ) 
	{
		if ( $t->get('type') == 'check_return' && $current_status == 'approved' )
		{
			if ( !$this->get_associate( ) )
				return false;		
			
			$params = $this->get_data_refund( $document_id );
			$this->preparation_request( $params );
		}
	}
	
	public function service_load( ) 
	{
		add_action( 'usam_order_paid', [$this, 'order_paid']);	
		add_action( 'usam_update_document_status', [$this, 'sell_refund'], 10, 4 );
	}
}