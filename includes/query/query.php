<?php
// Порядок сортировки
function usam_product_order( $default = 'ASC' )
{
	$order = $default;
	if ( isset($_REQUEST['order']) )
		$order = sanitize_title($_REQUEST['order']);	
	switch ( $order ) 
	{
		case "DESC":
		case "desc":
			$query_vars = 'DESC';
		break;
		case "ASC":
		case "asc":
			$query_vars = 'ASC';
		break;		
		default:
			$query_vars = $default;
		break;
	}
	return $query_vars;
}

/**
 * Получить необходимый порядок сортировки продукта
 * Если никакой сортировки не указан, порядок сортировки настраивается в Настройки -> магазин -> Презентация -> "Сортировать продукт" » используется.
 */
function usam_product_sort_order_query_vars( $orderby = null )
{		
	$query_vars = array();	
	switch ( $orderby ) 
	{
		case "dragndrop":
			$query_vars["orderby"] = 'menu_order';
		break;
		case "name":
			$query_vars["orderby"] = 'title';		
		break;			
		case "price":	
			$query_vars["orderby"]  = 'price';		
			$query_vars["type_price"]  = usam_get_customer_price_code();	
		break;			
		case "percent":		// сортировать по проценту скидки				
			$query_vars["orderby"]  = 'percent';		
			$query_vars["type_price"]  = usam_get_customer_price_code();
			$query_vars["order"]    = 'DESC';
		break;		
		case "date":
		case "sku":
		case "rating":
			$query_vars["orderby"]  = $orderby;
		break;
		case "purchased":
			$query_vars["orderby"] = ['purchased' => 'DESC'];					
		break;
		case "views":
			$query_vars["orderby"] = ['views' => 'DESC'];	
		break;
		case "popularity":
			$query_vars["orderby"] = ['purchased' => 'DESC', 'views' => 'DESC'];							
		break;
		case "id":
			$query_vars["orderby"] = 'ID';		
		break;	
		default:		
			$term = get_term_by( 'slug', $orderby, 'usam-product_attributes' );
			if ( !empty($term) && usam_get_term_metadata($term->term_id, 'sorting_products') )
			{
				$field_type = usam_get_term_metadata($term->term_id, 'field_type');				
				switch ( $field_type ) 
				{
					case "N":
					case "O":
						$query_vars["orderby"] = 'attribute_value_num';
					break;
					default:
						$query_vars["orderby"] = 'attribute_value';	
					break;		
				}										
				$query_vars["attribute_key"] = $orderby;
			}
			else
				$query_vars["orderby"] = 'ID';				
		break;			
	}	
	return $query_vars;
}

function usam_update_cache( $object_ids, $tables, $column )
{	
	global $wpdb;	
		
	if ( empty($object_ids) )
		return [];
	if ( !is_array($object_ids) ) 
	{
		$object_ids = preg_replace('|[^0-9,]|', '', $object_ids);
		$object_ids = explode(',', $object_ids);
	}
	$object_ids = array_map('intval', $object_ids);		
	
	$out = array();
	foreach ( $tables as $table => $cache_key ) 
	{					
		$ids = [];
		$cache = [];
		foreach ( $object_ids as $id ) 
		{
			$cached_object = wp_cache_get( $id, 'usam_'.$cache_key );
			if ( false === $cached_object )
				$ids[] = $id;
			else
				$cache[$id] = $cached_object;
		}
		if ( empty($ids) )
			return $cache;
			
		$id_list = join( ',', $ids );
		$meta_list = $wpdb->get_results( "SELECT * FROM $table WHERE $column IN ($id_list) ORDER BY $column ASC" );
		if ( !empty($meta_list) ) 
		{		
			foreach ( $meta_list as $metarow) 
			{				
				$cache[$metarow->$column][] = $metarow;
			}
		}		
		foreach ( $ids as $id ) 
		{
			if ( !isset($cache[$id]) )
			{
				$cache[$id] = [];
			}
			wp_cache_add( $id, $cache[$id], 'usam_'.$cache_key );
		}			
		$out[$cache_key] = $cache;
	}		
	return $out;
}

