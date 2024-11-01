<?php
//================================ ВЫВОД СТРАНИЦ УНИВЕРСАМА ==================================================

//template.php 

// Обрабатывает вывод основных страниц
class USAM_Template_Page_Handler
{
	public function __construct()
	{ 		
		add_action('template_redirect', array($this,'load_page_query'), 2 );	
		add_action('template_redirect', array($this,'template_redirect'), 30 );	
		add_filter('usam_the_title',  array($this,'the_title'), 10, 2 );			
	}	

	function the_title( $title, $type ) 
	{
		global $wp_query, $post;
		$obj = $wp_query->get_queried_object();	
		if( !empty($wp_query->query['usam-product_attributes']) )
		{ 
			$attribute = get_term_by('slug', $wp_query->query['usam-product_attributes'], 'usam-product_attributes');
			$option_value = usam_get_product_attribute_values(['fields' => 'value', 'slug' => $wp_query->query['attribute'], 'number' => 1]);
			$title = $attribute->name.' '.mb_strtolower($option_value);	
		}
		elseif ( !empty($wp_query->query_vars['seller_id']) )
		{
			$seller = usam_get_seller_data( $wp_query->query_vars['seller_id'] );
			if ( $seller )
			{
				$title = $seller['name'];
				if ( !empty($wp_query->query_vars['usam-category']) )
				{
					$term = get_term_by('slug', $wp_query->query_vars['usam-category'], 'usam-category');
					if ( isset($term->term_id) )
						$title .= ' - '.$term->name;
				}			
			}
		}
		elseif( !empty($obj->term_id) )	
			$title = $obj->name;	
		elseif( is_single() )
			$title = $obj->post_title;					
		elseif ( !empty($post->post_content) && strpos($post->post_content, '[reviews]') !== false ) 
		{ 
			if ( isset($wp_query->query['id']) )
			{
				$_post = get_post($wp_query->query['id']);
				if ( isset($_post->post_title) )
					$title = sprintf( __('Отзывы о товаре %s','usam'), '"'.$_post->post_title.'"' );
			}
		}
		elseif ( !empty($post->post_content) && strpos($post->post_content, '[point_delivery]') !== false && $post->ID == -111 ) 
		{
			if ( isset($wp_query->query['id']) )
			{
				$location = usam_get_location( $wp_query->query['id'] );				
				if ( isset($location['name']) )
					$title = $post->post_title = sprintf( __('Контакты пунктов выдачи в городе %s','usam'), htmlspecialchars($location['name']) );
			}
		} 	
		elseif ( !empty($obj->post_type) && $obj->post_type == 'page' )
		{		
			$title = $obj->post_title;		
			if ( isset($wp_query->query['usam-category']) )
			{
				$cat_term = get_term_by('slug',$wp_query->query_vars['usam-category'], 'usam-category');
				$title .= ' - '.$cat_term->name;
			}
			if ( $type != 'breadcrumbs' && $post )
			{
				if ( $post->post_name === 'pay_order' || $post->post_name === 'search' || $post->post_name === 'tracking' || $post->post_name === 'your_account' || $post->post_name === 'transaction_results' || $post->post_name === 'login' )
					$title = '';			
			}			
		}		
		return $title;
	}
	
	public function template_redirect()
	{ 			
		$this->canonical_url();
		$this->swap_the_template();			
	}
	
	private function canonical_url() 
	{
		$usam_url = usam_change_canonical_url( null );
		if ( $usam_url != null ) {
			remove_action( 'wp_head', 'rel_canonical' );
			add_action( 'wp_head', 'usam_insert_canonical_url' );
		}
	}
	
	// Ленты торговых площадок
	public function trading_platform_feed( )
	{
		global $wp_query;
		if ( !empty($wp_query->query_vars['trading_platform']) )
		{
			require_once( USAM_FILE_PATH . '/includes/exchange/feed.class.php');
			$platform_instance = usam_get_trading_platforms_class( $wp_query->query_vars['trading_platform'] );		
			if ( is_object($platform_instance) )			
				$platform_instance->upload_file( ); 
			exit;
		}
	}
	
