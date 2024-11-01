<?php 
class USAM_Basket_API extends USAM_API
{		
	public static function order_in_basket( WP_REST_Request $request ) 
	{	
		$order_id = $request->get_param( 'order_id' );	
		$order_data = usam_get_order( $order_id );		
		if ( $order_data )
		{	
			$products = usam_get_products_order( $order_id );	
			$properties = ['type_price' => $order_data['type_price']];	
			$properties['location'] = usam_get_order_metadata( $order_id, 'shippinglocation' );	
			$properties['coupon_name'] = usam_get_order_metadata( $order_id, 'coupon_name');
			
			$cart = usam_core_setup_cart();			
			$cart->set_properties( $properties );
			$cart->empty_cart(); 
			foreach ( $products as $product ) 
			{
				$parameters['unit_measure'] = $product->unit_measure;
				$parameters['quantity'] = $product->quantity;	
				$cart->add_product_basket( $product->product_id, $parameters );		
			}	
			$cart->recalculate();		
			return usam_get_basket( $cart );
		}
		return new WP_Error( 'order_id', 'Invalid order id', ['status' => 404]);
	}

	public static function get( WP_REST_Request $request ) 
	{			
		if ( current_user_can('universam_api') )
		{
			$basket_id = $request->get_param( 'basket_id' );
			if ( $basket_id )
				$cart = USAM_CART::instance( $basket_id );
			else
				$cart = usam_core_setup_cart();
		}
		else
			$cart = usam_core_setup_cart();	
		return usam_get_basket( $cart );
	}	
	
