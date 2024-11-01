<?php
/**
 * Лиды
 */ 
class USAM_Lead
{
	const CREATURE  = 'not_processed';
	const CLOSED    = 'order';	
	// строковые
	private static $string_cols = [
		'name',
		'type_price',
		'source',
		'date_insert',	
		'date_status_update',		
		'status',
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'number_products',			
		'user_id',		
		'bank_account_id',
		'contact_id',
		'company_id',
		'manager_id',
		'type_payer',	
	];
	// рациональные
	private static $float_cols = [
		'totalprice',		
	];			
	private $product_db_format = ['id' => '%d', 'product_id' => '%d', 'name' => '%s', 'lead_id' => '%d', 'price' => '%f', 'old_price' => '%f', 'quantity' => '%f', 'unit_measure' => '%s', 'date_insert' => '%s'];
	private $changed_data = [];
	private $recalculated = false; // Пересчитать
	private $data     = [];		
	private $fetched  = false;
	private $args     = ['col'   => '', 'value' => ''];	
	private $exists   = false; // если существует строка в БД
		
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
			$this->data = wp_cache_get( $value, 'usam_lead' );
		}		
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
		else
			$this->fetch();
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
		wp_cache_set( $id, $this->data, 'usam_lead' );		
		do_action( 'usam_lead_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_lead' );	
		do_action( 'usam_lead_delete_cache', $this );	
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_LEADS." WHERE {$col} = {$format}", $value );
		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_lead_data', $data );			
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
		do_action( 'usam_lead_fetched', $this );	
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
		return apply_filters( 'usam_lead_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_lead_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_lead_set_properties', $properties, $this );
		foreach ( $properties as $key => $value ) 
		{	
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{				
				$previous = $this->get( $key );			
				if ( $value != $previous )
					$this->changed_data[$key] = $previous;	
				$this->data[$key] = $value;
			}
		}		
		return $this;
	}
	
	private function product_key_cleaning( $product )
	{	
		foreach ( $product as $key => $value ) 
		{						
			if ( !isset($this->product_db_format[$key]) )		
				unset($product[$key]);				
		}
		return $product;
	}
	
	// Формат таблицы товаров заказа
	private function get_data_format_product( $data )
	{		
		$formats = array();		
		foreach ( $data as $key => $value ) 
		{						
			if ( isset($this->product_db_format[$key]) )		
				$formats[$key] = $this->product_db_format[$key];				
		}
		return $formats;
	}
	
	public function set_products( $products )
	{
		$id = $this->get( 'id' );		
		$lead_products = usam_get_products_lead( $id );
		$add_products = [];
		$update_products = [];
		$processed = [];
		$result = false;
		foreach ( $products as $new_product ) 
		{
			$add = true;
			if ( empty($new_product['unit_measure']) )
				$new_product['unit_measure'] = usam_get_product_property($new_product['product_id'], 'unit_measure_code');
			foreach ( $lead_products as $k => $product ) 
			{
				if ( isset($new_product['id']) && ctype_digit($new_product['id']) || $product->product_id == $new_product['product_id'] && $product->unit_measure == $new_product['unit_measure'] )
				{
					$new_product['id'] = $product->id;
					$update_products[] = $new_product;					
					unset($lead_products[$k]);
					$add = false;
					break;
				}
			}
			if ( $add && ( empty($processed[$new_product['product_id']]) || !in_array($new_product['unit_measure'], $processed[$new_product['product_id']]) ))
				$add_products[] = $new_product;
			$processed[$new_product['product_id']][] = $new_product['unit_measure'];
		}		
		if ( $this->add_products( $add_products, false ) )
			$result = true;	
		if ( $this->update_products( $update_products, false ) )
			$result = true;
		if ( $this->delete_products( $lead_products, false) )
			$result = true;				
		if ( $result )
		{
			$this->recalculated = true;	
			$this->save();
		}
		return $result;
	}
	
	// Изменить купленные товары
	public function update_products( $products, $calculate = true ) 
	{	
		global $wpdb;		
		
		if ( $this->exists == 0 || empty($products) )
			return false;
		
		require_once(USAM_FILE_PATH.'/includes/document/lead_product_tax.class.php');
			
		$products = apply_filters( 'usam_edit_lead_products_set_properties', $products, $this );	
		do_action( 'usam_edit_lead_products_pre_edit', $this );

		$lead_id = $this->get('id');
		$lead_products = usam_get_products_lead( $lead_id );
		foreach( $products as $k => &$new_product )		
		{
			$delete = true;
			foreach( $lead_products as $product )
			{
				if ( isset($new_product['id']) && $product->id == $new_product['id'] )
				{
					$delete = false;
					if ( isset($new_product['old_price']) && $new_product['old_price'] == 0 )
						$new_product['old_price'] = $new_product['price'];	
					$new_product['product_id'] = $product->product_id;	
					if ( !isset($new_product['unit_measure']) )		
						$new_product['unit_measure'] = $product->unit_measure;
				}				
			}
			if ( $delete )
				unset($products[$k]);
		}		
		$min_selling_price = get_option('usam_min_selling_price_product');	
		$product_taxes = usam_get_lead_product_taxes( $lead_id );			
		$delete_product_taxes = [];
		foreach( $products as $k => $product )
		{				
			$product = $this->product_key_cleaning( $product );
			if ( $min_selling_price )
			{
				$price = usam_get_product_price( $product['product_id'], $min_selling_price );				
				if ( isset($product['old_price']) && $price > $product['old_price'] )
					$product['old_price'] = $price;
				if ( isset($product['price']) && $price > $product['price'] )
					$product['price'] = $price;			
			}
			elseif ( isset($product['price']) && 0 > $product['price'] )
				$product['price'] = 0;	
						
			if ( isset($product['price']) )
			{
				$result_tax = $this->calculate_tax( $product );
				if( !empty($result_tax['product_taxes']) )
				{
					foreach ( $result_tax['product_taxes'] as $new_tax ) 
					{
						foreach( $product_taxes as $tax ) 
						{
							if ( $tax->product_id == $product['product_id'] && $tax->unit_measure == $product['unit_measure'] )							
							{
								if ( $tax->tax_id == $new_tax['tax_id'] )
								{
									$new_tax['lead_id'] = $lead_id;
									usam_update_lead_product_tax( $tax->id, $new_tax );
								}
								continue 2;
							}							
						}	
						$new_tax['lead_id'] = $lead_id;
						usam_insert_lead_product_tax( $new_tax );
					}	
				}
				else
					$delete_product_taxes[] = ['unit_measure' => $product['unit_measure'], 'product_id' => $product['product_id'] , 'lead_id' => $lead_id];
			}				
			$format = $this->get_data_format_product( $product );			
			$where = ['id' => $product['id'], 'lead_id' => $lead_id];						
			$format_where = $this->get_data_format_product( $where );		
			if ( $wpdb->update( USAM_TABLE_PRODUCTS_LEAD, $product, $where, $format, $format_where ) )
			{
				foreach( $lead_products as $number => $p )
				{						
					if ( $product['id'] == $p->id )
					{
						foreach( $product as $key => $value )
						{
							if ( $value != $p->$key && $key != 'old_price' )
							{
								$this->change_history( $p->product_id, 'edit', 'product_'.$key, $value, $p->$key );		
							}
							$lead_products[$number]->$key = $value;
						}						
					}
				}
			}
		} 
		if ( $delete_product_taxes && $product_taxes )	
		{
			foreach( $delete_product_taxes as $tax )
				$wpdb->delete( USAM_TABLE_TAX_PRODUCT_LEAD, $tax);
		}
		wp_cache_set( $lead_id, $lead_products, "usam_products_lead" );
		wp_cache_delete( $this->get( 'id' ), 'usam_lead_product_taxes' );
		
		if ( $calculate )
		{
			$this->recalculated = true;	
			$this->save();
		}
		do_action( 'usam_edit_lead_products_edit', $this );
		return true;
	}
	
	public function calculate_tax( $product ) 
	{
		$location_ids = [];
		$location = usam_get_lead_customerdata( $this->get('id'), 'location' );	
		if ( $location )
			$location_ids = array_values(usam_get_address_locations( $location, 'id' )); 
		return usam_calculate_tax(['location_ids' => $location_ids, 'type_payer' => $this->get('type_payer'), 'price' => $product['price']], $product['product_id'] );
	}
		
	public function add_products( $products, $calculate = true ) 
	{
		global $wpdb;
				
		if ( $this->exists == 0 || empty($products) )
			return false;
				
		$result = false;
		
		$product_ids = array();
		foreach( $products as $product )
		{
			if ( !empty($product['product_id']) )
				$product_ids[] = $product['product_id'];
		}
		if ( empty($product_ids) )
			return false;
		
		require_once(USAM_FILE_PATH.'/includes/document/lead_product_tax.class.php');
		require_once( USAM_FILE_PATH .'/includes/document/document_discount.class.php' );
		
		$type_price = $this->get('type_price');
		$lead_id = $this->get('id');
		if ( !$type_price )
			$type_price = usam_get_customer_price_code();
		
		$products_discounts = usam_get_products_discounts(['product_id' => $product_ids, 'code_price' => $type_price]);							
		foreach( $products as $product )
		{
			$product = $this->product_key_cleaning( $product );
			$product['date_insert'] = date( "Y-m-d H:i:s" );
			$product['lead_id'] = $lead_id;									
			
			if ( isset($product['id']) )
				unset($product['id']);		

			if ( empty($product['product_id']) )
				continue;
			
			if ( empty($product['unit_measure']) )
				$product['unit_measure'] = usam_get_product_property($product['product_id'], 'unit_measure_code');
						
			if ( !isset($product['price']) )
				$product['price'] = usam_get_product_price( $product['product_id'], $type_price, $product['unit_measure'] );
			if ( !isset($product['old_price']) )
				$product['old_price'] = usam_get_product_old_price( $product['product_id'], $type_price, $product['unit_measure'] );			
			if ( empty($product['old_price']) )
				$product['old_price'] = $product['price'];	
						
			$product['quantity'] = !isset($product['quantity']) ? 1:$product['quantity'];																	
			if ( empty($product['name']) )
				$product['name'] = get_the_title( $product['product_id'] );
			$product = apply_filters( 'usam_insert_product_lead', $product, $lead_id );	
										
			$format = $this->get_data_format_product( $product );			
			if ( $wpdb->insert( USAM_TABLE_PRODUCTS_LEAD, $product, $format ) ) 
			{					
				$result = true;
				$insert_id = $wpdb->insert_id;
				$product['id'] = $insert_id;	
				$result_tax = $this->calculate_tax( $product );
				if( !empty($result_tax['product_taxes']) )
				{
					foreach ( $result_tax['product_taxes'] as $new_tax ) 
					{
						$new_tax['lead_id'] = $lead_id;
						usam_insert_lead_product_tax( $new_tax );
					}	
				}	
				if ( !empty($products_discounts[$product['product_id']]) && $product['old_price'] !== $product['price'] )
				{ // Скидка на товар		
					foreach( $products_discounts[$product['product_id']] as $product_discount )
					{					
						usam_set_document_discount( $product_discount->discount_id, ['document_id' => $lead_id, 'document_type' => 'lead', 'product_id' => $product['product_id']] );
					}
				}			
				$this->change_history( $product['product_id'], 'add', 'product', $product['name'] );					
			}
		}					
		wp_cache_delete( $this->get( 'id' ), 'usam_products_lead' );
		wp_cache_delete( $this->get( 'id' ), 'usam_lead_product_taxes' );	
		
		if ( $calculate )
		{
			$this->recalculated = true;				
			$this->save();		
		}
		return $result;
	}
	
	// Получить данные купленного товара
	public function get_lead_product( $product_id, $unit_measure ) 
	{
		$lead_id = $this->get('id');
		$products = usam_get_products_lead( $lead_id );	
		foreach( $products as $product )
		{
			if ( $product->product_id == $product_id && $product->unit_measure == $unit_measure )
				return $product;			
		}	
		return array();
	}
	
	// Удалить товар из заказа
	public function delete_products( $products, $calculate = true ) 
	{		
		global $wpdb;
				
		if ( $this->exists == 0 || empty($products) )
			return false;
		
		$ids = [];
		$product_ids = [];
		foreach( $products as $product )
		{
			$ids[] = $product->id;		
			$product_ids[] = $product->product_id;
		}
		$lead_id = $this->get('id');		
		$wpdb->query("DELETE FROM `".USAM_TABLE_TAX_PRODUCT_LEAD."` WHERE `lead_id`=$lead_id AND `product_id` IN (".implode(',',$product_ids).")");
		$wpdb->query("DELETE FROM " . USAM_TABLE_DOCUMENT_DISCOUNTS . " WHERE `document_id`=$lead_id AND document_type='lead' AND `product_id` IN (".implode(',',$product_ids).")");
		$result_deleted = $wpdb->query("DELETE FROM `".USAM_TABLE_PRODUCTS_LEAD."` WHERE `id` IN (".implode(',',$ids).")");
		if ( $result_deleted )
		{
			wp_cache_delete( $this->get( 'id' ), 'usam_products_lead' );
			wp_cache_delete( $this->get( 'id' ), 'usam_lead_product_taxes' );	
			$this->delete_cache();	
			if ( $calculate )
			{
				$this->recalculated = true;				
				$this->save();		
			}				
			foreach( $products as $product )
				$this->change_history( $product->product_id, 'delete', 'product', $product->name );			
		}
		return $result_deleted;
	}
	
	private function change_history( $sub_object_id, $operation, $field = '', $value = '', $old_value = '' ) 
	{
		if ( !$this->is_creature() || $operation == 'add' && $sub_object_id == 0 )
		{  		
			$id = $this->get('id');
			usam_insert_change_history(['object_id' => $id, 'object_type' => 'lead', 'sub_object_id' => $sub_object_id, 'operation' => $operation, 'field' => $field, 'value' => $value, 'old_value' => $old_value]);	
		} 			
		return false;		
	}
	
	public function is_creature() {
		return $this->get( 'status' ) == self::CREATURE;
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

		do_action( 'usam_lead_pre_save', $this );				
		$result = false;	
		if( $this->args['col'] ) 
		{	
			$this->calculate_totalprice();			
			if ( empty($this->changed_data) )
				return true;
			
			if ( isset($this->changed_data['status']) ) 
				$this->data['date_status_update'] = date( "Y-m-d H:i:s" );							
			
			$where_format = self::get_column_format( $this->args['col'] );
			
			do_action( 'usam_lead_pre_update', $this );			
			
			$this->data = apply_filters( 'usam_lead_update_data', $this->data );			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );		
			$result = $wpdb->update( USAM_TABLE_LEADS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{
				$this->delete_cache( );					
				if ( !$this->is_creature() )
				{ 
					foreach ( $this->changed_data as $key => $value ) 
					{ 
						if ( $key != 'number_products ' && isset($this->data[$key]) )
							$this->change_history( 0, 'edit', $key, $this->data[$key], $value );
					}			
				}
				if ( isset($this->changed_data['status']) ) 
				{		
					$current_status = $this->get( 'status' );					
					do_action( 'usam_update_lead_status', $current_status, $this->changed_data['status'], $this );						
					usam_update_object_count_status( $current_status, 'lead' );
					usam_update_object_count_status( $this->changed_data['status'], 'lead' );	
					if ( $current_status == 'order' )
					{
						$products = usam_get_products_lead( $this->data['id'] );
						$data = array_merge( $this->data, ['status' => 'job_dispatched'] );
						usam_insert_order( $data, $products);					
					}
				}
			}
			do_action( 'usam_lead_update', $this );	
		} 
		else 
		{   
			do_action( 'usam_lead_pre_insert' );			
			unset( $this->data['id'] );				
			
			if ( empty($this->data['type_price']) )
				$this->data['type_price'] = usam_get_manager_type_price();
						
			if ( empty($this->data['type_payer']) )
				$this->data['type_payer'] = usam_get_type_payer_customer();
			
			if ( !isset($this->data['status']) )
				$this->data['status'] = self::CREATURE;
			
			if ( !isset($this->data['name']) )
				$this->data['name'] = __('Новый лид', 'usam');
						
			if ( empty($this->data['bank_account_id']) )
				$this->data['bank_account_id'] = get_option( 'usam_shop_company', 0 );	
								
			if ( empty($this->data['date_insert']) )				
				$this->data['date_insert'] = date("Y-m-d H:i:s");			
					
			$this->data = apply_filters( 'usam_lead_insert_data', $this->data );		
			$this->calculate_totalprice();					
			$formats = $this->get_data_format( $this->data );			
			$result = $wpdb->insert( USAM_TABLE_LEADS, $this->data, $formats );
			if ( $result ) 
			{					
				$this->exists = true;
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];		
			
				usam_update_object_count_status( $this->data['status'], 'lead' );
				do_action( 'usam_lead_insert', $this );
			}			
		} 		
		do_action( 'usam_lead_save', $this );
		$this->changed_data = [];
		return $result;
	}
	
	private function calculate_totalprice( ) 
	{		
		if ( $this->recalculated )
		{ 		
			$lead_id = $this->get('id');
			$products = usam_get_products_lead( $lead_id );	
			$product_taxes = usam_get_lead_product_taxes( $lead_id ); 
			$totalprice = 0;		
			foreach ( $products as $product ) 
			{
				$tax = 0;
				foreach ( $product_taxes as $k => $product_tax ) 
				{
					if ( $product_tax->is_in_price == 0 )
					{
						if ($product->product_id == $product_tax->product_id && $product->unit_measure == $product_tax->unit_measure)
						{
							$tax += $product_tax->tax;
							unset($product_taxes[$k]);
						}
					}
				}
				$totalprice += $product->quantity * ($product->price + $tax);		
			}	
			$this->set(['totalprice' => $totalprice, 'number_products' => count($products)]);
		}	
	}
}

