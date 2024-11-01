<?php
// Получить типы местоположений
function usam_get_types_location( $return = 'id' )
{
	$cache_key = 'types_location';
	$object_type = 'usam_system';
	$cache = wp_cache_get($cache_key, $object_type );
	if( $cache === false )
	{		
		global $wpdb;
	
		$cache = $wpdb->get_results( "SELECT * FROM `".USAM_TABLE_LOCATION_TYPE."` ORDER BY level ASC" );	
		wp_cache_set( $cache_key, $cache, $object_type );
	}		
	$out = array();
	if ( $return == 'id' )
		foreach ( $cache as $value )
		{		
			$out[$value->id] = $value;		
		}	
	else
		foreach ( $cache as $value )
		{		
			$out[$value->code] = $value;		
		}	
	return $out;	
}

function usam_get_location_id_by_meta( $key, $value ) 
{
	global $wpdb;	
	if ( !$value ) 
		return;
	
	$cache_key = "usam_location_$key-$value";
	$location_id = wp_cache_get( $cache_key );
	if ($location_id === false) 
	{	
		$location_id = (int)$wpdb->get_var($wpdb->prepare("SELECT location_id FROM ".USAM_TABLE_LOCATION_META." WHERE meta_key='%s' AND meta_value='%s' LIMIT 1", $key, $value));	
		wp_cache_set($cache_key, $location_id);
	}	
	return $location_id;
}

function usam_get_types_location_query( $order = 'DESC', $orderby = 'level' )
{
	global $wpdb;
	
	$sql = "SELECT * FROM `".USAM_TABLE_LOCATION_TYPE."` ORDER BY $orderby $order";	
	$results = $wpdb->get_results( $sql );	
	return $results;	
}

function usam_get_type_location( $value, $colum = 'id' )
{
	if ( $colum == 'code' )
	{
		$cache_code = wp_cache_get($value, 'usam_type_location_code' );
		if ( $cache_code )
		{ 
			$value =  $cache_code;
			$colum = 'id';
		}	
	}
	$object_type = 'usam_type_location';
	if( $colum == 'code' || ! $cache = wp_cache_get($value, $object_type ) )
	{		
		if ( $colum == 'id' ) 
			$format = '%d';
		else
			$format = '%s';
		
		global $wpdb;	
		$cache = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `".USAM_TABLE_LOCATION_TYPE."` WHERE $colum='$format'", $value ),'ARRAY_A' );	
		if ( !empty($cache) && $colum == 'code' )
		{
			$value = $cache['id'];
			$colum = 'id';
		}		
		if ( $colum == 'id' )
			wp_cache_set( $value, $cache, $object_type );
	}		
	return $cache;	
}

// Получить массив местоположений
function usam_get_array_locations_up( $parent = 0, $field = 'all', $level = 0 )
{
	static $locations;	
	
	if ( $level == 0 )			
		$locations = array();	
	
	$level ++;	

	$parent = (int)$parent;
	$location = usam_get_location( $parent );
	if ( !empty($location) )
	{	
		if ( $field == 'all' )		
			$locations[] = $location;		
		elseif ( isset($location[$field]) )
			$locations[] = $location[$field];
		elseif ( $field == 'code=>name' )
		{ 
			if ( !isset($locations[$location['code']]) )
				$locations[$location['code']] = $location['name'];
		}
		elseif ( $field == 'code=>value' )
			$locations[$location['code']] = $location;
		elseif ( $field == 'id=>value' )
			$locations[$location['id']] = $location;
		elseif ( $field == 'code=>id' )
			$locations[$location['code']] = $location['id'];
		else
			$locations[] = $location;		
		
		if ( $location['parent'] != 0 )						
			$result = usam_get_array_locations_up( $location['parent'], $field, $level );	
	}	
	return $locations;
}

function usam_get_address_locations( $location_id, $fields = 'code=>name' )
{		
	static $cache_locations = [];	
	if ( !$location_id )
		return [];
	
	$ancestors_locations = get_option( 'usam_ancestors_locations', [] );
	if ( empty($ancestors_locations) )
		$ancestors_locations = [];
		
	if( empty($ancestors_locations[$location_id]) )
	{			
		$cache = usam_get_array_locations_up( $location_id, 'code=>id' );	
		$ancestors_locations[$location_id] = $cache;
		update_option( 'usam_ancestors_locations', $ancestors_locations);
	}	
	else
		$cache = $ancestors_locations[$location_id];		
	if ( $fields == 'id' || $fields == 'code=>id' )	
		return $cache;
	elseif ( $fields == 'code=>name' && isset($cache_locations[$location_id]) )
		$results = $cache_locations[$location_id];
	else
	{		
		$results = usam_get_locations(['include' => array_values($cache), 'fields' => $fields]);
		if ( $fields == 'code=>name' )
			$cache_locations[$location_id] = $results;
	}
	return $results;
}

