<?php
class USAM_Order_Status_Change
{
	private $purchase_log;
	public function __construct( ) 
	{	
		add_action( 'usam_update_order_status', [$this, 'update_order_status'], 10, 4 );
		add_action( 'usam_order_before_delete', [$this, 'order_delete'], 10, 2 );			
		add_action( 'usam_order_paid', [$this, 'order_paid']);
	}
		
	function order_paid( $t ) 
	{		
		if ( get_option('usam_product_reserve_condition', '') === 'p' )
		{
			$data = $t->get_data();
			require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
			$documents = usam_get_shipping_documents(['order' => 'DESC', 'order_id' => $data['id'], 'cache_results' => true, 'cache_products' => true]);
			foreach( $documents as $document )
			{
				$shipped = new USAM_Shipped_Document( $document->id );
				$shipped->reserve_products();
			}
		}
	}	
	
	function order_delete( $order, $order_id ) 
	{		
		$this->update_crm_purchased( $order, true );	
	}

	// Действие при обновлении статуса заказа в Журнале продаж. Обрабатывает статусы заказов при их получении. Уменьшает запасы на складе
	function update_order_status( $order_id, $status, $old_status, $purchase_log ) 
	{		
		global $wpdb;
		$this->purchase_log = $purchase_log;	
		if ( !$this->purchase_log->is_creature() )
		{
			$this->send_email_customer( );	// отправить письмо покупателю	
			$this->process_transaction_coupon( );		
		}			
		if ( $this->purchase_log->is_closed_order() )
		{		
			do_action( 'usam_order_close', $order_id, $purchase_log );	
			$user_id = $this->purchase_log->get( 'user_ID' );				
			if ( $user_id != 0 )
			{
				$discount = usam_get_accumulative_discount_customer( 'price', $user_id );		
				update_user_meta( $user_id, 'usam_accumulative_discount', $discount );	

				$coupon_name = (string)usam_get_order_metadata( $order_id, 'coupon_name');	
				$coupon_data = usam_get_coupon( $coupon_name, 'coupon_code' );				
				if ( !empty($coupon_data['amount_bonuses_author']) )
				{					
					$sum = (float)preg_replace("/[^0-9\\.,]/", '', $coupon_data['amount_bonuses_author']);
					if ( stripos($coupon_data['amount_bonuses_author'],'%') !== false )
						$sum = round($sum*$this->purchase_log->get( 'totalprice' )/100, 0);						
					if( $coupon_data['coupon_type'] == 'referral' )
						$description = sprintf( __('Выплата референту с кодом %s после закрытия заказ','usam'), mb_strtoupper($coupon_name) );
					else
						$description = sprintf( __('Использование купона %s в заказе','usam'), $coupon_name);
					usam_insert_bonus(['object_id' => $order_id, 'object_type' => 'order', 'sum' => $sum, 'description' => $description], $user_id );				
				}							
			}
			$this->update_crm_purchased( $this->purchase_log->get_data() );
			$this->process_transaction_rules_coupon( );				
			usam_change_order_status_paid( $this->purchase_log->get('id') );
			usam_delete_order_metadata($order_id, 'date_pay_up' );	
							
			$manager_id = $this->purchase_log->get('manager_id');
			$motivation_employees = get_option('usam_motivation_employees');
			if ( !empty($motivation_employees['active']) && !empty($motivation_employees['type_price']) && $manager_id)
			{
				$bonus = 0;
				$products = usam_get_products_order( $this->purchase_log->get('id') );								
				foreach( $products as $product )
				{
					$sum = ($product->price - usam_get_product_price( $product->product_id, $motivation_employees['type_price'] ))*$product->quantity;
					if ( $sum )
						$bonus += $sum;
				}
				if ( $bonus > 0 )
				{
					$bonus_card = usam_get_bonus_card( $manager_id, 'user_id' );												
					if ( !empty($bonus_card) && $bonus_card['status'] == 'active' )
					{
						if ( $bonus_card['percent'] > 0 )							
							$bonus = round($bonus * $bonus_card['percent'] / 100, 0);							
						usam_insert_bonus(['object_id' => $order_id, 'object_type' => 'order', 'sum' => $bonus, 'description' => usam_get_bonus_type( 'manager_order' )], $manager_id );							
					}
				}					
			}	
			$this->change_status_shipped_document( 'shipped' );
		}	
		elseif ( usam_check_object_is_completed( $this->purchase_log->get('status'), 'order' ) )
		{
			$this->remove_product_reserve( );	
			usam_delete_order_metadata($order_id, 'date_pay_up' );	
			if ( $this->purchase_log->get('status') == 'canceled' )
			{
				$bonuses = usam_get_used_bonuses_order( $order_id );
				$user_id = $this->purchase_log->get( 'user_ID' );
				if ( $bonuses && $user_id )
				{
					usam_insert_bonus(['object_id' => $order_id, 'object_type' => 'order', 'sum' => $bonuses, 'description' => __('Возврат бонусов при отмене заказа','usam')], $user_id );
				}
			}								
		}			
		if ( $status == 'no_connection' )
		{
			$this->unable_to_contact();
		}
		if ( $old_status == 'closed' )
		{
			$this->update_crm_purchased( $this->purchase_log->get_data() );
			usam_delete_bonuses_transaction(['order_id' => $order_id]);
		}
		usam_update_object_count_status( $status, 'order' );
		usam_update_object_count_status( $old_status, 'order' );
	}

