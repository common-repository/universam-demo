<?php
require_once( USAM_FILE_PATH . '/includes/document/document_status_change.class.php'  );
require_once( USAM_FILE_PATH . '/includes/document/document_product_tax.class.php' );

class USAM_Document
{		
	private static $string_cols = [
		'number',	
		'date_insert',
		'name',
		'type_price',
		'closedate',			
		'status',	
		'customer_type',
		'type',			
		'external_document',		
	];	
	private static $int_cols = [
		'id',				
		'manager_id',	
		'customer_id',		
		'bank_account_id',					
	];		
	private static $float_cols = [
		'totalprice',			
	];		
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
	private $data         = [];
	private $changed_data = [];	
	private $fetched = false;	
	private $args    = ['col'   => '', 'value' => ''];
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
		if ( ! in_array( $col, array( 'id', 'number' ) ) )
			return;

		$this->args = ['col' => $col, 'value' => $value];	
	
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
			$this->data = wp_cache_get( $value, 'usam_document' );	
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
		wp_cache_set( $id, $this->data, 'usam_document' );	
		do_action( 'usam_document_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache(  ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_document' );	
		wp_cache_delete( $this->get( 'id' ), 'usam_document_products' );	
		do_action( 'usam_document_update_cache', $this );
	}

	/**
	 * Удаляет документ
	 */
	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get( 'id' );
		usam_delete_documents(['include' => [$id]]);
		return false;
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

		$this->data = [];
		$format = self::get_column_format( $col );	
		$this->exists = false;
		if ( $data = $wpdb->get_row($wpdb->prepare( "SELECT * FROM " . USAM_TABLE_DOCUMENTS . " WHERE {$col} = {$format}", $value ), ARRAY_A ) ) 
		{						
			$this->exists = true;
			$this->data = apply_filters( 'usam_document_data', $data );		
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			} 
			$this->update_cache( );
		} 
		do_action( 'usam_document_fetched', $this );
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
		return apply_filters( 'usam_document_get_property', $value, $key, $this );
	}

		/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_document_get_data', $this->data, $this );
	}	

	public function set_products( $products )
	{
		$id = $this->get( 'id' );		
		$document_products = usam_get_products_document( $id );
		$add_products = [];
		$update_products = [];
		$processed = [];
		$result = false;
		foreach ( $products as $new_product ) 
		{
			if ( !is_array($new_product) )
				$new_product = (array)$new_product;			
			$add = true;
			if ( empty($new_product['unit_measure']) )
				$new_product['unit_measure'] = usam_get_product_property($new_product['product_id'], 'unit_measure_code');
			foreach ( $document_products as $k => $product ) 
			{
				if ( isset($new_product['id']) && ctype_digit($new_product['id']) || $product->product_id == $new_product['product_id'] && $product->unit_measure == $new_product['unit_measure'] )
				{
					$new_product['id'] = $product->id;
					$update_products[] = $new_product;					
					unset($document_products[$k]);
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
		if ( $this->delete_products( $document_products, false ) )
			$result = true;		
		if ( $result )
			$this->calculate_totalprice();	
		return $result;
	}	
	
	// Изменить товары
	public function update_products( $products, $calculate = true ) 
	{	
		global $wpdb;		
		
		$document_id = $this->get('id');
		if ( empty($document_id) )
			return false;
		
		require_once( USAM_FILE_PATH . '/includes/document/product_document.class.php' );
		$product_taxes = usam_get_document_product_taxes( $document_id );
		$products = apply_filters( 'usam_edit_document_products_set_properties', $products, $this );			
		do_action( 'usam_edit_document_products_pre_edit', $this );
		$i = 0;		
		$delete_product_taxes = [];
		foreach( $products as $product )
		{				
			$product['document_id'] = $document_id;			
			$p = new USAM_Product_Document( $product['id'] );					
			if ( isset($product['price']) )
				$product['price'] = usam_round_price( $product['price'], $this->get('type_price') );
			if ( isset($product['old_price']) )
				$product['old_price'] = usam_round_price( $product['old_price'], $this->get('type_price') );			
			$p->set( $product );
			if ( $p->save() )
			{
				$i++;
			}	
			if ( isset($product['price']) )
			{
				$result_tax = $this->calculate_tax( $product );
				if( !empty($result_tax['product_taxes']) )
				{
					foreach ( $result_tax['product_taxes'] as $new_tax ) 
					{						
						foreach( $product_taxes as $tax ) 
						{
							if ( $tax->product_id == $product['product_id'] && $tax->unit_measure == $product['unit_measure'] && $tax->tax_id == $new_tax['tax_id'])
							{
								usam_update_document_product_tax( $tax->id, $new_tax );
								continue 2;
							}
						}		
						$new_tax['document_id'] = $document_id;
						usam_insert_document_product_tax( $new_tax );						
					}	
				}
				else
					$delete_product_taxes[] = ['unit_measure' => $product['unit_measure'], 'product_id' => $product['product_id'] , 'document_id' => $document_id];
			}			
		}		
		if ( $delete_product_taxes && $product_taxes )
		{
			foreach( $delete_product_taxes as $tax )
				$wpdb->delete( USAM_TABLE_TAX_PRODUCT_DOCUMENT, $tax);
		}
		if ( $calculate )
			$this->calculate_totalprice();	
		
		do_action( 'usam_document_products_edit', $this );
		return $i;
	}	

	public function add_products( $products, $calculate = true ) 
	{	
		require_once( USAM_FILE_PATH . '/includes/document/product_document.class.php' );
		
		$i = 0;
		$document_id = $this->get('id');
		foreach ( $products as $product ) 
		{
			$product = (array)$product;			
			$product['document_id'] = $document_id;
			if ( empty($product['unit_measure']) )
				$product['unit_measure'] = usam_get_product_property($product['product_id'], 'unit_measure_code');
			if ( empty($product['quantity']) )
				$product['quantity'] = 1;				
			$type_price = $this->get('type_price');
			if ( !isset($product['old_price']) && isset($product['price']) )
				$product['old_price'] =	$product['price'];
			if ( !empty($type_price) )
			{
				if ( !isset($product['price']) )
					$product['price'] = usam_get_product_price( $product['product_id'], $type_price );
				if ( !isset($product['old_price']) )
					$product['old_price'] = usam_get_product_old_price( $product['product_id'], $type_price );
			}									
			if ( isset($product['id']) )
				unset($product['id']);						
			$p = new USAM_Product_Document( $product );					
			if ( $p->save() )
			{ 
				$i++;
				$result_tax = $this->calculate_tax( $product );
				if( !empty($result_tax['product_taxes']) )
				{
					foreach ( $result_tax['product_taxes'] as $new_tax ) 
					{
						$new_tax['document_id'] = $document_id;
						usam_insert_document_product_tax( $new_tax );
					}	
				}
			}
		}
		if ( $calculate && $i ) 
			$this->calculate_totalprice( );	
		return $i;
	}
	
	public function calculate_tax( $product ) 
	{
		$customer_id = $this->get('customer_id');			
		$customer_type = $this->get('customer_type');	
		$args = ['price' => $product['price']];
		if ( $customer_id )		
		{						
			if ( $customer_type == 'contact' )	
			{
				$args['type_payer'] = [0, 1];	
				$args['contact'] = $customer_id;	
			}
			elseif ( $customer_type == 'company' )
			{
				$args['type_payer'] = [0, 2];	
				$args['company'] = $customer_id;	
			}
		}
		else
			$args['type_payer'] = 0;	
		return usam_calculate_tax( $args, $product['product_id'] );	
	}
			
	// Удалить товар
	public function delete_products( $products, $calculate = true ) 
	{		
		global $wpdb;	
		
		$id = $this->get('id');
		$type = $this->get('type');
		if ( $this->exists == 0 || empty($products) )
			return false;
		
		$ids = [];
		$product_ids = [];
		foreach( $products as $product )
		{
			$ids[] = $product->id;		
			$product_ids[] = $product->product_id;
		}
		$wpdb->query("DELETE FROM `".USAM_TABLE_TAX_PRODUCT_DOCUMENT."` WHERE `document_id`=$id AND `product_id` IN (".implode(',',$product_ids).")");	
		$wpdb->query("DELETE FROM " . USAM_TABLE_DOCUMENT_DISCOUNTS . " WHERE `document_id`=$id AND document_type='$type' AND `product_id` IN (".implode(',',$ids).")");
		$result_deleted = $wpdb->query($wpdb->prepare("DELETE FROM `".USAM_TABLE_DOCUMENT_PRODUCTS."` WHERE `document_id` = '%d' AND `id` IN (".implode(',',$ids).")", $id));	
		if ( $result_deleted )
		{
			$this->delete_cache( );	
			if ( $calculate )
				$this->calculate_totalprice( );		
		}		
		return $result_deleted;
	}		
	
	public function calculate_totalprice( ) 
	{
		$document_id = $this->get( 'id' );
		wp_cache_delete( $document_id, 'usam_document_products' );	
		
		$product_taxes = usam_get_document_product_taxes( $document_id ); 
		$taxes = []; 
		foreach ( $product_taxes as $product_tax ) 
		{
			if ( $product_tax->is_in_price == 0 )
			{
				$taxes[$product_tax->product_id] = isset($taxes[$product_tax->product_id])?$taxes[$product_tax->product_id]:0;
				$taxes[$product_tax->product_id] += $product_tax->tax;
			}
		}		
		$new_totalprice = 0;
		$products = usam_get_products_document( $document_id );		
		foreach( $products as $product )
		{
			$tax = isset($taxes[$product->product_id])?$taxes[$product->product_id]:0;						
			$new_totalprice += ($product->price + $tax)*$product->quantity;			
		}			
		$totalprice = $this->get( 'totalprice' );	
		$setting_price = usam_get_setting_price_by_code( $this->data['type_price'] );
		if ( isset($setting_price['rounding']) )
			$new_totalprice = round($new_totalprice, $setting_price['rounding']);	
		if ( $totalprice != $new_totalprice )
		{
			$this->set(['totalprice' => $new_totalprice]);
			$this->save();
		}									
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
		$properties = apply_filters( 'usam_document_set_properties', $properties, $this );	
		
		$this->fetch();		
		if ( ! is_array( $this->data ) )
			$this->data = [];		
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
	
	public function save()
	{
		global $wpdb;

		do_action( 'usam_document_pre_save', $this );		
		$result = false;		
		if ( $this->args['col'] ) 
		{	// обновление				
			if ( empty($this->changed_data) )
				return true;
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_document_pre_update', $this );
			
			if ( isset($this->data['manager_id']) && empty($this->data['manager_id']) )
				$this->data['manager_id'] = get_current_user_id();
			
			$this->data = apply_filters( 'usam_document_update_data', $this->data );
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$str = [];
			foreach( $formats as $key => $value ) 
			{
				if ( $data[$key] == null )
				{
					$str[] = "`$key` = NULL";
					unset($data[$key]);
				}
				else
					$str[] = "`$key` = '$value'";	
			}			
			$result = $wpdb->query( $wpdb->prepare("UPDATE `".USAM_TABLE_DOCUMENTS."` SET ".implode(', ', $str)." WHERE ".$this->args['col']." = '$where_format' ", array_merge(array_values($data), [$this->args['value']])) );
			if ( $result ) 				
			{ 						
				$this->delete_cache( );			
				$id = $this->get('id');						
				$old_customer = '';
				foreach( $this->changed_data as $key => $value ) 
				{
					if ( isset($this->data[$key]) )
					{
						if( $key == 'customer_type' ||  $key == 'company_id' )
						{
							if( !$old_customer )
							{
								$old_customer = isset($this->changed_data['customer_type']) ? $this->changed_data['customer_type'] : $this->data['customer_type'];
								usam_insert_change_history(['object_id' => $id, 'object_type' => $this->data['type'], 'operation' => 'edit', 'field' => 'customer', 'value' => $this->data['customer_type'].'-'.$this->data[$key], 'old_value' => $old_customer.'-'.$value]);
							}
						}
						else
							usam_insert_change_history(['object_id' => $id, 'object_type' => $this->data['type'], 'operation' => 'edit', 'field' => $key, 'value' => $this->data[$key], 'old_value' => $value]);	
					}
				}
				if ( isset($this->changed_data['status']) ) 
				{
					usam_update_object_count_status( $this->data['status'], $this->data['type'] );
					do_action( 'usam_update_document_status', $this->get('id'), $this->data['status'], $this->changed_data['status'], $this );
				}								
			}
			do_action( 'usam_document_update', $this );
		} 
		else 
		{   // создание	
			do_action( 'usam_document_pre_insert' );		
			
			if ( isset($this->data['id']) )
				unset($this->data['id']);			
							
			if ( !isset($this->data['type']) )
				return false;
				
			if ( !isset($this->data['status']) )
				$this->data['status'] = 'draft';	
			
			if ( empty($this->data['customer_id']) )
				$this->data['customer_id'] = 0;
									
			if ( !isset($this->data['manager_id']) )
				$this->data['manager_id'] = get_current_user_id();
			
			if ( empty($this->data['number']) )
				$this->data['number'] = usam_get_document_number( $this->data['type'] );
			if ( !isset($this->data['bank_account_id']) )
				$this->data['bank_account_id'] = get_option( 'usam_shop_company', 0 );			
			
			if ( empty($this->data['date_insert']) )
				$this->data['date_insert'] = date( "Y-m-d H:i:s" );
			
			if ( !usam_is_license_type('BUSINESS') && !usam_is_license_type('ENTERPRISE') )
				$this->data['name']	= 'Демо версия '.$this->data['name'];
			
			if ( isset($this->data['closedate']) && $this->data['closedate'] == '' )
				unset($this->data['closedate']);			
	
			$this->data = apply_filters( 'usam_document_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );
			$result = $wpdb->insert( USAM_TABLE_DOCUMENTS, $this->data, $formats );	 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = ['col' => 'id',  'value' => $this->get( 'id' )];	
				$this->exists = true;	
				usam_update_object_count_status( $this->data['status'], $this->data['type'] );	
				do_action( 'usam_document_insert', $this );
			}			
		} 		
		do_action( 'usam_document_save', $this );
		$this->changed_data = [];
		return $result;
	}	
}

function usam_get_document_number( $type )
{
	global $wpdb;
	$number_counter = get_option("usam_document_number_counter", []);
	if ( isset($number_counter[$type]) )
		$number = $number_counter[$type];		
	else
		$number = '1';	
	do 
	{
		$objects = 0;
		$number = usam_get_formatted_document_number( $number );
		if ( apply_filters( 'usam_check_document_number', true ) )
		{
			switch ( $type ) 
			{
				case 'order' :
					$objects = (int)$wpdb->get_var("SELECT id FROM ".USAM_TABLE_ORDERS." WHERE number='$number' LIMIT 1" );	
				break;
				case 'lead' :				
					//$objects = (int)$wpdb->get_var("SELECT id FROM ".USAM_TABLE_LEADS." WHERE number='$number' LIMIT 1" );	
				break;
				case 'payment' :
					$objects = (int)$wpdb->get_var("SELECT id FROM ".USAM_TABLE_PAYMENT_HISTORY." WHERE number='$number' LIMIT 1" );	
				break;
				case 'shipped' :
					$objects = (int)$wpdb->get_var("SELECT id FROM ".USAM_TABLE_SHIPPED_DOCUMENTS." WHERE number='$number' LIMIT 1" );
				break;			
				default:
					$document = usam_get_document_name( $type );
					if ( $document )
						$objects = (int)$wpdb->get_var("SELECT id FROM ".USAM_TABLE_DOCUMENTS." WHERE number='$number' AND type='$type' LIMIT 1" );				
				break;
			}	
		}	
	} 
	while ( $objects > 0 );	
	$number_counter[$type] = $number;
	update_option("usam_document_number_counter", $number_counter);	
	return $number;
}

function usam_update_document( $id, $update, $products = null, $by = 'id' )
{	
	$result = false;
	if ( $id )
	{
		$c = new USAM_Document( $id );	
		if ( $update )
		{
			$c->set( $update );
			$result = $c->save();
		}
		if ( is_array($products) )
		{
			if ( $c->set_products( $products ) )
				$result = true;
		}		
	}
	return $result;	
}

function usam_get_document( $value, $colum = 'id' )
{	
	if( !$value )
		return [];
	$c = new USAM_Document($value, $colum);	
	$data = $c->get_data();
	if( $data )
		$data['url'] = usam_get_document_url( $data );	
	return $data;
}

function usam_delete_document( $value, $colum = 'id' )
{	
	$c = new USAM_Document( $value, $colum );	
	return $c->delete();	
}

function usam_insert_document( $document, $products = null, $metas = [], $links = [] )
{	
	$c = new USAM_Document( $document );
	$c->save();
	$id = $c->get('id');
	if ( $id )
	{
		$document['id'] = $id;
		if ( is_array($products) )
			$c->set_products( $products );
		
		if ( $metas )
		{
			foreach( $metas as $key => $value )
				usam_update_document_metadata( $id, $key, $value );
		}
		if ( !empty($document['customer_id']) && !empty($document['customer_type']) )
		{
			require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );
			usam_set_ribbon(['event_id' => $document['customer_id'], 'event_type' => $document['customer_type']], [['object_id' => $id, 'object_type' => $document['type']]]);	
		}
		foreach ($links as $link )
		{
			$link['document_link_type'] = $document['type'];
			$link['document_link_id'] = $id;
			usam_add_document_link( $link, false );	
		}
		do_action( 'usam_insert_'.$document['type'], $document, $products, $metas);
	}
	return $id;
}


function usam_get_products_document( $id )
{		
	if ( empty($id) )
		return array();
	
	$object_type = "usam_document_products";
	$cache = wp_cache_get( $id, $object_type );		
	if ( $cache === false )		
	{			
		require_once( USAM_FILE_PATH . '/includes/document/products_document_query.class.php' );	
		$cache = usam_get_products_document_query(['document_id' => $id]);		
		if ( empty($cache))
			$cache = array();	
		
		wp_cache_set( $id, $cache, $object_type );	
	}
	return $cache;
}

function usam_get_contacts_document( $id ) 
{	
	if ( !$id )
		return array();
	$object_type = "usam_contacts_document";
	$cache = wp_cache_get( $id, $object_type );		
	if ( $cache === false )		
	{	
		$cache = usam_get_contacts(['document_ids' => $id, 'orderby' => 'name', 'cache_meta' => true, 'cache_results' => true]);
		wp_cache_set( $id, $cache, $object_type );	
	}			
	return $cache;
}

function usam_get_details_documents( ) 
{
	$documents = [
		'order' => ['single_name' => __("Заказ","usam"), 'plural_name' => __("Заказы","usam"), 'genitive' => __('заказу','usam'), 'url' => admin_url('admin.php?page=orders&tab=orders')],
		'lead' => ['single_name' => __("Лид","usam"), 'plural_name' => __("Лиды","usam"), 'genitive' => __('лиду','usam'), 'url' => admin_url('admin.php?page=orders&tab=leads')],
		'payment' => ['single_name' => __("Оплата","usam"), 'plural_name' => __("Оплаты","usam"), 'genitive' => __('оплате','usam'), 'url' => admin_url('admin.php?page=orders&tab=payment')],
		'shipped' => ['single_name' => __("Отгрузка","usam"), 'plural_name' => __("Отгрузки","usam"), 'genitive' => __('отгрузке','usam'), 'url' => admin_url('admin.php?page=storage&tab=warehouse_documents')],
		'buyer_refund' => ['single_name' => __("Возврат от покупателя","usam"), 'plural_name' => __("Возвраты от покупателей","usam"), 'genitive' => __('возврату от покупателей','usam'), 'url' => admin_url('admin.php?page=orders&tab=buyer_refunds'),],
		'decree' => ['single_name' => __("Приказ","usam"), 'plural_name' => __("Приказы","usam"), 'genitive' => __('приказу','usam'), 'url' => admin_url('admin.php?page=personnel&tab=documents')],
		'invoice_payment' => ['single_name' => __("Счет на оплату","usam"), 'plural_name' => __("Счета на оплату","usam"), 'genitive' => __('счету на оплату','usam'), 'url' => admin_url('admin.php?page=personnel&tab=documents')], 
		'proxy' => ['single_name' => __("Доверенность","usam"), 'plural_name' => __("Доверенности","usam"), 'genitive' => __('доверенности','usam'), 'url' => admin_url('admin.php?page=procurement&tab=proxy')],
		'contract' => ['single_name' => __("Договор","usam"), 'plural_name' => __("Договоры","usam"), 'genitive' => __('договору','usam'), 'url' => admin_url('admin.php?page=personnel&tab=documents')],
		'additional_agreement' => ['single_name' => __("Дополнительное соглашение","usam"), 'plural_name' => __("Дополнительные соглашения","usam"), 'genitive' => __('Дополнительному соглашению','usam'), 'url' => admin_url('admin.php?page=personnel&tab=documents')],
		'invoice' => ['single_name' => __("Счет","usam"), 'plural_name' => __("Счета","usam"), 'genitive' => __('счету','usam'), 'url' => admin_url('admin.php?page=personnel&tab=documents')],
		'invoice_offer' => ['single_name' => __("Счет-оферта","usam"), 'plural_name' => __("Счета-оферты","usam"), 'genitive' => __('счету-оферты','usam'), 'url' => admin_url('admin.php?page=personnel&tab=documents')],
		'act' => ['single_name' => __("Акт","usam"), 'plural_name' => __("Акты","usam"), 'genitive' => __('акту','usam'), 'url' => admin_url('admin.php?page=personnel&tab=documents')],
		'suggestion' => ['single_name' => __("Предложение","usam"), 'plural_name' => __("Коммерческие предложения","usam"), 'genitive' => __('коммерческому предложению','usam'), 'url' => admin_url('admin.php?page=personnel&tab=documents')],
		'movement' => ['single_name' => __("Перемещение","usam"), 'plural_name' => __("Перемещения","usam"), 'genitive' => __('перемещению','usam'), 'url' => admin_url('admin.php?page=personnel&tab=documents')],
		'receipt' => ['single_name' => __("Поступление товара","usam"), 'plural_name' => __("Поступления товаров","usam"), 'genitive' => __('поступлению товаров','usam'), 'url' => admin_url('admin.php?page=personnel&tab=documents') ],
		'payment_order' => ['single_name' => __("Платежное поручение","usam"), 'plural_name' => __("Платежные поручения","usam"), 'genitive' => __('платежному поручению','usam'), 'url' => admin_url('admin.php?page=bookkeeping&tab=payment_orders&view=table&table=payment_orders') ],
		'payment_received' => ['single_name' => __("Поступление платежа","usam"), 'plural_name' => __("Поступления платежей","usam"), 'genitive' => __('поступлению платежа','usam'), 'url' => admin_url('admin.php?page=bookkeeping&tab=payment_orders&view=table&table=payments_received') ],
		'partner_order' => ['single_name' => __("Заказ от партнера","usam"), 'plural_name' => __("Заказы от партнера","usam"), 'genitive' => __('заказу от партнера','usam'), 'url' => admin_url('admin.php?page=storage&tab=warehouse_documents&view=table&table=partner_orders') ],
		'order_contractor' => ['single_name' => __("Заказ поставщику","usam"), 'plural_name' => __("Заказы поставщику","usam"), 'genitive' => __('заказу от поставщика','usam'), 'url' => admin_url('admin.php?page=procurement&tab=order_contractor') ],
		'reconciliation_act' => ['single_name' => __("Акт сверки","usam"), 'plural_name' => __("Акты сверки","usam"), 'genitive' => __('акту сверки','usam'), 'url' => admin_url('admin.php?page=bookkeeping&tab=reconciliation_acts') ],	
		'check' => ['single_name' => __("Чек","usam"), 'plural_name' => __("Чеки","usam"), 'genitive' => __('чеку','usam'), 'url' => admin_url('admin.php?page=orders&tab=checks') ],
		'check_return' => ['single_name' => __("Чек на возврат","usam"), 'plural_name' => __("Чеки на возврат","usam"), 'genitive' => __('чеку на возврат','usam'), 'url' => admin_url('admin.php?page=orders&tab=checks') ], 		
	];
	return $documents;
}

function usam_get_details_document( $type ) 
{
	$details_documents = usam_get_details_documents();	
	if ( isset($details_documents[$type]) )
		return $details_documents[$type];
	return false;
}

function usam_get_document_name( $type, $key = 'single_name' )
{
	$details_documents = usam_get_details_documents( );	
	if ( isset($details_documents[$type]) )
		return $details_documents[$type][$key];
	return false;
}

function usam_get_document_full_name( $type, $id ) 
{
	$details_documents = usam_get_details_documents( );			
	if ( !empty($details_documents[$type]) )
	{
		if ( $type == 'order' ) 
		{
			$document = usam_get_order( $id );
			return sprintf( __('%s №%s от %s','usam'), $details_documents[$type]['single_name'], $document['id'], usam_local_date($document['date_insert'], "d.m.Y") );
		}
		elseif ( $type == 'lead' ) 
		{
			$document = usam_get_lead( $id );
			return sprintf( __('%s №%s от %s','usam'), $details_documents[$type]['single_name'], $document['id'], usam_local_date($document['date_insert'], "d.m.Y") );
		}
		elseif ( $type == 'shipped' )			
		{
			$document = usam_get_shipped_document( $id );
			return sprintf( __('%s №%s от %s','usam'), $details_documents[$type]['single_name'], $document['id'], usam_local_date($document['date_insert'], "d.m.Y") );
		}
		else
		{
			$document = usam_get_document( $id );
			return sprintf( __('%s №%s от %s','usam'), $details_documents[$type]['single_name'], $document['number'], usam_local_date($document['date_insert'], "d.m.Y") );
		}
	}
	return false;
}

function usam_check_access_to_view_document( $id, $type ) 
{
	$access = true;
	if ( !usam_check_is_employee() )
	{		
		$contact_id = usam_get_contact_id();
		if ( $type == 'order' ) 
		{
			$document = usam_get_order( $id );
			if ( empty($document) )
				return false;
			if ( $contact_id != $document['contact_id'] )
				$access = false;
		}
		elseif ( $type == 'lead' ) 
		{
			$document = usam_get_lead( $id );
			if ( empty($document) )
				return false;
			if ( $contact_id != $document['contact_id'] )
				$access = false;
		}
		elseif ( $type == 'shipped' )			
		{
			$document = usam_get_shipped_document( $id );
			if ( empty($document) )
				return false;
			$access = false;
		}
		else
		{
			$document = usam_get_document( $id );
			if ( empty($document) )
				return false;
			if ( $document['customer_id'] )
			{
				if ( $document['customer_type'] == 'contact' )
				{
					if ( $contact_id != $document['customer_id'] )
						$access = false;
				}
				elseif ( $document['customer_type'] == 'company' )
				{
					$contacts = usam_get_contacts(['company_id' => $document['customer_id'], 'include' => [$contact_id]]);
					if ( !$contacts )
						$access = false;
				}
			}
			else
				$access = false;
		}
		if ( $access && !is_user_logged_in() )
		{
			if ( strtotime($document['date_insert']) < strtotime("-20 day") )
				$access = false;
		}
	}
	return $access;		
}

function usam_get_document_url( $id, $type = '', $form_type = 'view' ) 
{
	if ( is_array($id) )
	{
		$type = $id['type'];
		$id = $id['id'];
	}	
	elseif ( is_object($id) )
	{
		$type = $id->type;
		$id = $id->id;
	}		
	$details_documents = usam_get_details_documents();
	$url = '';
	if ( isset($details_documents[$type]) )
	{
		$url = $details_documents[$type]['url'];	
		$url = add_query_arg(['form' => $form_type, 'form_name' => $type, 'id' => $id], $url );
	}
	return $url;
}

function usam_save_printing_form_to_pdf( $printed_form, $id, $name ) 
{ 	
	$pdf_file = usam_get_export_form_to_pdf( $printed_form, $id );		
	if ( !empty($pdf_file) )
	{
		$ext = 'pdf';			
		$directory = USAM_DOCUMENTS_DIR.$id; 	
		if ( !is_dir($directory) )
			mkdir($directory, 0775);	
		
		$file_name = $printed_form.'.'.$ext;
		$file_path = $directory . '/' . $file_name;			
		if ( file_put_contents($file_path, $pdf_file) )
		{						
			$insert = array( 'object_id' => $id, 'title' => $name, 'name' => $file_name, 'file_path' => $file_path, 'type' => 'document' );			
			$files = usam_get_files( array('object_id' => $id, 'type' => 'document', 'name' => $file_name ) );
			if ( !empty($files) )
			{ 
				usam_update_file( $files[0]->id, $insert );
				return $files[0]->id;
			}
			else
				return usam_insert_file( $insert ); 
		}
	}		
	return false;
}


function usam_get_pdf_document( $id ) 
{ 	
	$document = usam_get_document( $id );
	$pdf_file = usam_get_export_form_to_pdf( $document['type'], $id );	
	$ext = 'pdf';				
	$filename = sanitize_file_name( usam_sanitize_title_with_translit( $document['name'] ) );
	$filename = wp_unique_filename( USAM_FILE_DIR, $filename.'.'.$ext ); // Уникальное имя
	$file_path = USAM_FILE_DIR . $filename;
	$result = file_put_contents($file_path, $pdf_file );		
	return $file_path;
}

function usam_get_document_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('document', $object_id, USAM_TABLE_DOCUMENT_META, $meta_key, $single );
}

function usam_update_document_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('document', $object_id, $meta_key, $meta_value, USAM_TABLE_DOCUMENT_META, $prev_value );
}

