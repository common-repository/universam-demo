<?php
class USAM_Marking_Code
{	 
	private static $string_cols = array(	
		'code',
		'status'
	);		
	private static $int_cols = array(
		'id',	
		'product_id',
		'document_id',		
		'storage_id',			
	);
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';
			
		return false;
	}
	private $changed_data = array();	
	private $data    = array();
	private $fetched = false;	
	private $args    = array( 'col'   => '', 'value' => '' );
	private $exists  = false;
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( false === $value )
			return;

		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id' ) ) )
			return;

		$this->args = array( 'col' => $col, 'value' => $value );		
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_marking_code' );			
		}
		// кэш существует
		if ( $this->data ) 
		{	
			$this->fetched = true;
			$this->exists = true;			
		}	
		else
			$this->fetch();	
	}

	/**
	 * Обновить кеш
	 */
	public function update_cache( ) 
	{		
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_marking_code' );
		do_action( 'usam_marking_code_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_marking_code' );	
		do_action( 'usam_marking_code_update_cache', $this );
	}
	
	public function delete( ) 
	{		
		global $wpdb;
		$id = $this->get('id');		
		$data = $this->get_data();
		do_action( 'usam_marking_code_before_delete', $data );		
	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_PRODUCT_MARKING_CODES." WHERE id = '$id'");
		
		$this->delete_cache( );	
		do_action( 'usam_marking_code_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_PRODUCT_MARKING_CODES . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_marking_code_data', $data );			
			$this->update_cache( );	
		}
		do_action( 'usam_marking_code_fetched', $this );
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
		return apply_filters( 'usam_marking_code_get_property', $value, $key, $this );
	}

	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_marking_code_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_marking_code_set_properties', $properties, $this );		
		if ( ! is_array($this->data) )
			$this->data = array();
		foreach ( $properties as $key => $value ) 
		{	
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{				
				$previous = $this->get( $key );			
				if ( $value != $previous )
					$this->changed_data[$key] = $previous;	
				$this->data[$key] = $value;
			}
		}
		return $this;
	}

	/**
	 * Вернуть формат столбцов таблицы	
	 */
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

		do_action( 'usam_marking_code_pre_save', $this );		
			
		$result = false;				
		if ( $this->args['col'] ) 
		{	// обновление			
			if ( empty($this->changed_data) )
				return true;
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_marking_code_pre_update', $this );			

			$this->data = apply_filters( 'usam_marking_code_update_data', $this->data );	
			$formats = $this->get_data_format( );			
			
			$result = $wpdb->update( USAM_TABLE_PRODUCT_MARKING_CODES, $this->data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{				
				$this->update_cache( );	
				do_action( 'usam_marking_code_update', $this );					
			}
		} 
		else 
		{ 
			do_action( 'usam_marking_code_pre_insert' );	
			if ( empty($this->data['document_id']) )										
				$this->data['document_id'] = 0;
				
			if ( empty($this->data['status']) )										
				$this->data['status'] = 'available';
				
			$this->data = apply_filters( 'usam_marking_code_insert_data', $this->data );				
			$format = $this->get_data_format( );			
		
			$result = $wpdb->insert( USAM_TABLE_PRODUCT_MARKING_CODES, $this->data, $format );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = array('col' => 'id',  'value' => $this->get( 'id' ) );				
				$this->update_cache( );		
				do_action( 'usam_marking_code_insert', $this );	
			}			
		} 
		do_action( 'usam_marking_code_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_marking_code( $value, $colum = 'id' )
{	
	$marking_code = new USAM_Marking_Code($value, $colum);	
	$marking_code_data = $marking_code->get_data();	
	return $marking_code_data;	
}

function usam_update_marking_code( $id, $data )
{	
	if ( !empty($data) ) 
	{			
		$marking_code = new USAM_Marking_Code( $id );
		$marking_code->set( $data );
		return $marking_code->save();
	}
	return true;
}

function usam_insert_marking_code( $value )
{	
	$marking_code = new USAM_Marking_Code( $value );	
	$marking_code->save();
	$marking_code_id = $marking_code->get('id');	
	return $marking_code_id;		 
}

function usam_delete_marking_code( $id ) 
{
	$marking_code = new USAM_Marking_Code( $id );
	$result = $marking_code->delete( );
	return $result;
}

function usam_get_statuses_marking_code(  ) 
{
	return ['available' => __('Доступен','usam'), 'close' => __('Выведен из оборота','usam')];
}

function usam_get_marking_code_status_name( $key_status ) 
{
	$statuses = usam_get_statuses_marking_code( );	
	if ( isset($statuses[$key_status]) )
		return $statuses[$key_status];
	else
		return '';
}