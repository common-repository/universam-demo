<?php
function usam_get_customer_checkout( $code, $contact_id = null )
{
	if ( $contact_id === null )
		$contact_id = usam_get_contact_id();
	return usam_get_contact_metadata($contact_id, 'checkout_'.$code );
}

function usam_get_default_customer_order_data( $property )
{
	$contact_id = usam_get_contact_id();
	$result = '';			
	switch ( $property->field_type ) 
	{
		case "location":					
			$result = usam_get_customer_location();
		break;			
		case "mobile_phone":		
			$result = usam_get_contact_metadata($contact_id, 'mobile_phone' );
		break;						
		case "email":
			$result = usam_get_contact_metadata($contact_id, 'email' );
		break;		
	}	
	switch ( $property->code )
	{					
		case "shippingfirstname":
		case "billingfirstname":				
			$result = usam_get_contact_metadata($contact_id, 'firstname' );
		break;
		case "shippinglastname":
		case "billinglastname":				
			$result = usam_get_contact_metadata($contact_id, 'lastname' );
		break;	
		case "shippingpostcode":				
			$location_id = usam_get_customer_location();
			$result = usam_get_location_metadata($location_id, 'index' );
		break;				
		case "contact_person":
			$contact_data = usam_get_contact( $contact_id );
			$result =!empty($contact_data)?$contact_data['name']:'';
		break;	
		case "company_phone":
			$select_company = usam_get_select_company_customer();
			if ( !empty($select_company) )
			{
				$result = usam_get_company_metadata($select_company, 'phone' );
			}
		break;	
		case "company_email":
			$select_company = usam_get_select_company_customer();
			if ( !empty($select_company) )
			{
				$result = usam_get_company_metadata($select_company, 'email' );
			}
		break;
	}
	$result = $result=== false? '' : $result;
	return $result;
}

function usam_update_customer_checkout( $code, $value, $contact_id = null )
{
	if ( $contact_id === null )
		$contact_id = usam_get_contact_id();
	return usam_update_contact_metadata($contact_id, 'checkout_'.$code, $value );
}

function usam_get_companies_customer( )
{	
	$user_id = get_current_user_id();	
	if ( !empty($user_id) )
		$companies = usam_get_companies(['user_id' => $user_id]);
	else
		$companies = [];	
	return $companies;
}

function usam_get_select_company_customer( $contact_id = null )
{	
	if ( !$contact_id )
	{
		$contact_id = usam_get_contact_id();
		$user_id = get_current_user_id();	
	}
	else
	{
		$contact = usam_get_contact( $contact_id );
		$user_id = $contact['user_id'];	
	}
	$select_company = 0;
	if ( $user_id )
	{
		$select_company = usam_get_contact_metadata( $contact_id, 'checkout_company_id' );		
		if ( !$select_company )
		{
			$companies = usam_get_companies(['user_id' => $user_id, 'number' => 1, 'cache_results' => true]);
			if ( !empty($companies['id']) )
				$select_company = $companies['id'];	
		}		
	}	
	return absint($select_company);
}

function usam_get_customer_location( $contact_id = null )
{		
	if ( $contact_id  )
		$current_location = usam_get_contact_metadata( $contact_id, 'location' );	
	else if ( usam_is_bot() )
	{
		$current_location = 0;
		$bot = usam_get_bot();
		$option = get_site_option('usam_search_engine_location');
		$search_engine_location = maybe_unserialize( $option );	
		if ( !empty($search_engine_location) )
		{
			foreach( $search_engine_location as $value )	
			{
				if ( $value['search_engine'] == $bot )
				{								
					$current_location = $value['location'];
					break;
				}
			}
		}
	}		
	else
	{
		$contact_id = usam_get_contact_id();
		if ( !empty($_REQUEST['locid']) )
			$current_location = absint($_REQUEST['locid']);
		else
			$current_location = usam_get_contact_metadata( $contact_id, 'location' );
		if ( $current_location )
		{
			$location_ids = usam_get_address_locations( $current_location, 'id' );
			if ( !$location_ids )
				$current_location = 0;	
			elseif ( !empty($_REQUEST['locid']) )
				usam_update_contact_metadata( $contact_id, 'location', $current_location );
		}	
		if ( !$current_location )
		{			
			$current_location = usam_get_current_user_location( );					
			if ( !empty($current_location) )
				usam_update_contact_metadata( $contact_id, 'location', $current_location );
		}
	}
	if ( !$current_location )
		$current_location = get_option( 'usam_shop_location' );		
	return absint($current_location);
}

