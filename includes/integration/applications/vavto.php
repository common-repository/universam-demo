<?php 
/*
	Name: API сайта v-avto.ru
	Description: Загружает товары с сайта v-avto.ru на ваш сайт.
	Price: paid
	Group: import_products
*/
require_once( USAM_FILE_PATH . '/includes/application.class.php' );	
class USAM_Application_vavto extends USAM_Application
{	
	protected $API_URL = " https://api.v-avto.ru";
	protected $version = "1";
	protected $max_count = 50;

	public function get_products( $args = [] )
	{ 
		$params = ['page' => 1, 'a' => 1];		
		$params['page'] = !empty($args['paged']) ? $args['paged'] : 1;
		if( !empty(!empty($args['search'])) ) 
			$params['q'] = $args['search'];				
		$args = $this->get_args( 'GET' );
		$function = !empty($params['q']) ? 'search/name' : 'items';		
		$result = $this->send_request( $function.'?'. urldecode(http_build_query($params)), $args );
		if ( !empty($result['response']) )
		{
			foreach( $result['response']['items'] as &$item )
			{
				foreach( $item['images'] as &$image )
					$image = 'https://v-avto.ru'.$image;
			}
			return ['items' => $result['response']['items'], 'total_items' => $result['response']['page']['items']];
		}
		return [];
	}
	
	protected function get_default_option( ) 
	{
		return ['underprice' => 0, 'type_price' => 0, 'storage_id' => 0, 'contractor_id' => 0, 'address' => ''];
	}
	
	public function cron_upload_products()
	{
		if( !usam_check_process_is_running('vavto_application_first_start-'.$this->id) && ($this->option['type_price'] || $this->option['storage_id']) )
		{ 			
			$products = $this->get_products();
			usam_create_system_process( __("Обновление цен и остатков с &laquo;v-avto.ru&raquo;", "usam" ), $this->id, [&$this, 'update_products'], $products['total_items'], 'vavto_application_update-'.$this->id );			
		}
	}
	
	function update_products($id, $number, $event) 
	{		
		$done = 0;
		for ($i = 1; $i <= $this->max_count; $i++) 
		{			
			$products = $this->get_products(['paged' => $event['launch_number']]);
			if ( !empty($products['items']) )
			{
				$codes = [];	
				foreach ( $products['items'] as $item )
					$codes[] = $item['mog'];
				usam_get_product_ids_by_code( $codes, 'vavto_id' );
				foreach ( $products['items'] as $item )
				{				
					$product_id = usam_get_product_id_by_meta( 'vavto_id', $item['mog'] );
					if( $product_id )
					{								
						$_product = new USAM_Product( $product_id );
						if( $this->option['type_price'] )
						{
							$price = $item['price'] + $item['price']*$this->option['underprice']/100;
							$_product->set(['prices' => ['price_'.$this->option['type_price'] => $price]]);
							$_product->save_prices();
						}
						if( $this->option['storage_id'] )
						{
							$_product->set(['product_stock' => ['storage_'.$this->option['storage_id'] => $item['count']]]);
							$_product->save_stocks();
						}
					}
					usam_clean_product_cache( $product_id );
				}				
			}
			$event['launch_number']++;
			$done += count($products['items']);
			sleep(1);
		}		
		return ['done' => $done, 'launch_number' => $event['launch_number']];
	}
	
	function update_products_images($id, $number, $event) 
	{		
		$done = 0;
		for ($i = 1; $i <= $this->max_count; $i++) 
		{			
			$products = $this->get_products(['paged' => $event['launch_number']]);
			if ( !empty($products['items']) )
			{
				$codes = [];	
				foreach ( $products['items'] as $item )
					$codes[] = $item['mog'];
				usam_get_product_ids_by_code( $codes, 'vavto_id' );
				foreach ( $products['items'] as $item )
				{				
					$product_id = usam_get_product_id_by_meta( 'vavto_id', $item['mog'] );
					if( $product_id )
					{								
						$thumbnail_id = get_post_thumbnail_id( $product_id );	
						if( !$thumbnail_id )
						{						
							$_product = new USAM_Product( $product_id );
							$_product->set(['media_url' => $item['images']]);
							$_product->insert_media();
						}
					}
					usam_clean_product_cache( $product_id );
				}				
			}
			$event['launch_number']++;
			$done += count($products['items']);
			sleep(1);
		}		
		return ['done' => $done, 'launch_number' => $event['launch_number']];
	}