function usam_delete_document_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('document', $object_id, $meta_key, USAM_TABLE_DOCUMENT_META, $meta_value, $delete_all );
}


function usam_get_document_content( $object_id, $meta_key = '', $single = true) 
{	
	return(string)usam_get_metadata('document', $object_id, USAM_TABLE_DOCUMENT_CONTENT, $meta_key, $single, 'content' );
}

function usam_update_document_content($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('document', $object_id, $meta_key, $meta_value, USAM_TABLE_DOCUMENT_CONTENT, $prev_value, 'content' );
}

function usam_delete_document_content( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('document', $object_id, $meta_key, USAM_TABLE_DOCUMENT_CONTENT, $meta_value, $delete_all, 'content' );
}

/*
$unique(логический)
Определение уникальности ключа.

false - означает, что для этого объекта может быть определено несколько одинаковых ключей.
true - значит, что ключ для этого объекта может быть только один, т.е. если такой ключ уже существует, то функция не добавит никаких данных.
*/
function usam_add_document_metadata($object_id, $meta_key, $meta_value, $prev_value = true ) 
{
	return usam_add_metadata('document', $object_id, $meta_key, $meta_value, USAM_TABLE_DOCUMENT_META, $prev_value );
}

function usam_document_copy( $document_id, $args = [] ) 
{
	$data = usam_get_document( $document_id );
	$new_data = $data;
	unset($new_data['date_insert']);	
	unset($new_data['closedate']);		
	$new_data['number'] = '';
	if ( !isset($args['type']) && !isset($args['name']) )
		$new_data['name'] = $new_data['name'].' ('.__('Скопированное','usam').')';
	$new_data['status'] = 'draft';
	$new_data = array_merge( $new_data, $args );	
	
	$products = usam_get_products_document( $document_id );	
	$links = [];
	if ( $data['type'] != $new_data['type'] )
		$links[] = ['document_id' => $document_id, 'document_type' => $data['type']];	
	$new_document_id = usam_insert_document( $new_data, $products, [], $links ); 
	if ( $new_document_id )
	{		
		$contacts = usam_get_contact_ids_document(['document_id' => $document_id]);		
		foreach ( $contacts as $contact ) 
		{
			usam_add_contact_document( $new_document_id, $contact->contact_id, $contact->contact_type );
		}	
		if ( empty($args['type']) )
		{
			$document_metadata = usam_get_document_metadata( $document_id ); 
			foreach ( $document_metadata as $metadata ) 
			{
				usam_update_document_metadata($new_document_id, $metadata->meta_key, $metadata->meta_value ) ;
			}
			$document_metadata = usam_get_document_content( $document_id ); 
			foreach ( $document_metadata as $metadata ) 
			{
				usam_update_document_content($new_document_id, $metadata->meta_key, $metadata->meta_value ) ;
			}
		}		
	}	
	return $new_document_id;	
}

