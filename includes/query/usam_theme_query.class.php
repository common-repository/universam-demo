<?php
/**
 * Основные запрос в базу данных для шаблона
 */ 
new USAM_Theme_Query();
final class USAM_Theme_Query
{	
	public function __construct() 
	{		
		add_filter( 'query_vars', ['USAM_Theme_Query', 'query_vars'] );
		add_filter( 'pre_get_posts', ['USAM_Theme_Query', 'split_the_query'], 8 );
		if ( get_option('usam_website_type', 'store' ) != 'crm' )
		{						
			add_action( 'template_redirect', ['USAM_Theme_Query', 'start_the_query'], 8 );
			add_filter( 'request', ['USAM_Theme_Query', 'filter_query_request'],9 );
			add_action( 'posts_clauses', ['USAM_Theme_Query', 'posts_clauses'], 50, 2 );	
		} 
	}
	
	public static function posts_clauses( $clauses, $wp_query ) 
	{ 
		global $wpdb;			
		if ( isset($wp_query->query_vars['in_stock']) && !$wp_query->query_vars['in_stock'] && get_option('usam_display_sold_products', 'sort') == 'sort')
		{	
			$select_products = get_option('usam_types_products_sold', array('product', 'services'));
			if ( in_array('product', $select_products) || in_array('services', $select_products) )
			{// Работа с остатками только для товаров и услуг	
				$clauses['orderby'] = "IF(stock.meta_value>0,1,0) DESC,".$clauses['orderby'];
			}
		} 
		return $clauses;
	}
	
	public static function query_vars( $vars ) 
	{ 
		$vars[] = "api"; 
		$vars[] = "program"; 
		$vars[] = "campaign"; 		
		$vars[] = "post_type"; // post_type используется для указания того, что мы ищем продукты	
		$vars[] = "usam_page";		
		$vars[] = "tabs";      // tabs используется, чтобы найти страницу в профиле пользователя
		$vars[] = "subtab";    // subtab используется, чтобы найти страницу в профиле пользователя			
		$vars[] = "gateway_id";	
		$vars[] = "seller_id";			
		$vars[] = "id";	
		$vars[] = "code";
		$vars[] = "keyword";   
		$vars[] = "scat";
		$vars[] = "stag";
		$vars[] = "sku";
		$vars[] = "dialog_with_contact";
		$vars[] = "trading_platform";	
		$vars[] = "new_products";	
		$vars[] = "stock_products";
		$vars[] = "attribute";		
		return $vars;
	}	
	
