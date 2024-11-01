<?php
/**
 * Документы отгрузки
 */ 
class USAM_Shipped_Document
{
	const CLOSED = 'shipped';
	// строковые
	private static $string_cols = [
		'name',		
		'method',		
		'date_insert',		
		'track_id',	
		'status',	
		'customer_type',		
		'type_price',
		'number',		
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'order_id',				
		'courier',			
		'storage_pickup',
		'storage',	
		'include_in_cost',
		'tax_id',		
		'customer_id',
		'seller_id',
		'manager_id',		
	];
	// рациональные
	private static $float_cols = [
		'price',	
		'totalprice',	
		'tax_value',
	];
	private $product_db_format = ['id' => '%d', 'document_id' => '%d', 'name' => '%s', 'product_id' => '%d', 'quantity' => '%f', 'unit_measure' => '%s', 'reserve' => '%f', 'price' => '%f', 'date_insert' => '%s'];
	private $data     = [];		
	private $changed_data = [];	
	private $recalculated = false; 
	private $fetched  = false;
	private $args     = array( 'col'   => '', 'value' => '' );	
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
		if ( ! in_array( $col, ['id', 'track_id'] ) )
			return;
		
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
			self::$int_cols[] = 'seller_id';
					
		$this->args = ['col' => $col, 'value' => $value];		
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_document_shipped' );
		}			
		// кэш существует
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
		wp_cache_set( $id, $this->data, 'usam_document_shipped' );		
		do_action( 'usam_document_shipped_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache() 
	{		
		wp_cache_delete( $this->get( 'order_id' ), 'usam_shipped_documents_order' );
		wp_cache_delete( $this->get( 'id' ), 'usam_document_shipped' );	
		wp_cache_delete( $this->get( 'id' ), 'usam_products_shipped_document' );		
		do_action( 'usam_document_shipped_delete_cache', $this );	
	}			

	/**
	 *  Удалить документ отгрузки
	 */
	public function delete( ) 
	{		
		global  $wpdb;
		
		$id = $this->get( 'id' );
		$status = $this->get( 'status' );	
		$data = $this->get_data();
		do_action( 'usam_document_shipped_before_delete', $data );		
							
		$products = usam_get_products_shipped_document( $id );		
		foreach( $products as $key => &$product )
		{
			$product = (array)$product;		
			$product['quantity'] = 0;		
			$product['reserve'] = 0;				
		}					
		$this->set_products( $products );
		
		$order_id = $this->get( 'order_id' );	
		$price = $this->get( 'price' );		
		if ( $this->get('include_in_cost') && $order_id )
		{
			$data = usam_get_order( $order_id );
			if ( $data )
			{
				$shipping = $data['shipping'] - $price;
				usam_update_order( $this->data['order_id'], ['shipping' => $shipping]);	
			}
		}
		wp_cache_delete( $order_id, 'usam_shipped_documents_order' );	
		$this->delete_cache( );						
		
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_SHIPPED_DOCUMENTS." WHERE id = '$id'");

		usam_update_object_count_status( $status, 'shipped' );		
		do_action( 'usam_document_shipped_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_SHIPPED_DOCUMENTS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_document_shipped_data', $data );		
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
		do_action( 'usam_document_shipped_fetched', $this );	
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
		return apply_filters( 'usam_document_shipped_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_document_shipped_get_data', $this->data, $this );
	}
	
	public function get_changed_data()
	{
		return $this->changed_data;
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
			$properties = [$key => $value];			
		}			
		$this->fetch();	
		if ( ! is_array($this->data) )
			$this->data = array();				
		
		if ( !empty($properties['method']) )
		{
			$delivery_service = usam_get_delivery_service( $properties['method'] );		
			$properties['name'] = isset($delivery_service)?$delivery_service['name']:'';
		}			
		$properties = apply_filters( 'usam_document_shipped_set_properties', $properties, $this );
		if ( isset($properties['storage']) && $properties['storage'] == 0 )
			$properties['storage'] = get_option('usam_default_reservation_storage', '' );
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
	
	// Снять все товары с резерва
	public function remove_all_product_from_reserve( ) 
	{			
		$id = $this->get( 'id' );	
		$products = usam_get_products_shipped_document( $id );
		$update_products = [];
		foreach( $products as $product  )
		{
			$product = (array)$product;
			if ( $product['reserve'] )
			{
				$product['reserve'] = 0;
				$update_products[] = $product;
			}
		}	
		if ( $update_products )
			$this->update_products( $update_products );		
	}
	
	private function get_order_product( $product_id, $unit_measure ) 
	{
		$products = usam_get_products_order( $this->data['order_id'] );		
		foreach( $products as $product )
		{
			if ( $product->product_id == $product_id && $product->unit_measure == $unit_measure )
			{
				return $product;	
			}
		}					
		return [];
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
	
	private function get_product_format( $data ) 
	{
		$formats = [];
		foreach ( $data as $key => $value ) 
			$formats[$key] = $this->product_db_format[$key];
		return $formats;
	}	
	
	public function set_products( $products )
	{	 
		$id = $this->get( 'id' );		
		$shipped_products = usam_get_products_shipped_document( $id );
		$add_products = [];
		$update_products = [];
		$processed = [];
		$result = false;	
		foreach ( $products as $new_product ) 
		{
			$add = true;
			if ( empty($new_product['unit_measure']) )
				$new_product['unit_measure'] = usam_get_product_property($new_product['product_id'], 'unit_measure_code');
			foreach ( $shipped_products as $k => $product ) 
			{
				if ( isset($new_product['id']) && ctype_digit($new_product['id']) || $product->product_id == $new_product['product_id'] && $product->unit_measure == $new_product['unit_measure'] )
				{
					$new_product['id'] = $product->id;
					$update_products[] = $new_product;					
					unset($shipped_products[$k]);
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
		if ( $this->delete_products( $shipped_products, false) )
			$result = true;				
		if ( $result )
		{
			$this->recalculated = true;	
			$this->save();
		}
		return $result;
	}
	
	public function add_products( $products, $calculate = true ) 
	{
		global $wpdb;		
	
		if ( !$this->exists() || empty($products) )
			return false;	
		
		$result = false;		
		$product_ids = [];
		foreach( $products as $product )
		{
			if ( !empty($product['product_id']) )
				$product_ids[] = $product['product_id'];
		}
		if ( empty($product_ids) )
			return false;
		
		$type_price = $this->get('type_price');
		
		$id = $this->get('id');					
		foreach( $products as $product )
		{
			$product = $this->product_key_cleaning( $product );
			$product['document_id'] = $id;									
			
			if ( isset($product['id']) )
				unset($product['id']);	
			if ( empty($product['product_id']) )
				continue;			
			if ( !isset($product['reserve']) )
				$product['reserve'] = 0;
			if ( !isset($product['quantity']) )
				$product['quantity'] = 1;
			if ( empty($product['unit_measure']) )
				$product['unit_measure'] = usam_get_product_property($product['product_id'], 'unit_measure_code');	
			if ( empty($product['price']) )
				$product['price'] = usam_get_product_price( $product['product_id'], $type_price, $product['unit_measure'] );
			if ( !empty($this->data['order_id']) )
			{
				$order_product = $this->get_order_product( $product['product_id'], $product['unit_measure'] );		
				if ( empty($order_product) )
					return false;
							
				if ( $order_product->quantity < $product['quantity'] )			
					$product['quantity'] = $order_product->quantity;				
				$product['price'] = $order_product->price;
			}			
			$product['quantity'] = !isset($product['quantity']) ? 1:$product['quantity'];
			$product['reserve'] = usam_string_to_float($product['reserve']);
			if ( $product['reserve'] > $product['quantity'] )			
				$product['reserve'] = $product['quantity'];					
			if ( empty($product['name']) )
				$product['name'] = get_the_title( $product['product_id'] );				
			$product = apply_filters( 'usam_insert_product_shipped', $product, $id );							
			$format = $this->get_product_format( $product );		
			if ( $wpdb->insert( USAM_TABLE_SHIPPED_PRODUCTS, $product, $format ) ) 
			{					
				$result = true;
				$product['id'] = $wpdb->insert_id;		
				if( $product['reserve'] )
				{
					$reserve = $product['reserve']*usam_get_product_unit($product['product_id'], $product['unit_measure']);				
					$this->update_product_stock( $product['product_id'], $reserve );
				}
				$this->change_history( $product['product_id'], 'add', 'product', $product['name'] );					
			}
		}
		wp_cache_delete( $this->get( 'id' ), 'usam_products_order' );		
		if ( $calculate )
		{
			$this->recalculated = true;				
			$this->save();		
		}
		return $result;
	}	
	
	public function update_products( $products, $calculate = true ) 
	{	
		global $wpdb;		
		
		if ( !$this->exists() || empty($products) )
			return false;
		
		$products = apply_filters( 'usam_edit_shipped_products_set_properties', $products, $this );	
		do_action( 'usam_edit_shipped_products_pre_edit', $this );

		$id = $this->get('id');
		$shipped_products = usam_get_products_shipped_document( $id );
		foreach( $products as $k => &$new_product )		
		{
			$delete = true;			
			foreach( $shipped_products as $product )
			{
				if ( isset($new_product['id']) && $product->id == $new_product['id'] )
				{
					$delete = false;				
					$new_product['product_id'] = $product->product_id;	
					if ( !isset($new_product['unit_measure']) )		
						$new_product['unit_measure'] = $product->unit_measure;	
					if ( !isset($new_product['reserve']) )		
						$new_product['reserve'] = $product->reserve;	
					if ( !isset($new_product['quantity']) )		
						$new_product['quantity'] = $product->quantity;	
					$new_product['old_reserve'] = $product->reserve*usam_get_product_unit($product->product_id, $product->unit_measure);					
				}				
			}
			if ( $delete )
				unset($products[$k]);
		}
		foreach( $products as $k => $product )
		{				
			$old_reserve = $product['old_reserve'];
			if ( $product['reserve'] > $product['quantity'] )			
				$product['reserve'] = $product['quantity'];	
			
			if ( empty($product['date_insert']) )
				$product['date_insert'] = date("Y-m-d H:i:s");		
			
			$product = $this->product_key_cleaning( $product );
			$format = $this->get_product_format( $product );	
			$where = ['id' => $product['id'], 'document_id' => $id];						
			$format_where = $this->get_product_format( $where );		
			if ( $wpdb->update( USAM_TABLE_SHIPPED_PRODUCTS, $product, $where, $format, $format_where ) )
			{
				$reserve = $product['reserve']*usam_get_product_unit($product['product_id'], $product['unit_measure']);
				if ( $old_reserve != $reserve )
				{
					$this->update_product_stock( $product['product_id'], $reserve - $old_reserve );
				}
				foreach( $shipped_products as $number => $p )
				{						
					if ( $product['id'] == $p->id )
					{
						foreach( $product as $key => $value )
						{
							if ( $value != $p->$key )
								$this->change_history( $p->product_id, 'edit', 'product_'.$key, $value, $p->$key );	
							$shipped_products[$number]->$key = $value;
						}						
					}
				}
			}
		}
		wp_cache_set( $id, $shipped_products, "usam_products_shipped_document" );		
		if ( $calculate )
		{
			$this->recalculated = true;	
			$this->save();
		}
		do_action( 'usam_edit_shipped_products_edit', $this );
		return true;
	}			
	
	public function delete_products( $products, $calculate = true ) 
	{
		global $wpdb;
				
		if ( !$this->exists() || empty($products) )
			return false;
		
		$document_id = $this->get('id');		
		$ids = [];
		$product_ids = [];
		foreach( $products as $product )
		{
			$ids[] = $product->id;		
			$product_ids[] = $product->product_id;
			if ( $product->reserve !== 0 ) 
			{		
				$reserve = 0 - $product->reserve*usam_get_product_unit($product->product_id, $product->unit_measure);
				$this->update_product_stock( $product->product_id, $reserve);
			}
		}
		$result_deleted = $wpdb->query("DELETE FROM `".USAM_TABLE_SHIPPED_PRODUCTS."` WHERE document_id=$document_id AND `id` IN (".implode(',',$ids).")");
		if ( $result_deleted )
		{
			if ( $calculate )
			{
				$this->recalculated = true;				
				$this->save();		
			}
		}	
		wp_cache_delete( $this->get( 'id' ), 'usam_products_shipped_document' );
		return $result_deleted;	
	}
	
	public function update_product_stock( $product_id, $new_reserve ) 
	{  
		$storage_id = $this->get('storage');
		if ( $storage_id && $new_reserve > 0 )
		{
			$reserve = usam_get_reserve_in_storage( $storage_id, $product_id );	
			$reserve = $reserve + $new_reserve;
			$reserve = $reserve<0?0:$reserve;  
			usam_update_product_stock($product_id, 'reserve_'.$storage_id, $reserve );				
			usam_recalculate_stock_product( $product_id );
		}
	}
	
	public function reserve_products() 
	{ 
		$storage_id = $this->get('storage');
		if ( $storage_id )
		{
			$document_id = $this->get('id');
			$products = usam_get_products_shipped_document( $document_id );	
			$update_products = [];
			foreach( $products as $product  )
			{
				$new_reserve = $product->quantity-$product->reserve;
				$product = (array)$product;
				if ( $new_reserve )
				{
					$product['reserve'] = $new_reserve;
					$update_products[] = $product;
				}
			}	
			if ( $update_products )
				$this->update_products( $update_products );		
		}
	}
	
	private function calculate_totalprice( ) 
	{	
		if ( $this->recalculated )
		{ 		
			$document_id = $this->get('id');
			$products = usam_get_products_shipped_document( $document_id );	
			$totalprice = 0;		
			foreach ( $products as $product ) 
			{
				$totalprice += $product->quantity * $product->price;				
			}			
			if ( $this->data['totalprice'] != $totalprice )
			{
				$this->data['totalprice'] = $totalprice;
				$this->changed_data['totalprice'] = $totalprice;				
			//	$this->changed_data['number_products'] = $this->data['number_products'];
			//	$this->data['number_products'] = count($products);
			}
		}		
	}		

	// уменьшение запасов
	private function products_stock_updates( ) 
	{		
		$document_id = $this->get('id');
		$current_status = $this->get( 'status' );
		$products = usam_get_products_shipped_document( $document_id );	
		if ( empty($products) )
			return;		
	
		if ( $this->is_closed() || $current_status == 'canceled' )
		{			
			$this->remove_all_product_from_reserve();
			$add = false;				
		}
		elseif ( isset($this->changed_data['status']) && $this->changed_data['status'] == self::CLOSED )				
			$add = true;
		else
			return;			
		
		if ( get_option( 'usam_ftp_download_balances' ) )
			return;		
	
		$storage_id = $this->get('storage');		
		if ( !empty($storage_id) && $current_status != 'canceled' )
		{			
			usam_products_stock_updates($products, $storage_id, $add );
		}		
	}	
	
	private function change_history( $operation, $field = '', $value = '', $old_value = '' ) 
	{ 		
		$id = $this->get('id');
		usam_insert_change_history(['object_id' => $id, 'object_type' => 'shipped_document', 'operation' => $operation, 'field' => $field,	'value' => $value, 'old_value' => $old_value]);
	}	

	public function save()
	{
		global $wpdb;

		do_action( 'usam_document_shipped_pre_save', $this );	
		
		$result = false;	
		if ( $this->args['col'] ) 
		{	
			$this->calculate_totalprice();
			if ( empty($this->changed_data) )
				return true;
					
			$where_format = self::get_column_format( $this->args['col'] );				
			do_action( 'usam_document_shipped_pre_update', $this );	

			$this->data = apply_filters( 'usam_document_shipped_update_data', $this->data );			
			if ( isset($this->changed_data['track_id']) && $this->get('status') != 'shipped' )
				$this->data['status'] = 'referred';	
			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );
			$result = $wpdb->update( USAM_TABLE_SHIPPED_DOCUMENTS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{ 						
				$this->delete_cache( );					
				
				$id = $this->get('id');	
				foreach ( $this->changed_data as $key => $value ) 
				{ 
					if ( isset($this->data[$key]) )
						$this->change_history( 0, 'edit', $key, $this->data[$key], $value );
				}
				if ( isset($this->changed_data['track_id']) )
				{
					do_action( 'usam_update_document_shipped_track_id',  $id, $this->data['track_id'], $this->changed_data['track_id'], $this );
				}
				if ( isset($this->changed_data['price']) || isset($this->changed_data['include_in_cost']) )
				{ 
					$include_in_cost = $this->get('include_in_cost');	
					$price = $this->get('price');
					$order_id = $this->get('order_id');	
					$data = usam_get_order( $order_id );	   
					if ( isset($this->changed_data['price']) && !isset($this->changed_data['include_in_cost']) && $include_in_cost )
					{ // Если сменилась цена, но включение в цену доставки не изменилось						
						$shipping = $data['shipping'] + $price - $this->changed_data['price'];	
					}
					elseif ( isset($this->changed_data['price']) && isset($this->changed_data['include_in_cost']) && $include_in_cost == 0 )
					{	
						$shipping = $data['shipping'] - $this->changed_data['price'];						
					}
					elseif ( isset($this->changed_data['include_in_cost']) && $include_in_cost )
					{			
						$shipping = $data['shipping'] + $price;						
					}
					elseif ( isset($this->changed_data['include_in_cost']) && $include_in_cost == 0  )
					{	
						$shipping = $data['shipping'] - $price;						
					}	
					else
						$shipping = 0;
					usam_update_order( $order_id, ['shipping' => $shipping]); 
				}			
				if ( isset($this->changed_data['status']) ) 
				{								
					$current_status = $this->get( 'status' );				
					if ( isset($this->changed_data['storage']) )
						$this->products_stock_updates( );					
					
					if ( $current_status == self::CLOSED )
						usam_delete_shipped_document_metadata( $id, 'delivery_problem' );				
					
					usam_update_object_count_status( $current_status, 'shipped' );
					usam_update_object_count_status( $this->changed_data['status'], 'shipped' );
					do_action( 'usam_update_document_shipped_status',  $id, $current_status, $this->changed_data['status'], $this );						
				}	
				do_action( 'usam_document_shipped_update', $this );	
			} 			
		} 
		else 
		{  
			do_action( 'usam_document_shipped_pre_insert' );			
						
			if ( isset($this->data['id']) )
				unset($this->data['id']);
			
			if ( empty($this->data['date_insert']) )
				$this->data['date_insert'] = date( "Y-m-d H:i:s" );		
			
			if ( empty($this->data['status']) ) 
				$this->data['status'] = 'pending';					
			
			if ( !isset($this->data['include_in_cost']) )
				$this->data['include_in_cost'] = 1;
			
			if ( !isset($this->data['price']) )
				$this->data['price'] = 0;		

			if ( !isset($this->data['courier']) )
				$this->data['courier'] = 0;			
				
			if ( !isset($this->data['customer_type']) )
				$this->data['customer_type'] = 'company';	

			if ( empty($this->data['number']) )
				$this->data['number'] = usam_get_document_number( 'shipped' );			
			
			if ( empty($this->data['storage']) )
				$this->data['storage'] = get_option('usam_default_reservation_storage', '' );	
			if( isset($this->data['seller_id']) && get_option('usam_website_type', 'store' ) !== 'marketplace' )			
				unset($this->data['seller_id']);
		
			$this->data = apply_filters( 'usam_document_shipped_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );		
			$result = $wpdb->insert( USAM_TABLE_SHIPPED_DOCUMENTS, $this->data, $formats );					
			if ( $result ) 
			{							
				$this->set( 'id', $wpdb->insert_id );	
				$this->exists = true;				
				if ( !empty($this->data['price']) && $this->data['include_in_cost'] )
				{ 
					$data = usam_get_order( $this->data['order_id'] );
					$shipping = $data['shipping'] + $this->data['price'];					
					usam_update_order( $this->data['order_id'], ['shipping' => $shipping]);	
				}						
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];	
				usam_update_object_count_status( $this->data['status'], 'shipped' );
				do_action( 'usam_document_shipped_insert', $this );	
			}			
		} 		
		do_action( 'usam_document_shipped_save', $this );
		$this->changed_data = [];
		return $result;
	}
	
	public function is_closed() 
	{
		return $this->get( 'status' ) == self::CLOSED;
	}
}


// Получить документ доставки
function usam_get_shipped_document( $document_id, $col = 'id' )
{
	$shipped = new USAM_Shipped_Document( $document_id, $col );
	return $shipped->get_data( );	
}

// Добавить документ доставки
function usam_insert_shipped_document( $data, $products = [], $link = [] )
{	
	$shipped = new USAM_Shipped_Document( $data );	
	$id = false;
	if ( $shipped->save() )
	{
		$id = $shipped->get('id');
		if ( $link )
		{
			$link['document_link_id'] = $id;
			$link['document_link_type'] = 'shipped';
			usam_add_document_link( $link );
		}
		if ( !empty($products) )
			$shipped->add_products( $products );
	}
	return $id;
}

// Обновить документ доставки
function usam_update_shipped_document( $document_id, $data, $products = null )
{
	$shipped = new USAM_Shipped_Document( $document_id );
	$result = false;
	if ( !empty($data) )
	{
		$shipped->set( $data );
		$result = $shipped->save();
	}
	if ( $products !== null )
	{
		if ( $shipped->set_products( $products ) )
			$result = true;
	}
	return $result;
}

// Удалить документ доставки
function usam_delete_shipped_document( $document_id )
{
	$shipped = new USAM_Shipped_Document( $document_id );
	return $shipped->delete();
}

function usam_get_products_shipped_document( $document_id ) 
{			
	$object_type = "usam_products_shipped_document";
	$cache = wp_cache_get( $document_id, $object_type );		
	if ( $cache === false )		
	{		
		global $wpdb;
		$cache = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `". USAM_TABLE_SHIPPED_PRODUCTS ."` WHERE document_id = '%d'", $document_id) );
		if ( empty($cache))
			$cache = array();		
		else
		{
			
			foreach ($cache as $key => $result ) 
			{
				foreach (['id', 'document_id', 'product_id'] as $column ) 
				{			
					if ( isset($result->$column) )	
						$cache[$key]->$column = (int)$result->$column;
				}
				foreach (['price', 'reserve', 'quantity'] as $column ) 
				{			
					if ( isset($result->$column) )	
						$cache[$key]->$column = (float)$result->$column;
				}
			}
		}
		wp_cache_set( $document_id, $cache, $object_type );	
	}	
	return $cache;	
}

function usam_delete_shippeds( $args, $delete = false )
{	
	global $wpdb;
	if ( empty($args) )
		return 0;
	$args['cache_results'] = true;
	require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
	$documents = usam_get_shipping_documents( $args );
	if ( empty($documents) )
		return 0;

	$statuses = [];
	$delete_ids = [];
	usam_update_object_count_status( false );	
	foreach ( $documents as $document ) 
	{			
		usam_delete_shipped_document( $document->id );	
		$delete_ids[] = $document->id;
	}
	$wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_LINKS." WHERE document_id IN (".implode(',',$delete_ids).") AND document_type='shipped' OR document_link_id IN (".implode(',',$delete_ids).") AND document_link_type='shipped'");	
	usam_update_object_count_status( true );	
	return count($documents);
}

function usam_get_standard_delivery_problems( ) 
{ 
	return ['no_contact_person' => __('Нет контактного лица','usam'), 'couldnt_contact' => __('Не удалось связаться','usam'), 'product' => __('Проблема с товаром','usam'), 'document' => __('Проблема с документами','usam')];
}

function usam_add_shipped_document_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('document', $object_id, $meta_key, $meta_value, USAM_TABLE_SHIPPED_DOCUMENT_META, $prev_value );
}

function usam_get_shipped_document_metadata( $object_id, $meta_key = '', $single = true) 
{	
	$result = usam_get_metadata('document', $object_id, USAM_TABLE_SHIPPED_DOCUMENT_META, $meta_key, $single );
	switch ( $meta_key ) 
	{
		case 'tax_name' :
			$result = (string)$result;
		break;
		case 'tax_is_in_price' :
		case 'tax_rate' :		
			$result = (int)$result;
		break;		
	}
	return $result;
}

function usam_update_shipped_document_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('document', $object_id, $meta_key, $meta_value, USAM_TABLE_SHIPPED_DOCUMENT_META, $prev_value );
}

function usam_delete_shipped_document_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('document', $object_id, $meta_key, USAM_TABLE_SHIPPED_DOCUMENT_META, $meta_value, $delete_all );
}
?>