//Добавить ссылку на документ
function usam_add_document_link( $args, $check_existence = true ) 
{
	global $wpdb;	
	
	$formats = ['document_id' => '%d', 'document_type' => '%s', 'document_link_id' => '%d', 'document_link_type' => '%s', 'link_type' => '%s'];
	if ( empty($args['link_type']) )
		$args['link_type'] = 'link';
	$where = [];
	foreach ($formats as $key => $value ) 
	{
		if ( isset($args[$key]) )
			$where[] = "$key='".$args[$key]."'";
		else
			return false;
	}
	if ( $check_existence )
		$result = $wpdb->get_var("SELECT document_id FROM ".USAM_TABLE_DOCUMENT_LINKS." WHERE ".implode(' AND ', $where)." LIMIT 1");
	else
		$result = false;
	if ( !$result )
	{
		$format = [];
		foreach ($args as $key => $value ) 
		{
			if ( isset($formats[$key]) )
				$format[] = $formats[$key];
			else
				unset($args[$key]);
		}
		$result = $wpdb->insert( USAM_TABLE_DOCUMENT_LINKS, $args, $format );	
	}
	return $result;
}

//Удалить ссылку на документ
function usam_delete_document_link( $args ) 
{
	global $wpdb;

	if ( empty($args['document_id']) )
		return false;
			
	$formats = ['document_id' => '%d', 'document_type' => '%s', 'document_link_id' => '%d', 'document_link_type' => '%s', 'link_type' => '%s'];
	foreach ($args as $key => $value ) 
	{
		if ( isset($formats[$key]) )
			$format[] = $formats[$key];
		else
			unset($args[$key]);
	}		
	$result = $wpdb->delete( USAM_TABLE_DOCUMENT_LINKS, $args, $format );				
	return $result;
}

