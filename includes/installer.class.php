<?php
// Установка магазина
final class USAM_Install
{	
	public static $new_install = false;
	private static $errors = array();	
	
	public static function set_log_file()
	{		
		usam_log_file( self::$errors );
		self::$errors = array();
	}	
	
	public static function install() 
	{
		global $wpdb;		
				
		set_time_limit(3600);
		
		$permalink_structure = get_option( 'permalink_structure' );
		if ( empty($permalink_structure) )
			update_option( 'permalink_structure', '/%postname%/' );
		self::create_or_update_tables();
		self::add_option();		
		self::create_storages();		
		self::create_payment_gateway();
		self::create_shipping();	
		self::custom_product_tab();					
	
		USAM_Post_types::register_taxonomies();		
		if( get_option('usam_db_version', false) === false )
		{
			wp_schedule_single_event( time(), 'usam_theme_installer', ['domino'] );
			self::add_role();
			$api = new USAM_Service_API();
			$result = $api->set_free_license( );				
							
			self::theme_banners();		
			self::create_virtual_pages();
			self::create_system_pages();
			self::trigger_email();
			
			set_transient( '_usam_activation_redirect', 1, 300 );			
						
			require_once( USAM_FILE_PATH . '/admin/db/db-install/system_default_data.php' );
			new USAM_Load_System_Default_Data(['properties', 'webforms', 'properties_groups', 'location_type','location','currency','country', 'object_status', 'product_attributes', 'search_engine_regions', 'units_measure']);	

			add_option( 'usam_db_version', USAM_DB_VERSION, '', 'yes' );	
			add_option( 'usam_install_date', date( "Y-m-d H:i:s" ), '', 'yes' );
			
			USAM_Tracker::send_tracking_data( true );	
			usam_insert_folder(['name' => 'Universam', 'slug' => 'universam', 'parent_id' => 0]);			
		}	
		else
		{		
			$api = new USAM_Service_API();
			$result = $api->universam_activation( );				
		}							
		self::create_upload_directories();				
		flush_rewrite_rules( );
		
		return true;
	}	

	protected static function custom_product_tab() 
	{	
		require_once( USAM_FILE_PATH . '/includes/product/custom_product_tabs_query.class.php' );
		require_once( USAM_FILE_PATH . '/includes/product/custom_product_tab.class.php' );

		$product_tabs = usam_get_custom_product_tabs();
		if ( empty($product_tabs) )
		{
			$product_tabs = 
			[
				['name' => 'Характеристики', 'active' => 1, 'global' => 1, 'description' => '%characteristics%%content%', 'sort' => 1, 'code' => 'characteristics'],
				['name' => 'Описание', 'active' => 1, 'global' => 1, 'description' => '%description%', 'sort' => 1, 'code' => 'description'],
				['name' => 'Бренд', 'active' => 1, 'global' => 1, 'description' => '%brand%', 'sort' => 1, 'code' => 'brand'],
				['name' => 'Отзывы', 'active' => 1, 'global' => 1, 'description' => '%comment%', 'sort' => 1, 'code' => 'reviews'],
				['name' => 'Гарантия', 'active' => 1, 'global' => 0, 'description' => 'Гарантия на товар составляет 1 год', 'sort' => 1, 'code' => 'guarantee'],
				['name' => 'Оплата', 'active' => 1, 'global' => 0, 'description' => 'Доступные способы оплаты: Наличными, Онлайн-оплата картой, Кредитование, Рассрочка от Globaldrive, Покупка через Trade-in. Подробнее - в разделе Оплата и доставка', 'sort' => 1, 'code' => 'payment'],
				['name' => 'Возврат', 'active' => 1, 'global' => 0, 'description' => 'Возврат в течении 14 дней', 'sort' => 1, 'code' => 'return'],
			];	
			foreach ( $product_tabs as $product_tab )
				usam_insert_custom_product_tab( $product_tab );
		}	
	}
	