function usam_get_customer_sales_area( )
{
	$customer_location_ids = usam_get_customer_address_locations( $contact_id = null );

	$sales_area = usam_get_sales_areas();
	$id = false;
	foreach( $sales_area as $group )
	{					
		$result = array_intersect($group['locations'], $customer_location_ids );			
		if ( !empty($result) )
		{
			$id = $group['id'];
			break;
		}
	}
	return $id;
}

function usam_in_customer_sales_area( $area_id )
{	
	if ( empty($area_id) )
		return true;	
	
	$customer_sales_area = usam_get_customer_sales_area( );
	if ( $area_id == $customer_sales_area )
		return true;
	else
		return false;	
}

function usam_get_type_payer_customer( )
{	
	$contact_id = usam_get_contact_id();
	$type_payer = (int)usam_get_contact_metadata( $contact_id, 'type_payer' );
				
	$types_payers = usam_get_group_payers();	
	$result = false;
	if ( !empty($type_payer) )
	{		
		foreach ( $types_payers as $value )
		{			
			if ( $value['id'] === $type_payer )
			{			
				$result = true;
				break;
			}
		}
	}
	if ( !$result && !empty($types_payers[0]) )
		$type_payer = $types_payers[0]['id'];		
	return $type_payer;
}

// Получить все вложения местоположения пользователя
function usam_get_customer_address_locations( $contact_id = null )
{	
	$current_location = usam_get_customer_location( $contact_id );
	if ( $current_location )
		return array_values(usam_get_address_locations( $current_location, 'id' ));	
	else
		return [];	
}

function usam_get_customer_price_code( $contact_id = null )
{
	static $codes = [];	
	if ( !$contact_id )
		$contact_id = usam_get_contact_id();
	
	if( empty($codes[$contact_id]) )
	{
		$current_location_ids = usam_get_customer_address_locations( $contact_id );			
		$user = wp_get_current_user();
		if ( empty($user->roles) )
			$roles = array('notloggedin');
		else
		{			
			if ( !empty($user->ID) && !is_admin() && is_user_logged_in() )
			{			
				$select_company = usam_get_select_company_customer( $contact_id );					
				$code = usam_get_company_metadata($select_company ,'type_price');
				if ( $code )
				{ 
					$type_price = usam_get_setting_price_by_code( $code );					
					if ( !empty($type_price) )
						return $code;
				}
				$code = usam_get_contact_metadata($contact_id, 'type_price');
				if ( $code )
				{
					$type_price = usam_get_setting_price_by_code( $code );
					if ( !empty($type_price) )
						return $code;
				}
			}	
			$roles = $user->roles;		
		}
		$type_prices = usam_get_prices(['available' => 1, 'type' => 'R']); 
		$code = ''; 		
		foreach ( $type_prices as $id => $value )
		{					
			if ( !empty($value['roles']) )
			{					
				$result = array_intersect($roles, $value['roles'] );
				if ( empty($result) )
					continue;			
			}				
			if ( !empty($value['locations']) )
			{				
				$result = array_intersect( $value['locations'], $current_location_ids );
				if ( empty($result) )
					continue;		
			}
			$code = $value['code'];		
			break;			
		}		
		$codes[$contact_id] = $code;		
	}
	return $codes[$contact_id];
}


function usam_get_customer_balance_code()
{
	static $code = false;	
	if( $code === false )
	{
		$contact_id = usam_get_contact_id();
		$favorite_shop = usam_get_contact_metadata($contact_id, 'favorite_shop');	
		if ( $favorite_shop )
		{
			$storage = usam_get_storage( $favorite_shop );
			if ( !empty($storage['issuing']) )
				$code = 'storage_'.$favorite_shop;
		}
		if ( !$code )
		{
			$customer_sales_area = usam_get_customer_sales_area( );			
			if ( $customer_sales_area )
				$code = 'stock_'.$customer_sales_area;
			else
				$code = 'stock';
		}
	}	
	return $code;
}

