<?php
/* Строит меню категорий */
class Walker_Product_Category_Nav_Menu extends Walker
{
	public $tree_type = 'category';
	public $db_fields = ['parent' => 'parent', 'id' => 'term_id']; 
	public static $number = 0;
	public static $count_number = 0;
	public $max_item_column = 12;
	public static $current_number_column = 1;	
	public $term_link;
	
	public function start_lvl( &$output, $depth = 0, $args = [] ) 
	{			
		$indent = str_repeat("\t", $depth);			
		$depth = !empty($args['menu_button'])?$depth+1:$depth;
		switch ( $depth )
		{
			case 0:	
				self::$number = 0;
				self::$count_number = 0;				
				self::$current_number_column = 1;
				if ( empty($args['walker_nav_menu']) )
					$output .= "\n$indent<div class='sub-menu sub_menu_map'><div class='sub_menu_map__columns'><dl class='sub_menu_map__column'>\n";
				else
					$output .= "\n$indent<div class='sub-menu'><div class='sub_menu_map__columns'><dl class='sub_menu_map__column'>\n";
			break;
			case 1:					
				$output .= "\n$indent<dl class='header-sub-menu'>\n";			
			break;		
			default:
				$output .= "\n$indent<dl class='sub_menu_map__second_level'>\n";								
			break;						
		}		
	}
	
	public function end_lvl( &$output, $depth = 0, $args = array() ) 
	{
		$indent = str_repeat("\t", $depth);				
		$depth = !empty($args['menu_button'])?$depth+1:$depth;
		switch ( $depth )
		{			
			case 0:						
				if ( empty($args['walker_nav_menu']) )
					$output .= "$indent</dl></div></div>\n";
				else
					$output .= "$indent</dl></div></div>\n";
			break;
			case 1:						
				$output .= "$indent</dl>\n";
			break;		
			default:
				$output .= "$indent</dl>\n";				
			break;					
		}		
	}
	
	public function start_el( &$output, $item, $depth = 0, $args = [], $id = 0 ) 
	{		
		$depth = !empty($args['menu_button'])?$depth+1:$depth;		
		$category_children = get_option('usam-category_children');
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		$item->classes = empty($item->classes) ? array() : (array)$item->classes;
		$item->classes[] = 'menu-item-' . $item->term_id;
		$item->classes[] = 'menu-item';		
		if ( !empty($category_children[$item->term_id]) && $depth < 1 )
			$item->classes[] = 'menu-item-has-children';	
		$args = apply_filters( 'nav_menu_item_args', $args, $item, $depth );		
		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $item->classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';		
		
		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->term_id, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';	
		switch ( $depth )
		{			
			case 0:						
				$output .= $indent . '<li' . $id . $class_names .'>';
			break;
			case 1:		
				$args['number_columns'] = isset($args['number_columns'])?$args['number_columns']:1;							
				$count_children = self::get_count_category_children( $item->term_id, $args );									
				self::$count_number ++;	
				if ( self::$count_number != 1 )
				{
					$count = self::get_count_category_one_level( $item, $args, false );			
					$all_count = self::get_count_category_one_level( $item, $args );	
					if ( $all_count <= $this->max_item_column )
						$args['number_columns'] = 1;	
					elseif ( $all_count <= $this->max_item_column*2 )
						$args['number_columns'] = 2;				
					$c = ceil($all_count / $args['number_columns'] );		
					if ( ($c <= self::$number || self::$count_number == $count && $count_children > 3) && $args['number_columns'] > self::$current_number_column )
					{				
						self::$number = 0;
						self::$current_number_column ++;
						$output .= "</dl><dl class='sub_menu_map__column'>";			
					}					
				}
				$output .=  $indent . '<dt' . $id . $class_names .'>';
				self::$number ++;
				self::$number += $count_children;	
			break;				
			default:
				$output .= $indent . '<dd' . $id . $class_names .'>';
			break;	
		}	
		$atts = [];
		$atts['target'] = !empty( $item->target ) ? $item->target     : '';
		$atts['rel']    = !empty( $item->xfn )   ? $item->xfn        : '';
		$atts['class']  = 'menu-item-link';
		$atts['href']   = get_term_link($item->term_id, $item->category );				
		$atts = apply_filters( 'usam_product_category_nav_menu_link_attributes', $atts, $item, $args, $depth );
		$attributes = '';
		foreach ( $atts as $attr => $value ) 
		{
			if( !empty( $value ) ) 
			{
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}	
		$title = apply_filters( 'nav_menu_item_title', $item->name, $item, $args, $depth );
		$title = $args['link_before']. $title . $args['link_after'];

		$item_output = $args['before'];				
		if ( !isset($args['level_no_link']) || $args['level_no_link'] != $depth )
			$item_output .= '<a'. $attributes .'>'.$title.'</a>';
		else
			$item_output .= '<span'. $attributes .'>'.$title.'</span>';
		
		$item_output .= $args['after'];
		$output .= apply_filters( 'walker_product_category_nav_menu_start_el', $item_output, $item, $depth, $args );		
	}
	
	public function end_el( &$output, $item, $depth = 0, $args = array() ) 
	{	 		
		$depth = !empty($args['menu_button'])?$depth+1:$depth; 
		switch ( $depth )
		{
			case 0:						
				$output .= "</li>\n";
			break;
			case 1:										
				$output .= "</dt>\n";	
			break;					
			default:
				$output .= "</dd>\n";
			break;					
		}
	}	
	
//Сколько категорий на том же уровне	
	public static function get_count_category_one_level( $term, $args, $one_level = 'all' ) 
	{ 		
		if ( $term->parent == 0 )
		{
			static $all_count = null;
			if ( $all_count === null )
				$all_count = get_terms(['fields' => 'count', 'taxonomy' => 'usam-category', 'hide_empty' => 0, 'update_term_meta_cache' => false]);			
			return $all_count;
		}
		else
		{
			$category_children = get_option('usam-category_children');
			if ( !empty($category_children) )
			{					
				foreach ( $category_children as $children ) 
				{
					
					if ( in_array($term->term_id, $children) )	
					{			
						$count = count($children);	
						if ( $one_level == 'all' )
						{
							foreach ( $children as $id ) 
								$count += self::get_count_category_children( $id, $args );
						}
						return $count;
					}
				}		
			}
		}
		return 0;
	}
	
	//Получить все дочерние категории
	public static function get_count_category_children( $term_id, $args ) 
	{ 		
		static $depth = 0;
		$category_children = get_option('usam-category_children');
		$count = 0;
		if ( !empty($category_children[$term_id]) )
		{			
			$count = count($category_children[$term_id]);				
			if ( empty($args['depth']) || $depth <= $args['depth'] )
			{
				foreach ( $category_children[$term_id] as $id ) 
				{
					$count += self::get_count_category_children( $id, $args );
				}
			}
		}		
		return $count;
	}
} 