	protected static function create_payment_gateway() 
	{		
		global $wpdb;		
			
		$payment_gateways = usam_get_payment_gateways(['active' => 'all', 'fields' => 'id', 'number' => 1]);
		if ( !$payment_gateways)
		{
			$gateways = [
				array('name' => __('Наличными курьеру', 'usam'), 'type' => 'c', 'handler' => '', 'setting' => ['gateway_system' => [] ]),
				array('name' => __('Банковской картой курьеру', 'usam'), 'type' => 'c', 'handler' => '', 'setting' => ['gateway_system' => [] ]),
				array('name' => __('На расчетный счет', 'usam'), 'type' => 'c', 'handler' => '', 'setting' => ['gateway_system' => [] ] ),
				array('name' => __('Сбербанк', 'usam'), 'type' => 'c', 'handler' => 'sberbank', 'setting' => ['gateway_system' => [] ] ),
				array('name' => __('Робокасса', 'usam'), 'type' => 'c', 'handler' => 'robokassa', 'setting' => ['gateway_system' => [] ] ),
				array('name' => __('Яндекс Касса', 'usam'), 'type' => 'c', 'handler' => 'yandex_kassa', 'setting' => ['gateway_system' => []] ),
				array('name' => 'Paypal', 'type' => 'c', 'handler' => 'paypal', 'setting' => ['gateway_system' => [] ] ),
				array('name' => 'Onpay', 'type' => 'c', 'handler' => 'onpay', 'setting' => ['gateway_system' => [] ] ),
				array('name' => 'Fondy', 'type' => 'c', 'handler' => 'fondy', 'setting' => ['gateway_system' => [] ] ),
				array('name' => 'Тинькофф', 'type' => 'c', 'handler' => 'tinkoff', 'setting' => [ 'gateway_system' => [] ] ),
			];					
			foreach( (array)$gateways as $key => $gateway )
			{				
				$gateway['sort'] = $key;
				$gateway['active'] = 1;		
				$settings = array();	
				if ( isset($gateway['setting']) )
				{
					$settings = $gateway['setting'];
					unset($gateway['setting']);
				}				
				$wpdb->insert( USAM_TABLE_PAYMENT_GATEWAY, $gateway );				
				if ( $wpdb->insert_id )
				{								
					foreach( $settings as $key => $value )
						usam_update_payment_gateway_metadata($wpdb->insert_id, $key, $value);
					if ( $gateway['name'] == __('На расчетный счет', 'usam') )
					{
						$message_completed = "<h2>Посмотрите, пожалуйста, счет.</h2>
		 
		<p>Счет действителен 7 дней. При возникновении вопросов свяжитесь с нами по телефону %shop_phone% или напишите на почту %shop_mail%.</p>
		 
		<p>Итоговая стоимость: %total_price_currency%.</p>		
		<p></p>
		<p></p>
		<p>С уважением, Марина Курбатова - <strong>ваш персональный менеджер</strong></p>";
						
					}
					else
					{
					$message_completed = '<h2>Спасибо за Ваш заказ в интернет-магазине!</h2>
					&nbsp;
Ваш номер заказа: <strong>№ %order_id%</strong>
&nbsp;
<span style="font-size: 18pt; color: #ff0000;">Прочтите письмо, которое мы Вам отправили на %customer_email%!</span>
&nbsp;
[if order_paid=2 {<span style="font-size: 18pt; color: #99cc00;"><strong>Заказ оплачен</strong></span>}]

<p>Обработка заказа занимает от 1 до 5 рабочих дней.</p>
<p>Зарегистрированные пользователи могут просматривать информацию о готовности заказа в личном кабинете.</p>

&nbsp;

<p>Выбранный способ получения: %shipping_method_name%</p>
[if shipping_method_name=Самовывоз {<p><strong>Забрать из магазина %storage_address%</strong></p>
<p>График работы %storage_schedule%</p>
<p>Телефон %storage_phone%</p>

<p>Как только менеджер обработает заказ и товар будет доставлен в указанный магазин, он вам отправит смс и письмо на электронную почту.</p>}]
<p>Об изменениях статуса заказа Вы будете получать письмо на эл. почту, указанную при оформление заказа.</p>

<p>Вы заказали эти товары:</p>

%product_list%
<p>Итоговая стоимость: %total_price_currency%.</p>';

$message_fail = '<h2>Оплата заказа завершилось ошибкой.</h2>
<p>К сожалению ваш заказ № %order_id% не был оплачен.</p>
<p>Возможно на карте не достаточно средств. Повторите оплату или свяжитесь с нами.</p>
<p>Нажмите <a href="'.usam_get_url_system_page('checkout').'">здесь</a>, чтобы вернуться к странице оформления заказа.</p>';
					}
					
					usam_update_payment_gateway_metadata($wpdb->insert_id, 'message_completed', $message_completed);
					usam_update_payment_gateway_metadata($wpdb->insert_id, 'message_fail', $message_fail);
				}
			}
		}
	}
	
	protected static function create_shipping() 
	{		
		global $wpdb;		
		
		$delivery_services = usam_get_delivery_services(['active' => 'all', 'fields' => 'id', 'number' => 1]);
		if ( !$delivery_services )
		{
			$shippings = [
				['name' => __('Курьером', 'usam'), 'active' => 1, 'handler' => '', 'period_type' => 'd', 'setting' => ['price' => 1000, 'margin' => 0, 'margin_type' => 'P', 'type_payer' => 0]],
				['name' => __('Самовывоз', 'usam'), 'active' => 1, 'handler' => '', 'delivery_option' => 1, 'period_type' => 'd', 'setting' => ['price' => 0, 'margin' => 0, 'margin_type' => 'P', 'type_payer' => 0]],
				['name' => __('Почта России', 'usam'), 'active' => 1, 'handler' => 'emspost', 'period_type' => 'd', 'setting' => ['margin' => 0, 'margin_type' => 'P', 'type_payer' => 0]],
				['name' => __('EMS', 'usam'), 'active' => 0, 'handler' => 'emspost', 'period_type' => 'd', 'setting' => ['margin' => 0, 'margin_type' => 'P', 'type_payer' => 0]],
				['name' => 'DPD', 'active' => 0, 'handler' => 'dpd', 'period_type' => 'd', 'setting' => ['margin' => 0, 'margin_type' => 'P', 'type_payer' => 0]],
				['name' => __('СДЭК','usam'), 'active' => 0, 'handler' => 'cdek', 'period_type' => 'd', 'setting' => ['margin' => 0, 'margin_type' => 'P', 'type_payer' => 0]],
				['name' => __('Новая почта','usam'), 'active' => 0, 'handler' => 'novaposhta', 'period_type' => 'd', 'setting' => ['margin' => 0, 'margin_type' => 'P', 'type_payer' => 0] ],				
			];	
			foreach( (array)$shippings as $key => $shipping )
			{					
				$shipping['sort'] = $key;				
				$settings = array();
				if ( isset($shipping['setting']) )
				{
					$settings = $shipping['setting'];
					unset($shipping['setting']);
				}
				$wpdb->insert( USAM_TABLE_DELIVERY_SERVICE, $shipping );	
				if ( $wpdb->insert_id )
				{					
					foreach( $settings as $key => $value )
						usam_update_delivery_service_metadata($wpdb->insert_id, $key, $value);
				}
			}
		}
	}
	
	
	/*Эта часть создает страницы и автоматически ставит их URL-адреса в странице параметров. */
	public static function create_system_pages() 
	{		
		if ( get_option('usam_website_type', 'store' ) != 'crm' )
		{
			global $wpdb;
			
			$pages = usam_system_pages();
			$newpages = false; 
			foreach( (array)$pages as $page )
			{			
				$page_id = $wpdb->get_var("SELECT id FROM `".$wpdb->posts."` WHERE `post_content` LIKE '%".$page['content']."%'	AND `post_type` != 'revision'");		
				if( empty($page_id) )
				{		
					$page_id = wp_insert_post(['post_title' => $page['title'], 'post_type' => 'page', 'post_name' => $page['name'],	'comment_status' => 'closed', 'ping_status' => 'closed', 'post_content' => $page['content'], 'post_status' => 'publish', 'menu_order' => 0,	'post_parent' => '']);
					$newpages = true;
				}						
			} 			
			if ( $newpages ) 
			{
				wp_cache_delete( 'all_page_ids', 'pages' );
			} 
			usam_update_permalink_slugs();			
		}
	}	
	