	/**
	 * Исправления для некоторых несоответствий $wp_query при просмотре страниц. Причины следующие URL-адреса для работы (с нумерацией страниц с поддержкой):
	 */
	public static function filter_query_request( $args ) 
	{
		if ( !empty($args['keyword']) )
			$args['keyword'] = stripslashes(strip_tags(trim(rawurldecode($args['keyword']))));
		if ( !empty($args['usam-catalog']) )
		{
			$cookie_key = 'usamcatalogid';				
			if ( empty($_COOKIE[$cookie_key]) || $_COOKIE[$cookie_key] != $args['usam-catalog'] )
			{	
				setcookie( $cookie_key, $args['usam-catalog'], USAM_CUSTOMER_DATA_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
				$_COOKIE[$cookie_key] = $args['usam-catalog']; 
			}
		}		
		$product_list = basename(usam_get_url_system_page('products-list'));		
		if ( get_option( 'usam_category_hierarchical_url', 0 ) )
		{							
			if ( !empty($args['usam-product']) )
			{
				$components = explode( '/', $args['usam-product'] );
				$end_node = array_pop( $components );
				$parent_node = array_pop( $components );

				$posts = get_posts(['post_type' => 'usam-product', 'name' => $end_node]);
				if ( !empty( $posts ) )
				{
					$args['usam-product'] = $args['name'] = $end_node;
					if ( !empty( $parent_node ) )
						$args['usam-category'] = $parent_node;
				} 
				else 
				{
					$args['usam-category'] = $end_node;
					unset( $args['name'] );
					unset( $args['usam-product'] );
				}			
			}			
		}		
		else
		{		
			$is_sub_page = !empty( $args['usam-category'] ) && $args['usam-category'] != 'page' && ! term_exists($args['usam-category'], 'usam-category');
			// Убедитесь, что никакие ошибка 404 не создается для любых дополнительных страниц Товаров-странице
			if ( $is_sub_page )
			{						
				$pagename = "{$product_list}/{$args['usam-category']}";
				if ( isset($args['name']) )
					$pagename .= "/{$args['name']}";		
				$args = [];
				$args['pagename'] = $pagename;	
			} 
		}		
	// Когда странице продукта установлена для отображения всех продуктов или категории, а разбивку на страницы включена, $wp_query перепутались и is_home() равна истина. Это исправляет это.
		$needs_pagination_fix = isset($args['post_type'] ) && !empty($args['usam-category']) && 'usam-product' == $args['post_type'] && !empty( $args['usam-product'] ) && 'page' == $args['usam-category'];
		if ( $needs_pagination_fix ) 
		{ 
			$default_category = get_option( 'usam_default_category' );
			if ( $default_category == 'all' || $default_category != 'list' ) 
			{
				$page = $args['usam-product'];
				$args = array();
				$args['pagename'] = $product_list;
				$args['page'] = $page; 
			}
		}
		return $args;
	}	
	
	public static function split_the_query( $query )
	{ 
		if( $query->is_feed() && isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == 'usam-product' )
		{
			$query->query_vars['product_meta_cache'] = true;
			$query->query_vars['prices_cache'] = true;
		}
		else
		{
			require_once( USAM_FILE_PATH . '/includes/theme/virtual_page.class.php'  );
			$query->usam_in_the_loop = false;
			if ( $query->query_vars['pagename'] == 'api' )
				$pg = new USAM_Virtual_Page(['slug' => 'api', 'title' => 'API', 'content' => ""]);
			elseif ( !empty($query->query_vars['sku']) )
			{
				$product_id = usam_get_product_id_by_sku( $query->query_vars['sku'] );
				if ( $product_id )
				{
					$query->query_vars['p'] = $product_id;
					$query->is_single = true;
					$query->is_home = false;
					$query->query_vars['post_type'] = 'usam-product';
				}
				unset($query->query_vars['sku']);
			}		
			else
			{			
				$virtual_page = usam_get_virtual_page( $query->query_vars['pagename'] );	
				if ( !empty($virtual_page) ) 					
					$pg = new USAM_Virtual_Page( $virtual_page );
			}
		}
	}
		
	public static function is_product_query()
	{	
		global $wp_query, $post;		
		if ( !empty($post) && !empty($wp_query->query['pagename']) ) 
		{		
			if( isset($wp_query->query['pagename']) && $wp_query->query['pagename'] == 'products-list' )
			{			
				$option = get_option( 'usam_default_category' );
				if ( $option == 'catalogs-products' || $option == 'brands-products' || $option == 'brands-category-products' )
					return false;
			}		
			if ( !empty($wp_query->query['seller_id']) ) 
				return true;
			$pages = usam_system_pages();
			foreach ($pages as $key => $page )
			{ 
				if ( strpos($post->post_content, '['.$page['shortcode'].']') )
				{ 					
					$wp_query->query['usam_page'] = $key;
					if ( $key == 'reviews' )
						return false;
					else
						return true;
				}
			}
			$pages = ['compare', 'wish-list'];		
			if ( in_array($wp_query->query['pagename'], $pages) ) 
			{
				$wp_query->query['usam_page'] = $wp_query->query['pagename'];
				return true;			
			}
		}		
		elseif ( !empty($wp_query->query['usam-product_attributes']) ) 
			return true;			
		elseif ( !empty($wp_query->query['usam-category']) ) 
			return true;
		elseif ( !empty($wp_query->query['usam-brands']) ) 
			return true;
		elseif ( !empty($wp_query->query['usam-category_sale']) ) 
			return true;
		elseif ( !empty($wp_query->query['usam-catalog']) ) 
			return true;
		elseif ( !empty($wp_query->query['usam-selection']) ) 
			return true;
		elseif ( !empty($wp_query->query['product_tag']) ) 
			return true;
		
		return false;
	}
			
	public static function start_the_query()
	{
		global $wp_query, $usam_query;		
		if ( null == $usam_query && USAM_Theme_Query::is_product_query() ) 
		{
			if( isset($wp_query->query['pagename']) && $wp_query->query['pagename'] == 'products-list' && !isset($wp_query->post))
			{ 
				global $post;
				if( !isset($wp_query->query_vars['usam-category']) && ! isset($wp_query->query_vars['product_tag'] ) )
					$wp_query = new WP_Query('post_type=usam-product&name='.$wp_query->query_vars['name']);
				
				$test_term = get_term_by( 'slug', $wp_query->query_vars['name'], 'usam-category' );
				if ( empty($test_term) )
				{  // Узнаем существует ли термин
					$wp_query->is_404 = true;
				}					
			}			
			$sold = get_option( 'usam_display_sold_products', 'sort');
			$post_type_object = get_post_type_object( 'usam-product' );				
			$edit_product = current_user_can( $post_type_object->cap->edit_posts );	
			
			$query_vars = ['post_status' => 'publish',	'post_parent' => 0];
			if ( get_option('usam_product_pagination', 1) ) // количество продуктов на странице	
				$query_vars['posts_per_page'] = usam_get_number_products_page_customer();	
			else 
			{
				$query_vars['posts_per_page'] = -1;
				$query_vars['nopaging'] = 1;
			}			
			if ( !isset($query_vars['meta_query']))
				$query_vars['meta_query'] = [];
																	
			if( isset($wp_query->query_vars['preview']) && $wp_query->query_vars['preview'] )
				$query_vars['post_status'] = 'any';			
			
			if( isset($wp_query->query['pagename']) && $wp_query->query['pagename'] == 'products-list' )
			{ // Выберите, что показывать на странице товаров		
				$query_vars['post_type'] = 'usam-product';
				if( '' != ( $default_category = get_option('usam_default_category') ) )
				{  			
					if( is_numeric($default_category) && ( $default_term = get_term($default_category,'usam-category')) != '' )
					{
						$query_vars['taxonomy'] = 'usam-category';
						$query_vars['term'] = $default_term->slug;
					}
					elseif( is_numeric($default_category) && ( $default_term = get_term($default_category, 'usam-brands')) != '' )
					{
						$query_vars['taxonomy'] = 'usam-brands';
						$query_vars['term'] = $default_term->slug;
					}			
				}	
				if ($sold != 'show')
					$query_vars['in_stock'] = $sold == 'hide'; // Исключить проданный товар из показа
			}
			elseif(!empty($wp_query->query_vars['product_tag']))
			{
				$query_vars['product_tag'] = $wp_query->query_vars['product_tag'];				
				if ($sold != 'show')
					$query_vars['in_stock'] = $sold == 'hide'; // Исключить проданный товар из показа
			}
			elseif( !empty($wp_query->query_vars['usam-product']) )
			{	// Если открыт товар
				
			}	
			elseif ( isset($wp_query->query['usam_page']) && $wp_query->query['usam_page'] == 'wish-list' )
			{				
				$query_vars['post_type'] = 'usam-product';
				$contact_id = usam_get_contact_id();	
				$query_vars['user_list'] = ['list' => 'desired', 'contact_id' => $contact_id];
			}
			elseif ( isset($wp_query->query['usam_page']) && $wp_query->query['usam_page'] == 'compare' )
			{ //Если страница сравнения товаров
				$query_vars['post_type'] = 'usam-product';
				$contact_id = usam_get_contact_id();
				$query_vars['user_list'] = ['list' => 'compare', 'contact_id' => $contact_id];
			}
			else
			{
				$query_vars = usam_get_default_catalog_sort( $query_vars, 'array' );
				if( !empty($wp_query->query['usam-product_attributes']) )
				{ 
					$query_vars['post_type'] = 'usam-product';
					if ( !isset($query_vars['attributes_query']) )
						$query_vars['attributes_query'] = [];						
					$id = usam_get_product_attribute_values(['slug' => $wp_query->query['attribute'], 'number' => 1, 'fields' => 'id']);				
					if ( $id )
						$query_vars['attributes_query'][] = ['key' => $wp_query->query['usam-product_attributes'], 'value' => $id, 'compare' => '='];
					else
						$wp_query->is_404 = true;
				}				
				elseif( isset($wp_query->query_vars['usam-category_sale']))
				{			
					$ids = usam_get_discount_rules(['fields' => 'id', 'active' => 1, 'acting_now' => 1, 'term_slug' => $wp_query->query_vars['usam-category_sale']]);				
					if ( !empty($ids) )
					{
						$query_vars['discount'] = $ids;
						$query_vars['post_type'] = 'usam-product';	
						$term = get_term_by('slug', $wp_query->query_vars['usam-category_sale'], $wp_query->query_vars['taxonomy']);	
						if( !empty($term->term_id) )
						{
							$term_orderby = usam_get_term_metadata($term->term_id, 'product_sort_by');		
							if ( $term_orderby )
							{
								$options = usam_get_user_product_sorting_options();				
								if ( isset($options[$term_orderby]) )
								{
									$sort = explode('-', $term_orderby);								
									$query_vars['orderby'] = $sort[0];
									$query_vars['order'] = $sort[1];								
								}
							}
						}									
					}
					else
					{
						$query_vars['usam-category_sale'] = $wp_query->query_vars['usam-category_sale'];	
					}
				}
				elseif( isset($wp_query->query_vars['usam-category']) )
				{								
					if ( !empty($_REQUEST['brand']) )
					{
						$query_vars['usam-brands'] = sanitize_title($_REQUEST['brand']);							
					}							
					$query_vars['usam-category'] = $wp_query->query_vars['usam-category'];								
					if( isset($wp_query->query_vars['usam-brands']))
						$query_vars['usam-brands'] = $wp_query->query_vars['usam-brands'];						
					if( isset($wp_query->query_vars['usam-selection']))
						$query_vars['usam-selection'] = $wp_query->query_vars['usam-selection'];					
					if ( isset($wp_query->query_vars['pagename']) )
						$query_vars = array_merge( $query_vars, usam_get_query_vars_system_page( $wp_query->query_vars['pagename'] ) );
				}
				elseif( isset($wp_query->query_vars['usam-brands']))
				{						
					$query_vars['usam-brands'] = $wp_query->query_vars['usam-brands'];	
					if ( isset($wp_query->query_vars['pagename']) )
						$query_vars = array_merge( $query_vars, usam_get_query_vars_system_page( $wp_query->query_vars['pagename'] ) );	
				}					
				elseif( isset($wp_query->query_vars['usam-catalog']))
				{						
					$query_vars['usam-catalog'] = $wp_query->query_vars['usam-catalog'];	
				}
				elseif( isset($wp_query->query_vars['usam-selection']))
				{						
					$query_vars['usam-selection'] = $wp_query->query_vars['usam-selection'];
				}					
				else
				{					
					if ( isset($wp_query->query['usam_page']) )
					{
						if ( in_array($wp_query->query['usam_page'], ['sale','new-arrivals','compare']) )
							$query_vars['pagename'] = $wp_query->query['usam_page'];	
						
						if ( usam_get_virtual_page( $wp_query->query['pagename'] ) ) 
						{ // если виртуальная страница
							$query_vars['pagename'] = $wp_query->query['pagename'];
						}
						$query_vars = array_merge($query_vars, usam_get_query_vars_system_page( $wp_query->query['usam_page']) );
					}					
				}
				$query_vars['post_type'] = 'usam-product';
				add_filter( 'pre_get_posts', ['USAM_Theme_Query', 'change_product_request'], 11 );									
				$catalog = usam_get_active_catalog();
				if ( $catalog )
					$query_vars['usam-catalog'] = $catalog->slug;					
				if ($sold != 'show')
					$query_vars['in_stock'] = $sold == 'hide'; // Исключить проданный товар из показа
			}
			if ( get_option('usam_product_pagination',1) )
			{					
			//	$query_vars['nopaging'] = 1;	
				$query_vars['paged'] = get_query_var('paged');		
				if( isset($query_vars['paged']) && empty($query_vars['paged']))
					$query_vars['paged'] = get_query_var('page');	
			}
			if ( get_option('usam_website_type', 'store' ) == 'marketplace' &&  !empty($wp_query->query_vars['seller_id']) )
			{
				$query_vars['productmeta_query'][] = ['key' => 'seller_id', 'value' => $wp_query->query_vars['seller_id'], 'compare' => '='];
			}				
			$query_vars['prices_cache'] = true;
			$query_vars['stocks_cache'] = true;
			$query_vars['product_meta_cache'] = true;
			$query_vars['post_meta_cache'] = true;
			$query_vars['discount_cache'] = true;
			$query_vars['post_parent'] = 0;			
			if ( empty($query_vars['price_meta_query']) && !get_option('usam_show_zero_price', 1) )
			{
				$type_price = isset($query_vars["type_price"])?$query_vars["type_price"]:usam_get_customer_price_code();
				$query_vars['price_meta_query'][] = ['key' => 'price_'.$type_price, 'value' => 0, 'compare' => '>', 'type' => 'DECIMAL'];
			}			
			$usam_query = new WP_Query( $query_vars ); 	
			$usam_query->usam_in_the_loop = true;	
			if ( (isset($usam_query->post_count) && $usam_query->post_count == 0) && isset($query_vars['paged']) && $query_vars['paged'] > 1 )
			{   // Если нет товара в категории или в бренде показать страницу товаров					
				$url = add_query_arg(['paged' => 1] , stripslashes($_SERVER['REQUEST_URI']) );
				wp_redirect( $url ); 	
				exit;
			}	
			do_action( 'usam_start_the_query', $query_vars );	
			remove_filter( 'pre_get_posts', ['USAM_Theme_Query', 'change_product_request'], 11 );
		}
	}
		
	/** Событие срабатывает перед каждым запросом WP_Query. До того, как был сделан запрос в базу. Используется для изменения запроса.  */
	public static function change_product_request( $query ) 
	{
		global $wp_query;
		if ( $query->query_vars['pagename'] != '' )
		{
			$query->query_vars['post_type'] = 'usam-product';
			$query->query_vars['pagename']  = '';
			$query->is_page     = false;
			$query->is_tax      = false;
			$query->is_archive  = true;
			$query->is_singular = false;
			$query->is_single   = false;			
		}			
		if ( isset($query->query_vars['products'] ) && ($query->query_vars['products'] != null) && ($query->query_vars['name'] != null) )
		{
			unset( $query->query_vars['taxonomy'] );
			unset( $query->query_vars['term'] );
			$query->query_vars['post_type'] = 'usam-product';
			$query->is_tax      = false;
			$query->is_archive  = true;
			$query->is_singular = false;
			$query->is_single   = false;
		}
		if( !empty($_REQUEST['interval_prices']) )
		{			
			$query->query_vars['price_meta_query'] = ['relation' => 'OR'];
			$type_price = isset($query->query_vars["type_price"])?$query->query_vars["type_price"]:usam_get_customer_price_code();	
			foreach ($_REQUEST['interval_prices'] as $key => $interval )
			{					
				if ( is_array($interval) )
					$query->query_vars['price_meta_query'][] = ['key' => 'price_'.$type_price, 'value' => array_map('intval', $interval), 'compare' => 'BETWEEN', 'type' => 'DECIMAL'];
			}
		}
		else if( !empty($_REQUEST['prices']) )
		{						
			$query->query_vars['price_meta_query'] = [];
			$type_price = isset($query->query_vars["type_price"])?$query->query_vars["type_price"]:usam_get_customer_price_code();
			$prices = is_array($_REQUEST['prices'])?$_REQUEST['prices']:explode('-', $_REQUEST['prices']);
			$prices = array_map('intval', $prices);
			if( empty($prices[0]) && !empty($prices[1]) )			
				$query->query_vars['price_meta_query'][] = ['key' => 'price_'.$type_price, 'value' => [0.01, $prices[1]], 'compare' => 'BETWEEN', 'type' => 'DECIMAL'];
			elseif( empty($prices[1]) && !empty($prices[0])  )	
				$query->query_vars['price_meta_query'][] = ['key' => 'price_'.$type_price, 'value' => $prices[0], 'compare' => '>=', 'type' => 'DECIMAL'];
			elseif( !empty($prices[1]) && !empty($prices[0]))			
				$query->query_vars['price_meta_query'][] = ['key' => 'price_'.$type_price, 'value' => $prices, 'compare' => 'BETWEEN', 'type' => 'DECIMAL'];
			else
				$query->query_vars['price_meta_query'][] = ['key' => 'price_'.$type_price, 'value' => 0, 'compare' => '>', 'type' => 'DECIMAL'];				
		}		
		if ( !empty($_REQUEST['scat']) )
		{
			$scat = array_map('intval', is_array($_REQUEST['scat'])?$_REQUEST['scat']:explode('-', $_REQUEST['scat']));
			if ( !isset($query->query_vars['tax_query']) )
				$query->query_vars['tax_query'] = array();
			$query->query_vars['tax_query'] = [['taxonomy' => 'usam-category', 'field' => 'id', 'terms' => $scat, 'operator' => 'IN'] ];	
	
		//	$query->query_vars['usam-category'] = '';			
		}		
		if ( isset($_REQUEST['order']) )	
			$query->query_vars['order'] = usam_product_order();
		else
			$query->query_vars['order'] = get_option( 'usam_product_order' );		
		if ( isset($_REQUEST['orderby']) )	
			$query->query_vars['orderby'] = sanitize_title($_REQUEST['orderby']);		
		elseif ( empty($query->query_vars['discount']) ) //исключить категорию скидок из сортировки
			$query->query_vars = usam_get_default_catalog_sort( $query->query_vars, 'array' );
		$query->query_vars = array_merge( $query->query_vars, usam_product_sort_order_query_vars( $query->query_vars['orderby'] ) );
		if( !empty($_REQUEST['new_products'])) //Фильтр новинок
			$query->query_vars = array_merge($query->query_vars, usam_get_query_vars_system_page('new-arrivals') );	
		if( !empty($_REQUEST['stock_products'])) //Фильтр по товаров на акции
			$query->query_vars = array_merge($query->query_vars, usam_get_query_vars_system_page('sale') );						
						
		//Если тег продукта таксономии
		if (isset($wp_query->query_vars['product_tag']) && $wp_query->query_vars['product_tag'])
		{
			$query->query_vars['product_tag'] = $wp_query->query_vars['product_tag'];
			$query->query_vars['term'] = $wp_query->query_vars['term'];
			$query->query_vars['taxonomy'] = 'product_tag';
			$query->is_tax      = true;
		}			
		remove_filter( 'pre_get_posts', ['USAM_Theme_Query', 'change_product_request'], 11 );
		return $query;
	}
}

/**
 * Показывает только продукты из текущей категории, или из подкатегорий.
 */
class USAM_Hide_Subcatsprods_In_Cat
{
	private $q;
	function __construct( ) 
	{	
		add_action( 'pre_get_posts', [&$this, 'get_posts']);			
	}	
	
