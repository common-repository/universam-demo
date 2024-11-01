<?php 
/*
  Name: Модуль оплаты ЮKassa
 */
class USAM_Merchant_yandex_kassa extends USAM_Merchant 
{	
	protected $content_type = 'json';
	protected $api_version = '3';
	protected $type_operation = 'a';
	protected $ipn = true;
	protected $payment_link = 'https://api.yookassa.ru/v3/payments';

	/**
	* передает полученные данные в платежный шлюз
	payment_method_data:
	
bank_card — банковская карта;
yandex_money — Яндекс.Деньги;
sberbank — Сбербанк Онлайн;
qiwi — QIWI Wallet;
webmoney — Webmoney,
alfabank — Альфа-Клик;
cash — оплата наличными в терминале.
	*/
	function get_url( ) 
	{	
		$order_id = $this->purchase_log->get('id'); 
		$order_data = $this->purchase_log->get_data();
		if ( !$this->gateway_system['login'] || !$this->gateway_system['pass'] )
		{
			return false;
		}		
		$items = array();
		$email = (string)usam_get_order_customerdata( $order_data['id'], 'email' );
		$phone = (string)usam_get_order_customerdata( $order_data['id'], 'mobile_phone' );	
		$currency = $this->get_gateway_currency_code();
		$products = usam_get_products_order( $order_id );
		foreach ( $products as $product ) 
		{
			$items[] = ['description' => $product->name, 'quantity' => $product->quantity, 'amount' => ['value' => $product->price, 'currency' => $currency], 'payment_mode' => $this->gateway_system['payment_mode'], 'payment_subject' => $this->gateway_system['payment_subject'], 'vat_code' => $this->gateway_system['vat_code']];
		}				
		require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
		$documents = usam_get_shipping_documents(['order' => 'DESC', 'order_id' => $order_id, 'include_in_cost' => 1]);			
		foreach ( $documents as $document ) 
		{					
			if ( $document->price != '0.00' )
				$items[] = ['description' => __('Оплата за услуги по доставке','usam'), 'quantity' => 1, 'amount' => ['value' => $document->price, 'currency' => $currency], 'vat_code' => $this->gateway_system['vat_code']];
		}	
		$params = [
			'amount' => ['value' => $this->payment['sum'], 'currency' => $currency],	
			'receipt' => ['items' => $items, 'customer' => ['phone' => $phone, 'email' => $email]],	
			'payment_method_data' => ['type' => 'bank_card'],
			'capture' => $this->gateway_system['capture']?true:false,		
			'tax_system_code' => $this->gateway_system['tax_system_code'],			
			'confirmation' => ['type' => 'redirect', 'return_url' => $this->url_cancel_return],	
		//	'description' => sprintf( __("Оплата заказа %s"), $order_id),			
		//	'metadata' => array( '' => )
		];
		if ( $this->is_debug() )
			$params["test"] = true;	
			
		$args = $this->get_request_args( $params );		
		$result = $this->send_request( $this->payment_link, $args);			
		if ( isset($result['type']) && $result['type'] == 'error' )
		{
			$this->set_error( $result['description'] );
		}		
		if ( is_array($result) && !empty($result['confirmation']['confirmation_url']) )
		{	
			$this->update_payment_document(['transactid' => $result['id']]); 
			return $result['confirmation']['confirmation_url'];
		}
		else
			return $this->url_cancel_return;		
	}	
	
	private function get_request_args( $params = array(), $method = 'POST' ) 
	{		
		$headers['Authorization'] = 'Basic '.base64_encode($this->gateway_system['login'].':'.$this->gateway_system['pass']);
		$headers["Idempotence-Key"] = $this->payment_number;
		$headers["Content-Type"] = 'application/json';
		$headers["Accept"] = 'application/json';		
		$args = [
			'method' => $method,
			'timeout' => 45,
			'user-agent' => 'UNIVERSAM',
			'redirection' => 5,
			'blocking' => true,
			'headers' => $headers,			
			'sslverify' => true,
		];
		if( $params && $method == 'POST' )
			$args['body'] = json_encode($params);
		return $args;
	}
		
