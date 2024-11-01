<?php
/**
 * Главные функции админского интерфейса
 */  
//require_once( USAM_FILE_PATH.'/includes/packing-products.class.php' );
require_once( USAM_FILE_PATH.'/includes/vue-modules.class.php' );

require_once( USAM_FILE_PATH.'/admin/includes/admin_menu.class.php' );	
require_once( USAM_FILE_PATH.'/admin/includes/admin_network.class.php' );	
require_once( USAM_FILE_PATH.'/admin/includes/product/product-save.class.php' );
require_once( USAM_FILE_PATH.'/admin/includes/product/product-metabox.class.php' );
require_once( USAM_FILE_PATH.'/includes/taxonomy/class-walker-category-select.php' );

require_once( USAM_FILE_PATH.'/admin/includes/admin-assets.class.php' );
require_once( USAM_FILE_PATH.'/admin/includes/admin-form-functions.php' );
require_once( USAM_FILE_PATH.'/admin/includes/widget_admin.class.php' );
require_once( USAM_FILE_PATH.'/admin/includes/admin_functions.php' ); // Разные функции
require_once( USAM_FILE_PATH.'/admin/includes/code_name.function.php' ); 
require_once( USAM_FILE_PATH.'/admin/includes/metabox.class.php' ); 
require_once( USAM_FILE_PATH.'/admin/includes/help_center/help_tab.class.php' );
require_once( USAM_FILE_PATH.'/admin/includes/template.php'); 
require_once( USAM_FILE_PATH.'/admin/includes/admin-media.class.php'); 
require_once( USAM_FILE_PATH.'/admin/includes/page_tabs.class.php' );
require_once( USAM_FILE_PATH.'/admin/posts.page.php' );

require_once( USAM_FILE_PATH.'/includes/analytics/advertising_campaign.php' );
require_once( USAM_FILE_PATH.'/includes/crm/groups_query.class.php' );
require_once( USAM_FILE_PATH.'/includes/crm/object_statuses_query.class.php');

if ( get_option('usam_pointer', true) )
	require_once( USAM_FILE_PATH.'/admin/includes/pointer.class.php' );

$load_admin = new USAM_Load_Admin();	
class USAM_Load_Admin
{
	protected  $local_option = 'usam_options';
	protected  $site_option  = 'usam_site_options';
	function __construct( ) 
	{					
		if ( !empty($_GET['page']) && $_GET['page'] == 'usam-setup' )
		{
			if ( current_user_can('manage_options') )
				require_once( USAM_FILE_PATH.'/admin/includes/setup-wizard.class.php' );	
			else
			{
				wp_safe_redirect( admin_url() );
				exit;
			}			
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX == true ) 
			require_once( USAM_FILE_PATH.'/admin/includes/admin_ajax.php' );
		
		if ( isset($_REQUEST['usam_admin_action']) )
			require_once( USAM_FILE_PATH.'/admin/includes/admin_init.class.php' );
				
		if ( isset($_REQUEST['unprotected_query']) )
			require_once( USAM_FILE_PATH.'/admin/includes/admin_unprotected_query.class.php' );		

		add_action( 'permalink_structure_changed', array(&$this, 'action_permalink_structure_changed') );			
		add_filter( 'screen_layout_columns', array(&$this, 'screen_two_columns'), 10, 2 );
		add_action('admin_init', array(&$this, 'admin_init') );		
		add_action('init', array(&$this, 'init'),1 );	
		add_action( 'save_post', [&$this, 'refresh_page_urls'], 10, 2 );
		
		add_filter('admin_footer_text', [&$this, 'footer_name']);	
		add_filter('admin_footer', [&$this, 'admin_footer']);	

		add_action( 'admin_head', [&$this, 'admin_head'] ); 		
		add_filter( 'plugin_action_links_'.USAM_PLUGINSLUG, [&$this, 'add_action_link'], 10, 2 );	
			
		add_filter('set-screen-option', [&$this, 'set_screen_option'], 98, 3);	
		
		add_filter( 'update_option_usam_website_type', [&$this, 'save_website_type'], 10, 3 );			
		add_filter( 'allowed_options', [&$this, 'allowed_options']);		
		add_action( "update_option_site_icon", [&$this, 'site_icon'], 10, 3 );
		add_filter( 'media_row_actions', [&$this, 'add_regenerate_link_to_media_list_view'], 10, 2 );
		add_action( 'attachment_submitbox_misc_actions', [&$this, 'add_button_to_media_edit_page'], 99 );
		add_filter( 'bulk_actions-upload', [&$this, 'bulk_actions']);	
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) 
			add_action( 'admin_notices', [&$this, 'display_php_version_notice'] );	
		
