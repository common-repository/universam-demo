<?php
class USAM_Product_Day
{
	 // строковые
	private static $string_cols = array(
		'dtype',	
		'status',		
		'date_insert',				
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'rule_id',				
		'product_id',
		'sort',				
	);
	// рациональные
	private static $float_cols = array(
		'discount',		
	);
    private $data     = array();		
	private $changed_data = [];	
	private $products = null;		
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];		
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_product_day' );
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
		wp_cache_set( $id, $this->data, 'usam_product_day' );		
		do_action( 'usam_product_day_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{	
		wp_cache_delete( $this->get( 'id' ), 'usam_product_day' );	
		do_action( 'usam_product_day_delete_cache', $this );	
	}	

	/**
	 *  Удалить
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_product_day_before_delete', $data );
		
		$data = $this->get_data( );	
		$this->delete_cache();			
		$result = $wpdb->query( $wpdb->prepare("DELETE FROM ".USAM_TABLE_PRODUCT_DAY." WHERE id = '%d'",$data['id']) );		
		
		if ( $data['status'] == 1 ) 	
		{		
			wp_cache_delete( 'usam_active_products_day' );	
			usam_recalculate_price_products_ids( $data['product_id'], __("Отмена Товара дня","usam" ) );			
		}
		do_action( 'usam_product_day_delete', $data['id'] );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_PRODUCT_DAY." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_product_day_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}			
		do_action( 'usam_product_day_fetched', $this );	
		$this->fetched = true;			
	}

	/**
	 * Если строка существует в БД
	 * @since 4.9
	 */
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
		return apply_filters( 'usam_product_day_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_product_day_get_data', $this->data, $this );
	}

	/**
	 * Устанавливает свойство до определенного значения. Эта функция принимает ключ и значение в качестве аргументов, или ассоциативный массив, содержащий пары ключ-значение.
	 */
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
		$properties = apply_filters( 'usam_product_day_set_properties', $properties, $this );
		if( ! is_array($this->data) )
			$this->data = array();	
					
		foreach( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{				
				$previous = $this->get( $key );			
				if ( $value != $previous )
				{				
					$this->changed_data[$key] = $previous;					
				}
				$this->data[$key] = $value;
			}
		}
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
	 */
	private function get_data_format( $data ) 
	{
		$formats = array();
		foreach( $data as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )		
				$formats[$key] = $format;
		}
		return $formats;
	}	
	
	private function get_update_data( ) 
	{		
		$data = [];		
		foreach( $this->changed_data as $key => $value ) 
		{							
			if( self::get_column_format( $key ) !== false )
				$data[$key] = $this->data[$key];
		}
		return $data;
	}	
	
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_product_day_pre_save', $this );
		$result = false;	
		if( $this->args['col'] ) 
		{			
			if ( empty($this->changed_data) )
				return true;
			
			do_action( 'usam_product_day_pre_update', $this );				
			$this->data = apply_filters( 'usam_product_day_update_data', $this->data );			
			
			$data = $this->get_update_data();	
			$where_format = self::get_column_format( $where_col );			
			$formats = $this->get_data_format( $data );			
			$result = $wpdb->update( USAM_TABLE_PRODUCT_DAY, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result )
			{		
				$this->delete_cache( );			
				if ( $this->data['status'] == 1 )
					wp_cache_delete( 'usam_active_products_day' );	
			}
			do_action( 'usam_product_day_update', $this );
		} //USAM_TABLE_PRODUCT_DAY
		else 
		{   
			do_action( 'usam_product_day_pre_insert' );				
			
			if( !isset($this->data['rule_id']) )
				return false;
			
			if ( isset($this->data['id']) )
				unset($this->data['id']);		
			
			if ( empty($this->data['status']) )
				$this->data['status'] = 0;				
			
			$this->data = apply_filters( 'usam_product_day_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );					
			$data = $this->data;	
			$result = $wpdb->insert( USAM_TABLE_PRODUCT_DAY, $this->data, $formats );					
			if ( $result ) 
			{
				if ( $this->data['status'] == 1 )
					wp_cache_delete( 'usam_active_products_day' );	
				
				$this->set( 'id', $wpdb->insert_id );					
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];				
			}
			do_action( 'usam_product_day_insert', $this );
		} 		
		do_action( 'usam_product_day_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_product_day( $id )
{
	$pday = new USAM_Product_Day( $id );
	return $pday->get_data( );	
}

// Добавить 
function usam_insert_product_day( $data )
{
	$pday = new USAM_Product_Day( $data );	
	$result = $pday->save();	
	return $result;
}

// Обновить 
function usam_update_product_day( $id, $data )
{
	$pday = new USAM_Product_Day( $id );
	$pday->set( $data );
	return $pday->save();
}

function usam_update_product_day_status( $id, $status )
{
	$pday = new USAM_Product_Day( $id );
	$update = array( 'status' => $status );
	if ( $status == 1 )
		$update['sort'] = 1;
	
	$pday->set( $update );
	return $pday->save();
}

function usam_delete_product_day( $id )
{
	$pday = new USAM_Product_Day( $id );
	return $pday->delete();
}

function usam_get_active_products_day( )
{	
	$cache_key = 'usam_active_products_day';	
	$cache = wp_cache_get( $cache_key );
	if( $cache === false )
	{		
		require_once(USAM_FILE_PATH.'/includes/product/products_day_query.class.php');
		$cache = usam_get_products_day(['status' => 1]);	
		wp_cache_set( $cache_key, $cache );
	}			
	return $cache;	
}

function usam_get_active_products_day_id_by_codeprice( $code_price = null )
{	
	$products = usam_get_active_products_day_by_codeprice( $code_price );	
	$products_id = array();
	if ( !empty($products) )
	{
		foreach( $products as $product )					
			$products_id[] = $product->product_id;	
	}	
	return $products_id;
}

function usam_get_data_active_products_day( )
{	
	$products_ids = usam_get_active_products_day_id_by_codeprice();	
	
	if ( empty($products_ids) )
		return array();
	
	$args = array( 
		'post__in' => array_values($products_ids), 
		'product_meta_cache' => true, 
		'update_post_meta_cache' => true, 
		'update_post_term_cache' => false, 
		'cache_results' => true, 
		'post_status' => 'publish',
		'in_stock' => true,
	);
	$products = usam_get_products( $args );
	return $products;
}

function usam_get_active_products_day_by_codeprice( $code_price = null, $product_id = null )
{		
	if ( $code_price == null )
		$code_price = usam_get_customer_price_code();

	$products_day = usam_get_active_products_day();
	
	$option = get_site_option('usam_product_day_rules');
	$rules = maybe_unserialize( $option );	
							
	$current_time = time();						
	$products = array();
	if ( !empty($rules) )
	{
		foreach( $rules as $rule )		
		{
			if ( usam_validate_rule( $rule ) )
			{									
				if ( in_array($code_price, $rule['type_prices']) )
				{
					foreach( $products_day as $product )
					{
						if ( $product->rule_id == $rule['id'] )
						{
							if ( $product_id != null )
							{
								if ( $product_id == $product->product_id )
									return $product;
							}
							else
								$products[] = $product;
						}
					}
				}			
			}
		}
	}
	return $products;
}



class USAM_Work_Product_Day
{	
	private $rules = array();
	private $product_ids = array();
	public function __construct( ) 
	{
		$option = get_site_option('usam_product_day_rules');
		if ( !empty($option) )
			$this->rules = maybe_unserialize( $option );
	}

	// Заполняет очередь товар дня
	public function refill_the_queue_product_day( )
	{				
		foreach( $this->rules as $rule )		
		{
			$this->refill_the_queue_processing( $rule );
		}		
	}
	// Пополнить очередь с помощью номера правила
	public function refill_the_queue_by_rule_id( $rule_id ) 
	{					
		$rule = usam_get_data($rule_id, 'usam_product_day_rules');
		$this->refill_the_queue_processing( $rule );		
	}
	
	private function refill_the_queue_processing( $rule )
	{ 
		if ( usam_validate_rule( $rule ) && $rule['refill'] )
		{					
			require_once(USAM_FILE_PATH.'/includes/product/products_day_query.class.php');
			$products_day = usam_get_products_day( array( 'rule_id' => $rule['id'], 'status' => array( 0, 1 ), 'fields' => 'product_id' ) );
			$count = count($products_day);	
			
			$number = $rule['conditions']['c'] - $count;
			if ( $number <= 0 || $rule['conditions']['value'] == 0)
				return;
			$sort = 100;
			$taxonomies = get_taxonomies(['object_type' => ['usam-product'], 'public' => true, 'show_in_rest' => true], 'objects');		
			$data = ['rule_id' => $rule['id'], 'discount' => $rule['conditions']['value'], 'dtype' => 'p'];		
			foreach( $rule['type_prices'] as $code_price )		
			{				
				$args = ['post_status' => 'publish', 'posts_per_page' => $number, 'fields' => 'ids', 'cache_product' => false];
				$args['from_stock'] = $rule['conditions']['minstock'];				
				$args['from_price'] = $rule['conditions']['pricemin'];		
				$args['type_price'] = $code_price;	
				if ( !empty($rule['conditions']['pricemax']) )
					$args['to_price'] = $rule['conditions']['pricemax'];
								
				foreach($taxonomies as $taxonomy)
					if( isset($rule['conditions'][$taxonomy->name]) )
						$args['tax_query'][] = ['taxonomy' => $taxonomy->name, 'field' => 'id', 'terms' => $rule['conditions'][$taxonomy->name]];
				if ( !empty($products_day) )
					$args['post__not_in'] = $products_day;			
				$args['orderby'] = 'rand';	
				$products_ids = usam_get_products( $args );		
				foreach( $products_ids as $product_id )	
				{				
					$data['sort'] = $sort;
					$data['product_id'] = $product_id;						
					usam_insert_product_day( $data );				
				}
			}			
		}	
	}
	
	/*	Описание: автоматическое изменения Товара дня
	*/
	public function change_product_day() 
	{	
		require_once(USAM_FILE_PATH.'/includes/product/products_day_query.class.php');
		$products_day = usam_get_products_day(['status' => [0, 1]]);	
		foreach( $products_day as $key => $product )		
		{
			if( $product->status == 1 )
			{
				$this->product_ids[]	= $product->product_id;
				usam_update_product_day_status($product->id, 2 );	
				unset($products_day[$key]);
			}			
		}								
		foreach( $this->rules as $rule )		
		{
			$this->rule_processing( $rule, $products_day );
		}	
		usam_recalculate_price_products_ids( $this->product_ids, __("Изменения Товара дня","usam" ) );
	}
	
	public function set_product_day_by_rule_id( $rule_id ) 
	{			
		require_once(USAM_FILE_PATH.'/includes/product/products_day_query.class.php');
		$rule = usam_get_data($rule_id, 'usam_product_day_rules');		
		if ( usam_validate_rule( $rule ) )
		{
			$products_day = usam_get_products_day(['rule_id' => $rule_id, 'status' => [0, 1]]);
			foreach( $products_day as $product )		
			{
				if( $product->status == 1 )
					return true;
			}		
			$this->rule_processing( $rule, $products_day );					
		}
		else
		{
			$products_day = usam_get_products_day(['rule_id' => $rule_id, 'status' => [ 1 ]]);
			foreach( $products_day as $product )		
			{				
				$this->product_ids[] = $product->product_id;
				usam_update_product_day_status($product->id, 2 );	
			}			
		}	
		usam_recalculate_price_products_ids( $this->product_ids, __("Установка Товара дня","usam" ) );			
	}
	
	private function rule_processing( $rule, $products_day ) 
	{					
		if ( usam_validate_rule( $rule ) )
		{				
			foreach( $products_day as $product )		
			{ 
				if( $product->rule_id == $rule['id'] )
				{					
					$post = get_post( $product->product_id );
					if ( $post->post_status == 'publish' && usam_product_has_stock( $product->product_id ) )
					{							
						$this->product_ids[]	= $product->product_id;
						usam_update_product_day_status($product->id, 1 );	
						break;
					}
				}			
			}	
		}			
	}
}