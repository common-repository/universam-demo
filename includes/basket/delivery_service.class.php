<?php
/**
 * Работа с сервисами доставки
 */
class USAM_Delivery_Service
{
	private static $string_cols = array(
		'name',		
		'description',
		'period_type',		
		'handler',				
	);
	private static $int_cols = array(
		'id',		
		'storage_id',
		'tax_id',		
		'courier_company',
		'include_in_cost',
		'delivery_option',
		'active',
		'period_from',
		'period_to',
		'img',
		'sort',
	);	
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );		
		if ( $col == 'id' )
			$this->data = wp_cache_get( $value, 'usam_delivery_service' );
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
		$data = $this->get_data( );
		wp_cache_set( $id, $data, 'usam_delivery_service' );		
		do_action( 'usam_delivery_service_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_delivery_service' );	
		do_action( 'usam_delivery_service_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_delivery_service_before_delete', $data );		
		$wpdb->query("DELETE FROM ".USAM_TABLE_DELIVERY_SERVICE_META." WHERE delivery_id=$id)");
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_DELIVERY_SERVICE." WHERE id=$id");
		$this->delete_cache( );		
		do_action( 'usam_delivery_service_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_DELIVERY_SERVICE." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{
			$this->exists = true;
			$this->data = apply_filters( 'usam_delivery_service_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache( );
		}		
		do_action( 'usam_delivery_service_fetched', $this );	
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
		return apply_filters( 'usam_delivery_service_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty($this->data) )
			$this->fetch();

		return apply_filters( 'usam_delivery_service_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_delivery_service_set_properties', $properties, $this );
	
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
	
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_delivery_service_pre_save', $this );	
		
		$result = false;	
		if ( $this->args['col'] ) 
		{				
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_delivery_service_pre_update', $this );	

			$this->data = apply_filters( 'usam_delivery_service_update_data', $this->data );	
			$format = $this->get_data_format();
			$result = $wpdb->update( USAM_TABLE_DELIVERY_SERVICE, $this->data, [$this->args['col'] => $this->args['value']], $format, [$where_format] );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_delivery_service_update', $this );
		} 
		else 
		{   
			do_action( 'usam_delivery_service_pre_insert' );	
			
			if ( empty($this->data['delivery_service_type']))
				$this->data['delivery_service_type'] = 'delivery_service';	
			if ( isset($this->data['id']) )
				unset($this->data['id']);				
			$this->data = apply_filters( 'usam_delivery_service_insert_data', $this->data );
			$format = $this->get_data_format();
			$result = $wpdb->insert( USAM_TABLE_DELIVERY_SERVICE, $this->data, $format );
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];				
			}
			do_action( 'usam_delivery_service_insert', $this );
		} 		
		do_action( 'usam_delivery_service_save', $this );
		return $result;
	}
}

function usam_get_delivery_service( $value, $colum = 'id' )
{	
	$class = new USAM_Delivery_Service($value, $colum);		
	if ( $class->exists() )
	{
		$data = $class->get_data();
		if ( usam_is_multisite() && !is_main_site() )
		{
			$blog_id = get_current_blog_id();
			foreach (['name', 'description'] as $key)
			{
				$value = usam_get_delivery_service_metadata($data['id'],  $key.'_'.$blog_id );
				if ( $value )
					$data[$key] = $value;
			}
		}
	}
	else
		return array();	
	return $data;	
}

function usam_update_delivery_service( $id, $data )
{		
	if ( usam_is_multisite() && !is_main_site() )
	{
		$blog_id = get_current_blog_id();
		foreach (['name', 'description'] as $key)
		{
			if ( isset($data[$key]) )
			{
				usam_update_delivery_service_metadata($id, $key.'_'.$blog_id, $data[$key] );
				unset($data[$key]);
			}
		}
	}
	if ( $data )
	{
		$class = new USAM_Delivery_Service( $id );	
		$class->set( $data );	
		return $class->save();
	}
	else
		return true;
}

function usam_insert_delivery_service( $value )
{	
	$delivery_service = new USAM_Delivery_Service( $value );	
	$delivery_service->save();
	return $delivery_service->get('id');		 
}


function usam_get_delivery_service_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('delivery', $object_id, USAM_TABLE_DELIVERY_SERVICE_META, $meta_key, $single );
}

function usam_add_delivery_service_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('delivery', $object_id, $meta_key, $meta_value, USAM_TABLE_DELIVERY_SERVICE_META, $prev_value );
}

function usam_update_delivery_service_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('delivery', $object_id, $meta_key, $meta_value, USAM_TABLE_DELIVERY_SERVICE_META, $prev_value );
}

function usam_delete_delivery_service_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('delivery', $object_id, $meta_key, USAM_TABLE_DELIVERY_SERVICE_META, $meta_value, $delete_all );
}
?>