	/**
	 * Получить номер платежного документа
	 */
	protected function get_payment_number( ) 
	{ 
		if ( !isset($this->gateway_data['object']) )
			return false;		
					
		$transactid = sanitize_text_field($this->gateway_data['object']->id);
		require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
		$payments = usam_get_payments(['transactid' => $transactid]);
		if ( empty($payments))
			return false;					
	
		return $payments[0]->number;
	}

	/**
	 * Процесс шлюза уведомления, проверяет и решает, что делать с данными шлюза, выданных в торговых файлы
 {
  "type": "notification",
  "event": "payment.waiting_for_capture",
  "object": {
    "id": "21740069-000f-50be-b000-0486ffbf45b0",
    "status": "waiting_for_capture",
    "paid": true,
    "amount": {
      "value": "2.00",
      "currency": "RUB"
    },
    "created_at": "2017-10-14T10:53:29.072Z",
    "metadata": {},
    "payment_method": {
      "type": "yandex_money",
      "id": "731714f2-c6eb-4ae0-aeb6-8162e89c1065",
      "saved": false,
      "account_number": "410011066000000",
      "title": "Yandex.Money wallet 410011066000000"
    }
  }
}
	*/
	protected function parse_gateway_notification() 
	{ 	
		if ( !isset($this->gateway_data['object']) )
			return false;	
		
		$transactid = sanitize_text_field($this->gateway_data['object']->id);			
		$args = $this->get_request_args( [], 'GET' );
		$result = $this->send_request( $this->payment_link.'/'.$transactid, $args);	 			
		if ( isset($result['type']) && $result['type'] == 'error' )
			$this->set_error( $result['description'] );
		if ( isset($result['status']) )
		{
			$status = 0;
			switch( $result['status'] ) 
			{
				case 'pending' : //платеж создан, но не завершен
					$status = 1;
				break;
				case 'waiting_for_capture' : //платеж завершен и ожидает ваших действий.
					$status = 6;
				break;
				case 'succeeded' : // платеж успешно завершен,
					$status = 3;
				break;			
				case 'canceled' :		//  платеж отменен
					$status = 2;
				break;
			}			
			if ( $status )
				$this->update_payment_document(['status' => $status]);	
		}			
	}
	
	protected function get_default_option( ) 
	{
		return ['login' => '', 'pass' => '', 'vat_code' => 0, 'capture' => 1, 'tax_system_code' => 3, 'payment_subject' => 'commodity', 'payment_mode' => 'full_payment'];
	}
	
