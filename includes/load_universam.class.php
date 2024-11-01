<?php
class USAM_Load_Universam
{
	function __construct( ) 
	{					
		add_action('init', [&$this, 'updating_service'] );
		
		add_action('upgrader_process_complete', [&$this, 'db_upgrader'], 10, 2 );
		add_action('admin_init', [&$this, 'action_create_customer_id'], 5 );
		add_action('wp', [&$this, 'action_create_customer_id'], 5 );	
	/*	add_action('usam_api_basket_save', [&$this, 'action_create_customer_id']);	
		add_action('usam_api_add_product_basket', [&$this, 'action_create_customer_id']);	
		add_action('usam_api_add_products_basket', [&$this, 'action_create_customer_id']);	*/
		
		add_action('wp', [&$this, 'maintenance_mode'], 1 );	
		add_action('sanitize_title', 'usam_sanitize_title_with_translit', 0); //Русские в английские
		/*
		add_action( 'customize_register', array( $this, 'create_nav_menu_locations' ), 4 );
		add_action( 'admin_init', array( $this, 'create_nav_menu_locations' ) );
		
		if ( isset( $_POST['wp_customize'], $_POST['customized'] ) ) 
		{
			add_filter( 'wp_nav_menu_args', array( $this, 'filter_args_before_customizer' ) );
			add_filter( 'wp_nav_menu_args', array( $this, 'filter_args_after_customizer' ), 2000 );
		}
		*/
		//add_action( 'wp_loaded', array('flush_rules_universam') );	
		add_action('wp_footer', [&$this, 'footer'], 1);
		add_action('admin_footer', [&$this, 'footer'], 1);	
		add_filter('jpeg_quality', [&$this, 'jpeg_quality'], 100 );
	}
	
	function jpeg_quality( $quality ) 
	{
		return get_site_option( 'usam_image_quality', 100 );
	}
	
	function footer() 
	{
		if ( usam_check_current_user_role('administrator') || usam_check_current_user_role('shop_manager') )
			require_once( USAM_FILE_PATH . '/admin/includes/modal/task_manager.php' );	
	}
	
	function db_upgrader( $upgrader, $hook_extra ) 
	{	
		if ( !empty($upgrader->skin->new_plugin_data ) && !empty($upgrader->skin->new_plugin_data['Name']) )
		{			
			$name = mb_strtolower($upgrader->skin->plugin_info['Name']);
			if ( $name == 'universam' || $name == 'universam-demo' )
			{	
				set_transient( 'usam_process_complete', true, DAY_IN_SECONDS );	
				require_once( USAM_FILE_PATH.'/admin/includes/update.class.php' );
				USAM_Update::start_update( false );	
			}
		}
	}
	
	//отключить сайт для всех, кроме администратора сайта
	function maintenance_mode() 
	{		
		if ( usam_is_bot() || is_feed() || usam_check_current_user_role('administrator') )
			return false;			
		if ( ( defined('USAM_INSTALLING') && USAM_INSTALLING || get_option('usam_stand_service', 0 ) == 1) ) 
		{			
			$url = parse_url($_SERVER['REQUEST_URI']);
			$path = !empty($url['path']) ? trim($url['path'], '/') : '';
			if( $path != 'login' && $path != 'wp-login.php' ) 
			{
				wp_die( __('На сайте ведутся работы по техническому обслуживанию. Пожалуйста подождите...', 'usam') );
			}
		}
	}
	
	public function create_nav_menu_locations() 
	{
		static $once;
		global $_wp_registered_nav_menus;		
		if ( isset($_wp_registered_nav_menus) && ! $once ) 
		{ 
			$languages = usam_get_languages();
			$arr = array();
			foreach ( $_wp_registered_nav_menus as $location => $name )
			{
				foreach ( $languages as $language ) {
					$arr[$location . '___' .$language['code']] = $name.' ('.$language['name'].')';
				}
			}
			$_wp_registered_nav_menus = $arr;
			$once = true;
		}		
	}
	
	public function filter_args_before_customizer( $args )
	{
		$language = usam_get_contact_language();
		$args['theme_location'] = $args['theme_location'] . '___' .$language;
		return $args;
	}

