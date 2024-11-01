<?php
function usam_delete_data( $id, $option_key, $main = true ) 
{		
	if ( !is_array($id) )
	{
		$ids = array( $id );
	}
	else
		$ids = $id;
		
	if ( $main )
	{
		$option = get_site_option($option_key, array() );						
		$array = maybe_unserialize($option);	
	}
	else
	{
		$option = get_option($option_key, array() );						
		$array = maybe_unserialize($option);
	}

	$ids = array_map( 'intval', $ids );
	$result = false;	

	foreach( $array as $key => $value )
	{				
		if( in_array( $value['id'], $ids) )
		{ 
			do_action( $option_key.'-delete', $value );
			unset($array[$key]);
			$result = true;
		}
	} 
	if ( $result )
	{
		if ( $main )
			$result = update_site_option($option_key, maybe_serialize($array) );
		else
			$result = update_option($option_key, maybe_serialize($array) );
	}
}

function usam_add_data( $data, $option_key, $main = true ) 
{	
	if ( $main )
	{
		$option = get_site_option($option_key, [] );						
		$array = maybe_unserialize($option);	
	}
	else
	{
		$option = get_option($option_key, [] );						
		$array = maybe_unserialize($option);
	}	
	if ( !empty($array) && is_array($array) )
	{	
		usort($array, function($a, $b){  return ($b['id'] - $a['id']); });		
		$id = $array[0]['id'];
		$id++;				
	}
	else
	{
		$array = [];
		$id = 1;
	}
	$data['id'] = $id;
	$array[] = $data;	
	if ( $main )
		$result = update_site_option($option_key, maybe_serialize($array) );
	else
		$result = update_option($option_key, maybe_serialize($array) );		
	return $id;
}

function usam_edit_data( $update, $id, $option_key, $main = true ) 
{	
	if ( empty($update) )
		return false;
	
	if ( $main )
	{
		$option = get_site_option($option_key, array() );						
		$array = maybe_unserialize($option);	
	}
	else
	{
		$option = get_option($option_key, array() );						
		$array = maybe_unserialize($option);
	}
	if ( empty($array) )
		return false;
	
	$result = false;
	foreach ( $array as $key => $data ) 
	{
		if ( $data['id'] == $id )	
		{
			$result = true;
			$array[$key] = array_merge($array[$key], $update );		
			break;
		}
	}
	if ( $result )
	{
		if ( $main )
			$result = update_site_option($option_key, maybe_serialize($array) );
		else
			$result = update_option($option_key, maybe_serialize($array) );
		if ( $result )
			do_action( $option_key.'-update', $id, $update );
	}
	return $result;
}

function usam_get_data( $id, $option_key, $main = true ) 
{	
	if ( $main )
	{
		$option = get_site_option($option_key, array() );						
		$array = maybe_unserialize($option);	
	}
	else
	{
		$option = get_option($option_key, array() );						
		$array = maybe_unserialize($option);
	}
	$result = array();
	foreach ( $array as $key => $data ) 
	{
		if ( $data['id'] == $id )	
		{
			$result = $data;
			break;
		}
	}
	return $result;
}
?>