<?php
class USAM_Product_Set
{	 
	private static $string_cols = [
	
	];		
	private static $int_cols = [
		'id',	
		'sort',		
		'product_id',
		'category_id',		
		'set_id',	
		'status',		
	];
	private static $float_cols = array(
		'quantity',			
	);		
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';
		if ( in_array( $col, self::$int_cols ) )
			return '%d';		
		if ( in_array( $col, self::$float_cols ) )
			return '%f';	
		return false;
	}
	private $changed_data = [];	
	private $data    = [];
	private $fetched = false;	
	private $args    = ['col'   => '', 'value' => ''];
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
		if ( ! in_array( $col, ['id'] ) )
			return;

		$this->args = ['col' => $col, 'value' => $value ];		
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_product_set' );			
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
		wp_cache_set( $id, $this->data, 'usam_product_set' );
		do_action( 'usam_product_set_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_product_set' );	
		do_action( 'usam_product_set_update_cache', $this );
	}
	
	public function delete( ) 
	{		
		global $wpdb;
		$id = $this->get('id');		
		$data = $this->get_data();
		do_action( 'usam_product_set_before_delete', $data );		
	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_PRODUCTS_SETS." WHERE id = '$id'");
		
		$this->delete_cache( );	
		do_action( 'usam_product_set_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_PRODUCTS_SETS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_product_set_data', $data );	
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}			
			$this->update_cache( );	
		}
		do_action( 'usam_product_set_fetched', $this );
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
		return apply_filters( 'usam_product_set_get_property', $value, $key, $this );
	}

	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_product_set_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_set_product_set_properties', $properties, $this );		
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

		do_action( 'usam_product_set_pre_save', $this );		
			
		$result = false;				
		if ( $this->args['col'] ) 
		{	// обновление			
			if ( empty($this->changed_data) )
				return true;
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_product_set_pre_update', $this );

			$this->data = apply_filters( 'usam_product_set_update_data', $this->data );	
			$formats = $this->get_data_format( );			
			
			$result = $wpdb->update( USAM_TABLE_PRODUCTS_SETS, $this->data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{				
				$this->update_cache( );	
				do_action( 'usam_product_set_update', $this );					
			}
		} 
		else 
		{ 
			do_action( 'usam_product_set_pre_insert' );	
			
			if ( empty($this->data['status']) )										
				$this->data['status'] = 'draft';
				
			$this->data = apply_filters( 'usam_product_set_insert_data', $this->data );				
			$format = $this->get_data_format( );			
		
			$result = $wpdb->insert( USAM_TABLE_PRODUCTS_SETS, $this->data, $format );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = ['col' => 'id',  'value' => $this->get( 'id' )];				
				$this->update_cache( );		
				do_action( 'usam_product_set_insert', $this );	
			}			
		} 
		do_action( 'usam_product_set_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_product_set( $value, $colum = 'id' )
{	
	$set = new USAM_Product_Set($value, $colum);	
	$set_data = $set->get_data();	
	return $set_data;	
}

function usam_update_product_set( $id, $data )
{	
	if ( !empty($data) ) 
	{			
		$set = new USAM_Product_Set( $id );
		$set->set( $data );
		return $set->save();
	}
	return true;
}

function usam_insert_product_set( $value )
{	
	$set = new USAM_Product_Set( $value );	
	$set->save();
	$set_id = $set->get('id');	
	return $set_id;		 
}

function usam_delete_product_set( $id ) 
{
	$set = new USAM_Product_Set( $id );
	$result = $set->delete( );
	return $result;
}