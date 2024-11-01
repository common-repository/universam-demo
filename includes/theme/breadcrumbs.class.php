<?php
/**
* @return boolean
*/
function usam_has_breadcrumbs() 
{ 	
	$breadcrumbs = USAM_Breadcrumbs::instance();	
	if( ( $breadcrumbs::$breadcrumb_count > 0) && (get_option('usam_show_breadcrumbs', 1) == 1) )
		return true;
	else
		return false;	
}

function usam_have_breadcrumbs()
{
	$breadcrumbs = USAM_Breadcrumbs::instance();	
	return $breadcrumbs->have_breadcrumbs();
}

function usam_the_breadcrumb() 
{
	$breadcrumbs = USAM_Breadcrumbs::instance();	
	$breadcrumbs->the_breadcrumb();
}

function usam_breadcrumb_name() 
{
	$breadcrumbs = USAM_Breadcrumbs::instance();
	return htmlspecialchars_decode($breadcrumbs::$breadcrumb['name']);
}

function usam_breadcrumb_count() 
{
	$breadcrumbs = USAM_Breadcrumbs::instance();	
	return $breadcrumbs::$breadcrumb_count;
}

function usam_breadcrumb_slug() 
{
	$breadcrumbs = USAM_Breadcrumbs::instance();	
	return (isset($breadcrumbs::$breadcrumb['slug']) ? $breadcrumbs::$breadcrumb['slug'] : '');
}

function usam_breadcrumb_type() 
{
	$breadcrumbs = USAM_Breadcrumbs::instance();	
	return (isset($breadcrumbs::$breadcrumb['type']) ? $breadcrumbs::$breadcrumb['type'] : '');
}

/**
* Получить ссылку на объект
*/
function usam_breadcrumb_url()  
{	
	$breadcrumbs = USAM_Breadcrumbs::instance();
	return $breadcrumbs::$breadcrumb['url'];	
}

/**
* Вывод "хлебных крошек"
*/
function usam_output_breadcrumbs( $options = null )
{ 
	global $wp_query;

	if ( !usam_has_breadcrumbs() )
		return;

	// Настройки по умолчанию
	$options = apply_filters( 'usam_output_breadcrumbs_options', $options );
	$options = wp_parse_args( (array)$options, [
		'before-breadcrumbs' => '<div class="usam-breadcrumbs breadcrumbs" itemscope="" itemtype="http://schema.org/BreadcrumbList">',
		'after-breadcrumbs'  => '</div>',
		'before-crumb'       => '',
		'after-crumb'        => '',
		'crumb-separator'    => '&raquo;',
		'show_home_page'     => true,
		'echo'               => true
	]);	
	$products_page_id = usam_get_system_page_id( 'products-list' );	
	$output = '';	
	$position = 0;
	if ( get_option( 'page_on_front' ) != $products_page_id && $options['show_home_page'] ) 
	{
		$position++;
		$output .= $options['before-crumb'];
		$output .= "<span itemprop='itemListElement' itemscope='' itemtype='http://schema.org/ListItem'><a class='usam-crumb' id='usam-crumb-home' href='".get_option('home')."' itemprop='item'><span itemprop='name'>".__('Главная', 'usam')."</span></a><meta itemprop='position' content='$position'></span>";
		$output .= $options['after-crumb'];
	}
	$count = usam_breadcrumb_count();
	$i = 0;
	while ( usam_have_breadcrumbs() )
	{				
		usam_the_breadcrumb();	
		$i++;
		if ( !empty( $output ) )
			$output .= '<span class="usam_crumb_separator">'.$options['crumb-separator'].'</span>';		
		$output .= $options['before-crumb'];	
					
		if ( $i != $count )				
		{
			$position++;
			$output .= '<span itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem"><a class="usam-crumb usam-crumb-'.usam_breadcrumb_type().'" id="usam-crumb-' . usam_breadcrumb_slug() . '" href="' . usam_breadcrumb_url() . '" itemprop="item"><span itemprop="name">'.usam_breadcrumb_name().'</span></a>';
			$output .= "<meta itemprop='position' content='$position'></span>";
		}
		else
			$output .= '<span class="usam-crumb usam_crumb_current usam-crumb-'.usam_breadcrumb_type().'" id="usam-crumb-' . usam_breadcrumb_slug() . '">' . usam_breadcrumb_name() . '</span>';			
		$output .= $options['after-crumb'];	
	}
	$output = $options['before-breadcrumbs'] . apply_filters( 'usam_output_breadcrumbs', $output, $options ) . $options['after-breadcrumbs'];
	if ( $options['echo'] )
		echo $output;
	else
		return $output;	
}