function usam_cache_locations( )
{	
	$locations = usam_get_locations(['fields' => ['id', 'parent'], 'cache_results' => true, 'code' => 'city']);	
	$cache_locations = array();
	foreach( $locations as $location )
	{		
		$cache_locations[$location->id] = usam_get_array_locations_up( $location->id, 'code=>id' );
	}
	update_option( 'usam_ancestors_locations', $cache_locations);
	return $cache_locations;
}

function usam_get_array_locations_down( $id = 0, $fields = 'id', $level = 0 )
{
	static $locations;	
	
	if ( $level == 0 )			
		$locations = [];	
	
	$level ++;
	$ancestors_locations = get_option( 'usam_ancestors_locations', []);			
	if ( !empty($ancestors_locations) )
	{ 
		$results = [];
		foreach( $ancestors_locations as $loc )
		{					
			$parent = 0;
			$ok = false;
			foreach( $loc as $value )
			{					
				if ( $id == $value )
				{
					$ok = true;
					break;
				}
				$parent = $value;
			}
			if ( $ok )
				$results[] = $parent;
		}
		if ( $results )
		{
			if ( $fields == 'id' )
				$locations = $results;
			else	
				$locations = usam_get_locations(['include' => $results, 'fields' => $fields]);
		}
	}
	else
	{
		$locations = usam_get_locations(['parent' => $id, 'fields' => $fields]); 
		if ( !$locations )
		{	
			foreach( $locations as $location )			
				usam_get_array_locations_down( $location->id, $fields, $level );	
		}	
	}
	return $locations;
}

function usam_get_full_locations_name( $location_id, $string = '', $contact_location = false ) 
{	
	$location_id = (int)$location_id;
	if ( !$location_id )
		return '';

	$locations = usam_get_address_locations( $location_id );	
	if ( $contact_location )
	{		
		$contact_id = usam_get_contact_id();
		$contact_location_id = usam_get_contact_metadata( $contact_id, 'location' );
		if ( $contact_location_id ) 
		{
			$contact_locations = usam_get_address_locations( $contact_location_id );
			foreach ( $locations as $key1 => $location1 ) 
			{
				if ( $key1 == 'region' || $key1 == 'district' || $key1 == 'country' )
				{
					foreach ( $contact_locations as $key1 => $location2 ) 
					{ 
						if ( $location1 == $location2 )
						{
							unset($locations[$key1]);
							break;
						}
					}					
					if ( count($locations) == 1 )
						break;
				}
			}			
		}
	}
	if ( isset($locations['subregion']) && array_key_last($locations) != 'subregion' )	
		unset($locations['subregion']);
	if ( $string == '' )
	{		
		$string = implode(', ', $locations);	
	}
	elseif( count($locations) == 1 )
		$string = current($locations);
	else
	{		
		foreach ( $locations as $code => $name ) 
			$string = str_ireplace("%$code%", $name, $string );	
	
		preg_match_all('#%(.+?)%#s', $string, $results, PREG_SET_ORDER);	
		foreach ( $results as $code ) 
		{
			$string = str_ireplace($code[0].', ', '', $string );
			$string = str_ireplace($code[0].' ', '', $string );
			$string = str_ireplace($code[0], '', $string );
		}
	}	
	return $string;		
}

// Проверить местоположение в заданной ветке местоположений
function usam_seach_location_down( $id, $seach_id )
{		
	global $wpdb;	
	$locations = $wpdb->get_col("SELECT id FROM ".USAM_TABLE_LOCATION." WHERE parent='$id' ");
	$result = false;
	if ( in_array($seach_id, $locations) )			
		$result = true;
	elseif ( !empty($locations) )
		foreach( $locations as $id )
		{
			$result = usam_seach_location_down( $id, $seach_id );		
			if ( $result )
				break;
		}
	return $result;		
}

