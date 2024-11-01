<?php
function usam_productspage_nav_class( $classes, $item, $args = null, $depth = 0 )
{
	global $wp_query;
	$term = $wp_query->get_queried_object();
    if( !empty($term->term_id) && $term->taxonomy == 'usam-category' && $item->object_id == usam_get_system_page_id('products-list') )
        $classes[] = 'current-menu-item';
    return $classes;
}
add_filter( 'nav_menu_css_class', 'usam_productspage_nav_class', 10, 4 );


require_once( USAM_FILE_PATH . '/includes/taxonomy/class-walker-product_category-menu.php' );

class USAM_Walker_Nav_Menu extends Walker_Nav_Menu 
{	
	public $number = 0;	
	public $count_number = 0;		
	public $current_number_column = 1;	
	public static $display_catalog = true;
	public $menu_items = array();
	public function start_lvl( &$output, $depth = 0, $args = null ) 
	{								
		if ( self::$display_catalog || true )
		{
			$indent = str_repeat("\t", $depth);						
			$depth = !empty($args->menu_button)?$depth+1:$depth;				
			switch ( $depth )
			{				
				case 0:								
					$output .= "\n$indent<ul class='sub-menu category_menu'>\n";					
				break;
				case 1:		
					$this->count_number = 0;
					$this->number = 0;
					$this->current_number_column = 1;					
					$output .= "\n$indent<div class='sub-menu sub_menu_map'><div class='sub_menu_map__columns'><dl class='sub_menu_map__column'>\n";
				break;
				case 2:					
					$output .= "\n$indent<dl class='header-sub-menu'>\n";			
				break;		
				default:
					$output .= "\n$indent<dl class='sub_menu_map__second_level'>\n";								
				break;					
			}
		}		
		else
		{	
			parent::start_lvl( $output, $depth, $args );		
		}				
	}
	
	public function end_lvl( &$output, $depth = 0, $args = array() ) 
	{
		if ( self::$display_catalog  )
		{			
			$indent = str_repeat("\t", $depth);		
			$depth = !empty($args->menu_button)?$depth+1:$depth;
			switch ( $depth )
			{						
				case 0:								
					self::$display_catalog = true;
					$output .= "$indent</ul>\n";
				break;
				case 1:						
					$output .= "$indent</dl></div></div>\n";
				break;
				case 3:		
				default:							
					$output .= "$indent</dl>\n";				
				break;					
			}		
		}		
		else
		{	
			parent::end_lvl( $output, $depth, $args );		
		}
	}
	
	public function get_count_children( $id )
	{ 
		$i = 0;				
		foreach ( $this->menu_items as $menu_item ) 
		{				
			if ( $menu_item->menu_item_parent == $id )
			{ 
				$i++;				
				$i += $this->get_count_children( $menu_item->ID );				
			}
		}
		return $i;
	}
	
	public function end_el( &$output, $item, $depth = 0, $args = null ) 
	{		
		if ( self::$display_catalog  )
		{
			$depth = !empty($args->menu_button)?$depth+1:$depth;
			switch ( $depth )
			{
				case 0:		
				case 1:						
					$output .= '</li>'."\n";
				break;
				case 2:					
					$output .= "</dt>\n";	
					$number_columns = !empty($args->number_columns)?$args->number_columns:2;	
					$this->number ++;
					$this->count_number ++;	
					$all_count = $this->get_count_children( $item->menu_item_parent );	
				//	$count = $this->get_count_children( $item->ID );					
					if ( $all_count < 5 )
						$number_columns = 1;
					elseif( $number_columns > 2 && $all_count < 9 )
						$number_columns = 2;								
					$c = ceil( $all_count / $number_columns );							
					if ( ($c <= $this->number ) && $number_columns > $this->current_number_column && $all_count != $this->count_number )
					{
						$output .= "</dl><dl class='sub_menu_map__column'>";
						$this->number = 0;
						$this->current_number_column++;
					}
				break;
				default:
				case 3:		
					$output .= "</dd>\n";
					$this->number ++;
					$this->count_number ++;	
				break;				
			}
		}
		else
		{	
			parent::end_el( $output, $item, $depth, $args );		
		}
	}
	