	public static function create_virtual_pages() 
	{		
		if ( get_option('usam_website_type', 'store' ) != 'crm' )
		{
			global $wpdb;	
			
			$virtual_page = usam_virtual_page( );
			foreach( $virtual_page as $name => $page )
			{			
				$page_id = $wpdb->get_var("SELECT id FROM `".$wpdb->posts."` WHERE `post_content` LIKE '%".$page['content']."%'	AND `post_type` != 'revision'");	
				if( empty($page_id) )
					$page_id = wp_insert_post(['post_title' => $page['title'], 'post_name' => $name, 'comment_status' => 'closed',	'ping_status' => 'closed', 'post_content' => $page['content'], 'post_status' =>	'publish', 'post_parent' =>	0, 'post_type' => 'page']);	
			} 
		}
	}
	
	protected static function theme_banners() 
	{	
		$banners = [
			['name' => __('Наличие в магазинах','usam'), 'settings' => ['html' => '<strong>Наличие в магазинах</strong><br><span class = "stock_store">Нажми<span>', 'layouttype' => 'css', 'actions' => ['type' => 'modal', 'value' => 'availability_by_warehouses']], 'type' => 'html', 'banner_location' => 'purchase_terms'],
			['name' => __('Работаем каждый день','usam'), 'settings' => ['html' => '<strong>Работаем</strong><br>каждый день', 'layouttype' => 'css', 'actions' => ['type' => '', 'value' => '']], 'type' => 'html', 'banner_location' => 'purchase_terms'],
			['name' => __('Возврат товара','usam'), 'settings' => ['html' => '<strong>Возврат товара</strong><br>если он вам не подошел', 'layouttype' => 'css', 'actions' => ['type' => '', 'value' => '']], 'type' => 'html', 'banner_location' => 'purchase_terms'],
		];
		foreach( $banners as $banner )
		{
			$banner['status'] = 'active';
			$banner_id = usam_insert_banner( $banner );
			usam_set_banner_location( $banner_id, $banner['banner_location'] );
		}
	}
	
	// Создать склад интернет магазина	
	protected static function create_storages() 
	{	
		global $wpdb;
		$count = $wpdb->get_var(" SELECT COUNT(id) FROM `".USAM_TABLE_STORAGES."`" );		
		if ( empty($count) )
		{			
			$location_id = get_option('usam_shop_location', 129 );
			$storages = [
				['sort' => 0, 'code' => 4576, 'title' => __('Интернет-магазин','usam'), 'active' => 1,'issuing' => 1, 'shipping' => 1, 'location_id' => $location_id],
				['sort' => 0, 'code' => 45786, 'title' => __('Склад','usam'), 'active' => 1, 'issuing' => 0, 'shipping' => 1, 'location_id' => $location_id],
				['sort' => 0, 'code' => 4578, 'title' => __('Магазин','usam'), 'active' => 1, 'issuing' => 1, 'shipping' => 1, 'location_id' => $location_id],
				['sort' => 0, 'code' => 4586, 'title' => __('Магазин 2','usam'), 'active' => 1, 'issuing' => 1, 'shipping' => 1],
				['sort' => 0, 'code' => 486, 'title' => __('Посмарт','usam'), 'active' => 1, 'issuing' => 1, 'shipping' => 0, 'location_id' => $location_id]
			];	
			foreach( $storages as $storage )
			{
				$storage_id = usam_insert_storage( $storage );
				usam_update_storage_metadata( $storage_id, 'index', 101000);
			}
		}
	}			
		
