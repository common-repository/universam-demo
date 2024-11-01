<?php
class USAM_Custom_Product_Tab
{	
	 // строковые
	private static $string_cols = array(	
		'name',
		'title',	
		'description',	
		'code',			
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'active',	
		'global',
		'sort',			
	);
	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';		
		return '%d';
	}
	/**
	 *Содержит значения извлекаются из БД
	 * @since 4.9
	 */
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;

		$this->args = array( 'col' => $col, 'value' => $value );				
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_custom_product_tab' );			
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
		wp_cache_set( $id, $this->data, 'usam_custom_product_tab' );		
		do_action( 'usam_custom_product_tab_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_custom_product_tab' );	
		do_action( 'usam_custom_product_tab_update_cache', $this );
	}

	/**
	 * Удаляет
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get('id');		
		$data = $this->get_data();
		do_action( 'usam_custom_product_tab_before_delete', $data );
		
		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM " . USAM_TABLE_CUSTOM_PRODUCT_TABS . " WHERE id = %d", $id ) );	
		
		$this->delete_cache( );	
		do_action( 'usam_custom_product_tab_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_CUSTOM_PRODUCT_TABS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_custom_product_tab_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->update_cache( );	
		} 
		do_action( 'usam_custom_product_tab_fetched', $this );
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
		return apply_filters( 'usam_custom_product_tab_get_property', $value, $key, $this );
	}

	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_custom_product_tab_get_data', $this->data, $this );
	}
	
	
	public function set( $key, $value = null ) 
	{		
		if ( is_array($key) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = [$key => $value];
		}
		$properties = apply_filters( 'usam_custom_product_tab_set_properties', $properties, $this );		
		if ( ! is_array($this->data) )
			$this->data = [];
			
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
	 * Вернуть формат столбцов таблицы
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

		do_action( 'usam_custom_product_tab_pre_save', $this );	
		
		$result = false;		
		if( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )		
				return true;
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_custom_product_tab_pre_update', $this );
			$this->data = apply_filters( 'usam_custom_product_tab_update_data', $this->data );			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );	
					
			$result = $wpdb->update( USAM_TABLE_CUSTOM_PRODUCT_TABS, $data, [$this->args['col'] => $this->args['value']], $formats, array( $where_format ) );		
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_custom_product_tab_update', $this );
		} 
		else 
		{   // создание	
			do_action( 'usam_custom_product_tab_pre_insert' );	
								
			if ( isset($this->data['id']) )
				unset($this->data['id']);
			
			if ( empty($this->data['title']) )
				$this->data['title'] = '';
							
			if ( empty($this->data['title']) )
				$this->data['title'] = $this->data['name'];
			
			if ( empty($this->data['code']) )
				$this->data['code'] = sanitize_title($this->data['name']);		
			
			if ( empty($this->data['description']) )
				$this->data['description'] = '';		

			if ( empty($this->data['active']) )
				$this->data['active'] = 0;			
			
			$this->data = apply_filters( 'usam_custom_product_tab_insert_data', $this->data );				
			$formats = $this->get_data_format( $this->data );	
	
			$result = $wpdb->insert( USAM_TABLE_CUSTOM_PRODUCT_TABS, $this->data, $formats );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = ['col' => 'id',  'value' => $this->get( 'id' )];				
				do_action( 'usam_custom_product_tab_insert', $this );
			}			
		} 		
		do_action( 'usam_custom_product_tab_save', $this );
		return $result;
	}
}

function usam_get_custom_product_tab( $value )
{	
	$custom_product_tab = new USAM_Custom_Product_Tab($value);	
	$custom_product_tab_data = $custom_product_tab->get_data();	
	return $custom_product_tab_data;	
}

function usam_update_custom_product_tab( $id, $data )
{	
	if ( !empty($data) ) 
	{
		$custom_product_tab = new USAM_Custom_Product_Tab( $id );	
		$custom_product_tab->set( $data );	
		return $custom_product_tab->save();
	}
	return true;
}

function usam_insert_custom_product_tab( $value )
{	
	$custom_product_tab = new USAM_Custom_Product_Tab( $value );	
	$custom_product_tab->save();
	$custom_product_tab_id = $custom_product_tab->get('id');	
	return $custom_product_tab_id;		 
}

function usam_delete_custom_product_tab( $value, $colum = 'id' )
{	
	$subscriber = new USAM_Custom_Product_Tab( $value, $colum );
	return $subscriber->delete();		 
}