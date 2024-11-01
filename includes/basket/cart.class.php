<?php
// Класс корзины товаров

//Функция подготавливает данные корзины для получение по API, так же используется для первоначальной загрузке в переменную script
function usam_get_basket( $cart ) 
{
	if ( is_numeric($cart) )
		$cart = USAM_CART::instance( $cart );
	
	$basket_id = $cart->get_property( 'id' );
	$products = $cart->get_products();
	
	$type_payer = usam_get_type_payer_customer( );			
	$user = wp_get_current_user();
	$roles = empty($user->roles)?['notloggedin']:$user->roles;
	
	$meta_query = [];				
	$shipping = $cart->get_property('selected_shipping');	
	$meta_query[] = ['relation' => 'OR',['key' => 'shipping', 'compare' => '=', 'value' => $shipping],['key' => 'shipping', 'compare' => 'NOT EXISTS']];
	$shipping_method = $cart->get_shipping_method();	
	if ( isset($shipping_method->delivery_option) )
		$meta_query[] = ['relation' => 'OR',['key' => 'delivery_option', 'compare' => '=', 'value' => $shipping_method->delivery_option],['key' => 'delivery_option', 'compare' => 'NOT EXISTS']];
	else
		$meta_query[] = [['key' => 'delivery_option', 'compare' => 'NOT EXISTS']];
	
	$select_products = get_option('usam_types_products_sold', ['product', 'services']);
	if ( is_array($select_products) && count($select_products) > 1 )
	{
		$types = get_option('usam_types_products_sold', ['product', 'services']);
		if ( count($types) > 1 )
		{				
			$types = [];
			foreach ( $products as $product )
			{
				$types[] = usam_get_product_type_sold( $product->product_id );
			}
			$meta_query[] = ['relation' => 'OR',['key' => 'types_products', 'compare' => 'IN', 'value' => $types],['key' => 'types_products', 'compare' => 'NOT EXISTS']];
		}
	}		
	$category_ids = usam_cart_categories_ids( );
	$meta_query[] = ['relation' => 'OR',['key' => 'category', 'compare' => 'IN', 'value' => $category_ids], ['key' => 'category', 'compare' => 'NOT EXISTS']];			
	$order_properties = usam_get_properties(['type' => 'order', 'access' => true, 'type_payer' => $type_payer, 'orderby' => ['group', 'sort'], 'cache_results' => true, 'cache_group' => true, 'meta_query' => $meta_query]);		 
	$code = [];	
	$properties = [];	
	foreach( $order_properties as $property )
	{
		$code[] = $property->group;
		
		$default = usam_get_default_customer_order_data( $property );
		$checkout = usam_get_customer_checkout( $property->code );
	
		$property->value = $checkout=== false? $default : $checkout;
		$properties[$property->code] = usam_format_property_api( $property );
	} 
	if ( usam_is_type_payer_company($type_payer) )
	{
		$select_company = usam_get_select_company_customer();			
		if ( !empty($select_company) )
		{					
			$metas = usam_get_company_metas( $select_company );				
			$metas = usam_get_webform_data_from_CRM( $metas );			
			foreach ( $properties as $key => $property )	
			{								
				if ( !empty($metas[$property->code]) )
					unset($properties[$key]);
			}					
		}			
	}		 
	$groups = usam_get_property_groups(['code' => $code]);	
	$allowed_spend_bonuses = $cart->allowed_spend_bonuses();
	$storage_pickup = $cart->get_property('storage_pickup');		
	
	$shipping_methods = [];		
	foreach ( $cart->get_shipping_methods() as $method )
	{			
		if ( $method->price > 0 )
			$method->info_price = sprintf( __('Стоимость: %s', 'usam'), "<span class='gateways_form__option_value'>".usam_get_formatted_price($method->price)."</span>" );		
		elseif ( $method->handler === 0 )
			$method->info_price = __('Бесплатная доставка', 'usam');
		else
			$method->info_price = '';
	
		if( $method->delivery_option )
		{
			$method->storages = [];
			if ( $cart->get_property('selected_shipping') == $method->id )
			{
				$location_ids = $cart->get_property( 'location_ids' );
				$location_ids[] = 0;
				$method->storages = usam_get_storages(['fields' => 'id', 'issuing' => 1, 'location_id' => $location_ids, 'owner' => $method->storage_owner, 'number' => 2]);
				if ( $storage_pickup )
					$method->delivery_period = usam_get_storage_delivery_period( $storage_pickup );
			}	
		}			
		if ( empty($method->delivery_period) )				
			$method->delivery_period = usam_get_delivery_period($method->period_from, $method->period_to, $method->period_type);
		$shipping_methods[] = $method;
	}
	$thumb_ids = array();	
	foreach( $shipping_methods as $shipping_method )	
	{ 
		if ( $shipping_method->img && is_numeric($shipping_method->img) )
			$thumb_ids[] = $shipping_method->img;
	}	
	_prime_post_caches( $thumb_ids, false, true );
	foreach( $shipping_methods as $shipping_method )	
	{ 
		$shipping_method->image = '';
		if ( !empty($shipping_method->img) )
		{
			if ( is_numeric($shipping_method->img) )
				$shipping_method->image = wp_get_attachment_image_url( $shipping_method->img, 'thumbnail' );
		}	
	}	
	$gateways = new USAM_Gateways();
	$payment_methods = $gateways->get_gateways( );			
	
	$lists_variations = get_terms(['fields' => 'id=>name', 'hide_empty' => 0, 'orderby' => 'sort', 'taxonomy' => 'usam-variation']);
	$products = array_values($products); 	
		
	$thumb_ids = [];	
	foreach( $products as $product )	
	{ 
		if ( $id = get_post_thumbnail_id( $product->product_id ) )
			$thumb_ids[] = $id;
	}				
	_prime_post_caches( $thumb_ids, false, true );		
	$ids = array();
	$products_discount = 0;
	foreach( $products as $k => $product )
	{
	//	$product_id_multisite = usam_get_post_id_multisite( $product->product_id );	
		$license_agreement = usam_get_product_meta( $product->product_id, 'license_agreement' );
		if ( $license_agreement )
			$ids[] = $license_agreement;
		
		$terms = get_the_terms( $product->product_id, 'usam-variation' );
		$variations = [];
		if ( !empty($terms) )
		{			
			foreach( $terms as $term )
			{
				if ( isset($lists_variations[$term->parent]) )
					$variations[] = ['group' => $lists_variations[$term->parent], 'name' => $term->name];
			}
		} 
		$unit_measure = usam_get_product_property($product->product_id, 'unit_measure_code');
		$discount = $product->old_price?$product->old_price - $product->price:0;
		$products_discount += $discount*$product->quantity;			
		$products[$k]->price = ['currency' => usam_get_formatted_price($product->price), 'value' => $product->price]; 
		$products[$k]->old_price = ['currency' => usam_get_formatted_price($product->old_price), 'value' => $product->old_price];
		$products[$k]->discount = ['currency' => usam_get_formatted_price($discount), 'value' => $discount];
		$products[$k]->total = ['currency' => usam_get_formatted_price($product->total), 'value' => $product->total];
		$products[$k]->total_before_discount = ['currency' => usam_get_formatted_price($product->total_before_discount), 'value' => $product->total_before_discount];
		$products[$k]->variations = $variations;
		$products[$k]->quantity = usam_get_formatted_quantity_product($product->quantity, $product->unit_measure);
		$products[$k]->small_image = usam_get_product_thumbnail_src($product->product_id, 'small-product-thumbnail');	
		$products[$k]->quantity_unit_measure = usam_get_formatted_quantity_product_unit_measure( $product->quantity, $product->unit_measure );
		$products[$k]->additional_units = usam_get_product_property( $product->product_id, 'additional_units' );
		$products[$k]->unit = usam_get_product_unit( $product->product_id, $product->unit_measure );
		$products[$k]->step_quantity = $product->unit_measure == $unit_measure ? $products[$k]->unit : 1;
		$products[$k]->stock = usam_product_remaining_stock( $product->product_id );
		$products[$k]->url = usam_product_url( $product->product_id );
		$products[$k]->sku = usam_get_product_meta( $product->product_id, 'sku' );		
		if ( usam_chek_user_product_list('desired') ) 
			$products[$k]->desired = usam_checks_product_from_customer_list( 'desired', $product->product_id ); 
		if ( usam_chek_user_product_list('compare') ) 
			$products[$k]->compare = usam_checks_product_from_customer_list( 'compare', $product->product_id ); 
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
		{
			$products[$k]->seller = usam_get_seller_data( $product->seller_id );
		}	
	}	 
	if ( !empty($ids) )		
		$agreements = (array)usam_get_posts(['post_type' => 'usam-agreement',  'post__in' => $ids, 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC']);		
	else
		$agreements = [];
	$count = count($products);
	$number_items = usam_get_number_products_basket();
	$shipping = $cart->get_property( 'shipping' );		
	$total = $cart->get_property( 'total_price' );	
	$basket = [
		'id' => $basket_id,		
		'allowed_spend_bonuses' => $allowed_spend_bonuses,
		'agreements' => $agreements,
		'products' => $products,
		'number_products' => $count,
		'number_goods_message' => sprintf( _n('%d товар', '%d товаров', $count, 'usam'), $count),
		'number_items' => $number_items,
		'number_items_message' => sprintf( _n('%d товар', '%d товаров', $number_items, 'usam'), $number_items),
	//	'coupon' => ['value' => ''],
		'shipping_methods' => $shipping_methods,
		'selected_storage_address' => usam_cart_get_select_storage_address(),			
		'payment_methods' => $payment_methods,				
		'subtotal' => ['currency' => usam_get_basket_subtotal(false), 'value' => usam_get_basket_subtotal( false, false )],
		'discount' => ['currency' => usam_get_formatted_price( $products_discount ), 'value' => $products_discount],
		'shipping' => ['currency' => usam_get_formatted_price( $shipping ), 'value' => $shipping],
		'taxes' => usam_get_cart_taxes(),
		'virtual_products' => usam_virtual_products_in_basket(),			
		'amount_no_delivery' => ['currency' => usam_get_basket_subtotal( true ), 'value' => usam_get_basket_subtotal( true, false )],
		'total' => ['currency' => usam_get_formatted_price( $total ), 'value' => $total],
		'cost_paid' => ['currency' => usam_cart_cost_paid(), 'value' => usam_cart_cost_paid( false )],
		'cost_unpaid' => ['currency' => usam_unpaid_cart_amount(), 'value' => usam_unpaid_cart_amount( false )],			
		'uses_coupons' => (bool)get_site_option( 'usam_uses_coupons', 1 ),
		'uses_bonuses' =>(bool) get_site_option( 'usam_uses_bonuses', 1 ),	
		'errors' => $cart->get_errors_message( true )			
	];
	$selected = [
		'bonuses' => $cart->get_property( 'bonuses' ),
		'location' => (int)$cart->get_property('location'),
		'shipping' => (int)$cart->get_property('selected_shipping'),			
		'payment' => (int)$cart->get_property('selected_payment'),	
		'storage_pickup' => (int)$cart->get_property('storage_pickup'),
		'company' => usam_get_select_company_customer(),
		'type_payer' => $type_payer,
		'address' => (int)usam_get_basket_metadata( $basket_id, 'address' ),			
		'coupon' => $cart->get_property('coupon_name')
	];
	$location_id = usam_get_customer_location();
	$customer = [
		'bonuses' => usam_get_available_user_bonuses(),
		'user_logged' => is_user_logged_in(),
		'location' => usam_get_location( $location_id ),		
	];			
	return apply_filters('usam_api_basket', ['groups' => $groups, 'properties' => $properties, 'basket' => $basket, 'selected' => $selected, 'customer' => $customer]);
}


function usam_cart_errors_message() 
{
	$cart = USAM_CART::instance();
	return $cart->get_errors_message( true );
}

function usam_cart_get_select_storage_address()
{
	$cart = USAM_CART::instance();		
	$storage_pickup = $cart->get_property( 'storage_pickup' );
	$method = $cart->get_shipping_method();
		
	$title = '';
	$storage = usam_get_storage($cart->get_property( 'storage_pickup' ));	
	if ( !empty($method) && !empty($storage_pickup) && !empty($storage) && $storage['owner'] == $method->handler )
	{ 	
		$address = usam_get_storage_metadata( $storage['id'], 'address');	
		if ( $address )
		{
			$location = usam_get_location( $storage['location_id'] );			
			$city = isset($location['name'])?htmlspecialchars($location['name']).", ":'';
			$title = $city.$address;	
		}
		elseif ( isset($storage['title']) )
			$title = $storage['title'];
	}	
	return $title;
}

function usam_get_number_products_basket()
{
	$cart = USAM_CART::instance();	
	return $cart->get_number_products();
}

function usam_get_number_sku_basket()
{
	$cart = USAM_CART::instance();	
	return count($cart->get_products());
}

function usam_get_product_basket( $product_id )
{	
	$cart = USAM_CART::instance();	
	$products = $cart->get_products();
	$product_id = usam_get_post_id_multisite( $product_id );
	foreach( $products as $key => $product )
	{
		if ( $product->product_id == $product_id )
		{
			return (array)$product;
		}
	}
	return [];
}

/**
 * Вернуть полную стоимость корзины
 */
function usam_cart_total( $forDisplay = true ) 
{
	$cart = USAM_CART::instance();
	$total = $cart->get_property( 'total_price' );
    if( $forDisplay )
        return usam_get_formatted_price( $total );
    else
        return $total;
}

function usam_get_basket_subtotal( $discount = true, $forDisplay = true )
{
	$cart = USAM_CART::instance();	
	
	$subtotal = $cart->get_property( 'subtotal' );
	if( $discount == false )
	{
		$products_discount = $cart->get_property( 'products_discount' );		
		$subtotal += $products_discount;
	}		
	if( $forDisplay == true )
		$subtotal = usam_get_formatted_price( $subtotal );
	return $subtotal;
}

function usam_cart_cost_paid( $currency = true ) 
{
	$cart = USAM_CART::instance();
	$paid = $cart->get_cost_paid( );
	if ( $currency )
		$paid = usam_get_formatted_price( $paid );  
	return $paid;
}

function usam_unpaid_cart_amount( $currency = true ) 
{
	$cart = USAM_CART::instance();
	$totalprice = $cart->get_property( 'total_price' );
	$totalprice -= $cart->get_cost_paid( );
	if ( $currency )
		$totalprice = usam_get_formatted_price( $totalprice );  
	return $totalprice;
}

function usam_get_cart_taxes( $shipping = true )
{
	$cart = USAM_CART::instance();	
	$product_taxes = $cart->get_product_taxes( );  
	$products = $cart->get_products();
		
	$results = [];
	foreach ( $product_taxes as $product_tax ) 
	{				
		$quantity = 0;
		foreach ( $products as $key => $product ) 
		{	
			if ($product->product_id == $product_tax->product_id && $product->unit_measure == $product_tax->unit_measure)
			{
				$quantity = $product->quantity;
				break;
			}
		}
		$tax = $quantity * $product_tax->tax;	
		if ( isset($results[$product_tax->tax_id]) )
			$results[$product_tax->tax_id]['tax'] += $tax;
		else
			$results[$product_tax->tax_id] = ['name' => $product_tax->name, 'tax' => $tax];
	}		
	if ( $shipping )
	{
		$selected_shipping = $cart->get_shipping_method(); 
		if ( empty($selected_shipping->include_in_cost) ) 
		{
			if ( !empty($selected_shipping->tax_value) )
			{ 
				if ( isset($results[$selected_shipping->tax_id]) )
					$results[$selected_shipping->tax_id]['tax'] += $selected_shipping->tax_value;
				else
					$results[$selected_shipping->tax_id] = ['name' => $selected_shipping->tax_name, 'tax' => $selected_shipping->tax_value];	
			}
		}
	} 
	foreach ( $results as $key => $result ) 
	{ 
		$results[$key]['tax'] = usam_currency_display($result['tax'], ['decimal_point' => true] );
	}		
	return $results;
}

/**
* Получить стоимость доставки
*/
function usam_cart_shipping( $forDisplay = true ) 
{
	$cart = USAM_CART::instance();
	$total = $cart->get_property( 'shipping' );
    if( $forDisplay )
        $total = usam_get_formatted_price( $total );	
   return apply_filters( 'usam_cart_shipping', $total );
}

/**
* Узнать количество категорий добавленных товаров в корзину
*/
function usam_cart_categories_ids( ) 
{
	$cart = USAM_CART::instance();
	return $cart->get_cart_terms_ids();
}

function usam_selected_location()
{
	$cart = USAM_CART::instance();	
	if ( $cart->get_property('location') )
		return true;
	else
		return false;
}	

function usam_virtual_products_in_basket()
{
	$cart = USAM_CART::instance();	
	return $cart->check_virtual_products();	
}

function usam_core_setup_cart( $create_contact = true )
{ 
	$contact_id = usam_get_contact_id();
	if( !$contact_id )
	{
		if( $create_contact )
			$contact_id = usam_save_or_create_contact();	
		else
			return false;
	}
	$user_id = get_current_user_id();	
	$location = usam_get_customer_location();	
	$basket_parameters = ['location' => $location, 'contact_id' => $contact_id, 'user_id' => $user_id];		
	if ( $contact_id || $user_id )
	{
		require_once( USAM_FILE_PATH . '/includes/basket/users_basket_query.class.php' );		
		if ( is_user_logged_in() )
		{		
			$basket = usam_get_users_baskets(['user_id' => $user_id, 'cache_results' => true, 'cache_meta' => true, 'number' => 1]);
			if ( !empty($basket) ) 			
			{
				if ( $basket['contact_id'] != $contact_id )
				{ // Если у зарегистрированного пользователя есть корзина						
					$basket = usam_get_users_baskets(['contact_id' => $contact_id, 'cache_results' => true, 'cache_meta' => true, 'number' => 1]);	
					if ( $basket && $basket['user_id'] == 0 )
					{				
						require_once( USAM_FILE_PATH . '/includes/basket/products_basket_query.class.php'   );
						$products_baskets = usam_get_products_baskets(['cart_id' => $basket['id']]);								
						usam_delete_cart(['include' => [ $basket['id'] ]]);			
					}
				}	
			}
		}	
		if ( empty($basket) ) 		
			$basket = usam_get_users_baskets(['contact_id' => $contact_id, 'cache_results' => true, 'cache_meta' => true, 'number' => 1]);	
		
		$coupon_code = usam_get_contact_metadata($contact_id, 'coupon_code' );
		if ( $coupon_code )
		{
			$coupon = usam_get_coupon($coupon_code, 'coupon_code');
			if ( empty($coupon['active']) || $coupon['end_date'] == '0000-00-00 00:00:00' || !empty($coupon['end_date']) && strtotime($coupon['end_date']) < time() )
				usam_delete_contact_metadata($contact_id, 'coupon_code' );
			else
				$basket_parameters['coupon_name'] = $coupon_code;	
		} 
	}		 
	$basket_id = !empty($basket['id'])?$basket['id']:0;		
	$cart = USAM_CART::instance( $basket_id, $basket_parameters );
	if ( !empty($products_baskets) )
	{
		foreach( $products_baskets as $product )
			$result = $cart->add_product_basket( $product->product_id, ['quantity' => $product->quantity, 'unit_measure' => $product->unit_measure]);
		$cart->recalculate();
	}
	return $cart;
}

/**
 * Класс корзины
 */
class USAM_CART
{	
	private static $string_cols = array(	
		'date_insert',	
		'recalculation_date',			
		'coupon_name',	
	);
	private static $int_cols = array(
		'id',		
		'user_id',	
		'contact_id',			
		'shipping_method',		
		'gateway_method',	
		'storage_pickup',
		'bonuses',
	);		
	protected static $_instance = null;
	private $location = null;	
	private $location_ids = array();
	
	private $order_id = 0;
	private $recalculate_order = false;	
	private $id = null;	
	private $user_id = 0;	
	private $contact_id = 0;		
	
	private $type_payer = 0;
	private $storage_pickup = 0; // Склад самовывоза	
	
	private $shipping = 0;	
	private $total_price = 0;
	private $accumulative_discount = null;
	private $bonuses = 0;
	private $products_discount = 0;	
	private $subtotal = null;	
	private $paid = null;		
	private $cart_item_count = 0;	

	private $type_price;
	public  $product;
	private $products      = array();
	private $products_keys = array();	
	private $number_products = array();		

	private $current_cart_item = -1;
	private $in_the_loop = false;	
	
	private $selected_shipping = 0; 
	private $selected_payment = 0;
	
	private $shipping_methods = null;

	public  $coupon_name = '';		
	private $errors = [];	
	
	private $recalculation_date = false;

	public function __construct( $cart_id = null, $args = [] ) 
	{		
		$this->type_price = usam_get_customer_price_code();
		$this->user_id = get_current_user_id();		
		$this->type_payer = usam_get_type_payer_customer();		
		$this->contact_id = usam_get_contact_id();	
		if ( is_array( $cart_id) )
		{
			$args = $cart_id;		
			$cart_id = null;
		}
		$this->save_properties( $args );
		if ( !empty($cart_id)  )
		{ 
			$this->id = (int)$cart_id;			
			$data = $this->get_cart( );
			if ( empty($data) )						
			{							
				$this->insert_cart();	
				$this->update_location();				
			}	
			else
			{	
				$this->selected_shipping = (int)$data['shipping_method'];
				$this->storage_pickup = (int)$data['storage_pickup'];				
				$this->selected_payment = (int)$data['gateway_method'];
				$this->coupon_name = $this->coupon_name?$this->coupon_name:mb_strtoupper($data['coupon_name']);
				$this->order_id = (int)$data['order_id'];			
				$this->total_price = (float)$data['totalprice'];
				$this->number_products = (int)$data['quantity'];				
				$this->recalculation_date = $data['recalculation_date'];
				$this->bonuses = (int)$data['bonuses'];
				$this->user_id = isset($args['user_id'])?$args['user_id']:$data['user_id'];
				$this->contact_id = isset($args['contact_id'])?$args['contact_id']:$data['contact_id'];
				$this->shipping = (float)$data['shipping'];	
								
				$this->update_location();
				if ( $this->number_products )
					$this->set_products();			
			}			
		}
	}	
	
	public static function instance( $cart_id = null, $args = [] ) 
	{ 
		if ( is_null( self::$_instance ) )
			self::$_instance = new self( $cart_id, $args ); 
		return self::$_instance;
	}		
	
//  Проверить необходимость перерасчета	
	public function check_need_recalculation( )
	{ 
		if ( $this->recalculation_date == null || strtotime($this->recalculation_date) < strtotime('-1 hours') )
		{	
			$this->recalculation_date = null;	
			return true;
		}
		return false;
	}	
	
	public function recalculate( )
	{
		$this->recalculation_date = null;	
		
		usam_update_basket_metadata( $this->id, 'shipping_cost', [] );
		if ( !$this->recalculate_order ) 
			$this->order_id = 0;	
		$this->calculate_total_price();	
	}	
	
	/*
    * Загрузить корзину
    */
	public function get_cart( )
	{
		global $wpdb;		
		if ( $this->id )
		{
			$cache_key = 'usam_users_basket';
			if( ! $cache = wp_cache_get( $this->id, $cache_key ) )
			{		
				$cache = $wpdb->get_row( "SELECT * FROM ".USAM_TABLE_USERS_BASKET." WHERE id ='$this->id'", ARRAY_A );
				if ( $cache )
				{
					foreach ( $cache as $key => &$value ) 
					{
						$format = $this->get_column_format( $key );
						if ( $format == '%d' )
							$value = (int)$value;
						elseif( $format == '%f' )
							$value = (float)$value;
					}
				}
				else
					$cache = [];
				wp_cache_set( $this->id, $cache, $cache_key );
			}
		}
		else
			$cache = [];		
		return $cache;
	}	
	
	public function get( $key )
	{
		$data = $this->get_cart();
		if ( isset($data[$key]) )
			return $data[$key];
		else
			return false;
	}
			
	public function update( )
	{
		global $wpdb;

		if ( !$this->id )
			return false;
		
		do_action( 'usam_cart_pre_update', $this );			
		
		$result = false;	
		
		$where_col = 'id';		
		
		$data = $this->get_cart();
		$products = $this->get_products();
		$newdata['user_id'] = $this->user_id;	
		$newdata['contact_id'] = $this->contact_id;		
		$newdata['quantity'] = count($products);	
		$newdata['totalprice'] = $this->total_price;	
		$newdata['shipping'] = (float)$this->shipping;	
		$newdata['shipping_method'] = (int)$this->selected_shipping;
		$newdata['storage_pickup'] = (int)$this->storage_pickup;		
		$newdata['gateway_method'] = (int)$this->selected_payment;	
		$newdata['coupon_name'] = $this->coupon_name;		
		$newdata['order_id'] = (int)$this->order_id;		
		$newdata['bonuses'] = (int)$this->bonuses;			
		$newdata['recalculation_date'] = $this->recalculation_date == null?date( "Y-m-d H:i:s" ):$this->recalculation_date;	
	
		$result = false;		
		if ( $data )
		{
			foreach ( $newdata as $key => $value ) 
			{
				if ( $value != $data[$key] )
				{ 
					$result = true;
					break;
				}
			}
		}		
		if ( $result )
		{ 				
			$newdata = apply_filters( 'usam_cart_update_data', $newdata );	
			$format = $this->get_data_format( $newdata );	

			$where[$where_col] = $this->id;		
			$where_format = $this->get_data_format( $where );				
			$result = $wpdb->update( USAM_TABLE_USERS_BASKET, $newdata, $where, $format, $where_format );
			if ( $result ) 
			{
				wp_cache_delete( $this->id, 'usam_users_basket' );		
			}		
		}
		do_action( 'usam_cart_update', $this );				
		return $result;
	}	
	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';

		return '%f';
	}	
	
	private function get_data_format( $data ) 
	{
		$format = array();
		foreach ( $data as $key => $value ) 
		{			
			$format[$key] = self::get_column_format( $key );
		}
		return $format;
	}
	
	// создание корзины	
	public function insert_cart()
	{
		global $wpdb;

		do_action( 'usam_cart_pre_insert' );
	
		$this->empty_cart();
		
		$result = false;		
		 
		$products = $this->get_products();
		$data['order_id'] = (int)$this->order_id;		
		$data['bonuses'] = (int)$this->bonuses;	
		$data['quantity'] = count($products);	
		$data['totalprice'] = (float)$this->total_price;
		$data['user_id']       = $this->user_id;
		$data['contact_id']    = $this->contact_id;
		$data['date_insert']   = date( "Y-m-d H:i:s" );		
		$data['shipping_method'] =(int)$this->selected_shipping;
		$data['gateway_method'] = (int)$this->selected_payment;
		$data['storage_pickup'] = (int)$this->storage_pickup;	
		$data['shipping'] = (float)$this->shipping;	
		$data['coupon_name'] = $this->coupon_name;
		$data['recalculation_date'] = null;
		
		$data = apply_filters( 'usam_cart_insert_data', $data );
		$format = $this->get_data_format( $data );		
		$result = $wpdb->insert( USAM_TABLE_USERS_BASKET, $data, $format );
		if ( $result ) 
		{
			$this->id = $wpdb->insert_id;				
		} 	
		wp_cache_set( $this->id, $data, 'usam_users_basket' );			
		do_action( 'usam_cart_insert', $this );
		return $result;
	}	
	
	public function get_property( $property ) 
	{ 
		$return = null;
		if ( property_exists($this, $property) )
			$return = $this->$property;		
		return $return;
	}
	
	private function save_properties( $properties ) 
	{		
		if ( !empty($properties) && is_array($properties) )
		{	
			foreach( $properties as $key => $value )
			{	
				if ( property_exists($this, $key) && ( is_array($this->$key) || $this->$key != $value ) )
				{				
					$this->recalculation_date = null;		
					switch( $key ) 
					{																
						case 'coupon_name':								
							$this->$key = mb_strtoupper($value);	
						break;					
						case 'storage_pickup':													
						case 'selected_shipping':	
						case 'user_id':
						case 'contact_id':
						case 'order_id':						
						case 'selected_payment':										
						case 'type_payer':		
						case 'accumulative_discount':	
						case 'location':	
						case 'bonuses':	
							$this->$key = absint($value);											
						break;											
						case 'type_price':						
							$this->$key = $value;								
						break;											
					}	
				}	
				else
					unset($properties[$key]);
			}	
			return $properties;	
		}		
		return [];	
    }
		
	public function set_properties( $properties ) 
    {		
		$properties = $this->save_properties( $properties );			
		if ( empty($properties) )
			return false;
				
		$update_location = false;
		$update_shipping = false;	
		$update_bonuses = false;		
		foreach( $properties as $key => $value )
		{				
			switch( $key ) 
			{																
				case 'location':								
					$update_location = true;	
				break;	
				case 'selected_shipping':				
				case 'storage_pickup':						
					$update_shipping = true;	
				break;					
			}	
		}
		if ( $update_location )
			$this->update_location( );	
		if ( $update_shipping )
			$this->update_shipping( );
		$this->recalculate();	
		return true;
    }

	// Обновить регион доставки
	private function update_location( ) 
	{			
		if ( !$this->location )
			$this->location = usam_get_customer_location();	 
		$this->location_ids = array_values(usam_get_address_locations( $this->location, 'id' ));			
		$this->shipping_methods = null;	
	}
	
	function get_shipping_methods() 
	{ 			
		if ( $this->shipping_methods === null )
			$this->set_shipping_methods(); 
		return $this->shipping_methods;
	}	
	    
    // Получить доступные методы доставки
	public function set_shipping_methods( $update_price = false ) 
	{		
		$this->shipping_methods = array();	
		$products = $this->get_products();
		if ( empty($products) )
			return true;
		if ( $this->check_virtual_products() )		
		{	
			$this->selected_shipping = 0;	
			return true;
		}
		$weight = $this->calculate_total_weight();			
		$number = $this->get_number_products();
	
		$restrictions = ['price' => $this->subtotal, 'number_products' => $number, 'weight' => $weight, 'locations' => $this->location_ids, 'type_payer' => $this->type_payer];	
		if ( $this->user_id == 0 ) 
			$restrictions['roles'] = ['notloggedin'];
		else
		{
			$user = get_userdata( $this->user_id );					
			$restrictions['roles'] = $user->roles;
		}				
		require_once( USAM_FILE_PATH . '/includes/basket/calculate_delivery_service.class.php' );
		$ds = new USAM_Calculate_Delivery_Services( $restrictions );										
		$shipping_methods = $ds->get_delivery_services();
			
		$selected_shipping = 0;		
		$this->shipping_methods = [];
		
		if ( $update_price )
			$shipping_cost = [];
		else
		{
			$shipping_cost = usam_get_basket_metadata( $this->id, 'shipping_cost' );	
			if ( empty($shipping_cost) )
				$shipping_cost = [];	
		}			
		foreach( $shipping_methods as $key => $method )
		{						
			if ( $this->check_need_recalculation() || !isset($shipping_cost[$method->id]['price']) ) 
				$shipping_cost[$method->id] = $this->get_shipping_cost( $method );
			if ( isset($shipping_cost[$method->id]['price']) && $shipping_cost[$method->id]['price'] !== false )
			{
				$shipping_methods[$key]->price = $shipping_cost[$method->id]['price'];
				$shipping_methods[$key]->storage_pickup = (int)$shipping_cost[$method->id]['storage_pickup'];
				if ( !empty($shipping_cost[$method->id]['name']) )
					$shipping_methods[$key]->name = $shipping_cost[$method->id]['name'];				
				if ( !empty($shipping_cost[$method->id]['description']) )
					$shipping_methods[$key]->name = $shipping_cost[$method->id]['description'];
				if ( !empty($shipping_cost[$method->id]['logo']) )
					$shipping_methods[$key]->img = $shipping_cost[$method->id]['logo'];
				$tax = usam_get_tax( $method->tax_id );
				if ( !empty($tax) )
				{ 
					$shipping_methods[$key]->tax_name = $tax['name'];
					$shipping_methods[$key]->tax_is_in_price = $tax['is_in_price'];
					$shipping_methods[$key]->tax_rate = $tax['value'];
					$shipping_methods[$key]->tax_value = 0;	
					if ( $tax['is_in_price']->tax_is_in_price )					
						$shipping_methods[$key]->tax_value = $shipping_methods[$key]->price*$tax['tax_rate']/(100+$tax['tax_rate']);						
					else
					{ 
						$shipping_methods[$key]->tax_value = $shipping_methods[$key]->price*$tax['tax_rate']/100;			
						$shipping_methods[$key]->price += $shipping_methods[$key]->tax_value;	
					}		
				}					
				if ( $this->selected_shipping == $method->id )
					$selected_shipping = $method;
				
				$shipping_methods[$key]->delivery_option = (int)$method->delivery_option;				
				$this->shipping_methods[] = $shipping_methods[$key];
			}			
		}	
		usam_update_basket_metadata( $this->id, 'shipping_cost', $shipping_cost );
		if ( count($this->shipping_methods) > 0 )
		{
			if ( !$selected_shipping )
			{
				$this->selected_shipping = $this->shipping_methods[0]->id;
				$selected_shipping = $this->shipping_methods[0];
			}			
			$this->calculate_storage_pickup( $selected_shipping );
		}		
	}
	
			// Обновить доставку
	private function update_shipping( )
	{	
		$method = $this->get_shipping_method(); 			
		if ( !empty($method) )
			$this->calculate_storage_pickup( $method );
		else
			$this->selected_shipping = 0;	
	}
	
	private function calculate_storage_pickup( $method )
	{
		if ( $method->delivery_option )
		{
			$location_ids = $this->location_ids;
			$location_ids[] = 0;	
			if ( $this->storage_pickup )
			{
				$storage = usam_get_storage( $this->storage_pickup );						
				if ( empty($storage) || $storage['owner'] != $method->storage_owner )
					$this->storage_pickup = '';	
				elseif ( !usam_get_storages(['fields' => 'id', 'issuing' => 1, 'include' => [$this->storage_pickup], 'location_id' => $location_ids, 'owner' => $method->storage_owner]) )	
					$this->storage_pickup = '';
			}
			if ( !$this->storage_pickup )
			{
				$selected = usam_get_contact_metadata($this->contact_id, 'favorite_shop');										
				if ( usam_get_storages(['fields' => 'id', 'issuing' => 1, 'include' => [$selected], 'location_id' => $location_ids, 'owner' => $method->storage_owner]) )	
					$this->storage_pickup = $selected;
				else
				{
					$this->storage_pickup = usam_get_storages(['fields' => 'id', 'issuing' => 1, 'location_id' => $location_ids, 'owner' => $method->storage_owner, 'number' => 1]);					
					if ( !$this->storage_pickup )
						$this->storage_pickup = usam_get_storages(['fields' => 'id', 'issuing' => 1, 'owner' => $method->storage_owner, 'number' => 1]);
				} 
			} 
		}	
		else
			$this->storage_pickup = 0;	
	}
	
    // Получить стоимость доставки
	private function get_shipping_cost( $method )
	{				
		$weight = $this->calculate_total_weight();	
		$args['weight'] = usam_convert_weight( $weight, 'kg' );					
		$args['volume'] = usam_convert_volume( $this->calculate_total_volume(), 'm' );			
		$args['location'] = $this->location;
		$args['products'] = $this->get_products();
		$args['subtotal'] = $this->subtotal;		
		$args['order_id'] = $this->order_id;
		$args['basket_id'] = $this->id;		
		$args['type_payer'] = $this->type_payer;				
		$args['index'] = '';		
		if ( $method->delivery_option )
		{
			if ( $this->selected_shipping == $method->id )	
				$args['storage'] = $this->storage_pickup;
			else
			{
				$location_ids = $this->location_ids;
				$location_ids[] = 0;
				$args['storage'] = (int)usam_get_storages(['fields' => 'id', 'issuing' => 1, 'location_id' => $location_ids, 'owner' => $method->storage_owner, 'number' => 1]);
			}
		}
		else
			$args['storage'] = 0;			
		if ( $this->order_id )
		{
			$args['index'] = usam_get_order_metadata( $this->order_id, 'shippingpostcode' );
			if ( !$args['index'] )
				$args['index'] = usam_get_order_metadata( $this->order_id, 'company_shippingpostcode' ); 
		}
		else
		{			
			$args['index'] = usam_get_customer_checkout('shippingpostcode');			
			if ( !$args['index'] )
				$args['index'] = usam_get_customer_checkout('company_shippingpostcode');
		}		
		$shipped_instance = usam_get_shipping_class( $method->id );
		$shipping = $shipped_instance->get_final_delivery( $args );							
		//	$this->set_error( __('Не возможно посчитать доставку. Стоимость доставки вам сообщит менеджер.','usam'), 'delivery' );			
		if( $shipping )
			$shipping['storage_pickup'] = $args['storage'];
		return $shipping;		
	}  
		
	public function check_shipping_method() 
	{
		if ( $this->check_virtual_products() )
			return true;
		
		$selected_shipping = $this->get_shipping_method();
		if ( !empty($selected_shipping) ) 
			return true;
		else
			return false;
	}	
	
	public function get_shipping_method( $method_id = null )
	{
		$shipping_method = [];		
		$shipping_methods = $this->get_shipping_methods();
		if ( $method_id === null )
			$method_id = $this->selected_shipping;
		foreach( $shipping_methods as $shipping )
		{
			if ( $shipping->id == $method_id )
			{
				$shipping_method = $shipping;
				break;
			}			
		}
		return $shipping_method;
	}
				
    // Применить купон
	private function apply_coupons( )
	{			
		if ( $this->coupon_name != '' )
		{
			$coupon = usam_get_coupon( $this->coupon_name, 'coupon_code' );
			if ( $this->validate_coupon( $coupon ) )
			{						
				if ( $coupon['coupon_type'] == 'certificate' )
				{					
					if ( $coupon['is_used'] == 0 )
					{
						$certificates = usam_get_basket_metadata( $this->id, 'certificates' );
						if ( empty($certificates) )
							$certificates = array();
					
						$certificates[] = $this->coupon_name;											
						usam_update_basket_metadata( $this->id, 'certificates', $certificates );
					}
					$this->coupon_name = '';	
				}
				else
				{
					if ( $coupon['action'] == 's' ) //Бесплатная доставка
					{
						$shipping_methods = $this->get_shipping_methods();
						$shipping_cost = usam_get_basket_metadata( $this->id, 'shipping_cost' );	
						if ( empty($shipping_cost) )
							$shipping_cost = array();			
						foreach ( $this->shipping_methods as $key => $method )
						{	
							if ( $coupon['is_percentage'] != 1 )											
								$discount = $coupon['value'];				
							else															
								$discount = usam_round_price( $coupon['value']*$method->price / 100, $this->type_price);
																						
							$this->shipping_methods[$key]->price -= $discount;
							if ( $this->shipping_methods[$key]->price < 0)
								$this->shipping_methods[$key]->price = 0;
							
							if ( !empty($shipping_cost[$method->id]) && isset($shipping_cost[$method->id]['price']) )
								$shipping_cost[$method->id]['price'] = $this->shipping_methods[$key]->price;
						}		
						usam_update_basket_metadata( $this->id, 'shipping_cost', $shipping_cost );
					}
					else
					{ 
						$conditions = usam_get_coupon_metadata( $coupon['id'], 'conditions' );
						$return = $this->check_condition( $conditions ); // получить товары, которые удовлетворяют условиям купона	
						$sum = $return['sum'];	
						if ( $sum )
						{				
							if ( $coupon['is_percentage'] == 1  ) 	
							{								
								foreach ( $return['items'] as $key )	
								{										 									
									$this->products[$key]->old_price = $this->products[$key]->old_price == 0 ? $this->products[$key]->price : $this->products[$key]->old_price;
									$this->products[$key]->price = $this->products[$key]->price - $coupon['value'] * $this->products[$key]->price / 100;
									if ( $this->products[$key]->price < 0 )
										$this->products[$key]->price = 0;											
								}									
							}
							else
							{
								$products = [];
								foreach ( $return['items'] as $key )
									$products[] = $this->products[$key];		
								$this->add_fixed_discount( $coupon['value'], $products );		
							}
							foreach ( $return['items'] as $key )		
								$result = $this->update_basket_product( $this->products[$key]->id, ['price' => $this->products[$key]->price, 'old_price' => $this->products[$key]->old_price] );
							$this->calculate_subtotal();						
						}	
					}		
				}	
			}
			else
				$this->coupon_name = '';
		}
  	}

	// Проверяет, является ли текущий купон действителен для использования (Дата окончания действия, активность, использование ).
	private function validate_coupon( $coupon ) 
	{						
		if ( !empty($coupon) && usam_validate_rule($coupon) )
		{					
			if ( $coupon['user_id'] == 0 || $this->user_id == $coupon['user_id'] )				
				return true;	
		}
		$this->set_error( __('Купон не действителен','usam'), 'coupon' );		
		return false;
	}	
  
	private function get_product_prices( $product_id, $unit_measure = null )
	{
		$price = usam_get_product_price( $product_id, $this->type_price, $unit_measure, false);
		$old_price = usam_get_product_old_price( $product_id, $this->type_price, $unit_measure, false);						
		$unit = usam_get_product_unit( $product_id, $unit_measure );		
		if ( $price == $old_price )
			$old_price = 0;
		$unit_measure_code = usam_get_product_property($product_id, 'unit_measure_code');	
		if ( $unit_measure === null ||  $unit_measure_code === $unit_measure )
		{		
			$price = $price/$unit;
			$old_price = $old_price/$unit;
		}	
		if ( $this->accumulative_discount > 0 )
		{ 				
			if ( $old_price == 0 )
				$old_price = $price;
			
			$price = $price - ($price * $this->accumulative_discount / 100);
			$price = usam_round_price( $price, $this->type_price );
		}
		return ['price' => $price, 'old_price' => $old_price, 'unit' => $unit];
	}
			
   /**
    * Метод добавляет товар
    */	
	public function add_product_basket( $product_id, $parameters = [] )
	{			
		$result = false;			
		$unit_measure_code = usam_get_product_property($product_id, 'unit_measure_code', false);					
		$codes = apply_filters( 'usam_possible_unit_measure_basket', usam_get_product_property($product_id, 'possible_unit_measure', false), $product_id);
		$parameters = array_merge(['any_balance' => false, 'quantity' => 1, 'gift' => 0, 'unit_measure' => $unit_measure_code], $parameters);
		if ( !in_array($parameters['unit_measure'], $codes) )
			$parameters['unit_measure'] = current($codes);		
				
		$product_basket_id = 0;
		foreach((array)$this->products as $id => $product ) 
		{			
			if ( $product->product_id == $product_id && $product->unit_measure == $parameters['unit_measure'] )
			{
				$product_basket_id = $id;
				break;
			}
		}	
		if ( $parameters['gift'] )
		{
			$product_rule_id = (int)usam_get_product_meta($product_id, 'gift');		
			if ( !$product_rule_id )
				return false;			
			
			$ids = usam_get_discount_rules(['fields' => 'id', 'active' => 1, 'acting_now' => 1, 'include' => [$product_rule_id], 'type_rule' => 'basket']);	
			if ( !$ids )			
				return false;
			
			$perform_action = usam_get_discount_rule_metadata( $product_rule_id, 'perform_action');
			if ( $perform_action == 'gift_one_choice' )
			{
				foreach( $ids as $rule_id ) 
				{
					foreach((array)$this->products as $id => $product ) 
					{			
						if ( $product->gift )
						{
							$product_rule_id = usam_get_product_meta($product->product_id, 'gift');
							if ( $product_rule_id == $rule_id )
								return false;
						}
					}
				}
			}	 
		}		
		if ( $product_basket_id )
			$result = $this->update_quantity( $product_basket_id, $parameters['quantity'] );
		else
		{		
			if ( $parameters['any_balance'] || $this->check_remaining_quantity( $product_id, $parameters['quantity'], $parameters['unit_measure'] ) )
			{			
				$parameters = apply_filters( 'usam_add_product_basket', $parameters, $product_id, $this );				
				if ( $parameters['gift'] )
					$new_data = ['gift' => 1];
				else
					$new_data = $this->get_product_prices( $product_id, $parameters['unit_measure'] );		
				$new_data['quantity']  = $parameters['quantity'];
				$new_data['unit_measure'] = $parameters['unit_measure'];		
				$result = $this->set_basket_product( $product_id, $new_data );					
			}		
		}		
		return $result;
	}	
	
   /**
    * Метод изменения товара в корзине    
   */
	public function update_quantity( $product_basket_id, $new_quantity, $add = true )
	{				
		$result = false;					
		if( !empty($this->products[$product_basket_id]->product_id) ) 
		{	
			if ( $this->products[$product_basket_id]->gift || !$add && $this->products[$product_basket_id]->quantity == $new_quantity )
				return false;
							
			$product_id = $this->products[$product_basket_id]->product_id;
			if ( $add )
				$quantity = $this->products[$product_basket_id]->quantity + $new_quantity;	
			else
				$quantity = $new_quantity;		
			if( $quantity > 0 && ($new_quantity < 0 || $this->check_remaining_quantity($product_id, $quantity, $this->products[$product_basket_id]->unit_measure) ) )
			{								
				$new_data = $this->get_product_prices( $product_id, $this->products[$product_basket_id]->unit_measure );
				$new_data['quantity'] = $quantity;							
				$new_data = apply_filters( 'usam_update_quantity', $new_data, $product_id, $this );
				$result = $this->update_basket_product( $product_basket_id, $new_data );
				$this->calculate_subtotal();
				$this->refresh_product_taxes();
			}		
		} 			
		return $result;		
	}
			
	private function set_basket_product($product_id, $new_data) 
	{				
		if ( $this->id == null )
			$this->insert_cart();	
			
		if ( isset($new_data['unit_measure']) )
		{
			$unit = usam_get_unit_measure( $new_data['unit_measure'] );
			if ( empty($unit) )	
				$new_data['unit_measure'] = 'thing';
		}
		$default = ['quantity' => 1, 'unit_measure' => usam_get_product_property($product_id, 'unit_measure_code', false)];
		$new_data = array_merge ($default, $new_data);	
					
		$product_basket_id = false;
		foreach((array)$this->products as $id => $product ) 
		{			
			if ( $product->product_id == $product_id && $product->unit_measure == $new_data['unit_measure'] )
			{
				$product_basket_id = $id;
				break;
			}
		}			
		if ( $product_basket_id === false )	
		{			
			$_cart_item = new USAM_Cart_Item( $product_id, $new_data['unit_measure'], $this->id );
			$product_basket_id = $_cart_item->insert( $new_data );				
			if ( $product_basket_id )		
			{
				$cart_item_data = $_cart_item->get_data();	
				$this->products[$cart_item_data->id] = $cart_item_data;					
				$this->cart_item_count = count($this->products);			
				do_action( 'usam_add_item', $cart_item_data, $this );					
			}			
		}
		else
		{			
			$product_id = $this->products[$product_basket_id]->product_id;			
			$new_data = array_merge ($new_data, $this->get_product_prices( $product_id, $this->products[$product_basket_id]->unit_measure ) );			
			$new_data['quantity'] = $this->products[$product_basket_id]->quantity + $new_data['quantity'];		
			$new_data = apply_filters( 'usam_update_quantity', $new_data, $product_id, $this );
			if ( !$this->update_basket_product( $product_basket_id, $new_data ) )
				return false;			
		}
		return $product_basket_id;
	}
	
	private function update_basket_product( $product_basket_id, $new_data ) 
	{		
		$result = false;
		if ( isset($this->products[$product_basket_id]->product_id) )
		{
			$_cart_item = new USAM_Cart_Item( $this->products[$product_basket_id]->product_id, $this->products[$product_basket_id]->unit_measure, $this->id );		
			$result = $_cart_item->update( $new_data );	
			if ( $result )		
			{				
				$this->products[$product_basket_id] = $_cart_item->get_data();	
				do_action( 'usam_edit_item', $this->products[$product_basket_id], $this );	
			}
		}
		return $result;
	}			
		
	private function add_fixed_discount( $fixed_discount, $products, $discount = true, $write_to_property = '' ) 
	{				
		if ( $fixed_discount > 0 )
		{
			$subtotal = 0;
			$ids = [];	
			foreach ( $products as $key => $product ) 
			{
				if ( !$product->gift && ($discount || $product->old_price == 0) )	
				{
					$subtotal += $product->quantity*$product->price;
					$ids[] = $product->product_id;
				}
			}
			if ( $subtotal == 0 )
				return;		
	
			if ( $fixed_discount > $subtotal )
				$fixed_discount = $subtotal;
			$prozent = $fixed_discount*100/$subtotal;		
			$discounts = 0;					
			foreach($this->products as $key => $product)
			{					
				if ( !$product->gift && in_array($product->product_id, $ids) )
				{
					$discount = usam_round_price($product->price*$prozent/100, $this->type_price); 
					if ( $discount )
					{
						if ( $write_to_property )
								$this->products[$key]->$write_to_property = $discount;	
						if ( $product->old_price == 0 )
							$this->products[$key]->old_price = $product->price;
						$this->products[$key]->price = $product->price - $discount;
						$discounts += $discount*$product->quantity;
					}
				}
			}			
			if ( $fixed_discount != $discounts )
			{
				foreach($this->products as $key => $product)
				{					
					if ( !$product->gift && in_array($product->product_id, $ids) )
					{
						$discount = ($fixed_discount-$discounts)/$product->quantity;						
						$price = $product->price - $discount;						
						if ( $price )
						{
							$this->products[$key]->price = $price;
							if ( $write_to_property )
								$this->products[$key]->$write_to_property += $discount;
							break;
						}
					}
				}
			}	
		}
	}
	
	private function refresh_product_taxes( ) 
	{
		$new_product_taxes = []; 
		foreach ( $this->products as $product_key => $product ) 
		{		
			if ( empty($product->gift) )
			{					
				$result_tax = $this->get_calculate_tax(['price' => $product->price], $product->product_id );			
				if( !empty($result_tax['product_taxes']) )	
				{
					foreach ( $result_tax['product_taxes'] as $key => $tax ) 
					{
						$result_tax['product_taxes'][$key]['unit_measure'] = $product->unit_measure;
					}
					$new_product_taxes = array_merge( $new_product_taxes, $result_tax['product_taxes'] );							
				}
				$this->products[$product_key]->tax = $result_tax['data']['tax'];		
			}				
		}		
		$product_taxes = $this->get_product_taxes( );
		$update_ids = [];			
		$cache_delete = false;		
		foreach ( $new_product_taxes as $i => $new_product_tax ) 
		{
			foreach ( $product_taxes as $product_tax ) 
			{
				if ( $new_product_tax['tax_id'] == $product_tax->tax_id && $new_product_tax['product_id'] == $product_tax->product_id && $new_product_tax['unit_measure'] == $product_tax->unit_measure )
				{										
					if ( $new_product_tax['tax'] != $product_tax->tax || $new_product_tax['is_in_price'] != $product_tax->is_in_price || $new_product_tax['rate'] != $product_tax->rate  )
					{
						$cache_delete = true;
						usam_update_order_product_tax( $product_tax->id, $new_product_tax );							
					}									
					$update_ids[] = $product_tax->id;					
					unset($new_product_taxes[$i]);							
				}
			}
		} 		
		$update = false;
		$ids = usam_get_basket_metadata( $this->id, 'product_taxes' );	
		if ( empty($ids) )
			$ids = [];
		if ( !empty($product_taxes) )
		{			
			foreach ( $product_taxes as $product_tax ) 
			{ 
				if( !in_array($product_tax->id, $update_ids) )
				{
					$cache_delete = true;
					usam_delete_order_product_tax( $product_tax->id );
					if( ($key = array_search($product_tax->id,$ids)) !== false )
					{
						$update = true;
						unset($ids[$key]);
					}
				}
			}
		}		
		else	
			$ids = [];
		
		foreach ( $new_product_taxes as $new_product_tax ) 
		{ 
			$update = true;
			$cache_delete = true;				
			$ids[] = usam_insert_order_product_tax( $new_product_tax );	
		}			
		if ( $update )
			usam_update_basket_metadata( $this->id, 'product_taxes', $ids );		
	
		if ( $cache_delete )					
			wp_cache_delete( $this->id, 'usam_cart_product_taxes' );	
	}
			
	//Рассчитать налог
	private function get_calculate_tax( $data, $product_id ) 
	{				
		$data['tax'] = 0;
		$new_product_taxes = [];
		if ( empty($data['price']) )
			return ['data' => $data, 'product_taxes' => $new_product_taxes];
				
		$result_tax = usam_calculate_tax(['location_ids' => $this->location_ids, 'type_payer' => $this->type_payer, 'payment' => $this->selected_payment, 'price' => $data['price']], $product_id );		
			
		$data['tax'] = (float)$result_tax['tax'];
		return ['data' => $data, 'product_taxes' => $result_tax['product_taxes']];
	}
	
	private function get_tax( )
	{	
		$results = [];		
		$products = $this->get_products();
		if ( !empty($products) )
		{	
			foreach($products as $key => $product)
			{
				$results[$product->product_id] = ['tax' => 0, 'is_in_price' => 0, 'not_in_price' => 0];
			}	
			$product_taxes = $this->get_product_taxes( );	
			foreach ( $product_taxes as $product_tax ) 
			{			
				if ( !isset($results[$product_tax->product_id]) )
					continue;
				$results[$product_tax->product_id]['tax'] += $product_tax->tax;	
				if ( $product_tax->is_in_price )
					$results[$product_tax->product_id]['is_in_price'] += $product_tax->tax;
				else
					$results[$product_tax->product_id]['not_in_price'] += $product_tax->tax;
			}
		}
		return $results;
	}
	
	// Получить налог на товары
	public function get_product_taxes( )
	{	
		if ( $this->id )
		{
			$cache_key = 'usam_cart_product_taxes';		
			if( ! $cache = wp_cache_get($this->id, $cache_key) )
			{		
				$ids = usam_get_basket_metadata( $this->id, 'product_taxes' );			
				if ( !empty($ids) )	
				{
					require_once(USAM_FILE_PATH.'/includes/document/order_product_taxes_query.class.php');
					$cache = usam_get_order_product_taxes_query(['include' => $ids]);
				}
				else
					$cache = [];
				wp_cache_set( $this->id, $cache, $cache_key );
			}
		}		
		else
			$cache = [];
		return $cache;
	}		
	
	/**
    * Удалить из корзины товар
    */
	public function remove_products( $ids, $recalculate = false )
	{
		global $wpdb;		
		
		if ( $this->id == null || empty($ids) )
			return false;		
		
		if ( is_numeric($ids) )
			$ids = [ $ids ];
		
		$product_ids = [];
		foreach( $ids as $id )
		{		
			if ( empty($this->products[$id]) )
				continue;	
			$product_ids[] = $this->products[$id]->product_id;
		}
		if ( $product_ids )
			$wpdb->query( "DELETE FROM `".USAM_TABLE_TAX_PRODUCT_ORDER."` WHERE `product_id` IN (".implode(",", $product_ids).") AND order_id=0" );				
		if( $wpdb->query( $wpdb->prepare("DELETE FROM `".USAM_TABLE_PRODUCTS_BASKET."` WHERE `cart_id` IN (%s) AND `id` IN (".implode(",", $ids).")", $this->id ) ) )
		{			
			foreach( $ids as $id ) 
			{
				do_action( 'usam_product_basket_delete', $this->products[$id] );
				unset($this->products[$id]);
			}
			$this->cart_item_count = count($this->products);
			$this->current_cart_item = -1;
			if ( $recalculate )
				$this->recalculate();
			return true;	
		}		
		return false;
	}
		
	// Получить номер товара в цикле
	public function get_item_id_by( $value, $colum = 'product_id' )
	{
		$result = false;		
		if ( !in_array($colum, ['id', 'product_id']) )
			return $result;		
		
		$products = $this->get_products();
		foreach( $products as $product_basket_id => $product ) 
		{
			if ( $product->$colum == $value )
			{
				$result = $product_basket_id; 	
				break;
			}
		}
		return $result;
	}
	
	// Количество товаров в корзине
	public function get_number_products()
	{		
		$count = 0;
		$products = $this->get_products();
		foreach( $products as $product ) 
			$count += $product->quantity;   	
		return $count;
	}
	
	public function set_error( $error, $key = 'basket' )
	{	
		if ( !empty($error) )
		{ 
			$errors = $this->get_errors_message( true );		
			$errors[$key] = $error;
			$this->errors = array_merge($errors, $this->errors);				
			usam_update_basket_metadata( $this->id, 'errors', $errors );				
		}
	}
	
	public function get_errors_message( $erase = false )
	{			
		$errors = usam_get_basket_metadata( $this->id, 'errors' );		
		if ( !is_array($errors) )
			$errors = [];
	
		if ( $erase )
			usam_update_basket_metadata( $this->id, 'errors', [] );
		return array_merge($errors, $this->errors);
	}

	// Метод проверяет оставшееся количество. В настоящее время только проверяет оставшиеся запасы, в будущем будет делать заявки на запасы и лимиты количества
	private function check_remaining_quantity( $product_id, $quantity, $unit_measure )
	{		
		$result = false;	
		$stock = usam_product_remaining_stock( $product_id, null, $unit_measure );				
		$product = get_post( $product_id );
		if( $stock > 0 ) 
		{			
			if( $quantity <= $stock )
			   $result = true;
			else
				$this->set_error( sprintf( __('Извините, но есть только %s ед. товара %s.', 'usam'), $stock, $product->post_title ), 'product'.$product_id );		
		}
		else			
			$this->set_error( sprintf(__('Извините, но товара "%s" нет в наличии.', 'usam'), $product->post_title), 'product'.$product_id );	
		return $result;
	}	
   
   /**
    * Метод очистки Корзина
   */
  public function empty_cart( ) 
  {				
		$this->products = [];
		$this->product = null;
		$this->cart_item_count = 0;
		$this->current_cart_item = -1;
		$this->subtotal = null;				
		$this->coupon_name = '';			
		$this->bonuses	= 0;	
		$this->paid	= 0;	
		$this->order_id	= 0;		
		
		$this->clear_cache();	
		
		if ( $this->id )
		{
			usam_remove_products_basket( $this->id );		
			usam_update_basket_metadata( $this->id, 'certificates', [] );	
			usam_update_basket_metadata( $this->id, 'errors', [] );
			$this->remove_discount( );			
			$this->update();	
		}				
		do_action( 'usam_clear_cart', $this );
	}
	
    // Метод Очистить кэш, используется для очистки кэшированных итоги
	function clear_cache()
	{				
		$this->shipping = 0;	
		$this->total_price = 0;		
		$this->accumulative_discount = 0;	
		$this->recalculation_date = null;		
		$this->products_discount = 0;	
	
		do_action ( 'usam_after_cart_clear_cache', $this );
	}  	
	
  // Обновление корзины
	private function refresh_cart_items() 
	{	
		$new_product_taxes = []; 
		if ( !$this->recalculate_order )
		{
			$ids = [];
			foreach ( $this->products as $key => $product ) 
			{					
				if ( usam_product_has_stock( $product->product_id ) )	
				{
					if ( empty($product->gift) )
					{					
						$new_data = $this->get_product_prices( $product->product_id, $product->unit_measure );	
						$this->products[$key]->price = $new_data['price'];
						$this->products[$key]->old_price = $new_data['old_price'];					
					}			
				}
				else
				{
					$ids[] = $product->id;
					$this->set_error( sprintf( __('Извините, но товара "%s" уже нет в наличии. Мы его удалили из вашей корзины.', 'usam'), $product->name ),'product'.$product->product_id);				
				}
			}
			$this->remove_products( $ids );
			$this->calculate_subtotal();	
		}
	} 	
	
   // Метод считает сумму корзины
	function calculate_total_price() 
	{		
		if ( count($this->products) )
		{	// Рассчитать индивидуальный компонент, составляющих корзина	
			$total = 0;			
			if ( $this->check_need_recalculation() )
			{	
				$this->refresh_cart_items( );			
				$shipping_method = $this->get_shipping_method();	 
				$this->recalculation_date = date("Y-m-d H:i:s");
				$this->apply_coupons();
				$this->calculation_basket_shares();				
				$this->spend_bonuses();
 
				$rules = get_option('usam_bonus_rules', []);					
				$this->add_fixed_discount( $this->bonuses, $this->products, empty($rules['exclude_discount_products']), 'used_bonuses' ); //Рассчитать скидку по бонусам
				$this->calculate_subtotal();
				$this->refresh_product_taxes();
				$this->calculate_subtotal();
											
				$total += $this->subtotal;			
				if ( $total < 0 )
					$total = 0;
								
				if ( !empty($shipping_method) )
				{ 
					$this->shipping = $shipping_method->price;
					if ( $shipping_method->include_in_cost )
						$total += $this->shipping;					
				}			
				else
					$this->shipping = 0;			
	
				foreach ( $this->products as $key => $product ) 
				{					
					if ( empty($product->gift) )
					{												
						$_cart_item = new USAM_Cart_Item( $product->product_id, $product->unit_measure, $this->id );		
						$_cart_item->refresh_item(['price' => $product->price, 'old_price' => $product->old_price, 'used_bonuses' => $product->used_bonuses]);
					}					
				}							
				$this->total_price = apply_filters( 'usam_calculate_total_price', $total, $this );// общий фильтр
				$this->update();
			}			
		}
		else
			$this->total_price = 0;		
		return $this->total_price;
	}
	
	private function get_product_gifts( $discount_rules ) 
	{		
		global $wpdb;			
		$ids = [];
		foreach ( (array)$discount_rules as $rule )	
		{
			$perform_action = usam_get_discount_rule_metadata( $rule->id, 'perform_action');
			if ( $perform_action == 'g' || $perform_action == 'gift_choice' || $perform_action == 'gift_one_choice' )
				$ids[] = $rule->id;
		}
		$product_gifts = [];
		if ( $ids )
		{			
			$results = $wpdb->get_results("SELECT meta_value, product_id FROM ".USAM_TABLE_PRODUCT_META." WHERE meta_value IN (".implode(",", $ids ).") AND meta_key='gift'");			
			if ( !empty($results) )
			{
				foreach ( $results as $result )	
					$product_gifts[$result->meta_value][] = $result->product_id;
			}
		}
		return $product_gifts;
	}
	
	// Расчет правил корзины
	private function calculation_basket_shares() 
	{		
		$discount_rules = usam_get_discount_rules(['active' => 1, 'acting_now' => 1, 'orderby' => 'priority', 'order' => 'ASC', 'type_rule' => 'basket', 'cache_meta' => true]);		
		$accrue_bonuses = 0;
		$calculate_subtotal = false;
		if ( !empty($discount_rules) )
		{			
			$discount_ids = array();
			$this->remove_discount( );		
			
			$discont_price_product = [];
			$discont_price_shipping = [];			
						
			$gift_ids = [];
			foreach( $this->products as $key => $product)
			{		
				if ( !$product->gift )	
					$discont_price_product[$key] = $product->price; // Получить массив цен со скидками для расчета правил
			}					
			$product_gifts = $this->get_product_gifts( $discount_rules );	
			foreach ( (array)$discount_rules as $rule )	
			{	
				$perform_action = usam_get_discount_rule_metadata( $rule->id, 'perform_action');					
				$conditions = usam_get_discount_rule_metadata( $rule->id, 'conditions');			
				$return = $this->check_condition( $conditions ); 
				$sum = $return['sum'];						
				if ( $sum )
				{										
					if ( $rule->dtype == 'f' )								
						$discount = $rule->discount;				
					else															
						$discount = usam_round_price( $rule->discount*$sum / 100, $this->type_price);			

					switch( $perform_action )
					{							
						case 'p': // Изменить цену товара			
							foreach ( $return['items'] as $key )	
							{								
								if ( $rule->dtype == 'f' )									
									$discount = $rule->discount;				
								else															
									$discount = usam_round_price( $rule->discount*$discont_price_product[$key] / 100, $this->type_price);								
								if ( $discount > 0 )
									$discont_price_product[$key] = $discont_price_product[$key] - $discount;
							}		
						break;
						case 's': // Изменить доставку									
							$shipping_methods = $this->get_shipping_methods();
							foreach ( $this->shipping_methods as $key => $shipping_method )
							{	
								if ( $rule->dtype == 'f' )											
									$discount = $rule->discount;				
								else															
									$discount = usam_round_price( $rule->discount*$shipping_method->price / 100, $this->type_price);
								
								$this->shipping_methods[$key]->price -= $discount;											
								if ( $this->shipping_methods[$key]->price < 0)
									$this->shipping_methods[$key]->price = 0;
								
								$discont_price_shipping[$shipping_method->id] = $this->shipping_methods[$key]->price;
							}									
						break;
						case 'b': // Добавить бонусы															
							$accrue_bonuses += $discount;																		
						break;					
						case 'gift_one_choice':
						case 'gift_choice':								
						case 'g': // Добавить подарок							
							if ( !empty($product_gifts) )
							{
								foreach ( (array)$product_gifts[$rule->id] as $product_id )	
								{		
									$gift = false;
									foreach($this->products as $key => $product)
									{
										if ( $product->product_id == $product_id )
										{											
											$gift = true;
											break;
										}
									}								
									if ( $gift )
									{
										if ( isset($gift_ids[$product->product_id]) )
											$gift_ids[$product->product_id] += 1;
										else
											$gift_ids[$product->product_id] = 1;
									}
									elseif ( $perform_action != 'gift_choice' ) // Пользователь сам должен добавить подарок
									{
										$calculate_subtotal = true;
										$this->set_basket_product( $product_id, ['quantity' => 1, 'gift' => 1]);
									}
								}
							}
						break;						
					}						
					$discount_ids[] = $rule->id;						
					if ( $rule->end)
						break;							
				}				
			}	
			$ids = [];
			foreach($this->products as $key => $product)
			{
				if ( $product->gift )	
				{
					$gift = false;
					foreach ( $gift_ids as $product_id => $quantity )
					{					
						if ( $product->product_id == $product_id )
						{
							$gift = true;
							if ( $product->quantity != $quantity )							
								$this->update_basket_product( $product->id, ['quantity' => $quantity, 'gift' => 1] );	
							break;
						}							
					}
					if ( !$gift )
						$ids[] = $product->id;										
				}
			}	
			$this->remove_products( $ids );			
			if ( !empty($discont_price_shipping) )
			{		
				usam_update_basket_metadata( $this->id, 'shipping_cost', $discont_price_shipping );
			}
			if ( !empty($discont_price_product) )	
			{ 					
				foreach ( $discont_price_product as $key => $price )	
				{						
					if ( $price != $this->products[$key]->price )
					{
						$calculate_subtotal = true;
						$old_price = $this->products[$key]->old_price == 0 ? $this->products[$key]->price : $this->products[$key]->old_price;
						$this->update_basket_product( $this->products[$key]->id, ['price' => $price, 'old_price' => $old_price] );		
					}
				}					
			}		
			if ( $calculate_subtotal )
				$this->calculate_subtotal();			
			$this->set_rule_discount( $discount_ids );	
		}			
		$db_accrue_bonuses = (int)usam_get_basket_metadata( $this->id, 'accrue_bonuses' );
		if ( $accrue_bonuses != $db_accrue_bonuses )
			usam_update_basket_metadata( $this->id, 'accrue_bonuses', $accrue_bonuses ); 
	}	
	
	function compare_products ($v1, $v2) 
	{		
		if ($v1["priority"] == $v2["priority"]) return 0;
		return ($v1["priority"] > $v2["priority"])? -1: 1;
	}
	
	private function get_accumulative_discount( ) 
	{	
		if ( $this->accumulative_discount === null && $this->user_id )
		{						
			$this->accumulative_discount = get_user_meta( $this->user_id, 'usam_accumulative_discount', true );							
		}
	}
	
	// Получить товары
	public function get_products()
	{					
		return $this->products;		
	}
	
	public function get_product( $product_id, $unit_measure = null )
	{			
		if ( $unit_measure === null )
			$unit_measure = usam_get_product_property($product_id, 'unit_measure_code', false);		
		$products = $this->get_products();
		foreach( $products as $key => $product )
		{											
			if ( $product->product_id == $product_id && $product->unit_measure == $unit_measure )
			{
				return (array)$product;
			}
		}
		return array();
	}	
	
	public function set_products()
	{					
		require_once( USAM_FILE_PATH . '/includes/basket/products_basket_query.class.php' );
		$query_args = apply_filters( 'usam_query_args_products_baskets', ['cart_id' => $this->id, 'orderby' => 'id', 'order' => 'ASC', 'cache_results' => true], $this );
		$products = usam_get_products_baskets( $query_args );
		if ( !empty($products) )
		{
			$this->get_accumulative_discount( ); // Получить накопительную скидку	
			$post_ids = [];
			foreach( $products as $product )
			{
				$post_ids[] = $product->product_id;
				
				$product->old_price = (float)$product->old_price;
				$product->price = (float)$product->price;
		
				$this->products[$product->id] = $product;
			}	
			update_meta_cache('post', $post_ids);
			usam_update_cache( $post_ids, [USAM_TABLE_PRODUCT_META => 'product_meta'], 'product_id');
			$this->cart_item_count = count($this->products);				
				
			$this->calculate_subtotal();
			$this->calculate_total_price();
		}		
	}

  /**
    * Рассчитывает сумму по корзине
   */
	private function calculate_subtotal( )
	{	
		$this->subtotal = 0;					
		$this->products_discount = 0;	
		$product_taxes = $this->get_tax();		
		foreach( $this->products as $key => &$product)
		{	
			$product->tax = isset($product_taxes[$product->product_id]['tax'])?$product_taxes[$product->product_id]['tax']:0;	
			$product->is_in_price = isset($product_taxes[$product->product_id]['is_in_price'])?$product_taxes[$product->product_id]['is_in_price']:0;	
			$product->not_in_price = isset($product_taxes[$product->product_id]['not_in_price'])?$product_taxes[$product->product_id]['not_in_price']:0;	
			$product->unit = usam_get_product_unit($product->product_id, $product->unit_measure);			
			$product->total = $product->quantity*($product->price + $product->not_in_price);
			$product->total_before_discount = $product->quantity*($product->old_price + $product->not_in_price);
			$this->subtotal += $product->total;				
			if ( $product->old_price )
				$this->products_discount += $product->quantity*($product->old_price - $product->price);	
		}				
		$this->number_products = count($this->products);
	}
	
	public function check_virtual_products( )
	{						
		$virtual_products = true;
		$products = $this->get_products();
		foreach( $products as $key => $product )
		{				
			if ( usam_check_product_type_sold( 'product', $product->product_id ) )
			{
				$virtual_products = false;
				break;
			}
		}
		return $virtual_products;
	}
	
	/**
	 * Возвращает элементы корзины.
	 * Принимает массив аргументов:
	 * - 'fields': По умолчанию «all», который возвращает все поля. В противном случае, укажите поля, которые нужно получить, например, «quantity» или «pnp».
	 * - 'orderby': Specify a field to sort the cart items. Default to '', which means "unsorted".
	 * - 'order'  : Specify the direction of the sort, 'ASC' for ascending, 'DESC' for descending.
	 *              Defaults to 'DESC'
	 */
	public function get_items( $args = array() )
	{
		$defaults = ['fields'  => 'all', 'orderby' => '', 'order' => 'ASC'];
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		$results = $this->get_products();

		if ( !empty( $orderby ) ) 
		{
			$comparison = new USAM_Comparison_Object( $orderby, $order );
			usort( $results, [$comparison, 'compare'] );
		}
		if ( $fields != 'all' )
			$results = wp_list_pluck( $results, $fields );

		return $results;
	}
   
   /** 
    * Рассчитать вес корзины
   */
	function calculate_total_weight() 
	{		
		$total = 0;		
		$products = $this->get_products();
		foreach($products as $key => $product)
		{
			$weight = usam_get_product_property( $product->product_id, 'weight', false);
			if ( empty($weight) )			
				continue;
			$total += $weight*$product->quantity*$product->unit;			
		}
		return $total;
	}	
	
	function calculate_total_volume() 
	{		
		$total = 0;		
		$products = $this->get_products();
		foreach($products as $key => $product)
		{			
			$volume = usam_get_product_property( $product->product_id, 'volume', false);			
			$total += $volume*$product->quantity*$product->unit;			
		}
		return $total;
	}		
	
	function get_cart_terms_ids( $taxonomy = 'usam-category' )
	{
		$ids = [];
		$products = $this->get_products();
		if ( empty($products) )
			return [];
		
		foreach($products as $key => $product) 
		{
			$ids[] = $product->product_id;
		}			
		$object_terms = wp_get_object_terms( $ids, $taxonomy, ['fields' => 'ids'] );		
		if ( $taxonomy == 'usam-category' )
		{			
			$ids = $object_terms = array_unique( $object_terms );
			foreach( $object_terms as $term_id ) 
			{
				$ancestors = usam_get_ancestors( $term_id, $taxonomy );
				$ids = array_merge( $ids, $ancestors );						
			}
			return array_unique( $ids );
		}
		return array_unique( $object_terms );	
	}  
  
	private function spend_bonuses()
	{
		if ( $this->bonuses == 0 )
			return false;
		if ( $this->subtotal )
		{	
			$rules = get_option('usam_bonus_rules', '');				
			if ( empty($rules['bonus_coupon']) && $this->get_property( 'coupons_amount' ) != 0 )
			{
				$this->set_error( __('Нельзя использовать и бонусы и купоны одновременно', 'usam'),'bonus' );
				return false;	
			}		
			$btotal_price = $this->allowed_spend_bonuses( true );
			$this->bonuses = $btotal_price>$this->bonuses?$this->bonuses:$btotal_price;		
		}		
	}
	
	public function allowed_spend_bonuses( $message = false )
	{
		if ( $this->subtotal )
		{	
			$bonuses = usam_get_available_user_bonuses();
			if ( $bonuses )
			{
				$rules = get_option('usam_bonus_rules', '');				
				if ( empty($rules['bonus_coupon']) && $this->get_property( 'coupons_amount' ) != 0 )
					return 0;
				if ( empty($rules['percent']) )
					$rules['percent'] = 20;		
		
				$subtotal = 0;
				$products = $this->get_products();
				if ( !empty($rules['exclude_discount_products']) )
				{
					$product_ids = [];
					foreach( $products as $product )
					{					
						$product_ids[] = $product->product_id;
					}	
					$products_discounts = usam_get_products_discounts(['product_id' => $product_ids, 'code_price' => $this->type_price]);
					$discount_message = false;
					foreach( $products as $product )
					{					
						if ( !$product->old_price && !isset($products_discounts[$product->product_id]) )
							$subtotal += $product->price * $product->quantity;
						else
							$discount_message = true;
					}				
					if ( $message && $discount_message )
						$this->set_error( __('Обратите внимание! По условиям программы на товары со скидками бонусы использовать нельзя', 'usam'),'bonus' );
				}	
				else
				{
					foreach( $products as $product )
						$subtotal += $product->price * $product->quantity;
				}	
				$btotal_price = round($subtotal * $rules['percent'] / 100, 0);		
				return $btotal_price>$bonuses?$bonuses:$btotal_price;								
			}
		}	
		return 0;		
	}
	
   /**
    * Перебирает товары в корзине -------------------------------------------------------------------
   */
	function next_cart_item() 
	{
		$this->current_cart_item++;	
		
		$this->product = $this->products[$this->products_keys[$this->current_cart_item]];
		return $this->product;
   }

  function the_cart_item() 
  {
		$this->in_the_loop = true;		
		$this->product = $this->next_cart_item();
		if ( $this->current_cart_item == 0 ) 
			do_action('usam_cart_loop_start');
   }

   function have_cart_items() 
   {
		if ($this->current_cart_item + 1 < $this->cart_item_count)
		{
			$this->products_keys = array_keys($this->products);
			return true;      
		}
		else if ($this->current_cart_item + 1 == $this->cart_item_count && $this->cart_item_count > 0) 
		{
			do_action('usam_cart_loop_end');       
			$this->rewind_cart_items();
		}
		$this->in_the_loop = false;
		return false;
   }

   function rewind_cart_items() 
   {
		$this->current_cart_item = -1;
		if ($this->cart_item_count > 0) 
			$this->product = $this->products[$this->products_keys[0]];      
   } 
   
   /**
   * Обновить заказ
   */
	public function save_order( $data = [] )
	{
		$this->calculate_total_price();
		$basket_products = $this->get_products();
		$number_products = count($basket_products); //$number = $this->get_number_products();
		if ( !$this->calculate_purchase_rules() || $number_products == 0 )
			return false;			

		$args = [
			'totalprice'          => $this->total_price,	
			'number_products'     => $number_products,			
			'status'              => 'incomplete_sale',				
			'type_price'          => $this->type_price,	
			'type_payer'          => $this->type_payer,				
			'user_ID'             => $this->user_id,
			'contact_id'          => $this->contact_id,
		];				
		if ( usam_get_order( $this->order_id ) )
			return $this->order_id; 
		else
		{
			$args = array_merge( $args, $data );	
			$purchase_log = new USAM_Order( $args );	
			$result = $purchase_log->save();	
			if ( !$result )
				return false;		
						
			$website_type = get_option('usam_website_type', 'store' );
			$this->order_id = $purchase_log->get('id');	
			if ( $this->coupon_name )
				usam_update_order_metadata($this->order_id, 'coupon_name', $this->coupon_name );
			
			$this->update_order();							
			
			if ( !empty($_COOKIE['advertising_campaign']) )
				usam_update_order_metadata($this->order_id , 'campaign_id', $_COOKIE['advertising_campaign'] );
			
			// сохранить товары заказа в базу			
			foreach( $basket_products as $key => $product )
			{		
				$products[] = [
					'product_id'   => $product->product_id,
					'name'         => $product->name,
					'order_id'     => $this->order_id,
					'used_bonuses' => $product->used_bonuses,
					'price'        => $product->price,
					'old_price'    => $product->old_price,	
					'quantity'     => $product->quantity,	
					'unit_measure' => $product->unit_measure
				];				
			}			
			$purchase_log->add_products( $products );
			
			$product_reserve_condition = get_option('usam_product_reserve_condition', '');
			$shipped_products = [];		
			foreach( $basket_products as $product )
			{
				if ( usam_check_product_type_sold( 'product', $product->product_id ) )
				{				
					$reserve = $product_reserve_condition == 'o' ? $product->quantity : 0;
					$shipped_product = ['product_id' => $product->product_id, 'quantity' => $product->quantity, 'unit_measure' => $product->unit_measure, 'reserve' => $reserve];
					if ( $website_type == 'marketplace' )
						$shipped_products[$product->seller_id][] = $shipped_product;
					else
						$shipped_products[] = $shipped_product;
				}
			}
			$document_shipped = ['price' => $this->shipping];
			$selected_shipping = $this->get_shipping_method();
			if ( !empty($selected_shipping) )
			{
				if ( $selected_shipping->tax_id )
				{				
					$document_shipped['tax_id']          = $selected_shipping->tax_id;				
					$document_shipped['tax_name']        = $selected_shipping->tax_name;
					$document_shipped['tax_value']       = $selected_shipping->tax_value;
					$document_shipped['tax_is_in_price'] = $selected_shipping->tax_is_in_price;
					$document_shipped['tax_rate']        = $selected_shipping->tax_rate;
				}				
			}	
			if ( $website_type == 'marketplace' )
			{
				foreach( $shipped_products as $seller_id => $products )
				{
					$document_shipped['seller_id'] = $seller_id;
					$this->insert_shipped_document( $document_shipped, $products );
				}
			}
			else
			{
				if ( $shipped_products )
					$this->insert_shipped_document( $document_shipped, $shipped_products );
			}
			$certificates = usam_get_basket_metadata( $this->id, 'certificates' );
			if ( !empty($certificates) )
			{ //Оплата сертификатами	
				$certificates = usam_get_coupons(['coupon_code' => $certificates]);
				if ( $certificates )
				{
					$payment = ['document_id' => $this->order_id, 'status' => 3];
					foreach( $certificates as $coupon )
					{
						$payment['sum'] = $coupon->value;
						$payment['payment_type'] = 'certificate';			
						usam_insert_payment_document( $payment, ['document_id' => $this->order_id, 'document_type' => 'order']);									
						usam_update_coupon($coupon->id, ['is_used' => 1]);			
					}
				}
			}	
			$this->set_bonus_account( );
			usam_update_basket_metadata( $this->id, 'errors', []);
		}
		$this->update();
		do_action( 'usam_basket_add_order', $this->order_id );	
		return $this->order_id;		
	}
	
	private function insert_shipped_document( $document_shipped, $products = [] ): int
	{		
		$selected_shipping = $this->get_shipping_method();	
		$document_shipped['method'] = $this->selected_shipping;		
		if ( !empty($selected_shipping) )
		{
			if ( $selected_shipping->delivery_option )
				$document_shipped['storage_pickup'] = $this->storage_pickup;				
			$document_shipped['include_in_cost'] = $selected_shipping->include_in_cost;	
			$document_shipped['name']            = $selected_shipping->name;
		}
		if ( !empty($this->storage_pickup) )
		{
			$storage = usam_get_storage( $this->storage_pickup );
			if ( !empty($storage['shipping']) )
				$document_shipped['storage'] = $this->storage_pickup;		
		}
		$document_shipped['status']   = 'pending';
		$document_shipped['order_id'] = $this->order_id;			
		$document_id = usam_insert_shipped_document( $document_shipped, $products, ['document_id' => $this->order_id, 'document_type' => 'order']);	
		if ( !empty($document_shipped['tax_name']) )
			usam_add_shipped_document_metadata( $document_id, 'tax_name', $document_shipped['tax_name'] );
		if ( !empty($document_shipped['tax_is_in_price']) )
			usam_add_shipped_document_metadata( $document_id, 'tax_is_in_price', $document_shipped['tax_is_in_price'] );
		if ( !empty($document_shipped['tax_rate']) )
			usam_add_shipped_document_metadata( $document_id, 'tax_rate', $document_shipped['tax_rate'] );		
		
		return $document_id;		
	}
	
	// Пересчитать заказ
	public function set_order( $order_id )
	{
		global $wpdb;
		
		$this->recalculate_order = true;
		if ( $this->id == null )
			$this->insert_cart();
						
		$order = new USAM_Order( $order_id );	
		$order_data = $order->get_data();
		$order_products = usam_get_products_order( $order_id );
				
		$wpdb->query( "DELETE FROM `".USAM_TABLE_TAX_PRODUCT_ORDER."` WHERE  order_id={$order_id}" );
		$wpdb->query( "DELETE FROM `".USAM_TABLE_DISCOUNT_RULES."` WHERE  order_id={$order_id}" );				
		
		$properties = ['bonuses' => usam_get_used_bonuses_order( $order_id ), 'type_price' => $order_data['type_price'], 'type_payer' => $order_data['type_payer'], 'user_id' => $order_data['user_ID']];
		if ( usam_is_type_payer_company( $order_data['type_payer'] ) )
			$location = usam_get_order_metadata( $order_id, 'company_shippinglocation' );			
		else
			$location = usam_get_order_metadata( $order_id, 'shippinglocation' );
		
		$properties['location'] = $location?$location:get_option('usam_shop_location');
		$properties['coupon_name'] = usam_get_order_metadata( $order_id, 'coupon_name');		
				
		$this->save_properties( $properties );				
		foreach ( $order_products as $product ) 
		{						
			$new_data = $this->get_product_prices( $product->product_id, $product->unit_measure );	
			$new_data['quantity'] = $product->quantity;		
			$new_data['unit_measure'] = $product->unit_measure;		
			$this->set_basket_product( $product->product_id, $new_data );
		}		
		$this->order_id = (int)$order_id;
		$this->recalculate();		
				
		$args = ['totalprice' => $this->total_price];	
		$products = [];		
		$product_ids = [];
		foreach ( $order_products as $order_product )
		{
			foreach ( $this->get_products() as $product ) 	
			{				
				if ( $product->product_id == $order_product->product_id && $product->unit_measure == $order_product->unit_measure )
				{
					$products[] = ['price' => $product->price, 'old_price' => $product->old_price, 'id' => $order_product->id];
					$product_ids[] = $product->product_id;
				}
			}
		}
		$this->update_order( );	
		
		$order->update_products( $products );				
		$order->set( $args );	
		$order->save();		
		
		usam_update_order_metadata($order_id, 'coupon_name', $this->coupon_name );
								
		require_once( USAM_FILE_PATH .'/includes/document/document_discounts_query.class.php' );
		$products_discounts = usam_get_products_discounts(['product_id' => $product_ids, 'code_price' => $this->type_price]);	
		$document_discounts = usam_get_document_discounts_query(['document_id' => $order_id, 'document_type' => 'order','product_id' => $product_ids]);
		$d = [];
		foreach ( $document_discounts as $document_discount ) 
		{
			$d[$document_discount->rule_id][] = $document_discount;
		}
		require_once( USAM_FILE_PATH .'/includes/document/document_discount.class.php' );
		foreach ( $product_ids as $product_id ) 			
		{
			if ( isset($products_discounts[$product_id]) )
			{
				foreach ( $products_discounts[$product_id] as $products_discount )
				{
					if ( isset($d[$products_discount->discount_id]) )
					{
						usam_update_document_discount( $products_discount->discount_id, $d[$products_discount->discount_id] );
						unset($d[$products_discount->discount_id]);
					}
					else									
						usam_set_document_discount( $products_discount->discount_id, ['document_id' => $order_id, 'document_type' => 'order', 'product_id' => $product_id]);
				}
			}
		}
		foreach ( $d as $document_discount ) 
		{
			usam_delete_document_discount( $document_discount->id );
		}		
		do_action('usam_document_order_save', $order_id);
	} 
	
	public function update_order( )
	{					
		require_once( USAM_FILE_PATH .'/includes/document/document_discount.class.php' );
		$product_taxes = $this->get_product_taxes( );		
		foreach ( $product_taxes as $product_tax ) 
		{
			usam_update_order_product_tax( $product_tax->id, ['order_id' => $this->order_id] );			
		}			
		$discounts_ids = $this->get_cart_discounts_ids();			
		if ( !empty($discounts_ids))
		{	 
			$discount_rules = usam_get_discount_rules(['include' => $discounts_ids]);
			foreach( $discount_rules as $rule )
			{				
				usam_set_document_discount( $rule->id, ['document_id' => $this->order_id, 'document_type' => 'order']);
			}				
			$this->remove_discount( );	
		}		
	}
	
	public function get_cost_paid( )
	{	
		if ( $this->paid === null )
		{
			$this->paid = 0;
			$certificates = usam_get_basket_metadata( $this->id, 'certificates' );
			if ( $certificates )
			{
				$certificates = usam_get_coupons(['fields' => 'value', 'coupon_code' => $certificates]);
				foreach( $certificates as $value )
				{			
					$this->paid += $value;
				}
			}	
		}
		return $this->paid;
	}
	
	private function get_cart_discounts_ids( ) 
	{
		global $wpdb;
		
		$discont_ids = [];
		if ( $this->id )
		{
			$cache_key = 'cart_discounts_ids';
			$discont_ids = wp_cache_get($cache_key, $this->id );
			if( $discont_ids === false )
			{		
				$discont_ids = $wpdb->get_col( "SELECT discount_order_id FROM ".USAM_TABLE_DISCOUNT_BASKET." WHERE cart_id = '$this->id'" );		
				wp_cache_set( $this->id, $discont_ids, $cache_key );
			}	
		}
		return $discont_ids;
	}
	
	function set_rule_discount( $ids ) 
	{	
		global $wpdb;				
		foreach ( $ids as $id )	
		{				
			$sql = "INSERT INTO `".USAM_TABLE_DISCOUNT_BASKET."` (`cart_id`,`discount_order_id`) VALUES ('%d','%d') ON DUPLICATE KEY UPDATE `discount_order_id`='%d'";					
			$insert = $wpdb->query( $wpdb->prepare($sql, $this->id, $id, $id ) );	
		}
		wp_cache_set( $this->id, $ids, 'cart_discounts_ids' );
	}
	
	//Разорвать связь между скидками и корзиной, когда заказ создан
	function remove_discount( ) 
	{
		global $wpdb;
		
		if ( !$this->id )
			return false;
		
		wp_cache_delete( $this->id, 'cart_discounts_ids' );
		return $wpdb->query("DELETE FROM ".USAM_TABLE_DISCOUNT_BASKET." WHERE cart_id='$this->id'");
	}
	
	// Добавляет бонусы по купону
	public function set_bonus_account( )
	{
		if ( $this->order_id )
		{					
			$data = ['object_id' => $this->order_id, 'object_type' => 'order'];
			if ( $this->bonuses )
			{
				$data['type_transaction'] = 1;
				$data['sum'] = $this->bonuses;
				$data['description'] = __('Оплата заказа', 'usam');
				usam_insert_bonus( $data, $this->user_id );		
				$this->bonuses = 0;
			} 
			$accrue_bonuses = (int)usam_get_basket_metadata( $this->id, 'accrue_bonuses' );	
			if ( $accrue_bonuses )
			{
				$data['sum'] = $accrue_bonuses;
				$data['type'] = usam_get_bonus_type( 'buy' );
				usam_insert_bonus( $data, $this->user_id );	
			}
		}		
	}
	
// Проверить термины на условие
	private function compare_terms( $product_id, $taxonomy, $c ) 
	{				
		static $product_term_ids = null;		
		$product_id = (int)$product_id;
		if ( $product_term_ids === null )
		{
			$ids = [];		
			foreach($this->products as $key => $product) 
			{
				$ids[] = $product->product_id;
			}		
			$object_terms = wp_get_object_terms( $ids, ['usam-brands', 'usam-category', 'usam-category_sale'], ['fields' => 'all_with_object_id']);				
			$product_term_ids = [];
			foreach( $object_terms as $term ) 
			{
				$product_term_ids[$term->taxonomy][$term->object_id][] = $term->term_id;
				if ( $term->taxonomy == 'usam-category' )
				{
					$ancestors = usam_get_ancestors( $term->term_id, $term->taxonomy );
					$product_term_ids[$term->taxonomy][$term->object_id] = array_merge( $product_term_ids[$term->taxonomy][$term->object_id], $ancestors );
				}	
			}
					
		}
		$ids = isset($product_term_ids[$taxonomy][$product_id])?$product_term_ids[$taxonomy][$product_id]:[];			
		$result = false;	
		if ( is_array($c['value']) )
		{
			$compare = new USAM_Compare();
			$result = $compare->compare_arrays($c['logic'], $ids, $c['value'] );
		}
		else
		{
			$value = (int)$c['value'];					
			$compare = new USAM_Compare();	
			$result = $compare->compare_array($c['logic'], $ids, $value );				
		}		
		return $result;
	}	
	
	/**
	 * Проверка логики для всей корзины
	 */
	private function cart_compare_logic( $c ) 
	{
		$c = apply_filters( 'usam_cart_compare_logic_before', $c );
		$result = false;
		
		$key = $c['property'];	
		$compare = new USAM_Compare();
		if ( property_exists($this, $key) )
		{		
			if ( is_array($c['value']) )								
			{	
				$key = $c['property'];		
				$result = $compare->compare_arrays($c['logic'], $this->$key, $c['value'] );			
			}
			elseif ( is_string($this->$key) )
			{	
				$key = $c['property'];			
				$result = $compare->compare_string($c['logic'], $this->$key, $c['value'] );		
			}
			else								
			{	
				$key = $c['property'];	
				$result = $compare->compare_number($c['logic'], $this->$key, $c['value'] );	
			}
		}	
		elseif ($c['property'] == 'item_count_total')
		{
			$quantity = 0;
			foreach( $this->get_products() as $key => $product)
			{
				$quantity += $product->quantity;
				$result = $compare->compare_number($c['logic'], $quantity, $c['value'] );
				if ( $result )
					break;
			}	
		}
		elseif ($c['property'] == 'subtotal_without_discount')
		{
			$sum = 0;
			foreach( $this->get_products() as $key => $product)
			{
				$old_price = usam_get_product_old_price( $product->product_id, $this->type_price, false);	 
				if ( $old_price == 0 )
					$sum += $product->price;
			}	
			$result = $compare->compare_number($c['logic'], $sum, $c['value'] );		
		}		
		elseif ($c['property'] == 'locations')
		{
			$result = $compare->compare_arrays( $c['logic'], $this->location_ids, $c['value'] );
		}			
		elseif ($c['property'] == 'category' || $c['property'] == 'brands' || $c['property'] == 'category_sale' )
		{		
			$products = $this->get_products();
			foreach( $products as $key => $product)
			{
				$result = $this->compare_terms( $product->product_id, 'usam-'.$c['property'], $c );
				if ( $result )
					break;
			}
		}		
		elseif ($c['property'] == 'birthday')
		{			
			$contact_id = usam_get_contact_id();
			if ( !empty($contact_id) )
			{									
				$birthday = usam_get_contact_metadata($contact_id, 'birthday');
				if ( $birthday )
				{
					$birthday = strtotime( get_date_from_gmt($birthday, "Y-m-d H:i:s") ); 						
					$day = date('d', $birthday);
					$m = date('m', $birthday);
					if ( date('d') == $day && date('m') == $m )
						$result = true;						
				}
			} 
		}	
		elseif ($c['property'] == 'newcustomer')
		{		
			$meta_query = array();
			$properties = usam_get_properties(['type' => 'order', 'fields' => 'code', 'field_type' => ['mobile_phone', 'phone', 'email']]);		
			if ( $this->order_id == 0 )
			{								
				foreach( $properties as $code )
				{
					$value = usam_get_customer_checkout($code);
					if ( $value )
						$meta_query[] = array('key' => $code, 'compare' => '=', 'value' => $value);
				}
			}
			else
			{	foreach( $properties as $code )
				{
					$value = usam_get_order_metadata( $this->order_id, $code );
					if ( $value )
						$meta_query[] = array('key' => $code, 'compare' => '=', 'value' => $value);
				}
			}
			if ( empty($meta_query) )
				$result = true;
			else
			{ 
				$meta_query['relation'] = 'OR';
				$props = usam_get_orders(['fields' => 'id', 'meta_query' => $meta_query, 'exclude' => array( $this->order_id ), 'status' => array('closed') ]);	
				if ( empty($props) )
					$result = true;			
			}			
		}
		elseif ($c['property'] == 'customer')
		{		
			$props = usam_get_orders(['fields' => 'id', 'contacts' => array( $this->contact_id ), 'exclude' => [$this->order_id] ]);
			if ( !empty($props) )
				$result = true;	
		}		
		elseif ($c['property'] == 'weekday')
		{		
			$value = (int)$c['value'];		
			$result = $compare->compare_number($c['logic'], date("w"), $value );			
		}				
		elseif ($c['property'] == 'user')
		{	
			$value = (int)$c['value'];			
			$result = $compare->compare_number($c['logic'], $this->user_id, $value );			
		}	
		elseif ($c['property'] == 'roles')
		{						
			if ( $this->user_id > 0 )
			{
				$user = get_userdata( $this->user_id  );				
				if ( is_array($c['value']) )								
					$result = $compare->compare_arrays($c['logic'], $user->roles, $c['value'] );			
				else								
					$result = $compare->compare_array($c['logic'], $user->roles, $c['value'] );	
			}
			else
			{
				if ( is_array($c['value']) )								
					$result = $compare->compare_array( $c['logic'], 'notloggedin', $c['value'] );	
				else								
					$result = $compare->compare_number( $c['logic'], 'notloggedin', $c['value'] );
			}				
		}			
		elseif ( stristr($c['property'], 'order_property') !== false)
		{		
			$property = str_replace("order_property-", "", $c['property']);
			$value = usam_get_customer_checkout( $property );
			if ( $value )
				$result = $compare->compare_string($c['logic'], $value, $c['value'] );	
		}				
		else 
			$result = true;	
		$result = apply_filters( 'usam_cart_compare_logic_after', $result, $c );		
		return $result;		
	}
		
	public function check_condition( $conditions ) 
	{		
		$check_items_key = array();
		foreach( $this->products as $key => $product)
		{
			$check_items_key[$key] = $key;
		}	
		$return_items_key = $this->compare_logic( $conditions, $check_items_key );		
		$sum = 0;
		if ( !empty($return_items_key) ) 
		{							
			foreach( $return_items_key as $key )
			{
				$sum += $this->products[$key]->price*$this->products[$key]->quantity;			
			}
		}	
		return ['sum' => $sum, 'items' => $return_items_key];	
	}
	
	
	/**
	 * Проверяет, соответствует ли корзина логике	
	 */
	public function compare_logic( $conditions, $check_items_key ) 
	{				
		$result = true;	
		$allow_operation = true;	
		$return_items_key = $check_items_key;
		if ( !empty($conditions) )
		{
			foreach( $conditions as $c )
			{				
				if ( !isset($c['logic_operator']) )
				{
					if ( !$allow_operation )
						continue;
					
					switch( $c['type'] )
					{
						case 'products':						
							$return_items_key = $this->get_products_meet_conditions( $c['rules'], $return_items_key );							
							if ( empty($return_items_key) )
								$result = false;
							else
								$result = true;			
						break;
						case 'group':
							$return_items_key = $this->compare_logic( $c['rules'], $return_items_key );
							if ( empty($return_items_key) )
								$result = false;
							else
								$result = true;								
						break;
						case 'simple':
							$result = $this->cart_compare_logic( $c ); 
						break;
					}		
					if ( !$result )
					{	
						$return_items_key = [];			
						$allow_operation = false;
					}
				}				
				else
				{ // Если условие И, ИЛИ
					if ( $c['logic_operator'] == 'AND' )
					{ // Если и
								
					}
					else					
					{ // Если или		
						if ( $result )
						{	// Если условия истина до ближайшего оператора ИЛИ то завершить цикл
							break;
						}
						else	
						{
							$return_items_key = $check_items_key;
							$allow_operation = true;
						}
					}				
				}			
			}	
		}			
		return $return_items_key;
	}	
	
		/**
	 * Получить сумму товаров соответствующих логике
	 */
	private function get_products_meet_conditions( $conditions, $check_items_key ) 
	{		
		$product_keys = array();
		foreach( $check_items_key as $key )
		{			
			if ( $this->cart_item_conditions( $conditions, $this->products[$key] ) )
				$product_keys[] = $key;			
		}			
		return $product_keys;		
	}
	
	/**
	 * Проверить удовлетворяет ли товар соответствующих логике
	 */
	private function cart_item_conditions( $conditions, $product ) 
	{	
		$result = true;		
		$allow_operation = true; // Разрешить операцию		
		foreach( $conditions as $c )
		{			
			if ( !isset($c['logic_operator']) && $allow_operation )
			{		
				switch( $c['type'] )
				{					
					case 'group':
						$result = $this->cart_item_conditions( $c['rules'], $product );				
					break;
					case 'simple':
						$result = $this->check_condition_simple_basket_item( $c, $product );
					break;
				}				
				if ( !$result )	
					$allow_operation = false;
			}
			elseif ( isset($c['logic_operator']) )
			{ // Если условие И, ИЛИ
				if ( $c['logic_operator'] == 'AND' )
				{ // Если и
							
				}
				else					
				{ // Если или		
					if ( $result )
					{	// Если условия истина до ближайшего оператора ИЛИ то завершить цикл
						break;
					}
					else						
						$allow_operation = true;					
				}				
			}			
		}		
		return apply_filters( 'usam_cart_item_compare_logic', $result, $conditions, $product );	
	}	
	
	/**
	 * Проверить удовлетворяет ли товар соответствующих логике
	 */
	private function check_condition_simple_basket_item( $c, $product ) 
	{						
		$compare = new USAM_Compare();
		if ( $c['property'] == 'item_name' ) 
		{					
			$post_title = get_post_field( 'post_title', $product->product_id);			
			$return = $compare->compare_string($c['logic'], $post_title, $c['value'] );			
		}	
		elseif ($c['property'] == 'item_quantity') //Количество товара
		{					
			$return = $compare->compare_number($c['logic'], $product->quantity, $c['value'] );		
		} 			
		elseif ($c['property'] == 'item_price')
		{				
			$return = $compare->compare_number($c['logic'], $product->price, $c['value'] );					
		} 					
		elseif ($c['property'] == 'item_old_price')
		{						
			$old_price = usam_get_product_old_price( $product->product_id, $this->type_price, false);
			$return = $compare->compare_number($c['logic'], $old_price, $c['value'] );	
		}
		elseif ($c['property'] == 'item_sku')
		{						
			$sku = usam_get_product_meta($product->product_id, 'sku');		
			$return = $compare->compare_string($c['logic'], $sku, $c['value'] );
		}	
		elseif ($c['property'] == 'item_barcode')
		{				
			$barcode = usam_get_product_meta($product->product_id, 'barcode');
			$return = $compare->compare_string($c['logic'], $barcode, $c['value'] );
		}			
		elseif ( $c['property'] == 'category' || $c['property'] == 'brands' || $c['property'] == 'category_sale' ) 
		{		
			$return = $this->compare_terms( $product->product_id, 'usam-'.$c['property'], $c );		
		}
		else 
			$return = true;				
		return $return;	
	}		
	
// Проверить правила покупки	
	public function calculate_purchase_rules( ) 
	{
		if ( usam_is_license_type('LITE') || usam_is_license_type('FREE') )
			return true;
		
		$option = get_site_option('usam_purchase_rules');
		$rules = maybe_unserialize( $option );	
		if ( empty($rules) )
			return true;		
	
		foreach( $rules as $rule )
		{
			if ( $rule['active'] == 1 )
			{
				if ( $rule['conditions'] )
				{	
					$return = $this->check_condition( $rule['conditions'] );
					$sum = $return['sum'];
					if( $sum > 0 )
					{
						if ( $rule['description'] )						
							$this->set_error( $rule['description'], 'purchase_rules' );
						else
							$this->set_error( __('Заказ не удовлетворяет правилам покупки. Свяжитесь с менеджером сайта и он сообщит вам причину.','usam'), 'purchase_rules' );		
						return false;
					}	
				}				
			}
		}			
		return true;
	}
}


/**
 * Класс элементов корзины
 */
class USAM_Cart_Item 
{  
	private $unit_measure = '';	
	private $cart_id;	
	private $product_id;	
	private $data = null;
	
  /**
   * требуется идентификатор продукта и параметры для продукта
   */
   public function __construct( $product_id, $unit_measure, $cart_id ) 
   {				
		$this->product_id = absint($product_id);		
		$this->unit_measure = $unit_measure;
		$this->cart_id    = absint($cart_id);
	}  

	public function refresh_item( $newdata )
	{				
		$data = $this->get_data();		
		if ( empty($data) )
			return false;

		$data = (array)$data;		
		if ( $newdata['price'] != $data['price'] || $newdata['old_price'] != $data['old_price'] ) 		
			$result = $this->update( $newdata ); 			
		else
			$result = true;
		return $result;
	}	

	private function get_title( ) 
	{		
		if ( usam_is_multisite() && !is_main_site() )
		{
			$blog_id = get_current_blog_id();
			switch_to_blog( 1 );	
		}
		else
			$blog_id  = 0;
		$product = get_post( $this->product_id );	
		if ( ! $product->post_parent )
			return get_post_field( 'post_title', $this->product_id);	
		
		if ( $product->post_parent )					
			$product_attribute = wp_get_object_terms( $this->product_id, 'usam-variation', ['fields' => 'names']);	
		
		$title = get_post_field( 'post_title', $product->post_parent );			
		$vars   = implode( ', ', $product_attribute );
		$title .= ' (' . $vars . ')';
			
		$title = apply_filters( 'usam_cart_product_title', $title, $this->product_id );		
		
		if ( $blog_id )
			switch_to_blog( $blog_id );	
		
		return $title;
    }  
	
	public function get_data() 
	{		
		global $wpdb;
		
		if ( $this->data == null && $this->cart_id )
		{
			$cache_key = "usam_cart_item_{$this->product_id}_{$this->unit_measure}";
			$this->data = wp_cache_get( $this->cart_id, $cache_key );
			if( $this->data === false )
			{		
				$this->data = $wpdb->get_row("SELECT * FROM ".USAM_TABLE_PRODUCTS_BASKET." WHERE product_id='$this->product_id' AND cart_id='$this->cart_id' AND unit_measure='$this->unit_measure'");
				if ( !empty($this->data) )
				{
					$this->data->old_price = (float)$this->data->old_price;
					$this->data->price = (float)$this->data->price;
				}
				else
					$this->data = array();
				wp_cache_set( $this->cart_id, $this->data, $cache_key );				
			}				
		}		
		return $this->data;
	}
	
	public function update( $data ) 
	{	
		global $wpdb;		
			
		if ( isset($data['price']) )
			$data['price'] = (float)$data['price'];
		
		if ( isset($data['old_price']) )
			$data['old_price'] = (float)$data['old_price'];	
			
		$this->get_data();		
		$changed_data = array_diff_assoc ($data, (array)$this->data );		
		if ( empty($changed_data) )
			return true;
					
		$where = ['product_id' => $this->product_id, 'unit_measure' => $this->unit_measure, 'cart_id' => $this->cart_id];
		$where_format = $this->get_format_data( $where );	
								
		$data = apply_filters( 'usam_update_product_basket', $data, $this->product_id, $this->cart_id );		
		$this->set_data( $data );			
		if ( empty($this->data) )
			return false;		
				
		$update = (array)$this->data; 
		if ( isset($update['id']) )
			unset($update['id']);
		
		if ( isset($update['unit_measure']) )
			unset($update['unit_measure']);
		
		if ( isset($update['date_insert']) )
			unset($update['date_insert']);
		
		$insert_format = $this->get_format_data( $update );				
		$result = $wpdb->update( USAM_TABLE_PRODUCTS_BASKET, $update, $where, $insert_format, $where_format );	
		if ( $result )
		{		
			do_action( 'usam_product_basket_update', (array)$this->data, $changed_data, $this );				
			wp_cache_set( $this->cart_id, $this->data, 'usam_cart_item_'.$this->product_id.'_'.$this->unit_measure );	
		}		
		return $result;
	}
	
	public function set_data( $data )
	{
		$format = $this->get_format();		
		
		if ( empty($this->data) )
			$this->data = (object)$this->data;
		
		$update_format = [];
		foreach ( $data as $key => $value )
		{	
			if ( isset($format[$key]) )
				$this->data->$key = $value;
		}				
	}
	
	public function get_format( )
	{ 
		$format = ['id' => '%d', 'product_id' => '%d', 'cart_id' => '%d','name' => '%s', 'price' => '%f','old_price' => '%f', 'quantity' => '%f', 'used_bonuses' => '%d', 'unit_measure' => '%s', 'date_insert' => '%s', 'gift' => '%d', 'seller_id' => '%d'];			
		return $format;
	}
	
	public function get_format_data( $data )
	{
		$format = $this->get_format();		
		
		$update_format = array();
		foreach ($data as $key => $value )
		{	
			if ( isset($format[$key]) )
			{
				$update_format[] = $format[$key];
			}		
		}		
		return $update_format;
	}	
	
	public function insert( $data )
	{
		global $wpdb;
				
		$data['gift'] = !empty($data['gift']) ? 1 : 0;		
		if ( isset($data['price']) )
			$data['price'] = (float)$data['price'];
		else
			$data['price'] = 0;
		
		if ( isset($data['old_price']) )
			$data['old_price'] = (float)$data['old_price'];	
		else
			$data['old_price'] = 0;
		
		if ( !isset($data['used_bonuses']) )
			$data['used_bonuses'] = 0;		
		
		$data['name'] = $this->get_title( );			
		$data['cart_id'] = $this->cart_id;
		$data['unit_measure'] = $this->unit_measure;		
		$data['product_id'] = $this->product_id;
		$data['date_insert'] = date("Y-m-d H:i:s");		
		
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
		{
			$data['seller_id'] = usam_get_product_meta( $this->product_id, 'seller_id' );
		}					
		$data = apply_filters( 'usam_update_product_basket', $data, $this->product_id, $this->cart_id );	
		$this->set_data( $data );	
		if ( empty($this->data) )
			return false;	
				
		$data = (array)$this->data;		
		$insert_format = $this->get_format_data( $data );		
	
		$result = $wpdb->insert( USAM_TABLE_PRODUCTS_BASKET, $data, $insert_format );	
		if ( $result )
		{
			$this->data->id = $wpdb->insert_id;			
			do_action( 'usam_product_basket_insert', $data, $this );				
			wp_cache_set( $this->cart_id, (object)$this->data, 'usam_cart_item_'.$this->product_id.'_'.$this->unit_measure );	
		}
		return $wpdb->insert_id;
	}  
}

function usam_delete_cart( $args = [] ) 
{
	global $wpdb;
	
	require_once( USAM_FILE_PATH . '/includes/basket/users_basket_query.class.php' );
	$args['fields'] = 'id';
	$args['cache_meta'] = true;
	
	$ids = usam_get_users_baskets( $args );
	
	if ( empty($ids) )
		return false;
	
	if ( is_numeric($ids) )
		$ids = array( $ids );
	
	$ids = array_map( 'intval', $ids );	
		
	usam_remove_products_basket( $ids );
	
	$in = implode( ', ', $ids );	
	
	$result = $wpdb->query("DELETE FROM ".USAM_TABLE_USERS_BASKET_META." WHERE basket_id IN ($in)");		
	$result = $wpdb->query("DELETE FROM ".USAM_TABLE_DISCOUNT_BASKET." WHERE cart_id IN ($in)");	
	$result = $wpdb->query("DELETE FROM ".USAM_TABLE_USERS_BASKET." WHERE id IN ($in)");		
	return $result;
}


// Удалить товары по выбранной корзине
function usam_remove_products_basket( $cart_ids ) 
{
	global $wpdb;
		
	if ( empty($cart_ids) )
		return false;
	
	if ( is_numeric($cart_ids) )
		$cart_ids = array($cart_ids);	

	$ids = array_map( 'intval', $cart_ids );
	require_once( USAM_FILE_PATH . '/includes/basket/products_basket_query.class.php'   );
	$products = usam_get_products_baskets(['cart_id' => $ids]);
	$product_taxes = array();
	foreach( $ids as $id )
	{
		$taxes = usam_get_basket_metadata( $id, 'product_taxes' );		
		if ( !empty($taxes) )
			$product_taxes += $taxes;
		
		wp_cache_delete( $id, 'usam_cart_product_taxes' );	
	}				
	if ( !empty($product_taxes) )
		$wpdb->query( "DELETE FROM `".USAM_TABLE_TAX_PRODUCT_ORDER."` WHERE `id` IN (".implode( ",", wp_parse_id_list($product_taxes) ).") AND order_id=0" );
	
	$wpdb->query( "DELETE FROM `".USAM_TABLE_PRODUCTS_BASKET."` WHERE `cart_id` IN (".implode( ', ', $ids ).")");

	$product_ids = [];
	foreach( $products as $product )
		$product_ids[] = $product->product_id;
	usam_update_cache( $product_ids, [USAM_TABLE_POST_META => 'post_meta'], 'post_id' );
	foreach( $products as $product )
	{
		do_action( 'usam_product_basket_delete', $product );
		wp_cache_delete( $product->product_id, 'usam_post_meta' );
	}
	foreach( $ids as $id )
		do_action( 'usam_basket_delete', $id );			
}


function usam_get_basket_metadata( $basket_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('basket', $basket_id, USAM_TABLE_USERS_BASKET_META, $meta_key, $single );
}

function usam_update_basket_metadata($basket_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('basket', $basket_id, $meta_key, $meta_value, USAM_TABLE_USERS_BASKET_META, $prev_value );
}

function usam_delete_basket_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('basket', $object_id, $meta_key, USAM_TABLE_USERS_BASKET_META, $meta_value, $delete_all );
}