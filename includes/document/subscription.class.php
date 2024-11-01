<?php
require_once( USAM_FILE_PATH . '/includes/document/subscription_renewal.class.php'  );
class USAM_Subscription
{
	// строковые
	private static $string_cols = array(
		'status',		
		'customer_type',	
		'date_insert',
		'start_date',		
		'end_date',	
		'type_price',		
	);
	// цифровые
	private static $int_cols = array(
		'id',
		'customer_id',
		'manager_id',
		'number_products',		
	);	
	// рациональные
	private static $float_cols = ['totalprice'];
	private $data = [];		
	private $changed_data = [];	
	private $fetched = false;
	private $args = ['col'   => '', 'value' => ''];	
	private $exists = false; // если существует строка в БД
	private $product_db_format = ['id' => '%d', 'product_id' => '%d', 'name' => '%s', 'subscription_id' => '%d', 'price' => '%f', 'old_price' => '%f', 'quantity' => '%f', 'unit_measure' => '%s', 'date_insert' => '%s'];
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, ['id', 'code'] ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );	
		if ( $col == 'code'  && $id = wp_cache_get( $value, 'usam_subscription_code' ) )
		{   
			$col = 'id';
			$value = $id;			
		}		
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_subscription' );
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
		wp_cache_set( $id, $this->data, 'usam_subscription' );	
		if ( $code = $this->get( 'code' ) )
			wp_cache_set( $code, $id, 'usam_subscription_code' );
		do_action( 'usam_subscription_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_subscription' );	
		wp_cache_delete( $this->get( 'code' ), 'usam_subscription_code' );	
		do_action( 'usam_subscription_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_subscription_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SUBSCRIPTIONS." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_subscription_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SUBSCRIPTIONS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_subscription_data', $data );			
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
		do_action( 'usam_subscription_fetched', $this );	
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
		return apply_filters( 'usam_subscription_get_subscription', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_subscription_get_data', $this->data, $this );
	}
	
	private function product_key_cleaning( $product )
	{	
		foreach ( $product as $key => $value ) 
		{						
			if ( !isset($this->product_db_format[$key]) )		
				unset($product[$key]);				
		}
		return $product;
	}
	
	// Формат таблицы товаров заказа
	private function get_data_format_product( $data )
	{		
		$formats = array();		
		foreach ( $data as $key => $value ) 
		{						
			if ( isset($this->product_db_format[$key]) )		
				$formats[$key] = $this->product_db_format[$key];				
		}
		return $formats;
	}
	
	public function set_products( $products )
	{	
		$id = $this->get( 'id' );		
		$subscription_products = usam_get_products_subscription( $id );
		$add_products = [];
		$update_products = [];
		$processed = [];
		$result = false;	
		foreach ( $products as $new_product ) 
		{
			if ( !is_array($new_product) )
				$new_product = (array)$new_product;			
			$add = true;
			if ( empty($new_product['unit_measure']) )
				$new_product['unit_measure'] = usam_get_product_property($new_product['product_id'], 'unit_measure_code');
			foreach ( $subscription_products as $k => $product ) 
			{
				if ( isset($new_product['id']) && ctype_digit($new_product['id']) || $product->product_id == $new_product['product_id'] && $product->unit_measure == $new_product['unit_measure'] )
				{
					$new_product['id'] = $product->id;
					$update_products[] = $new_product;					
					unset($subscription_products[$k]);
					$add = false;
					break;
				}
			}
			if ( $add && ( empty($processed[$new_product['product_id']]) || !in_array($new_product['unit_measure'], $processed[$new_product['product_id']]) ))
				$add_products[] = $new_product;
			$processed[$new_product['product_id']][] = $new_product['unit_measure'];
		}					
		if ( $this->add_products( $add_products, false ) )
			$result = true;	
		if ( $this->update_products( $update_products, false ) )
			$result = true;
		if ( $this->delete_products( $subscription_products, false ) )
			$result = true;		
		if ( $result )
			$this->calculate_totalprice();	
		return $result;
	}	
	
	// Изменить товары
	public function update_products( $products, $calculate = true ) 
	{	
		global $wpdb;		
		
		$subscription_id = $this->get('id');
		if ( empty($subscription_id) )
			return false;
		
		$products = apply_filters( 'usam_edit_subscription_products_set_properties', $products, $this );			
		do_action( 'usam_edit_subscription_products_pre_edit', $this );
		$i = 0;		
		foreach( $products as $product )
		{				
			if( isset($product['subscription_id']) )
				unset($product['subscription_id']);
			if ( isset($product['price']) )
				$product['price'] = usam_round_price( $product['price'], $this->get('type_price') );
			if ( isset($product['old_price']) )
				$product['old_price'] = usam_round_price( $product['old_price'], $this->get('type_price') );			
			$format = $this->get_data_format_product( $product );			
			$where = ['id' => $product['id'], 'subscription_id' => $subscription_id];	
			$product = $this->product_key_cleaning( $product );
			if ( $wpdb->update( USAM_TABLE_SUBSCRIPTION_PRODUCTS, $product, $where, $format, $this->get_data_format_product( $where ) ) )
			{
				$i++;
			}			
		}			
		if ( $calculate )
			$this->calculate_totalprice();	
		
		do_action( 'usam_subscription_products_edit', $this );
		return $i;
	}	

	public function add_products( $products, $calculate = true ) 
	{			
		global $wpdb;
		$i = 0;
		$subscription_id = $this->get('id');						
		foreach ( $products as $product ) 
		{
			$product = (array)$product;		
			$product['subscription_id'] = $subscription_id;
			if ( empty($product['unit_measure']) )
				$product['unit_measure'] = usam_get_product_property($product['product_id'], 'unit_measure_code');			
			if ( empty($product['quantity']) )
				$product['quantity'] = 1;				
			$type_price = $this->get('type_price');
			if ( !isset($product['old_price']) && isset($product['price']) )
				$product['old_price'] =	$product['price'];
			if ( !empty($type_price) )
			{
				if ( !isset($product['price']) )
					$product['price'] = usam_get_product_price( $product['product_id'], $type_price );
				if ( !isset($product['old_price']) )
					$product['old_price'] = usam_get_product_old_price( $product['product_id'], $type_price );
			}									
			if ( isset($product['id']) )
				unset($product['id']);						
			$product = $this->product_key_cleaning( $product );
			$format = $this->get_data_format_product( $product );			
			if ( $wpdb->insert( USAM_TABLE_SUBSCRIPTION_PRODUCTS, $product, $format ) ) 
			{
				$i++;
			}
		}
		if ( $calculate && $i ) 
			$this->calculate_totalprice( );	
		return $i;
	}
			
	// Удалить товар
	public function delete_products( $products, $calculate = true ) 
	{		
		global $wpdb;	
		
		$id = $this->get('id');
		if ( $this->exists == 0 || empty($products) )
			return false;
		
		$ids = [];
		foreach( $products as $product )
		{
			$ids[] = $product->id;		
		}
		$result_deleted = $wpdb->query($wpdb->prepare("DELETE FROM `".USAM_TABLE_SUBSCRIPTION_PRODUCTS."` WHERE `subscription_id` = '%d' AND `id` IN (".implode(',',$ids).")", $id));	
		if ( $result_deleted )
		{
			$this->delete_cache( );	
			if ( $calculate )
				$this->calculate_totalprice( );		
		}		
		return $result_deleted;
	}		
	
	public function calculate_totalprice( ) 
	{
		$subscription_id = $this->get( 'id' );
		wp_cache_delete( $subscription_id, 'usam_subscription_products' );	
						
		$new_totalprice = 0;
		$products = usam_get_products_subscription( $subscription_id );		
		foreach( $products as $product )
		{					
			$new_totalprice += $product->price*$product->quantity;			
		}			
		$totalprice = $this->get( 'totalprice' );	
		$setting_price = usam_get_setting_price_by_code( $this->data['type_price'] );
		if ( isset($setting_price['rounding']) )
			$new_totalprice = round($new_totalprice, $setting_price['rounding']);	
		
		$this->set(['totalprice' => $new_totalprice, 'number_products' => count($products)]);
		$this->save();
	}

	
	public function set( $key, $value = null ) 
	{		
		if ( is_array( $key ) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = [$key => $value];			
		}		
		$properties = apply_filters( 'usam_subscription_set_properties', $properties, $this );	
		if ( ! is_array($this->data) )
			$this->data = [];
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
	 * Возвращает массив, содержащий отформатированные параметры
	 */
	private function get_data_format( $data ) 
	{
		$formats = array();
		foreach ( $data as $key => $value ) 
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

		do_action( 'usam_subscription_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{				
			if ( empty($this->changed_data) )
				return true;
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_subscription_pre_update', $this );		
			
			$this->data = apply_filters( 'usam_subscription_update_data', $this->data );	
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_SUBSCRIPTIONS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{ 
				foreach( $this->changed_data as $key => $value ) 
				{ 
					if ( isset($this->data[$key]) )
						$this->change_history( 'edit', $key, $this->data[$key], $value );
				}
				$this->delete_cache( );			
				do_action( 'usam_subscription_update', $this );
			}
		} 
		else 
		{   
			do_action( 'usam_subscription_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			if ( empty($this->data['start_date']) )
				$this->data['start_date'] = date("Y-m-d H:i:s");			
			
			if ( empty($this->data['status']) )
				$this->data['status'] = 'not_signed';	
			
			if ( empty($this->data['type_price']) )
				$this->data['type_price'] = usam_get_manager_type_price();						
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );	
			$this->data = apply_filters( 'usam_subscription_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );					
			$result = $wpdb->insert( USAM_TABLE_SUBSCRIPTIONS, $this->data, $formats ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];				
				$this->exists = true;
				do_action( 'usam_subscription_insert', $this );
			}			
		} 		
		if ( isset($this->changed_data['status']) && !empty($this->data['totalprice']) && isset($this->data['status']) && $this->data['status'] == 'signed' )
		{
			$id = $this->get('id');
			usam_insert_subscription_renewal(['status' => 'paid', 'subscription_id' => $id, 'sum' => $this->data['totalprice'], 'start_date' => $this->data['start_date'], 'end_date' => $this->data['end_date']]);	
		}	
		$this->changed_data = [];
		do_action( 'usam_subscription_save', $this );
		return $result;
	}
	
	private function change_history( $operation, $field = '', $value = '', $old_value = '' ) 
	{ 	
		usam_insert_change_history(['object_id' => $this->data['id'], 'object_type' => 'subscription', 'operation' => $operation, 'field' => $field, 'value' => $value, 'old_value' => $old_value]);
	}
}

