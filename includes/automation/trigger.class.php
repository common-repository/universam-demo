<?php
class USAM_Trigger
{
	private static $string_cols = [
		'title',		
		'description',	
		'code',	
		'event',	
		'conditions',
	];
	private static $int_cols = [
		'id',				
		'sort',	
		'active',	
	];	
	private $data = [];		
	private $changed_data = [];
	private $fetched = false;
	private $args = ['col' => '', 'value' => ''];	
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
					
		$this->args = ['col' => $col, 'value' => $value];			
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_trigger' );
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
		wp_cache_set( $id, $this->data, 'usam_trigger' );		
		do_action( 'usam_trigger_update_cache', $this );
	}
	
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_trigger' );	
		do_action( 'usam_trigger_delete_cache', $this );	
	}

	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_trigger_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_TRIGGERS." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_trigger_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_TRIGGERS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_trigger_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_trigger_fetched', $this );	
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
		return apply_filters( 'usam_trigger_get_trigger', $value, $key, $this );
	}
	
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_trigger_get_data', $this->data, $this );
	}

	
	public function set( $key, $value = null ) 
	{		
		if ( is_array($key) ) 
			$properties = $key;
		else 
		{
			if ( is_null($value) )
				return $this;
			$properties = [$key => $value];			
		}			
		if ( ! is_array($this->data) )
			$this->data = array();	
		
		$properties = apply_filters( 'usam_trigger_set_properties', $properties, $this );
		foreach( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{
				$previous = $this->get( $key );	
				$this->data[$key] = $value;						
				if ( $value != $previous )
					$this->changed_data[$key] = $previous;	
			}
		}
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
		
	public function save()
	{
		global $wpdb;

		do_action( 'usam_trigger_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_trigger_pre_update', $this );		
			
			$this->data = apply_filters( 'usam_trigger_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$data = $this->data;
			
			$result = $wpdb->update( USAM_TABLE_TRIGGERS, $this->data, [$this->args['col'] => $this->args['value']], $formats, [ $where_format ]);	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_trigger_update', $this );
		} 
		else 
		{   
			do_action( 'usam_trigger_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			if ( empty($this->data['title']) )							
				$this->data['code'] = sanitize_title($this->data['title']);
						
			$this->data = apply_filters( 'usam_trigger_insert_data', $this->data );
			$format = $this->get_data_format();
										
			$result = $wpdb->insert( USAM_TABLE_TRIGGERS, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];				
			}
			do_action( 'usam_trigger_insert', $this );
		} 		
		do_action( 'usam_trigger_save', $this );

		return $result;
	}
}

function usam_get_trigger( $id, $colum = 'id' )
{ 
	$trigger = new USAM_Trigger( $id, $colum );
	return $trigger->get_data( );	
}

function usam_delete_trigger( $id ) 
{
	$trigger = new USAM_Trigger( $id );
	$result = $trigger->delete( );
	return $result;
}

function usam_insert_trigger( $data ) 
{
	$trigger = new USAM_Trigger( $data );
	$trigger->save();
	return $trigger->get('id');
}

function usam_update_trigger( $id, $data ) 
{
	$trigger = new USAM_Trigger( $id );
	$trigger->set( $data );
	return $trigger->save();
}

function usam_get_trigger_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('trigger', $object_id, USAM_TABLE_TRIGGER_META, $meta_key, $single );
}

function usam_update_trigger_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('trigger', $object_id, $meta_key, $meta_value, USAM_TABLE_TRIGGER_META, $prev_value );
}

function usam_delete_trigger_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('trigger', $object_id, $meta_key, USAM_TABLE_TRIGGER_META, $meta_value, $delete_all );
}

function usam_add_trigger_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('trigger', $object_id, $meta_key, $meta_value, USAM_TABLE_TRIGGER_META, $prev_value );
}
?>