	public static function save( WP_REST_Request $request ) 
	{ 
		do_action( 'usam_api_basket_save', $request );	
		if ( current_user_can('universam_api') )
		{
			$basket_id = $request->get_param( 'basket_id' );
			if ( $basket_id )
				$cart = USAM_CART::instance( $basket_id );
			else
				$cart = usam_core_setup_cart();
		}
		else
			$cart = usam_core_setup_cart();
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
				
		$contact_id = usam_get_contact_id( true );
				
		$basket_args = [];
		if ( isset($parameters['shipping']) )
			$basket_args['selected_shipping'] = $parameters['shipping'];
		if ( isset($parameters['storage_pickup']) )
			$basket_args['storage_pickup'] = $parameters['storage_pickup'];
		if ( isset($parameters['payment']) )
			$basket_args['selected_payment'] = $parameters['payment'];	
		if ( isset($parameters['coupon']) )
			$basket_args['coupon_name'] = sanitize_text_field($parameters['coupon']);
		if ( isset($parameters['type_payer']) )
		{
			$basket_args['type_payer'] = sanitize_text_field($parameters['type_payer']);
			usam_update_contact_metadata( $contact_id, 'type_payer', $parameters['type_payer'] );		
		}
		if ( isset($parameters['bonuses']) )
		{
			if ( $parameters['bonuses'] )
			{
				$user_id = get_current_user_id();
				if ( $user_id )
				{
					$bonus_card = usam_get_bonus_card( $user_id, 'user_id' );	
					if ( !empty($bonus_card) && $bonus_card['status'] == 'active' && $bonus_card['sum'] > 0 )	
						$basket_args['bonuses'] = $bonus_card['sum'];
				}
			}
			else
				$basket_args['bonuses'] = 0;
		}	
		if ( isset($parameters['company']) )
		{
			usam_update_contact_metadata( $contact_id, 'checkout_company_id', $parameters['company'] );		
			if ( isset($parameters['type_payer']) && usam_is_type_payer_company( $parameters['type_payer'] ) )
			{			
				if ( $parameters['company'] )
				{
					$metas = usam_get_company_metas( $parameters['company'] );	
					$data = usam_get_webform_data_from_CRM( $metas );
					if ( empty($parameters['checkout']) )
						$parameters['checkout'] = [];
					$parameters['checkout'] = array_merge( $data, $parameters['checkout'] );					
				}							
			}	
		}				 
		if ( isset($parameters['checkout']) )
		{		 
			$properties = usam_get_cache_properties( 'order', true );
			$checkout_details = array();	
			if ( !empty($parameters['checkout']) )
			{
				foreach ( (array)$parameters['checkout'] as $code => $value )
				{		
					if( !empty($properties[$code]) )
					{		
						if ( !is_array($value) )
							$value = trim($value);					
						switch ( $properties[$code]->field_type ) 
						{	
							case "phone":				
								$value = preg_replace('/[^0-9]/', '', $value);						
							break;					
							case "mobile_phone":
								$value = preg_replace('/[^0-9]/', '', $value);						
								$value = preg_replace('/^89/',79, $value);
							break;
							case "location":	
								$value = (int)$value;			
								$location = usam_get_location( $value );														
								if( $location )
									$basket_args['location'] = $value;	
							break;	
							case "location_type":				
								$value = preg_replace('/[^0-9]/', '', $value);											
							break;										
							case "address":			
							case "textarea":
							case "text":	
								$value = stripslashes($value);
							break;						
						}
						$checkout_details[$code] = $value;
					}
				}
			}	
			$checkout_details = apply_filters( 'usam_update_customer_checkout_details', $checkout_details );			
			$metas = usam_get_CRM_data_from_webform( $checkout_details );	
			$contact = $metas['contact']; 
			$contact['contact_source'] = 'order';
			usam_combine_contact( $contact_id, $contact );			
			foreach( (array)$checkout_details as $code => $value )
				usam_update_customer_checkout($code, $value, $contact_id);					
		}		 
		if ( $basket_args )
			$cart->set_properties( $basket_args );	
		
		$basket_id = (int)$cart->get('id');		
		if ( isset($parameters['address']) )
			usam_update_basket_metadata( $basket_id, 'address', $parameters['address'] );
		$order_id = 0;
		if ( !empty($parameters['buy']) )
		{					
			$error_code = array();			
			if ( $cart->get_property('cart_item_count') == 0 )
				$error_code[] = 'cart_empty';
			if ( !$cart->check_shipping_method() ) 
				$error_code[] = 'shipping_method';
			USAM_Gateways::init();
			if ( !USAM_Gateways::check_gateway_method() )	
				$error_code[] = 'gateway';
			if( usam_show_user_login_form() )
				$error_code[] = 'login';	
			
			$gateway_id = $cart->get_property( 'selected_payment' );	
			$merchant_instance = usam_get_merchant_class( $gateway_id );					
			if ( !is_object($merchant_instance) )
				$error_code[] = 'gateway';				
			
			if ( empty($error_code) )
			{				
				$order_id = $cart->save_order( );
				if ( !$order_id )
					$error_code[] = 'purchase_rules';
				else
				{				
					if ( isset($parameters['checkout']) )
						usam_add_order_customerdata( $order_id, $parameters['checkout'] );	
					
					if ( !empty($parameters['address']) )
						usam_update_order_metadata( $order_id, 'address', $parameters['address'] );					
					if( get_option('usam_registration_upon_purchase') == 'automatic' && !is_user_logged_in() )
						$merchant_instance->add_new_user( $order_id );
					
					usam_add_notification(['title' => sprintf(__('Получен новый заказ №%s','usam'), $order_id)], ['object_type' => 'order', 'object_id' => $order_id]);
					$visit_id = usam_get_contact_visit_id();
					if ( $visit_id )
						usam_update_visit_metadata($visit_id, 'order_id', $order_id );
				}
			}
			if ( !$order_id )
				$results = usam_get_basket( $cart );
			$results['error_message'] = usam_get_errors_checkout( $error_code );
			$results['order_id'] = $order_id;
		
		}
		else
			$results = usam_get_basket( $cart );
		return $results;
	}
	
	public static function clear( WP_REST_Request $request ) 
	{
		if ( current_user_can('universam_api') )
		{
			$basket_id = $request->get_param( 'basket_id' );
			if ( $basket_id )
				$cart = USAM_CART::instance( $basket_id );
			else
				$cart = usam_core_setup_cart();
		}
		else
			$cart = usam_core_setup_cart();			
		$cart->empty_cart();
		return usam_get_basket( $cart );
	}
	
