<?php

global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables( array(USAM_TABLE_PRODUCT_PRICE, USAM_TABLE_PRODUCT_META, USAM_TABLE_ORDERS) );

$wpdb->query("TRUNCATE TABLE ".USAM_TABLE_PRODUCT_PRICE);
$wpdb->query( "INSERT `".USAM_TABLE_PRODUCT_PRICE."` (product_id,meta_key,meta_value) SELECT post_id,meta_key,meta_value FROM ".$wpdb->postmeta." WHERE meta_key LIKE '_usam_price%' OR meta_key LIKE '_usam_old_price%' OR meta_key LIKE '_usam_underprice%'" );

$prices = usam_get_prices( );
foreach ( $prices as $price )
{
	$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_PRICE." SET meta_key='price_".$price['code']."' WHERE `meta_key`='_usam_price_".$price['code']."'");
	$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_PRICE." SET meta_key='old_price_".$price['code']."' WHERE `meta_key`='_usam_old_price_".$price['code']."'");
	$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_PRICE." SET meta_key='underprice_".$price['code']."' WHERE `meta_key`='_usam_underprice_".$price['code']."'");
}
update_option( 'usam_db_version', 439 );

$wpdb->query("DELETE FROM ".USAM_TABLE_PRODUCT_PRICE." WHERE meta_key LIKE '_usam_%'");
$wpdb->query("DELETE FROM ".USAM_TABLE_STOCK_BALANCES." WHERE meta_key LIKE '_usam_%'");