function usam_get_user_select_calendars( )
{
	$user_id = get_current_user_id();
	$user_calendars = get_the_author_meta('usam_calendars', $user_id);
	if (empty($user_calendars))
		$user_calendars = array();
	return $user_calendars;
}

function usam_get_calendars( $args = [] )
{
	$args = array_merge(['type' => 'user'], $args );	
	$user_id = get_current_user_id();
	
	$option = get_site_option('usam_calendars');
	$calendars = maybe_unserialize( $option );

	$user_calendars = array();
	if ( !empty($calendars) )
		foreach( $calendars as $key => $item )
		{				
			if ( $item['user_id'] == $user_id && ($args['type'] == 'user' || $args['type'] == 'all') )
				$user_calendars[] = $item;
			elseif ( $item['user_id'] == 0 && ($args['type'] == 'system' || $args['type'] == 'all') )
				$user_calendars[] = $item;
		}	

	if ( $args['type'] == 'user' && empty($user_calendars) )
	{
		$default_user_calendars = ['name' => __('Календарь по умолчанию', 'usam'), 'user_id' => $user_id, 'sort' => 1, 'type' => 'user'];	
		$user_calendars[] = usam_insert_user_calendars( $default_user_calendars );			
	}
	return $user_calendars;
}

// Получить название календаря по id
function usam_get_calendar_name_by_id( $id )
{
	$option = get_site_option('usam_calendars');
	$calendars = maybe_unserialize( $option );
	if ( !empty($calendars) )
		foreach( $calendars as $key => $calendar )
		{	
			if ( $calendar['id'] == $id )
				return $calendar['name'];
		}	
	return false;
}


// Получить номер системного календаря
function usam_get_id_system_calendar( $type = 'order' )
{	
	$option = get_site_option('usam_calendars');
	$calendars = maybe_unserialize( $option );
	
	if ( !empty($calendars) )
		foreach( $calendars as $key => $item )
		{	
			if ( $item['type'] == $type )
			{
				return $item['id'];
			}
		}	
	return false;
}

// Добавить календарь
function usam_insert_user_calendars( $user_calendar )
{	
	$option = get_site_option('usam_calendars', array() );						
	$calendares = maybe_unserialize($option);		
								
	$user_calendar['user_id'] = get_current_user_id();
	if ( empty($calendares) )				
		$calendares[1] = $user_calendar;					
	else
		$calendares[] = $user_calendar;	
	
	end($calendares);
	$key = key($calendares);
	
	$calendares[$key]['id'] = $key;			
	update_site_option('usam_calendars', serialize($calendares) );	
	
	return $calendares[$key];
}

// Получить списки текущего пользователя
function usam_get_currentuser_list()
{	
	$user_id = get_current_user_id();
	$user_info = get_userdata( $user_id );
	
	if ( empty($user_info->user_email) )
		return array();	
	
	$results = array();
	$lists = usam_get_subscriber_list( $user_info->user_email );
	foreach ( $lists as $list )
		$results[] = $list->list;
	return $results;
}

// Удалить подписку пользователя
function usam_delete_user_lists( $lists, $user_id = null )
{					
	if ( $user_id == null )	
		$user_id = get_current_user_id();
	$user_info = get_userdata( $user_id );
	
	if ( empty($user_info->user_email) )
		return false;
	
	$args = [];
	foreach ( $lists as $list_id )
		$args = ['communication' => $user_info->user_email, 'id' => $list_id];
	if ( $args )
		usam_delete_subscriber_lists( $args );	
}

// Удалить товар из списка клиента
function usam_delete_post_from_customer_list( $product_id, $list_name, $contact_id = null )
{		
	global $wpdb;	
	if ( $contact_id !== null )
		$query = $wpdb->prepare("DELETE FROM ".USAM_TABLE_USER_POSTS." WHERE product_id='%d' AND user_list='%s' AND contact_id='%d'", $product_id, $list_name, $contact_id );
	else
		$query = $wpdb->prepare("DELETE FROM ".USAM_TABLE_USER_POSTS." WHERE product_id='%d' AND user_list='%s'", $product_id, $list_name );
	
	$result = $wpdb->query( $query );
	do_action( 'usam_user_post_delete', ['product_id' => $product_id, 'user_list' => $list_name, 'contact_id' => $contact_id] );
}