function usam_get_subscription( $id, $colum = 'id' )
{ 
	$subscription = new USAM_Subscription( $id, $colum );
	return $subscription->get_data();	
}

function usam_delete_subscription( $id ) 
{
	$subscription = new USAM_Subscription( $id );
	$result = $subscription->delete( );
	return $result;
}

function usam_insert_subscription( $data, $products = null ) 
{
	$subscription = new USAM_Subscription( $data );
	$id = false;
	if( $subscription->save() )	
	{
		$id = $subscription->get('id');
		if ( is_array($products) )
			$subscription->set_products( $products );
	}
	return $id;
}

function usam_update_subscription( $id, $data, $products = null ) 
{	
	$result = false;
	$subscription = new USAM_Subscription( $id );	
	if ( $data )
	{
		$subscription->set( $data );
		$result = $subscription->save();
	}
	if( $subscription->exists() )	
	{
		if ( is_array($products) )
		{
			if ( $subscription->set_products( $products ) )
				$result = true;
		}
	}
	return $result;
}

function usam_get_products_subscription( $id )
{		
	if ( empty($id) )
		return array();
	
	$object_type = "usam_subscription_products";
	$cache = wp_cache_get( $id, $object_type );		
	if ( $cache === false )		
	{			
		require_once( USAM_FILE_PATH . '/includes/document/products_subscription_query.class.php' );	
		$cache = usam_get_products_subscription_query(['subscription_id' => $id]);		
		if ( empty($cache))
			$cache = array();			
		wp_cache_set( $id, $cache, $object_type );	
	}
	return $cache;
}


function usam_get_subscription_statuses(  ) 
{
	return ['not_signed' => __('Не подписан','usam'), 'signed' => __('Подписан','usam'), 'from_signed' => __('Отписался','usam')];
}

function usam_get_status_name_subscription( $status )
{	
	$statuses = usam_get_subscription_statuses( );
	if ( isset($statuses[$status]) )
		return $statuses[$status];
	return '';
}

function usam_add_subscription_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('subscription', $object_id, $meta_key, $meta_value, USAM_TABLE_SUBSCRIPTION_META, $prev_value );
}

function usam_get_subscription_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('subscription', $object_id, USAM_TABLE_SUBSCRIPTION_META, $meta_key, $single );
}

function usam_update_subscription_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('subscription', $object_id, $meta_key, $meta_value, USAM_TABLE_SUBSCRIPTION_META, $prev_value );
}

function usam_delete_subscription_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('subscription', $object_id, $meta_key, USAM_TABLE_SUBSCRIPTION_META, $meta_value, $delete_all );
}
?>