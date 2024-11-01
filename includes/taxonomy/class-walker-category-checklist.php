<?php
class Walker_Category_Checklist extends Walker 
{	
	public $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); 
	private static $recursion = 0;
	private $prefix = '';
	
	public function start_lvl( &$output, $depth = 0, $args = array() ) 
	{
		self::$recursion++;
		$this->prefix = str_repeat( '&nbsp;&nbsp;&nbsp;' , self::$recursion );
		$indent = str_repeat("\t", $depth);	
	}
	
	public function end_lvl( &$output, $depth = 0, $args = array() ) 
	{		
		self::$recursion--;
		$indent = str_repeat("\t", $depth);	
	}
	
	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 )
	{
		$taxonomy = $args['taxonomy'];
	
		$args['popular_cats'] = empty( $args['popular_cats'] ) ? array() : $args['popular_cats'];	
		$class = in_array( $category->term_id, $args['popular_cats'] ) ? ' class="popular-category"' : '';

		$args['selected_cats'] = empty( $args['selected_cats'] ) ? array() : $args['selected_cats'];
		
			
		$inner_class = 'category';				
		$output .= "<label class='selectit'><input class='$inner_class' id='in-|".esc_attr( $taxonomy )."-$category->term_id' data-term-id='$category->term_id' type='checkbox' value='1' name='category[$category->slug]' ".checked( $checked, true, false ).disabled( empty( $args['disabled'] ), false, false )."/>".$this->prefix.esc_html( $category->name )."
		</label>";			
	}
	
	public function end_el( &$output, $category, $depth = 0, $args = array() ) {
		$output .= "";
	}
}