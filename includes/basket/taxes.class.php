<?php
class USAM_Tax
{	
	 // строковые
	private static $string_cols = array(
		'name',		
		'description',		
		'date_update',
		'setting',	
	);
	// цифровые
	private static $int_cols = array(
		'id',		
		'active',				
		'sort',			
		'type_payer',
		'is_in_price',		
	);
	// рациональные
	private static $float_cols = array(
		'value',		
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
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_taxes' );
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
		wp_cache_set( $id, $this->data, 'usam_taxes' );		
		do_action( 'usam_taxes_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_taxes' );	
		do_action( 'usam_taxes_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		$id = $this->get( 'id' );
		$data = $this->get_data();
		do_action( 'usam_taxes_before_delete', $data );
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_TAXES." WHERE id = '$id'");
		$this->delete_cache( );		
		do_action( 'usam_taxes_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_TAXES." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{
			$data['setting'] = unserialize( $data['setting'] );		
			$this->exists = true;
			$this->data = apply_filters( 'usam_taxes_data', $data );			
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
		do_action( 'usam_taxes_fetched', $this );	
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
		return apply_filters( 'usam_taxes_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 * @since 4.9
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_taxes_get_data', $this->data, $this );
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
		if ( ! is_array($this->data) )
			$this->data = array();	
		
		foreach ( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
				$this->data[$key] = $value;
		}
		$this->data = apply_filters( 'usam_taxes_set_properties', $this->data, $this );			
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

		do_action( 'usam_taxes_pre_save', $this );	
		$where_col = $this->args['col'];
			
		if ( isset($this->data['setting']) )	
			$this->data['setting'] = serialize( $this->data['setting'] );
		
		$this->data['date_update']      = date( "Y-m-d H:i:s" );		
		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];			
			$where_format = self::get_column_format( $where_col );
			
			do_action( 'usam_taxes_pre_update', $this );		
			
			$this->data = apply_filters( 'usam_taxes_update_data', $this->data );			
			$format = $this->get_data_format( );
			$this->data_format( );	
						
			$result = $wpdb->update( USAM_TABLE_TAXES, $this->data, [$this->args['col'] => $where_val], $format, [ $where_format] );	
			if ( $result ) 
				$this->delete_cache( );		
			
			do_action( 'usam_taxes_update', $this );
		} 
		else 
		{   
			do_action( 'usam_taxes_pre_insert' );		
			unset( $this->data['id'] );	
			
			$this->data = apply_filters( 'usam_taxes_insert_data', $this->data );		
			$format = $this->get_data_format( );	
			$this->data_format( );			
					
			$result = $wpdb->insert( USAM_TABLE_TAXES, $this->data, $format );
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );			
			}
			do_action( 'usam_taxes_insert', $this );
		} 		
		do_action( 'usam_taxes_save', $this );

		return $result;
	}
}

function usam_get_tax( $value )  
{
   $tax = new USAM_Tax( $value );	
   return $tax->get_data();
}

// Получить налоговые ставки
function usam_get_taxes( $args = array() ) 
{
	global $wpdb;
	
	if ( isset($args['fields']) )
	{
		$fields = $args['fields'] == 'all'?'*':implode( ",", $args['fields'] );
	}
	else
		$fields = '*';
	
	$_where = array('1=1');
	if ( isset($args['active']) && $args['active'] == 0 )
		$_where[] = "active = '0'";
	else
		$_where[] = "active = '1'";
	
	if ( isset($args['type_payer']) )
	{
		$type_payer = is_array($args['type_payer'])?$args['type_payer']:array($args['type_payer']);		
		$_where[] = "type_payer IN (".implode(',',$type_payer).")";	
	}
	if ( isset($args['is_in_price']) )
		$_where[] = "is_in_price = '".$args['is_in_price']."'";	
	
	if ( isset($args['ids']) )
	{		
		$ids = implode( ',', wp_parse_id_list( $args['ids'] ) );
		$_where[] = "id IN( $ids )";
	}
	
	$where = implode( " AND ", $_where);
		
	if ( isset($args['orderby']) )
		$orderby = $args['orderby'];
	else
		$orderby = 'sort';
	$orderby = "ORDER BY $orderby";
	
	if ( isset($args['order']) )	
		$order = $args['order'];	
	else
		$order = 'DESC';	
	
	$result = $wpdb->get_results( "SELECT $fields FROM ".USAM_TABLE_TAXES." WHERE $where $orderby $order" );
	return $result;
}

function usam_calculate_tax( $args, $product_id = 0 ) 
{				
	static $taxes = null;  
	$args = array_merge(['location_ids' => [], 'type_payer' => 0, 'payment' => 0, 'price' => 0], $args );
	
	$new_product_taxes = [];	
	if ( empty($args['price']) )
		return ['tax' => 0, 'product_taxes' => $new_product_taxes];
	
	$compare = new USAM_Compare();	
	$result_tax = 0;
	if ( $taxes === null )
		$taxes = usam_get_taxes(['active' => 1]);		
	if ( !empty($taxes) )
	{	
		if ( !empty($args['contact']) )			
			$location = usam_get_contact_metadata( $args['contact'], 'location' );
		elseif ( !empty($args['company']) )
			$location = usam_get_company_metadata( $args['company'], 'contactlocation' );			
		if ( !empty($location) )	
			$args['location_ids'] = array_values(usam_get_address_locations( $location, 'id' )); 
		elseif ( !isset($args['location_ids']) )
			$args['location_ids'] = [];			
		foreach( $taxes as $value )
		{			
			$value->setting = maybe_unserialize( $value->setting );		
			if( (empty($value->type_payer) || $value->type_payer == $args['type_payer']) && (empty($value->setting['payments']) || in_array($args['payment'], $value->setting['payments'])) )
			{	
				if ( empty($value->setting['locations']) || $compare->compare_arrays('equal', $value->setting['locations'], $args['location_ids'] ) )
				{	
					$result = true;
					if ( $product_id )
					{
						foreach( ['category', 'brands'] as $key ) 
						{	
							if ( !empty($value->setting[$key]) )					
							{													
								$result = $compare->compare_terms( $product_id, 'usam-'.$key, ['logic' => 'equal', 'value' => $value->setting[$key]]);	
								if ( !$result )							
									break;
							} 
						}							
					}
					if ( $result )
					{
						if ( $value->is_in_price )					
							$tax = $args['price']*$value->value/(100+$value->value);						
						else
						{
							$tax = $args['price']*$value->value/100;
							$result_tax += $tax;
						}
						$new_product_taxes[] = ['tax_id' => $value->id, 'product_id' => $product_id, 'name' => $value->name, 'tax' => $tax, 'is_in_price' => $value->is_in_price, 'rate' => $value->value ];
					}
				}
			}
		}	
	}	
	return ['tax' => $result_tax, 'product_taxes' => $new_product_taxes];
}
?>