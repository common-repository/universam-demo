<?php
// Описание: Просмотренные товары

?>
<div class = 'profile__title'>
	<h1 class="title"><?php _e( 'Мои просмотренные товары', 'usam'); ?></h1>
</div>		
<div class='history_views_product products_grid'>
<?php
$contact_id = usam_get_contact_id();
if ( $contact_id )
{ 
	require_once( USAM_FILE_PATH . '/includes/analytics/pages_viewed_query.class.php' );
	$product_ids = usam_get_pages_viewed( array( 'groupby' => 'post_id', 'fields' => 'post_id', 'post-type' => 'usam-product', 'contact_id' => $contact_id, 'number' => 24 ) );	
	if ( !empty($product_ids) )		
	{	
		global $wp_query, $product_limit;
		$product_limit = 4;		
		$wp_query = new WP_Query( array( 'post_status' => 'publish', 'post__in' => $product_ids, 'post_type' => 'usam-product', 'orderby' => 'post__in', 'post_parent' => 0, 'no_found_rows' => false ) );	
		update_post_thumbnail_cache( $wp_query );	
		while (usam_have_products()) :  			
			usam_the_product(); 		
			include( usam_get_template_file_path( 'grid_product' ) );
		endwhile; 	
		wp_reset_postdata();
		wp_reset_query();	
	}
}
?>				
</div>	
<div class = "usam_navigation">
	<?php
	usam_product_info();	
	usam_pagination();
	?>
</div>