function usam_check_current_user_role( $role, $user_id = null ) 
{
	if ( !is_user_logged_in() )
	{
		return $role == 'notloggedin';
	}	
	$user = is_numeric($user_id) ? get_userdata($user_id) : wp_get_current_user();

	if( empty($user->ID) )
		return false;

	if ( is_array($role) )
		return !empty(array_intersect($role, (array)$user->roles));
	else
		return in_array($role, (array)$user->roles);
}

function usam_get_customer_primary_mailbox_id( )
{		
	$user_id = get_current_user_id();
	$mailbox_id = get_the_author_meta('usam_email_default', $user_id);
	if ( empty($mailbox_id) )
	{
		$mailbox = usam_get_primary_mailbox();
		$mailbox_id = $mailbox['id'];	
	}
	return $mailbox_id;
}


// Проверяет есть ли товар в списке
function usam_checks_product_from_customer_list( $list_name, $product_id = null )
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$product_ids = usam_get_product_ids_in_user_list( $list_name );		
	if ( !empty($product_ids) && in_array($product_id, $product_ids) )
		return true;
	else
		return false;
}

function usam_get_product_ids_in_user_list( $user_list )
{
	$cache_key = "user_list";
	$product_ids = wp_cache_get($cache_key, $user_list );
	if( $product_ids === false )
	{				
		$product_ids = array();
		$contact_id = usam_get_contact_id();
		if ( $contact_id )
		{ 
			require_once(USAM_FILE_PATH.'/includes/customer/user_posts_query.class.php');
			$user_products = usam_get_user_posts(['contact_id' => $contact_id]);			
			$user_product_ids = array();
			foreach ( $user_products as $product )	
			{
				$user_product_ids[$product->user_list][] = $product->product_id;
			}
			if ( !isset($user_product_ids['compare']) )		
				$user_product_ids['compare'] = array();
			
			if ( !isset($user_product_ids['desired']) )		
				$user_product_ids['desired'] = array();	

			if ( !isset($user_product_ids['like']) )		
				$user_product_ids['like'] = array();			
			
			foreach ( $user_product_ids as $list => $ids )	
			{ 
				wp_cache_set( $cache_key, $ids, $list );			
			}					
			if ( isset($user_product_ids[$user_list]) )
				$product_ids = $user_product_ids[$user_list];
		}		
	}	 
	return $product_ids;
}
	
// Возвращает количество товара в сравнении
function usam_get_quantity_products_compare( )
{	
	$contact_id = usam_get_contact_id();
	return usam_get_contact_metadata($contact_id, 'compare');
}	

// Возвращает количество товара в избранном
function usam_get_quantity_products_desired( )
{
	$contact_id = usam_get_contact_id();
	return usam_get_contact_metadata($contact_id, 'desired');	
}	

function usam_get_customer_name( $user_id = null )
{
	if ( empty($user_id) )
		$user_id = get_current_user_id();
	
	if ( $user_id == 0 )
		return __('Гость','usam');
	
	$contact = usam_get_contact( $user_id, 'user_id' );		
	if ( !empty($contact['appeal']) )
	{
		return $contact['appeal'];		
	}	
	else
	{  
		$user = get_user_by('id', $user_id );
		return $user->display_name;
	}
}	

