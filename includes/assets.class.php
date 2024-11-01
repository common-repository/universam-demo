<?php
/**
 * Загрузка стилей и скриптов.
 */

class USAM_Assets 
{		
	private static $suffix = '';
	
	public function __construct() 
	{						
		self::$suffix = defined('WP_DEBUG') && WP_DEBUG ? '.min' : '';	
		
		add_action( 'init', [$this, 'register'] );
		
		add_action( 'wp_head', [$this, 'api'], 1 );				
		add_action( 'admin_head', [$this, 'api'], 1 );
		add_action( 'customize_controls_enqueue_scripts', [$this, 'api'], 1 );		
		
		add_action( 'admin_footer', [$this, 'admin_footer'], 4 );
		add_action( 'wp_footer', [$this, 'site_footer'], 4 );
		
		add_action( 'wp_enqueue_scripts', array( $this, 'theme_script_and_css' ), 5 );				
		add_filter( 'script_loader_tag', array( $this, 'mihdan_add_defer_attribute' ), 10, 2 );	
		add_action( 'customize_controls_enqueue_scripts', [__CLASS__, 'customize_control']);
		add_action( 'customize_preview_init', array(__CLASS__,'customize_preview') );
	}
	
	function mihdan_add_defer_attribute( $tag, $handle ) 
	{    
		$handles = [
			'usam-checkout-page',
			'usam-product_filter',
			'bootstrap',			
			'tab',	
			'usam-admin_bar',
			'usam-tape',
		]; 
		foreach( $handles as $defer_script) 
		{
			if ( $defer_script === $handle ) 
			{
				return str_replace( ' src', ' defer="defer" src', $tag );
			}
		}
		return $tag;
	}		
		
	public function api( ) 
	{  		
		global $wp_query;
		$contact = usam_get_contact();		
		
		if ( is_admin() )
			$type_price = usam_get_manager_type_price();
		else
			$type_price = usam_get_customer_price_code();
		
		$price_setting = usam_get_setting_price_by_code( $type_price );
		$currency = '';
		if ( isset($price_setting['currency']) )
		{
			$currency_data = usam_get_currency( $price_setting['currency'] );
			if ( $currency_data )
				$currency = !empty($currency_data['symbol_html']) ? $currency_data['symbol_html'] : $currency_data['symbol'];
		}
		//SimplePie_Parse_Date::$month
		$setting = [
			'resturl' => rest_url('/usam/v1/'),
			'wp_resturl' => rest_url('wp/v2/'),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		];
		?>
		<script>
			var usamSettings=<?php echo json_encode( $setting ); ?>;		
			var ajaxurl = "<?php echo is_admin()?admin_url('admin-ajax.php', 'relative'):get_home_url(); ?>";
			var svgicon = "<?php echo file_exists(get_template_directory().'/assets/sprite.svg') ? get_template_directory_uri().'/assets/sprite.svg' : USAM_CORE_THEME_URL.'assets/sprite.svg'; ?>";
			var usamstr = "<?php echo usam_create_ajax_nonce( 'nonce_fields' ) ?>";
			var currency = "<?php echo $currency; ?>";	
			var thousands_separator = "<?php echo get_option('usam_thousands_separator', '.') ?>";
			var decimal_separator = "<?php echo get_option('usam_decimal_separator', ',') ?>";
			var keyword_search = "<?php echo isset($wp_query->query_vars) && isset($wp_query->query_vars['keyword'])?addslashes($wp_query->query_vars['keyword']):'' ?>";		
		</script>
		<?php
	}
	
	public function admin_footer( $pagehook ) 
	{  		
		$this->admin_bar();		
	}
	
