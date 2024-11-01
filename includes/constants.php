<?php
// Описание файла: Здесь указываются константы

// Определить отладки переменные для разработчиков
if ( !defined('USAM_DEBUG') )
{
	if( isset($_COOKIE['usam_activate_debug']) && $_COOKIE['usam_activate_debug'] ) 
		define( 'USAM_DEBUG', true );
	else
		define( 'USAM_DEBUG', false );	
}
if ( !defined('USAM_SQL_TIME_ZONE') )
	define('USAM_SQL_TIME_ZONE', false ); 

define( 'USAM_DIR_NAME',  basename( USAM_FILE_PATH ) ); // Имя папки плагина		
define( 'USAM_PLUGINSLUG',  USAM_DIR_NAME.'/universam.php' );

define( 'USAM_APPLICATION_PATH',  USAM_FILE_PATH.'/includes/integration' );			

// Путь к папке с картинками
define( 'USAM_CORE_IMAGES_URL',  USAM_URL . '/assets/images' );
define( 'USAM_CORE_IMAGES_PATH', USAM_FILE_PATH . '/assets/images' );
// JS URL
define( 'USAM_CORE_JS_URL',  USAM_URL . '/assets/js/' );
define( 'USAM_SVG_ICON_URL', USAM_URL . '/assets/sprite.svg' );

// Пути к папке magazine в активной теме
define( 'USAM_THEMES_PATH', get_template_directory() . '/magazine/' );
define( 'USAM_THEME_URL', get_template_directory_uri() . '/magazine/' );
		
// Папка с темой
define( 'USAM_CORE_THEME_PATH', USAM_FILE_PATH . '/theme/' );
define( 'USAM_CORE_THEME_URL' , USAM_URL       . '/theme/' );			

if ( !defined('COOKIEHASH')  )
	wp_cookie_constants();	

define( 'USAM_CUSTOMER_COOKIE', '_usamcustomergid_' . COOKIEHASH );	
define( 'USAM_VISIT_COOKIE', '_usamvisitgid_' . COOKIEHASH );	
define( 'USAM_CUSTOMER_DATA_EXPIRATION', time() + 3600000 );//тысячу дней
define( 'USAM_UNLIMITED_STOCK', 999999 );
define( 'USAM_CONTACT_ONLINE', time() - 1200 );

/**
* Список глобальные констант для таблиц
 */
global $wpdb;

define( 'USAM_META_PREFIX', '_usam_' );              //  префикс для мета данных товаров
define( 'USAM_OPTION_PREFIX', 'usam_' );
$usam_base_prefix = $usam_prefix = $wpdb->prefix.'usam_'; 
if ( usam_is_multisite() )
	$usam_base_prefix = $wpdb->base_prefix.'usam_'; 

define( 'USAM_TABLE_PRODUCTS_BASKET',        "{$usam_base_prefix}products_basket" );
define( 'USAM_TABLE_USERS_BASKET',           "{$usam_base_prefix}users_basket" );	
define( 'USAM_TABLE_USERS_BASKET_META',      "{$usam_base_prefix}users_basket_meta" );	
define( 'USAM_TABLE_DISCOUNT_BASKET',        "{$usam_base_prefix}discount_cart" );	
define( 'USAM_TABLE_COUPON_CODES',           "{$usam_base_prefix}coupon_codes" ); 	
define( 'USAM_TABLE_COUPON_CODE_META',       "{$usam_base_prefix}coupon_code_meta" );
define( 'USAM_TABLE_SEARCHING_RESULTS',      "{$usam_base_prefix}searching_results" );	
define( 'USAM_TABLE_PRODUCTS_ON_INTERNET',   "{$usam_base_prefix}products_on_internet" );

define( 'USAM_TABLE_OBJECT_STATUSES',        "{$usam_base_prefix}order_status" );
define( 'USAM_TABLE_OBJECT_STATUS_META',     "{$usam_base_prefix}object_status_meta" );	

define( 'USAM_TABLE_LEADS',                  "{$usam_base_prefix}leads" );
define( 'USAM_TABLE_LEAD_META',              "{$usam_base_prefix}lead_meta" );
define( 'USAM_TABLE_PRODUCTS_LEAD',          "{$usam_base_prefix}products_lead" );
define( 'USAM_TABLE_TAX_PRODUCT_LEAD',       "{$usam_base_prefix}tax_product_lead" );
define( 'USAM_TABLE_DOCUMENT_DISCOUNTS',     "{$usam_base_prefix}document_discounts" );

define( 'USAM_TABLE_ORDERS',                 "{$usam_base_prefix}orders" );
define( 'USAM_TABLE_ORDER_META',             "{$usam_base_prefix}order_meta" );
define( 'USAM_TABLE_SHIPPED_PRODUCTS',       "{$usam_base_prefix}shipped_products" );	
define( 'USAM_TABLE_SHIPPED_DOCUMENTS',      "{$usam_base_prefix}shipped_documents" );	
define( 'USAM_TABLE_SHIPPED_DOCUMENT_META',  "{$usam_base_prefix}shipped_document_meta" );		
define( 'USAM_TABLE_PAYMENT_HISTORY',        "{$usam_base_prefix}payment_history" );	
define( 'USAM_TABLE_PAYMENT_HISTORY_META',   "{$usam_base_prefix}payment_history_meta" );	
define( 'USAM_TABLE_PRODUCTS_ORDER',         "{$usam_base_prefix}products_order" );
define( 'USAM_TABLE_PRODUCT_ORDER_META',     "{$usam_base_prefix}product_order_meta" );
define( 'USAM_TABLE_TAX_PRODUCT_ORDER',      "{$usam_base_prefix}tax_product_order" );
define( 'USAM_TABLE_CHANGE_HISTORY',         "{$usam_base_prefix}change_history" );	
define( 'USAM_TABLE_DOWNLOAD_STATUS',        "{$usam_base_prefix}download_status" );	
define( 'USAM_TABLE_SUBSCRIPTIONS',          "{$usam_base_prefix}subscriptions" );
define( 'USAM_TABLE_SUBSCRIPTION_META',      "{$usam_base_prefix}subscription_meta" );
define( 'USAM_TABLE_SUBSCRIPTION_PRODUCTS',  "{$usam_base_prefix}subscription_products" );
define( 'USAM_TABLE_SUBSCRIPTION_RENEWAL',   "{$usam_base_prefix}subscription_renewal" );