	public function update_crm_purchased( $order, $delete = false )
	{
		global $wpdb;		
		if ( $order['contact_id'] )
		{
			if ( $delete )
			{
				$last_order_date = $wpdb->get_var("SELECT date_insert FROM ".USAM_TABLE_ORDERS." WHERE status='closed' AND contact_id=".$order['contact_id']." ORDER BY id DESC" );
				if ( !$last_order_date )
					$last_order_date = NULL;
			}
			else
				$last_order_date = $order['date_insert'];
			$orders = $wpdb->get_row("SELECT COUNT(*) AS number_orders, SUM(totalprice) AS total_purchased FROM ".USAM_TABLE_ORDERS." WHERE status='closed' AND contact_id=".$order['contact_id']."" );			
			usam_update_contact($order['contact_id'], ['total_purchased' => $orders->total_purchased, 'number_orders' => $orders->number_orders, 'last_order_date' => $last_order_date]);			
		}	
		if ( $order['company_id'] )
		{
			if ( $delete )
			{
				$last_order_date = $wpdb->get_var("SELECT date_insert FROM ".USAM_TABLE_ORDERS." WHERE status='closed' AND company_id=".$order['company_id']." ORDER BY id DESC" );
				if ( !$last_order_date )
					$last_order_date = NULL;
			}
			else
				$last_order_date = $order['date_insert'];
			$orders = $wpdb->get_row("SELECT COUNT(*) AS number_orders, SUM(totalprice) AS total_purchased FROM ".USAM_TABLE_ORDERS." WHERE status='closed' AND company_id='".$order['company_id']."'" );
			usam_update_company($order['company_id'], ['total_purchased' => $orders->total_purchased, 'number_orders' => $orders->number_orders, 'last_order_date' => $last_order_date]);
		}
	}

	public function change_status_shipped_document( $status )
	{
		$document_update = array('status' => $status);			
		$order_id = $this->purchase_log->get('id');
		$documents = usam_get_shipping_documents_order( $order_id );	
		foreach( $documents as $document  )
		{	
			if ( $status != $document->status )
				usam_update_shipped_document( $document->id, $document_update );
		}
	}
	
	public function remove_product_reserve(  )
	{
		$this->change_status_shipped_document( 'canceled' );
		$documents = usam_get_shipping_documents_order( $this->purchase_log->get('id') );
		foreach( $documents as $document )
		{
			$shipped = new USAM_Shipped_Document( $document->id );
			$shipped->remove_all_product_from_reserve();
		}
	}
	
	public function unable_to_contact(  )
	{
		$order = $this->purchase_log->get_data();		
		$event = array( 'title' => sprintf( __('Связаться с клиентом о заказе №%s','usam'), $order['id'] ), 'user_id' => $order['manager_id'] );	
		
		$reminder_date = date("Y-m-d H:i:s", mktime( date('H'),date('i'),0,date('m'),date('d')+1,date('Y')));
		$event_id = usam_insert_system_event( $event, ['object_type' => 'order', 'object_id' => $order['id']], $reminder_date );
	}
	
	// отправка письма при изменение статуса заказа
	public function send_email_customer(  )
	{		
		if( apply_filters( 'usam_prevent_notification_change_status', true ) ) 	
		{			
			$notification = new USAM_Сustomer_Notification_Change_Order_Status( $this->purchase_log );			
			$email_sent = $notification->send_mail();
			$sms_sent = $notification->send_sms();	
		}
	}
	
	//процессе обработки купона сделки
	function process_transaction_coupon( ) 
	{
		global $wpdb;	

		$coupon_name = (string)usam_get_order_metadata( $this->purchase_log->get('id'), 'coupon_name');	
		if ( $coupon_name ) 
		{
			$_coupon = new USAM_Coupon( $coupon_name, 'coupon_code' );
			$coupon_data = $_coupon->get_data();
			if ( $coupon_data['active'] )
			{							
				if ( $coupon_data['max_is_used'] > 0 && $coupon_data['max_is_used'] <= $coupon_data['is_used'] )
					$coupon_data['active'] = 0;
			}
			$coupon_data['is_used']++;				
			$_coupon->set( $coupon_data );
			$_coupon->save();			
		}
	}
	
