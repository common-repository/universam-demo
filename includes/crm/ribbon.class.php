<?php
class USAM_Ribbon
{
	// строковые
	private static $string_cols = [
		'date_insert',		
		'event_type',	
	];
	// цифровые
	private static $int_cols = [
		'id',			
		'event_id',
	];
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
		if ( ! in_array( $col, ['id'] ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );	
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_ribbon' );
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
		wp_cache_set( $id, $this->data, 'usam_ribbon' );	
		do_action( 'usam_ribbon_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_ribbon' );	
		do_action( 'usam_ribbon_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		if ( $this->get('status') == 1 )
		{			
			$id = $this->get('id');
			$data = $this->get_data();
			do_action( 'usam_ribbon_before_delete', $data );
			$result = $wpdb->query("DELETE FROM ".USAM_TABLE_RIBBON." WHERE id = '$id'");				
			$wpdb->query("DELETE FROM ".USAM_TABLE_RIBBON_LINKS." WHERE ribbon_id = '$id'");
			$wpdb->query("DELETE FROM ".USAM_TABLE_RIBBON_META." WHERE object_id = '$id'");	
			
			$this->delete_cache( );		
			do_action( 'usam_ribbon_delete', $id );
		}
		else
		{
			$this->set( ['status' => 1] );
			$result = $this->save();
		}
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_RIBBON." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_ribbon_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_ribbon_fetched', $this );	
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
		return apply_filters( 'usam_ribbon_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_ribbon_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_ribbon_set_properties', $properties, $this );
	
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

		do_action( 'usam_ribbon_pre_save', $this );			
		$result = false;	
		if ( $this->args['col'] ) 
		{
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_ribbon_pre_update', $this );

			$this->data = apply_filters( 'usam_ribbon_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$data = $this->data;
			
			$result = $wpdb->update( USAM_TABLE_RIBBON, $this->data, [$this->args['col'] => $this->args['value']], $formats, [$where_format] );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_ribbon_update', $this );
		} 
		else 
		{   
			do_action( 'usam_ribbon_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			if ( empty($this->data['date_insert']) )
				$this->data['date_insert'] = date( "Y-m-d H:i:s" );	
			
			if ( empty($this->data['event_id']) )
				return false;
			
			if ( empty($this->data['event_type']) )
				return false;	
						
			$this->data = apply_filters( 'usam_ribbon_insert_data', $this->data );
			$format = $this->get_data_format();					
			$result = $wpdb->insert( USAM_TABLE_RIBBON, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];
				do_action( 'usam_ribbon_insert', $this );				
			}			
		} 		
		do_action( 'usam_ribbon_save', $this );

		return $result;
	}
}

function usam_get_ribbon( $id, $colum = 'id' )
{
	$ribbon = new USAM_Ribbon( $id, $colum );
	return $ribbon->get_data( );	
}

// Вставить
function usam_insert_ribbon( $data, $links = [] ) 
{ 
	$ribbon = new USAM_Ribbon( $data );
	$ribbon->save();
	$id = $ribbon->get('id');	
	if ( $id )
	{	
		if ( isset($links['object_id']) )			
			$links = [ $links ];
		foreach ($links as $link )
		{
			$link['ribbon_id'] = $id; 
			usam_add_ribbon_link( $link, false );	
		}
	}
	return $ribbon->get('id');
}

function usam_update_ribbon( $id, $data ) 
{
	$ribbon = new USAM_Ribbon( $id );
	$ribbon->set( $data );
	return $ribbon->save();
}

function usam_set_ribbon( $data, $links ) 
{ 
	if ( empty($data['event_id']) || empty($data['event_type']) )
		return false;
	require_once( USAM_FILE_PATH . '/includes/crm/ribbon_query.class.php' );	
	$ribbon = usam_get_ribbon_query(['event_id' => $data['event_id'], 'event_type' => $data['event_type'], 'number' => 1]);	
	if ( $ribbon )
		foreach ( $links as $link )	
		{
			$link['ribbon_id'] = $ribbon['id'];	
			usam_add_ribbon_link( $link );
		}
	else
		usam_insert_ribbon($data, $links);
	return true;
}


//Добавить ссылку
function usam_add_ribbon_link( $args, $check_existence = true ) 
{
	global $wpdb;	

	$formats = ['ribbon_id' => '%d', 'object_id' => '%d', 'object_type' => '%s'];
	$where = [];
	foreach ($formats as $key => $value ) 
	{
		if ( !empty($args[$key]) )
			$where[] = "$key='".$args[$key]."'";
		else
			return false;
	}
	if ( $check_existence )
		$result = $wpdb->get_var("SELECT ribbon_id FROM ".USAM_TABLE_RIBBON_LINKS." WHERE ".implode(' AND ', $where)." LIMIT 1");
	else
		$result = false;
	if ( !$result )
	{
		$format = [];
		foreach ($args as $key => $value ) 
		{
			if ( isset($formats[$key]) )
				$format[] = $formats[$key];
			else
				unset($args[$key]);
		}	
		$result = $wpdb->insert( USAM_TABLE_RIBBON_LINKS, $args, $format );	
	}
	return $result;
}

function usam_delete_ribbon_link( $args ) 
{
	global $wpdb;

	$formats = ['ribbon_id' => '%d', 'object_id' => '%d', 'object_type' => '%s'];
	$format = [];
	foreach ($args as $key => $value ) 
	{
		if ( isset($formats[$key]) )
			$format[] = $formats[$key];
		else
			unset($args[$key]);
	}
	return $wpdb->delete( USAM_TABLE_RIBBON_LINKS, $args, $format ); 
}

function usam_get_ribbon_links( $event_id, $event_type ) 
{
	global $wpdb;
	if ( !$event_id || !$event_type )
		return [];
	
	$links = $wpdb->get_results("SELECT l.object_id, l.object_type FROM ".USAM_TABLE_RIBBON_LINKS." AS l INNER JOIN `".USAM_TABLE_RIBBON."` AS r ON (r.id=l.ribbon_id AND r.event_id=$event_id AND r.event_type='$event_type')");
	return $links;
}

function usam_delete_ribbon( $args ) 
{	
	global $wpdb;
	require_once( USAM_FILE_PATH . '/includes/crm/ribbon_query.class.php' );	
	$items = usam_get_ribbon_query( $args );
	if ( empty($items) )
		return false;	
		
	$ids = array();
	foreach ($items as $item )
	{
		$ids[] = $item->id;
		do_action( 'usam_ribbon_before_delete', (array)$item );		
	}
	$wpdb->query( "DELETE FROM " . USAM_TABLE_RIBBON_LINKS . " WHERE ribbon_id IN ('".implode("','", $ids)."')" );	
	//$wpdb->query( "DELETE FROM " . USAM_TABLE_RIBBON_META . " WHERE ribbon_id IN ('".implode("','", $ids)."')" );			
	$wpdb->query( "DELETE FROM " . USAM_TABLE_RIBBON . " WHERE id IN ('".implode("','", $ids)."')" );		
	
	return count($ids);	
}

function usam_add_ribbon_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('ribbon', $object_id, $meta_key, $meta_value, USAM_TABLE_RIBBON_META, $prev_value );
}

function usam_get_ribbon_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('ribbon', $object_id, USAM_TABLE_RIBBON_META, $meta_key, $single );
}

function usam_update_ribbon_metadata($object_id, $meta_key, $meta_value, $prev_value = true ) 
{
	return usam_update_metadata('ribbon', $object_id, $meta_key, $meta_value, USAM_TABLE_RIBBON_META, $prev_value );
}

function usam_delete_ribbon_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('ribbon', $object_id, $meta_key, USAM_TABLE_RIBBON_META, $meta_value, $delete_all );
}
?>