function usam_get_array_metadata($object_id, $meta_type, $meta_key, $type_value = 'string')
{
	if( $meta_type === 'product' )
		$function = "usam_get_{$meta_type}_meta";
	else
		$function = "usam_get_{$meta_type}_metadata";	
	$metas = $function($object_id, $meta_key, false);
	$results = [];
	if ( !empty($metas) )
		foreach( $metas as $meta )
		{
			if( $type_value === 'number' )
				$results[] = absint( $meta->meta_value );
			else
				$results[] = maybe_unserialize( $meta->meta_value );
		}
	return $results;
}

function usam_save_array_metadata($object_id, $meta_type, $meta_key, $values)
{	
	if( $meta_type === 'product' )
	{
		$add_function = "usam_add_{$meta_type}_meta";
		$delete_function = "usam_delete_{$meta_type}_meta";		
	}
	else
	{
		$add_function = "usam_add_{$meta_type}_metadata";
		$delete_function = "usam_delete_{$meta_type}_metadata";		
	}
	$metadata = usam_get_array_metadata( $object_id, $meta_type, $meta_key );	
	$i = 0;
	foreach ( $values as $v ) 
	{
		if ( !in_array($v, $metadata) )	
		{
			if ( $add_function( $object_id, $meta_key, $v, false ) )
				$i++;
		}
	}
	if ( $metadata )
	{
		foreach( $metadata as $v )
		{
			if ( !in_array($v, $values) )
			{
				if ( $delete_function($object_id, $meta_key, $v) )
					$i++;
			}
		}
	}	
	return $i;
}

function usam_save_meta( $id, $type, $code, $values )
{	
	$get_function = "usam_get_{$type}_metadata";
	$add_function = "usam_add_{$type}_metadata";
	$delete_function = "usam_delete_{$type}_metadata";		
	$metadata = $get_function( $id, $code, false );	
	$result = false;
	foreach ( $values as $v ) 
	{
		$ok = true;	
		foreach ( $metadata as $meta )
		{									
			if ( $meta->meta_value == $v )
			{
				$ok = false;
				break;
			}					
		}	
		if ( $ok )
		{
			if ( $add_function( $id, $code, $v ) )
				$result = true;
		}
	}
	if ( $metadata )
	{
		foreach( $metadata as $meta )
		{
			$ok = true;
			foreach ( $values as $v ) 
			{
				if ( $meta->meta_value == $v )
				{
					$ok = false;
					break;
				}
			} 
			if ( $ok )
			{
				$delete_function( $id, $code, $meta->meta_value );
				$result = true;
			}
		}
	}	
	return $result;
}

function usam_get_metadata($meta_type, $object_id, $table, $meta_key = '', $single = false, $name_meta = 'meta') 
{ 
	if ( ! $meta_type || ! is_numeric($object_id) )
		return false;
	$object_id = absint( $object_id );
	if ( ! $object_id ) {
		return false;
	}

	$check = apply_filters( "usam_get_{$meta_type}_{$name_meta}data", null, $object_id, $meta_key, $single );
	if ( null !== $check ) 
	{
		if ( $single && is_array( $check ) )
			return $check[0];
		else
			return $check;
	}
	$meta_cache = wp_cache_get($object_id, "usam_{$meta_type}_{$name_meta}");
	if ( $meta_cache === false ) 
	{
		$cache = usam_update_cache([ $object_id ], [$table => $meta_type. '_'.$name_meta], $meta_type. '_id' );	
		if ( isset($cache[$meta_type.'_'.$name_meta]) )
			$meta_cache = $cache[$meta_type.'_'.$name_meta][$object_id];
	}	
	if ( !$meta_key )
		return $meta_cache;				
	if ( !empty($meta_cache) ) 
	{ 
		$values = [];
		foreach ( $meta_cache as $value ) 
		{
			if ( $value->meta_key == $meta_key )
				$values[] = $value;		
		}	
		if ( !empty($values) )
		{			
			if ( $single )
			{
				if ( $name_meta == 'meta' )
					return maybe_unserialize( $values[0]->meta_value );
				else
					return $values[0]->meta_value;
			}
			else
			{
				if ( $name_meta == 'meta' )
					return array_map('maybe_unserialize', $values);
				else
					return $values;
			}
		}
	}
	if ( $single )
		return false;
	else
		return [];
}