function usam_get_accumulative_discount_customer( $type = 'price', $customer_id = null ) 
{		                        
	$type_price = usam_get_customer_price_code();
	
	if ( empty($customer_id) )
		$customer_id = get_current_user_id();
			 
	$option = get_site_option('usam_accumulative_discount', '');		
	$rules = maybe_unserialize( $option );				
	$applied_rules = array();
	$discount = 0;
	if ( !empty($rules) )
	{						
		foreach ( $rules as $rule )
		{										
			if ( usam_validate_rule( $rule ) && $rule['method'] == $type && ( in_array($type_price, $rule['type_prices']) ) )
			{ 						
				$args = array('status' => 'closed', 'user_id' => $customer_id, 'type_price' => $rule['type_prices'] );						
				if ( $rule['period'] == 'd' )
				{ // За период								
					$date_from = strtotime($rule['start_calculation_date']);		
					$date_to = strtotime($rule['end_calculation_date']);	
					$args['date_query'] = array(
						'after'     => ['year'  => date('Y',$date_from), 'month' => date('m',$date_from), 'day' => date('d',$date_from)],
						'before'    => ['year'  => date('Y',$date_to),   'month' => date('m',$date_to),   'day' => date('d',$date_to) ],
						'inclusive' => true,
					);
				}
				elseif ( $rule['period'] == 'p' )
				{
					//Последнее несколько дней, месяцев, лет							
					$date = new DateTime();									
					if ( $rule['period_from_type'] == 'y' )
					{
						$date->modify('-'.$rule['period_from'].' year');	
					}
					elseif ( $rule['period_from_type'] == 'm' )
					{
						$date->modify('-'.$rule['period_from'].' month');	
					}
					else
					{								
						$date->modify('-'.$rule['period_from'].' day');	
					}
					$args['date_query'] = array( 'after' => $date->format('Y-m-d H:i:s'), 'inclusive' => true );
				}						
				$args['count_total'] = false;
				$orders = new USAM_Orders_Query( $args );				
				$total_sum = $orders->get_total_amount(); 
				if ( !$total_sum )
					continue;	
			
				foreach ($rule['layers'] as $layer)	
				{
					if ( $total_sum < $layer['sum'] )
						break;
					else													
						$discount = $layer['discount'];
				}			
			}	
		}
	} 
	return $discount;
}

/**
 * Проверка доступности прайс-листа
 */
function usam_availability_check_price_list( )
{
	require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
	require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
	$user_id = get_current_user_id();
	$rules = usam_get_exchange_rules(['type' => 'pricelist', 'schedule' => 1]);	
	$user = get_userdata( $user_id );		
	if ( !empty($rules) && !empty($user) )
	{				
		foreach($rules as $rule)	
		{		
			$roles = usam_get_exchange_rule_metadata( $rule->id, 'roles' );
			if ( empty($roles) )
				return true;
			
			$result = array_intersect($roles, $user->roles);
			if ( !empty($result) )
				return true;
		}
	}
	return false;
}

function usam_save_cookie_contact_id( $contact_id ) 
{ 
	if ( empty($contact_id) )
		return false;

	$contact = usam_get_contact( $contact_id );		
	if ( empty($contact['secret_key']) )
		return false;		
			
	if ( empty($_COOKIE[USAM_CUSTOMER_COOKIE]) || $_COOKIE[USAM_CUSTOMER_COOKIE] != $contact['secret_key'] )
		setcookie( USAM_CUSTOMER_COOKIE, $contact['secret_key'], USAM_CUSTOMER_DATA_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN );
	$_COOKIE[USAM_CUSTOMER_COOKIE] = $contact['secret_key'];
}

function usam_get_contact_id()
{				
	static $contact_id = null;
	if ( usam_is_bot() )
		return 0;		
	
	if ( $contact_id !== null )
		return $contact_id;			
	if ( !function_exists('is_user_logged_in') )	
		return $contact_id;	
	$cookie_contact_id = !empty($_COOKIE[USAM_CUSTOMER_COOKIE]) ? sanitize_text_field($_COOKIE[USAM_CUSTOMER_COOKIE]) : 0; 	
	if( is_user_logged_in() )
	{
		$user_id = get_current_user_id(); 
		$contact = usam_get_contact( $user_id, 'user_id' );		
		if ( !empty($contact['id']) )
			$contact_id = $contact['id'];
		else
		{
			$user_info = get_userdata( $user_id );		
			$data = ['user_id' => $user_id, 'online' => date("Y-m-d H:i:s")];	
			foreach(['lastname' => 'last_name', 'firstname' => 'first_name', 'email' => 'user_email'] as $usam_key => $wordpress_key )
			{
				if( !empty($user_info->data->$wordpress_key) )
					$data[$usam_key] = $user_info->data->$wordpress_key;
				else
				{
					$value = get_user_meta( $user_id, $wordpress_key, true );
					if( $value )
						$data[$usam_key] = $value;				
				}
			}
			$data['contact_source'] = user_can($user_id, 'manage_options') ? 'employee': 'register';							
			if ( $contact_id )
				usam_combine_contact( $contact_id, $data );
			else
			{
				$location_id = usam_get_current_user_location();
				$contact = new USAM_Contact( $data );	
				$contact->save();
				$contact_id = $contact->get('id');	
				$metas = [];
				if ( !empty($location_id) )
					$metas['location'] = $location_id;				
				usam_update_contact_metas($contact_id, $metas);
			}
		}	
	}
	elseif( $cookie_contact_id )
	{	
		$contact = usam_get_contact( $cookie_contact_id, 'secret_key' );		
		global $wpdb;
		if ( $wpdb->last_error ) 
		{ 	
			$contact_id = null;
			return 0;
		}
		if ( !empty($contact) )
			$contact_id = $cookie_contact_id = $contact['id'];
	}	
/*	if ( empty($contact_id) )
	{		
		$visit_id = usam_get_contact_visit_id();
		if ( $visit_id ) 
		{ 
			$visit = usam_get_visit( $visit_id );		
			$visit_contact_id = !empty($visit['contact_id'])?(int)$visit['contact_id']:0;
			if ( $visit_contact_id )
			{
				$contact = usam_get_contact( $visit_contact_id );
				if ( !empty($contact) && $contact['status'] == 'temporary')
					$contact_id = $visit_contact_id;
			}
		}				
	}  	*/
	if ( empty($contact_id) )
	{
		$contact_id = null;
		return 0;
	}
	else
		return (int)$contact_id;
}

