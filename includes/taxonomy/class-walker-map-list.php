<?php
/* Строит карту */
class Walker_Map_List extends Walker
{
	public $tree_type = 'category';
	public $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); 
	public static $number = 0;	
	private $start_with_categories = null;	
	
	public function get_count_category_children( $args ) 
	{
		$category_children = get_option($args['taxonomy'].'_children');	
		$cat_count = array();
		$count = 0; 
		$current_default   = get_option( 'usam_default_menu_category', 0 );	
		$categories = get_terms( "taxonomy=".$args['taxonomy']."&hierarchical=false&parent=$current_default&update_term_meta_cache=0&fields=ids" );		
		foreach ( $categories as $id ) 
		{
			$cat_count[$id] = 1;
			if ( isset($category_children[$id]) )
			{
				$children = $category_children[$id];
				$cat_count[$id] = + count($children) + $this->_get_count_category_children( $args, $children );
				$count += $cat_count[$id];
			}		
		}	
		$result = ceil( $count / $args['split'] );
		
		$start_with_categories = array();	
		$sum = 0;	
		foreach ( $cat_count as $id => $count ) 
		{
			$sum += $count;			
			if ( $sum >= $result )
			{
				$sum = 0;
				$start_with_categories[] = $id;
			}			
		}	
		return $start_with_categories;
	}
	
	public function start_lvl( &$output, $depth = 0, $args = array() ) 
	{			
		$i = $depth +1; 
		$indent = str_repeat("\t", $depth);	
		$output .= "\n$indent<ul class=\"categories_list categories_list_$i\">\n";	
	}
	
	public function end_lvl( &$output, $depth = 0, $args = array() ) 
	{
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";	
	}
	
	public function _get_count_category_children( $args, $_category_children ) 
	{
		$category_children = get_option($args['taxonomy'].'_children');
		$count = 0;
		foreach ( $_category_children as $id ) 
		{			
			if ( isset($category_children[$id]) )
				$count += $this->_get_count_category_children( $args, $category_children[$id] );
		}	
		return $count;
	}
	
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) 
	{	
		global $wp_query;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'category_' . $item->term_id;
		$classes[] = 'category_level_' . $depth;		
		$args = apply_filters( 'category_list_item_args', $args, $item, $depth );
		
		$class_names = join( ' ', apply_filters( 'product_category_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
		
		$id = apply_filters( 'category_list_item_id', 'menu-item-'. $item->term_id, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';
	
		$output .= $indent . '<li' . $id . $class_names .'>';	

		$atts = array();
		$atts['class']  = !empty( $item->class )  ? $item->class : '';
		$atts['target'] = !empty( $item->target ) ? $item->target     : '';
		$atts['rel']    = !empty( $item->xfn )    ? $item->xfn        : '';

		$term_link   = get_term_link($item->term_id, $item->taxonomy);			
		$atts['href'] = $term_link;		
		if ( $args['taxonomy'] == 'usam-category' )
		{
			if ( isset($wp_query->query['pagename']) && in_array($wp_query->query['pagename'], usam_get_product_pages()) ) //Если страница распродаж
			{		
				$permalinks  = get_option( 'usam_permalinks' );		
				$atts['href'] = str_replace( $permalinks['category_base'], $wp_query->query['pagename'], $term_link );	
			}		
		}
		$atts = apply_filters( 'category_list_link_attributes', $atts, $item, $args, $depth );

		$attributes = '';
		foreach ( $atts as $attr => $value ) 
		{
			if ( !empty( $value ) ) {
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}	
		$title = apply_filters( 'category_list_item_title', $item->name, $item, $args, $depth );

		if ( !empty($args['count']) )			
			$title .= " ($item->count)";	
		
		$item_output = $args['before'].'<a'. $attributes .'>'.$args['link_before']. $title . $args['link_after'].'</a>'.$args['after'];
		$output .= apply_filters( 'walker_map_list_start_el', $item_output, $item, $depth, $args );
	}
	
	public function end_el( &$output, $item, $depth = 0, $args = array() ) 
	{	
		$posts = get_posts(['post_status' => 'publish', 'post_type' => 'post', 'numberposts' => -1, 'tax_query' => [['taxonomy' => $args['taxonomy'], 'field' => 'id', 'terms' => [$item->term_id] ]]]);
		if ( !empty($posts) )
		{
			$output .= '<ul class ="main_categories">';			
			$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
			foreach ( $posts as $post ) 
			{
				$output .= $indent . "<li id='map_post-{$post->ID}' class='map_post_$depth'><a href='".get_permalink( $post->ID )."'>".$args['link_before']. esc_html($post->post_title) . $args['link_after'].'</a></li>';			
			}
			$output .= '</ul>';	
		}
		$output .= '</li>';	
		if ( $args['split'] > 1 )
		{
			switch ($depth)
			{
				case 0:	
					self::$number ++;					
					if ( $this->start_with_categories === null && !empty($args['split']) )
						$this->start_with_categories = $this->get_count_category_children( $args );
					
					if ( !empty($this->start_with_categories) && in_array( $item->term_id, $this->start_with_categories ) )			
					{				
						self::$number = 0;
						$output .= '</ul><ul class ="main_categories">';				
					}		
				break;		
			}
		}
	}
} 