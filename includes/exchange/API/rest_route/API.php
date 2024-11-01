<?php
require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/api-handler-assistant.php' );
class USAM_API extends USAM_API_handler_assistant
{			
	public static function get_admin_filters( WP_REST_Request $request ) 
	{					
		$parameters = self::get_parameters( $request );
			
		$user_id = get_current_user_id();
		require_once( USAM_FILE_PATH . '/admin/includes/filters_query.class.php' );														
		$filters = usam_get_admin_filters(['screen_id' => $parameters['screen_id'], 'user_id' => array(0, $user_id)]);
		$results = [];
		foreach ( $filters as $filter ) 
		{					
			$results[] = ['id' => $filter->id, 'name' => $filter->name, 'setting' => maybe_unserialize($filter->setting)];			
		}
		return $results;	
	}
	
	public static function delete_admin_filter( WP_REST_Request $request ) 
	{					
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$user_id = get_current_user_id();	
		
		require_once( USAM_FILE_PATH . '/admin/includes/filter.class.php' );
		
		$filter = new USAM_Filter( $parameters['id'] );	
		if ( $user_id == $filter->get('user_id') )
			return $filter->delete();
		else
			return usam_get_callback_messages(['ready' => 0]);
	}

	public static function save_admin_filter( WP_REST_Request $request ) 
	{					
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();					
		
		require_once( USAM_FILE_PATH . '/admin/includes/filter.class.php' );
		
		$screen_id = sanitize_title( $parameters['screen_id'] );
		$name = sanitize_text_field(stripslashes($parameters['name']));	
		$filters = stripslashes_deep($parameters['filters']);			

		$filter = new USAM_Filter(['name' => $name, 'screen_id' => $screen_id, 'setting' => $filters]);
		if ( $filter->save() )
		{
			$data = $filter->get_data();
			$message = usam_get_callback_messages(['ready' => 1]);
			return array_merge( $message, $data );
		}
		return usam_get_callback_messages(['ready' => 0]);
	}	
	
	public static function get_interface_filters( WP_REST_Request $request )
	{	
		$parameters = self::get_parameters( $request );	
		$results = []; 
		$methods = [
			'companies' => 'get_companies',
			'exchange_rules' => 'get_exchange_rules',			
			'company_own' => 'get_company_own', 
			'post_author' => 'get_post_author',			
			'sources' => 'get_traffic_sources', 
			'mailing_lists' => 'get_mailing_lists', 
			'payment' => 'get_payment_services', 
			'status' => 'get_statuses', 			
			'seller' => 'get_own_companies', 
			'shipping_gateways' => 'get_shipping_gateways', 
			'shipping' => 'get_shippings', 
			'courier_delivery' => 'get_courier_delivery', 
			'storage_pickup' => 'get_storages_pickup', 
			'storage' => 'get_storages', 
			'manager' => 'get_managers',
			'author' => 'get_managers',
			'users' => 'get_contacts_users', 
			'discount' => 'get_discounts',
			'document_discount' => 'get_document_discounts', 		
			'payer' => 'get_payers', 
			'code_price' => 'get_type_prices', 
			'weekday' => 'get_weekdays', 
			'group' => 'get_groups', 
			'webform' => 'get_webforms',
			'newsletter' => 'get_newsletters', 
			'category' => 'get_categories', 
			'brands' => 'get_brands', 
			'selection' => 'get_selections', 			
			'category_sale' => 'get_category_sales', 
			'catalog' => 'get_catalogs', 
			'variation' => 'get_variations', 
			'chat_channel' => 'get_types_social_network',
			'types_event' => 'get_types_event',
			'export' => 'get_export',
			'banner_location' => 'get_banner_location',
			'reason' => 'get_communication_errors',
			'taxonomy' => 'get_taxonomy',
			'industry' => 'get_industry',
			'contractors' => 'get_contractors',
			'companies_types' => 'get_companies_types',
			'contacts_source' => 'get_contacts_source',
			'language' => 'get_languages',
			'sale_area' => 'get_sales_area',
			'parsing_sites' => 'get_parsing_sites',
			'department' => 'get_departments',
			'campaign' => 'get_campaigns', 			
			'user_work' => 'get_company_structure',
			'property_groups' => 'get_property_groups_filter',
			'document_types' => 'get_document_types',	
			'triggers' => 'get_site_triggers',
		];			
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/crm_API.class.php' );		
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/newsletter_API.class.php' );		
		foreach ( $parameters as $filter => $args ) 
		{					
			if ( !empty($methods[$filter]) )
			{								
				$args = is_array($args)?$args:[];
				$method = $methods[$filter];				
				$args['count'] = 1000;
				self::$interface_filters = true;
				if ( $filter == 'companies' || $filter == 'webform' )
					$data = USAM_CRM_API::$method( $request, $args );
				elseif ( $filter == 'newsletter' )
					$data = USAM_Newsletter_API::$method( $request, $args );		
				elseif ( $filter == 'category' || $filter == 'brands' || $filter == 'variation' || $filter == 'catalog' || $filter == 'category_sale' || $filter == 'selection')
					$data = USAM_Products_API::$method( $request, $args );						
				else					
					$data = self::$method( $request, $args );				
				$results[$filter] = isset($data['items'])?$data['items']:$data;
			}
		}
		return $results;
	}
	
	public static function save_interface_filters( WP_REST_Request $request, $args = [] ) 
	{			
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();	
	
		$filters = isset($parameters['filters'])?$parameters['filters']:[];	
		$screen_id = sanitize_title($parameters['screen_id']);				
		$sort = get_user_option( 'usam_sort_interface_filters' );	
		if ( empty($sort) ) 
			$sort = [];
		$sort[$screen_id] = $filters;
		$user_id = get_current_user_id();
		return update_user_option( $user_id, 'usam_sort_interface_filters', $sort );
	}
	
	public static function get_document_types( WP_REST_Request $request, $args = [] ) 
	{		
		$results = array();
		foreach( usam_get_details_documents( ) as $key => $document )
		{
			$results[] = ['id' => $key, 'name' => $document['plural_name']];
		}
		return $results;
	}
	
	public static function get_property_groups_filter( WP_REST_Request $request, $args = [] ) 
	{		
		$results = array();
		foreach( usam_get_property_groups( $args ) as $group )
		{
			$results[] = ['id' => $group->code, 'name' => $group->name];
		}
		return $results;
	}
		
	public static function get_company_structure(WP_REST_Request $request, $parameters = null) 
	{
		$user_id = get_current_user_id();	
		$subordinates = usam_get_subordinates( null, array('appeal', 'user_id') );
		$results = [['id' => $user_id, 'name' => __('Я', 'usam')]];
		if ( !empty($subordinates) )
		{			
			$contact_id = usam_get_contact_id();
			require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
			$departments = usam_get_departments(['chief' => $contact_id]);				
			if ( !empty($departments) )
			{	
				foreach( $departments as $department )
				{
					$results[] = ['id' => $department->id, 'name' => $department->name];
				}
			}
			foreach( $subordinates as $contact )
			{
				if ( $contact->user_id != $user_id && $contact->user_id )
					$results[] = ['id' => $contact->user_id, 'name' => $contact->appeal];
			}
		}
		return $results;
	}
	
	public static function get_company_own( WP_REST_Request $request, $parameters = null ) 
	{
		require_once(USAM_FILE_PATH.'/includes/crm/bank_accounts_query.class.php');	
		$own_companies = usam_get_companies(['type' => 'own']);		
		if ( !$own_companies )
			return [];
		
		$ids = array();
		$selected_companies = array();
		foreach ( $own_companies as $company )
		{
			$ids[] = $company->id;
			$selected_companies[$company->id] = $company;
		}
		$bank_accounts = usam_get_bank_accounts(['company' => $ids]);
		$results = [];
		foreach( $bank_accounts as $account )
		{
			$currency = usam_get_currency_sign( $account->currency );
			$results[] = ['id' => $account->id, 'name' => $selected_companies[$account->company_id]->name." - $account->name ( $currency )"];		
		}
		return $results;
	}
	
	public static function get_sites( WP_REST_Request $request, $parameters = null ) 
	{
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );				
		require_once(USAM_FILE_PATH.'/includes/seo/sites_query.class.php');
		$query = new USAM_Sites_Query( self::$query_vars );	
		$items = $query->get_results();	
		if ( !empty($items) )
		{					
			$count = $query->get_total();	
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function get_parsing_sites( WP_REST_Request $request, $parameters = null ) 
	{
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );		
	
		if ( !empty(self::$query_vars['site_type']) )
			self::$query_vars['site_type'] = array_map('sanitize_title', (array)self::$query_vars['site_type']);
		require_once(USAM_FILE_PATH.'/includes/parser/parsing_sites_query.class.php');
		$query = new USAM_Parsing_Sites_Query( self::$query_vars );	
		$items = $query->get_results();			
		if ( !empty($items) )
		{				
			$count = $query->get_total();
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function get_sales_area( WP_REST_Request $request, $parameters = null ) 
	{
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );	
		
		$items = usam_get_sales_areas( self::$query_vars );			
		return ['count' => count($items), 'items' => $items];
	}
	
	public static function get_departments( WP_REST_Request $request, $parameters = null ) 
	{					
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );								
		
		require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
		$query = new USAM_Departments_Query( self::$query_vars );		
		$items = $query->get_results();			
		if ( !empty($items) )
		{				
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;	
	}
		
	public static function get_campaigns( WP_REST_Request $request, $parameters = null ) 
	{				
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );								
		
		require_once(USAM_FILE_PATH . '/includes/analytics/advertising_campaigns_query.class.php');
		$query = new USAM_Advertising_Campaigns_Query( self::$query_vars );		
		$items = $query->get_results();			
		if ( !empty($items) )
		{
			if ( isset($parameters['fields']) && $parameters['fields'] == 'autocomplete' )
			{
				$results = [];
				foreach ( $items as $item ) 		
					$results[] = ['id' => $item->id, 'name' => $item->title];
				$items = $results;		
			}	
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;	
	}

	public static function get_languages( WP_REST_Request $request, $parameters = [] ) 
	{		
		$data = maybe_unserialize(get_site_option('usam_languages'));
		$results = [];
		foreach( $data as $key => $item )
			$results[] = ['id' => $item['id'], 'name' => $item['name']];
		return $results;
	}
	
	public static function get_contacts_source( WP_REST_Request $request, $parameters = [] ) 
	{
		$option = get_option('usam_crm_contact_source', array() );
		$data = maybe_unserialize( $option );	
		$results = [];
		foreach( $data as $key => $item )
			$results[] = ['id' => $item['id'], 'name' => $item['name']];
		return $results;
	}
	
	public static function get_companies_types( WP_REST_Request $request, $parameters = [] ) 
	{
		$data = usam_get_companies_types();		
		$results = [];
		foreach( $data as $key => $name )
			$results[] = ['id' => $key, 'name' => $name];
		return $results;
	}
	
	public static function get_industry( WP_REST_Request $request, $parameters = [] ) 
	{
		$data = usam_get_companies_industry();		
		$results = [];
		foreach( $data as $key => $name )
			$results[] = ['id' => $key, 'name' => $name];
		return $results;
	}
	
	public static function get_shipping_gateways( WP_REST_Request $request, $parameters = [] ) 
	{
		$results = [['id' => '', 'name' => __('Ваши', 'usam')]];
		foreach (usam_get_data_integrations( 'shipping', ['name' => 'Name', 'points' => 'Points'] ) as $key => $gateway)
		{
			if ( $gateway['points'] && $gateway['points'] == 'Да' )
				$results[] = ['id' => $key, 'name' => $gateway['name']];
		}
		return $results;
	}
	
	public static function get_communication_errors( WP_REST_Request $request, $parameters = [] )  
	{
		$communication_errors = usam_get_text_communication_errors();		
		$results = [];
		foreach( $communication_errors['email'] as $key => $name )
			$results[] = ['id' => $key, 'name' => $name];
		return $results;
	}
	
	public static function get_banner_location( WP_REST_Request $request, $parameters = [] ) 
	{
		$banner_location = usam_register_banners();		
		$results = [];
		foreach( $banner_location as $key => $name )
			$results[] = ['id' => $key, 'name' => $name];
		return $results;
	}
	
	public static function get_exchange_rules( WP_REST_Request $request, $parameters = null ) 
	{
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );	
		if ( !empty($parameters['orderby']) )
			self::$query_vars['orderby'] = sanitize_title( $parameters['orderby'] );
		else
			self::$query_vars['orderby'] = 'name';
		if ( !empty($parameters['order']) )
			self::$query_vars['order'] = sanitize_title( $parameters['order'] );
		else
			self::$query_vars['order'] = 'ASC';
		$rules = usam_get_exchange_rules( self::$query_vars );
		$results = [];
		foreach( $rules as $key => $rule )
			$results[] = ['id' => $rule->id, 'name' => $rule->name];
		return $results;
	}
		
	public static function get_export( WP_REST_Request $request, $parameters = [] ) 
	{
		return [['id' => 1, 'name' =>  __('Выгруженные', 'usam')], ['id' => 0, 'name' => __('Не выгруженные', 'usam')]];
	}
	
	public static function get_types_event( WP_REST_Request $request, $parameters = [] )  
	{
		$types = usam_get_events_types();		
		$results = [];
		foreach( $types as $type => $event )
		{				
			if ( in_array($type, ['affair', 'meeting', 'call', 'task']) )
				$results[] = ['id' => $type, 'name' => $event['single_name']];
		}		
		return $results;
	}
	
	public static function get_types_social_network( WP_REST_Request $request, $parameters = [] ) 
	{
		$types = usam_get_types_social_network();		
		$results = [];
		foreach( $types as $type => $name )
			$results[] = ['id' => $type, 'name' => $name];
		return $results;
	}	
	
	public static function get_traffic_sources( WP_REST_Request $request, $parameters = [] ) 
	{
		$results = [];
		$sources = usam_get_traffic_sources();
		foreach( $sources as $source => $title )
			$results[] = ['id' => $source, 'name' => $title];
		return $results;
	}	
		
	public static function get_sliders( WP_REST_Request $request, $parameters = [] ) 
	{	
		$args = array();
		if ( isset($_GET['active']) )
			$args['active'] = absint($_GET['active']);
		else
			$args['active'] = 1;
		$results = [];
		foreach( \usam_get_sliders( $args ) as $slider)
			$results[] = (array)$slider;
		return $results;
	}
	
	public static function insert_slider( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		include_once(USAM_FILE_PATH.'/includes/theme/slider.php');		
		if ( !empty($parameters['slides']) )
		{
			$slides = $parameters['slides'];
			unset($parameters['slides']);
			foreach ( $slides as $key => $value )
			{
				if ( ($key == 'start_date' || $key == 'end_date') && $value )
					$slide[$key] = USAM_Request_Processing::sanitize_date($value);				
			}
		}
		else
			$slides = [];
		return usam_insert_slider( $parameters, $slides );
	}