		//add_filter('usam_possibility_to_call', '__return_true');	
	}
	
	public function admin_footer() 
	{
		if ( is_user_logged_in() && !usam_check_current_user_role('subscriber') )
			require_once( USAM_FILE_PATH.'/admin/tape.php' );	

		include_once( USAM_FILE_PATH.'/admin/templates/template-parts/media-browser.php' );
		$module = new USAM_VUE_Modules();
		$module->print();
		?>
		<div id="modals"></div>
		<?php
	}
	
	public function site_icon( $old_value, $value, $option ) 
	{
		if ( $value )
		{
			$icon_32  = get_site_icon_url( 32 );	
			$icon_192 = get_site_icon_url( 192 );	
			$icon_180 = get_site_icon_url( 180 );
			$icon_270 = get_site_icon_url( 270 );
			$data = [32 => $icon_32, 192 => $icon_192, 180 => $icon_180, 270 => $icon_270];
		}
		update_option('usam_cahe_site_icon', $data);
		return $url;
	}
	
	public function display_php_version_notice( $actions ) 
	{
		?><div id='usam-warning' class='error'><p><?php printf( __('Вы используете PHP %s, которое уже устарело! Некоторые модули УНИВЕРСАМ %s требует PHP 8.0 или выше. Пожалуйста, свяжитесь с вашим хостинг-провайдером для получения дополнительной помощи.', 'usam'), PHP_VERSION, USAM_VERSION ); ?></p></div><?php
	}
		
	public function bulk_actions( $actions ) 
	{ 
		if ( ! current_user_can('manage_options') )
			return $actions;
		$actions['regenerate_thumbnails'] = __('Пересоздать миниатюры', 'usam');
		return $actions;
	}
	
	public function add_regenerate_link_to_media_list_view( $actions, $post ) 
	{ 
		if ( ! current_user_can('manage_options') || ! $this->is_regeneratable( $post ) )
			return $actions;
		$actions['regenerate_thumbnails'] = '<a href="#" id="regenerate_thumbnails">' . __( 'Пересоздать миниатюры', 'usam' ) . '</a>';
		return $actions;
	}	

	public function add_button_to_media_edit_page() 
	{
		global $post;

		if ( ! current_user_can('manage_options') || ! $this->is_regeneratable( $post ) )
			return;
		
		echo '<div class="misc-pub-section regenerate_thumbnails">';
		echo '<a href="#" id="regenerate_thumbnails" class="button-secondary button-large">' . __( 'Пересоздать миниатюры', 'usam' ) . '</a>';
		echo '</div>';
	}	
	
	public function is_regeneratable( $post ) 
	{
		if ( 'site-icon' === get_post_meta( $post->ID, '_wp_attachment_context', true ) ) {
			return false;
		}

		if ( wp_attachment_is_image( $post ) ) {
			return true;
		}

		if ( function_exists( 'wp_get_original_image_path' ) ) {
			$fullsize = wp_get_original_image_path( $post->ID );
		} else {
			$fullsize = get_attached_file( $post->ID );
		}

		if ( ! $fullsize || ! file_exists( $fullsize ) ) {
			return false;
		}

		$image_editor_args = array(
			'path'    => $fullsize,
			'methods' => array( 'resize' )
		);

		$file_info = wp_check_filetype( $image_editor_args['path'] );
		// If $file_info['type'] is false, then we let the editor attempt to
		// figure out the file type, rather than forcing a failure based on extension.
		if ( isset( $file_info ) && $file_info['type'] ) {
			$image_editor_args['mime_type'] = $file_info['type'];
		}

		return (bool) _wp_image_editor_choose( $image_editor_args );
	}
	
	public function save_options( $options ) 
	{
		if ( usam_check_current_user_role('administrator') || usam_check_current_user_role('shop_manager') || current_user_can('view_seo_setting') )
		{		
			if ( isset($options[$this->local_option]) )
			{					
				$usam_options = stripslashes_deep($options[$this->local_option]);	
				foreach ( $usam_options as $key => $values ) 
				{  
					$key = sanitize_title($key);					
					if ( is_array($values) )	
					{
						$db = get_option( 'usam_'.$key, [] );
						foreach ( $values as $k => $value )
						{					
							if ( is_array($value) )	
							{
								foreach ( $value as $i => $v )
								{
									if ( $v == '******' )	
										$values[$k][$i] = $db[$k][$i];				
								}
							}
							elseif ( $value == '******' )	
								$values[$k] = $db[$k];
						}
						foreach ( $db as $k => $value )
						{
							if ( is_array($value) && !isset($values[$k]) )
								$values[$k] = $value;
						}					
					}
					elseif ( $values == '******' )		
						continue;						
					update_option( 'usam_'.$key, $values );
				}				
				unset($options[$this->local_option]);	
			} 
			if ( isset($options[$this->site_option]) )
			{
				$usam_options = stripslashes_deep($options[$this->site_option]);				
				foreach ( $usam_options as $key => $value ) 
				{  
					if ( is_array($value) )	
					{
						$db = get_option( 'usam_'.$key, $value );
						foreach ( $value as $k => $v )
						{
							if ( $v == '******' )	
								$value[$key][$k] = $db[$key][$k];
						}						
					}
					elseif ( $value == '******' )		
						continue;
					update_site_option( 'usam_'.$key, $value );
				} 			
				unset($options[$this->site_option]);	
			}
		}
	}
	
	public function allowed_options( $options ) 
	{ 
		$this->save_options( $options );
		return $options;
	}
	
	public function admin_init( ) 
	{
		if ( isset($_REQUEST['service_api']) )
			$this->display_service_api();		
		
		if ( usam_needs_upgrade() ) 	
			add_action( 'admin_notices', [$this, 'database_update_notice'] );	
		
		if ( !usam_check_license() ) 	
			add_action( 'admin_notices', array($this, 'license_notice') );
		
		$this->admin_includes();
		$this->save_options( $_POST );		

		add_action( 'admin_notices', [$this, 'events_reminder']);		
	}
	
	public function events_reminder( ) 
	{
		require_once( USAM_FILE_PATH.'/admin/includes/events_reminder.php' );
	}
					
	public function save_website_type( $old_value, $value, $option ) 
	{ 
		if ( $value == 'marketplace' )
		{ 					
			require_once( USAM_FILE_PATH.'/admin/db/db-install/marketplace_installation.php' );	
		}		
	}
	
	function set_screen_option($status, $option, $value)
	{ 	
		if( $value < 2 )
			$value = 2;
		if( $value <= 999 && $value > 1 )
		{	
			if ( "edit_usam_variation_per_page" == $option )
			{
				$user_id = get_current_user_id();	
				update_user_option($user_id,'edit_usam-variation_per_page',$value);
			}			
		}			
		return $value;
	}
	
	function page_view_options()
	{ 			
		if ( !empty($_REQUEST['page_view_option']) )				
		{
			check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );
			
			$screen_id = sanitize_title($_REQUEST['screen_id']);					
			$page_view_options = get_user_option( 'usam_page_view_options' );
			$page_view_options[$screen_id] = sanitize_title($_REQUEST['page_view_option']);		
			$user_id = get_current_user_id();
			update_user_option( $user_id, 'usam_page_view_options', $page_view_options );		
		}
	}
	
	function display_service_api() 
	{	
		wp_die( __('Готово. Можете закрыть окно...', 'usam') );
	}
	
	public function add_action_link( $links, $file ) 
	{ 
		if ( current_user_can( 'universam_settings' ) )
		{
			$settings_link = '<a href="'.esc_url( admin_url( 'options-general.php?page=shop_settings' ) ).'">'.__('Настройки', 'usam').'</a>';
			array_unshift( $links, $settings_link );
		}		
		$premium_link = '<a href="https://wp-universam.ru/buy/">'.__('Возможности', 'usam').'</a>';
		array_unshift( $links, $premium_link );
		
		$premium_link = '<a href="https://wp-universam.ru/support/">'.__('Поддержка', 'usam').'</a>';
		array_unshift( $links, $premium_link );
		
		$faq_link = '<a href="https://docs.wp-universam.ru/document/category/users" style="color: red">'.__('Документация', 'usam').'</a>';
		array_unshift( $links, $faq_link );
		
		return $links;
	}
	
	
	
	function admin_head() 
	{	
	   echo '<link rel="shortcut icon" href="'.USAM_URL.'/admin/images/favicon.png"/>'; /*text-color #999999*/
	   $colors_scheme = ['text-color' => '#80868e', 'main-color' => '#d5e8f1', 'main-color2' => '#a4286a', 'main-color3' => '#01799c', 'main-color4' => '#4c6470','color-good' => '#9ad652', 'color-error' => '#CD5C5C', 'main-color-excretion' => '#7db1c9', 'hover-color' => '#2271b1'];		
	  ?>
		<style>
			[v-cloak] {display: none;}
			:root{<?php foreach ( $colors_scheme as $key => $color ){ echo "--$key: $color;"; } ?>}
			<?php 
			$site_style = usam_get_site_style();
			foreach( $site_style as $key => $style )
			{ 
				if ( $style['type'] == 'color' )
				{
					?>
					.has-<?php echo $key; ?>-color{color:<?php echo get_theme_mod( $key, $style['default'] ); ?>}
					.has-<?php echo $key; ?>-background-color{background-color:<?php echo get_theme_mod( $key, $style['default'] ); ?>}<?php 
				}
			} 
			?>	
		</style>
		<?php
	
	}
	
	public function admin_includes() 
	{				
		// Таксомании		
		if ( !empty($_REQUEST['taxonomy']) )
		{				
			require_once( USAM_FILE_PATH.'/admin/includes/taxonomies/display-taxonomies.class.php' );					
			require_once( USAM_FILE_PATH.'/admin/includes/taxonomies/display-product_attributes.class.php' );
			require_once( USAM_FILE_PATH.'/admin/includes/taxonomies/display-category_sale.class.php' );
			require_once( USAM_FILE_PATH.'/admin/includes/taxonomies/display-variation.class.php' );
			require_once( USAM_FILE_PATH.'/admin/includes/taxonomies/display-brands.class.php' );	
			require_once( USAM_FILE_PATH.'/admin/includes/taxonomies/display-category.class.php' );
			require_once( USAM_FILE_PATH.'/admin/includes/taxonomies/display-catalog.class.php' );			
		}		
		add_action( 'current_screen', array( &$this, 'screen_includes' ) );
	}	

	public function screen_includes() 
	{		
		$screen = get_current_screen();			
		switch ( $screen->id ) 
		{		
			case 'plugins' :
				
			break;			
			case 'options-permalink' :
				require_once( USAM_FILE_PATH.'/admin/includes/wp-options/class-permalink-settings.php' ); 
			break;			
			case 'options-media' :
				require_once( USAM_FILE_PATH.'/admin/includes/wp-options/class-media-settings.php' ); 
			break;		
			case 'users' :
			case 'user' :
			case 'profile' :
			case 'user-edit' :
				require_once( USAM_FILE_PATH.'/admin/includes/user_profile_fields.class.php' ); 
			break;
		}
	}	
	
	function init()
	{
		$this->download_file_system_report();
		$this->select_code_price();
		$this->page_view_options();		
		add_action( 'in_plugin_update_message-'.USAM_DIR_NAME.'/universam.php', [$this, 'in_plugin_update_message'], 11 );  // для обновления через сервер wordpress
	}
	
	public function download_file_system_report() 
	{
		if( !empty($_GET['system_report']) )
		{ 
			$id = sanitize_title($_GET['system_report']);
			require_once(USAM_FILE_PATH.'/includes/technical/system_report.class.php');
			$system_report = usam_get_system_report( $id );
			$info = pathinfo($system_report['filename']);			
			$newfilepath = USAM_EXCHANGE_DIR .'archive/'.$info['filename'].$id.'.'.$info['extension'];	
			if ( file_exists($newfilepath) )
			{
				usam_download_file( $newfilepath, $system_report['filename'] );	
				exit;
			}
		}
	}
			
	function select_code_price()
	{ 
		if ( !empty($_REQUEST['type_price']) )
		{
			$user_id = get_current_user_id();
			$code_price = sanitize_title($_REQUEST['type_price']);
			update_user_meta($user_id, 'manager_type_price', $code_price ); 
		}		
	}	
	
	//Изменяем сообщение в футуре админки
	function footer_name ( $text )
	{
		$text = '<span id="footer-thankyou"><a href="https://www.wp-universam.ru/">«УНИВЕРСАМ»</a> - платформа для управления бизнесом, версия '.USAM_VERSION.', 2012 - '.date('Y').' гг.</span> ';
		return $text;
	}
	
	/**
	 * Обновить URL-адрес страницы, когда обновляется страница
	 */
	function refresh_page_urls( $post_id, $post )
	{
		if ( 'page' != $post->post_type )
			return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		if ( !in_array( $post->post_status, array( 'publish', 'private' ) ) )
			return;
		usam_update_permalink_slugs();
		return $post_id;
	}

	//Модификация для обеспечения макета несколько колонок
	function screen_two_columns( $columns, $screen ) 
	{
		if ( $screen == 'toplevel_page_usam-edit-products' )
			$columns['toplevel_page_usam-edit-products'] = 2;
		return $columns;
	}	
	
	/**
	 * Отображает уведомление обновление базы
	 */
	function database_update_notice() 
	{ 
		?>
		<div class="error">
			<p><?php printf( __('<strong>Платформа для интернет-магазина УНИВЕРСАМ нуждаются в обновлении</strong>.<br>Вы должны <a href="%1s">обновить базу данных</a> вашего магазина, чтобы продолжить работу.', 'usam'), admin_url( 'tools.php?page=shop&tab=update' ) ) ?></p>
		</div>
		<?php
	}
		
	function license_notice() 
	{
		?>		
		<div style="width:calc(100% + 20px);margin-left:-20px;color:#fff;background-color:#a4286a;box-shadow: inset 0 10px 10px -5px rgba(123, 30, 80, 0.5), inset 0 -10px 10px -5px rgba(123, 30, 80, 0.5);">		
			<div style="padding:5px;display: flex;flex-direction: column; align-items: center;">
				<p style="margin:0 0 5px 0;">Лицензия для платформы Универсам зарегистрированна не для домена <?php echo $_SERVER['SERVER_NAME'] ?> или действительна!</p>
				<p style="margin:0 0 5px 0">Свяжитесь с продавцом.</p>
				<p style="margin:0"><a target="_blank"  rel="noopener" href="<?php echo admin_url("index.php?page=usam-license"); ?>" style="color:#fff;font-weight:600; text-transform: uppercase;">Посмотреть вашу лицензия</a></p>
			</div>	
		</div>
		<?php
	}
			
	public function in_plugin_update_message( array $args ) 
	{ 	
		include __DIR__ . '/includes/message/plugin-update-message-compatibility.php';
	}
	
	// Обновить URL страницы продуктов при изменении настройки схемы постоянных ссылок.
	function action_permalink_structure_changed() 
	{
		
	}
}


function usam_needs_upgrade() 
{
	if ( ! current_user_can( 'update_plugins' ) )
		return false;

	$current_db_ver = get_option( 'usam_db_version', 0 );
	if ( USAM_DB_VERSION <= $current_db_ver )
		return false;	
	return true;
}
?>