function usam_get_parent_documents( $document_link_id, $document_link_type, $link_type = 'link' ) 
{
	global $wpdb;
	$args = ["document_link_id = '$document_link_id'", "document_link_type = '$document_link_type'"];
	if ( $link_type )	
		$args[] = "link_type = '$link_type'";
	$results = $wpdb->get_results( "SELECT document_id, document_type FROM ".USAM_TABLE_DOCUMENT_LINKS." WHERE ".implode(" AND ", $args) );
	return $results;
}

function usam_get_child_documents( $document_id, $document_type, $link_type = 'link' ) 
{
	global $wpdb;	
	$args = ["document_id = '$document_id'", "document_type = '$document_type'"];
	if ( $link_type )	
		$args[] = "link_type = '$link_type'";	
	$results = $wpdb->get_results( "SELECT document_link_id AS document_id, document_link_type AS document_type FROM ".USAM_TABLE_DOCUMENT_LINKS." WHERE ".implode(" AND ", $args) );
	return $results;
}

function usam_add_contact_document( $document_id, $contact_id, $contact_type = 'participant' ) 
{
	global $wpdb;	
	
	if ( empty($document_id) )
		return false;
		
	if ( empty($contact_id) )
		return false;			
	$result = $wpdb->get_var( "SELECT document_id FROM ".USAM_TABLE_DOCUMENT_CONTACTS." WHERE document_id = '$document_id' AND contact_id = '$contact_id' AND contact_type = '$contact_type'" );		
	if ( !$result )
	{
		$result = $wpdb->insert( USAM_TABLE_DOCUMENT_CONTACTS, ['document_id' => $document_id, 'contact_id' => $contact_id, 'contact_type' => $contact_type], array( '%d', '%d', '%s') );		
		if ( $result )
			do_action( 'usam_set_document_id_user', $document_id, $contact_id, $contact_type );
	}
	return $result;
}

