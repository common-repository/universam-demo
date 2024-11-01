<?php
class USAM_Document_Discount
{
	// строковые
	private static $string_cols = [
		'name',		
		'type_rule',	
		'code',
		'date_insert',
		'start_date',		
		'end_date',	
		'dtype',
		'document_type',		
	];
	// цифровые
	private static $int_cols = [
		'id',
		'document_id',		
		'product_id',		
		'rule_id',
	];
	// рациональные
	private static $float_cols = ['discount'];
	private $data = [];		
	private $changed_data = [];
	private $fetched = false;
	private $args = ['col'   => '', 'value' => ''];	
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
		if ( ! in_array( $col, ['id'] ) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];			
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_document_discount' );
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
		wp_cache_set( $id, $this->data, 'usam_document_discount' );		
		do_action( 'usam_document_discount_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_document_discount' );	
		do_action( 'usam_document_discount_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_document_discount_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_DISCOUNTS." WHERE id = '$id'");
		
		$this->delete_cache( );		
		do_action( 'usam_document_discount_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_DOCUMENT_DISCOUNTS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_document_discount_data', $data );			
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
		do_action( 'usam_document_discount_fetched', $this );	
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
		return apply_filters( 'usam_document_discount_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_document_discount_get_data', $this->data, $this );
	}

	
	public function set( $key, $value = null ) 
	{		
		if ( is_array( $key ) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = [$key => $value];			
		}		
		if ( ! is_array($this->data) )
			$this->data = [];
		$properties = apply_filters( 'usam_document_discount_set_properties', $properties, $this );
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

		do_action( 'usam_document_discount_pre_save', $this );	
		
		$result = false;	
		if( $this->args['col'] ) 
		{
			if ( empty($this->changed_data) )
				return true;
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_document_discount_pre_update', $this );	

			$this->data = apply_filters( 'usam_document_discount_update_data', $this->data );	
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_DOCUMENT_DISCOUNTS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_document_discount_update', $this );
		} 
		else 
		{   
			do_action( 'usam_document_discount_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );				
			
			if ( !isset($this->data['type_rule']) )
				$this->data['type_rule'] = '';	
			
			if ( !isset($this->data['product_id']) )
				$this->data['product_id'] = 0;			

			if ( empty($this->data['document_type']) || empty($this->data['document_id']) )
				return false;				
								
			$this->data['date_insert'] = date("Y-m-d H:i:s");	
			$this->data = apply_filters( 'usam_document_discount_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );	
			$result = $wpdb->insert( USAM_TABLE_DOCUMENT_DISCOUNTS, $this->data, $formats ); 
			if ( $result ) 
			{				
				if ( !isset($this->data['start_date']) )
					$this->data['start_date'] = NULL;			
				
				if ( !isset($this->data['end_date']) )
					$this->data['end_date'] = NULL;	
				
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];
				$this->exists = true;
			}
			do_action( 'usam_document_discount_insert', $this );
		} 		
		do_action( 'usam_document_discount_save', $this );
		return $result;
	}
}

function usam_get_document_discount( $id, $colum = 'id' )
{ 
	$discount = new USAM_Document_Discount( $id, $colum );
	return $discount->get_data( );	
}

function usam_delete_document_discount( $id ) 
{
	$discount = new USAM_Document_Discount( $id );
	$result = $discount->delete( );
	return $result;
}

function usam_insert_document_discount( $data ) 
{
	$discount = new USAM_Document_Discount( $data );
	$discount->save();
	return $discount->get('id');
}

function usam_update_document_discount( $id, $data )
{
	$discount = new USAM_Document_Discount( $id );
	$discount->set( $data );
	return $discount->save();
}

function usam_set_document_discount( $id, $data ) 
{
	$discount = usam_get_discount_rule( $id );
	$data['rule_id'] = $id;
	$discount = array_merge($discount, $data);
	return usam_insert_document_discount( $discount );
}
?>