define( 'USAM_TABLE_COUNTRY',                "{$usam_base_prefix}country" );	
define( 'USAM_TABLE_CURRENCY',               "{$usam_base_prefix}currency_list" );	
define( 'USAM_TABLE_CURRENCY_RATES',         "{$usam_base_prefix}currency_rates" );			
define( 'USAM_TABLE_LOCATION',    			 "{$usam_base_prefix}location" );	
define( 'USAM_TABLE_LOCATION_META',    	     "{$usam_base_prefix}location_meta" );	
define( 'USAM_TABLE_LOCATION_TYPE',    	     "{$usam_base_prefix}location_type" );		
define( 'USAM_TABLE_LOCATIONS_DISTANCE',     "{$usam_base_prefix}locations_distance" ); 
define( 'USAM_TABLE_TAXES',     	         "{$usam_base_prefix}taxes" );	
define( 'USAM_TABLE_TERM_META',              "{$usam_base_prefix}term_meta" );
define( 'USAM_TABLE_STOCK_BALANCES',         "{$usam_base_prefix}stock_balances" );
define( 'USAM_TABLE_PRODUCT_PRICE',          "{$usam_base_prefix}product_price" );
define( 'USAM_TABLE_PRODUCT_META',           "{$usam_base_prefix}product_meta" );
define( 'USAM_TABLE_POST_META',              "{$usam_base_prefix}post_meta" );
define( 'USAM_TABLE_PRODUCT_MARKING_CODES',  "{$usam_base_prefix}product_marking_codes" );
define( 'USAM_TABLE_DISCOUNT_RULES',         "{$usam_base_prefix}discount_rules" );
define( 'USAM_TABLE_DISCOUNT_RULE_META',     "{$usam_base_prefix}discount_rule_meta" );
define( 'USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS', "{$usam_base_prefix}product_discount_relationships" );	
define( 'USAM_TABLE_ASSOCIATED_PRODUCTS',    "{$usam_base_prefix}associated_products" );
define( 'USAM_TABLE_CUSTOM_PRODUCT_TABS',    "{$usam_base_prefix}custom_product_tabs" );
define( 'USAM_TABLE_PRODUCT_DAY',            "{$usam_base_prefix}product_day" );		
define( 'USAM_TABLE_STORAGES',           "{$usam_base_prefix}storage_list" );	
define( 'USAM_TABLE_STORAGE_META',           "{$usam_base_prefix}storage_meta" );	
define( 'USAM_TABLE_STOCK_MANAGEMENT_DATA',  "{$usam_base_prefix}stock_management_data" );
define( 'USAM_TABLE_DATA_ORDER_PRODUCTS',    "{$usam_base_prefix}data_order_products" );
define( 'USAM_TABLE_SETS',                   "{$usam_base_prefix}sets" );
define( 'USAM_TABLE_SET_META',               "{$usam_base_prefix}set_meta" );
define( 'USAM_TABLE_PRODUCTS_SETS',          "{$usam_base_prefix}products_sets" );

define( 'USAM_TABLE_MAILBOX',                "{$usam_base_prefix}mailbox" );
define( 'USAM_TABLE_MAILBOX_META',           "{$usam_base_prefix}mailbox_meta" );
define( 'USAM_TABLE_MAILBOX_USERS',          "{$usam_base_prefix}mailbox_users" );
define( 'USAM_TABLE_LOADED_MESSAGES_LOG',    "{$usam_base_prefix}loaded_messages_log" );
define( 'USAM_TABLE_EMAIL',                  "{$usam_base_prefix}email" );
define( 'USAM_TABLE_EMAIL_META',             "{$usam_base_prefix}email_meta" );
define( 'USAM_TABLE_EMAIL_RELATIONSHIPS',    "{$usam_base_prefix}email_relationships" );
define( 'USAM_TABLE_EMAIL_FILTERS',          "{$usam_base_prefix}email_filters" );
define( 'USAM_TABLE_EMAIL_FOLDERS',          "{$usam_base_prefix}email_folders" );	
define( 'USAM_TABLE_SIGNATURES',             "{$usam_base_prefix}signatures" );
define( 'USAM_TABLE_SMS',                    "{$usam_base_prefix}sms" );		
define( 'USAM_TABLE_CALLS',                  "{$usam_base_prefix}calls" );
define( 'USAM_TABLE_CHAT_BOT_TEMPLATES',     "{$usam_base_prefix}chat_bot_templates" );
define( 'USAM_TABLE_CHAT_BOT_COMMANDS',      "{$usam_base_prefix}chat_bot_commands" );
define( 'USAM_TABLE_CHAT_BOT_COMMAND_META',  "{$usam_base_prefix}chat_bot_command_meta" );
define( 'USAM_TABLE_BONUS_CARDS',            "{$usam_base_prefix}bonus_cards" );
define( 'USAM_TABLE_BONUS_TRANSACTIONS',     "{$usam_base_prefix}customer_bonuses" );
define( 'USAM_TABLE_CUSTOMER_REVIEWS',       "{$usam_base_prefix}customer_reviews" );
define( 'USAM_TABLE_CUSTOMER_REVIEW_META',   "{$usam_base_prefix}customer_review_meta" );		
define( 'USAM_TABLE_CHAT',                  "{$usam_base_prefix}chat" );
define( 'USAM_TABLE_CHAT_DIALOGS',          "{$usam_base_prefix}chat_dialogs" );
define( 'USAM_TABLE_CHAT_USERS',            "{$usam_base_prefix}chat_users" );
define( 'USAM_TABLE_CHAT_MESSAGE_STATUSES', "{$usam_base_prefix}chat_dialogs_statuses" );


define( 'USAM_TABLE_CONTACTINGS',           "{$usam_base_prefix}contactings" );		
define( 'USAM_TABLE_CONTACTING_META',       "{$usam_base_prefix}contacting_meta" );	
define( 'USAM_TABLE_EVENTS',                "{$usam_base_prefix}events" );		
define( 'USAM_TABLE_EVENT_META',            "{$usam_base_prefix}event_meta" );	
define( 'USAM_TABLE_EVENT_ACTION_LIST',     "{$usam_base_prefix}event_action_list" );	
define( 'USAM_TABLE_EVENT_USERS',           "{$usam_base_prefix}event_users" );	
define( 'USAM_TABLE_SOCIAL_NETWORK_PROFILES',"{$usam_base_prefix}social_network_profiles" );
define( 'USAM_TABLE_SOCIAL_NETWORK_PROFILE_META',"{$usam_base_prefix}social_network_profile_meta" );
define( 'USAM_TABLE_NOTIFICATION_RELATIONSHIPS', "{$usam_base_prefix}notification_relationships" );	
define( 'USAM_TABLE_NOTIFICATIONS',          "{$usam_base_prefix}notifications" );
define( 'USAM_TABLE_NOTES',                 "{$usam_base_prefix}notes" );
	
define( 'USAM_TABLE_COMMENTS',              "{$usam_base_prefix}comments" );	
define( 'USAM_TABLE_COMMENT_META',          "{$usam_base_prefix}comment_meta" );
define( 'USAM_TABLE_RIBBON',                "{$usam_base_prefix}ribbon" );
define( 'USAM_TABLE_RIBBON_META',           "{$usam_base_prefix}ribbon_meta" );
define( 'USAM_TABLE_RIBBON_LINKS',          "{$usam_base_prefix}ribbon_links" );	
	