	protected static function get_options() 
	{	
		$prices = [
			['id' => 1, 'type' => 'R', 'title' => __('По умолчанию', 'usam'), 'code' => 'tp_1', 'available' => 1, 'currency' => 'RUB', 'base_type' => '0', 'underprice' => 80, 'rounding' => 1, 'sort' => 1], 
			['id' => 2, 'type' => 'R', 'title' => __('Оптовая', 'usam'), 'code' => 'tp_2', 'available' => 1, 'currency' => 'RUB', 'base_type' => 'tp_1', 'underprice' => -20, 'rounding' => 1, 'sort' => 1, 'roles' => ['wholesale_buyer']],
			['id' => 3, 'type' => 'P', 'title' => __('Закупочная', 'usam'), 'code' => 'tp_3', 'currency' => 'RUB', 'rounding' => 1, 'sort' => 1, 'base_type' => '0', 'underprice' => 0] 
		];
		$underprice_rules = [
			array( 'id' => 1, 'title' => '+10%', 'value' => 10, 'category' => array(), 'brands' => array(), 'category_sale' => array(), 'type_prices' => array() ), 
			array( 'id' => 2, 'title' => '+30%', 'value' => 30, 'category' => array(), 'brands' => array(), 'category_sale' => array(), 'type_prices' => array() ),
			array( 'id' => 3, 'title' => '-20%', 'value' => -20, 'category' => array(), 'brands' => array(), 'category_sale' => array(), 'type_prices' => array() ) 
		];		
		$calendares = [1 => ['id' => 1,  'name' => __('Заказы', 'usam'), 'user_id' => 0, 'sort' => 1, 'type' => 'order'], 2 => ['id' => 2,  'name' => __('Дела', 'usam'), 'user_id' => 0, 'sort' => 2, 'type' => 'affair']];	
		add_site_option( 'usam_calendars', serialize($calendares), '', false );							
							
		$crm_contact_source = array( 
			array( 'name' => __('Свой контакт', 'usam'), 'id' => 'self' ), 
			array( 'name' => __('Сделал заказ', 'usam'), 'id' => 'order' ),
			array( 'name' => __('Зарегистрировался на Вашем сайте', 'usam'), 'id' => 'register' ),
			array( 'name' => __('Офлайн магазины', 'usam'), 'id' => 'offline' ),
			array( 'name' => __('Существующий клиент', 'usam'), 'id' => 'partner' ),
			array( 'name' => __('Звонок', 'usam'), 'id' => 'call' ),
			array( 'name' => __('Веб-сайт', 'usam'), 'id' => 'web' ),
			array( 'name' => __('Электронная почта', 'usam'), 'id' => 'email' ),
			array( 'name' => __('Конференция', 'usam'), 'id' => 'conference' ),
			array( 'name' => __('Яндекс', 'usam'), 'id' => 'yandex' ),	
			array( 'name' => __('Google', 'usam'), 'id' => 'google' ),			
			array( 'name' => __('Выставка', 'usam'), 'id' => 'trade_show' ),
			array( 'name' => __('Сотрудник', 'usam'), 'id' => 'employee' ),
			array( 'name' => __('Бывший сотрудник', 'usam'), 'id' => 'formeremployee' ),
			array( 'name' => __('Компания', 'usam'), 'id' => 'company' ),
			array( 'name' => __('Импортированный', 'usam'), 'id' => 'import' ),
			array( 'name' => __('Письмо', 'usam'), 'id' => 'mail' ),
			array( 'name' => __('Чат', 'usam'), 'id' => 'chat' ), 				
			array( 'name' => 'Instagram', 'id' => 'instagram' ),
			array( 'name' => 'Facebook', 'id' => 'facebook' ),
			array( 'name' => 'Telegram', 'id' => 'telegram' ),	
			array( 'name' => 'Viber', 'id' => 'viber' ),	
			array( 'name' => 'WhatsApp', 'id' => 'whatsapp' ),	
			array( 'name' => __('Вконтакте', 'usam'), 'id' => 'vk' ),
			array( 'name' => __('Однокласники', 'usam'), 'id' => 'ok' ),					
			array( 'name' => __('Другое', 'usam'), 'id' => 'orher' ),
		);			
		$add_option = array(				
		//	'page_for_personal_data' => ['value' => 0, 'autoload' => true],
			'cahe_site_icon' => ['value' => [], 'autoload' => false],	//кеширование иконки сайта
						
			'document_number_counter' => ['value' => [], 'autoload' => false],	
			'crm_contact_source' => ['value' =>  $crm_contact_source, 'autoload' => false],	
			'attachment_redirect' => array( 'value' => 1, 'autoload' => true ),	
			'1c' => ['value' => ['active' => 0, 'version' => '2.09', 'product' => ['title' => 1, 'variation' => 1,'body' => 1, 'excerpt' => 1, 'attachments' => 1, 'categories' => 1], 'order' => ['upload_1c' => 1]], 'autoload' => true],													
			
			'mytarget_counter_active' => array( 'value' =>  0, 'autoload' => true ),	
			'google_analytics_active' => array( 'value' =>  0, 'autoload' => true ),	
			'google' => array( 'value' =>  array(), 'autoload' => true ),	
			'yandex_metrika_active' => array( 'value' =>  0, 'autoload' => true ),						
			'yandex' => array( 'value' => array(), 'autoload' => true ),
			'pinterest' => array( 'value' => array(), 'autoload' => true ),		
			'bing' => array( 'value' => array(), 'autoload' => true ),				
			'check_position_site' => array( 'value' => 0, 'autoload' => true ),
			'metas' => array( 'value' => ['pages' => [''], 'post_types' => [], 'terms' => []], 'autoload' => true ),			
			
			'shop_location' => array( 'value' =>  1061, 'autoload' => true ),	
			'phones' => ['value' =>  1061, 'autoload' => true],			
			'set_events' => array( 'value' =>  array(), 'autoload' => false ),
							
			'crosssell_conditions' => ['value' =>  array(), 'autoload' => false],
			'type_prices' => ['value' => $prices , 'autoload' => true],
			'underprice_rules' => array( 'value' => $underprice_rules , 'autoload' => false ),
			'default_reservation_storage' => array( 'value' => 1, 'autoload' => true ),			
			'ftp_download_prices' => array( 'value' => false , 'autoload' => true ),		
			'ftp_download_balances' => array( 'value' => false , 'autoload' => true ),	
			'ftp_export_order' => array( 'value' => false , 'autoload' => true ),		
			'exchange_ftp_settings' => ['value' => false , 'autoload' => true],
			
			'notifications' => ['value' => '' , 'autoload' => false],							
			
			'allow_tracking' => ['value' => 1 , 'autoload' => true],	
			'pointer' => ['value' => 1, 'autoload' => true],				

 			'sets_of_products' => array( 'value' => array(), 'autoload' => false ),	
			
			'time_keeping_baskets' => ['value' => 30 , 'autoload' => true],	
			'product_reserve_clear_period' => ['value' => 7 , 'autoload' => true],	
			'product_reserve_condition' => ['value' => '' , 'autoload' => true],		
			'bonus_rules' => array( 'value' => array('percent' => 20, 'bonus_coupon' => 0) , 'autoload' => true ),			

			'vk_api' => array('value' => ['client_id' => '', 'service_token' => '', 'client_secret' => ''], 'autoload' => true ),	
			'vk_publish_reviews' => array( 'value' => 1 , 'autoload' => true ),						 
			'vk_publishing_rules' => array( 'value' => [], 'autoload' => false ),		
			'vk_contest' => array( 'value' => array() , 'autoload' => true ),								
			'vk_autopost' => array( 'value' => array( 
				'upload_photo_count' => 4, 
				'excerpt_length' => 25, 
				'excerpt_length_strings' => 2688, 
				'from_signed' => 1, 
				'add_link' => 1,		
				'birthday' => __('Интернет-магазин','usam').' '.strtoupper(get_bloginfo('name')).' поздравляет с Днем Рождения участников группы, родившихся сегодня!\n\n{%user_link%}',				
				'product_review_message' => '%header%\n\n%review_title%\n%review_author%\n\n%link%\n\n%review_excerpt%\n\n%review_response%', 
				'reviews_message' => '%header%\n\n%review_title%\n%review_author%\n\n%link%\n\n%review_excerpt%\n\n%review_response%', 
				'post_message' => "%title%\n\n%excerpt%", 
				'product_message' => __('Интернет-магазин','usam').' '.strtoupper(get_bloginfo('name')).' '.__('предлагает','usam').':\n%title%\n\nКупить - %link%\n%price%', 
				'product_day_message' => '---------------------------------'.__('ТОВАР ДНЯ','usam').'---------------------------------\n\n%name%\n%title%\nКупить - %link%\n%price%',
			) , 'autoload' => false ),	
			
		// Презентация			
			'default_catalog' => array( 'value' => '', 'autoload' => true ),			
			'types_products_sold' => array( 'value' => array('product'), 'autoload' => true ),
			'number_days_product_new' => array( 'value' => 14, 'autoload' => true ),
			'view_tab' => array( 'value' => array('characteristics' => 1, 'description' => 1, 'comment' => 1, 'brand' => 1), 'autoload' => true ),	
			'product_content_display' => array( 'value' => 'tab' , 'autoload' => true ),			
			'show_breadcrumbs' => array( 'value' => 1, 'autoload' => true ),			
			'product_pagination' => array( 'value' => 1 , 'autoload' => true ),		
			'products_per_page' => array( 'value' => 24 , 'autoload' => true ),						
			'default_category' => ['value' => 'all' , 'autoload' => true],				
			'display_categories' => array( 'value' => 1, 'autoload' => true ),
			'display_sold_products' => array( 'value' => 'show', 'autoload' => true ),
			'show_zero_price' => array( 'value' => 1, 'autoload' => true ),	
			'number_products_line' => array( 'value' => 4, 'autoload' => true ),	
			'theme_home_blocks' => array( 'value' => [], 'autoload' => true ),			
						
			'order_action_buttons' => ['value' => ['pay', 'add_to_cart'], 'autoload' => true],	
				
			'show_name_variation' => array( 'value' => 0, 'autoload' => true ),						
			'show_product_rating' => array( 'value' => 1, 'autoload' => true ),
			'show_multi_add' => ['value' => 1, 'autoload' => true],								

			'product_sort_by' => ['value' => 'date-desc' , 'autoload' => true],		
			'sorting_options' => ['value' => ['name', 'price', 'popularity', 'date'] , 'autoload' => true],	
			
			'category_hierarchical_url' => array( 'value' => 0 , 'autoload' => true ),
			'show_subcatsprods_in_cat' => array( 'value' => 1 , 'autoload' => true ),
			'display_category_name' => array( 'value' => 1 , 'autoload' => true ),
			'category_description' => array( 'value' => 1 , 'autoload' => true ),				
		// Поиск				 
			'search_product_property' => ['value' => ['sku' => 'like', 'barcode' => 1, 'post_content' => 0, 'post_excerpt' => 0] , 'autoload' => true],	
			'search_result_items' => array( 'value' => 5 , 'autoload' => true ),	
			'search_length_name_items' => array( 'value' => 50 , 'autoload' => true ),	
			'search_global_search' => array( 'value' => 1 , 'autoload' => true ),			
			'search_text_lenght' => array( 'value' => 100, 'autoload' => true ),
			'search_sku_enable' => array( 'value' => 1, 'autoload' => true ),	
			'search_in_stock_enable' => array( 'value' => 1, 'autoload' => true ),	
			'search_price_enable' => array( 'value' => 1, 'autoload' => true ),
			'search_categories_enable' => array( 'value' => 1, 'autoload' => true ),	
			'search_tags_enable' => array( 'value' => 1, 'autoload' => true ),	
			'search_box_text' => array( 'value' => __('Поиск по названию и коду', 'usam'), 'autoload' => true ),		
			'search_length_description_items' => array( 'value' => 100, 'autoload' => true ),			
			
			'consent_processing_personal_data' => array( 'value' => '' , 'autoload' => true ),    // Согласие на обработку персональных данных
			'cookie_notice' => ['value' => '', 'autoload' => true],				
						
			//Обратная связь
			'reviews' => array( 'value' => ['form_location' => 'top', 'goto_show_button' => 'review', 'per_page' => 10, 'show_hcard' => 1, 'show_hcard_on' => 1], 'autoload' => true ),
			'chat' => array( 'value' => array("show_button" => 1, 'show_chat' => 1, 'phone' => 1, 'webform' => 'product_info', 'backcall' => 'back_call'), 'autoload' => true ),
	
			'printing_form' => array( 'value' => '' , 'autoload' => false ),
			'purchase_rules' => array( 'value' => '' , 'autoload' => false ),
			'coupons_roles' => array( 'value' => '' , 'autoload' => false ),		
			'product_views' => array( 'value' => ['grid', 'list'], 'autoload' => true ),	
			'users_product_lists' => ['value' => ['compare', 'desired'], 'autoload' => true],	
			'product_tags' => ['value' => ['sold', 'percent_action', 'new'], 'autoload' => true],			
			'product_view' => array( 'value' => 'grid' , 'autoload' => true ),				
			'permalinks' => ['value' => array('product_base' => 'products') , 'autoload' => true],				
			'types_payers' => ['value' => [['id' => 1, 'name' => __('Физическое лицо', 'usam'), 'type' => 'contact', 'active' => 1, 'sort' => 10], ['id' => 2, 'name' => __('Юридическое лицо', 'usam'), 'type' => 'company', 'active' => 1, 'sort' => 20]], 'autoload' => true], // Типы плательщиков			
						
			// Скидки				
			'product_day_rules' => array( 'value' => '' , 'autoload' => true ),	
			'bonuses_rules' => array( 'value' => '' , 'autoload' => true ),				
			
			// Главные						
			'product_order' => ['value' => 'DESC' , 'autoload' => false],		
			'page_transaction_results' => ['value' => '' , 'autoload' => false],			
			'return_email' => ['value' => '' , 'autoload' => true],	// Почта магазина
			'currency_type' => ['value' => 'RUB' , 'autoload' => true],				//валюта
			'thousands_separator' => array( 'value' => ' ' , 'autoload' => true ),
			'decimal_separator' => array( 'value' => ',' , 'autoload' => true ),			
			'currency_sign_location' => array( 'value' => '2' , 'autoload' => true ),//Расположение валюты				
			'weight_unit' => array( 'value' => 'g' , 'autoload' => true ),
			'dimension_unit' => array( 'value' => 'mm' , 'autoload' => true ),		
			'max_downloads' => array( 'value' => 1 , 'autoload' => true ),		//Максимальное количество закачек файлов
			'inventory_control' => array( 'value' => 0 , 'autoload' => true ),	
			'accurate_inventory_control' => array( 'value' => 1, 'autoload' => true ),				
			'license' => ['value' => '' , 'autoload' => true],					
			
			'get_customer_location' => array( 'value' => 1, 'autoload' => true ),	
			'website_type' => array( 'value' => 'store', 'autoload' => true ),	
			
			'inventory_management' => ['value' => array('enable' => 0), 'autoload' => true],		
			'languages' => ['value' => [], 'autoload' => true],	
			'sales_area' => ['value' => [], 'autoload' => true],	
			
			'registration_upon_purchase' => array( 'value' => 'not_require', 'autoload' => true ),
			
			'cache_menu' => array( 'value' => 0, 'autoload' => true ),	
			'default_menu_category' => array('value' => '', 'autoload' => true ),	
			
			'options_earning_marketplace' => ['value' => '', 'autoload' => true],	//Как будем зарабатывать		
		);	
		return $add_option;
	}
	
