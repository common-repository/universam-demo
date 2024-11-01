<?php
class USAM_Admin_Menu
{	
	protected static $_instance = null;
	function __construct( )
	{
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) 
		{ 	
			if ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'inline-save' )
				add_action( 'admin_init', [$this, 'display_products_list'], 2 );
		}
		add_action( 'admin_menu', [$this, 'admin_menu_pages']);
	}		
		
	function load_edit()
	{
		$screen = get_current_screen();	
		if( $screen->post_type == 'usam-product' )	
		{
			$this->display_products_list(); 	
			add_filter('screen_settings', [$this, 'screen_settings'], 100, 2  );	
		}
	}
	
	function display_products_list()
	{
		$screen = get_current_screen();				
		if ( empty($screen) )
			return false;
			
		$page_view_option = get_user_option( 'usam_page_view_options' );		
		if ( empty($page_view_option) )		
			$page_view_option = [];		
		if ( empty($page_view_option[$screen->id]) )
			$page_view_option[$screen->id] = 'manager';				
		switch ( $page_view_option[$screen->id] ) 
		{
			case 'stocks' :
				require_once( USAM_FILE_PATH . '/admin/display-products-stocks.php' );
				new USAM_Display_Products_Stocks(); 
			break;		
			case 'regions' :
				require_once( USAM_FILE_PATH . '/admin/display-products-regions.php' );
				new USAM_Display_Products_Regions(); 
			break;				
			default:
				require_once( USAM_FILE_PATH . '/admin/display-items.page.php' );
				new USAM_Display_Product_Page(); 	
			break;	
		}
	}
	
	public function screen_settings( $screen_settings, $t ) 
	{ 
		$type_price = usam_get_manager_type_price();		
		$page_view_options = get_user_option( 'usam_page_view_options' );		
		$screen = get_current_screen();		
		$page_view_option = !empty($page_view_options[$screen->id])?$page_view_options[$screen->id]:'manager';			
		return 
		"<input type='hidden' name='screen_id' value='$screen->id' />
		<fieldset class='viewing'>
			<legend>".__("Вариант просмотра", "usam")."</legend>
			<div class='viewing_options'>
				<div class='viewing_options__item'><input name='page_view_option' type='radio' ".checked($page_view_option,'manager', false)." value='manager'>".__("Список для управления", "usam")."</div>
				<div class='viewing_options__item'><input name='page_view_option' type='radio' ".checked($page_view_option,'stocks', false)." value='stocks'>".__("Остатки по складам", "usam")."</div>
				<div class='viewing_options__item'><input name='page_view_option' type='radio' ".checked($page_view_option,'regions', false)." value='regions'>".__("Остатки по регионам", "usam")."</div>
			</div>
		</fieldset>
		<fieldset class='viewing'>
			<legend>".__("Тип цены просмотра каталога", "usam")."</legend>
			<div class='viewing_options'>
				".usam_get_select_prices( $type_price )."
			</div>
		</fieldset>";
	}
	
	function init_page_tabs( )
	{			
		USAM_Page_Tabs::init();
		self::$_instance = USAM_Page_Tabs::get_instance();		
	}	
	
	function display_page_tabs( )
	{
		if ( !is_null( self::$_instance ) )
		{
			self::$_instance->display();
		}			
	}	
	
	function add_menu_page_tabs( )
	{		
		do_action( 'usam_add_submenu-before' );
		$function = [$this,'display_page_tabs'];	
		
		$admin_menu = usam_get_admin_menu();	
		foreach ( $admin_menu as $menu_slug => $menu )
		{			
			if ( !isset($menu['submenu']) )
				continue;
			if ( isset($menu['toplevel']) )
			{
				if ( current_user_can($menu['toplevel']['capability']) )	
				{
					foreach ( $menu['submenu'] as $submenu )
					{							
						$cap = apply_filters( 'usam_submenu_page_cap', $submenu['capability'] );
						if ( current_user_can( $cap ) )		
						{ 			
							$menu_slug = $submenu['menu_slug'];
							break;
						}
					}	
					$menu_page_hook = add_menu_page( $menu['toplevel']['page_title'], $menu['toplevel']['menu_title'], $menu['toplevel']['capability'], $menu_slug, $function, $menu['toplevel']['icon'], $menu['toplevel']['position']);
				}
			}
			foreach ( $menu['submenu'] as $submenu )
			{							
				$cap = apply_filters( 'usam_submenu_page_cap', $submenu['capability'] );
				if ( current_user_can( $cap ) )		
				{ 			
					$page_hook = add_submenu_page( $menu_slug, $submenu['page_title'], $submenu['menu_title'], $cap, $submenu['menu_slug'], $function );	
					add_action( 'load-'.$page_hook, [$this, 'init_page_tabs']);
				}
			}			
		}	
		do_action( 'usam_add_submenu-after' ); 
	}	
	
	function admin_menu_pages() 
	{ 
		$this->add_menu_page_tabs( );		
		add_action( 'load-edit.php', array($this, 'load_edit') ); // Загрузка списка товаров		
	}
}
$admin_menu = new USAM_Admin_Menu();