	function first_start($id, $number, $event) 
	{		
		$done = 0;		
		for ($i = 1; $i <= $this->max_count; $i++) 
		{ 
			$products = $this->get_products(['paged' => $event['launch_number']]);
			if ( !empty($products['items']) )
			{
				$codes = [];	
				foreach ( $products['items'] as $item )
					$codes[] = $item['oem_num'];
				usam_get_product_ids_by_code( $codes, 'sku' );
				foreach ( $products['items'] as $item )
				{		
					$product_id = usam_get_product_id_by_meta( 'sku', $item['oem_num'] );		
					if( $product_id )
					{					
						usam_update_product_meta( $product_id, 'vavto_id', $item['mog'] );	
						usam_update_product_meta( $product_id, 'contractor', $this->option['contractor_id'] );							
						$_product = new USAM_Product( $product_id );
						if( $this->option['type_price'] )
						{
							$price = $item['price'] + $item['price']*$this->option['underprice']/100;
							$_product->set(['prices' => ['price_'.$this->option['type_price'] => $price]]);
							$_product->save_prices();
						}
						if( $this->option['storage_id'] )
						{
							$_product->set(['product_stock' => ['storage_'.$this->option['storage_id'] => $item['count']]]);
							$_product->save_stocks();
						}
					}
					usam_clean_product_cache( $product_id );
				}
			}
			if ( $this->max_count !== $i )
				$event['launch_number']++;	
			$done += count($products['items']);
			sleep(1);
		}
		return ['done' => $done, 'launch_number' => $event['launch_number']];
	}	
	
	public function insert_product( $args )
	{				
		$i = 0;
		$products = $this->get_products( $args );
		if ( !empty($products['items']) )
		{
			foreach ( $products['items'] as $item )
			{						
				$product_id = usam_get_product_id_by_meta( 'sku', $item['mog'] );
				$product = ['post_title' => $item['name'], 'productmeta' => ['sku' => $item['mog'], 'vavto_id' => $item['mog'], 'contractor' => $this->option['contractor_id']]];				
				if( $this->option['type_price'] )						
					$product['prices']['price_'.$this->option['type_price']] = $item['price'] + $item['price']*$this->option['underprice']/100;
				if( $this->option['storage_id'] )
					$product['product_stock']['storage_'.$this->option['storage_id']] = $item['count'];				
				$product['media_url'] = $item['images'];
				if ( !$product_id )
				{							
					$terms = get_terms(['hide_empty' => 0, 'taxonomy' => 'usam-brands', 'name__like' => $item['oem_brand'] ]);	
					if ( !empty($terms[0]) )
						$product['tax_input']['usam-brands'] = array( $terms[0]->term_id );
					else
					{
						$term = wp_insert_term( $item['oem_brand'], 'usam-brands', array( 'parent' => 0 ) );		
						if ( !is_wp_error($term) ) 
							$product['tax_input']['usam-brands'] = $term['term_id'];
					}	
					$_product = new USAM_Product( $product );	
					$product_id = $_product->insert_product();
					$_product->insert_media();
					$i++;
				}
				else
				{
					$_product = new USAM_Product( $product_id );
					$_product->set( $product ); 
					$_product->update_product();
					$thumbnail_id = get_post_thumbnail_id( $product_id );	
					if( !$thumbnail_id )
						$_product->insert_media();
					$i++;
				}
				break;
			}
		}
		return $i;
	}
	
	
		/*
	images	Массив	Список картинок
p_code	Строка	Код поставщика. По-умолчанию, "VNY6"
mog	Строка	Артикул номенклатуры в базе Восхода
oem_num	Строка	Артикул производителя
oem_brand	Строка	Бренд производителя
name	Строка	Название номенклатуры
shipment	Целое	Кратность покупки
delivery	Целое	Срок доставки
department	Строка	Название отдела в базе Восхода
count	Целое	Общее количество товара на всех складах
count_chel	Целое	Количество товара на складе в Челябинске (всего)
count_chel_st	Целое	Количество товара на складе в Челябинске (Сталеваров)
count_chel_cin	Целое	Количество товара на складе в Челябинске (Цинковая)
count_ekb	Целое	Количество товара на складе в Екатеринбурге
count_magn	Целое	Количество товара на складе в Магнитогорске
count_surgut	Целое	Количество товара на складе в Сургуте
unit_code	Целое	Код единицы измерения
unit	Целое	Единица измерения
price	Вещественное	Стоимость товара с учетом количества
updated_at	Дата	Время последнего обновления товара: цены, остатков
va_catalog_id	Строка	Идентификатор каталога в 1С
va_item_id	Строка	Идентификатор товара в 1С
*/

	
	public function insert_order( $request )
	{	
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		
		if( $this->option['contractor_id'] == $parameters['customer_id'] && !empty($parameters['products']) )
		{		
			$delivery_type = !empty($this->option['address']) ? 1 : 0; 			
			$params = ['comment' => $parameters['note'], 'delivery_address' => $this->option['address'], 'delivery_type' => $delivery_type, 'items' => []];		
			foreach( $parameters['products'] as $product )
			{
				$vavto_id = usam_get_product_meta( $product->product_id, 'vavto_id' );			
				$params['items'][] = ['count' => $product['quantity'], 'mog' => $vavto_id?$vavto_id:$product['sku']];				
			}		
			$args = $this->get_args( 'POST', ["order" => $params] );	
			$result = $this->send_request( 'orders', $args );		
			if( !empty($result['response']) && !empty($result['response']['order']) )
			{
				usam_update_document_metadata( $parameters['id'], 'vavto_id', $result['response']['order']['uid'] );
				return true;
			}
		}
		return false;	
	}
		
