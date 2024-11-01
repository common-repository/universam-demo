<?php
global $wpdb;
USAM_Install::create_or_update_tables( array(USAM_TABLE_PRODUCT_META, USAM_TABLE_POST_META) );


$post_metas = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '%market_publish_date_%' OR meta_key LIKE '%market_id_%'" );
foreach ( $post_metas as $meta )
{
	if ( !$meta->meta_value )
		continue;
		
	$meta_key = str_replace('_usam_','',$meta->meta_key);
	usam_update_product_meta($meta->post_id, $meta_key, $meta->meta_value );	
}
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '%market_publish_date_%' OR meta_key LIKE '%market_id_%'");

$post_metas = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '%_publish_date_vk%' OR meta_key LIKE '%_post_id_vk%'" );
foreach ( $post_metas as $meta )
{
	if ( !$meta->meta_value )
		continue;
		
	$meta_key = str_replace('_usam_','',$meta->meta_key);
	usam_update_post_meta($meta->post_id, $meta_key, $meta->meta_value );	
}
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '%publish_date_%' OR meta_key LIKE '%_post_id_%'");