	protected static function add_option() 
	{
		global $wpdb;	
		$options = self::get_options();
		
		$autoload_yes = [];
		$autoload_no = [];
		foreach( (array)$options as $key => $option )
		{
			$option_key = USAM_OPTION_PREFIX.$key;
			if ( !add_option($option_key, $option['value'], '', $option['autoload']) )
			{
				if ( $option['autoload'] )
					$autoload_yes[] = $option_key;
				else
					$autoload_no[] = $option_key;
			}
		}				
		$options = self::get_global_options();
		foreach( (array)$options as $key => $option )
		{
			$option_key = USAM_OPTION_PREFIX.$key;
			if ( !add_site_option($option_key, $option['value'], '', $option['autoload']) )
			{
				if ( $option['autoload'] )
					$autoload_yes[] = $option_key;
				else
					$autoload_no[] = $option_key;
			}
		}	
		if ( $autoload_yes )
			$wpdb->query( "UPDATE `$wpdb->options` SET `autoload` = 'yes' WHERE `option_name` IN ('".implode("','",$autoload_yes)."') AND `autoload` = 'no'");
		elseif ( $autoload_no )
			$wpdb->query( "UPDATE `$wpdb->options` SET `autoload` = 'no' WHERE `option_name` IN ('".implode("','",$autoload_no)."') AND `autoload` = 'yes'");
	}
	