// Получить документ
function usam_get_lead( $id, $colum = 'id' )
{
	$_lead = new USAM_Lead( $id, $colum );
	$result = $_lead->get_data( );		
	return $result;	
}

function usam_update_lead( $id, $data, $products = null, $by = 'id' )
{
	$result = false;
	if ( $id )
	{
		$_lead = new USAM_Lead( $id, $by );
		if ( $data )
		{
			$_lead->set( $data );
			$result = $_lead->save();
		}	
		if ( is_array($products) )
		{
			if ( $_lead->set_products( $products ) )
				$result = true;
		}
	}
	return $result;
}

function usam_insert_lead( $data, $products = null )
{
	$_lead = new USAM_Lead( $data );
	$_lead->save();
	if ( is_array($products) )
		$_lead->add_products( $products );
	return $_lead->get('id');
}

function usam_add_lead_customerdata( $id, $data ) 
{
	$properties = usam_get_cache_properties('order');	
	$customer_data = [];
	$update = false;			
	foreach ( $data as $meta_key => $meta_value ) 
	{
		if ( isset($properties[$meta_key]) )
		{
			$meta_value = usam_sanitize_property( $meta_value, $properties[$meta_key]->field_type );			
			if ( $properties[$meta_key]->field_type == 'checkbox' )
			{
				if ( usam_save_array_metadata( $id, 'lead', $meta_key, $meta_value ) )
					$update = true;
			}
			elseif ( usam_update_lead_metadata( $id, $meta_key, $meta_value ) )
				$update = true;
			$customer_data[$meta_key] = $meta_value;
		}
	}
	if ( $update )
		do_action( 'usam_add_lead_customerdata', $id, $customer_data );
	return $update;
}

