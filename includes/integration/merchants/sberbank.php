<?php
/**
 * Name: Оплата через Сбербанк
 */
class USAM_Merchant_sberbank extends USAM_Merchant 
{
	protected $content_type = 'postget';
	protected $api_version = '2.0';
	protected $type_operation = 'a';
	protected $ipn = true;		
	protected $user_account_url = 'https://securepayments.sberbank.ru/mportal3/login';
		
	function get_currency_list( ) 
	{
		return array("RUB", "USD");
	}
	
	protected function get_default_option( ) 
	{
		return ['login' => '', 'pass' => '', 'url' => 1, 'tax_type' => 0, 'pass_notification' => '', 'payment_method' => 1];
	}
			
	protected function get_payment_link_sberbank( $order_id )
	{		
		if ( !empty($this->gateway_system['url']) )
			$url = "https://securepayments.sberbank.ru/payment/merchants/sbersafe/payment_ru.html";				
		else
			$url = "https://3dsec.sberbank.ru/payment/merchants/sbersafe/payment_ru.html";		
		
		$url = "$url?mdOrder=$order_id";
		return $url;
	}
	
	protected function get_url_sberbank( $function )
	{		
		if ( !empty($this->gateway_system['url']) )
			$url = "https://securepayments.sberbank.ru/payment/rest";				
		else
			$url = "https://3dsec.sberbank.ru/payment/rest";
		
		$url = "$url/$function.do";
		return $url;
	}	
	
	protected function get_payment_status( )
	{	
		if ( !empty($this->payment['transactid']) )
		{				
			$args = $this->get_request_args(['orderId' => $this->payment['transactid']]);
			$result = $this->send_request( $this->get_url_sberbank('getOrderStatus'), $args );
			return $result;		
		}
		return false;
	}
	