	public function load_page_query( )
	{
		global $wp_query;	

		$this->trading_platform_feed( );
		$obj = $wp_query->get_queried_object();
		$page_name = isset($wp_query->query_vars['pagename'] ) ? $wp_query->query_vars['pagename'] : '';		
		$this->load_page( $page_name );				
	}	
	
	// Узнать какая страница сейчас загружается и загрузить её
	public function load_page( $page_name )
	{					
		switch ( $page_name ) 
		{
			case 'checkout' :				
			case 'basket' :				
				add_action( 'wp_footer', [__CLASS__, 'basket_footer'] );
			break;
			case 'your-account' :					
				if( !is_user_logged_in() )
				{
					wp_redirect( usam_get_url_system_page('login') );
					exit;
				}									
			break;
		}
	}	
	
	public static function basket_footer()
	{	
		remove_action( 'wp_footer', [__CLASS__, 'basket_footer'] );
		if( !is_user_logged_in() && get_option('usam_registration_upon_purchase') == 'suggest')
			usam_include_template_file( 'login', 'modaltemplate' );
		
		$cart = usam_core_setup_cart( false );	
		if( $cart )
		{
			$basket = usam_get_basket( $cart )
			?>
			<script>	
				var basket_data = <?php echo json_encode( $basket ); ?>;	
			</script>	
			<?php		
		}
	}
	
// Выбрать нужный шаблон	
	private function swap_the_template()
	{		
		do_action('usam_swap_the_template');			

		$template = $this->get_template();		
		if ( !empty($template) )
		{ 
			load_template( $template, false );			
			exit;
		} 
	}
	
