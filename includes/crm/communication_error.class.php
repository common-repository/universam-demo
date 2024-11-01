<?php
class USAM_Communication_Error
{	 // строковые
	private static $string_cols = array(	
		'communication_type',
		'reason',
		'communication',
		'date_insert',		
	);	
	// цифровые
	private static $int_cols = array(
		'id',	
		'status',				
	);	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';
			
		return false;
	}
	/**
	 *Содержит значения извлекаются из БД
	 * @since 4.9
	 */
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;

		$this->args = array( 'col' => $col, 'value' => $value );	
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_communication_error' );			
		}
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
		wp_cache_set( $id, $this->data, 'usam_communication_error' );	
		do_action( 'usam_communication_error_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_communication_error' );	
		do_action( 'usam_communication_error_update_cache', $this );
	}

	/**
	 * Удаляет
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get('id');		
		$data = $this->get_data();
		do_action( 'usam_communication_error_before_delete', $data );
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_COMMUNICATION_ERRORS . " WHERE id = %d", $id ) );	
		
		$this->delete_cache( );	
		do_action( 'usam_communication_error_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_COMMUNICATION_ERRORS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_communication_error_data', $data );
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}
			$this->update_cache( );	
		}
		do_action( 'usam_communication_error_fetched', $this );
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
		return apply_filters( 'usam_communication_error_get_property', $value, $key, $this );
	}

	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_communication_error_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_communication_error_set_properties', $properties, $this );	

		$this->fetch();		
		if ( ! is_array( $this->data ) )
			$this->data = array();

		$this->data = array_merge( $this->data, $properties );
		return $this;
	}

	/**
	 * Вернуть формат столбцов таблицы
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
	
	/**
	 * Сохраняет в базу данных
	 * @since 4.9
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_communication_error_pre_save', $this );	
		
		$where_col = $this->args['col'];
		$result = false;		
		if ( $where_col ) 
		{	// обновление			
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_communication_error_pre_update', $this );			
			$where = array( $this->args['col'] => $where_format);

			$this->data = apply_filters( 'usam_communication_error_update_data', $this->data );	
			$formats = $this->get_data_format( );			
			
			$result = $wpdb->update( USAM_TABLE_COMMUNICATION_ERRORS, $this->data, array( $where_col => $where_val ), $formats, $where_format );
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_communication_error_update', $this );
		} 
		else 
		{   // создание	
			do_action( 'usam_communication_error_pre_insert' );	
			
			if ( empty($this->data['communication_type']) )
				return false;	
			
			if ( empty($this->data['communication']) )
				return false;	

			if ( empty($this->data['reason']) || $this->data['reason'] === false )
				return false;			
			
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );
								
			$this->data = apply_filters( 'usam_communication_error_insert_data', $this->data );				
			$format = $this->get_data_format( );			
		
			$result = $wpdb->insert( USAM_TABLE_COMMUNICATION_ERRORS, $this->data, $format );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = array('col'   => 'id',  'value' => $this->get( 'id' ) );				
				do_action( 'usam_communication_error_insert', $this );
			}			
		} 		
		do_action( 'usam_communication_error_save', $this );
		return $result;
	}
}

function usam_get_communication_error( $value, $colum = 'id' )
{	
	$communication_error = new USAM_Communication_Error($value, $colum);	
	$communication_error_data = $communication_error->get_data();	
	return $communication_error_data;	
}

function usam_update_communication_error( $id, $data )
{	
	if ( !empty($data) ) 
	{
		$communication_error = new USAM_Communication_Error( $id );	
		$communication_error->set( $data );	
		return $communication_error->save();
	}
	return true;
}

function usam_insert_communication_error( $value )
{	
	$communication_error = new USAM_Communication_Error( $value );	
	$communication_error->save();
	$communication_error_id = $communication_error->get('id');	
	return $communication_error_id;		 
}

function usam_delete_communication_error( $id ) 
{
	$communication_error = new USAM_Communication_Error( $id );
	$result = $communication_error->delete( );
	return $result;
}