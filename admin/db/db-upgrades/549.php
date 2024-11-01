<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';

USAM_Install::create_or_update_tables([USAM_TABLE_SUBSCRIPTION_PRODUCTS, USAM_TABLE_SUBSCRIPTION_META]); 

$results = $wpdb->get_results( "SELECT id, product_id, type_price FROM ".USAM_TABLE_SUBSCRIPTIONS." WHERE product_id!=0" );
foreach ( $results as $k => $result ) 
	$result = $wpdb->insert( USAM_TABLE_SUBSCRIPTION_PRODUCTS, ['product_id' => $result->product_id, 'subscription_id' => $result->id, 'date_insert' => date("Y-m-d H:i:s"), 'price' => usam_get_product_price( $result->product_id, $result->type_price ), 'name' => get_the_title( $result->product_id )] );
	
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SUBSCRIPTIONS."` DROP COLUMN `product_id`" );