	public function get_form()
	{			
		$output = "
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_login'>shopId:</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' size='40' id ='option_gateway_login' value='".$this->gateway_system['login']."' name='gateway_handler[login]' />
			</div>
		</div>
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_pass'>".esc_html__('Секретный ключ', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' id='option_gateway_pass' size='40' value='".$this->gateway_system['pass']."' name='gateway_handler[pass]' />
			</div>
		</div>		
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'>".esc_html__('Cистем налогообложения', 'usam').":</div>
			<div class ='edit_form__item_option'>
				<select name='gateway_handler[tax_system_code]'>
					<option value='1' ". selected( $this->gateway_system['tax_system_code'], 1, false ) .">" . __('Общая система налогообложения', 'usam') . "</option>
					<option value='2' ". selected( $this->gateway_system['tax_system_code'], 2, false ) .">" . __('Упрощенная (УСН, доходы)', 'usam') . "</option>		
					<option value='3' ". selected( $this->gateway_system['tax_system_code'], 3, false ) .">" . __('Упрощенная (УСН, доходы минус расходы)', 'usam') . "</option>
					<option value='4' ". selected( $this->gateway_system['tax_system_code'], 4, false ) .">" . __('Единый налог на вмененный доход (ЕНВД)', 'usam') . "</option>
					<option value='5' ". selected( $this->gateway_system['tax_system_code'], 5, false ) .">" . __('Единый сельскохозяйственный налог (ЕСН)', 'usam') . "</option>	
					<option value='6' ". selected( $this->gateway_system['tax_system_code'], 6, false ) .">" . __('Патентная система налогообложения', 'usam') . "</option>						
				</select>
			</div>
		</div>
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'>".esc_html__('НДС', 'usam').":</div>
			<div class ='edit_form__item_option'>
				<select name='gateway_handler[vat_code]'>
					<option value='1' ". selected( $this->gateway_system['vat_code'], 1, false ) .">" . __('Без НДС', 'usam') . "</option>
					<option value='2' ". selected( $this->gateway_system['vat_code'], 2, false ) .">" . __('НДС по ставке 0%', 'usam') . "</option>		
					<option value='3' ". selected( $this->gateway_system['vat_code'], 3, false ) .">" . __('НДС по ставке 10%', 'usam') . "</option>
					<option value='4' ". selected( $this->gateway_system['vat_code'], 4, false ) .">" . __('НДС по ставке 20%', 'usam') . "</option>
					<option value='5' ". selected( $this->gateway_system['vat_code'], 5, false ) .">" . __('НДС по ставке 10/110%', 'usam') . "</option>	
					<option value='6' ". selected( $this->gateway_system['vat_code'], 6, false ) .">" . __('НДС по ставке 20/120%', 'usam') . "</option>						
				</select>
			</div>
		</div>		
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'>".esc_html__('Признак предмета расчета', 'usam').":</div>
			<div class ='edit_form__item_option'>
				<select name='gateway_handler[payment_subject]'>
					<option value='commodity' ". selected( $this->gateway_system['payment_subject'], 'commodity', false ) .">" . __('Товар', 'usam') . "</option>		
					<option value='excise' ". selected( $this->gateway_system['payment_subject'], 'excise', false ) .">" . __('Подакцизный товар', 'usam') . "</option>	
					<option value='job' ". selected( $this->gateway_system['payment_subject'], 'job', false ) .">" . __('Работа', 'usam') . "</option>
					<option value='service' ". selected( $this->gateway_system['payment_subject'], 'service', false ) .">" . __('Услуга', 'usam') . "</option>
					<option value='payment' ". selected( $this->gateway_system['payment_subject'], 'payment', false ) .">" . __('Платеж', 'usam') . "</option>
					<option value='marked' ". selected( $this->gateway_system['payment_subject'], 'marked', false ) .">" . __('Товар, подлежащий маркировке средством идентификации, имеющим код маркировки, за исключением подакцизного товара', 'usam') . "</option>
					<option value='non_marked' ". selected( $this->gateway_system['payment_subject'], 'non_marked', false ) .">" . __('Товар, подлежащий маркировке средством идентификации, не имеющим кода маркировки, за исключением подакцизного товара', 'usam') . "</option>
					<option value='marked_excise' ". selected( $this->gateway_system['payment_subject'], 'marked_excise', false ) .">" . __('Подакцизный товар, подлежащий маркировке средством идентификации, имеющим код маркировки', 'usam') . "</option>	
					<option value='non_marked_excise' ". selected( $this->gateway_system['payment_subject'], 'non_marked_excise', false ) .">" . __('Подакцизный товар, подлежащий маркировке средством идентификации, не имеющим кода маркировки', 'usam') . "</option>
					<option value='fine' ". selected( $this->gateway_system['payment_subject'], 'fine', false ) .">" . __('Выплата', 'usam') . "</option>
					<option value='tax' ". selected( $this->gateway_system['payment_subject'], 'tax', false ) .">" . __('Страховые взносы', 'usam') . "</option>				
				</select>
			</div>
		</div>
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'>".esc_html__('Признак способа расчета', 'usam').":</div>
			<div class ='edit_form__item_option'>
				<select name='gateway_handler[payment_mode]'>
					<option value='full_payment' ". selected( $this->gateway_system['payment_mode'], 'full_payment', false ) .">" . __('Полный расчет', 'usam') . "</option>
					<option value='full_prepayment' ". selected( $this->gateway_system['payment_mode'], 'full_prepayment', false ) .">" . __('Полная предоплата', 'usam') . "</option>					
				</select>
			</div>
		</div>
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'>".esc_html__('Автоматически принимать платежи', 'usam').":</div>
			<div class ='edit_form__item_option'>
				<select name='gateway_handler[capture]'>
					<option value='1' ". selected( $this->gateway_system['capture'], 1, false ) .">" . __('Да', 'usam') . "</option>
					<option value='0' ". selected( $this->gateway_system['capture'], 0, false ) .">" . __('Нет', 'usam') . "</option>					
				</select>
			</div>
		</div>
		";		
		return $output;
	}
}
?>