function usam_get_contact_visit_id()
{
	static $user_view_id = null;
	if ( usam_is_bot() || is_feed() )
		return 0;
	if( $user_view_id === null )
	{		
		if( empty($_COOKIE[USAM_VISIT_COOKIE]) )
		{
			$ip = ip2long($_SERVER['REMOTE_ADDR']);
			if ( !empty($ip) ) 
			{   //Получить контакт по IP но не более 2 часов с момента последнего визита				
				$user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '-';
				$visit = usam_get_visits(['ip' => $ip, 'contact_id__not_in' => 0, 'number' => 1, 'order' => 'DESC', 'orderby' => 'date_insert', 'date_query' => ['after' => '2 hours ago'], 'cache_results' => true]);			
				$user_view_id = !empty($visit['id'])?(int)$visit['id']:0;				
			}
			if( !$user_view_id )
				$user_view_id = usam_new_visit();
			
			if ( empty($_COOKIE[USAM_VISIT_COOKIE]) || $_COOKIE[USAM_VISIT_COOKIE] != $user_view_id )
				setcookie( USAM_VISIT_COOKIE, $user_view_id, time() + 86400, COOKIEPATH, COOKIE_DOMAIN );
			$_COOKIE[USAM_VISIT_COOKIE] = $user_view_id;
		}
		else
			$user_view_id = (int)$_COOKIE[USAM_VISIT_COOKIE];
	}		
	return $user_view_id;
}

function usam_get_contact_language()
{
	if ( usam_is_multisite() )
		$lang = get_option( 'usam_language' );
	else
	{
		$languages = usam_get_languages();
		$lang = '';
		if ( !empty($languages) )
		{
			$cookie_key = 'usamlang';			
			if( isset($_COOKIE[$cookie_key]) )
				$lang = $_COOKIE[$cookie_key];
			
			if ( empty($lang) )
			{	
				foreach( $languages as $language )
				{
					$lang = $language['code'];				
					break;
				}
			}
		}
	}	
	return $lang;
}

function usam_get_languages( $args = array() )
{	
	$orderby = !empty($args['orderby'])?$args['orderby']:'sort';		
	$order = isset($args['order'])&&$args['order']=='ASC'?'ASC':'DESC';	
	
	$languages = maybe_unserialize(get_site_option('usam_languages'));
	if ( !empty($languages) )
	{
		$comparison = new USAM_Comparison_Array( $orderby, $order );
		usort( $languages, array( $comparison, 'compare' ) );	
	}
	else
		$languages = array();
		
	return $languages;
}