	private function get_template()
	{ 
		global $post, $wp_query, $usam_query;		

		$obj = $wp_query->get_queried_object();
		$content = isset($obj->post_content ) ? $obj->post_content : '';
		$page_name = is_page()?$post->post_name:'';
		$templates = [];		
		if( is_home() )		
		{			
			$templates[] = TEMPLATEPATH."/home.php";
			$templates[] = USAM_CORE_THEME_PATH."home.php";
		}
		elseif( is_single() && get_query_var( 'post_type' ) == 'usam-product' )
		{
			$templates[] = USAM_THEMES_PATH."content-single_product-{$post->post_name}.php";
			$templates[] = USAM_THEMES_PATH."content-single_product-{$post->ID}.php";		
			$templates[] = usam_get_template_file_path("single-product");	
		}		
		elseif( $category_sale = get_query_var('usam-category_sale' ) )
		{				
			$templates[] = usam_get_template_file_path("category_sale-$category_sale");
			$templates[] = usam_get_template_file_path("category_sale");		
			$templates[] = usam_get_template_file_path("page");	
		}						
		elseif( $category = get_query_var('usam-category') || get_query_var('usam-brands') && get_option("usam_default_category", 'all') === 'brands-category-products' )		
		{
			$display_product = false;
			if ( !empty($usam_query->query['usam-category']) )
			{
				if( get_option('usam_show_subcatsprods_in_cat', 1) )
				{
					$display_product = true;					
				}
				else
				{
					global $usam_query;			
					if ( !empty($usam_query->query['usam-category']) )
					{
						$term = get_term_by('slug', $usam_query->query['usam-category'], 'usam-category');
						$children = get_term_children( $term->term_id, 'usam-category' );
						if ( empty($children) )
							$display_product = true;
					}			
				}
			}
			if( $display_product )
			{
				$templates[] = usam_get_template_file_path("category-$category");	
				$templates[] = usam_get_template_file_path("category");	
				$templates[] = usam_get_template_file_path("page-products");		
				$templates[] = usam_get_template_file_path("page");
			}
			else
			{
				$templates[] = usam_get_template_file_path("page-categories");
				$templates[] = usam_get_template_file_path("page");
			}
		}
		elseif( $brand = get_query_var('usam-brands') )
		{			
			$templates[] = usam_get_template_file_path("brand-$brand");
			$templates[] = usam_get_template_file_path("brand");
			$templates[] = usam_get_template_file_path("page-products");		
			$templates[] = usam_get_template_file_path("page");
		}
		elseif( $product_tag = get_query_var('product_tag' ) )
		{		
			$templates[] = usam_get_template_file_path("product_tag-$product_tag");
			$templates[] = usam_get_template_file_path("product_tag");	
			$templates[] = usam_get_template_file_path("page-products");		
			$templates[] = usam_get_template_file_path("page");
		}
		elseif( $selection = get_query_var( 'usam-selection' ) )		
		{											
			$templates[] = usam_get_template_file_path("selection-$selection");	
			$templates[] = usam_get_template_file_path("selection");	
			$templates[] = usam_get_template_file_path("page-products");		
			$templates[] = usam_get_template_file_path("page");
		}		
		elseif( $catalog = get_query_var( 'usam-catalog' ) )		
		{											
			$templates[] = usam_get_template_file_path("catalog-$catalog");	
			$templates[] = usam_get_template_file_path("catalog");	
			$templates[] = usam_get_template_file_path("page-products");		
			$templates[] = usam_get_template_file_path("page");
		}		
		elseif ( is_user_logged_in() && $page_name == 'login' )
		{
			wp_redirect( usam_get_url_system_page('your-account') );
			exit;
		}	
		elseif ( !empty($wp_query->query['attribute']) )
		{
			$templates[] = usam_get_template_file_path("attribute-".$wp_query->query['attribute']);
			$templates[] = usam_get_template_file_path("attribute");	
			$templates[] = usam_get_template_file_path("page-products");		
			$templates[] = usam_get_template_file_path("page");
		}
		elseif ( $page_name == 'basket' )
		{
			$file_name = str_replace("-", "_", $page_name);
			$templates[] = usam_get_template_file_path("page-checkout");
			$templates[] = usam_get_template_file_path("page-$file_name");				
			$templates[] = usam_get_template_file_path("page");	
		}	
		elseif ( get_option('usam_website_type', 'store' ) == 'marketplace' &&  !empty($wp_query->query['seller_id']) )
		{
			$templates[] = usam_get_template_file_path("page-products_seller");
			$templates[] = usam_get_template_file_path("page-products_list");						
			$templates[] = usam_get_template_file_path("page");	
		}	
		else
		{	
			$virtual_page = usam_get_virtual_page( $page_name );			
			if ( !empty($virtual_page) && $page_name != 'login' ) 
			{ 				
				$file_name = str_replace("-", "_", $page_name);
				$templates[] = usam_get_template_file_path("page-$file_name");				
				$templates[] = usam_get_template_file_path("page");	
			}		
			else
			{
				$pages = usam_system_pages();				
				foreach( $pages as $key => $page )
				{		
					if ( strpos($content, '['.$page['shortcode'].']') || strpos($content, $page['content']) ) 
					{ 
						$file_name = str_replace("-", "_", $page['name']);
						if ( $key == 'products-list' )
						{
							$default = get_option("usam_default_category", 'all');
							switch ( $default ) 
							{				
								case 'list' :
									$templates[] = usam_get_template_file_path("page-categories");
								break;
								case 'catalogs-products' : 
									$templates[] = usam_get_template_file_path("page-catalogs");
								break;
								case 'brands-products' : 
									$templates[] = usam_get_template_file_path("page-brands");
								break;
								case 'brands-category-products' : 
									$templates[] = usam_get_template_file_path("page-brands");
								break;							
							}
						}								
						$templates[] = usam_get_template_file_path("page-$file_name");				
						
						if( in_array($page['name'], usam_get_product_pages() ) )
							$templates[] = usam_get_template_file_path("page-products_list");
						
						$templates[] = usam_get_template_file_path("page");		
						break;
					}
				}				
			}
			if ( empty($templates) )
			{						
				$shortcode_pages = ['shareonline', 'agreements'];	
				foreach( $shortcode_pages as $file_name )
				{	
					if ( preg_match("/\[$file_name\]/", $content) )
					{ 						
						$templates[] = usam_get_template_file_path("page-$file_name");				
						$templates[] = usam_get_template_file_path("page");		
						break;
					}
				}
			}			
		}
		if ( !empty($templates) )
		{
			foreach( $templates as $template )
			{ 
				if ( file_exists($template) )
				{ 
					return $template;
				}
			}
		} 
		return false;
	}	
}
new USAM_Template_Page_Handler();

function usam_get_page_content( $pagename, $template_name )
{
	$page = new USAM_Page();
	$page->load_page( $pagename );

	ob_start();

	usam_load_template( $template_name );
	
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}
?>