	public function site_footer( $pagehook ) 
	{  		
		if ( is_admin_bar_showing()  )
			$this->admin_bar();					
	}
	/**
	 * Загрузка скриптов
	 */	 
	public function admin_bar( ) 
	{  	
		if ( usam_check_current_user_role( 'administrator' ) || usam_check_current_user_role('shop_manager') )
		{
			wp_enqueue_script( 'usam-admin_bar' );
			wp_localize_script( 'usam-admin_bar', 'USAM_Admin_Bar', [
				'number_of_unread_menager_chat_messages_nonce' => usam_create_ajax_nonce( 'number_of_unread_menager_chat_messages' ),
				'update_consultant_status_nonce'       => usam_create_ajax_nonce( 'update_consultant_status' ),								
				]
			);			
			wp_enqueue_style( 'usam-admin-bar' );												
		}	
		if ( !usam_check_current_user_role('subscriber') )
		{				
			wp_enqueue_style( 'usam-tape' );
			wp_enqueue_script( 'usam-tape' );		
		}		
	}	
	
	/**
	 * Зарегистрировать стили и скрипты
	 */
	public function register( ) 
	{		
		$scripts = [
			'universam' => ['file_name' => 'universam', 'deps' => ['jquery', 'vue'], 'in_footer' => false],
			'usam-theme' => ['file_name' => 'universam.theme', 'deps' => ['jquery', 'vue', 'v-mask', 'universam'], 'in_footer' => true],
			'usam-rating' => array( 'file_name' => 'rating', 'deps' => array( 'jquery' ), 'in_footer' => true),//Рейтинг
			'hc-sticky' => array( 'file_name' => 'jquery.hc-sticky.min', 'deps' => array( 'jquery' ), 'in_footer' => false),//Фиксированное меню		
			'usam-graph' => array( 'file_name' => 'graph', 'deps' => array( 'jquery', 'd3' ), 'in_footer' => false),//График			
			'usam-zoo' => array( 'file_name' => 'zoo', 'deps' => array( 'jquery' ), 'in_footer' => true),			
			'usam-admin_bar' => array( 'file_name' => 'admin_bar', 'deps' => array( 'jquery' ), 'in_footer' => true),
			'usam-tape' => array( 'file_name' => 'tape', 'deps' => array( 'jquery' ), 'in_footer' => true),
			'bootstrap' => array( 'file_name' => 'bootstrap', 'deps' => array( 'jquery' ), 'in_footer' => true),//Модальные окна
			'usam-tab' => array( 'file_name' => 'tab', 'deps' => array( 'jquery' ), 'in_footer' => true),// вкладки			
			'fileupload' => array( 'file_name' => 'jquery.fileupload', 'deps' => array( 'jquery','jquery-ui-widget' ), 'in_footer' => true),// загрузка файлов
			'iframe-transport' => array( 'file_name' => 'jquery.iframe-transport', 'deps' => array( 'jquery' ), 'in_footer' => true),// загрузка файлов	
			'knob' => array( 'file_name' => 'jquery.knob', 'deps' => array( 'jquery' ), 'in_footer' => true),// загрузка файлов			
			'owl-carousel' => array( "cdn" => "cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js", 'deps' => array( 'jquery' ), 'in_footer' => true, 'version' => '2.3.4' ),// слайдер
			'chosen' => array('cdn' => "cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js", 'deps' => array(), 'in_footer' => true, 'version' => '1.8.7'),// красивые селекты
			'd3' => array('cdn' => "cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js", 'deps' => array(), 'in_footer' => true, 'version' => '6.5.0'),//График
			'vue' => ['cdn' => "unpkg.com/vue@2.7.0/dist/vue.min.js", "development" => "unpkg.com/vue@2.7.0/dist/vue.js", 'deps' => [], 'in_footer' => false, 'version' => '2.7.0'],	
		//	'vue' => ['cdn' => "unpkg.com/vue@3.4.31/dist/vue.global.js", "development" => "unpkg.com/vue@3.4.31/dist/vue.global.js", 'deps' => [], 'in_footer' => false, 'version' => '3.4.31'],
			'vue-demi' => ['cdn' => "unpkg.com/vue-demi@0.14.10/lib/index.iife.js", 'deps' => ['vue'], 'in_footer' => false, 'version' => '2.2.2'],
			'pinia' => ['cdn' => "unpkg.com/pinia@2.2.2/dist/pinia.iife.js", 'deps' => ['vue','vue-demi'], 'in_footer' => false, 'version' => '2.2.2'],				
		
			'v-mask' => ['cdn' => "unpkg.com/v-mask@2.3.0/dist/v-mask.min.js", 'deps' => ['vue'], 'in_footer' => false, 'version' => '6.1.2'],		
			'v-color' => ['cdn' => "cdnjs.cloudflare.com/ajax/libs/vue-color/2.8.1/vue-color.min.js", 'deps' => ['vue'], 'in_footer' => false, 'version' => '2.8.1'],
		//	'v-calendar' => ['cdn' => "cdn.jsdelivr.net/npm/v-calendar@2.4.2/lib/v-calendar.umd.min.js", 'deps' => [], 'in_footer' => false, 'version' => ''],					
			'v-calendar' => ['cdn' => "unpkg.com/v-calendar@2.4.2/lib/v-calendar.umd.min.js", 'deps' => [], 'in_footer' => false, 'version' => ''],						
		//	'qs' => array( 'file_name' => "qs.min", 'deps' => array('jquery'), 'in_footer' => false),			
		];
/*
v-mask
#	Number (0-9)
A	Letter in any case (a-z,A-Z)
N	Number or letter
X	Any symbol
?	Optional (next character)
		*/
		foreach( $scripts as $name => $script )
		{ 
			if ( isset($script['cdn']) )
			{
				if ( defined('WP_DEBUG') && WP_DEBUG && isset($script['development']) )
					wp_register_script( $name, 'https://'.$script['development'], $script['deps'], $script['version'], $script['in_footer'] );
				else
					wp_register_script( $name, 'https://'.$script['cdn'], $script['deps'], $script['version'], $script['in_footer'] );
			}
			else
				wp_register_script( $name, USAM_CORE_JS_URL.$script['file_name'].'.js', $script['deps'], USAM_VERSION_ASSETS, $script['in_footer'] );
		}		
		wp_register_script( 'yandex_maps', "https://api-maps.yandex.ru/2.1/?lang=ru_RU", array(), USAM_VERSION_ASSETS, true );			
		$styles = [ 
			'owl-carousel' => array( 'file_name' => 'owl.carousel.min', 'deps' => array(), 'media' => 'all' ),	//слайдер		
			'usam-tape' => array( 'file_name' => 'tape', 'deps' => array(), 'media' => 'all' ),
			'usam-silder-filter' => array( 'file_name' => 'silder-filter', 'deps' => array(), 'media' => 'all' ),
		];		
		foreach( $styles as $name => $style )
		{ 
			if ( isset($style['cdn']) )
				wp_register_style( $name, 'https://'.$style['cdn'], $style['deps'], USAM_VERSION_ASSETS, $style['media'] );	
			else
				wp_register_style( $name, USAM_URL . '/assets/css/'.$style['file_name'].'.css', $style['deps'], USAM_VERSION_ASSETS, $style['media'] );	
		}
		wp_register_style( 'usam-admin-bar', USAM_URL .'/admin/assets/css/admin-bar.css', false, USAM_VERSION_ASSETS );		
		wp_register_style( 'usam-global-style', usam_get_template_file_url('usam-global-style.css'), false, USAM_VERSION_ASSETS );
	//	wp_register_style( 'usam-bloks-style', usam_get_template_file_url('usam-bloks-style.css'), false, USAM_VERSION_ASSETS );
		
		$baseurl = includes_url( 'js/tinymce' );
		wp_register_script( 'wp-tinymce', includes_url( 'js/tinymce' )."/tinymce.min.js", array( 'jquery' ), USAM_VERSION_ASSETS );				
		
		$localize_script = [
			'js_url'            => USAM_CORE_JS_URL,
			'get_modal'         => usam_create_ajax_nonce( 'get_modal' ), 		
			'item_delete_text'  => __('Элемент удален', 'usam'),
			'action_error'      => __('Ошибка выполнения', 'usam')			
		];	
		wp_localize_script( 'universam', 'UNIVERSAM', $localize_script );
	}
	