// Проверить местоположение в заданной ветке местоположений
function usam_seach_location_up( $id, $seach_id )
{		
	global $wpdb;	
	$location = $wpdb->get_var("SELECT parent FROM ".USAM_TABLE_LOCATION." WHERE id='$seach_id' ");
	$result = false;	
	if ( $location == $id )			
		$result = true;
	elseif ( $location != 0 )
		$result = usam_seach_location_up( $id, $location );		
	return $result;		
}

function usam_delete_locations( $ids )
{	
	global $wpdb;	
	$delete_ids = array();
	foreach ( $ids as $id )
	{		
		$locations_ids = usam_get_locations( array( 'parent' => $id, 'fields' => 'id') );
		if ( !empty($locations_ids) )
			usam_delete_locations( $locations_ids );
	
		$delete_ids[] = $id;
	}	
	if ( !empty($delete_ids) )
	{
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_LOCATION." WHERE id IN (".implode(',',$delete_ids).")");	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_LOCATION_META." WHERE location_id IN (".implode(',',$delete_ids).")");	
	}
}

function usam_install_locations( $codes )
{
	global $wpdb;	
	$columns = array( 'id', 'code', 'parent', 'name', 'sort' );
	$country_address_classifier = ['RU' => ['RU_location_metas']];	
	foreach ( $codes as $code )
	{		
		$filename = USAM_FILE_PATH . "/admin/db/db-install/".$code."_location.csv";
		if (file_exists($filename)) 
		{			
			$classifiers = array();			
			if ( isset($country_address_classifier[$code]) )
			{
				foreach ( $country_address_classifier[$code] as $classifier )
				{
					$classifier_filename = USAM_FILE_PATH . "/admin/db/db-install/".$classifier.".csv";
					if (file_exists($classifier_filename)) 
					{
						$lists = usam_read_txt_file( $classifier_filename, ',' ); 					
						$classifier_columns = $lists[0];
						unset($lists[0]);					
						foreach ( $lists as $list )
						{	
							$location_id = $list[0];
							unset($list[0]);						
							foreach ( $list as $meta_key => $meta_value )
							{
								if ( !empty($meta_value) )
									$classifiers[$location_id][] = array( 'meta_key' => $classifier_columns[$meta_key], 'meta_value' => $meta_value );
							}	
						}
					}
				}
			}			
			$lists = usam_read_txt_file( $filename, ',' ); 				
			foreach ( $lists as $value )
			{	
				$insert = array();
				foreach ( $columns as $number => $key )
				{				
					if ( isset($value[$number]) )
						$insert[$key] = $value[$number];
				}		
				if ( !empty($insert['code']) )
				{
					$result = $wpdb->insert( USAM_TABLE_LOCATION, $insert );
					$id = $wpdb->insert_id;
					if ( isset($classifiers[$value[0]]) )
					{
						foreach ( $classifiers[$value[0]] as $metas )
						{
							$result = usam_update_location_metadata( $id, $metas['meta_key'], $metas['meta_value'] );
						}
					}
				}
			}	
		}			
	}		
	usam_link_locations_and_countries( );
}

function usam_link_locations_and_countries( )
{
	require_once( USAM_FILE_PATH . '/includes/directory/country_query.class.php');		
	$countries = usam_get_countries( array('code' => 'country', 'fields' => array('code', 'name') ) );
	if ( empty($countries))
		return false;	
	$locations = usam_get_locations( array('code' => 'country', 'fields' => array('id', 'name') ) );		
	if ( empty($locations))
		return false;
	foreach ( $locations as $location )
	{
		foreach ( $countries as $country )
		{
			if (strcasecmp($location->name, $country->name) == 0) 
			{
				$update = array( 'location_id' => $location->id );
				$result = usam_update_country( $country->code, $update );
			}
		}
	}		
	return true;
}


// Выбор одного региона
function usam_get_list_locations( $locations, $select, $recursion = 0 )
{		
	$recursion++;
	$prefix = str_repeat( '&nbsp;&nbsp;&nbsp;' , $recursion );
	$out = '<ul>';
	foreach ( $locations as $location )			
	{
		$active = ($location['id'] == $select)?'active':'';
		$out .= "<li id = 'list_".$location['id']."' data-location = '".$location['id']."'>$prefix";
		
		if ( $location['code'] == 'city' )
			$out .= "<a href='".add_query_arg( array( 'location' => $location['id'] ) )."'><label class = 'title $active' for = 'location_".$location['id']."'>".$location['name']."</label></a>";
		else
			$out .= "<label class = 'title $active' for = 'location_".$location['id']."'>".$location['name']."</label>";
		if ( !empty($location['level']) )
		{				
			$sub_location = usam_get_list_locations( $location['level'], $select, $recursion );		
			$out .= "<span id = 'open_".$location['id']."' > + </span>".$sub_location;
		}
		$out .= "</li>";			
	}
	$out .= '</ul>';
	return $out;
}

