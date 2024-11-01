<?php
class USAM_Payment_Gateway
{
	private static $string_cols = [
		'name',		
		'description',		
		'type',	
		'handler',
	];
	private static $int_cols = [
		'id',		
		'active',
		'debug',		
		'ipn',
		'bank_account_id',
		'img',
		'sort'
	];
	private static $float_cols = [];		

	private $data    = [];		
	private $fetched = false;
	private $args    = array( 'col'   => '', 'value' => '' );	
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
		if ( ! in_array($col, ['id']) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];			
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get($value, 'usam_payment_gateway');
		}			
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
	}
	
	private static function get_column_format( $col ) 
	{
		if ( in_array($col, self::$string_cols) )
			return '%s';

		if ( in_array($col, self::$int_cols) )
			return '%d';

		if ( in_array($col, self::$float_cols) )
			return '%f';
		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );
		$data = $this->get_data( );
		wp_cache_set( $id, $data, 'usam_payment_gateway' );
		do_action( 'usam_payment_gateway_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_payment_gateway' );	
		do_action( 'usam_payment_gateway_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_payment_gateway_before_delete', $data );
		$wpdb->query("DELETE FROM ".USAM_TABLE_PAYMENT_GATEWAY_META." WHERE payment_id=$id)");
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_PAYMENT_GATEWAY." WHERE id=$id");
		$this->delete_cache( );		
		do_action( 'usam_payment_gateway_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_PAYMENT_GATEWAY." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{
			$this->exists = true;
			$this->data = apply_filters( 'usam_payment_gateway_data', $data );			
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
		do_action( 'usam_payment_gateway_fetched', $this );	
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
		if ( empty( $this->data ) || ! array_key_exists($key, $this->data) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_payment_gateway_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty($this->data) )
			$this->fetch();

		return apply_filters( 'usam_payment_gateway_get_data', $this->data, $this );
	}
	
	//Устанавливает свойство до определенного значения. Эта функция принимает ключ и значение в качестве аргументов, или ассоциативный массив, содержащий пары ключ-значение.
	public function set( $key, $value = null ) 
	{		
		if ( is_array($key) ) 
			$properties = $key;
		else 
		{
			if ( is_null($value) )
				return $this;
			$properties = array( $key => $value );			
		}		
		$properties = apply_filters( 'usam_payment_gateway_set_properties', $properties, $this );
	
		if ( ! is_array($this->data) )
			$this->data = array();
		$this->data = array_merge( $this->data, $properties );			
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
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

		do_action( 'usam_payment_gateway_pre_save', $this );	
		$where_col = $this->args['col'];		
		
		$result = false;	
		if ( $where_col ) 
		{				
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_payment_gateway_pre_update', $this );	

			$this->data = apply_filters( 'usam_payment_gateway_update_data', $this->data );	
			$format = $this->get_data_format( );
			$this->data_format( );		
		
			$result = $wpdb->update( USAM_TABLE_PAYMENT_GATEWAY, $this->data, [$this->args['col'] => $where_val], $format, [$where_format] );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_payment_gateway_update', $this );
		} 
		else 
		{   
			do_action( 'usam_payment_gateway_pre_insert' );	
					
			if ( isset($this->data['id']) )
				unset($this->data['id']);				
			$this->data = apply_filters( 'usam_payment_gateway_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );		
			$result = $wpdb->insert( USAM_TABLE_PAYMENT_GATEWAY, $this->data, $format );
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );				
			}
			do_action( 'usam_payment_gateway_insert', $this );
		} 		
		do_action( 'usam_payment_gateway_save', $this );
		return $result;
	}
}

function usam_get_payment_gateway( $value, $colum = 'id' )
{	
	$class = new USAM_Payment_Gateway($value, $colum);		
	if ( $class->exists() )
	{
		$data = $class->get_data();
		if ( usam_is_multisite() && !is_main_site() )
		{
			$blog_id = get_current_blog_id();
			foreach (['name', 'description'] as $key)
			{
				$value = usam_get_payment_gateway_metadata($data['id'],  $key.'_'.$blog_id );
				if ( $value )
					$data[$key] = $value;
			}
		}
	}
	else
		return array();	
	return $data;	
}

function usam_update_payment_gateway( $id, $data )
{		
	if ( usam_is_multisite() && !is_main_site() )
	{
		$blog_id = get_current_blog_id();
		foreach (['name', 'description'] as $key)
		{
			if ( isset($data[$key]) )
			{
				usam_update_payment_gateway_metadata($id, $key.'_'.$blog_id, $data[$key] );
				unset($data[$key]);
			}
		}
	}
	if ( $data )
	{
		$class = new USAM_Payment_Gateway( $id );	
		$class->set( $data );	
		return $class->save();
	}
	else
		return true;
}

function usam_insert_payment_gateway( $value )
{	
	$payment_gateway = new USAM_Payment_Gateway( $value );	
	$payment_gateway->save();
	return $payment_gateway->get('id');		 
}

function usam_add_payment_gateway_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('payment', $object_id, $meta_key, $meta_value, USAM_TABLE_PAYMENT_GATEWAY_META, $prev_value );
}

function usam_get_payment_gateway_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('payment', $object_id, USAM_TABLE_PAYMENT_GATEWAY_META, $meta_key, $single );
}

function usam_update_payment_gateway_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('payment', $object_id, $meta_key, $meta_value, USAM_TABLE_PAYMENT_GATEWAY_META, $prev_value );
}

function usam_delete_payment_gateway_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('payment', $object_id, $meta_key, USAM_TABLE_PAYMENT_GATEWAY_META, $meta_value, $delete_all );
}


/**
 * Класс шлюза оплаты
 */
class USAM_Gateways 
{
	private static $gateways = null;

