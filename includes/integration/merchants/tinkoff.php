<?php
/**
 * Name: Оплата через Тинькофф
 */
class USAM_Merchant_tinkoff extends USAM_Merchant 
{
	protected $api_version = '2.0';
	protected $type_operation = 'a';
	protected $ipn = true;		
	protected $url = 'https://securepay.tinkoff.ru/v2/';
		
	function get_currency_list( ) 
	{
		return array("RUB", "USD");
	}
	
	protected function get_default_option( ) 
	{
		return array( 'pass' => '', 'tax_type' => 0, 'pass_notification' => '', 'taxation' => 'usn_income', 'terminalkey' => '', 'shop_id' => '', 'credit_product' => 'default');
	}
	
	protected function get_request_args( $params )
	{
		$params['TerminalKey'] = $this->gateway_system['terminalkey'];
		$params['Token'] = $this->get_token( $params );
		
		$headers["Content-Type"] = 'application/json';
		$headers["Accept"] = 'application/json';			
		$args = [
			'method' => 'POST',
			'headers' => $headers,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'body' => json_encode($params),
			'cookies' => array(),
			'sslverify' => true
		];	
		return $args;
	}
	
	protected function get_payment_status( )
	{	
		$language = mb_strtolower($this->get_country_code());
		if ( !empty($this->payment['transactid']) )
		{			
			$params = array(			
				'PaymentId'  => $this->payment['transactid'],					
			);						
			$result = $this->send_request( $this->url.'GetState', $this->get_request_args( $params ) );	
			return $result;		
		}
		return false;
	}
	
	protected function get_url( )
	{				
		$currency_code = $this->get_gateway_currency_code();	
		$currency = usam_get_currency( $currency_code );	
		/*if ( !empty($this->payment['transactid']) )
		{	
			$result = $this->get_payment_status();		
			if ( isset($result['Status']) )
			{  
				return 'https://securepay.tinkoff.ru/rest/Authorize/'.$this->payment['transactid'] );	
			}				
		} */
		$order_id = $this->purchase_log->get('id');
		$order_data = $this->purchase_log->get_data();
		
		$Receipt = array();
		$order_products = usam_get_products_order( $order_data['id'] );	
		$amount = 0;
		foreach ( $order_products as $product ) 
		{
			$sum = round($product->price*100* $product->quantity);	
		//	$tax = isset($product_taxes[$product->product_id])?$product_taxes[$product->product_id][$product->unit_measure]*100:0;			
			$Receipt['Items'][] = [
				"Name" => $product->name,
				"Quantity" => $product->quantity,
				"Price" => $product->price*100,		
				"Amount" => $sum,
				"PaymentMethod" => "full_prepayment",
				"PaymentObject" => "commodity",			
				"Tax" => $this->gateway_system['tax_type'],					
//	"Ean13" => "0123456789", маркировка товара				
			];
			$amount += $sum;
		}		
		require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
		$documents = usam_get_shipping_documents(['order' => 'DESC', 'order_id' => $order_id, 'include_in_cost' => 1]);	
		foreach ( $documents as $document ) 
		{					
			if ( $document->price != '0.00' )
			{
				$Receipt['Items'][] = [
					"Name" => __('Оплата за услуги по доставке','usam'),
					"Quantity" => 1.00,
					"Price" => $document->price*100,					
					"Amount" => round($document->price*100),
					"PaymentMethod" => "full_prepayment",
					"PaymentObject" => "service",				
					"Tax" => $this->gateway_system['tax_type'],			
				];		
				$amount += round( $document->price*100 );
			}				
		}	
		$Receipt['Taxation'] = $this->gateway_system['taxation'];
		if ( usam_is_type_payer_company( $order_data['type_payer'] ) )
		{
			$email = usam_get_order_metadata( $order_data['id'], 'company_email' );
			$phone = usam_get_order_metadata( $order_data['id'], 'company_phone' );
		}
		else
		{
			$email = usam_get_order_metadata( $order_data['id'], 'billingemail' );
			$phone = usam_get_order_metadata( $order_data['id'], 'billingphone' );					
		}					
		if ( $email )
			$Receipt['Email'] = $email;
		if ( $phone ) 
			$Receipt['Phone'] = $phone;		
		$params = [		
			'Amount' => $amount,
			'OrderId' => $this->payment_number,
			'Description' => sprintf( __("Оплата заказа %s"), $order_id ),	
			'Language' => 'ru', // язык меню
			'SuccessURL' => $this->url_return, // при удачном платеже
			'FailURL' => $this->url_cancel_return,// Страница ошибки				
		];					
		$params['Receipt'] = $Receipt;
		if ( $email )
			$params['DATA']['Email'] = $email;
		if ( $phone ) 
			$params['DATA']['Phone'] = $phone;	
		$result = $this->send_request( $this->url.'Init', $this->get_request_args( $params ) );	
		if ( !empty($result['ErrorCode']) )
		{
			$this->set_error( $result['Message'] );
		} 
		if ( is_array($result) && !empty($result['PaymentURL']) )
		{			
			$payment['transactid'] = $result['PaymentId'];
			$this->update_payment_document( $payment );				
			return $result['PaymentURL'];
		}
		else
			return $this->url_cancel_return;		
	}	
		
