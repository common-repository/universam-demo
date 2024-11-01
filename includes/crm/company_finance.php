<?php
class USAM_Company_Finance
{	 // строковые
	private static $string_cols = array( );
	// цифровые
	private static $int_cols = array(
		'id',		
		'company_id',
		'year',
		'code',
		'value',		
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
	private $changed_data = array();
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
		
		$this->args = ['col' => $col, 'value' => $value];		
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_company_finance' );			
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
		wp_cache_set( $id, $this->data, 'usam_company_finance' );
		do_action( 'usam_company_finance_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_company_finance' );			
		do_action( 'usam_company_finance_update_cache', $this );
	}

	/**
	 * Удаляет
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get('id');		
		$data = $this->get_data();
		do_action( 'usam_company_finance_before_delete', $data );			
								
		$result = $wpdb->delete( USAM_TABLE_COMPANY_FINANCE, ['id' => $id], ['%d']);
		
		$this->delete_cache( );		
		do_action( 'usam_company_finance_delete', $id );
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
		$this->exists = false;
		if ( $data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_COMPANY_FINANCE . " WHERE {$col} = {$format}", $value ), ARRAY_A ) ) 
		{				
			$this->exists = true;
			$this->data = apply_filters( 'usam_company_finance_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}
			$this->update_cache();
		}
		do_action( 'usam_company_finance_fetched', $this );
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
		if ( empty($this->data) || !array_key_exists($key, $this->data) )
			$this->fetch();		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_company_finance_get_property', $value, $key, $this );
	}

		/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_company_finance_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_company_finance_set_properties', $properties, $this );	

		$this->fetch();		
		if ( ! is_array( $this->data ) )
			$this->data = array();

		foreach ( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{
				$previous = $this->get( $key );	
				$this->data[$key] = $value;						
				if ( $value != $previous )
				{				
					$this->changed_data[$key] = $previous;	
				}
			}
		}				
		return $this;
	}

	/**
	 * Вернуть формат столбцов таблицы
	 */
	private function get_data_format( $data ) 
	{
		$formats = array();
		foreach( $data as $key => $value ) 
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
		
	public function save()
	{
		global $wpdb;

		do_action( 'usam_company_finance_pre_save', $this );	
		
		$where_col = $this->args['col'];
		$result = false;		
		if ( $where_col ) 
		{	// обновление			
			if ( empty($this->changed_data) )
				return true;	
			
			$subscriber_id = $this->get('id');
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_company_finance_pre_update', $this );
			$this->data = apply_filters( 'usam_company_finance_update_data', $this->data );			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_COMPANY_FINANCE, $data, [$where_col => $where_val], $formats, [$where_format]);		
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_company_finance_update', $this );
		} 
		else 
		{
			do_action( 'usam_company_finance_pre_insert' );	
			
			if ( empty($this->data['company_id']) )
				return false;	
			if ( empty($this->data['year']) )
				return false;	
			if ( empty($this->data['code']) )
				return false;	
			if ( !isset($this->data['value']) )
				return false;	
				
			$this->data = apply_filters( 'usam_company_finance_insert_data', $this->data );				
			$formats = $this->get_data_format( $this->data );			
			$result = $wpdb->insert( USAM_TABLE_COMPANY_FINANCE, $this->data, $formats );	
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );				
				$this->exists = true;
				$this->args = array('col'   => 'id',  'value' => $this->get( 'id' ), );				
				do_action( 'usam_company_finance_insert', $this );
			}			
		} 		
		do_action( 'usam_company_finance_save', $this );
		$this->changed_data = [];
		return $result;
	}		
}

function usam_get_company_finance( $value, $colum = 'id' )
{	
	$class = new USAM_Company_Finance($value, $colum);	
	$data = $class->get_data();	
	if ( empty($data) )
		return array();
	
	return $data;	
}

function usam_update_company_finance( $id, $data )
{		
	$class = new USAM_Company_Finance( $id );	
	$class->set( $data );	
	return $class->save();
}

function usam_insert_company_finance( $value )
{	
	$class = new USAM_Company_Finance( $value );	
	$class->save();
	$group_id = $class->get('id');	
	return $group_id;		
}

function usam_delete_company_finance( $value, $colum = 'id' )
{	
	$class = new USAM_Company_Finance( $value, $colum );
	return $class->delete();		 
}