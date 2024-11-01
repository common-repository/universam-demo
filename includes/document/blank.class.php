<?php
class USAM_Blank
{
	// строковые
	private static $string_cols = array(
		'date_insert',		
		'blank_type',
		'phone',		
		'status',
	);
	// цифровые
	private static $int_cols = array(
		'id',			
		'manager_id',
		'blank_id',		
		'time',	
	);	
	private static $float_cols = array(
		'price',			
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
			$this->data = wp_cache_get( $value, 'usam_blank' );
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
		wp_cache_set( $id, $this->data, 'usam_blank' );	
		do_action( 'usam_blank_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_blank' );	
		do_action( 'usam_blank_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_blank_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_BLANKS." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_blank_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_BLANKS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_blank_data', $data );	
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
		do_action( 'usam_blank_fetched', $this );	
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
		return apply_filters( 'usam_blank_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_blank_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_blank_set_properties', $properties, $this );
	
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

		do_action( 'usam_blank_pre_save', $this );	
		$where_col = $this->args['col'];
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_blank_pre_update', $this );			
			$where = array( $this->args['col'] => $where_val);

			$this->data = apply_filters( 'usam_blank_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$this->data_format( );		
			$data = $this->data;
			
			$result = $wpdb->update( USAM_TABLE_BLANKS, $this->data, $where, $formats, array( $where_format ) );	
			if ( $result ) 			
				$this->delete_cache( );			
			do_action( 'usam_blank_update', $this );
		} 
		else 
		{   
			do_action( 'usam_blank_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			$this->data['date_insert']     = date( "Y-m-d H:i:s" );	
			if ( empty($this->data['manager_id']) )
				$this->data['manager_id'] = get_current_user_id();			
						
			$this->data = apply_filters( 'usam_blank_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );	
					
			$result = $wpdb->insert( USAM_TABLE_BLANKS, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );					
			}
			do_action( 'usam_blank_insert', $this );
		} 		
		do_action( 'usam_blank_save', $this );

		return $result;
	}
}

function usam_get_blank( $id, $colum = 'id' )
{
	$blank = new USAM_Blank( $id, $colum );
	return $blank->get_data( );	
}

function usam_delete_blank( $id ) 
{
	$blank = new USAM_Blank( $id );
	$result = $blank->delete( );
	return $result;
}

function usam_insert_blank( $data ) 
{
	$blank = new USAM_Blank( $data );
	$blank->save();
	return $blank->get('id');
}

function usam_update_blank( $id, $data ) 
{
	$blank = new USAM_Blank( $id );
	$blank->set( $data );
	return $blank->save();
}

function usam_get_blank_metadata( $blank_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('blank', $blank_id, USAM_TABLE_BLANK_META, $meta_key, $single );
}

function usam_update_blank_metadata($blank_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('blank', $blank_id, $meta_key, $meta_value, USAM_TABLE_BLANK_META, $prev_value );
}

function usam_delete_blank_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('blank', $object_id, $meta_key, USAM_TABLE_BLANK_META, $meta_value, $delete_all );
}
?>