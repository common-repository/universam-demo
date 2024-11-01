<?php
/**
 * Name: Оплата через PayPal 
 */
class USAM_Merchant_paypal_standard extends USAM_Merchant 
{
	protected $api_version = '2.0';
	protected $type_operation = 'a';
	protected $ipn = true;	
	protected $payment_link = 'https://www.paypal.com/cgi-bin/webscr';
	protected $test_payment_link = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	
	function get_currency_list( ) 
	{
		return array('AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'SEK', 'SGD', 'THB', 'TWD', 'USD', "RUB");
	}
		
	function get_vars( $aggregate = false ) 
	{			
		$buy_now = defined( 'USAM_PAYPAL_BUY_NOW' ) && USAM_PAYPAL_BUY_NOW;		

		$order = $this->purchase_log->get_data( ); 
		$country = usam_get_country_location( );	
		$paypal_vars = array(
			'business' => $this->gateway_system['business'],
			'return' => $this->url_return, // при удачном платеже
			'cancel_return' => $this->url_cancel_return,// URL-адрес, к которому PayPal перенаправляет браузер покупателя, если они отменить проверку до завершения их выплаты.
			'rm' => '2',
			'currency_code' => $this->get_gateway_currency_code(),
			'lc' => !empty($country['code'])?$country['code']:'RU', // язык меню
			'bn' => '', // Имя программного обеспечения
			'no_note' => '1',
			'charset' => 'utf-8',
			'invoice' => $this->payment_number,
			'paymentaction' => 'sale',		
		);				
		if ( $this->gateway_system['ipn'] ) // отправка ответа об успешном платеже					
			$paypal_vars['ipn_notification_url'] = $this->notification_url;		
		
		if ( (bool) $this->gateway_system['ship'] && ! $buy_now ) 
		{	// Добавить данные клиента	
			$customer_location = $this->get_customer_data_by_type('location');	
			if ( $customer_location !== '' )
			{						
				$shipping = usam_get_address_locations( $customer_location );								
				if ( isset($shipping['city']) )
					$paypal_vars['city'] = $shipping['city'];
				else
					$paypal_vars['city'] = '';	
				if ( isset($shipping['region']) )
					$paypal_vars['state'] = $shipping['region'];
				else
					$paypal_vars['state'] = '';					
			}	
			$paypal_vars['country'] = $country['code'];				
			$paypal_vars += array(						
				'email' => $this->get_customer_data_by_type('email'),
				'night_phone_b' => $this->get_customer_data_by_type('phone'),			
				'first_name' => usam_get_order_metadata( $order['id'], 'shippingfirstname' ),
				'last_name' => usam_get_order_metadata( $order['id'], 'shippinglastname' ),					
				'zip' => usam_get_order_metadata( $order['id'], 'shippingpostcode' ),			
			);	
			$address = usam_get_order_metadata( $order['id'], 'shippingaddress' );	
			if ( $address != '' )
			{
				$paypal_vars['address1'] = $address;			
				$paypal_vars['address_override'] = '1';		// Заменять доставку PayPal на адрес введенный покупателем на сайте
				$paypal_vars['no_shipping'] = '0';			// Запрашивать данные или нет		
			}
			else
				$paypal_vars['no_shipping'] = '2';	
		}
		if ( $buy_now )
			$paypal_vars['custom'] = 'buy_now';			
		
		$cart_contents = usam_get_products_order( $order['id'] );		
		if ( $this->payment_type == 'is_subscription' ) 
		{	// Если подписка
			$paypal_vars += array(
				'cmd'=> '_xclick-subscriptions',
			);
			$reprocessed_cart_data['shopping_cart'] = array(
				'is_used' => false,
				'price' => 0,
				'length' => 1,
				'unit' => 'd',
				'times_to_rebill' => 1,
			);
			$reprocessed_cart_data['subscription'] = array(
				'is_used' => false,
				'price' => 0,
				'length' => 1,
				'unit' => 'D',
				'times_to_rebill' => 1,
			);			
			foreach ($cart_contents as $product) 
			{
				if ( $product->subscribed ) 
				{// Подписка
					$reprocessed_cart_data['subscription']['is_used'] = true;
					$reprocessed_cart_data['subscription']['price'] = $this->convert( $product->price );
			//		$reprocessed_cart_data['subscription']['length'] = $product['recurring_data']['rebill_interval']['length'];
			//		$reprocessed_cart_data['subscription']['unit'] = strtoupper($product['recurring_data']['rebill_interval']['unit']);
			//		$reprocessed_cart_data['subscription']['times_to_rebill'] = $product['recurring_data']['times_to_rebill'];
				} 
				else 
				{
					$sum = $product->price * $product->quantity;
					if ($sum > 0)
					{
						$reprocessed_cart_data['shopping_cart']['price'] += $sum;
						$reprocessed_cart_data['shopping_cart']['is_used'] = true;
					}
				}
				$paypal_vars += array(
					'item_name' => apply_filters( 'the_title', $product->name, $product->product_id ),					
					'src' => '1'
				);			
				if ($reprocessed_cart_data['shopping_cart']['is_used']) 
				{
					$paypal_vars += array(
						"a1" => $this->convert($reprocessed_cart_data['shopping_cart']['price']),
						"p1" => $reprocessed_cart_data['shopping_cart']['length'],
						"t1" => $reprocessed_cart_data['shopping_cart']['unit'],
					);
				}				
				if ($reprocessed_cart_data['subscription']['is_used']) 
				{
					$paypal_vars += array(
						"a3" => $this->convert($reprocessed_cart_data['subscription']['price']),
						"p3" => $reprocessed_cart_data['subscription']['length'],
						"t3" => $reprocessed_cart_data['subscription']['unit'],
					);					
					if ($reprocessed_cart_data['subscription']['times_to_rebill'] > 1) 
					{
						$paypal_vars += array(	'srt' => $reprocessed_cart_data['subscription']['times_to_rebill'],	);
					}
				}
			} 
		} 
		elseif ( $this->payment_type == 'surcharge' )
		{		
			$payment_document = usam_get_payment_document( $this->payment_number, 'number' );
			
			$paypal_vars += array(
				'upload' => '1',
				'cmd' => '_ext-enter',
				'redirect_cmd' => '_cart',
				'handling_cart' => 0
			);								
			$paypal_vars['item_name_1'] = sprintf( __( "Оплата счета %s за заказ %s", 'usam'), $payment_document['number'], $order['id']);
			$paypal_vars['amount_1'] = $this->convert( $payment_document['sum'] );
			$paypal_vars['quantity_1'] = 1;
			$paypal_vars['shipping_1'] = 0;
			$paypal_vars['shipping2_1'] = 0;
			$paypal_vars['handling_1'] = 0;		
			$paypal_vars['tax_cart'] = 0;		
		}
		else
		{
			if ( $buy_now )
				$paypal_vars['cmd'] = '_xclick';
			else
				$paypal_vars += array(
					'upload' => '1',
					'cmd' => '_ext-enter',
					'redirect_cmd' => '_cart',
				);		
			$handling = $order['shipping'];			
			$paypal_vars += array(
				'handling_cart' => $this->convert( $handling )
			);		
			$i = 1;				
			if ( !$aggregate ) 
			{	
				foreach ($cart_contents as $product) 
				{		
					$item_number = usam_get_product_meta( $product->product_id, 'sku' );
					if ( ! $item_number )
						$item_number = $product->product_id;				
					$paypal_vars += array(
						"item_name_$i" => apply_filters( 'the_title', $product->name, $product->product_id ),
						"amount_$i" => $this->convert($product->price),
						"quantity_$i" => $product->quantity,
						"item_number_$i" => $item_number,
					);							
					++$i;
				}							
			} 
			else 				
			{			
				$paypal_vars['item_name_'.$i] = __( "Ваша корзина", 'usam');
				$paypal_vars['amount_'.$i] = $this->convert( $this->payment['sum'] );
				$paypal_vars['quantity_'.$i] = 1;
				$paypal_vars['shipping_'.$i] = 0;
				$paypal_vars['shipping2_'.$i] = 0;
				$paypal_vars['handling_'.$i] = 0;
			}
			$total_tax = usam_get_tax_amount_order( $order['id'] );			
			$paypal_vars['tax_cart'] = $this->convert( $total_tax );		
		}	
		return apply_filters( 'usam_paypal_standard_vars', $paypal_vars );
	}
	
	private function import_ipn_data( $received_values ) 
	{
		if ( ! $this->purchase_log->exists() )
			return;
		$order_id = $this->purchase_log->get('id');
		$field_mapping = array(
			'firstname' => 'first_name',
			'lastname'  => 'last_name',
			'country'   => 'address_country_code',
			'email'     => 'payer_email',
			'city'      => 'address_city',
			'address'   => 'address_street',
			'phone'     => 'contact_phone',
		);		
		$customer_data_new = array();
		$properties = usam_get_properties( array('type' => 'order','fields' => 'code') );	
		foreach ( array( 'billing', 'shipping' ) as $type ) 
		{		
			foreach ( $field_mapping as $key => $value ) 
			{
				$code = $type . $key;				
				if ( !empty( $received_values[$value]) && in_array($code, $properties) )
					usam_update_order_metadata( $order_id, $code, $received_values[$value] );
			}
		}			
	}	
	
	/**
	 * Получить номер платежного документа
	 */
	protected function get_payment_number( ) 
	{ 
		if (isset($this->gateway_data['invoice']))
			return $this->gateway_data['invoice'];
		else
			return false;
	}

	
	/**
	 * Процесс уведомления из торгового шлюза. Проверяет данные и возвращает ответ.
	*/
	/*
    [mc_gross_1] => 532.00
    [mc_gross_2] => 2176.00
    [mc_handling1] => 0.00
    [mc_handling2] => 0.00
    [num_cart_items] => 2
    [payer_id] => AJGAF89TJ795A
    [address_country_code] => RU
    [ipn_track_id] => 3dc431da4294
    [address_zip] => 
    [invoice] => PH0000001028
    [charset] => KOI8_R
    [payment_gross] => 
    [address_status] => unconfirmed
    [address_street] => ֌. 󅒈υКƒρѠĮ25/7
    [verify_sign] => Ai1PaghZh5FmBLCDCTQpwG8jB264A77MogZRROecEoQZPpmTpD89iMrZ
    [tax1] => 0.00
    [tax2] => 0.00
    [mc_shipping] => 0.00
    [txn_type] => cart
    [receiver_id] => QSZJYP433Z7F4
    [payment_fee] => 
    [item_number1] => 814F02-28
    [item_number2] => 9H10-0.28
    [mc_currency] => RUB
    [transaction_subject] => 
    [custom] => 
    [protection_eligibility] => Eligible
    [quantity1] => 1
    [quantity2] => 1
    [address_country] => Russia
    [payer_status] => verified
    [first_name] => 
    [item_name1] => 
    [item_name2] => 
    [address_name] =>
    [mc_gross] => 2708.00
    [mc_shipping1] => 0.00
    [mc_shipping2] => 0.00
    [payment_date] => 07:03:30 Jun 01, 2016 PDT
    [payment_status] => Completed
    [business] => b@radov39.ru
    [mc_handling] => 0.00
    [last_name] =>
    [address_state] =>
    [txn_id] => 91C00283HG200184J
    [mc_fee] => 115.61
    [resend] => true
    [payment_type] => instant
    [notify_version] => 3.8
    [payer_email] => ke_ry_@mail.ru
    [receiver_email] => b@radov39.ru
    [address_city] => 
    [tax] => 0.00
    [residence_country] => RU
*/
	protected function parse_gateway_notification() 
	{			
		/// PayPal первый ожидает, что переменные IPN должны быть возвращены ему в течение 30 секунд, поэтому мы делаем это в первую очередь.
		$this->error = 'IPN';		
		$received_values = array();
		$received_values['cmd'] = '_notify-validate';
  		$received_values += $this->gateway_data;
		$options = array(
			'timeout' => 20,
			'body' => $received_values,
			'user-agent' => USAM_VERSION
		);

		$response = wp_remote_post("https://www.paypal.com/cgi-bin/webscr", $options);
		if ( is_wp_error( $response ) ) 
		{
			$this->set_error( __("Ошибка wp_remote_post","usam") );
			return false;			
		}		
		if( 'VERIFIED' == $response['body'] ) 
			$this->payment_number = $received_values['invoice'];		
		else 
		{
			$this->notification_errors( 'renouncement' );
			update_payment_document(['status' => 5]);
			return false;		
		}	
		$status = 5;
		switch ( strtolower( $received_values['payment_status'] ) ) 
		{
			case 'pending':
				$status = 6; // В ожидании
			break;
			case 'completed': //  оплата была завершена
				$payment['date_payed'] = $received_values['payment_date'];
				$status = 3;
			break;
			case 'reversed': //  Средства были удалены из вашего счета и возвращаются покупателю. 
				$status = 4;
			break;
			case 'refunded': // возвращен платеж.
				$status = 4;
			break;
			case 'failed': // оплата не удалось
				$status = 2;
			break;
			case 'denied':   // оплата была отклонена
				$status = 2;
			break;			
		}
		do_action( 'usam_paypal_standard_ipn', $received_values, $this );		
	
		if( strtolower($received_values['receiver_email']) == $this->gateway_system['business'] || strtolower($received_values['business']) == $this->gateway_system['business'] ) 
		{
			$payment['status']     = $status;
			$payment['sum']        = $received_values['mc_gross'];		
			$payment['currency']   = $received_values['mc_currency'];			
			$payment['transactid'] = $received_values['txn_id']; // номер документа в платежной системе
			
			switch($received_values['txn_type']) 
			{
				case 'cart':
				case 'express_checkout':
				case 'web_accept':					
					if ( isset($received_values['custom'] ) && $received_values['custom'] == 'buy_now' ) 
						$this->import_ipn_data( $received_values );					
					$this->update_payment_document( $payment );						
				break;
				case 'subscr_signup':
				case 'subscr_payment':	// Подписка оплачена				
					$this->update_payment_document( $payment );						
					$products = usam_get_products_order( $this->purchase_log->get('id') );	
					foreach($products as $product) 
					{
						if( $product->subscribed ) 
						{
							do_action('usam_activate_subscription', $product->product_id, $received_values['subscr_id']);
							do_action('usam_activated_subscription',$product->product_id, $this );
						}
					}
				break;
				case 'subscr_cancel':
					do_action( 'usam_paypal_standard_deactivate_subscription', $received_values['subscr_id'], $this );
				case 'subscr_eot':
				case 'subscr_failed':
					// Проблема с подпиской
				break;
				default:
				break;
			}
		}					
    }
	
	protected function get_default_option( ) 
	{
		return array( 'onpay_debug' => 0, 'business' => '', 'url' => 0, 'ship' => 0, 'address_override' => 0, 'currency' => 'USD');
	}
	
	public function get_form() 
	{			   
	    $output = "
				<div class ='edit_form__item'>
					<div class ='edit_form__item_name'><label for='option_gateway_business'>".esc_html__('Имя пользователя', 'usam').":</label></div>
					<div class ='edit_form__item_option'>
						<input type='text' id='option_gateway_business' size='40' value='".$this->gateway_system['business']."' name='gateway_handler[business]' />
						<p class='description'>" . __('Это ваш PayPal адрес электронной почты', 'usam') . "</p>
					</div>
				</div>
				<div class ='edit_form__item'>
					<div class ='edit_form__item_name'><label>".esc_html__('Передать данные клиента', 'usam').":</label></div>
					<div class ='edit_form__item_option'>
						<input type='radio' value='1' name='gateway_handler[ship]' id='paypal_ship1' ".checked($this->gateway_system['ship'], 1, false)." /> <label for='paypal_ship1'>".__('Да', 'usam')."</label> &nbsp;
						<input type='radio' value='0' name='gateway_handler[ship]' id='paypal_ship2' ".checked($this->gateway_system['ship'], 0, false)." /> <label for='paypal_ship2'>".__('Нет', 'usam')."</label>
						<p class='description'>".__( "Примечание: Если Вы хотите отправить данные клиента в PaylPal, то поставте да", 'usam') . ".</p>
					</div>
				</div>
				<div class ='edit_form__item'>
					<div class ='edit_form__item_name'><label for='option_gateway_pass'>".esc_html__('Заменить адрес', 'usam').":</label></div>
					<div class ='edit_form__item_option'>
						<input type='radio' value='1' name='gateway_handler[address_override]' id='address_override1' ".checked($this->gateway_system['address_override'], 1, false)." /> <label for='address_override1'>".__('Да', 'usam')."</label> &nbsp;
						<input type='radio' value='0' name='gateway_handler[address_override]' id='address_override2' ".checked($this->gateway_system['address_override'], 0, false)." /> <label for='address_override2'>".__('Нет', 'usam')."</label>
						<p class='description'>".__( "Если у ваших клиентов уже есть аккаунт в PayPal, то PayPal будет пытаться заменить адрес указанный в заказе на данные аккаунта.", 'usam') . "</p>
					</div>
				</div>";			
		return $output;
	}	
}