function usam_get_products_lead( $id, $formatting = 'db' ) 
{			
	if ( !$id )
		return array();
	$object_type = "usam_products_lead";
	$cache = wp_cache_get( $id, $object_type );		
	if ( $cache === false )		
	{
		require_once( USAM_FILE_PATH .'/includes/document/products_lead_query.class.php' );
		$cache = usam_get_products_lead_query(['lead_id' => $id]);		
		if ( empty($cache))
			$cache = [];		
		wp_cache_set( $id, $cache, $object_type );	
	}	
	if ( usam_is_multisite() && !is_main_site() )
	{ //Загрузить переводы
		$ids = [];
		foreach( $cache as $product )
		{
			$ids[$product->product_id] = usam_get_post_id_multisite($product->product_id);
		}
		if ( $ids )
		{
			$products = usam_get_products(['post__in' => $ids, 'update_post_term_cache' => false, 'update_post_meta_cache' => false, 'stocks_cache' => false, 'prices_cache' => false, 'cache_results' => false]);		
			foreach( $cache as &$lead_product )
			{ 
				foreach( $products as $product )
				{					
					if ( $product->ID == $ids[$lead_product->product_id] )
					{
						$lead_product->name = $product->post_title;
						break;
					}
				}
			}
		}
	}
	if ( $formatting == 'html' )
	{
		foreach( $cache as &$product )
		{					
			$product->name = stripcslashes($product->name);
		}
	}
	return $cache;	
}

