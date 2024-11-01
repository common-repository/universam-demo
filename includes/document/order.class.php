<?php
// * основной класс ЖУРНАЛА ПРОДАЖ. Создает заказы в таблице*
class USAM_Order
{	
	const CREATURE  = 'incomplete_sale';
	const CLOSED    = 'closed';	
	private static $string_cols = [
		'code',			
		'date_insert',
		'date_paid',	
		'date_status_update',
		'type_price',
		'number',
		'status',	
		'source',			
	];
	private static $int_cols = [
		'id',			
		'user_ID',
		'company_id',	
		'contact_id',	
		'manager_id',		
		'number_products',			
		'type_payer',		
		'paid',		
		'bank_account_id',			
	];	
	private static $float_cols = [
		'totalprice',	
		'cost_price',	
		'shipping',				
	];
	private $product_db_format = ['id' => '%d', 'product_id' => '%d', 'name' => '%s', 'order_id' => '%d', 'price' => '%f', 'old_price' => '%f', 'product_day' => '%d', 'quantity' => '%f', 'unit_measure' => '%s', 'bonus' => '%d', 'used_bonuses' => '%d', 'date_insert' => '%s', 'purchase_price' => '%f'];
	private $data = [];		
	private $fetched       = false;
	private $is_order_paid = false;	
	
	private $recalculated = false; // Пересчитать заказ
	private $changed_data = [];	

	private $customer_data = array();	
	private $document_shipped = array();
	private $document_payment = null;
	private $downloadable_files = null;
	private $args = array( 'col'   => '', 'value' => '' );	
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
		if ( ! in_array( $col, array( 'id', 'code' ) ) )
			return;		
			