	public static function theme_register_css() 
	{		
		$styles = [					
			'usam-theme' => ['file_name' => 'usam-default', 'deps' => false, 'media' => 'all'],			
		];		
		foreach( $styles as $name => $style )
		{
			wp_register_style( $name, usam_get_template_file_url( $style['file_name'].'.css' ), $style['deps'], USAM_VERSION_ASSETS, $style['media'] );	
		}
	}
	public static function customize_preview() 
	{
		wp_enqueue_script( 'usam-customize-preview', USAM_CORE_JS_URL . 'customize-preview.js', array( 'customize-preview' ), USAM_VERSION_ASSETS, true );
	}
	
	public static function customize_control() 
	{		
		wp_enqueue_script( 'usam-color-scheme-control', USAM_CORE_JS_URL . 'color-scheme-control.js', ['customize-controls', 'iris', 'underscore', 'wp-util'], USAM_VERSION_ASSETS, true );	
		
		$color_scheme = usam_get_site_color_scheme();
		$styles = usam_get_site_style();	
		foreach( $color_scheme as &$style_scheme )
		{
			foreach( $styles as $k => $style )
				$style_scheme['styles'][$k] = isset($style_scheme['styles'][$k]) ? $style_scheme['styles'][$k] : $style['default'];	
		}		
		wp_localize_script( 'usam-color-scheme-control', 'Color_Scheme', $color_scheme );
	}
	
