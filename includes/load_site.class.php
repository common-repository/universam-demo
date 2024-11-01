<?php
// Загрузить сайт 
class USAM_Load_Site
{
	function __construct( ) 
	{ 
		if ( !defined( 'DOING_CRON' ) || !DOING_CRON )
		{								 
			// Настройка ядра корзины UNIVERSAM		
			add_action( 'template_redirect',  [&$this, 'core_setup_cart'] );							
			// Темы
			require_once( USAM_FILE_PATH . '/includes/theme/theme.functions.php'   );	
			require_once( USAM_FILE_PATH . '/includes/theme/template_page_handler.class.php'   );
			require_once( USAM_FILE_PATH . '/includes/theme/breadcrumbs.class.php' );					
			require_once( USAM_FILE_PATH . '/includes/theme/theme_shortcode.class.php'  );			
			require_once( USAM_FILE_PATH . '/includes/basket/submit_checkout.class.php'   );	
			require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
			require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
			require_once( USAM_FILE_PATH . '/includes/theme/banners_query.class.php' );
			require_once( USAM_FILE_PATH . '/includes/basket/coupons_query.class.php' );
			require_once(USAM_FILE_PATH.'/includes/customer/user_posts_query.class.php');
			
			require_once( USAM_FILE_PATH . '/includes/seo/seo-frontend.class.php' );//Аналитика	

			add_filter( 'body_class', array(&$this, 'body_class') );
			add_filter( 'nav_menu_css_class', array(&$this, 'nav_menu_css_class'), 10, 4);		
			add_action( 'template_redirect', [&$this, 'feed_rss'], 2 );			

			add_action( 'wp_head', [&$this, 'head'] );	
			add_action('wp_footer', [&$this, 'footer'], 1);				
			
			add_filter( 'pre_option_site_icon', '__return_zero');
			add_filter( 'get_site_icon_url', [&$this, 'get_site_icon_url'], 10, 3);
		}			
		require_once(USAM_FILE_PATH . '/includes/query/usam_theme_query.class.php');	
		add_action('wp',array( &$this, 'wp_load'), 1);		
		add_action('request',array(&$this, 'request'), 1 );
		
		if ( get_option('usam_website_type', 'store' ) != 'price_platform' )
		{
			if ( isset($_REQUEST['click_pay']) )
				add_action('init',array( &$this, 'controller_click_pay') );
		}
	
		if ( !empty($_REQUEST['catalog']) ) 
		{ 
			$catalog = sanitize_title($_REQUEST['catalog']); 
			$cookie_key = 'usamcatalogid';	
			if ( empty($_COOKIE[$cookie_key]) || $_COOKIE[$cookie_key] != $catalog )
			{	
				setcookie( $cookie_key, $catalog, USAM_CUSTOMER_DATA_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );				
				$_COOKIE[$cookie_key] = $catalog;
			}
		}	
		if ( isset($_GET['language']) )
		{			
			$cookie_key = 'usamlang';
			$language = sanitize_title($_GET['language']);
			setcookie( $cookie_key, $language, USAM_CUSTOMER_DATA_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );		
			$_COOKIE[$cookie_key] = $language;			
		}
		if ( get_option("usam_attachment_redirect", '1') === '1' )
			add_action( 'template_redirect', [&$this, 'attachment_redirect']);
		
		add_action('usam_process_completed', [&$this, 'process_completed'], 10, 2 );	
		add_action('wp_body_open', [&$this, 'body_open']);			
	}
	
	public function get_site_icon_url( $url, $size, $blog_id ) 
	{		
		$icon = get_option('usam_cahe_site_icon', []);			
		if ( !empty($icon[$size]) )
			return $icon[$size];
		else
		{			
			remove_filter( 'get_site_icon_url', [&$this, 'get_site_icon_url'], 10, 3);
			remove_filter( 'pre_option_site_icon', '__return_zero');
			$url = get_site_icon_url( $size, $url, $blog_id );	
			$icon[$size] = $url;
			update_option('usam_cahe_site_icon', $icon);		
			return $url;
		}
	}
	
	function body_open()
	{
		usam_theme_banners(['banner_location' => 'body_open', 'class' => 'banner_body_open', 'number' => 1]);
	}
					
