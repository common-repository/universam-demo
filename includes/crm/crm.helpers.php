<?php
new USAM_CRM_Helpers(  );
final class USAM_CRM_Helpers
{	
	private $wordpress_keys = ['lastname' => 'last_name', 'firstname' => 'first_name', 'email' => 'user_email'];
	function __construct( )
	{	
		add_action( 'usam_add_order_customerdata', [$this, 'order_processing_customer_data'], 10, 2 );
		add_action( 'usam_add_lead_customerdata', [$this, 'lead_processing_customer_data'], 10, 2 );
		add_action( 'user_register',  [$this, 'register'], 1, 2 );		
		add_action( 'usam_register', [$this, 'usam_register'], 10, 4 );		
		add_action( 'deleted_user', [$this, 'deleted_user'], 10, 3 );
		add_action( 'usam_updated_contact_meta', [$this, 'usam_updated_contact_meta'], 10, 3 );	
	}
	
	public function usam_updated_contact_meta( $object_id, $meta_key, $meta_value )
	{
		if ( $meta_key == 'email' || $meta_key == 'mobilephone' )
		{
			$contact = usam_get_contact( $object_id );
			if ( !empty($contact['user_id']) )
			{
				$userdata = [];
				foreach($this->wordpress_keys as $usam_key => $wordpress_key )
					if( $meta_key == $usam_key )
						$userdata[$wordpress_key] = $meta_value;	
				if( $userdata )
				{
					$userdata['ID'] = $contact['user_id'];
					wp_update_user( $userdata );
				}
				if ( $meta_key == 'mobilephone' )					
					update_user_meta( $contact['user_id'], 'user_management_phone', $meta_value );
			}			
		}			
	}
	
	public function register( $user_id, $userdata )
	{
		$data = ['user_id' => $user_id];	
		foreach($this->wordpress_keys as $usam_key => $wordpress_key )
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
		$data['online'] = date("Y-m-d H:i:s");				
		$data['status'] = 'customer';
		$contact = usam_get_contact();	
		if ( !empty($contact['user_id']) || $contact['status'] != 'temporary' )		
		{ //Если у контакта существует личный кабинет, то создать новый. (Например, если администратор регистрирует в админке)					
			$contact_id = usam_insert_contact( $data );	
		}
		else
		{
			$contact_id = $contact['id'];
			usam_update_contact( $contact_id, $data);				
		}
	}
	
	public function usam_register( $user_id, $user, $data, $confirm )
	{		
		if( !empty($data['company']) )
		{			
			$insert = [];
			if ( user_can( $user_id, 'seller_company' ) )
				$insert['type'] = 'seller';
			
			$company_id = usam_insert_company( $insert, $data['company'] );
			usam_add_company_personal_account( $company_id, $user_id, false );
			$data['company_id'] = $company_id;
			if ( user_can( $user_id, 'seller_company' ) )
				usam_insert_seller(['seller_type' => 'company', 'customer_id' => $company_id]);
			unset($data['company']);
		}		
		$contact = usam_get_contact( $user_id, 'user_id' );
		if( !empty($contact['id']) )
		{						
			if( !empty($data['full_name']) )
				$data['full_name'] = sanitize_text_field(stripcslashes($data['full_name']));	
			$data['status'] = 'customer';
			usam_update_contact( $contact['id'], $data );			
			if ( user_can( $user_id, 'seller_contact' ) )
				usam_insert_seller(['seller_type' => 'contact', 'customer_id' => $contact['id']]);			
		}		
		$option = get_option('usam_bonus_rules', [] );
		if( !empty($option['generate_cards']) )
		{
			$code = usam_generate_bonus_card();
			usam_insert_bonus_card(['status' => 'active', 'user_id' => $user_id, 'code' => $code]);	
		}
	}
	
	public function order_processing_customer_data( $document_id, $customer_data )
	{					
		$this->order_customerdata( $document_id, $customer_data );
		$this->adding_coordinates( $document_id, $customer_data );
	}
		
