<?php
class USAM_Currency_Rate
{	// строковые
	private static $string_cols = array(
		'basic_currency',		
		'currency',			
		'date_update',		
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'autoupdate'
	);
	private static $float_cols = array(
		'rate',		
		'markup',		
	);
	private $data = array();	
	private $changed_data = array();		
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
			$this->data = wp_cache_get( $value, 'usam_currency_rate' );
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
		wp_cache_set( $id, $this->data, 'usam_currency_rate' );	
		do_action( 'usam_currency_rate_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_currency_rate' );	
		do_action( 'usam_currency_rate_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_currency_rate_before_delete', $data );			
							
		$this->delete_cache( );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_CURRENCY_RATES." WHERE id = '$id'");			
		do_action( 'usam_currency_rate_delete', $id );	
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_CURRENCY_RATES." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_currency_rate_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}
			$this->fetched = true;				
			$this->update_cache( );
		} 
		do_action( 'usam_currency_rate_fetched', $this );	
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
		return apply_filters( 'usam_currency_rate_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_currency_rate_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_currency_rate_set_properties', $properties, $this );			
		if ( ! is_array($this->data) )
			$this->data = array();
		foreach ( $properties as $key => $value ) 
		{	
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{				
				$previous = $this->get( $key );			
				if ( $value != $previous )
				{				
					$this->changed_data[$key] = $previous;					
				}
				$this->data[$key] = $value;
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
		do_action( 'usam_currency_rate_pre_save', $this );		
		
		$this->data['date_update'] = date( "Y-m-d H:i:s" );
		$result = false;			
		if( $this->args['col'] ) 
		{			
			if ( empty($this->changed_data) )
				return true;				
		
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_currency_rate_pre_update', $this );
			
			$this->data = apply_filters( 'usam_currency_rate_update_data', $this->data );			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_CURRENCY_RATES, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if( $result ) 
			{ 
				$this->update_cache( );												
			}
			do_action( 'usam_currency_rate_update', $this );			
		} 
		else 
		{   
			do_action( 'usam_currency_rate_pre_insert' );			
					
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );		
			
			if ( empty($this->data['markup']) )
				$this->data['markup'] = 0;	
			
			if ( empty($this->data['rate']) )
				$this->data['rate'] = 0;
			
			if ( empty($this->data['autoupdate']) )
				$this->data['autoupdate'] = 0;	
					
			$this->data = apply_filters( 'usam_currency_rate_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );								
			$result = $wpdb->insert( USAM_TABLE_CURRENCY_RATES, $this->data, $formats ); 
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );
				$this->update_cache( );
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );						
			}
			do_action( 'usam_currency_rate_insert', $this );
		} 		
		do_action( 'usam_currency_rate_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_currency_rate( $id, $colum = 'id' )
{
	$_rate = new USAM_Currency_Rate( $id, $colum );
	return $_rate->get_data( );	
}

function usam_delete_currency_rate( $id ) 
{
	$_rate = new USAM_Currency_Rate( $id );
	$result = $_rate->delete();
	return $result;
}

// Вставить
function usam_insert_currency_rate( $data ) 
{
	$_rate = new USAM_Currency_Rate( $data );
	$_rate->save();
	return $_rate->get('id');
}

function usam_update_currency_rate( $id, $data ) 
{		
	$_rate = new USAM_Currency_Rate( $id );
	$_rate->set( $data );
	return $_rate->save();
}
?>