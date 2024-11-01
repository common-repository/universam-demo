<?php
class Walker_Category_Select extends Walker 
{	
	public $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); 

	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 )
	{ 
		$taxonomy = $args['taxonomy'];
		$prefix = str_repeat( '&nbsp;&nbsp;&nbsp;' , $depth );
		$args['popular_cats'] = empty( $args['popular_cats'] ) ? array() : $args['popular_cats'];	
		$class = in_array( $category->term_id, $args['popular_cats'] ) ? "class='popular-category category'" : "class='category'";
		$selected = empty($args['selected_cats']) ? '' : $args['selected_cats'][0];
		$return_value = !empty($args['list_only']) && $args['list_only'] == 'slug' ? 'slug' : 'id';		
		if ( $return_value == 'slug' ) 
		{				
			$output .= "<option $class data-term-id='$category->term_id' value='$category->slug'".disabled( empty($args['disabled']), false, false ).selected($selected, $category->slug, false).">".$prefix.esc_html__( apply_filters('the_category', $category->name) ).'</option>';
		}	
		else 
		{		
			$output .= "<option id='{$taxonomy}-{$category->term_id}' $class value='$category->term_id' ".selected($category->term_id, $selected, false) . disabled(empty($args['disabled']), false, false )."> ".$prefix.esc_html__( apply_filters('the_category', $category->name) )."</option>";
		}
	}
	
	public function end_el( &$output, $category, $depth = 0, $args = array() ) {		
		$output .= ""; 
	}
}