	public function adding_coordinates( $document_id, $customer_data )
	{ 
		$property_types = usam_get_order_property_types( $document_id );
		if( !empty($property_types['delivery_address']) ) 
		{ 
			$coordinates = apply_filters( 'usam_upload_coordinates', [], $property_types['delivery_address']['full_address'] );
			if ( !empty($coordinates['geo_lat']) )
			{
				usam_update_order_metadata( $document_id, 'latitude', $coordinates['geo_lat'] );
				usam_update_order_metadata( $document_id, 'longitude', $coordinates['geo_lon'] );
			}
		}
	}
	
	public function order_customerdata( $document_id, $customer_data )
	{					
		$empty = true;
		foreach( $customer_data as $data ) 
		{
			if( !empty($data) )
			{
				$empty = false;
				break;
			}
		}
		if ( $empty )
		{
			usam_update_order( $document_id, ['company_id' => 0, 'contact_id' => 0]);	
			return;
		}		
		$order = usam_get_order( $document_id );
		if ( $order['user_ID'] )
			$contact = usam_get_contact( $order['user_ID'], 'user_id' );		
		
		$metas = usam_get_CRM_data_from_webform( $customer_data );
		if ( usam_is_type_payer_company( $order['type_payer'] ) ) 
		{	
			if ( !empty($contact) )
				usam_update_order( $document_id, ['contact_id' => $contact['id']]);	
			
			if ( !empty($metas['company']['company_name']) )
				$company['name'] = $metas['company']['company_name'];
			elseif ( !empty($metas['company']['full_company_name']) )
				$company['name'] = $metas['company']['full_company_name'];
			else
				$company['name'] = sprintf(__('Добавлена из заказа %s','usam'), $document_id);			
			$company['type'] = 'customer';		
			$company_id = $order['company_id'];	
			if ( $company_id )
			{
				$order_company = usam_get_company( $company_id );
				if ( !$order_company )
					$company_id = 0;
			}
			if ( empty($metas['company']['inn']) )
			{
				$link_id = apply_filters( 'usam_link_company_to_order', null, $order, $company, $metas );
				if ( !$link_id )
				{				
					$found_company = usam_get_companies(['name' => $company['name'], 'number' => 1, 'cache_results' => true]);
					if ( $found_company )
						$company_id = $found_company['id'];
				}
				else
					$company_id = $link_id;				
				if ( $company_id )
					usam_update_company_metas( $company_id, $metas['company'] );
			}	
			else
				$company_id = 0; // Чтобы проверить по ИНН
			if ( !$company_id )
				$company_id = usam_insert_company( $company, $metas['company'] );		
			if ( $order['company_id'] != $company_id )
				usam_update_order( $document_id, ['company_id' => $company_id]);
		}
		else
		{								
			$new_contact = $metas['contact'];	
			$new_contact['contact_source'] = 'order';			
			$new_contact['user_id'] = $order['user_ID'];						
			$contact_id = 0;
			if ( !empty($contact) )
			{
				$contact_id = $contact['id'];					
				usam_combine_contact( $contact['id'], $new_contact );
				if ( $order['contact_id'] != $contact_id )
					usam_update_order( $document_id, ['contact_id' => $contact_id]);	
				return;
			}			
			if ( $order['contact_id'] )
			{
				$contact = usam_get_contact( $order['contact_id'] );
				if ( empty($contact['firstname']) || empty($contact['lastname']) || !empty($new_contact['firstname']) && mb_strtolower($new_contact['firstname']) == mb_strtolower($contact['firstname']) || !empty($new_contact['lastname']) && mb_strtolower($new_contact['lastname']) == mb_strtolower($contact['lastname']) )
				{
					usam_combine_contact( $order['contact_id'], $new_contact );
					$contact_id  = $order['contact_id'];				
				}
			}
			if ( $contact_id == 0 )
			{
				$contact_id = usam_insert_contact( $new_contact );						
				usam_save_cookie_contact_id( $contact_id );
				usam_update_order( $document_id, ['contact_id' => $contact_id]);	
			}			
		}	
	}
	
	public function lead_processing_customer_data( $document_id, $customer_data )
	{					
		$this->lead_customerdata( $document_id, $customer_data );
	}
	
