<?php
class USAM_Competitor_Product_Price
{	
	 // строковые
	private static $string_cols = array(	
		'date_insert',			
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'competitor_product_id',
	);
	// рациональные
	private static $float_cols = ['price'];	
	private $data     = array();		
	private $fetched  = false;
	private $args     = ['col'   => '', 'value' => ''];	
	private $exists   = false; // если существует строка в БД
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id', ) ) )
			return;		
					
		$this->args = array( 'col' => $col, 'value' => $value );		
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_competitor_product_price' );
		}		
		// кэш существует
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
		else
			$this->fetch();
	}
	
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
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );	
		wp_cache_set( $id, $this->data, 'usam_competitor_product_price' );		
		do_action( 'usam_competitor_product_price_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache(  ) 
	{
		wp_cache_delete( $this->get( 'id' ), 'usam_competitor_product_price' );	
		do_action( 'usam_competitor_product_price_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_competitor_product_price_before_delete', $data );		
		
		$this->delete_cache( );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_COMPETITOR_PRODUCT_PRICE." WHERE id=$id");
		
		do_action( 'usam_competitor_product_price_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_COMPETITOR_PRODUCT_PRICE." WHERE {$col} = {$format} ORDER BY id DESC LIMIT 1", $value );
		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_competitor_product_price_data', $data );					
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}
			$this->fetched = true;				
			$this->update_cache( );		
		}			
		do_action( 'usam_competitor_product_price_fetched', $this );	
		$this->fetched = true;			
	}

	public function exists() 
	{		
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства
	 * @since 4.9
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_competitor_product_price_get_property', $value, $key, $this );
	}

	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_competitor_product_price_get_data', $this->data, $this );
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
		if ( ! is_array($this->data) )
			$this->data = array();	
		
		foreach ( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
				$this->data[$key] = $value;
		}
		$this->data = apply_filters( 'usam_competitor_product_price_set_properties', $this->data, $this );			
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

		do_action( 'usam_competitor_product_price_pre_save', $this );	
		$where_col = $this->args['col'];		
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];			
			$where_format = self::get_column_format( $where_col );	

			if ( isset($this->data['date_insert']) )
				unset($this->data['date_insert']);
			
			do_action( 'usam_competitor_product_price_pre_update', $this );				
			$where = array( $where_col => $where_val );			

			$this->data = apply_filters( 'usam_competitor_product_price_update_data', $this->data );			
			$format = $this->get_data_format( );	
			$this->data_format( );		
			
			$result = $wpdb->update( USAM_TABLE_COMPETITOR_PRODUCT_PRICE, $this->data, $where, $format, $where_format );			
			if ( $result ) 
				$this->delete_cache( );						
			do_action( 'usam_competitor_product_price_update', $this );
		} 
		else 
		{   
			do_action( 'usam_competitor_product_price_pre_insert' );		
			unset( $this->data['id'] );	
									
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );					
			
			$this->data = apply_filters( 'usam_competitor_product_price_insert_data', $this->data );			
			$format = $this->get_data_format();	

			if ( empty($this->data['competitor_product_id']) )
				return false;					
					
			$this->data_format( );							
			$result = $wpdb->insert( USAM_TABLE_COMPETITOR_PRODUCT_PRICE, $this->data, $format );
					
			if ( $result ) 
			{						
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];
				$this->exists = true;
			}
			do_action( 'usam_competitor_product_price_insert', $this );
		} 		
		do_action( 'usam_competitor_product_price_save', $this );

		return $result;
	}
}

// Обновить
function usam_update_competitor_product_price( $id, $data )
{
	$competitor_product_price = new USAM_Competitor_Product_Price( $id );
	$competitor_product_price->set( $data );
	return $competitor_product_price->save();
}

// Получить
function usam_get_competitor_product_price( $id, $colum = 'id' )
{
	$competitor_product_price = new USAM_Competitor_Product_Price( $id, $colum );
	return $competitor_product_price->get_data( );	
}

// Добавить
function usam_insert_competitor_product_price( $data )
{
	$competitor_product_price = new USAM_Competitor_Product_Price( $data );
	$competitor_product_price->save();
	return $competitor_product_price->get('id');
}

// Удалить
function usam_delete_competitor_product_price( $id )
{
	$competitor_product_price = new USAM_Competitor_Product_Price( $id );
	return $competitor_product_price->delete();
}
?>