	protected function get_url( )
	{ 
		$currency_code = $this->get_gateway_currency_code();	
		$currency = usam_get_currency( $currency_code );
		$headers["Content-Type"] = 'application/x-www-form-urlencoded';		
		if ( !empty($this->payment['transactid']) )
		{	
			$result = $this->get_payment_status();		
			if ( !empty($result['OrderStatus']) )
				return $this->get_payment_link_sberbank( $this->payment['transactid'] );	
		}
		$order_id = $this->purchase_log->get('id');
		$order_data = $this->purchase_log->get_data();
		
		$orderBundle = ['orderCreationDate' => strtotime($order_data['date_insert'])];
		$order_products = usam_get_products_order( $order_data['id'] );
		$shipping_documents = usam_get_shipping_documents_order( $order_data['id'] );
		$product_marking_codes = [];
		require_once(USAM_FILE_PATH.'/includes/product/marking_codes_query.class.php');
		foreach ( $shipping_documents as $shipping_document ) 
		{
			$marking_codes = usam_get_marking_codes(['document_id' => $shipping_document->id]);
			foreach ( $marking_codes as $marking_code ) 
			{
				$product_marking_codes[$marking_code->product_id][] = ['name' => 'nomenclature', 'value' => $marking_code->code];
			}
		}		
		$taxes = usam_get_order_product_taxes( $order_id ); 
		$amount = 0;
		usort($order_products, function($a, $b){  return ($a->price - $b->price); });
		$remainder_tax = 0;	
		$count = count($order_products)-1;
		foreach ( $order_products as $key => $product ) 
		{
			$sum = round($product->price*100*$product->quantity);	
			$totaltax = 0;
			foreach( $taxes as $product_tax )
			{
				if ($product->product_id == $product_tax->product_id && $product->unit_measure == $product_tax->unit_measure)
				{
					$totaltax = $product_tax->tax*100*$product->quantity;
					break;
				}
			}
			if ( $count == $key )
				$totaltax_sberbank = round($totaltax+$remainder_tax);
			else
			{
				$totaltax_sberbank = round($totaltax);
				$remainder_tax = $remainder_tax + ($totaltax - $totaltax_sberbank);
			}			
			$payment_object = 1;
			if ( usam_check_type_product_sold( 'product' ) )
				$payment_object = 1;
			elseif ( usam_check_type_product_sold( 'service' ) )
				$payment_object = 4;
				
			$itemAttributes = array( array('name' => 'paymentMethod', 'value' => $this->gateway_system['payment_method']), array('name' => 'paymentObject', 'value' => $payment_object) );
			if ( !empty($product_marking_codes[$product->product_id]) )
			{				
				$i = 1;
				foreach ( $product_marking_codes[$product->product_id] as $code )
				{
					$itemAttributes[] = $code;					
					if( $i == $product->quantity ) 
						break;
					$i++;
				}
			}
			$orderBundle['cartItems']['items'][] = array( 					
				"positionId" => $product->id,
				"name" => $product->name,
				"quantity" => ['value' => $product->quantity, 'measure' => usam_get_product_unit_name( $product->product_id )],
				"itemAmount" => $sum,
				"itemCode" => $product->product_id,
				"itemPrice" => round($product->price*100),
				"tax" => ['taxType' => $this->gateway_system['tax_type'], 'taxSum' => $totaltax_sberbank],	
				"itemAttributes" => array( 'attributes' => $itemAttributes )
			);
			$amount += $sum;
		}		
		require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
		$documents = usam_get_shipping_documents(['order' => 'DESC', 'order_id' => $order_id, 'include_in_cost' => 1]);			
		foreach ( $documents as $document ) 
		{					
			if ( $document->price != '0.00' )
			{
				$sum = round($document->price*100);	
				$orderBundle['cartItems']['items'][] = [					
					"positionId" => 1,
					"name" => __('Оплата за услуги по доставке','usam'),
					"quantity" => ['value' => 1, 'measure' => __('услуга','usam')],
					"itemAmount" => $sum,
					"itemCode" => 'service-'.$document->method,
					"itemPrice" => $sum,
					"tax" => ['taxType' => $this->gateway_system['tax_type'], 'taxSum' => round($document->tax_value,2)*100],	
					"itemAttributes" => ['attributes' => [['name' => 'paymentMethod', 'value' => $this->gateway_system['payment_method']], ['name' => 'paymentObject', 'value' => 4]]]			
				];		
				$amount += $sum;
			}				
		}		
		$email = (string)usam_get_order_customerdata( $order_data['id'], 'email' );
		$phone = (string)usam_get_order_customerdata( $order_data['id'], 'mobile_phone' );			
		if ( $email )
			$orderBundle['customerDetails']['email'] = $email;
		elseif ( $phone ) 
			$orderBundle['customerDetails']['phone'] = $phone;		
		$params = [			
			'amount' => $amount,
			'currency' => $currency['numerical'],
			'orderNumber' => $this->payment_number,						
			'returnUrl' => $this->url_return, // при удачном платеже
			'failUrl' => $this->url_cancel_return,// URL-адрес, к которому PayPal перенаправляет браузер покупателя, если они отменить проверку до завершения их выплаты.
			'pageView' => 'DESKTOP', //DESKTOP – для отображения на экранах ПК  MOBILE – для отображения на экранах мобильных устройств		
			'description' => sprintf( __("Оплата заказа %s"), $order_id ),		
			'orderBundle' => json_encode($orderBundle),
		];
		$args = $this->get_request_args( $params );
		$result = $this->send_request( $this->get_url_sberbank('register'), $args );		
		if ( isset($result['errorCode']) )
			$this->set_error( $result['errorMessage'] );
		if ( is_array($result) && !empty($result['formUrl']) )
		{			
			$payment['transactid'] = $result['orderId'];
			$this->update_payment_document( $payment );				
			return $result['formUrl'];
		}
		else
			return $this->url_cancel_return;
	}	
	
	/**
	 * Получить номер платежного документа
	 */
	protected function get_payment_number( ) 
	{  		
		if (isset($this->gateway_data['orderNumber']))
			return $this->gateway_data['orderNumber'];
		else
			return false;
	}
	