function usam_update_metadata($meta_type, $object_id, $meta_key, $meta_value, $table, $prev_value = '', $name_meta = 'meta') 
{
	global $wpdb;

	if( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) )
		return false;
	$object_id = absint( $object_id );
	if ( ! $object_id ) {
		return false;
	}
	$column = sanitize_key($meta_type . '_id');
	$id_column = 'meta_id';
	
	$raw_meta_key = $meta_key;
	$meta_key = wp_unslash($meta_key);
	$passed_value = $meta_value;
	$meta_value = wp_unslash($meta_value);
	$meta_value = sanitize_meta( $meta_key, $meta_value, $meta_type );
	
	$check = apply_filters( "usam_update_{$meta_type}_{$name_meta}data", null, $object_id, $meta_key, $meta_value, $prev_value );
	if ( null !== $check )
		return (bool) $check;

	if ( empty($prev_value) ) 
	{
		$old_value = usam_get_metadata($meta_type, $object_id, $table, $meta_key, false, $name_meta);		
		if ( count($old_value) == 1 ) 
		{
			if ( $name_meta == 'price' || $name_meta == 'stock' )
				$old_value[0]->meta_value = (float)$old_value[0]->meta_value;			
			if ( $old_value[0]->meta_value == $meta_value )
				return 2;	
		}
	}
	if ( !empty($prev_value) || count($old_value) < 1 )
	{
		$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_key = %s AND $column = %d LIMIT 1", $meta_key, $object_id ) );
		if ( empty($meta_ids) ) {
			return usam_add_metadata( $meta_type, $object_id, $raw_meta_key, $passed_value, $table );
		}
	}
	$_meta_value = $meta_value;
	if ( $name_meta == 'meta' )
	{		
		$meta_value = maybe_serialize( $meta_value );		
	}
	$data  = compact( 'meta_value' );
	$where = [$column => $object_id, 'meta_key' => $meta_key];
	if ( !empty($prev_value) ) 
	{
		if ( $name_meta == 'meta' )
			$prev_value = maybe_serialize($prev_value);
		$where['meta_value'] = $prev_value;
	}
	do_action( "usam_update_{$meta_type}_{$name_meta}", $object_id, $meta_key, $_meta_value );
	$result = $wpdb->update( $table, $data, $where );
	if ( ! $result )
		return false;

	$meta_cache = wp_cache_get($object_id, "usam_{$meta_type}_{$name_meta}");
	if ( $meta_cache !== false )
	{
		foreach ( $meta_cache as $key => $value )
		{
			if ( $value->meta_key == $meta_key )
			{
				if ( !empty($prev_value) )
				{
					if ( $meta_cache[$key]->meta_value === $prev_value )					
						$meta_cache[$key]->meta_value = $meta_value;
				}
				else
					$meta_cache[$key]->meta_value = $meta_value;
			}			
		}
	}
	else
		wp_cache_delete($object_id, "usam_{$meta_type}_{$name_meta}");
	do_action( "usam_updated_{$meta_type}_{$name_meta}", $object_id, $meta_key, $_meta_value );
	return true;
}

