<?php
class USAM_Location
{
	private static $string_cols = array(
		'name',
		'code',		
	);
	private static $int_cols = array(
		'id',
		'parent',		
		'sort',		
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );	
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_location' );
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
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_location' );	
		do_action( 'usam_location_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_location' );	
		do_action( 'usam_location_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_location_before_delete', $data );		
		
		$locations_ids = usam_get_locations(['parent' => $id, 'fields' => 'id']);
		if ( !empty($locations_ids) )
		{
			foreach ( $locations_ids as $locations_id )
				usam_delete_location( $locations_id );
		}		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_LOCATION." WHERE id = '$id'");	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_LOCATION_META." WHERE location_id=".$id."");		
		
		$this->delete_cache( );		
		do_action( 'usam_location_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_LOCATION." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_location_data', $data );	
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}			
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_location_fetched', $this );	
		$this->fetched = true;			
	}

	/**
	 * Если строка существует в БД
	 */
	public function exists() 
	{		
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_location_get_location', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_location_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_location_set_properties', $properties, $this );
	
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
		
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_location_pre_save', $this );	
		
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_location_pre_update', $this );			

			$this->data = apply_filters( 'usam_location_update_data', $this->data );	
			if ( isset($this->data['id']) && isset($this->data['parent']) && $this->data['parent'] === $this->data['id'] )
				$this->data['parent'] = 0;
			
			$formats = $this->get_data_format( );
			$this->data_format( );	
			
			$result = $wpdb->update( USAM_TABLE_LOCATION, $this->data, [$this->args['col'] => $this->args['value']], $formats, array( $where_format ) );	
			if ( $result ) 
			{
				$this->delete_cache( );			
				update_option( 'usam_ancestors_locations', array() );
			}
			do_action( 'usam_location_update', $this );
		} 
		else 
		{   
			do_action( 'usam_location_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	

			if ( empty($this->data['name']) )
				return false;		

			if ( !isset($this->data['sort']) )
				$this->data['sort'] = 100;					
			
			$this->data = apply_filters( 'usam_location_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );	
					
			$result = $wpdb->insert( USAM_TABLE_LOCATION, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				update_option( 'usam_ancestors_locations', array() );
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );				
			}
			do_action( 'usam_location_insert', $this );
		} 		
		do_action( 'usam_location_save', $this );

		return $result;
	}
}

function usam_get_location( $id = null, $colum = 'id' )
{
	if ( $id === null )
		$id = usam_get_customer_location();	
	$location = new USAM_Location( $id, $colum );
	return $location->get_data( );	
}

function usam_delete_location( $id ) 
{
	$location = new USAM_Location( $id );
	$result = $location->delete( );
	return $result;
}

function usam_insert_location( $data ) 
{
	$location = new USAM_Location( $data );
	$location->save();
	return $location->get('id');
}

function usam_update_location( $id, $data ) 
{
	$location = new USAM_Location( $id );
	$location->set( $data );
	return $location->save();
}

function usam_get_location_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('location', $object_id, USAM_TABLE_LOCATION_META, $meta_key, $single );
}

function usam_update_location_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('location', $object_id, $meta_key, $meta_value, USAM_TABLE_LOCATION_META, $prev_value );
}

function usam_delete_location_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('location', $object_id, $meta_key, USAM_TABLE_LOCATION_META, $meta_value, $delete_all );
}
?>