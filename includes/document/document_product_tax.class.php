<?php

class USAM_Document_Product_Tax
{	
	 // строковые
	private static $string_cols = array(
		'name',	
		'unit_measure',		
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'tax_id',				
		'product_id',			
		'document_id',
		'is_in_price',		
		'rate',			
	);

	// рациональные
	private static $float_cols = array(
		'tax',		
	);	
	private $data    = array();		
	private $fetched = false;
	private $args    = array( 'col'   => '', 'value' => '' );	
	private $exists  = false; // если существует строка в БД

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
			$this->data = wp_cache_get( $value, 'usam_document_product_tax' );
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
		wp_cache_set( $id, $this->data, 'usam_document_product_tax' );		
		do_action( 'usam_document_product_tax_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_document_product_tax' );	
		do_action( 'usam_document_product_tax_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_document_product_tax_before_delete', $data );
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_TAX_PRODUCT_DOCUMENT." WHERE id = '$id'");
		$this->delete_cache( );		
		do_action( 'usam_document_product_tax_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_TAX_PRODUCT_DOCUMENT." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_document_product_tax_data', $data );			
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
		do_action( 'usam_document_product_tax_fetched', $this );	
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
		return apply_filters( 'usam_document_product_tax_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_document_product_tax_get_data', $this->data, $this );
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
		$this->data = apply_filters( 'usam_document_product_tax_set_properties', $this->data, $this );			
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

		do_action( 'usam_document_product_tax_pre_save', $this );	
		$where_col = $this->args['col'];
					
		$result = false;	
		if ( $where_col ) 
		{		
			$where_format = self::get_column_format( $where_col );
			
			do_action( 'usam_document_product_tax_pre_update', $this );		
			
			$where = array( $this->args['col'] => $this->args['value']);

			$this->data = apply_filters( 'usam_document_product_tax_update_data', $this->data );			
			$format = $this->get_data_format( );
			$this->data_format( );	
						
			$result = $wpdb->update( USAM_TABLE_TAX_PRODUCT_DOCUMENT, $this->data, $where, $format, [$where_format] );	
			if ( $result ) 
				$this->delete_cache( );		
			
			do_action( 'usam_document_product_tax_update', $this );
		} 
		else 
		{   
			do_action( 'usam_document_product_tax_pre_insert' );		
			unset( $this->data['id'] );	
			
			if ( !isset($this->data['document_id']))
				$this->data['document_id'] = 0;
			
			$this->data = apply_filters( 'usam_document_product_tax_insert_data', $this->data );		
			$format = $this->get_data_format( );	
			$this->data_format( );			
							
			$result = $wpdb->insert( USAM_TABLE_TAX_PRODUCT_DOCUMENT, $this->data, $format );
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];		
				$this->exists = true;
				if ( !empty($this->data['document_id']))
					wp_cache_delete( $this->data['document_id'], 'usam_document_product_taxes' );		
			}
			do_action( 'usam_document_product_tax_insert', $this );
		} 		
		do_action( 'usam_document_product_tax_save', $this );

		return $result;
	}
}

function usam_update_document_product_tax( $id, $data, $colum = 'id' )
{
	$document_product_tax = new USAM_Document_Product_Tax( $id, $colum );
	$document_product_tax->set( $data );
	return $document_product_tax->save();
}

// Получить 
function usam_get_document_product_tax( $id, $colum = 'id' )
{
	$document_product_tax = new USAM_Document_Product_Tax( $id, $colum );
	$result = $document_product_tax->get_data( );		
	return $result;	
}

// Добавить 
function usam_insert_document_product_tax( $data )
{
	$document_product_tax = new USAM_Document_Product_Tax( $data );
	$document_product_tax->save();
	return $document_product_tax->get('id');
}

// Удалить 
function usam_delete_document_product_tax( $id )
{
	$document_product_tax = new USAM_Document_Product_Tax( $id );
	return $document_product_tax->delete();
}
?>