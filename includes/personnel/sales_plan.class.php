<?php
class USAM_Sales_Plan
{
	// строковые
	private static $string_cols = array(
		'period_type',		
		'from_period',		
		'to_period',		
		'target',	
		'plan_type',
		'date_insert',		
	);
	// цифровые
	private static $int_cols = array(
		'id',	
		'manager_id',	
		'sum',			
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
			$this->data = wp_cache_get( $value, 'usam_sales_plan' );
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
		wp_cache_set( $id, $this->data, 'usam_sales_plan' );	
		do_action( 'usam_sales_plan_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_sales_plan' );	
		do_action( 'usam_sales_plan_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_sales_plan_before_delete', $data );	

		$this->delete_cache( );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SALES_PLAN." WHERE id = '$id'");	
		do_action( 'usam_sales_plan_delete', $id );
	
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SALES_PLAN." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_sales_plan_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}
			$this->fetched = true;				
			$this->update_cache( );
		}		
		do_action( 'usam_sales_plan_fetched', $this );	
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
		return apply_filters( 'usam_sales_plan_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_sales_plan_get_data', $this->data, $this );
	}

	/**
	 * Устанавливает свойство до определенного значения. Эта функция принимает ключ и значение в качестве аргументов, или ассоциативный массив, содержащий пары ключ-значение.
	 * @since 4.9
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
		$properties = apply_filters( 'usam_sales_plan_set_properties', $properties, $this );			
		if ( ! is_array($this->data) )
			$this->data = array();
		$this->data = array_merge( $this->data, $properties );		
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
	 * @since 4.9
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
		do_action( 'usam_sales_plan_pre_save', $this );	
		
		$where_col = $this->args['col'];			
		$result = false;	
		if ( $where_col ) 
		{			
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_sales_plan_pre_update', $this );
			
			$this->data = apply_filters( 'usam_sales_plan_update_data', $this->data );			
			$format = $this->get_data_format(  );
			$this->data_format( );					
			$data = $this->data;
			
			$result = $wpdb->update( USAM_TABLE_SALES_PLAN, $this->data, array( $where_col => $where_val ), $format, $where_format );
			if ( $result ) 
			{
				$this->delete_cache( );				
			}
			do_action( 'usam_sales_plan_update', $this );			
		} 
		else 
		{   
			do_action( 'usam_sales_plan_pre_insert' );			
			
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );		
			$this->data['manager_id'] = get_current_user_id();		
			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );			
						
			if ( empty($this->data['period_type']) )
				$this->data['period_type'] = '';	
			
			if ( empty($this->data['from_period']) )
				$this->data['from_period'] = '';	
			
			if ( empty($this->data['to_period']) )
				$this->data['to_period'] = '';	
			
			if ( empty($this->data['plan_type']) )
				$this->data['plan_type'] = '';	

			if ( empty($this->data['target']) )
				$this->data['target'] = '';					
					
			$this->data = apply_filters( 'usam_sales_plan_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );				
								
			$result = $wpdb->insert( USAM_TABLE_SALES_PLAN, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );				
			}
			do_action( 'usam_sales_plan_insert', $this );
		} 		
		do_action( 'usam_sales_plan_save', $this );

		return $result;
	}
}

function usam_get_sales_plan( $id, $colum = 'id' )
{
	$_sales_plan = new USAM_Sales_Plan( $id, $colum );
	return $_sales_plan->get_data( );	
}

function usam_delete_sales_plan( $id ) 
{
	$_sales_plan = new USAM_Sales_Plan( $id );
	$result = $_sales_plan->delete();
	return $result;
}

// Вставить
function usam_insert_sales_plan( $data ) 
{
	$_sales_plan = new USAM_Sales_Plan( $data );
	$_sales_plan->save();
	return $_sales_plan->get('id');
}

function usam_update_sales_plan( $id, $data ) 
{
	$_sales_plan = new USAM_Sales_Plan( $id );	
	$_sales_plan->set( $data );
	return $_sales_plan->save();
}

function usam_save_sales_plan_amounts( $data ) 
{
	global $wpdb;
	
	if ( empty($data['plan_id']) )
		return false;
	
	if ( empty($data['object_id']) )
		return false;
	
	if ( empty($data['price']) )
		$data['price'] = 0;
		
	$sql = "INSERT INTO `".USAM_TABLE_PLAN_AMOUNTS."` (`plan_id`,`object_id`,`price`) VALUES ('%d','%d','%d') ON DUPLICATE KEY UPDATE `price`='%d'";		
	return $wpdb->query( $wpdb->prepare($sql, $data['plan_id'], $data['object_id'], $data['price'], $data['price'] ) );
}

function usam_delete_sales_plan_amounts( $plan_id, $object_id = null ) 
{
	global $wpdb;

	if ( empty($plan_id) )
		return false;
			
	$delete = array( 'plan_id' => $plan_id );
	$format = array('%d');
	
	if ( !empty($object_id) )
	{
		$delete['object_id'] = $object_id;
		$format[] = '%d';
	}	
	$result = $wpdb->delete( USAM_TABLE_PLAN_AMOUNTS, $delete, $format );				
	return $result;
}

function usam_get_sales_plan_amounts( $plan_id, $orderby = 'plan_id', $order = 'ASC' )
{ 
	global $wpdb;		

	$orderby = "ORDER BY $orderby";	
	$where = " WHERE plan_id = '$plan_id' ";	
	$results = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_PLAN_AMOUNTS." $where $orderby $order" );
	$amounts = array();
	if ( !empty($results) )	
		foreach( $results as $result ) 
		{ 
			$amounts[$result->object_id] = $result->price;	
		}			
	return $amounts;
}
?>