	public static function update_slider( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		include_once(USAM_FILE_PATH.'/includes/theme/slider.php');
		if ( !empty($parameters['slides']) )
		{
			$slides = $parameters['slides'];
			unset($parameters['slides']);
			foreach ( $slides as $key => $value )
			{
				if( $key == 'start_date' || $key == 'end_date' )
					$slide[$key] = USAM_Request_Processing::sanitize_date($value);				
			}			
		}		
		else
			$slides = []; 	
		return usam_update_slider( $id, $parameters, $slides );
	}
		
	public static function delete_slider( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		include_once(USAM_FILE_PATH.'/includes/theme/slider.php');
		return usam_delete_slider( $id );
	}
			
	public static function get_groups( WP_REST_Request $request, $parameters = null ) 
	{
		$user_id = get_current_user_id();
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
		
		if ( !empty(self::$query_vars['id']) )
			unset(self::$query_vars['id']);		
		require_once( USAM_FILE_PATH . '/includes/crm/groups_query.class.php' );
		$query = new USAM_Groups_Query( self::$query_vars );		
		$items = $query->get_results();			
		if ( !empty($items) )
		{
			if ( !empty($parameters['id']) && !empty($parameters['type']) )
			{
				if( $parameters['type'] === 'file' )
					$selected = array_map('intval', usam_get_array_metadata( $parameters['id'], 'file', 'group' ));
				else
					$selected = usam_get_groups_object( $parameters['id'], $parameters['type'] );	 
				foreach( $items as &$item )
					$item->checked = in_array($item->id, $selected);
			}	
			$results = ['count' => $query->get_total(), 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function insert_group( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH.'/includes/crm/group.class.php' );
		return usam_insert_group( $parameters );
	}

	public static function update_group( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH.'/includes/crm/group.class.php' );
		return usam_update_group( $id, $parameters );
	}	
	
	public static function update_groups( WP_REST_Request $request ) 
	{				
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		$i = 0;
		require_once( USAM_FILE_PATH.'/includes/crm/group.class.php' );
		foreach( $parameters['items'] as $item )
		{
			$id = 0;			
			if ( !empty($item['id']) )
				$id = absint($item['id']);	
			if ( usam_update_group( $id, $item ) )
				$i++;
		}			
		return $i;
	}
		
	public static function delete_group( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		require_once( USAM_FILE_PATH.'/includes/crm/group.class.php' );
		return usam_delete_group( $id );
	}
	
	public static function get_trigger( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );
		return usam_get_trigger( $id );
	}	
	
