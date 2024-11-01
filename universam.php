<?php
/*
 * Plugin Name: UNIVERSAM
 * Plugin URI: https://wp-universam.ru
 * Description: Платформа для управления бизнесом и интернет-магазином. Встроенный парсинг, CRM, соц. сети, мессенджеры, карты, план продаж, управление остатками, контакт-центр, коммерческие предложения, счета, акты, инструменты для продвижения сайта, конструктор отчетов, файлы, рассылки, СМС.
 * Version: 8.58.3
 * Author: universam
 * Author URI: https://wp-universam.ru
 * Text Domain: usam
 * Domain Path: /languages/
 * Copyright 2012 - 2023
 * Requires at least: 5.7
 * Requires PHP: 7.4
*/
final class UNIVERSAM 
{	
	protected static $_instance = null;
	private          $version = '8.58.3';
	public function __construct()
	{
		define( 'USAM_DB_VERSION', 564 );
		define( 'USAM_VERSION', $this->version );
		define( 'USAM_VERSION_ASSETS', defined('WP_DEBUG') && WP_DEBUG ? time() : USAM_VERSION );	

		if ( !defined('USAM_DEBUG_THEME') )
			define('USAM_DEBUG_THEME', false );
		
		if ( !defined('USAM_DISABLE_INTEGRATIONS') )
			define('USAM_DISABLE_INTEGRATIONS', false );
		register_activation_hook( __FILE__, [$this, 'activation']);
		register_deactivation_hook( __FILE__, [$this, 'deactivate']);			

		if ( USAM_DEBUG_THEME  )
		{ 
			$WEBPACK_HOST = parse_url(home_url(), PHP_URL_HOST).":55555";								
			$anonymous_function = function( $url, $path, $plugin) { 
				$url = str_replace( parse_url($url, PHP_URL_HOST), parse_url($url, PHP_URL_HOST).":55555", $url);	
				return $url; 
			};	
			add_filter( 'plugins_url', $anonymous_function, 10, 3 );	
			define('WP_HOME', 'http://' . $WEBPACK_HOST);
			define('WP_SITEURL', 'http://' . $WEBPACK_HOST);
			remove_filter('template_redirect', 'redirect_canonical'); 
		}
		define( 'USAM_FILE_PATH', dirname( __FILE__ ) );    // Задать путь к файлам плагина		
		define( 'USAM_URL',      plugins_url( '', __FILE__ ) );   // Определяет URL к папке плагина	
		require_once( USAM_FILE_PATH . '/includes/includes.php' );			
		add_action( 'plugins_loaded', [$this, 'plugin_loaded'] );		
		if ( defined('WP_DEBUG') && WP_DEBUG )
			$this->debug();		
	}
				
	function debug() 
	{
		add_filter( 'block_local_requests', '__return_false' );
		add_filter( 'https_ssl_verify', '__return_false' );
	}
	
	 // Инициализация класса
	function plugin_loaded()
	{		
		if ( get_option( 'usam_db_version', false ) === false )
		{ 
			if ( isset($_GET['install']) && $_GET['install'] == 'start' )	
				add_action('init', array($this, 'start_install') );		
			else
				require_once( USAM_FILE_PATH . '/admin/includes/installation-process.class.php' );				
		}
		else
		{
			if ( defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE ) 
				$this->multisite_install();								
			$this->load();								
		}
	}		
				
	// Настройка ядра
	function load() 
	{	
		do_action( 'usam_pre_load' ); // Крюк перед установкой	
		//load_plugin_textdomain( 'usam', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
				
		if ( !USAM_DISABLE_INTEGRATIONS || true )
		{
			$services = usam_get_applications(['active' => 1, 'cache_meta' => true]);	
			foreach( $services as $service )
			{				
				$class = usam_get_class_application( (array)$service );
				if ( $class )
					$class->service_load();	
			}						
		}
		include_once( USAM_FILE_PATH . '/includes/load_universam.class.php' );
		if ( is_admin() )		
			include_once( USAM_FILE_PATH . '/admin/admin.php' );	
		else
			include_once( USAM_FILE_PATH . '/includes/load_site.class.php' );			
		do_action( 'usam_loaded' );  // крюк когда UNIVERSAM полностью загружен	
	}		
		
	public function activation() 
	{
		if ( get_option('usam_db_version', false) !== false )
		{
			require_once(USAM_FILE_PATH.'/includes/installer.class.php');
			USAM_Install::install();		
		}
	}
	
	public function start_install() 
	{	
		require_once(USAM_FILE_PATH.'/includes/installer.class.php');
		$output = USAM_Install::install();
		echo json_encode( $output );
		exit;
	}
	
	/**
	 * При деактивации
	 */
	public function deactivate() 
	{		
		foreach ( wp_get_schedules() as $cron => $schedule ) 		
			wp_clear_scheduled_hook( "usam_{$cron}_cron_task" );			

		$api = new USAM_Service_API();
		$result = $api->universam_deactivation( );		
	}
	
	public function multisite_install() 
	{	
		if ( get_site_option('usam_type_prices') === false )
		{
			$options = ['coupons_roles', 'calendars', 'type_prices', 'sales_area', 'languages', 'vk_contest', 'purchase_rules', 'types_payers', 'order_view_grouping', 'underprice_rules', 'accumulative_discount', 'crosssell_conditions', 'vk_publishing_rules', 'product_day_rules', 'notifications', 'search_engine_location', 'bonuses_rules', 'phones'];
			foreach( $options as $key )
			{
				$option = get_blog_option(1, 'usam_'.$key );					
				update_site_option('usam_'.$key, $option);
			}
		}
	}
	
	public static function instance() 
	{ 
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}		
} 
UNIVERSAM::instance();
?>