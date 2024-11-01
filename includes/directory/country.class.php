<?php
// Класс стран
class USAM_Country
{	
	// строковые
	private static $string_cols = array(
		'name',
		'code',
		'continent',	
		'currency',
		'language',
		'language_code',	
	);
	// цифровые
	private static $int_cols = array(
		'numerical',
		'phone_code',
		'location_id',		
	);	
	/**
	 * Содержит значения извлекаются из БД
	 */	
	private $data = array();		
	private $fetched           = false;
	private $args = array( 'col'   => '', 'value' => '' );	
	private $exists = false; // если существует строка в БД

	public function __construct( $value = false, $col = 'code' ) 
	{
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'code', 'location_id' ) ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );		
		if ( $col == 'location_id'  && $code = wp_cache_get( $value, 'usam_country_location_id' ) )
		{   // если находится в кэше, вытащить идентификатор
			$col = 'code';
			$value = $code;
		}		
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'code' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_country' );
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
		$code = $this->get( 'code' );
		wp_cache_set( $code, $this->data, 'usam_country' );
		if ( $location_id = $this->get( 'location_id' ) )
			wp_cache_set( $location_id, $code, 'usam_country_location_id' );	
		do_action( 'usam_country_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'code' ), 'usam_country' );
		wp_cache_delete( $this->get( 'location_id' ), 'usam_country_location_id' );		
		do_action( 'usam_country_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( $code ) 
	{		
		global  $wpdb;
		$data = $this->get_data();
		do_action( 'usam_country_before_delete', $data );
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_COUNTRY." WHERE code = '$code'");
		$this->delete_cache( );		
		do_action( 'usam_country_delete', $code );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_COUNTRY." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_country_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_country_fetched', $this );	
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
		return apply_filters( 'usam_country_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_country_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_country_set_properties', $properties, $this );
	
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

		do_action( 'usam_country_pre_save', $this );	
		$where_col = $this->args['col'];			
	
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_country_pre_update', $this );	

			$this->data = apply_filters( 'usam_country_update_data', $this->data );	
			$format = $this->get_data_format( );
			$this->data_format( );	
			
			$result = $wpdb->update( USAM_TABLE_COUNTRY, $this->data, array( $where_col => $where_val ), $format, $where_format );
			if ( $result ) 
				$this->delete_cache( );						
			do_action( 'usam_country_update', $this );
		} 
		else 
		{   
			do_action( 'usam_country_pre_insert' );		
			
			if ( empty($this->data['code']) )
				return false;
			
			$this->data = apply_filters( 'usam_country_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );		
			$result = $wpdb->insert( USAM_TABLE_COUNTRY, $this->data, $format );
			if ( $result ) 
			{				
				$this->args = array('col' => 'code',  'value' => $this->data['code'] );				
			}
			do_action( 'usam_country_insert', $this );
		} 		
		do_action( 'usam_country_save', $this );

		return $result;
	}
}

// Обновить страну 
function usam_update_country( $code, $data, $colum = 'code' )
{
	$country = new USAM_Country( $code, $colum );
	$country->set( $data );
	return $country->save();
}

// Получить страну
function usam_get_country( $code, $colum = 'code' )
{
	$country = new USAM_Country( $code, $colum );
	return $country->get_data( );	
}

// Добавить страну
function usam_insert_country( $data )
{
	$country = new USAM_Country( $data );
	return $country->save();
}

// Удалить страну
function usam_delete_country( $code )
{
	$country = new USAM_Country( $code );
	return $country->delete();
}

function usam_get_country_location( )
{
	$location_ids = usam_get_customer_address_locations();		
	$country_location_id = array_pop($location_ids);		
	$country = usam_get_country( $country_location_id , 'location_id' );	
	return $country;
}