// Удалить документ оплаты
function usam_delete_lead( $id )
{
	return usam_delete_leads(['include' => [$id]]);
}

function usam_add_lead_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('lead', $object_id, $meta_key, $meta_value, USAM_TABLE_LEAD_META, $prev_value );
}

function usam_get_lead_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('lead', $object_id, USAM_TABLE_LEAD_META, $meta_key, $single );
}

function usam_update_lead_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('lead', $object_id, $meta_key, $meta_value, USAM_TABLE_LEAD_META, $prev_value );
}

function usam_delete_lead_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('lead', $object_id, $meta_key, USAM_TABLE_LEAD_META, $meta_value, $delete_all );
}

function usam_get_lead_customerdata( $id, $type ) 
{
	$properties = usam_get_cache_properties();	
	foreach ( $properties as $property )
	{	 
		if ( $property->field_type == $type )
		{
			$value = usam_get_lead_metadata( $id, $property->code );	
			if ( $value != '' )
				return $value;
		}
	}	
	return false;
}

function usam_get_lead_product_taxes( $lead_id )
{	
	$cache_key = 'usam_lead_product_taxes';
	$cache = wp_cache_get( $lead_id, $cache_key );		
	if ( $cache === false )		
	{	
		require_once(USAM_FILE_PATH.'/includes/document/lead_product_taxes_query.class.php');
		$cache = usam_get_lead_product_taxes_query(['lead_id' => $lead_id]);
		wp_cache_set( $lead_id, $cache, $cache_key );
	}		
	return $cache;
}	

