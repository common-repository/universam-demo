<?php
class USAM_Lead_Product_Tax
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
		'lead_id',
		'is_in_price',		
		'rate',			
	);

	// рациональные
	private static $float_cols = array(
		'tax',		
	);	
	private $data    = array();		
	private $changed_data = array();		
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
			$this->data = wp_cache_get( $value, 'usam_lead_product_tax' );
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
		wp_cache_set( $id, $this->data, 'usam_lead_product_tax' );		
		do_action( 'usam_lead_product_tax_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_lead_product_tax' );	
		do_action( 'usam_lead_product_tax_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_lead_product_tax_before_delete', $data );
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_TAX_PRODUCT_LEAD." WHERE id = '$id'");
		$this->delete_cache( );		
		do_action( 'usam_lead_product_tax_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_TAX_PRODUCT_LEAD." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_lead_product_tax_data', $data );
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
		do_action( 'usam_lead_product_tax_fetched', $this );	
		$this->fetched = true;			
	}
	
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
		return apply_filters( 'usam_lead_product_tax_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_lead_product_tax_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_lead_product_tax_set_properties', $properties, $this );		
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

		do_action( 'usam_lead_product_tax_pre_save', $this );						
		$result = false;	
		if( $this->args['col'] ) 
		{	
			if ( empty($this->changed_data) )
				return true;	
					
			$where_format = self::get_column_format( $this->args['col'] );
			
			do_action( 'usam_lead_product_tax_pre_update', $this );	

			$this->data = apply_filters( 'usam_lead_product_tax_update_data', $this->data );			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_TAX_PRODUCT_LEAD, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
				$this->delete_cache( );		
			
			do_action( 'usam_lead_product_tax_update', $this );
		} 
		else 
		{   
			do_action( 'usam_lead_product_tax_pre_insert' );		
			unset( $this->data['id'] );	
			
			if ( !isset($this->data['lead_id']))
				$this->data['lead_id'] = 0;	
			if ( empty($this->data['product_id']))
				return false;
			
			$this->data = apply_filters( 'usam_lead_product_tax_insert_data', $this->data );		
			$format = $this->get_data_format( );	
			$formats = $this->get_data_format( $this->data );	
			$result = $wpdb->insert( USAM_TABLE_TAX_PRODUCT_LEAD, $this->data, $formats );
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];		
				$this->exists = true;	
				if ( !empty($this->data['lead_id']))
					wp_cache_delete( $this->data['lead_id'], 'usam_lead_product_taxes' );				
			}
			do_action( 'usam_lead_product_tax_insert', $this );
		} 		
		do_action( 'usam_lead_product_tax_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_update_lead_product_tax( $id, $data, $colum = 'id' )
{
	$lead_product_tax = new USAM_Lead_Product_Tax( $id, $colum );
	$lead_product_tax->set( $data );
	return $lead_product_tax->save();
}

// Получить 
function usam_get_lead_product_tax( $id, $colum = 'id' )
{
	$lead_product_tax = new USAM_Lead_Product_Tax( $id, $colum );
	$result = $lead_product_tax->get_data( );		
	return $result;	
}

// Добавить 
function usam_insert_lead_product_tax( $data )
{
	$lead_product_tax = new USAM_Lead_Product_Tax( $data );
	$lead_product_tax->save();
	return $lead_product_tax->get('id');
}

// Удалить 
function usam_delete_lead_product_tax( $id )
{
	$lead_product_tax = new USAM_Lead_Product_Tax( $id );
	return $lead_product_tax->delete();
}
?>