	public static function insert_trigger( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );
		$id = usam_insert_trigger( $parameters );
		if ( $id )
		{
			foreach( $parameters['actions'] as $i => $item )
			{
				if ( !isset($item['id']) )
					unset($parameters['actions'][$i]);
			}			
			usam_save_array_metadata( $id, 'trigger', 'actions', $parameters['actions'] );
		}		
		return $id;
	}

	public static function update_trigger( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );
		$result = usam_update_trigger( $id, $parameters );
		if ( isset($parameters['actions']) )
		{
			foreach( $parameters['actions'] as $i => $item )
			{
				if ( !isset($item['id']) )
					unset($parameters['actions'][$i]);
			}			
			usam_save_array_metadata( $id, 'trigger', 'actions', $parameters['actions'] );
		}
		return true;
	}	
	
	public static function update_triggers( WP_REST_Request $request ) 
	{				
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		$i = 0;
		require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );
		foreach( $parameters['items'] as $item )
		{
			$id = 0;			
			if ( !empty($item['id']) )
				$id = absint($item['id']);	
			if ( usam_update_trigger( $id, $item ) )
				$i++;
		}			
		return $i;
	}
		
	public static function delete_trigger( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );				
		return usam_delete_trigger( $id );
	}
			
	public static function get_mailing_lists( WP_REST_Request $request, $parameters = null ) 
	{	
		require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );				
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );	
		
		if ( isset($parameters['view']) )
			$parameters['view'] = $parameters['view'] && (current_user_can('universam_api') || current_user_can('view_lists') ) ? 1:0;
		elseif ( !current_user_can('universam_api') && !current_user_can('view_lists') )
			$parameters['view'] = 1;				
					
		self::$query_vars = self::get_query_vars( $parameters, $parameters );					
		$query = new USAM_Mailing_Lists_Query( self::$query_vars );		
		$items = $query->get_results();			
		if ( !empty($items) )
		{
			$count = $query->get_total();
			if ( !empty($parameters['not_added']) )
				array_unshift($items, ['id' => 0, 'name' => __('Не добавлены', 'usam')]);
			
			if ( !empty($parameters['subscribed']) )
			{				
				$contact_id = usam_get_contact_id();
				$condition = usam_get_contact_metadata( $contact_id, $parameters['subscribed'] );						
				$subscriber_list = usam_get_subscriber_list( $condition );
				foreach( $items as &$item )
				{
					$item->subscribe = 0;
					foreach( $subscriber_list as $list )
					{
						if ( $list->list == $item->id && $list->status != 2 )
							$item->subscribe = 1;
					}
				}
			}
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function new_subscribe( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();						
		
		$result = false;
		$email = sanitize_email($parameters['email']);
		if ( is_email($email) )	
		{
			$contact_id = usam_get_contact_id();			
			$contact = usam_get_contacts(['meta_value' => $email, 'meta_key' => 'email', 'number' => 1]);				
			if ( empty($contact) )
			{				
				$data = [];
				foreach(['lastname', 'firstname', 'email'] as $key )
					if( !empty($parameters[$key]) )
						$data[$key] = $parameters[$key];
				$contact = usam_get_contact( $contact_id );
				if ( $contact_id )
				{
					if ( $contact['status'] == 'temporary' )
						$data['status'] = 'customer';	
					usam_combine_contact($contact_id, $data );
				}
				else
				{
					$data['contact_source'] = 'email';
					$contact_id = usam_save_or_create_contact( $data );
				}
			}
			if ( $contact_id )
			{
				if ( !empty($parameters['lists']) )
					$lists = array_map('intval', (array)$parameters['lists']);		
				else
				{
					require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );
					$lists = usam_get_mailing_lists(['fields' => 'id', 'view' => 1]);
				}
				foreach ( $lists as $list_id )
					usam_set_subscriber_lists(['communication' => $email, 'status' => 1, 'id' => $list_id, 'type' => 'email']);	
				usam_update_mailing_statuses(['include' => $lists]);	
			}	
			do_action( 'usam_subscribe_for_newsletter', $email );		
			$result = true;
		}		
		return $result;
	}
		
	public static function save_subscriptions( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$contact_id = usam_get_contact_id();
		$communication = usam_get_contact_metadata( $contact_id, $parameters['communication'] );
		foreach( $parameters['lists'] as $list_id => $status )		
			usam_set_subscriber_lists(['communication' => $communication, 'status' => $status, 'id' => $list_id]);			
		return true;
	}	
	
	public static function delete_post_userlist( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$contact_id = usam_get_contact_id();
		require_once(USAM_FILE_PATH.'/includes/customer/user_posts_query.class.php');		
		$query_vars = ['user_list' => $parameters['list'], 'fields' => 'product_id'];
		if ( $parameters['list'] !== 'sticky' || !current_user_can('edit_product') )
		{
			$query_vars['contact_id'] = $contact_id;	
			$contact_id = null;
		}
		$ids = usam_get_user_posts( $query_vars );
		foreach( $ids as $post_id )
		{
			usam_delete_post_from_customer_list( $post_id, $parameters['list'], $contact_id );			
		}	
	}	
	
	public static function add_post_userlist( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$contact_id = usam_get_contact_id();		
		$post_id = usam_get_post_id_main_site( $parameters['post_id'] );
		$length_list = 500;		
	
		require_once(USAM_FILE_PATH.'/includes/customer/user_posts_query.class.php');
		require_once(USAM_FILE_PATH.'/includes/customer/user_post.class.php');
		
		$query_vars = ['user_list' => $parameters['list'], 'fields' => 'product_id'];		
		if ( $parameters['list'] === 'sticky' && current_user_can('edit_product') )		
			$contact_id = null;
		else			
			$query_vars['contact_id'] = $contact_id;
		$product_ids = usam_get_user_posts( $query_vars );	
		$result = false;
		if ( in_array($post_id, $product_ids) )			
		{	
			usam_delete_post_from_customer_list( $post_id, $parameters['list'], $contact_id);
			$result = 'deleted';	
		}	
		else
		{
			if ( count($product_ids) > $length_list )	
				$result = 'limit';	
			else
			{	
				if ( usam_insert_user_post(['user_list' => $parameters['list'], 'product_id' => $post_id, 'contact_id' => $contact_id]) )					
					$result = 'add';		
			}
		}
		return $result;
	}	
	
	public static function add_seller_userlist( WP_REST_Request $request ) 
	{	
		global $wpdb;
		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$contact_id = usam_get_contact_id();		
		$length_list = 500;		
	
		require_once(USAM_FILE_PATH.'/includes/customer/user_sellers_query.class.php');
		require_once(USAM_FILE_PATH.'/includes/customer/user_seller.class.php');
		$seller_ids = usam_get_user_sellers(['user_list' => $parameters['list'], 'fields' => 'seller_id', 'contact_id' => $contact_id]);		 
		if ( in_array($parameters['seller_id'], $seller_ids) )			
		{				
			$result = $wpdb->query( $wpdb->prepare("DELETE FROM ".USAM_TABLE_USER_SELLERS." WHERE seller_id='%d' AND user_list='%s' AND contact_id='%d'", $parameters['seller_id'], $parameters['list'], $contact_id ));
			do_action( 'usam_user_seller_delete', ['seller_id' => $parameters['seller_id'], 'user_list' => $parameters['list'], 'contact_id' => $contact_id] );
			$result = 'deleted';	
		}	
		else
		{
			if ( count($seller_ids) > $length_list )	
				$result = 'limit';	
			else
			{
				if ( usam_insert_user_seller(['user_list' => $parameters['list'], 'seller_id' => $parameters['seller_id'], 'contact_id' => $contact_id]) )					
					$result = 'add';				
			}
		}
		return $result;
	}		
		
	public static function delete_seller_userlist( WP_REST_Request $request ) 
	{	
		global $wpdb;
		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$contact_id = usam_get_contact_id();
		require_once(USAM_FILE_PATH.'/includes/customer/user_sellers_query.class.php');
		$ids = usam_get_user_sellers(['user_list' => $parameters['list'], 'fields' => 'seller_id', 'contact_id' => $contact_id]);
		foreach( $ids as $seller_id )
		{
			$result = $wpdb->query( $wpdb->prepare("DELETE FROM ".USAM_TABLE_USER_SELLERS." WHERE seller_id='%d' AND user_list='%s' AND contact_id='%d'", $seller_id, $parameters['list'], $contact_id ));
			do_action( 'usam_user_seller_delete', ['seller_id' => $seller_id, 'user_list' => $parameters['list'], 'contact_id' => $contact_id] );
		}	
	}
	
	public static function get_payment_services( WP_REST_Request $request, $parameters = [] ) 
	{		
		$payment_gateways = usam_get_payment_gateways(['active' => 'all']);	
		$results = [];
		foreach ( $payment_gateways as $method ) 
			$results[] = array( 'id' => $method->id, 'name' => $method->name);
		return $results;
	}
	
	public static function get_contractors( WP_REST_Request $request, $parameters = [] ) 
	{
		$companies = usam_get_companies(['fields' => ['id', 'name'], 'type' => 'contractor']);
		$results = [];
		foreach ( $companies as $company )
			$results[] = array( 'id' => $company->id, 'name' => $company->name);	
		return $results;			
	}
	
	public static function get_own_companies( WP_REST_Request $request, $parameters = [] ) 
	{	
		require_once(USAM_FILE_PATH.'/includes/crm/bank_accounts_query.class.php');	
		$companies = usam_get_companies(['type' => 'own']);
		$results = [];
		if ( !empty($companies) )
		{		
			$ids = array();
			$selected_companies = array();
			foreach ( $companies as $company )
			{
				$ids[] = $company->id;
				$selected_companies[$company->id] = $company;
			}
			$bank_accounts = usam_get_bank_accounts(['company' => $ids]);	
			if ( !empty($bank_accounts) )
			{					
				foreach ( $bank_accounts as $acc ) 
				{
					$results[] = ['id' => $acc->id, 'name' => $selected_companies[$acc->company_id]->name." - $acc->name"];
				} 
			}
		}
		return $results;
	}	
	
	public static function get_shippings( WP_REST_Request $request, $parameters = [] ) 
	{
		$delivery_service = usam_get_delivery_services(['active' => 'all']);			
		$results = [];
		foreach ( $delivery_service as $method ) 
		{			
			$results[] = array( 'id' => $method->id, 'name' => $method->name);
		} 	
		return $results;
	}
	
	public static function get_courier_delivery( WP_REST_Request $request, $parameters = [] )  
	{
		$delivery_service = usam_get_delivery_services(['delivery_option' => 0]);			
		$results = [];
		foreach ( $delivery_service as $method ) 
		{			
			$results[] = ['id' => $method->id, 'name' => $method->name];
		} 	
		return $results;
	}
	
	public static function get_storages( WP_REST_Request $request, $parameters = null )
	{
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );			
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
		if( !empty($parameters['add_fields']) )
		{
			self::$query_vars['cache_meta'] = true;
			if ( in_array('city', self::$query_vars['add_fields']) )
				self::$query_vars['cache_location'] = true;
			if ( in_array('images', self::$query_vars['add_fields']) )
				self::$query_vars['images_cache'] = true;
		}		
		$query = new USAM_Storages_Query( self::$query_vars );
		$items = $query->get_results();	
		if ( $items )
		{
			foreach( $items as &$item )
			{						
				if( !empty($parameters['add_fields']) )
				{						
					foreach ( $parameters['add_fields'] as $field )
						switch( $field ) 
						{
							case 'phone':	
								$item->phone = (string)usam_get_storage_metadata( $item->id, 'phone');
								$item->phone_format = (string)usam_phone_format($item->phone, '7 (999) 999 99 99');
							break;								
							case 'email':	
								$item->email = (string)usam_get_storage_metadata( $item->id, 'email');
							break;
							case 'schedule':	
								$item->schedule = (string)usam_get_storage_metadata( $item->id, 'schedule');
							break;
							case 'city':	
								$location = usam_get_location( $item->location_id );
								$item->city = isset($location['name'])?htmlspecialchars($location['name']):'';
							break;
							case 'address':	
								$item->address = (string)usam_get_storage_metadata( $item->id, 'address');
							break;
							case 'coordinates':	
								$item->longitude = (float)usam_get_storage_metadata( $item->id, 'longitude');
								$item->latitude = (float)usam_get_storage_metadata( $item->id, 'latitude');
							break;	
							case 'images':	
								$item->images = usam_get_storage_images( $item->id );
							break;								
						}
				}	
				if( isset($parameters['fields']) && is_string($parameters['fields']) && $parameters['fields'] === 'autocomplete' )
				{
					foreach( $items as &$item )
						$item->name = $item->title;
				}	
			}
			$count = $query->get_total();			
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function delete_storage( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		return usam_delete_storage( $id );
	}
	
	public static function get_storage( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = self::get_parameters( $request );		
		$storage = usam_get_storage( $id );
		if( !empty($parameters['add_fields']) )
		{						
			foreach ( $parameters['add_fields'] as $field )
				switch( $field ) 
				{
					case 'phone':	
						$storage['phone'] = (string)usam_get_storage_metadata( $id, 'phone');
						$storage['phone_format'] = (string)usam_phone_format($storage['phone'], '7 (999) 999 99 99');
					break;								
					case 'email':	
						$storage[$field] = (string)usam_get_storage_metadata( $id, 'email');
					break;
					case 'schedule':	
						$storage[$field] = (string)usam_get_storage_metadata( $id, 'schedule');
					break;
					case 'city':	
						$location = usam_get_location( $storage['location_id'] );
						$storage[$field] = isset($location['name'])?htmlspecialchars($location['name']):'';
					break;
					case 'address':	
						$storage[$field] = (string)usam_get_storage_metadata( $id, 'address');
					break;
					case 'coordinates':	
						$storage['longitude'] = (float)usam_get_storage_metadata( $id, 'longitude');
						$storage['latitude'] = (float)usam_get_storage_metadata( $id, 'latitude');
					break;	
					case 'images':	
						$storage['images'] = usam_get_storage_images( $id );
					break;								
				}
		}	
		return $storage;
	}	
	
	public static function insert_storage( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$id = usam_insert_storage( $parameters );
		if ( $id )
		{
			USAM_API::update_storage_metadata( $id, $parameters );
			usam_recalculate_stock_products();	
		}		
		return $id;
	}

	public static function update_storage( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$result = usam_update_storage( $id, $parameters );
		USAM_API::update_storage_metadata( $id, $parameters );						
		$sales_area = usam_get_sales_areas();
		$recalculate = false;
		if ( !empty($sales_area) )
		{
			foreach( $sales_area as $value )
			{					
				if( usam_update_storage_metadata( $id, 'sale_area_'.$value['id'], isset($parameters['sale_area_'.$value['id']]) && $parameters['sale_area_'.$value['id']] ) === true )
					$recalculate = true;
			}
		}
		else
		{
			global $wpdb;
			$recalculate = $wpdb->get_var("SELECT COUNT(*) AS c FROM `".USAM_TABLE_STORAGE_META."` WHERE meta_key LIKE 'sale_area_%'"); 	
			$wpdb->query("DELETE FROM `".USAM_TABLE_STORAGE_META."` WHERE meta_key LIKE 'sale_area_%'"); 
		}
		if ( $recalculate )
			usam_recalculate_stock_products();	
		return true;
	}	
	
	private static function update_storage_metadata( $id, $parameters ) 
	{	
		foreach(['images', 'image', 'address', 'index', 'description', 'schedule', 'email', 'phone', 'latitude', 'longitude', 'period_from', 'period_to', 'period_type'] as $key )
			if( isset($parameters[$key]) )
				usam_update_storage_metadata( $id, $key, $parameters[$key] );
	}	
		
	public static function get_storages_pickup( WP_REST_Request $request, $parameters = null ) 
	{
		return self::get_storages( $request, $parameters );	
	}
			
	protected static function get_managers( WP_REST_Request $request, $parameters = [] ) 
	{		
		$results = [];
		$users = usam_get_contacts(['orderby' => 'name','source' => 'employee', 'fields' => ['user_id','appeal']]);
		foreach( $users as $user )
		{				
			if ( $user->user_id )
				$results[] = ['id' => $user->user_id, 'name' => $user->appeal];
		}		
		return $results;
	}
	
	protected static function get_contacts_users( WP_REST_Request $request, $parameters = [] ) 
	{		
		$results = [];
		$args = ['orderby' => 'name', 'fields' => ['user_id','appeal']];
		if ( !empty($parameters['source']) )
			$args['source'] = array_map('sanitize_title', (array)$parameters['source']);
		$users = usam_get_contacts( $args );
		foreach( $users as $user )
		{				
			if ( $user->user_id )
				$results[] = ['id' => $user->user_id, 'name' => $user->appeal];
		}			
		return $results;
	}
	
	protected static function get_post_author( WP_REST_Request $request, $parameters = [] ) 
	{		
		global $wpdb;
		$results = [];
		$ids = $wpdb->get_col( "SELECT post_author FROM $wpdb->posts WHERE post_type = 'usam-product' GROUP BY post_author" );	
		if ( $ids )
		{
			$users = usam_get_contacts(['orderby' => 'name','source' => 'employee', 'fields' => ['user_id','appeal'], 'user_id' => $ids]);
			foreach( $users as $user )
			{				
				$results[] = ['id' => $user->user_id, 'name' => $user->appeal];
			}	
		}
		return $results;
	}
	
	protected static function get_document_discounts( WP_REST_Request $request, $parameters = null ) 
	{			
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );	
		
		if ( !empty($parameters['orderby']) )
			$parameters['orderby'] = sanitize_title( $parameters['orderby'] );
		else
			$parameters['orderby'] = 'date';
		if ( !empty($parameters['order']) )
			$parameters['order'] = sanitize_title( $parameters['order'] );
		else
			$parameters['order'] = 'DESC';
		if ( !empty($parameters['type_rule']) )
			$parameters['type_rule'] = array_map('sanitize_title', (array)$parameters['type_rule']);	
		
		require_once( USAM_FILE_PATH . '/includes/document/document_discounts_query.class.php');
		$query = new USAM_Document_Discounts_Query( $parameters );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			foreach( $items as &$item ) 
				$item->name = usam_get_discount_rule_name( $item );
			$results = ['count' => $query->get_total(), 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	protected static function get_discounts( WP_REST_Request $request, $parameters = null ) 
	{			
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );	
		
		if ( !empty($parameters['orderby']) )
			$parameters['orderby'] = sanitize_title( $parameters['orderby'] );
		else
			$parameters['orderby'] = 'date';
		if ( !empty($parameters['order']) )
			$parameters['order'] = sanitize_title( $parameters['order'] );
		else
			$parameters['order'] = 'DESC';		
		require_once( USAM_FILE_PATH . '/includes/product/discount_rules_query.class.php');
		$query = new USAM_Discount_Rules_Query( $parameters );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			foreach( $items as &$item ) 
				$item->name = usam_get_discount_rule_name( $item );
			$results = ['count' => $query->get_total(), 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function get_payers( WP_REST_Request $request, $parameters = null ) 
	{		
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters );
		
		$types_payers = usam_get_group_payers( self::$query_vars );	
		if ( !empty($types_payers) )
			$results = ['count' => count($types_payers), 'items' => $types_payers];
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function get_site_triggers( WP_REST_Request $request, $parameters = null ) 
	{		
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters );
		
		$triggers = usam_get_site_triggers( self::$query_vars );	
		$results = [];
		foreach ( $triggers as $id => $name ) 
		{
			$results[] = ['id' => $id, 'name' => $name];
		} 
		return ['count' => count($results), 'items' => $results];
	}
	
	public static function get_type_prices(WP_REST_Request $request, $parameters = null) 
	{			
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );			
		$autocomplete = false;
		if ( isset($parameters['fields']) )
		{
			if( $parameters['fields'] == 'autocomplete' )
				$autocomplete = true;
			else
				self::$query_vars['fields'] = $parameters['fields'];	
		}		
		if ( isset($parameters['base_type']))
			self::$query_vars['base_type'] = absint($parameters['base_type']);	
		if ( !empty($parameters['type']))
			self::$query_vars['type'] = sanitize_title($parameters['type']);	
		$type_prices = usam_get_prices( self::$query_vars );		
		if ( self::$interface_filters || $autocomplete )
		{
			$results = [];
			foreach ( $type_prices as $type_price )
				$results[] = ['id' => $type_price['code'], 'name' => $type_price['title']];
			$type_prices = $results;
		}	
		return ['count' => count($type_prices), 'items' => $type_prices];
	}	
	
	public static function get_roles(WP_REST_Request $request, $parameters = null) 
	{			
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );			
	
		require_once ABSPATH . 'wp-admin/includes/user.php';
		$roles = get_editable_roles();	
		if( !empty($parameters['fields']) )
		{
			if( $parameters['fields'] == 'code=>name' )
			{
				$results = [];
				$results['notloggedin'] = __('Не вошел в систему','usam');
				foreach ($roles as $role => $info) 
				{
					$results[$role] = translate_user_role( $info['name'] );
				}
				$roles = $results;
			}
		}
		return ['count' => count($roles), 'items' => $roles];
	}
		
	public static function get_type_price( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		return usam_get_data($id, 'usam_type_prices');
	}
	
	public static function insert_type_price( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		return usam_insert_type_price( $parameters );	
	}

	public static function update_type_price( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$parameters = usam_get_data($id, 'usam_type_prices');
		$new = array_merge( $parameters, $new );
		return usam_edit_data( $new, $id, 'usam_type_prices' );	
	}	
	
	public static function delete_type_price( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );		
		return usam_delete_data( $id, 'usam_type_prices' );
	}	
			
	public static function get_weekdays(WP_REST_Request $request, $parameters = []) 
	{	
		return [['id' => '2', 'name' => __('Понедельник','usam')], ['id' => '3', 'name' => __('Вторник','usam')], ['id' => '4', 'name' => __('Среда','usam')], ['id' => '5', 'name' => __('Четверг','usam')], ['id' => '6', 'name' => __('Пятница','usam')], ['id' => '7', 'name' => __('Суббота','usam')], ['id' => '1','name' => __('Воскресение','usam')]];
	}	
	
	public static function get_locations( WP_REST_Request $request ) 
	{					
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );	
		
		if ( isset($parameters['fields']) && $parameters['fields'] == 'autocomplete' )
			unset(self::$query_vars['fields']);
			
		$query = new USAM_Locations_Query( self::$query_vars );
		$found_locations = $query->get_results();
		if ( !empty($found_locations) )
		{
			$items = [];		
			$count = $query->get_total();						
			$cache = [];
			$ids = [];
			foreach( $found_locations as $location ) 
			{
				$cache[$location->id] = ['ids' => array_values(usam_get_address_locations( $location->id, 'id' )), 'location' => $location];	
				$ids = array_merge( $ids, $cache[$location->id]['ids'] );	
			}
			$ids = array_unique($ids);
			$locations = usam_get_locations(['include' => $ids]);
			foreach ( $cache as $location_id => $value)
			{
				$str = array();
				foreach( $value['ids'] as $id )
				{
					foreach ( $locations as $loc )
					{
						if ( $loc->id == $id )
						{
							$str[] = $loc->name;
							break;
						}
					}
				}
				$x = $value['location'];
				$x->name = implode(', ', $str);
				$items[] = $x;
			}	
			$results = ['count' => $count, 'items' => $items];	
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;	
	}
			
	public static function get_point_your_company_map( WP_REST_Request $request ) 
	{ 				
		$company = usam_shop_requisites();	
		$output = '';
		if ( !empty($company['latitude']) && !empty($company['longitude']) )
		{
			$points = [['longitude' => (float)$company['longitude'], 'latitude' => (float)$company['latitude'], 'title' => $company['name'], 'description' => '']];
			return ['points' => $points, 'longitude' => (float)$company['longitude'], 'latitude' => (float)$company['latitude']];
		}
		else
			return ['points' => [], 'longitude' => (float)$company['longitude'], 'latitude' => (float)$company['latitude']];
	}	
	
	public static function get_points_partners( WP_REST_Request $request ) 
	{ 
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters );
		self::$query_vars['type'] = 'partner';
		self::$query_vars['cache_meta'] = true;
		$companies = usam_get_companies( self::$query_vars );	
		$points = array();
		foreach ($companies as $company)
		{
			$partner = array();
			$partner['latitude'] = (float)usam_get_company_metadata( $company->id, 'latitude' );
			$partner['longitude'] = (float)usam_get_company_metadata( $company->id, 'longitude' );
			if( $partner['latitude'] !== '' && $partner['longitude'] !== '' )
			{
				$partner['title'] = (string)usam_get_company_metadata( $company->id, 'company_name' );
				$partner['map_description'] = "<div class='partners_map__address'>".__("Адрес","usam").": ".usam_get_company_metadata( $company->id, 'contactaddress' )."</div>"; 
				$points[] = $partner;
			}
		}		
		$company = usam_shop_requisites_shortcode();
		return ['points' => $points, 'longitude' => !empty($company['longitude'])?(float)$company['longitude']:20.495988051123053, 'latitude' => !empty($company['latitude'])?(float)$company['latitude']:54.71082307941693]; 
	}	
	
	public static function get_points_delivery( WP_REST_Request $request ) 
	{					
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters );			

		$cart = usam_core_setup_cart( false );	
		if( !$cart )
			return [];
		$location_id = !empty($parameters['location_id']) ? $parameters['location_id'] : usam_get_customer_location();		
		$location = usam_get_location( $location_id );
		$points_city = isset($location['name'])?$location['name']:'';
		$branch_number = false;
		if ( !empty($parameters['search']) )
		{
			if ( is_numeric($parameters['search']) )
			{
				$branch_number = true;			
				self::$query_vars['search_columns'] = ['branch_number'];
			}
			else
				self::$query_vars['search_columns'] = ['address', 'title'];
		}
		self::$query_vars['location_id'] = [0, $location_id];
		self::$query_vars = array_merge(['issuing' => $parameters['issuing'], 'orderby' => 'location_id', 'order' => 'DESC', 'cache_meta' => true, 'cache_location' => true], self::$query_vars );
		if ( empty($parameters['product_id']) )
		{
			$products = $cart->get_products( );	
			$shipping = $cart->get_shipping_method(); 
			if ( isset($shipping->storage_owner) )
				self::$query_vars['owner'] = $shipping->storage_owner;	
		}
		$center_customer_location = false;			
		$selected_store = $cart->get_property( 'storage_pickup' );	
		$latitude = $longitude = 0;	
		if ( $location_id )
		{					
			$latitude = (float)usam_get_location_metadata( $location_id, 'latitude' );
			$longitude = (float)usam_get_location_metadata( $location_id, 'longitude' );		
			if ( $longitude > 0 && $latitude > 0 && !$branch_number )
			{
				self::$query_vars['meta_query'] = ['relation' => 'OR', 
					['key' => 'latitude', 'compare' => 'NOT EXISTS'], 
					['key' => 'latitude', 'compare' => 'IN', 'value' => ['','0']],
					['key' => 'latitude', 'compare' => 'BETWEEN', 'value' => [$latitude-2, $latitude+2]],
					['key' => 'longitude', 'compare' => 'BETWEEN', 'value' => [$longitude-2, $longitude+2]]
				];				
			}
		}		
		if ( !isset(self::$query_vars['owner']) )
			self::$query_vars['owner'] = '';
		self::$query_vars['active'] = 1;
		$query = new USAM_Storages_Query( self::$query_vars );
		$storages = $query->get_results();
		$count = $query->get_total();
		
		$results = [];		
		foreach( $storages as $key => $storage )
		{			
			$storage_latitude = (float)usam_get_storage_metadata( $storage->id, 'latitude');
			$storage_longitude = (float)usam_get_storage_metadata( $storage->id, 'longitude');		
			$stock = 0;
			if ( !empty($parameters['product_id']) )
			{
				$stock = usam_get_stock_in_storage($storage->id, $parameters['product_id'], 'short');
				$in_stock = usam_get_stock_in_storage($storage->id, $parameters['product_id'])?1:0;
			}
			else
			{
				$in_stock = 1;
				foreach( $products as $product )
				{
					$stock = usam_get_stock_in_storage($storage->id, $product->product_id);
					if ( $stock <= $product->quantity )
					{						
						$in_stock = 0;
						break;
					}
				}
			}			
			$location = usam_get_location( $storage->location_id );
			$city = isset($location['name'])?$location['name'].", ":'';
			$phone = usam_get_storage_metadata( $storage->id, 'phone');
			$schedule = htmlspecialchars(usam_get_storage_metadata( $storage->id, 'schedule'));
			$address = usam_get_storage_metadata( $storage->id, 'address');	
			$title = $address?$city.$address:$storage->title;
			$title = htmlspecialchars($title);
			$email = esc_html(usam_get_storage_metadata( $storage->id, 'email'));			
			ob_start();				
			?>			
			<div class="store_row">			
				<?php 
				if ( $phone ) 
				{
					?><div class="phone"><?php _e('Телефон', 'usam'); ?>: <?php echo $phone; ?></div><?php 
				}
				if ( $email ) 
				{
					?><div class="email"><?php _e('Электронная почта', 'usam'); ?>: <a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></div><?php 
				}										
				if ( $schedule )
				{ 
					?><div class="shud"><?php _e('Режим работы', 'usam'); ?>: <?php echo $schedule; ?></div><?php 
				}							
				?>			
			</div>				
			<?php			
			$map_description = ob_get_clean();			
			$results[] = ['id' => $storage->id, 'title' => $title, 'phone' => usam_get_phone_format($phone), 'schedule' => $schedule, 'available' => $in_stock, 'delivery_period' => $in_stock?__("Забрать сегодня",'usam'):usam_get_storage_delivery_period($storage->id), 'map_description' => $map_description, 'longitude' => $storage_longitude, 'latitude' => $storage_latitude, 'stock' => $stock, 'branch_number' => usam_get_storage_metadata($storage->id, 'branch_number')];	
			if ( $storage_longitude > 0 && $storage_latitude > 0 && $latitude == 0 )
			{
				$latitude = $storage_latitude;
				$longitude = $storage_longitude;
			}							
		}		
		$latitude = $latitude > 0 ? $latitude : 54.71082307941693;
		$longitude = $longitude > 0 ? $longitude : 20.495988051123053;	
		return ['points' => $results, 'count' => $count, 'city' => $points_city, 'selected' => $selected_store, 'latitude' => $latitude, 'longitude' => $longitude];
	}	
	
	public static function get_properties( WP_REST_Request $request ) 
	{		
		self::$query_vars = self::get_parameters( $request );	
		if ( !current_user_can('universam_api') && !usam_check_is_employee() || !isset(self::$query_vars['active']) )				
			self::$query_vars['active'] = 1;
		
		self::$query_vars['access'] = true;	
		
		if ( !empty(self::$query_vars['current_role']) )
		{
			$user = wp_get_current_user();
			$roles = empty($user->roles)?['notloggedin']:$user->roles;
			$meta_query = ['relation' => 'AND', ['relation' => 'OR',['key' => 'role', 'compare' => 'IN', 'value' => $roles], ['key' => 'role', 'compare' => 'NOT EXISTS']]];	
		}	
		if( !empty($meta_query) )
			self::$query_vars['meta_query'] = $meta_query;			
		if( isset(self::$query_vars['add_fields']) )
		{
			if ( in_array('roles', self::$query_vars['add_fields']) )
				self::$query_vars['cache_meta'] = true;
		}
		$query = new USAM_Properties_Query( self::$query_vars );
		$items = $query->get_results();
		if ( !empty($items) )
		{		
			foreach( $items as &$item )
			{
				if ( isset(self::$query_vars['add_fields']) )
				{
					if ( in_array('post', self::$query_vars['add_fields']) && self::$query_vars['post_id'] )
						$item->value = (string)usam_get_post_meta(self::$query_vars['post_id'], $item->code);
					if ( in_array('roles', self::$query_vars['add_fields']) )
						$item->roles = usam_get_array_metadata($item->id, 'property', 'role');
				}
			}  
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function get_property( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		return usam_get_property( $id );
	}	
	
	public static function get_code_property( WP_REST_Request $request ) 
	{		
		$code = $request->get_param( 'code' );	
		$type = $request->get_param( 'type' );	
		$property = usam_get_properties(['access' => true, 'type' => $type, 'code' => $code, 'number' => 1]);		
		if ( $property )
		{
			$metadatas = usam_get_property_metadata( $property['id'] );
			foreach($metadatas as $metadata )
				$property[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );
		}
		return $property;
	}	
		
	public static function insert_property( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		return usam_insert_property( $parameters );
	}

	public static function update_property( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		return usam_update_property( $id, $parameters );
	}	
	
	public static function update_properties( WP_REST_Request $request ) 
	{				
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		$i = 0;
		foreach( $parameters['items'] as $item )
		{
			$id = 0;			
			if ( !empty($item['id']) )
				$id = absint($item['id']);	
			if ( usam_update_property( $id, $item ) )
				$i++;
		}			
		return $i;
	}
		
	public static function delete_property( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		return usam_delete_property( $id );
	}	
			
	public static function get_statuses( WP_REST_Request $request, $parameters = [] ) 
	{		
		if ( empty($parameters) )
			$parameters = self::get_parameters( $request );
		require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');	
		$statuses = usam_get_object_statuses( $parameters );
		if ( self::$interface_filters )
		{
			$results = [];
			foreach( $statuses as $status )
				$results[] = ['id' => $status->internalname, 'name' => $status->name];
			return $results;
		}
		else
			return $statuses;
	}	
	
	public static function get_property_groups( WP_REST_Request $request, $parameters = [] ) 
	{		
		self::$query_vars = self::get_parameters( $request );	
		$query = new USAM_Property_Groups_Query( self::$query_vars );
		$items = $query->get_results();		
		if ( !empty($items) )
		{				
			foreach( $items as $k => $item )
			{
				if ( $item->type == 'order' )
					$items[$k]->type_payers = array_map('intval', (array)usam_get_array_metadata($item->id, 'property_group', 'type_payer'));
			}
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function get_currencies( WP_REST_Request $request, $parameters = [] ) 
	{		
		self::$query_vars = self::get_parameters( $request );	
		$autocomplete = false;
		if( !empty(self::$query_vars['fields']) && 'autocomplete' == self::$query_vars['fields'] ) 	
		{
			self::$query_vars['fields'] = ["name", "code"];	
			self::$query_vars['orderby'] = "name";	
			self::$query_vars['order'] = "ASC";	
			$autocomplete = true;
		}
		$items = usam_get_currencies( self::$query_vars );		
		if ( !empty($items) )
		{							
			foreach( $items as $k => $item )
			{
				if ( $autocomplete )
				{
					$items[$k]->id = $item->code;
					$items[$k]->name = "$item->code ($item->name)";
				}
			}
			$results = ['count' => count($items), 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}	
	
	public static function get_files( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
							
		self::$query_vars['orderby'] = !empty(self::$query_vars['orderby'])?self::$query_vars['orderby']:'name';
		self::$query_vars['order'] = !empty(self::$query_vars['order'])?self::$query_vars['order']:'ASC';				
		if ( empty(self::$query_vars['include']) )
		{			
			if ( !empty(self::$query_vars['status']) )
				self::$query_vars['status'] = self::$query_vars['status'];	
			else
			{
				if ( isset(self::$query_vars['folder']) )
					self::$query_vars['folder_id'] = self::$query_vars['folder'];
			}			
		}
		self::$query_vars['type__not_in'] = 'temporary';
		self::$query_vars['number'] = 1000;
		if ( !empty(self::$query_vars['purchased_user_files']) )
			self::$query_vars['purchased_user_files'] = get_current_user_id();
		if ( current_user_can('view_my_files') && !current_user_can('view_all_files') && !current_user_can('universam_api') )
			self::$query_vars['user_id'] = get_current_user_id();
		elseif ( !current_user_can('view_my_files') && !current_user_can('view_all_files') && !current_user_can('universam_api') )
		{
			self::$query_vars['status'] = ['closed', 'open'];
			if ( !empty(self::$query_vars['purchased_user_files']) )
				self::$query_vars['purchased_user_files'] = get_current_user_id();
			else
				self::$query_vars['user_id'] = get_current_user_id();
		}
		elseif ( !empty(self::$query_vars['user_id']) )
		{
			if ( self::$query_vars['user_id'] == 'current' )
				self::$query_vars['user_id'] = get_current_user_id();
		}	
		$query = new USAM_Files_Query( self::$query_vars );	
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			foreach( $items as $key => $item )
			{
				$items[$key]->icon = usam_get_file_icon( $item->id );		
				$filepath = USAM_UPLOAD_DIR.$item->file_path;
				$items[$key]->url = get_bloginfo('url').'/file/'.$item->code;
				$items[$key]->shortname = usam_get_formatted_filename( $item->name );				
				$items[$key]->size = file_exists($filepath)?size_format( filesize($filepath) ):'';	
			}			
			$count = $query->get_total();			
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];	
		return $results;
	}
	
	public static function upload_file( WP_REST_Request $request ) 
	{		
		$args = $request->get_json_params();		
		if ( !$args )
			$args = $request->get_body_params();	
		
		$parameters = $request->get_file_params();
		$access = false;
		if ( !empty($args['property']) )
		{
			$property = usam_get_property( $args['property'] );
			if ( !empty($property['active']) )
			{
				$property_roles = usam_get_array_metadata($property['id'], 'property', 'role');	
				if( empty($property_roles) || in_array($property_roles, $roles) )
				{
					$file_types = usam_get_property_metadata($property['id'], 'file_types');
					if ( $file_types )
					{		
						$file_types = explode(',', $file_types);
						$file_types = array_map('trim', $file_types);
						$extension = pathinfo($parameters['file']['name'], PATHINFO_EXTENSION);
						if( in_array(strtolower($extension), $file_types) )	
							$access = true;
						else
							return ['status' => 'error', 'error_message' => __('В этом формате нельзя загружать. Загрузите в другом формате.','usam')];
					}
					else
						$access = true;
				}				
			}				
		}
		elseif ( current_user_can('view_files') || usam_check_current_user_role('administrator') )
			$access = true;
				
		if ( !$access )
			return new WP_Error( 'db_error', 'Доступ закрыт', ['status' => 404]);
			
		$results = usam_fileupload( $parameters['file'], null, $args );
		return $results;
	}
	
	public static function no_image_uploaded( WP_REST_Request $request ) 
	{			
		$parameters = $request->get_file_params();	 
		if( !is_dir(USAM_NO_IMAGE_DIR) )
		{			
			if ( !mkdir(USAM_NO_IMAGE_DIR, 0755, true) ) 
				return false;
		}			
		$data = @getimagesize($parameters['file']['tmp_name']); 
		if( preg_match('{image/(.*)}is', $data['mime'], $p) ) 
		{
			$dirHandle = opendir(USAM_NO_IMAGE_DIR);
			while( false !== ($file = readdir($dirHandle)) )
			{ 
				if( is_file(USAM_NO_IMAGE_DIR.$file) )
					unlink(USAM_NO_IMAGE_DIR.$file);
			}				
			$ext = pathinfo($parameters['file']['name'], PATHINFO_EXTENSION);
			if( move_uploaded_file($parameters['file']['tmp_name'], USAM_NO_IMAGE_DIR."no-image-uploaded.".$ext) )
				return usam_get_no_image_uploaded_file().'?v='.time();
		}
		return false;
	}	
	
	public static function get_file( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );		
		$file = usam_get_file( $id );
		$file['url'] = get_bloginfo('url').'/file/'.$file['code'];
		$file['icon'] = usam_get_file_icon( $id );
		return $file;
	}
	
	public static function save_file( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$args = [];
		if ( !empty($parameters['title']) )
			$args['title'] = trim(sanitize_text_field(stripslashes($parameters['title'])));
		if ( !empty($parameters['status']) )
			$args['status'] = sanitize_title($parameters['status']);	
		if ( isset($parameters['folder_id']) )
			$args['folder_id'] = absint($parameters['folder_id']);		
		return usam_update_file( $id, $args );
	}	
	
	public static function delete_file( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );				
		return usam_delete_file( $id );
	}
		
	public static function get_folders( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
									
		self::$query_vars['orderby'] = !empty($parameters['orderby'])?$parameters['orderby']:'name';
		self::$query_vars['order'] = !empty($parameters['order'])?$parameters['order']:'ASC';
		if ( current_user_can('view_my_files') && !current_user_can('view_all_files') && !current_user_can('universam_api') )
			self::$query_vars['user_id'] = get_current_user_id();
		if ( !empty(self::$query_vars['user_id']) )
		{
			if ( self::$query_vars['user_id'] == 'current' )
				self::$query_vars['user_id'] = get_current_user_id();
		}		
		if ( empty(self::$query_vars['include']) )
		{			
			if ( !empty(self::$query_vars['status']) )
				self::$query_vars['status'] = self::$query_vars['status'];
			if ( isset(self::$query_vars['parent']) )
				self::$query_vars['parent_id'] = absint(self::$query_vars['parent']);		
		}			
		self::$query_vars['number'] = 100;
		$query = new USAM_Folders_Query( self::$query_vars );			
		$items = $query->get_results();		 
		if ( !empty($items) )
		{
			foreach( $items as $key => $item )
			{
				$icon = $item->count?'folder':'empty_folder';	
				$items[$key]->icon = USAM_SVG_ICON_URL."#{$icon}-usage";
			}
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];		
		if ( !empty(self::$query_vars['breadcrumbs']) )
		{
			if ( !empty(self::$query_vars['parent_id']) )
				$results['breadcrumbs'] = usam_get_folders(['ancestor' => self::$query_vars['parent_id'], 'orderby' => 'include']);	
			else
				$results['breadcrumbs'] = [];
		}
		return $results;
	}
	
	public static function add_folder( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$name = !empty($parameters['name'])?sanitize_text_field(stripslashes($parameters['name'])):__('Новая папка');
		$parent_id = !empty($parameters['parent'])?absint($parameters['parent']):0;
		$user_id = isset($parameters['user_id'])?absint($parameters['user_id']):get_current_user_id();
		$id = usam_insert_folder(['name' => $name, 'parent_id' => $parent_id, 'user_id' => $user_id]);
		if ( $id )
		{
			$folder = usam_get_folder( $id );
			$folder['icon'] = USAM_SVG_ICON_URL."#empty_folder-usage";
			return $folder;
		}
		else
			return new WP_Error( 'db_error', 'Invalid author', ['status' => 404]);		
	}	
	
	public static function get_folder( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );		
		$folder = usam_get_folder( $id );
		$folder['icon'] = USAM_SVG_ICON_URL."#empty_folder-usage";
		return $folder;
	}
		
	public static function save_folder( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$args = [];
		if ( !empty($parameters['name']) )
			$args['name'] = trim(sanitize_text_field(stripslashes($parameters['name'])));
		if ( !empty($parameters['status']) )
			$args['status'] = sanitize_title($parameters['status']);	
		if ( isset($parameters['parent']) )
			$args['parent_id'] = sanitize_title($parameters['parent']);	
		return usam_update_folder( $id, $args );
	}	
	
	public static function delete_folder( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		return usam_delete_folder( $id );
	}
	
	public static function get_notes( WP_REST_Request $request ) 
	{		
		$user_id = get_current_user_id();
		if ( $user_id )
		{
			require_once( USAM_FILE_PATH . '/includes/crm/notes_query.class.php' );	
			$notes = usam_get_notes(['user_id' => $user_id, 'order' => 'DESC']);			
			return $notes;			
		}
		else
			return new WP_Error( 'no_author', 'Invalid author', array( 'status' => 404 ) );
	}
		
	public static function save_note( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		$id = $request->get_param( 'id' );
		
		require_once( USAM_FILE_PATH . '/includes/crm/note.class.php' );		
		$note = usam_get_note($id );
		if ( $note && $note['user_id'] == get_current_user_id() )
		{			
			$update['note'] = sanitize_textarea_field(stripslashes($parameters['note']));
			return usam_update_note($id, $update);
		}
		else
			return new WP_Error( 'no_author', 'Invalid author', array( 'status' => 404 ) );		
	}	
	
	public static function delete_note( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );
		
		require_once( USAM_FILE_PATH . '/includes/crm/note.class.php' );		
		$note = usam_get_note( $id );
		if ( $note && $note['user_id'] == get_current_user_id() )
			return usam_delete_note($id);
		else
			return new WP_Error( 'no_author', 'Invalid author', array( 'status' => 404 ) );		
	}	
	
	public static function insert_note( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		require_once( USAM_FILE_PATH . '/includes/crm/note.class.php' );	
		$update['note'] = sanitize_textarea_field(stripslashes($parameters['note']));
		$update['user_id'] = get_current_user_id();
		return usam_insert_note( $update );
	}
	
	public static function get_popups( WP_REST_Request $request ) 
	{	
		require_once(USAM_FILE_PATH . '/includes/theme/banners_query.class.php');					
		require_once(USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php');
		
		$parameters = self::get_parameters( $request );	
		$post_id = isset($parameters['page_id'])?absint($parameters['page_id']):0;	
		
		$query_vars = ['acting_now' => 1, 'conditions' => [['key' => 'actuation_time', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC']]];
		$items = usam_get_banners( $query_vars );	
		$results = ['banners' => [], 'webforms' => []];
		foreach ( $items as $item )
		{
			ob_start();
			$banner = (array)$item;
			include usam_get_template_file_path( 'banner', 'modaltemplate' );
			$item->modal = ob_get_clean();		
			$results['banners'][] = $item;
		}	
		$items = usam_get_webforms( $query_vars );
		foreach ( $items as $item )
		{
			$webform = (array)$item;
			ob_start();
			include usam_get_template_file_path( 'webform', 'modaltemplate' );
			$item->modal = ob_get_clean();
			$item->template = usam_get_webform_template( $webform, $post_id, true, !$request->get_header('X-WP-Admin') );	
			$results['webforms'][] = $item;
		}
		return $results;
	}	
	
	public static function get_banners( WP_REST_Request $request, $parameters = null ) 
	{	
		require_once(USAM_FILE_PATH . '/includes/theme/banners_query.class.php');	
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );			
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
		if ( !isset(self::$query_vars['active']) )
			self::$query_vars['active'] = 1;		
	
		$query = new USAM_Banners_Query( self::$query_vars );	
		$items = $query->get_results();		
		if ( !empty($items) )
		{
		/*	foreach( $items as $key => $item )
			{
				
			}			*/
			$count = $query->get_total();			
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];	
		return $results;
	}	
	
	public static function get_banner( WP_REST_Request $request ) 
	{
		$id = $request->get_param( 'id' );	
		require_once( USAM_FILE_PATH . '/includes/theme/banner.class.php' );
		$banner = usam_get_banner( $id );	
		return $banner;
	}	
	
	public static function delete_banner( WP_REST_Request $request ) 
	{
		$id = $request->get_param( 'id' );	
		require_once( USAM_FILE_PATH . '/includes/theme/banner.class.php' );
		return usam_delete_banner( $id );
	}	
		
	public static function insert_banner( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH . '/includes/theme/banner.class.php' );		
		$locations = !empty($parameters['locations']) ? array_map('sanitize_title', $parameters['locations']) : [];
		$banner_id = usam_insert_banner( $parameters, $locations );		
		return $banner_id;		
	}

	public static function update_banner( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH . '/includes/theme/banner.class.php' );
		$locations = !empty($parameters['locations']) ? array_map('sanitize_title', $parameters['locations']) : [];
		return usam_update_banner( $id, $parameters, $locations );
	}
	
	public static function get_location_banners( WP_REST_Request $request, $parameters = null ) 
	{	
		$items = [];
		foreach ( usam_register_banners() as $key => $title ) 
		{	
			$items[] = ['id' => $key, 'title' => $title];
		}
		return ['count' => count($items), 'items' => $items];	
	}	
			
	public static function get_menu( WP_REST_Request $request ) 
	{		
		$menu_id = $request->get_param( 'menu_id' );
		$locations = get_nav_menu_locations();	
		if( $locations && isset( $locations[$menu_id] ) )
			$results = wp_get_nav_menu_items( $locations[$menu_id] );
		else
			return new WP_Error( 'menu_id', 'Invalid menu id', ['status' => 404]);		
		return $results;	
	}	
	
	public static function get_menus( WP_REST_Request $request ) 
	{				
		$parameters = self::get_parameters( $request );		
		$locations = get_nav_menu_locations();	 
		$results = [];
		if( $locations )
		{
			$fields = !empty($parameters['fields'])?$parameters['fields']:'';
			$menu_items = [];	
			if ( !empty($parameters['location']) )
			{ 
				$_locations = is_string($parameters['location'])?[$parameters['location']]:$parameters['location'];
				foreach( $_locations as $location )
				{			
					if( isset($locations[$location]) )
						$menu_items[$location] = wp_get_nav_menu_items( $locations[$location] );
				}
			}
			else
			{
				foreach( $locations as $location => $menu_id )
					$menu_items[$location] = wp_get_nav_menu_items( $menu_id );
			}			
			foreach( $menu_items as $location => $items )
			{
				foreach( $items as $item )
				{					
					$item->ID = (int)$item->ID;
					$item->menu_item_parent = (int)$item->menu_item_parent;
					$item->object_id = (int)$item->object_id;				
					if ( $fields == 'lists' )
					{
						$item->location = $location;
						$results[] = $item;
					}
					else
						$results[$location][] = $item;						
				}
			}
		}	
		return $results;	
	}	
	
	public static function get_processes( WP_REST_Request $request ) 
	{
		$processes = usam_get_system_process( );
		$items = [];
		if ( $processes )
		{
			foreach( $processes as $id => $item )
			{
				$item['percent'] = $item['count'] == 0 ? 0 : round($item['done']*100/$item['count'],0);
				$item['date'] = usam_local_formatted_date( $item['date_insert'] );
				$item['id'] = $id;				
				$items[] = $item;
			}	
		}		
		return ['count' => count($items), 'items' => $items];
	}
	
	public static function delete_process( WP_REST_Request $request ) 
	{				
		$process_id = $request->get_param( 'process_id' );					
		return usam_delete_system_process( $process_id );
	}		
	
	public static function save_process( WP_REST_Request $request ) 
	{				
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		if ( $parameters['status'] == 'start' || $parameters['status'] == 'pause' )
		{
			$process_id = $request->get_param( 'process_id' );					
			return usam_update_system_process( $process_id, $parameters );
		}
		else
			return false;
	}	

	public static function start_importer( WP_REST_Request $request ) 
	{				
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		if( !empty($parameters['template_id']) )
		{
			if ( !empty($parameters['file']) )
			{
				$rule = usam_get_exchange_rule( $parameters['template_id'] );
				$metas = usam_get_exchange_rule_metadata( $rule['id'] );
				foreach($metas as $metadata )
					$rule[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );
				$rule['file_data'] = $parameters['file'];
				$rule['exchange_option'] = 'local';		
				$rule['process'] = '';			
				$result = usam_start_exchange( $rule );				
			}			
			else
				$result = usam_start_exchange( $parameters['template_id'] );
		}
		else
		{			
			$rule_type = $parameters['type'];			
			if ( $rule_type == 'location_import'  )
			{
				global $wpdb;
				if ( !empty($parameters['rule']['delete_existing']) ) 
					$wpdb->query("TRUNCATE TABLE `".USAM_TABLE_LOCATION."`");
			}
			$source = sanitize_text_field($parameters['source']);			
			if ( $rule_type == 'location_import' && $source != 'file' )
			{
				$args = $parameters['rule'];
				$args['source'] = $source;		
				$result = usam_create_system_process( __("Загрузка местоположений","usam"), $args, 'loading_locations', count($args['countries']), 'loading_locations' );
			}
			else
			{
				$rule = ['id' => 0, 'name' => __("Ручной импорт", "usam"), 'exchange_option' => 'local', 'process' => ''];			
				$rule['encoding'] = isset($parameters['file_settings']['encoding'])?sanitize_title($parameters['file_settings']['encoding']):'';
				$rule['type_file'] = isset($parameters['file_settings']['type_file'])?sanitize_title($parameters['file_settings']['type_file']):'';					
				$rule['file_data'] = $parameters['file'];
				$rule['type'] = $rule_type;
				$rule['start_line'] = absint($parameters['file_settings']['start_line']);
				$rule['end_line'] = absint($parameters['file_settings']['end_line']);			
				$rule['columns'] = array_map('sanitize_title', $parameters['columns']);
				$rule = array_merge($rule, $parameters['rule']);
				$process_id = 'load_data-'.time();	
				$result = usam_create_system_process( __("Подготовка обмена", "usam"), $rule, 'preparation_exchange_data', 1, $process_id );				
			}			
		}		
		return usam_get_callback_messages(['add_event' => $result]);
	}	
	
	public static function get_importer_file_data( WP_REST_Request $request ) 
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();

		ini_set("max_execution_time", 4600 );			
		
		$directory = USAM_UPLOAD_DIR.'exchange/';
		$file = sanitize_text_field($parameters['file']);		
		$filepath = $directory.$file;		
		$type_file = sanitize_title($parameters['file_settings']['type_file']);	
		$args = [];
		if( !empty($parameters['count']) )
			$args['count'] = $parameters['count'];
		if( !empty($parameters['file_settings']['start_line']) )
			$args['start_line'] = absint($parameters['file_settings']['start_line']);
		if( !empty($parameters['file_settings']['encoding']) )
			$args['encoding'] = sanitize_title($parameters['file_settings']['encoding']);
		if ( $type_file )
		{
			$extension = usam_get_type_file_exchange( $type_file, 'ext' );
			$delimiter = usam_get_type_file_exchange( $type_file, 'delimiter' );		
		}
		else
		{
			$path_parts = pathinfo($filepath);
			$extension = $path_parts['extension']; 
			$delimiter = '';
		}
		switch ( $extension ) 
		{
			case 'xls':	
			case 'xlsx':				
				$data = usam_read_exel_file( $filepath, $args );	
			break;
			case 'txt':
			case 'csv':									
				$data = usam_read_txt_file( $filepath, $delimiter, $args );	
			break;
			default:
				$data = usam_read_file( $filepath, $args );
			break;
		}	
		return $data;
	}
	
	public static function importer_file_upload( WP_REST_Request $request ) 
	{ 		
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$file = $request->get_file_params();
		
		$results = ['status' => 'error', 'error_message' => __('Файл не передан','usam')];		
		if ( !empty($parameters['template_id']) )
		{
			$id = absint($parameters['template_id']);
			$rule = usam_get_exchange_rule( $id );	
			if ( $rule && $rule['exchange_option'] == 'folder' )
			{
				$results = usam_fileupload( $file['file'], null, ['folder_id' => $rule['file_data'], 'type' => 'loaded'] );
				$results['file_library'] = true;
				return $results;
			}
		}
		if( isset($file['file']) && $file['file']['error'] == 0 && isset($file['file']['tmp_name']) && file_exists($file['file']['tmp_name']) )
		{			
			$filename = sanitize_file_name(usam_sanitize_title_with_translit($file['file']['name']));		
			$filesize = filesize($file['file']['tmp_name']);
			
			$directory = USAM_UPLOAD_DIR.'exchange/';						
			$new_filename = wp_unique_filename( $directory, $filename );		
			$filepath = $directory.$new_filename;						
			if( move_uploaded_file($file['file']['tmp_name'], $filepath) )
				$results = ['status' => 'success', 'icon' => usam_get_file_icon( $filepath, 'filepath' ), 'title' => usam_get_formatted_filename( $file['file']['name'] ), 'name' => $new_filename, 'size' => size_format($filesize), 'file_library' => false];
			else
				$results['error_message'] = __('Ошибка копирования','usam');
		}
		return $results;
	}
	
	private static function update_exchange_rule_metas( $id, $parameters ) 
	{
		$metas = [];
		if( !empty($parameters['exchange_option']) && $parameters['exchange_option'] == 'email' )
		{
			$metas['to_email'] = sanitize_text_field($parameters['to_email']);
			$metas['subject'] = sanitize_text_field(stripcslashes($parameters['subject']));
		}
		foreach(['split_into_files', 'weekday', 'delete_file', 'weekday', 'status', 'source', 'from_id', 'to_id', 'from_dateinsert', 'to_dateinsert'] as $key )
		{
			if( array_key_exists($key, $parameters) )
				$metas[$key] = $parameters[$key];
		}	
		$metas['columns'] = [];		
		if( str_contains($parameters['type'], 'import') )
		{			
			$metas['columns2'] = [];				
			$metas['exception'] = [];				
			if ( !empty($parameters['columns']) )
			{ 
				$i = 0;
				foreach( $parameters['columns'] as $column )
				{
					if ( !empty($column['name']) )
					{						
						$key = $parameters['headings'] ? sanitize_text_field($column['column']) : $i;
						$metas['columns'][$key] = sanitize_text_field($column['name']);							
						$metas['exception'][$key] = !empty($column['exception']['value'])?$column['exception']:[];
						$metas['columns2'][$key] = isset($column['column2'])?sanitize_text_field($column['column2']):'';	
					}		
					$i++;
				}
			}	
		}
		else
		{
			if ( !empty($parameters['columns']) )
			{ 					
				foreach( $parameters['columns'] as $column )
				{
					if ( !empty($column['name']) )
						$metas['columns'][sanitize_text_field($column['column'])] = $column['name'];
				}
			}
		}
		if( $parameters['type'] == 'product_export' || $parameters['type'] == 'product_import' || $parameters['type'] == 'pricelist' )
		{		
			$taxonomies = get_taxonomies(['object_type' => ['usam-product']]);
			foreach( $taxonomies as $taxonomy ) 
				 if( array_key_exists($taxonomy, $parameters) )
					$metas[$taxonomy] = $parameters[$taxonomy];
				
			foreach(['type_price', 'from_day', 'to_day', 'from_price', 'to_price', 'from_stock', 'to_stock', 'from_total_balance', 'to_total_balance', 'from_views', 'to_views', 'contractor'] as $key )
			{
				if( array_key_exists($key, $parameters) )
					$metas[$key] = $parameters[$key];
			}			
			if( $parameters['type'] == 'product_import' )
				foreach(['user_id', 'change_stock', 'product_views', 'change_price', 'change_price2', 'post_status', 'selection_raw_data', 'not_updated_products_status', 'not_updated_products_stock'] as $key )
				{
					if( array_key_exists($key, $parameters) )
						$metas[$key] = $parameters[$key];
				}
			if( $parameters['type'] == 'pricelist' )
				foreach(['file_generation', 'roles'] as $key )
				{
					if( array_key_exists($key, $parameters) )
						$metas[$key] = $parameters[$key];
				}
		}
		elseif( $parameters['type'] == 'contact_export' )
		{			
			foreach(['from_ordercount', 'to_ordercount', 'from_ordersum', 'to_ordersum', 'location', 'groups', 'sex', 'from_age', 'to_age', 'post'] as $key )
			{
				if( array_key_exists($key, $parameters) )
					$metas[$key] = $parameters[$key];
			}
		}
		elseif( $parameters['type'] == 'company_export' )
		{			
			foreach(['from_ordercount', 'to_ordercount', 'from_ordersum', 'to_ordersum', 'location', 'groups', 'company_industry', 'company_group', 'company_type'] as $key )
			{
				if( array_key_exists($key, $parameters) )
					$metas[$key] = $parameters[$key];
			}
		}
		elseif( $parameters['type'] == 'order_export' )
		{			
			foreach(['from_ordersum', 'to_ordersum', 'location', 'from_productcount', 'to_productcount', 'groups'] as $key )
			{
				if( array_key_exists($key, $parameters) )
					$metas[$key] = $parameters[$key];
			}
		}	
		elseif( $parameters['type'] == 'contact_import' )
		{			
			foreach(['groups'] as $key )
			{
				if( array_key_exists($key, $parameters) )
					$metas[$key] = $parameters[$key];
			}
		}
		elseif( $parameters['type'] == 'company_import' )
		{			
			foreach(['groups'] as $key )
			{
				if( array_key_exists($key, $parameters) )
					$metas[$key] = $parameters[$key];
			}
		}
		elseif( $parameters['type'] == 'order_import' )
		{			
			foreach(['groups'] as $key )
			{
				if( array_key_exists($key, $parameters) )
					$metas[$key] = $parameters[$key];
			}
		}
		foreach( $metas as $key => $value )
			usam_update_exchange_rule_metadata( $id, $key, $value );
			
		if( !empty($metas['file_generation']) && $parameters['type'] == 'pricelist' )
		{
			require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
			$rule = usam_get_exchange_rule( $id );
			if( $rule['schedule'] == 1 )
			{
			//	$file_path = USAM_UPLOAD_DIR."exchange/exporter_".$id.".".usam_get_type_file_exchange( $rule['type_file'], 'ext' );	
				require_once( USAM_FILE_PATH . '/includes/product/product_exporter.class.php' );
				$export = new USAM_Product_Exporter( $rule['id'] );
				$i = $export->get_total();	
				usam_create_system_process( __("Создание прайс-листа", "usam" ).' - '.$rule['name'], $rule['id'], 'pricelist_creation', $i, 'exchange_'.$rule['type']."-".$rule['id'] );
			}
		}
	}
	
	public static function insert_exchange_rule( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
				
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');
		 
		$parameters['time'] = isset($parameters['time'])?date( "H:i", strtotime($parameters['time'])):'';
		$id = usam_insert_exchange_rule( $parameters );		
		USAM_API::update_exchange_rule_metas( $id, $parameters );
		return $id;
	}
	
	public static function update_exchange_rule( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
				
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
		
		usam_update_exchange_rule( $id, $parameters );		
		USAM_API::update_exchange_rule_metas( $id, $parameters );
		return true;
	}	
	
	public static function download_exchange_rule( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
		$rule = usam_get_exchange_rule( $id );		
		if ( !empty($rule) )
		{
			$file_generation = usam_get_exchange_rule_metadata( $id, 'file_generation' );
			switch( $rule['type'] )
			{	
				case 'pricelist':	
					require_once( USAM_FILE_PATH . '/includes/product/price_list.class.php' );
					$export = new USAM_Price_List( $id );
				break;
				case 'company_export':									
					require_once( USAM_FILE_PATH . '/includes/crm/company_exporter.class.php' );
					$export = new USAM_Companies_Exporter( $id );
				break;
				case 'contact_export':		
					require_once( USAM_FILE_PATH . '/includes/crm/contact_exporter.class.php' );
					$export = new USAM_Contacts_Exporter( $id );
				break;
				case 'product_export':		
					require_once( USAM_FILE_PATH . '/includes/product/product_exporter.class.php' );
					$export = new USAM_Product_Exporter( $id );
				break;
				case 'order_export':	
					if ( !current_user_can('export_order') )
						return ['access' => 1];
					require_once( USAM_FILE_PATH . '/includes/document/order_exporter.class.php' );
					$export = new USAM_Order_Exporter( $id );
				break;
				default:
					return ['access' => 1];
				break;
			}
			ob_start();
			if ( $file_generation )
			{					
				$file_path = USAM_UPLOAD_DIR."exchange/exporter_{$id}.".usam_get_type_file_exchange( $rule['type_file'], 'ext' );				
				if ( is_file( $file_path ) )
				{
					ob_start();
					readfile($file_path);
					$data = ob_get_clean();	
				}
				else
				{
					$i = $export->get_total();	
					usam_create_system_process( __("Создание прайс-листа", "usam" ).' - '.$rule['name'], $rule['id'], 'pricelist_creation', $i, 'exchange_'.$rule['type']."-".$rule['id'] );
					return ['add_event' => 1];
				}
			}	
			else
			{											
				$output = $export->start();		
				ob_start();
				if ( is_string($output) && @is_file($output) )
				{
					readfile($output);	
					$data = ob_get_clean();	
					return ['download' => "data:application/octet-stream;base64,".base64_encode($data), 'title' => $rule['name'].".zip"];
				}
				else			
					echo $output;	
				$data = ob_get_clean();								
			}	
			if ( $rule['type_file'] == 'exel' )
				return ['download' => "data:application/vnd.ms-excel;base64,".base64_encode($data), 'title' => $rule['name'].'.'.usam_get_type_file_exchange( $rule['type_file'], 'ext' )];
			else
				return ['download' => "data:application/octet-stream;base64,".base64_encode($data), 'title' => $rule['name'].'.'.usam_get_type_file_exchange( $rule['type_file'], 'ext' )];
		}	
		return false;
	}	
	
	public static function delete_exchange_rule( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );
		 require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );				
		return usam_delete_exchange_rule( $id );
	}
	
	public static function get_tracking( WP_REST_Request $request ) 
	{				
		$track_id = $request->get_param( 'number' );
		$shipped_document = usam_get_shipped_document( $track_id, 'track_id' );
		$track_data = [];
		if ( $shipped_document )
		{
			$shipped_instance = usam_get_shipping_class( $shipped_document['method'] );	
			$track_data = $shipped_instance->get_delivery_history( $shipped_document['track_id'] );	
		}
		return $track_data;
	}	
	
	public static function get_sets( WP_REST_Request $request ) 
	{				
		require_once(USAM_FILE_PATH.'/includes/product/sets_query.class.php');
		require_once(USAM_FILE_PATH.'/includes/product/products_set_query.class.php');
		
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
		$query_vars['meta_query'] = [];		
		
		$catalog = usam_get_active_catalog();
		if ( $catalog )
			$query_vars['meta_query'][] = ['relation' => 'OR',['key' => 'catalog', 'compare' => '=', 'value' => $catalog->term_id], ['key' => 'catalog', 'compare' => 'NOT EXISTS']];
		$user = wp_get_current_user();
		$roles = empty($user->roles)?['notloggedin']:$user->roles;	 	
		$query_vars['meta_query'][] = ['relation' => 'OR',['key' => 'role', 'compare' => 'IN', 'value' => $roles], ['key' => 'role', 'compare' => 'NOT EXISTS']];	
		
		$query_vars['status'] = empty($query_vars['status'])?'publish':$query_vars['status'];
		$query = new USAM_Sets_Query( $query_vars );		
		$items = $query->get_results();		
		if ( !empty($items) )
		{
			$ids = [];
			foreach($items as $item )
				$ids[] = $item->id;	
			$products_sets = usam_get_products_sets_query(['set' => $ids, 'orderby' => ['category_id' => 'ASC', 'status' => 'DESC', 'sort' => 'ASC']]);
			$type_price = !empty($parameters['type_price']) ? $parameters['type_price'] : usam_get_customer_price_code();
			foreach($items as &$item )
			{				
				$item->currency = usam_get_currency_sign();
				$products = usam_get_products(['sets' => [$item->id]]);			
				if ( $item->thumbnail_id )
				{
					$image_attributes = wp_get_attachment_image_src( $item->thumbnail_id, 'small-product-thumbnail' );
					$item->image = !empty($image_attributes[0]) ? $image_attributes[0] : '';
				}
				else
					$item->image = '';
				foreach($products_sets as $s => $products_set )				
				{					
					foreach($products as $product )
					{
						if ( $products_set->product_id == $product->ID && $products_set->set_id == $item->id )
						{
							$product->small_image = usam_get_product_thumbnail_src($product->ID, 'small-product-thumbnail');
							$price = usam_get_product_price($product->ID, $type_price );					
							$product->price = ['currency' => usam_get_formatted_price($price), 'value' => $price]; 
							$old_price = usam_get_product_old_price($product->ID, $type_price );
							$product->old_price = ['currency' => usam_get_formatted_price($old_price), 'value' => $old_price];
							$discount = usam_get_percent_product_discount($product->ID);
							$product->discount = ['currency' => usam_get_formatted_price($discount), 'value' => $discount];
							$product->stock = usam_product_remaining_stock( $product->ID );
							$product->stock = usam_product_remaining_stock( $product->ID );
							$product->url = usam_product_url( $product->ID );
							$product->sku = usam_get_product_meta( $product->ID, 'sku' );
							$product->unit = usam_get_product_unit( $product->ID, $product->unit_measure );
							$product->status = $products_set->status;						
							$product->quantity = usam_get_formatted_quantity_product($products_set->quantity, usam_get_product_property($product->ID, 'unit_measure_code'));
							if ( isset($item->categories[$products_set->category_id]) )
								$item->categories[$products_set->category_id]['products'][] = $product;
							elseif ( $products_set->category_id )
							{
								$item->categories[$products_set->category_id] = get_term($products_set->category_id, 'usam-category', ARRAY_A);		
								$item->categories[$products_set->category_id]['products'][] = $product;
							}
							break;
						}
					}
				}
			}		
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}	
	
	
	public static function get_set( WP_REST_Request $request ) 
	{				
		$id = $request->get_param( 'id' );
		
		require_once(USAM_FILE_PATH.'/includes/product/set.class.php');
		require_once(USAM_FILE_PATH.'/includes/product/products_set_query.class.php');
		
		$set = usam_get_set( $id );		
		
		$products_sets = usam_get_products_sets_query(['set' => [$id], 'orderby' => 'sort']);
		$type_price = !empty($parameters['type_price']) ? $parameters['type_price'] : usam_get_customer_price_code();
		$set['products'] = usam_get_products(['sets' => [$id]]);	
		foreach($set['products'] as &$product )
		{
			$terms = wp_get_post_terms($product->ID, 'usam-category');							
			$product->category = [];
			foreach ( $terms as $term )
			{
				$product->category[] = $term;
			}
			$price = usam_get_product_price($product->ID, $type_price );
			$product->price = ['currency' => usam_get_formatted_price($price), 'value' => $price]; 
			$old_price = usam_get_product_old_price($product->ID, $type_price );
			$product->old_price = ['currency' => usam_get_formatted_price($old_price), 'value' => $old_price];
			$discount = usam_get_percent_product_discount($product->ID);
			$product->discount = ['currency' => usam_get_formatted_price($discount), 'value' => $discount];
			$product->stock = usam_product_remaining_stock( $product->ID );
			$product->url = usam_product_url( $product->ID );
			$product->sku = usam_get_product_meta( $product->ID, 'sku' );
			$product->small_image = usam_get_product_thumbnail_src($product->ID, 'small-product-thumbnail');	
			foreach($products_sets as $s => $products_set )
			{
				if ( $products_set->product_id == $product->ID )
				{
					$product->id = $products_set->id;					
					$product->product_id = $product->ID;
					$product->category_id = $products_set->category_id;	
					$product->quantity = usam_get_formatted_quantity_product($products_set->quantity, usam_get_product_property($product->product_id, 'unit_measure_code'));
					$product->status = $products_set->status;
					unset($products_sets[$s]);
					break;
				}
			}
		}		
		return $set;
	}
	
	public static function add_support_message( WP_REST_Request $request ) 
	{				
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$insert = ['message' => sanitize_textarea_field(stripslashes($parameters['message'])), 'subject' => sanitize_text_field(stripslashes($parameters['subject']))];
		$api = new USAM_Service_API();
		if ( $api->sent_support_message( $insert ) )
			return usam_insert_support_message( $insert );
		else
			return false;
	}
	
	public static function update_support_message( WP_REST_Request $request ) 
	{				
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		$id = $request->get_param( 'id' );
		return usam_update_support_message( $id, $parameters);
	}
	
	public static function get_support_messages( WP_REST_Request $request ) 
	{				
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );	
		
		require_once( USAM_FILE_PATH . '/includes/technical/support_message_query.class.php');
		$query = new USAM_Support_Message_Query( $query_vars );			
		$items = $query->get_results();			
		if ( !empty($items) )
		{	
			foreach( $items as $key => $value) 
			{				
				if ( date("Y", strtotime($value->date_insert) ) == date("Y") )
					$format = "d F H:m";
				else
					$format = "d F Y H:m";				
				$items[$key]->date = usam_local_date( $value->date_insert, $format );
				
				$subject = usam_get_subject_support_message();			
				$items[$key]->subject = isset($subject[$value->subject])?$subject[$value->subject]:'';							
			}
			return ['count' => $query->get_total(), 'items' => $items];
		}
		else
			return ['count' => 0, 'items' => []];		
	}	
	
	public static function get_knowledge_base( WP_REST_Request $request ) 
	{				
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );	
		
		$license = get_option ( 'usam_license', []);	
		
		$params = ['message' => $query_vars['search'], 'query' => 'knowledge_base_search', 'software' => 'universam', 'version' => USAM_VERSION, 'server_name' => $_SERVER['SERVER_NAME']];	
		$params['license'] = !empty($license['name'])?$license['license']:'';					
					
		$response = wp_remote_post('https://docs.wp-universam.ru/api', ['method' => 'POST', 'timeout' => 45, 'redirection' => 5, 'sslverify' => true, 'headers' => [], 'body' => $params]);	

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		if ( 200 !== $response_code && !empty( $response_message ) )
			return new WP_Error( 'api', sprintf(__('Произошла API ошибка №%s. %s.', 'usam'),$response_code, $response_message), ['status' => 404]);
		else 
		{ 
			$results = json_decode( wp_remote_retrieve_body( $response ), true );	
			if ( isset($results['error'] ) ) 
				return new WP_Error( 'api', $results['error'], ['status' => 404]);
			return $results;
		}
	}	
	
	public static function get_visits( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
		if( isset($parameters['add_fields']) )
		{		
			if ( in_array('contact', $parameters['add_fields']) )
				$query_vars['cache_contacts'] = true;
		}
		$query_vars['cache_meta'] = true;									
		$query_vars['orderby'] = !empty($query_vars['orderby'])?$query_vars['orderby']:'date_insert';
		$query = new USAM_Visits_Query( $query_vars );			
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			foreach( $items as $key => &$item )
			{			
				$item->order_id = (int)usam_get_visit_metadata($item->id, 'order_id' ); 
				$item->order_url = $item->order_id ? usam_get_url_order( $item->order_id, 'order' ) : '';
				$item->date_insert = get_date_from_gmt($item->date_insert);		
				$item->time = human_time_diff( strtotime($item->date_update), strtotime($item->date_insert) );				
				if( isset($parameters['add_fields']) )
				{						
					if ( in_array('long2ip', $parameters['add_fields']) )
						$item->long2ip = long2ip($item->ip);
					if ( in_array('contact', $parameters['add_fields']) )
						$item->contact = self::author_data( $item->contact_id );
				}
			}
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];			
		return $results;
	}	
	
	public static function save_table_columns( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$columns_document = get_user_option( 'usam_columns_document' );	
		if ( empty($columns_document) ) 
			$columns_document = [];			
		
		$columns_document[$parameters['type']] = !empty($parameters['columns'])?$parameters['columns']:[];
		$user_id = get_current_user_id();
		return update_user_option( $user_id, 'usam_columns_document', $columns_document );	
	}
	
	public static function get_table_columns( WP_REST_Request $request ) 
	{			
		$parameters = self::get_parameters( $request );		
		$table_columns = get_user_option( 'usam_columns_document' );	
		if ( empty($table_columns[$parameters['type']]) )
			$table_columns[$parameters['type']] = [];		
		return $table_columns[$parameters['type']];	
	}	
	
	public static function get_columns_documents( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$user_columns = get_user_option('usam_columns_document');	
		if ( empty($user_columns) )
			$user_columns = [];
		$results = [];
		foreach( $parameters['types'] as $type )
		{				
			if ( !empty($user_columns[$type]) )
				$results[$type] = $user_columns[$type];	
			else
				$results[$type] = [];
		}
		return $results;
	}	
	
	public static function get_applications( WP_REST_Request $request ) 
	{			
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
									
		$orderby = !empty($query_vars['orderby'])?$query_vars['orderby']:'name';
		$order = !empty($query_vars['order'])?$query_vars['order']:'ASC';
				
		$items = [];
		foreach (usam_get_data_integrations( 'applications', ['name' => 'Name', 'icon' => 'Icon', 'description' => 'Description', 'group' => 'Group', 'closed' => 'Closed', 'price' => 'Price'] ) as $key => $item)
		{ 
			if ( !empty($query_vars['group']) && $item['group'] != $query_vars['group'] )
				continue;
			
			if ( !empty($item['closed']) && $item['closed'] == 'yes' && ( !defined('WP_DEBUG') || !WP_DEBUG ) )
				continue;
			
			if ( empty($query_vars['search']) || (mb_stripos($item['group'], $query_vars['search'])!== false || mb_stripos($item['name'], $query_vars['search'])!== false || mb_stripos($key, $query_vars['search'])!== false) )
			{ 				
				$item['icon'] = $item['icon'] ? USAM_SVG_ICON_URL."#".$item['icon']."-usage" : '';
				$item['code'] = $key;
				$item['url'] = add_query_arg(['id' => 0, 'form' => 'edit', 'service_code' => $key, 'form_name' => 'application', 'page' => 'applications', 'tab' => 'installed_applications'], admin_url('admin.php') );
				$items[] = $item;
			}	
		}	
		if ( !empty($query_vars['installed']) )
		{
			$services = [];
			foreach ( usam_get_applications() as $service )
			{
				foreach ( $items as $item )
				{
					if ( $service->service_code == $item['code'] )
					{
						$item['url'] = add_query_arg(['id' => $service->id, 'form' => 'edit', 'form_name' => 'application', 'page' => 'applications', 'tab' => 'installed_applications'], admin_url('admin.php') );							
						$item['active'] = $service->active;
						$services[] = $item;
					}
				}
			}	
			$items = $services;	
		}
		$comparison = new USAM_Comparison_Array( $orderby, $order );
		usort( $items, [$comparison, 'compare'] );
		return ['count' => count($items), 'items' => $items];	
	}
	
	public static function get_gallery( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		self::get_query_vars( $parameters, $parameters );	
		if ( is_array(self::$query_vars['add_fields']) )
		{					
			if ( in_array('images', self::$query_vars['add_fields']) )		
				self::$query_vars['images_cache'] = true;	
		}		
		self::$query_vars['taxonomy'] = 'usam-gallery';
		$results = self::get_terms( self::$query_vars );
		foreach( $results['items'] as &$term )
		{
			foreach( self::$query_vars['add_fields'] as $key )
			{
				if ( $key == 'images' )
				{
					$term->$key = usam_get_gallery_images( $term->term_id );
				}				
			}
		}
		return $results;
	}
	
	public static function get_pages( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		self::get_query_vars( $parameters, $parameters );
		$user_id = get_current_user_id();
		
		self::$query_vars['orderby'] = !empty(self::$query_vars['orderby'])?self::$query_vars['orderby']:'date';		
		if ( isset(self::$query_vars['author']) && self::$query_vars['author'] == 'my' )
			self::$query_vars['author'] = $user_id;	
		if ( isset(self::$query_vars['search']) )
		{
			self::$query_vars['s'] = self::$query_vars['search'];
			unset(self::$query_vars['search']);
		}
		$post_type_object = get_post_type_object( 'post' );				
		$edit_product = current_user_can( $post_type_object->cap->edit_posts );	
		if ( !$edit_product )
			self::$query_vars['post_status'] = 'publish';
		self::$query_vars['post_type'] = 'page';
		self::$query_vars['posts_per_page'] = self::$query_vars['number'];
		
		self::get_date_interval_for_query($parameters, ['date_insert']);
		$image = false;
		if ( !empty(self::$query_vars['add_fields']) && is_array(self::$query_vars['add_fields']) )
		{			
			if( in_array('thumbnail', self::$query_vars['add_fields']) || in_array('medium_image', self::$query_vars['add_fields'])  || in_array('small_image', self::$query_vars['add_fields']) || in_array('full_image', self::$query_vars['add_fields']) )
			{
				$image = true;
				self::$query_vars['post_meta_cache'] = true;	
			}			
			if ( in_array('images', self::$query_vars['add_fields']) )		
				self::$query_vars['product_images_cache'] = true;					
			if ( in_array('views', self::$query_vars['add_fields']) )		
				self::$query_vars['post_meta_cache'] = true;				
		}	
		self::$query_vars['ignore_sticky_posts'] = true;
		$wp_query = new WP_Query;	
		$items = $wp_query->query( self::$query_vars );			
		$count = $wp_query->found_posts;	
		if( $image )
			update_post_thumbnail_cache( $wp_query );	
		if ( !empty($items) )
		{					
			if ( isset($parameters['fields']) && $parameters['fields'] == 'autocomplete' )
			{
				$results = [];
				foreach ( $items as $item ) 		
					$results[] = ['id' => $item->ID, 'name' => $item->post_title];
				$items = $results;		
			}
			else
				foreach ( $items as &$item )
				{		
					$item->post_excerpt = get_the_excerpt( $item->ID );						
					$item->url = get_permalink( $item->ID );
					if( !empty(self::$query_vars['add_fields']) )
					{
						foreach( self::$query_vars['add_fields'] as $key )
						{
							if ( $key == 'thumbnail' )
								$item->small_image = usam_get_product_thumbnail_src($item->ID, 'thumbnail');
							elseif( $key == 'medium_image' )
								$item->$key = usam_get_product_thumbnail_src($item->ID, 'medium');
							elseif( $key == 'large_image' )
								$item->$key = usam_get_product_thumbnail_src($item->ID, 'large');
							elseif( $key == 'full_image' )
								$item->$key = usam_get_product_thumbnail_src($item->ID, 'full');
							elseif( $key == 'views' )
								$item->views = usam_get_post_meta( $item->ID, 'views' );
							elseif( $key == 'edit_link' )
								$item->edit_link = get_edit_post_link( $item->ID );
						}
					}
				}
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];			
		return $results;
	}
	
	public static function get_posts( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		self::get_query_vars( $parameters, $parameters );
		$user_id = get_current_user_id();
		
		self::$query_vars['orderby'] = !empty(self::$query_vars['orderby'])?self::$query_vars['orderby']:'date';		
		if ( isset(self::$query_vars['author']) && self::$query_vars['author'] == 'my' )
			self::$query_vars['author'] = $user_id;	
		if ( isset(self::$query_vars['search']) )
		{
			self::$query_vars['s'] = self::$query_vars['search'];
			unset(self::$query_vars['search']);
		}
		$post_type_object = get_post_type_object( 'post' );				
		$edit_product = current_user_can( $post_type_object->cap->edit_posts );	
		if ( !$edit_product )
			self::$query_vars['post_status'] = 'publish';
		self::$query_vars['post_type'] = 'post';
		self::$query_vars['posts_per_page'] = self::$query_vars['number'];
		
		self::get_date_interval_for_query($parameters, ['date_insert']);
		$image = false;
		if ( !empty(self::$query_vars['add_fields']) && is_array(self::$query_vars['add_fields']) )
		{			
			if( in_array('thumbnail', self::$query_vars['add_fields']) || in_array('medium_image', self::$query_vars['add_fields'])  || in_array('small_image', self::$query_vars['add_fields']) || in_array('full_image', self::$query_vars['add_fields']) )
			{
				$image = true;
				self::$query_vars['post_meta_cache'] = true;	
			}			
			if ( in_array('images', self::$query_vars['add_fields']) )		
				self::$query_vars['product_images_cache'] = true;				
			if ( in_array('category', self::$query_vars['add_fields']) )		
				self::$query_vars['update_post_term_cache'] = true;	
			if ( in_array('views', self::$query_vars['add_fields']) )		
				self::$query_vars['post_meta_cache'] = true;				
		}	
		self::$query_vars['ignore_sticky_posts'] = true;
		$wp_query = new WP_Query;	
		$items = $wp_query->query( self::$query_vars );			
		$count = $wp_query->found_posts;	
		if( $image )
			update_post_thumbnail_cache( $wp_query );	
		if ( !empty($items) )
		{					
			foreach ( $items as &$item )
			{				
				$item->post_excerpt = get_the_excerpt( $item->ID );						
				$item->url = get_permalink( $item->ID );
				if( !empty(self::$query_vars['add_fields']) )
				{
					foreach( self::$query_vars['add_fields'] as $key )
					{
						if ( $key == 'category_name' )
						{
							$terms = wp_get_post_terms($item->ID, 'category');							
							$item->$key = !empty($terms[0]) ? $terms[0]->name : new stdClass();
						}
						elseif ( $key == 'thumbnail' )
							$item->small_image = usam_get_product_thumbnail_src($item->ID, 'thumbnail');
						elseif( $key == 'medium_image' )
							$item->$key = usam_get_product_thumbnail_src($item->ID, 'medium');
						elseif( $key == 'large_image' )
							$item->$key = usam_get_product_thumbnail_src($item->ID, 'large');
						elseif( $key == 'full_image' )
							$item->$key = usam_get_product_thumbnail_src($item->ID, 'full');
						elseif ( $key == 'views' )
							$item->views = usam_get_post_meta( $item->ID, 'views' );
						elseif ( $key == 'edit_link' )
							$item->edit_link = get_edit_post_link( $item->ID );
					}
				}
			}
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];			
		return $results;
	}
	
	public static function get_agreements( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		self::get_query_vars( $parameters, $parameters );
		$user_id = get_current_user_id();
		
		self::$query_vars['orderby'] = !empty(self::$query_vars['orderby'])?self::$query_vars['orderby']:'date';		
		if ( isset(self::$query_vars['author']) && self::$query_vars['author'] == 'my' )
			self::$query_vars['author'] = $user_id;	
		if ( isset(self::$query_vars['search']) )
		{
			self::$query_vars['s'] = self::$query_vars['search'];
			unset(self::$query_vars['search']);
		}
		$post_type_object = get_post_type_object( 'post' );				
		$edit_product = current_user_can( $post_type_object->cap->edit_posts );	
		if ( !$edit_product )
			self::$query_vars['post_status'] = 'publish';
		self::$query_vars['post_type'] = 'usam-agreement';
		self::$query_vars['posts_per_page'] = self::$query_vars['number'];
		
		self::get_date_interval_for_query($parameters, ['date_insert']);
		$image = false;
		if ( !empty(self::$query_vars['add_fields']) && is_array(self::$query_vars['add_fields']) )
		{			
			if( in_array('thumbnail', self::$query_vars['add_fields']) || in_array('medium_image', self::$query_vars['add_fields'])  || in_array('small_image', self::$query_vars['add_fields']) || in_array('full_image', self::$query_vars['add_fields']) )
			{
				$image = true;
				self::$query_vars['post_meta_cache'] = true;	
			}			
			if ( in_array('views', self::$query_vars['add_fields']) )		
				self::$query_vars['post_meta_cache'] = true;				
		}	
		self::$query_vars['ignore_sticky_posts'] = true;
		$wp_query = new WP_Query;	
		$items = $wp_query->query( self::$query_vars );			
		$count = $wp_query->found_posts;	
		if( $image )
			update_post_thumbnail_cache( $wp_query );	
		if ( !empty($items) )
		{					
			foreach ( $items as &$item )
			{				
				$item->post_excerpt = get_the_excerpt( $item->ID );						
				$item->url = get_permalink( $item->ID );
				if( !empty(self::$query_vars['add_fields']) )
				{
					foreach( self::$query_vars['add_fields'] as $key )
					{
						if ( $key == 'thumbnail' )
							$item->small_image = usam_get_product_thumbnail_src($item->ID, 'thumbnail');
						elseif( $key == 'medium_image' )
							$item->$key = usam_get_product_thumbnail_src($item->ID, 'medium');
						elseif( $key == 'large_image' )
							$item->$key = usam_get_product_thumbnail_src($item->ID, 'large');
						elseif( $key == 'full_image' )
							$item->$key = usam_get_product_thumbnail_src($item->ID, 'full');
						elseif ( $key == 'views' )
							$item->views = usam_get_post_meta( $item->ID, 'views' );
						elseif ( $key == 'edit_link' )
							$item->edit_link = get_edit_post_link( $item->ID );
					}
				}
			}
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];			
		return $results;
	}
		
	public static function save_post( WP_REST_Request $request ) 
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		$id = $request->get_param( 'id' );
		$post_type_object = get_post_type_object( 'post' );
		if ( current_user_can($post_type_object->cap->edit_post, $id) )
		{
			$parameters['ID'] = $id;
			wp_update_post($parameters);
		}		
	}	
	
	public static function get_notifications( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
									
		require_once(USAM_FILE_PATH.'/includes/crm/notifications_query.class.php');
		if ( isset(self::$query_vars['author']) )
			self::$query_vars['author'] = get_current_user_id();
		else
			self::$query_vars['author'] = get_current_user_id();
		
		$query = new USAM_Notifications_Query( self::$query_vars );				
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			foreach( $items as &$item ) 
			{						
				if ( !empty($parameters['fields']) )
				{
					if ( in_array('author', $parameters['fields']) )
						$item->author = self::author_data( $item->user_id );
					if ( in_array('objects', $parameters['fields']) )
					{
						$item->objects = [];
						$objects = usam_get_notification_objects_all( $item->id );			
						if ( !empty($objects) )
						{				
							foreach($objects as $object)
							{
								$display_object = usam_get_object( $object );				
								if ( !empty($display_object['name']) )
									$item->objects[] = $display_object;
							}
						}
					}
				}
			}
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];			
		return $results;
	}	
	
	public static function update_notifications( WP_REST_Request $request ) 
	{		
		$user_id = get_current_user_id();
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		remove_action( 'usam_notification_update', ['USAM_Counters', 'notification_update'], 30 );	
		$i = 0;
		foreach($parameters['items'] as $item)
		{
			if ( !empty($item['id']) )
			{
				if ( isset($item['user_id']) )				
					unset($item['user_id']);	
				if ( usam_update_notification( $item['id'], $item) )
					$i++;
			}
		}
		$contact = usam_get_contact($user_id, 'user_id');
		if ( $contact )
		{
			require_once(USAM_FILE_PATH.'/includes/crm/notifications_query.class.php');
			$counter = usam_get_notifications(['status' => 'started', 'fields' => 'count', 'author' => $user_id, 'number' => 1]);
			usam_update_contact_metadata( $contact['id'], 'unread_notifications', $counter );	
		}
		return $i;
	}	
	
	public static function read_notifications( WP_REST_Request $request ) 
	{		
		global $wpdb;
		$user_id = get_current_user_id();
		$result = $wpdb->query("UPDATE ".USAM_TABLE_NOTIFICATIONS." SET status='completed' WHERE `status`='started' AND user_id='$user_id'");	
		if ( $result )
		{
			$contact = usam_get_contact($user_id, 'user_id');
			if ( $contact )
				usam_update_contact_metadata( $contact['id'], 'unread_notifications', 0 );	
		}
		return $result;
	}		
	
	public static function save_theme_options( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$i = 0;
		foreach($parameters['options'] as $key => $option)
		{
			if ( set_theme_mod($key, $option) )
				$i++;
		}
		return $i;
	}	
	
	public static function change_theme_edit( WP_REST_Request $request ) 
	{		
		$user_id = get_current_user_id();
		$result = get_user_meta($user_id, 'edit_theme', true);		
		update_user_meta($user_id, 'edit_theme', !$result ); 
	}	

	public static function insert_parser( WP_REST_Request $request ) 
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		require_once(USAM_FILE_PATH.'/includes/parser/parsing_site.class.php');	
		if ( !empty($parameters['domain']) )
		{
			$domain = parse_url($parameters['domain'], PHP_URL_HOST);						
			if ( !empty($domain) )
				$parameters['domain'] = $domain;	
		}		
		$id = usam_insert_parsing_site( $parameters );	
		USAM_API::save_meta_parser( $id, $parameters );
		return $id;
	}
	
	public static function get_parser( WP_REST_Request $request ) 
	{
		$id = $request->get_param( 'id' );
		
		require_once(USAM_FILE_PATH.'/includes/parser/parsing_site.class.php');
		$data = usam_get_parsing_site( $id );
		return $data;
	}	
	
	public static function save_parser( WP_REST_Request $request ) 
	{
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		if ( !empty($parameters['domain']) )
		{
			$domain = parse_url($parameters['domain'], PHP_URL_HOST);						
			if ( !empty($domain) )
				$parameters['domain'] = $domain;	
		}
		require_once(USAM_FILE_PATH.'/includes/parser/parsing_site.class.php');
		$result = usam_update_parsing_site( $id, $parameters );
		USAM_API::save_meta_parser( $id, $parameters );
		return $result;
	}	
	
	
	private static function save_meta_parser( $id, $parameters  ) 
	{
		if( isset($parameters['tags']) )
		{			
			foreach( $parameters['tags'] as $key => $value )
			{
				if ( isset($value['number']) )
					$parameters['tags'][$key]['number'] = absint($value['number']);
				if ( isset($value['tag']) )	
					$parameters['tags'][$key]['tag'] = sanitize_text_field(stripcslashes($value['tag']));					
			} 
			usam_update_parsing_site_metadata( $id, 'tags', $parameters['tags'] );
		}		
		if ( isset($parameters['urls']) && !usam_check_parsing_is_running( $id ) )
		{				
			$urls = [];
			foreach( $parameters['urls'] as $item )
			{ 					
				$conditions = [];
				if ( !empty($item['conditions']) )
				{
					foreach( $item['conditions'] as $value )
					{
						if ( $value['value'] !== '' && !empty($value['tag']) && !empty($value['operator']) )
							$conditions[] = ['tag' => sanitize_text_field($value['tag']), 'operator' => sanitize_text_field($value['operator']), 'value' => sanitize_text_field($value['value'])];
					}
				}						
				$category = !empty($item['category']) ? (int)$item['category'] : 0;
				$status = !empty($item['status']) ? (int)$item['status'] : 0;
				$urls[] = ['url' => sanitize_text_field($item['url']), 'category' => $category, 'conditions' => $conditions, 'status' => $status];	
			} 
			usam_update_parsing_site_metadata( $id, 'urls', $urls );
		}					
		if ( isset($parameters['parent_variation']) )
			usam_update_parsing_site_metadata( $id, 'parent_variation', $parameters['parent_variation'] );
		if ( isset($parameters['variations']) )
			usam_update_parsing_site_metadata( $id, 'variations', $parameters['variations'] );	
		if ( isset($parameters['headers']) )
			usam_update_parsing_site_metadata( $id, 'headers', $parameters['headers'] );	
		if ( isset($parameters['authorization']) )
			usam_update_parsing_site_metadata( $id, 'authorization', $parameters['authorization'] );	
		if ( isset($parameters['login_page']) )
			usam_update_parsing_site_metadata( $id, 'login_page', sanitize_text_field($parameters['login_page']) );						
		if ( isset($parameters['authorization_parameters']) )
			usam_update_parsing_site_metadata( $id, 'authorization_parameters', sanitize_text_field($parameters['authorization_parameters']) );				
		if ( isset($parameters['type_import']) )
			usam_update_parsing_site_metadata( $id, 'type_import', sanitize_title($parameters['type_import']) );
		if ( isset($parameters['translate']) )
			usam_update_parsing_site_metadata( $id, 'translate', isset($parameters['translate'])?sanitize_title($parameters['translate']):'' );			
		if ( isset($parameters['product_loading']) )
			usam_update_parsing_site_metadata( $id, 'product_loading', !empty($parameters['product_loading'])?sanitize_title($parameters['product_loading']):'' );		
		if ( isset($parameters['excluded']) )
			usam_update_parsing_site_metadata( $id, 'excluded', sanitize_textarea_field($parameters['excluded']) );	
		if ( isset($parameters['existence_check']) )
			usam_update_parsing_site_metadata( $id, 'existence_check', sanitize_text_field($parameters['existence_check']) );		
		if ( isset($parameters['link_option']) )
			usam_update_parsing_site_metadata( $id, 'link_option', $parameters['link_option'] );			
		if ( isset($parameters['post_status']) )
			usam_update_parsing_site_metadata( $id, 'post_status', $parameters['post_status'] );
		if ( isset($parameters['contractor']) )
			usam_update_parsing_site_metadata( $id, 'contractor', $parameters['contractor'] );			
		if ( isset($parameters['bypass_speed']) )
			usam_update_parsing_site_metadata( $id, 'bypass_speed', $parameters['bypass_speed'] );
	}	
	
	public static function delete_parser( WP_REST_Request $request ) 
	{
		$id = $request->get_param( 'id' );		
		require_once(USAM_FILE_PATH.'/includes/parser/parsing_site.class.php');
		return usam_delete_parsing_site( $id );
	}
	
	public static function test_data_parser( WP_REST_Request $request ) 
	{			
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH . '/includes/parser/parser.class.php' );
		$parser = new USAM_Parser( $id );
		$data = $parser->get_website_data( $parameters['url'] );
		$parser->clear();		
		return $data;
	}		
	
	public static function test_login_parser( WP_REST_Request $request ) 
	{				
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		$id = $request->get_param( 'id' );
			
		require_once( USAM_FILE_PATH . '/includes/parser/parser.class.php' );
		$webspy = new USAM_Parser( $id );
		return $webspy->site_login();
	}	
	
	public static function get_subscription( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php' );
		$data = usam_get_subscription( $id );
		if ( $data )
		{
			$data['products'] = usam_get_products_subscription( $data['id'] );
			$data['start_date'] = get_date_from_gmt( $data['start_date'], "Y-m-d H:i:s" );			
			$data['end_date'] = get_date_from_gmt( $data['end_date'], "Y-m-d H:i:s" );
			$data['date_insert'] = get_date_from_gmt( $data['date_insert'], "Y-m-d H:i:s" );
			$data['period_value'] = usam_get_subscription_metadata($id, 'period_value');
			$data['period'] = usam_get_subscription_metadata($id, 'period');		
		}
		return $data;
	}	
	
	public static function insert_subscription( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
			
		$parameters['customer_type'] = !empty($parameters['customer_type']) && $parameters['customer_type'] == 'company' ? 'company' : 'contact';	
		if ( empty($parameters['start_date']) )
			$parameters['start_date'] = date( "Y-m-d H:i:s" );	
		require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php' );
		$products = null;
		if ( !empty($parameters['products']) )
		{			
			$products = $parameters['products'];
			unset($parameters['products']);
		}
		if ( !empty($parameters['period_value']) && !empty($parameters['period']) && empty($parameters['end_date']) )
			$parameters['end_date'] = date("Y-m-d H:i:s", strtotime('+'.$parameters['period_value'].' '.$parameters['period'].'s', strtotime($parameters['start_date'])));
		$id = usam_insert_subscription( $parameters, $products );
		if ( $id )
		{		
			if ( !empty($parameters['period_value']) )
				usam_add_subscription_metadata($id, 'period_value', $parameters['period_value']);			
			if ( !empty($parameters['period']) )
				usam_add_subscription_metadata($id, 'period', $parameters['period']);
		}	
		return $id;
	}

	public static function update_subscription( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php');	
		if ( !empty($parameters['period_value']) && !empty($parameters['period']) && !empty($parameters['start_date']) )
			$parameters['end_date'] = date("Y-m-d H:i:s", strtotime('+'.$parameters['period_value'].' '.$parameters['period'].'s', strtotime($parameters['start_date'])));
		$products = null;
		if ( !empty($parameters['products']) )
		{			
			$products = $parameters['products'];
			unset($parameters['products']);
		}		
		$result = usam_update_subscription( $id, $parameters, $products );
		if ( !empty($parameters['period_value']) )
			usam_update_subscription_metadata($id, 'period_value', $parameters['period_value']);
		if ( !empty($parameters['period']) )
			usam_update_subscription_metadata($id, 'period', $parameters['period']);		
		return $result;
	}
	
	public static function renew_subscription( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = self::get_parameters( $request );	
		$parameters = self::get_query_vars( $parameters, $parameters );		
		require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php');
		$data = usam_get_subscription( $id );
		
		$document_id = !empty($parameters['document_id']) ? $parameters['document_id'] : 0;	
		
		$period_value = usam_get_subscription_metadata($id, 'period_value');
		$period = usam_get_subscription_metadata($id, 'period');
		$endtime = strtotime($data['end_date']);		
		if ( $endtime < time() )
			$data['end_date'] = date("Y-m-d H:i:s");
		$endtime = strtotime($data['end_date']);	
		$end_date = date("Y-m-d H:i:s", strtotime('+'.$period_value.' '.$period.'s', $endtime));		
		$result = false;
		if ( usam_insert_subscription_renewal(['status' => 'paid', 'start_date' => $data['end_date'], 'end_date' => $end_date, 'sum' => $data['totalprice'], 'subscription_id' => $id, 'document_id' => $document_id]) )
		{
			$update = ['start_date' => $data['end_date'], 'end_date' => $end_date];
			if ( $data['status'] != 'signed' )
				$update['status'] = 'signed';
			$result = usam_update_subscription($id, $update);		
		}
		return $result;
	}	
		
	public static function delete_subscription( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php' );
		return usam_delete_subscription( $id );
	}
	
	public static function get_template( WP_REST_Request $request ) 
	{		
		$template = $request->get_param( 'name' );			
		$file_name = usam_get_module_template_file( 'newsletter-templates', $template, $template.'.xml' );
		require_once( USAM_FILE_PATH . '/includes/exchange/save-object-settings.class.php' );
		$c = new USAM_Read_Object_Settings();
		return $c->get_settings( $file_name );			
	}	
	
	public static function affair_complete( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );
		return usam_update_change_history( $id, ['end' => date( "Y-m-d H:i:s")]);
	}	

	public static function update_options( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();			
				
		if( !empty($parameters['options']) )
		{
			add_action( 'update_option_usam_category_hierarchical_url', function() 
			{ 
				flush_rewrite_rules( false );
			});
		//	$style = usam_get_site_style();
			foreach( $parameters['options'] as $option )
			{
				if ( !empty($option['set_option']) && $option['set_option'] == 'theme_mod' )
					set_theme_mod( $option['code'], $option['value'] );
				elseif ( usam_is_multisite() && $option['set_option'] == 'global' )
					update_site_option( 'usam_'.$option['code'], $option['value'] );
				else	
					update_option( 'usam_'.$option['code'], $option['value'] );
			}
		}
		if( isset($parameters['home_blocks']) )
			update_option( 'usam_theme_home_blocks', $parameters['home_blocks'] );
		if( isset($parameters['htmlblocks']) )
			update_option( 'usam_html_blocks', $parameters['htmlblocks'] );	
		return true;
	}	
	
	public static function get_htmlblock( WP_REST_Request $request )
	{
		$id = $request->get_param( 'id' );		
		$parameters = self::get_parameters( $request );	
		
		$block = usam_get_html_block( $id );
		if( !$block )
			return '';
		
		$product_id = !empty($parameters['product_id'])?(int)$parameters['product_id']:0;	
		$file_name = usam_get_template_file_path( $block['template'].'/index' );
		
		ob_start();	
		if ( file_exists($file_name) )
			include( $file_name );
		return ob_get_clean();
	}
	
	public static function get_htmlblocks( WP_REST_Request $request )
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$items = get_option( 'usam_html_blocks' );	
		if ( !empty($items) )
		{
			if ( isset($parameters['fields']) && $parameters['fields'] == 'autocomplete' )
			{
				$results = [];
				foreach ( $items as $item ) 		
					$results[] = ['id' => $item['id'], 'name' => $item['id'].' - '.$item['html_name']];
				$items = $results;		
			}		
			$results = ['count' => count($items), 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];	
		return $results;
	}	
}
?>