	public function register_routes( ) 
	{ 
		register_rest_route( $this->namespace, $this->route.'/order', array(		
			[
				'permission_callback' => false,
				'methods'  => 'POST',
				'callback' => [$this, 'insert_order'],	
				'args' => [
					'id' => ['type' => 'integer', 'required' => false],
					'note' => ['type' => 'string', 'required' => false],
					'products' => ['type' => 'array', 'required' => false],
				]
			],			
		));	
	}
		
	protected function get_args( $method, $params = [] )
	{ 
		$headers["Accept"] = 'application/json';		
		$headers["X-Voshod-API-KEY"] = $this->token; 
		if ( !empty($params) )
			$headers["Content-type"] = 'application/json';
		$args = [
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
		];	
		if ( !empty($params) && $method !== 'GET' )
			$args['body'] = json_encode($params);
		return $args;
	}
			
	public function page_tabs( $tabs )
	{		
		$add = true;
		foreach( $tabs['exchange'] as $tab )
		{
			if ( $tab['id'] == 'external_api' )
			{
				$add = false;
				break;
			}			
		}
		if ( $add )
			$tabs['exchange'][] = ['id' => 'external_api',  'title' => __('Интеграции', 'usam'), 'capability' => 'view_product_importer'];
		return $tabs;
	}
	
	public function filter_tab_sections( $sections, $tab, $page )
	{
		if ( $page == 'exchange' && $tab == 'external_api' )
			$sections['import_products_'.$this->option['service_code']] = ['title' => 'v-avto.ru', 'type' => 'table'];		
		return $sections;
	}

	public function filter_actions( $result, $action, $records ) 
	{
		if ( current_user_can('view_product_importer') )
		{	
			switch( $action )
			{			
				case 'download':
					$i = 0;
					foreach ( $records as $sku )
						$i += $this->insert_product(['search' => $sku]);
					return ['add_product' => $i]; 
				break;			
			}	
		}
	}	
	
	public function filter_table_data( $products, $query_vars ) 
	{
		$products = $this->get_products( $query_vars );	//$query_vars['paged']	
		foreach ( $products['items'] as &$product )
		{
			$product['sku'] = $product['mog'];		
			$product['photo'] = !empty($product['images'][0]) ? $product['images'][0] : '';				
			$product['product_title'] = $product['name'];
		}
		return $products;		
	}	
	
