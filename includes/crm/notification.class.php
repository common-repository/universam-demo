<?php
class USAM_Notification
{	// строковые
	private static $string_cols = array(
		'date_insert',					
		'title',		
		'status'
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'user_id',	
	);	
	private $data = array();		
	private $is_status_changed = false;	
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
			$this->data = wp_cache_get( $value, 'usam_notification' );
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
		wp_cache_set( $id, $this->data, 'usam_notification' );	
		do_action( 'usam_notification_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_notification' );	
		do_action( 'usam_notification_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		if ( !$id )
			return false;
		
		$data = $this->get_data();
		do_action( 'usam_notification_before_delete', $data );
		$wpdb->query("DELETE FROM ".USAM_TABLE_NOTIFICATION_RELATIONSHIPS." WHERE notification_id = '$id'");
		
		$this->delete_cache( );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_NOTIFICATIONS." WHERE id = '$id'");	
		do_action( 'usam_notification_delete', $data );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_NOTIFICATIONS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_notification_data', $data );		
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}			
			$this->fetched = true;				
			$this->update_cache( );
		}		
		do_action( 'usam_notification_fetched', $this );	
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
		return apply_filters( 'usam_notification_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_notification_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_notification_set_properties', $properties, $this );
		
		if ( array_key_exists( 'status', $properties ) ) 
		{	
			$previous_status = $this->get( 'status' );
			if ( $properties['status'] != $previous_status )
				$this->is_status_changed = true;			
		}			
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
		do_action( 'usam_notification_pre_save', $this );	
		$where_col = $this->args['col'];				
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_notification_pre_update', $this );
			
			$this->data = apply_filters( 'usam_notification_update_data', $this->data );			
			$format = $this->get_data_format(  );
			$this->data_format( );					
			$data = $this->data;
			
			$str = array();
			foreach ( $format as $key => $value ) 
			{
				if ( $data[$key] == null )
				{
					$str[] = "`$key` = NULL";
					unset($data[$key]);
				}
				else
					$str[] = "`$key` = '$value'";					
			}				
			$result = $wpdb->query( $wpdb->prepare( "UPDATE `".USAM_TABLE_NOTIFICATIONS."` SET ".implode( ', ', $str )." WHERE $where_col = '$where_format' ", array_merge( array_values( $data ), array( $where_val ) ) ) );	
			if ( $result ) 
			{
				$this->delete_cache();	
				do_action( 'usam_notification_update', $this );					
			}					
		} 
		else 
		{   
			do_action( 'usam_notification_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );	
			if ( !isset($this->data['user_id']) )
				$this->data['user_id'] = get_current_user_id();	
			if ( empty($this->data['status']) )
				$this->data['status'] = 'started';					
						
			$this->data = apply_filters( 'usam_notification_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );	
					
			$result = $wpdb->insert( USAM_TABLE_NOTIFICATIONS, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];		
				do_action( 'usam_notification_insert', $this );				
			}			
		} 		
		do_action( 'usam_notification_save', $this );

		return $result;
	}
}

function usam_get_notification( $id )
{
	$_notification = new USAM_Notification( $id );
	return $_notification->get_data( );	
}

function usam_delete_notification( $id ) 
{
	$_notification = new USAM_Notification( $id );
	return $_notification->delete( );	
}

// Вставить задачу
function usam_insert_notification( $data, $objects = array() ) 
{
	$_notification = new USAM_Notification( $data );
	$_notification->save();
	$notification_id = $_notification->get('id');
	if ( $notification_id && !empty($objects) ) 
	{
		if ( !isset($objects[0]) )
			$objects = array( $objects );
	
		foreach ( $objects as $object ) 
		{
			$object['notification_id'] = $notification_id;
			usam_set_notification_object( $object ); 
		}
	}
	return $notification_id;
}

function usam_update_notification( $id, $data ) 
{
	$_notification = new USAM_Notification( $id );	
	$_notification->set( $data );
	return $_notification->save();
}

function usam_get_notification_objects_all( $notification_id ) 
{	
	$cache_key = 'usam_notification_relationships';
	$cache = wp_cache_get( $notification_id, $cache_key );
	if( $cache === false )
	{	
		$cache = usam_get_notification_objects(['notification_id' => $notification_id]);
		wp_cache_set( $notification_id, $cache, $cache_key );
	}	
	return $cache;
}

function usam_get_notification_objects( $args ) 
{
	global $wpdb;
		
	$query_where = '1=1';	
	if ( !empty($args['notification_id']) )
	{
		$notification_id = implode( ',',  (array)$args['notification_id'] );		
		$query_where .= " AND ".USAM_TABLE_NOTIFICATION_RELATIONSHIPS.".notification_id IN ($notification_id)";
	}
	if ( !empty($args['object_id']) )
	{
		$object_id = implode( ',',  (array)$args['object_id'] );		
		$query_where .= " AND ".USAM_TABLE_NOTIFICATION_RELATIONSHIPS.".object_id IN ($object_id)";
	}
	if ( !empty($args['object_type']) )
	{
		$object_type = implode( "','",  (array)$args['object_type'] );		
		$query_where .= " AND ".USAM_TABLE_NOTIFICATION_RELATIONSHIPS.".object_type IN ('$object_type')";
	}
	$data = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_NOTIFICATION_RELATIONSHIPS." WHERE $query_where ORDER BY object_type" );	
	return $data;	
}

function usam_set_notification_object( $object ) 
{
	global $wpdb;	
	
	if ( empty($object['notification_id']) || empty($object['object_id']) || empty($object['object_type']) )
		return false;
	
	$sql = "INSERT INTO `".USAM_TABLE_NOTIFICATION_RELATIONSHIPS."` (`notification_id`,`object_type`,`object_id`) VALUES ('%d','%s','%d') ON DUPLICATE KEY UPDATE `object_id`='%d'";	
	$insert = $wpdb->query( $wpdb->prepare($sql, $object['notification_id'], $object['object_type'], $object['object_id'], $object['object_id'] ) );	
	return $wpdb->insert_id;
}

function usam_delete_notification_object( $args ) 
{
	global $wpdb;
	
	if ( empty($args['notification_id']) || empty($args['object_id']) || empty($args['object_type']) )
		return false;
	
	$notification_id = absint($args['notification_id']);
	$object_id = absint($args['object_id']);
	$object_type = $args['object_type'];
	
	return $wpdb->delete( USAM_TABLE_NOTIFICATION_RELATIONSHIPS, ['notification_id' => $notification_id, 'object_id' => $object_id, 'object_type' => $object_type], ['%d', '%d', '%s'] );	
}

// Добавить системное событие
function usam_add_notification( $data, $object = array(), $user_ids = array() ) 
{	
	$data['status'] = 'started';		
	$data['type'] = 'notification';	
	
	if ( empty($user_ids) )
		$user_ids = get_users(['role__in' => ['shop_manager','administrator'], 'fields' => 'ID', 'meta_query' => ['relation' => 'OR', ['key' => 'usam_notification_'.$object['object_type'], 'compare' => "NOT EXISTS"], ['key' => 'usam_notification_'.$object['object_type'],'value' => 0, 'compare' => '=']]]);
	elseif ( !is_array($user_ids) )
		$user_ids = array( $user_ids );
	
	foreach ( $user_ids as $user_id ) 
	{
		$user_notification = get_user_meta( $user_id, 'usam_notification_'.$object['object_type'] );			
		if ( !$user_notification )
		{
			$data['user_id'] = $user_id; 
			$notification_id = usam_insert_notification( $data, $object );
		}
	}
}
?>