function usam_get_current_user_location( )
{ 
	static $current_location = null;
	if ( get_option('usam_get_customer_location', 1) )
	{
		if ( $current_location === null )
		{
			$current_location = 0;	
			$ip = $_SERVER['REMOTE_ADDR']; //GEOIP_COUNTRY_CODE
			if ( rest_is_ip_address($ip) )
			{
				$current_location = usam_get_visits(['fields' => 'location_id', 'ip' => ip2long($ip), 'number' => 1, 'monthnum' => date('m'), 'year' => date('Y'), 'orderby' => 'ip', 'order' => 'DESC']);
				if ( !$current_location )
					$current_location = apply_filters( 'usam_location_by_ip', $current_location, $ip );
			}			
		}
	}
	return $current_location;
}		

// Получить координаты по названию
function usam_get_geocode( $name )
{ 
	$result = apply_filters( 'usam_geocode', false, $name );
	if ( $result )
		return $result;
	
	$headers["Accept"] = 'application/json';
	$headers["Content-type"] = 'application/json';		
	
	//$query = http_build_query(['geocode' => $name, 'apikey' => $apikey, 'format' => 'json']);  
	$data = wp_remote_get( 'https://geocode-maps.yandex.ru/1.x/?', ['sslverify' => true, 'body' => ['geocode' => $name, 'apikey' => $apikey, 'format' => 'json'], 'headers' => $headers]);	
	if ( is_wp_error($data) )
		return false;
	$resp = json_decode($data['body'], true);	
	if ( isset($resp['error'] ) ) 
		return false;
	if ( isset($resp['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos']))
	{
		$result = $resp['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];			
		return explode(' ', $result );
	}
	return false;
}

// Получить расстояние между координатами
function usam_get_distance_yandex( $origins, $destinations, $mode = 'driving' )
{ 
	$apikey = '';
	
	$headers["Accept"] = 'application/json';
	$headers["Content-type"] = 'application/json';		
	
	$params = ['origins' => $origins, 'destinations' => $destinations, 'mode' => $mode, 'apikey' => $apikey];	
	$data = wp_remote_get( 'https://api.routing.yandex.net/v2/route', ['sslverify' => true, 'body' => $params, 'headers' => $headers]);	
	
	if ( is_wp_error($data) )
		return false;
	
	$resp = json_decode($data['body'], true);	
	if ( isset($resp['error'] ) ) 
	{		
		return false;
	}	
	return $resp;
}

function usam_get_geocode_map( $address )
{ 
	$yandex = get_option('usam_yandex');					
	if ( !empty($yandex['developer']['api']) )
	{
		$params = array( 'geocode' => $address, 'format' => 'json', 'apikey' => $yandex['developer']['api'] );						
		$request_link = "https://geocode-maps.yandex.ru/1.x?".http_build_query($params);
		$response = @file_get_contents($request_link);
		$response = json_decode( $response );	
		if ( !empty($response->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos) )
		{ 
			return explode(' ', $response->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos);
		}	
	}	
	return array();
}
	
// Получить расстояние между координатами
function usam_get_distance_by_name( $from, $to )
{ 		
	require_once( USAM_FILE_PATH . '/includes/seo/google/maps.class.php' );		
	$pagespeed = new USAM_Google_Maps();	
	$result = $pagespeed->get_distance_matrix( $from, $to );
	return $result;	
}

function usam_get_name_sales_area( $area_id )
{	
	if ( empty($area_id) )
		return '';
	
	$option = get_site_option('usam_sales_area');
	$grouping = maybe_unserialize( $option );
	$name = '';
	foreach( $grouping as $group )
	{				
		if ( $group['id'] == $area_id )
		{
			$name = $group['name'];
			break;
		}
	}
	return $name;
}

function usam_locations_in_sales_area( $sales_area, $location_ids )
{
	$option = get_site_option('usam_sales_area');
	$grouping = maybe_unserialize( $option );
	foreach( $grouping as $group )
	{					
		if ( !in_array($group['id'], $sales_area) )
			continue;
		
		$result = array_intersect($group['locations'], $location_ids );			
		if ( !empty($result) )
			return true;
	}
	return false;
}

function usam_get_sales_areas( $args = array() )
{	
	$option = get_site_option('usam_sales_area');
	$areas = maybe_unserialize( $option );	
	if ( empty($areas) )
		$areas = array();
	if ( !empty($args) )
	{
		foreach ( $areas as $key => $item )
		{
			if ( !empty($args['include']) && !in_array($item['id'], $args['include']) )
				unset($areas[$key]);
			elseif ( !empty($args['locations']) && array_intersect($item['locations'], $args['locations']) )
				unset($areas[$key]);
			elseif ( !empty($args['search']) && stripos($item['title'], $args['search'])=== false )
				unset($areas[$key]);	
			elseif ( isset($args['active']) && $args['active'] != $item['active'] )
				unset($areas[$key]);			
		}
	}
	return $areas;
}

function usam_get_location_timezone( $location_id, $country = 'RU' ) 
{
	$timezone = usam_get_location_metadata( $location_id, 'timezone' );		
	if ( !$timezone )
	{
		$latitude = usam_get_location_metadata( $location_id, 'latitude' );
		$longitude = usam_get_location_metadata( $location_id, 'longitude' );
			
		$data = wp_remote_post('http://htmlweb.ru/json/geo/timezone', ['body' => ['latitude' => $latitude, 'longitude' => $longitude, 'country' => $country], 'sslverify' => true, 'timeout' => 5, 'method' => 'GET']);
		if ( is_wp_error($data) )
			return false;
		$resp = json_decode($data['body'],true);	
		$timezone = isset($resp['offset'])?$resp['offset']:false;	
		if ( $timezone )
			usam_update_location_metadata( $location_id, 'timezone', $timezone );				
	}	
	return $timezone;
}

function usam_get_location_time( $location_id, $format = 'H:i' ) 
{
	$timezone = usam_get_location_timezone( $location_id );
	if ( $timezone )
	{
		$timezone = str_replace("UTC", "", $timezone);		
		$today = new DateTime("now", new DateTimeZone($timezone) );
		return $today->format($format);
	}
	return '';
}

function usam_set_locations_distance( $from_location_id, $to_location_id, $distance  ) 
{
	global $wpdb;
	
	if ( $from_location_id == 0 || $to_location_id == 0 || $distance == 0 ) 
		return false;
	
	$sql = "INSERT INTO `".USAM_TABLE_LOCATIONS_DISTANCE."` (`from_location_id`,`to_location_id`,`distance`) VALUES ('%d','%d','%d') ON DUPLICATE KEY UPDATE `distance`='%d'";					
	$insert = $wpdb->query( $wpdb->prepare($sql, $from_location_id, $to_location_id, $distance, $distance ) );
	
	return $insert;
}

function usam_get_locations_distance( $from_location_id, $to_location_id ) 
{
	global $wpdb;	
	
	$result = $wpdb->get_var("SELECT distance FROM ".USAM_TABLE_LOCATIONS_DISTANCE." WHERE (from_location_id='$from_location_id' AND to_location_id='$to_location_id') OR (from_location_id='$to_location_id' AND to_location_id='$from_location_id') LIMIT 1" );		
	return $result;
}

function usam_get_columns_location_import() 
{											
	$columns = array(
		'id'	   => __('ID','usam'),
		'code'	   => __('Тип местоположения','usam'),
		'parent'   => __('Родитель','usam'),		
		'name'	   => __('Название','usam'),	
		'sort'	   => __('Сортировка','usam'),
		'index'	   => __('Индекс','usam'),
		'KLADR'	   => __('Код КЛАДР','usam'),
		'FIAS'	   => __('Код ФИАС','usam'),		
		'OKATO'	   => __('Код ОКАТО','usam'),
		'OKTMO'	   => __('Код ОКТМО','usam'),
		'IFNS'	   => __('Код ИФНС','usam'),		
		'timezone' => __('Часовой пояс','usam'),
		'latitude' => __('Широта','usam'),		
		'longitude'=> __('Долгота','usam'),
		'population' => __('Население','usam')
	);	
	return apply_filters('usam_columns_location_import', $columns);
}