		$this->args = array( 'col' => $col, 'value' => $value );	
		if ( $col == 'code'  && $id = wp_cache_get( $value, 'usam_order_code' ) )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $id;
		}		
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{		
			$this->data = wp_cache_get( $value, 'usam_order' );
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
		wp_cache_set( $id, $this->data, 'usam_order' );
		if ( $code = $this->get( 'code' ) )
			wp_cache_set( $code, $id, 'usam_order_code' );	
		do_action( 'usam_order_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{ 		
		$id = $this->get( 'id' );
		if ( !$id )
			return false;
		
		wp_cache_delete( $id, 'usam_order' );
		wp_cache_delete( $this->get( 'code' ), 'usam_order_code' );
		wp_cache_delete( $id, 'usam_products_order' );
		wp_cache_delete( $id, 'usam_shipped_documents_order' );
		wp_cache_delete( $id, 'usam_payment_order' );
		wp_cache_delete( $id, 'usam_properties_order' );
		wp_cache_delete( $id, 'usam_order_meta' );	
		do_action( 'usam_order_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete( ) 
	{		
		$id = $this->get( 'id' );
		if ( !$id )
			return false;
		
		$this->delete_cache( );
		usam_delete_orders(['include' => [$id]]);	
	}		
// ДОКУМЕНТЫ ОПЛАТЫ==========================================================================================================
					
	// Получить данные о платежах	
	public function get_payment_status_sum( ) 
	{
		$order_id = $this->get('id');
		$payments = usam_get_payment_documents_order( $order_id );			
		$payment_status_sum = ['total_paid' => 0];
		foreach ($payments as $key => $payment)
		{					
			switch ( $payment->status ) 
			{																
				case 3: // Оплачено					
					$payment_status_sum['total_paid'] += $payment->sum;											
				break;
				case 4: // Возвращено
					$payment_status_sum['total_paid'] -= $payment->sum;											
				break;	
			}
		}		
		$payment_status_sum['payment_required'] = $this->data['totalprice'] - $payment_status_sum['total_paid'];
		if ( $payment_status_sum['payment_required'] == 0 )
			$payment_status_sum['order_status'] = __('Оплачен', 'usam');
		elseif ( $payment_status_sum['payment_required'] == $this->data['totalprice'] )
			$payment_status_sum['order_status'] = __('Не оплачен', 'usam');	
		elseif ( $payment_status_sum['payment_required'] > 0 )
			$payment_status_sum['order_status'] = __('Частично оплачен', 'usam');		
		else
			$payment_status_sum['order_status'] = __('Переплачен', 'usam');	
		return $payment_status_sum;
	}
	
	// Последний документ оплаты
	public function get_payment_document( ) 
	{				
		$order_id = $this->get('id');
		$payment_documents = usam_get_payment_documents_order( $order_id );	
		$payment_document = (array)array_pop($payment_documents);		
		return $payment_document;
	}
	
	// Последний способ доставки
	public function get_shipped_document( ) 
	{				
		$order_id = $this->get('id');
		$shipped_documents = usam_get_shipping_documents_order( $order_id );
		$shipped_document = (array)array_pop($shipped_documents);		
		return $shipped_document;
	}
		
	// Пометить заказ как оплаченный
	public function mark_order_as_paid( ) 
	{			
		$totalprice = (float)$this->get( 'totalprice' );		
		$payments = usam_get_payment_documents_order( $this->get('id') );
		$sum_payment = 0;
		$date_paid = '';
		foreach ($payments as $key => $payment)
		{
			if ( $payment->status == 3 )
			{
				$sum_payment += $payment->sum;
				if ( !$date_paid )
					$date_paid = $payment->date_payed ? $payment->date_payed : date("Y-m-d H:i:s");
			}
		}
		if ( $sum_payment >= $totalprice )
		{	// Полностью оплачен	
			if ( $this->get('paid') != 2 )
				$this->set(['date_paid' => $date_paid, 'paid' => 2]);
		}			
		elseif ( $sum_payment > 0 )
		{ // Частично оплачен		
			if ( $this->get('paid') != 1 )
				$this->set(['date_paid' => '', 'paid' => 1]);
		}	
		elseif ( $sum_payment <= 0 )
		{ // Не оплачен				
			if ( $this->get('paid') != 0 )
				$this->set(['date_paid' => '', 'paid' => 0]);
		} 		
	}
	
	public function get_downloadable_files( )
	{
		global $wpdb;	
		if ( empty($this->downloadable_files) )
		{			
			$order_id = $this->get('id');
			$this->downloadable_files = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_DOWNLOAD_STATUS." WHERE order_id = '%d'", $order_id ) );		
		}
		return $this->downloadable_files;
	}

	public function edit_downloadable_files( $files ) 
	{			
		$files = apply_filters( 'usam_downloadable_files_set_properties', $files, $this );			
		do_action( 'usam_downloadable_files_pre_edit', $this );
		
		$i = 0;		
		foreach( $files as $file_id => $data )
		{			
			$download_status = new USAM_PRODUCT_DOWNLOAD_STATUS( $data['id'] );
			$download_status->set( $data );			
			$download_status->save();		
		}		
		$this->downloadable_files = array();		
		do_action( 'usam_downloadable_files_edit', $this );
		return $i;
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
		$order_products = usam_get_products_order( $id );
		$add_products = [];
		$update_products = [];
		$processed = [];
		$result = false;		
		if ( !empty($products) )
		{
			foreach ( $products as $new_product ) 
			{
				$add = true;
				if ( empty($new_product['unit_measure']) )
					$new_product['unit_measure'] = usam_get_product_property($new_product['product_id'], 'unit_measure_code');
				foreach ( $order_products as $k => $product ) 
				{
					if ( isset($new_product['id']) && ctype_digit($new_product['id']) || $product->product_id == $new_product['product_id'] && $product->unit_measure == $new_product['unit_measure'] )
					{
						$new_product['id'] = $product->id;
						$update_products[] = $new_product;					
						unset($order_products[$k]);
						$add = false;
						break;
					}
				}
				if ( $add && ( empty($processed[$new_product['product_id']]) || !in_array($new_product['unit_measure'], $processed[$new_product['product_id']]) ))
					$add_products[] = $new_product;
				$processed[$new_product['product_id']][] = $new_product['unit_measure'];
			}	
		}				
		if ( $this->add_products( $add_products, 0, false ) )
			$result = true;	
		if ( $this->update_products( $update_products, false ) )
			$result = true;
		if ( $this->delete_products( $order_products, false) )
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
			
		$order_id = $this->get('id');
		if ( !$order_id )
			return false;		
		
		$products = apply_filters( 'usam_edit_order_products_set_properties', $products, $this );	
		do_action( 'usam_edit_order_products_pre_edit', $this );		
		
		$type_price = $this->get('type_price');
		$order_products = usam_get_products_order( $order_id );		
		$min_selling_price = get_option('usam_min_selling_price_product');	
		$product_taxes = usam_get_order_product_taxes( $order_id );
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
				$product['price'] = usam_round_price( $product['price'], $type_price );
			
				$result_tax = $this->calculate_tax( $product );
				if( !empty($result_tax['product_taxes']) )
				{
					foreach ( $result_tax['product_taxes'] as $new_tax ) 
					{						
						foreach( $product_taxes as $tax ) 
						{
							if ( $tax->product_id == $product['product_id'] && $tax->unit_measure == $product['unit_measure'] && $tax->tax_id == $new_tax['tax_id'])
							{
								usam_update_order_product_tax( $tax->id, $new_tax );
								continue 2;
							}
						}		
						$new_tax['order_id'] = $order_id;
						usam_insert_order_product_tax( $new_tax );						
					}	
				}
				else
					$delete_product_taxes[] = ['unit_measure' => $product['unit_measure'], 'product_id' => $product['product_id'] , 'order_id' => $order_id];
			}		
			if ( isset($product['old_price']) )
				$product['old_price'] = usam_round_price( $product['old_price'], $type_price );			
			$format = $this->get_data_format_product( $product );			
			$where = ['id' => $product['id'], 'order_id' => $order_id];	
			$products[$k] = $product; 
			if ( $wpdb->update( USAM_TABLE_PRODUCTS_ORDER, $product, $where, $format, $this->get_data_format_product( $where ) ) )
			{								
				foreach( $order_products as $number => $p )
				{							
					if ( $product['id'] == $p->id )
					{
						do_action( 'usam_order_product_update', $product, (array)$p, $this );
						foreach( $product as $key => $value )
						{
							if ( $value != $p->$key && $key != 'old_price' )
							{
								$this->change_history( $p->product_id, 'edit', 'product_'.$key, $value, $p->$key );		
							}
							$order_products[$number]->$key = $value;
						}						
					}
				}
			}
		} 
		if ( $delete_product_taxes && $product_taxes )
		{
			foreach( $delete_product_taxes as $tax )
				$wpdb->delete( USAM_TABLE_TAX_PRODUCT_ORDER, $tax);
		}
		wp_cache_set( $order_id, $order_products, "usam_products_order" );		
		wp_cache_delete( $order_id, 'usam_order_product_taxes' );
		
		if ( $calculate )
		{
			$this->recalculated = true;	
			$this->save();
		}
		do_action( 'usam_order_products_update', $products, $this );
		return true;
	}	
	
	public function calculate_tax( $product ) 
	{
		$location_ids = array();
		$location = usam_get_order_customerdata( $this->get('id'), 'location' );			
		if ( $location )
			$location_ids = array_values(usam_get_address_locations( $location, 'id' )); 	
	
		$payment = $this->get_payment_document();
		return usam_calculate_tax(['location_ids' => $location_ids, 'type_payer' => $this->get('type_payer'), 'payment' => !empty($payment['gateway_id'])?$payment['gateway_id']:0, 'price' => $product['price']], $product['product_id'] );
	}
		
	public function add_products( $products, $shipped_document_id = 0, $calculate = true ) 
	{
		global $wpdb;
			
		if ( $this->exists == 0 || empty($products) )
			return false;
			
		$order_id = $this->get('id');
		if ( !$order_id )
			return false;
		
		$result = false;
		$shipped_products = [];
		$download = get_option('usam_max_downloads', 1);
		
		$product_ids = [];
		foreach( $products as &$new_product )
		{
			$new_product = (array)$new_product;
			if ( !empty($new_product['product_id']) )
				$product_ids[] = $new_product['product_id'];
		}
		if ( empty($product_ids) )
			return false;
		
		$type_price = $this->get('type_price');		
		if ( $type_price )
			$products_discounts = usam_get_products_discounts(['product_id' => $product_ids, 'code_price' => $type_price]);									
		foreach( $products as $k => $product )
		{
			$product = $this->product_key_cleaning( $product );
			$product['date_insert'] = date( "Y-m-d H:i:s" );
			$product['order_id'] = $order_id;									
			
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
			
			$unit_measure_code = usam_get_product_property($product['product_id'], 'unit_measure_code');	
			if ( $unit_measure_code === $product['unit_measure'] )
			{		
				$unit = usam_get_product_unit( $product['product_id'], $product['unit_measure'] );
				$product['price'] = $product['price']/$unit;
				$product['old_price'] = $product['old_price']/$unit;
			}						
			$product['quantity'] = !isset($product['quantity']) ? 1:$product['quantity'];	
			if ( !isset($product['product_day']) )
			{
				$rule_product_day = usam_get_active_products_day_by_codeprice( $type_price, $product['product_id'] );			
				$product['product_day'] = !empty($rule_product_day)?$rule_product_day->rule_id:0;
			}															
			if ( empty($product['name']) )
				$product['name'] = get_the_title( $product['product_id'] );								
			if ( !isset($product['bonus']) )
			{
				$bonus_settings = usam_get_product_bonus_settings( $product['product_id'], $type_price );								
				$product['bonus'] = 0;
				if ( !empty($bonus_settings['value']) )
				{
					if ( $bonus_settings['type'] == 'p' )
						$product['bonus'] = round($product['price'] * $bonus_settings['value'] / 100, 0);
					else
						$product['bonus'] = $bonus_settings['value'];
				}	
			}
			$product = apply_filters( 'usam_insert_product_order', $product, $order_id );	
			
			if ( empty($product['old_price']) )
				$product['old_price'] = $product['price'];		
			if ( empty($product['used_bonuses']) )
				$product['used_bonuses'] = 0;				
			
			$products[$k] = $product;
			$format = $this->get_data_format_product( $product );				
			if ( $wpdb->insert( USAM_TABLE_PRODUCTS_ORDER, $product, $format ) ) 
			{				
				do_action( 'usam_order_product_insert', $product, $this );
				$result = true;
				$product['id'] = $wpdb->insert_id;		
				$result_tax = $this->calculate_tax( $product );
				if( !empty($result_tax['product_taxes']) )
				{
					foreach ( $result_tax['product_taxes'] as $new_tax ) 
					{
						$new_tax['order_id'] = $order_id;
						usam_insert_order_product_tax( $new_tax );
					}	
				}
				if ( !empty($products_discounts[$product['product_id']]) )
				{ // Скидка на товар		
					require_once( USAM_FILE_PATH .'/includes/document/document_discount.class.php' );
					foreach( $products_discounts[$product['product_id']] as $product_discount )
						usam_set_document_discount( $product_discount->discount_id, ['document_id' => $order_id, 'document_type' => 'order', 'product_id' => $product['product_id']] );
				}
				if ( $shipped_document_id > 0 && usam_check_product_type_sold( 'product', $product['product_id'] ) )
					$shipped_products[] = ['product_id' => $product['product_id'], 'quantity' => $product['quantity'], 'reserve' => $product['quantity'], 'unit_measure' => $product['unit_measure']];
				$this->change_history( $product['product_id'], 'add', 'product', $product['name'] );					
			}
		}		
		$files = usam_get_files(['object_id' => $product_ids, 'type' => 'product']);
		if( count($files) > 0 ) 			
		{		
			foreach($files as $file)
			{ 
				usam_insert_product_download_status(['fileid' => $file->id, 'order_id' => $order_id, 'object_id' => $file->object_id, 'downloads' => $download*$product['quantity']]);
			}		
		}				
		wp_cache_delete( $order_id, 'usam_products_order' );
		wp_cache_delete( $order_id, 'usam_order_product_taxes' );	
		
		if ( $calculate )
		{
			$this->recalculated = true;				
			$this->save();		
		}
		do_action( 'usam_order_products_insert', $products, $this );
		if ( $shipped_document_id > 0 && count($shipped_products) )
		{		
			$shipped = new USAM_Shipped_Document( $shipped_document_id );
			$shipped->set_products( $shipped_products );
		}
		return $result;
	}
	
	// Получить данные купленного товара
	public function get_order_product( $product_id, $unit_measure ) 
	{
		$order_id = $this->get('id');
		$products = usam_get_products_order( $order_id );	
		foreach( $products as $product )
		{
			if ( $product->product_id == $product_id && $product->unit_measure == $unit_measure )
				return $product;			
		}	
		return [];
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
		$order_id = $this->get('id');				
		
		$documents = usam_get_shipping_documents_order( $order_id );
		if ( !empty($documents) )
		{
			$document_ids = [];
			foreach( $documents as $document )
				$document_ids[] = $document->id;
			if ( !empty($document_ids) )
				$wpdb->query("DELETE FROM `".USAM_TABLE_SHIPPED_PRODUCTS."` WHERE product_id IN (".implode(',',$product_ids).") AND document_id IN (".implode(',',$document_ids).")");
		}
		$wpdb->query("DELETE FROM `".USAM_TABLE_DOWNLOAD_STATUS."` WHERE `order_id`=$order_id AND product_id IN (".implode(',',$product_ids).")");		
		$wpdb->query("DELETE FROM `".USAM_TABLE_TAX_PRODUCT_ORDER."` WHERE `order_id`=$order_id AND `product_id` IN (".implode(',',$product_ids).")");		
		$wpdb->query("DELETE FROM " . USAM_TABLE_DOCUMENT_DISCOUNTS . " WHERE `document_id`=$order_id AND document_type='order' AND `product_id` IN (".implode(',',$product_ids).")");
		$result_deleted = $wpdb->query("DELETE FROM `".USAM_TABLE_PRODUCTS_ORDER."` WHERE `id` IN (".implode(',',$ids).")");
		if ( $result_deleted )
		{
			$this->delete_cache( );		
			do_action( 'usam_order_products_delete', $products );
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
			usam_insert_change_history(['object_id' => $id, 'object_type' => 'order', 'sub_object_id' => $sub_object_id, 'operation' => $operation, 'field' => $field, 'value' => $value, 'old_value' => $old_value]);
		} 			
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

		$format = self::get_column_format( $col );
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_ORDERS." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{					
			$this->exists = true;
			$this->data = apply_filters( 'usam_order_data', $data );	
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
		do_action( 'usam_order_fetched', $this );	
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
		return apply_filters( 'usam_order_get_property', $value, $key, $this );
	}

	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{ 
		if ( empty($this->data) )
			$this->fetch();
		
		return apply_filters( 'usam_order_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_order_set_properties', $properties, $this );			
		if ( array_key_exists( 'paid', $properties ) ) 
		{	
			$previous_order_paid = $this->get( 'paid' );
			if ( $properties['paid'] != $previous_order_paid && $properties['paid'] == 2 )
				$this->is_order_paid = true;			
		}			
		// Проверим нужно ли пересчитать заказ
		$keys = array( 'shipping' );			
		foreach ( $keys as $key ) 
		{
			$previous = $this->get( $key );
			if ( isset($properties[$key]) && $properties[$key] != $previous )
			{				
				$this->recalculated = true;			
				break;
			}
		}			
		if ( isset($properties['number']) )
			$properties['number'] = mb_strtoupper($properties['number']);	
						
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
		
	// Сохраняет в базу данных	
	public function save()
	{
		global $wpdb;
		do_action( 'usam_order_pre_save', $this );	
		$result = false;		
		
		if ( isset($this->data['user_ID']) )
			$this->data['user_ID'] = (int)$this->data['user_ID'];
		
		if ( isset($this->data['type_payer']) )
			$this->data['type_payer'] = (int)$this->data['type_payer'];			
					
		if ( $this->args['col'] ) 
		{										
			$this->calculate_totalprice();
			if ( empty($this->changed_data) )
				return true;

			if ( isset($this->changed_data['status']) ) 
				$this->data['date_status_update'] = date( "Y-m-d H:i:s" );
			$where_format = self::get_column_format( $this->args['col'] );		
			do_action( 'usam_order_pre_update', $this );		
			
			$this->data = apply_filters( 'usam_order_update_data', $this->data );	
			$data = $this->get_update_data();		
			$formats = $this->get_data_format( $data );	
			$str = array();
			foreach( $formats as $key => $value ) 
			{
				if ( ($key == 'date_paid' || $key == 'date_status_update') && empty($data[$key]) )
				{
					$str[] = "`$key` = NULL";
					unset($data[$key]);
				}
				else
					$str[] = "`$key` = '$value'";		
			}	
			$result = $wpdb->query( $wpdb->prepare("UPDATE `".USAM_TABLE_ORDERS."` SET ".implode( ', ', $str )." WHERE ".$this->args['col']."='$where_format'", array_merge( array_values( $data ), [ $this->args['value'] ] ) ) );
			if ( $result )
			{
				$this->update_cache();				
				$order_id = $this->get('id');
				if ( !$this->is_creature() )
				{ 
					foreach( $this->changed_data as $key => $value ) 
					{ 
						if ( $key != 'number_products ' && isset($this->data[$key]) )
							$this->change_history( 0, 'edit', $key, $this->data[$key], $value );
					}			
				}						
				if ( $this->is_order_paid ) 
				{
					do_action( 'usam_order_paid', $this );
				}
				if ( $this->is_transaction_completed() )
					$this->update_downloadable_status();	
				if ( isset($this->changed_data['status']) ) 
				{				
					$current_status = $this->get( 'status' );	
					do_action( 'usam_update_order_status', $order_id, $current_status, $this->changed_data['status'], $this );					
				}	
				do_action( 'usam_order_update', $this );
			}
		} 
		else 
		{   			
			do_action( 'usam_order_pre_insert' );			
			
			$default = ['totalprice' => 0, 'number_products' => 0, 'code' => '', 'paid' => 0, 'date_paid' => '', 'status' => 'incomplete_sale', 'user_ID' => 0, 'manager_id' => 0, 'contact_id' => 0, 'company_id' => 0, 'shipping' => 0, 'source' => 'order', 'date_insert' => date("Y-m-d H:i:s"), 'date_status_update' => date("Y-m-d H:i:s") ];
			
			if ( isset($this->data['id']) )
				unset($this->data['id']);
			
			$this->data = array_merge($default, $this->data);
			if ( !empty($this->data['number']) )
			{
				$number = $wpdb->get_var("SELECT COUNT(*) FROM `".USAM_TABLE_ORDERS."` WHERE `number` = '".$this->data['number']."'");		
				if ( !empty($number) )
					$this->data['number'] = '';
			}
			if ( empty($this->data['type_price']) )
				$this->data['type_price'] = usam_get_manager_type_price();
			if ( empty($this->data['number']) )
				$this->data['number'] = usam_get_document_number( 'order' );
			if ( empty($this->data['bank_account_id']) )
				$this->data['bank_account_id'] = get_option( 'usam_shop_company', 0 );	
			
			if ( empty($this->data['type_payer']) )
				$this->data['type_payer'] = usam_get_type_payer_customer();		
					
			$this->data = apply_filters( 'usam_order_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );	
			$data = $this->data;
			foreach( $formats as $key => $value ) 
			{
				if( $key == 'date_paid' && empty($data[$key]) )			
					unset($data[$key]);	
			}
			$result = $wpdb->insert( USAM_TABLE_ORDERS, $data, $formats );	
			if ( $result ) 
			{ 
				$this->set( 'id', $wpdb->insert_id );
				$this->args = ['col' => 'id', 'value' => $wpdb->insert_id];	
				$this->exists = true;	
				
				usam_update_object_count_status( $this->data['status'], 'order' );						
				do_action( 'usam_order_insert', $this );
			}
		} 	
		do_action( 'usam_order_save', $this );
		$this->changed_data = [];
		return $result;
	}
	
	// Пересчитать общую сумму заказа, когда добавляют или удаляют товар в заказе
	private function calculate_totalprice( ) 
	{	
		if ( $this->recalculated )
		{ 		
			$order_id = $this->get('id');
			$products = usam_get_products_order( $order_id );	
			
			$product_taxes = usam_get_order_product_taxes( $order_id ); 
			$subtotalprice = 0;		
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
				$subtotalprice += $product->quantity * ($product->price + $tax);	
			} 
			$id = $this->get( 'id' );	
			$totalprice = $this->get( 'totalprice' );			
			$this->data['totalprice'] = $subtotalprice + $this->data['shipping'];
			$setting_price = usam_get_setting_price_by_code( $this->data['type_price'] );
			if ( isset($setting_price['rounding']) )
				$this->data['totalprice'] = round($this->data['totalprice'], $setting_price['rounding']);
			$this->set(['number_products' => count($products)]);  
			if ( $totalprice != $this->data['totalprice'] )
			{
				$this->changed_data['totalprice'] = $totalprice;	
				$this->mark_order_as_paid( );
			}			
		}		
	}
	
	public function get_calculated_data( )
	{
		if ( ! $this->exists() )
			return array();
		
		$order_id = $this->get('id');
		$products = usam_get_products_order( $order_id );	

		$order_basket = 0;		
		$order_final_basket = 0;		
		$data['weight'] = 0;	
		$data['volume'] = 0;	
		$data['number_products'] = 0;
		$post_ids = array();
		foreach ( $products as $item ) 
		{
			$post_ids[] = $item->product_id;
		}
		usam_update_cache( $post_ids, [USAM_TABLE_PRODUCT_META => 'product_meta'], 'product_id' );
		$length = [];
		foreach ( $products as $item ) 
		{			
			$order_basket += $item->old_price * $item->quantity;		
			$order_final_basket += $item->price * $item->quantity;				
			$weight = (float)usam_get_product_weight( $item->product_id );
			
			$data['weight'] += $weight * $item->quantity;
			$data['number_products'] += $item->quantity;
			$data['volume'] += usam_get_product_volume( $item->product_id ) * $item->quantity;
		}		
		$data['order_basket'] = $order_basket;		
		$data['order_final_basket'] = $order_final_basket;	
		$data['order_basket_discount'] = $order_basket - $order_final_basket;	
		return apply_filters( 'usam_order_calculated_data', $data, $this );	
	}


	private function update_downloadable_status()
	{
		global $wpdb;
		$wpdb->update( USAM_TABLE_DOWNLOAD_STATUS, array('active' => 1), array( 'order_id' => $this->get( 'id' ) ) );				
	}		
	
	public function is_creature() {
		return $this->get( 'status' ) == self::CREATURE;
	}	
	
	public function is_transaction_completed() {
		return $this->get( 'paid' ) == 2;
	}
	
	public function is_closed_order() {
		return $this->get( 'status' ) == self::CLOSED;
	}
}