function usam_delete_contact_document( $document_id, $contact_id = '', $contact_type = '' ) 
{
	global $wpdb;

	if ( empty($contact_id) )
		return false;
			
	$delete = array( 'document_id' => $document_id );
	$format = array('%d');
	
	if ( !empty($contact_id) )
	{
		$delete['contact_id'] = $contact_id;
		$format[] = '%d';
	}	
	if ( !empty($contact_type) )
	{
		$delete['contact_type'] = $contact_type;
		$format[] = '%d';
	}	
	$result = $wpdb->delete( USAM_TABLE_DOCUMENT_CONTACTS, $delete, $format );				
	return $result;
}
	
function usam_get_contact_ids_document( $qv = array() )
{ 
	global $wpdb;	

	if ( isset($qv['fields']) )
	{
		$fields = $qv['fields'] == 'all'?'*':$qv['fields'];
	}
	else
		$fields = '*';
	
	$_where[] = '1=1';
	
	if ( isset($qv['document_id']) )
		$_where[] = "document_id = '".$qv['document_id']."'";	
	
	if ( isset($qv['contact_id']) )
		$_where[] = "contact_id IN( '".implode( "','", $qv['contact_id'] )."' )";
	
	if ( isset($qv['contact_type']) )
		$_where[] = "contact_type IN( '".implode( "','", $qv['contact_type'] )."' )";
	
	$where = implode( " AND ", $_where);	
	
	if ( isset($qv['orderby']) )	
		$orderby = $qv['orderby'];	
	else
		$orderby = 'document_id';
	$orderby = "ORDER BY $orderby";
	
	if ( isset($qv['order']) )	
		$order = $qv['order'];	
	else
		$order = 'DESC';	
	
	if ( $where != '' )
		$where = " WHERE $where ";
	
	$result = $wpdb->get_results( "SELECT $fields FROM ".USAM_TABLE_DOCUMENT_CONTACTS." $where $orderby $order" );
	return $result;
}