function usam_get_lead_taxes( $lead_id )
{	
	$product_taxes = usam_get_lead_product_taxes( $lead_id );  
	$products = usam_get_products_lead( $lead_id );   	
	$results = array();	
	foreach ( $product_taxes as $product_tax ) 
	{		
		$quantity = 0;
		foreach ( $products as $key => $product ) 
		{
			if ($product->product_id == $product_tax->product_id && $product->unit_measure == $product_tax->unit_measure)
			{
				$quantity = $product->quantity;
				break;
			}
		}
		$tax = $quantity * $product_tax->tax;		
		if ( isset($results[$product_tax->tax_id]) )
			$results[$product_tax->tax_id]['tax'] += $tax;
		else
			$results[$product_tax->tax_id] = ['name' => $product_tax->name, 'tax' => $tax, 'tax_rate' => $product_tax->rate, 'is_in_price' => $product_tax->is_in_price];
	}	
	return $results;
}

function usam_get_tax_amount_lead( $lead_id )
{	
	$product_taxes = usam_get_lead_product_taxes( $lead_id );  
	$products = usam_get_products_lead( $lead_id ); 	
	$tax = 0;
	foreach ( $product_taxes as $product_tax ) 
	{		
		$quantity = 0;
		foreach ( $products as $key => $product ) 
		{
			if ($product->product_id == $product_tax->product_id && $product->unit_measure == $product_tax->unit_measure)
			{
				$quantity = $product->quantity;
				unset($products[$key]);
				break;
			}
		}
		$tax += $quantity * $product_tax->tax;
	}	
	return $tax;
}