define( 'USAM_TABLE_CONTACTS',               "{$usam_base_prefix}contacts_list" );	
define( 'USAM_TABLE_CONTACT_META',           "{$usam_base_prefix}contact_meta" );
define( 'USAM_TABLE_CONTACT_ADDRESS',        "{$usam_base_prefix}contact_address" );
define( 'USAM_TABLE_DEPARTMENTS',            "{$usam_base_prefix}departments" );
define( 'USAM_TABLE_SALES_PLAN',             "{$usam_base_prefix}sales_plan" );	
define( 'USAM_TABLE_PLAN_AMOUNTS',           "{$usam_base_prefix}plan_amounts" );
define( 'USAM_TABLE_USER_POSTS',             "{$usam_base_prefix}user_posts" ); 
define( 'USAM_TABLE_USER_POST_LISTS',        "{$usam_base_prefix}user_posts_lists" ); 
define( 'USAM_TABLE_USER_SELLERS',           "{$usam_base_prefix}user_sellers" );
define( 'USAM_TABLE_VISITS',                 "{$usam_base_prefix}visits" );	
define( 'USAM_TABLE_VISIT_META',             "{$usam_base_prefix}visit_meta" );	
define( 'USAM_TABLE_PAGE_VIEWED',            "{$usam_base_prefix}page_viewed" );	
define( 'USAM_TABLE_COMPANY',                "{$usam_base_prefix}company" );	
define( 'USAM_TABLE_COMPANY_META',           "{$usam_base_prefix}company_meta" );			
define( 'USAM_TABLE_COMPANY_ACC_NUMBER',     "{$usam_base_prefix}company_acc_number" );	
define( 'USAM_TABLE_COMPANY_FINANCE',        "{$usam_base_prefix}company_finance" );
define( 'USAM_TABLE_COMPANY_CONNECTIONS',    "{$usam_base_prefix}company_connections" );
define( 'USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS',"{$usam_base_prefix}company_personal_accounts");
define( 'USAM_TABLE_GROUPS',                 "{$usam_base_prefix}groups" );	
define( 'USAM_TABLE_GROUP_RELATIONSHIPS',    "{$usam_base_prefix}group_relationships" );	
define( 'USAM_TABLE_CUSTOMER_ACCOUNTS',      "{$usam_base_prefix}customer_accounts" );	
define( 'USAM_TABLE_ACCOUNT_TRANSACTIONS',   "{$usam_base_prefix}accounts_transactions" );	

define( 'USAM_TABLE_DOCUMENTS',              "{$usam_base_prefix}documents" );	
define( 'USAM_TABLE_DOCUMENT_META',          "{$usam_base_prefix}document_meta" );	
define( 'USAM_TABLE_DOCUMENT_CONTENT',       "{$usam_base_prefix}document_content" );	
define( 'USAM_TABLE_DOCUMENT_LINKS',         "{$usam_base_prefix}document_links" );	
define( 'USAM_TABLE_DOCUMENT_LINKS_PATH',    "{$usam_base_prefix}document_links_path" );
define( 'USAM_TABLE_DOCUMENT_CONTACTS',      "{$usam_base_prefix}document_contacts" );
define( 'USAM_TABLE_DOCUMENT_PRODUCTS',      "{$usam_base_prefix}document_products" );		
define( 'USAM_TABLE_TAX_PRODUCT_DOCUMENT',   "{$usam_base_prefix}tax_product_document" );	
		
define( 'USAM_TABLE_SUBSCRIBER_LISTS',       "{$usam_base_prefix}subscriber_lists" );
define( 'USAM_TABLE_NEWSLETTERS',   "{$usam_base_prefix}newsletter_templates" );
define( 'USAM_TABLE_NEWSLETTER_TEMPLATE_META', "{$usam_base_prefix}newsletter_template_meta" );
define( 'USAM_TABLE_NEWSLETTER_USER_STAT',   "{$usam_base_prefix}newsletter_user_stat" );	
define( 'USAM_TABLE_NEWSLETTER_LISTS',       "{$usam_base_prefix}newsletter_lists" );	
define( 'USAM_TABLE_MAILING_LISTS',          "{$usam_base_prefix}mailing_lists" );	
define( 'USAM_TABLE_MAILING_LIST_META',      "{$usam_base_prefix}mailing_list_meta" );
define( 'USAM_TABLE_USER_REFERRALS',         "{$usam_prefix}user_referrals" );			
define( 'USAM_TABLE_OPEN_REFERRAL_LINKS',    "{$usam_prefix}open_referral_links" );		

define( 'USAM_TABLE_DELIVERY_SERVICE',     	 "{$usam_base_prefix}delivery_service" );
define( 'USAM_TABLE_DELIVERY_SERVICE_META',  "{$usam_base_prefix}delivery_service_meta" );
define( 'USAM_TABLE_PAYMENT_GATEWAY',     	 "{$usam_base_prefix}payment_gateway" );	
define( 'USAM_TABLE_PAYMENT_GATEWAY_META',   "{$usam_base_prefix}payment_gateway_meta" );			

define( 'USAM_TABLE_SLIDER',                 "{$usam_prefix}slider" );	
define( 'USAM_TABLE_SLIDES',                 "{$usam_prefix}slides" );
define( 'USAM_TABLE_BANNERS',                "{$usam_prefix}banners" );	
define( 'USAM_TABLE_BANNER_RELATIONSHIPS',   "{$usam_prefix}banner_relationships" );	
define( 'USAM_TABLE_CAMPAIGNS',              "{$usam_prefix}advertising_campaigns" );	
define( 'USAM_TABLE_CAMPAIGN_TRANSITIONS',   "{$usam_prefix}campaign_transitions" );
define( 'USAM_TABLE_WEBFORMS',               "{$usam_prefix}webforms" ); //usam_get_table_db('taxonomy_relationships')

define( 'USAM_TABLE_TRIGGERS',              "{$usam_base_prefix}triggers" );
define( 'USAM_TABLE_TRIGGER_META',          "{$usam_base_prefix}trigger_meta" );

define( 'USAM_TABLE_FILES',                  "{$usam_base_prefix}files" );	
define( 'USAM_TABLE_FILE_META',              "{$usam_base_prefix}file_meta" );	
define( 'USAM_TABLE_FOLDERS',                "{$usam_base_prefix}folders" );	

