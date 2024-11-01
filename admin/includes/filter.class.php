<?php
/**
 * Фильтр
 */ 
class USAM_Filter
{
	 // строковые
	private static $string_cols = array(
		'name',		
		'type',	
		'screen_id',
		'setting',
	);
	// цифровые
	private static $int_cols = array(
		'id',
		'user_id',	
		'sort',			
	);		
	private $data     = array();		
	private $fetched  = false;
	private $args     = ['col'   => '', 'value' => ''];	
	private $exists   = false; // если существует строка в БД	
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
			$this->data = wp_cache_get( $value, 'usam_filter' );
		}		
		// кэш существует
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
		else
			$this->fetch();
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
		wp_cache_set( $id, $this->data, 'usam_filter' );		
		do_action( 'usam_filter_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_filter' );			
		do_action( 'usam_filter_delete_cache', $this );	
	}		
	
	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global $wpdb;
		
		$id = $this->get( 'id' );	
		$data = $this->get_data();
		do_action( 'usam_filter_before_delete', $data );		
		
		$this->delete_cache( );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_FILTERS." WHERE id = '$id'");
		
		do_action( 'usam_filter_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_FILTERS." WHERE {$col} = {$format}", $value );
		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$data['setting'] = maybe_unserialize( $data['setting'] );	
			$this->exists = true;
			$this->data = apply_filters( 'usam_filter_data', $data );	
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}			
			$this->fetched = true;				
			$this->update_cache( );
		}			
		do_action( 'usam_filter_fetched', $this );	
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
		return apply_filters( 'usam_filter_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_filter_get_data', $this->data, $this );
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
		if ( ! is_array($this->data) )
			$this->data = array();		
		
		foreach ( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
				$this->data[$key] = $value;
		}
		$this->data = apply_filters( 'usam_filter_set_properties', $this->data, $this );			
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

		do_action( 'usam_filter_pre_save', $this );	
		$where_col = $this->args['col'];						
		$result = false;	
		if ( isset($this->data['setting']) )
			$this->data['setting'] = serialize( $this->data['setting'] );	
	
		if ( $where_col ) 
		{				
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_filter_pre_update', $this );		
			$this->data = apply_filters( 'usam_filter_update_data', $this->data );	
			$format = $this->get_data_format( );
			$this->data_format( );				
			
			$where = array( $this->args['col'] => $where_val );
			$result = $wpdb->update( USAM_TABLE_FILTERS, $this->data, $where, $format, array($where_format) );			
			if ( $result ) 
			{
				$this->delete_cache( );					
			}
			do_action( 'usam_filter_update', $this );	
		} 
		else 
		{   
			do_action( 'usam_filter_pre_insert' );		
			unset( $this->data['id'] );					
			
			if ( empty($this->data['screen_id']) )
				return false;			
			
			if ( !isset($this->data['name']) )
				$this->data['name'] = '';					
				
			$this->data['user_id'] = get_current_user_id();		

			$this->data = apply_filters( 'usam_filter_insert_data', $this->data );			
			$format = $this->get_data_format(  );
			$this->data_format( );				
				
			$result = $wpdb->insert( USAM_TABLE_FILTERS, $this->data, $format );
			if ( $result ) 
			{	
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );	
			}
			do_action( 'usam_filter_insert', $this );
		} 		
		do_action( 'usam_filter_save', $this );
		return $result;
	}
}
?>