// Получить данные заказа
function usam_get_order( $value, $col = 'id' )  
{
   if ( $value )
   {
		$order = new USAM_Order( $value, $col );	
		return $order->get_data();
   }
   else
	   return [];
}

function usam_update_order( $id, $data, $products = null, $metas = [], $by = 'id' )
{
	$result = false;
	if ( $id )
	{
		$order = new USAM_Order( $id, $by );
		if ( $data )
		{
			$order->set( $data );
			$result = $order->save();
		}	
		if ( is_array($products) )
		{
			if ( $order->set_products( $products ) )
				$result = true;
		}
		foreach( $metas as $k => $v )
			usam_add_order_metadata($id, $k, $v);
	}
	return $result;
}

function usam_insert_order( $data, $products = null, $metas = [] )
{
	$order = new USAM_Order( $data );
	$id = false;
	if ( $order->save() )
	{
		$id = $order->get('id');
		if ( is_array($products) )
			$order->add_products( $products );
		foreach( $metas as $k => $v )
			usam_add_order_metadata($id, $k, $v);
	}
	return $id;
}

// Удалить заказ
function usam_delete_orders( $args, $delete = false ) 
{	
	global $wpdb;
	if ( empty($args) )
		return 0;	
	if( !isset($args['status__not_in']) )
		$args['status__not_in'] = '';
	$orders = usam_get_orders( $args );	
	if ( empty($orders) )
		return 0;		
	
	$delete_ids = [];
	$delete_orders = [];
	$update_ids = [];
	usam_update_object_count_status( false );
	foreach ( $orders as $order )
	{	
		if ( $order->status == 'delete' || $delete )
		{			
			$delete_ids[] = $order->id;		
			$delete_orders[] = $order;	
			usam_update_object_count_status( $order->status, 'order' );
			do_action( 'usam_order_before_delete', (array)$order, $order->id );			
		}
		else
			usam_update_order( $order->id, ['status' => 'delete'] );
	}	
	if ( $delete_ids )
	{
		$in = implode( ', ', $delete_ids );	
			
		require_once(USAM_FILE_PATH.'/includes/document/products_order_query.class.php');
		$products = usam_get_products_order_query(['order_id' => $delete_ids]);	
		do_action( 'usam_order_products_delete', $products );
		
		$wpdb->query("DELETE FROM " . USAM_TABLE_PRODUCTS_ORDER . " WHERE order_id IN ($in)");
		$wpdb->query("DELETE FROM " . USAM_TABLE_DOWNLOAD_STATUS . " WHERE order_id IN ($in)" );		
		$wpdb->query("DELETE FROM " . USAM_TABLE_TAX_PRODUCT_ORDER . " WHERE order_id IN ($in)" );		
		
		require_once( USAM_FILE_PATH.'/includes/crm/comment.class.php' );
		usam_delete_comments(['object_id' => $delete_ids, 'object_type' => 'order'], true);
		usam_delete_bonuses_transaction(['order_id' => $delete_ids]);				
		$wpdb->query("DELETE FROM " . USAM_TABLE_CHANGE_HISTORY . " WHERE object_id IN ($in) AND object_type='order'");
		$wpdb->query("DELETE FROM " . USAM_TABLE_DOCUMENT_DISCOUNTS . " WHERE document_id IN ($in) AND document_type='order'");
		$wpdb->query("DELETE FROM " . USAM_TABLE_ORDER_META . " WHERE order_id IN ($in)");
		$wpdb->query("DELETE FROM ".USAM_TABLE_RIBBON_LINKS." WHERE object_type='order' AND object_id IN (".implode(",", $delete_ids).")");
		$wpdb->query("DELETE FROM " . USAM_TABLE_ORDERS . " WHERE id IN ($in)");	
		$wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_LINKS." WHERE document_id IN (".implode(',',$delete_ids).") AND document_type='order' OR document_link_id IN (".implode(',',$delete_ids).") AND document_link_type='order'");		
		
		foreach ( $delete_orders as $order )
		{				
			wp_cache_delete( $order->id, 'usam_order' );
			wp_cache_delete( $order->id, 'usam_products_order' );
			wp_cache_delete( $order->id, 'usam_properties_order' );
			wp_cache_delete( $order->id, 'usam_order_meta' );
			
			do_action( 'usam_order_delete', $order );						
		}
		usam_delete_shippeds(['order_id' => $delete_ids], true);			
		usam_delete_payments(['document_id' => $delete_ids], true);
	}
	usam_update_object_count_status( true );
	return count($delete_ids);
}