function usam_get_phones( $args = array() )
{	
	$orderby = !empty($args['orderby'])?$args['orderby']:'sort';		
	$order = isset($args['order'])&&$args['order']=='ASC'?'ASC':'DESC';	
	
	$phones = maybe_unserialize(get_site_option('usam_phones'));
	if ( !empty($phones) && is_array($phones) )
	{ 
		if ( !empty($args) )
		{
			foreach ( $phones as $key => $item )
			{
				if ( !empty($args['include']) && !in_array($item['id'], $args['include']) )
					unset($phones[$key]);			
				elseif ( !empty($args['search']) && stripos($item['name'], $args['search'])=== false )
					unset($phones[$key]);	
				elseif ( isset($args['active']) && $args['active'] != $item['active'] )
					unset($phones[$key]);			
			}
		}		
		if ( $phones )
		{
			$comparison = new USAM_Comparison_Array( $orderby, $order );
			usort( $phones, [$comparison, 'compare'] );	
		}
	}
	else
		$phones = [];		
	return $phones;
}

function usam_update_view()
{	
	if ( !empty($_REQUEST['view_type']) )
	{
		$view_type = sanitize_text_field($_REQUEST['view_type']);
		$views = get_option('usam_product_views', ['grid', 'list']);
		if ( !in_array($view_type, $views) )
			$view_type = get_option('usam_product_view', 'grid');
		$contact_id = usam_get_contact_id();
		usam_update_contact_metadata($contact_id, 'catalog_view', $view_type);	
	}
}

function usam_get_active_catalog( ) 
{ 
	$default_catalog = (int)get_option('usam_default_catalog', 0);	
	if ( !$default_catalog )
		return false;
		
	global $wp_query;
	if ( !empty($wp_query->query['usam-catalog']))
		return get_term_by('slug', $wp_query->query['usam-catalog'], 'usam-catalog');
	
	$cookie_key = 'usamcatalogid';
	if ( isset($_COOKIE[$cookie_key]) )
	{
		$catalog = sanitize_title($_COOKIE[$cookie_key]);
		$term = get_term_by('slug', $catalog, 'usam-catalog');	
	}
	else
		$term = get_term( $default_catalog, 'usam-catalog' );
	if ( !is_wp_error($term) ) 
		return $term;
	return false;
}

function usam_get_user_product_sorting_options()
{	
	static $attributes = null;
	$sorting_options = usam_get_product_sorting_options();
	$options = get_option( 'usam_sorting_options', ['name', 'price', 'popularity', 'date']);	
	foreach( $sorting_options as $key => $value )
	{
		if( !in_array($key, $options) )
			unset($sorting_options[$key]);
	}		
	if ( $attributes === null )
		$attributes = get_terms(['hide_empty' => 0, 'orderby' => 'name', 'usam_meta_query' => [['key' => 'sorting_products','value' => 1, 'compare' => '=']], 'taxonomy' => 'usam-product_attributes']);	
	foreach ( $attributes as $attribute ) 
	{
		$sorting_options[$attribute->slug] = $attribute->name; 
	}
	return $sorting_options;
}

function usam_get_customer_orderby( $format = 'string' )
{	
	if ( !empty($_GET['orderby']) )
	{
		$orderby = sanitize_title($_GET['orderby']);
		$order = isset($_GET['order'])?sanitize_title($_GET['order']):'desc';		
		$sort = $orderby.'-'.$order;
	}
	else
	{			
		global $wp_query;	
		$sort = usam_get_default_catalog_sort( $wp_query->query_vars );
	}	
	if ( $format == 'string' )
		return $sort;
	else
	{
		$sort = explode('-', $sort);
		return ['orderby' => $sort[0], 'order' => $sort[1] ];
	}
}

function usam_get_default_catalog_sort( $query_vars, $format = 'string' )
{
	$sort = get_option('usam_product_sort_by', 'date-desc'); 
	if ( isset($query_vars['pagename']) && $query_vars['pagename'] == 'new-arrivals' )	//Если страница новинки
		$sort = 'date-desc';
	else
	{		
		$slug = '';	
		$taxonomies = get_taxonomies(['object_type' => ['usam-product'], 'public' => true], 'objects');
		foreach ( $taxonomies as $taxonomy => $value )
		{
			if ( !empty($query_vars[$taxonomy]) )
			{
				$slug = $query_vars[$taxonomy];
				break;
			}
		}		
		if ( $slug )
		{
			$term = get_term_by('slug', $slug, $taxonomy);		
			if( !empty($term->term_id) )
			{
				$term_orderby = usam_get_term_metadata($term->term_id, 'product_sort_by');	
				if ( $term_orderby )
				{
					$options = usam_get_user_product_sorting_options();				
					if ( isset($options[$term_orderby]) )
						$sort = $term_orderby;						
				}
			}
		}
	}			
	if ( $format == 'string' )
		return $sort;
	else
	{
		$sort = explode('-', $sort);			
		$query_vars['orderby'] = $sort[0];
		$query_vars['order'] = isset($sort[1])?$sort[1]:'desc';
		return $query_vars;
	}
}

