<?php
class USAM_Keyword
{
	// строковые
	private static $string_cols = array(
		'keyword',
		'source',	
		'link',		
	);
	// цифровые
	private static $int_cols = array(
		'id',	
		'parent',
		'check',		
		'yandex_hits',	
		'importance',		
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
			$this->data = wp_cache_get( $value, 'usam_keyword' );
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
		wp_cache_set( $id, $this->data, 'usam_keyword' );	
		do_action( 'usam_keyword_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_keyword' );	
		do_action( 'usam_keyword_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_keyword_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_KEYWORDS." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_keyword_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_KEYWORDS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_keyword_data', $data );		
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}			
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_keyword_fetched', $this );	
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
		return apply_filters( 'usam_keyword_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_keyword_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_keyword_set_properties', $properties, $this );
	
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

		do_action( 'usam_keyword_pre_save', $this );	
		$where_col = $this->args['col'];
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_keyword_pre_update', $this );			
			$where = array( $this->args['col'] => $where_val);

			$this->data = apply_filters( 'usam_keyword_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$this->data_format( );		
			$data = $this->data;
		
			$result = $wpdb->update( USAM_TABLE_KEYWORDS, $this->data, $where, $formats, array( $where_format ) );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_keyword_update', $this );
		} 
		else 
		{   
			do_action( 'usam_keyword_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			if ( empty($this->data['check']) )
				$this->data['check'] = 0;	
			
			if ( empty($this->data['source']) )
				$this->data['source'] = 'manager';				
						
			$this->data = apply_filters( 'usam_keyword_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );	
					
			$result = $wpdb->insert( USAM_TABLE_KEYWORDS, $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );					
			}
			do_action( 'usam_keyword_insert', $this );
		} 		
		do_action( 'usam_keyword_save', $this );

		return $result;
	}
}

function usam_get_keyword( $id, $colum = 'id' )
{
	$keyword = new USAM_Keyword( $id, $colum );
	return $keyword->get_data( );	
}

function usam_delete_keyword( $id ) 
{
	$keyword = new USAM_Keyword( $id );
	$result = $keyword->delete( );
	return $result;
}

function usam_insert_keyword( $data ) 
{
	$keyword = new USAM_Keyword( $data );
	$keyword->save();
	return $keyword->get('id');
}

function usam_update_keyword( $id, $data ) 
{
	if ( $id )
	{
		$keyword = new USAM_Keyword( $id );
		$keyword->set( $data );
		return $keyword->save();
	}
	else
		return false;
}

function usam_get_keyword_sources( ) 
{
	return array( 'manager' => __("Добавлено менеджером","usam"), 'wordstat' => __("Wordstat","usam"), 'yandex' => __("Яндекс популярные запросы","usam"), 'google' => __("Google","usam") );
}
?>