<?php
/**
 	Name: Яндекс Маркет
	Description: Выгрузка товаров
	Group: storage
	Icon: yandexmarket
 */
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_yandexmarket extends USAM_Application
{	
	protected $API_URL = "https://api.partner.market.yandex.ru";
	private $posts_per_page = 200;
		
	public function get_categories( $query_vars = [] )
	{ 
		$args = $this->get_args( 'POST', $query_vars );
		return $this->send_request( "categories/tree", $args );			
	}
	
	public function update_products( $query_vars = [] )
	{ 
		$query_vars['posts_per_page'] = 500;
		$query_vars['orderby'] = 'ID';
		$query_vars['order'] = 'ASC';
		if ( !isset($query_vars['post_status']) )
			$query_vars['post_status'] = 'any';
		
		$products = usam_get_products( $query_vars );	
		$items = [];
		foreach( $products as $key => $product )	
			$items[] = $this->get_yandex_product( $product );
		$result = false;
		if ( $items )
		{			
			$args = $this->get_args( 'POST', ['offerMappings' => $items] );
			$result = $this->send_request( "businesses/{$this->option['business_id']}/offer-mappings/update", $args );	
			if ( !empty($result['error']) && $result['error'] )
			{
				foreach( $products as $key => $product )
					usam_update_product_meta( $product->ID, 'yandex_id', 0 );
				$result = true;
			}
		} 	
		return $result;
	}	
	
	private function get_yandex_product( $product )
	{ 
		if ( is_numeric($product) )
			$product = get_post( $product );	
				
		$urls = usam_get_product_images_urls( $product->ID );	
		$insert = ['offerId' => (string)$product->ID, 'name' => $product->post_title, 'pictures' => $urls, 'description' => $product->post_excerpt, 'downloadable' => usam_get_product_type_sold($product->ID) == 'electronic_product'];
								
		$barcode = usam_get_product_meta($product->ID, 'barcode');
		$brand = usam_product_brand( $product_id );
		if( !empty($brand) )
			$insert['vendor'] = $brand->name;
		if( $barcode )
			$insert['barcodes'] = [$barcode];

		$insert['weightDimensions'] = [
			'length' => usam_get_product_property( $product->ID, 'length' ), 
			'width' => usam_get_product_property( $product->ID, 'width' ),
			'height' => usam_get_product_property( $product->ID, 'height' ),
			'weight' => usam_get_product_weight( $product->ID )		
		];	
		return ['offer' => $insert, 'mapping' => ['marketSku' => (int)usam_get_product_meta($product->ID, 'yandex_id')]];
	}
	
	public function update_stock( $query_vars = [] )
	{ 
		$query_vars = [] ;
		$query_vars['posts_per_page'] = $this->posts_per_page;
		if ( !isset($query_vars['post_status']) )
			$query_vars['post_status'] = 'any';	
		$query_vars['productmeta_query'] = [['key' => 'yandex_id', 'compare' => 'EXISTS']];	
		$query_vars['stocks_cache'] = true;
		$query_vars['prices_cache'] = false;			
		$products = usam_get_products( $query_vars );	
		$result = false;
		if ( $products )
		{
			$archive = [];
			$unarchive = [];
			foreach( $products as $product )
			{
				if( $product->post_status == 'publish' && usam_product_remaining_stock( $product_id ) )
					$archive[] = $product_id;
				else
					$unarchive[] = $product_id;
			}
			$this->in_archive( $archive );
			$this->in_unarchive( $unarchive );
		} 	
		return count($items);
	}	

	public function in_archive( $ids )
	{	
		if( !$ids )
			return false;
		$args = $this->get_args( 'POST', ['offerIds' => $ids] );
		return $this->send_request( "businesses/{$this->option['business_id']}/offer-mappings/archive", $args );
	}
	
	public function in_unarchive( $ids )
	{	
		if( !$ids )
			return false;
		$args = $this->get_args( 'POST', ['offerIds' => $ids] );
		return $this->send_request( "businesses/{$this->option['business_id']}/offer-mappings/unarchive", $args );
	}
	
	public function update_prices( $query_vars = [] )
	{ 
		$query_vars['posts_per_page'] = $this->posts_per_page;
		if ( !isset($query_vars['post_status']) )
			$query_vars['post_status'] = 'any';
		$query_vars['fields'] = 'ids';
		$query_vars['productmeta_query'] = [['key' => 'yandex_id', 'compare' => 'EXISTS']];
		$query_vars['prices_cache'] = true;		
		$query_vars['stocks_cache'] = false;
		
		$currency = strtoupper(usam_get_currency_price_by_code( $this->option['type_price'] ));
		
		$products = usam_get_products( $query_vars );		
		$items = [];
		foreach( $products as $key => $product_id )	
		{
			$price = usam_get_product_price( $product_id, $this->option['type_price'] );
			$items[] = ['offerId' => (string)$product_id, 'price' => ['value' => $price, 'currencyId' => $currency]];			
		}
		$result = false;
		if ( $items )
		{			
			$args = $this->get_args( 'POST', ['offers' => $items] );
			$result = $this->send_request( "businesses/{$this->option['business_id']}/offer-prices/updates", $args );	
		} 	
		return count($items);
	}
	
	public function delete_products( $query_vars = [] )
	{ 
		$query_vars = [] ;
		$query_vars['posts_per_page'] = $this->posts_per_page;
		if ( !isset($query_vars['post_status']) )
			$query_vars['post_status'] = 'any';	
		$query_vars['fields'] = 'ids';
		$query_vars['productmeta_query'] = [['key' => 'yandex_id', 'compare' => 'EXISTS']];
		$query_vars['stocks_cache'] = false;
		$query_vars['prices_cache'] = false;			
		$products = usam_get_products( $query_vars );	
		$result = false;
		if ( $products )
		{
			$args = $this->get_args( 'POST', ['offerIds' => $products] );
			$result = $this->send_request( "businesses/{$this->option['business_id']}/offer-mappings/delete", $args );	
			if( isset($result['result']) )
			{
				foreach( $products as $key => $product_id )	
					if ( empty($result['notDeletedOfferIds']) || !in_array($product_id, $result['notDeletedOfferIds']) )
						usam_delete_product_meta($product->ID, 'yandex_id');
			}
		} 	
		return count($items);
	}
	
	public function get_orders( $query_vars = [] )
	{ 
		$args = $this->get_args( 'POST', $query_vars );
		return $this->send_request( "/campaigns/{$this->option['campaign_id']}/orders", $args );			
	}
		
	private function get_order_data( $document )
	{		
		$data = ['date_insert' => date("Y-m-d H:i:s", strtotime($document['creationDate'])), 'bank_account_id' => $this->option['company_id'], 'type_price' => $this->option['type_price']];
		$data['status'] = usam_get_status_code_by_meta( 'yandex_id', $document['status'], 'order' );
		$metas = ['yandex_id' => $document['id'], 'note' => $document['notes']];
		if( isset($document['warehouseId']) )
			$metas['warehouse_id'] = $document['warehouseId'];
		$products = [];
		foreach( $document['items'] as $product )
		{
			$product_id = usam_get_product_id_by_meta( 'yandex_id', $product['offerId'] );			
			$products[] = ['product_id' => $product_id, 'name' => $product['offerName'], 'quantity' => usam_string_to_float($product['count']), 'price' => usam_string_to_float($product['price']), 'oldprice' => usam_string_to_float($product['buyerPriceBeforeDiscount'])];				
		}
		return ['order' => $data, 'products' => $products, 'metas' => $metas]; 
	}
	
	function update_orders_from_yandex($id, $number, $event) 
	{		
		$results = $this->get_orders();		
		$done = 0;
		if( !$results )
		{
			$codes = [];
			foreach( $parameters['postings'] as $v )
				$codes[] = $v['posting_number'];
			usam_get_order_ids_by_code( $codes, 'yandex_id' );
			unset($codes);
			usam_update_object_count_status( false );
			foreach( $parameters['postings'] as $document )
			{
				$order_id = usam_get_order_id_by_meta('yandex_id', $document['posting_number']);
				if( !$order_id )
				{
					$result = $this->get_order_data( $document['order'] );
					extract( $result );					
					$order_id = usam_insert_order( $data, $products, $metas );	
					if( $order_id )
					{
						if( isset($document['delivery']) )
						{
							$document_id = usam_insert_shipped_document(['name' => $document['delivery']['serviceName'], 'price' => $document['delivery']['price'], 'order_id' => $order_id], $products, ['document_id' => $order_id, 'document_type' => 'order']);
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
	
			
	protected function get_args( $method = 'GET', $params = [] )
	{ 
		$yandex = new USAM_Yandex();
		$token = $yandex->get_token();
		$headers["Content-type"] = 'application/json';
		$headers["Authorization"] = "Bearer {$token}";
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
	
	//Передает магазину заказ и просит подтвердить его принятие.
	protected function rest_api_order_accept( $parameters ) 
	{	
		$result = $this->get_order_data( $parameters['order'] );
		extract( $result );	
		$order_id = usam_insert_order( $data, $products, $metas );	
		if( isset($document['delivery']) )
		{
			$document_id = usam_insert_shipped_document(['name' => $document['delivery']['serviceName'], 'price' => $document['delivery']['price'], 'order_id' => $order_id], $products, ['document_id' => $order_id, 'document_type' => 'order']);
		}		
		return true;
	}
	
	//Сообщает магазину, что статус заказа изменился.
	protected function rest_api_order_status( $parameters ) 
	{		
		$order_id = usam_get_order_id_by_meta('yandex_id', $parameters['order']['id']);
		if( $order_id )
		{
			$status = usam_get_status_code_by_meta( 'yandex_id', $parameters['order']['status'], 'order' );
			usam_update_order( $order_id, ["status" => $status]);		
		}
		return true;
	}
	
	//Уведомляет магазин о создании заявки на отмену заказа.
	protected function rest_api_order_cancellation_notify( $parameters ) 
	{	
		$order_id = usam_get_order_id_by_meta('yandex_id', $parameters['order']['id']);
		if( $order_id )
			usam_update_order( $order_id, ["status" => 'canceled'] );
		return ["result" => true];
	}
	
	//Запрашивает у магазина информацию о товарах в корзине.
	protected function rest_api_cart( $parameters ) 
	{	
	
		return ["result" => true];
	}
	
	//Запрашивает у магазина актуальную информацию по остаткам товаров.
	protected function rest_api_stocks( $parameters ) 
	{	
	
		return ["result" => true];
	}
	
	public function rest_api( $request ) 
	{			
		$headers = $request->get_headers();	
		if ( isset($headers['authorization']) && $headers['authorization'] == $this->option['access_token'] || true)
		{				
			$parameters = $request->get_json_params();					
			$method = "rest_api_".str_replace("/", "_", $request->get_param('method'));
			if ( method_exists($this, $method) )
				$this->$method( $parameters );	
			return new WP_REST_Response( true, 200 );			
		}
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
		register_rest_route( $this->namespace, $this->route.'/notifications/(?P<method>\S+)', [['permission_callback' => false, 'methods'  => 'POST,GET', 'callback' => 'usam_application_rest_api', 'args' => []]]);
	}
	
	protected function get_default_option( ) 
	{
		return ['login' => '', 'token' => '', 'type_price' => '', 'business_id' => '', 'campaign_id' => ''];
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
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Тип цены', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo usam_get_select_prices( $this->option['type_price'] ); ?></div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Идентификатор кабинета' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type="text" name="business_id" value="<?php echo $this->option['business_id']; ?>"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Идентификатор магазина в кабинете' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type="text" name="campaign_id" value="<?php echo $this->option['campaign_id']; ?>"/>
				</div>
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
		$metas['business_id'] = isset($_POST['business_id'])?sanitize_text_field($_POST['business_id']):'';		
		$metas['campaign_id'] = isset($_POST['campaign_id'])?sanitize_text_field($_POST['campaign_id']):'';			
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
	
	public function order_status_settings_edit_form( $t, $data ) 
	{
		$statuses = [
			'CANCELLED' => 'заказ отменен',
			'DELIVERED' => 'заказ получен покупателем',
			'DELIVERY' => 'заказ передан в службу доставки',
			'PICKUP' => 'заказ доставлен в пункт самовывоза',
			'PROCESSING' => 'заказ находится в обработке',
			'PENDING' => 'заказ ожидает обработки продавцом',
			'UNPAID' => 'заказ оформлен, но еще не оплачен (если выбрана оплата при оформлении)',
			'PLACING' => 'заказ оформляется, подготовка к резервированию',
			'RESERVED' => 'заказ pарезервирован, но недооформлен',
			'PARTIALLY_RETURNED' => 'частичный возврат',
			'RETURNED' => 'полный возврат',
			'UNKNOWN' => 'неизвестный статус',
		];
		?>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e( 'Соответствия статусам Яндекс Маркет' , 'usam'); ?>:</div>
			<div class ="edit_form__item_option">					
				<select name='yandex_id'>						
					<option value=''><?php esc_html_e('Не выбранно' , 'usam'); ?></option>
					<?php
					foreach( $statuses as $code => $title ) 
					{										
						?><option <?php selected( $code, $data['yandex_id'] ); ?> value='<?php echo $code; ?>'><?php echo $title; ?></option><?php
					}
					?>
				</select>
			</div>
		</div>
		<?php
	}
	
	public function order_status_edit_form_data( $data, $t ) 
	{
		$data['yandex_id'] = usam_get_object_status_metadata( $data['id'], 'yandex_id' );
		return $data;
	}	
	
	public function object_status_save( $t ) 
	{
		if( isset($_POST['yandex_id']) && $t->get('type') == 'order' )
			usam_update_object_status_metadata( $t->get('id'), 'yandex_id', sanitize_title($_POST['yandex_id']) );		
	}
	
	public function admin_init() 
	{
		if( current_user_can( 'setting_document' ) )			
			add_action( 'usam_object_status_save', [&$this, 'object_status_save']);
		
		add_action( 'usam_order_status_settings_edit_form',  [&$this, 'order_status_settings_edit_form'], 10, 2 );	
		add_filter( 'usam_order_status_edit_form_data', [&$this, 'order_status_edit_form_data'], 10, 2 );
	}
		
	public function save_category_form( $term_id, $tt_id ) 
	{ 
		
	}	
	
	function edit_category_forms( $tag, $taxonomy ) 
	{				
		
	}
	
	function register_bulk_actions( $bulk_actions ) 
	{				
		$bulk_actions['yandexmarket_update_products'] = __('Выгрузить в Яндекс Маркет', 'usam');
		return $bulk_actions;
	}	

	function bulk_action_handler( $redirect_to, $action, $post_ids )
	{
		switch ( $action ) 
		{						
			case 'yandexmarket_update_products':				
				if ( $post_ids )
					$this->update_products(['post__in' => $post_ids]);
			break;			
		}		
		return $redirect_to;
	}

	function update_stock_from_yandexmarket($id, $number, $event) 
	{		
		$done = $this->update_stock(['paged' => $event['launch_number']]);
		return ['done' => $done];
	}
	
	function update_prices_from_yandexmarket($id, $number, $event) 
	{		
		$done = $this->update_prices(['paged' => $event['launch_number']]);
		return ['done' => $done];
	}
	
	function cron_update_stock() 
	{				
		$i = usam_get_total_products(['productmeta_query' => [['key' => 'yandex_id', 'compare' => 'EXISTS']]]);		
		usam_create_system_process( __("Обновление остатков в Яндекс Маркет", "usam" ), $this->id, [&$this, 'update_stock_from_yandexmarket'], $i, 'yandexmarket_application_update_stock-'.$this->id );		
	}
	
	function cron_update_prices() 
	{				
		$i = usam_get_total_products(['productmeta_query' => [['key' => 'yandex_id', 'compare' => 'EXISTS']]]);		
		usam_create_system_process( __("Обновление цен в Яндекс Маркет", "usam" ), $this->id, [&$this, 'update_prices_from_yandexmarket'], $i, 'yandexmarket_application_update_prices-'.$this->id );		
	}
	
	function filter_options_exporting_product_platforms( $platforms ) 
	{				
		$platforms[] = ['id' => 'yandexmarket', 'name' => 'Яндекс Маркет'];
 		return $platforms;	
	}
	
	public function service_load( ) 
	{	
		add_action('rest_api_init', [$this,'register_routes']);	
		add_action( 'usam-category_edit_form_fields', [&$this, 'edit_category_forms'], 11, 2 );
		add_action( 'edited_usam-category', [&$this, 'save_category_form'], 10 , 2 );
		add_filter( 'bulk_actions-edit-usam-product', [&$this, 'register_bulk_actions'] );	
		add_action( 'handle_bulk_actions-edit-usam-product', [&$this, 'bulk_action_handler'], 10, 3);
				
		add_action('usam_application_update_stock_schedule_'.$this->option['service_code'],  [$this, 'cron_update_stock']);
		add_action('usam_application_update_prices_schedule_'.$this->option['service_code'],  [$this, 'cron_update_prices']);
		
		add_action('usam_filter_options_exporting_product_platforms',  [$this, 'filter_options_exporting_product_platforms']);		
	}
}