	public function __construct( )	
	{
		if ( self::$gateways === null )
			self::init();
	}
	
	public static function init( ) 
	{				
		$cart = USAM_CART::instance();	
		$selected_shipping_method = $cart->get_property( 'selected_shipping' );		
		$selected_payer = usam_get_type_payer_customer( );
		$user_id = $cart->get_property( 'user_id' );
						
		self::$gateways = usam_get_customer_payment_gateways(['user_id' => $user_id, 'shipping' => [$selected_shipping_method], 'type_payer' => $selected_payer, 'products' => $cart->get_products()]);	
		if ( !self::check_gateway_method() && !empty(self::$gateways) )
			$cart->set_properties(['selected_payment' => self::$gateways[0]->id]);		

		$thumb_ids = array();
		foreach( self::$gateways as $gateway )	
		{ 
			if ( $gateway->img )
				$thumb_ids[] = $gateway->img;
		}	
		_prime_post_caches( $thumb_ids, false, true );		
		foreach( self::$gateways as &$gateway )	
		{ 		
			$url = '';
			if ( !empty($gateway->img) )
			{  
				$image_attributes = wp_get_attachment_image_src( $gateway->img, 'thumbnail' );	
				if ( !empty($image_attributes[0]) )
					$url = $image_attributes[0];
			}
			$gateway->image = $url;
		}	
	}
	
	public static function check_gateway_method() 
	{		
		$result = false;
		$cart = USAM_CART::instance();		
		$selected_payment = $cart->get_property( 'selected_payment' );	
		if ( $selected_payment != null ) 
		{
			foreach( self::$gateways as $gateway )	
			{ 
				if ( $gateway->id == $selected_payment)
				{
					$result = true;
					break;
				}
			}			
		}
		return $result;
	}
	
	public static function get_gateways() 
	{		
		return self::$gateways;
	}
}	

function usam_get_customer_payment_gateways( $args = array() ) 
{
	$default = ['user_id' => get_current_user_id(), 'sales_area' => usam_get_customer_sales_area()];
	$args = array_merge( $default, $args );		
	$meta_query = [];
	if ( !empty($args['type_payer']) )
		$meta_query[] = ['relation' => 'OR', ['key' => 'types_payers','value' => $args['type_payer'], 'compare' => 'IN'],['key' => 'types_payers', 'compare' => "NOT EXISTS"]];		
	if ( !empty($args['shipping']) )
		$meta_query[] = ['relation' => 'OR', ['key' => 'shipping','value' => $args['shipping'], 'compare' => 'IN'],['key' => 'shipping', 'compare' => "NOT EXISTS"]];	
	if ( !empty($args['sales_area']) )
		$meta_query[] = ['relation' => 'OR', ['key' => 'sales_area','value' => $args['sales_area'], 'compare' => 'IN'],['key' => 'sales_area', 'compare' => "NOT EXISTS"]];	
	if ( !empty($args['roles']) )
		$meta_query[] = ['relation' => 'OR', ['key' => 'roles','value' => $args['roles'], 'compare' => 'IN'],['key' => 'roles', 'compare' => "NOT EXISTS"]];	
	elseif ( isset($args['user_id']) )
	{
		if ( $args['user_id'] == 0 ) 
			$meta_query[] = ['relation' => 'OR', ['key' => 'roles','value' => 'notloggedin', 'compare' => 'IN'],['key' => 'roles', 'compare' => "NOT EXISTS"]];
		else
		{
			$user = get_userdata( $args['user_id'] );					
			$meta_query[] = ['relation' => 'OR', ['key' => 'roles','value' => $user->roles, 'compare' => 'IN'],['key' => 'roles', 'compare' => "NOT EXISTS"]];
		}
	}	
	if ( !empty($args['products']) )
	{
		$unit_measure = [];
		$category = [];
		$brands = [];
		foreach( $args['products'] as $product ) 
		{
			$unit_measure[] = usam_get_product_property($product->product_id, 'unit_measure_code');	
			$category = array_merge($category, usam_get_product_term_ids( $product->product_id ) );	
			$brands = array_merge($brands, usam_get_product_term_ids( $product->product_id ) );	
		}
	}	
	$query = ['meta_query' => $meta_query, 'cache_meta' => true, 'cache' => true];
	$gateways = usam_get_payment_gateways( $query );		
	if ( $args['user_id'] )
	{
		require_once( USAM_FILE_PATH . '/includes/customer/customer_accounts_query.class.php'  );
		$accounts = usam_get_customer_accounts(['status' => 'active', 'user_id' => $args['user_id'], 'conditions' => ['key' => 'sum', 'compare' => '>', 'value' => 0]]);
	}	
	$results = array();	
	foreach( $gateways as $gateway ) 
	{				
		if ( usam_is_multisite() && !is_main_site() )
		{
			$blog_id = get_current_blog_id();
			foreach (['name', 'description'] as $key)
			{
				$value = usam_get_payment_gateway_metadata($gateway->id,  $key.'_'.$blog_id );
				if ( $value )
					$gateway->$key = $value;
			}
		}
		if ( $gateway->handler == 'customer_accounts' && empty($accounts) )
			continue;	
		$gateway_units = usam_get_array_metadata( $gateway->id, 'payment_gateway', 'units');		
		if ( $gateway_units && !array_intersect($unit_measure, $gateway_units) )
			continue;
		$gateway_category = usam_get_array_metadata( $gateway->id, 'payment_gateway', 'category');		
		if ( $gateway_category && !array_intersect($category, $gateway_category) )
			continue;	
		$gateway_brands = usam_get_array_metadata( $gateway->id, 'payment_gateway', 'brands');		
		if ( $gateway_brands && !array_intersect($brands, $gateway_brands) )
			continue;	
		$results[] = $gateway;
	}	
	return $results;
}