<?php
class USAM_Property_Group
{
	private static $string_cols = array(
		'name',	
		'code',		
		'type',	
	);
	private static $int_cols = array(
		'id',			
		'group',				
		'sort',		
		'parent_id',
		'system',
		
	);	
	private $data = array();		
	private $fetched           = false;
	private $args = array( 'col'   => '', 'value' => '' );	
	private $exists = false; // если существует строка в БД
		
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id', 'code' ) ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );	
		if ( $col == 'code'  && $id = wp_cache_get( $value, 'usam_property_group_code' ) )
		{   
			$col = 'id';
			$value = $id;			
		}		
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_property_group' );
		}			
		// кэш существует
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
	}
	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';

		return false;
	}
	
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_property_group' );	
		if ( $code = $this->get( 'code' ) )
			wp_cache_set( $code, $id, 'usam_property_group_code' );
		do_action( 'usam_property_group_update_cache', $this );
	}
	
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_property_group' );	
		wp_cache_delete( $this->get( 'code' ), 'usam_property_group_code' );	
		do_action( 'usam_property_group_delete_cache', $this );	
	}

	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_property_group_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".usam_get_table_db('property_groups')." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_property_group_delete', $id );
		
		return $result;
	}		
	
	/**
	 * Выбирает фактические записи из базы данных
	 */
	private function fetch() 
	{
		global $wpdb;
		if ( $this->fetched )
			return;
		
		if ( ! $this->args['col'] || ! $this->args['value'] )
			return;

		extract( $this->args );

		$format = self::get_column_format( $col );
		$sql = $wpdb->prepare( "SELECT * FROM ".usam_get_table_db('property_groups')." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_property_group_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_property_group_fetched', $this );	
		$this->fetched = true;			
	}
	
	public function exists() 
	{		
		$this->fetch();
		return $this->exists;
	}
	
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_property_group_get_property_group', $value, $key, $this );
	}
	
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_property_group_get_data', $this->data, $this );
	}

	
	public function set( $key, $value = null ) 
	{		
		if ( is_array( $key ) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = array( $key => $value );			
		}		
		$properties = apply_filters( 'usam_property_group_set_properties', $properties, $this );
	
		if ( ! is_array($this->data) )
			$this->data = array();
		$this->data = array_merge( $this->data, $properties );			
		return $this;
	}
	
	private function get_data_format( ) 
	{
		$formats = array();
		foreach ( $this->data as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )		
				$formats[$key] = $format;	
			else
				unset($this->data[$key]);
		}
		return $formats;
	}	
	
	private function data_format( ) 
	{
		foreach ( $this->data as $key => $value ) 
		{			
			if ( in_array( $key, self::$string_cols ) && !is_array($value) )
				$this->data[$key] = stripcslashes($value);
		}		
	}
	
	public function save()
	{
		global $wpdb;

		do_action( 'usam_property_group_pre_save', $this );	
		$where_col = $this->args['col'];
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_property_group_pre_update', $this );			
			$where = array( $this->args['col'] => $where_val);

			$this->data = apply_filters( 'usam_property_group_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$this->data_format( );		
			$data = $this->data;
			
			$result = $wpdb->update( usam_get_table_db('property_groups'), $this->data, $where, $formats, array( $where_format ) );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_property_group_update', $this );
		} 
		else 
		{   
			do_action( 'usam_property_group_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			if ( empty($this->data['code']) && !empty($this->data['name']) )
				$this->data['code'] = sanitize_title($this->data['name']);
			
			$this->data = apply_filters( 'usam_property_group_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );	
					
			$result = $wpdb->insert( usam_get_table_db('property_groups'), $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );		
				do_action( 'usam_property_group_insert', $this );				
			}			
		} 		
		do_action( 'usam_property_group_save', $this );

		return $result;
	}
}

function usam_get_property_group( $id, $colum = 'id' )
{ 
	$property_group = new USAM_Property_Group( $id, $colum );
	return $property_group->get_data( );	
}

function usam_delete_property_group( $id ) 
{
	$property_group = new USAM_Property_Group( $id );
	$result = $property_group->delete( );
	return $result;
}

function usam_insert_property_group( $data ) 
{
	$property_group = new USAM_Property_Group( $data );
	$property_group->save();
	return $property_group->get('id');
}

function usam_update_property_group( $id, $data ) 
{
	$property_group = new USAM_Property_Group( $id );
	$property_group->set( $data );
	return $property_group->save();
}

function usam_get_property_group_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('group', $object_id, usam_get_table_db('property_group_meta'), $meta_key, $single );
}

function usam_update_property_group_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('group', $object_id, $meta_key, $meta_value, usam_get_table_db('property_group_meta'), $prev_value );
}

function usam_delete_property_group_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('group', $object_id, $meta_key, usam_get_table_db('property_group_meta'), $meta_value, $delete_all );
}

function usam_add_property_group_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('group', $object_id, $meta_key, $meta_value, usam_get_table_db('property_group_meta'), $prev_value );
}
?>