	protected static function get_global_options() 
	{
		return [	
			//Изображения
			'crop_thumbnails' => ['value' => 0, 'autoload' => true],	//Обрезать миниатюры	
			'rename_attacment' => ['value' => 0, 'autoload' => true],		
			'format_file_name_attacment' => ['value' => 'post_name' , 'autoload' => false],	
			'format_file_title_attacment' => ['value' => 'post_title [sku]' , 'autoload' => false],		
			'image_quality' => ['value' => 100, 'autoload' => true],				
			'product_image' => ['value' => ['width'  => 300, 'height' => 300] , 'autoload' => true],	//размеры для изображений			
			'single_view_image' => ['value' => ['width'  => 600, 'height' => 600] , 'autoload' => true],	
			'uses_coupons' => ['value' => 1 , 'autoload' => true],
			'uses_bonuses' => ['value' => 1 , 'autoload' => true],		
			'hide_addtocart_button' => ['value' => 0 , 'autoload' => true],	
			'popup_adding_to_cart' => ['value' => 'popup', 'autoload' => true],	
			'name_addtocart_button' => ['value' => __('В корзину', 'usam'), 'autoload' => true],
			'under_order_button' => ['value' => 0 , 'autoload' => true],		
		];
	}
	
	protected static function get_templates( $type )
	{	
		$files = usam_list_dir( USAM_FILE_PATH . '/admin/db/templates/'. $type, 'path' );
		return $files;
	}	
	
	
	protected static function trigger_email() 
	{		
		require_once( USAM_FILE_PATH . '/includes/exchange/save-object-settings.class.php' );
		require_once( USAM_FILE_PATH . '/admin/includes/mail/usam_edit_mail.class.php' );			
		$files = self::get_templates('newsletter');
		foreach( $files as $file )
		{
			$c = new USAM_Read_Object_Settings();
			$data = $c->get_settings( $file );
			$id = usam_insert_newsletter( $data );
			if ( isset($data['settings']) )
			{
				if( usam_update_newsletter_metadata( $id, 'settings', $data['settings'] ) )
				{					
					$mail = new USAM_Edit_Newsletter( $id );
					$mail->save_mailcontent(); 		
				}	
			}
			if( isset($data['event_start']) )
				usam_update_newsletter_metadata( $id, 'event_start', $data['event_start'] );
			if( isset($data['conditions']) )
				usam_update_newsletter_metadata( $id, 'conditions', $data['conditions'] );
		}			
	}
	