function usam_get_document_product_taxes( $document_id )
{	
	$cache_key = 'usam_order_product_taxes';
	if( ! $cache = wp_cache_get($cache_key, $document_id ) )
	{		
		require_once( USAM_FILE_PATH . '/includes/document/document_products_taxes_query.class.php' );		
		$cache = usam_get_document_products_taxes_query(['document_id' => $document_id]);
		wp_cache_set( $cache_key, $cache, $document_id );
	}		
	return $cache;
}	

function usam_get_document_taxes( $document_id )
{	
	$product_taxes = usam_get_document_product_taxes( $document_id );  
	$products = usam_get_products_document( $document_id );   

	$products_quantity = [];
	foreach ( $products as $product ) 
		$products_quantity[$product->product_id] = $product->quantity;
	
	$results = [];	
	foreach ( $product_taxes as $product_tax ) 
	{		
		$tax = $products_quantity[$product_tax->product_id] * $product_tax->tax;
		if ( isset($results[$product_tax->tax_id]) )
			$results[$product_tax->tax_id]['tax'] += $tax;
		else
			$results[$product_tax->tax_id] = ['name' => $product_tax->name, 'tax' => $tax];
	}	
	return $results;
}

function usam_get_tax_amount_document( $document_id )
{	
	$product_taxes = usam_get_document_product_taxes( $document_id );  
	$products = usam_get_products_document( $document_id );   

	$products_quantity = array();
	foreach ( $products as $product ) 
		$products_quantity[$product->product_id] = $product->quantity;
	
	$tax = 0;
	foreach ( $product_taxes as $product_tax ) 
	{		
		$tax += $products_quantity[$product_tax->product_id] * $product_tax->tax;
	}	
	return $tax;
}

