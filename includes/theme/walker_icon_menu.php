<?php
class USAM_Walker_Icon_Menu extends Walker_Nav_Menu 
{	
	public static $number = 0;	
	
	function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) 
	{
		global $wp_query;
		
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		
		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = ' class="' . esc_attr( $class_names ) . '"';

		$output .= $indent . '<li id="menu-item-'. $item->ID . '"' . $value . $class_names .'>';

		$attributes = !empty( $item->attr_title ) ? ' title="' . esc_attr( $item->attr_title ) .'"' : '';
		$attributes .= !empty( $item->target ) ? ' target="' . esc_attr( $item->target ) .'"' : '';
		$attributes .= !empty( $item->xfn ) ? ' rel="' . esc_attr( $item->xfn ) .'"' : '';
		$attributes .= !empty( $item->url ) ? ' href="' . esc_attr( $item->url ) .'"' : '';

		$domain = explode('.',parse_url($item->url, PHP_URL_HOST));	
		$icon = count($domain)==2?$domain[0]:$domain[1];
		if ( $icon === 't' )
			$icon = 'telegram';
	
		$item_output = $args->before;
		$item_output .= '<a'. $attributes .' target="_blank" rel="nofollow">';		
		$item_output .= $args->link_before . usam_get_svg_icon( $icon ). $args->link_after;	
		$item_output .= '</a>';			
		$output .= apply_filters( 'usam_walker_icon_menu_start_el', $item_output, $item, $depth, $args );
	}
}
?>