	/*
	images	Массив	Список картинок
p_code	Строка	Код поставщика. По-умолчанию, "VNY6"
mog	Строка	Артикул номенклатуры в базе Восхода
oem_num	Строка	Артикул производителя
oem_brand	Строка	Бренд производителя
name	Строка	Название номенклатуры
shipment	Целое	Кратность покупки
delivery	Целое	Срок доставки
department	Строка	Название отдела в базе Восхода
count	Целое	Общее количество товара на всех складах
count_chel	Целое	Количество товара на складе в Челябинске (всего)
count_chel_st	Целое	Количество товара на складе в Челябинске (Сталеваров)
count_chel_cin	Целое	Количество товара на складе в Челябинске (Цинковая)
count_ekb	Целое	Количество товара на складе в Екатеринбурге
count_magn	Целое	Количество товара на складе в Магнитогорске
count_surgut	Целое	Количество товара на складе в Сургуте
unit_code	Целое	Код единицы измерения
unit	Целое	Единица измерения
price	Вещественное	Стоимость товара с учетом количества
updated_at	Дата	Время последнего обновления товара: цены, остатков
va_catalog_id	Строка	Идентификатор каталога в 1С
va_item_id	Строка	Идентификатор товара в 1С
*/
	
	public function filter_table_columns( ) 
	{
		return [        
			'cb'            => '<input type="checkbox" />',	
			'product_title' => __('Название товара', 'usam'),		
			'oem_brand'     => __('Бренд', 'usam'),				
			'price'         => __('Цена', 'usam'),	
			'count'         => __('Остаток', 'usam'),
			'department'    => __('Название отдела', 'usam'),		
			'oem_num'       => __('Артикул производителя', 'usam'),
        ];
	}
	
	public function document_actions( $actions, $type ) 
	{
		if( $type == "order_contractor" && $this->option['contractor_id'] )
			$actions[] = ['function' => "addOrderSupplier('vavto/$this->id/order')", 'icon' => 'add', 'title' => __('Отправить заказ в v-avto.ru', 'usam'), 'attr' => "v-if='data.customer_id==".$this->option['contractor_id']."'"]; 
		return $actions;
	}
	
	public function product_metabox_codes( $codes )
	{ 	
		$codes['code_moysklad']	= __( 'Код v-avto.ru', 'usam');
		return $codes;
	}	
	
	public function service_load()
	{ 
		add_filter( 'usam_page_tabs', [&$this, 'page_tabs']);
		add_filter( 'usam_tab_sections', [$this, 'filter_tab_sections'], 10, 3 );	
		add_filter( 'usam_import_products_vavto_actions', [$this, 'filter_actions'], 10, 3);	
		add_filter( 'usam_application_import_products_data', [$this, 'filter_table_data'], 10, 2);
		add_filter( 'usam_application_import_products_columns', [$this, 'filter_table_columns']);		
		add_action('usam_application_update_schedule_'.$this->option['service_code'],  [$this, 'cron_upload_products']);
		add_filter( 'usam_document_action', [$this, 'document_actions'], 10, 2 );
		add_action('rest_api_init', array($this,'register_routes') );
		add_filter( 'usam_product_metabox_codes', [&$this, 'product_metabox_codes'], 10, 2 );			
	}	
	