define( 'USAM_TABLE_KEYWORDS',               "{$usam_base_prefix}keywords" );	
define( 'USAM_TABLE_SITES',                  "{$usam_base_prefix}sites" );		
define( 'USAM_TABLE_STATISTICS_KEYWORDS',    "{$usam_base_prefix}statistics_keywords" );
define( 'USAM_TABLE_SEARCH_ENGINE_REGIONS',  "{$usam_base_prefix}search_engine_regions" );			

define( 'USAM_TABLE_SUPPORT_MESSAGE',        "{$usam_base_prefix}support_message" );	
define( 'USAM_TABLE_FILTERS',                "{$usam_base_prefix}filters" );
define( 'USAM_TABLE_EXCHANGE_RULES',         "{$usam_base_prefix}exchange_rules" );
define( 'USAM_TABLE_EXCHANGE_RULE_META',     "{$usam_base_prefix}exchange_rule_meta" );	
define( 'USAM_TABLE_SHOWCASES',              "{$usam_base_prefix}showcases" );
define( 'USAM_TABLE_COMMUNICATION_ERRORS',   "{$usam_base_prefix}communication_errors" );	
define( 'USAM_TABLE_APPLICATIONS',           "{$usam_base_prefix}integration_services" );	
define( 'USAM_TABLE_APPLICATION_META',       "{$usam_base_prefix}integration_service_meta" );	
define( 'USAM_TABLE_FEEDS',                  "{$usam_base_prefix}feeds" );
define( 'USAM_TABLE_FEED_META',              "{$usam_base_prefix}feed_meta" );	

define( 'USAM_TABLE_SELLERS',                 "{$usam_base_prefix}sellers" ); 
define( 'USAM_TABLE_SELLER_META',             "{$usam_base_prefix}seller_meta" );			
define( 'USAM_TABLE_MARKETPLACE_COMMISSIONS', "{$usam_base_prefix}marketplace_commissions" ); 	

define( 'USAM_TABLE_PARSING_SITES',           "{$usam_base_prefix}parsing_sites" ); 	
define( 'USAM_TABLE_PARSING_SITE_META',       "{$usam_base_prefix}parsing_site_meta" ); 	
define( 'USAM_TABLE_PARSING_SITE_URL',        "{$usam_base_prefix}parsing_site_url" ); 	
define( 'USAM_TABLE_PRODUCTS_COMPETITORS',    "{$usam_base_prefix}products_competitors" );	
define( 'USAM_TABLE_COMPETITOR_PRODUCT_PRICE',"{$usam_base_prefix}competitor_product_price" );	
define( 'USAM_TABLE_CATEGORIES_COMPETITORS',  "{$usam_base_prefix}categories_competitors" );	

define( 'USAM_TABLE_SYSTEM_REPORTS',        "{$usam_base_prefix}system_reports" );
define( 'USAM_TABLE_LICENSES',              "{$usam_base_prefix}licenses" );


 // Константы каталога загрузки
$upload_path = '';
$upload_url = '';
$wp_upload_dir_data = wp_upload_dir();

// Добавить путь
if ( isset($wp_upload_dir_data['basedir'] ) )
	$upload_path = $wp_upload_dir_data['basedir'];

// добавить каталог
if ( isset($wp_upload_dir_data['baseurl'] ) )
	$upload_url = $wp_upload_dir_data['baseurl'];

// Проверка SSL адресов
if ( is_ssl() )
	$upload_url = str_replace( 'http://', 'https://', $upload_url );

// Установка URL-адреса и папки для $usam_upload_sub_dir в папке upload
$usam_upload_sub_dir = '/universam/';
$usam_upload_dir     = $upload_path . $usam_upload_sub_dir;
$usam_upload_url     = $upload_url  . $usam_upload_sub_dir;

// субкаталоги внутри папки $usam_upload_sub_dir
$sub_dirs = ['documents', 'downloadables', 'backup', 'exchange', 'Log', 'product'];
// Расположение директории Upload 
define( 'USAM_UPLOAD_DIR', $usam_upload_dir );
define( 'USAM_UPLOAD_URL', $usam_upload_url );		
// Цикл по вложенным каталогам
$create_directory = false;
foreach ( $sub_dirs as $sub_directory )
{
	$paths[] = trailingslashit( $usam_upload_dir . $sub_directory );
	$urls[] = trailingslashit( $usam_upload_url . $sub_directory );
	if ( !file_exists($usam_upload_dir . $sub_directory) )	
		$create_directory = true;	
}
// Указание пути
define( 'USAM_DOCUMENTS_DIR',    $paths[0] );
define( 'USAM_FILE_DIR',         $paths[1] );	
define( 'USAM_BACKUP_DIR',       $paths[2] );	
define( 'USAM_EXCHANGE_DIR',     $paths[3] );		

// Определение URL-адресов
define( 'USAM_DOCUMENTS_URL',    $urls[0] );
define( 'USAM_FILE_URL',         $urls[1] );	
define( 'USAM_THEME_BACKUP_URL', $urls[2] );

define( 'USAM_NO_IMAGE_DIR', USAM_UPLOAD_DIR."product/no_image/" );
define( 'USAM_NO_IMAGE_URL', USAM_UPLOAD_URL."product/no_image/" );
do_action( 'usam_constants' );  

if ( $create_directory )
{	
	require_once(USAM_FILE_PATH.'/includes/installer.class.php');
	USAM_Install::create_upload_directories();
}
/**
 * Инициализация базовых констант
 */
class USAM_Load_Constants
{	
	function __construct() 
	{			
		add_filter( 'rewrite_rules_array', array(&$this, 'taxonomy_rewrite_rules'), 99 ); // Добавляет в новых правила перезаписи
		add_filter( 'intermediate_image_sizes_advanced', array( &$this, 'intermediate_image_sizes_advanced'), 10, 1 );
		add_action( 'init', array( $this, 'load_thumbnail_sizes' ) );	 // Загрузка размеров эскизов	
	}	
	
	/*
	 * Загружает основные размеры миниатюр продуктов
	 */
	function load_thumbnail_sizes() 
	{			
		$product_image = get_site_option( 'usam_product_image', ['width' => 300, 'height' => 300]);
		add_image_size( 'product-thumbnails', $product_image['width'], $product_image['height'], get_site_option('usam_crop_thumbnails', 0)  );
		$single_view_image = get_site_option( 'usam_single_view_image', ['width' => 600, 'height' => 600]);
		add_image_size( 'medium-single-product', $single_view_image['width'], $single_view_image['height'], get_site_option('usam_crop_thumbnails', 0) ); // Средний
		add_image_size( 'small-product-thumbnail', 100, 100, get_site_option('usam_crop_thumbnails', 0) ); // Маленький		
	}
	