function usam_add_metadata($meta_type, $object_id, $meta_key, $meta_value, $table, $unique = false, $name_meta = 'meta') 
{
	global $wpdb;

	if ( ! $meta_type || ! $meta_key || ! is_numeric($object_id) ) {
		return false;
	}
	$object_id = absint( $object_id );
	if ( ! $object_id ) {
		return false;
	} 
	$column = sanitize_key($meta_type . '_id');

	// expected_slashed ($meta_key)
	$meta_key = wp_unslash($meta_key);
	$meta_value = wp_unslash($meta_value);
	$meta_value = sanitize_meta( $meta_key, $meta_value, $meta_type );

	$check = apply_filters( "usam_add_{$meta_type}_{$name_meta}data", null, $object_id, $meta_key, $meta_value, $unique );
	if ( null !== $check )
		return $check;
		
	if ( $unique && $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE meta_key = %s AND $column = %d LIMIT 1", $meta_key, $object_id ) ) )
		return false;

	$_meta_value = $meta_value;
	if ( $name_meta == 'meta' )	
		$meta_value = maybe_serialize( $meta_value );

	do_action( "usam_add_{$meta_type}_{$name_meta}", $object_id, $meta_key, $_meta_value );

	$result = $wpdb->insert( $table, [$column => $object_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value] );
	if ( ! $result )
		return false;

	$mid = (int) $wpdb->insert_id;
	wp_cache_delete($object_id, "usam_{$meta_type}_{$name_meta}");	
	do_action( "usam_added_{$meta_type}_{$name_meta}", $mid, $object_id, $meta_key, $_meta_value );
	return $mid;
}

function usam_delete_metadata($meta_type, $object_id, $meta_key, $table, $meta_value = '', $delete_all = false, $name_meta = 'meta') 
{
	global $wpdb;

	if ( ! $meta_type || ! $meta_key || ! is_numeric($object_id) && ! $delete_all ) {
		return false;
	}

	$object_id = absint( $object_id );
	if ( ! $object_id && ! $delete_all ) {
		return false;
	}
	$type_column = sanitize_key($meta_type . '_id');
	$id_column = 'meta_id';
	
	$meta_key = wp_unslash($meta_key);
	$meta_value = wp_unslash($meta_value);

	$check = apply_filters( "usam_delete_{$meta_type}_{$name_meta}data", null, $object_id, $meta_key, $meta_value, $delete_all );
	if ( null !== $check )
		return (bool) $check;

	$_meta_value = $meta_value;
	if ( $name_meta == 'meta' )	
		$meta_value = maybe_serialize( $meta_value );

	$query = $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_key = %s", $meta_key );

	if ( !$delete_all )
		$query .= $wpdb->prepare(" AND $type_column = %d", $object_id );

	if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value )
		$query .= $wpdb->prepare(" AND meta_value = %s", $meta_value );

	$meta_ids = $wpdb->get_col( $query );
	if ( !count( $meta_ids ) )
		return false;
		
	do_action( "delete_{$meta_type}_{$name_meta}", $meta_ids, $object_id, $meta_key, $_meta_value );
	$query = "DELETE FROM $table WHERE $id_column IN( " . implode( ',', $meta_ids ) . " )";
	$count = $wpdb->query($query);
	if ( !$count )
		return false;

	if ( $delete_all ) 
	{
		if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value )
			$object_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $type_column FROM $table WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ) );
		else
			$object_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $type_column FROM $table WHERE meta_key = %s", $meta_key ) );		
		foreach ( (array) $object_ids as $o_id ) {
			wp_cache_delete($o_id, 'usam_'.$meta_type . '_'.$name_meta);
		}
	} 
	else 
	{
		wp_cache_delete($object_id, 'usam_'.$meta_type . '_'.$name_meta);
	}
	do_action( "deleted_{$meta_type}_{$name_meta}", $meta_ids, $object_id, $meta_key, $_meta_value );
	return true;
}

function usam_products_metas( $product_ids ) 
{
	$results = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_STOCK_BALANCES." WHERE product_id IN (".implode(",",array_keys($update_products)).")" );	
	$product_metas = array();
	foreach ( $results as $result )			
	{
		$product_metas[$result->product_id][] = $result;
	}
	return $product_metas;
}


function update_post_meta_cache( $query ) 
{	
	if (empty($query->posts)) 
		return false;
	
	$ids = [];
	foreach($query->posts as $key => $post)
		$ids[] = $post->ID;
	return usam_update_cache( $ids, [USAM_TABLE_POST_META => 'post_meta'], 'post_id' );
}
?>