//Статус оплаты заказа
function usam_get_order_payment_status_name( $paid )
{
	if ( $paid == 2 )
		$payment_status = __('Оплачен', 'usam');
	elseif ( $paid == 1 )
		$payment_status = __('Частично оплачен', 'usam');
	else
		$payment_status = __('Не оплачен', 'usam');	
	return $payment_status;
}

function usam_get_order_product_taxes( $order_id )
{	
	$cache_key = 'usam_order_product_taxes';
	$cache = wp_cache_get( $order_id, $cache_key );		
	if ( $cache === false )		
	{	
		require_once(USAM_FILE_PATH.'/includes/document/order_product_taxes_query.class.php');
		$cache = usam_get_order_product_taxes_query(['order_id' => $order_id]);
		wp_cache_set( $order_id, $cache, $cache_key );
	}		
	return $cache;
}	

function usam_get_order_taxes( $order_id )
{	
	$product_taxes = usam_get_order_product_taxes( $order_id );  
	$products = usam_get_products_order( $order_id );   	
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

function usam_get_tax_amount_order( $order_id )
{	
	$product_taxes = usam_get_order_product_taxes( $order_id );  
	$products = usam_get_products_order( $order_id );
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

// Получить купленные товары
// Вывод $formatting db или html
function usam_get_products_order( $order_id, $formatting = 'db' ) 
{			
	if ( !$order_id )
		return array();
	$object_type = "usam_products_order";
	$cache = wp_cache_get( $order_id, $object_type );		
	if ( $cache === false )		
	{
		require_once(USAM_FILE_PATH.'/includes/document/products_order_query.class.php');
		$cache = usam_get_products_order_query(['order_id' => $order_id]);
		if ( empty($cache))
			$cache = [];		
		wp_cache_set( $order_id, $cache, $object_type );	
	}	
	if ( usam_is_multisite() && !is_main_site() )
	{ //Загрузить переводы
		$ids = [];
		foreach( $cache as $product )
			$ids[$product->product_id] = usam_get_post_id_multisite($product->product_id);
		if ( $ids )
		{
			$products = usam_get_products(['post__in' => $ids, 'update_post_term_cache' => false, 'update_post_meta_cache' => false, 'stocks_cache' => false, 'prices_cache' => false, 'cache_results' => false]);		
			foreach( $cache as &$order_product )
			{ 
				foreach( $products as $product )
				{					
					if ( $product->ID == $ids[$order_product->product_id] )
					{
						$order_product->name = $product->post_title;
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

function usam_get_order_property_types( $order_id ) 
{	
	$properties = usam_get_cache_properties( );
	$results = array( );
	$full_address = array( );	
	$keys = ['payer', 'payer_address', 'delivery_contact', 'delivery_address'];
	foreach ( $properties as $property )
	{
		$single = $property->field_type == 'checkbox'?false:true;
		$value = usam_get_order_metadata($order_id, $property->code, $single);	
		foreach ( $keys as $key )
		{
			$metadata = usam_get_property_metadata($property->id, $key);
			if ( $metadata )
			{  
				$display_value = usam_get_formatted_property( $value, $property );
				$results[$key][$property->code] = $display_value;
				if ( $key == 'payer_address' || 'delivery_address' )
				{
					if ( !isset($full_address[$key]) )
						$full_address[$key] = array();
					if ( $property->field_type == 'location' )
						$full_address[$key][] = usam_get_full_locations_name( $value ); 
					else
						$full_address[$key][] = $display_value;
				}
			}
		}			
	}
	foreach ( $results as $key => $result )
	{
		$results[$key]['_name'] = implode(' ', $result);
		if ( $key == 'payer_address' || 'delivery_address' )
			$results[$key]['full_address'] = implode(' ', $full_address[$key]);
	}
	return $results;
}

// Получить данные об отгрузках
function usam_get_shipping_documents_order( $order_id ) 
{		
	if ( empty($order_id) )
		return array();
	
	$object_type = 'usam_shipped_documents_order';		
	$cache = wp_cache_get( $order_id, $object_type );
	if ( $cache === false )		
	{			
		require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
		$cache = usam_get_shipping_documents(['order' => 'DESC', 'order_id' => $order_id]);
		if ( empty($cache) )
			$cache = array();
		
		wp_cache_set( $order_id, $cache, $object_type );
	}	
	return $cache;
}	

// Получить документ оплаты
function usam_get_payment_documents_order( $order_id ) 
{				
	if ( empty($order_id) )
		return [];
			
	$object_type = 'usam_payment_order';	
	$cache = wp_cache_get( $order_id, $object_type );		
	if ( $cache === false )			
	{				
		require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
		$cache = usam_get_payments(['order' => 'DESC', 'document_id' => $order_id]);
		wp_cache_set( $order_id, $cache, $object_type );						
	}	
	return $cache;	
}			

function usam_get_columns_order_import() 
{
	$columns = [
		'document_id' => __('ID', 'usam'), 
		'number'     => __('Номер', 'usam'), 		
		'status'     => __('Статус', 'usam'), 		
		'bank_account' => __('Номер счета', 'usam'), 		
		'totalprice'  => __('Сумма', 'usam'), 
		'paid'       => __('Статус оплаты', 'usam'),
		'date_paid'  => __('Дата оплаты', 'usam'), 		
		'type_payer' => __('Тип покупателя', 'usam'), 
		'manager_id' => __('Номер менеджера', 'usam'), 		
		'manager'    => __('Имя менеджера', 'usam'),	
		'coupon_name' => __('Номер купона', 'usam'),
		'bonus'       => __('Используемые бонусы', 'usam'),		
		'notes'       => __('Комментарий менеджера', 'usam'),
		'date_insert'    => __('Дата создания', 'usam'),		
		'shipping'       => __('Стоимость доставки', 'usam'),			
		'poduct_id'	     => __('ID товара','usam'),
		'name'	         => __('Имя товара','usam'),		
		'sku'			  => __('Артикул','usam'),				
		'code'		      => __('Внешний код','usam'),
		'barcode'         => __('Штрихкод','usam'),
		'quantity'		  => __('Количество в заказе','usam'),
		'unit'		      => __('Коэффициент единицы измерения','usam'),
		'unit_measure'    => __('Единица измерения','usam'),
		'price'		      => __('Цена','usam'),
		'old_price'	      => __('Старая цена','usam'),		
		'discount'	      => __('Скидка','usam')
	];
	$fields = usam_get_properties(['type' => ['order'], 'active' => 1, 'fields' => 'code=>name']);		
	$columns += $fields;
	return apply_filters('usam_columns_order_import', $columns);
}

function usam_get_columns_order_export()
{
	$columns = [
		'document_id' => __('ID', 'usam'), 
		'number'     => __('Номер', 'usam'), 		
		'status'     => __('Статус', 'usam'), 		
		'bank_account' => __('Номер счета', 'usam'), 		
		'totalprice'  => __('Сумма', 'usam'), 
		'currency'   => __('Валюта', 'usam'),		
		'paid'       => __('Статус оплаты', 'usam'),
		'date_paid'  => __('Дата оплаты', 'usam'), 		
		'type_payer' => __('Тип покупателя', 'usam'), 
		'manager_id' => __('Номер менеджера', 'usam'), 		
		'manager'    => __('Имя менеджера', 'usam'),	
		'coupon_name' => __('Номер купона', 'usam'),
		'bonus'       => __('Используемые бонусы', 'usam'),		
		'notes'       => __('Комментарий менеджера', 'usam'),
		'date_insert'    => __('Дата создания', 'usam'),		
		'shipping'       => __('Стоимость доставки', 'usam'),		
		'source'         => __('Источник', 'usam'),				
		'poduct_id'	     => __('ID товара','usam'),
		'name'	         => __('Имя товара','usam'),		
		'sku'			  => __('Артикул','usam'),				
		'code'		      => __('Внешний код','usam'),
		'weight'		  => __('Вес коробки','usam'),
		'weight_unit'     => __('Единица измерения веса коробки','usam'),
		'barcode'         => __('Штрихкод','usam'),
		'contractor'      => __('Поставщик товара','usam'),
		'quantity'		  => __('Количество в заказе','usam'),
		'unit'		      => __('Коэффициент единицы измерения','usam'),
		'unit_measure'    => __('Единица измерения','usam'),
		'name_unit_measure' => __('Название единицы измерения','usam'),
		'price'		      => __('Цена','usam'),
		'old_price'	      => __('Старая цена','usam'),		
		'discount'	      => __('Скидка','usam'),			
		'length'	      => __('Длина коробки','usam'),		
		'width'	          => __('Ширина коробки','usam'),		
		'height'     	  => __('Высота коробки','usam'),
		'nds'        	  => __('НДС','usam'),		
	];		
	$product_attributes = get_terms(['hide_empty' => 0, 'orderby' => 'sort', 'taxonomy' => 'usam-product_attributes']);	
	foreach( $product_attributes as $term )
	{ 
		if ( $term->parent != 0 )
			$columns['attribute_'.$term->term_id] = $term->name.' ('.__('свойство товара','usam').')';
	}
	$fields = usam_get_properties(['type' => ['order'], 'active' => 1, 'fields' => 'code=>name']);		
	$columns += $fields;
	return apply_filters('usam_columns_order_export', $columns);
}

function usam_get_order_customerdata( $order_id, $type ) 
{
	$properties = usam_get_cache_properties();	
	foreach ( $properties as $property )
	{	 
		if ( $property->field_type == $type )
		{
			$value = usam_get_order_metadata( $order_id, $property->code );	
			if ( $value !== '' && $value !== false )
				return $value;
		}
	}	
	return false;
}

function usam_get_used_bonuses_order( $order_id ) 
{
	require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );
	return (int)usam_get_bonuses(['order_id' => $order_id, 'fields' => 'SUM_Bonuses', 'number' => 1]);
}

function usam_add_order_customerdata( $id, $data ) 
{
	$properties = usam_get_cache_properties();	
	$customer_data = [];
	$update = false;			
	foreach( $data as $meta_key => $meta_value ) 
	{
		if( isset($properties[$meta_key]) )
		{			
			$update = usam_save_property_meta( $id, $properties[$meta_key], $meta_value );
			$customer_data[$meta_key] = $meta_value;
		}
	}
	if ( $update )
		do_action( 'usam_add_order_customerdata', $id, $customer_data );
	return $update;
}

function usam_get_order_props_value( $args ) 
{
	global $wpdb;
	
	if ( isset($args['fields']) )
	{
		$fields = $args['fields'] == 'all'?'*':implode( ",", $args['fields'] );
	}
	else
		$fields = '*';
	$_where = array('1=1');
	if ( !empty($args['order_id']) )
	{
		$ids = implode( ',', wp_parse_id_list( $args['order_id'] ) );
		$_where[] = "order_id IN ($ids)";
	}
	if ( !empty($args['meta_key']) )
	{ 
		$meta_keys = implode( "','", (array)$args['meta_key'] );
		$_where[] = "meta_key IN ('$meta_keys')";
	}
	$where = implode( " AND ", $_where);
	$results = $wpdb->get_results( "SELECT $fields FROM ".USAM_TABLE_ORDER_META." WHERE $where" );	
	return $results;
}

function usam_get_order_files( $order_id )
{		
	global $wpdb;			
	$downloadables = $wpdb->get_results( $wpdb->prepare("SELECT * FROM ".USAM_TABLE_DOWNLOAD_STATUS." WHERE order_id = '%d'", $order_id) );	
	$downloadable_files = array();
	if ( !empty($downloadables) )
	{		
		foreach ( $downloadables as $downloadable )
		{
			$downloadable_files[$downloadable->fileid] = (array)$downloadable;
		}		
		$files = usam_get_files(['include' => array_keys($downloadable_files)]);
		foreach ( $files as $file )
		{
			$downloadable_files[$file->id] = array_merge((array)$file, $downloadable_files[$file->id]);	
		}
	}
	return $downloadable_files;
}

function usam_add_order_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('order', $object_id, $meta_key, $meta_value, USAM_TABLE_ORDER_META, $prev_value );
}

function usam_get_order_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('order', $object_id, USAM_TABLE_ORDER_META, $meta_key, $single );
}

function usam_update_order_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('order', $object_id, $meta_key, $meta_value, USAM_TABLE_ORDER_META, $prev_value );
}

function usam_delete_order_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('order', $object_id, $meta_key, USAM_TABLE_ORDER_META, $meta_value, $delete_all );
}


function usam_add_product_order_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('product_key', $object_id, $meta_key, $meta_value, USAM_TABLE_PRODUCT_ORDER_META, $prev_value );
}

function usam_get_product_order_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('product_key', $object_id, USAM_TABLE_PRODUCT_ORDER_META, $meta_key, $single );
}

function usam_update_product_order_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('product_key', $object_id, $meta_key, $meta_value, USAM_TABLE_PRODUCT_ORDER_META, $prev_value );
}