	public static function add_role() 
	{	
		require_once( USAM_FILE_PATH . '/includes/customer/capabilities_schema.php' );		
	}
	
	// Создает директории в папке UPLOAD
	public static function create_upload_directories() 
	{
		$folders = array(
			array( 'dir' => USAM_UPLOAD_DIR, 'mode' => 0775, 'htaccess' => 0 ),
			array( 'dir' => USAM_FILE_DIR, 'mode' => 0775, 'htaccess' => 1 ),
			array( 'dir' => USAM_DOCUMENTS_DIR, 'mode' => 0775, 'htaccess' => 1 ),		
			array( 'dir' => USAM_EXCHANGE_DIR, 'mode' => 0775, 'htaccess' => 1 ),
			array( 'dir' => USAM_UPLOAD_DIR.'Log/', 'mode' => 0775, 'htaccess' => 1 ),		
			array( 'dir' => USAM_UPLOAD_DIR.'order_invoices/', 'mode' => 0775, 'htaccess' => 1 ),
			array( 'dir' => USAM_BACKUP_DIR, 'mode' => 0775, 'htaccess' => 1 ),
		);
		$htaccess = "order deny,allow\n\r";
		$htaccess .= "deny from all\n\r";
		$htaccess .= "allow from none\n\r";
		foreach ( $folders as $folder ) 
		{ 
			wp_mkdir_p( $folder['dir'] );		
			@ chmod($folder['dir'], $folder['mode']);
			$filename = $folder['dir'] . ".htaccess";	
			if ( $folder['htaccess'] && !is_file($filename) ) 
			{			
				$file_handle = @ fopen($filename, 'w+');
				if ( $file_handle )
				{
					@ fwrite($file_handle, $htaccess);
					@ fclose($file_handle);
					@ chmod($filename, 0665);
				}
			}
		}	
	}
	
