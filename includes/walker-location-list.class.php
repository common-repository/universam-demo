<?php
/* Строит меню категорий */
class Walker_Location_Checklist extends Walker
{
	public $tree_type = 'category';
	public $db_fields = array ('parent' => 'parent', 'id' => 'id'); 
	public static $number = 0;		
	
	public function __construct() 
	{		
	
	}	
	
	public function start_lvl( &$output, $depth = 0, $args = array() ) 
	{			
		$indent = str_repeat("\t", $depth);	
		$output .= "\n$indent<ul class=\"categories_list\">\n";	
	}
	
	public function end_lvl( &$output, $depth = 0, $args = array() ) 
	{
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";	
	}
		
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) 
	{	
		global $wp_query;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'location_' . $item->id;
		$classes[] = 'location_level_' . $depth;
		if ( !empty($args['select']) && in_array($item->id, $args['select'])  )
			$classes[] = 'select_category';		
		
		$args = apply_filters( 'location_list_item_args', $args, $item, $depth );
		
		$class_names = join( ' ', apply_filters( 'location_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
		
		$id = apply_filters( 'location_list_item_id', 'location-item-'. $item->id, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';
	
		$output .= $indent . '<li' . $id . $class_names .'>';	
		
		$title = apply_filters( 'location_list_item_title', $item->name, $item, $args, $depth );

		if ( !empty($args['count']) )			
			$title .= " ($item->count)";	
		
		$prefix = str_repeat( '&nbsp;&nbsp;&nbsp;' , $depth );		
		$item_output = $args['before'];
		$item_output .= $prefix."<input id = 'location_".$item->id."' type='checkbox' name='locations[]' value='".$item->id."' ".checked( in_array( $item->id, $args['selected'] ), true, false )." ><label for='location_".$item->id."'>".$title."</label>";		
		
		if ( $item->code != $args['code'] )			
			$item_output .= "<span id = 'open_".$item->id."' > + </span>";
		
		$item_output .= $args['after'];
		$output .= apply_filters( 'walker_location_list_start_el', $item_output, $item, $depth, $args );
		
	}
	
	public function end_el( &$output, $item, $depth = 0, $args = array() ) 
	{	
		$output .= "</li>\n";
	}
} 