	//процесс обработки правил купонов
	function process_transaction_rules_coupon( ) 
	{
		$order = $this->purchase_log->get_data();
		$coupons_roles = usam_get_coupons_rules( array('rule_type' => 'order', 'active' => 1, 'user_id' => $order['user_ID'] ) );	
		if ( !empty($coupons_roles) ) 
		{			
			$totalprice = (float)$order['totalprice'];				
			$order_id = $this->purchase_log->get( 'id' );	
			$location = usam_get_order_customerdata( $order_id, 'location' );
			if ( !empty($location) )
				$current_location_ids = usam_get_address_locations( $location, 'id' );
			else
				$current_location_ids = array();			
			
			$properties = usam_get_properties(['type' => 'order', 'active' => 1, 'field_type' => ['mobile_phone', 'email'], 'fields' => ['code','field_type']]);
			$email = '';
			$phone = '';
			foreach ( $properties as $property ) 
			{
				$value = usam_get_order_customerdata( $order_id, $property->code );
				if ( $value )
				{
					if ( $property->field_type == 'email' )
						$email = $value;		
					elseif ( $property->field_type == 'mobile_phone' )
						$phone = $value;
				}
			}	
			
			foreach( $coupons_roles as $key => $rule )
			{	
				if ( $rule['totalprice'] <= $totalprice )
				{			
					$coupon = usam_get_coupon( $rule['coupon_id'] );			
					if (empty($coupon))
						continue;
					
					if ( !empty($rule['sales_area']) )
					{							
						if ( empty($current_location_ids) )						
							continue;									
						if ( !usam_locations_in_sales_area( $rule['sales_area'], $current_location_ids ) )
							continue;					
					}		
					switch( $rule['discount_type'] )
					{			
						case 0:
							$coupon['value'] = (double)$rule['discount'];
							$coupon['is_percentage'] = 0;
						break;
						case 1:
							$coupon['value'] = (double)$rule['discount'];
							$coupon['is_percentage'] = 1;
						break;
						case 2:
							$coupon['value'] = (double) round($rule['discount']*$totalprice/100, 0);						
							$coupon['is_percentage'] = 0;
						break;
						case 3:
							$coupon['value'] = 0;
							$coupon['is_percentage'] = 2;
						break;
					}
					if ( $rule['percentage_of_use'] && $coupon['is_percentage'] === 0 )
					{
						$order_sum = (double) round( $coupon['value']*100/$rule['percentage_of_use'], 0);	
						$condition = array( 'type' => 'simple', 'property' => 'subtotal', 'logic' => 'greater', 'value' => $order_sum );						
						if ( !empty($coupon['condition']) )
						{
							$coupon['condition'][] = array( 'logic_operator' => 'AND' );
						}
						$coupon['condition'][] = $condition;
					}
					else
						$order_sum = 0;					
					
					$coupon['coupon_code']   = usam_generate_coupon_code( $rule['format'], $rule['type_format'] );
					$coupon['description']   = sprintf( __('Купон создан заказом №%s из правила %s.', 'usam'), $order_id, '"'.$rule['title'].'"' );
					$coupon['active']        = 1;				
					$coupon['start']         = date( "Y-m-d H:i:s" );			
					$coupon['end_date']        = date( 'Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d")+$rule['day'], date("Y")) );
					$coupon['coupon_type']   = 'coupon';										
					if ( !empty($rule['customer']) )	
						$coupon['customer'] = 0;	
					elseif ( is_numeric($rule['customer']) )					
						$coupon['customer'] = $rule['customer'];					
					elseif ( $rule['customer'] == 'order' )
					{
						$user = $this->purchase_log->get( 'user_ID' );							
						$coupon['customer'] = $user;	
					}
					usam_insert_coupon( $coupon );				
								
					$args = [							
						'total_price'     => $totalprice,
						'shop_name'       => get_option( 'blogname' ),
						'coupon_sum'      => $coupon['value'],						
						'coupon_code'     => $coupon['coupon_code'],
						'coupon_day'      => $rule['day'],
						'order_sum'       => $order_sum,		
						'order_id'        => $order_id,
						'procent'         => $rule['discount'],
					];					
					$args = array_map( 'esc_html', $args );	
					$tokens = array_keys( $args );
					$values = array_values( $args );
					foreach ( $tokens as &$token ) 
					{	
						$token = "%{$token}%";
					}
					$links = [['object_id' => $order_id, 'object_type' => 'order']];
					if ( $email != '' && !empty($rule['message']) )
					{
						$html = str_replace( $tokens, $values, $rule['message'] );
						$html = wpautop( $html );		
						$message = str_replace( "<br />", '<br /><br />',$html);							
						usam_send_mail_by_id(['message' => $message, 'title' => $rule['subject'], 'email' => $email], [], $links);
					}
					$sms_message = str_replace( $tokens, $values, $rule['sms_message'] );	
					if ( $rule['sms_message'] && $phone )
						usam_add_send_sms(['phone' => $phone, 'message' => $sms_message], $links);					
					return true;
				}				
			}		
		}
	}	
}
new USAM_Order_Status_Change();