<?php
require_once( USAM_FILE_PATH . '/admin/includes/filter_processing.class.php' );
abstract class USAM_API_handler_assistant
{					
	protected static $interface_filters = false;
	protected static $query_vars = [];	
	protected static function check_wpdb_error() 
	{
		global $wpdb;
		if ( !$wpdb->last_error ) 
			return false;
		return true;
	}	
	
	protected static function get_parameters( $request ) 
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
		{
			$parameters = $request->get_body_params();	
			if ( !$parameters )
			{
				$parameters = $request->get_query_params();		
				foreach ( $parameters as $k => &$parameter )
				{	 
					if ( is_string($parameter) && $k != 'fields' )
					{						
						if ( stripos($parameter, '=') !== false ) 
						{					
							$options = explode(',', $parameter);
							$parameter = [];
							foreach ( $options as $option ) 
							{
								$mas = explode('=', $option);
								$parameter[$mas[0]] = $mas[1];	
							}					
						}						
					}
				}
			}			
		}
		foreach ( $parameters as $k => &$parameter )
		{								
			if ( is_string($parameter) )
			{
				if ( $k == 'fields' && stripos($parameter, ',') !== false )
					$parameter = explode(',', $parameter);
			}
		}	
		if ( isset($parameters['add_fields']) )
			$parameters['add_fields'] = is_string($parameters['add_fields']) ? explode(',', $parameters['add_fields']) : $parameters['add_fields'];
		return array_merge($request->get_default_params(), $parameters );	
	}
	
	protected static function get_query_vars( $parameters, $query_vars = [] ) 
	{		
		$paged = isset($parameters['paged']) ? (int)$parameters['paged']:1;		
		if ( isset($parameters['count']) )
		{
			if ( $parameters['count'] === 0 )
				$query_vars['number'] = 10000;
			else
			{
				$query_vars['number'] = (int)$parameters['count'];
				$query_vars['number'] = $query_vars['number'] <= 10000 ? $query_vars['number'] : 10000;
			}
			unset($query_vars['count']);
		}
		else
			$query_vars['number'] = 20;
		$query_vars['paged'] = $paged;		
		if ( !empty($parameters['search']) )
			$query_vars['search'] = sanitize_text_field(trim(stripslashes($parameters['search'])));	
		elseif ( !empty($parameters['s']) )
			$query_vars['search'] = sanitize_text_field(trim(stripslashes($parameters['s'])));	
		$query_vars['order'] = !empty($query_vars['order'])?$query_vars['order']:'DESC';
		self::$query_vars = $query_vars;
		return $query_vars;
	}
	
	protected static function get_contact_data( $id ) 
	{		 
		$contact_id = usam_get_contact_id();
		if ( is_array($id) )
			$contact = $id;
		else
			$contact = usam_get_contact( $id );
		
		if ( !$contact )
			return [];	
		$contact['foto'] = usam_get_contact_foto( $contact['id'] );
		$contact['url'] = usam_get_contact_url( $contact['id'] );
		$contact['notifications_email'] = (int)usam_get_contact_metadata( $contact['id'], 'notifications_email' );
		$contact['notifications_sms'] = (int)usam_get_contact_metadata( $contact['id'], 'notifications_sms' );
		$contact['sex'] = usam_get_contact_metadata( $contact['id'], 'sex' );
		$contact['profile_activation'] = (int)get_user_option( 'usam_user_profile_activation', $contact['user_id'] );	
		$location_id = usam_get_contact_metadata( $contact['id'], 'location' );
		$location = usam_get_location( $location_id );
		$contact['location_name'] = !empty($location['name'])?$location['name']:'';
		$contact['date_online'] = $contact['online'];	
		if ( $contact['online'] && strtotime($contact['online']) >= USAM_CONTACT_ONLINE )
			$contact['online'] = true;		
		$contact['profile'] = !empty($contact['user_id']);
		if ( $contact_id != $contact['id'] && !current_user_can('view_contacts') )
		{							
			unset($contact['open']);
			unset($contact['manager_id']);
			unset($contact['number_orders']);
			unset($contact['total_purchased']);
			unset($contact['last_order_date']);
			unset($contact['contact_source']);
			unset($contact['date_insert']);
			unset($contact['user_id']);			
		}
		else	
			$contact['source_name'] = usam_get_name_contact_source( $contact['contact_source'] );	
		if ( !current_user_can('edit_contact') )
			unset($contact['id']);		
		return $contact;
	}
	
	protected static function author_data( $user_id, $column = 'user_id' )
	{
		$author = [];
		if( $user_id )
		{
			$author = usam_get_contact( $user_id, $column );
			if( $author )
			{
				$author['online'] = strtotime($author['online']) >= USAM_CONTACT_ONLINE;
				$author['post'] = (string)usam_get_contact_metadata($author['id'], 'post');
				$author['foto'] = usam_get_contact_foto( $author['id'] );
				$author['url'] = usam_get_contact_url( $author['id'] );	
				$author['mine'] = $author['user_id'] == get_current_user_id();	
				unset($author['user_id']);
			}			
		}
		return $author;
	}
	
	protected static function get_digital_interval_for_query( $parameters, $columns_search, $key = 'conditions' )
	{				
		if ( !isset(self::$query_vars[$key]) )
			self::$query_vars[$key] = [];		
		
		$f = new Filter_Processing( $parameters );
		self::$query_vars[$key] = array_merge(self::$query_vars[$key], $f->get_digital_interval_for_query( $columns_search ) );
	}
	
	protected static function get_date_interval_for_query( $parameters, $columns_search, $key = 'conditions' )
	{			
		if ( !isset(self::$query_vars['date_query']) )
			self::$query_vars['date_query'] = [];
		
		$f = new Filter_Processing( $parameters );
		self::$query_vars['date_query'] = array_merge(self::$query_vars['date_query'], $f->get_date_interval_for_query( $columns_search ) );
	}
		
	protected static function get_string_for_query( $parameters, $columns_search, $key = 'conditions' )
	{			
		if ( !isset(self::$query_vars[$key]) )
			self::$query_vars[$key] = [];
		
		$f = new Filter_Processing( $parameters );
		self::$query_vars[$key] = array_merge(self::$query_vars[$key], $f->get_string_for_query( $columns_search ) );
	}
	
	protected static function get_meta_for_query( $parameters, $type, $key = 'meta_query' )
	{			
		if ( !isset(self::$query_vars[$key]) )
			self::$query_vars[$key] = [];
		
		$f = new Filter_Processing( $parameters );
		self::$query_vars[$key] = array_merge(self::$query_vars[$key], $f->get_meta_for_query( $type, self::$query_vars[$key] ) );
	}

	public static function get_terms( $query_vars ) 
	{	
		$autocomplete = false;
		if( !empty($query_vars['fields']) )
		{
			switch ( $query_vars['fields'] ) 
			{
				case 'autocomplete' :
					$autocomplete = true;
					$path = true;
					unset($query_vars['fields']);
				break;			
			}
		}
		if ( !empty($query_vars['taxonomy_object']) )
		{
			$query_vars['taxonomy'] = get_taxonomies(['object_type' => [$query_vars['taxonomy_object']]]);		
			unset($query_vars['taxonomy']['usam-product_attributes']);		
			unset($query_vars['taxonomy']['usam-variation']);		
		}
		if ( !empty($query_vars['search']) )
			$query_vars['search'] = usam_remove_emoji($query_vars['search']);
		
		if ( !isset($query_vars['hide_empty']) )
			$query_vars['hide_empty'] = 0;
		if ( isset($query_vars['paged']) )
			$query_vars['offset'] = ($query_vars['paged'] - 1)*$query_vars['number'];		
		if ( empty($query_vars['status']) )
			$query_vars['status'] = 'publish';
		elseif ( $query_vars['status'] == 'all' )
			unset($query_vars['status']);
		if ( !empty($query_vars['external_code']) )
			$query_vars['usam_meta_query'] = [['key' => 'external_code', 'value' => array_map('sanitize_text_field', (array)$query_vars['external_code']), 'compare' => 'IN']];			
		$term_query = new WP_Term_Query( $query_vars );		
		
		$items = [];
		$count = 0;		
		if ( !empty($term_query->terms) )
		{
			foreach( $term_query->terms as $k => $term )
			{
				if ( $autocomplete )
				{
					$obj = new stdClass();
					$obj->id = (int)$term->term_id;					
					$obj->name = stripcslashes($term->name);
					$items[] = $obj;
				}					
				else
				{
					if( is_object($term) )
					{
						$term->url = get_term_link($term->term_id, $term->taxonomy);
						$term->term_id = (int)$term->term_id;
						$term->parent = (int)$term->parent;										
						if ( isset($query_vars['add_fields']) )
						{
							if ( in_array('children', $query_vars['add_fields']) )
								$term->children = get_term_children($term->term_id, $term->taxonomy);
							if ( in_array('external_code', $query_vars['add_fields']) )
								$term->external_code = (string)usam_get_term_metadata($term->term_id, 'external_code');
							if ( in_array('childs', $query_vars['add_fields']) )
								$term->childs = get_term_children( $term->term_id, $term->taxonomy );
							if ( in_array('ancestors', $query_vars['add_fields']) )
								$term->ancestors = usam_get_ancestors( $term->term_id, $term->taxonomy );
							if ( in_array('sort', $query_vars['add_fields']) )
								$term->sort = usam_get_term_metadata( $term->term_id, 'sort' );		
							if ( in_array('status', $query_vars['add_fields']) )
								$term->status = usam_get_term_metadata( $term->term_id, 'status' );	
						}
					}
					$items[$k] = $term;
				}								
			}
			//$category_children = get_option($taxonomy.'_children', []);
			if( isset($query_vars['name_format']) )
			{
				if( $query_vars['name_format'] == 'path' )
				{
					$ids = [];
					foreach( $items as $item )
					{
						$ids = array_merge( $ids, usam_get_ancestors($item->term_id) );		
					}	
					$terms = get_terms(['fields' => 'id=>name', 'hide_empty' => 0, 'include' => $ids]);
					foreach( $items as &$item )				
					{
						$ancestors = usam_get_ancestors( $item->term_id );
						$names = [];	
						foreach( $ancestors as $id )
							$names[] = $terms[$id];	
						$names = array_reverse($names);
						$names[] = $item->name;	
						$item->name = implode(' => ', $names);
					}
				}
				if( $query_vars['name_format'] == 'hierarchy' )
				{
					$r = [];											
					foreach( $items as $k => $item )	
					{
						if( !$item->parent )
						{
							$r[] = $item;	
							foreach( $items as $k2 => $item2 )	
							{
								$ancestors = usam_get_ancestors( $item2->term_id, $item2->taxonomy );
								if( in_array($item->term_id, $ancestors) )
								{
									$item2->name = str_repeat("   ", count($ancestors)).$item2->name;
									$r[] = $item2;
									unset($items[$k2]);
								}
							}
						}
					}
					$items = $r;
				}
			}
			$count = count($items);			
		}	
		return ['count' => $count, 'items' => $items];
	}	
}
?>