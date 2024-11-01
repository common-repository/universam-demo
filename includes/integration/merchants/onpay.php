<?php
/**
	Name: Оплата через шлюз "Onpay"
*/
class USAM_Merchant_onpay extends USAM_Merchant 
{	
	protected $api_version = '2.0';
	protected $type_operation = 'c';
	protected $ipn = true;
	protected $payment_link = 'https://secure.onpay.ru/pay/';
	
	function get_currency_list( ) 
	{
		return array('USD', "RUB");
	}
	
	protected function get_url( ) 
	{		
		return $this->payment_link.$this->gateway_system['login'];	
	}
	
	function to_float($sum) 
	{
		if ( strpos($sum, ".") )
			$sum = round($sum, 2);
		else 
			$sum = $sum . ".0";    
		return $sum;
	}

	// передает полученные данные в платежный шлюз
	function get_vars( $aggregate = false ) 
	{	
		$sum_for_md5 = $this->to_float( $this->payment['sum'] );	
		$crc = "fix;$sum_for_md5;".$this->get_gateway_currency_code().";".$this->payment_number.";yes;".$this->gateway_system['key'];
		
		$onpay_ap = "onpay".$this->gateway_system['key'];
		
		$args = array(			
			'price' => $this->payment['sum'],			
			'pay_for' => $this->payment_number,		
			'url_success_enc' => base64_encode($this->url_return),
			'url_fail_enc' => base64_encode($this->url_cancel_return),
			'pay_mode' => 'fix',
			'md5' => md5($crc),
			'user_email' => $this->get_customer_data_by_type( 'email' ),
			'currency' => $this->get_gateway_currency_code(),
			'onpay_api_gateway'  => 'onpay',
			'onpay_api_signature' => sha1($onpay_ap),
		);	
		return $args;
	}	
	
	/**
	 * Получить номер платежного документа
	 */
	protected function get_payment_number( ) 
	{ 
		if (isset($this->gateway_data['pay_for']))
			return $this->gateway_data['pay_for'];
		else
			return false;
	}
	
	/**
	 * Процесс уведомления магазина от торгового шлюза. Проверяет данные и возвращает ответ.
	 
    [gateway] => onpay
    [type] => pay
    [onpay_id] => 19138768
    [amount] => 100.0
    [balance_amount] => 100.0
    [balance_currency] => WMB
    [order_amount] => 100.0
    [order_currency] => WMB
    [exchange_rate] => 1.0
    [pay_for] => 215
    [paymentDateTime] => 2016-02-09T17:37:38+03:00
    [user_phone] => 
    [paid_amount] => 100.0
    [md5] => 5B60FDF8A9914EF9BA335C7F177F3922
	
	*/
	protected function parse_gateway_notification() 
	{	
		$onpay_key      = $this->gateway_system['key'];
		$order_amount   = $this->gateway_data['order_amount'];
		$onpay_id       = $this->gateway_data['onpay_id'];
		$md5            = $this->gateway_data['md5'];	
		$order_currency = $this->get_gateway_currency_code();
		
		$md5fb = strtoupper(md5($this->gateway_data['type'].";".$this->payment_number.";".$onpay_id.";".$order_amount.";".$order_currency.";".$onpay_key.""));
		switch ($this->gateway_data['type']) 
		{
			case 'check':
				if ($md5fb == $md5) 
				{  //Отвечаем серверу OnPay, что все хорошо, можно принимать деньги					
					$code = 0;
					$text = 'OK';	
				} 
				else 	
				{
					$this->notification_errors( 'signature' );
					return false;			
				}
				echo( $this->answer($this->gateway_data['type'], $code, $order_amount, $text, $onpay_key) ); 		
			break;
			case 'pay':				
				if ($md5fb == $md5) 
				{
					$payment['status']     = 3;			
					$payment['transactid'] = $onpay_id;     // номер документа в платежной системе					
					$this->update_payment_document( $payment );						
					$code = 0;
					$text = 'OK';					
				} 
				else 
				{
					$this->notification_errors( 'signature' );
					return false;				
				}
				echo($this->answerpay($this->gateway_data['type'], $code, $order_amount, $text, $onpay_id, $onpay_key));
			break;
			default:
				$this->set_error( __("Проблема с транзакцией! Нет check или pay","usam") );				
			break;
		}
    }
	
	public function get_form() 
	{
		$output = "
			<div class ='edit_form__item'>
				<div class ='edit_form__item_name'><label for='option_gateway_login'>".esc_html__('Логин', 'usam').":</label></div>
				<div class ='edit_form__item_option'>
					<input type='text' id='option_gateway_login' size='40' value='".$this->gateway_system['login']."' name='gateway_handler[login]' />
					<p class='description'>".__('Ваш логин в платежном агрегаторе Onpay.', 'usam')."</p>
				</div>
			</div>
			<div class ='edit_form__item'>
				<div class ='edit_form__item_name'><label for='option_gateway_pass'>".esc_html__('Ключ API', 'usam').":</label></div>
				<div class ='edit_form__item_option'>
					<input type='text' id='option_gateway_pass' size='40' value='".$this->gateway_system['key']."' name='gateway_handler[key]' />
					<p class='description'>".__('Такой же как и в настройках магазина на сайте Onpay.ru. Должен содержать не менее 10 символов.', 'usam')."</p>
				</div>
			</div>";
		return $output;
	}
	
	private function answer($type, $code, $order_amount, $text, $key)
	{
		$order_currency = $this->get_gateway_currency_code();
		$md5 = strtoupper(md5("$type;$this->payment_number;$order_amount;$order_currency;$code;$key"));
		return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result>\n<code>$code</code>\n<pay_for>$pay_for</pay_for>\n<comment>$text</comment>\n<md5>$md5</md5>\n</result>";
	}

	private function answerpay($type, $code, $order_amount, $text, $onpay_id, $key) 
	{
		$order_currency = $this->get_gateway_currency_code();
		$md5 = strtoupper(md5("$type;$this->payment_number;$onpay_id;$pay_for;$order_amount;$order_currency;$code;$key"));
		return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result>\n<code>$code</code>\n <comment>$text</comment>\n<onpay_id>$onpay_id</onpay_id>\n <pay_for>$pay_for</pay_for>\n<order_id>$pay_for</order_id>\n<md5>$md5</md5>\n</result>";
	}
}
?>