class USAM_Breadcrumbs
{
	private static $_instance = null;
	public static $breadcrumbs = array();
	public static $breadcrumb_count = 0;
	public static $current_breadcrumb = -1;
	public static $breadcrumb;
	
	public function __construct( )
	{ 
		global $wp_query, $usam_query, $post;
		foreach( ['usam-category', 'usam-brands', 'usam-category_sale', 'usam-catalog', 'usam-selection'] as $tax ) 
		{				
			if ( !empty($usam_query->query_vars[$tax]) )
				$this->set_terms( $usam_query->query_vars[$tax], $tax );	
		}	
		if ( !empty($usam_query->query_vars['discount']) && !empty($wp_query->query_vars['usam-category_sale']) )
			$this->set_terms( $wp_query->query_vars['usam-category_sale'], 'usam-category_sale' );				
		if ( is_single() && !is_attachment() )
		{
			$page = get_the_title( );
			self::$breadcrumbs[] = ['name' => htmlentities( $page, ENT_QUOTES, 'UTF-8'), 'url' => get_permalink($wp_query->post->ID ), 'slug' => $wp_query->post->post_name, 'type' => $wp_query->post->post_type];				
			if( $wp_query->post->post_type == 'usam-product' )
			{
				$taxonomy = 'usam-category';
				$categories = get_the_terms( $wp_query->post->ID, $taxonomy );				
			}
			elseif ( $wp_query->post->post_type == 'post')
			{
				$taxonomy = 'category';
				$categories = get_the_terms( $wp_query->post->ID , $taxonomy );
			}	
			if( !empty($categories) )	
			{
				self::$breadcrumbs[] = ['name' => htmlentities( $categories[0]->name, ENT_QUOTES, 'UTF-8'), 'url' => get_term_link( $categories[0]->term_id, $taxonomy), 'slug' => $categories[0]->slug, 'type' => $taxonomy];
				$this->add_terms( $categories[0], $taxonomy );
			}
		}		
		elseif ( !empty($wp_query->query_vars['pagename']) )
		{
			if ( $wp_query->query_vars['pagename'] == 'your-account' )
			{
				$account_current = usam_your_account_current_tab();	
				$tabs = usam_get_menu_your_account();
				foreach( $tabs as $tab ) 
				{					
					if ( $tab['slug'] == $account_current['tab'] && $tab['title'] )
					{ 
						self::$breadcrumbs[] = ['name' => htmlentities( $tab['title'], ENT_QUOTES, 'UTF-8'), 'url' => usam_get_user_account_url( $tab['slug'] )];
						break;
					}
				} 
				self::$breadcrumbs[] = ['name' => htmlentities( get_the_title( $wp_query->post->ID ), ENT_QUOTES, 'UTF-8'),	'url' => get_permalink($wp_query->post->ID )]; 
			}
			elseif ($wp_query->query_vars['pagename'] == 'point-delivery' && isset($wp_query->query['id']) )
			{		
				self::$breadcrumbs[] = ['name' => apply_filters( 'usam_the_title', htmlentities( get_the_title($wp_query->post->ID), ENT_QUOTES, 'UTF-8'), 'breadcrumbs'), 'url' => usam_get_url_system_page('point-delivery', $wp_query->query['id'])];		
				self::$breadcrumbs[] = ['name' => htmlentities( get_the_title( $wp_query->post->ID ), ENT_QUOTES, 'UTF-8'), 'url' => usam_get_url_system_page('point-delivery')];				
			}			
			elseif ( $wp_query->is_404 )
			{ 
				self::$breadcrumbs[] = ['name' => __("Страница не найдена","usam"), 'url' => ''];
			}	
			else
			{
				if ( $wp_query->post->ID === -111 )
				{
					self::$breadcrumbs[] = ['name' => apply_filters( 'usam_the_title', htmlentities($wp_query->post->post_title, ENT_QUOTES, 'UTF-8'), 'breadcrumbs' ), 'url' => '', 'slug' => $wp_query->post->post_name, 'type' => $wp_query->post->post_type];
				}
				else
				{
					self::$breadcrumbs[] = ['name' => apply_filters( 'usam_the_title', htmlentities(get_the_title( $wp_query->post->ID ), ENT_QUOTES, 'UTF-8'), 'breadcrumbs' ), 'url' => get_permalink($wp_query->post->ID), 'slug' => $wp_query->post->post_name, 'type' => $wp_query->post->post_type];
					$parents = get_post_ancestors( $wp_query->post->ID );
					foreach ( array_reverse( $parents ) as $page_id ) 
					{				
						self::$breadcrumbs[] = ['name' => get_the_title( $page_id ), 'url' => get_page_link( $page_id ), 'slug' => 'page_'.$page_id];
					}
				}
			}
		}		
		elseif ( !empty($wp_query->query_vars['category_name']) ) 
		{
			if ( !empty($wp_query->query_vars['name']) ) 
			{ 
				self::$breadcrumbs[] = ['name' => htmlentities($wp_query->post->post_title, ENT_QUOTES, 'UTF-8'), 'url' => get_permalink($wp_query->post->ID), 'slug' => $wp_query->query_vars['name']];				
				$categories = get_the_terms( $wp_query->post->ID , 'category' );
				if( !empty($categories) )
				{
					$category = '';
					$count_categories = count($categories);			
					if( $count_categories > 1 && isset($usam_query->query_vars['category']))
						$category = $usam_query->query_vars['category'];
					elseif( $count_categories > 0)
						$category = current($categories)->slug;	
											
					if( !empty($category) )	
						$this->set_terms( $category, 'category');
				}						
			}
			else
			{							
				$this->set_terms( $wp_query->query_vars['category_name'], 'category');
			}			
		}
		self::$breadcrumbs = apply_filters( 'usam_breadcrumbs', array_reverse( self::$breadcrumbs ) );
		self::$breadcrumb_count = count(self::$breadcrumbs);
	}		
	