function usam_delete_product_order_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('product_key', $object_id, $meta_key, USAM_TABLE_PRODUCT_ORDER_META, $meta_value, $delete_all );
}


function usam_get_order_id_by_meta( $key, $value ) 
{
	global $wpdb;	
	if ( !$value ) 
		return;
	
	$cache_key = "usam_order_$key";
	$order_id = wp_cache_get( $value, $cache_key );
	if ($order_id === false) 
	{	
		$order_id = (int)$wpdb->get_var($wpdb->prepare("SELECT order_id FROM ".USAM_TABLE_ORDER_META." WHERE meta_key='%s' AND meta_value='%s' LIMIT 1", $key, $value));
		wp_cache_set($value, $order_id, $cache_key);
	}
	else
		$order_id = (int)$order_id;
	return $order_id;
}

function usam_get_order_ids_by_code( $codes, $meta_key = 'code' )
{
	global $wpdb;	
	if ( !$codes )
		return [];
	$order_codes = [];
	$cache_key = "usam_product_$meta_key";
	foreach($codes as $k => $code )
	{				
		$order_id = wp_cache_get( $code, $cache_key );
		if ($order_id !== false) 
		{
			$order_codes[$code] = $order_id;
			unset($codes[$k]);
		}
	} 	
	if ( empty($codes) )
		return $order_codes;
	$results = $wpdb->get_results("SELECT meta_value, order_id FROM ".USAM_TABLE_ORDER_META." WHERE meta_value IN ('".implode("','", $codes )."') AND meta_key='{$meta_key}'");
	$c = [];
	foreach($results as $result )	
	{
		$c[$result->meta_value] = $result->order_id;
	}	
	foreach( $codes as $code )
	{				
		if ( !isset($c[$code]) )
			wp_cache_set($code, 0, $cache_key );
		else
		{
			$order_codes[$code] = $c[$code];		
			wp_cache_set($code, $order_codes[$code], $cache_key );
		}
	} 	
	return $order_codes;
}