<?php
class USAM_Mailing_List 
{	
	 // строковые
	private static $string_cols = array(	
		'name',
		'description',
		'type',
		'date_insert',		
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'view',	
		'parent_id',		
		'subscribed',
		'unconfirmed',
		'unsubscribed',		
	);
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
		if ( ! in_array($col, ['id']) )
			return;

		$this->args = array( 'col' => $col, 'value' => $value );
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_mailing_list' );			
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
		wp_cache_set( $id, $this->data, 'usam_mailing_list' );		
		do_action( 'usam_mailing_list_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_mailing_list' );	
		do_action( 'usam_mailing_list_update_cache', $this );
	}

	/**
	 * Удаляет
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get('id');		
		$data = $this->get_data();
		do_action( 'usam_mailing_list_before_delete', $data );
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_MAILING_LISTS . " WHERE id = %d", $id ) );	
		
		$this->delete_cache( );	
		do_action( 'usam_mailing_list_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_MAILING_LISTS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_mailing_list_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}
			$this->update_cache( );	
		}
		do_action( 'usam_mailing_list_fetched', $this );
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
		return apply_filters( 'usam_mailing_list_get_property', $value, $key, $this );
	}

	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_mailing_list_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_mailing_list_set_properties', $properties, $this );	

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

		do_action( 'usam_mailing_list_pre_save', $this );	
		
		$where_col = $this->args['col'];
		$result = false;		
		if ( $where_col ) 
		{	// обновление			
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_mailing_list_pre_update', $this );			
			$where = array( $this->args['col'] => $where_format);

			$this->data = apply_filters( 'usam_mailing_list_update_data', $this->data );	
			$formats = $this->get_data_format( );			
			
			$result = $wpdb->update( USAM_TABLE_MAILING_LISTS, $this->data, array( $where_col => $where_val ), $formats, $where_format );
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_mailing_list_update', $this );
		} 
		else 
		{   // создание	
			do_action( 'usam_mailing_list_pre_insert' );	
			
			if ( empty($this->data['name']) )
				$this->data['name'] = '';	
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );					
								
			$this->data = apply_filters( 'usam_mailing_list_insert_data', $this->data );				
			$format = $this->get_data_format( );			
		
			$result = $wpdb->insert( USAM_TABLE_MAILING_LISTS, $this->data, $format );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = array('col'   => 'id',  'value' => $this->get( 'id' ) );				
				do_action( 'usam_mailing_list_insert', $this );
			}			
		} 		
		do_action( 'usam_mailing_list_save', $this );
		return $result;
	}
}

function usam_get_mailing_list( $value, $colum = 'id' )
{	
	$mailing_list = new USAM_Mailing_List($value, $colum);	
	$mailing_list_data = $mailing_list->get_data();	
	return $mailing_list_data;	
}

function usam_update_mailing_list( $id, $data )
{	
	if ( !empty($data) ) 
	{
		$mailing_list = new USAM_Mailing_List( $id );	
		$mailing_list->set( $data );	
		return $mailing_list->save();
	}
	return true;
}

function usam_insert_mailing_list( $value )
{	
	$mailing_list = new USAM_Mailing_List( $value );	
	$mailing_list->save();
	$mailing_list_id = $mailing_list->get('id');	
	return $mailing_list_id;		 
}

function usam_delete_mailing_list( $value, $colum = 'id' )
{	
	$subscriber = new USAM_Mailing_List( $value, $colum );
	return $subscriber->delete();		 
}

function usam_get_mailing_list_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('list', $object_id, USAM_TABLE_MAILING_LIST_META, $meta_key, $single );
}

function usam_update_mailing_list_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('list', $object_id, $meta_key, $meta_value, USAM_TABLE_MAILING_LIST_META, $prev_value );
}

function usam_delete_mailing_list_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('list', $object_id, $meta_key, USAM_TABLE_MAILING_LIST_META, $meta_value, $delete_all );
}

function usam_add_mailing_list_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{	
	return usam_add_metadata('list', $object_id, $meta_key, $meta_value, USAM_TABLE_MAILING_LIST_META, $prev_value );
}