	public static function cross_sells( WP_REST_Request $request ) 
	{
		if ( current_user_can('universam_api') )
		{
			$basket_id = $request->get_param( 'basket_id' );
			if ( $basket_id )
				$cart = USAM_CART::instance( $basket_id );
			else
				$cart = usam_core_setup_cart();
		}
		else
			$cart = usam_core_setup_cart();		
		
		$products = $cart->get_products();
		if( !$products )
			return '';
		$ids = array();
		foreach( $products as $product )	
			$ids[] = $product->product_id;
		$query = [ 
			'update_post_meta_cache' => true, 
			'post_meta_cache' => true,			
			'product_meta_cache' => true, 	
			'update_post_term_cache' => true, 
			'cache_results' => true, 
			'prices_cache' => true, 	
			'stocks_cache' => true, 			
			'associated_product' => [['list' => 'crosssell', 'product_id' => $ids]],
			'post_status' => 'publish',
			'post_type' => 'usam-product',
			'post_parent' => 0,
			'in_stock' => true,	
			'posts_per_page' => 20,	
		];
		global $wp_query;	
		$wp_query = new WP_Query( $query );		
		update_post_thumbnail_cache( $wp_query );
		ob_start();			
		usam_include_template_file('cross_sells', 'template-parts');
		return ob_get_clean();
	}	
	
	public static function gifts( WP_REST_Request $request ) 
	{
		$ids = usam_get_discount_rules(['fields' => 'id', 'active' => 1, 'acting_now' => 1, 'orderby' => 'priority', 'order' => 'ASC', 'type_rule' => 'basket', 'meta_query' => [['key' => 'perform_action', 'value' => ['gift_choice', 'gift_one_choice'], 'compare' => 'IN']]]);	
		if ( !$ids )			
			return [];
		
		$query = [ 
			'update_post_meta_cache' => true, 
			'post_meta_cache' => true,
			'product_meta_cache' => true, 	
			'update_post_term_cache' => true, 
			'prices_cache' => true,
			'stocks_cache' => true, 	
			'cache_results' => true, 
			'productmeta_query' => [['key' => 'gift', 'value' => $ids, 'compare' => 'IN']],
			'post_status' => 'publish',
			'post_type' => 'usam-product',
			'post_parent' => 0,
			'in_stock' => true,	
			'posts_per_page' => 20,	
		];	 
		$products = usam_get_products( $query, true );	 
		foreach( $products as $k => $product )
		{					
			$terms = get_the_terms( $product->ID, 'usam-variation' );
			$variations = [];
			if ( !empty($terms) )
			{			
				foreach( $terms as $term )
				{
					if ( isset($lists_variations[$term->parent]) )
						$variations[] = ['group' => $lists_variations[$term->parent], 'name' => $term->name];
				}
			} 
			$products[$k]->variations = $variations;
			$products[$k]->small_image = usam_get_product_thumbnail_src($product->ID);
			$products[$k]->url = usam_product_url( $product->ID );
			$products[$k]->sku = usam_get_product_meta( $product->ID, 'sku' );
		}
		return $products;
	}	
	
	public static function update_product( WP_REST_Request $request ) 
	{
		if ( current_user_can('universam_api') )
		{
			$basket_id = $request->get_param( 'basket_id' );
			if ( $basket_id )
				$cart = USAM_CART::instance( $basket_id );
			else
				$cart = usam_core_setup_cart();
		}
		else
			$cart = usam_core_setup_cart();	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$quantity = isset($parameters['quantity']) ? $parameters['quantity'] : 1;
		$cart->update_quantity( $parameters['id'], $quantity, false );	
		$cart->recalculate();		
		return usam_get_basket( $cart );
	}

	public static function update_products( WP_REST_Request $request ) 
	{
		if ( current_user_can('universam_api') )
		{
			$basket_id = $request->get_param( 'basket_id' );
			if ( $basket_id )
				$cart = USAM_CART::instance( $basket_id );
			else
				$cart = usam_core_setup_cart();
		}
		else
			$cart = usam_core_setup_cart();	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		foreach ( $parameters['products'] as $product )
		{			
			$cart->update_quantity( $product['id'], $product['quantity'], false );				
		}
		$cart->recalculate();
		return usam_get_basket( $cart );
	}	
	
	public static function delete_product( WP_REST_Request $request ) 
	{
		if ( current_user_can('universam_api') )
		{
			$basket_id = $request->get_param( 'basket_id' );
			if ( $basket_id )
				$cart = USAM_CART::instance( $basket_id );
			else
				$cart = usam_core_setup_cart();
		}
		else
			$cart = usam_core_setup_cart();	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		
		$result = $cart->remove_products( $parameters['id'], true );		 
		return usam_get_basket( $cart );
	}	
	
	public static function delete_products( WP_REST_Request $request ) 
	{
		if ( current_user_can('universam_api') )
		{
			$basket_id = $request->get_param( 'basket_id' );
			if ( $basket_id )
				$cart = USAM_CART::instance( $basket_id );
			else
				$cart = usam_core_setup_cart();
		}
		else
			$cart = usam_core_setup_cart();	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		if ( !empty($parameters['items']) )
			$cart->remove_products( $parameters['items'], true );
		return usam_get_basket( $cart );
	}
	