	function intermediate_image_sizes_advanced( $sizes )
	{	
		$crop = get_site_option('usam_crop_thumbnails', 0);				
		$single_view_image = get_site_option( 'usam_single_view_image', ['width' => 600, 'height' => 600]);
		$sizes['medium-single-product'] = ["width" => $single_view_image['width'], "height" => $single_view_image['height'], "crop" => $crop];		
		$product_image = get_site_option( 'usam_product_image', ['width' => 300, 'height' => 300]);
		$sizes['product-thumbnails'] = ["width" => $product_image['width'], "height" => $product_image['height'], "crop" => $crop];		
		$sizes['small-product-thumbnail'] = ["width" => 100, "height" => 100];	
		return $sizes;
	}
	
	
	/**
	 * Добавляет в новых правила перезаписи для категорий, продукции, страницы категорий, и неоднозначные(либо категорий или продуктов)
	 * Также изменяет правила перезаписи для URL-адресов продукции, чтобы добавить в типе записи.
	 */	 
	function taxonomy_rewrite_rules( $old_rules ) 
	{ 
		$permalinks        = get_option( 'usam_permalinks' );
		$product_permalink = empty($permalinks['product_base']) ? '' : ltrim($permalinks['product_base'], '/');
		$category_permalink = empty($permalinks['category_base']) ? '' : trim($permalinks['category_base']);	
		$rules = [];
		if ( $product_permalink == 'products/%product_cat%' )
		{
			$product_permalink = 'products';
			$rules['(products)/(.+?)/(.+?)/?$'] = 'index.php?post_type=usam-product&usam-product=$matches[3]';
		}
		elseif ( $product_permalink == 'products/%sku%' )
		{			
			$product_permalink = 'products';
			$rules['(products)/(.+?)/?$'] = 'index.php?post_type=usam-product&sku=$matches[2]';	
		}
		$rules["products-list/(feed|rdf|rss|rss2|atom)/?$"] = 'index.php?post_type=usam-product&feed=$matches[1]';
		$rules["{$product_permalink}/(.+?)/(feed|rdf|rss|rss2|atom)/?$"] = 'index.php?post_type=usam-product&usam-product=$matches[1]&feed=$matches[2]';
	//	$rules["{$product_permalink}/(.+?)/(.+?)$"] = 'index.php?post_type=usam-product&usam-product=$matches[2]';	
		
		$rules["trading-platform/feed/([0-9]+)?$"] = 'index.php?trading_platform=$matches[1]';

		// используется, чтобы вывести товары следующей страницы на странице товаров
		$products_list = usam_get_system_page_name( 'products-list' );
		$rules['('.$products_list.')/page/([0-9]+)/?'] = 'index.php?pagename=$matches[1]&paged=$matches[2]';	

		// используется, чтобы найти страницу категории на странице		
		foreach ( usam_get_product_pages() as $page_slug )
		{
			$rules["($page_slug)/page/([0-9]+)/?"] = 'index.php?pagename=$matches[1]&paged=$matches[2]';			
			$rules["($page_slug)/(.+?)/page/([0-9]+)/?"] = 'index.php?pagename=$matches[1]&usam-category=$matches[2]&paged=$matches[3]';
			$rules["($page_slug)/(.+?)/?$"] = 'index.php?pagename=$matches[1]&usam-category=$matches[2]';			
		}
		// используется, чтобы найти страницу search
		$rules['(search)/(.+?)/?$'] = 'index.php?pagename=search&keyword=$matches[2]';	
		$rules['(search)/(scat)/(.+?)/?$'] = 'index.php?pagename=search&scat=$matches[2]&keyword=$matches[3]';	
		$rules['(search)/(stag)/(.+?)/?$'] = 'index.php?pagename=search&stag=$matches[2]&keyword=$matches[3]';			
		$rules['(keyword)/(.+?)/?$'] = 'index.php?pagename=search&keyword=$matches[2]';
		$rules['(search)$'] = 'index.php?pagename=search';		
			
		// используется, чтобы найти страницу
		$rules['(login)?$'] = 'index.php?pagename=login';
		$rules['(wish-list)/page/([0-9]+)/?$'] = 'index.php?pagename=wish-list&paged=$matches[2]';
		$rules["(wish-list)/(.+?)/page/([0-9]+)/?"] = 'index.php?pagename=$matches[1]&usam-category=$matches[2]&paged=$matches[3]';
		$virtual_page =  usam_virtual_page( );
		foreach ( $virtual_page as $page_slug => $page )
		{
			if ( $page_slug != 'your-account' &&  $page_slug != 'seller' && $page_slug != 'transaction-results'  )
				$rules["($page_slug)?$"] = "index.php?pagename=$page_slug";
		}		
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
		{
			$seller_base = empty($permalinks['seller_base']) ? 'seller' : trim($permalinks['seller_base']);
			$rules[$seller_base.'/(.+?)/products/(.+?)/?$'] = 'index.php?pagename=seller&seller_id=$matches[1]&usam-category=$matches[2]';	
			$rules[$seller_base.'/(.+?)/products/?$'] = 'index.php?pagename=seller&seller_id=$matches[1]';	
			$rules[$seller_base.'/(.+?)/?$'] = 'index.php?pagename=seller&seller=$matches[1]';			
			$rules['('.$seller_base.')$'] = 'index.php?pagename=seller';
		}
		// используется, чтобы найти страницу в профиле пользователя
		$rules['(your-account)/(.+?)/page/([0-9]+)/?$'] = 'index.php?pagename=your-account&tabs=$matches[2]&paged=$matches[3]';
		$rules['(your-account)/(.+?)/(.+?)/([0-9]+)?$'] = 'index.php?pagename=your-account&tabs=$matches[2]&subtab=$matches[3]&code=$matches[4]';
		$rules['(your-account)/(.+?)/(.+?)/?$'] = 'index.php?pagename=your-account&tabs=$matches[2]&subtab=$matches[3]';
		$rules['(your-account)/(.+?)/?$'] = 'index.php?pagename=your-account&tabs=$matches[2]';	
		$rules['(your-account)$'] = 'index.php?pagename=your-account';	
		
		$rules['(reviews)/([0-9]+)/?$'] = 'index.php?pagename=reviews&id=$matches[2]';	
		$rules['(reviews)/(.+?)/page/([0-9]+)?$'] = 'index.php?pagename=reviews&id=$matches[2]&paged=$matches[3]';
		$rules['(reviews)/page/([0-9]+)/?$'] = 'index.php?pagename=reviews&paged=$matches[2]';
		$rules['(reviews)$'] = 'index.php?pagename=reviews';
		
		$rules['(transaction-results)/(.+?)/(.+?)/?$'] = 'index.php?pagename=transaction-results&tabs=$matches[2]&gateway_id=$matches[3]';	
		$rules['(transaction-results)/(.+?)/?$'] = 'index.php?pagename=transaction-results&tabs=$matches[2]';	
		$rules['(transaction-results)$'] = 'index.php?pagename=transaction-results';	
					
		$terms = get_terms(['fields' => 'id=>slug', 'taxonomy' => 'usam-product_attributes', 'hide_empty' => 0, 'usam_meta_query' => [['key' => 'switch_to_selection', 'value' => 1, 'compare' => '=']]]);
		foreach ( $terms as $id => $slug )
		{			
			$rules["$slug/(.+?)/page/([0-9]+)/?$"] = 'index.php?usam-product_attributes='.$slug.'&attribute=$matches[1]&paged=$matches[2]';								
			$rules["$slug/(.+?)/?$"] = 'index.php?usam-product_attributes='.$slug.'&attribute=$matches[1]';
		}
		foreach (['category_sale' => 'category_sale', 'selection' => 'selection', 'catalog' => 'catalog', 'brand' => 'brands'] as $k => $term ) 
		{					
			$permalink = empty($permalinks[$k.'_base']) ? $k : trim($permalinks[$k.'_base']);
			$rules[$permalink.'/(.+?)/(.+?)/page/([0-9]+)/?$'] = 'index.php?usam-'.$term.'=$matches[1]&usam-category=$matches[2]&paged=$matches[3]';	
			$rules[$permalink.'/(.+?)/page/([0-9]+)/?$'] = 'index.php?usam-'.$term.'=$matches[1]&paged=$matches[2]';			
			$rules[$permalink.'/(.+?)/(.+?)/?$'] = 'index.php?usam-'.$term.'=$matches[1]&usam-category=$matches[2]';	
		}
		$rules['api/(.+?)/?$'] = 'index.php?pagename=api&program=$matches[1]';	
		$rules['(.+?)/chat/([0-9]+)/?$'] = 'index.php?pagename=$matches[1]&dialog_with_contact=$matches[2]';
		$rules['file/(.+?)/?$'] = 'index.php?pagename=file&code=$matches[1]';
		$rules['show_file/(.+?)/?$'] = 'index.php?pagename=show_file&code=$matches[1]';	
		$rules['c/(.+?)/?$'] = 'index.php?pagename=c&code=$matches[1]';	
		$rules['r/(.+?)/?$'] = 'index.php?pagename=r&code=$matches[1]';	
		$rules['ac/(.+?)/?$'] = 'index.php?pagename=advertising_campaign&campaign=$matches[1]';					
		$rules['point-delivery/?$'] = 'index.php?pagename=point-delivery';		
		$rules['point-delivery/([0-9]+)/?$'] = 'index.php?pagename=point-delivery&id=$matches[1]';			
		return array_merge( $rules, $old_rules );
	}
}
if ( get_option('usam_db_version', false) !== false )
	new USAM_Load_Constants();

