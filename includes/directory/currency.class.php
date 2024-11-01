<?php
// Класс валют
class USAM_Currency
{
	// строковые
	private static $string_cols = array(
		'name',
		'code',
		'symbol',
		'symbol_html',
		'external_code',		
	);
	// цифровые
	private static $int_cols = array(
		'numerical',
		'display_currency'
	);		
	private $data = array();		
	private $fetched           = false;
	private $args = array( 'col'   => '', 'value' => '' );	
	private $exists = false; // если существует строка в БД
	
	public function __construct( $value = null, $col = 'code' ) 
	{
		if ( empty($value) )
		{
			$value = get_option("usam_currency_type");		
		}			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array('code', 'numerical') ) )
			return;
					
		$this->args = array( 'col' => $col, 'value' => $value );	
		if ( $col == 'numerical'  && $code = wp_cache_get( $value, 'usam_currency_numerical' ) )
		{   // если находится в кэше, вытащить идентификатор
			$col = 'code';
			$value = $code;
		}		
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'code' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_currency' );
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
		if ( in_array($col, self::$string_cols) )
			return '%s';
		if ( in_array($col, self::$int_cols) )
			return '%d';
		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$code = $this->get( 'code' );
		wp_cache_set( $code, $this->data, 'usam_currency' );
		if ( $numerical = $this->get( 'numerical' ) )
			wp_cache_set( $numerical, $code, 'usam_currency_numerical' );	
		do_action( 'usam_currency_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{
		wp_cache_delete( $this->get( 'code' ), 'usam_currency' );
		wp_cache_delete( $this->get( 'numerical' ), 'usam_currency_numerical' );		
		do_action( 'usam_currency_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		global $wpdb;
				
		$code = $this->get( 'code' );
		$data = $this->get_data();
		do_action( 'usam_currency_before_delete', $data );
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_CURRENCY." WHERE code = '$code'");
		$this->delete_cache( );		
		do_action( 'usam_currency_delete', $code );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_CURRENCY." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_currency_data', $data );	
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}			
			$this->fetched = true;				
			$this->update_cache( );
		}		
		do_action( 'usam_currency_fetched', $this );	
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
		return apply_filters( 'usam_currency_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_currency_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_currency_set_properties', $properties, $this );
	
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

		do_action( 'usam_currency_pre_save', $this );	
		$where_col = $this->args['col'];			
	
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_currency_pre_update', $this );	

			$this->data = apply_filters( 'usam_currency_update_data', $this->data );	
			$formats = $this->get_data_format( );			
	
			$result = $wpdb->update( USAM_TABLE_CURRENCY, $this->data, array( $where_col => $where_val ), $formats, $where_format );	
				
			if ( $result ) 
			{
				$this->delete_cache( );				
				do_action( 'usam_currency_update', $this );	
			}
		} 
		else 
		{   
			do_action( 'usam_currency_pre_insert' );	
			
			if ( empty($this->data['code']) )
				return false;
		
			$this->data = apply_filters( 'usam_currency_insert_data', $this->data );
			$formats = $this->get_data_format( );	
			$result = $wpdb->insert( USAM_TABLE_CURRENCY, $this->data, $formats );
			if ( $result ) 
			{			
				$this->args = array('col' => 'code',  'value' => $this->data['code'] );		
				do_action( 'usam_currency_insert', $this );				
			}			
		} 		
		do_action( 'usam_currency_save', $this );

		return $result;
	}
}

// Обновить валюту 
function usam_update_currency( $code, $data, $colum = 'code' )
{
	$currency = new USAM_Currency( $code, $colum );
	$currency->set( $data );
	return $currency->save();
}

// Получить валюту
function usam_get_currency( $code = null, $colum = 'code' )
{
	$currency = new USAM_Currency( $code, $colum );
	return $currency->get_data( );	
}

// Добавить валюту
function usam_insert_currency( $data )
{
	$currency = new USAM_Currency( $data );
	$currency->save();
	return $currency->get('code');
}

// Удалить валюту
function usam_delete_currency( $code )
{
	$currency = new USAM_Currency( $code );
	return $currency->delete();
}

function usam_get_currency_sign( $code = null, $colum = 'code' )
{ 
	if ( !$code )
		$code = get_option("usam_currency_type");
		
	$currency = new USAM_Currency( $code, $colum );
	$symbol = $currency->get('symbol');		
	$currency_sign = !empty( $symbol ) ? $symbol : $currency->get('code');
	return $currency_sign;
}

function usam_get_currency_name( $code = null, $colum = 'code' )
{
	$currency = new USAM_Currency( $code, $colum );
	return $currency->get( 'code' ).' ('.$currency->get( 'name' ).')';
}

function usam_get_currencies( $args = array() )
{		
	$currencies_list = wp_cache_get( 'usam_currencies_list' );
	if( !$currencies_list || $currencies_list && empty($args) ) 
	{	
		global $wpdb;		
		
		$defaults = ['orderby' => 'name', 'order' => 'ASC', 'fields' => 'all'];
		$args = wp_parse_args( $args, $defaults );
		if ( $args['fields'] == 'all' )
			$fields = " * ";	
		elseif ( is_array($args['fields']) )
			$fields = implode( ', ',$args[ 'fields']);
		else 
			$fields = $args['fields'];
		
		$where = '';
		if ( !empty($args['where']) ) 
		{			
			foreach ( $args['where'] as $column => $value ) 
			{	
				switch( $value['compare'] )
				{							
					case 'in':	
						$compare = " IN ";					
						$condition = "('".implode("','",$value['condition'])."') ";					
					break;
					case 'not in':	
						$compare = " NOT IN ";				
						$condition = "('".implode("','",$value['condition'])."') ";					
					break;
					default:
						$compare = $value['compare'];				
						$condition = $value['condition'];
						$condition = esc_sql( $condition );		
						$condition = ( is_numeric( $condition ) ) ? " {$condition}" : "'{$condition}'";							
					break;
				}				
				$where_query[] = "{$column}{$compare}{$condition}";
			}
		}
		if ( isset($where_query ) )
			$where = " WHERE ".implode( ' AND ', $where_query );		
		$orderby = isset($args[ 'orderby' ] ) ? $args[ 'orderby' ] : '';
		$order = isset($args[ 'order' ] ) ? $args[ 'order' ] : '';		
		
		$query = "SELECT $fields FROM ".USAM_TABLE_CURRENCY." $where ORDER BY $orderby $order";	
		$currencies_list = $wpdb->get_results( $query );	
		if ( !isset($where_query ) )
		{			
			wp_cache_set( 'usam_currencies_list', $currencies_list );
		}		
	}
	return $currencies_list;
}