	public static function add_product( WP_REST_Request $request )
	{
		$parameters = self::get_parameters( $request );	
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		do_action( 'usam_api_add_product_basket', $request );
		if ( current_user_can('universam_api') )
		{
			$basket_id = $request->get_param( 'basket_id' );
			if ( $basket_id )
				$cart = USAM_CART::instance( $basket_id );
			else
				$cart = usam_core_setup_cart();
		}
		else
			$cart = usam_core_setup_cart();	
								
		$product = get_post( $parameters['product_id'] );
		if ( $product->post_status != 'publish' || 'usam-product' != $product->post_type )
			return new WP_Error( 'no_product', 'Invalid product id', ['status' => 404]);		
		
		$result = false;
		$product = self::insert_product( $cart, $parameters );	
		if ( $product )
		{	
			$results = usam_get_basket( $cart );			
			$result = true;
			$product['image'] = usam_get_product_thumbnail_src($parameters['product_id'], 'product-thumbnails');
			$product['quantity_unit_measure'] = usam_get_formatted_quantity_product_unit_measure($product['quantity'], $product['unit_measure']);
			$product['price_currency'] = usam_get_formatted_price($product['price']);
			$cart_messages[] = apply_filters('usam_add_product_basket_message', str_replace("[product_name]", $product['name'], __('Вы добавили &laquo;[product_name]&raquo; в корзину.', 'usam') ), $product, $parameters);			
			$results['product'] = $product;
		}
		else 	
			$cart_messages = $cart->get_errors_message();	
		
		$results['popup'] = get_site_option("usam_popup_adding_to_cart", 'popup');
		$results['result'] = $result;		
		$results['notification'] = implode('<br>',$cart_messages);						
		return $results;
	}
	
	public static function add_products( WP_REST_Request $request )
	{
		do_action( 'usam_api_add_products_basket', $request );
		if ( current_user_can('universam_api') )
		{
			$basket_id = $request->get_param( 'basket_id' );
			if ( $basket_id )
				$cart = USAM_CART::instance( $basket_id );
			else
				$cart = usam_core_setup_cart();
		}
		else
			$cart = usam_core_setup_cart();	
		
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$ids = [];
		foreach( $parameters['items'] as $k => $item )
		{
			if ( isset($item['product_id']) )
				$ids[] = absint($item['product_id']);
		}
		if( isset($parameters['clear']) && $parameters['clear'] )
			$cart->empty_cart();
		$result = false;
		if ( $ids )
		{
			$products = usam_get_products(['post__in' => $ids]);
			if ( $products )
			{
				foreach( $products as $k => $product )
				{
					foreach( $parameters['items'] as $k => $item )
					{					
						if ( $item['product_id'] == $product->ID )
						{
							if ( self::insert_product( $cart, $item ) )
								$result = true;	
						}
					}
					
				}
			}
		}
		if ( $result )
			$results = usam_get_basket( $cart );	
		else
		{
			$cart_messages = $cart->get_errors_message( );	
			$results['notification'] = implode('<br>',$cart_messages);	
		}		
		$results['popup'] = get_site_option("usam_popup_adding_to_cart", 'popup');
		$results['result'] = $result;							
		return $results;
	}
			
	private static function insert_product( $cart, $parameters )
	{		
		$args = [];						
		$product_id = $parameters['product_id'];	
		if( !empty($parameters['variations']))
			$product_id = usam_get_id_product_variation( $product_id, $parameters['variations'] );		
		if ( !empty($parameters['quantity']) )		
			$args['quantity'] = $parameters['quantity'];
		if ( !empty($parameters['gift']) )		
			$args['gift'] = $parameters['gift'];			
		$product_id = usam_get_post_id_main_site( $product_id );	
		if ( !empty($parameters['unit_measure']) )		
		{
			$args['unit_measure'] = $parameters['unit_measure'];	
			$unit = usam_get_unit_measure( $args['unit_measure']);	
			if ( !$unit )
				$args['unit_measure'] = usam_get_product_property($product_id, 'unit_measure_code');
		}	
		else
			$args['unit_measure'] = usam_get_product_property($product_id, 'unit_measure_code');
		if ( $cart->add_product_basket( $product_id, $args ) ) 	
		{
			$cart->recalculate();
			return $cart->get_product( $product_id, $args['unit_measure'] );
		}
		return false;			
	}
	