	public function lead_customerdata( $document_id, $customer_data )
	{						
		$empty = true;
		foreach ( $customer_data as $data ) 
		{
			if ( !empty($data) )
			{
				$empty = false;
				break;
			}
		}
		if ( $empty )
		{
			usam_update_lead( $document_id, ['company_id' => 0, 'contact_id' => 0]);	
			return;
		}		
		$lead = usam_get_lead( $document_id );
		if ( $lead['user_id'] )
			$contact = usam_get_contact( $lead['user_id'], 'user_id' );		
		
		$metas = usam_get_CRM_data_from_webform( $customer_data );
		if ( usam_is_type_payer_company( $lead['type_payer'] ) ) 
		{	
			if ( !empty($contact) )
				usam_update_lead( $document_id, ['contact_id' => $contact['id']]);	
			
			if ( !empty($metas['company']['company_name']) )
				$company['name'] = $metas['company']['company_name'];
			elseif ( !empty($metas['company']['full_company_name']) )
				$company['name'] = $metas['company']['full_company_name'];
			elseif ( !empty($metas['company']['inn']) )
				$company['name'] = sprintf(__('Добавлена из лида %s','usam'), $document_id);
			else
				return false; // Не добавлять компании без ИНН и названия
			$company['type'] = 'customer';	
			$company_id = $lead['company_id'];	
			if ( $company_id )
			{
				$lead_company = usam_get_company( $company_id );
				if ( !$lead_company )
					$company_id = 0;
			}
			if ( empty($metas['company']['inn']) )
			{			
				$found_company = usam_get_companies(['name' => $company['name'], 'number' => 1, 'cache_results' => true]);
				if ( $found_company )
					$company_id = $found_company['id'];							
				if ( $company_id )
					usam_update_company_metas( $company_id, $metas['company'] );
			}	
			else
				$company_id = 0; // Чтобы проверить по ИНН
			if ( !$company_id )
				$company_id = usam_insert_company( $company, $metas['company'] );		
			if ( $lead['company_id'] != $company_id )
				usam_update_lead( $document_id, ['company_id' => $company_id]);
		}
		else
		{								
			$new_contact = $metas['contact'];	
			$new_contact['contact_source'] = 'lead';			
			$new_contact['user_id'] = $lead['user_id'];						
			$contact_id = 0;
			if ( !empty($contact) )
			{
				$contact_id = $contact['id'];					
				usam_combine_contact( $contact['id'], $new_contact );
				if ( $lead['contact_id'] != $contact_id )
					usam_update_lead( $document_id, ['contact_id' => $contact_id]);	
				return;
			}			
			if ( $lead['contact_id'] )
			{
				$contact = usam_get_contact( $lead['contact_id'] );
				if ( empty($contact['firstname']) || empty($contact['lastname']) || !empty($new_contact['firstname']) && mb_strtolower($new_contact['firstname']) == mb_strtolower($contact['firstname']) || !empty($new_contact['lastname']) && mb_strtolower($new_contact['lastname']) == mb_strtolower($contact['lastname']) )
				{
					usam_combine_contact( $lead['contact_id'], $new_contact );
					$contact_id  = $lead['contact_id'];				
				}
			}
			if ( $contact_id == 0 )
			{
				$contact_id = usam_insert_contact( $new_contact );	
				usam_update_lead( $document_id, ['contact_id' => $contact_id]);	
			}			
		}	
	}
	
