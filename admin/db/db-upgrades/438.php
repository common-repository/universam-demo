<?php

global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables( array(usam_get_table_db('posts_search'), usam_get_table_db('product_attribute_options'), usam_get_table_db('product_filters'), USAM_TABLE_STOCK_BALANCES, USAM_TABLE_POST_META) );

$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_usam_product_filter'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_filter_%'");


$wpdb->query("TRUNCATE TABLE ".USAM_TABLE_STOCK_BALANCES);
$wpdb->query( "INSERT `".USAM_TABLE_STOCK_BALANCES."` (product_id,meta_key,meta_value) SELECT post_id,meta_key,meta_value FROM ".$wpdb->postmeta." WHERE meta_key LIKE '_usam_stock%' OR meta_key LIKE '_usam_storage%' OR meta_key LIKE '_usam_reserve%' OR meta_key LIKE '_usam_total_balance'" );
$wpdb->query("UPDATE ".USAM_TABLE_STOCK_BALANCES." SET meta_key='total_balance' WHERE `meta_key`='_usam_total_balance'");
$wpdb->query("UPDATE ".USAM_TABLE_STOCK_BALANCES." SET meta_key='stock' WHERE `meta_key`='_usam_stock'");
$storages = usam_get_storages( array('cache_meta' => true) );

$sales_area = maybe_unserialize( get_option('usam_sales_area') );
if (!empty($sales_area) )
{
	foreach ( $sales_area as $sale_area )
	{			
		$wpdb->query("UPDATE ".USAM_TABLE_STOCK_BALANCES." SET meta_key='stock_".$sale_area['id']."' WHERE `meta_key`='_usam_stock_".$sale_area['id']."'");
	}
}
foreach ( $storages as $storage)
{
	$wpdb->query("UPDATE ".USAM_TABLE_STOCK_BALANCES." SET meta_key='storage_$storage->id' WHERE `meta_key`='_usam_storage_$storage->id'");
}

require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
$reviews = usam_get_customer_reviews( array('groupby' => 'page_id', 'fields' => array( 'count', 'page_id') ) );
foreach ( $reviews as $review )
{
	if ( $review->count )
		usam_update_post_meta($review->page_id, 'comment', $review->count);			
}

$term_ids = get_terms( array('fields' => 'ids', 'taxonomy' => 'usam-product_attributes', 'hide_empty' => 0, 'meta_query' => array( array('key' => 'filter','value' => 1, 'compare' => '=') ) ));
foreach( $term_ids as $term_id )
{
	usam_calculate_product_filters( $term_id );	
}