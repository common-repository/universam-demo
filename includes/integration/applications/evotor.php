<?php
/*
	Name: Эвотор
	Description: Передача товаров на кассу, загрузка проведенных чеков. Изменение статуса оплаты заказа
	Group: cashbox
	Price: free
	Icon: evotor
*/
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_Evotor extends USAM_Application
{		
	protected $API_URL = "https://api.evotor.ru";
	protected $max_count = 3;

	private function get_load_product( $product ) 
	{
		$results = [
			'name' => $product->post_title, 		
			'code' => $product->ID,
			'allow_to_sell' => true, //Признак разрешения продажи. Указывает можно добавить товар в чек или нельзя:			
			'type' => 'NORMAL', 
			'quantity' => usam_string_to_float(usam_get_product_stock($product->ID, 'stock' )), 
			'measure_name' => usam_get_product_unit_name( $product->ID, 'short' ),
			'tax' => $this->option['tax_type'],
			'price' => usam_get_product_price($product->ID),
			'cost_price' => usam_get_product_price($product->ID), //Закупочная цена товара или модификации товара.
			'description' => '',
			'article_number' => usam_get_product_meta( $product->ID, 'sku' ),
			'barcodes' => [ usam_get_product_meta( $product->ID, 'barcode' ) ],
		];  		
		return $results;
	}	
	
	public function update_products( $args = [] ) 
	{
		$args['posts_per_page'] = 2000;
		$products = usam_get_products( $args );	
		$add = [];
		$update = [];
		foreach ( $products as $product ) 
		{			
			$load_product = $this->get_load_product( $product );
			$code_evotor = usam_get_product_meta( $product->ID, 'code_evotor');
			if ( $code_evotor )
			{
				$load_product['id'] = $code_evotor;
				$update[] = $load_product;
			}
			else
				$add[] = $load_product;
		}							
		if ( $update )	
		{
			$args = $this->get_args( 'PUT', $update );		
			$results = $this->send_request( "stores/".$this->option['store_id']."/products", $args );
		}
		if ( $add )	
		{
			$args = $this->get_args( 'POST', $add );		
			$results = $this->send_request( "stores/".$this->option['store_id']."/products", $args );			
			foreach ( $results as $result ) 
			{
				if ( !empty($result['code']) )
					usam_update_product_meta($result['code'], 'code_evotor', $result['id'] );	
			}			
		}
		return true;
	}
			
	public function set_groups( $args = [] ) 
	{
		$args['taxonomy'] = 'usam-category';
		$args['orderby'] = 'id';
		$args['order'] = 'ASC';
		$terms = get_terms( $args );	
		$params = [];
		foreach ( $terms as $term ) 
		{
			$row = ['name' => $term->name, 'uuid' => usam_get_term_uuid( $term->term_id )];
			if ( $term->parent_id )
				$row['parent_id'] = usam_get_term_uuid( $term->parent_id );
			$params[] = $row;
		}
		if ( $params )
		{
			$args = $this->get_args( 'POST', $params );
			$result = $this->send_request( "inventories/stores/".$this->option['store_id']."/product-groups", $args );	
		}
		return $result;	
	}
	
	public function delete_products( $args = [] ) 
	{
		$products = usam_get_products( $args );
		$ids = [];
		foreach ( $products as $product ) 
		{
			$code_evotor = usam_get_product_meta( $product->ID, 'code_evotor');
			if ( $code_evotor )
				$ids[] = $code_evotor;
		}
		$result = true;
		if ( $ids )
		{
			$args = $this->get_args( 'DELETE', ['id' => implode(',',$ids)]);
			$result = $this->send_request( "stores/".$this->option['store_id']."/products", $args );	
		}
		return $result;	
	}
	
	public function delete_product( $id ) 
	{
		$args = $this->get_args( 'DELETE' );
		return $this->send_request( "stores/".$this->option['store_id']."/products/".$id, $args );	
	}
	
	function delete_all_products($data, $number, $event) 
	{		
		$done = 0;
		for ($i = 1; $i <= $this->max_count; $i++) 
		{			
			$args = [];
	//		if( !empty($data['cursor']) )
	//			$args['cursor'] = $data['cursor'];	
			$products = $this->get_products( $args );
	//		if( !empty($products['paging']['cursor']) )
	//			$data['cursor'] = $products['paging']['cursor'];				
			if ( !empty($products['items']) )
			{
				$ids = [];
				foreach( $products['items'] as $i => $product )
				{
					$ids[] = $product['id'];
					if( $i % 100 == 0 )
					{
						$args = $this->get_args('DELETE', ['id' => implode(',',$ids)], true);
						$result = $this->send_request( "stores/".$this->option['store_id']."/products", $args );
						$ids = [];
					}
				}
			}
			$event['launch_number']++;
			$done += count($products['items']);
			sleep(1);
		}		
		return ['done' => $done, 'data' => $data, 'launch_number' => $event['launch_number']];
	}
	
	protected function get_default_option( ) 
	{
		return ['access_token' => '', 'store_id' => '', 'tax_type' => 'VAT_0', 'storage_id' => 0, 'number' => ''];
	}
	
	protected function get_args( $method = 'GET', $params = [], $bulk = false )
	{ 		
		$headers["X-Authorization"] = $this->token;		
		$headers["Accept"] = 'application/vnd.evotor.v2+json';	
		if( $bulk )
			$headers["Content-type"] = 'application/vnd.evotor.v2+bulk+json';
		else		
			$headers["Content-type"] = 'application/vnd.evotor.v2+json';
		$args = array(
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers
		);	
		if ( $params )
		{
			if ( $method == 'GET' )
				$args['body'] = $params;
			else
				$args['body'] = json_encode($params);
		}
		return $args;
	}
	
	public function register_routes( ) 
	{ 
		register_rest_route( $this->namespace, $this->route.'/(?P<method>.+)', array(		
			array(
				'permission_callback' => false,
				'methods'  => WP_REST_Server::ALLMETHODS,// GET, POST, PUT, PATCH, DELETE
				'callback' => 'usam_application_rest_api',	
				'args' => array()
			),			
		));
	}

	public function rest_api( $request ) 
	{			
		$headers = $request->get_headers();	
		if ( isset($headers['authorization']) && $headers['authorization'] == 'Bearer '.$this->option['access_token'] )
		{	
			$parameters = $request->get_json_params();					
			$method = "controller_".$request->get_param('method');
			if ( method_exists($this, $method) )
				$this->$method( $parameters );	
			return new WP_REST_Response( true, 200 );			
		}
	} 
	
	public function controller_check( $parameters ) 
	{		
		$data = $parameters['data'];		
		$type = $data['type']=='SELL'?'check':'check_return';			
		$document = ['date_insert' => date("Y-m-d H:i:s",strtotime($data['dateTime'])), 'type' => $type, 'status' => 'approved'];
		if ( !empty($data['employeeId']) )
		{
			$employee = usam_get_employees(['meta_key' => 'evotor_id', 'meta_value' => sanitize_text_field($data['employeeId']), 'number' => 1]);
			if ( $employee )
				$document['manager_id'] = $employee['id'];
		}		
		$document_id = usam_insert_document( $document );
		if ( $document_id )
		{
			usam_update_document_metadata($document_id, 'store_id', $this->option['store_id'] );
			$shift_id = !empty($data['shiftId'])?sanitize_text_field($data['shiftId']):'';
			usam_update_document_metadata($document_id, 'shift_id', $shift_id );
			switch( $data['paymentSource'] ) 
			{	
				case 'PAY_CARD'://оплата по карте
					$payment_type = 'card';			
				case 'OTHER'://  нескольких способов оплаты, 			
					
				break;	
				default:				
				case 'PAY_CASH'://оплата наличными без сдачи;
					$payment_type = 'cash';
				break;	
			}
			usam_update_document_metadata($document_id, 'payment_type', $payment_type );
			usam_update_document_metadata($document_id, 'code_evotor', sanitize_text_field($data['id']) );

			$info_check = !empty($data['infoCheck'])?sanitize_text_field($data['infoCheck']):'';
			usam_update_document_metadata($document_id, 'info_check', $info_check );			
			
			$storeId = !empty($data['storeId'])?sanitize_text_field($data['storeId']):'';
			$deviceId = !empty($data['deviceId'])?sanitize_text_field($data['deviceId']):'';
			$products = [];
			$product_ids = [];
			$totalprice = 0;
			foreach ( $data['items'] as $item )
			{
				$old_price = $item['price']+$item['discount'];
				$product_id = usam_get_product_id_by_meta( 'code_evotor', $item['id'] );				
				if ( $product_id )
				{
					$unit = usam_get_unit_measure($item['measureName'], 'short');
					$unit_measure = isset($unit['code']) ? $unit['code'] : '';
					$products[] = ['product_id' => $product_id, 'quantity' => $item['quantity'], 'price' => $item['price'], 'old_price' => $old_price, 'name' => $item['name'], 'unit_measure' => $unit_measure];
					$product_ids[$product_id] = $item['quantity'];
					$totalprice += $item['sumPrice'];
				}
			}			/*"id\":\"e011f034-1fa0-4615-863b-5f078ee3492b\",\"timestamp\":1633531545846,\"type\":\"ReceiptCreated\",\"version\":2,\"userId\":\"01-000000005417056\",\"data\":{\"id\":\"7d802f68-6e8a-4185-8c9b-fdf14d1b6b76\",\"deviceId\":\"20210906-4104-40E9-8011-E6F84AA2BA14\",\"storeId\":\"20210906-D57C-40A7-8053-B3A9F316925A\",\"dateTime\":\"2021-10-06T14:45:42.000Z\",\"type\":\"SELL\",\"shiftId\":\"3\",\"employeeId\":\"20210906-2A07-4090-80B1-DF7AFAE5793C\",\"paymentSource\":\"PAY_CASH\",\"infoCheck\":false,\"egais\":false,\"items\":[{\"id\":\"8b47f8d3-0441-4727-a9ae-21685cbd492e\",\"name\":\"Глубоко увлажняющая минеральная маска Lavender Mint Deep Conditioning Mineral Hair Mask, 19 г х 1 шт\",\"itemType\":\"NORMAL\",\"measureName\":\"шт\",\"quantity\":1,\"price\":530,\"costPrice\":530,\"sumPrice\":530,\"tax\":0,\"taxPercent\":0.0,\"discount\":0}],\"totalTax\":0,\"totalDiscount\":0.00,\"totalAmount\":530.0,\"extras\":{}}}"*/			
			if ( $products )
			{
				$new_document = new USAM_Document( $document_id );	
				$new_document->add_products( $products );			
			}		
			require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');	
			$statuses = usam_get_object_statuses(['fields' => 'internalname','type' => 'order', 'close' => 1]);
			$statuses[] = 'accepted_payment';
			$ids = usam_get_orders(['fields' => 'id', 'status__not_in' => $statuses, 'conditions' => [['key' => 'number_products', 'compare' => '=', 'value' => count($data['items'])],['key' => 'totalprice', 'compare' => '=', 'value' => $totalprice]], 'cache_order_products' => true]);
						
			$order_ids = [];
			foreach ( $ids as $id )
			{
				$products_order = usam_get_products_order( $id );
				$result = true;
				foreach ( $products_order as $product )
				{
					if ( !isset($product_ids[$product->product_id]) || $product_ids[$product->product_id] != $product->quantity )
					{
						$result = false;
						break;
					}
				}
				if ( $result )
					$order_ids[] = $id;
			}			
			if ( $order_ids && count($order_ids) == 1  )
			{
				$order = usam_get_order( $order_ids[0] );
				usam_update_order( $order_ids[0], ['status' => 'accepted_payment'] );
				if ( $order['company_id'] )
					usam_update_document($document_id, ['customer_id' => $order['company_id'], 'customer_type' => 'company']);
				elseif ( $order['contact_id'] )
					usam_update_document($document_id, ['customer_id' => $order['contact_id'], 'customer_type' => 'contact']);
			}
		}
		else
			return new WP_Error('no_insert_document', 'Invalid insert document', ['status' => 404]);
	}
	
	public function controller_employee( $parameters ) 
	{
		$employee = usam_get_employees(['meta_key' => 'evotor_id', 'meta_value' => sanitize_text_field($parameters['uuid']), 'number' => 1]);
		if ( empty($employee) )
		{
			$employee = usam_get_employees(['meta_key' => 'mobilephone', 'meta_value' => $parameters['phone'], 'number' => 1]);		
			if ( !empty($employee) )
				usam_update_contact_metadata($employee['id'], 'evotor_id', sanitize_text_field($parameters['uuid']) );
			else
				return false;
		}
		$data = ['lastname' => sanitize_text_field(stripslashes($parameters['lastName'])), 'firstname' => sanitize_text_field(stripslashes($parameters['name']))];
		if ( !empty($parameters['patronymicName']) )
			$data['patronymic'] = sanitize_text_field(stripslashes($parameters['patronymicName']));
		$data['appeal'] = usam_get_formatting_contact_name( $metas );	
	//	usam_update_contact( $employee['id'], $data );
	}
	
	public function controller_documents( $parameters ) 
	{
		
	}
	
	public function filter_bulk_actions_orders( $groups ) 
	{	
		$storage_id = usam_get_application_metadata( $this->option['id'], 'storage_id' );	
		$storage = usam_get_storage( $storage_id );
		$storage_name = $storage ? ' &#171;'.$storage['title'].'&#187;' : '';
		$option = '<option value="cashbox-'.$this->option['id'].'">'.usam_get_name_service( $this->option['service_code'] ).$storage_name. "</option>";
		$groups['cashbox']['name'] = __('Выгрузить товары в кассу', 'usam');
		$groups['cashbox'][] = $option;
		return $groups;
	}
	
	public function filter_order_action_cashbox( $result, $action, $order_ids ) 
	{
		if ( strpos($action, 'cashbox') !== false) 
		{						
			$str = explode("cashbox-", $action);
			if ( !empty($str[1]) && $str[1] == $this->option['id'] )
			{
				require_once(USAM_FILE_PATH.'/includes/document/products_order_query.class.php');
				$ids = usam_get_products_order_query(['fields' => 'product_id', 'order_id' => $order_ids]);
				$this->update_products(['post__in' => $ids]);
				$result = ['export' => count($order_ids)]; 
				$errors = $this->get_errors();
				if ( $errors )
					$result['errors'] = $errors;								
				return $result; 
			}
		}
		return $result;
	}
	
	public function service_load( ) 
	{
		add_action('rest_api_init', [$this,'register_routes']);	
		add_action('usam_application_catalog_schedule_'.$this->option['service_code'],  "usam_".$this->option['service_code']."_update_catalog" );	
		add_action('usam_application_documents_schedule_'.$this->option['service_code'],  "usam_".$this->option['service_code']."_loading_documents" );		
		if ( current_user_can('export_order') )
		{
			add_filter('usam_bulk_actions_orders', [$this,'filter_bulk_actions_orders']);				
			add_filter('usam_orders_actions', [$this,'filter_order_action_cashbox'], 10, 3);
		}	
	}		
		
	public function get_stores( ) 
	{
		$args = $this->get_args( 'GET' );
		$stores = $this->send_request( "stores", $args );
		if ( isset($stores['items']) )
			return $stores['items'];
		return [];
	}
	
	public function get_employees( ) 
	{
		$args = $this->get_args( 'GET' );
		return $this->send_request( "employees", $args );
	}
	
	public function get_products( ) 
	{
		$args = $this->get_args( 'GET' );
		return $this->send_request( "stores/".$this->option['store_id']."/products", $args );	
	}
		
	public function get_documents( $args = [] ) 
	{
		$args = $this->get_args( 'GET', $args );
		return $this->send_request( "stores/".$this->option['store_id']."/documents", $args );	
	}
	
	public function loading_documents( $args = [] ) 
	{
		$since = usam_get_application_metadata($this->id, 'documents_'.$this->option['service_code']);		
		$cursor = '';		
		do 
		{			
			if ( $cursor )
				$request = ['cursor' => $cursor];
			else
				$request = ['since' => $since];
			
			$documents = $this->get_documents( $request );		
			if ( isset($documents['items']) )	
			{
				$cursor = !empty($documents['paging']['next_cursor'])?$documents['paging']['next_cursor']:'';
				$ids = [];
				foreach( $documents['items'] as $document )
				{
					$ids[] = $document['id'];
				}
				usam_get_document_ids_by_code($ids, 'code_evotor');
				foreach( $documents['items'] as $document )
				{
					if ( !usam_get_document_id_by_meta( 'code_evotor', $document['id'] ) )
					{						
						$method = "document_".$document['type'];
						if ( method_exists($this, $method) )			
							$this->$method( $document );
					}
				}
			}
			else
				break;
		} 
		while ( $cursor );		
		usam_update_application_metadata($this->id, 'documents_'.$this->option['service_code'], $this->milliseconds());
		return 0;
	}
	
	function milliseconds() 
	{
		$mt = explode(' ', microtime());
		return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
	}
	
	public function document_sell( $data ) 
	{				
		$document = ['date_insert' => date("Y-m-d H:i:s",strtotime($data['close_date'])), 'type' => 'check', 'status' => 'approved'];
		if ( !empty($data['close_user_id']) )
		{
			$employee = usam_get_employees(['meta_key' => 'evotor_id', 'meta_value' => sanitize_text_field($data['close_user_id']), 'number' => 1]);
			if ( $employee )
				$document['manager_id'] = $employee['id'];
		}		
		$document_id = usam_insert_document( $document );
		if ( $document_id )
		{				
			$shift_id = !empty($data['session_number'])?sanitize_text_field($data['session_number']):'';
			usam_update_document_metadata($document_id, 'shift_id', $shift_id );			
			usam_update_document_metadata($document_id, 'code_evotor', sanitize_text_field($data['id']) );
							
			$store_id = !empty($data['store_id'])?sanitize_text_field($data['store_id']):$this->option['store_id'];
			$device_id = !empty($data['device_id'])?sanitize_text_field($data['device_id']):'';
			$products = [];
			$product_ids = [];
			$totalprice = 0;			
			foreach ( $data['body']['positions'] as $item )
			{				
				$product_id = usam_get_product_id_by_meta( 'code_evotor', $item['product_id'] );	
				if ( $product_id )
				{
					if ( !empty($item['doc_discounts']) )
						$old_price = $item['price']+$item['doc_discounts']['discount_sum'];
					else
						$old_price = 0;
					$unit = usam_get_unit_measure($item['measure_name'], 'short');
					$unit_measure = isset($unit['code']) ? $unit['code'] : '';
					$products[] = ['product_id' => $product_id, 'quantity' => $item['quantity'], 'price' => $item['price'], 'old_price' => $old_price, 'unit_measure' => $unit_measure];
					$product_ids[$product_id] = $item['quantity'];
					$totalprice += $item['sum'];
				}
			}		
			if ( $products )
			{
				$new_document = new USAM_Document( $document_id );	
				$new_document->add_products( $products );			
			}		
			require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');	
			$statuses = usam_get_object_statuses(['fields' => 'internalname','type' => 'order', 'close' => 1]);
			$statuses[] = 'accepted_payment';
			$ids = usam_get_orders(['fields' => 'id', 'status__not_in' => $statuses, 'conditions' => [['key' => 'number_products', 'compare' => '=', 'value' => count($data['body']['positions'])],['key' => 'totalprice', 'compare' => '=', 'value' => $totalprice]], 'cache_order_products' => true]);
						
			$order_ids = [];
			foreach ( $ids as $id )
			{
				$products_order = usam_get_products_order( $id );
				$result = true;
				foreach ( $products_order as $product )
				{
					if ( !isset($product_ids[$product->product_id]) || $product_ids[$product->product_id] != $product->quantity )
					{
						$result = false;
						break;
					}
				}
				if ( $result )
					$order_ids[] = $id;
			}			
			if ( $order_ids && count($order_ids) == 1  )
			{
				$order = usam_get_order( $order_ids[0] );
				usam_update_order( $order_ids[0], ['status' => 'accepted_payment'] );
				if ( $order['company_id'] )
					usam_update_document($document_id, ['customer_id' => $order['company_id'], 'customer_type' => 'company']);
				elseif ( $order['contact_id'] )
					usam_update_document($document_id, ['customer_id' => $order['contact_id'], 'customer_type' => 'contact']);
			}
		}
	}
	
	public function set_storages() 
	{			
		$stores = $this->get_stores();
		$results = [];		
		if ( $stores )
		{
			$storages = usam_get_storages(['fields' => 'code=>data']);			
			foreach( $stores as $k => $store)
			{
				$ok = false;
				foreach( $storages as $code => $storage)
				{
					if ( $code == $store['id'] )
					{
						unset($storages[$code]);
						$ok = true;
					}
				}
				if ( !$ok )
				{
					foreach( $storages as $code => $storage)
					{
						if ( $storage->title == $store['name'] )
						{
							unset($storages[$code]);
							$ok = true;
						}
					}
				}
				if ( !$ok )
				{	
					$storage_id = usam_insert_storage(['title' => $store['name'], 'code' => $store['id'], 'active' => 1, 'shipping' => 0, 'issuing' => 0]);
					if ( !empty($store['address']) )
						usam_update_storage_metadata( $storage_id, 'address', $store['address']);
				}
				$results[$store['id']] = $store['name'].' '.(!empty($store['address'])?$store['address']:'');
				unset($stores[$k]);
			}
		}
		return $results;
	}
	
	public function get_form_buttons( ) 
	{
		return '<input type="submit" name="all_delete" class="button" value="'.__( 'Удалить все товары' , 'usam').'">';
	}
			
	public function display_form() 
	{
		$stores = $this->set_storages();
		$catalog_schedule = usam_get_application_metadata( $this->id, 'catalog_schedule' );	
		$catalog_time = usam_get_application_metadata( $this->id, 'catalog_schedule_time' );
		if ( !$catalog_time )
			$catalog_time = '00:00';
		?>							
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_date'><?php esc_html_e( 'Место нахождение кассы', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_storage_dropdown($this->option['storage_id']) ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='messenger_secret_key'><?php esc_html_e( 'Токен', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['access_token'], ['name' => 'access_token', 'id' => 'messenger_secret_key']); ?>
				</div>
			</div>				
			<?php if ( $stores ){ ?>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_number'><?php esc_html_e( 'Идентификатор устройства в Облаке Эвотор', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select name='store_id' id='option_number'>
							<?php foreach( $stores as $id => $name) { ?>
								<option value='<?php echo $id ?>' <?php selected($this->option['store_id'], $id) ?>><?php echo $name; ?></option>							
							<?php } ?>
						</select>
					</div>
				</div>	
			<?php } ?>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_tax_type'><?php esc_html_e( 'Ставка налога', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name='tax_type' id='option_tax_type'>
						<option value='NO_VAT' <?php selected( $this->option['tax_type'], 'NO_VAT') ?>><?php esc_html_e('без НДС', 'usam'); ?></option>
						<option value='VAT_0' <?php selected( $this->option['tax_type'], 'VAT_0') ?>><?php esc_html_e('НДС по ставке 0%', 'usam'); ?></option>
						<option value='VAT_10' <?php selected( $this->option['tax_type'], 'VAT_10') ?>><?php esc_html_e('НДС по ставке 10%', 'usam'); ?></option>
						<option value='VAT_18' <?php selected( $this->option['tax_type'], 'VAT_18') ?>><?php esc_html_e('НДС чека по ставке 18%', 'usam'); ?></option>						
						<option value='VAT_18_118' <?php selected( $this->option['tax_type'], 'VAT_18_118') ?>><?php esc_html_e('НДС чека по расчетной ставке 18/118', 'usam'); ?></option>
						<option value='VAT_10_110' <?php selected( $this->option['tax_type'], 'VAT_10_110') ?>><?php esc_html_e('НДС чека по расчетной ставке 10/110', 'usam'); ?></option>
					</select>
				</div>
			</div>				
			<?php 
			if ( $this->id )
			{
				$methods = ['check' => __('Получить чек', 'usam'), 'employee' => __('Получить сотрудника', 'usam'), 'documents' => __('Документы', 'usam')];
				foreach( $methods as $method => $title ) 
				{ ?>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_code'><?php echo $title; ?>:</label></div>
					<div class ="edit_form__item_option"><span class="js-copy-clipboard"><?php echo get_rest_url(null,$this->namespace.'/'.$this->option['service_code'].'/'.$this->id.'/'.$method); ?></span></div>
				</div>	
				<?php 
				}
			} ?>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_store_id'><?php esc_html_e( 'Номер кассы', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="option_number" name="number" value="<?php echo $this->option['number']; ?>">
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_catalog_schedule'><?php esc_html_e( 'Автоматический обмен каталогом каждые' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<select id='option_catalog_schedule' name='metadata[catalog_schedule]'>						
						<option value=''><?php esc_html_e('Отключено' , 'usam'); ?></option>
						<?php
						foreach ( wp_get_schedules() as $cron => $schedule ) 
						{										
							?><option <?php selected( $catalog_schedule, $cron ); ?> value='<?php echo $cron; ?>'><?php echo $schedule['display']; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_catalog_time'><?php esc_html_e( 'Время обмена каталогом' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">
					<input type="text" id = "option_catalog_time" name="metadata[catalog_schedule_time]" value="<?php echo $catalog_time; ?>"/>
				</div>
			</div>			
		</div>	
		<?php
	}
	
	public function save_form( ) 
	{
		$metas = [];			
		$metas['store_id'] = isset($_POST['store_id'])?sanitize_text_field($_POST['store_id']):'';
		if ( !$metas['store_id'] )
		{
			$stores = $this->get_stores();
			if ( !empty($stores) )
				$metas['store_id'] = $stores[0]['id'];			
		}
		$metas['tax_type'] = isset($_POST['tax_type'])?sanitize_text_field($_POST['tax_type']):'';
		$metas['storage_id'] = isset($_POST['storage'])?sanitize_text_field($_POST['storage']):0;
		$metas['number'] = isset($_POST['number'])?sanitize_text_field($_POST['number']):0;	
		foreach( $metas as $meta_key => $meta_value)
		{			
			usam_update_application_metadata($this->id, $meta_key, $meta_value);
		}
		$this->remove_hook( 'catalog' );	
		$this->remove_hook( 'documents' );	
		if ( $this->is_token() )
		{
			if ( $this->option['active'] )
			{	
				$this->add_hook('catalog');
				$this->add_hook_ten_minutes('documents');
			}
			if( !empty($_POST['all_delete']) )
			{
				$products = $this->get_products();
				if( !empty($products['items']) )
				{
					$i = usam_get_total_products();					
					$i = count($products['items'])>$i ? count($products['items']) : $i;
					usam_create_system_process( __("Удаление товаров из кассы &laquo;Эватор&raquo;", "usam" ), [], [$this, 'delete_all_products'], $i, 'evotor_application_delete_all_products-'.$this->id );				
				}
			}
		}
	}	
}

function usam_evotor_update_catalog( $id )
{ 
	if ( is_numeric($id) )
	{			
		$i = usam_get_total_products();		
		usam_create_system_process( __("Выгрузка каталога в &laquo;Эвотор&raquo;", "usam" ), $id, 'usam_evotor_start_update_catalog', $i, 'integration_service_catalog-'.$id );		
	}
}

function usam_evotor_start_update_catalog( $id, $number, $event )
{		
	$class = usam_get_class_application( $id );
	if ( $class )
		$done = $class->update_products(['paged' => $event['launch_number']]);
	else
		$done = 0;
	return ['done' => $done];		
}


function usam_evotor_loading_documents( $id )
{ 
	if ( is_numeric($id) )
		usam_create_system_process( __("Загрузка документов из &laquo;Эвотор&raquo;", "usam" ), $id, 'usam_evotor_start_loading_documents', 1, 'integration_service_documents-'.$id );	
}

function usam_evotor_start_loading_documents( $id, $number, $event )
{		
	$class = usam_get_class_application( $id );
	if ( $class )
		$done = $class->loading_documents(['paged' => $event['launch_number']]);
	else
		$done = 0;
	return ['done' => $done];		
}