	function get_posts( &$q ) 
	{ 
		$this->q =& $q;
		if ( ( !isset($q->query_vars['taxonomy']) || ( "usam-category" != $q->query_vars['taxonomy'] )) )
			return false;

		add_action( 'posts_where', array( &$this, 'where' ) );
		add_action( 'posts_join', array( &$this, 'join' ) );
	}

	function where( $where )
	{
		global $wpdb;

		remove_action( 'posts_where', array( &$this, 'where' ) );
		$term = get_term_by( 'slug', $this->q->query_vars['term'], $this->q->query_vars['taxonomy'] );		
		if ( empty($term->term_taxonomy_id) )
			return $where;
		$field = preg_quote( "$wpdb->term_relationships.term_taxonomy_id", '#' );
		$just_one = $wpdb->prepare( " AND $wpdb->term_relationships.term_taxonomy_id = %d ", $term->term_taxonomy_id );
		
		if ( preg_match( "#AND\s+$field\s+IN\s*\(\s*(?:['\"]?\d+['\"]?\s*,\s*)*['\"]?\d+['\"]?\s*\)#", $where, $matches ) )
			$where = str_replace( $matches[0], $just_one, $where );
		else
			$where .= $just_one;
		return $where;
	}

	function join($join)
	{ 
		global $wpdb;
		remove_action( 'posts_where', array( &$this, 'where' ) );
		remove_action( 'posts_join', array( &$this, 'join' ) );
		if( strpos($join, "JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)" ) )
			return $join;		
		$join .= " JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)";
		return $join;
	}
}

//Показать подкатегории товаров в родительской категории
if( ! get_option( 'usam_show_subcatsprods_in_cat', 1 ) )
	$hide_subcatsprods = new USAM_Hide_Subcatsprods_In_Cat;


function usam_get_query_vars_system_page( $page )
{
	$query_vars = [];
	switch ( $page ) 
	{
		case 'sale':          //Если страница распродаж
			$type_price = usam_get_customer_price_code();
			$query_vars['price_meta_query'] = [['key' => 'old_price_'.$type_price, 'value' => 0, 'compare' => '>']];
		break;
		case 'purchased':
			$query_vars['purchased'] = true;
			$query_vars["orderby"] = ['purchased' => 'DESC', 'views' => 'DESC'];
		break;
		case 'popular':          //Если страница популярные товары	
			$query_vars['purchased'] = true;	
			$query_vars["orderby"] = 'popularity';
		break;		
		case 'new-arrivals':       //Если страница новинки
			$day = (int)get_option("usam_number_days_product_new", 14);
			if ( $day )
				$query_vars['date_query'] = [['after' => $day.' days ago']];				
		break;	
		case 'recommend':		//Если страница рекомендуемые товары
			$query_vars['user_list'] = 'sticky';
		break;			
	}	
	return $query_vars;
}
?>