	public static function get_delivery_service_options( $request )
	{		
		$handler = $request->get_param( 'handler' );
		$shipping_class = 'USAM_Shipping_'.$handler;	
		$data = ['selfpickup' => 0];
		if ( !class_exists($shipping_class) )
		{
			$file =  USAM_APPLICATION_PATH . "/shipping/{$handler}.php";
			if( file_exists( $file ) )	
			{
				require_once( $file );	
				$data = get_file_data( $file, ['selfpickup' => 'SELFPICKUP']);	
				$data['selfpickup'] = isset($data['selfpickup']) && $data['selfpickup'] == 'Да';				
			}
			else
				$shipping_class = 'USAM_Shipping';
		}
		else
			$shipping_class = 'USAM_Shipping';
		$shipping_instance = new $shipping_class( 0 );
		$data['options'] = $shipping_instance->get_options(); 
		return $data;
	}		
	
	public static function delete_delivery( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		return usam_delete_delivery_service( $id );
	}
	
	public static function get_delivery( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = self::get_parameters( $request );		
		$data = usam_get_delivery_service( $id );		
		return $data;
	}	
	
	public static function insert_delivery( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$id = usam_insert_delivery_service( $parameters );
		if ( $id )
			self::update_delivery_metadata( $id, $parameters );
		return $id;
	}

	public static function update_delivery( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$result = usam_update_delivery_service( $id, $parameters );
		self::update_delivery_metadata( $id, $parameters );
		return true;
	}	
	
	private static function update_delivery_metadata( $id, $parameters ) 
	{	
		$merchant_instance = usam_get_shipping_class( $id );
		$options = $merchant_instance->get_options();		
		foreach( $options as $option )
			if( isset($parameters[$option['code']]) )
				usam_update_delivery_service_metadata( $id, $option['code'], $parameters[$option['code']] );	

		$metas = [['storage_owner' => '']];
		$delivery = usam_get_delivery_service( $id );		
		if ( !empty($delivery['handler']) )
		{
			$file =  USAM_APPLICATION_PATH . "/shipping/{$delivery['handler']}.php";
			$data = get_file_data( $file, ['points' => 'Points']);	
			if ( !empty($data['points']) && $data['points'] == 'Да' )
				$metas['storage_owner'] = $delivery['handler'];
		}			
		$metas['margin'] = isset($parameters['margin'])?(float)$parameters['margin']:0;
		$metas['margin_type'] = isset($parameters['margin_type'])?sanitize_text_field($parameters['margin_type']):'';					
		if ( isset($parameters['price_from']) )
			$metas['price_from'] = (float)$parameters['price_from'];							
		if ( isset($parameters['products_from']) )
			$metas['products_from'] = (float)$parameters['products_from'];						
		if ( isset($parameters['products_to']) )
			$metas['products_to'] = (float)$parameters['products_to'];						
		if ( isset($parameters['price_to']) )
			$metas['price_to'] = (float)$parameters['price_to'];
		if ( isset($parameters['weight_from']) )
			$metas['weight_from'] = (float)$parameters['weight_from'];						
		if ( isset($parameters['weight_to']) )
			$metas['weight_to'] = (float)$parameters['weight_to'];							
		foreach( $metas as $meta_key => $meta_value)
			usam_update_delivery_service_metadata($id, sanitize_text_field($meta_key), $meta_value);
		if ( !empty($parameters['roles']) )
		{
			$roles = array_map('sanitize_text_field', $parameters['roles']);
			usam_save_meta( $id, 'delivery_service', 'roles', $roles );
		}
		else
			usam_delete_delivery_service_metadata($id, 'roles');	
		if ( !empty($parameters['locations']) )
		{
			$locations = array_map('intval', $parameters['locations']);
			usam_save_meta( $id, 'delivery_service', 'locations', $locations );
		}
		else
			usam_delete_delivery_service_metadata($id, 'locations');			
		if ( !empty($parameters['types_payers']) )
			usam_save_meta( $id, 'delivery_service', 'type_payer', array_map('intval', $parameters['types_payers']) );
		else
			usam_delete_delivery_service_metadata($id, 'type_payer');	
		
		
		$merchant_instance->load_data();
	}
}
?>