function usam_delete_leads( $args, $delete = false )
{	
	global $wpdb;
	if ( empty($args) )
		return 0;
	$args['fields'] = ['id', 'status'];
	require_once(USAM_FILE_PATH.'/includes/document/leads_query.class.php');
	$leads = usam_get_leads( $args );
	if ( empty($leads) )
		return 0;
	
	$statuses = [];
	usam_update_object_count_status( false );
	$ids = [];
	foreach ( $leads as $lead ) 
	{
		usam_update_object_count_status( $lead->status, 'lead' );
		$ids[] = $lead->id;
		do_action( 'usam_lead_before_delete', (array)$lead );
	}
	$in = implode( ', ', $ids );
		
	$wpdb->query("DELETE FROM " . USAM_TABLE_CHANGE_HISTORY . " WHERE object_id IN ($in) AND object_type='lead'");
	$wpdb->query("DELETE FROM " . USAM_TABLE_LEAD_META . " WHERE lead_id IN ($in)");
	$wpdb->query("DELETE FROM " . USAM_TABLE_DOCUMENT_DISCOUNTS . " WHERE document_id IN ($in) AND document_type='lead'");
	$wpdb->query("DELETE FROM ".USAM_TABLE_RIBBON_LINKS." WHERE object_type='lead' AND object_id IN (".implode(",", $ids).")");
	$wpdb->query("DELETE FROM " . USAM_TABLE_LEADS . " WHERE id IN ($in)");	
	
	foreach ( $ids as $id )
	{
		do_action( 'usam_lead_delete', $id );		
		wp_cache_delete( $id, 'usam_lead' );		
	}	
	usam_update_object_count_status( true );
	return count($ids);
}
?>