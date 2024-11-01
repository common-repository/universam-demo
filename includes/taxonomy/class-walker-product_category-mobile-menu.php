<?php
/* Строит меню категорий */
class Walker_Product_Category_Nav_Mobile_Menu extends Walker
{
	public $tree_type = 'category';
	public $db_fields = ['parent' => 'parent', 'id' => 'term_id']; 
		
	public function start_el( &$output, $item, $depth = 0, $args = [], $id = 0 ) 
	{			
		$category_children = get_option('usam-category_children');
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		$item->classes = empty($item->classes) ? [] : (array)$item->classes;
		$item->classes[] = 'menu-item-' . $item->object_id;
		$item->classes[] = 'menu-item';	
		
		$categories = [];
		if ( apply_filters( 'usam_add_categories_catalog_menu', true, $item, $depth ) && $item->object_id == usam_get_system_page_id('products-list') && !$depth )
		{ 
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
		
		$args = apply_filters( 'nav_menu_item_args', $args, $item, $depth );		
		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $item->classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';		
		
		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->term_id, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';		
		$output .= $indent . '<li' . $id . $class_names .'>';		
		$atts = [];
		$atts['target'] = !empty( $item->target ) ? $item->target : '';
		$atts['rel']    = !empty( $item->xfn )   ? $item->xfn : '';
		$atts['class']  = 'menu-item-link';
		$atts['href']   = get_term_link($item->term_id, $item->category );		
		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

		$attributes = '';
		foreach ( $atts as $attr => $value ) 
		{
			if ( !empty( $value ) ) 
			{
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}	
		$title = apply_filters( 'nav_menu_item_title', $item->name, $item, $args, $depth );
		$title = (isset($args['link_before'])?$args['link_before']:''). $title . (isset($args['link_after'])?$args['link_after']:'');

		if ( !empty($category_children[$item->term_id]) )
			$title .= usam_get_svg_icon("angle-down-solid", "toggle_level");
		
		$item_output = isset($args['before'])?$args['before']:'';			
		if ( !isset($args['level_no_link']) || $args['level_no_link'] != $depth )
			$item_output .= '<a'. $attributes .'>'.$title.'</a>';
		else
			$item_output .= '<span'. $attributes .'>'.$title.'</span>';
		
		if ( $item->object == 'usam-category' )
		{
			$category_children = get_option('usam-category_children');
			if ( isset($category_children[$item->object_id]) )
			{
				$categories = usam_get_terms(['child_of' => $item->object_id]);
				$args_menu = ['walker_nav_menu' => true, 'number_columns' => 1, 'before' => '', 'after' => '', 'link_before' => '', 'link_after' => '']; 
				require_once( USAM_FILE_PATH . '/includes/taxonomy/class-walker-product_category-columns-menu.php' );
				$walker = new Walker_Product_Category_Colums_Nav_Menu();
				$item_output .= '<ul class ="sub-menu">'.call_user_func_array([$walker, 'walk'], [$categories, 0, $args_menu]).'</ul>';
			}
		}		
		$item_output .= isset($args['after'])?$args['after']:'';		
		$output .= apply_filters( 'walker_product_category_nav_mobile_menu_start_el', $item_output, $item, $depth, $args );		
	}
	
	public function end_el( &$output, $item, $depth = 0, $args = array() ) 
	{	 				
		$output .= "</li>\n";
	}
	
	public function start_lvl( &$output, $depth = 0, $args = array() ) 
	{			
		$i = $depth +1; 
		$indent = str_repeat("\t", $depth);	
		$output .= "\n$indent<ul class='sub-menu'>";	
	}
	
	public function end_lvl( &$output, $depth = 0, $args = array() ) 
	{
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";	
	}
} 