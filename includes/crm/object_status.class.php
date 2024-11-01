<?php
class USAM_Object_Status
{
	// строковые
	private static $string_cols = array(
		'internalname',						
		'name',	
		'description',
		'color',
		'text_color',
		'short_name',
		'type',
		'subject_email',
		'email',
		'sms',	
		'external_code',		
	);
	// цифровые
	private static $int_cols = array(
		'id',
		'active',			
		'sort',				
		'visibility',	
		'close',	
		'pay',
		'number',		
	);	
	private $data = [];
	private $changed_data = [];	
	private $fetched           = false;
	private $args = ['col' => '', 'value' => ''];	
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
		{			
			$this->data = wp_cache_get( $value, 'usam_object_status' );
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
		wp_cache_set( $id, $this->data, 'usam_object_status' );			
		do_action( 'usam_object_status_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_object_status' );	
		do_action( 'usam_object_status_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_object_status_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_OBJECT_STATUSES." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_object_status_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_OBJECT_STATUSES." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_object_status_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_object_status_fetched', $this );	
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
		return apply_filters( 'usam_object_status_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_object_status_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_object_status_set_properties', $properties, $this );	

		$this->fetch();		
		if ( ! is_array( $this->data ) )
			$this->data = array();

		foreach ( $properties as $key => $value ) 
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
		do_action( 'usam_object_status_pre_save', $this );			
		$result = false;
		if( $this->args['col'] ) 
		{	
			if( !empty($this->changed_data) )
			{
				$where_val = $this->args['value'];
				$where_format = self::get_column_format( $this->args['col'] );
				do_action( 'usam_object_status_pre_update', $this );

				$this->data = apply_filters( 'usam_object_status_update_data', $this->data );			
				$data = $this->get_update_data();
				$formats = $this->get_data_format( $data );
				$result = $wpdb->update( USAM_TABLE_OBJECT_STATUSES, $data, [$this->args['col'] => $where_val], $formats, [$where_format]);				
				if ( $result ) 
				{				
					$this->delete_cache( );			
				}
				do_action( 'usam_object_status_update', $this );
			}
			else
				$result = true;
		} 
		else 
		{ 
			do_action( 'usam_object_status_pre_insert' );			
			if ( empty($this->data['internalname']) )							
				$this->data['internalname'] = sanitize_title($this->data['name']);
			
			if ( empty($this->data['short_name']) )
				$this->data['short_name'] = $this->data['name'];	
			
			if ( empty($this->data['number']) )
				$this->data['number'] = 0;
				
			if ( !isset($this->data['visibility']) )
				$this->data['visibility'] = 1;		
		
			if ( isset($this->data['id']) )
				unset($this->data['id']);				
			
			if ( empty($this->data['type']) )
				$this->data['type'] = 'order';	
			$check_availability = usam_get_object_status_by_code( $this->data['internalname'], $this->data['type'] );
			if ( !empty($check_availability) )
			{
				$this->set( 'id', $check_availability['id'] );		
				$this->data = $check_availability;
				return true;			
			} 
			if ( !isset($this->data['pay']) )
				$this->data['pay'] = 0;			
			
			$this->data = apply_filters( 'usam_object_status_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );					
			$result = $wpdb->insert( USAM_TABLE_OBJECT_STATUSES, $this->data, $formats ); 
			if ( $result ) 
			{
				$result = $wpdb->insert_id;
				$this->set( 'id', $wpdb->insert_id );					
				
				$this->args = ['col'   => 'id',  'value' => $this->get( 'id' )];				
				do_action( 'usam_object_status_insert', $this );
			}	
			do_action( 'usam_object_status_insert', $this );
		}
		do_action( 'usam_object_status_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_object_status( $id, $colum = 'id' )
{ 
	$object_status = new USAM_Object_Status( $id, $colum );
	return $object_status->get_data( );	
}

function usam_delete_object_status( $id ) 
{
	$object_status = new USAM_Object_Status( $id );
	$result = $object_status->delete( );
	return $result;
}

function usam_insert_object_status( $data ) 
{
	$object_status = new USAM_Object_Status( $data );
	$object_status->save();	
	return $object_status->get('id');
}

function usam_update_object_status( $id, $data ) 
{
	$object_status = new USAM_Object_Status( $id );
	$object_status->set( $data );
	return $object_status->save();
}

/*
 * Найти имя статуса заказа по его номеру
 */
function usam_get_object_status_name( $internalname, $type ) 
{
	$status = usam_get_object_status_by_code( $internalname, $type );	
	return isset($status['name'])?$status['name']:'';
}

/*
 * Найти описание статуса заказа по его номеру
 */
function usam_get_object_status_description( $internalname, $type ) 
{
	$status = usam_get_object_status_by_code( $internalname, $type );	
	return isset($status['description'])?$status['description']:'';
}

/*
 *  Узнать завершен ли заказ по id статуса
 */
function usam_check_object_is_completed( $internalname, $status_type ) 
{
	$status = usam_get_object_status_by_code( $internalname, $status_type );	
	return !empty($status)?$status['close']:false;
}

function usam_get_object_status_by_code( $code, $status_type ) 
{	
	$object_statuses = usam_get_object_statuses_by_type( $status_type );
	foreach ( $object_statuses as $status ) 
	{
		if ( $status->internalname == $code )
		{
			return (array)$status;
		}
	}
	return [];
}

function usam_get_object_statuses_by_type( $status_type, $object_status = '' ) 
{	
	$cache_key = "usam_{$status_type}_status"; 
	$object_statuses = wp_cache_get( $cache_key );
	if ($object_statuses === false) 
	{	
		require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
		$object_statuses = usam_get_object_statuses(['type' => $status_type, 'cache_results' => true, 'cache_meta' => true]);
		wp_cache_set($cache_key, $object_statuses);			
	}
	if ( $object_status != '' )
	{
		$results = [];
		foreach ( $object_statuses as $status ) 
		{										
			$statuses = usam_get_array_metadata( $status->id, 'object_status', 'statuses' );
			if ( $status->internalname == $object_status || $status->visibility && (empty($statuses) || in_array($object_status, $statuses)) )
				$results[] = $status;
		}
		return $results;
	}
	else
		return $object_statuses;
}

function usam_update_object_count_status( $status, $type = '' )
{
	static $calculate = true, $statuses = [];	
	global $wpdb;
	
	if ( $status === '' )
		return false;
	
	if ( is_string($status) )
		$statuses[$type][$status] = $status;
	else
		$calculate = $status;
	if ( $calculate )
	{
		foreach ( $statuses as $type => $items )
		{
			foreach ( $items as $status )
			{						
				$object_status = usam_get_object_status_by_code( $status, $type );	
				if ( $object_status )
				{
					$count = usam_get_number_objects_status( $status, $type );		
					usam_update_object_status( $object_status['id'], ['number' => $count] );
				}
			}
			unset($statuses[$type]);
		}
	}	
	return $statuses;
}

function usam_get_number_objects_status( $status, $type )
{	
	global $wpdb;
	switch ( $type ) 
	{
		case 'order' :
			$objects = $wpdb->get_row("SELECT COUNT(*) AS count FROM ".USAM_TABLE_ORDERS." WHERE status='$status'" );	
		break;
		case 'lead' :
			$objects = $wpdb->get_row("SELECT COUNT(*) AS count FROM ".USAM_TABLE_LEADS." WHERE status='$status'" );	
		break;
		case 'payment' :
			$objects = $wpdb->get_row("SELECT COUNT(*) AS count FROM ".USAM_TABLE_PAYMENT_HISTORY." WHERE status='$status'" );	
		break;
		case 'shipped' :
			$objects = $wpdb->get_row("SELECT COUNT(*) AS count FROM ".USAM_TABLE_SHIPPED_DOCUMENTS." WHERE status='$status'" );
		break;
		case 'company' :
			$objects = $wpdb->get_row("SELECT COUNT(*) AS count FROM ".USAM_TABLE_COMPANY." WHERE status='$status'" );	
		break;
		case 'contact' :
			$objects = $wpdb->get_row("SELECT COUNT(*) AS count FROM ".USAM_TABLE_CONTACTS." WHERE status='$status'" );
		break;
		case 'contacting' :
			$objects = $wpdb->get_row("SELECT COUNT(*) AS count FROM ".USAM_TABLE_CONTACTINGS." WHERE status='$status'" );
		break;		
		default:
			$document = usam_get_document_name( $type );		
			if ( $document )
				$objects = $wpdb->get_row("SELECT COUNT(*) AS count FROM ".USAM_TABLE_DOCUMENTS." WHERE status='$status' AND type='$type'" );
			else
			{
				$events_types = usam_get_events_types( );
				if ( isset($events_types[$type]) )
					$objects = $wpdb->get_row("SELECT COUNT(*) AS count FROM ".USAM_TABLE_EVENTS." WHERE status='$status' AND type='$type'" );				
			}		
		break;
	}				
	return empty($objects->count)?0:$objects->count;
}

function usam_add_object_status_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('status', $object_id, $meta_key, $meta_value, USAM_TABLE_OBJECT_STATUS_META, $prev_value );
}

function usam_get_object_status_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('status', $object_id, USAM_TABLE_OBJECT_STATUS_META, $meta_key, $single );
}

function usam_update_object_status_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('status', $object_id, $meta_key, $meta_value, USAM_TABLE_OBJECT_STATUS_META, $prev_value );
}

function usam_delete_object_status_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('status', $object_id, $meta_key, USAM_TABLE_OBJECT_STATUS_META, $meta_value, $delete_all );
}

function usam_get_status_code_by_meta( $key, $value, $status_type = 'order' ) 
{
	$object_statuses = usam_get_object_statuses_by_type( $status_type );
	if( $object_statuses )
	{
		$ids = [];	
		foreach( $object_statuses as $result ) 
		{
			if ( isset($result->id) )
				$ids[] = $result->id; 					
		}
		usam_update_cache( $ids, [USAM_TABLE_OBJECT_STATUS_META => 'status_meta'], 'status_id' );
		foreach( $object_statuses as $status ) 
		{
			$metadata = usam_get_object_status_metadata( $status->id, $key );
			if ( $metadata == $value )
				return $status->internalname;
		}
	}
	return '';
}
?>