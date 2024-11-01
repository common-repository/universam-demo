<?php
/**
 * Name: Оплата через WayForPay
 */
class USAM_Merchant_wayforpay extends USAM_Merchant 
{
	protected $api_version = '2.0';
	protected $type_operation = 'a';
	protected $ipn = true;		
	protected $payment_link = 'https://secure.wayforpay.com/pay/';
	protected $content_type = 'json';
	
	function get_currency_list( ) 
	{
		return ["USD", "EUR", "RUB"];
	}
	
	function get_vars( $aggregate = false ) 
	{					
		$order_data = $this->purchase_log->get_data();
		$params = array(
			'merchantAccount' => $this->gateway_system['login'],
			'merchantAuthType' => 'SimpleSignature',
			'merchantDomainName' => parse_url(get_bloginfo('url'), PHP_URL_HOST),
			'merchantTransactionSecureType' => 'AUTO',
			'language' => mb_strtoupper($this->get_country_code()),
			'returnUrl' => $this->url_return,
			'serviceUrl' => $this->notification_url,
			'orderReference' => $this->payment_number,
			'orderDate' => strtotime($order_data['date_insert']),
			'amount' => $this->payment['sum'],
			'currency' => $this->get_gateway_currency_code(),	
		);	
		if ( usam_is_type_payer_company( $order_data['type_payer'] ) )
		{
			$params['clientEmail'] = usam_get_order_metadata( $order_data['id'], 'company_email' );
			$params['clientPhone'] = usam_get_order_metadata( $order_data['id'], 'company_phone' );	
		}
		else
		{
			$params['clientEmail'] = usam_get_order_metadata( $order_data['id'], 'billingemail' );	
			$params['clientPhone'] = usam_get_order_metadata( $order_data['id'], 'billingphone' );
		}		
		return $params;
	}	
	
	public function submit( ) 
	{
		if( !$this->gateway_system['login'] || !$this->gateway_system['pass'] )
		{
			return false;
		}	
		$vars = $this->get_vars();
		$order_data = $this->purchase_log->get_data();
		$products = usam_get_products_order( $order_data['id'] );	
		$keys = array('merchantAccount', 'merchantDomainName', 'orderReference', 'orderDate', 'amount', 'currency');
		$string = array();
		foreach( $keys as $key ) 
		{
			if ( isset($vars[$key]) )
				$string[] = $vars[$key];
		}		
		foreach($products as $product)
		{
			$string[] = $product->name;
		}
		foreach($products as $product)
		{
			$string[] = round($product->quantity, 3);
		}
		foreach($products as $product)
		{
			$string[] = $product->price;
		}		
		$string = implode(';', $string);						
		$vars['merchantSignature'] = hash_hmac("md5", $string, $this->gateway_system['pass'] );
		header("HTTP/1.1 200 OK");
		header("Content-Type: text/html; charset=utf-8");		
		?>
		<form method="post" action="<?php echo $this->payment_link; ?>" accept-charset="utf-8" id="form_merchant" style="display:none">
			<?php
			foreach($vars as $key => $value)
			{
				?><input name="<?php echo $key; ?>" value="<?php echo $value; ?>"><?php				
			}
			foreach($products as $product)
			{
				?><input name="productName[]" value="<?php echo $product->name; ?>"><?php
			}
			foreach($products as $product)
			{
				?><input name="productPrice[]" value="<?php echo $product->price; ?>"><?php
			}
			foreach($products as $product)
			{
				?><input name="productCount[]" value="<?php echo round($product->quantity, 3); ?>"><?php
			}
			?>
		</form>
		<script>
			document.getElementById('form_merchant').submit();
		</script>
		<?php
		exit;
	}
		
	/**
	 * Получить номер платежного документа
	 */
	protected function get_payment_number( ) 
	{ 
		if (isset($this->gateway_data['orderReference']))
			return $this->gateway_data['orderReference'];
		else
			return false;
	}

	/**
	 * Процесс шлюза уведомления, проверяет и решает, что делать с данными шлюза, выданных в торговых файлы
    */
	protected function parse_gateway_notification() 
	{ 	
		$keys = array('merchantAccount', 'orderReference', 'amount', 'currency', 'authCode', 'cardPan', 'transactionStatus', 'reasonCode');
		$string = array();
		foreach( $keys as $key ) 
		{
			if ( isset($this->gateway_data[$key]) )
				$string[] = $this->gateway_data[$key];
		}		
		$string = implode(';', $string);		
		if ( $this->gateway_data['merchantSignature'] == hash_hmac("md5", $string, $this->gateway_system['pass']) )
		{				
			$keys = array('orderReference', 'status');
			$string = array();
			foreach( $keys as $key ) 
			{
				if ( isset($this->gateway_data[$key]) )
					$string[] = $this->gateway_data[$key];
			}		
			$time = time();
			$string[] = $time;
			$string = implode(';', $string);			
			$received_values = array('orderReference' => $this->gateway_data['orderReference'], 'status' => $this->gateway_data['status'], 'time' => $time, 'signature' => hash_hmac("md5", $string, $this->gateway_system['pass']));			
			$options = array('timeout' => 20, 'body' => $received_values );
			$response = wp_remote_post($this->payment_link, $options);
			if ( is_wp_error($response) ) 
			{
				$this->set_error( __("Ошибка wp_remote_post","usam") );
				return false;			
			}			
			if ( isset($response['transactionStatus']) )
			{
				$status = 0;
				switch ( $response['transactionStatus'] ) 
				{
					case 'InProcessing' : //В обработке
						$status = 7;
					break;
					case 'WaitingAuthComplete' : //Заказ ожидает подтверждение списания средств
						$status = 6;
					break;	
					case 'Approved' : // платеж успешно завершен,
						$status = 3;
					break;
					case 'Pending' : //На проверке Antifraud
						$status = 7;
					break;	
					case 'Expired' : //Истек срок оплаты
						$status = 2;
					break;					
					case 'Declined' : //  Отклонен
						$status = 5;
					break;
				}			
				if ( $status )
					$this->update_payment_document(['status' => $status]);	
			}				
		}
		else
			$this->notification_errors( 'signature' );			
	}
	
	protected function get_default_option( ) 
	{
		return array( 'login' => '', 'pass' => '');
	}
	
	public function get_form()
	{	
		$output = "
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_login'>Логин:</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' size='40' id ='option_gateway_login' value='".$this->gateway_system['login']."' name='gateway_handler[login]' />
			</div>
		</div>
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_pass'>".esc_html__('Секретный ключ', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' id='option_gateway_pass' size='40' value='".$this->gateway_system['pass']."' name='gateway_handler[pass]' />
			</div>
		</div>";			
		return $output;
	}
}
?>