function usam_get_payment_types( ) 
{
	$types = ['cash' => __('Наличными', 'usam'), 'card' => __('Банковской картой', 'usam'), 'certificate' => __('Сертификатом', 'usam'), 'account' => __('Оптата с внутреннего счета', 'usam')];
	return $types;
}

function usam_get_payment_type_name( $type ) 
{
	$payment_types = usam_get_payment_types();
	if ( isset($payment_types[$type]) )
		$result = $payment_types[$type];
	else
		$result = '';
	return $result;
}

function usam_get_document_id_by_meta( $key, $value ) 
{
	global $wpdb;	
	if ( !$value ) 
		return;
	
	$cache_key = "usam_document_$key-$value";
	$document_id = wp_cache_get( $cache_key );
	if ($document_id === false) 
	{	
		$document_id = (int)$wpdb->get_var($wpdb->prepare("SELECT document_id FROM ".USAM_TABLE_DOCUMENT_META." WHERE meta_key='%s' AND meta_value='%s' LIMIT 1", $key, $value));
		wp_cache_set($cache_key, $document_id);
	}	
	return $document_id;
}

function usam_get_document_ids_by_code( $codes, $meta_key = 'code' )
{
	global $wpdb;	
	$document_codes = array();
	foreach($codes as $key => $code )
	{				
		$document_id = wp_cache_get( "usam_document_{$meta_key}-".$code );
		if ($document_id !== false) 
		{
			$document_codes[$code] = $document_id;
			unset($codes[$key]);
		}
	} 	
	if ( empty($codes) )
		return $document_codes;
	$results = $wpdb->get_results("SELECT meta_value, document_id FROM ".USAM_TABLE_DOCUMENT_META." WHERE meta_value IN ('".implode("','", $codes )."') AND meta_key='{$meta_key}'");	
	$c = array();
	foreach($results as $result )	
	{
		$c[$result->meta_value] = $result->document_id;
	}	
	foreach($codes as $code )
	{				
		if ( !isset($c[$code]) )
			wp_cache_set("usam_document_{$meta_key}-".$code, 0);
		else
		{
			$document_codes[$code] = $c[$code];		
			wp_cache_set("usam_document_{$meta_key}-".$code, $document_codes[$code]);
		}
	} 	
	return $document_codes;
}

