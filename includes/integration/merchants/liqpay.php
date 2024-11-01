<?php 
/*
  Name: Модуль оплаты LiqPay‎
 */
class USAM_Merchant_liqpay extends USAM_Merchant 
{	
	protected $api_version = '3';
	protected $type_operation = 'a';
	protected $ipn = true;		
	protected $payment_link = 'https://www.liqpay.ua/api/request';
	protected $content_type = 'post';
	
	function get_currency_list( ) 
	{
		return ["USD", "EUR", "RUB", "UAH", "BYN", "KZT"];
	}
	
	function get_vars( $aggregate = false ) 
	{			
		$order_data = $this->purchase_log->get_data();
		$params = array(
			'version' => $this->api_version,			
			'public_key' => $this->gateway_system['public_key'],
			'private_key' => $this->gateway_system['private_key'],			
			'action' => 'pay', //pay - платеж, hold - блокировка средств на счету отправителя, subscribe - регулярный платеж, paydonate - пожертвование, auth - предавторизация карты
			'amount' => $this->payment['sum'],
			'currency' => $this->get_gateway_currency_code(),	
			'description' => sprintf( __("Оплата заказа №%s","usam"), $order_data['id']),	
			'order_id' => $this->payment_number,
			'result_url' => $this->url_return,
			'server_url' => $this->notification_url,		
		);			
		$pay_up = usam_get_order_metadata($order_data['id'], 'date_pay_up' );
		if ( $pay_up )
			$params['expired_date'] = date("Y-m-d H:i:s", strtotime($pay_up));		
		
		$language = usam_get_contact_language();
		if ( $language )
			$params['language'] = $language;
		return $params;
	}	
	
	private function get_signature( $data )
	{ 
		return base64_encode( sha1($this->gateway_system['private_key']+$data+$this->gateway_system['private_key']));
	}
	
	public function submit( ) 
	{
		if( !$this->gateway_system['public_key'] || !$this->gateway_system['private_key'] )
		{
			return false;
		}	
		$data = base64_encode( json_encode( $this->get_vars() ) );
		$signature = $this->get_signature( $data );
		header("HTTP/1.1 200 OK");
		header("Content-Type: text/html; charset=utf-8");		
		?>
		<form method="post" action="https://www.liqpay.ua/api/3/checkout" accept-charset="utf-8" id="form_merchant" style="display:none">			
			<input type="hidden" name="data" value="<?php echo $data; ?>"/>
			<input type="hidden" name="signature" value="<?php echo $signature; ?>"/>
			<input type="image" src="//static.liqpay.ua/buttons/p1ru.radius.png"/>
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
		$signature = $this->get_signature( $this->gateway_data['data'] ); //проверить подпись
		if ( $this->gateway_data['signature'] == $signature )
		{		
			$data = base64_encode( json_encode(['action' => "status", 'version' => $this->api_version, 'public_key' => $this->gateway_system['public_key'], 'order_id' => $this->payment_number]) );
			$signature = $this->get_signature( $data ); //сделать новую подпись
			$response = wp_remote_post($this->payment_link, ['timeout' => 20, 'body' => ['data' => $data, 'signature' => $signature]]);
			if ( is_wp_error( $response ) ) 
			{
				$this->set_error( __("Ошибка wp_remote_post","usam") );
				return false;			
			}			
			if ( isset($response['status']) )
			{
				$status = 0;
				switch ( $response['status'] ) 
				{
					case 'processing' : // Платеж обрабатывается
						$status = 7;
					break;
					case 'captcha_verify' : // Ожидается подтверждение captcha
					case 'ivr_verify' : // Ожидается подтверждение звонком ivr
					case 'cash_wait' : // Заказ ожидает подтверждение списания средств
					case 'subscribed' : // Подписка успешно оформлена			
						$status = 6;
					break;						
					case 'wait_compensation' :
					case 'success' : // Успешный платеж
						$status = 3;
					break;
					case 'wait_secure' : // Платеж на проверке
						$status = 7;
					break;					
					case 'try_again' : // Оплата неуспешна. Клиент может повторить попытку еще раз
					case 'failure' : // Неуспешный платеж
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
		return ['public_key' => '', 'private_key' => ''];
	}
	
	public function get_form()
	{	
		$output = "
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_public_key'>".esc_html__('Публичный ключ', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' id='option_public_key' size='40' value='".$this->gateway_system['public_key']."' name='gateway_handler[public_key]' />
			</div>
		</div>
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_private_key'>".esc_html__('Приватный ключ', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' id='option_private_key' size='40' value='".$this->gateway_system['private_key']."' name='gateway_handler[private_key]' />
			</div>
		</div>";			
		return $output;
	}
}
?>