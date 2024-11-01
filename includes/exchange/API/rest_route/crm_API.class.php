<?php 
class USAM_CRM_API extends USAM_API
{			
	public static function get_companies( WP_REST_Request $request, $parameters = null ) 
	{					
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );
		$query_vars = self::get_query_vars( $parameters, $parameters );	
		$fields = isset($parameters['fields'])?(array)$parameters['fields']:[];			
		$autocomplete = false;
		if ( isset($query_vars['fields']) )
		{
			if ( $query_vars['fields'] == 'autocomplete' )
			{
				$query_vars['fields'] = ['id', 'name'];	
				$autocomplete = true;
				unset($query_vars['fields']);
			}				
		}			
		if ( !$autocomplete && (isset($query_vars['user_id']) && $query_vars['user_id'] === -1 || (!current_user_can('universam_api') && current_user_can('view_company'))) )
		{
			$user_id = get_current_user_id();
			if ( $user_id )
				$query_vars['user_id'] = $user_id;
			else				
				return ['count' => 0, 'items' => []];
		}		
		$query = new USAM_Companies_Query( $query_vars );
		$items = $query->get_results();					
		if ( !empty($items) )
		{			
			if ( $autocomplete )
			{	
				foreach ( $items as &$item )
					$item->name = stripcslashes($item->name);
			}
			elseif( empty($parameters['fields']) || $parameters['fields'] !== 'id=>name' )
			{				
				if ( in_array('metas', $fields) || isset($parameters['add_fields']) && in_array('properties', $parameters['add_fields']) )
				{
					$args = ['access' => true, 'type' => 'company', 'fields' => ['code', 'description', 'field_type', 'group', 'id', 'mandatory', 'mask', 'name']];
					if ( !current_user_can('universam_api') && !current_user_can('view_company') )				
						$args['profile'] = 1;
					$properties = usam_get_properties( $args );
				}
				foreach ( $items as &$item )
				{							
					$item->name = stripcslashes($item->name);
					$item->url = usam_get_company_url( $item->id );
					if ( isset($item->date_insert) )
						$item->date_insert = usam_local_date(strtotime($item->date_insert), 'c');						
					if ( in_array('metas', $fields) )
					{						
						foreach( $properties as $property )
						{
							$k = $property->code;							
							if ( $property->field_type == 'checkbox' )
								$item->$k = usam_get_array_metadata( $item->id, 'company', $property->code );
							else
								$item->$k = usam_get_company_metadata( $item->id, $property->code );		
						}
					}	
					if( isset($parameters['add_fields']) )
					{						
						if ( in_array('logo', $parameters['add_fields']) || in_array('foto', $parameters['add_fields']) )
							$item->logo = usam_get_company_logo( $item->id );
						if ( in_array('thumbnail', $parameters['add_fields']) )
						{
							$item->thumbnail = '';
							$thumbnail_id = usam_get_company_logo( $item->id, 'logo' );
							if ( $thumbnail_id )
							{
								$image_attributes = wp_get_attachment_image_src( $thumbnail_id, 'small-product-thumbnail' );
								$item->thumbnail = !empty($image_attributes[0]) ? $image_attributes[0] : '';
							}
						}
						if( in_array('status_data', $parameters['add_fields']) && isset($item->status) )
						{
							$object_status = usam_get_object_status_by_code( $item->status, 'contact' );
							$item->status_name = isset($object_status['name'])?$object_status['name']:'';
							$item->status_color = isset($object_status['color'])?$object_status['color']:'';
							$item->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';
						}
						if ( in_array('company_type', $parameters['add_fields']) )
							$item->company_type = usam_get_name_type_company( $item->type );
						if ( in_array('affairs', $parameters['add_fields']) )
							$item->affairs = usam_get_customer_case( $item->id, 'company' );	
						if ( in_array('communication', $parameters['add_fields']) && isset($item->status) )
						{
							$item->emails = usam_get_company_emails( $item->id );	
							$item->phones = usam_get_company_phones( $item->id );	
						}
						if( in_array('manager', $parameters['add_fields']) )
						{
							$item->manager = usam_get_contact( $item->manager_id, 'user_id' );
							if( !empty($item->manager['id']) )
								$item->manager['foto'] = usam_get_contact_foto( $item->manager['id'] );
						}				
						if ( in_array('properties', $parameters['add_fields']) )
						{						
							foreach( $properties as $p )
								$item->properties[$p->code] = usam_get_object_property_value( $item->id, $p );
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
	
	public static function save_company( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		if( !$company = usam_get_company( $id ) )
			return new WP_Error( 'no_data', __('Ничего не найдено','usam'), ['status' => 403]);		
		
		if ( !current_user_can('universam_api') && !current_user_can('edit_company') )
		{
			if( $company['type'] == 'own' )
				return new WP_Error( 'no_data', __('Ничего не найдено','usam'), ['status' => 403]);
			$user_id = get_current_user_id();
			$user_ids = usam_get_company_personal_accounts( $id );
			if ( !in_array($user_id, $user_ids) )
				return new WP_Error( 'no_company', __('Вы не можете добавить или сохранить эту компанию. Компания добавлена другому пользователю','usam'), ['status' => 403]);
		}	
		if ( isset($parameters['user_id'])	)
		{
			if ( $parameters['user_id'] === -1	)
				$user_id = get_current_user_id();
			else
				$user_id = $parameters['user_id'];
			usam_add_company_personal_account($id, $user_id);
		} 
		if ( isset($parameters['user_ids'])	)
		{	
			$user_ids = usam_get_company_personal_accounts( $id );
			foreach ( $parameters['user_ids'] as $user_id )
				usam_add_company_personal_account($id, $user_id);
			if ( $user_ids )
			{
				$user_ids = array_diff($user_ids, $parameters['user_ids']);
				usam_delete_company_personal_accounts($id, $user_ids);
			}
		}	 
		$properties = usam_get_properties(['access' => true, 'type' => 'company', 'fields' => 'code=>data']);		
		foreach( $parameters as $meta_key => $value )
		{
			if ( !isset($properties[$meta_key]) )
				continue;	
			usam_save_property_meta( $id, $properties[$meta_key], $value );
			unset($parameters[$meta_key]);
		}		
		if( $parameters )
		{	
			usam_update_company( $id, $parameters );	
			$keys = ['employees', 'type_price',	'code', 'description', 'revenue'];
			foreach ( $keys as $key )
				if( isset($parameters[$key]) )
					usam_update_company_metadata( $id, $key, $parameters[$key] );	
			if( isset($parameters['groups']) )
				usam_set_groups_object( $id, 'company', $parameters['groups'] );	
			if( isset($parameters['logo']) )
				usam_update_company_logo( $id, $parameters['logo'] );
		}
		if( !empty($parameters['connection_id']) )
			usam_set_company_selections($id, $parameters['connection_id']);		

		if( $id == get_option('usam_shop_company', '') )
			update_option('usam_shop_requisites_shortcode', []);		// Сбросить кэш реквизитов	
		return true;
	}	
	
	public static function add_company( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		
		if ( !empty($parameters['inn']) )
		{
			$company_ids = usam_get_companies(['parents' => 0, 'exclude' => $id, 'meta_key' => 'inn', 'meta_value' => $parameters['inn'], 'fields' => 'id']);				
			if ( !empty($company_ids) )
				return new WP_Error( 'no_data', sprintf(__('ИНН %s принадлежит другой компании', 'usam'), $parameters['inn'] ), ['status' => 403]);		
		}
		if ( empty($parameters['name']) )
		{
			if ( !empty($parameters['company_name']) )
				$parameters['name'] = $parameters['company_name'];
			elseif ( !empty($parameters['full_company_name']) )
				$parameters['name'] = $parameters['full_company_name'];
			else
				$parameters['name'] = __("Новая компания","usam");		
		}	
		if ( isset($parameters['type']) && !current_user_can('universam_api') && !current_user_can('add_company') ) 
			unset($parameters['type']);		
		$id = usam_insert_company( $parameters );
		if ( $id )
		{		
			if ( !current_user_can('universam_api') && !current_user_can('add_company') || isset($parameters['user_id']) && $parameters['user_id'] === -1 )
				$user_id = get_current_user_id();
			elseif ( !empty($parameters['user_id']) )
				$user_id = $parameters['user_id'];
			if ( !empty($user_id) )
				usam_add_company_personal_account($id, $user_id);
			$properties = usam_get_properties(['access' => true, 'type' => 'company', 'fields' => 'code=>data']);		
			foreach ( $parameters as $meta_key => $value ) 
			{	
				if ( !isset($properties[$meta_key]) )
					continue;			
				usam_save_property_meta( $id, $properties[$meta_key], $value );
				unset($parameters[$meta_key]);
			}
			if( isset($parameters['groups']) )
				usam_set_groups_object( $id, 'company', $parameters['groups'] );	 
			if( isset($parameters['logo']) )
				usam_update_company_logo( $id, $parameters['logo'] );			
			
			$keys = ['employees', 'type_price',	'code', 'description', 'revenue'];
			foreach ( $keys as $key )
				if( isset($parameters[$key]) )
					usam_add_company_metadata( $id, $key, $parameters[$key] );	
		}		
		return $id;
	}
	
	public static function get_company( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = self::get_parameters( $request );
		$company = usam_get_company( $id );
		if ( empty($company) )
			return [];
		
		$company['date_insert'] = usam_local_date(strtotime($company['date_insert']) );	
		$company['logo'] = usam_get_company_logo( $company['id'] );			
		if( isset($parameters['add_fields']) )
		{						
			if ( in_array('order_properties', $parameters['add_fields']) )
			{					
				$company['properties'] = [];
				$payers = usam_get_group_payers(['type' => 'company']);
				$metas = usam_get_company_metas( $id );							
				$args = ['access' => true, 'type' => 'order', 'cache_meta' => true];
				if ( isset($payers[0]['id']) )
					$args['type_payer'] = $payers[0]['id'];
				$properties = usam_get_properties( $args );	
				foreach ($properties as $property)
				{
					$connection = usam_get_property_metadata($property->id, 'connection');				
					$property->value = '';
					if ( !empty($connection) )
					{
						$connection = preg_replace('/company-/', '', $connection, 1);	
						if ( !empty($metas[$connection]) )
							$property->value = $metas[$connection];
					}
					$company['properties'][$property->code] = usam_format_property_api( $property );
				}
			}
			if ( in_array('groups', $parameters['add_fields']) )
				$company['groups'] = usam_get_property_groups(['type' => 'company']);	
			if ( in_array('properties', $parameters['add_fields']) )
			{
				$args = ['active' => 1, 'type' => 'company', 'orderby' => ['group', 'sort']];	
				if ( current_user_can('universam_api') || current_user_can('view_company') )
					$args['show_staff'] = 1;		
				else
					$args['profile'] = 1;
				$company['properties'] = self::get_object_properties( $id, $args );	
			}
		}		
		return $company;
	}
	
	public static function get_accounts( WP_REST_Request $request, $parameters = null ) 
	{		
		require_once(USAM_FILE_PATH.'/includes/crm/bank_accounts_query.class.php');	
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );
		$query_vars = self::get_query_vars( $parameters, $parameters );	
		$query = new USAM_Bank_Accounts_Query( $query_vars );
		$items = $query->get_results();					
		if ( !empty($items) )
		{			
			foreach ( $items as &$item )
			{
				$item->currency_name = usam_get_currency_name( $item->currency );
			}
			$results = ['count' => $query->get_total(), 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;	
	}
	
	public static function add_account( WP_REST_Request $request ) 
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$account = [];
		if( $id = usam_insert_bank_account( $parameters ) )
		{
			$account = usam_get_bank_account( $id );
			$account['currency_name'] = usam_get_currency_name( $account['currency'] );
		}
		return $account;
	}
	
	public static function save_account( WP_REST_Request $request ) 
	{
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		if( !$account = usam_get_bank_account( $id ) )
			return new WP_Error( 'no_data', __('Ничего не найдено','usam'), ['status' => 403]);
		if ( !current_user_can('universam_api') && !current_user_can('edit_company') )
		{
			if ( $id == get_option('usam_shop_company', '') )
				return new WP_Error( 'no_data', __('Ничего не найдено','usam'), ['status' => 403]);
			
			$user_id = get_current_user_id();
			$user_ids = usam_get_company_personal_accounts( $account['company_id'] );
			if ( !in_array($user_id, $user_ids) )
				return new WP_Error( 'no_company', __('Вы не можете добавить или сохранить эту компанию','usam'), ['status' => 403]);
		}	
		if( usam_update_bank_account( $id, $parameters ) )
		{
			if ( $id == get_option('usam_shop_company', '') )
				update_option('usam_shop_requisites_shortcode', []);		// Сбросить кэш реквизитов	
			return true;
		}
		return false;
	}
	
	public static function save_accounts( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$items = [];
		foreach ( $parameters['items'] as $item )
		{
			$id = false;
			if ( !empty($item['id']) )
			{
				usam_update_bank_account( $item['id'], $item );
				$id = $item['id'];
			}
			else
				$id = usam_insert_bank_account( $item );
			if( $id )
			{
				$account = usam_get_bank_account( $id );
				$account['currency_name'] = usam_get_currency_name( $account['currency'] );
				$items[] = $account;			
			}
		}
		return $items;
	}		
	
	public static function delete_account( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		return usam_delete_bank_account( $id );
	}
	
	public static function delete_accounts( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$i = 0;
		foreach ( $parameters['items'] as $id )
		{
			if ( usam_delete_bank_account( $id ) )
				$i++;
		}
		return $i;
	}	
			
	public static function search_companies( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );
		$query_vars = self::get_query_vars( $parameters, $parameters );
		
		if ( !empty($query_vars['inn']) ) 
			$query['meta_query'][] = ['key' => 'inn', 'value' => $query_vars['inn'], 'compare' => '=', 'relation' => 'AND'];	
		if ( !empty($query_vars['ppc']) ) 
			$query['meta_query'][] = ['key' => 'ppc', 'value' => $query_vars['ppc'], 'compare' => '=', 'relation' => 'AND'];		
		
		$query_vars['cache_meta'] = true;
		$query_vars['fields'] = ['id', 'name'];
		$query = new USAM_Companies_Query( $query_vars );
		$items = $query->get_results();					
		$payers = usam_get_group_payers(['type' => 'company']);
		if ( empty($items) )
		{
			if ( !empty($query_vars['search']) )
				$items = usam_find_company_in_directory(['search' => $query_vars['search'], 'search_type' => 'name']);
			else
				$items = usam_find_company_in_directory(['search' => $query_vars['search'], 'search_type' => 'inn']);
			foreach ( $items as $k => &$item )
			{
				$customer_data = (object)usam_get_webform_data_from_CRM( $item, 'order', $payers[0]['id'] );	
				$customer_data->name = $customer_data->company;	
				$customer_data->_name_legallocation = $item['_name_legallocation'];
				$item = $customer_data;
			}
			$results = ['count' => count($items), 'items' => $items];
		}
		else
		{		
			foreach ( $items as &$item )
			{				
				$metas = usam_get_company_metas( $item->id );	
				$customer_data = (object)usam_get_webform_data_from_CRM( $metas, 'order', $payers[0]['id'] );
				$customer_data->name = isset($customer_data->company)?$customer_data->company:$item->name;
				$customer_data->id = $item->id;
				$item = $customer_data;		
			}
			$count = $query->get_total();
			$results = ['count' => $count, 'items' => $items];
		}
		return $results;
	}	
		
	public static function search_company( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );
		$query_vars = self::get_query_vars( $parameters, $parameters );		
							
		$properties = usam_get_properties(['access' => true, 'type' => 'company', 'profile' => 1]);		
		
		$query = ['number' => 1, 'cache_meta' => true, 'fields' => ['id', 'name']];
		$meta_query = [];
		$meta_query[] = ['key' => 'inn', 'value' => $query_vars['inn'], 'compare' => '=', 'relation' => 'AND'];
		if ( !empty($query_vars['ppc']) ) 
			$meta_query[] = ['key' => 'ppc', 'value' => $query_vars['ppc'], 'compare' => '=', 'relation' => 'AND'];	
	
		$query['meta_query'] = $meta_query;	
		$company = usam_get_companies( $query );		
		if ( empty($company) )
		{
			$result = usam_find_company_in_directory(['search' => $query_vars['inn'], 'ppc' => $query_vars['ppc']]);			
			$company['id'] = 0;						
			foreach ( $properties as $property )
			{				
				$property->value = isset($result[$property->code])?$result[$property->code]:'';	
				$company['properties'][$property->code] = usam_format_property_api( $property );
			}			
		}
		else
		{	
			$company = (array)$company[0];
			foreach ( $properties as $p )
				$company['properties'][$p->code] = usam_get_object_property_value($company['id'], $p);
		}
		$company['name'] = isset($company['properties']['company_name'])?$company['properties']['company_name']->value:$company['properties']['full_company_name']->value;
		return $company;		
	}
	
	public static function search_directory_companies( WP_REST_Request $request ) 
	{
		$parameters = self::get_parameters( $request );
		$query_vars = self::get_query_vars( $parameters, $parameters );		
		$items = usam_find_company_in_directory(['search' => $query_vars['search'], 'search_type' => 'inn']);
		$properties = usam_get_properties(['access' => true, 'type' => 'company']);
		$results = [];
		foreach ( $properties as $property )
		{
			if ( isset($items[$property->code]) )
				$property->value = $items[$property->code];
			else
				$property->value = '';
			$results[$property->code] = usam_format_property_api( $property );
		}		
		return $results;
	}
		
	public static function delete_company( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		return usam_delete_company( $id );
	}
	
	public static function get_users( WP_REST_Request $request ) 
	{					
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
		
		$fields = isset($parameters['fields'])?$parameters['fields']:'';		
		unset($query_vars['fields']);
		
		if ( !empty($parameters['search']) )
			$query_vars['search'] .= '*';
		
		if ( !empty($parameters['company_id']) )
		{
			$user_ids = usam_get_contacts(['fields' => 'user_id', 'user_id__not_in' => 0, 'company_id' => $parameters['company_id']]);		
			if ( !empty($user_ids) )
				$query_vars['include'] = $user_ids;
			else
				return ['count' => 0, 'items' => []];
		}	
		if ( !empty($parameters['company_personal_account']) )
		{
			$user_ids = usam_get_company_personal_accounts( $parameters['company_personal_account'] );		
			if ( !empty($user_ids) )
				$query_vars['include'] = $user_ids;
			else
				return ['count' => 0, 'items' => []];
		}		
		$query = new WP_User_Query( $query_vars );		
		$items = $query->get_results();			
		if ( !empty($items) )
		{
			if ( $fields == 'autocomplete' )
			{	
				foreach ( $items as &$item )
				{
					unset($item->data->user_pass);
					unset($item->data->user_activation_key);
					$item = $item->data;
					$item->ID = (int)$item->ID;	
					$item->name = stripcslashes($item->user_nicename);
					unset($item->user_nicename);
				}
			}	
			else
			{
				$ids = [];
				foreach ( $items as &$item )
				{
					$ids[] = $item->ID;
				}
				$contacts = [];
				foreach ( usam_get_contacts(['user_id' => $ids]) as $contact )
				{
					$contacts[$contact->user_id] = $contact;
				}
				foreach ( $items as &$item )
				{
					$item->ID = (int)$item->ID;	
					$user = new stdClass();
					unset($item->data->user_pass);
					unset($item->data->user_activation_key);
					foreach( $item->data as $key => $data )
						$user->$key = $data;									
					
					$user->url = add_query_arg(['user_id' => $item->ID], admin_url('user-edit.php') );
					$user->roles = [];
					foreach( $item->roles as $role )
					{
						$user->roles[] = ['name' => translate_user_role( wp_roles()->roles[$role]['name'] ), 'code' => $role];
					}
					$user->contact = $contacts[$contact->user_id];
					$user->contact->url	= usam_get_contact_url( $user->contact->id );
					$item = $user;
				}
			}
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function get_user( WP_REST_Request $request ) 
	{
		$id = $request->get_param( 'id' );
		$userdata = get_userdata( $id );	
		$user = new stdClass();
		unset($userdata->data->user_pass);
		unset($userdata->data->user_activation_key);
		foreach( $userdata->data as $key => $data )
			$user->$key = $data;
		$user->url = add_query_arg(['user_id' => $user->ID], admin_url('user-edit.php') );
		foreach( $userdata->roles as $role )
		{
			$user->roles[] = ['name' => translate_user_role( wp_roles()->roles[$role]['name'] ), 'code' => $role];
		}
		return $user;
	}
	
	public static function save_password( WP_REST_Request $request ) 
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();				
		$user = wp_get_current_user();	
		
		$result = wp_set_password( $parameters['pass'], $user->ID );
		update_user_meta( $user->ID, 'default_password_nag', false );
		$redirect_to = usam_get_url_system_page('login');		
		return ['result' => true, 'redirect_to' => $redirect_to];
	}	
		
	public static function get_sellers( WP_REST_Request $request ) 
	{					
		$parameters = self::get_parameters( $request );
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
		
		$fields = isset($parameters['fields'])?$parameters['fields']:[];		
		if ( isset(self::$query_vars['fields']) && self::$query_vars['fields'] != 'id=>name' )
			unset(self::$query_vars['fields']);

		if ( $fields == 'autocomplete' )
			self::$query_vars['fields'] = ['id', 'name'];		
				
		if ( isset(self::$query_vars['status']) )
			self::$query_vars['status'] = array_map('sanitize_title', (array)self::$query_vars['status']);
		
		if ( isset(self::$query_vars['company']) )
			self::$query_vars['company_id'] = array_map('intval', (array)self::$query_vars['company']);
		
		if ( isset(self::$query_vars['gender']) )
			self::$query_vars['meta_query'][] = ['key' => 'sex', 'value' => self::$query_vars['gender'], 'compare' => '='];

		if ( isset(self::$query_vars['from_age']) )
		{
			$from_age = date('Y') - absint(self::$query_vars['from_age']);
			self::$query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $from_age.'-12-31', 'compare' => '>=', 'type' => 'DATE'];
		}
		if ( isset(self::$query_vars['to_age']) )
		{
			$to_age = date('Y') - absint(self::$query_vars['to_age']);
			self::$query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $to_age.'-1-1', 'compare' => '<=', 'type' => 'DATE'];
		}
		if ( isset(self::$query_vars['location']) )
			self::$query_vars['location'] = array_map('intval', (array)self::$query_vars['location']);
		
		if ( !empty(self::$query_vars['user_list']) )
		{
			$contact_id = usam_get_contact_id();	
			self::$query_vars['user_list'] = ['list' => self::$query_vars['user_list'], 'contact_id' => $contact_id];
		}				
		self::get_digital_interval_for_query($parameters, ['rating', 'number_products']);
		require_once(USAM_FILE_PATH.'/includes/marketplace/sellers_query.class.php');
		$query = new USAM_Sellers_Query( self::$query_vars );		
		$items = $query->get_results();			
		if ( !empty($items) )
		{
			if ( $fields == 'id=>name' || $fields == 'autocomplete' )
			{	
				foreach ( $items as &$item )
					$item = stripcslashes($item);
			}			
			else
			{
				if ( !empty($parameters['add_fields']) && in_array('properties', $parameters['add_fields']) )
					$properties = usam_get_properties(['access' => true, 'type' => 'contact']);
				
				foreach ( $items as &$item )
				{	
					if ( isset($item->date_insert) )
						$item->date_insert = usam_local_date(strtotime($item->date_insert), 'c');	
					if ( isset($item->status) )
					{
						$object_status = usam_get_object_status_by_code( $item->status, 'contact' );
						$item->status_name = isset($object_status['name'])?$object_status['name']:'';
						$item->status_color = isset($object_status['color'])?$object_status['color']:'';
						$item->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';
					}
					if( !empty($parameters['add_fields']) )
					{
						if ( in_array('foto', $parameters['add_fields']) )
						{
							if ( $item->seller_type == 'company'  )
								$item->foto = usam_get_company_logo( $item->id );
							else
								$item->foto = usam_get_contact_foto( $item->id );
						}					
						if ( in_array('properties', $parameters['add_fields']) )
						{						
							foreach( $properties as $p )
								$item->properties[$p->code] = usam_get_object_property_value( $item->id, $p );
						}
					}
				}				
			}
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		
		return apply_filters('usam_api_sellers', $results, self::$query_vars, $request);
	}
	
	public static function get_contacts( WP_REST_Request $request ) 
	{					
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
		if( !isset(self::$query_vars['source']) || !current_user_can('view_employees') )
			self::$query_vars['source__not_in'] = 'employee';
		
		$fields = isset($parameters['fields'])?$parameters['fields']:[];		
		if ( isset(self::$query_vars['fields']) && self::$query_vars['fields'] != 'id=>name' )
			unset(self::$query_vars['fields']);

		if ( $fields == 'autocomplete' )
			self::$query_vars['fields'] = ['id', 'appeal'];		
				
		if ( isset(self::$query_vars['status']) )
			self::$query_vars['status'] = array_map('sanitize_title', (array)self::$query_vars['status']);
		
		if ( isset(self::$query_vars['company']) )
			self::$query_vars['company_id'] = array_map('intval', (array)self::$query_vars['company']);
		
		if ( isset(self::$query_vars['gender']) )
			self::$query_vars['meta_query'][] = ['key' => 'sex', 'value' => self::$query_vars['gender'], 'compare' => '='];

		if ( isset(self::$query_vars['from_age']) )
		{
			$from_age = date('Y') - absint(self::$query_vars['from_age']);
			self::$query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $from_age.'-12-31', 'compare' => '>=', 'type' => 'DATE'];
		}
		if ( isset(self::$query_vars['to_age']) )
		{
			$to_age = date('Y') - absint(self::$query_vars['to_age']);
			self::$query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $to_age.'-1-1', 'compare' => '<=', 'type' => 'DATE'];
		}
		if ( isset(self::$query_vars['location']) )
			self::$query_vars['location'] = array_map('intval', (array)self::$query_vars['location']);
				
		if ( isset($parameters['add_fields']) )
		{
			if( in_array('post', $parameters['add_fields']) || in_array('properties', $parameters['add_fields']) || in_array('communication', $parameters['add_fields']) )
				self::$query_vars['cache_meta'] = true;	
			if ( in_array('foto', $parameters['add_fields']) )
			{
				self::$query_vars['cache_thumbnail'] = true;	
				self::$query_vars['cache_meta'] = true;	
			}
			if ( in_array('affairs', $parameters['add_fields']) )
				self::$query_vars['cache_case'] = true;
		}			
		
		$query = new USAM_Contacts_Query( self::$query_vars );		
		$items = $query->get_results();			
		if ( !empty($items) )
		{
			if ( $fields == 'id=>name' )
			{	
				foreach ( $items as &$item )
					$item = esc_html($item);
			}
			elseif ( $fields == 'autocomplete' )
			{	
				foreach ( $items as &$item )
				{
					$item->name = stripcslashes($item->appeal);
					unset($item->appeal);
				}
			}
			else
			{
				if ( isset($parameters['add_fields']) && in_array('properties', $parameters['add_fields']) )
					$properties = usam_get_properties(['access' => true, 'type' => 'contact']);
				
				foreach ( $items as &$item )
				{
					if ( isset($item->date_insert) )
						$item->date_insert = usam_local_date(strtotime($item->date_insert), 'c');	
					$item->online = $item->online && strtotime($item->online) >= USAM_CONTACT_ONLINE;					
					if ( isset($parameters['add_fields']) )
					{
						if( in_array('manager', $parameters['add_fields']) )
						{
							$item->manager = usam_get_contact( $item->manager_id, 'user_id' );
							if( $item->manager )
								$item->manager['foto'] = usam_get_contact_foto( $item->manager['id'] );
						}
						if ( in_array('foto', $parameters['add_fields']) )
							$item->foto = usam_get_contact_foto( $item->id );	
						if ( in_array('post', $parameters['add_fields']) )
							$item->post = usam_get_contact_metadata($item->id, 'post');	
						if ( in_array('source', $parameters['add_fields']) )
							$item->source = usam_get_name_contact_source( $item->contact_source );		
						if( in_array('status_data', $parameters['add_fields']) && isset($item->status) )
						{
							$object_status = usam_get_object_status_by_code( $item->status, 'contact' );
							$item->status_name = isset($object_status['name'])?$object_status['name']:'';
							$item->status_color = isset($object_status['color'])?$object_status['color']:'';
							$item->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';
						}
						if ( in_array('affairs', $parameters['add_fields']) )
						{
							$item->affairs = usam_get_customer_case( $item->id, 'contact' );	
						}
						if ( in_array('communication', $parameters['add_fields']) && isset($item->status) )
						{
							$item->emails = usam_get_contact_emails( $item->id );	
							$item->phones = usam_get_contact_phones( $item->id );	
						}
						if ( in_array('properties', $parameters['add_fields']) )
						{						
							foreach( $properties as $p )
								$item->properties[$p->code] = usam_get_object_property_value( $item->id, $p );
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
	
	public static function get_contact( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );
		$args = ['active' => 1, 'type' => 'contact', 'orderby' => ['group', 'sort']];
		$id = usam_get_contact_id();
		if( current_user_can('universam_api') || current_user_can('view_contacts') )
		{
			$contact_id = $request->get_param( 'id' );	
			if ( $contact_id )
				$id = $contact_id;
			$args['show_staff'] = 1;	
		}
		else		
			$args['profile'] = 1;
		$contact = self::get_contact_data( $id );		
		if( isset($parameters['add_fields']) )
		{						
			if ( in_array('order_properties', $parameters['add_fields']) )
			{					
				$contact['properties'] = [];
				$payers = usam_get_group_payers(['type' => 'contact']);
				$metas = usam_get_contact_metas( $id );							
				$args = ['access' => true, 'type' => 'order', 'cache_meta' => true];
				if ( isset($payers[0]['id']) )
					$args['type_payer'] = $payers[0]['id'];
				$properties = usam_get_properties( $args );		
				foreach ($properties as $property)
				{
					$connection = usam_get_property_metadata($property->id, 'connection');				
					$property->value = '';
					if ( !empty($connection) )
					{
						if ( !empty($metas[$connection]) )
							$property->value = $metas[$connection];	
					}
					$contact['properties'][$property->code] = usam_format_property_api( $property );
				}
			}
			if ( in_array('groups', $parameters['add_fields']) )
				$contact['groups'] = usam_get_property_groups(['type' => 'contact']);	
			if ( in_array('post', $parameters['add_fields']) )
				$contact['post'] = (string)usam_get_contact_metadata($id, 'post');
			if ( in_array('properties', $parameters['add_fields']) )				
				$contact['properties'] = self::get_object_properties( $id, $args );
		}		
		return $contact;		
	}
		
	public static function get_profile( WP_REST_Request $request ) 
	{		
		$user_id = get_current_user_id();
		$contact_id = usam_get_contact_id();
		
		$properties_args = ['access' => true, 'active' => 1, 'profile' => 1, 'orderby' => ['group', 'sort']];
		$result = ['company' => []];				
		if( current_user_can('seller_company') )
		{
			$properties_args['type'] = 'company';
			$company = usam_get_companies(['user_id' => $user_id, 'number' => 1, 'cache_results' => true]);
			if ( !empty($company) )
			{	
				$result['company']['logo'] = usam_get_company_logo( $company['id'] );
				$result['properties'] = self::get_object_properties($company['id'], $properties_args);
			}
			else
			{
				$properties_args['fields'] = 'code=>data';
				$result['properties'] = usam_get_properties( $properties_args );
			}			
			$result['groups'] = usam_get_property_groups(['type' => 'company']);	
		}
		else
		{					
			$result = self::get_contact( $request );
			$result['profile_type'] = 'contact';
			$properties_args['type'] = 'contact';		
			$result['properties'] = self::get_object_properties( $contact_id, $properties_args );
			$result['groups'] = usam_get_property_groups(['type' => 'contact']);			
		}
		$result['contact'] = self::get_contact_data( $contact_id );
		return $result;
	}
	
	public static function get_object_properties( $id, $args ) 
	{	
		$args['active'] = 1;
		$args['access'] = true;
		$args['cache_meta'] = true;
		$properties = usam_get_properties( $args );
		$result = [];	
		foreach ( $properties as $p )
			$result[$p->code] = usam_get_object_property_value( $id, $p );
		return $result;
	}
	
	public static function save_object_properties( $id, $parameters, $args = [] ) 
	{	
		$args['active'] = 1;
		$args['access'] = true;
		$args['cache_meta'] = true;		
		$properties = usam_get_properties( $args );  
		foreach ( $properties as $property )
		{	
			if ( !isset($parameters[$property->code]) )
				continue;		
			usam_save_property_meta( $id, $property, $parameters[$property->code] );
		}
	}
	
	public static function save_contact( WP_REST_Request $request ) 
	{		
		$contact_id = (int)$request->get_param( 'id' );	
		if( $contact_id === 0 || !current_user_can('universam_api') && !current_user_can('edit_contact') )
			$contact_id = usam_get_contact_id();
	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		if ( USAM_CRM_API::update_contact( $contact_id, $parameters ) )
		{		
			$args = ['type' => 'contact'];
			if ( !current_user_can('edit_contact') )
				$args['profile'] = 1;
			self::save_object_properties( $contact_id, $parameters, $args);
			return true;
		}
		else
			return new WP_Error( 'no_contact', 'Invalid contact id', ['status' => 404]);
	}	
	
	public static function insert_contact( WP_REST_Request $request ) 
	{				
		$contact_id = usam_get_contact_id();		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();	
				
		$contact_id = usam_insert_contact( $parameters );
		if ( $contact_id )
		{
			$keys = ['sex', 'notifications_email',	'notifications_sms', 'foto', 'post', 'code', 'favorite_shop', 'type_price'];
			foreach ( $keys as $key )
				if( isset($parameters[$key]) )
					usam_add_contact_metadata( $contact_id, $key, $parameters[$key] );				
			if ( isset($parameters['birthday']) )			
			{
				if ( !empty($parameters['birthday']) )
					$parameters['birthday'] = date("Y-m-d H:i:s", strtotime($parameters['birthday']));
				usam_add_contact_metadata( $contact_id, 'birthday', $parameters['birthday'] );	
			}		
			if ( !empty($parameters['subscriptions']) )
			{
				$condition = usam_get_contact_metadata( $contact_id, 'email' );
				foreach( $parameters['subscriptions'] as $list_id => $status )
					usam_set_subscriber_lists(['communication' => $condition, 'status' => $status, 'id' => $list_id]);
			}
			if( isset($parameters['groups']) )
				usam_set_groups_object( $contact_id, 'contact', $parameters['groups'] );
			return $contact_id;
		}
		else
			return new WP_Error( 'no_contact', 'Invalid insert contact', ['status' => 404]);
	}

	public static function save_profile( WP_REST_Request $request ) 
	{				
		$user_id = get_current_user_id();
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();					
		$result = true;
		if ( !empty($parameters['contact']) )
		{
			$contact_id = usam_get_contact_id();	
			$properties = usam_get_properties(['access' => true, 'type' => 'contact', 'active' => 1, 'profile' => 1]);
			$data = [];
			foreach( $properties as $k => $property )
			{			
				if ( isset($parameters[$property->code]) )
					$data[$property->code] = $parameters[$property->code];
			}
			if ( !USAM_CRM_API::update_contact( $contact_id, $data ) )			
				return new WP_Error( 'no_contact', 'Invalid contact id', ['status' => 404]);
		}
		if( current_user_can('seller_company') )
		{
			$company = usam_get_companies(['user_id' => $user_id, 'number' => 1, 'cache_results' => true]);
			if ( $company )
			{
				self::save_object_properties( $company['id'], $parameters, ['type' => 'company', 'profile' => 1]);			
				if ( !empty($parameters['logo']) )
					usam_update_company_logo( $company['id'], absint($parameters['logo']) );		
			}
			else
				return new WP_Error( 'no_contact', 'Invalid company id', ['status' => 404]);
		}		
		if ( get_option('usam_user_profile_activation') )
		{	
			$user_id = get_current_user_id();		
			if ( update_user_option($user_id, 'usam_user_profile_activation', 1) )
				do_action( 'usam_user_profile_activation', $user_id);
		}
		return $result;
	}
		
	private static function update_contact( $contact_id, $parameters ) 
	{		
		$contact = usam_get_contact( $contact_id );
		if ( $contact )	
		{					
			if ( isset($parameters['birthday']) )
				$parameters['birthday'] = $parameters['birthday'] ? date("Y-m-d H:i:s", strtotime($parameters['birthday'])) : '';
				
			$names = usam_create_contact_name( $contact_id, $parameters );	
			if( current_user_can('edit_contact') )
			{
				if( !isset($parameters['appeal']) && isset($names['appeal']) )
					$parameters['appeal'] = $names['appeal'];
			}
			else
			{							
				$keys = ['date_insert', 'appeal', 'contact_source', 'status', 'user_id', 'manager_id', 'open'];
				foreach ( $keys as $key )
					if( isset($parameters[$key]) )
						unset($parameters[$key]);	
				if( isset($names['appeal']) )
					$parameters['appeal'] = $names['appeal'];
			}	
			if( isset($names['full_name']) )
				$parameters['full_name'] = $names['full_name'];							
			if ( !empty($parameters['subscriptions']) )
			{
				$condition = usam_get_contact_metadata( $contact_id, 'email' );
				foreach( $parameters['subscriptions'] as $list_id => $status )
					usam_set_subscriber_lists(['communication' => $condition, 'status' => $status, 'id' => $list_id]);
				unset($parameters['subscriptions']);
			}
			if( isset($parameters['groups']) )
			{
				usam_set_groups_object( $contact_id, 'contact', $parameters['groups'] );
				unset($parameters['groups']);
			}
			if( $parameters )
				usam_update_contact( $contact_id, $parameters );			
			return true;
		}
		return false;
	}
		
	public static function delete_contact( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$contact = usam_get_contact( $id );
		if ( $contact && $contact['contact_source'] != 'employee' )
			return usam_delete_contact( $id );
		else
			return new WP_Error( 'no_contact', 'Invalid contact id', ['status' => 404]);
	}
	
	public static function get_addresses( WP_REST_Request $request ) 
	{					
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
					
		if ( !current_user_can('universam_api') && !current_user_can('view_contacts') || $query_vars['contact_id'] == 'current' )		
			$query_vars['contact_id'] = usam_get_contact_id();	
		
		require_once( USAM_FILE_PATH . '/includes/crm/contact_address_query.class.php' );
		$query = new USAM_Contact_Address_Query( $query_vars );		
		$items = $query->get_results();			
		if ( !empty($items) )
		{
			foreach ( $items as &$item )
			{
				$locations = usam_get_address_locations( $item->location_id );
				foreach ( $locations as $key => $name )
				{
					$item->$key = $name;
				}		
				$flat = $item->flat?', '.__( 'кв.', 'kodi').' '.$item->flat:'';
				$floor = $item->floor?', '.__( 'этаж.', 'kodi').' '.$item->floor:'';
				$item->name = __('г.', 'usam')." $city, ".$item->street.", ".$item->house."{$flat}{$floor}";			
			}
			$count = $query->get_total();	
			$results = ['count' => $count, 'items' => $items];
			if ( !empty($query_vars['contact_id']) )
				$results['main_address'] = usam_get_contact_metadata( $query_vars['contact_id'], 'main_address' );
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function insert_address( WP_REST_Request $request ) 
	{					
		$args = $request->get_json_params();		
		if ( !$args )
			$args = $request->get_body_params();
		
		if ( !current_user_can('universam_api') && !current_user_can('view_contacts') || empty($args['contact_id']) )		
			$args['contact_id'] = usam_get_contact_id();
		
		if ( empty($args['contact_id']) )
			return new WP_Error( 'no_contact', 'Invalid contact id', ['status' => 404]);
		
		if ( empty($args['location_id']) )
			$args['location_id'] =  usam_get_customer_location();
		if ( empty($args['location_id']) )
			return new WP_Error( 'no_location', 'Invalid location id', ['status' => 404]);
								
		$args['street'] = stripslashes($args['street']);
		require_once( USAM_FILE_PATH . '/includes/crm/contact_address.class.php' );
		$address_id = usam_insert_contact_address( $args );
		
		if ( $address_id && !empty($args['main']) )
			usam_update_contact_metadata( $args['contact_id'], 'main_address', $address_id );
		else
		{
			$main_address = usam_get_contact_metadata( $args['contact_id'], 'main_address' );		
			if ( !$main_address )
				usam_update_contact_metadata( $args['contact_id'], 'main_address', $address_id );	
		}
		return ['id' => $address_id];
	}
	
	public static function delete_address( WP_REST_Request $request ) 
	{					
		require_once( USAM_FILE_PATH . '/includes/crm/contact_address.class.php' );
		$id = $request->get_param( 'id' );		
		if ( !current_user_can('universam_api') )	
		{
			$address = usam_get_contact_address( $id );
			if ( $address['contact_id'] != usam_get_contact_id() )
				return new WP_Error( 'no_contact', 'Invalid contact id', ['status' => 404]);
		}
		return usam_delete_contact_address( $id );
	}	

	public static function save_address( WP_REST_Request $request ) 
	{					
		$id = $request->get_param( 'id' );		
		$args = $request->get_json_params();		
		if ( !$args )
			$args = $request->get_body_params();
		
		if ( !current_user_can('universam_api') && !current_user_can('view_contacts') )
			$args['contact_id'] = usam_get_contact_id();	
		
		if ( !empty($args['contact_id']) && !empty($args['main']) )
		{			
			$main = $args['main']?$id:0;
			usam_update_contact_metadata( $args['contact_id'], 'main_address', $main );	
		}
		require_once( USAM_FILE_PATH . '/includes/crm/contact_address.class.php' );
		
		$args['street'] = stripslashes($args['street']);
		return usam_update_contact_address( $id, $args );
	}
	
	
	public static function get_coupons( WP_REST_Request $request ) 
	{					
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
					
		if ( !current_user_can('universam_api') && !current_user_can('view_coupons') || $query_vars['user_id'] == 'current' )		
			$query_vars['user_id'] = get_current_user_id();		
		require_once( USAM_FILE_PATH . '/includes/basket/coupons_query.class.php' );
		$query = new USAM_Coupons_Query( $query_vars );		
		$items = $query->get_results();			
		if ( !empty($items) )			
		{
			foreach ( $items as &$item )
			{
				$item->sign = $item->is_percentage == 0 ? '%': usam_get_currency_sign();				
			}
			$count = $query->get_total();	
			$results = ['count' => $count, 'items' => $items];		
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function insert_coupon( WP_REST_Request $request ) 
	{					
		$args = $request->get_json_params();		
		if ( !$args )
			$args = $request->get_body_params();		
		$id = usam_insert_coupon( $args );
		return $id;
	}
	
	public static function get_coupon( WP_REST_Request $request ) 
	{					
		$id = $request->get_param( 'id' );		
		return usam_get_coupon( $id );
	}
	
	public static function delete_coupon( WP_REST_Request $request ) 
	{					
		$id = $request->get_param( 'id' );		
		return usam_delete_coupon( $id );
	}

	public static function save_coupon( WP_REST_Request $request ) 
	{					
		$id = $request->get_param( 'id' );	
		$args = $request->get_json_params();		
		if ( !$args )
			$args = $request->get_body_params();

		return usam_update_coupon( $id, $args );
	}	
				
	public static function get_couriers( WP_REST_Request $request ) 
	{					
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
		$query_vars['role__in'] = 'courier';	
		$query_vars['source'] = 'all';
		$query_vars['cache_thumbnail'] = true;
		$fields = isset($parameters['fields'])?$parameters['fields']:[];		
		if ( isset($query_vars['fields']) && $query_vars['fields'] != 'id=>name' )
			unset($query_vars['fields']);

		if ( $fields == 'autocomplete' )
			$query_vars['fields'] = ['id', 'appeal'];		
		
		if ( isset($query_vars['status']) )
			$query_vars['status'] = array_map('sanitize_title', (array)$query_vars['status']);
		
		if ( isset($query_vars['company']) )
			$query_vars['company_id'] = array_map('intval', (array)$query_vars['company']);
		
		if ( isset($query_vars['gender']) )
			$query_vars['sex'] = array_map('sanitize_title', (array)$query_vars['gender']);

		if ( isset($query_vars['from_age']) )
		{
			$from_age = date('Y') - absint($query_vars['from_age']);
			$query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $from_age.'-12-31', 'compare' => '>=', 'type' => 'DATE'];
		}
		if ( isset($query_vars['to_age']) )
		{
			$to_age = date('Y') - absint($query_vars['to_age']);
			$query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $to_age.'-1-1', 'compare' => '<=', 'type' => 'DATE'];
		}
		if ( isset($query_vars['location']) )
			$query_vars['location'] = array_map('intval', (array)$query_vars['location']);
		
		if ( isset($query_vars['department']) )
		{
			$department = array_map('intval', (array)$query_vars['department']);
			$query_vars['meta_query'][] = ['key' => 'department', 'compare' => '=', 'value' => $department, 'relation' => 'AND', 'type' => 'NUMERIC'];				
		}		
		$query_vars['cache_meta'] = true;
		$query = new USAM_Contacts_Query( $query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			if ( $fields == 'id=>name' )
			{	
				foreach ( $items as &$item )
					$item = esc_html($item);
			}
			elseif ( $fields == 'autocomplete' )
			{	
				foreach ( $items as &$item )
				{
					$item->name = stripcslashes($item->appeal);
					unset($item->appeal);
				}
			}
			else
			{
				if ( isset($parameters['add_fields']) && in_array('properties', $parameters['add_fields']) )
					$properties = usam_get_properties(['access' => true, 'type' => 'contact']);
				
				foreach ( $items as &$item )
				{	
					if ( isset($item->date_insert) )
						$item->date_insert = usam_local_date(strtotime($item->date_insert), 'c');	
					$item->department_id = usam_get_contact_metadata($item->id, 'department');						
					$item->foto = usam_get_contact_foto( $item->id );
					if ( strtotime($item->online) >= USAM_CONTACT_ONLINE )
						$item->online = true;
					else
						$item->online = false;					
					$item->post = (string)usam_get_contact_metadata($item->id, 'post');				
					if ( isset($parameters['add_fields']) )
					{
						if( in_array('manager', $parameters['add_fields']) )
						{
							$item->manager = usam_get_contact( $item->manager_id, 'user_id' );
							if( $item->manager )
								$item->manager['foto'] = usam_get_contact_foto( $item->manager['id'] );
						}
						if ( in_array('foto', $parameters['add_fields']) )
							$item->foto = usam_get_contact_foto( $item->id );	
						if ( in_array('post', $parameters['add_fields']) )
							$item->post = usam_get_contact_metadata($item->id, 'post');	
						if ( in_array('source', $parameters['add_fields']) )
							$item->source = usam_get_name_contact_source( $item->contact_source );	
						if ( in_array('status_data', $parameters['add_fields']) && isset($item->status) )
						{
							$object_status = usam_get_object_status_by_code( $item->status, 'contact' );
							$item->status_name = isset($object_status['name'])?$object_status['name']:'';
							$item->status_color = isset($object_status['color'])?$object_status['color']:'';
							$item->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';
						}
						if ( in_array('affairs', $parameters['add_fields']) )
						{
							$item->affairs = usam_get_customer_case( $item->id, 'contact' );	
						}
						if ( in_array('properties', $parameters['add_fields']) )
						{						
							foreach( $properties as $p )
								$item->properties[$p->code] = usam_get_object_property_value( $item->id, $p );
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
			
	public static function get_employees( WP_REST_Request $request ) 
	{							
		$parameters = self::get_parameters( $request );		
		$query_vars = self::get_query_vars( $parameters, $parameters );
		$query_vars['source'] = 'employee';			
		$fields = isset($parameters['fields'])?$parameters['fields']:[];
		if ( $fields === 'autocomplete' )
			$query_vars['fields'] = ['id', 'appeal'];			
		if ( $fields !== 'id=>name' && $fields !== 'user_id=>name' && $fields !== 'autocomplete' )
		{
			$query_vars['cache_meta'] = true;
			$query_vars['cache_thumbnail'] = true;
		}
		if ( isset($query_vars['status']) )
			$query_vars['status'] = array_map('sanitize_title', (array)$query_vars['status']);
		
		if ( isset($query_vars['company']) )
			$query_vars['company_id'] = array_map('intval', (array)$query_vars['company']);
		
		if ( isset($query_vars['gender']) )
			$query_vars['sex'] = array_map('sanitize_title', (array)$query_vars['gender']);

		if ( isset($query_vars['from_age']) )
		{
			$from_age = date('Y') - absint($query_vars['from_age']);
			$query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $from_age.'-12-31', 'compare' => '>=', 'type' => 'DATE'];
		}
		if ( isset($query_vars['to_age']) )
		{
			$to_age = date('Y') - absint($query_vars['to_age']);
			$query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $to_age.'-1-1', 'compare' => '<=', 'type' => 'DATE'];
		}
		if ( isset($query_vars['location']) )
			$query_vars['location'] = array_map('intval', (array)$query_vars['location']);
		
		if ( isset($query_vars['department']) )
		{
			$department = array_map('intval', (array)$query_vars['department']);
			$query_vars['meta_query'][] = array('key' => 'department', 'compare' => '=', 'value' => $department, 'relation' => 'AND', 'type' => 'NUMERIC');				
		}		
		if ( isset($parameters['add_fields']) )
		{
			if( in_array('properties', $parameters['add_fields']) )
			{
				$properties = usam_get_properties(['access' => true, 'type' => 'contact']);
			}
		}	
		$query = new USAM_Contacts_Query( $query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			if ( $fields === 'id=>name' || $fields === 'user_id=>name' )
			{	
				foreach( $items as &$item )
					$item = esc_html($item);
			}
			elseif ( $fields == 'autocomplete' )
			{	
				foreach ( $items as &$item )
				{
					$item->name = stripcslashes($item->appeal);
					unset($item->appeal);
				}
			}
			else
			{				
				foreach ( $items as &$item )
				{	
					if ( isset($item->date_insert) )
						$item->date_insert = usam_local_date(strtotime($item->date_insert), 'c');	
					$item->department_id = (int)usam_get_contact_metadata($item->id, 'department');	
					$item->online = strtotime($item->online) >= USAM_CONTACT_ONLINE;
					if ( isset($parameters['add_fields']) )
					{
						if( in_array('manager', $parameters['add_fields']) )
						{
							$item->manager = usam_get_contact( $item->manager_id, 'user_id' );
							if( $item->manager )
								$item->manager['foto'] = usam_get_contact_foto( $item->manager['id'] );
						}
						if ( in_array('foto', $parameters['add_fields']) )
							$item->foto = usam_get_contact_foto( $item->id );	
						if ( in_array('post', $parameters['add_fields']) )
							$item->post = (string)usam_get_contact_metadata($item->id, 'post');	
						if ( in_array('source', $parameters['add_fields']) )
							$item->source = usam_get_name_contact_source( $item->contact_source );	
						if ( in_array('status_data', $parameters['add_fields']) && isset($item->status) )
						{
							$object_status = usam_get_object_status_by_code( $item->status, 'contact' );
							$item->status_name = isset($object_status['name'])?$object_status['name']:'';
							$item->status_color = isset($object_status['color'])?$object_status['color']:'';
							$item->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';
						}
						if( in_array('properties', $parameters['add_fields']) )
						{			
							foreach( $properties as $p )
								$item->properties[$p->code] = usam_get_object_property_value( $item->id, $p );
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
	
	public static function get_employee( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );		
		if( !$id )
			$id = usam_get_contact_id();
		$parameters = self::get_parameters( $request );	
		$contact = usam_get_contact( $id );
		if ( $contact['contact_source'] == 'employee' )
		{	
			$contact['online'] = strtotime($contact['online']) >= USAM_CONTACT_ONLINE;
			if( isset($parameters['add_fields']) )
			{
				if ( in_array('department', $parameters['add_fields']) )
					$contact['department_id'] = usam_get_contact_metadata($contact['id'], 'department');
				if ( in_array('foto', $parameters['add_fields']) )
					$contact['foto'] = usam_get_contact_foto( $contact['id'] );
				if ( in_array('groups', $parameters['add_fields']) )
					$contact['groups'] = usam_get_property_groups(['type' => 'contact']);	
				if ( in_array('post', $parameters['add_fields']) )
					$contact['post'] = (string)usam_get_contact_metadata($id, 'post');
				if ( in_array('properties', $parameters['add_fields']) )				
					$contact['properties'] = self::get_object_properties( $id, ['active' => 1, 'type' => 'contact', 'orderby' => ['group', 'sort'], 'cache_results' => true, 'cache_group' => true]);
			}	
			return $contact;
		}
		else
			return new WP_Error( 'no_employee', 'Invalid employee id', ['status' => 404]);
	}	
	
	public static function insert_employee( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
						
		$parameters['contact_source'] = 'employee';
		$contact_id = usam_insert_contact( $parameters );
		if ( $contact_id )
		{
			$keys = ['sex', 'foto', 'post', 'code', 'department'];
			foreach ( $keys as $key )
				if( isset($parameters[$key]) )
					usam_add_contact_metadata( $contact_id, $key, $parameters[$key] );				
			if ( isset($parameters['birthday']) )			
			{
				if ( !empty($parameters['birthday']) )
					$parameters['birthday'] = date("Y-m-d H:i:s", strtotime($parameters['birthday']));
				usam_add_contact_metadata( $contact_id, 'birthday', $parameters['birthday'] );	
			}		
			if ( !empty($parameters['start_work_date']) )
				usam_add_contact_metadata($id, 'start_work_date', date("Y-m-d H:i:s", strtotime($parameters['start_work_date'])));				
			if( isset($parameters['groups']) )
				usam_set_groups_object( $contact_id, 'contact', $parameters['groups'] );
			return $contact_id;
		}
		else
			return new WP_Error( 'no_contact', 'Invalid insert employee', ['status' => 404]);
	}	
	
	public static function save_employee( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$contact = usam_get_contact( $id );	
		if ( $contact )		
		{						
			$parameters['contact_source'] = 'employee';
			USAM_CRM_API::update_contact( $id, $parameters );			
			if ( !empty($parameters['department']) )
				usam_update_contact_metadata($id, 'department', intval($parameters['department']));	
			if ( !empty($parameters['start_work_date']) )
				usam_update_contact_metadata($id, 'start_work_date', date("Y-m-d H:i:s", strtotime($parameters['start_work_date'])));			
			return true;
		}
		else
			return new WP_Error( 'no_employee', 'Invalid employee id', ['status' => 404]);
	}	
	
	public static function delete_employee( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		return usam_delete_contact( $id );
	}
	
	public static function dismissal_employee( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$contact = usam_get_contact( $id );
		if( $contact['contact_source'] == 'employee' )
		{
			usam_update_contact( $id, ['contact_source' => 'formeremployee'] );
			if( $contact['user_id'] )
			{
				$user_object = new WP_User( $contact['user_id'] );
				$user_object->set_role( get_option('default_role') );
			}
			return true;
		}
		else
			return new WP_Error( 'no_employee', 'Invalid employee id', ['status' => 404]);
	}
			
	public static function insert_department( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();				
	
		require_once( USAM_FILE_PATH .'/includes/personnel/department.class.php' );
		$id = usam_insert_department( $parameters );
		return $id;		
	}

	public static function update_department( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
			
		require_once( USAM_FILE_PATH .'/includes/personnel/department.class.php' );
		return usam_update_department( $id, $parameters );
	}	
	
	public static function delete_department( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		require_once( USAM_FILE_PATH .'/includes/personnel/department.class.php' );
		return usam_delete_department( $id );		
	}
	
	public static function get_comments( WP_REST_Request $request ) 
	{
		$user_id = get_current_user_id();
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
		
		require_once( USAM_FILE_PATH . '/includes/crm/comments_query.class.php' );
		$query = new USAM_Comments_Query( $query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			foreach ( $items as &$item )
			{					
				$item->author = usam_get_contact( $item->user_id, 'user_id');
				$item->author_image = usam_get_contact_foto( $item->user_id, 'user_id' );
				$item->author_url = usam_get_contact_url( $item->user_id, 'user_id' );	
				$item->mine = $user_id == $item->user_id;	
			}
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function insert_comment( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
				
		require_once( USAM_FILE_PATH.'/includes/crm/comment.class.php' );
		if ( empty($parameters['user_id']) )
			$parameters['user_id'] = get_current_user_id();	
		$id = usam_insert_comment( $parameters );
		$comment = usam_get_comment( $id );		
		$user_id = get_current_user_id();						
		$comment['author'] = usam_get_contact( $comment['user_id'], 'user_id');
		$comment['author_image'] = usam_get_contact_foto( $comment['user_id'], 'user_id' );
		$comment['author_url'] = usam_get_contact_url( $comment['user_id'], 'user_id' );						
		$comment['mine'] = $user_id == $comment['user_id'];
		if ( !empty($parameters['ribbon']) )
		{
			require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );
			$comment['event_id'] = $id;
			$comment['event_type'] = 'comment';
			usam_insert_ribbon( $comment, $parameters );
		}
		return $comment;		
	}

	public static function update_comment( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH.'/includes/crm/comment.class.php' );
		return usam_update_comment( $id, $parameters );
	}	
	
	public static function delete_comment( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		require_once( USAM_FILE_PATH.'/includes/crm/comment.class.php' );
		if ( current_user_can('universam_api') )
			return usam_delete_comment( $id );	
		else
		{
			$comment = usam_get_comment( $id );
			if ( $comment && $comment['user_id'] == get_current_user_id() )
				return usam_delete_comment( $id );
			else
				return false;
		}
	}
	
	public static function get_livefeed( WP_REST_Request $request ) 
	{
		$user_id = get_current_user_id();
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
		
		require_once( USAM_FILE_PATH . '/includes/crm/ribbon_query.class.php' );
	
		$query = new USAM_Ribbon_Query( $query_vars );		
		$items = $query->get_results();	 
		if ( !empty($items) )
		{
			$objects = [];
			foreach ($items as $k => $result )
			{
				$events_types = usam_get_events_types();
				if ( isset($events_types[$result->event_type]) )
					$objects['event'][] = $result->event_id;	
				else
					$objects[$result->event_type][] = $result->event_id;							
			}	
			$items = self::get_objects( $objects, $items );
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	private static function get_objects( $objects, $items )
	{	
		foreach ($objects as $type => $ids )
		{
			switch( $type ) 
			{
				case 'contact' :
					require_once( USAM_FILE_PATH .'/includes/crm/contacts_query.class.php' );
					$data = usam_get_contacts(['include' => $ids]);	
					$items = self::object_format_data( $items, $data, $type );	
					foreach ( $items as $k => $result )
					{
						if ( $result->object_type == $type )
							$items[$k]->foto = usam_get_contact_foto( $result->id );
					}
				break;
				case 'company' :
					require_once( USAM_FILE_PATH .'/includes/crm/companies_query.class.php' );
					$data = usam_get_companies(['include' => $ids]);	
					$items = self::object_format_data( $items, $data, $type );
					foreach ( $items as $k => $result )
					{
						if ( $result->object_type == $type )
							$items[$k]->logo = usam_get_company_logo( $result->id );
					}					
				break;
				case 'page' :
					$data = usam_get_posts(['post__in' => $ids, 'post_type' => 'any']);	
					$items = self::object_format_data( $items, $data, $type );
					foreach ( $items as $k => $result )
					{
						if ( $result->object_type == $type )
							$items[$k]->url = get_permalink( $result->ID );
					}					
				break;
				case 'product' :
					$data = usam_get_products(['post__in' => $ids]);	
					$items = self::object_format_data( $items, $data, $type );	
					foreach ( $items as $k => $result )
					{
						if ( $result->object_type == $type )
						{
							$items[$k]->medium_image = usam_get_product_thumbnail_src( $result->ID, 'product-thumbnails' );
							$items[$k]->sku = usam_get_product_meta($result->ID, 'sku');
						}
					}
				break;
				case 'sms' :
					require_once( USAM_FILE_PATH .'/includes/feedback/sms_query.class.php'  );
					$data = usam_get_sms_query(['include' => $ids]);	
					$items = self::livefeed_format_data( $items, $data, $type );
				break;				
				case 'email' :
					require_once(USAM_FILE_PATH.'/includes/mailings/email_query.class.php');
					$data = usam_get_emails(['include' => $ids, 'folder_not_in' => 'deleted', 'add_fields' => 'size']);	
					$items = self::livefeed_format_data( $items, $data, $type );
					foreach ( $items as $k => $result )
					{	
						if( $result->event_type == $type )
							$items[$k] = USAM_CRM_API::format_event_email( $items[$k] );
					}			
				break;
				case 'comment' :
					require_once( USAM_FILE_PATH . '/includes/crm/comments_query.class.php' );
					$data = usam_get_comments(['include' => $ids, 'folder_not_in' => 'deleted']);	
					$items = self::livefeed_format_data( $items, $data, $type );
				break;
				case 'chat' :
					require_once(USAM_FILE_PATH.'/includes/feedback/chat_messages_query.class.php');
					$data = usam_get_chat_messages(['include' => $ids, 'add_fields' => 'status']);
					foreach ( $items as $k => $result )
					{
						if ( $result->event_type == $type )
						{
							$ok = false;
							foreach ( $data as $i => $item )
							{
								if ( $result->event_id == $item->id )
								{		
									$ok = true;
									foreach ( $item as $key => $value )				
										$items[$k]->$key = $value;	
									$contact = self::get_contact_data( $items[$k]->contact_id );
									$items[$k]->author = new stdClass();
									foreach ( $contact as $key => $value )				
										$items[$k]->author->$key = $value;										
									unset($data[$i]);
									break;
								}
							}
							if ( !$ok )
								unset($items[$k]);
						}
					}						
				break;
				case 'event' :
					$data = usam_get_events(['include' => $ids]);	
					foreach ( $items as $k => $result )			
						foreach ( $data as $item )
						{
							if ( $result->event_id == $item->id && $result->event_type == $item->type )
							{
								foreach ( $item as $key => $value )
									$items[$k]->$key = $value;	
								$object_status = usam_get_object_status_by_code( $item->status, $item->type );
								$items[$k]->status_is_completed = (int)usam_check_object_is_completed( $item->status, $item->type );
								$items[$k]->status_name = isset($object_status['name'])?$object_status['name']:'';
								$items[$k]->status_color = isset($object_status['color'])?$object_status['color']:'';
								$items[$k]->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';
								$items[$k]->reminder_date = usam_get_event_reminder_date( $item->id );	
								$items[$k]->author = self::author_data( $items[$k]->user_id );
							}
						}					
				break;
				case 'review' :
					require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
					$data = usam_get_customer_reviews(['include' => $ids]);	
					$items = self::object_format_data( $items, $data, $type );					
				break;
				case 'order' :
					require_once( USAM_FILE_PATH .'/includes/document/orders_query.class.php'  );
					$data = usam_get_orders(['include' => $ids]);	
					$items = self::object_format_data( $items, $data, $type );					
				break;
				case 'lead' :
					require_once( USAM_FILE_PATH .'/includes/document/leads_query.class.php'  );
					$data = usam_get_leads(['include' => $ids]);	
					$items = self::object_format_data( $items, $data, $type );					
				break;
				case 'payment' :
					require_once( USAM_FILE_PATH .'/includes/document/payments_query.class.php'  );
					$data = usam_get_payments(['include' => $ids]);	
					$items = self::object_format_data( $items, $data, $type );					
				break;
				case 'shipped' :
					require_once( USAM_FILE_PATH .'/includes/document/shipped_documents_query.class.php'  );
					$data = usam_get_shipping_documents(['include' => $ids]);	
					$items = self::object_format_data( $items, $data, $type );					
				break;
				default:
					$details_documents = usam_get_details_documents();	
					if ( isset($details_documents[$type]) )
					{
						require_once( USAM_FILE_PATH .'/includes/document/documents_query.class.php'  );
						$data = usam_get_documents(['include' => $ids]);	
						$items = self::object_format_data( $items, $data, $type );
					}
				break;
			}
		}
		foreach ( $items as &$item )
		{					
			if ( isset($item->date_insert) )
				$item->date_insert = get_date_from_gmt($item->date_insert);
		}	
		return $items;
	}
	
	private static function format_event_email( $event ) 
	{	
		$event->author = self::author_data( $event->user_id );		
		if( !current_user_can('view_communication_data') )
		{
			$event->to_email = usam_get_hiding_data( $event->to_email, 'email' );
			$event->from_email = usam_get_hiding_data( $event->from_email, 'email' );
		}	
		else
			$event->copy_email = usam_get_email_metadata( $event->id, 'copy_email' );
		if( $event->type ==='sent_letter' )
		{
			$event->opened_at = usam_get_email_metadata( $event->id, 'opened_at' );
			if ( $event->opened_at )
				$event->opened_at = get_date_from_gmt($event->opened_at);
		}								
		$event->attachments = usam_get_email_attachments( $event->id );
		foreach( $event->attachments as &$file ) 
		{
			$file->size = size_format( $file->size );
			$file->icon = usam_get_file_icon( $file->id );					
		}
		
							
							$event->attacheds = [];
							$event->related = [];
							$event->objects = [];

							
							/*$email['attacheds'] = usam_get_emails(['include' => [ $id ], 'folder' => 'attached', 'object_query' => [['object_type' => 'email']]]);				
							$args = ['object_query' => [['object_id' => $id, 'object_type' => 'email']]];
							if ( $email['type'] == 'inbox_letter' )
								$args['folder_not_in'] = ['deleted'];
							else
								$args['sent_at'] = 'yes';
							$email['related'] = usam_get_emails( $args );
				
				if ( in_array('objects', $parameters['add_fields']) )
				{
					$email['objects'] = self::get_objects( $objects, $items );		
				}*/
				
		return $event;
	}
		
	private static function livefeed_format_data( $items, $data, $object_type )
	{	
		foreach( $items as $k => $result )
		{			
			if( $result->event_type == $object_type )
			{
				$ok = false;
				foreach ( $data as $i => $item )
				{
					if ( $result->event_id == $item->id )
					{		
						$ok = true;
						foreach ( $item as $key => $value )				
							$items[$k]->$key = $value;	
						$items[$k]->author = self::author_data( $items[$k]->user_id );
						unset($data[$i]);
						break;
					}
				}
				if ( !$ok )
					unset($items[$k]);
			}
		}
		return $items;
	}
	
	private static function object_format_data( $items, $data, $object_type )
	{	
		foreach( $items as $k => $result )
		{			
			if( $result->object_type == $object_type )
			{
				$ok = false;
				foreach( $data as $i => $item )
				{
					if( $result->object_id == $item->id || $object_type == 'product' && $result->object_id == $item->ID )
					{		
						$ok = true;
						foreach ( $item as $key => $value )				
							$items[$k]->$key = $value;	
						unset($data[$i]);
						break;
					}
				}
				if ( !$ok )
					unset($items[$k]);
			}
		}
		return array_values($items);
	}
	
	public static function get_reviews( WP_REST_Request $request ) 
	{					
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
					
		if ( !current_user_can('view_reviews') )		
			$query_vars['status'] = 2;	
			
		if( !empty($parameters['add_fields']) )
		{
			if( in_array('media', $parameters['add_fields']) )
				$query_vars['cache_attachments'] = true;
		}
		require_once( USAM_FILE_PATH . '/includes/feedback/customer_reviews_query.class.php' );
		$query = new USAM_Customer_Reviews_Query( $query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )			
		{
			foreach ( $items as &$item )
			{
				if ( in_array('media', $parameters['add_fields']) )
				{
					$attachments = usam_get_review_attachments( $item->id );	
					$item->images = [];
					$item->video = [];
					foreach( $attachments as $attachment )
					{
						$item->images[] = ['small' => get_bloginfo('url').'/show_file/'.$attachment->id.'?size=thumbnail', 'full' => get_bloginfo('url').'/show_file/'.$attachment->id, 'alt' => $attachment->title];
					} 
				}
				if ( in_array('author', $parameters['add_fields']) )
				{
					$item->author = '';
					foreach(['lastname', 'firstname', 'full_name', 'author', 'name'] as $key )
					{
						$value = usam_get_review_metadata( $item->id, 'webform_'.$key );
						if( !$value )
							$value = usam_get_review_metadata( $item->id, $key );		
						if( $value )
						{
							$item->author = $value;
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

	public static function get_review( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$review = usam_get_review( $id );
		
		$review['date_insert'] = usam_local_date(strtotime($review['date_insert']) );	
		
		$webform_code = usam_get_review_metadata( $id, 'webform');	
		$webform = usam_get_webform( $webform_code, 'code' );
		$groups = [];
		$properties = [];
		if ( !empty($webform) )
		{
			if ( isset($webform['settings']['fields']['review_title']) )
				unset($webform['settings']['fields']['review_title']);
			if ( isset($webform['settings']['fields']['review']) )
				unset($webform['settings']['fields']['review']);	
			$review_properties = usam_get_properties(['access' => true, 'type' => 'webform', 'active' => 1, 'code' => array_keys($webform['settings']['fields'])]);	
			$code_groups = [];	
			foreach ( $review_properties as $property )
			{			
				if ( $property->code == 'rating' )
					$property->value = $review['rating'];
				else
				{			
					if ( $property->field_type == 'checkbox' )
						$property->value = usam_get_array_metadata( $id, 'review', 'webform_'.$property->code );
					else
					{
						$property->value = usam_get_review_metadata( $id, 'webform_'.$property->code );
						if ( $property->value === false )
							$property->value = '';
					}
				}
				$properties[$property->code] = usam_format_property_api( $property );
				$code_groups[$property->group] = $property->group;				
			} 	
			$groups = usam_get_property_groups(['type' => 'webform', 'orderby' => 'sort', 'order' => 'ASC']);		
			foreach ( $groups as $k => $group )
			{
				if ( !in_array($group->code, $code_groups) ) 
				{
					if ( $group->parent_id == 0 )
					{
						foreach ( $groups as $k2 => $group2 )
						{
							if ( $group->id == $group2->parent_id && in_array($group2->code, $code_groups) )
								continue 2;
						}
					}
					unset($groups[$k]);
				}
			}		
		}
		return ['review' => $review, 'properties' => $properties, 'groups' => $groups];		
	}	
	
	public static function get_change_history( WP_REST_Request $request ) 
	{					
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
		if( current_user_can('edit_'.$query_vars['object_type']) || current_user_can('view_'.$query_vars['object_type']) )
		{			
			require_once( USAM_FILE_PATH .'/includes/change_history_description.php'  );
			require_once( USAM_FILE_PATH .'/includes/change_history_query.class.php'  );
			$query = new USAM_Change_History_Query( $query_vars );		
			$items = $query->get_results();	
			if ( !empty($items) )			
			{
				foreach ( $items as &$item )
				{
					$item->name_type = usam_change_history_event_type( $item );
					$item->name_description = usam_change_history_description( $item );
					$item->user = usam_get_contact( $item->user_id, 'user_id' );
					if( $item->user )
						$item->user['foto'] = usam_get_contact_foto( $item->user['id'] );
				}
				$count = $query->get_total();	
				return ['count' => $count, 'items' => $items];		
			}
		}
		return ['count' => 0, 'items' => []];
	}
	
	public static function get_calendars( WP_REST_Request $request, $parameters = null ) 
	{	
		require_once(USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php');	
		if ( $parameters === null )
			$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );		
		$items = usam_get_calendars( self::$query_vars );	
		$user_calendars = usam_get_user_select_calendars();
		foreach($items as &$item)
		{
			$item['checked'] = in_array($item['id'], $user_calendars);
		}
		$results = ['count' => count($items), 'items' => $items];
		return $results;
	}
	
	public static function save_user_calendar( WP_REST_Request $request ) 
	{					
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$calendar_ids = array_map('intval', $parameters['calendars']);		
		$user_id = get_current_user_id();
		return update_user_meta($user_id, 'usam_calendars', $calendar_ids );
	}
			
	public static function get_events( WP_REST_Request $request ) 
	{						
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );				
		$user_id = get_current_user_id();
		if ( isset(self::$query_vars['fields']) )
			unset(self::$query_vars['fields']);	
		
		$user_ids = usam_get_subordinates();
		$user_ids[] = $user_id;
		self::$query_vars['user_work'] = $user_ids;				
		if ( !empty(self::$query_vars['author']) )		
		{			
			if ( is_string(self::$query_vars['author']) && self::$query_vars['author'] == 'my' )
				self::$query_vars['author'] = [ $user_id ];		
			else
				self::$query_vars['author'] = array_map('intval', (array)self::$query_vars['author']);						
			self::$query_vars['author'] = array_intersect(self::$query_vars['author'], self::$query_vars['user_work']);
			if ( empty(self::$query_vars['author']) )
				self::$query_vars['author'] = 0; 
			unset(self::$query_vars['user_work']);
		}			
		if ( !empty(self::$query_vars['user_work']) )	
		{	
			$department_ids = [];
			foreach(self::$query_vars['user_work'] as $value)
			{
				$department_ids[] = str_replace("department-", "", $value);		
			}
			if ( $department_ids )
			{
				$ids = usam_get_contacts(['fields' => 'user_id', 'source' => 'employee', 'meta_query' => [['key' => 'department', 'value' => $department_ids, 'compare' => 'IN']], 'orderby' => 'name']);
				self::$query_vars['user_work'] = array_merge(self::$query_vars['user_work'], (array)$ids);
			}			
			self::$query_vars['user_work'] = array_intersect(self::$query_vars['user_work'], $user_ids);
		}
		if ( !empty(self::$query_vars['role']) )
		{
			if ( in_array('assignments', self::$query_vars['role']) )
			{
				self::$query_vars['users__not_in']['participant'] = $user_id;
				self::$query_vars['author'] = $user_id;
			}
			if ( in_array('my', self::$query_vars['role']) )
				self::$query_vars['user_work'] = $user_id;
			if ( in_array('commission', self::$query_vars['role']) )
				self::$query_vars['users']['participant'] = $user_id;
			unset(self::$query_vars['role']);
		}
		if ( isset(self::$query_vars['type']) )
		{
			self::$query_vars['type'] = array_map('sanitize_title', (array)self::$query_vars['type']);		
			foreach ( self::$query_vars['type'] as $k => $type )
			{				
				if ( !current_user_can('view_'.$type) )
					unset(self::$query_vars['type'][$k]);
			}
			if ( empty(self::$query_vars['type']) )
				return ['count' => 0, 'items' => []];
		}
		if ( !empty(self::$query_vars['reminder']) )
		{			
			$timestamp = current_time('timestamp', 1);
			self::$query_vars['meta_query'][] = ['key' => 'reminder_date_'.$user_id, 'value' => date("Y-m-d H:i:s"), 'compare' => '<='];
			if ( !isset($parameters['fields']) )
				$parameters['fields'] = [];
			$parameters['fields'][] = 'reminder';
		}	
		if ( !empty(self::$query_vars['webform']) )	
		{
			self::$query_vars['meta_query'][] = ['key' => 'webform', 'value' => self::$query_vars['webform'], 'compare' => '='];	
			unset(self::$query_vars['webform']);
		}
		$objects = array_keys(usam_get_details_documents());
		$objects[] = 'company';
		$objects[] = 'contact';
		foreach($objects as $object_type)
		{
			if ( !empty(self::$query_vars['_'.$object_type]) )	
			{
				$object_ids = array_map('intval', (array)self::$query_vars['_'.$object_type]);
				self::$query_vars['links_query'][] = ['object_type' => sanitize_title($object_type), 'object_id' => $object_ids, 'field' => 'slug'];	
			}
		}	
		if ( !empty($parameters['fields']) )	
		{				
			if ( in_array('users', $parameters['fields']) )
				self::$query_vars['cache_contacts'] = true;	
			if ( in_array('comments', $parameters['fields']) )
				require_once( USAM_FILE_PATH . '/includes/crm/comments_query.class.php' );
		}	
		if ( !empty($parameters['add_fields']) )	
		{				
			if ( is_string($parameters['add_fields']) && $parameters['add_fields'] == 'last_comment' || in_array('last_comment', $parameters['add_fields']) )
				self::$query_vars['cache_last_comment_contacts'] = true;
		}
		$query = new USAM_Events_Query( self::$query_vars );			
		$items = $query->get_results();	
		if ( !empty($items) )
		{			
			foreach ( $items as $k => &$item )
			{				
				if ( isset($item->date_insert) )
					$item->date_insert = get_date_from_gmt( $item->date_insert );	
				if ( isset($item->start) )
					$item->start = get_date_from_gmt( $item->start );
				if ( isset($item->end) )	
					$item->end = get_date_from_gmt( $item->end );	
				if ( isset($item->last_comment) )
				{					
					$item->last_comment_user_foto = usam_get_contact_foto( $item->last_comment_user, 'user_id' );
					$item->last_comment_user_name = usam_get_manager_name( $item->last_comment_user);
					$item->display_last_comment_date = usam_local_formatted_date( $item->last_comment_date );
					$item->last_comment = nl2br($item->last_comment);
				}								
				$item->url = usam_get_event_url( $item->id, $item->type );
				if ( isset($item->status) )
					$item->status_name = usam_get_object_status_name( $item->status, $item->type );
				if ( !empty($parameters['fields']) )
				{
					if ( in_array('author', $parameters['fields']) )
					{
						$item->author = usam_get_contact( $item->user_id, 'user_id');
						$item->author_image = usam_get_contact_foto( $item->user_id, 'user_id' );
						$item->author_url = usam_get_contact_url( $item->user_id, 'user_id' );	
					}	
					if ( in_array('object', $parameters['fields']) )
					{				
						$item->object = new stdClass();
						$display_object = usam_get_object((object)['object_type' => $item->type, 'object_id' => $item->id]);			
						if ( !empty($display_object['name']) )
							$item->object = (object)$display_object;
					}						
					$method = 'get_event_'.$item->type;					
					if ( method_exists('USAM_CRM_API', $method) )
					{
						$event = USAM_CRM_API::$method( (array)$item );
						foreach( $event as $k => $value )
							$item->$k = $value;	
					}
					if ( in_array('reminder', $parameters['fields']) )
						$item->reminder_date = usam_get_event_reminder_date( $item->id );
					if ( in_array('status_data', $parameters['fields']) && isset($item->status) )
					{
						$object_status = usam_get_object_status_by_code( $item->status, $item->type );
						$item->status_is_completed = (int)usam_check_object_is_completed( $item->status, $item->type );
						$item->status_name = isset($object_status['name'])?$object_status['name']:'';
						$item->status_color = isset($object_status['color'])?$object_status['color']:'';
						$item->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';										
					}
					if ( in_array('comments', $parameters['fields']) )
					{
						$item->comments = ['items' => []];
						$query = new USAM_Comments_Query(['object_id' => $item->id, 'object_type' => 'event', 'order' => 'DESC', 'number' => 10, 'status' => 0]);		
						$comments = $query->get_results();	
						$item->comments['count'] = $query->get_total();							
						foreach ( $comments as $comment ) 
						{						
							$comment->author_image = usam_get_contact_foto( $comment->user_id, 'user_id' );
							$comment->author = usam_get_contact( $comment->user_id, 'user_id');
							$comment->author_url = usam_get_contact_url( $comment->user_id, 'user_id' );	
							$item->comments['items'][] = $comment;
						}
						unset($comments);
					}				
					if ( in_array('users', $parameters['fields']) )
					{
						$item->users = new stdClass();
						$item->users->responsible = [];
						$item->users->participant = [];
						$users = usam_get_event_users( $item->id );						
						if ( !empty($users['responsible']) )
						{
							foreach( $users['responsible'] as $user_id )
								$item->users->responsible[] = usam_get_contact( $user_id, 'user_id');
						}
						if ( !empty($users['participant']) )
						{
							foreach( $users['participant'] as $user_id )
							{
								$item->users->participant[] = usam_get_contact( $user_id, 'user_id');
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
	
	public static function update_event( WP_REST_Request $request ) 
	{		
		$event_id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$event = usam_get_event( $event_id );
		if ( !$event )
			return false;		
		if( isset($parameters['type']) && $parameters['type'] != 'project' && $parameters['type'] != 'closed_project' )
			unset($parameters['type']);
		
		if( !usam_check_event_access( $event, 'edit' ) )
		{
			if( isset($parameters['description']) )
				unset($parameters['description']);
			if( isset($parameters['title']) )
				unset($parameters['title']);
		}
		usam_update_event( $event_id, $parameters );
		if ( !empty($parameters['reminder_date']) )		
		{	
			if ( is_numeric($parameters['reminder_date']) )
				$reminder_date = date("Y-m-d H:i:s",current_time('timestamp')+$parameters['reminder_date']*60);		
			else
				$reminder_date = date( "Y-m-d H:i:s", strtotime($parameters['reminder_date']) );	
			usam_update_event_reminder_date( $event_id, get_gmt_from_date($reminder_date) );			
		}
		elseif ( isset($parameters['reminder_date']) )
			usam_delete_event_reminder_date( $event_id );			
		if ( !empty($parameters['links']) )
		{							
			require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
			usam_set_ribbon(['event_id' => $event_id, 'event_type' => $event['type'], 'date_insert' => $event['date_insert']], $parameters['links']);				
		}
		self::save_event( $event_id, $event['type'], $parameters );		
		return USAM_CRM_API::get_event_data( $event_id );
	}
		
	public static function insert_event( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
							
		$links = !empty($parameters['links']) ? $parameters['links'] : [];	
		$event_id = usam_insert_event( $parameters, $links );
		if( $event_id )
		{			
			self::save_event( $event_id, $parameters['type'], $parameters );	
			return USAM_CRM_API::get_event_data( $event_id );
		}
		return false;
	}	
	
	private static function save_event( $event_id, $type, $parameters ) 
	{	
		if( isset($parameters['responsible']) || isset($parameters['participant']) || isset($parameters['observer']) )
			$manager_ids = usam_get_event_users( $event_id );
		foreach(["participant", "observer"] as $role )
		{
			$delete_manager_ids = array();	
			if( isset($parameters[$role]) )
			{ 
				if ( !empty($manager_ids[$role]) )
				{
					$delete_manager_ids = array_diff($manager_ids[$role], $parameters[$role]);
					$add_manager_ids = array_diff($parameters[$role], $manager_ids[$role]);		
				}
				else
					$add_manager_ids = $parameters[$role];		
				
				foreach ( $add_manager_ids as $user_id )
				{
					if ( usam_check_event_access( $event_id, 'add_'.$role ) )
						usam_set_event_user( $event_id, $user_id, $role ); 		
				}
			}
			elseif ( !empty($manager_ids[$role]) )
				$delete_manager_ids = $manager_ids[$role];
			foreach ( $delete_manager_ids as $user_id )
			{
				if ( usam_check_event_access( $event_id, 'delete_'.$role ) )
					usam_delete_event_user(['event_id' => $event_id, 'user_id' => $user_id, 'user_type' => $role]);
			}
		}		
		if( isset($parameters['responsible']) )
		{
			if( !empty($manager_ids['responsible']) && $manager_ids['responsible'][0] != $parameters['responsible'] && usam_check_event_access( $event_id, 'delete_responsible' ) )
				usam_delete_event_user(['event_id' => $event_id, 'user_type' => 'responsible']);
			if( empty($manager_ids['responsible']) || $manager_ids['responsible'][0] != $parameters['responsible'] && usam_check_event_access( $event_id, 'add_responsible' ) )
				usam_set_event_user( $event_id, $parameters['responsible'], 'responsible' );
		}		
		if( isset($parameters['request_solution']) )
			usam_update_event_metadata( $event_id, 'request_solution', $parameters['request_solution'] );
		if( isset($parameters['schedule']) )
		{
			$to_repeat = !empty($parameters['to_repeat'])?$parameters['to_repeat']:'';		
			if ( $parameters['schedule'] && $to_repeat )
			{
				$weekly_interval = !empty($parameters['weekly_interval'])?$parameters['weekly_interval']:[];
				$monthly_interval = !empty($parameters['monthly_interval'])?$parameters['monthly_interval']:'';
				
				usam_update_event_metadata($event_id, 'to_repeat', absint($to_repeat));
				if ( $parameters['schedule'] == 'monthly' )
				{
					usam_update_event_metadata( $event_id, 'monthly_interval', $monthly_interval);	
					usam_delete_event_metadata( $event_id, 'weekly_interval' );
				}
				elseif ( $parameters['schedule'] == 'weekly' )
				{
					usam_save_array_metadata( $event_id, 'event', 'weekly_interval', $weekly_interval );
					usam_delete_event_metadata( $event_id, 'monthly_interval' );
				}
			}	
			else
			{
				usam_delete_event_metadata( $event_id, 'to_repeat' );
				usam_delete_event_metadata( $event_id, 'monthly_interval' );		
				usam_delete_event_metadata( $event_id, 'weekly_interval' );					
			}
		}
		if( isset($parameters['groups']) )
			usam_set_groups_object( $event_id, $type, $parameters['groups'] );
		if( isset($parameters['files']) )
		{
			$file_ids = usam_get_files(['fields' => 'id', 'object_id' => $event_id, 'type' => $type]);
			$delete_ids = [];
			if( !empty($file_ids) )
			{
				$delete_ids = array_diff($file_ids, $parameters['files']);
				$add_ids = array_diff($parameters['files'], $file_ids);		
			}
			else
				$add_ids = $parameters['files'];
			foreach ( $add_ids as $id )
				usam_update_file( $id, ['object_id' => $event_id]);
			usam_delete_files(['include' => $delete_ids], true);
		}
		
		if( isset($parameters['budget']) )
			usam_update_event_metadata( $event_id, 'budget', $parameters['budget']);
		if( isset($parameters['venue']) )
			usam_update_event_metadata( $event_id, 'venue', $parameters['venue']);				
	}
		
	public static function delete_event( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		return usam_delete_event( $id );
	}	
	
	public static function add_event_action( WP_REST_Request $request ) 
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		if ( usam_check_event_access( $parameters['event_id'], 'add_action' ) )
			return usam_insert_event_action( $parameters );	
		else
			return false;
	}
	
	public static function save_event_action( WP_REST_Request $request ) 
	{
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		if ( usam_check_event_access( $parameters['event_id'], 'edit_action' ) )
			return usam_update_event_action( $id, $parameters );	
		else
			return false;		
	}
	
	public static function get_event_actions( WP_REST_Request $request ) 
	{
		$id = $request->get_param( 'id' );	
		return usam_get_event_action_lists(['event_id' => $id]);
	}
		
	public static function save_event_actions( WP_REST_Request $request ) 
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		foreach($parameters['items'] as $action) 
		{ 						
			if ( !empty($action['id']) && !empty($action['event_id']) )
			{
				$action_id = absint( $action['id'] );	
				if ( usam_check_event_access( $action['event_id'], 'edit_action' ) )
					usam_update_event_action( $action_id, $action );
			}
		}	
	}	
	
	public static function delete_link_event( WP_REST_Request $request ) 
	{				
		$id = $request->get_param( 'id' );
		$event = usam_get_event( $id );
		if ( $event && current_user_can('edit_'.$event['type']) )
		{
			$parameters = $request->get_json_params();		
			if ( !$parameters )
				$parameters = $request->get_body_params();	
			
			require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );
			require_once( USAM_FILE_PATH . '/includes/crm/ribbon_query.class.php' );	
									
			$ribbon = usam_get_ribbon_query(['event_id' => $event['id'], 'event_type' => $event['type'], 'number' => 1]);
			if ( $ribbon )
			{
				$parameters['ribbon_id'] = $ribbon['id'];
				if ( current_user_can('universam_api') )
					return usam_delete_ribbon_link( $parameters );
			}
		}
		return false;
	}
	
	public static function get_event( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = self::get_parameters( $request );		
		return USAM_CRM_API::get_event_data( $id, $parameters );
	}
	
	private static function get_event_data( $id, $parameters = [] )
	{				
		$event = usam_get_event( $id );		
		if( $event )
		{
			if ( !current_user_can('universam_api') && !current_user_can('view_'.$event['type']) )
			{
				$user_id = get_current_user_id();
				if ( $event['user_id'] != $user_id )
					return false;
			}
			$event['date_insert'] = get_date_from_gmt( $event['date_insert'] );
			$event['reminder_date'] = usam_get_event_reminder_date( $event['id'] );		
			if( $event['start'] )
				$event['start'] = get_date_from_gmt( $event['start'] );
			if( $event['end'] )
				$event['end'] = get_date_from_gmt( $event['end'] );			
			$object_status = usam_get_object_status_by_code( $event['status'], $event['type'] );
			$event['status_is_completed'] = isset($object_status['close'])?(int)$object_status['close']:0;
			$event['status_name'] = isset($object_status['name'])?$object_status['name']:'';
			$event['status_color'] = isset($object_status['color'])?$object_status['color']:'';
			$event['status_text_color'] = isset($object_status['text_color'])?$object_status['text_color']:'';	
			$event['author'] = usam_get_contact( $event['user_id'], 'user_id');
			$event['author_image'] = usam_get_contact_foto( $event['user_id'], 'user_id' );
			$event['author_url'] = usam_get_contact_url( $event['user_id'], 'user_id' );			
				
			$method = 'get_event_'.$event['type'];
			if ( method_exists('USAM_CRM_API', $method) )
				$event = USAM_CRM_API::$method( $event );	
			if( isset($parameters['add_fields']) )
			{	
				if( in_array('crm', $parameters['add_fields']) )
				{
					$event['crm'] = [];		
					require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
					$items = usam_get_ribbon_links( $event['id'], $event['type'] );	
					if ( !empty($items) )
					{
						$objects = [];
						foreach ($items as $k => $result )
							$objects[$result->object_type][] = $result->object_id;	
						$event['crm'] = self::get_objects( $objects, $items );	
					}
				}
				if( in_array('rights', $parameters['add_fields']) )
				{
					$rights = ['edit', 'edit_status', 'edit_action', 'add_action', 'add_participant', 'delete_participant', 'add_observer', 'delete_observer', 'comments'];
					$event['rights'] = [];
					foreach( $rights as $right )
						$event['rights'][$right] = usam_check_event_access($event, $right);
				}				
				if( in_array('author', $parameters['add_fields']) )
					$event['author'] = self::author_data( $event['user_id'] );
				if( in_array('users', $parameters['add_fields']) )
				{
					$event_users = usam_get_event_users( $event['id'] );				
					$this->data['responsible'] = !empty($event_users['responsible'])?$event_users['responsible'][0]:0;
					$this->users = !empty($event_users['participant']) ? $event_users['participant']:[];
					
					$event['users'] = [];
					$user_ids = usam_get_event_users( $event['id'], false );			
					if( $user_ids )
					{
						$contacts = usam_get_contacts(["user_id" => $user_ids, 'source' => 'all']);
						foreach( $event_users as $type => $user_ids )				
						{
							foreach( $contacts as $contact )
								if( in_array($contact->user_id, $user_ids) )
								{
									$contact->foto = usam_get_contact_foto( $contact->id );
									$event['users'][$type][] = $contact;							
								}
						}
					}
				}				
				if ( in_array('actions', $parameters['add_fields']) )
				{
					$event['actions'] = usam_get_event_action_lists(['event_id' => $id]);
				}
			}
			return $event;	
		}
		return false;	
	}
	
	public static function get_event_task( $event ) 
	{		
		$event['to_repeat'] = (int)usam_get_event_metadata( $event['id'], 'to_repeat');
		$weekly_interval = usam_get_array_metadata( $event['id'], 'event', 'weekly_interval');
		$event['weekly_interval'] = $weekly_interval?$weekly_interval:[];
		$event['monthly_interval'] = usam_get_event_metadata( $event['id'], 'monthly_interval');
		return $event;		
	}
	
	public static function get_event_meeting( $event ) 
	{		
		$event['venue'] = usam_get_event_metadata( $event['id'], 'venue');
		$event['request_solution'] = (string)usam_get_event_metadata( $event['id'], 'request_solution');	
		return $event;		
	}
	
	public static function get_event_call( $event ) 
	{		
		$event['venue'] = usam_get_event_metadata( $event['id'], 'venue');
		$event['request_solution'] = (string)usam_get_event_metadata( $event['id'], 'request_solution');	
		return $event;		
	}
	
	public static function get_event_project( $event ) 
	{		
		$event['request_solution'] = (string)usam_get_event_metadata( $event['id'], 'request_solution');	
		return $event;		
	}
	
	public static function get_event_closed_project( $event ) 
	{		
		$event['request_solution'] = (string)usam_get_event_metadata( $event['id'], 'request_solution');	
		return $event;		
	}
	
	public static function get_event_convocation( $event ) 
	{		
		$event['venue'] = usam_get_event_metadata( $event['id'], 'venue');
		$event['request_solution'] = (string)usam_get_event_metadata( $event['id'], 'request_solution');		
		return $event;		
	}	
		
	public static function send_webform( WP_REST_Request $request ) 
	{			
		$code = $request->get_param( 'webform_code' );		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		/*
		$option_work = get_option("usam_option_work","simple");		
		if ( $option_work == 'recast' )
		{				
			$params = $parameters;
			$params['usam_ajax_action'] = 'send_webform';
			return send_request_central_universam( $params );				
		}
		*/
		$result_message = false;			
		$language = usam_get_contact_language();
		$webform = usam_get_webforms(['code' => $code, 'language' => ['', $language], 'number' => 1, 'acting_now' => 1]);	
		if ( !$webform)
			return new WP_Error( 'no_webform', 'Invalid webform code', ['status' => 404]);			
			
		if ( !empty($webform['settings']['fields']) )
			$properties = usam_get_properties(['access' => true, 'type' => 'webform', 'fields' => 'code=>data', 'code' => array_keys($webform['settings']['fields'])]);	 
		foreach ( $parameters['data'] as $meta_key => $value ) 
		{		
			if ( isset($properties[$meta_key]) )
				$webform_meta[$meta_key] = usam_sanitize_property( $value, $properties[$meta_key]->field_type );	
		}				
		$webform_data = $webform_meta;
		if( !empty($webform_data['full_name']) )
		{
			$names = explode(' ', sanitize_text_field(stripcslashes($webform_data['full_name'])));
			$webform_data["lastname"] = trim($names[0]);
			$webform_data["firstname"] = isset($names[1]) ? trim($names[1]) : '';
			$webform_data["patronymic"] = isset($names[2]) ? trim($names[2]) : '';
		}		
		$page_id = empty($parameters['page_id'])?0:absint($parameters['page_id']);					
		$location = usam_get_customer_location( );	
		$contacting_id = 0;
		if ( $webform['action'] == 'order' )
		{							
			$type_payer = usam_get_type_payer_customer( );					
			$type_price = usam_get_customer_price_code();
			$args = ['type_price' => $type_price, 'type_payer' => $type_payer];
			$cart = new USAM_CART( $args );	
			
			$parameters['quantity'] = !empty($parameters['quantity'])?(int)$parameters['quantity']:1;		
			if ( $cart->add_product_basket( $page_id, $parameters ) )
			{						
				$cart->set_properties(['gateway' => '']);
				$cart->recalculate();
				$order_id = $cart->save_order();		
				if ( $order_id )
				{									
					$payers = usam_get_group_payers(['type' => 'contact']);
					usam_update_order($order_id, ['status' => 'received', 'shipping' => 0, 'source' => 'webform', 'source' => 'webform', 'type_payer' => $payers[0]['id']]);
					$customer_data = usam_get_webform_data_from_CRM( $webform_data, 'order', $payers[0]['id'] );
					usam_insert_payment_document(['document_id' => $order_id], ['document_id' => $order_id, 'document_type' => 'order']);
					$order_properties =  usam_get_properties(['access' => true, 'type' => 'order', 'fields' => 'code=>data', 'type_payer' => $payers[0]['id']]);	
					foreach ( $order_properties as $property ) 
					{
						if ($property->field_type == 'location')
							$customer_data[$property->code] = $location;
						elseif ( !empty($webform_data['message']) && $property->field_type == 'textarea' )
							$customer_data[$property->code] = $webform_data['message'];	
					}								
					usam_add_order_customerdata( $order_id, $customer_data );
					
					$result_message = sprintf(__('Заказ №%s создан! Когда специалист обработает Ваш заказ, он свяжется с вами...','usam'), $order_id);	
					usam_add_notification( array('title' => sprintf(__('Получен новый заказ №%s','usam'), $order_id)), array('object_type' => 'order', 'object_id' => $order_id)  );
				}
				else
					return __('Ошибка создания заказа.','usam');
			}
			else
			{
				$cart_messages = $cart->get_errors_message( );	
				$result_message = '';
				foreach ( (array)$cart_messages as $cart_message ) 
				{
					$result_message = "<div class='action_result_notification__message'>" . $cart_message . "</div>";
				}	
				return $result_message;					
			}		
		}
		else
		{	
			$metas = usam_get_CRM_data_from_webform( $webform_data, 'webform' );	
			$contact = $metas['contact'];											
			if ( !empty($webform_data['company']) )
			{
				$metas['company']['contactlocation'] = $location;
				$metas['company']['legallocation'] = $location;					
				$company_id = usam_insert_company( ['name' => $webform_data['company']], $metas['company'] );							
			}
			else
				$company_id = 0;
			if ( $company_id )
				$contact['company_id'] = $company_id;		
			$contact_id = usam_save_or_create_contact( $contact );	
			if ( $webform['action'] == 'review' )
			{		
				if ( !empty($webform_meta['review_title']) )
				{
					$insert['title'] = sanitize_text_field(stripslashes($webform_meta['review_title']));	
					unset($webform_meta['review_title']);
				}
				if ( !empty($webform_meta['review']) )
				{
					$insert['review_text'] = sanitize_textarea_field(stripslashes($webform_meta['review']));	
					unset($webform_meta['review']);
				}			
				$insert['page_id'] = $page_id;
				if ( !empty($webform_meta['rating']) )
				{
					$insert['rating'] = absint($webform_meta['rating']);	
					unset($webform_meta['rating']);
				}	
				$insert['contact_id'] = $contact_id;
				$review_id = usam_insert_review( $insert );  	
				if ( $review_id )
				{						
					usam_add_notification(['title' => __('Новый отзыв','usam')], ['object_type' => 'review', 'object_id' => $review_id]); 
					if ( !empty($webform_meta) )
					{					
						foreach( $webform_meta as $meta_key => $value )
						{
							usam_update_review_metadata( $review_id, 'webform_'.$meta_key, $value );							
							usam_save_file_property( $value, $properties[$meta_key]->field_type, ['type' => 'review', 'object_id' => $review_id]);
						}
					}
					$result_message = __('Отзыв отправлен','usam');
				}
				if ( !empty($webform) )
					usam_update_review_metadata( $review_id, 'webform', $webform['code'] );
				if ( !empty($parameters['object']) )
				{
					if ( !empty($parameters['object']['order']) )
					{
						$order_id = absint($parameters['object']['order']);						
						usam_update_review_metadata( $review_id, 'order_id', $order_id );
						usam_update_order_metadata( $order_id, 'review_id', $review_id );	 
					}
				}								
			}
			else
			{												
				require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
				$links = [];				
				if ( $contact_id )
					$links[] = ['object_id' => $contact_id, 'object_type' => 'contact'];	
				if ( $company_id )		
					$links[] = ['object_id' => $company_id, 'object_type' => 'company'];		
				$contacting_id = usam_insert_contacting(['post_id' => $page_id], $links);					
				if ( empty($contacting_id) )
					return __('Сообщение не отправлено! Попробуйте еще раз.','usam');	
						
				if ( !empty($webform_data['topic']) )
				{	// распределение по сотрудникам			
	
				}				
				if ( !empty($webform) )
					usam_update_contacting_metadata( $contacting_id, 'webform', $webform['code'] );				
				if ( !empty($webform_meta) )
				{
					foreach( $webform_meta as $meta_key => $value )
					{
						usam_update_contacting_metadata( $contacting_id, 'webform_'.$meta_key, $value );							
						usam_save_file_property( $value, $properties[$meta_key]->field_type, ['type' => 'webform', 'object_id' => $contacting_id]);
					}
				}
				$visit_id = usam_get_contact_visit_id();
				usam_update_contacting_metadata( $contacting_id, 'visit_id', $visit_id );
				$campaign_id = usam_get_visit_metadata($visit_id, 'campaign_id');
				if ( $campaign_id )
					usam_update_contacting_metadata( $contacting_id, 'campaign_id', $campaign_id );
				$user_ids = usam_get_contacts(['fields' => 'user_id', 'source' => 'employee', 'capability' => 'view_contacting']);		
				usam_add_notification(['title' => sprintf( __('Получено новое обращение из веб-формы &laquo;%s&raquo;','usam'), $webform['title'])], ['object_type' => 'contacting', 'object_id' => $contacting_id], $user_ids );				
				$result_message = __('Сообщение отправлено! Ожидайте обработку вашего обращения...','usam');					
				
				do_action( 'usam_new_request_customer', $contacting_id, $webform, $webform_data, $properties );				
			}
			usam_delete_contact_metadata( $contact_id, 'draft_webform_'.$webform['id'] );
		}			
		if( !empty($webform['settings']['result_message']) )
		{
			$args = [
				'contacting_id' => $contacting_id,
				'shop_name' => get_bloginfo('name'),
			];
			$shortcode = new USAM_Shortcode();		
			$result_message = $shortcode->process_args( $args, $webform['settings']['result_message'] );
		}	
		if ( !empty($order_id) )
		{
			$order_shortcode = new USAM_Order_Shortcode( $order_id );				
			$result_message = $order_shortcode->get_html( $result_message );
		}				
		do_action( 'usam_receiving_request_webform', $webform, $contacting_id, $webform_data, $properties );
		return apply_filters( 'usam_message_webform_submit_result', $result_message, $webform, $contacting_id, $webform_data, $properties );				
	}	

	public static function get_webform( WP_REST_Request $request ) 
	{
		$code = $request->get_param( 'webform_code' );		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_query_params();
		$language = usam_get_contact_language();
		$webform = usam_get_webforms(['code' => $code, 'language' => ['', $language], 'number' => 1, 'acting_now' => 1]);		
		if( $webform )
		{
			$properties = [];	
			$step_groups = [];			
			$webform['button_name'] = $webform['settings']['button_name'];
			$webform['result_message'] = $webform['settings']['result_message'];
			if( !empty($webform['settings']['fields']) )
			{
				$webform_properties = usam_get_properties(['access' => true, 'type' => 'webform', 'active' => 1, 'code' => array_keys($webform['settings']['fields']), 'cache_meta' => true]);		
				$contact_id = usam_get_contact_id();
				$draft_webform = usam_get_contact_metadata( $contact_id, 'draft_webform_'.$webform['id'] );					
				foreach( $webform['settings']['fields'] as $code => $field )
				{
					foreach( $webform_properties as $key => $property )
					{
						if ( $property->code == $code )
						{
							$property->value = !empty($draft_webform[$property->code])?$draft_webform[$property->code]:usam_get_property_value( $property );
							$property->mandatory = (int)$field['require'];
							$property = usam_format_property_api( $property );
							$step_groups[] = $property->group;	
							$properties[$code] = $property;							
							unset($webform_properties[$key]);
							break;
						}
					}
				}
			} 
			$post_id = isset($parameters['page_id'])?absint($parameters['page_id']):0;		
			ob_start();
			include usam_get_template_file_path( 'webform', 'modaltemplate' );
			$modal = ob_get_clean();	
			
			$args = ['shop_name' => get_bloginfo('name')];				
			if ( $post_id ) 
			{			
				$args['product_price'] = usam_get_product_price_currency( $post_id );
				$args['product_name'] =  get_the_title( $post_id );		
			}
			$shortcode = new USAM_Shortcode();
			$webform['description'] = $shortcode->process_args( $args, $webform['settings']['description'] );	
						
			$template = usam_get_webform_template( $webform, $post_id, true, !$request->get_header('X-WP-Admin') );			
			$file = usam_get_template_file_path($webform['template'], 'webforms');
			if ( $file )
				$data = get_file_data( $file, ['name' => 'Описание', 'group' => 'load_group']);	
			$groups = [];
			if ( isset($data['group']) && $data['group'] == 'yes' && $step_groups )		
			{				
				$groups = usam_get_property_groups(['code' => $step_groups, 'orderby' => 'sort', 'order' => 'ASC']);
				$step_groups = [];
				foreach( $groups as $group )
				{
					$step_groups[] = $group->id;
					if ( $group->parent_id )
						$step_groups[] = $group->parent_id;
				} 
				$groups = usam_get_property_groups(['include' => $step_groups, 'type' => 'webform', 'orderby' => 'sort', 'order' => 'ASC']);	 
			}	 
			return ['webform' => $webform, 'properties' => $properties, 'groups' => $groups, 'template' => $template, 'modal' => $modal];
		}	
	}
	
	public static function save_webform( WP_REST_Request $request ) 
	{		
		$code = $request->get_param( 'webform_code' );		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$language = usam_get_contact_language();
		$webform = usam_get_webforms(['code' => $code, 'language' => ['', $language], 'number' => 1, 'acting_now' => 1]);
		if ( $webform )
		{			
			$contact_id = usam_get_contact_id();		
			$properties = usam_get_properties(['access' => true, 'type' => 'webform', 'active' => 1, 'profile' => 1, 'code' => array_keys($webform['settings']['fields']), 'fields' => 'code=>data']);	
			$metas = usam_get_contact_metadata( $contact_id, 'draft_webform_'.$webform['id'] );
			$metas = is_array($metas)?$metas:[];		
			foreach( $properties as $property )
			{
				if ( isset($parameters['data'][$property->code]) )
				{
					$metas[$property->code] = usam_sanitize_property( $parameters['data'][$property->code], $property->field_type );		
					usam_save_file_property( $metas[$property->code], $property->field_type, ['type' => 'webform']);
				}
			}
			usam_update_contact_metadata( $contact_id, 'draft_webform_'.$webform['id'], $metas );
		}
		else
			return new WP_Error( 'no_webform', 'Invalid webform code', ['status' => 404]);	
	}	
	
	public static function get_webforms( WP_REST_Request $request, $parameters = null ) 
	{	
		require_once(USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php');	
		$interface_filters = true;
		if ( $parameters === null )
		{
			$interface_filters = false;
			$parameters = self::get_parameters( $request );	
		}
		$args = [];
		if ( isset($parameters['active']) )
			$args['active'] = absint($parameters['active']);
		else
			$args['active'] = 1;
		if ( !empty($parameters['acting_now']) )
			$args['acting_now'] = 1;
		if ( !empty($parameters['conditions']) )
			$args['conditions'] = $parameters['conditions'];
				
		$items = usam_get_webforms( $args );		
		$results = [];
		foreach ( $items as $item )
		{
			if ( $interface_filters )
				$results[] = ['id' => $item->code, 'name' => $item->title, 'code' => $item->code];
			else
				$results[] = $item;
		}
		return $results;
	}
	
	public static function get_bonus_cards( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
				
		if ( !empty(self::$query_vars['search']) && empty(self::$query_vars['search_columns']) )
			self::$query_vars['search_columns'] = ['email', 'phone', 'customer_name'];
					
		require_once( USAM_FILE_PATH . '/includes/customer/bonus_cards_query.class.php'); 
		$query = new USAM_Bonus_Cards_Query( self::$query_vars );		
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
	
	public static function get_bonus_transactions( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );		
					
		require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php');
		$query = new USAM_Bonuses_Query( self::$query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			$bonus_rules = get_option('usam_bonus_rules', ['activation_date' => 14]);
			foreach( $items as $k => $item )
			{				
				$items[$k]->days_before_activation = human_time_diff( strtotime($item->date_insert), strtotime('-'.$bonus_rules['activation_date'].' days'));
				$items[$k]->user = usam_get_contact( $item->user_id, 'user_id' );
				if( $item->user )
					$items[$k]->user['foto'] = usam_get_contact_foto( $item->user['id'] );				
			} 
			if( isset($parameters['add_fields']) )
			{				
				if ( in_array('objects', $parameters['add_fields']) )
				{
					$objects = [];
					$object_items = [];
					foreach( $items as $result )
					{
						if( $result->object_type && $result->object_id )
						{
							$objects[$result->object_type][] = $result->object_id;	
							$o = new stdClass();
							$o->object_id = $result->object_id;
							$o->object_type = $result->object_type;						
							$object_items[] = $o;
						}
					}
					$objects = self::get_objects( $objects, $object_items );
					foreach( $objects as $k => $result )
					{
						foreach( $items as $k => $item )
						{
							if( $result->object_type == $item->object_type && $result->object_id == $item->object_id )
								$items[$k]->object = $result;
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
	
	public static function delete_bonus( WP_REST_Request $request )
	{		
		$number = $request->get_param( 'number' );
		if ( $number )
			return usam_delete_bonuses_transaction(['include' => [$number]]);
		else
			return false;
	}
	
	public static function get_bonus_card( WP_REST_Request $request )
	{		
		$number = $request->get_param( 'number' );
		return usam_get_bonus_card( $number );	
	}	
		
	public static function insert_bonus( WP_REST_Request $request ) 
	{		
		$number = sanitize_title($request->get_param( 'number' ));
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$type_transaction = !empty($parameters['type_transaction'])?1:0;
		$description = sanitize_textarea_field(stripslashes($parameters['description']));
		$sum = absint($parameters['bonus']);
		$order_id = !empty($parameters['order_id'])?$parameters['order_id']:0;
		return usam_insert_bonus(['object_id' => $order_id, 'object_type' => 'order', 'sum' => $sum, 'description' => $description, 'code' => $number, 'type_transaction' => $type_transaction]);	
	}
	
	public static function insert_bonus_by_user_id( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$user_id = !empty($parameters['user_id'])?1:0;				
		if( !empty($parameters['order_id']) )
		{
			$parameters['object_id'] = $parameters['order_id'];
			$parameters['object_type'] = 'order';
			unset($parameters['order_id']);
		}
		return usam_insert_bonus($parameters, $user_id);	
	}
	
	public static function get_account_transactions( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );		
					
		require_once( USAM_FILE_PATH . '/includes/customer/account_transactions_query.class.php');
		$query = new USAM_Account_Transactions_Query( self::$query_vars );					
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			$bonus_rules = get_option('usam_account_rules', ['activation_date' => 0]);
			foreach( $items as &$item )
			{				
				$item->days_before_activation = human_time_diff( strtotime($item->date_insert), strtotime('-'.$bonus_rules['activation_date'].' days'));
				$item->user = usam_get_contact( $item->user_id, 'user_id' );
				if( $item->user )
					$item->user['foto'] = usam_get_contact_foto( $item->user['id'] );
			} 
			$count = $query->get_total();			
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}	

	public static function insert_account_transaction( WP_REST_Request $request ) 
	{		
		require_once( USAM_FILE_PATH . '/includes/customer/account_transaction.class.php' );
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$parameters['type_transaction'] = !empty($parameters['type_transaction'])?1:0;
		$parameters['account_id'] = $request->get_param( 'id' );
		return usam_insert_account_transaction( $parameters );	
	}	
	
		
	public static function delete_account_transaction( WP_REST_Request $request )
	{		
		require_once( USAM_FILE_PATH . '/includes/customer/account_transaction.class.php' );
		$id = $request->get_param( 'id' );	
		if ( $id )
			return usam_delete_account_transaction( $id );
		else
			return false;
	}
	
	public static function get_account( WP_REST_Request $request )
	{		
		require_once( USAM_FILE_PATH . '/includes/customer/customer_account.class.php' );
		$id = $request->get_param( 'id' );	
		return usam_get_customer_account( $id );	
	}
		
	public static function get_contacting( WP_REST_Request $request ) 
	{				
		$id = $request->get_param( 'id' );			
		$parameters = self::get_parameters( $request );			
		require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
		$event = usam_get_contacting( $id );		
		if( $event )
		{
			if ( !current_user_can('universam_api') && !current_user_can('view_contacting') )
			{
				$contact_id = usam_get_contact_id();
				if ( $event['contact_id'] != $contact_id )
					return false;
			}
			$event['date_insert'] = get_date_from_gmt( $event['date_insert'] );		
			$object_status = usam_get_object_status_by_code( $event['status'], 'contacting' );
			$event['status_is_completed'] = isset($object_status['close'])?(int)$object_status['close']:0;
			$event['status_name'] = isset($object_status['name'])?$object_status['name']:'';
			$event['status_color'] = isset($object_status['color'])?$object_status['color']:'';
			$event['status_text_color'] = isset($object_status['text_color'])?$object_status['text_color']:'';						
			if( isset($parameters['add_fields']) )
			{	
				if( in_array('crm', $parameters['add_fields']) )
				{
					$event['crm'] = [];		
					require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
					$items = usam_get_ribbon_links( $event['id'], 'contacting' );	
					if ( !empty($items) )
					{
						$objects = [];
						foreach ($items as $k => $result )
							$objects[$result->object_type][] = $result->object_id;	
						$event['crm'] = self::get_objects( $objects, $items );	
					}
				}	
				if( in_array('webform', $parameters['add_fields']) )
				{
					$event['webform_code'] = usam_get_contacting_metadata( $event['id'], 'webform');
					$webform = usam_get_webform( $event['webform_code'], 'code' );			
					$groups = [];
					$event['properties'] = [];
					if ( !empty($webform) )
					{									
						$event_properties = usam_get_properties(['access' => true, 'type' => 'webform', 'active' => 1, 'code' => array_keys($webform['settings']['fields'])]);	
						$code_groups = [];	
						foreach ( $event_properties as $property )
						{
							if ( $property->field_type == 'checkbox' )
								$property->value = usam_get_array_metadata( $event['id'], 'contacting', 'webform_'.$property->code );
							else
							{
								$property->value = usam_get_contacting_metadata( $event['id'], 'webform_'.$property->code );
								if ( $property->value === false )
									$property->value = '';
							}
							$property = usam_format_property_api( $property );
							$event['properties'][$property->code] = $property;
							$code_groups[$property->group] = $property->group;				
						} 	
						$groups = usam_get_property_groups(['type' => 'webform', 'orderby' => 'sort', 'order' => 'ASC']);		
						foreach( $groups as $k => $group )
						{
							if ( !in_array($group->code, $code_groups) ) 
							{
								if ( $group->parent_id == 0 )
								{
									foreach ( $groups as $k2 => $group2 )
									{
										if ( $group->id == $group2->parent_id && in_array($group2->code, $code_groups) )
											continue 2;
									}
								}
								unset($groups[$k]);
							}
						}
					}
					$event['groups'] = array_values($groups);
				}		
				if( in_array('request_solution', $parameters['add_fields']) )
				{	
					$event['request_solution'] = (string)usam_get_contacting_metadata( $event['id'], 'request_solution');
				}
				if( in_array('manager', $parameters['add_fields']) )
				{	
					$event['manager'] = usam_get_contact( $event['manager_id'], 'user_id' );
					if( !empty($event['manager']['id']) )
						$event['manager']['foto'] = usam_get_contact_foto( $event['manager_id'] );
				}
				if( in_array('contact', $parameters['add_fields']) )
				{	
					$event['contact'] = usam_get_contact( $event['contact_id'] );
					if( !empty($event['contact']['id']) )
						$event['contact']['foto'] = usam_get_contact_foto( $event['contact_id'] );
				}	
				if( in_array('post', $parameters['add_fields']) )
				{	
					$event['post'] = get_post( $event['post_id'] );					
					if( !empty($event['post']) )
					{
						$event['post']->url = get_edit_post_link( $event['post_id'] );
						if( $event['post']->post_type === 'usam-product' )
						{
							$event['post']->sku = usam_get_product_meta( $event['post']->ID, 'sku' );
							$event['post']->thumbnail = usam_get_product_thumbnail( $event['post']->ID, 'manage-products' );
						}
					}
				}
				if( in_array('analytics', $parameters['add_fields']) )
				{	
					if( current_user_can('view_contacting') )
					{
						$visit_id = usam_get_contacting_metadata( $event['id'], 'visit_id' );	
						$event['visit'] = usam_get_visit( $visit_id );		
						if ( $event['visit'] )
						{
							$event['visit']['source_name'] = usam_get_name_source_visit( $event['visit']['source'] );
							$event['visit']['referer'] = usam_get_visit_metadata($visit_id, 'referer');
							$event['visit']['device'] = usam_get_visit_metadata($visit_id, 'device');
						}
						$campaign_id = usam_get_contacting_metadata( $event['id'], 'campaign_id' );			
						$event['campaign'] = usam_get_advertising_campaign( $campaign_id );		
					}
				}
			}
			return $event;	
		}
		return false;	
	}
	
	public static function insert_contacting( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
							
		require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
		$links = !empty($parameters['links']) ? $parameters['links'] : [];	
		$id = usam_insert_contacting( $parameters, $links );
		if( $id )
		{			
			self::save_contacting( $id, $parameters );	
			return $id;
		}
		return false;
	}	
	
	public static function update_contacting( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
		$event = usam_get_contacting( $id );
		if ( !$event )
			return false;		
		
		$contact = usam_get_contact();
		if( $event['manager_id'] )
		{
			if( $event['manager_id'] !== $contact['user_id'] )
			{			
				foreach( $parameters as $k => $v ) 
				{
					if( $k !== 'manager_id' )
						unset($parameters[$k]);
				}
			}
		}
		else
			$parameters['manager_id'] = get_current_user_id();
		usam_update_contacting( $id, $parameters );				
		if ( !empty($parameters['links']) )
		{							
			require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
			usam_set_ribbon(['event_id' => $id, 'event_type' => $event['type'], 'date_insert' => $event['date_insert']], $parameters['links']);				
		}
		self::save_contacting( $id, $parameters );	
		return true;
	}	
	
	private static function save_contacting( $id, $parameters ) 
	{			
		if( isset($parameters['request_solution']) )
			usam_update_contacting_metadata( $id, 'request_solution', $parameters['request_solution'] );		
		if( isset($parameters['groups']) )
			usam_set_groups_object( $id, 'contacting', $parameters['groups'] );		
		if( isset($parameters['webform']) )					
		{
			$properties = usam_get_properties(['access' => true, 'type' => 'webform', 'fields' => 'code=>data']);						
			$new_fields = [];
			$files_ids = [];
			foreach( $properties as $meta_key => $property ) 
			{		
				if ( isset($parameters['webform'][$meta_key]) )
					$meta_value = usam_sanitize_property( $parameters['webform'][$meta_key], $property->field_type );
				else
					continue;
			
				$update = usam_update_contacting_metadata( $id, 'webform_'.$meta_key, $meta_value );	
				$ids = usam_save_file_property( $parameters['webform'][$meta_key], $property->field_type, ['type' => 'webform', 'object_id' => $id]);
				$files_ids = array_merge( $files_ids, $ids );					
			}
			usam_delete_files(['exclude' => $files_ids, 'object_id' => $id, 'type' => 'webform']);
		}
	}
	
	public static function add_order_contacting( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
		$event = usam_get_contacting( $id );
		if( !empty($event['post_id']) )
		{
			$post = get_post($event['post_id']);
			if( $post->post_type !== 'usam-product' )
				return false;
			
			$anonymous_function = function($a) { return false; };	
			add_filter( 'usam_prevent_notification_change_status', $anonymous_function);
			$payers = usam_get_group_payers(['type' => 'contact']);							
			$contact = usam_get_contact( $event['contact_id'] );
			$args['location_id'] = usam_get_contact_metadata( $contact['id'], 'location' );
			$args['type_price'] = usam_get_customer_price_code( );	
			$args['type_payer'] = isset($payers[0])?$payers[0]['id']:0;		
							
			$cart = new USAM_CART();
			$cart->set_properties( $args );						
			$cart->add_product_basket( $post->ID, ['any_balance' => true, 'quantity' => 1]);			
			$cart->recalculate();			
			if ( count($cart->get_products()) )
			{
				$order_id = $cart->save_order(['status' => 'received', 'manager_id' => get_current_user_id(), 'user_ID' => $contact['user_id'], 'contact_id' => $contact['id']]);						
				if ( !$order_id )
					return false;
								
				$metas = usam_get_contact_metas( $contact['id'] );		
				$customer_data = usam_get_webform_data_from_CRM( $metas, 'order', $payers[0]['id'] );					
				usam_add_order_customerdata( $order_id, $customer_data );				
				$order = usam_get_order( $order_id );				
				$payment['sum'] = $order['totalprice'];		
				$payment['document_id'] = $order_id;
				$payment_id = usam_insert_payment_document( $payment, ['document_id' => $order_id, 'document_type' => 'order']);
				do_action('usam_document_order_save', $order_id);
				return ['id' => $order_id, 'redirection' => usam_get_url_order( $order_id )]; 
			}
		}
		return false;		
	}
	
	public static function get_contactings( WP_REST_Request $request ) 
	{						
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );				
		$contact_id = usam_get_contact_id();		
		if ( !empty(self::$query_vars['author']) )		
		{			
			if ( is_string(self::$query_vars['author']) && self::$query_vars['author'] == 'my' )
				self::$query_vars['contacts'] = [ $contact_id ];		
			else
				self::$query_vars['contacts'] = array_map('intval', (array)self::$query_vars['author']);				
		}
		if( !current_user_can('view_contacting')	)
			self::$query_vars['contacts'] = [ $contact_id ];
		if ( !empty(self::$query_vars['webform']) )	
		{
			self::$query_vars['meta_query'][] = ['key' => 'webform', 'value' => self::$query_vars['webform'], 'compare' => '='];	
			unset(self::$query_vars['webform']);
		}
		$objects = array_keys(usam_get_details_documents());
		$objects[] = 'company';
		$objects[] = 'contact';
		foreach($objects as $object_type)
		{
			if ( !empty(self::$query_vars['_'.$object_type]) )	
			{
				$object_ids = array_map('intval', (array)self::$query_vars['_'.$object_type]);
				self::$query_vars['links_query'][] = ['object_type' => sanitize_title($object_type), 'object_id' => $object_ids, 'field' => 'slug'];	
			}
		}
		if ( !empty($parameters['add_fields']) )	
		{				
			if ( is_string($parameters['add_fields']) && $parameters['add_fields'] == 'last_comment' || in_array('last_comment', $parameters['add_fields']) )
				self::$query_vars['cache_last_comment_contacts'] = true;
			if ( in_array('comments', $parameters['add_fields']) )
				require_once( USAM_FILE_PATH . '/includes/crm/comments_query.class.php' );
		}
		require_once(USAM_FILE_PATH.'/includes/crm/contactings_query.class.php');
		require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
		$query = new USAM_Contactings_Query( self::$query_vars );			
		$items = $query->get_results();	
		if ( !empty($items) )
		{			
			foreach ( $items as $k => &$item )
			{				
				if ( isset($item->date_insert) )
					$item->date_insert = get_date_from_gmt( $item->date_insert );						
				if ( isset($item->last_comment) )
				{					
					$item->last_comment_user_foto = usam_get_contact_foto( $item->last_comment_user, 'user_id' );
					$item->last_comment_user_name = usam_get_manager_name( $item->last_comment_user);
					$item->display_last_comment_date = usam_local_formatted_date( $item->last_comment_date );
					$item->last_comment = nl2br($item->last_comment);
				}
				if ( !empty($parameters['add_fields']) )
				{
					if( in_array('request_solution', $parameters['add_fields']) )
					{
						$item->request_solution = (string)usam_get_contacting_metadata( $item->id, 'request_solution');
					}				
					if( in_array('author', $parameters['add_fields']) )
					{
						$item->author = usam_get_contact( $item->contact_id );
						$item->author_image = usam_get_contact_foto( $item->contact_id );
						$item->author_url = usam_get_contact_url( $item->contact_id );	
					}							
					if( in_array('status_data', $parameters['add_fields']) && isset($item->status) )
					{
						$object_status = usam_get_object_status_by_code( $item->status, 'contacting' );
						$item->status_is_completed = (int)usam_check_object_is_completed( $item->status, 'contacting' );
						$item->status_name = isset($object_status['name'])?$object_status['name']:'';
						$item->status_color = isset($object_status['color'])?$object_status['color']:'';
						$item->status_text_color = isset($object_status['text_color'])?$object_status['text_color']:'';										
					}
					if( in_array('webform', $parameters['add_fields']) )
					{
						$item->webform_code = usam_get_contacting_metadata( $item->id, 'webform');
						$webform = usam_get_webform( $item->webform_code, 'code' );			
						$groups = [];
						$item->properties = [];
						if ( !empty($webform) )
						{									
							$event_properties = usam_get_properties(['access' => true, 'type' => 'webform', 'active' => 1, 'code' => array_keys($webform['settings']['fields'])]);	
							$code_groups = [];	
							foreach ( $event_properties as $property )
							{
								if ( $property->field_type == 'checkbox' )
									$property->value = usam_get_array_metadata( $item->id, 'contacting', 'webform_'.$property->code );
								else
								{
									$property->value = usam_get_contacting_metadata( $item->id, 'webform_'.$property->code );
									if ( $property->value === false )
										$property->value = '';
								}
								$property = usam_format_property_api( $property );
								$item->properties[$property->code] = $property;
								$code_groups[$property->group] = $property->group;				
							} 	
							$groups = usam_get_property_groups(['type' => 'webform', 'orderby' => 'sort', 'order' => 'ASC']);		
							foreach ( $groups as $k => $group )
							{
								if ( !in_array($group->code, $code_groups) ) 
								{
									if ( $group->parent_id == 0 )
									{
										foreach ( $groups as $k2 => $group2 )
										{
											if ( $group->id == $group2->parent_id && in_array($group2->code, $code_groups) )
												continue 2;
										}
									}
									unset($groups[$k]);
								}
							}
						}
						$item->groups = $groups;		
					}					
					if( in_array('comments', $parameters['add_fields']) )
					{
						$item->comments = ['items' => []];
						$query = new USAM_Comments_Query(['object_id' => $item->id, 'object_type' => 'event', 'order' => 'DESC', 'number' => 10, 'status' => 0]);		
						$comments = $query->get_results();	
						$item->comments['count'] = $query->get_total();							
						foreach ( $comments as $comment ) 
						{						
							$comment->author_image = usam_get_contact_foto( $comment->user_id, 'user_id' );
							$comment->author = usam_get_contact( $comment->user_id, 'user_id');
							$comment->author_url = usam_get_contact_url( $comment->user_id, 'user_id' );	
							$item->comments['items'][] = $comment;
						}
						unset($comments);
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
	
	public static function get_email( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = self::get_parameters( $request );	
		$email = usam_get_email( $id );
		if( !$email )
		{	
			$email['date_insert'] = get_date_from_gmt( $email['date_insert'] );
			if( isset($parameters['add_fields']) )
			{
				if ( in_array('attacheds', $parameters['add_fields']) )
					$email['attacheds'] = usam_get_emails(['include' => [ $id ], 'folder' => 'attached', 'object_query' => [['object_type' => 'email']]]);
				if ( in_array('related', $parameters['add_fields']) )
				{
					$args = ['object_query' => [['object_id' => $id, 'object_type' => 'email']]];
					if ( $email['type'] == 'inbox_letter' )
						$args['folder_not_in'] = ['deleted'];
					else
						$args['sent_at'] = 'yes';
					$email['related'] = usam_get_emails( $args );
				}		
				if ( in_array('attachments', $parameters['add_fields']) )
				{
					$email['attachments'] = usam_get_email_attachments( $id );
					foreach( $email['attachments'] as &$file ) 
					{
						$file->size = size_format( $file->size );
						$file->icon = usam_get_file_icon( $file->id );					
					}
				}
				if ( in_array('objects', $parameters['add_fields']) )
				{
					$email['objects'] = self::get_objects( $objects, $items );		
				}
				if ( in_array('crm', $parameters['add_fields']) )
				{
					$email['copy_email'] = usam_get_email_metadata( $id, 'copy_email' );				
					$properties = usam_get_properties(['access' => true, 'type' => ['contact', 'company'], 'field_type' => 'email', 'fields' => ['code', 'type']]);
					foreach( ['from' => 'from_email', 'to' => 'to_email', 'copy' => 'copy_email'] as $code => $key )
					{
						if( empty($email[$key]) )
							continue;
						
						$rows = array();
						$meta_query = ['relation' => 'OR'];
						foreach( $properties as $property )
							$meta_query[] = ['value' => $email[$key], 'key' => $property->code];
						$contact = usam_get_contacts(['meta_query' => $meta_query, 'source' => 'all', 'number' => 1, 'cache_meta' => true]);
						if ( !empty($contact) )
						{		
							$location = usam_get_contact_metadata($contact['id'], 'location' ); 		
							
							$email[$code] = $contact;	
							$email[$code]['object_type'] = 'contact';							
							$email[$code]['url'] = usam_get_contact_url( $contact['id'] );
							$email[$code]['company'] = usam_get_company( $contact['company_id'] );
							$email[$code]['manager'] = usam_get_contact( $contact['manager_id'], 'user_id' );
							$email[$code]['groups'] = usam_get_contact_groups( $contact['id'] );							
							if ( !empty($location) ) 
							{
								$email[$code]['full_location_name'] = usam_get_full_locations_name($location, '%country%, %city%');
								$email[$code]['location_time'] = usam_get_location_time( $location );
							}
							if( $email[$code]['company'] )
							{
								$email[$code]['company']['post'] = usam_get_contact_metadata($contact['id'], 'post');
								$email[$code]['company']['site'] = usam_get_company_metadata($contact['company_id'], 'site');								
								$email[$code]['company']['name_type'] = usam_get_name_type_company( $email[$code]['company']['type'] );
								$email[$code]['company']['groups'] = usam_get_company_groups( $contact['company_id'] );
							}
							$email[$code]['mobilephone'] = usam_get_contact_metadata($contact['id'], 'mobilephone');
							$email[$code]['site'] = usam_get_contact_metadata($contact['id'], 'site');
							$email[$code]['status_name'] = usam_get_object_status_name( $contact['status'], 'contact');							
						}
						else
						{ 
							$company = usam_get_companies(['meta_query' => $meta_query, 'number' => 1, 'cache_meta' => true]);	
							if ( !empty($company) )
							{  
								$location = usam_get_company_metadata($company['id'], 'location' ); 
								$email[$code] = $company;
								if ( !empty($location) ) 
								{
									$email[$code]['full_location_name'] = usam_get_full_locations_name($location, '%country%, %city%');
									$email[$code]['location_time'] = usam_get_location_time( $location );
								}
								$email[$code]['phone'] = usam_get_company_metadata($company['id'], 'phone');
								$email[$code]['site'] = usam_get_company_metadata($company['id'], 'site');
								$email[$code]['manager'] = usam_get_contact( $company['manager_id'], 'user_id' );
								$email[$code]['status_name'] = usam_get_object_status_name( $company['status'], 'company');		
								$email[$code]['groups'] = usam_get_company_groups( $company['id'] );
								$email[$code]['name_type'] = usam_get_name_type_company( $company['type'] );
							}
						}
					}							
				}				
			}	
			return $email;
		}
		else
			return new WP_Error( 'no_email', 'Invalid email id', ['status' => 404]);
	}	
		
	public static function update_email( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		return usam_update_email( $id, $parameters );
	}
	
	public static function get_mailboxes( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );		
			
		if( !empty($parameters['add_fields']) )
		{
	//		if( in_array('media', $parameters['add_fields']) )
	//			$query_vars['cache_attachments'] = true;
		}
		require_once( USAM_FILE_PATH . '/includes/mailings/mailboxes_query.class.php' );
		$query = new USAM_Mailboxes_Query( $query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )			
		{
			foreach ( $items as &$item )
			{
				if( isset($parameters['add_fields']) )
				{
					
				}
			}
			$count = $query->get_total();	
			$results = ['count' => $count, 'items' => $items];		
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}	
	
	public static function get_signatures( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );		
			
		if( !empty($parameters['add_fields']) )
		{
	//		if( in_array('media', $parameters['add_fields']) )
	//			$query_vars['cache_attachments'] = true;
		}
		require_once( USAM_FILE_PATH . '/includes/mailings/signature_query.class.php' );
		$query = new USAM_Signatures_Query( $query_vars );		
		$items = $query->get_results();	
		if ( !empty($items) )			
		{
			foreach ( $items as &$item )
			{
				if( isset($parameters['add_fields']) )
				{
					
				}
			}
			$count = $query->get_total();	
			$results = ['count' => $count, 'items' => $items];		
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}	
	
	public static function send_email( WP_REST_Request $request ) 
	{				
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();						
			
		$email = ['body' => nl2br( $parameters['message'] ), 'title' => $parameters['subject'], 'mailbox_id' => $parameters['mailbox_id']];		
		if ( $email['body'] != '' )
		{
			$style = new USAM_Mail_Styling( $parameters['mailbox_id'] );
			$email['body'] = $style->get_message( $email['body'] );
		}
		$links = [];
		if ( !empty($parameters['object_type']) && !empty($parameters['object_id']) )
			$links[] = ['object_id' => $parameters['object_id'], 'object_type' => $parameters['object_type']];		

		$files = !empty($parameters['files'])?$parameters['files']: [];
		$result = false;	
		if ( !empty($parameters['to_companies']) )
		{				
			foreach( $parameters['to_companies'] as $id ) 
			{								
				$to_email = usam_get_company_metadata($id, 'email' );
				if ( !empty($to_email) )
				{
					$company = usam_get_company( $id );
					$email['to_name'] = $company['name'];						
					$email['to_email'] = $to_email;
					$result = usam_send_mail($email, $files, $links);
				}					
			}						
		}
		elseif ( !empty($parameters['to_contacts']) )
		{				
			foreach( $parameters['to_contacts'] as $id ) 
			{					
				$to_email = usam_get_contact_metadata($id, 'email' );
				if ( !empty($to_email) )
				{
					$contact = usam_get_contact( $id );
					$email['to_name'] = $contact['appeal'];
					$email['to_email'] = $to_email;
					$result = usam_send_mail($email, $files, $links);
				}
			}						
		}			
		elseif ( !empty($parameters['to_orders']) )
		{				
			foreach( $parameters['to_orders'] as $id ) 
			{					
				$to_email = usam_get_order_customerdata( $id, 'email' );
				if ( !empty($to_email) )
				{
					$email['to_email'] = $to_email;						
					$order_shortcode = new USAM_Order_Shortcode( $id );				
					$email['body'] = $order_shortcode->get_html( $email['body'] );							
					$result = usam_send_mail($email, $files, $links);
				}
			}				
		}		
		elseif( !empty($parameters['email']) )
		{
			if( is_numeric($parameters['email']) )
			{
				$property = usam_get_property( $parameters['email'] );
				if( !empty($property) )
					
				$to_email = usam_get_order_customerdata( $id, 'email' );				
			}
			$email['to_email'] = $parameters['email'];
			if( $files && !empty($parameters['object_type']) && $parameters['object_type'] == 'file' )
			{ //чтобы скопировать файл и прикрепить к письму
				$files = usam_get_files(['include' => $files]);
				$f = [];
				foreach( $files as $file ) 
				{					
					$f[] = ['file_path' => USAM_UPLOAD_DIR.$file->file_path, 'title' => $file->title];
				}
				$files = $f;
			}
			$result = usam_send_mail($email, $files, $links);
		}				
		if( $result )
		{
			$result = USAM_CRM_API::format_event_email( (object)$result );	
			$result->date_insert = get_date_from_gmt( $result->date_insert );
		}	
		return $result;
	}
		
	public static function delete_email( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' ); 
		return usam_delete_email( $id );
	}	
	
	public static function email_download_files( WP_REST_Request $request ) 
	{		
		$delete = false;
		$id = $request->get_param( 'id' ); 
		$email_attachments = usam_get_email_attachments( $id );	
		if ( count($email_attachments) == 1 )
		{
			$file_name = $email_attachments[0]->name;
			$file_path = USAM_UPLOAD_DIR.$email_attachments[0]->file_path;
		}
		elseif ( !empty($email_attachments) )
		{
			$delete = true;
			$zip = new ZipArchive();		
			$file_name = "email_attachments.zip";
			$file_path = USAM_FILE_DIR.$file_name;
			if ( $zip->open($file_path, ZIPARCHIVE::CREATE) === true ) 
			{
				foreach ( $email_attachments as $file ) 
				{ 
					if( file_exists(USAM_UPLOAD_DIR.$file->file_path) )
						$zip->addFile( USAM_UPLOAD_DIR.$file->file_path, basename(USAM_UPLOAD_DIR.$file->file_path) );
				}
				$zip->close();
			}
		}
		if( file_exists($file_path) )
		{				
			ob_start();
			readfile($file_path);
			$data = ob_get_clean();
			if( $delete )
				unlink( $file_path );					
			return ["download" => "data:application/octet-stream;base64,".base64_encode($data), 'title' => $file_name];
		}
		return false;
	}		
	
	public static function send_sms( WP_REST_Request $request ) 
	{				
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();			
		$parameters['message'] = nl2br( $parameters['message'] );		
		$links = [];
		if ( !empty($parameters['object_id']) && !empty($parameters['object_type']) )		
			$links[] = ['object_id' => $parameters['object_id'], 'object_type' => $parameters['object_type']];	 
		$id = usam_add_send_sms( $parameters, $links );
		if ( $id )
		{
			$sms = (object)usam_get_sms( $id );
			$sms->author = self::author_data( $sms->user_id );
			return $sms;
		}
		else
			return false;
	}
	
	public static function phone_call( WP_REST_Request $request ) 
	{				
		$parameters = self::get_parameters( $request );		
		$links = [];
		if ( !empty($parameters['object_id']) && !empty($parameters['object_type']) )		
			$links[] = ['object_id' => $parameters['object_id'], 'object_type' => $parameters['object_type']];	 
		return apply_filters( 'usam_phone_call', $parameters['phone'], $links );
	}	
	
	public static function phone_cancel( WP_REST_Request $request ) 
	{				
		$parameters = self::get_parameters( $request );	
		return apply_filters( 'usam_cancel_phone_call', true, $parameters['gateway'], $parameters['id'] );
	}		
}
?>