	/**
	 * Процесс уведомления из торгового шлюза. Проверяет данные и возвращает ответ.
	 
approved - операция удержания (холдирования) суммы;
deposited - операция завершения;
reversed - операция отмены;
refunded - операция возврата.

checksum

Возвращает
[gateway] => sberbank
[orderNumber] => PH0000002675
[mdOrder] => 34f1998c-e7df-7718-34f1-998c00148666
[operation] => deposited
[status] => 1
*/
	protected function parse_gateway_notification() 
	{			
		if ( isset($this->gateway_data['status']) && isset($this->gateway_data['operation']) )
		{				
			if ( $this->gateway_system['pass_notification'] != '' )
			{			
				$parameters = $_REQUEST;		
				unset($parameters['checksum']);
				if ( isset($parameters['sign_alias']) )
					unset($parameters['sign_alias']);			
				ksort($parameters);				
				$str = '';
				foreach ( $parameters as $key => $value)
					$str .= $key.';'.$value.';';	
					
				$hmac = strtoupper(hash_hmac( 'sha256', $str, $this->gateway_system['pass_notification']));					
				if ( $hmac != $this->gateway_data['checksum'] )
				{
					$this->set_error( __('Проверка подлинности соединения не пройдена.','usam') ); 
					return false;
				}
			} 
			$transact_id = $this->gateway_data['mdOrder'];		
			if ( $transact_id != $this->payment['transactid'] )
			{		
				$this->set_error(  __("Номер транзакции не совпадает с номером в платежном документе","usam").' '.$transact_id.' != '.$this->payment['transactid'] ); 				
				$this->notification_errors( 'transactid' );
				return false;				
			}			
			$status = 0;
			if ( $this->gateway_data['operation'] == 'approved' ) //операция холдирования суммы;
				$status = $this->gateway_data['status'] == 1?8:5;
			elseif ( $this->gateway_data['operation'] == 'deposited' ) // операция завершения;
			{				
				$status = $this->gateway_data['status'] == 1?3:5;
				if ( $status == 3  )
				{
					$args = $this->get_request_args( ['orderNumber' => $this->payment_number] );
					$result = $this->send_request( $this->get_url_sberbank('getOrderStatusExtended'), $args );	
					if ( !empty($result['bankInfo']) && !empty($result['bankInfo']['bankName']) )
						usam_update_payment_metadata($this->payment['id'], 'bank_name', $result['bankInfo']['bankName']);
					/*					
					$args = $this->get_request_args( ['orderNumber' => $this->payment_number] );
					$result = $this->send_request( $this->get_url_sberbank('getReceiptStatus'), $args );							
					if ( !empty($result['receipt']) )					
					{	
						switch( $result['receipt']['receiptStatus'] ) 
						{			
							case 0: // отправлен платёж;
							case 1: //оставлен платёж
								$type = 'check';		
							case 3: //  доставлен возврат, 										
							case 4: //  доставлен возврат, 			
								$type = 'check_return';
							break;	
							default:									
								$type = '';
							break;	
						}
						if ( $type )
						{
							$document = ['date_insert' => date("Y-m-d H:i:s",strtotime($result['receipt']['receipt_date_time'])), 'type' => $type, 'status' => 'approved'];						
							$document_id = usam_insert_document( $document );
							if ( $document_id )
							{
								if ( isset($result['receipt']['original_ofd_uuid']) )
									usam_update_document_metadata($document_id, 'ofd_uuid', $result['receipt']['original_ofd_uuid'] );
								if ( isset($result['receipt']['uuid']) )
									usam_update_document_metadata($document_id, 'uuid', $result['receipt']['uuid'] );
								if ( isset($result['receipt']['shift_number']) )
									usam_update_document_metadata($document_id, 'shift_id', $result['receipt']['shift_number'] );
								usam_update_document_metadata($document_id, 'ofd_receipt_url', $result['receipt']['ofd_receipt_url'] );	
								usam_update_document_metadata($document_id, 'payment_type', 'card' );			
								
								$order_id = $this->purchase_log->get('id');
								$products = usam_get_products_order( $order_id );
								if ( $products )
								{									
									$new_document = new USAM_Document( $document_id );		
									$new_document->add_products( $products );			
								}								
							}
						}
					} */
				}
			}
			elseif ( $this->gateway_data['operation'] == 'reversed' ) //операция отмены;
				$status = $this->gateway_data['status'] == 1?2:5;
			elseif ( $this->gateway_data['operation'] == 'refunded' ) //операция возврата;
			{				
				$args = $this->get_request_args( ['orderNumber' => $this->payment_number] );
				$result = $this->send_request( $this->get_url_sberbank('getOrderStatusExtended'), $args );		
				if ( !empty($result['paymentAmountInfo']) && !empty($result['paymentAmountInfo']['refundedAmount']) )
				{				
					$sum = $result['paymentAmountInfo']['refundedAmount']/100;				
					$order_id = $this->purchase_log->get('id');
					$payment_id = usam_insert_payment_document(['sum' => $sum, 'document_id' => $order_id, 'status' => 4], ['document_id' => $order_id, 'document_type' => 'order']);
					if ( $payment_id )
					{
						if ( !empty($result['bankInfo']) && !empty($result['bankInfo']['bankName']) )
							usam_update_payment_metadata($payment_id, 'bank_name', $result['bankInfo']['bankName']);
					}
				}				
			}			
			if ( $status )
				$this->update_payment_document(['status' => $status]);	
			header("HTTP/1.1 200 OK");
		}	
    }
	