	protected function get_token( $params ) 
	{
		if ( isset($params['Receipt']) )
			unset($params['Receipt']);
		if ( isset($params['DATA']) )
			unset($params['DATA']);
		if ( isset($params['Token']) )
			unset($params['Token']);
		$params['Password'] = $this->gateway_system['pass'];
		ksort($params);
		$string = implode("",$params);
		$token = hash_hmac('sha256',$string, $this->gateway_system['pass'] );
		return $token;
	}
	
	/*promoCode Идентификатор кредитного продукта(кредит/рассрочка)*/
	public function get_button_onclick( $args ) 
	{
		if ( !empty($args['product_id']) )
		{		
			$price = usam_get_product_price( $args['product_id'] );
			if ( $price >= 3000 && $price <= 200000 )
			{		
				$quantity = !empty($args['quantity'])?$args['quantity']:1;
				?>
				<script>						
					document.addEventListener("DOMContentLoaded", () => {						
						tinkoff.methods.on(tinkoff.constants.SUCCESS, onMessage);
						tinkoff.methods.on(tinkoff.constants.REJECT, onMessage);
						tinkoff.methods.on(tinkoff.constants.CANCEL, onMessage);						
						function onMessage( data ) 
						{													
							switch (data.type)
							{
								case tinkoff.constants.SUCCESS:
									
								break;
								case tinkoff.constants.REJECT:
								
								break;
								case tinkoff.constants.CANCEL:
								 
								break;
								default:
								 
								return;
							}
							tinkoff.methods.off(tinkoff.constants.SUCCESS, onMessage);
							tinkoff.methods.off(tinkoff.constants.REJECT, onMessage);
							tinkoff.methods.off(tinkoff.constants.CANCEL, onMessage);
						//	data.meta.iframe.destroy();
						}						
					})
				</script>	
				<?php	
				add_action('wp_footer', function() use ( $args, $quantity ) 
				{ 							
					wp_enqueue_script( 'tinkoff', "https://forma.tinkoff.ru/static/onlineScript.js", [], USAM_VERSION_ASSETS, true ); 									
				}, 1);				
				$function = $this->gateway_system['debug']?"createDemo":'create';	
				$demo = $this->gateway_system['debug']?'demoFlow:"sms",':'';
				$product_id = $args['product_id'];
				$title = get_the_title( $args['product_id'] );
				$sum = $price*$quantity;
				$type_price = usam_get_customer_price_code();
				return 'usam_api("order/save", {type_price:"'.$type_price.'",status:"bank_review",bank_account_id:'.$this->gateway_system['bank_account_id'].',products:[{product_id:'.$product_id.',quantity:'.$quantity.'}]}, "POST", function( r ){ 	
					tinkoff.'.$function.'(
					{
						sum:'.$sum.',
						items:[{name:"'.$title.'", price:'.$price.', quantity:'.$quantity.'}],
						'.$demo.'
						promoCode:"'.$this->gateway_system['credit_product'].'",
						orderNumber:r.payment_number,		
						webhookURL:"'.$this->notification_url.'",					
						shopId:"'.$this->gateway_system['shop_id'].'",
						showcaseId:"'.$this->gateway_system['terminalkey'].'",			
					},{view: "modal"});				
				} )';				
			}
		}
		return '';
	}
		
	/**
	 * Получить номер платежного документа
	 */
	protected function get_payment_number( ) 
	{  		
		if (isset($this->gateway_data['OrderId']))
			return $this->gateway_data['OrderId'];
		elseif (isset($this->gateway_data['id']))
			return $this->gateway_data['id'];
		else
			return false;
	}
	
	/* Процесс уведомления из торгового шлюза. Проверяет данные и возвращает ответ.*/
	protected function parse_gateway_notification() 
	{				
		if ( isset($this->gateway_data['status']) && isset($this->gateway_data['operation']) )
		{					
			$token = $this->get_token( $this->gateway_data );
			if ( $token != $this->gateway_data['Token'] )
			{	
				$this->set_error( __('Проверка подлинности соединения не пройдена.','usam') ); 
				return false;
			} 
			$transact_id = $this->gateway_data['PaymentId'];		
			$payment = usam_get_payment_document( $this->payment_number, 'number');	
			if ( $transact_id != $payment['transactid'] )
			{		
				$this->notification_errors( 'transactid' );
				return false;				
			}					
			if ( $this->gateway_data['status'] == 'CONFIRMED' ) 
				$status = 3; //Оплачено
			else
				$status = 2; //Отклонено
		
			$payment['status'] = $status;			
			$this->update_payment_document( $payment );		
			header("HTTP/1.1 200 OK");
		}	
		elseif ( isset($this->gateway_data['status']) && isset($this->gateway_data['first_name']) )
		{
			/*
			  "id": "123e4567—e89b—12d3—a456—426655440000",
			  "status": "approved",
			  "created_at": "2020-03-31T22:55:16.716Z",
			  "demo": true,
			  "committed": false,
			  "first_payment": 0,
			  "order_amount": 6000,
			  "credit_amount": 6000,
			  "product": "credit",
			  "term": 6,
			  "monthly_payment": 1000, // Номер договора
			  "first_name": "Имя",
			  "last_name": "Фамилия",
			  "middle_name": "Отчество",
			  "phone": "+79000000000",
			  "loan_number": "0000000000",
			  "email": "email@example.com",
			  "signing_type": "sms"
			 */		
			$order_id = $this->purchase_log->get('id');
			
			$payers = usam_get_group_payers(['type' => 'contact']);			
			$contact['lastname'] = sanitize_text_field($this->gateway_data['last_name']);
			$contact['firstname'] = sanitize_text_field($this->gateway_data['first_name']);
			$contact['patronymic'] = sanitize_text_field($this->gateway_data['middle_name']);		
			$contact['mobilephone'] = sanitize_text_field($this->gateway_data['phone']);	
			$contact['email'] = sanitize_text_field($this->gateway_data['email']);			
			$customer_data = usam_get_webform_data_from_CRM( $contact, 'order', $payers[0]['id'] );
			usam_add_order_customerdata( $order_id, $customer_data );
			if ( $this->gateway_data['status'] == 'approved' || $this->gateway_data['status'] == 'signed' ) 
				$this->set_purchase_processed_by_purchid( 'credit_approved' );
			elseif ( $this->gateway_data['status'] == 'rejected' ) 
				$this->set_purchase_processed_by_purchid( 'credit_rejected' );
			elseif ( $this->gateway_data['status'] == 'canceled' ) 
				$this->set_purchase_processed_by_purchid( 'canceled' );		
		}	
    }
	
	public function get_form() 
	{		
		$output = "	
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_shop_id'>".esc_html__('Идентификатор магазина', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' id='option_gateway_shop_id' size='20' value='".$this->gateway_system['shop_id']."' name='gateway_handler[shop_id]' />
			</div>
		</div>	
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_login'>".esc_html__('Идентификатор терминала', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' id='option_gateway_login' size='20' value='".$this->gateway_system['terminalkey']."' name='gateway_handler[terminalkey]' />
			</div>
		</div>	
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_pass'>".esc_html__('Пароль', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<input type='text' id='option_gateway_pass' size='40' value='".$this->gateway_system['pass']."' name='gateway_handler[pass]' />
			</div>
		</div>				
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_taxation'>".esc_html__('Система налогообложения', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<select name='gateway_handler[taxation]' id='option_gateway_taxation'>
					<option value='osn' ". selected( $this->gateway_system['taxation'], 'osn', false ) .">" . __('общая', 'usam') . "</option>
					<option value='usn_income' ". selected( $this->gateway_system['taxation'], 'usn_income', false ) .">" . __('упрощенная (доходы)', 'usam') . "</option>
					<option value='usn_income_outcome' ". selected( $this->gateway_system['taxation'], 'usn_income_outcome', false ) .">" . __('упрощенная (доходы минус расходы)', 'usam') . "</option>
					<option value='patent' ". selected( $this->gateway_system['taxation'], 'patent', false ) .">" . __('патентная', 'usam') . "</option>
					<option value='esn' ". selected( $this->gateway_system['taxation'], 'esn', false ) .">" . __('единый сельскохозяйственный налог', 'usam') . "</option>
				</select>
			</div>
		</div>	
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_tax_type'>".esc_html__('Ставка НДС', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<select name='gateway_handler[tax_type]' id='option_tax_type'>
					<option value='none' ". selected( $this->gateway_system['tax_type'], 'none', false ) .">" . __('без НДС', 'usam') . "</option>
					<option value='vat0' ". selected( $this->gateway_system['tax_type'], 'vat0', false ) .">" . __('НДС по ставке 0%', 'usam') . "</option>
					<option value='vat10' ". selected( $this->gateway_system['tax_type'], 'vat10', false ) .">" . __('НДС чека по ставке 10%', 'usam') . "</option>
					<option value='vat20' ". selected( $this->gateway_system['tax_type'], 'vat20', false ) .">" . __('НДС чека по ставке 20%', 'usam') . "</option>
					<option value='vat110' ". selected( $this->gateway_system['tax_type'], 'vat110', false ) .">" . __('НДС чека по расчетной ставке 10/110', 'usam') . "</option>
					<option value='vat120' ". selected( $this->gateway_system['tax_type'], 'vat120', false ) .">" . __('НДС чека по расчетной ставке 20/120', 'usam') . "</option>
				</select>
			</div>
		</div>
		<div class ='edit_form__item'>
			<div class ='edit_form__item_name'><label for='option_gateway_credit_product'>".esc_html__('Вариант кредитного продукта', 'usam').":</label></div>
			<div class ='edit_form__item_option'>
				<select name='gateway_handler[credit_product]' id='option_gateway_credit_product'>
					<option value='default' ".selected( $this->gateway_system['credit_product'], 'default', false ) .">" . __('кредит', 'usam') . "</option>
					<option value='installment_0_0_6_5' ". selected( $this->gateway_system['credit_product'], 'installment_0_0_6_5', false ) .">" . __('рассрочка', 'usam') . "</option>
				</select>
			</div>
		</div>";	
		return $output;
	}	
}