function usam_system_pages()
{
	$pages = [
		'products-list' => [		
			'title' =>  __('Каталог', 'usam'),
			'content' => '<!-- wp:shortcode -->[productspage]<!-- /wp:shortcode -->',
			'shortcode' => 'productspage',
			'name' => 'products-list',			
		],		
		'sale' => [	
			'title' =>  __('Распродажа', 'usam'),
			'content' => '<!-- wp:shortcode -->[sale]<!-- /wp:shortcode -->',
			'shortcode' => 'sale',
			'name' => 'sale',			
		],			
		'new-arrivals' => [
			'title' =>  __('Новинки', 'usam'),
			'content' => '<!-- wp:shortcode -->[newarrivals]<!-- /wp:shortcode -->',
			'shortcode' => 'newarrivals',
			'name' => 'new-arrivals',			
		],	
		'reviews' => [		
			'title' => __('Отзывы', 'usam'),
			'content' => '<!-- wp:shortcode -->[reviews]<!-- /wp:shortcode -->',
			'shortcode' => 'reviews',
			'name' => 'reviews',
		],	
		'brands' => [			
			'title' => __('Бренды', 'usam'),
			'content' => '<!-- wp:shortcode -->[brands]<!-- /wp:shortcode -->',
			'shortcode' => 'brands',
			'name' => 'brands',
		],	
		'recommend' => [		
			'title' => __('Рекомендуемые товары', 'usam'),
			'content' => '<!-- wp:shortcode -->[recommend]<!-- /wp:shortcode -->',
			'shortcode' => 'recommend',
			'name' => 'recommend',
		],		
		'popular' => [		
			'title' => __('Популярные товары', 'usam'),
			'content' => '<!-- wp:shortcode -->[popular]<!-- /wp:shortcode -->',
			'shortcode' => 'popular',
			'name' => 'popular',
		],	
		'set' => [		
			'title' => __('Наборы товаров', 'usam'),
			'content' => '<!-- wp:shortcode -->[set]<!-- /wp:shortcode -->',
			'shortcode' => 'set',
			'name' => 'set',
		]		
	];	
	return $pages;
}

function usam_get_product_pages( ) 
{	
	return ['new-arrivals', 'sale', 'recommend', 'popular', 'purchased'];
}

function usam_virtual_page()
{	
	$pages = [ 
		'search' => ['slug' => 'search',  'title' => __('Поиск','usam'), 'content' => "[search]"],
		'point-delivery' => ['slug' => 'point-delivery',  'title' => __('Пункты выдачи','usam'), 'content' => "[point_delivery]"],		
		'map' => ['slug' => 'map',  'title' => __('Карта','usam'), 'content' => "[map]"],
		'pay_order' => ['slug' => 'pay_order',  'title' => __('Оплата заказа','usam'), 'content' => "[pay_order]"],	
		'tracking' => ['slug' => 'tracking',  'title' => __('Отслеживание отправлений','usam'), 'content' => "[tracking]"],	
		'compare' => ['slug' => 'compare',  'title' => __('Сравнение товаров','usam'), 'content' => "[compare_products]"],
		'wish-list' => ['slug' => 'wish-list',  'title' => __('Избранное','usam'), 'content' => "[wishlist]"],   
		'checkout' => ['slug' => 'checkout',  'title' => __('Оформить заказ','usam'), 'content' => "[checkout]"],   
		'transaction-results' => ['slug' => 'transaction-results',  'title' => __('Ваш заказ принят!','usam'), 'content' => "[transaction_results]"],		
		'basket' => ['slug' => 'basket',  'title' => __('Корзина','usam'), 'content' => "[basket]"],   
		'your-account' => ['slug' => 'your-account',  'title' => __('Личный кабинет','usam'), 'content' => "[your_account]"],   
		'your-subscribed' => ['slug' => 'your-subscribed',  'title' => __('Подписка на новости','usam'), 'content' => "[your_subscribed]"]
	]; 
	if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
	{
		$pages['seller'] = ['slug' => 'seller',  'title' => __('Продавцы','usam'), 'content' => "[sellers]"];
	}
	return apply_filters( 'usam_virtual_page', $pages );	
}