function usam_get_admin_menu() 
{		
	if ( usam_check_license() )
	{
		$menu['orders']['toplevel'] = ['page_title' => __('Бизнес','usam'), 'menu_title' => __('Бизнес','usam'), 'icon' => "dashicons-cart",  'position' => "27", 'capability' => 'store_section'];
		$menu['orders']['submenu'] = [
			['page_title' => __('Центр управления продажами', 'usam'), 'menu_title' => __('Продажи', 'usam'), 'capability' => 'view_orders', 'menu_slug' => 'orders'],			
		];
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
		{
			$menu['orders']['submenu'][] = ['page_title' => __('Маркетплейс', 'usam'), 'menu_title' => __('Маркетплейс', 'usam'), 'capability' => 'view_marketplace', 'menu_slug' => 'marketplace'];
		}		
		$menu['orders']['submenu'] = array_merge( $menu['orders']['submenu'], [
			['page_title' => __('Центр управления доставкой', 'usam'), 'menu_title' => __('Доставка', 'usam'), 'capability' => 'view_delivery', 'menu_slug' => 'delivery'],	
			['page_title' => __('Контакт центр', 'usam'), 'menu_title' => __('Контакт центр', 'usam'), 'capability' => 'view_feedback', 'menu_slug' => 'feedback'],	
			['page_title' => __('Задачи и проекты', 'usam'), 'menu_title' => __('Задачи и проекты', 'usam'), 'capability' => 'view_personnel', 'menu_slug' => 'personnel'],	
			['page_title' => __('CRM', 'usam'), 'menu_title' => __('CRM', 'usam'), 'capability' => 'view_crm','menu_slug' => 'crm'],
			['page_title' => __('Закупки', 'usam'), 'menu_title' => __('Закупки', 'usam'), 'capability' => 'view_procurement', 'menu_slug' => 'procurement'],
			['page_title' => __('Журнал документов', 'usam'), 'menu_title' => __('Документы', 'usam'), 'capability' => 'view_documents', 'menu_slug' => 'documents'],				
			['page_title' => __('Компания', 'usam'), 'menu_title' => __('Компания', 'usam'), 'capability' => 'view_site_company', 'menu_slug' => 'site_company'],	
			['page_title' => __('Библиотека файлов', 'usam'), 'menu_title' => __('Библиотека файлов', 'usam'), 'capability' => 'view_files', 'menu_slug' => 'files'],				
			['page_title' => __('Бухгалтерия компании', 'usam'), 'menu_title' => __('Бухгалтерия', 'usam'), 'capability' => 'view_bookkeeping',  'menu_slug' => 'bookkeeping'],		
			['page_title' => __('Программы лояльности', 'usam'), 'menu_title' => __('Скидки', 'usam'), 'capability' => 'manage_prices', 'menu_slug' => 'manage_discounts'],		
			['page_title' => __('Центр управления ценами', 'usam'), 'menu_title' => __('Цены', 'usam'), 'capability' => 'manage_prices', 'menu_slug' => 'manage_prices'],	
			['page_title' => __('Учет и управление складскими запасами', 'usam'), 'menu_title' => __('Складской учет', 'usam'), 'capability' => 'view_inventory_control', 'menu_slug' => 'storage'],	
			['page_title' => __('Конструктор дизайна', 'usam'), 'menu_title' => __('Дизайн', 'usam'), 'capability' => 'view_interface', 'menu_slug' => 'interface'],		
			['page_title' => __('Экспорт / Импорт', 'usam'), 'menu_title' => __('Экспорт / Импорт', 'usam'), 'capability' => 'view_exchange', 'menu_slug' => 'exchange']
		]);
		$menu['orders']['submenu'][] = ['page_title' => __('Автоматизация бизнес процессов', 'usam'), 'menu_title' => __('Автоматизация', 'usam'), 'capability' => 'view_automation', 'menu_slug' => 'automation'];
		$menu['applications']['toplevel'] = ['page_title' => __('Приложения','usam'), 'menu_title' => __('Приложения','usam'), 'icon' => "dashicons-bank",  'position' => "33", 'capability' => 'applications_section'];
		$menu['applications']['submenu'] = [			
			['page_title' => __('Приложения', 'usam'), 'menu_title' => __('Приложения', 'usam'), 'capability' => 'view_applications', 'menu_slug' => 'applications'],			
		];
		$menu['marketing']['toplevel'] = ['page_title' => __('Центр продвижения сайта','usam'), 'menu_title' => __('Продвижение','usam'), 'icon' => "dashicons-chart-bar", 'position' => "29", 'capability' => 'marketing_section'];	
		$menu['marketing']['submenu'] = [						
			['page_title' => __('Кабинет маркетолога', 'usam'), 'menu_title' => __('Маркетинг', 'usam'), 'capability' => 'view_marketing',  'menu_slug' => 'marketing'],
			['page_title' => __('Центр управления социальными сетями','usam'), 'menu_title' => __('Социальные сети','usam'), 'capability' => 'view_social_networks', 'menu_slug' => 'social_networks'],
			['page_title' => __('Центр управления рассылками', 'usam'), 'menu_title' => __('Рассылка', 'usam'), 'capability' => 'view_newsletter',  'menu_slug' => 'newsletter'],
			['page_title' => __('Поисковая оптимизация', 'usam'), 'menu_title' => __('SEO', 'usam'), 'capability' => 'view_seo', 'menu_slug' => 'seo'],	
		];
		if ( !usam_is_multisite() || is_main_site() )
		{		
			$menu['report']['toplevel'] = ['page_title' => __('Центр отчетов и анализа','usam'), 'menu_title' => __('Аналитика','usam'), 'icon' => "dashicons-chart-area", 'position' => "30", 'capability' => 'view_reports'];	
			//view_report_constructor
			$menu['report']['submenu'] = [					
				['page_title' => __('Конструктор отчетов и аналитика', 'usam'), 'menu_title' => __('Отчеты', 'usam'), 'capability' => 'view_reports', 'menu_slug' => 'reports'],
				['page_title' => __('Анализ конкурентов', 'usam'), 'menu_title' => __('Конкуренты', 'usam'), 'capability' => 'view_competitor_analysis', 'menu_slug' => 'competitor_analysis'],
			];				
		}
		$menu['options-general.php']['submenu'] = [
			['page_title' => __('Настройки магазина', 'usam'), 'menu_title' => __('Универсам', 'usam'), 'capability' => 'universam_settings',  'menu_slug' => 'shop_settings'],
		];	
		$menu['tools.php']['submenu'] = [ 
			['page_title' => __('Инструменты платформы Универсам', 'usam'), 'menu_title' => __('Универсам', 'usam'), 'capability' => 'shop_tools',  'menu_slug' => 'shop'],			
		];
		$menu['services']['toplevel'] = ['page_title' => __('Услуги','usam'), 'menu_title' => __('Услуги','usam'), 'icon' => "dashicons-welcome-learn-more", 'position' => 99, 'capability' => 'services_section'];
		$menu['services']['submenu'] = [			
			['page_title' => __('Услуги', 'usam'), 'menu_title' => __('Услуги', 'usam'), 'capability' => 'services_section', 'menu_slug' => 'services'],
		];		
		$menu['help']['toplevel'] = ['page_title' => __('Помощь в работе с платформой','usam'), 'menu_title' => __('Помощь','usam'), 'icon' => "dashicons-editor-help", 'position' => usam_is_license_type('FREE') ? 27 : 100, 'capability' => 'help_section'];
		$menu['help']['submenu'] = [			
			['page_title' => __('Помощь', 'usam'), 'menu_title' => __('Помощь', 'usam'), 'capability' => 'help_section', 'menu_slug' => 'help'],
		]; //customization		
	}
	$menu['index.php']['submenu'] = [ 
		['page_title' => __('Подписки на лицензии', 'usam'), 'menu_title' => __('UNIVERSAM', 'usam'), 'capability' => 'manage_options',  'menu_slug' => 'license'],			
	];		
	return apply_filters( 'usam_menu', $menu );
}

