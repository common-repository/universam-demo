<?php
class USAM_Product_Document
{ // строковые
	private static $string_cols = array(			
		'name', 
		'date_insert', 
		'unit_measure', 		
	);
	// цифровые
	private static $int_cols = [
		'id',		
		'document_id',		
		'product_id',
		'used_bonuses',			
	];	
	private static $float_cols = array(
		'price', 
		'old_price',	
		'quantity',
		'unit',		
	);
	
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
	/* Содержит значения извлекаются из БД */
	private $data    = array();	
	private $changed_data = [];	
	private $fetched = false;	
	private $args    = ['col' => '', 'value' => ''];
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
	
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_product_document' );			
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
		wp_cache_set( $id, $this->data, 'usam_product_document' );	
		do_action( 'usam_product_document_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_product_document' );		
		do_action( 'usam_product_document_delete_cache', $this );
	}

	/**
	 * Удаляет
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->data['id'];		
		$data = $this->get_data();
		do_action( 'usam_product_document_before_delete', $data );
	
		$sql = $wpdb->prepare( "DELETE FROM ".USAM_TABLE_DOCUMENT_PRODUCTS." WHERE id = '%d'", $id );		
		$wpdb->query( $sql );	
		
		$this->delete_cache( );		
		do_action( 'usam_product_document_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_DOCUMENT_PRODUCTS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{						
			$this->exists = true;
			$this->data = apply_filters( 'usam_product_document_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}
			$this->update_cache( );
		}
		do_action( 'usam_product_document_fetched', $this );
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
		return apply_filters( 'usam_product_document_get_property', $value, $key, $this );
	}

		/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_product_document_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_product_document_set_properties', $properties, $this );	

		$this->fetch();		
		if ( ! is_array( $this->data ) )
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

		do_action( 'usam_product_document_pre_save', $this );	
		$result = false;		
		if( $this->args['col'] ) 
		{	// обновление				
			if ( empty($this->changed_data) )
				return true;
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_product_document_pre_update', $this );			
					
			$this->data = apply_filters( 'usam_product_document_update_data', $this->data );			
			if ( isset($this->changed_data['name']) && !usam_is_license_type('BUSINESS') && !usam_is_license_type('ENTERPRISE') )
				$this->data['name']	= 'Демо версия';	
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_DOCUMENT_PRODUCTS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );	
			if ( $result ) 
			{			
				$this->delete_cache( );			
				$id = $this->get('id');		
				$document_id = $this->get('document_id');	
				$document = usam_get_document( $document_id );
				if ( !empty($document) )
				{
					foreach ( $this->changed_data as $key => $value ) 
					{ 
						if ( isset($this->data[$key]) )
						{	
							usam_insert_change_history(['object_id' => $document['id'], 'object_type' => $document['type'], 'sub_object_id' => $id, 'operation' => 'edit', 'field' => 'product_'.$key, 'value' => $this->data[$key], 'old_value' => $value]);			
						}
					}
				}
			}
			do_action( 'usam_product_document_update', $this );
		} 
		else 
		{   // создание	
			do_action( 'usam_product_document_pre_insert' );							
			if ( empty($this->data['product_id']) )
				return false;
			
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );			
			if ( empty($this->data['name']) )
				$this->data['name'] = get_the_title($this->data['product_id']);		

			if ( !usam_is_license_type('BUSINESS') && !usam_is_license_type('ENTERPRISE') )
				$this->data['name']	= 'Демо версия';		
			
			$this->data = apply_filters( 'usam_product_document_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );			
			$result = $wpdb->insert( USAM_TABLE_DOCUMENT_PRODUCTS, $this->data, $formats );	
			if ( $result ) 
			{
				$this->exists = true;
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col'   => 'id',  'value' => $this->get( 'id' )];				
				do_action( 'usam_product_document_insert', $this );
			}			
		} 		
		do_action( 'usam_product_document_save', $this );
		$this->changed_data = [];
		return $result;
	}	
}