function usam_delete_documents( $args, $delete = true ) 
{		
	global $wpdb;		
	$args['fields'] = ['id', 'status', 'type'];
	$args['number'] = 500000;
	$args['cache_results'] = false;
	require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );
	$documents = usam_get_documents( $args );	
	
	if ( empty($documents) )
		return false;	
	
	$delete_ids = [];
	$update_ids = [];
	usam_update_object_count_status( false );
	foreach ( $documents as $k => $document )
	{				
		if ( current_user_can('delete_'.$document->type) && in_array($document->status, ['delete', 'draft']) || current_user_can('delete_any_'.$document->type) )
		{	
			usam_update_object_count_status( $document->status, $document->type );				
			if ( $document->status == 'delete' || $delete )
			{
				$delete_ids[] = $document->id;				
				wp_cache_delete( $document->id, 'usam_document' );	
				wp_cache_delete( $document->id, 'usam_document_products' );	
				do_action( 'usam_document_before_delete', (array)$document );
				unset($documents[$k]);
			}
			else
				$update_ids[] = $document->id;			
		}
	}
	if ( !empty($delete_ids) )
	{
		$wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_PRODUCTS." WHERE document_id IN (".implode(',',$delete_ids).")");	
		$wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_META." WHERE document_id IN (".implode(',',$delete_ids).")");
		$wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_CONTACTS." WHERE document_id IN (".implode(',',$delete_ids).")");		
		$types = [];
		foreach ( $documents as $document )
		{
			$types[$document->type][] = $document->id;
		}
		foreach ( $types as $type => $ids )
		{
			$wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_DISCOUNTS." WHERE document_id IN (".implode(',',$ids).") AND document_type='$type'");
			$wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_LINKS." WHERE document_id IN (".implode(',',$ids).") AND document_type='$type' OR document_link_id IN (".implode(',',$ids).") AND document_link_type='$type'");
		}
		usam_delete_object_files( $delete_ids, 'document' );
		require_once( USAM_FILE_PATH.'/includes/crm/comment.class.php' );
		usam_delete_comments(['object_id' => $delete_ids, 'object_type' => 'document'], true);
		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENTS." WHERE id IN (".implode(',',$delete_ids).")");
		if ( $result )
		{
			foreach ( $documents as $document ) 
			{
				do_action( 'usam_document_delete', $document->id );		
			}	
		}		
	}
	if ( !empty($update_ids) )
		$wpdb->query("UPDATE ".USAM_TABLE_DOCUMENTS." SET status='delete' WHERE `id` IN (".implode(',',$update_ids).")");
	
	usam_update_object_count_status( true );
	return count($delete_ids);
}

function usam_check_document_access( $document, $document_type, $type_access, $contact_id = null )
{	
	if ( empty($document) )
		return true;
		
	if ( $contact_id === null )
	{
		$contact_id = usam_get_contact_id();
		$contact = usam_get_contact( $contact_id );
		$user_id = get_current_user_id();	
	}
	else
	{
		$contact = usam_get_contact( $contact_id );
		$user_id = isset($contact['user_id'])?$contact['user_id']:0;
	}	
	if ( empty($contact) )
		return false;
	
	$document = (array)$document;		
	$access = false; 
	 
	if ( user_can($user_id, 'any_'.$type_access.'_'.$document_type) )
		$access = true;
	elseif ( user_can($user_id, 'company_'.$type_access.'_'.$document_type) )
	{
		$access = true;
		if ( !empty($document['bank_account_id']) )
		{
			$account = usam_get_bank_account( $document['bank_account_id'] );	
			if ( !empty($account['company_id']) )
			{					
				if ( !empty($contact['company_id']) &&  $contact['company_id'] == $account['company_id'] )
					$access = true;
			}
		}	
	}
	elseif ( user_can($user_id, 'department_'.$type_access.'_'.$document_type) )
	{
		if ( empty($document['manager_id']) )
			$access = true;
		else
		{						
			$department_id = usam_get_contact_metadata($contact_id, 'department');
			if ( $department_id )
			{ 
				$manager_contact = usam_get_contact( $document['manager_id'], 'user_id' );
				$manager_department_id = usam_get_contact_metadata($manager_contact['id'], 'department');				
				if ( $manager_department_id === $department_id )
					$access = true;
			}	
		}
	}
	elseif ( user_can($user_id, $type_access.'_'.$document_type) )
	{			
		if ( empty($document['manager_id']) || $contact['user_id'] == $document['manager_id'] )
			$access = true;
	}	
	return $access;
}