	/**
	 * поставить в очередь все JavaScript и CSS
	 */
	function theme_script_and_css() 
	{ 
		global $post;		
		wp_enqueue_script( 'jquery' );	
		wp_enqueue_script( 'vue');		
		wp_enqueue_script('v-mask');						
		
		wp_enqueue_script( 'universam' );
		self::theme();
		
		if ( has_filter( 'usam_enqueue_user_script_and_css' ) && apply_filters( 'usam_scripts_css_filters', false ) )
			do_action( 'usam_enqueue_user_script_and_css' );	
		else 
		{			
			self::theme_register_css();				
			
			wp_enqueue_style( 'usam-global-style' );
		//	wp_enqueue_style( 'usam-bloks-style' );
			wp_enqueue_style( 'usam-theme' );							
			wp_enqueue_script( 'usam-tab' );			
			wp_enqueue_script( 'owl-carousel' );						
			if ( !empty($post) )
			{ 
				if ( !empty($post->post_name) ) 
				{
					switch ( $post->post_name ) 
					{
						case 'basket' :
						case 'checkout' :							
							wp_enqueue_script( 'yandex_maps' );
							wp_enqueue_script( 'usam-basket-page', USAM_CORE_JS_URL. 'theme/basket-page.js', ['jquery'], USAM_VERSION_ASSETS, true );
						break;		
						case 'search' :
							self::product_filter();
						break;							
						case 'your-account' :
							wp_enqueue_script( 'usam-your-account-page', USAM_CORE_JS_URL. 'theme/your-account.js', ['vue'], USAM_VERSION_ASSETS, false );
						break;											
					}					
				}
			}					
			wp_enqueue_script( 'bootstrap' );	
			if ( usam_is_product() )
			{				
				wp_enqueue_script( 'usam-zoo' );	
				wp_enqueue_script( 'yandex_maps' );				
			}		
		}		
	}