function usam_get_message_transaction() 
{			
	$message = [
		'insufficient_funds' => ['title' => __('Недостаточно средств для оплаты','usam'), 'text' => sprintf(__('Извините Ваша сделка не была принята из-за нехватки средств.<br /><a href="%1$s">Нажмите здесь, чтобы вернуться к странице оформления заказа</a>.', 'usam'), usam_get_url_system_page('checkout'))],
		'unknown_transaction' => ['title' => __('Не известная транзакция','usam'),'text' => sprintf( __('Сервер вернул не известную транзакцию. Мы проверим данные и свяжемся с вами. <br /> Нажмите <a href="%1$s">здесь, чтобы вернуться к товарам.</a>', 'usam'), usam_get_url_system_page('products-list'))],
		'old_transaction' => ['title' => __('Время оплаты вышло','usam'),'text' => sprintf(__('К сожалению это заказ нельзя оплатить. Прошло слишком много времени. Создайте заказ по новой. <br /><a href="%1$s">Нажмите здесь, чтобы вернуться к товарам</a>.', 'usam'), usam_get_url_system_page('products-list'))],
		'order_paid' => ['title' => __('Заказ уже оплачен','usam'),'text' => sprintf( __('Этот заказ уже оплачен. <br /><a href="%1$s">Нажмите здесь, чтобы посмотреть его в Вашем кабинете</a>.', 'usam'), usam_get_url_system_page('your-account'))],
		'gateway' => ['title' => __('Ошибка платежного шлюза','usam'),'text' => sprintf( __('Через выбранный платежный шлюз нельзя оплатить или он отключен. Попробуйте еще раз.', 'usam'))],
	];
	return $message;
}

function usam_get_site_bots( )
{
	$bots = [
		'rambler','aport','yahoo','msnbot','turtle','mail.ru','omsktele',
		'yetibot','picsearch','sape.bot','sape_context','gigabot','snapbot','alexa.com',
		'megadownload.net','askpeter.info','igde.ru','ask.com','qwartabot','yanga.co.uk',
		'scoutjet','similarpages','oozbot','shrinktheweb.com','aboutusbot','followsite.com',
		'dataparksearch','google','google page speed insights','google-sitemaps','appengine-google','feedfetcher-google','adsBot-google-mobile','googlebot','apis-google', 'googleweblight', 'google favicon', 'duplexWeb-google','google-read-aloud', 'feedfetcher-google','adsbot-google-mobile-apps','mediapartners-google','googlebot-video', 'chrome-lighthouse',
		'liveinternet.ru','xml-sitemaps.com','agama','metadatalabs.com','h1.hrn.ru',
		'googlealert.com','seo-rus.com','yadirectbot','yandeg', 'copyscape.com','adsbot-google','domaintools.com',
		'yandex','yandexaccessibilitybot', 'yandexdialogs','yandexdirectdyn','yandexmobilebot','yandexscreenshotbot','yandeximages','yandexbot','yandexvideo','yandexvideoparser','yandexfavicons','yandexwebmaster','yandexmedia','yandexblogs','yandexdirect', 'yandexsomething', 'yandexpagechecker', 'yandeximageresizer', 'yandexadnet', 'yadirectfetcher', 'yandexcalendar', 'yandexsitelinks', 'yandexnews', 'yandexcatalog', 'yandexmarket', 'yandexvertis', 'yandexfordomain', 'yandexspravbot', 'yandexsearchshop', 'yandexontodbapi', 'yandexverticals',
		'nigma.ru','bing.com','dotnetdotcom', 'bingbot', 'stackrambler', 'meanpathbot','megaindex.ru',
		'mj12bot','netseer', 'linksmasterrobot', 'linkstats bot', 'ltx71', 'baiduspider', 'cncat', 'deusu', 'dotbot', 'sputnikfaviconbot','statonlinerubot', 'vkshare','webartexbot','web-monitoring', 'runet-research-crawler','safedns','seopultcontentanalyzer',
		'facebookexternalhit', 		
		'guzzlehttp', 'java/1.4.1_04', 'java/1.8.0_60'
	];
	return $bots;
}

function usam_get_types_products_sold( )
{
	$products = [
		'product' => ['single' => __('Физический товар', 'usam'), 'plural' => __('Физические товары', 'usam')], 
		'service' => ['single' => __('Услуга', 'usam'), 'plural' => __('Услуги', 'usam')], 
		'subscription' => ['single' => __('Подписка', 'usam'), 'plural' => __('Подписки', 'usam')], 
		'electronic_product' => ['single' => __('Электронный товар', 'usam'), 'plural' => __('Электронные товары', 'usam')]
	];
	return $products;
}

function usam_get_types_social_network( )
{
	$channel = ['chat' => __('Чат'), 'instagram' => 'Instagram', 'name' => 'Facebook', 'telegram' => 'Telegram', 'viber' => 'Viber', 'whatsapp' => 'WhatsApp', 'vk' => __('Вконтакте'), 'ok' => __('Однокласники')];
	return $channel;
}

function usam_get_conditions( $type = 'string_number' )
{
	$conditions = ['contains' => __('Содержит', 'usam'), 'not_contain' => __('Не содержит', 'usam'), '!=' => __('Не равно', 'usam'), '=' => __('Равно', 'usam'), '>' => __('Больше', 'usam'), '<' => __('Меньше', 'usam'), 'exists' => __('Существует', 'usam'), 'not_exists' => __('Не существует', 'usam'), 'begins' => __('Начинается', 'usam'), 'ends' => __('Заканчивается', 'usam')];
	if ( $type != 'all' )
	{
		$strings_numbers = ['contains', 'not_contain', 'begins', 'ends', '!=', '=', '>', '<'];
		$strings = ['contains', 'not_contain', 'begins', 'ends', '!=', '='];
		$metas = ['contains', 'not_contain', 'exists', 'not_exists', 'begins', 'ends'];
		$numbers = ['!=', '=', '>', '<'];
		foreach( $conditions as $key => $value )
		{
			if ( $type == 'string' )
			{
				if ( !in_array($key, $strings) )
					unset($conditions[$key]);
			}
			elseif ( $type == 'meta' )
			{
				if ( !in_array($key, $metas) )
					unset($conditions[$key]);
			}
			elseif ( $type == 'string_number' )
			{
				if ( !in_array($key, $strings_numbers) )
					unset($conditions[$key]);					
			}
			elseif ( $type == 'number' )
			{
				if ( !in_array($key, $numbers) )
					unset($conditions[$key]);					
			}
		}
	}
	return $conditions;
} 

function usam_get_name_condition( $code )
{
	$conditions = usam_get_conditions( 'all' );
	if ( isset($conditions[$code]) )
		return $conditions[$code];
	else
		return '';
}