	function display_form( ) 
	{
		$type_price = usam_get_application_metadata( $this->id, 'type_price' );	
		$storage_id = usam_get_application_metadata( $this->id, 'storage_id' );	
		$update_schedule = usam_get_application_metadata( $this->id, 'update_schedule' );	
		$update_schedule_time = usam_get_application_metadata( $this->id, 'update_schedule_time' );
		if ( !$update_schedule_time )
			$update_schedule_time = '00:00';
		?>		
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='messenger_secret_key'><?php esc_html_e( 'Токен', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['access_token'], ['name' => 'access_token', 'id' => 'messenger_secret_key'] ); ?>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Тип цены', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name = "type_price">
						<option value='' <?php selected('', $type_price) ?>><?php esc_html_e( 'Не обновлять цены', 'usam'); ?></option>
						<?php 
						$prices = usam_get_prices();	
						foreach ( $prices as $price )
						{	
							?><option value='<?php echo $price['code']; ?>' <?php selected($price['code'], $type_price) ?>><?php echo $price['title']; ?></option><?php 
						}
						?>	
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Наценка' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<input type="text" name="underprice" value="<?php echo $this->option['underprice']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Склад для хранения остатков', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<?php $storages = usam_get_storages(); ?>
					<select name = "storage_id">
						<option value='0' <?php selected($storage_id, 0); ?>><?php esc_html_e( 'Не обновлять остатки', 'usam'); ?></option>
						<?php												
						foreach ( $storages as $storage ) 
						{
							?><option value='<?php echo $storage->id; ?>' <?php selected($storage_id, $storage->id); ?>><?php echo $storage->title; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_catalog_schedule'><?php esc_html_e( 'Автоматическое обновление цен и остатков каждые' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<select name='update_schedule'>						
						<option value=''><?php esc_html_e('Отключено' , 'usam'); ?></option>
						<?php
						foreach ( wp_get_schedules() as $cron => $schedule ) 
						{										
							?><option <?php selected( $update_schedule, $cron ); ?> value='<?php echo $cron; ?>'><?php echo $schedule['display']; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_stock_time'><?php esc_html_e( 'Начиная с' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">
					<input type="text" id = "option_stock_time" name="update_schedule_time" value="<?php echo $update_schedule_time; ?>"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Адрес доставки' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<textarea name="address" style="height:100px;"><?php echo htmlspecialchars($this->option['address']); ?></textarea>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( 'Поставщик товара' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<select name='contractor_id'>
						<option value=''><?php esc_html_e('Не менять' , 'usam'); ?></option>
						<?php			
						$companies = usam_get_companies(['fields' => ['id', 'name'], 'type' => 'contractor', 'orderby' => 'name']);
						foreach( $companies as $company )
						{					
							?><option value="<?php echo $company->id; ?>" <?php selected($company->id, $this->option['contractor_id']); ?>><?php echo $company->name; ?></option><?php
						}				
						?>
					</select>	
				</div>
			</div>			
		</div>		
		<?php
	}	
		
	public function get_form_buttons( ) 
	{
		return '<input type="submit" name="first_start" class="button" value="'.__( 'Первый запуск' , 'usam').'">
		<input type="submit" name="products_images" class="button" value="'.__( 'Загрузить картинки' , 'usam').'">';
	}
	
	public function save_form( ) 
	{
		$metas['update_schedule'] = isset($_POST['update_schedule'])?sanitize_text_field($_POST['update_schedule']):'';
		$metas['update_schedule_time'] = !empty($_POST['update_schedule_time'])?sanitize_text_field($_POST['update_schedule_time']):'00:00';
		$metas['type_price'] = isset($_POST['type_price'])?sanitize_text_field($_POST['type_price']):'';
		$metas['storage_id'] = isset($_POST['storage_id'])?absint($_POST['storage_id']):'';		
		$metas['contractor_id'] = isset($_POST['contractor_id'])?absint($_POST['contractor_id']):'';	
		$metas['underprice'] = isset($_POST['underprice'])?usam_string_to_float($_POST['underprice']):'';
		$metas['address'] = isset($_POST['address'])?sanitize_textarea_field($_POST['address']):'';		
		foreach( $metas as $meta_key => $meta_value)
		{			
			usam_update_application_metadata($this->id, $meta_key, $meta_value);
		}
		$this->remove_hook( 'update' );	
		if ( $this->is_token() )
		{
			if ( $this->option['active'] )
				$this->add_hook('update');			
			if( !empty($_POST['first_start']) )
			{
				$products = $this->get_products(['a' => 0]);
				if( !empty($products['total_items']) )
					usam_create_system_process( __("Первый запуск &laquo;v-avto.ru&raquo;", "usam" ), $this->id, [$this, 'first_start'], $products['total_items'], 'vavto_application_first_start-'.$this->id );		
			}
			if( !empty($_POST['products_images']) )
			{
				$products = $this->get_products(['a' => 0]);
				if( !empty($products['total_items']) )
					usam_create_system_process( __("Загрузить картинки с &laquo;v-avto.ru&raquo;", "usam" ), $this->id, [$this, 'update_products_images'], $products['total_items'], 'vavto_application_products_images-'.$this->id );		
			}			
		}		
	}
}

require_once( USAM_FILE_PATH .'/admin/includes/list_table/application_import_products_list_table.php' );
class USAM_List_Table_import_products_vavto extends USAM_Application_Import_Products_Table{ 
	protected $per_page = 50;
}
?>