	function api( $query )
	{			
		if ( !empty($query->query_vars['pagename']) && $query->query_vars['pagename'] == 'api' && isset($query->query_vars['program']) )
		{ 			
			switch ( $query->query_vars['program'] ) 
			{					
				case 'google-auth' : // /api/google-auth
					require_once(USAM_FILE_PATH . '/includes/feedback/google_auth.class.php' );
					$vk = new USAM_Google_Auth();
					$vk->auth();					
				break;		
				case 'facebook-auth' : // /api/facebook-auth
					require_once(USAM_FILE_PATH . '/includes/feedback/facebook_auth.class.php' );
					$fb = new USAM_Facebook_Auth();
					$fb->auth();					
				break;
				case 'vk-auth' : // /api/vk-auth
					require_once(USAM_FILE_PATH . '/includes/feedback/vkontakte_auth.class.php' );
					$vk = new USAM_VKontakte_Auth();
					$vk->auth();					
				break;	
				case 'ok-auth' : // /api/ok-auth
					require_once(USAM_FILE_PATH . '/includes/feedback/ok_auth.class.php' );
					$ok = new USAM_OK_Auth();
					$ok->auth();					
				break;				
				case 'vk' : // /api/vk
					require_once(USAM_FILE_PATH . '/includes/feedback/vkontakte_notifications.class.php' );
				break;	
				case 'telegram' : // /api/telegram
					if ( usam_is_license_type('BUSINESS') || usam_is_license_type('ENTERPRISE') )
					{
						require_once(USAM_FILE_PATH . '/includes/feedback/telegram.class.php' );	
						$telegram = new USAM_Telegram();
						$telegram->notifications();					
					}
				break;
				case 'viber' : // /api/viber
					if ( usam_is_license_type('BUSINESS') || usam_is_license_type('ENTERPRISE') )
					{
						require_once( USAM_FILE_PATH . '/includes/feedback/viber.class.php' );	
						$viber = new USAM_Viber();
						$viber->notifications();					
					}
				break;	
				case 'facebook' : // /api/facebook
					if ( usam_is_license_type('BUSINESS') || usam_is_license_type('ENTERPRISE') )
					{
						require_once( USAM_FILE_PATH . '/includes/feedback/facebook.class.php' );	
						$facebook = new USAM_Facebook();
						$facebook->notifications();					
					}
				break;	
				case 'skype' : // /api/skype
					if ( usam_is_license_type('BUSINESS') || usam_is_license_type('ENTERPRISE') )
					{
						require_once( USAM_FILE_PATH . '/includes/feedback/skype.class.php' );	
						$skype = new USAM_Skype();
						$skype->notifications();					
					}
				break;											
				case '1c' :
					if ( usam_is_license_type('SMALL_BUSINESS') || usam_is_license_type('BUSINESS') || usam_is_license_type('ENTERPRISE') )
						require_once(USAM_FILE_PATH . '/includes/exchange/1C_api.class.php' );					 					
				break;	
				case 'shipping' :
					if ( isset($_GET['id']) )
					{
						$id = absint($_GET['id']);	
						$shipping = usam_get_shipping_class( $id );	
						$result = $shipping->notifications( );					
					}
				break; 									
			}	
			exit;
		}	
	}
	
	function wp_load( $query )
	{	
		$this->prevent_caching( $query );
		$this->api( $query );
	}
	
	function prevent_caching( $query )
	{	
		if ( ! is_blog_installed() )
			return;		
		global $post;
		if ( !empty($post) && in_array($post->post_name, ['pay_order', 'tracking', 'compare', 'wish-list', 'basket', 'checkout', 'transaction-results', 'your-account', 'your-subscribed', 'search']) )
			self::set_nocache_constants();
	}
		
	public static function set_nocache_constants( )
	{
		usam_set_nocache_constants();
		nocache_headers();
	}