function usam_show_referral_menu() 
{	
	$user_id = get_current_user_id();
	if ( $user_id )
	{
		if ( get_site_option( 'usam_uses_coupons', 1 ) )
		{
			require_once( USAM_FILE_PATH . '/includes/basket/coupons_query.class.php' );		
			$coupons = usam_get_coupons(['user_id' => $user_id, 'coupon_type' => 'referral', 'number' => 1]);	
			if ( !empty($coupons) )
				return true;
		}
		require_once( USAM_FILE_PATH . '/includes/customer/user_referrals_query.class.php' );	
		$referral = usam_get_user_referrals(['user_id' => $user_id, 'number' => 1, 'status' => 'active']);	
		if ( !empty($referral) )
			return true;
	}
	return false;
}

function usam_get_menu_your_account() 
{
	static $menu = null;
	if( $menu === null )
	{
		$current_user = wp_get_current_user();	
		$templates = array();
		foreach ( $current_user->roles as $role )
			$templates[] = usam_get_template_file_path("menu-your-account-".$role);		
		$templates[] = usam_get_template_file_path("menu-your-account");
		
		$menu_group = [];
		$tabs = [];
		foreach ( $templates as $template )
		{
			if ( file_exists( $template ) )
			{
				include( $template );			
				break;
			}
		}		
		$menu = apply_filters( 'usam_your_account_sub_menu', $tabs );
	}
	return $menu;
}

function usam_get_id_seller( $user_id = null ) 
{
	global $wpdb; 
	if ( $user_id == null )
		$user_id = get_current_user_id();	
		
	if( user_can( $user_id, 'seller_company' ) )
		$seller_id = (int)$wpdb->get_var("SELECT s.id FROM `".USAM_TABLE_SELLERS."` AS s INNER JOIN `".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS."` AS pa ON (pa.company_id=s.customer_id AND pa.user_id={$user_id}) WHERE s.seller_type='company' LIMIT 1");
	else			
		$seller_id = (int)$wpdb->get_var("SELECT s.id FROM `".USAM_TABLE_SELLERS."` AS s INNER JOIN `".USAM_TABLE_CONTACTS."` AS c ON (c.id=s.customer_id AND c.user_id={$user_id}) WHERE s.seller_type='contact' LIMIT 1");
	return $seller_id;
}

// Проверяет есть ли продавец в списке
function usam_checks_seller_from_customer_list( $list_name, $seller_id )
{	
	$ids = usam_get_seller_ids_in_user_list( $list_name );			
	if ( !empty($ids) && in_array($seller_id, $ids) )
		return true;
	else
		return false;
}

function usam_get_seller_ids_in_user_list( $user_list )
{
	$cache_key = "seller_list";
	$user_sellers = wp_cache_get( $cache_key );
	if( $user_sellers === false )
	{				
		$seller_ids = array();
		$contact_id = usam_get_contact_id();
		if ( $contact_id )
		{ 
			require_once(USAM_FILE_PATH.'/includes/customer/user_sellers_query.class.php');
			$user_sellers = usam_get_user_sellers(['contact_id' => $contact_id]);	
			wp_cache_set( $cache_key, $user_sellers );
		}
		else
			return [];
	}	 	
	$user_seller_ids = array();
	foreach ( $user_sellers as $user_seller )	
	{
		$user_seller_ids[$user_seller->user_list][] = $user_seller->seller_id;
	}		
	return isset($user_seller_ids[$user_list])?$user_seller_ids[$user_list]:[];
}

function usam_get_number_products_page_customer()
{
	$per_page = get_option('usam_products_per_page', 24);
	return (int)!empty($_COOKIE['number_products'])?$_COOKIE['number_products']:$per_page;
}
?>