	public function deleted_user( $id, $reassign, $user )
	{
		global $wpdb;
		$wpdb->query("DELETE FROM ".USAM_TABLE_NOTIFICATIONS." WHERE user_id=$id");	
		$wpdb->query("UPDATE ".USAM_TABLE_CONTACTS." SET user_id='0' WHERE `user_id`=$id");
		$wpdb->query("DELETE FROM ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS." WHERE user_id=$id" );			
	}
}


function usam_get_customer_case( $customer_id, $customer_type )
{	
	$events = wp_cache_get( $customer_id, 'usam_affairs_'.$customer_type );	
	if ( $events === false )
	{							
		$events = usam_get_events(['links_query' => [['object_type' => $customer_type, 'object_id' => $customer_id]], 'status' => ['not_started', 'started']]);
		wp_cache_set( $customer_id, $events, 'usam_affairs_'.$customer_type );						
	}
	return $events;
}

function usam_update_comments_cache( $object_ids, $object_type )
{	
	if ( !is_array($object_ids) ) 
	{
		$object_ids = preg_replace('|[^0-9,]|', '', $object_ids);
		$object_ids = explode(',', $object_ids);
	}
	$object_ids = array_map('intval', $object_ids);		
	
	$cache_key = "usam_comments_$object_type";					
	$ids = array();
	$cache = array();
	foreach ( $object_ids as $id ) 
	{
		$cached_object = wp_cache_get( $id, $cache_key );
		if ( false === $cached_object )
			$ids[] = $id;
		else
			$cache[$id] = $cached_object;
	}
	if ( empty($ids) )
		return $cache;
	
	require_once( USAM_FILE_PATH . '/includes/crm/comments_query.class.php' );
	$comments = usam_get_comments(['object_type' => $object_type, 'object_id' => $ids]);	
	$comment_ids = array();	
	foreach ( $ids as $id ) 
	{
		if ( !isset($comments[$id]) )
			$cache[$id] = array();
		else
			$cache[$id] = $comments[$id];
		
		wp_cache_set( $id, $comments[$id], $cache_key );		
	}
	return $cache;
}

function usam_update_affairs_cache( $object_ids, $object_type )
{	
	$events = usam_get_events(['links_query' => [['object_type' => $object_type, 'object_id' => $object_ids]], 'status' => ['not_started', 'started']]);	
	$event_ids = [];
	foreach ( $events as $event ) 
	{				
		$results[$event->id] = $event; 	
		$event_ids[] = $event->id;
	}
	$cache = array();
	if ( !empty($event_ids) )
	{
		require_once( USAM_FILE_PATH . '/includes/crm/ribbon_query.class.php' );
		$types = usam_get_events_types();
		$objects = usam_get_ribbon_query(['event_id' => $event_ids, 'event_type' => array_keys($types), 'add_fields' => ['object_id', 'object_type']]);	
		foreach ( $objects as $object ) 
			$cache[$object->object_id][] = $results[$object->event_id]; 	
	}
	foreach ( $object_ids as $id ) 
	{
		if ( !isset($cache[$id]) )
			$cache[$id] = array();
		
		wp_cache_set( $id, $cache[$id], 'usam_affairs_'.$object_type );		
	}
	return $cache;
}

function usam_get_crm_address( $id, $properties, $type = 'order' ) 
{
	$function = "usam_get_{$type}_metadata";
	$address = array();
	foreach ($properties as $type => $code)
	{
		if ( $function( $id, $code ) )
		{							
			if ( $type == 'location' )							
				$address += array_reverse(usam_get_address_locations( $function( $id, $code ) ));
			else
				$address[$type] = $function( $id, $code );
		}				
	}
	return $address;
}

function usam_get_crm_objects() 
{
	$crm = usam_get_details_documents( );
	$crm += usam_get_events_types();
	$crm['contact'] = ['single_name' => __("Контакт","usam"), 'plural_name' => __("Контакты","usam"), 'genitive' => __('контакта','usam'), 'url' => admin_url('admin.php?page=crm&tab=contacts') ];
	$crm['company'] = ['single_name' => __("Компания","usam"), 'plural_name' => __("Компании","usam"), 'genitive' => __('компании','usam'), 'url' => admin_url('admin.php?page=companies&tab=company') ];
	$crm['product'] = ['single_name' => __("Товар","usam"), 'plural_name' => __("Товары","usam"), 'genitive' => __('товаров','usam'), 'url' => '' ];
	$crm['page'] = ['single_name' => __("Страница","usam"), 'plural_name' => __("Страницы","usam"), 'genitive' => __('страниц','usam'), 'url' => '' ];
	return $crm;
}

function usam_get_hiding_data( $data, $type ) 
{
	if( $data && !current_user_can('view_communication_data') && current_user_can('store_section') )
	{		
		$s = "***";
		switch( $type ) 
		{					
			case 'email':
				$data = preg_replace("/^[^@]+/si", $s, $data);			
			break;
			case 'phone':
			case 'mobile_phone':	
				$data = $data[0].str_repeat('*', strlen($data)-3 ).substr($data, -2);			
			break;
		}
	}
	return $data;
}
?>