	public static function instance(  ) 
	{ 
		if ( is_null( self::$_instance ) )
		{
			self::$_instance = new self( ); 							
		}
		return self::$_instance;
	}
	
	private function set_terms( $term_slug, $taxonomy ) 
	{		
		$term = get_term_by('slug', $term_slug, $taxonomy);		
		if ( !empty($term) )
		{
			self::$breadcrumbs[] = ['name' => htmlentities( $term->name, ENT_QUOTES, 'UTF-8'), 'url' => get_term_link( $term->term_id, $taxonomy), 'slug' => $term->slug, 'type' => $taxonomy];
			$this->add_terms( $term, $taxonomy );
		}		
	}
	
	private function add_terms( $term, $taxonomy ) 
	{			
		if( !empty($term) && is_taxonomy_hierarchical($taxonomy) )
		{
			$ids = usam_get_ancestors( $term->term_id, $taxonomy );
			if( $ids )
			{
				$default_menu_category = get_option( 'usam_default_menu_category' );
				$terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => 0, 'include' => $ids, 'oderby' => 'include', 'order' => 'ASC', 'update_term_meta_cache' => false, 'cache_results' => false]);	
				foreach( $terms as $term ) 
				{
					if ( $default_menu_category == $term->term_id )
						break;	
					
					if ( !apply_filters('usam_breadcrumb_show_item_term', true, $term) )
						continue;			
					self::$breadcrumbs[] = ['name' => htmlentities( $term->name, ENT_QUOTES, 'UTF-8'), 'url' => get_term_link($term->term_id, $taxonomy), 'type' => $taxonomy];
				}
			}
		}		
	}
	
	function next_breadcrumbs() 
	{
		self::$current_breadcrumb++;
		self::$breadcrumb = self::$breadcrumbs[self::$current_breadcrumb];
		return self::$breadcrumb;
	}	
	
	function the_breadcrumb() 
	{
		self::$breadcrumb = $this->next_breadcrumbs();
	}
	
	function have_breadcrumbs() 
	{
		if (self::$current_breadcrumb + 1 < self::$breadcrumb_count) {
			return true;
		} else if (self::$current_breadcrumb + 1 == self::$breadcrumb_count && self::$breadcrumb_count > 0) {
			$this->rewind_breadcrumbs();
		}
		return false;
	}
	
	function rewind_breadcrumbs() 
	{
		self::$current_breadcrumb = -1;
		if (self::$breadcrumb_count > 0) {
			self::$breadcrumb = self::$breadcrumbs[0];
		}
	}	
}
?>