	public static function theme( ) 
	{	
		wp_reset_postdata();
		global $post;
		wp_enqueue_script( 'usam-theme' );		
		$type_payer = usam_get_type_payer_customer();
		$localize_script = [							
			'notifi'            => ['position' => 'bottom', 'y' => 0, 'x' => 0],	
			'page_id'           => isset($post->ID) && $post->ID > 0? (int)$post->ID : 0,
			'message_search_result'        => __('Нет больше результатов для вывода', 'usam'),		
			'message_load_search_result'   => __('Загрузка еще результатов...', 'usam'),					
			'message_search_nothing_found' => __('Ничего не найдено', 'usam'),			
			'message_not_authorized' => __('Действие не доступно. Вы не вошли в свой профиль.', 'usam'),
			'message_product_added_comparelist' => __('Товар добавлен в список сравнения', 'usam'),		
			'message_product_removed_comparelist' => __('Товар удален из списка сравнения', 'usam'),	
			'message_product_added_wishlist' => __('Товар добавлен в избранное', 'usam'),		
			'message_product_removed_wishlist' => __('Товар удален из избранного', 'usam'),	
			'message_limit_products_list' => __('Больше нельзя добавлять', 'usam'),	
			'message_list_change_operation_error' => __('Товар не добавлен', 'usam'),	
			'message_subscribe'          => __('Вы успешно подписались на новости!', 'usam'),	
			'message_unsubscribe' => __('К сожалению мы больше не сможем вас информировать о новостях!', 'usam'),				
			'message_choose_options' => __('Выберете варианты, чтобы добавить товар', 'usam'),	
			'message_savepassword' => __('Пароль был изменен', 'usam'),	
			'message_saved' => __('Сохранено', 'usam'),	
			'message_deleted' => __('Удалено', 'usam'),	
		];		
		$localize_script = apply_filters( 'universam_localize_script', $localize_script );
		if( is_admin() )
			unset($localize_script['notifi']);
		wp_localize_script( 'usam-theme', 'USAM_THEME', $localize_script );	
	}
	
	public static function product_filter( ) 
	{
		global $wp_query, $usam_query;		
		if ( !empty($_GET['f']) )				
		{
			$select_filters = $_GET['f'];
			if ( !is_array($select_filters) )
				$select_filters = usam_url_array_decode($select_filters);
			foreach( $select_filters as $key => $value )
			{
				if ( is_string($value) )
					$select_filters[$key] = explode('-', $value);
			}				
		}
		else
			$select_filters = [];
		wp_enqueue_script( 'usam-product_filter',  USAM_CORE_JS_URL.'product-filter.js', ['vue', 'universam'], USAM_VERSION_ASSETS, true );			
		$query = isset($usam_query->query)?$usam_query:$wp_query;			
		if ( isset($query->query['name']) )
			unset($query->query['name']);
		if ( isset($query->query['usam-product']) )
			unset($query->query['usam-product']);		
		if ( empty($query->query['paged']) )
			$query->query['paged'] = 1;	
			
		$contact_id = usam_get_contact_id();
		wp_localize_script( 'usam-product_filter', 'USAM_Product_Filter', [
			'query' => $query->query,	
			'count' => (int)$query->found_posts,		
			'get_products_nonce' => wp_create_nonce("get_products"),	
			'select_filters' => $select_filters,
			'select_categories' => !empty($_GET['scat'])?explode('-', $_GET['scat']):array(),	
			'select_rating' => !empty($_GET['rating'])?explode('-', $_GET['rating']):array(),	
			'select_prices' => !empty($_GET['prices'])?explode('-', $_GET['prices']):array(),
			'type_price' => usam_get_customer_price_code(),
			'shop' => (int)usam_get_contact_metadata($contact_id, 'favorite_shop'),			
			'company' => (int)usam_get_select_company_customer(),
			'sort' => usam_get_customer_orderby('array'),
			'view_type' => usam_get_display_type()
		]);					
	}	
}
if ( get_option( 'usam_db_version', false ) )
	new USAM_Assets();
/*
Скрипты, которые идут в комплекте с WordPress
https://wp-kama.ru/function/wp_enqueue_script
*/
?>