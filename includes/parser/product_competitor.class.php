<?php
require_once( USAM_FILE_PATH . '/includes/parser/competitor_product_price.class.php' );
class USAM_Product_Competitor
{	
	 // строковые
	private static $string_cols = [
		'title',		
		'date_insert',	
		'date_update',
		'old_price_date',
		'url',
		'thumbnail',
		'sku',		
		'status',		
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'product_id',		
		'competitor_category_id',
		'site_id',		
	];
	// рациональные
	private static $float_cols = [
		'current_price',
		'old_price',
	];	
	private $data     = [];		
	private $changed_data = [];
	private $fetched  = false;
	private $args     = array( 'col'   => '', 'value' => '' );	
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
			$this->data = wp_cache_get( $value, 'usam_product_competitor' );
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
		wp_cache_set( $id, $this->data, 'usam_product_competitor' );		
		do_action( 'usam_product_competitor_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache(  ) 
	{
		wp_cache_delete( $this->get( 'id' ), 'usam_product_competitor' );				
		do_action( 'usam_product_competitor_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_product_competitor_before_delete', $data );
		$this->delete_cache( );				
		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_COMPETITOR_PRODUCT_PRICE." WHERE competitor_product_id=$id");
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_PRODUCTS_COMPETITORS." WHERE id=$id");
		
		do_action( 'usam_product_competitor_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_PRODUCTS_COMPETITORS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_product_competitor_data', $data );			
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
		do_action( 'usam_product_competitor_fetched', $this );	
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
		return apply_filters( 'usam_product_competitor_get_property', $value, $key, $this );
	}
	
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_product_competitor_get_data', $this->data, $this );
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
		
		$properties = apply_filters( 'usam_product_competitor_set_properties', $properties, $this );	
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
			
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_product_competitor_pre_save', $this );			
		$this->data['date_update'] = date( "Y-m-d H:i:s" );		
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;
					
			$where_format = self::get_column_format( $this->args['col'] );	

			if ( isset($this->data['date_insert']) )
				unset($this->data['date_insert']);
			
			do_action( 'usam_product_competitor_pre_update', $this );		

			$this->data = apply_filters( 'usam_product_competitor_update_data', $this->data );			
			$format = $this->get_data_format();	
			
			$result = $wpdb->update( USAM_TABLE_PRODUCTS_COMPETITORS, $this->data, [$this->args['col'] => $this->args['value']], $format, $where_format );			
			if ( $result ) 
			{
				$this->delete_cache( );		
				if ( isset($this->changed_data['current_price']) && isset($this->changed_data['old_price']) && $this->changed_data['current_price'] !== $this->changed_data['old_price'] && $this->changed_data['old_price'] > 0.00 )
					do_action( 'usam_competitor_price_changed', $this->data, $this->changed_data );		
				do_action( 'usam_product_competitor_update', $this );
			}			
		} 
		else 
		{   
			do_action( 'usam_product_competitor_pre_insert' );		
			unset( $this->data['id'] );	
									
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );		
			if ( !isset($this->data['status']) )
				$this->data['status'] = 'available';			
			
			$this->data = apply_filters( 'usam_product_competitor_insert_data', $this->data );			
			$format = $this->get_data_format(  );		
											
			$result = $wpdb->insert( USAM_TABLE_PRODUCTS_COMPETITORS, $this->data, $format );					
			if ( $result ) 
			{						
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];	
				do_action( 'usam_product_competitor_insert', $this );				
			}			
		} 		
		if ( $result )
			do_action( 'usam_product_competitor_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

// Обновить
function usam_update_product_competitor( $id, $data )
{
	$product_competitor = new USAM_Product_Competitor( $id );
	$product_competitor->set( $data );
	return $product_competitor->save();
}

// Получить
function usam_get_product_competitor( $id, $colum = 'id' )
{
	$product_competitor = new USAM_Product_Competitor( $id, $colum );
	return $product_competitor->get_data( );	
}

// Добавить
function usam_insert_product_competitor( $data )
{
	$product_competitor = new USAM_Product_Competitor( $data );
	$product_competitor->save();
	return $product_competitor->get('id');
}

// Удалить
function usam_delete_product_competitor( $id )
{
	$product_competitor = new USAM_Product_Competitor( $id );
	return $product_competitor->delete();
}

function usam_insert_competitor_category_product( $data )
{
	global $wpdb;
	if ( empty($data['name']) || empty($data['category_id']) )
		return false;
	$result = $wpdb->insert( USAM_TABLE_CATEGORIES_COMPETITORS, $data, ['name' => '%s', 'category_id' => '%d']);
	return $wpdb->insert_id;
}
?>