<?php

global $wp_roles, $wpdb;
$wpdb->query( "ALTER TABLE `".USAM_TABLE_USER_POSTS."` DROP COLUMN `user_code`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_USER_POSTS."` DROP COLUMN `user_id`" );

USAM_Install::create_or_update_tables( array(USAM_TABLE_POST_META) );


$post_metas = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_usam_sticky_products' OR meta_key = '_usam_product_views' OR meta_key = '_usam_rating' OR meta_key = '_usam_rating_count'" );
foreach ( $post_metas as $meta )
{
	if ( !$meta->meta_value )
		continue;
		
	if ( $meta->meta_key == '_usam_sticky_products' )
	{
		require_once(USAM_FILE_PATH.'/includes/customer/user_post.class.php');
		usam_insert_user_post( array('user_list' => 'sticky', 'product_id' => $meta->post_id) );
		continue;
	}	
	switch( $meta->meta_key )
	{
		case '_usam_product_views':			
			$meta_key = 'views';
		break;		
		case '_usam_rating':			
			$meta_key = 'rating';
		break;
		case '_usam_rating_count':			
			$meta_key = 'rating_count';
		break;
	}				
	usam_update_post_meta($meta->post_id, $meta_key, $meta->meta_value );	
}
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_usam_sticky_products' OR meta_key = '_usam_product_views' OR meta_key = '_usam_rating' OR meta_key = '_usam_rating_count'");