	function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) 
	{
		global $wp_query;
		
		if ( empty($this->menu_items) && isset($args->theme_location) )
		{  
			$menu_locations = get_nav_menu_locations();
			if ( !empty($menu_locations[$args->theme_location]) )
				$this->menu_items = wp_get_nav_menu_items( $menu_locations[$args->theme_location], ['update_post_term_cache' => false]);
		}				
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		$item->classes = empty($item->classes) ? array() : (array)$item->classes;
		$item->classes[] = "menu-item-depth-$depth";	
		
		$atts = [];
		$atts['title'] = !empty($item->attr_title) ? $item->attr_title : '';
		$atts['target'] = !empty($item->target) ? $item->target: '';
		$atts['rel'] = !empty($item->xfn) ? $item->xfn : '';
		$atts['href'] = !empty($item->url) ? $item->url : '';		
		$categories = [];
		if ( apply_filters( 'usam_add_categories_catalog_menu', true, $item, $depth ) && $item->object_id == usam_get_system_page_id('products-list') && !$depth )
		{ 
			$item->classes[] = 'menu-item-page-products';
			$atts['class'] = "menu-item-link-page-products";
			$count = $this->get_count_children( $item->ID );	
			if ( !$count )
			{			
				$categories = usam_get_terms();		
				if ( $categories )
					$item->classes[] = 'menu-item-has-children';
			}
			else
				self::$display_catalog = true;
		}
		elseif ( $item->object == 'usam-category' )
		{
			$category_children = get_option('usam-category_children');
			if ( isset($category_children[$item->object_id]) )
				$item->classes[] = 'menu-item-has-children';	
		}		
		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $item->classes ), $item, $args, $depth ) );
		$class_names = ' class="' . esc_attr( $class_names ) . '"';
	
		if ( self::$display_catalog )
		{ 
			$depth = !empty($args->menu_button)?$depth+1:$depth;
			switch ( $depth )
			{			
				case 0:		
				case 1:						
					$output .= $indent . '<li' . $class_names .'>';
				break;
				case 2:						
					$output .= $indent . '<dt' . $class_names .'>';
				break;
				default:
				case 3:						
					$output .= $indent . '<dd' . $class_names .'>';
				break;				
			}
		}		
		else
			$output .= $indent . '<li' . $class_names .'>';		

		$item_output = isset($args->before)?$args->before:$args['before'];
		$atts = apply_filters( 'usam_nav_menu_link_attributes', $atts, $item, $args, $depth );	
		$attributes = '';
		foreach ( $atts as $attr => $value ) 
		{
			if ( !empty($value) ) 
			{
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}	
		$item_output .= '<a'. $attributes .'>';
		
		$title = apply_filters( 'nav_menu_item_title', $item->title, $item, $args, $depth );
		
		$item_output .= (isset($args->link_before)?$args->link_before:$args['link_before']).$title.(isset($args->link_after)?$args->link_after:$args['link_after']);	
		$item_output .= '</a>';	
		if ( $item->object == 'usam-category' )
		{
			$category_children = get_option('usam-category_children');
			if ( isset($category_children[$item->object_id]) )
			{
				$categories = usam_get_terms(['child_of' => $item->object_id]);
				$number_columns = isset($args->number_columns)?$args->number_columns:3;
				$args_menu = ['walker_nav_menu' => true, 'number_columns' => $number_columns, 'before' => '', 'after' => '', 'link_before' => '', 'link_after' => '']; 
				require_once( USAM_FILE_PATH . '/includes/taxonomy/class-walker-product_category-columns-menu.php' );
				$walker = new Walker_Product_Category_Colums_Nav_Menu();
				$item_output .= '<div class="sub-menu menu-columns"><ul class ="menu-column">'.call_user_func_array([$walker, 'walk'], [$categories, 0, $args_menu]).'</ul></div>';
			}
		}	
		elseif ( $categories )
		{
			$args_menu = [
				'taxonomy' => 'usam-category',	
				'menu_button'          => !empty($args->menu_button)?true:false,
				'number_columns'       => !empty($args->number_columns)?$args->number_columns:2,							
				'before' => '', 
				'after' => '',
				'link_before' => '', 
				'link_after' => '',
			]; 
			$walker = new Walker_Product_Category_Nav_Menu();
			$output_catalog = call_user_func_array([$walker, 'walk'], [$categories, 0, $args_menu]);
			if ( $depth == 0 )
			{
				$map   = "<li class = 'usam_site_map'><a href='".usam_get_url_system_page('map')."'>".__('Карта категорий','usam')."</a></li>";
				$category_menu = '<ul class ="sub-menu category_menu">'.$output_catalog.$map.'</ul>';
				$item_output .= apply_filters( 'usam_nav_menu_categories', $category_menu, $output_catalog, $map, $item );
			}
			else
				$item_output .= "<div class='sub-menu sub_menu_map'><div class='sub_menu_map__columns'><dl class='sub_menu_map__column'>$output_catalog</dl></div></div>";
		}				
		$item_output .= isset($args->after)?$args->after:$args['after'];
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
}
?>