function usam_get_page_tabs() 
{  	
	$page_tabs = [
		'orders' => [
			['id' => 'orders',  'title' => usam_get_document_name('order', 'plural_name'), 'capability' => 'view_order_lists'],
			['id' => 'leads',  'title' => usam_get_document_name('lead', 'plural_name'), 'capability' => 'view_lead_lists'],
			['id' => 'subscriptions',  'title' => __('Подписки', 'usam'), 'capability' => 'view_subscriptions'],				
			['id' => 'payment',  'title' => usam_get_document_name('payment', 'plural_name'), 'capability' => 'view_payment_lists'],
			['id' => 'buyer_refunds',  'title' => __('Возвраты', 'usam'), 'capability' => 'view_buyer_refund_lists'],
			['id' => 'checks',  'title' => usam_get_document_name('check', 'plural_name'), 'capability' => 'view_check_lists'],
		],
		'delivery' => [
			['id' => 'delivery_documents',  'title' => __('Доставка', 'usam'), 'capability' => 'view_delivery_documents'],
			['id' => 'couriers',  'title' => __('Курьеры', 'usam'), 'capability' => 'view_couriers'],
		],
		'files' => [
			['id' => 'my_files',  'title' => __('Мои файлы', 'usam'), 'capability' => 'view_my_files'],
			['id' => 'files',  'title' => __('Файлы', 'usam'), 'capability' => 'view_all_files'],	
		],
		'bookkeeping' => [
			['id' => 'payment_orders', 'title' => __('Платежи', 'usam'), 'capability' => 'view_bank_payments'],
			['id' => 'reconciliation_acts', 'title' => usam_get_document_name('reconciliation_act', 'plural_name'), 'capability' => 'view_reconciliation_act_lists'],
			['id' => 'account_transactions', 'title' => __('Внутренние транзакции', 'usam'), 'capability' => 'view_account_transactions'],	
		],	
		'procurement' => [		
			['id' => 'invoice_payment', 'title' => usam_get_document_name('invoice_payment', 'plural_name'), 'capability' => 'view_invoice_payment_lists'],
			['id' => 'order_contractor', 'title' => usam_get_document_name('order_contractor', 'plural_name'), 'capability' => 'view_order_contractor_lists'],			
			['id' => 'proxy', 'title' => usam_get_document_name('proxy', 'plural_name'), 'capability' => 'view_proxy_lists'],							
		],		
		'personnel' => [		
			['id' => 'tasks', 'title' => __('Задания', 'usam'), 'capability' => 'view_tasks'],						
			['id' => 'projects', 'title' => __('Проекты', 'usam'), 'capability' => 'view_project'],			
			['id' => 'notes', 'title' => __('Заметки', 'usam'), 'capability' => 'view_notes'],				
		],
		'site_company' => [		
			['id' => 'decree', 'title' => __('Приказы', 'usam'), 'capability' => 'view_decree'],				
			['id' => 'convocation', 'title' => __('Собрания', 'usam'), 'capability' => 'view_convocation'],			
			['id' => 'employees', 'title' => __('Сотрудники', 'usam'), 'capability' => 'view_employees'],
		],		
		'documents' => [		
			['id' => 'documents', 'title' => __('Документы', 'usam'), 'capability' => 'view_documents'],	
			['id' => 'contracts', 'title' => usam_get_document_name('contract', 'plural_name'), 'capability' => 'view_contract_lists'],			
		],		
		'feedback' => [
			['id' => 'email',  'title' => __('Почта', 'usam'), 'capability' => 'view_email'],
			['id' => 'chat',  'title' => __('Чат', 'usam') , 'capability' => 'view_chat'],	
			['id' => 'contacting', 'title' => __('Обращения', 'usam'), 'capability' => 'view_contacting'],
			['id' => 'reviews',  'title' => __('Отзывы', 'usam'), 'capability' => 'view_reviews'],
			['id' => 'sms',     'title' => __('СМС', 'usam'), 'capability' => 'view_sms'],
			['id' => 'monitor',  'title' => __('Монитор', 'usam'), 'capability' => 'view_monitor'],
		],		
		'crm' => [
			['id' => 'contacts',   'title' => __('Контакты', 'usam'), 'capability' => 'view_contacts'],		
			['id' => 'companies',  'title' => __('Компании', 'usam'), 'capability' => 'view_company'],
			['id' => 'affairs',    'title' => __('Дела', 'usam'), 'capability' => 'view_affairs'],
			['id' => 'suggestions','title' => __('Коммерческие', 'usam'), 'capability' => 'view_suggestion_lists'],
			['id' => 'invoice', 'title' => usam_get_document_name('invoice', 'plural_name'), 'capability' => 'view_invoice_lists'],	
			['id' => 'acts', 'title' => usam_get_document_name('act', 'plural_name'), 'capability' => 'view_act_lists'],		
			['id' => 'pricelist',  'title' => __('Прайс-лист', 'usam'), 'capability' => 'view_pricelist'],
			['id' => 'plan', 'title' => __('План продаж', 'usam'), 'capability' => 'view_plan'],
		],
		'manage_discounts' => [
			['id' => 'discount',  'title' => __('Скидки', 'usam'), 'capability' => 'manage_prices'], 	
			['id' => 'basket',  'title' => __('Корзина', 'usam'), 'capability' => 'view_basket'],
			['id' => 'product_day',  'title' => __('Товар дня', 'usam'), 'capability' => 'view_product_day'],	
			['id' => 'accumulative',  'title' => __('Накопительные', 'usam'), 'capability' => 'view_accumulative'],
			['id' => 'certificates',  'title' => __('Сертификаты', 'usam'), 'capability' => 'view_certificates'],
			['id' => 'coupons',  'title' => __('Купоны', 'usam'), 'capability' => 'view_coupons'],
			['id' => 'loyalty_programs', 'title' => __('Программы лояльности', 'usam'), 'capability' => 'view_rules_bonuses'],			
		],
		'manage_prices' => [
			['id' => 'products', 'title' => __('Управление ценой', 'usam'), 'capability' => 'view_products'],
			['id' => 'rates',    'title' => __('Валютные курсы', 'usam'), 'capability' => 'view_rates'],
			['id' => 'underprice', 'title' => __('Наценки', 'usam'), 'capability' => 'view_underprice'],
			['id' => 'taxes', 'title' => __('Налоги', 'usam'), 'capability' => 'view_taxes'],			
		],
		'storage' => [
			['id' => 'warehouse_documents',   'title' => __('Документы', 'usam'), 'capability' => 'view_warehouse_documents'], 	
			['id' => 'marking_codes','title' => __('Маркировка', 'usam'), 'capability' => 'view_marking_codes'], 				
			['id' => 'sold',       'title' => __('Заказ товара', 'usam'), 'capability' => 'view_sold'],				
			['id' => 'inventory',  'title' => __('Инвентаризация', 'usam'), 'capability' => 'view_inventory'],	
			['id' => 'storage',    'title' => __('Склады', 'usam'), 'capability' => 'view_storage'], 
		],
		'exchange' => [					
			['id' => 'product_importer',  'title' => __('Импорт товаров', 'usam'), 'capability' => 'view_product_importer'],		
			['id' => 'product_exporter',  'title' => __('Экспорт товаров', 'usam'), 'capability' => 'view_product_exporter'],
			['id' => 'parser',  'title' => __('Парсер', 'usam'), 'capability' => 'view_parser'],
			['id' => 'showcases',  'title' => __('Витрины', 'usam'), 'capability' => 'view_showcases'],
			['id' => '1c',  'title' => '1С', 'capability' => 'view_1С'],			
		],
		'applications' => [
			['id' => 'all_applications',  'title' => __('Все приложения', 'usam'), 'capability' => 'view_all_applications'],
			['id' => 'installed_applications',  'title' => __('Установленные', 'usam'), 'capability' => 'view_installed_applications'],
		],
		'automation' => [
			['id' => 'triggers', 'title' => __('Триггеры', 'usam'), 'capability' => 'view_triggers'],		
		//	['id' => 'business_processes', 'title' => __('Бизнес процессы', 'usam'), 'capability' => 'view_business_processes'],	
		],			
		'interface' => [
			['id' => 'sliders',  'title' => __('Слайдер', 'usam'), 'capability' => 'view_interface'],
			['id' => 'webforms',  'title' => __('Веб-формы', 'usam'), 'capability' => 'view_interface'],			
			['id' => 'banners',  'title' => __('Баннеры', 'usam'), 'capability' => 'view_interface'],			
			['id' => 'presentation',  'title' => __('Настройка темы', 'usam'), 'capability' => 'view_interface'],
			['id' => 'html_blocks',  'title' => __('HTML блоки', 'usam'), 'capability' => 'view_interface'],	
			['id' => 'balance_information',  'title' => __('Товар', 'usam'), 'capability' => 'view_interface'],
		],	
		'license' => [
			['id' => 'universam_activation',  'title' => __('Активация Universam', 'usam')],
			['id' => 'licenses',  'title' => __('Лицензии', 'usam')],				
		],
		'shop' => [
			['id' => 'log',  'title' => __('Логи', 'usam')],
			['id' => 'state_system',  'title' => __('Общие состояние', 'usam')],
			['id' => 'update',  'title' => __('Обновление', 'usam')],
			['id' => 'theme_file',  'title' => __('Файлы темы', 'usam')],
			['id' => 'debug',  'title' => __('Отладка', 'usam')], 
			['id' => 'nuke',  'title' => __('Удаление', 'usam')],
			['id' => 'tools',  'title' => __('Инструменты', 'usam')],
		//	['id' => 'sql',  'title' => __('Таблицы в БД', 'usam')],
			['id' => 'backup',  'title' => __('Резервирование', 'usam')],			
		],
		'reports' => [
			//['id' => 'reports',  'title' => __('Готовые отчеты', 'usam')],
			['id' => 'constructor',  'title' => __('Созданные', 'usam'), 'capability' => 'view_report_constructor'],		
			['id' => 'order_report',  'title' => __('Заказы', 'usam'), 'capability' => 'view_order_report', 
				'level' => [			
					['id' => 'warehouse_sales_report',  'title' => __('Продажи по складам', 'usam'), 'capability' => 'view_warehouse_sales_report'],
					['id' => 'coupon_report',  'title' => __('Купоны', 'usam'), 'capability' => 'view_coupon_report'],
					['id' => 'order_products_report',  'title' => __('Товары заказов', 'usam'), 'capability' => 'view_order_products_report'],					
			  ],		
			],	
			['id' => 'payment_report',  'title' => __('Оплаты', 'usam'), 'capability' => 'view_payment_report'],
			['id' => 'payment_received_report',  'title' => __('Поступления', 'usam'), 'capability' => 'view_payment_received_report'],	
			['id' => 'products_report',  'title' => __('Товары', 'usam'), 'capability' => 'view_products_report',  
				'level' => [					
					['id' => 'illiquid_products_report',  'title' => __('Неликвидные товары', 'usam'), 'capability' => 'view_illiquid_products_report'],
					['id' => 'product_work_report',  'title' => __('Публикация товаров', 'usam'), 'capability' => 'view_product_work_report'],							
			  ],	
			],
			['id' => 'metrika_report',  'title' => __('Посещаемость', 'usam'), 'capability' => 'view_metrika_report'], //view_attendance_report
			['id' => 'searching_results_report',  'title' => __('Поиск', 'usam'), 'capability' => 'view_searching_results_report'],
			['id' => 'personnel_report',  'title' => __('Персонал', 'usam'), 'capability' => 'view_personnel_report'],
			['id' => 'buyers_report',  'title' => __('Покупатели', 'usam'), 'capability' => 'view_buyers_report'],		
		],
		'competitor_analysis' => [
			['id' => 'competitors_products', 'title' => __('Товары', 'usam'), 'capability' => 'view_competitors_products'],
			['id' => 'price_analysis', 'title' => __('Цены', 'usam'), 'capability' => 'view_price_analysis'],
		],
		'marketing' => [
			['id' => 'advertising_campaigns',  'title' => __('Реклама', 'usam'), 'capability' => 'view_advertising_campaigns'],	
			['id' => 'trading_platforms',  'title' => __('Маркетплейсы', 'usam'), 'capability' => 'view_platforms'],
			['id' => 'reputation',  'title' => __('Репутация', 'usam'), 'capability' => 'view_reputation'],						
			['id' => 'chat_bots',  'title' => __('Чат-боты', 'usam'), 'capability' => 'view_chat_bots'],							
			['id' => 'crosssell',  'title' => __('Cross-Selling', 'usam'), 'capability' => 'view_crosssell'],
			['id' => 'sets',  'title' => __('Наборы', 'usam'), 'capability' => 'view_sets'],	
		],
		'social_networks' => [
			['id' => 'publishing_rules',  'title' => __('Правила публикаций', 'usam'), 'capability' => 'view_sn_publishing_rules'],	
			['id' => 'products_on_internet', 'title' => __('Товар в интернете', 'usam'), 'capability' => 'view_products_on_internet'],
			['id' => 'vk_products',  'title' => __('вКонтакте', 'usam'), 'capability' => 'view_vk'],	 	
			['id' => 'instagram',  'title' => __('Instagram', 'usam'), 'capability' => 'view_instagram'],	
			['id' => 'pinterest',  'title' => __('Pinterest', 'usam'), 'capability' => 'view_pinterest'],	
			['id' => 'ok_products',  'title' => __('Одноклассники', 'usam'), 'capability' => 'view_odnoklassniki'],	
			['id' => 'facebook', 'title' => __('Facebook', 'usam'), 'capability' => 'view_facebook'],
		],
		'newsletter' => [
			['id' => 'email_newsletters',  'title' => __('Email-рассылки', 'usam'), 'capability' => 'view_sending_email'],
			['id' => 'sms_newsletters',  'title' => __('СМС-рассылки', 'usam'), 'capability' => 'view_sending_sms'],
			['id' => 'lists',  'title' => __('Списки', 'usam'), 'capability' => 'view_lists'], 
		],
		'seo' => [
			['id' => 'positions',  'title' => __('Анализ', 'usam'), 'capability' => 'view_seo_positions'],
			['id' => 'keywords',   'title' => __('Ядро', 'usam'), 'capability' => 'view_keywords'],	
			['id' => 'search_engines',  'title' => __('Вид в поисковиках', 'usam'), 'capability' => 'view_seo_dashboard'],
			['id' => 'sites',  'title' => __('Сайты', 'usam'), 'capability' => 'view_sites'],
			['id' => 'seo_tools', 'title' => __('Инструменты', 'usam'), 'capability' => 'view_product_editor'],	
		],
		'shop_settings' => [
			['id' => 'general',  'title' => __('Главные', 'usam')],	
			['id' => 'admin_menu',  'title' => __('Доступы', 'usam')],	
			['id' => 'blanks',  'title' => __('Бланки', 'usam')],			
			['id' => 'directories',  'title' => __('Справочники', 'usam')],
			['id' => 'notification',  'title' => __('Уведомления', 'usam')],		
			['id' => 'purchase',  'title' => __('Заказ', 'usam')],				
			['id' => 'ftp_settings',  'title' => __('FTP', 'usam')],				
		]
	];
	$page_tabs['services'] = [
		['id' => 'services_sites', 'title' => __('Разработка сайта', 'usam')],
	//	['id' => 'services_design', 'title' => __('Дизайн', 'usam')],
		['id' => 'services_seo', 'title' => __('SEO', 'usam')],
	//	['id' => 'services_advertising', 'title' => __('Контекстная реклама', 'usam')],
	];	
	$page_tabs['help'] = [
		['id' => 'customization', 'title' => __('Настройка шаг за шагом', 'usam')],
		['id' => 'documentation', 'title' => __('Документация', 'usam')],
	];
	if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
	{
		$page_tabs['marketplace'] = [
			['id' => 'sellers',  'title' => __('Продавцы', 'usam'), 'capability' => 'view_marketplace'],
			['id' => 'commissions',  'title' => __('Комиссии', 'usam') , 'capability' => 'view_marketplace'],	
		];
	}
	return apply_filters( 'usam_page_tabs', $page_tabs );
}
?>