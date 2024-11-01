<?php
/**
 	Name: Ozon
	Description: Выгрузка товаров
	Group: storage
	Icon: ozon
 */
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_ozon extends USAM_Application
{	
	protected $API_URL = "https://api-seller.ozon.ru";
		
	public function get_categories( $query_vars = [] )
	{ 
		$args = $this->get_args( 'POST', $query_vars );
		$results = $this->send_request( "v1/description-category/tree", $args );			 
		if( isset($results['result']) )
			return $this->format_categories( $results['result'] );
		return [];
	}
	
	public function format_categories( $categories )
	{ 
		$results = [];
		foreach( $categories as $category )	
		{
			$children = [];
			if( !empty($category['children']) )
				$children = $this->format_categories( $category['children'] );
			if( isset($category['description_category_id']) )
				$results[] = ['id' => $category['description_category_id'], 'name' => $category['category_name'], 'children' => $children];
			else
				$results[] = ['id' => $category['type_id'], 'name' => $category['type_name'], 'children' => $children];			
		}		
		return $results;
	}
	
	public function update_products( $query_vars = [] )
	{ 
		$query_vars['posts_per_page'] = 20;
		$query_vars['orderby'] = 'ID';
		$query_vars['order'] = 'ASC';
		if ( !isset($query_vars['post_status']) )
			$query_vars['post_status'] = 'any';
		
		$products = usam_get_products( $query_vars );
		$category_ids = [];
		foreach( $products as $key => $product )	
			$category_ids[] = $this->get_category_ozon( $product->ID );	
		
		$args = $this->get_args( 'POST', ['attribute_type' => 'ALL', 'category_id' => $category_ids, 'language' => 'RU']);
		unset($category_ids);
		$ozon_attributes = $this->send_request( "v3/category/attribute", $args );		
		$items = [];
		foreach( $products as $key => $product )	
			$items[] = $this->get_ozon_product( $product, $ozon_attributes );
		$result = false;
		if ( $items )
		{			
			$args = $this->get_args( 'POST', ['items' => $items] );
			$result = $this->send_request( "v2/product/import", $args );	
			if ( !empty($result['result']['task_id']) )	
				wp_schedule_single_event( time() + 60, 'usam_ozon_update_ozon_id', [ $result['result']['task_id'] ] ); 	
		} 	
		return $result;
	}			
	
	public function update_ozon_id( $task_id )
	{		
		$args = $this->get_args( 'POST', ['task_id' => $task_id] );
		$result = $this->send_request( "v1/product/import/info", $args );
		if ( !empty($result['result']) )
		{		
			foreach( $result['result']['items'] as $k => $item )
				usam_update_product_meta( $item['offer_id'], 'ozon_id', $item['product_id'] );
		}
		else
			wp_schedule_single_event( time() + 60, 'usam_ozon_update_ozon_id', [ $task_id ] ); 		
	}
	
	//images, name, offer_id, price, vat	
	private function get_ozon_product( $product, $ozon_attributes = [] )
	{ 
		if ( is_numeric($product) )
			$product = get_post( $product );	
				
		$sku = usam_get_product_meta( $product->ID, 'sku' );						
		$barcode = usam_get_product_meta($product->ID, 'barcode');
		$price = usam_get_product_price( $product->ID, $this->option['type_price'] );
		$old_price = usam_get_product_old_price( $product->ID, $this->option['type_price'] );
			
		$insert = ['name' => (string)$product->post_title, 'offer_id' => (string)$product->ID, 'price' => (string)$price, 'old_price' => (string)$old_price, 'barcode' => (string)$barcode, 'description' => (string)$product->post_excerpt, 'attributes' => [], 'stock' => (int)usam_product_remaining_stock( $product->ID )];		
		$insert['vat'] = '0';//Ставка НДС для товара.						
		$insert['currency_code'] = usam_get_currency_price_by_code( $this->option['type_price'] );			
		$urls = usam_get_product_images_urls( $product->ID );
		if ( $urls )
		{
			foreach ($urls as $url)
				$insert['images'][] = $url;
		}	
		$category_ozon_id = $this->get_category_ozon( $product->ID );
		$insert['category_id'] = $category_ozon_id;		
		if ( $ozon_attributes )
		{
			$product_attributes = usam_get_product_attributes_display( $product->ID, ['show_all' => true] );
			foreach ($ozon_attributes['result'] as $ozon_attrs)
			{				
				if ( $category_ozon_id == $ozon_attrs['category_id'] )
				{					
					foreach ($ozon_attrs['attributes'] as $ozon_attribute)
					{
						foreach( $product_attributes as $attribute )
						{							
							if ( $attribute['parent'] && $attribute['name'] == $ozon_attribute['name'] )
							{
								$values = [];
								foreach( $attribute['value'] as $value )
									$values[] = ['dictionary_value_id' => 0, 'value' => $value];
								$insert['attributes'][] = ['complex_id' => 0, 'id' => $ozon_attribute['id'], 'values' => $values];
							}
						}
					}
				}
			}
		}		
		$volume = (int)usam_get_product_volume( $product->ID ); 
		if ( $volume > 0 )
			$insert['volume'] = $volume;
		$weight = (int)usam_get_product_weight( $product->ID ); 
		if ( $weight > 0 )
		{
			$insert['weight'] = $weight;
			$insert['weight_unit'] = get_option( 'usam_weight_unit', 'kg' );
		}				
		$width = usam_get_product_meta( $product->ID, 'width' ); 
		if ( $width > 0 )		
			$insert['width'] = $width;
		$height = usam_get_product_meta( $product->ID, 'height' ); 
		if ( $height > 0 )
			$insert['height'] = $height;	
		$length = usam_get_product_meta( $product->ID, 'length' ); 
		if ( $length > 0 )
			$insert['depth'] = $length;
		$insert['dimension_unit'] = get_option( 'usam_dimension_unit', 'mm' );		
		return $insert;
	}
	
	public function update_stock( $query_vars = [] )
	{ 
		$query_vars = [] ;
		$query_vars['posts_per_page'] = 20;
		if ( !isset($query_vars['post_status']) )
			$query_vars['post_status'] = 'any';	
		$query_vars['fields'] = 'ids';
		$query_vars['productmeta_query'] = [['key' => 'ozon_id', 'compare' => 'EXISTS']];	
		$query_vars['stocks_cache'] = true;
		$query_vars['prices_cache'] = false;		
		
		$products = usam_get_products( $query_vars );
		
		$items = [];
		foreach( $products as $key => $product_id )	
			$items[] = ['offer_id' => (string)$product_id, 'stock' => (int)usam_product_remaining_stock( $product_id ) ];
		$result = false;
		if ( $items )
		{
			$args = $this->get_args( 'POST', ['stocks' => $items] );
			$result = $this->send_request( "v1/product/import/stocks", $args );	
		} 	
		return count($items);
	}	
	
	public function update_prices( $query_vars = [] )
	{ 
		$query_vars['posts_per_page'] = 20;
		if ( !isset($query_vars['post_status']) )
			$query_vars['post_status'] = 'any';
		$query_vars['fields'] = 'ids';
		$query_vars['productmeta_query'] = [['key' => 'ozon_id', 'compare' => 'EXISTS']];
		$query_vars['prices_cache'] = true;		
		$query_vars['stocks_cache'] = false;
		
		$currency = usam_get_currency_price_by_code( $this->option['type_price'] );
		
		$products = usam_get_products( $query_vars );		
		$items = [];
		foreach( $products as $key => $product_id )	
		{
			$price = usam_get_product_price( $product_id, $this->option['type_price'] );
			$old_price = usam_get_product_old_price( $product_id, $this->option['type_price'] );
			$items[] = ['offer_id' => (string)$product_id, 'auto_action_enabled' => 'UNKNOWN', 'currency_code' => $currency, 'min_price' => 0, 'old_price' => $old_price, 'price' => $price];			
		}
		$result = false;
		if ( $items )
		{			
			$args = $this->get_args( 'POST', ['items' => $items] );
			$result = $this->send_request( "v1/product/import/prices", $args );	
		} 	
		return count($items);
	}
	
	private function get_category_ozon( $product_id )
	{
		$category_ozon_id = (int)usam_get_product_meta( $product_id, 'category_ozon' ); 
		if ( $category_ozon_id > 0 )		
			$category_id = $category_ozon_id;	
		else
		{
			$terms = get_the_terms( $product_id, 'usam-category');				
			$product_term_ids = [];
			foreach( $terms as $term )
			{
				$ozon_category = (int)usam_get_term_metadata($term->term_id, 'ozon_category');
				if ( $ozon_category )
				{
					$category_id = $ozon_category;
					break;
				}
				else
				{					
					$ancestors = usam_get_ancestors( $term->term_id, 'usam-category' );
					foreach( $ancestors as $term_id ) 
					{
						$ozon_category = (int)usam_get_term_metadata($term_id, 'ozon_category');
						if ( !$ozon_category )
						{
							$category_id = $ozon_category;
							break;
						}
					}			
				}			
			}			
		}	
		return $category_id;
	}
	
	protected function get_orders( $query_vars = [] )
	{				
		$query_vars = array_merge(['limit' => 1000, "with" => ["analytics_data" => true, "financial_data" => true, "translit" => true]], $query_vars );
		$args = $this->get_args( 'POST', $query_vars );
		$result = $this->send_request( "v3/posting/fbs/list", $args );
		if( isset($result['result']) )
			return $result['result'];
		return false;
	}
	
	protected function get_order( $ozon_number )
	{				
		$query_vars = ['posting_number' => $ozon_number];
		$args = $this->get_args( 'POST', $query_vars );
		$result = $this->send_request( "v3/posting/fbs/get", $args );
		if( isset($result['result']) )
			return $result['result'];
		return false;
	}	
	
	protected function get_args( $method = 'GET', $params = [] )
	{ 
		$headers["Content-type"] = 'application/json';
		$headers["Api-Key"] = $this->token;
		$headers["Client-Id"] = $this->login;
		$args = [
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers
		];	
		if ( $params )
		{
			if ( $method == 'GET' )
				$args['body'] = $params;
			else
				$args['body'] = json_encode($params);
		}
		return $args;
	}
	
	protected function get_default_option( ) 
	{
		return ['login' => '', 'token' => '', 'type_price' => '', 'company_id' => get_option( 'usam_shop_company' )];
	}
		
	public function display_form() 
	{	
		$prices_schedule = usam_get_application_metadata( $this->id, 'update_prices_schedule' );	
		$prices_time = usam_get_application_metadata( $this->id, 'update_prices_schedule_time' );
		if ( !$prices_time )
			$prices_time = '00:00';
		$stock_schedule = usam_get_application_metadata( $this->id, 'stock_schedule' );	
		$stock_time = usam_get_application_metadata( $this->id, 'stock_schedule_time' );
		if ( !$stock_time )
			$stock_time = '00:00';
		?>			
		<div class="edit_form" > 
			<label class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Client ID', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_login' name="login" value="<?php echo $this->option['login']; ?>">
				</div>
			</label>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'API key', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php usam_get_password_input( $this->option['access_token'], ['name' => 'access_token', 'id' => 'option_token']); ?></div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Ваша фирма', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<?php usam_select_bank_accounts( $this->option['company_id'], ['name' => "company_id"] ); ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Тип цены', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo usam_get_select_prices( $this->option['type_price'] ); ?></div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Автоматический обновление цен каждые' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<select name='prices_schedule'>						
						<option value=''><?php esc_html_e('Отключено' , 'usam'); ?></option>
						<?php
						foreach ( wp_get_schedules() as $cron => $schedule ) 
						{										
							?><option <?php selected( $prices_schedule, $cron ); ?> value='<?php echo $cron; ?>'><?php echo $schedule['display']; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_prices_time'><?php esc_html_e( 'Начиная с' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">
					<input type="text" id = "option_prices_time" name="prices_time" value="<?php echo $prices_time; ?>"/>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_catalog_schedule'><?php esc_html_e( 'Автоматическое обновление остатков каждые' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<select name='stock_schedule'>						
						<option value=''><?php esc_html_e('Отключено' , 'usam'); ?></option>
						<?php
						foreach ( wp_get_schedules() as $cron => $schedule ) 
						{										
							?><option <?php selected( $stock_schedule, $cron ); ?> value='<?php echo $cron; ?>'><?php echo $schedule['display']; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_stock_time'><?php esc_html_e( 'Начиная с' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">
					<input type="text" id = "option_stock_time" name="stock_time" value="<?php echo $stock_time; ?>"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Ссылка для уведомлений', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<span class="js-copy-clipboard"><?php echo rest_url($this->namespace.'/'.$this->option['service_code'].'/'.$this->option['id'].'/notifications'); ?></span>
				</div>
			</div>			
		</div>
		<?php		
	}	
		
	public function save_form( ) 
	{ 
		$metas = [];	
		$metas['type_price'] = isset($_POST['type_price'])?sanitize_text_field($_POST['type_price']):'';
		$metas['company_id'] = isset($_POST['company_id'])?sanitize_text_field($_POST['company_id']):'';		
		$metas['update_prices_schedule'] = isset($_POST['prices_schedule'])?sanitize_text_field($_POST['prices_schedule']):'';
		$metas['update_prices_schedule_time'] = !empty($_POST['prices_time'])?sanitize_text_field($_POST['prices_time']):'00:00';
		$metas['update_stock_schedule'] = isset($_POST['stock_schedule'])?sanitize_text_field($_POST['stock_schedule']):'';
		$metas['update_stock_schedule_time'] = !empty($_POST['stock_time'])?sanitize_text_field($_POST['stock_time']):'00:00';		
		foreach( $metas as $meta_key => $meta_value)
		{	
			usam_update_application_metadata($this->id, $meta_key, $meta_value);
		}
		$this->remove_hook( 'update_stock' );			
		$this->remove_hook( 'update_prices' );	
		if ( $this->is_token() )
		{
			if ( $this->option['active'] )
			{
				$this->add_hook('update_stock');
				$this->add_hook('update_prices');
			}
		}
	}
	
	public function save_category_form( $term_id, $tt_id ) 
	{ 
		if ( isset($_POST['ozon_category'] ) )
			usam_update_term_metadata($term_id, 'ozon_category', absint($_POST['ozon_category']));
	}	
	
	function edit_category_forms( $tag, $taxonomy ) 
	{				
		add_action( 'admin_footer',  function() use ($tag){			
			?>
			<script>			
				var application_id = <?php echo $this->id; ?>;			
				var ozon_category = <?php echo (int)usam_get_term_metadata($tag->term_id, 'ozon_category'); ?>;					
			</script>	
			<?php			
		});			
		?>		
		<tr id="ozon_categories" class="form-field">
			<th scope="row" valign="top"><?php esc_html_e( 'Категория Ozon', 'usam'); ?><input type='hidden' name="ozon_category" v-model="category"></th>
			<td>
				<div><ozon-categories @change="category=$event" :lists="categories" :selected="category"/></div>									
			</td>			
		</tr>			
		<?php
	}
	
	function register_bulk_actions( $bulk_actions ) 
	{				
		$bulk_actions['ozon_update_products'] = __('Выгрузить в OZON', 'usam');
		return $bulk_actions;
	}	

	function bulk_action_handler( $redirect_to, $action, $post_ids )
	{
		switch ( $action ) 
		{						
			case 'ozon_update_products':				
				if ( $post_ids )
					$this->update_products(['post__in' => $post_ids]);
			break;			
		}		
		return $redirect_to;
	}

	function update_stock_from_ozon($id, $number, $event) 
	{		
		$done = $this->update_stock(['paged' => $event['launch_number']]);
		return ['done' => $done];
	}
	
	function update_prices_from_ozon($id, $number, $event) 
	{		
		$done = $this->update_prices(['paged' => $event['launch_number']]);
		return ['done' => $done];
	}
	
	private function get_order_data( $document )
	{		
		$data = ['date_insert' => date("Y-m-d H:i:s", strtotime($document['in_process_at'])), 'bank_account_id' => $this->option['company_id'], 'type_price' => $this->option['type_price']];
		if( isset($document['substatus']) )
			$data['status'] = usam_get_status_code_by_meta( 'ozon_id', $document['substatus'], 'order' );
		$metas = ['ozon_id' => $document['posting_number'], 'warehouse_id' => $document['warehouse_id']]; //'seller_id' => $parameters['seller_id']
		$products = [];
		foreach( $document['products'] as $product )
		{
			$product_id = usam_get_product_id_by_meta( 'ozon_id', $product['offer_id'] );
			$price = isset($product['price']) ? usam_string_to_float($product['price']) : 0;
			$products[] = ['product_id' => $product_id, 'quantity' => usam_string_to_float($product['quantity']), 'price' => $price];				
		}
		return ['order' => $data, 'products' => $products, 'metas' => $metas]; 
	}
	
	function update_orders_from_ozon($id, $number, $event) 
	{		
		$results = $this->get_orders();		
		$done = 0;
		if( !$results )
		{
			$codes = [];
			foreach( $parameters['postings'] as $v )
				$codes[] = $v['posting_number'];
			usam_get_order_ids_by_code( $codes, 'ozon_id' );
			unset($codes);
			usam_update_object_count_status( false );
			foreach( $parameters['postings'] as $document )
			{
				$order_id = usam_get_order_id_by_meta('ozon_id', $document['posting_number']);
				if( !$order_id )
				{
					$result = $this->get_order_data( $document );
					extract( $result );					
					$order_id = usam_insert_order( $data, $products, $metas );	
					if( $order_id )
					{
						if( isset($document['delivery_method']) )
						{
							$document_id = usam_insert_shipped_document(['name' => $document['delivery_method']['name'], 'price' => 0, 'order_id' => $order_id], $products, ['document_id' => $order_id, 'document_type' => 'order']);
						}
					}
				}		
			}
			usam_update_object_count_status( true );
			if( $results['has_next'] )
				$done = 1;
		}
		return ['done' => $done];
	}
	
	function cron_update_stock() 
	{				
		$i = usam_get_total_products(['productmeta_query' => [['key' => 'ozon_id', 'compare' => 'EXISTS']]]);		
		usam_create_system_process( __("Обновление остатков в &laquo;Озон&raquo;", "usam" ), $this->id, [&$this, 'update_stock_from_ozon'], $i, 'ozon_application_update_stock-'.$this->id );		
	}
	
	function cron_update_prices() 
	{				
		$i = usam_get_total_products(['productmeta_query' => [['key' => 'ozon_id', 'compare' => 'EXISTS']]]);		
		usam_create_system_process( __("Обновление цен в &laquo;Озон&raquo;", "usam" ), $this->id, [&$this, 'update_prices_from_ozon'], $i, 'ozon_application_update_prices-'.$this->id );		
	}
	
	function cron_update_orders() 
	{						
		usam_create_system_process( __("Обновление заказов в &laquo;Озон&raquo;", "usam" ), $this->id, [&$this, 'update_orders_from_ozon'], 100, 'ozon_application_update_orders-'.$this->id );		
	}
	
	function filter_options_exporting_product_platforms( $platforms ) 
	{				
		$platforms[] = ['id' => 'ozon', 'name' => 'Ozon'];
 		return $platforms;	
	}
	
	protected function rest_api_type_ping( $parameters ) 
	{		
		return ["version" => USAM_VERSION, "name" => "universam", "time" => date("c")];
	}
	
	//Новое отправление
	protected function rest_api_type_new_posting( $parameters ) 
	{
		$result = $this->get_order_data( $parameters );
		extract( $result );	
		$order_id = usam_insert_order( $data, $products, $metas );	
		if( isset($parameters['delivery_method']) )
		{
			$document_id = usam_insert_shipped_document(['name' => $parameters['delivery_method']['name'], 'price' => 0, 'order_id' => $order_id], $products, ['document_id' => $order_id, 'document_type' => 'order']);
		}		
		return ["result" => true];
	}
	
	//Отмена отправления
	protected function rest_api_type_posting_cancelled( $parameters ) 
	{	
		$order_id = usam_get_order_id_by_meta('ozon_id', $parameters['posting_number']);
		if( $order_id )
			usam_update_order( $order_id, ["status" => 'canceled'], null, ["cancellation_reason" => $parameters['reason']] );		
		return ["result" => true];
	}
	
	//Изменение статуса отправления
	protected function rest_api_type_state_changed( $parameters ) 
	{	
		$order_id = usam_get_order_id_by_meta('ozon_id', $parameters['posting_number']);
		if( $order_id )
		{
			$status = usam_get_status_code_by_meta( 'ozon_id', $parameters['new_state'], 'order' );
			usam_update_order( $order_id, ["status" => $status]);		
		}
		return ["result" => true];
	}
	
	//Изменение даты отгрузки отправления
	protected function rest_api_type_cutoff_date_changed( $parameters ) 
	{	
		$order_id = usam_get_order_id_by_meta('ozon_id', $parameters['posting_number']);
		if( $order_id )
			usam_update_order_metadata($order_id, 'new_cutoff_date', date("Y-m-d H:i:s", strtotime($document['new_cutoff_date'])));	//дата и время отгрузки
		return ["result" => true];
	}
	
	//Изменение даты доставки отправления
	protected function rest_api_type_delivery_date_changed( $parameters ) 
	{	
		$order_id = usam_get_order_id_by_meta('ozon_id', $parameters['posting_number']);
		if( $order_id )
		{
			usam_update_order_metadata($order_id, 'new_delivery_date_begin', date("Y-m-d H:i:s", strtotime($document['new_delivery_date_begin'])));	//Новые дата и время начала доставки в формате UTC.
			usam_update_order_metadata($order_id, 'new_delivery_date_end', date("Y-m-d H:i:s", strtotime($document['new_delivery_date_end'])));	//Новые дата и время окончания доставки в формате UTC.
		}
		return ["result" => true];
	}
	
	//Новое сообщение в чате
	protected function rest_api_type_new_message( $parameters ) 
	{	
	
		return ["result" => true];
	}
	
	//Сообщение в чате изменено
	protected function rest_api_type_update_message( $parameters ) 
	{	
	
		return ["result" => true];
	}
	
	//Ваше сообщение прочитано
	protected function rest_api_type_message_read( $parameters ) 
	{	
	
		return ["result" => true];
	}
	
	//Чат закрыт
	protected function rest_api_type_chat_closed( $parameters ) 
	{	
	
		return ["result" => true];
	}
		
	public function register_routes( ) 
	{ 
		register_rest_route( $this->namespace, $this->route.'/categories', [
			[
				'methods'  => 'GET',
				'callback' => [$this, 'get_categories'],	
				'args' => [],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('manage_options') || current_user_can('edit_product');
				},
			],			
		]);	
		register_rest_route( $this->namespace, $this->route.'/notifications', [
			[
				'permission_callback' => false,
				'methods'  => 'POST,GET',
				'callback' => 'usam_application_rest_api',	
				'args' => array()
			]			
		]);	
	}	
		
	public function rest_api( $request ) 
	{				
		$ip = $_SERVER['REMOTE_ADDR'];
		if( usam_ip_in_range($ip, '195.34.21.0/24') || usam_ip_in_range($ip, '185.73.192.0/22') || usam_ip_in_range($ip, '91.223.93.0/24') || WP_DEBUG && usam_ip_in_range($ip, '127.0.0.1/24') )
		{
			$parameters = $request->get_json_params();
			if( isset($parameters['message_type']) )
			{
				$method = "rest_api_".strtolower($parameters['message_type']);
				if ( method_exists($this, $method) )
				{
					$result = $this->$method( $parameters );				
					exit( json_encode( $result ) );
				}
				return new WP_REST_Response( true, 200 );			
			}
		}
	} 
	
	public function order_status_settings_edit_form( $t, $data ) 
	{
		$statuses = [
			'posting_acceptance_in_progress' => 'идёт приёмка',
			'posting_transferring_to_delivery' => 'передаётся в доставку',
			'posting_in_carriage' => 'в перевозке',
			'posting_not_in_carriage' => 'не добавлен в перевозку',
			'posting_in_arbitration' => 'арбитраж',
			'posting_in_client_arbitration' => 'клиентский арбитраж доставки',
			'posting_on_way_to_city' => 'на пути в город',
			'posting_transferred_to_courier_service' => 'передаётся курьеру',
			'posting_in_courier_service' => 'курьер в пути',
			'posting_on_way_to_pickup_point' => 'на пути в пункт выдачи',
			'posting_in_pickup_point' => 'в пункте выдачи',
			'posting_conditionally_delivered' => 'условно доставлено',
			'posting_driver_pick_up' => 'у водителя',
			'posting_delivered' => 'доставлено',
			'posting_not_in_sort_center' => 'не принят на сортировочном центре'
		];
		?>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e( 'Соответствия статусам ozon' , 'usam'); ?>:</div>
			<div class ="edit_form__item_option">					
				<select name='ozon_id'>						
					<option value=''><?php esc_html_e('Не выбранно' , 'usam'); ?></option>
					<?php
					foreach( $statuses as $code => $title ) 
					{										
						?><option <?php selected( $code, $data['ozon_id'] ); ?> value='<?php echo $code; ?>'><?php echo $title; ?></option><?php
					}
					?>
				</select>
			</div>
		</div>
		<?php
	}
	
	public function order_status_edit_form_data( $data, $t ) 
	{
		$data['ozon_id'] = usam_get_object_status_metadata( $data['id'], 'ozon_id' );
		return $data;
	}	
	
	public function object_status_save( $t ) 
	{
		if( isset($_POST['ozon_id']) && $t->get('type') == 'order' )
			usam_update_object_status_metadata( $t->get('id'), 'ozon_id', sanitize_title($_POST['ozon_id']) );		
	}
	
	public function admin_init() 
	{
		if( current_user_can( 'setting_document' ) )			
			add_action( 'usam_object_status_save', [&$this, 'object_status_save']);
		
		add_action( 'usam_order_status_settings_edit_form',  [&$this, 'order_status_settings_edit_form'], 10, 2 );	
		add_filter( 'usam_order_status_edit_form_data', [&$this, 'order_status_edit_form_data'], 10, 2 );
	}
	
	public function service_load( ) 
	{		
		add_action( 'usam_ozon_update_ozon_id',  [&$this, 'update_ozon_id'] );
		add_action('rest_api_init', [$this,'register_routes']);	
		add_action( 'usam-category_edit_form_fields', [&$this, 'edit_category_forms'], 11, 2 );
		add_action( 'edited_usam-category', [&$this, 'save_category_form'], 10 , 2 );
		add_filter( 'bulk_actions-edit-usam-product', [&$this, 'register_bulk_actions'] );	
		add_action( 'handle_bulk_actions-edit-usam-product', [&$this, 'bulk_action_handler'], 10, 3);
				
		add_action('usam_application_update_stock_schedule_'.$this->option['service_code'],  [$this, 'cron_update_stock']);
		add_action('usam_application_update_prices_schedule_'.$this->option['service_code'],  [$this, 'cron_update_prices']);
		
		add_action('usam_filter_options_exporting_product_platforms',  [$this, 'filter_options_exporting_product_platforms']);			
		add_action( 'admin_init', [&$this, 'admin_init']);	
	}
}