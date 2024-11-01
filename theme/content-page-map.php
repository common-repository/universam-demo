<?php
// Описание: Шаблон страницы "Карта сайта"
if ( get_option('usam_website_type', 'store' ) != 'crm' )
{
	require_once( USAM_FILE_PATH . '/includes/taxonomy/class-walker-product_category-list.php'     );
	$current_default   = get_option( 'usam_default_menu_category', 0 );	
	$args_menu = [
				'descendants_and_self' => 0,				
				'taxonomy'             => 'usam-category',				
				'checked_ontop'        => false, 			
				'before' => '', 
				'after' => '',
				'link_before' => '', 
				'link_after' => '',
				'split' => 3,
				'disable_svg_icon' => true,				
			];	
	$categories = get_terms("taxonomy=usam-category&hide_empty=0&child_of=$current_default&update_term_meta_cache=0" );				

	$walker = new Walker_Product_Category_List();
	echo '<div class="map_categories"><ul class ="main_categories column3">'.call_user_func_array( array( $walker, 'walk' ), array( $categories, 0, $args_menu ) ).'</ul></div>';
}
else
{
	require_once( USAM_FILE_PATH . '/includes/taxonomy/class-walker-map-list.php' );
	$args_menu = array(
				'descendants_and_self' => 0,				
				'taxonomy'             => 'category',				
				'checked_ontop'        => false, 			
				'before' => '', 
				'after' => '',
				'link_before' => '', 
				'link_after' => '',
				'split' => 1,
			);	
	$categories = get_terms("taxonomy=category&hide_empty=0&update_term_meta_cache=0" );				

	$walker = new Walker_Map_List();
	$posts = get_posts( array( 'post_status' => 'publish', 'post_type' => 'page', 'numberposts' => -1));
	echo '<div class="map_categories"><ul class ="main_categories column1">';	
	foreach ( $posts as $post )
	{
		echo "<li id='map_page-{$post->ID}' class='map_page'><a href='".get_permalink( $post->ID )."'>".esc_html($post->post_title).'</a></li>';	
	}	
	echo '</ul>';		
	echo '<ul class ="main_categories column1">'.call_user_func_array( array( $walker, 'walk' ), array( $categories, 0, $args_menu ) ).'</ul></div>';	
}