	function process_completed( $type, $event ) 
	{	
		require_once( USAM_FILE_PATH . '/includes/product/product_exporter.class.php' );
		require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
		$rules = usam_get_exchange_rules(['type' => 'product_import']);		
		foreach($rules as $rule )
		{
			if ( $type == "exchange_{$rule->type}-{$rule->id}" )
			{			
				$rules = usam_get_exchange_rules(['type' => 'pricelist', 'meta_key' => 'file_generation', 'meta_value' => 'rule_'.$rule->id, 'schedule' => 1]);
				foreach( $rules as $rule )
				{			
					$export = new USAM_Product_Exporter( $rule->id );
					$i = $export->get_total();		
					usam_create_system_process( __("Создание прайс-листа", "usam").' - '.$rule->name, $rule->id, 'pricelist_creation', $i, 'exchange_'.$rule->type."-".$rule->id );	
				}	
				break;
			}			
		}
	}
		
	function attachment_redirect() 
	{
		global $post;
		if ( is_attachment() ) 
		{
			if( $post->post_parent )
				wp_redirect( get_permalink( $post->post_parent ), 301 );
			else
				wp_redirect( home_url(), 301 );
			exit();
		}
	}
	
	function footer( )
	{				
		if ( is_user_logged_in() && !usam_check_current_user_role('subscriber') )
			require_once( USAM_FILE_PATH.'/admin/tape.php' );
		
		if ( apply_filters( 'usam_show_button', true ) )
		{
			$chat_option = get_option( "usam_chat" );
			if ( !empty($chat_option['show_button']) )
				include_once( usam_get_template_file_path( 'chat-button', 'template-parts' ) ); 
		}
		if ( get_site_option("usam_popup_adding_to_cart", 'popup') == 'popup' )
			include_once( usam_get_template_file_path( 'popup-add-to-cart', 'checkout' ) ); 
		elseif ( get_site_option("usam_popup_adding_to_cart", 'popup') == 'sidebar' && !is_page('basket') && !is_page('checkout') )
			include_once( usam_get_template_file_path( 'popup-sidebar-add-to-cart', 'checkout' ) ); 
		if ( get_option("usam_cookie_notice") && empty($_COOKIE['cookienotice']) )
		{
			?><div class="cookie_notice"><div class="cookie_notice__message"><noindex><?php echo get_option("usam_cookie_notice"); ?></noindex></div><button class="button main-button cookie_notice__close js-cookie-notice-close">Ок</button></div><?php			
		}	
		if ( usam_check_current_user_role( 'administrator' ) || usam_check_current_user_role('shop_manager') )
		{
			?>		
			<style>	
				.change_block{width:100%; border-bottom:2px solid #004677; position:relative;}
				.change_block__title{position:absolute; right:0; top:0; font-size:14px; line-height:1; background-color:#004677; padding:5px 10px; z-index:9; text-transform:none;}
				.change_block__title a{color:#ffffff!important;}
				.change_home_block{width:100%; border-bottom:2px solid #446084; position:relative; padding:5px}
				.change_home_block__active{}
				.change_block__sort{color:#ffffff;}				
				.edit_banner{position:absolute!important; right:0!important; top:0!important; font-size:12px!important; background-color:#446084!important; padding:3px 5px!important; z-index:9!important; color:#ffffff!important; cursor:pointer!important; display:none!important;}
				.usam_banner:hover .edit_banner{display:block!important;}
				.screen-reader-text,
				.screen-reader-text span,
				.ui-helper-hidden-accessible {border: 0; clip: rect(1px, 1px, 1px, 1px); -webkit-clip-path: inset(50%); clip-path: inset(50%); height: 1px; margin: -1px;overflow: hidden; padding: 0; position: absolute; width: 1px; word-wrap: normal !important; }
				.button .screen-reader-text {	height: auto;}
				.screen-reader-shortcut {position: absolute; top: -1000em;	}
				.screen-reader-shortcut:focus {
					left: 6px; top: -25px; height: auto; width: auto; display: block; font-size: 14px; font-weight: 600; padding: 15px 23px 14px; background: #f1f1f1; color: #0073aa; z-index: 100000; line-height: normal; box-shadow: 0 0 2px 2px rgba(0, 0, 0, 0.6); text-decoration: none; /* Only visible in Windows High Contrast mode */
					outline: 2px solid transparent; outline-offset: -2px;				
				}
				.media-router .media-menu-item{color:#000000!important;}
			</style>				
			<?php			
		}
		usam_include_template_file( 'modal-window', 'vue-templates' ); 
		usam_include_template_file( 'hint-window', 'vue-templates' ); 
		if( usam_chek_user_product_list('subscription') ) 
			usam_include_template_file('product-subscription', 'modaltemplate');
	}
		
	function head( )
	{
		$sliders = usam_register_sliders( );
		foreach ($sliders as $key => $slider)
		{
			$anonymous_function = function() use ( $key )
			{ 
				usam_theme_sliders( $key );
			};				
			add_action('usam_'.$key, $anonymous_function );	
		}	
		$blocks = usam_get_html_blocks();
		foreach ($blocks as $key => $block)
		{			
			if( !empty($block['hooks']) )
			{
				$block = apply_filters( 'usam_block_output', $block );
				$anonymous_function = function() use ( $block )
				{ 
					include( usam_get_template_file_path( 'html-blocks', 'template-parts' ) );
				};	
				foreach ($block['hooks'] as $hook)
					add_action('usam_'.$hook, $anonymous_function );	
			}
		}		
		$site_style = usam_get_site_style();
		?>
		<style id="tmpl-color-scheme-css">		
			:root{			
				<?php 
				foreach( $site_style as $key => $style )
				{ 
					$v = get_theme_mod( $key, $style['default'] );
					if( $v )
						echo "--$key: ".$v.";"; 
				} ?>
			}
			<?php 
			if( isset($site_style['body-color']) )
			{
				$v = get_theme_mod( 'body-color', $site_style['body-color']['default'] );
				if( $v )
				{
					?>
					body{background-color:<?php echo $v; ?>}
					<?php 
				}
			}
			foreach( $site_style as $key => $style )
			{ 
				if ( $style['type'] == 'color' )
				{
					?>
					.has-<?php echo $key; ?>-color{color:<?php echo get_theme_mod( $key, $style['default'] ); ?>}
					.has-<?php echo $key; ?>-background-color{background-color:<?php echo get_theme_mod( $key, $style['default'] ); ?>}
					<?php 
				}
			} 
			?>			
		</style>
		<?php
		$this->save_page();
	}
	
	function save_page()
	{ 				
		global $post;
		if ( empty($post) || ($post->post_name != 'your-account' && $post->post_name != 'login' && $post->post_name != 'transaction-results') )
			$this->insert_page_viewed();
	}
	
	function insert_page_viewed(  )
	{
		if ( usam_is_bot() || is_feed() )
			return false;
				
		global $post;					 
		if ( !usam_check_is_employee() && !current_user_can('store_section') && (empty($_SERVER['HTTP_REFERER']) || !str_contains($_SERVER['HTTP_REFERER'], '/wp-content/uploads/')) )
		{
			$id = usam_insert_page_viewed();			
			if ( !empty($post->ID) )
			{
				$count = usam_get_post_meta($post->ID, 'views' );	 
				if( !$count )	
					$count = 0;					
				$count++;				
				usam_update_post_meta($post->ID, 'views', $count); 
			}
			return $id;
		}	
		return false;
	}
	
	function feed_rss(  )
	{		
		global $wp_query;
		if( $wp_query->is_feed() && $wp_query->query_vars['post_type'] == 'usam-product' )
		{ 
			update_post_thumbnail_cache( $wp_query );
			header( "Content-Type: application/xml; charset=UTF-8" );
			header( 'Content-Disposition: inline; filename="usam_product_list.rss"' );
			require_once(USAM_FILE_PATH . '/includes/theme/rss_template.php');
			exit();
		}		
	}	
	
	function nav_menu_css_class( $classes, $item, $args = null, $depth = 0 )
	{
		$catalog = usam_get_active_catalog();
		if ( $catalog )
		{ 
			if ( $item->object == 'usam-catalog' && $item->type == 'taxonomy' )
			{					
				if ( !empty($catalog) && $item->object_id == $catalog->term_id )
				{
					if ( !in_array('current-menu-item', $classes) )
						$classes[] = 'current-menu-item';
				}
				else
				{
					$k = array_search('current-menu-item', $classes);
					if ( $k !== false )
						unset($classes[$k]);
				}					
			}
		}		
		return $classes;
	}
	
	function page_viewed_insert( $t )
	{ 
		$data = $t->get_data();
		if ( isset($_COOKIE['advertising_campaign']) )
			usam_update_visit_metadata($data['visit_id'], 'campaign_id', $_COOKIE['advertising_campaign'] );
	}
		
	function request( $args )
	{	
		$code = '';
		if ( !empty($args['pagename']) && $args['pagename'] == 'advertising_campaign' && !empty($args['campaign']) )
			$code = sanitize_title($args['campaign']);	
		elseif ( !empty($_GET['utm_campaign']) )
			$code = sanitize_title($_GET['utm_campaign']);	
		if ( $code )	
		{ 
			$campaign = usam_get_advertising_campaign( $code, 'code' );
			if ( !empty($campaign) )
			{ 
				$contact_id = usam_get_contact_id();
				$contact = usam_get_contact( $contact_id );
				if ( empty($contact['contact_source']) || $contact['contact_source'] != 'employee' )
				{
					$campaign['transitions']++;
					usam_update_advertising_campaign( $campaign['id'], $campaign );					
					usam_insert_campaign_transition(['contact_id' => $contact_id, 'campaign_id' => $campaign['id']]);
					
					$cookie_key = 'advertising_campaign';
					setcookie( $cookie_key, $campaign['id'], time()+86400, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
					$_COOKIE[$cookie_key] = $campaign['id'];			
					add_action('usam_page_viewed_insert', [$this, 'page_viewed_insert']);						
				}							
				$this->insert_page_viewed();									
				if ( !empty($campaign['redirect']) )
				{					
					wp_redirect( $campaign['redirect'] ); 
					exit;
				}
			}			
		}
		if ( !empty($args['pagename']) )
		{ 
			if ( $args['pagename'] == 'r' && !empty($args['code']) )
			{				
				$id = sanitize_text_field( $args['code'] );	
				$cookie_key = 'reflink'.$id;
				if ( empty($_COOKIE[$cookie_key]) )
				{
					setcookie( $cookie_key, true, USAM_CUSTOMER_DATA_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
					$_COOKIE[$cookie_key] = true;								
					$this->insert_page_viewed();
					do_action( 'usam_open_referral_url', $id);		
				}						
				wp_redirect( get_bloginfo('url') ); 
				exit();
			
			}
			elseif ( $args['pagename'] == 'c' && !empty($args['code']) )
			{				
				$coupon_code = sanitize_text_field( $args['code'] );	
				$coupon = usam_get_coupon( $coupon_code, 'coupon_code' );
				if ( !empty($coupon['active']) )
				{
					$contact_id = usam_get_contact_id();
					usam_update_contact_metadata($contact_id, 'coupon_code', $coupon_code );
					if ( $coupon['coupon_type'] == 'referral' )
						usam_update_contact_metadata($contact_id, 'referral', $coupon_code );					
				}
				$this->insert_page_viewed();
				wp_redirect( get_bloginfo('url') ); 
				exit();
			}			
			elseif ( $args['pagename'] == 'file' && !empty($args['code']) )
			{
				$file_code = sanitize_text_field( $args['code'] );	
				$file = usam_get_file( $file_code, 'code' );		
				if ( empty($file) )
					wp_die( __('Ничего не найдено.', 'usam') );
											
				$user_id = get_current_user_id();
				if ( !current_user_can('view_files') && $file['status'] !== 'open' && $file['status'] !== 'limited' && ($file['user_id'] !== $user_id || $file['user_id'] === 0) )
				{ 	
					$download_file = usam_get_files(['fields' => ['download_id', 'ip_number', 'downloads'], 'purchased_user_files' => $user_id, 'active' => 1, 'code' => $file['code'], 'number' => 1]);
					if ( !empty($download_file) )
					{
						$message = __('Данная загрузка больше не доступна, пожалуйста, свяжитесь с администратором сайта для получения дополнительной информации.', 'usam');				
						if ( !empty($download_file->downloads) )
						{	
							$update = array();
							if ( (get_option( 'usam_ip_lock_downloads', 0 ) == 1) && ($_SERVER['REMOTE_ADDR'] != null) ) 
							{	
								$ip_number = $_SERVER['REMOTE_ADDR'];
								if ( $download_file->ip_number == '' ) 
									$update['ip_number'] = $ip_number;	
								elseif ( $ip_number != $download_file->ip_number ) 				
									wp_die( $message );	
							}
							$update['downloads'] = $download_file->downloads - 1;					
							$download_status = new USAM_PRODUCT_DOWNLOAD_STATUS( $download_file->download_id );	
							$download_status->set( $update );
							$download_status->save();					
						}
						else
							wp_die( $message );
					}
					elseif ( !usam_get_files(['user_file' => $user_id, 'id' => $file['id']]) )
						wp_die( __('Ничего не найдено.', 'usam') );
				}
				$filepath = USAM_UPLOAD_DIR.$file['file_path'];		
				if ( is_file($filepath) )
				{
					if ( isset($_REQUEST['size']) )
						$filepath = usam_get_given_image_size( $filepath, sanitize_title($_REQUEST['size']) );
					if ( !current_user_can('view_files') )	
					{						
						$maximum_load = (int)usam_get_file_metadata( $file['id'], 'maximum_load' );
						if ( $file['status'] == 'limited' )
						{
							if ( $maximum_load == 0 )
								wp_die(__('Файл не существует!', 'usam'));
							$maximum_load--;
							usam_update_file_metadata( $file['id'], 'maximum_load', $maximum_load );
						}
						$file['uploaded']++;
						usam_update_file( $file['id'], $file );
					}				
					usam_download_file( $filepath, $file['file_name'] );
					exit();
				}
				else
					wp_die(__('Файл не существует!', 'usam'));
			}
			elseif ( $args['pagename'] == 'show_file' && !empty($args['code']) )
			{
				$file_code = sanitize_text_field( $args['code'] );	
				$file = usam_get_file( $file_code );
				if ( !$file )
					wp_die( __('Неверный идентификатор файла.', 'usam') );

				$filepath = USAM_UPLOAD_DIR.$file['file_path'];					
				if ( is_file($filepath) )
				{ 					
					if ( isset($_REQUEST['size']) )
						$filepath = usam_get_given_image_size( $filepath, sanitize_title($_REQUEST['size']) );					
					usam_download_file( $filepath, $file['file_name'] );	
					exit();
				}
				else
					wp_die(__('Файл не существует!', 'usam'));
			}			
		}		
		return $args;
	}
	
	public function controller_click_pay( )
	{		
		$product_id = absint($_REQUEST['click_pay']);		
		$seller_id = usam_get_product_meta( $product_id, 'seller_id' );
		if ( $seller_id )
		{
			$commission_seller = get_option('usam_sales_commission_seller', 5 );
			usam_insert_marketplace_commission(['status' => 'approved', 'seller_id' => $seller_id, 'sum' => $commission_seller]);	
		}
		$link = usam_get_product_meta($product_id, 'webspy_link' );
		if ( $link )
		{
			wp_redirect( $link ); 
			exit;			
		}
	}
		
	// Фильтр основной части. Добавляет дополнительные классы к тегу body.
	function body_class( $classes )
	{
		global $wp_query, $usam_query;	
		
		if ( empty($wp_query->post) )
			return $classes;
		
		$post_content = isset($wp_query->post->post_content) ? $wp_query->post->post_content : '';	
		// если категория или товар...
		if ( usam_is_in_category() )
		{
			$classes[] = 'usam';
			$classes[] = 'usam-category';	
		}
		elseif(  usam_is_product() )
		{	
			$classes[] = 'usam';	
			$classes[] = 'usam-single-product';				
		}	
		elseif( is_page() )
		{
			$classes[] = 'usam-page-'.$wp_query->post->post_name;		
			$virtual_page = usam_get_virtual_page( $wp_query->post->post_name );	
			if ( !empty($virtual_page) ) 	
				$classes[] = 'usam';
			switch ( $wp_query->post->post_name ) 
			{
				case 'checkout' :
					$classes[] = 'usam-checkout';
				break;			
				case 'basket' :
					$classes[] = 'usam-basket';
				break;		
				case 'your-account' :
					$account_current = usam_your_account_current_tab();
					$classes[] = 'usam-account-'.$account_current['tab'];
				break;				
			}	
		}	
		return $classes;
	}	
		
	//Установка корзины
	function core_setup_cart() 
	{		
		if ( usam_is_bot() || is_feed() || isset($_REQUEST['usam_ajax_action']) || isset($_REQUEST['usam_action']) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
			return false;
		usam_core_setup_cart( false );
	}
}
new USAM_Load_Site();
?>