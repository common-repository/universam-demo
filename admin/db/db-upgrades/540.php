<?php
global $wp_roles, $wpdb, $wp_rewrite;

USAM_Install::create_or_update_tables([USAM_TABLE_OBJECT_STATUSES, USAM_TABLE_SHIPPED_DOCUMENTS, USAM_TABLE_USER_POSTS]);

$products = $wpdb->get_results( "SELECT product_id, user_list, COUNT(*) AS counter FROM ".USAM_TABLE_USER_POSTS." WHERE user_list='desired' GROUP BY product_id" );	
foreach( $products as $product )
{
	usam_update_post_meta($product->product_id, $product->user_list, $product->counter);	
}

$products = $wpdb->get_results( "SELECT product_id, user_list, COUNT(*) AS counter FROM ".USAM_TABLE_USER_POSTS." WHERE user_list='compare' GROUP BY product_id" );	
foreach( $products as $product )
{
	usam_update_post_meta($product->product_id, $product->user_list, $product->counter);	
}

$products = $wpdb->get_results( "SELECT po.product_id, SUM(po.quantity) AS counter FROM ".USAM_TABLE_PRODUCTS_ORDER." AS po INNER JOIN ".USAM_TABLE_ORDERS." AS p ON (p.id = po.order_id AND p.status='closed') GROUP BY po.product_id" );	
foreach( $products as $product )
{
	usam_update_post_meta($product->product_id, 'purchased', $product->counter);	
}

$products = $wpdb->get_results( "SELECT product_id, SUM(quantity) AS counter FROM ".USAM_TABLE_PRODUCTS_BASKET." GROUP BY product_id" );	
foreach( $products as $product )
{
	usam_update_post_meta($product->product_id, 'basket', $product->counter);	
}