	private function get_request_args( $params = array() ) 
	{
		$params['password'] = $this->gateway_system['pass'];
		$params['userName'] = $this->gateway_system['login'];	
		$language = $this->get_country_language();
		if ( $language )
			$params['language'] = mb_strtolower($language);
		
		//$headers["Content-Type"] = 'application/json;charset=UTF-8';		
	//	$headers["Authorization"] = 'Bearer '.$this->deliver['token'];		
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
	//		'headers' => $headers,
			'body' => $params,
			'cookies' => array(),
			'sslverify' => false
		); 
		return $args;
	}
		
	public function get_form() 
	{					
		$output = "
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_login'>".esc_html__('Имя пользователя', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' id='option_gateway_login' size='40' value='".$this->gateway_system['login']."' name='gateway_handler[login]' />
			</div>
		</div>
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_pass'>".esc_html__('Пароль', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' id='option_gateway_pass' size='40' value='".$this->gateway_system['pass']."' name='gateway_handler[pass]' />
			</div>
		</div>		
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_pass_notification'>".esc_html__('Пароль для уведомлений платежей', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' id='option_gateway_pass_notification' size='40' value='".$this->gateway_system['pass_notification']."' name='gateway_handler[pass_notification]' />
			</div>
		</div>	
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_url'>".esc_html__('Тип среды', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<select name='gateway_handler[url]' id='option_gateway_url'>
					<option value='0' ". selected( $this->gateway_system['url'], 0, false ) .">" . __('Тестовая', 'usam') . "</option>
					<option value='1' ". selected( $this->gateway_system['url'], 1, false ) .">" . __('Реальный счет', 'usam') . "</option>
				</select>
				<p class='description'>".__('Если вы хотите принимать деньги используйте "Реальный счет".', 'usam')."</p>
			</div>
		</div>
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_tax_type'>".esc_html__('Ставка НДС', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<select name='gateway_handler[tax_type]' id='option_tax_type'>
					<option value='0' ". selected( $this->gateway_system['tax_type'], 0, false ) .">" . __('без НДС', 'usam') . "</option>
					<option value='1' ". selected( $this->gateway_system['tax_type'], 1, false ) .">" . __('НДС по ставке 0%', 'usam') . "</option>
					<option value='2' ". selected( $this->gateway_system['tax_type'], 2, false ) .">" . __('НДС чека по ставке 10%', 'usam') . "</option>
					<option value='4' ". selected( $this->gateway_system['tax_type'], 4, false ) .">" . __('НДС чека по расчетной ставке 10/110', 'usam') . "</option>
					<option value='6' ". selected( $this->gateway_system['tax_type'], 6, false ) .">" . __('НДС чека по ставке 20%', 'usam') . "</option>
					<option value='7' ". selected( $this->gateway_system['tax_type'], 7, false ) .">" . __('НДС чека по расчётной ставке 20/120', 'usam') . "</option>
				</select>
				<p class='description'>".__('Для фискализации.', 'usam')."</p>
			</div>
		</div>
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_payment_method'>".esc_html__('Тип оплаты', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<select name='gateway_handler[payment_method]' id='option_payment_method'>
					<option value='1' ". selected( $this->gateway_system['payment_method'], 1, false ) .">" . __('полная предварительная оплата до момента передачи предмета расчёта', 'usam') . "</option>
					<option value='2' ". selected( $this->gateway_system['payment_method'], 2, false ) .">" . __('частичная предварительная оплата до момента передачи предмета расчёта', 'usam') . "</option>
					<option value='3' ". selected( $this->gateway_system['payment_method'], 3, false ) .">" . __('аванс', 'usam') . "</option>
					<option value='4' ". selected( $this->gateway_system['payment_method'], 4, false ) .">" . __('полная оплата в момент передачи предмета расчёта', 'usam') . "</option>
					<option value='5' ". selected( $this->gateway_system['payment_method'], 5, false ) .">" . __('частичная оплата предмета расчёта в момент его передачи с последующей оплатой в кредит', 'usam') . "</option>
					<option value='6' ". selected( $this->gateway_system['payment_method'], 6, false ) .">" . __('передача предмета расчёта без его оплаты в момент его передачи с последующей оплатой в кредит', 'usam') . "</option>
					<option value='7' ". selected( $this->gateway_system['payment_method'], 7, false ) .">" . __('оплата предмета расчёта после его передачи с оплатой в кредит', 'usam') . "</option>
				</select>
				<p class='description'>".__('Для фискализации.', 'usam')."</p>
			</div>
		</div>";			
		return $output;
	}	
}