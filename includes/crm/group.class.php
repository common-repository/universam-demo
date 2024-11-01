<?php
class USAM_Group
{	 // строковые
	private static $string_cols = [
		'name',
		'code',
		'type',
		'status',
	];	
	// цифровые
	private static $int_cols = [
		'id',		
		'sort',
		'user_id',
	];	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';
			
		return false;
	}
	private $data    = array();
	private $fetched = false;	
	private $args    = array( 'col'   => '', 'value' => '' );
	private $exists  = false;
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( false === $value )
			return;

		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id', 'code' ) ) )
			return;

		$this->args = array( 'col' => $col, 'value' => $value );	
		if ( $col == 'code'  && $id = wp_cache_get( $value, 'usam_group_code' ) )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $id;
		}			
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_group' );			
		}
		// кэш существует
		if ( $this->data ) 
		{	
			$this->fetched = true;
			$this->exists = true;			
		}	
		else
			$this->fetch();	
	}

	/**
	 * Обновить кеш
	 */
	public function update_cache( ) 
	{		
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_group' );	
		if ( $code = $this->get( 'code' ) )
			wp_cache_set( $code, $id, 'usam_group_code' );	
		do_action( 'usam_group_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_group' );	
		wp_cache_delete( $this->get( 'code' ), 'usam_group_code' );
		do_action( 'usam_group_update_cache', $this );
	}

	/**
	 * Удаляет
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get('id');		
		$data = $this->get_data();
		do_action( 'usam_group_before_delete', $data );
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_GROUP_RELATIONSHIPS . " WHERE group_id = %d", $id ) );	
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_GROUPS . " WHERE id = %d", $id ) );		
		
		$this->delete_cache( );	
		do_action( 'usam_group_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_GROUPS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_group_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->update_cache( );	
		}
		do_action( 'usam_group_fetched', $this );
		$this->fetched = true;
	}	

	/**
	 * Проверить существует ли строка в БД
	 */
	public function exists() 
	{
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства из БД
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_group_get_property', $value, $key, $this );
	}

	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_group_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_group_set_properties', $properties, $this );	

		$this->fetch();		
		if ( ! is_array( $this->data ) )
			$this->data = array();

		$this->data = array_merge( $this->data, $properties );
		return $this;
	}

	/**
	 * Вернуть формат столбцов таблицы
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

		do_action( 'usam_group_pre_save', $this );	
		
		$result = false;		
		if ( $this->args['col'] ) 
		{	// обновление
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_group_pre_update', $this );			
			$this->data = apply_filters( 'usam_group_update_data', $this->data );	
			$formats = $this->get_data_format( );			
			
			$result = $wpdb->update( USAM_TABLE_GROUPS, $this->data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_group_update', $this );
		} 
		else 
		{   // создание	
			do_action( 'usam_group_pre_insert' );	
			
			if ( empty($this->data['name']) )
				return false;	
			
			if ( empty($this->data['type']) )
				return false;	
						
			if ( empty($this->data['status']) )
				$this->data['status'] = 'public';
			if ( !isset($this->data['user_id']) )
				$this->data['user_id'] = get_current_user_id();	
			
			require_once( USAM_FILE_PATH.'/includes/crm/groups_query.class.php' );
			$groups = usam_get_groups(['type' => $this->data['type'], 'name' => $this->data['name'], 'number' => 1]);	
			if ( !empty($groups) )
				return false;
			if ( empty($this->data['code']) )
				$this->data['code'] = sanitize_title($this->data['name']);	
			
			if ( !isset($this->data['sort']) )
				$this->data['sort'] = 255;
								
			$this->data = apply_filters( 'usam_group_insert_data', $this->data );				
			$format = $this->get_data_format( );			
		
			$result = $wpdb->insert( USAM_TABLE_GROUPS, $this->data, $format );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = ['col'   => 'id',  'value' => $this->get( 'id' )];				
				do_action( 'usam_group_insert', $this );
			}			
		} 		
		do_action( 'usam_group_save', $this );
		return $result;
	}
}

function usam_get_group( $value, $colum = 'id' )
{	
	$group = new USAM_Group($value, $colum);	
	$group_data = $group->get_data();	
	return $group_data;	
}

function usam_update_group( $id, $data )
{	
	if ( !empty($data) ) 
	{
		$group = new USAM_Group( $id );	
		$group->set( $data );	
		return $group->save();
	}
	return true;
}

function usam_insert_group( $value )
{	
	$group = new USAM_Group( $value );	
	$group->save();
	$group_id = $group->get('id');	
	return $group_id;		 
}

function usam_delete_group( $id ) 
{
	$group = new USAM_Group( $id );
	$result = $group->delete( );
	return $result;
}

function usam_get_groups_object( $id, $type ) 
{	
	global $wpdb;
	$results = $wpdb->get_col("SELECT group_id FROM ".USAM_TABLE_GROUP_RELATIONSHIPS." AS r LEFT JOIN ".USAM_TABLE_GROUPS." AS g ON (g.id=r.group_id) WHERE r.object_id='$id' AND g.type='$type' ORDER BY sort ASC");	
	if ( $results )
		$results = array_map('intval', $results);
	return $results;
}

function usam_set_groups_object( $object_id, $type, $new_group_ids, $append = false ) 
{	
	global $wpdb;
	
	if ( !is_array($new_group_ids) )
		$new_group_ids = array($new_group_ids);	
	$i = 0;
	$group_ids = usam_get_groups_object( $object_id, $type );
	foreach ( $new_group_ids as $key => $group_id ) 
	{ 
		if ( !in_array($group_id, $group_ids) )
		{
			if( usam_insert_group_object( $group_id, $object_id ) )
				$i++;
			unset($new_group_ids[$key]);
		}
	}
	if ( $append == false )
	{
		$results = array_diff($group_ids, $new_group_ids);
		if ( !empty($results) )
		{
			usam_delete_groups_object( $object_id, $results );
		}
	}
	return $i;
}

function usam_insert_group_object( $group_id, $object_id ) 
{	
	global $wpdb;
	$insert = $wpdb->insert( USAM_TABLE_GROUP_RELATIONSHIPS, ['group_id' => $group_id, 'object_id' => $object_id], ['%d', '%d'] );
	return $wpdb->insert_id;
}

function usam_delete_groups_object( $object_id, $group_ids ) 
{	
	global $wpdb;	
	if ( !is_array($group_ids) )
		$group_ids = array($group_ids);		
	return $wpdb->query( "DELETE FROM ".USAM_TABLE_GROUP_RELATIONSHIPS." WHERE object_id='$object_id' AND group_id IN (".implode(',',$group_ids).")" );		
}