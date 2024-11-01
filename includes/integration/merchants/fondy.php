<?php 
/*
  Name: Модуль оплаты FONDY‎
 */
class USAM_Merchant_fondy extends USAM_Merchant 
{	
	protected $api_version = '2.0';
	protected $type_operation = 'a';
	protected $content_type = 'json';	
	protected $ipn = true;
	protected $payment_link = 'https://api.fondy.eu/api/checkout/url/';	
	
	private function getSignature( $params = array() )
	{
		$params = array_filter($params,'strlen');
		ksort($params);		
		$params = array_values($params);
		array_unshift( $params , $this->gateway_system['key'] );
		$params = join('|',$params);	
		return mb_strtolower(sha1($params));
	}
	
	protected function get_parameters( )
	{			
		$amount = (int)$this->payment['sum']*100;
		$order_id = $this->purchase_log->get('id'); 
		$params = array(
			'order_id' => $this->payment_number,		
			'merchant_id' => $this->gateway_system['login'],	
			'order_desc' => sprintf( __("Оплата заказа №%s","usam"), $order_id),	
			'amount' => $amount,		
			'currency' => $this->get_gateway_currency_code(),	
			'response_url' => $this->url_return,			
			'server_callback_url' => $this->notification_url
		);	
		return $params;
	}
	
	protected function get_url( )
	{				
		$params = $this->get_parameters();
		$params['signature'] = $this->getSignature( $params );	
		$args = array(
			'redirection' => 2,
			'user-agent' => 'UNIVERSAM',
            'headers' => array("Content-type" => "application/json;charset=UTF-8"),
			'body' => json_encode(array('request' => $params)),
		);							
		$result = $this->send_request( $this->payment_link, $args );		
		if ( !empty($result['response']) )
		{
			if ( $result['response']['response_status'] == 'success' )
			{
				$this->update_payment_document( array( 'transactid' => $result['response']['payment_id'] ) );	
				return $result['response']['checkout_url'];
			}
			elseif ( !empty($result['response']['error_message']) )
			{
				$this->set_error( $result['response']['error_message'] );				
			}
		}
		return $this->url_cancel_return;	
	}
			
	/**
	 * Получить номер платежного документа
	 */
	protected function get_payment_number( ) 
	{ 
		if (isset($this->gateway_data['order_id']))
			return $this->gateway_data['order_id'];
		else
			return false;
	}
	
	/**
	 * Процесс шлюза уведомления, проверяет и решает, что делать с данными шлюза, выданных в торговых файлы
	*/
	protected function parse_gateway_notification() 
	{ 		
		$payment = usam_get_payment_document( $this->payment_number, 'number');	
		
		$transact_id = $this->gateway_data['payment_id'];	
		if ( $transact_id != $payment['transactid'] )
		{		
			$this->notification_errors( 'transactid' );
			return false;				
		}			
		$signature = $this->gateway_data['signature'];	
		if ( isset($this->gateway_data['response_signature_string']) )
			 unset($this->gateway_data['response_signature_string']);
		if ( isset($this->gateway_data['signature']) )
			 unset($this->gateway_data['signature']);			
		
		if ( $signature != $this->getSignature( $this->gateway_data ) )
		{		
			$this->notification_errors( 'signature' );
			return false;		
		}						   
		$payment = array();			
		switch ( strtolower( $this->gateway_data['order_status'] ) ) 
		{
			case 'created':   //заказ был создан, но клиент еще не ввел платежные реквизиты; необходимо продолжать опрашивать статус заказа
			case 'processing': //заказ все еще находится в процессе обработки платежным шлюзом; необходимо продолжать опрашивать статус заказа
				$status = 6; // В ожидании
			break;			
			case 'approved': //  заказ успешно совершен, средства заблокированы на счету плательщика и вскоре будут зачислены мерчанту; мерчант может оказывать услугу или “отгружать” товар
				$payment['date_payed'] = date("Y-m-d H:i:s");
				$status = 3;
			break;				
			case 'reversed': // оплата не удалось
				$status = 2;
			break;
			case 'expired': //время жизни заказа, указанное в параметре lifetime, истекло.
				$status = 5;
			break;			
			case 'declined':   //заказ отклонен платежным шлюзом FONDY, внешней платежной системой или банком-эквайером
				$status = 2;  // оплата была отклонена
			break;			
		}
		$payment['status'] = $status;
		$this->update_payment_document( $payment );	
		header("HTTP/1.1 200 OK");
	}
	
	protected function get_default_option( ) 
	{
		return array( 'login' => '', 'key' => '');
	}
	
	public function get_form()
	{
		$output = "
			<div class ='edit_form__item'>
				<div class ='edit_form__item_name'><label for='option_gateway_login'>".esc_html__('Merchant ID', 'usam').":</label></div>
				<div class ='edit_form__item_option'>
					<input type='text' id='option_gateway_login' size='40' value='".$this->gateway_system['login']."' name='gateway_handler[login]'>
				</div>
			</div>
			<div class ='edit_form__item'>
				<div class ='edit_form__item_name'><label for='option_gateway_key'>".esc_html__('Пароль', 'usam').":</label></div>
				<div class ='edit_form__item_option'>
					<input type='text' id='option_gateway_key' size='40' value='".$this->gateway_system['key']."' name='gateway_handler[key]'>
				</div>
			</div>";
		return $output;
	}
}
?>