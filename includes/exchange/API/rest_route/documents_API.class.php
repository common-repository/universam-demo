<?php 
class USAM_Documents_API extends USAM_API
{			
	public static function get_orders( WP_REST_Request $request )
	{		
		$parameters = self::get_parameters( $request );	
		self::get_query_vars( $parameters, $parameters );		
		if ( !current_user_can('universam_api') && !current_user_can('view_orders') )
		{
			$user_id = get_current_user_id();
			if ( !$user_id )
				return new WP_Error( 'no_user', 'Invalid user id', ['status' => 404]);
			self::$query_vars['user_id'] = $user_id;
		}	
		elseif ( !empty($parameters['user']) )
		{   
			if ( is_string($parameters['user']) && $parameters['user'] == 'my' )
				self::$query_vars['user_id'] = get_current_user_id();
			elseif ( is_numeric($parameters['user']) )
				self::$query_vars['user_id'] = absint($parameters['user']);
			else
				self::$query_vars['user_id'] = array_map('intval', (array)$parameters['user']);
		}		
		if ( isset(self::$query_vars['fields']) )
			unset(self::$query_vars['fields']);		
						
		if ( !empty($parameters['manager']) )
			self::$query_vars['manager_id'] = $parameters['manager'];		
		if ( !empty($parameters['seller']) )
			self::$query_vars['bank_account_id'] = $parameters['seller'];
		if ( !empty($parameters['code_price']) )
			self::$query_vars['type_prices'] = $parameters['code_price'];				
		if ( !empty($parameters['payer']) )
			self::$query_vars['type_payer'] = $parameters['payer'];		
		if ( !empty($parameters['payment']) )
			self::$query_vars['payment_gateway'] = $parameters['payment'];		
		if ( !empty($parameters['shipping']) )
			self::$query_vars['shipping_method'] = $parameters['shipping'];								
		if ( !empty($parameters['campaign']) )			
			self::$query_vars['meta_query'][] = ['key' => 'campaign_id', 'value' => $parameters['campaign'], 'compare' => 'IN'];		
		self::get_digital_interval_for_query($parameters, ['sum', 'prod', 'coupon_v', 'bonus', 'tax', 'shipping_sum']);
		self::get_string_for_query($parameters, ['coupon_name'], 'meta_query');
		self::get_meta_for_query($parameters, 'order');	
		self::get_date_interval_for_query($parameters, ['date_insert']);					
		self::$query_vars['orderby'] = $parameters['orderby'];
		self::$query_vars['order'] = $parameters['order'];	
			
		if ( isset($parameters['exchange']) )
			self::$query_vars['exchange'] = $parameters['exchange'];		
		self::$query_vars['cache_meta'] = true;
		if ( !empty($parameters['fields']) )	
		{				
			if ( in_array('products', $parameters['fields']) )
				self::$query_vars['cache_order_products'] = true;		
			if ( in_array('shipping', $parameters['fields']) )
				self::$query_vars['cache_order_shippeds'] = true;
			if ( in_array('payments', $parameters['fields']) )
				self::$query_vars['cache_order_payments'] = true;						
		}	
		$query = new USAM_Orders_Query( self::$query_vars );	
		$items = $query->get_results();	
		if ( !empty($items) )
		{ 
			$properties_args = ['access' => true, 'active' => 1, 'type' => 'order', 'orderby' => ['group', 'sort'], 'add_fields' => ['group_id'], 'cache_results' => true, 'cache_group' => true];
			$properties = usam_get_properties( $properties_args );
			$count = $query->get_total();
			foreach ( $items as $key => &$item ) 
			{		
				if ( isset($item->date_insert) )
				{
					$item->date_insert = usam_local_date(strtotime($item->date_insert), 'c');
					$item->date = usam_local_formatted_date( $item->date_insert );
				}			
				if ( isset($item->totalprice) )
					$item->totalprice_currency = usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price]);
				if ( isset($item->shipping) )
					$item->shipping_currency = usam_get_formatted_price($item->shipping, ['type_price' => $item->type_price]);				
				if ( isset($item->date_status_update) )
					$item->date_status_update = usam_local_date(strtotime($item->date_status_update), 'c');	
				if ( isset($item->date_paid) )
					$item->date_paid = usam_local_date(strtotime($item->date_paid), 'c');			
				if ( isset($item->type_price) )
					$item->currency = usam_get_currency_sign_price_by_code( $item->type_price );				
				if ( isset($item->last_comment) )
				{					
					$item->last_comment_user_foto = usam_get_contact_foto( $item->last_comment_user, 'user_id' );
					$item->last_comment_user_name = usam_get_manager_name($item->last_comment_user);
					$item->display_last_comment_date = usam_local_formatted_date( $item->last_comment_date );
					$item->last_comment = nl2br($item->last_comment);
				}	
				$item->review_id = (int)usam_get_order_metadata($item->id, 'review_id');
				if ( !empty($parameters['fields']) )
				{
					if ( in_array('status_data', $parameters['fields']) && isset($item->status) )
					{
						$object_status = usam_get_object_status_by_code( $item->status, 'order' );
						$item->status_is_completed = (int)usam_check_object_is_completed( $item->status, 'order' );
						$item->status_name = isset($object_status['name'])?$object_status['name']:'';
						$item->status_color = isset($object_status['color'])?$object_status['color']:'';
						$item->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';						
						$item->can_pay = false;
						if ( isset($item->paid) )
						{
							$item->payment_status = usam_get_order_payment_status_name( $item->paid );
							$item->date_pay_up = usam_get_order_metadata($item->id, 'date_pay_up' );						
							if ( $item->paid != 2 && !$item->status_is_completed )
							{									
								if ( !empty($item->date_pay_up) && strtotime($item->date_pay_up) >= time() )
									$item->can_pay = true;
							}	
						}						
					}
					if ( in_array('products', $parameters['fields']) )
					{
						$item->products = usam_get_products_order( $item->id );
						$item->price_without_discount = 0;
						$item->discounted_price = 0;
						foreach( $item->products as $k => &$product )
						{
							$product->sku = usam_get_product_meta( $product->product_id, 'sku' );
							if ( current_user_can('universam_api') )
								$product->code = usam_get_product_meta( $product->product_id, 'code' );
							$product->date_insert = usam_local_date(strtotime($product->date_insert), 'c');	
							$product->quantity_unit_measure = usam_get_formatted_quantity_product_unit_measure( $product->quantity, $product->unit_measure );
							$product->small_image = usam_get_product_thumbnail_src($product->product_id, 'small-product-thumbnail');	
							$product->price_currency = usam_get_formatted_price($product->price, ['type_price' => $item->type_price]);
							$product->url = get_permalink( $product->product_id );
							$product->old_price_currency = usam_get_formatted_price($product->old_price, ['type_price' => $item->type_price]);
							$product->total = $product->price * $product->quantity;		
							$product->total_currency = usam_get_formatted_price($product->price * $product->quantity, ['type_price' => $item->type_price]);	
							$item->price_without_discount += $product->old_price * $product->quantity;	
							$item->discounted_price += $product->price * $product->quantity;							
						}		
						$item->discount = $item->price_without_discount - $item->discounted_price;		
						$item->price_without_discount_currency = usam_get_formatted_price($item->price_without_discount, ['type_price' => $item->type_price]);
						$item->discounted_price_currency = usam_get_formatted_price($item->discounted_price, ['type_price' => $item->type_price]);
						$item->discount_currency = usam_get_formatted_price($item->discount, ['type_price' => $item->type_price]);					
					}					
					if ( in_array('shipping', $parameters['fields']) )
					{
						$item->shipping_documents = self::get_shipping_documents_order( $item->id, $item->type_price );
					}
					if ( in_array('payments', $parameters['fields']) )
					{
						$item->payments = self::get_payment_documents_order( $item->id );
					}
					if ( in_array('customer', $parameters['fields']) )
					{
						$item->company = [];
						$item->contact = [];
						if ( $item->company_id )
							$item->company = usam_get_company( $item->company_id );
						if ( $item->contact_id )
							$item->contact = usam_get_contact( $item->contact_id );
					}
					if ( in_array('manager', $parameters['fields']) )
						$item->manager = usam_get_contact( $item->manager_id, 'user_id' );
					if ( in_array('taxes', $parameters['fields']) )
						$item->taxes = usam_get_order_product_taxes( $item->id );	
					if ( in_array('properties', $parameters['fields']) )
					{
						$item->properties = [];	
						foreach( $properties as $property )
						{						
							$type_payers = array_map('intval', (array)usam_get_array_metadata($property->group_id, 'property_group', 'type_payer'));
							if ( in_array($item->type_payer, $type_payers) )
							{
								$p = clone $property;
								if ( $p->field_type == 'checkbox' )
									$p->value = usam_get_array_metadata( $item->id, 'order', $p->code );
								else
									$p->value = usam_get_order_metadata( $item->id, $p->code );
								$item->properties[$p->code] = usam_format_property_api( $p );
							}
						}
					}
				}				
			}
			$items = apply_filters( 'usam_api_orders', $items, $parameters );			
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];		 
		return $results;
	}
	
	public static function get_payments( WP_REST_Request $request )
	{		
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
		
		if ( !current_user_can('universam_api') )
		{
			$user_id = get_current_user_id();
			if ( !$user_id )
				return new WP_Error( 'no_user', 'Invalid user id', ['status' => 404]);
			self::$query_vars['user_id'] = $user_id;
		}
		if ( !empty($parameters['export']) )
		{
			foreach ( $parameters['export'] as $export ) 
			{
				if ( $export )
					self::$query_vars['meta_query'] = [['key' => 'exchange','value' => 1, 'compare' => '=']];					
				else
					self::$query_vars['meta_query'] = ['relation' => 'OR', ['key' => 'exchange', 'compare' => "NOT EXISTS"], ['key' => 'exchange','value' => 0, 'compare' => '=']];
			}
		}		
		self::get_digital_interval_for_query($parameters, ['sum']);		
		self::$query_vars['cache_meta'] = true;			
		require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
		$query = new USAM_Payments_Query( self::$query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			$count = $query->get_total();
			$problems = usam_get_standard_delivery_problems();	
			foreach ( $items as $key => &$item ) 
			{		
				if ( isset($item->date_insert) )
					$item->date_insert = get_date_from_gmt($item->date_insert);
				if ( isset($item->sum) )
					$item->sum_currency = usam_get_formatted_price($item->sum);	
				if ( isset($item->status) )
				{
					$object_status = usam_get_object_status_by_code( $item->status, 'payment' );
					$item->status_is_completed = (int)usam_check_object_is_completed( $item->status, 'payment' );
					$item->status_name = isset($object_status['name'])?$object_status['name']:'';
					$item->status_color = isset($object_status['color'])?$object_status['color']:'';
					$item->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';					
				}
				if ( isset($item->last_comment) )
				{
					$item->last_comment_user_foto = usam_get_contact_foto( $item->last_comment_user, 'user_id' );
					$item->last_comment_user_name = usam_get_manager_name($item->last_comment_user);
					$item->display_last_comment_date = usam_local_formatted_date( $item->last_comment_date );
					$item->last_comment = nl2br($item->last_comment);
				}					
				if ( $item->date_payed )
					$item->date_payed = get_date_from_gmt($item->date_payed);					
			}		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];		 
		return $results;
	}	
	
	public static function delete_payments( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		if ( !empty($parameters['args']) )
			return usam_delete_payments( $parameters['args'] );
		return false;
	}
	
	public static function save_payments( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$return = [];
		usam_update_object_count_status( false );
		$args = [];
		foreach( $parameters['items'] as $k => $item )
		{
			if ( !empty($item['date_payed']) )
				$item['date_payed'] = USAM_Request_Processing::sanitize_date( $item['date_payed'] );	
			if ( !empty($item['date_insert']) )
				$item['date_insert'] = USAM_Request_Processing::sanitize_date( $item['date_insert'] );
			if ( !empty($item['id']) )
			{
				$id = absint($item['id']);
				if( $id )
					$args['include'][] = $id;	
				$return[$k] = [];
			}
			else
			{
				$link = [];
				if ( !empty($item['document_id']) )
					$link = ['document_id' => $item['document_id'], 'document_type' => 'order'];
				$id = usam_insert_payment_document( $item, $link );				
				if ( $id )
					USAM_Documents_API::save_payment_metadata( $id, $item );				
				$return[$k] = usam_get_payment_document( $id );
				if( !empty($return[$k]['document_id']) )
				{
					$order = usam_get_order( $return[$k]['document_id'] );
					if ( !empty($order) )
						$return[$k]['order'] = ['paid' => $order['paid'], 'date_payed' => $order['date_paid']];
				}
			}			
		}	
		if ( !empty($args) )
		{
			require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
			$payments = usam_get_payments( $args );
			foreach( $payments as $payment )
			{
				foreach( $parameters['items'] as $k => $item )
				{
					if ( !empty($item['id']) && $item['id'] == $payment->id )
					{		
						$return[$k] = (array)$item;
						USAM_Documents_API::save_payment( (array)$payment, $item);
						unset($parameters['items'][$k]);
					}
				}
			}
		}
		usam_update_object_count_status( true );
		return $return;
	}
	
	private static function save_payment( $payment, $parameters ) 
	{				
		$result = false;										
		if ( !current_user_can('universam_api') && !usam_check_document_access( $payment, 'payment', 'edit' ) )
			return new WP_Error( 'forbidden', 'Forbidden', ['status' => 403]);
			
		if( !empty($parameters['status']) )
		{
			require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
			$statuses = usam_get_object_statuses(['fields' => 'internalname', 'type' => 'payment']);
			if ( !in_array($parameters['status'], $statuses))
				unset($parameters['status']);
		}		
		$result = usam_update_payment_document($payment['id'], $parameters);		
		if ( USAM_Documents_API::save_payment_metadata( $payment['id'], $parameters ) )
			$result = true;
		return $result;
	}
	
	private static function save_payment_metadata( $id, $parameters ) 
	{
		$result = false;
		if( isset($parameters['exchange_completed']) )
		{				
			$result = true;
			usam_update_payment_metadata($id, 'exchange', 1);		
			if ( !empty($parameters['date_exchange']) )		
				$date_exchange = date("Y-m-d H:i:s", strtotime($parameters['date_exchange']));
			else
				$date_exchange = date("Y-m-d H:i:s");				
			usam_update_payment_metadata($id, 'date_exchange', $date_exchange);
		}
		return $result;
	}
		
	public static function get_shippeds( WP_REST_Request $request )
	{		
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
		
		if ( !current_user_can('universam_api') )
		{
			$user_id = get_current_user_id();
			if ( !$user_id )
				return new WP_Error( 'no_user', 'Invalid user id', ['status' => 404]);
			self::$query_vars['user_id'] = $user_id;
		}						
		if ( !empty($parameters['courier_delivery']) )
			self::$query_vars['method'] = $parameters['courier_delivery'];
		if ( !empty($parameters['export']) )
		{
			foreach ( $parameters['export'] as $export ) 
			{
				if ( $export )
					self::$query_vars['meta_query'] = [['key' => 'exchange','value' => 1, 'compare' => '=']];					
				else
					self::$query_vars['meta_query'] = ['relation' => 'OR', ['key' => 'exchange', 'compare' => "NOT EXISTS"], ['key' => 'exchange','value' => 0, 'compare' => '=']];
			}
		}		
		self::get_digital_interval_for_query($parameters, ['price']);		
		self::$query_vars['cache_meta'] = true;
		self::$query_vars['cache_order_meta'] = true;		
		if ( !empty($parameters['add_fields']) )	
		{				
			if ( in_array('products', $parameters['add_fields']) || in_array('document_products', $parameters['add_fields']) )
				self::$query_vars['cache_products'] = true;		
		}	
		require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
		$query = new USAM_Shippeds_Document_Query( self::$query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			$count = $query->get_total();
			$problems = usam_get_standard_delivery_problems();	
			foreach ( $items as $key => &$item ) 
			{		
				if ( isset($item->date_insert) )
					$item->date_insert = get_date_from_gmt($item->date_insert);
				if ( isset($item->totalprice) )
					$item->totalprice_currency = usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price]);	
				if ( isset($item->status) )
					$item->status_is_completed = (int)usam_check_object_is_completed( $item->status, 'shipped' );
				if ( isset($item->type_price) )
					$item->currency = usam_get_currency_sign_price_by_code( $item->type_price );
				if ( isset($item->storage_pickup) )
					$item->pickup = self::get_pickup( $item->storage_pickup );				
				if ( isset($item->last_comment) )
				{
					$item->last_comment_user_foto = usam_get_contact_foto( $item->last_comment_user, 'user_id' );
					$item->last_comment_user_name = usam_get_manager_name($item->last_comment_user);
					$item->display_last_comment_date = usam_local_formatted_date( $item->last_comment_date );
					$item->last_comment = nl2br($item->last_comment);
				}	
				$item->date_delivery = (string)usam_get_shipped_document_metadata( $item->id, 'date_delivery' );				
				if ( $item->date_delivery )
					$item->date_delivery = get_date_from_gmt($item->date_delivery);	
				$item->readiness_date = (string)usam_get_shipped_document_metadata( $item->id, 'readiness_date' );
				if ( $item->readiness_date )
					$item->readiness_date = get_date_from_gmt($item->readiness_date);	
				$item->external_document_date = (string)usam_get_shipped_document_metadata( $item->id, 'external_document_date' ); 
				if ( $item->external_document_date )
					$item->external_document_date = get_date_from_gmt($item->external_document_date);		
				$item->external_document = (string)usam_get_shipped_document_metadata( $item->id, 'external_document' );	
				$item->exchange = usam_get_shipped_document_metadata( $item->id, 'exchange' );				
				$item->note = (string)usam_get_shipped_document_metadata($item->id, 'note');
				$delivery_problem = usam_get_shipped_document_metadata( $item->id, 'delivery_problem' );				
				$item->problem = isset($problems[$delivery_problem])?$problems[$delivery_problem]:$delivery_problem;				
				if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
				{
					$company = usam_get_company( $item->seller_id );
					$item->seller = [];
					if ( $company )
						$item->seller = $company;
				}
				if ( !empty($parameters['add_fields']) )
				{
					if ( in_array('products', $parameters['add_fields']) )
					{
						$item->products = self::get_products_shipped_document( $item->id, $item->type_price );
						foreach ( $item->products as &$product ) 
						{
							$product->quantity = usam_get_formatted_quantity_product( $product->quantity, $product->unit_measure );
							$product->reserve = usam_get_formatted_quantity_product( $product->reserve, $product->unit_measure );								
						}
					}
					if ( in_array('document_products', $parameters['add_fields']) )
					{
						$item->products = self::get_products_shipped_document( $item->id, $item->type_price );						
						$user_columns = usam_get_user_columns( 'shipped' );
						foreach ( $item->products as &$product ) 
						{
							$product->quantity = usam_get_formatted_quantity_product( $product->quantity, $product->unit_measure );
							$product->reserve = usam_get_formatted_quantity_product( $product->reserve, $product->unit_measure );						
							$product->storage = usam_get_product_stock($product->product_id, 'storage_'.$item->storage );
							$product->url = get_permalink( $product->product_id );
							foreach( $user_columns as $column)
							{								
								$product->$column = usam_get_product_property($product->product_id, $column );	
							}
						}
					}					
					if ( in_array('status_data', $parameters['add_fields']) )
					{
						$object_status = usam_get_object_status_by_code( $item->status, 'shipped' );
						$item->status_name = isset($object_status['name'])?$object_status['name']:'';
						$item->status_color = isset($object_status['color'])?$object_status['color']:'';
						$item->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';		
					}
					if ( in_array('storage_data', $parameters['add_fields']) )
					{
						$item->storage_pickup_data = usam_get_storage( $item->storage_pickup, true );
						$item->storage_data = usam_get_storage( $item->storage, true );
					}					
					if ( in_array('property_types', $parameters['add_fields']) )
					{
						$property_types = usam_get_order_property_types( $item->order_id );	
						foreach( $property_types as $k => $value )
							$item->$k = $value;
					}
				}				
			}
			$items = apply_filters( 'usam_api_orders', $items, $parameters );			
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];		 
		return $results;
	}		
	
	public static function save_shippeds( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$return = [];
		usam_update_object_count_status( false );
		$args = [];
		foreach( $parameters['items'] as $k => &$item )
		{
			if ( !empty($item['external_document_date']) )
				$item['external_document_date'] = USAM_Request_Processing::sanitize_date( $item['external_document_date'] );	
			if ( !empty($item['date_delivery']) )
				$item['date_delivery'] = USAM_Request_Processing::sanitize_date( $item['date_delivery'] );
			if ( !empty($item['readiness_date']) )
				$item['readiness_date'] = USAM_Request_Processing::sanitize_date( $item['readiness_date'] );
			if ( !empty($item['date_insert']) )
				$item['date_insert'] = USAM_Request_Processing::sanitize_date( $item['date_insert'] );
			if ( !empty($item['id']) )
			{
				$id = absint($item['id']);
				if( $id )
					$args['include'][] = $id;	
				$return[$k] = [];
			}
			else
			{	
				$link = [];
				if ( !empty($item['order_id']) )
					$link = ['document_id' => $item['order_id'], 'document_type' => 'order'];
				$products = [];
				if ( !empty($item['products']) )
				{
					$products = $item['products'];
					unset($item['products']);
				}
				$id = usam_insert_shipped_document( $item, $products, $link );		
				if ( $id )
					USAM_Documents_API::save_shipped_metadata( $id, $item );				
				$return[$k] = usam_get_shipped_document( $id );
			}			
		}				
		if ( !empty($args) )
		{
			require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
			$shippeds = usam_get_shipping_documents( $args );
			foreach( $shippeds as $shipped )
			{
				foreach( $parameters['items'] as $k => $item )
				{
					if ( !empty($item['id']) && $item['id'] == $shipped->id )
					{		
						$return[$k] = (array)$item;
						USAM_Documents_API::save_shipped( (array)$shipped, $item);
						unset($parameters['items'][$k]);
					}
				}
			}
		}
		usam_update_object_count_status( true );
		return $return;
	}
	
	public static function insert_shipped( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
	
		$link = [];
		if ( !empty($parameters['order_id']) )
			$link = ['document_id' => $parameters['order_id'], 'document_type' => 'order'];
		$products = [];
		if ( !empty($parameters['products']) )
		{
			$products = $parameters['products'];
			unset($parameters['products']);
		}
		$id = usam_insert_shipped_document( $item, $products, $link );	
		if( $id )
			USAM_Documents_API::save_shipped_metadata( $id, $parameters );	
		return $id;
	}
	
	public static function update_shipped( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		
		$document = usam_get_shipped_document( $id );			
		if ( !$document )
			return new WP_Error( 'document_id', 'Invalid id', ['status' => 404]);
		return USAM_Documents_API::save_shipped($document, $parameters);
	}
	
	private static function save_shipped( $document, $parameters) 
	{				
		$result = false;
		if ( $document )
		{									
			if ( !current_user_can('universam_api') && !usam_check_document_access( $document, 'shipped', 'edit' ) )
				return new WP_Error( 'forbidden', 'Forbidden', ['status' => 403]);
						
			$update = $parameters;	
			if ( !empty($parameters['status']) )
			{
				require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
				$statuses = usam_get_object_statuses(['fields' => 'internalname', 'type' => 'shipped']);
				if ( in_array($parameters['status'], $statuses))
					$update['status'] = $parameters['status'];
			}				
			$products = isset($parameters['products'])&& is_array($parameters['products'])?$parameters['products']:null;
			$result = usam_update_shipped_document($document['id'], $update, $products);		
			$result = true;
			USAM_Documents_API::save_shipped_metadata( $document['id'], $parameters );	
		}
		return $result;
	}
			
	private static function save_shipped_metadata( $id, $parameters ) 
	{
		$result = true;	
		if( isset($parameters['date_delivery']) )
			usam_update_shipped_document_metadata($id, 'date_delivery', $parameters['date_delivery']);	
		if( isset($parameters['readiness_date']) )				
			usam_update_shipped_document_metadata($id, 'readiness_date', $parameters['readiness_date']);	
		if( isset($parameters['delivery_problem']) )		
		{
			$problems = usam_get_standard_delivery_problems();
			if ( isset($problems[$parameters['delivery_problem']]) )
				usam_update_shipped_document_metadata($id, 'delivery_problem', $parameters['delivery_problem']);						
		}
		if( isset($parameters['note']) )	
			usam_update_shipped_document_metadata($id, 'note', $parameters['note']);			
		if( isset($parameters['external_document']) )	
			usam_update_shipped_document_metadata($id, 'external_document', $parameters['external_document']);	
		if( isset($parameters['external_document_date']) )				
			usam_update_shipped_document_metadata($id, 'external_document_date', $parameters['external_document_date']);
		if( isset($parameters['exchange_completed']) )
		{				
			$result = true;
			usam_update_shipped_document_metadata($id, 'exchange', 1);		
			if ( !empty($parameters['date_exchange']) )		
				$date_exchange = date("Y-m-d H:i:s", strtotime($parameters['date_exchange']));
			else
				$date_exchange = date("Y-m-d H:i:s");				
			usam_update_shipped_document_metadata($id, 'date_exchange', $date_exchange);
		}
		return $result;
	}
	
	private static function get_shipping_documents_order( $order_id, $type_price ) 
	{
		$documents = usam_get_shipping_documents_order( $order_id );
		foreach( $documents as $k => &$document )
		{
			$document->date_insert = usam_local_date(strtotime($document->date_insert), 'c');						
			$date_delivery = usam_get_shipped_document_metadata( $document->id, 'date_delivery' );
			$document->date_delivery = $date_delivery ? usam_local_date(strtotime($date_delivery), 'c'): null;			
			if ( $document->storage_pickup )
				$document->pickup = self::get_pickup( $document->storage_pickup );
			$object_status = usam_get_object_status_by_code( $document->status, 'shipped' );
			$document->status_is_completed = (int)usam_check_object_is_completed( $document->status, 'shipped' );
			$document->status_name = isset($object_status['name'])?$object_status['name']:'';
			$document->status_color = isset($object_status['color'])?$object_status['color']:'';
			$document->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';					
			$readiness_date = usam_get_shipped_document_metadata( $document->id, 'readiness_date' );
			$document->readiness_date = $readiness_date ? usam_local_date(strtotime($readiness_date), 'c') : null;
			$document->products = self::get_products_shipped_document( $document->id, $type_price );
			if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
			{
				$company = usam_get_company( $document->seller_id );
				$document->seller = [];
				if ( $company )
					$document->seller = $company;
			}
		}
		return $documents;
	}
	
	private static function get_products_shipped_document( $id, $type_price = '' ) 
	{
		$products = usam_get_products_shipped_document( $id );
		foreach( $products as $k => &$product )
		{
			$product->sku = usam_get_product_meta( $product->product_id, 'sku' );
			if ( current_user_can('universam_api') )
				$product->code = usam_get_product_meta( $product->product_id, 'code' );
			$product->date_insert = get_date_from_gmt($product->date_insert);
			$product->quantity_unit_measure = usam_get_formatted_quantity_product_unit_measure( $product->quantity, $product->unit_measure );
			$product->small_image = usam_get_product_thumbnail_src($product->product_id, 'small-product-thumbnail');	
			$product->price_currency = usam_get_formatted_price($product->price, ['type_price' => $type_price]);
			//$product->old_price_currency = usam_get_formatted_price($product->old_price, ['type_price' => $type_price]);
			$product->total = $product->price * $product->quantity;		
			$product->total_currency = usam_get_formatted_price($product->price * $product->quantity, ['type_price' => $type_price]);				
		}
		return $products;
	}
	
	public static function get_shipped( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );			
		$parameters = self::get_parameters( $request );			
		$document = usam_get_shipped_document( $id );	
		if( $document )
		{
			$document['pickup'] = self::get_pickup( $document['storage_pickup'] );	
			$object_status = usam_get_object_status_by_code( $document['status'], 'shipped' );
			$document['status_is_completed'] = isset($object_status['close'])?(int)$object_status['close']:0;
			$document['status_name'] = isset($object_status['name'])?$object_status['name']:'';
			$document['status_color'] = isset($object_status['color'])?$object_status['color']:'';
			$document['status_text_color'] = isset($object_status['text_color'])?$object_status['text_color']:'';				
			if( !empty($parameters['add_fields']) )
			{
				if ( in_array('document_products', $parameters['add_fields']) )
				{
					$document['products'] = self::get_products_shipped_document($id, $document['type_price'] );
					$user_columns = usam_get_user_columns( 'shipped' );
					$storage_code = !empty($document['storage'])?'storage_'.$document['storage']:null;	
					foreach( $document['products'] as &$product ) 
					{
						$product->url = get_permalink( $product->product_id );
						$product->storage = (int)usam_get_product_stock( $product->product_id, $storage_code );
						foreach( $user_columns as $column)
							$product->$column = usam_get_product_property($product->product_id, $column );	
					}	
				}
				if ( in_array('storage_data', $parameters['add_fields']) )
				{
					$document['storage_pickup_data'] = usam_get_storage( $document['storage_pickup'], true );
					$document['storage_data'] = usam_get_storage( $document['storage'], true );
				}				
				if ( in_array('products', $parameters['add_fields']) )
					$document['products'] = self::get_products_shipped_document( $id, $document['type_price'] );
			}				
			$document['totalprice_currency'] = usam_get_formatted_price( $document['totalprice'], ['type_price' => $document['type_price']]);			
			$document['external_document'] = usam_get_shipped_document_metadata($id, 'external_document');
			$document['external_document_date'] = usam_get_shipped_document_metadata($id, 'external_document_date');
			if( !empty($document['external_document_date']) )
				$document['external_document_date'] = get_date_from_gmt( $document['external_document_date'], "Y-m-d H:i:s" );
			$document['date_delivery'] = usam_get_shipped_document_metadata($id, 'date_delivery');	
			if( !empty($document['date_delivery']) )			
				$document['date_delivery'] = get_date_from_gmt( $document['date_delivery'], "Y-m-d H:i:s" );
			$document['date_insert'] = get_date_from_gmt($document['date_insert']);			
			$document['readiness_date'] = usam_get_shipped_document_metadata($id, 'readiness_date');
			if( !empty($document['readiness_date']) )
				$document['readiness_date'] = get_date_from_gmt( $document['readiness_date'], "Y-m-d H:i:s" );	
			$document['note'] = usam_get_shipped_document_metadata($id, 'note');		
			$document['exchange'] = usam_get_shipped_document_metadata($id, 'exchange');
			if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
				$document['seller'] = usam_get_seller_data( $document['seller_id'] );
		}
		return apply_filters('usam_api_shipped_document', $document);
	}
	
	public static function delete_shippeds( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		if ( !empty($parameters['args']) )
			return usam_delete_shippeds( $parameters['args'] );	
		return false;
	}
	
	public static function recalculate_shipped( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );				
		return usam_calculate_amount_shipped_document( $id );	
	}
	
	public static function get_delivery_problems( WP_REST_Request $request ) 
	{				
		return usam_get_standard_delivery_problems();
	}	
	
	public static function get_delivery_services( WP_REST_Request $request ) 
	{				
		$parameters = self::get_parameters( $request );		
		$restrictions = ['active' => 'all'];
		if ( $parameters['order_id'] )
		{
			$order = new USAM_Order( $parameters['order_id'] );										
			$order_data = $order->get_data();
			if ( $order_data )
			{
				$calculated_data = $order->get_calculated_data();
				$location = usam_get_order_customerdata( $order_data['id'], 'location' );						
				$restrictions = array_merge( $restrictions, ['price' => $calculated_data['order_final_basket'], 'number_products' => $calculated_data['number_products'], 'weight' => $calculated_data['weight'], 'type_payer' => $order_data['type_payer']] );
				if ( !empty($location) )
				{
					$restrictions['locations'] = array_values(usam_get_address_locations( $location, 'id' )); 
				}
				if ( $order_data['user_ID'] == 0 ) 
					$restrictions['roles'] = ['notloggedin'];
				else
				{
					$user = get_userdata( $order_data['user_ID'] );					
					$restrictions['roles'] = $user->roles;
				}
			}
		}
		require_once( USAM_FILE_PATH . '/includes/basket/calculate_delivery_service.class.php' );
		$ds = new USAM_Calculate_Delivery_Services( $restrictions );										
		$delivery_service = $ds->get_delivery_services();
		$delivery_services_disabled = $ds->get_delivery_services_disabled();
		$gateways = [];
		foreach (usam_get_data_integrations( 'shipping', ['order' => 'Order'] ) as $key => $item)
		{
			if ( $item['order'] == 'Да' )
				$gateways[] = $key;
		}		
		$services = [];
		foreach( $delivery_service as $k => $service )
		{
			$service->disabled = false;
			$service->is_create_order = in_array($service->handler, $gateways);
			$services[] = $service;
		}
		foreach( $delivery_services_disabled as $k => $service )
		{
			$service->disabled = true;
			$service->is_create_order = in_array($service->handler, $gateways);
			$services[] = $service;
		}
		return $services;
	}	
		
	public static function tracking_shipped( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );				
		$class = new USAM_Sending_Messages();
		return $class->customer_notification_tracking( $id );
	}
	
	public static function create_move_shipped( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );				
		$shipped_document = usam_get_shipped_document( $id );
		$products = usam_get_products_shipped_document( $id );
		
		$document = new USAM_Document(['type' => 'movement']);	
		$document->save();		
		$document->add_products( $products );
		$document_id = $document->get( 'id' );	
		usam_update_document_metadata($document_id, 'for_storage', $shipped_document['storage'] ); 			
		return $document_id;	
	}
	
	public static function create_order_transport_company( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );				
		$shipped_document = usam_get_shipped_document( $id );		
		$merchant_instance = usam_get_shipping_class( $shipped_document['method'] );
		$result = $merchant_instance->create_order( $id );		
		return ['result' => $result, 'errors' => $merchant_instance->get_errors()];	
	}

	private static function get_pickup( $storage_pickup ) 
	{
		$pickup = ['city' => '', 'address' => '', 'phone' => '', 'schedule' => ''];
		$storage = usam_get_storage( $storage_pickup );
		if ( $storage )
		{
			$location = usam_get_location( $storage['location_id'] );
			$city = isset($location['name'])?htmlspecialchars($location['name'])." ":'';
			$phone = usam_get_storage_metadata( $storage['id'], 'phone');
			$schedule = (string)usam_get_storage_metadata( $storage['id'], 'schedule');
			$address = (string)usam_get_storage_metadata( $storage['id'], 'address');
			$pickup = ['city' => $city, 'address' => htmlspecialchars($address), 'phone' => usam_get_phone_format($phone), 'schedule' => htmlspecialchars($schedule)];
		}
		return $pickup;
	}	

	private static function get_payment_documents_order( $order_id ) 
	{
		$documents = usam_get_payment_documents_order( $order_id );
		foreach( $documents as $k => &$document )
		{
			$document->date_insert = usam_local_date(strtotime($document->date_insert), 'c');	
			$object_status = usam_get_object_status_by_code( $document->status, 'payment' );
			$document->status_is_completed = (int)usam_check_object_is_completed( $document->status, 'payment' );
			$document->status_name = isset($object_status['name'])?$object_status['name']:'';
			$document->status_color = isset($object_status['color'])?$object_status['color']:'';
			$document->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';							
		}
		return $documents;
	}	
	
	public static function get_document_order( $order_id, $parameters ) 
	{			
		$query = new USAM_Order( $order_id );		
		$results = $query->get_data();	
		if( $results )
		{					
			$properties_args = ['access' => true, 'active' => 1, 'type' => 'order', 'orderby' => ['group', 'sort'], 'cache_results' => true, 'cache_group' => true];
			$results['date_insert'] = usam_local_date(strtotime($results['date_insert']), 'c');
			$results['date_status_update'] = usam_local_date(strtotime($results['date_status_update']), 'c');				
			if ( $results['date_paid'] )			
				$results['date_paid'] = usam_local_date(strtotime($results['date_paid']), 'c');	
			$results['review_id'] = (int)usam_get_order_metadata($order_id, 'review_id');			
			$object_status = usam_get_object_status_by_code( $results['status'], 'order' );
			$results['status_is_completed'] = (int)usam_check_object_is_completed( $results['status'], 'order' );
			$results['status_name'] = isset($object_status['name'])?$object_status['name']:'';
			$results['status_color'] = isset($object_status['color'])?$object_status['color']:'';
			$results['status_text_color'] = isset($object_status['text_color'])?$object_status['text_color']:'';			
			$results['can_pay'] = false;
			$results['payment_status'] = usam_get_order_payment_status_name( $results['paid'] );
			$results['date_pay_up'] = usam_get_order_metadata($order_id, 'date_pay_up' );						
			if ( $results['paid'] != 2 && !$results['status_is_completed'] )
			{									
				if ( !empty($results['date_pay_up']) && strtotime($results['date_pay_up']) >= time() )
					$results['can_pay'] = true;
			}	
			$results['coupon_name'] = (string)usam_get_order_metadata( $order_id, 'coupon_name');	
			$products = usam_get_products_order( $order_id );			
			$products_discount = 0;
			$product_taxes = usam_get_order_product_taxes( $order_id );
			$results['price_without_discount'] = 0;
			$results['discounted_price'] = 0;
			foreach( $products as $k => $product )
			{
				$not_in_price = isset($product_taxes[$product->product_id]['not_in_price'])?$product_taxes[$product->product_id]['not_in_price']:0;			
				$total = $product->quantity*($product->price + $not_in_price);	
				$discount = $product->old_price?$product->old_price - $product->price:0;
				$products_discount += $discount*$product->quantity;			
				$products[$k]->price_currency = usam_get_formatted_price($product->price, ['type_price' => $results['type_price']]); 
				$products[$k]->old_price_currency = usam_get_formatted_price($product->old_price, ['type_price' => $results['type_price']]);
				$products[$k]->discount_currency = usam_get_formatted_price($discount, ['type_price' => $results['type_price']]);				
				$products[$k]->total_currency = usam_get_formatted_price($total, ['type_price' => $results['type_price']]);
				$products[$k]->total = $total;		
				$products[$k]->sku = usam_get_product_meta( $product->product_id, 'sku' );
				$products[$k]->code = usam_get_product_meta( $product->product_id, 'code' );
				$products[$k]->date_insert = usam_local_date(strtotime($product->date_insert), 'c');	
				$products[$k]->url = get_permalink( $products[$k]->product_id );
				$products[$k]->small_image = usam_get_product_thumbnail_src($product->product_id, 'small-product-thumbnail');	
				$products[$k]->quantity_unit_measure = usam_get_formatted_quantity_product_unit_measure( $product->quantity, $product->unit_measure );
				$results['price_without_discount'] += $product->old_price * $product->quantity;	
				$results['discounted_price'] += $product->price * $product->quantity;
				if( current_user_can( 'edit_order_contractor' ) )
					$products[$k]->contractor = (int)usam_get_product_meta( $product->product_id, 'contractor' );				
			}	
			if ( !empty($parameters['add_fields']) )
			{
				if ( in_array('storages_stock', $parameters['add_fields']) )
				{
					$location = usam_get_order_customerdata( $order_id, 'loacion' );
					$results['storages'] = usam_get_storages(['region' => $location, 'add_fields' => ['phone','schedule','city','address'], 'fields' => 'id=>data', 'number' => 500]);	
					$results['storages_stock'] = [];		
					foreach( $products as $product )
					{						
						$results['storages_stock'][0][$product->product_id] = usam_product_remaining_stock( $product->product_id, "stock" );
						foreach( $results['storages'] as $key => $storage )
						{				
							if ( $storage->shipping == 1 )
							{
								$stock = usam_get_stock_in_storage($storage->id, $product->product_id);								
								if ( $stock > 0 )
									$results['storages_stock'][$storage->id][$product->product_id] = $stock;
							}
						}
					}					
				}
				if ( in_array('manager', $parameters['add_fields']) )
					$results['manager'] = self::author_data( $results['manager_id'] );	
				if ( in_array('contact', $parameters['add_fields']) )
					$results['contact'] = self::author_data( $results['contact_id'], 'id' );
				if ( in_array('document_products', $parameters['add_fields']) )
				{
					$products = usam_get_products_order( $id );		
					$results['taxes'] = usam_get_order_product_taxes( $id );
					$results['products'] = self::get_products($products, $results['taxes'], $results['type_price'] );						
					$user_columns = usam_get_user_columns( 'order' );
					require_once( USAM_FILE_PATH .'/includes/document/document_discounts_query.class.php' );
					$discounts = usam_get_document_discounts_query(['document_id' => $results['id'], 'document_type' => 'order']);
					foreach ( $results['products'] as &$product ) 
					{
						$product->url = get_permalink( $product->product_id );
						$product->discounts = [];	
						if ( !empty($discounts))
						{
							foreach( $discounts as $discount )
							{
								if ( $discount->product_id == $product->product_id )				
									$products[$k]->discounts[] = ['name' => usam_get_discount_rule_name($discount, $results['type_price']), 'id' => $discount->id];								
							}					
						}						
						foreach( $user_columns as $column)
							$product->$column = usam_get_product_property($product->product_id, $column );	
					}
					$results['contact_type_price'] = (string)usam_get_contact_metadata( $results['contact_id'], 'type_price');	
				}				
				if ( in_array('groups', $parameters['add_fields']) )
				{
					$results['groups'] = usam_get_property_groups(['type' => 'order']);
					foreach( $results['groups'] as $k => $group )
						$results['groups'][$k]->type_payers = array_map('intval', (array)usam_get_array_metadata($group->id, 'property_group', 'type_payer'));	
				}					
				if ( in_array('properties', $parameters['add_fields']) )
				{
					$results['properties'] = [];
					$properties = usam_get_properties( $properties_args );
					foreach( $properties as $property )
					{
						if ( $property->field_type == 'checkbox' )
							$property->value = usam_get_array_metadata( $order_id, 'order', $property->code );
						else
							$property->value = usam_get_order_metadata( $order_id, $property->code );
						$results['properties'][$property->code] = usam_format_property_api( $property );
					}			
				}			
				if ( in_array('files', $parameters['add_fields']) )
				{
					if( usam_check_type_product_sold('electronic_product') )
						$results['files'] = usam_get_order_files( $order_id );	
					else
						$results['files'] = [];
				}
				if ( in_array('shipping', $parameters['add_fields']) )
				{
					$results['shipping'] = self::get_shipping_documents_order( $order_id, $results['type_price'] );
				}
				if ( in_array('payments', $parameters['add_fields']) )
				{
					$results['payments'] = self::get_payment_documents_order( $order_id );		
				}
			}						
			$results['discount'] = $results['price_without_discount'] - $results['discounted_price'];		
			$results['price_without_discount_currency'] = usam_get_formatted_price($results['price_without_discount'], ['type_price' => $results['type_price']]);
			$results['discounted_price_currency'] = usam_get_formatted_price($results['discounted_price'], ['type_price' => $results['type_price']]);
			$results['discount_currency'] = usam_get_formatted_price($results['discount'], ['type_price' => $results['type_price']]);
			$results['shipping_currency'] = usam_get_formatted_price( $results['shipping'], ['type_price' => $results['type_price']]);
			$results['totalprice_currency'] = usam_get_formatted_price( $results['totalprice'], ['type_price' => $results['type_price']]);
			$results['products'] = $products;								
			$results['taxes'] = $product_taxes;				
		}	
		return apply_filters('usam_api_order', $results);		
	}
	
	public static function get_order( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$parameters = self::get_parameters( $request );	
		return self::get_document_order( $id, $parameters );
	}
	
	public static function get_order_by_payment_number( WP_REST_Request $request ) 
	{	
		$payment_number = $request->get_param( 'number' );
		$payment = usam_get_payment_document($payment_number, 'number');
		if ( $payment['document_id'] )
		{
			$parameters = self::get_parameters( $request );	
			return self::get_document_order( $payment['document_id'], $parameters );
		}
		else
			return [];
	}
		
	public static function save_orders( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$i = 0;
		usam_update_object_count_status( false );
		foreach( $parameters['items'] as $item )
		{
			$id = 0;
			if ( !empty($item['code']) )
			{
				$code = sanitize_text_field($item['code']);			
				$order = usam_get_order( $code, 'code' ); 
				$id = $order['id'];
			}
			elseif ( !empty($item['id']) )
				$id = absint($item['id']);
			if ( USAM_Documents_API::save_order($id, $item) )
				$i++;
		}	
		usam_update_object_count_status( true );
		return $i;
	}
	
	public static function update_order( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();						
		if ( !$parameters )
			$parameters = $request->get_body_params(); 	
		return USAM_Documents_API::save_order($id, $parameters);
	}
	
	private static function save_order( $order_id, $parameters) 
	{			
		$result = false;
		if ( $order_id )
		{
			$order = usam_get_order( $order_id ); 		
			if( !$order )
				return new WP_Error( 'document_id', 'Invalid id', ['status' => 404]);
					
			if( !current_user_can('universam_api') && !usam_check_document_access( $order, 'order', 'edit' ) )
				return new WP_Error( 'forbidden', 'Forbidden', ['status' => 403]);
						
			if( !empty($parameters['prevent_notification']) )
				add_filter( 'usam_prevent_notification_change_status', '__return_false' );
			
			$user_id = get_current_user_id();
			if ( empty($order['manager_id']) && empty($parameters['manager_id']) )
				$parameters['manager_id'] = $user_id;						
			if( isset($parameters['user_id']) )
				$parameters['user_ID'] = absint($parameters['user_id']);						
			if( !empty($parameters['status']) )
			{
				require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
				$statuses = usam_get_object_statuses(['fields' => 'internalname', 'type' => 'order']);
				if ( in_array($parameters['status'], $statuses))
					$parameters['status'] = $parameters['status'];
			}	
			$products = isset($parameters['products'])&& is_array($parameters['products'])?$parameters['products']:null;			
			/*
			
			if( !get_option('usam_accurate_inventory_control', 1) )
		{
			if ( $storage_id )			
				$stock = usam_string_to_float(usam_get_product_stock($product_id, "storage_".$storage_id));
			else
				$stock = 0;
			$reserve = usam_string_to_float(usam_get_product_stock($product_id, 'reserve_'.$storage_id));	
			$stock = $stock - $reserve;
			if ( $stock < 0 )
			{
				$storage = usam_get_storage( $storage_id );
				$title = !empty($storage['title'])?"&#8220;".$storage['title']."&#8221;":"";
				$return['error'] = sprintf(__("Товара нет на остатке на складе %s. Остаток %s (в резерве %s)","usam"), $title, $stock, $reserve);	
				return $return;	
			}
			elseif ( $stock < $quantity )
			{
				$storage = usam_get_storage( $storage_id );
				$title = !empty($storage['title'])?"&#8220;".$storage['title']."&#8221;":"";
				$return['error'] = sprintf(__("Вы добавляете слишком много. На складе %s доступно %s (в резерве %s)","usam"), $title, $stock, $reserve);	
				return $return;				
			}
		}
		else
		{
			$stock = usam_string_to_float(usam_get_product_stock($product_id, 'stock'));
			if ( $stock < 0 )
			{
				$return['error'] = sprintf(__("Товара нет на остатке. Остаток %s","usam"), $stock);	
				return $return;	
			}
		}
			
			*/			
			if( !empty($parameters['files']) )
			{ 
				$purchase_log = new USAM_Order( $order_id );
				$purchase_log->edit_downloadable_files( $parameters['files'] );				
			} 	
			$result = usam_update_order($order_id, $parameters, $products);	
			if ( $products )
			{				
				if ( !empty($parameters['change_payment']) )
					usam_update_payment_document( $parameters['change_payment'], ['sum' => $order['totalprice']] );
				foreach ( $products as &$shipping_product )
				{
					unset($shipping_product['id']);
				}				
				if ( !empty($parameters['change_shipping']) )
					usam_update_shipped_document($parameters['change_shipping'], [], $products );
				elseif ( !empty($parameters['add_shipping']) )
					usam_insert_shipped_document(['storage' => abs($parameters['add_shipping']), 'order_id' => $order_id], $products, ['document_id' => $order_id, 'document_type' => 'order'] );	
			}
			if ( isset($parameters['address_id']) )
				usam_update_order_metadata( $order_id, 'address', absint($parameters['address_id']) );	
			if ( isset($parameters['note']) )
				usam_update_order_metadata($order_id, 'note', $parameters['note'] );
			if ( !empty($parameters['properties']) )
			{
				if ( usam_add_order_customerdata( $order_id, $parameters['properties'] ) )
					$result = true;
			}
			if ( isset($parameters['coupon_name']) )
			{
				$old_value = (string)usam_get_order_metadata($order_id, 'coupon_name');
				$coupon = sanitize_title($parameters['coupon_name']);		
				if ( $coupon )
					usam_update_order_metadata($order_id, 'coupon_name', $coupon );
				elseif ( $old_value )
					usam_delete_order_metadata($order_id, 'coupon_name' );
				if ( $coupon != $old_value )
					usam_insert_change_history(['object_id' => $order_id, 'object_type' => 'order', 'operation' => 'edit', 'field' => 'coupon_name', 'value' => $coupon, 'old_value' => $old_value]);
			}
			if( !empty($parameters['bonuses']) )
				usam_insert_bonus(['object_id' => $order['id'], 'object_type' => 'order', 'sum' => $parameters['bonuses'], 'description' => __('Оплата заказа','usam'), 'type_transaction' => 1], $order['user_ID'] );
			if( isset($parameters['exchange']) )
			{				
				$result = true;
				usam_update_order_metadata($order_id, 'exchange', 1);		
				if ( !empty($parameters['date_exchange']) )		
					$date_exchange = date("Y-m-d H:i:s", strtotime($parameters['date_exchange']));
				else
					$date_exchange = date("Y-m-d H:i:s");				
				usam_update_order_metadata($order_id, 'date_exchange', $date_exchange);
			}
			if( isset($parameters['date_pay_up']) )
				usam_update_order_metadata($order_id, 'date_pay_up', $parameters['date_pay_up']);		 
			if( isset($parameters['cancellation_reason']) )
			{
				usam_update_order_metadata($order_id, 'cancellation_reason', $parameters['cancellation_reason']);	
				$user_id = get_current_user_id();
				if ( !current_user_can('universam_api') && $order['user_ID'] == $user_id )
				{			
					$user_ids = usam_get_contacts(['fields' => 'user_id', 'source' => 'employee', 'capability' => 'view_contacting']);
					usam_add_notification(['title' => sprintf( __('Клиента %s отменил заказ №%s','usam'), usam_get_customer_name( $order['user_ID'] ), $order_id )], ['object_type' => 'order', 'object_id' => $order_id], $user_ids );
				}							
			}
			if ( isset($parameters['groups']) )			
				usam_set_groups_object( $order_id, 'order', $parameters['groups'] );
			do_action('usam_document_order_save', $order_id);
		}
		return $result;
	}
	
	public static function insert_order( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
						
		if( !empty($parameters['user_id']) )
			$parameters['user_ID'] = absint($parameters['user_id']);
		elseif( !isset($parameters['user_id']) )
			$parameters['user_ID'] = get_current_user_id();		
		if ( isset($parameters['user_ID']) )
		{			
			$contact = usam_get_contact( $parameters['user_ID'], 'user_id' );
			if ( $contact )
				$parameters['contact_id'] = $contact['id'];
			$company = usam_get_company( $parameters['user_ID'], 'user_id' );
			if ( $company )
				$parameters['company_id'] = $company['id'];
		}		
		if( !empty($parameters['type_price']) )
		{
			$type_price = $parameters['type_price'];
			$prices = usam_get_prices(['type' => 'R']);
			foreach ( $prices as $price )
			{
				if ( $price['code'] == $type_price )
				{
					$parameters['type_price'] = $price['code'];
					break;
				}
			}
		}	
		elseif ( !empty($parameters['price_external_code']) )
		{
			$type_price = $parameters['price_external_code'];
			$prices = usam_get_prices(['type' => 'R']);
			foreach ( $prices as $price )
			{	
				if ( $price['external_code'] == $type_price )
				{
					$parameters['type_price'] = $price['code'];
					break;
				}
			}
		}			
		if ( isset($parameters['bank_account_id']) )			
			$parameters['bank_account_id'] = usam_get_companies(['fields' => 'id', 'type' => 'own', 'include' => [$parameters['bank_account_id']], 'number' => 1]);	
		
		$purchase_log = new USAM_Order( $parameters );	
		$result = $purchase_log->save();		
		if ( !$result )
			return false;		
		$order_id = $purchase_log->get('id');	
		$user_id = $purchase_log->get('user_id');
		if ( !empty($parameters['bonuses']) )
			usam_insert_bonus(['object_id' => $order_id, 'object_type' => 'order', 'sum' => $parameters['bonuses'], 'description' => __('Оплата заказа','usam'), 'type_transaction' => 1], $user_id );		
		$order_products = [];	
		foreach( $parameters['products'] as $key => $product )
		{			
			$product_id = 0;
			if ( !empty($product['code']) )
			{
				$code = sanitize_text_field($product['code']);
				$product_id = usam_get_product_id_by_code( $code );
			}
			elseif ( !empty($product['product_id']) )
				$product_id = absint($product['product_id']);
			if ( $product_id )	
			{
				$update = ['product_id' => $product_id ];
				$update['quantity'] = !empty($product['quantity'])?usam_string_to_float($product['quantity']):1;
				if ( isset($product['price']) )
					$update['price'] = usam_string_to_float($product['price']);
				if ( isset($product['old_price']) )
					$update['old_price'] = usam_string_to_float($product['old_price']);
				if ( isset($product['unit_measure']) )
					$update['unit_measure'] = sanitize_text_field($product['unit_measure']);
				$order_products[] = $update;
			}
		}			
		$purchase_log->add_products( $order_products );
		$totalprice = $purchase_log->get( 'totalprice' );	
		if ( !empty($parameters['properties']) )
			usam_add_order_customerdata( $order_id, $parameters['properties'] );
		if ( isset($parameters['date_pay_up']) )
			usam_update_order_metadata($order_id, 'date_pay_up', $parameters['date_pay_up']);
		if ( !empty($parameters['store_code']) )
		{			
			$storage = usam_get_storage( sanitize_text_field($parameters['store_code']), 'code' );
			if ( isset($storage['id']) )
			{
				$document_shipped['storage_pickup'] = $storage['id'];
				$document_shipped['storage'] = $storage['id'];				
				$document_shipped['status']   = 'pending';
				$document_shipped['order_id'] = $order_id;
				if ( $purchase_log->get('status') == 'closed' )
					$document_shipped['status'] = 'shipped';	
				usam_insert_shipped_document( $document_shipped, $order_products, ['document_id' => $order_id, 'document_type' => 'order'] );	
			}							
		}
		if ( $totalprice )
		{
			$payment['sum'] = $totalprice;	
			$payment['document_id'] = $order_id;	
			if ( !empty($parameters['bank_account_id']) )			
				$payment['bank_account_id'] =  sanitize_text_field($parameters['bank_account_id']);
			if ( $purchase_log->get('status') == 'closed' )
				$payment['status'] = 3;
			$payment_id = usam_insert_payment_document( $payment, ['document_id' => $order_id, 'document_type' => 'order']);	
			$payment = usam_get_payment_document( $payment_id );	
			$payment_number = $payment['number'];		
		}
		else
			$payment_number = 0;
		
		if ( !empty($parameters['status']) )
		{
			require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
			$statuses = usam_get_object_statuses(['fields' => 'internalname', 'type' => 'order']);
			if ( in_array($parameters['status'], $statuses))
			{
				$purchase_log->set(['status' => $parameters['status']]);
				$purchase_log->save();
			}
		}
		if ( isset($parameters['groups']) )			
			usam_set_groups_object( $order_id, 'order', $parameters['groups'] );
		do_action('usam_document_order_save', $order_id);
		return ['order_id' => $order_id, 'payment_number' => $payment_number];
	}	

	public static function order_copy( WP_REST_Request $request ) 
	{	
		$order_id = $request->get_param( 'id' );
		$new_order_id = usam_order_copy($order_id, ['source' => 'repeat'], true);
		if ( $new_order_id )
		{									
			$cart = new USAM_CART();
			$cart->set_order( $new_order_id );
			$parameters = self::get_parameters( $request );	
			return self::get_document_order( $new_order_id, $parameters );
		}
		return new WP_Error( 'order_id', 'Ошибка создания заказа', ['status' => 404]);
	}
	
	public static function get_status_order( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$order = usam_get_order( $id );
		if ( isset($order['status']) && !usam_check_object_is_completed( $order['status'], 'order' ) )
		{
			$status = usam_get_object_status_by_code( $order['status'], 'order' );			
			$documents = usam_get_shipping_documents_order( $id );
			$document = (array)array_pop($documents);	
			$results = ['id' => $id, 'status_name' => $status['name'], 'status_description' => $status['description']];
			$results['date_insert'] = usam_local_date(strtotime($order['date_insert']),get_option( 'date_format', 'Y/m/d' ));
			$results['shipping_method'] = $document['name'];
			$results['track_id'] = $document['track_id'];	
			return $results;
		}
		else
			return false;
	}
		
	public static function get_customer_details( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );						
		if ( $parameters['customer_type'] == 'contact' )
		{				
			$contact = usam_get_contact( $parameters['customer_id'] );
			$metas = usam_get_contact_metas( $parameters['customer_id'] );	
			$user_id = !empty($contact)?$contact['user_id']:0;
			$update = ['contact_id' => $parameters['customer_id'], 'company_id' => 0, 'user_ID' => $user_id];
		}
		else
		{				
			$metas = usam_get_company_metas( $parameters['customer_id'] );
			$company = usam_get_company( $parameters['customer_id'] );
			$user_id = !empty($company)?$company['user_id']:0;
			$update = ['contact_id' => 0, 'company_id' => $parameters['customer_id'], 'user_ID' => $user_id];
		}				
		$payers = usam_get_group_payers(['type' => $parameters['customer_type']]);			
		$metas = usam_get_webform_data_from_CRM( $metas, 'order', $payers[0]['id'] );
		
		$properties = usam_get_properties(['type' => 'order', 'access' => true]);		
		foreach( $properties as $property )
		{
			$property->value = isset($metas[$property->code]) ? $metas[$property->code] : '';
			$update['properties'][$property->code] = usam_format_property_api( $property );
		}
		return $update;
	}		
	
	public static function get_leads( WP_REST_Request $request )
	{		
		$parameters = self::get_parameters( $request );	
		self::get_query_vars( $parameters, $parameters );
		require_once( USAM_FILE_PATH .'/includes/document/leads_query.class.php' );
		if ( !empty($parameters['user']) )
		{
			if ( is_string($parameters['user_id']) && $parameters['user'] == 'my' )
				self::$query_vars['user_id'] = get_current_user_id();
			elseif ( is_numeric($parameters['user']) )
				self::$query_vars['user_id'] = absint($parameters['user']);
			else
				self::$query_vars['user_id'] = array_map('intval', (array)$parameters['user']);
		}		
		if ( isset(self::$query_vars['fields']) )
			unset(self::$query_vars['fields']);		
		
		if ( !empty(self::$query_vars['status']) )
			self::$query_vars['status'] = array_map('sanitize_title', (array)self::$query_vars['status']);	
		if ( isset(self::$query_vars['bank_account_id']) )
			self::$query_vars['bank_account_id'] = array_map('intval', (array)self::$query_vars['bank_account_id']);		
		self::$query_vars['cache_meta'] = true;
		if ( !empty($parameters['fields']) )	
		{				
			if ( in_array('products', $parameters['fields']) )
				$query_vars['cache_lead_products'] = true;					
		}			 
		$properties_args = ['access' => true, 'active' => 1, 'type' => 'order', 'orderby' => ['group', 'sort'], 'add_fields' => ['group_id'], 'cache_results' => true, 'cache_group' => true];
		$properties = usam_get_properties( $properties_args );		
		require_once( USAM_FILE_PATH .'/includes/document/leads_query.class.php' );
		require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );
		$query = new USAM_Leads_Query( self::$query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{ 
			$count = $query->get_total();
			foreach ( $items as $key => $item ) 
			{							
				if ( isset($item->date_insert) )
					$items[$key]->date_insert = get_date_from_gmt( $item->date_insert );
				if ( isset($item->totalprice) )
					$items[$key]->totalprice_currency = usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price]);
				if ( isset($item->date_status_update) )
					$items[$key]->date_status_update = usam_local_date(strtotime($item->date_status_update), 'c');					
				if ( isset($item->status) )
				{
					$items[$key]->status_name = usam_get_object_status_name( $item->status, 'lead' );	
					$items[$key]->status_is_completed = (int)usam_check_object_is_completed( $item->status, 'lead' );
				}	
				if ( isset($item->last_comment) )
				{					
					$item->last_comment_user_foto = usam_get_contact_foto( $item->last_comment_user, 'user_id' );
					$item->last_comment_user_name = usam_get_manager_name( $item->last_comment_user);
					$item->display_last_comment_date = usam_local_formatted_date( $item->last_comment_date );
					$item->last_comment = nl2br($item->last_comment);
				}
				if ( !empty($parameters['fields']) )
				{
					if ( in_array('products', $parameters['fields']) )
					{
						$items[$key]->products = usam_get_products_lead( $item->id );
						foreach( $items[$key]->products as $k => &$product )
						{
							$product->sku = usam_get_product_meta( $product->product_id, 'sku' );
							if ( current_user_can('universam_api') )
								$product->code = usam_get_product_meta( $product->product_id, 'code' );
							$product->date_insert = usam_local_date(strtotime($product->date_insert), 'c');	
							$product->quantity_unit_measure = usam_get_formatted_quantity_product_unit_measure( $product->quantity, $product->unit_measure );
							$product->small_image = usam_get_product_thumbnail_src($product->product_id, 'small-product-thumbnail');	
							$product->price_currency = usam_get_formatted_price($product->price, ['type_price' => $item->type_price]);
							$product->old_price_currency = usam_get_formatted_price($product->old_price, ['type_price' => $item->type_price]);									
						}					
					}					
					if ( in_array('customer', $parameters['fields']) )
					{
						$item->company = [];
						$item->contact = [];
						if ( $item->company_id )
							$item->company = usam_get_company( $item->company_id );
						if ( $item->contact_id )
							$item->contact = usam_get_contact( $item->contact_id );
					}
					if ( in_array('manager', $parameters['fields']) )
						$item->manager = usam_get_contact( $item->manager_id, 'user_id' );
					if ( in_array('taxes', $parameters['fields']) )
						$items[$key]->taxes = usam_get_lead_product_taxes( $item->id );	
					if ( in_array('properties', $parameters['fields']) )
					{
						$item->properties = [];	
						foreach( $properties as $property )
						{						
							$type_payers = array_map('intval', (array)usam_get_array_metadata($property->group_id, 'property_group', 'type_payer'));	
							if ( in_array($item->type_payer, $type_payers) )
							{
								$p = clone $property;
								if ( $p->field_type == 'checkbox' )
									$p->value = usam_get_array_metadata( $item->id, 'lead', $p->code );
								else
									$p->value = usam_get_lead_metadata( $item->id, $p->code );
								$item->properties[$p->code] = usam_format_property_api( $p );
							}
						}
					}
				}
			}
			$items = apply_filters( 'usam_api_leads', $items, $parameters );
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	private static function get_products( $products, $product_taxes, $type_price = '' ) 
	{
		foreach( $products as $k => &$product )
		{
			$not_in_price = isset($product_taxes[$product->product_id]['not_in_price'])?$product_taxes[$product->product_id]['not_in_price']:0;				
			$product->discount = $product->old_price?$product->old_price - $product->price:0;
			$product->quantity = usam_get_formatted_quantity_product( $product->quantity, $product->unit_measure );						
			$product->sku = usam_get_product_meta( $product->product_id, 'sku' );
			if ( current_user_can('universam_api') )
				$product->code = usam_get_product_meta( $product->product_id, 'code' );
			$product->date_insert = get_date_from_gmt($product->date_insert);
			$product->quantity_unit_measure = usam_get_formatted_quantity_product_unit_measure( $product->quantity, $product->unit_measure );
			$product->small_image = usam_get_product_thumbnail_src($product->product_id, 'small-product-thumbnail');	
			$product->price_currency = usam_get_formatted_price($product->price, ['type_price' => $type_price]);
			$product->old_price_currency = usam_get_formatted_price($product->old_price, ['type_price' => $type_price]);
			$product->discount_currency = usam_get_formatted_price($product->discount, ['type_price' => $type_price]);
			$product->total = $product->price * ($product->price + $not_in_price);		
			$product->total_currency = usam_get_formatted_price($product->total, ['type_price' => $type_price]);	
			foreach( $product_taxes as $product_tax )
				if ( $product_tax->product_id == $product->product_id && $product_tax->unit_measure == $product->unit_measure )
					$product->taxes[] = $product_tax;			
		}
		return $products;
	}	
	
	public static function get_lead( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );				
		$parameters = self::get_parameters( $request );
		require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );		
		$results = usam_get_lead( $id );	
		if ( $results )
		{
			$properties_args = ['access' => true, 'active' => 1, 'type' => 'order', 'orderby' => ['group', 'sort'], 'cache_results' => true, 'cache_group' => true];			
			$results['date_insert'] = get_date_from_gmt($results['date_insert']);							
			if ( !empty($parameters['add_fields']) )
			{
				if ( in_array('products', $parameters['add_fields']) )
				{
					$products = usam_get_products_lead( $id );		
					$results['taxes'] = usam_get_lead_product_taxes( $id );
					$results['products'] = self::get_products($products, $results['taxes'], $results['type_price'] );
				}
				if ( in_array('document_products', $parameters['add_fields']) )
				{
					$products = usam_get_products_lead( $id );		
					$results['taxes'] = usam_get_lead_product_taxes( $id );
					$results['products'] = self::get_products($products, $results['taxes'], $results['type_price'] );						
					$user_columns = usam_get_user_columns( 'lead' );
					require_once( USAM_FILE_PATH .'/includes/document/document_discounts_query.class.php' );
					$discounts = usam_get_document_discounts_query(['document_id' => $results['id'], 'document_type' => 'lead']);
					foreach ( $results['products'] as &$product ) 
					{
						$product->url = get_permalink( $product->product_id );
						$product->discounts = [];	
						if ( !empty($discounts))
						{
							foreach( $discounts as $discount )
							{
								if ( $discount->product_id == $product->product_id )				
									$products[$k]->discounts[] = ['name' => usam_get_discount_rule_name($discount, $results['type_price']), 'id' => $discount->id];								
							}					
						}						
						foreach( $user_columns as $column)
							$product->$column = usam_get_product_property($product->product_id, $column );	
					}
					$results['contact_type_price'] = (string)usam_get_contact_metadata( $results['contact_id'], 'type_price');	
				}				
				if ( in_array('groups', $parameters['add_fields']) )
				{
					$results['groups'] = usam_get_property_groups(['type' => 'order']);
					foreach( $results['groups'] as $k => $group )
						$results['groups'][$k]->type_payers = array_map('intval', (array)usam_get_array_metadata($group->id, 'property_group', 'type_payer'));	
				}
				if ( in_array('manager', $parameters['add_fields']) )
					$results['manager'] = self::author_data( $results['manager_id'] );	
				if ( in_array('contact', $parameters['add_fields']) )
					$results['contact'] = self::author_data( $results['contact_id'], 'id' );
				if ( in_array('properties', $parameters['add_fields']) )
				{
					$results['properties'] = [];
					$properties = usam_get_properties( $properties_args );
					foreach( $properties as $property )
					{
						if ( $property->field_type == 'checkbox' )
							$property->value = usam_get_array_metadata( $id, 'lead', $property->code );
						else
							$property->value = usam_get_lead_metadata( $id, $property->code );
						$results['properties'][$property->code] = usam_format_property_api( $property );
					}			
				}
			}
		}	
		return apply_filters('usam_api_lead', $results);		
	}
			
	public static function save_leads( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );
		$i = 0;
		usam_update_object_count_status( false );
		foreach( $parameters['items'] as $item )
		{
			$id = 0;
			if ( !empty($item['id']) )
				$id = absint($item['id']);
			if ( USAM_Documents_API::save_lead($id, $item) )
				$i++;
		}	
		usam_update_object_count_status( true );
		return $i;
	}
	
	public static function update_lead( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );
		return USAM_Documents_API::save_lead($id, $parameters);
	}
	
	private static function save_lead( $document_id, $parameters) 
	{				
		$result = false;
		if ( $document_id )
		{				
			require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );	
			$current_document = usam_get_lead( $document_id );			
			if ( !$current_document )
				return new WP_Error( 'document_id', 'Invalid id', ['status' => 404]);										
			if ( !current_user_can('universam_api') && !usam_check_document_access( $current_document, 'lead', 'edit' ) )
				return new WP_Error( 'forbidden', 'Forbidden', ['status' => 403]);
			
			if( !empty($parameters['prevent_notification']) )
				add_filter( 'usam_prevent_notification_change_status', '__return_false' );
			
			if( isset($parameters['user_id']) )
				$parameters['user_ID'] = absint($parameters['user_id']);
			if( !empty($parameters['status']) )
			{
				require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
				$statuses = usam_get_object_statuses(['fields' => 'internalname', 'type' => 'lead']);
				if ( !in_array($parameters['status'], $statuses))
					unset($parameters['status']);
			}						
			$products = isset($parameters['products'])&& is_array($parameters['products'])?$parameters['products']:null;
			if ( !empty($parameters['properties']) )
			{
				if ( usam_add_lead_customerdata( $document_id, $parameters['properties'] ) )
					$result = true;
				unset($parameters['properties']);
			}	
			$result = usam_update_lead($document_id, $parameters, $products);		
			$metas = [];
			if ( isset($parameters['cancellation_reason']) )
				$metas['cancellation_reason'] = $parameters['cancellation_reason'];				
			if ( !empty($parameters['external_document']) )			
				$metas['external_document'] = $parameters['external_document'];		
			if ( !empty($parameters['external_document_date']) )			
				$metas['external_document_date'] = date("Y-m-d H:i:s", strtotime($parameters['external_document_date']));	
			foreach ( $metas as $meta_key => $meta_value )
				usam_update_lead_metadata($document_id, $meta_key, $meta_value);			
			if( isset($parameters['exchange_completed']) )
			{				
				$result = true;
				usam_update_lead_metadata($id, 'exchange', 1);		
				if ( !empty($parameters['date_exchange']) )		
					$date_exchange = date("Y-m-d H:i:s", strtotime($parameters['date_exchange']));
				else
					$date_exchange = date("Y-m-d H:i:s");	
				usam_update_lead_metadata($id, 'date_exchange', $date_exchange);			
			}
			if ( isset($parameters['groups']) )			
				usam_set_groups_object( $document_id, 'lead', $parameters['groups'] );			
			do_action('usam_document_lead_save', $document_id);
		}
		return $result;
	}
	
	public static function insert_lead( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
						
		require_once( USAM_FILE_PATH .'/includes/document/lead.class.php' );		
		if ( isset($parameters['user_id']) )
		{			
			$contact = usam_get_contact( $parameters['user_id'], 'user_id' );
			if ( $contact )
				$parameters['contact_id'] = $contact['id'];
			$company = usam_get_company( $parameters['user_id'], 'user_id' );
			if ( $company )
				$parameters['company_id'] = $company['id'];
		}		
		if ( !empty($parameters['type_price']) )
		{
			if ( !usam_get_setting_price_by_code( $parameters['type_price'] ) )
				unset($parameters['type_price']);
		}	
		elseif ( !empty($parameters['price_external_code']) )
		{			
			$setting_price = usam_get_setting_price_by_code( $parameters['price_external_code'], 'external_code' );
			if( $setting_price )
				$parameters['type_price'] = $setting_price['code'];
			unset($parameters['price_external_code']);
		}				
		if ( !empty($parameters['status']) )
		{
			require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
			$statuses = usam_get_object_statuses(['fields' => 'internalname', 'type' => 'lead']);
			if ( !in_array($parameters['status'], $statuses))
				unset($parameters['status']);
		}			
		$lead_products = null;
		if ( isset($parameters['products']) )
		{
			$lead_products = [];	
			foreach( $parameters['products'] as $key => $product )
			{			
				$product_id = 0;
				if ( !empty($product['code']) )
				{
					$code = sanitize_text_field($product['code']);
					$product_id = usam_get_product_id_by_code( $code );
				}
				elseif ( !empty($product['product_id']) )
					$product_id = absint($product['product_id']);
				if ( $product_id )	
				{
					$update = ['product_id' => $product_id ];
					$update['quantity'] = !empty($product['quantity'])?usam_string_to_float($product['quantity']):1;
					if ( isset($product['price']) )
						$update['price'] = usam_string_to_float($product['price']);
					if ( isset($product['old_price']) )
						$update['old_price'] = usam_string_to_float($product['old_price']);
					if ( isset($product['unit_measure']) )
						$update['unit_measure'] = sanitize_text_field($product['unit_measure']);
					$lead_products[] = $update;
				}
			}
			unset($parameters['products']);
		}		
		$document_id = usam_insert_lead($parameters, $lead_products);		
		if ( !$document_id )
			return false;
		$metas = [];
		if ( isset($parameters['cancellation_reason']) )
			$metas['cancellation_reason'] = $parameters['cancellation_reason'];				
		if ( !empty($parameters['external_document']) )			
			$metas['external_document'] = $parameters['external_document'];		
		if ( !empty($parameters['external_document_date']) )			
			$metas['external_document_date'] = date("Y-m-d H:i:s", strtotime($parameters['external_document_date']));	
		foreach ( $metas as $meta_key => $meta_value )
			usam_add_lead_metadata($document_id, $meta_key, $meta_value);	
		if ( !empty($parameters['properties']) )
		{
			usam_add_lead_customerdata( $document_id, $parameters['properties'] );					
		}	
		if ( isset($parameters['groups']) )			
			usam_set_groups_object( $document_id, 'lead', $parameters['groups'] );
		do_action('usam_document_lead_save', $document_id);
		return $document_id;
	}
			
	private static function update( $document_id, $parameters ) 
	{			
		$result = false;
		if ( $document_id )
		{
			$current_document = usam_get_document( $document_id );					
			if ( !$current_document )
				return new WP_Error( 'document_id', 'Invalid id', ['status' => 404]);
									
			if ( !current_user_can('universam_api') && !usam_check_document_access( $current_document, $current_document['type'], 'edit' ) )
				return new WP_Error( 'forbidden', 'Forbidden', ['status' => 403]);	
			if ( isset($parameters['user_id']) )
			{
				$parameters['user_id'] = absint($parameters['user_id']);
				$contact = usam_get_contact( $parameters['user_id'], 'user_id' );
				if ( $contact )
				{
					$args['customer_id'] = $contact['id'];
					$args['customer_type'] = 'contact';
				}
			}			
			if ( !empty($parameters['status']) )
			{
				require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
				$statuses = usam_get_object_statuses(['fields' => 'internalname', 'type' => $current_document['type']]);
				if ( !in_array($parameters['status'], $statuses))
					unset($parameters['status']);					
			}	
			$products = isset($parameters['products'])&& is_array($parameters['products'])?$parameters['products']:null;
			$result = usam_update_document($document_id, $parameters, $products);
			if ( isset($parameters['note']) )	
				usam_update_document_metadata($document_id, 'note', $parameters['note']);
			if ( isset($parameters['external_document']) )
				usam_update_document_metadata($document_id, 'external_document', $parameters['external_document']);	
			if ( isset($parameters['external_document_date']) )				
				usam_update_document_metadata($document_id, 'external_document_date', $parameters['external_document_date']);		
			if ( isset($parameters['document_content']) )				
				usam_update_document_content( $document_id, 'document_content', $parameters['document_content'] );
			if ( isset($parameters['description']) )				
				usam_update_document_content( $document_id, 'description', $parameters['description'] );
			if ( isset($parameters['conditions']) )				
				usam_update_document_content( $document_id, 'conditions', $parameters['conditions'] );			
			if ( isset($parameters['exchange_completed']) )
			{				
				$result = true;
				usam_update_document_metadata($document_id, 'exchange', 1);		
				if ( !empty($parameters['date_exchange']) )		
					$date_exchange = date("Y-m-d H:i:s", strtotime($parameters['date_exchange']));
				else
					$date_exchange = date("Y-m-d H:i:s");				
				usam_update_document_metadata($document_id, 'date_exchange', $date_exchange);			
			}			
			if( isset($parameters['contacts']) )
			{
				$contacts = usam_get_contact_ids_document(['document_id' => $document_id]);	
				if ( !empty($parameters['contacts']) )
				{ 		
					foreach($contacts as $customer)	
					{ 
						if ( !in_array($customer->contact_id, $parameters['contacts']) )
							usam_delete_contact_document( $document_id, $customer->contact_id, $customer->contact_type );	
					}	
					foreach($parameters['contacts'] as $contact_id)
						usam_add_contact_document( $document_id, $contact_id );	
				}	
				else
				{			
					foreach($contacts as $customer)	
						usam_delete_contact_document( $document_id, $customer->contact_id, $customer->contact_type );	
				}
			}
			if ( isset($parameters['groups']) )			
				usam_set_groups_object( $document_id, $current_document['type'], $parameters['groups'] );
		}
		return $result;
	}
	
	public static function insert( $parameters, $metas = [], $products = [] ) 
	{
		$links = [];
		if ( isset($parameters['links']) )
		{			
			$links = $parameters['links'];
			unset($parameters['links']);
		}	
		if ( isset($parameters['user_id']) )
		{
			$parameters['user_id'] = absint($parameters['user_id']);
			$contact = usam_get_contact( $parameters['user_id'], 'user_id' );
			if ( $contact )
			{
				$parameters['customer_id'] = $contact['id'];
				$parameters['customer_type'] = 'contact';
				unset($parameters['user_id']);
			}
		}		
		if ( !empty($parameters['code']) )			
			$parameters['external_document'] = sanitize_text_field($parameters['code']);		
		if ( !empty($parameters['external_document_date']) )			
			$metas['external_document_date'] = date("Y-m-d H:i:s", strtotime($parameters['external_document_date']));		
		if ( isset($parameters['note']) )	
			$metas['note'] = $parameters['note'];			
		if ( !empty($parameters['type_price']) )
		{
			$type_price = sanitize_text_field($parameters['type_price']);
			unset($parameters['type_price']);
			$prices = usam_get_prices(['type' => 'R']);
			foreach ( $prices as $price )
			{
				if ( $price['code'] == $type_price )
				{
					$parameters['type_price'] = $price['code'];
					break;
				}
			}
		}	
		elseif ( !empty($parameters['price_external_code']) )
		{
			$type_price = sanitize_text_field($parameters['price_external_code']);
			unset($parameters['price_external_code']);
			$prices = usam_get_prices(['type' => 'R']);
			foreach ( $prices as $price )
			{
				if ( $price['external_code'] == $type_price )
				{
					$parameters['type_price'] = $price['code'];
					break;
				}
			}
		}				 
		if ( !empty($parameters['status']) )
		{
			require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
			$statuses = usam_get_object_statuses(['fields' => 'internalname', 'type' => $parameters['type']]);
			if ( !in_array($parameters['status'], $statuses))
				unset($parameters['status']);					
		}
		if ( empty($parameters['status']) )		
			$parameters['status'] = 'draft';		
		$new_products = [];
		foreach( $products as $key => $product )
		{			
			$product_id = 0;
			if ( !empty($product['code']) )
			{
				$code = sanitize_text_field($product['code']);
				$product_id = usam_get_product_id_by_code( $code );
			}
			elseif ( !empty($product['product_id']) )
			{				
				$product['product_id'] = absint($product['product_id']);
				$product_id = $product['product_id'];				
			}
			if ( $product_id )	
			{	
				$product['product_id'] = $product_id;
				$product['quantity'] = !empty($product['quantity'])?usam_string_to_float($product['quantity']):1;
				if ( isset($product['price']) )
					$product['price'] = usam_string_to_float($product['price']);
				if ( isset($product['unit_measure']) )
					$product['unit_measure'] = sanitize_text_field($product['unit_measure']);
				$new_products[] = $product;
			}
		}	
		if ( isset($parameters['contacts']) )
		{
			$contacts = $parameters['contacts'];
			unset($parameters['contacts']);			
		}
		$document_id = usam_insert_document( $parameters, $new_products, $metas, $links );	
		if( $document_id )
		{
			if( !empty($contacts) )
			{
				foreach($contacts as $contact_id)
					usam_add_contact_document( $document_id, $contact_id );			
			}
			if ( !empty($parameters['groups']) )	
			{
				foreach( $parameters['groups'] as $group_id )
					usam_insert_group_object( $group_id, $document_id );		
			}
		}
		return $document_id;
	}
	
	public static function insert_check( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'check';		
		$metas = [];	
		if ( !empty($parameters['store_id']) )
			$metas['store_id'] = $parameters['store_id'];
		elseif ( !empty($parameters['store_code']) )
		{
			$storage = usam_get_storage( sanitize_text_field($parameters['store_code']), 'code' );
			if ( isset($storage['id']) )
				$metas['store_id'] = $storage['id'];		
		}
		$products = $parameters['products'];
		unset($parameters['products']);
		$metas['payment_type'] = !empty($parameters['payment_type']) ? $parameters['payment_type'] : 'cash';
		$metas['info_check'] = isset($parameters['info_check']) ? $parameters['info_check'] : 0;
		if ( isset($parameters['shift_id']) )
			$metas['shift_id'] = $parameters['shift_id'];	
		return USAM_Documents_API::insert( $parameters, $metas, $products );
	}	

	public static function delete_document( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		return usam_delete_document( $id );
	}	
	
	public static function delete_documents( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		if ( !empty($parameters['args']) )
			return usam_delete_documents( $parameters['args'] );
		return false;
	}		
	
	public static function manager_approving_document( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
				
		$current_document = usam_get_document( $document_id );			
		if ( !$current_document )
			return new WP_Error( 'document_id', 'Invalid id', ['status' => 404]);
								
		if ( !current_user_can('universam_api') && !usam_check_document_access( $current_document, $current_document['type'], 'view' ) ) // для подписи достаточно режима просмотра
			return new WP_Error( 'forbidden', 'Forbidden', ['status' => 403]);
		
		$contact_id = usam_get_contact_id();
		return usam_update_document_metadata($id, 'matching_'.$contact_id, $parameters['status']);  
	}	
			
	public static function get_payment( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_payment_document( $id );
		return apply_filters('usam_api_payment_document', $document);
	}	
		
	public static function get_buyer_refund( WP_REST_Request $request ) 
	{
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'buyer_refund' )
		{
			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}
	
	public static function insert_buyer_refund( WP_REST_Request $request ) 
	{
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'buyer_refund';		
		$metas = [];	
		if ( !empty($parameters['store_id']) )
			$metas['store_id'] = $parameters['store_id'];
		elseif ( !empty($parameters['store_code']) )
		{
			$storage = usam_get_storage( sanitize_text_field($parameters['store_code']), 'code' );
			if ( isset($storage['id']) )
				$metas['store_id'] = $storage['id'];		
		}		
		$products = $parameters['products'];
		$order_id = 0;
		if ( !empty($parameters['order_external_code']) )
		{
			require_once(USAM_FILE_PATH.'/includes/document/orders_query.class.php');
			$order = usam_get_orders(['code' => $parameters['order_external_code'], 'number' => 1]);
			if ( !empty($order) )
			{
				$parameters['links'] = [['document_id' => $order['id'], 'document_type' => 'order']];
				$order_id = $order['id'];
				$products = [];
				foreach ( usam_get_products_order( $order['id'] ) as $product_order )
				{
					foreach ( $parameters['products'] as $product )
					{
						if ( !empty($product['code']) )
						{
							$code = sanitize_text_field($product['code']);
							$product_id = usam_get_product_id_by_code( $code );
						}
						elseif ( !empty($product['product_id']) )
						{				
							$product['product_id'] = absint($product['product_id']);
							$product_id = $product['product_id'];				
						}
						if ( $product_order->product_id == $product_id )
							$products[] = array_merge((array)$product_order, (array)$product);
					}
				}
				if ( empty($parameters['price_external_code']) )
					$parameters['type_price'] = $order['type_price'];
				if ( empty($parameters['bank_account_code']) )
					$parameters['bank_account_id'] = $order['bank_account_id'];
				if ( !empty($order['company_id']) )
				{
					$parameters['customer_type'] = 'company';
					$parameters['customer_id'] = $order['company_id'];
				}
				else
				{
					$parameters['customer_id'] = $order['contact_id'];
					$parameters['customer_type'] = 'contact';					
				}
			}
		}		
		unset($parameters['products']);
		if ( isset($parameters['store_id']) )
			$metas['store_id'] = $parameters['store_id'];
		return USAM_Documents_API::insert( $parameters, $metas, $products );
	}	
	
	public static function update_buyer_refund( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		USAM_Documents_API::update($id, $parameters);
		if ( isset($parameters['store_id']) )
			usam_update_document_metadata($id, 'store_id', $parameters['store_id']);	
		return true;
	}
		
	public static function get_decree( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'decree' )
		{
			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}
	
	public static function insert_decree( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'decree';		
		$metas = [];		
		$id = USAM_Documents_API::insert( $parameters, $metas );		
		if ( isset($parameters['document_content']) )				
			usam_update_document_content( $id, 'document_content', $parameters['document_content'] );
		return $id;
	}	
	
	public static function update_decree( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		return USAM_Documents_API::update($id, $parameters);
	}	
	
	public static function get_invoice_payment( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'invoice_payment' )
		{
			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;	
	}
	
	public static function insert_invoice_payment( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'invoice_payment';		
		$metas = [];		
		return USAM_Documents_API::insert( $parameters, $metas );
	}
	
	public static function update_invoice_payment( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		return USAM_Documents_API::update($id, $parameters);
	}
	
	public static function get_proxy( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'proxy' )
		{			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;	
	}
	
	public static function update_proxy( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		return USAM_Documents_API::update($id, $parameters);
	}
			
	public static function get_additional_agreement( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'additional_agreement' )
		{
			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}
	
	public static function insert_additional_agreement( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'additional_agreement';				
		$metas = [];		
		if ( isset($parameters['closedate']) )	
			$metas['closedate'] = $parameters['closedate'];
		if ( isset($parameters['contract']) )	
			$metas['contract'] = $parameters['contract'];
		$id = USAM_Documents_API::insert( $parameters, $metas );		
		if ( isset($parameters['document_content']) )				
			usam_update_document_content( $id, 'document_content', $parameters['document_content'] );
		return $id;
	}
	
	public static function update_additional_agreement( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		return USAM_Documents_API::update($id, $parameters);
	}
	
	public static function get_invoice( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'invoice' )
		{
		
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;	
	}
	
	public static function insert_invoice( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'invoice';		
		$products = $parameters['products'];
		unset($parameters['products']);
		$metas = [];		
		if ( isset($parameters['closedate']) )	
			$metas['closedate'] = $parameters['closedate'];
		if ( isset($parameters['contract']) )	
			$metas['contract'] = $parameters['contract'];
		return USAM_Documents_API::insert( $parameters, $metas, $products );
	}
	
	public static function update_invoice( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		$result = USAM_Documents_API::update($id, $parameters);	
		if ( isset($parameters['contract']) )	
			usam_update_document_metadata($id, 'contract', $parameters['contract']);	 
		return $result;
	}
	
	public static function insert_invoice_offer( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'invoice_offer';		
		$products = $parameters['products'];
		unset($parameters['products']);
		$metas = [];		
		if ( isset($parameters['closedate']) )	
			$metas['closedate'] = $parameters['closedate'];
		if ( isset($parameters['contract']) )	
			$metas['contract'] = $parameters['contract'];
		return USAM_Documents_API::insert( $parameters, $metas, $products );
	}
	
	public static function get_invoice_offer( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'invoice_offer' )
		{
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;	
	}
	
	public static function update_invoice_offer( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		return USAM_Documents_API::update($id, $parameters);
	}
	
	public static function get_acts( WP_REST_Request $request )
	{		
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );	
		$query_vars['type'] = 'act';		

		if ( isset($parameters['add_fields']) )
		{
			if ( in_array('products', $parameters['add_fields']) )
				$query_vars['cache_products'] = true;
		}
		$query = new USAM_Documents_Query( $query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			if ( isset($parameters['add_fields']) && in_array('properties', $parameters['add_fields']) )
				$properties = usam_get_properties(['access' => true, 'type' => 'act']);
			
			foreach ( $items as $key => $item ) 
			{				
				if ( isset($item->date_insert) )
					$items[$key]->date_insert = get_date_from_gmt( $item->date_insert );
				if ( isset($item->totalprice) )
					$item->totalprice_currency = usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price]);
				if ( isset($item->status) )
				{
					$items[$key]->status_name = usam_get_object_status_name( $item->status, $item->type );	
					$items[$key]->status_is_completed = (int)usam_check_object_is_completed( $item->status, $item->type );
				}			
				if ( isset($parameters['add_fields']) )
				{
					if ( in_array('products', $parameters['add_fields']) )
						$items[$key]->products = self::get_products_document($item->id, $item->type_price );
					if ( in_array('taxes', $parameters['add_fields']) )
						$items[$key]->taxes = usam_get_document_product_taxes( $item->id );						
					if ( in_array('properties', $parameters['add_fields']) )
					{						
						foreach( $properties as $p )
							$item->properties[$p->code] = usam_get_object_property_value( $item->id, $p );
					}
				}
			}
			$items = apply_filters( 'usam_api_documents', $items, $parameters );
			$results = ['count' => $query->get_total(), 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}	
	
	public static function get_act( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$parameters = self::get_parameters( $request );	
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'act' )
		{			
			if ( !empty($parameters['add_fields']) )
			{
				if ( in_array('products', $parameters['add_fields']) )
				{
					$products = usam_get_products_document( $id );		
					$document['taxes'] = usam_get_document_product_taxes( $id );
					$document['products'] = self::get_products($products, $document['taxes'], $document['type_price'] );
				}
				if ( in_array('document_products', $parameters['add_fields']) )
				{
					$products = usam_get_products_document( $id );		
					$document['taxes'] = usam_get_document_product_taxes( $id );
					$document['products'] = self::get_products($products, $document['taxes'], $document['type_price'] );						
					$user_columns = usam_get_user_columns( $document->type );			
					foreach ( $document['products'] as &$product ) 
					{
						$product->url = get_permalink( $product->product_id );										
						foreach( $user_columns as $column)
							$product->$column = usam_get_product_property($product->product_id, $column );	
					}
					$document['contact_type_price'] = (string)usam_get_contact_metadata( $document['contact_id'], 'type_price');	
				}
			}
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}
	
	public static function insert_act( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'act';		
		$products = $parameters['products'];
		unset($parameters['products']);
		$metas = [];
		return USAM_Documents_API::insert( $parameters, $metas, $products );
	}
	
	public static function update_act( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		return USAM_Documents_API::update($id, $parameters);
	}	
	
	public static function get_suggestion( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'suggestion' )
		{
			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}
	
	public static function insert_suggestion( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'suggestion';		
		$products = $parameters['products'];
		unset($parameters['products']);
		$metas = [];		
		if ( isset($parameters['closedate']) )	
			$metas['closedate'] = $parameters['closedate'];
		return USAM_Documents_API::insert( $parameters, $metas, $products );
	}
	
	public static function update_suggestion( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		return USAM_Documents_API::update($id, $parameters);
	}
	
	public static function get_movement( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'movement' )
		{
			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}
	
	public static function insert_movement( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'movement';		
		$metas = [];		
		if ( isset($parameters['from_storage']) )	
			$metas['from_storage'] = $parameters['from_storage'];
		if ( isset($parameters['for_storage']) )	
			$metas['for_storage'] = $parameters['for_storage'];
		$products = $parameters['products'];
		unset($parameters['products']);
		return USAM_Documents_API::insert( $parameters, $metas, $products );
	}
	
	public static function update_movement( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		$result = USAM_Documents_API::update($id, $parameters);
		if ( isset($parameters['from_storage']) )	
			usam_update_document_metadata($id, 'from_storage', $parameters['from_storage']);
		if ( isset($parameters['for_storage']) )	
			usam_update_document_metadata($id, 'for_storage', $parameters['for_storage']);	 
		return $result;
	}
	
	public static function get_receipt( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'receipt' )
		{
			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}
	
	public static function insert_receipt( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'receipt';		
		$metas = [];		
		if ( !empty($parameters['store_id']) )
			$metas['store_id'] = $parameters['store_id'];
		elseif ( !empty($parameters['store_code']) )
		{
			$storage = usam_get_storage( sanitize_text_field($parameters['store_code']), 'code' );
			if ( isset($storage['id']) )
				$metas['store_id'] = $storage['id'];		
		}
		$products = [];
		if ( isset($parameters['products']) )
		{
			$products = $parameters['products'];
			unset($parameters['products']);
		}
		return USAM_Documents_API::insert( $parameters, $metas, $products );
	}
	
	public static function update_receipt( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		USAM_Documents_API::update($id, $parameters);
		if ( isset($parameters['store_id']) )
			usam_update_document_metadata($id, 'store_id', $parameters['store_id']);	
		return true;
	}
	
	public static function get_payment_order( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'payment_order' )
		{
			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}
	
	public static function insert_payment_order( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'payment_order';		
		$metas = [];		
		return USAM_Documents_API::insert( $parameters, $metas );
	}
	
	public static function update_payment_order( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		return USAM_Documents_API::update($id, $parameters);
	}
	
	public static function get_payment_received( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'payment_received' )
		{
			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}
	
	public static function update_payment_received( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		return USAM_Documents_API::update($id, $parameters);
	}
	
	public static function get_partner_order( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'partner_order' )
		{
			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}	
	
	public static function update_partner_order( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		return USAM_Documents_API::update($id, $parameters);
	}
	
	public static function get_reconciliation_act( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'reconciliation_act' )
		{
			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}
	
	public static function insert_reconciliation_act( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'reconciliation_act';			
		$metas = [];		
		if ( isset($parameters['contract']) )	
			$metas['contract'] = $parameters['contract'];
		$id = USAM_Documents_API::insert( $parameters, $metas );
		if ( isset($parameters['contract']) )	
			usam_update_document_metadata($id, 'contract', $parameters['contract']);
		if ( !empty($parameters['start_date']) )	
			usam_update_document_metadata($id, 'start_date', $parameters['start_date']);	
		$end_date = !empty($parameters['end_date']) ? strtotime($parameters['end_date']) : time();
		usam_update_document_metadata( $id, 'end_date', date("Y-m-d", $end_date)." 23:59:59" );	
		USAM_Documents_API::recalculate_reconciliation_act( $id );	
		return $id;
	}
	
	public static function recalculate_reconciliation_act( $id ) 
	{
		$document = usam_get_document( $id );
		$start_date = usam_get_document_metadata($id, 'start_date');
		$end_date = usam_get_document_metadata($id, 'end_date');
		if ( !$end_date )
		{
			$end_date = date("Y-m-d", $end_date)." 23:59:59";	
			usam_update_document_metadata($id, 'end_date', $end_date );			
		}		
		$start_balance = 0;
		$end_balance = 0;
		$sum = 0;
		if ( !empty($document['customer_id']) && $document['bank_account_id'] && $document['customer_type'] )
		{						
			require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );
			$v = apply_filters('usam_reconciliation_documents', 'act' );
			if ( $v == 'payment_received' )						
				$document_type = ['invoice','invoice_offer','payment_received'];
			else
				$document_type = ['invoice','invoice_offer','act'];
			$documents = usam_get_documents(['customer_id' => $document['customer_id'], 'bank_account_id' => $document['bank_account_id'], 'customer_type' => $document['customer_type'], 'type' => $document_type, 'status' => ['paid','approved','sent'], 'orderby' => 'date_insert']);
			usam_delete_document_link(['document_id' => $id, 'document_type' => $document['type']]);							
			foreach ( $documents as $doc )
			{
				if ( !$start_date )
					$start_date = $doc->date_insert;							
				if( (!$start_date || $doc->date_insert >= $start_date) && (!$end_date || $doc->date_insert <= $end_date) )
				{  
					usam_add_document_link(['document_id' => $document['id'], 'document_type' => $document['type'], 'document_link_id' => $doc->id, 'document_link_type' => $doc->type, 'link_type' => 'subordinate']);
					if ( $v == 'payment_received' )
					{
						if ( $doc->type != 'payment_received' )
						{
							$sum += $doc->totalprice;
							$end_balance -= $doc->totalprice;
						}
						else
							$end_balance += $doc->totalprice;
					}
					else
					{
						if ( $doc->type == 'act' )
						{
							$sum += $doc->totalprice;
							$end_balance -= $doc->totalprice;
						}
						else
							$end_balance += $doc->totalprice;
					}	
				}
				elseif( !$start_date || $doc->date_insert <= $start_date)
				{ 
					if ( $v == 'payment_received' )
					{
						if ( $doc->type != 'payment_received' )									
							$start_balance -= $doc->totalprice;
						else
							$start_balance += $doc->totalprice;
					}
					else
					{
						if ( $doc->type == 'act' )
							$start_balance -= $doc->totalprice;
						else
							$start_balance += $doc->totalprice;
					}																					
				}
			}	
			$end_balance += $start_balance;						
		}	
		usam_update_document( $id, ['totalprice' => $sum]);
		usam_update_document_metadata($id, 'start_date', $start_date ); 							
		usam_update_document_metadata($id, 'start_balance', $start_balance ); 					
		usam_update_document_metadata($id, 'end_balance', $end_balance ); 
	}
	
	public static function update_reconciliation_act( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		USAM_Documents_API::update($id, $parameters);
		if ( isset($parameters['contract']) )	
			usam_update_document_metadata($id, 'contract', $parameters['contract']);
		if ( isset($parameters['start_date']) )	
			usam_update_document_metadata($id, 'start_date', $parameters['start_date']);	
		if ( isset($parameters['end_date']) )	
			usam_update_document_metadata($id, 'end_date', date("Y-m-d", strtotime($parameters['end_date']))." 23:59:59");		
		USAM_Documents_API::recalculate_reconciliation_act( $id );	
		return true;
	}
	
	public static function get_checks( WP_REST_Request $request )
	{		
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );	
		$query_vars['cache_products'] = true;
		$query_vars['type'] = 'check';		

		if ( isset($query_vars['fields']) )
			unset($query_vars['fields']);		

		if ( isset($parameters['add_fields']) )
		{
			if ( in_array('products', $parameters['add_fields']) )
				$query_vars['cache_products'] = true;
		}
		$query = new USAM_Documents_Query( $query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			if ( isset($parameters['add_fields']) && in_array('properties', $parameters['add_fields']) )
				$properties = usam_get_properties(['access' => true, 'type' => 'check']);
			
			foreach ( $items as $key => $item ) 
			{				
				if ( isset($item->date_insert) )
					$items[$key]->date_insert = get_date_from_gmt( $item->date_insert );
				if ( isset($item->totalprice) )
					$item->totalprice_currency = usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price]);
				if ( isset($item->status) )
				{
					$items[$key]->status_name = usam_get_object_status_name( $item->status, $item->type );	
					$items[$key]->status_is_completed = (int)usam_check_object_is_completed( $item->status, $item->type );
				}			
				if ( isset($parameters['add_fields']) )
				{
					if ( in_array('products', $parameters['add_fields']) )
						$items[$key]->products = self::get_products_document($item->id, $item->type_price );
					if ( in_array('taxes', $parameters['add_fields']) )
						$items[$key]->taxes = usam_get_document_product_taxes( $item->id );						
					if ( in_array('properties', $parameters['add_fields']) )
					{						
						foreach( $properties as $p )
							$item->properties[$p->code] = usam_get_object_property_value( $item->id, $p );
					}
				}
			}
			$items = apply_filters( 'usam_api_documents', $items, $parameters );
			$results = ['count' => $query->get_total(), 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function update_check( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		USAM_Documents_API::update($id, $parameters);		
		if ( isset($parameters['store_id']) )	
			usam_update_document_metadata($id, 'store_id', $parameters['store_id']);
		if ( isset($parameters['shift_id']) )	
			usam_update_document_metadata($id, 'shift_id', $parameters['shift_id']);	
		if ( isset($parameters['info_check']) )	
			usam_update_document_metadata($id, 'info_check', $parameters['info_check']);
		if ( isset($parameters['payment_type']) )	
			usam_update_document_metadata($id, 'payment_type', $parameters['payment_type']);		
		return true;
	}
		
	public static function get_check( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'check' )
		{		
			$document['date_insert'] = usam_local_date(strtotime($document['date_insert']), 'c');								
			$document['properties'] = [];
			$document['products'] = self::get_products_document($document['id'], $document['type_price']);
			$document['taxes'] = usam_get_document_product_taxes( $id );
		/*	$properties = usam_get_cache_properties();	
			$code = [];	
			foreach ( $properties as $property )
			{	 		
				$property->value = usam_get_order_metadata( $id, $property->code );
				if ( $property->value !== false )
				{
					$code[] = $property->group;
					$document['properties'][$property->code] = usam_format_property_api( $property );
				}
			}	*/
			$document['totalprice_currency'] = usam_get_formatted_price( $document['totalprice'], ['type_price' => $document['type_price']]);
			$document['discount_currency'] = usam_get_formatted_price( $document['discount'], ['type_price' => $document['type_price']]);
			return apply_filters('usam_api_'.$document['type'].'_document', $document);			
		}	
		return null;
	}
			
	public static function save_checks( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		usam_update_object_count_status( false );
		$results = [];
		foreach( $parameters['items'] as $item )
		{
			$document_id = 0;		
			if ( !empty($item['code']) )
			{
				$code = sanitize_text_field($item['code']);			
				$document_id = usam_get_document_id_by_meta( 'external_code', $code );  
			}
			elseif ( !empty($item['id']) )
				$document_id = absint($item['id']);
			
			if ( $document_id )
				USAM_Documents_API::update($id, $item);
			else
			{
				$item['type'] = 'check';
				$metas = [];	
				if ( !empty($item['code']) )
					$metas['external_code'] = $code;
				if ( !empty($item['store_id']) )
					$metas['store_id'] = $item['store_id'];
				elseif ( !empty($item['store_code']) )
				{
					$storage = usam_get_storage( sanitize_text_field($item['store_code']), 'code' );
					if ( isset($storage['id']) )
						$metas['store_id'] = $storage['id'];		
				}
				if ( !empty($item['products']) )
				{
					$products = $item['products'];
					unset($item['products']);
					if ( empty($metas['payment_type']) )
						$metas['payment_type'] = 'cash';
					$document_id = USAM_Documents_API::insert( $item, $metas, $products );
				}
			}
			if ( isset($code) )
				$results[$code] = $document_id;
			else
				$results[] = 0;
		}	
		usam_update_object_count_status( true );
		return $results;
	}	
		
	public static function get_contracts( WP_REST_Request $request )
	{		
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );	
		$query_vars['type'] = 'contract';	
		
		$query = new USAM_Documents_Query( $query_vars );		
		$items = $query->get_results();		
		if ( !empty($items) )
		{
			if ( isset($parameters['add_fields']) && in_array('properties', $parameters['add_fields']) )
				$properties = usam_get_properties(['access' => true, 'type' => 'contract']);
			
			foreach ( $items as $key => $item ) 
			{				
				if ( isset($item->date_insert) )
					$items[$key]->date_insert = get_date_from_gmt( $item->date_insert );
				if ( isset($item->totalprice) )
					$item->totalprice_currency = usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price]);
				if ( isset($item->status) )
				{
					$items[$key]->status_name = usam_get_object_status_name( $item->status, $item->type );	
					$items[$key]->status_is_completed = (int)usam_check_object_is_completed( $item->status, $item->type );
				}			
				if ( isset($parameters['add_fields']) )
				{					
					if ( in_array('properties', $parameters['add_fields']) )
					{						
						foreach( $properties as $p )
							$item->properties[$p->code] = usam_get_object_property_value( $item->id, $p );
					}
				}
			}
			$results = ['count' => $query->get_total(), 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function insert_contract( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'contract';			
		$metas = [];
		if ( isset($parameters['closedate']) )	
			$metas['closedate'] = $parameters['closedate'];
		$id = USAM_Documents_API::insert( $parameters, $metas );	
		if ( isset($parameters['document_content']) )				
			usam_update_document_content( $id, 'document_content', $parameters['document_content'] );
		return $id;
	}
	
	public static function update_contract( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		
		return USAM_Documents_API::update($id, $parameters);
	}
		
	public static function get_contract( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'contract' )
		{		
			$document['date_insert'] = usam_local_date(strtotime($document['date_insert']), 'c');								
			$document['properties'] = [];
		/*	$properties = usam_get_cache_properties();	
			$code = [];	
			foreach ( $properties as $property )
			{	 		
				$property->value = usam_get_order_metadata( $id, $property->code );
				if ( $property->value !== false )
				{
					$code[] = $property->group;
					$document['properties'][$property->code] = usam_format_property_api( $property );
				}
			}	*/
			$document['totalprice_currency'] = usam_get_formatted_price( $document['totalprice'], ['type_price' => $document['type_price']]);
			return apply_filters('usam_api_'.$document['type'].'_document', $document);			
		}	
		return null;
	}
			
	public static function save_contracts( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		usam_update_object_count_status( false );
		$results = [];
		foreach( $parameters['items'] as $item )
		{
			$document_id = 0;		
			if ( !empty($item['code']) )
			{
				$code = sanitize_text_field($item['code']);			
				$document_id = usam_get_document_id_by_meta( 'external_code', $code );  
			}
			elseif ( !empty($item['id']) )
				$document_id = absint($item['id']);
			
			if ( $document_id )
				USAM_Documents_API::update($id, $item);
			else
			{
				$item['type'] = 'contract';
				$metas = [];	
				if ( !empty($item['code']) )
					$metas['external_code'] = $code;				
				$document_id = USAM_Documents_API::insert( $item, $metas, $products );
			}
			if ( isset($code) )
				$results[$code] = $document_id;
			else
				$results[] = 0;
		}	
		usam_update_object_count_status( true );
		return $results;
	}	
	
	public static function get_orders_contractor( WP_REST_Request $request )
	{		
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );	
		if ( !empty($parameters['add_fields']) )	
		{				
			if ( in_array('products', $parameters['add_fields']) || in_array('document_products', $parameters['add_fields']) )
				self::$query_vars['cache_products'] = true;		
		}
		self::$query_vars['type'] = 'order_contractor';			
		$query = new USAM_Documents_Query( self::$query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			$count = $query->get_total();
			foreach( $items as $key => &$item ) 
			{				
				if ( isset($item->date_insert) )
					$item->date_insert = get_date_from_gmt( $item->date_insert );
				if ( isset($item->totalprice) )
					$item->totalprice_currency = usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price]);
				if ( isset($item->status) )
				{
					$item->status_name = usam_get_object_status_name( $item->status, $item->type );	
					$item->status_is_completed = (int)usam_check_object_is_completed( $item->status, $item->type );
				}			
				if ( !empty($parameters['add_fields']) )
				{
					if ( in_array('products', $parameters['add_fields']) )
						$item->products = self::get_products_document($item->id, $item->type_price );
					if ( in_array('document_products', $parameters['add_fields']) )
					{
						$item->products = self::get_products_document($item->id, $item->type_price );
						$user_columns = usam_get_user_columns( $item->type );
						foreach ( $item->products as &$product ) 
						{
							$product->url = get_permalink( $product->product_id );
							foreach( $user_columns as $column)
								$product->$column = usam_get_product_property($product->product_id, $column );	
						}
					}
					if ( in_array('taxes', $parameters['add_fields']) )
						$item->taxes = usam_get_document_product_taxes( $item->id );
					if ( in_array('note', $parameters['add_fields']) )
						$item->note = (string)usam_get_document_metadata($item->id, 'note');
				}
				$item->external_document_date = (string)usam_get_document_metadata( $item->id, 'external_document_date' ); 
				if( $item->external_document_date )
					$item->external_document_date = get_date_from_gmt($item->external_document_date);		
				$item->external_document = (string)usam_get_document_metadata( $item->id, 'external_document' );	
				$item->track_id = (string)usam_get_document_metadata( $item->id, 'track_id' );	
				$item->exchange = usam_get_document_metadata( $item->id, 'exchange' );		
			}
			$items = apply_filters( 'usam_api_documents', $items, $parameters );
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function get_order_contractor( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'order_contractor' )
		{		
			$document['date_insert'] = usam_local_date(strtotime($document['date_insert']), 'c');	
			$document['products'] = self::get_products_document($document['id'], $document['type_price']);
			$document['taxes'] = usam_get_document_product_taxes( $id );
			$document['totalprice_currency'] = usam_get_formatted_price( $document['totalprice'], ['type_price' => $document['type_price']]);
			$document['discount_currency'] = usam_get_formatted_price( $document['discount'], ['type_price' => $document['type_price']]);
			$document['track_id'] = usam_get_document_metadata( $document['id'], 'track_id' );			
			return apply_filters('usam_api_'.$document['type'].'_document', $document);			
		}	
		return null;
	}
	
	public static function update_order_contractor( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		
		USAM_Documents_API::update($id, $parameters);
		if ( isset($parameters['track_id']) )	
			usam_update_document_metadata($id, 'track_id', $parameters['track_id']);		
		return true;
	}	
	
	public static function insert_order_contractor( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$parameters['type'] = 'order_contractor';		
		$metas = [];		
		$products = $parameters['products'];
		unset($parameters['products']);
		return USAM_Documents_API::insert( $parameters, $metas, $products);
	}
			
	public static function save_orders_contractor( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$return = [];
		usam_update_object_count_status( false );
		$args = [];
		foreach( $parameters['items'] as $k => $item )
		{	
			if ( !empty($item['date_insert']) )
				$item['date_insert'] = USAM_Request_Processing::sanitize_date( $item['date_insert'] );
			if ( !empty($item['id']) )
			{
				$id = absint($item['id']);
				if( $id )
					$args['include'][] = $id;	
				$return[$k] = [];
			}
			else
			{
				$products = [];
				$metas = [];	
				if ( !empty($item['document_id']) )
					$link = ['document_id' => $item['document_id'], 'document_type' => 'order'];		
				if ( !empty($item['external_code']) )
					$metas['external_code'] = $item['external_code'];
				if( !empty($item['products']) )
				{
					$products = $item['products'];
					unset($item['products']);
				}	
				$document_id = USAM_Documents_API::insert( (array)$item, $metas, $products ); 
				$return[$k] = usam_get_document( $document_id );
				if ( !empty($item['external_code']) )
					$return[$k] = $item['external_code'];
			}			
		}	
		if ( !empty($args) )
		{
			require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
			$documents = usam_get_documents( $args );	
			foreach( $documents as $document )
			{
				foreach( $parameters['items'] as $k => $item )
				{
					if( !empty($item['id']) && $item['id'] == $document->id )
					{						
						$return[$k] = $item;
						$document_id = USAM_Documents_API::update( $document->id, $item );						
						if ( isset($item['track_id']) )	
							usam_update_document_metadata($document->id, 'track_id', $item['track_id']);			
						unset($parameters['items'][$k]);
					}
				}
			}
		}
		usam_update_object_count_status( true );
		return $return;
	}	
	
	public static function get_check_return( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );		
		$document = usam_get_document( $id );	
		if ( !empty($document['type']) && $document['type'] == 'check_return' )
		{
			return apply_filters('usam_api_'.$document['type'].'_document', $document);
		}
		else
			return null;		
	}
	
	public static function update_check_return( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		return USAM_Documents_API::update($id, $parameters);
	}
	
	public static function get_list_units( WP_REST_Request $request ) 
	{			
		$parameters = $request->get_json_params();		
		if ( !$parameters )
		{
			$parameters = $request->get_body_params();	
			if ( !$parameters )
				$parameters = $request->get_query_params();
		}		
		$units = usam_get_list_units();
		if( !empty($parameters['fields']) )
		{
			$r = [];
			foreach( $units as $unit )
			{				
				if ( $parameters['fields'] == 'code=>short' )
					$r[$unit['code']] = $unit['short'];
			}
			return $r;
		}
		return $units;
	}

	private static function get_products_document( $id, $type_price = '' ) 
	{
		$product_taxes = usam_get_document_product_taxes( $id );
		$products = usam_get_products_document( $id );
		foreach( $products as $k => &$product )
		{
			$not_in_price = isset($product_taxes[$product->product_id]['not_in_price'])?$product_taxes[$product->product_id]['not_in_price']:0;				
			$product->discount = $product->old_price?$product->old_price - $product->price:0;
			$product->quantity = usam_get_formatted_quantity_product( $product->quantity, $product->unit_measure );			
				
			$product->sku = usam_get_product_meta( $product->product_id, 'sku' );
			if ( current_user_can('universam_api') )
				$product->code = usam_get_product_meta( $product->product_id, 'code' );
			$product->date_insert = get_date_from_gmt($product->date_insert);
			$product->quantity_unit_measure = usam_get_formatted_quantity_product_unit_measure( $product->quantity, $product->unit_measure );
			$product->small_image = usam_get_product_thumbnail_src($product->product_id, 'small-product-thumbnail');	
			$product->price_currency = usam_get_formatted_price($product->price, ['type_price' => $type_price]);
			$product->old_price_currency = usam_get_formatted_price($product->old_price, ['type_price' => $type_price]);
			$product->discount_currency = usam_get_formatted_price($product->discount, ['type_price' => $type_price]);
			$product->total = $product->price * ($product->price + $not_in_price);		
			$product->total_currency = usam_get_formatted_price($product->total, ['type_price' => $type_price]);				
		}
		return $products;
	}	
	
	public static function get_additional_agreements( WP_REST_Request $request )
	{		
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );	
		$query_vars['cache_products'] = true;
		$query_vars['type'] = 'additional_agreement';				
	
		$query = new USAM_Documents_Query( $query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			if ( isset($parameters['add_fields']) && in_array('properties', $parameters['add_fields']) )
				$properties = usam_get_properties(['access' => true, 'type' => 'additional_agreement']);
			
			foreach ( $items as $key => $item ) 
			{				
				if ( isset($item->date_insert) )
					$items[$key]->date_insert = get_date_from_gmt( $item->date_insert );
				if ( isset($item->totalprice) )
					$item->totalprice_currency = usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price]);
				if ( isset($item->status) )
				{
					$items[$key]->status_name = usam_get_object_status_name( $item->status, $item->type );	
					$items[$key]->status_is_completed = (int)usam_check_object_is_completed( $item->status, $item->type );
				}			
				if ( isset($parameters['add_fields']) )
				{					
					if ( in_array('properties', $parameters['add_fields']) )
					{						
						foreach( $properties as $p )
							$item->properties[$p->code] = usam_get_object_property_value( $item->id, $p );
					}
				}
			}
			$items = apply_filters( 'usam_api_documents', $items, $parameters );
			$results = ['count' => $query->get_total(), 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function get_documents( WP_REST_Request $request )
	{		
		$results = ['count' => 0, 'items' => []];
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );	
		$types = [];
		foreach( array_keys(usam_get_details_documents()) as $document_type )
		{			
			if ( self::viewing_allowed($document_type) )
				$types[] = $document_type;
		}
		if ( $types )
		{
			self::$query_vars['conditions'] = [['key' => 'type', 'value' => $types, 'compare' => 'IN']];		
			$query = new USAM_Documents_Query( self::$query_vars );		
			$items = $query->get_results();	
			if ( !empty($items) )
			{
				$details_documents = usam_get_details_documents();
				foreach ( $items as $key => $item ) 
				{				
					if ( isset($item->date_insert) )
						$items[$key]->date_insert = get_date_from_gmt( $item->date_insert );
					if ( isset($item->totalprice) )
						$item->totalprice_currency = usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price]);								
					$items[$key]->document_type = isset($details_documents[$item->type]) ? $details_documents[$item->type]['single_name'] : '';
					if ( isset($parameters['add_fields']) )
					{
						if ( in_array('manager', $parameters['add_fields']) )
							$item->manager = usam_get_contact( $item->manager_id, 'user_id' );					
						if ( in_array('currency', $parameters['add_fields']) )
							$item->currency = usam_get_currency_sign_price_by_code( $item->type_price );
						if ( in_array('status_data', $parameters['add_fields']) )
						{
							$object_status = usam_get_object_status_by_code( $item->status, $item->type );
							$item->status_name = isset($object_status['name'])?$object_status['name']:'';
							$item->status_color = isset($object_status['color'])?$object_status['color']:'';
							$item->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';		
						}
					}
				}
				$items = apply_filters( 'usam_api_documents', $items, $parameters );
				$results = ['count' => $query->get_total(), 'items' => $items];
			}
		}			
		return $results;
	}	
	
	protected static function viewing_allowed( $document_type )
	{	
		static $department_employees = null, $bank_accounts = null;
		$contact_id = usam_get_contact_id();
		$contact = usam_get_contact( $contact_id );
		$user_id = get_current_user_id();
								
		$conditions = [];
		$access_allowed = false;
		if ( current_user_can('any_view_'.$document_type) )
			$access_allowed = true;
		elseif ( current_user_can('view_'.$document_type) )
		{
			if ( $document_type == 'payment' || $document_type == 'shipped' )
				return true;
			$conditions[] = ['key' => 'manager_id', 'value' => [$user_id, 0], 'compare' => 'IN'];
			$access_allowed = true;
		}
		elseif ( current_user_can('company_view_'.$document_type) )
		{
			if ( $document_type == 'shipped' )
				return true;
			if ( !empty($contact['company_id']) )
			{
				if ( $bank_accounts === null )
				{
					$bank_accounts = usam_get_bank_accounts(['company_id' => $contact['company_id'], 'fields' => 'id']);	
					$bank_accounts[] = 0;
				}
				$conditions[] = ['key' => 'bank_account_id', 'value' => $bank_accounts, 'compare' => 'IN'];
				$access_allowed = true;
			}
		}
		elseif ( current_user_can('department_view_'.$document_type) )
		{
			if ( $document_type == 'payment' || $document_type == 'shipped' )
				return true;
			$department_id = usam_get_contact_metadata($contact_id, 'department');
			if ( $department_id )
			{ 
				if ( $department_employees === null )
				{
					$department_employees = usam_get_contacts(['meta_key' => 'department', 'source' => 'all', 'meta_value' => $department_id, 'fields' => 'user_id']);					
					$department_employees[] = 0;
				}
				$conditions[] = ['key' => 'manager_id', 'value' => $department_employees, 'compare' => 'IN'];
				$access_allowed = true;
			}
		}
		if ( $conditions )
			self::$query_vars['conditions'] = $conditions;			
		return $access_allowed;
	}
}
?>