	/**
	 * Создание или изменение таблиц базы данных
	 */
	public static function create_or_update_tables( $table = [] )
	{
		global $wpdb;	
		
		set_time_limit(40000);
		include( USAM_FILE_PATH . '/admin/db/database.php' );
		$template_hash = sha1( serialize( $database_template ) );
	// Фильтр для добавления или изменения шаблона базы данных, убедитесь, ваша функция вернет массив, иначе обновления таблиц базы данных не состоится
		$database_template = apply_filters( 'usam_alter_database_template', $database_template );

		$upgrade_failed = false;
		foreach ( (array)$database_template as $table_name => $table_data )
		{
			if ( !empty($table) && !in_array($table_name, $table))
				continue;
			
			$show_table = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );
			// убедитесь, что таблица не существует под правильным именем, а затем проверить, есть ли предыдущее название, если есть, проверьте таблицы под этим именем тоже
			if ( !$show_table && (!isset($table_data['previous_names']) || (isset($table_data['previous_names']) && !$wpdb->get_var("SHOW TABLES LIKE '{$table_data['previous_names']}'")) ) ) 
			{
				$constructed_sql_parts = array( );
				$constructed_sql = "CREATE TABLE `{$table_name}` (\n";

				foreach ( (array)$table_data['columns'] as $column => $properties ) {
					$constructed_sql_parts[] = "`$column` $properties";
				}
				if ( !empty($table_data['indexes']) )
				{
					foreach ( (array)$table_data['indexes'] as $properties ) {
						$constructed_sql_parts[] = "$properties";
					}					
				}	
				$constructed_sql .= implode( ",", $constructed_sql_parts );
				$constructed_sql .= "\n) ENGINE=MyISAM";
				$constructed_sql .= " CHARSET=$wpdb->charset";	
				$constructed_sql .= " COLLATE=$wpdb->collate";	
				$constructed_sql .= ";";

				if ( !$wpdb->query( $constructed_sql ) ) {
					$upgrade_failed = true;
					self::$errors[] = $wpdb->last_error;
				}
				if ( !empty($table_data['alter']) )
				{
					foreach ( (array)$table_data['alter'] as $alter )
						$wpdb->query( "ALTER TABLE `{$table_name}` {$alter}" );
				}
				if ( isset($table_data['actions']['after']['all'] ) && is_callable( $table_data['actions']['after']['all'] ) ) {
					$table_data['actions']['after']['all']();
				}
			} 
			else 
			{   // проверить, если новое имя таблицы в использовании
				if ( !$show_table && (isset($table_data['previous_names']) && $wpdb->get_var( "SHOW TABLES LIKE '{$table_data['previous_names']}'" )) ) 
				{
					$wpdb->query( "ALTER TABLE	`{$table_data['previous_names']}` RENAME TO `{$table_name}`;" );
					self::$errors[] = $wpdb->last_error;
				}				
				$existing_table_columns = array( );
				//проверить и, возможно, обновить кодировку символов			
				$table_status_data = $wpdb->get_row( "SHOW TABLE STATUS LIKE '$table_name'", ARRAY_A );					
				if ( $table_status_data['Collation'] != $wpdb->collate ) 
					$wpdb->query( "ALTER TABLE `$table_name` DEFAULT CHARACTER SET {$wpdb->charset} COLLATE ".$wpdb->collate );
				if ( isset($table_data['actions']['before']['all'] ) && is_callable( $table_data['actions']['before']['all'] ) ) {
					$table_data['actions']['before']['all']();
				}			
				$existing_table_column_data = $wpdb->get_results( "SHOW FULL COLUMNS FROM `$table_name`", ARRAY_A );

				foreach ( (array)$existing_table_column_data as $existing_table_column ) 
				{
					$column_name = $existing_table_column['Field'];
					$existing_table_columns[] = $column_name;

					$null_match = false;
					if ( $existing_table_column['Null'] = 'NO' ) {
						if ( isset($table_data['columns'][$column_name] ) && stristr( $table_data['columns'][$column_name], "NOT NULL" ) !== false ) {
							$null_match = true;
						}
					} else {
						if ( isset($table_data['columns'][$column_name] ) && stristr( $table_data['columns'][$column_name], "NOT NULL" ) === false ) {
							$null_match = true;
						}
					}
					if ( isset($table_data['columns'][$column_name] ) && ((stristr( $table_data['columns'][$column_name], $existing_table_column['Type'] ) === false) || ($null_match != true)) ) 
					{
						if ( isset($table_data['actions']['before'][$column_name] ) && is_callable( $table_data['actions']['before'][$column_name] ) )
							$table_data['actions']['before'][$column_name]( $column_name );						
						if ( !$wpdb->query( "ALTER TABLE `$table_name` CHANGE `$column_name` `$column_name` {$table_data['columns'][$column_name]} " ) ) 
						{
							$upgrade_failed = true;
							self::$errors[] = $wpdb->last_error;
						}
					}
				}
				$supplied_table_columns = array_keys( $table_data['columns'] );
				// сравните поставленные и существующие столбцы, чтобы найти различия
				$missing_or_extra_table_columns = array_diff( $supplied_table_columns, $existing_table_columns );
			//	$missing_or_extra_table_columns = array_diff( $existing_table_columns, $supplied_table_columns );
				if ( count( $missing_or_extra_table_columns ) > 0 ) 
				{
					foreach ( (array)$missing_or_extra_table_columns as $missing_or_extra_table_column ) 
					{
						if ( isset($table_data['columns'][$missing_or_extra_table_column] ) ) {
							//table column is missing, add it
							$index = array_search( $missing_or_extra_table_column, $supplied_table_columns ) - 1;

							$previous_column = isset($supplied_table_columns[$index]) ? $supplied_table_columns[$index] : '';
							if ( $previous_column != '' ) {
								$previous_column = "AFTER `$previous_column`";
							}
							$constructed_sql = "ALTER TABLE `$table_name` ADD `$missing_or_extra_table_column` ".$table_data['columns'][$missing_or_extra_table_column]." $previous_column;";
							if ( !$wpdb->query( $constructed_sql ) ) 
							{
								$upgrade_failed = true;
								self::$errors[] = $wpdb->last_error;
							}
							// запустить обновление функции
							if ( isset($table_data['actions']['after'][$missing_or_extra_table_column] ) && is_callable( $table_data['actions']['after'][$missing_or_extra_table_column] ) ) {
								$table_data['actions']['after'][$missing_or_extra_table_column]( $missing_or_extra_table_column );
							}
						}
					}
				}
				if ( isset($table_data['actions']['after']['all'] ) && is_callable( $table_data['actions']['after']['all'] ) ) {
					$table_data['actions']['after']['all']();
				}
				// получить список существующих индексов
				$existing_table_index_data = $wpdb->get_results( "SHOW INDEX FROM `$table_name`", ARRAY_A );
				$existing_table_indexes = array( );
				foreach ( $existing_table_index_data as $existing_table_index ) {
					$existing_table_indexes[] = $existing_table_index['Key_name'];
				}
				$existing_table_indexes = array_unique( $existing_table_indexes );
				$supplied_table_indexes = array_keys( $table_data['indexes'] );

				// сравните поставляемые и существующие индексы, чтобы найти различия
				$missing_or_extra_table_indexes = array_diff( $supplied_table_indexes, $existing_table_indexes );
				//$missing_or_extra_table_indexes = array_diff( $existing_table_indexes, $supplied_table_indexes );
				if ( count( $missing_or_extra_table_indexes ) > 0 ) 
				{
					foreach ( $missing_or_extra_table_indexes as $missing_or_extra_table_index ) 
					{
						if ( isset($table_data['indexes'][$missing_or_extra_table_index] ) ) 
						{
							$constructed_sql = "ALTER TABLE `$table_name` ADD " . $table_data['indexes'][$missing_or_extra_table_index] . ";";
							if ( !$wpdb->query( $constructed_sql ) ) 
							{
								$upgrade_failed = true;
								self::$errors[] = $wpdb->last_error;
							}
						}
					}
				}
			}
		}
		if ( $upgrade_failed !== true )			
			return true;	 
		else
			return false;
	}
}