function usam_get_types_file_exchange(  ) 
{		
	$type_file_exchange = [
		"exel" => ["title" => __("Книга Exel(*.xlsx)", "usam"), "ext" => "xlsx", "delimiter" => ""],
		"csv" =>  ["title" => __("CSV (разделитель запятая) (*.csv)", "usam"), "ext" => "csv", "delimiter" => ","],
		"csv2" => ["title" => __("CSV (разделитель точка с запятой) (*.csv)", "usam"), "ext" => "csv", "delimiter" => ";"],
		"csv3" => ["title" => __("CSV со столбцами в кавычках (разделитель запятая) (*.csv)", "usam"), "ext" => "csv", "delimiter" => '","'],
		"csv6" => ["title" => __("CSV со столбцами в кавычках (разделитель точка с запятой) (*.csv)", "usam"), "ext" => "csv", "delimiter" => '";"'],
		"csv4" => ["title" => __("CSV разделенный табуляцией (*.csv)", "usam"), "ext" => "csv", "delimiter" => "\t"],
		"csv5" => ["title" => __("Текст разделенный | (*.txt)", "usam"), "ext" => "csv", "delimiter" => "|"],
		"txt"  => ["title" => __("Текст (разделитель запятая) (*.txt)", "usam"), "ext" => "txt", "delimiter" => ","],
		"txt2" => ["title" => __("Текст (разделитель точка с запятой) (*.txt)", "usam"), "ext" => "txt", "delimiter" => ";"],
		"txt3" => ["title" => __("Текст со столбцами в кавычках (разделитель запятая) (*.txt)", "usam"), "ext" => "txt", "delimiter" => '","'],		
		"txt5" => ["title" => __("Текст разделенный | (*.txt)", "usam"), "ext" => "txt", "delimiter" => "|"],
		"tsv"  => ["title" => __("TSV разделенный табуляцией (*.tsv)", "usam"), "ext" => "tsv", "delimiter" => "\t"],
	];	
	return $type_file_exchange;
} 

function usam_get_type_file_exchange( $type, $key )
{	
	$types_file = usam_get_types_file_exchange();
	if ( isset($types_file[$type]) )
		return $types_file[$type][$key];
	else
		return '';
}

function usam_set_nocache_constants( )
{
	if ( !defined( 'DONOTCACHEPAGE' ) )
		define( 'DONOTCACHEPAGE', true );
	if ( !defined( 'DONOTCACHEOBJECT' ) )
		define( 'DONOTCACHEOBJECT', true );
	if ( !defined( 'DONOTCACHEDB' ) )
		define( 'DONOTCACHEDB', true );
}
 
function usam_color_palette()
{
	$styles = usam_get_site_style( );	
	$new_colors = [];
	if ( is_array($styles) )
	{
		foreach ( $styles as $key => $style )
		{
			$color = get_theme_mod($key, $style['default']);
			$new_colors[] = ['name' => $style['label'], 'slug' => $key, 'color' => $color];
		}	
	}
	add_theme_support('editor-color-palette', $new_colors );	
}
add_action( 'after_setup_theme', 'usam_color_palette' );

/*	$current_offset = get_option('gmt_offset');
$tzstring = get_option('timezone_string');

if ( false !== strpos($tzstring,'Etc/GMT') )
	$tzstring = '';

if ( empty($tzstring) ) 
{			
	if ( 0 == $current_offset )
		$tzstring = '+0';
	elseif ($current_offset < 0)
		$tzstring = $current_offset;
	else
		$tzstring = '+' . $current_offset;
}			
date_default_timezone_set( 'Etc/GMT'.$tzstring );
*/

function usam_get_callback_messages( $result ) 
{
	if( isset($result['deleted']) )
		$result['messages'][] = sprintf( _n('Удалена %s запись', 'Удалено %s записей', $result['deleted'], 'usam'), $result['deleted'] );
	if( isset($result['trashed']) )
		$result['messages'][] = sprintf( _n('%s запись перемещена в корзину', '%s записей перемещены в корзину', $result['trashed'], 'usam'), $result['trashed'] );		
	if( isset($result['access']) )
		$result['errors'][] = __("Доступ закрыт","usam");
	if( isset($result['updated']) )
		$result['messages'][] = sprintf( _n('Изменена %s запись', 'Изменена %s записей', $result['updated'], 'usam'), $result['updated'] );
	if ( isset($result['ready']) )
	{
		if ( $result['ready'] )
			$result['messages'][] = __("Выполнено","usam");
		else
			$result['errors'][] = __("Не выполнено","usam");
	}
	elseif( isset($result['add_event']) )
	{
		if ( $result['add_event'] )
			$result['messages'][] = __('Задача добавлена', 'usam');
		else
			$result['messages'][] = __('Задача не добавлена', 'usam');
	}
	elseif( isset($result['send_email']) )
	{
		if ( $result['send_email'] )
			$result['messages'][] = __("Сообщение отправлено","usam");
		else
			$result['messages'][] = __("Сообщение не отправлено. Пожалуйста, убедитесь, что ваш сервер может отправлять сообщения электронной почты.","usam");
	}
	elseif( isset($result['send_sms']) )
	{
		if ( $result['send_sms'] )
			$result['messages'][] = __("Сообщение отправлено","usam");
		else
			$result['errors'][] = __("Сообщение не отправлено. Пожалуйста, убедитесь, что вы правильно настроили смс шлюз.","usam");
	}
	if( isset($result['created']) )
		$result['messages'][] = sprintf( _n( 'Создана %s запись', 'Создано %s записей', $result['created'], 'usam'), $result['created'] );	
	if( isset($result['add_product']) )
		$result['messages'][] = sprintf( _n( 'Добавлен %s товар', 'Добавлено %s товаров', $result['add_product'], 'usam'), $result['add_product'] );	
	if( isset($result['update_product']) )
		$result['messages'][] = sprintf( _n( 'обновлен %s товар', 'Обновлено %s товаров', $result['update_product'], 'usam'), $result['update_product'] );
	if( isset($result['update']) )		
		$result['messages'][] = __('Настройки сохранены', 'usam');		
	if( isset($result['save']) )		
		$result['messages'][] = __('Сохранено', 'usam');	
	return $result;
}

function usam_get_statuses_telephony( )
{
	$statuses = array( 'compound' => __('Соединение','usam'), 'completed' => __('Завершен','usam'), 'failed' => __('Ошибка соединения','usam'), 'answered' => __('Разговор','usam'), 'busy' => __('Занято','usam'), 'cancel' => __('Отменен','usam'), 'no_answer' => __('Без ответа','usam'), 'no_money' => __('Нет средств','usam'), 'unallocated_number' => __('Номер не существует','usam'), 'no_limit' => __('Превышен лимит','usam'),'line_limit' => __('Превышен лимит линий','usam') );
	
	return $statuses;
}