	public function filter_args_after_customizer( $args ) 
	{
		$infos = $this->explode_location( $args['theme_location'] );
		$args['theme_location'] = $infos['location'];
		return $args;
	}
	
	public function explode_location( $loc ) 
	{
		$infos = explode( '___', $loc );
		if ( 1 == count($infos) ) 		
			$infos[] = usam_get_contact_language();
		return array_combine( array('location', 'language'), $infos );
	}
	
// если наши правила еще не включены
	function flush_rules_universam()
	{
		$rules = get_option( 'rules' );
		if ( !isset($rules['(your-account)/(\d*)$']) ) 
			flush_rewrite_rules( );
	}	
	
	/* Создать идентификатор клиента после 'plugins_loaded'
	 */
	function action_create_customer_id() 
	{	
		if ( usam_is_bot() || is_feed() )
			return false;

		if ( (defined( 'DOING_CRON' ) && DOING_CRON) || (defined( 'DOING_AJAX' ) && DOING_AJAX) )					
			return false;	

		$version = get_option( 'usam_db_version', false );	
		if ( $version === false )
			return false;	
				
		$contact_id = usam_get_contact_id();	
		global $wpdb;
		if ( $wpdb->last_error ) 
		{ 	
			return false;
		}		
		if ( !empty($contact_id) )
		{ 
			$contact = usam_get_contact( $contact_id );	
			$update = [];
			if ( strtotime($contact['online']) < USAM_CONTACT_ONLINE )
			{ //не было 10 часов
				if( $contact['user_id'] && $contact['contact_source'] != 'employee' && usam_check_current_user_role(['administrator','shop_manager','editor','shop_crm','company_management']) )
					$update['contact_source'] = 'employee';													
				$update['online'] = date("Y-m-d H:i:s");			
				do_action( 'usam_new_user_visit' );	
			}
			if ( $update )
				usam_update_contact( $contact_id, $update );					
		}
		else
		{ 
			$user_id = get_current_user_id();			
			$new_data['online'] = date("Y-m-d H:i:s");	
			if ( $user_id == 0 )
				$new_data['status'] = 'temporary';	
			$new_data['user_id'] = $user_id;	
			if( user_can($user_id, 'store_section') )
				$new_data['contact_source'] = 'employee';	
			elseif ( !empty($_SERVER['HTTP_REFERER']) )			
			{		
				$referer = parse_url(sanitize_text_field($_SERVER['HTTP_REFERER']), PHP_URL_HOST);
				if( $referer )
				{
					if( stripos($referer, 'yandex') !== false )
						$new_data['contact_source'] = 'yandex';
					elseif( stripos($referer, 'google.') !== false )
						$new_data['contact_source'] = 'google';
					elseif( stripos($referer, 'instagram.') !== false )
						$new_data['contact_source'] = 'instagram';						
					elseif( stripos($referer, 'facebook.') !== false )
						$new_data['contact_source'] = 'facebook';
					elseif( stripos($referer, 'vk.') !== false )
						$new_data['contact_source'] = 'vk';
					elseif( stripos($referer, 'ok.') !== false )
						$new_data['contact_source'] = 'ok';
					elseif ( strlen($referer) < 200 )
						$new_data['contact_source'] = $referer;
				}
			}	
			if( isset($_COOKIE[USAM_VISIT_COOKIE]) )
			{
				$contact_id = usam_insert_contact( $new_data );
				if ( $contact_id )
				{
					usam_add_contact_metadata( $contact_id, 'visit', 1 );
					usam_update_visit( $_COOKIE[USAM_VISIT_COOKIE], ['contact_id' => $contact_id] );
					$location = usam_get_current_user_location();
					if ( $location )
						usam_add_contact_metadata( $contact_id, 'location', $location );
				}
			}
		}			
		usam_save_cookie_contact_id( $contact_id );		
		$user_view_id = usam_get_contact_visit_id();	
	}
	
	public function updating_service()
	{			
		require_once( USAM_FILE_PATH . '/includes/exchange/upgrader_plugin.class.php');
		require_once( USAM_FILE_PATH . '/includes/exchange/upgrader_themes.class.php');
		
		$upgrader = new USAM_Upgrader_Plugin();	
		$upgrader = new USAM_Upgrader_Themes();			
	}	
}
new USAM_Load_Universam();
?>