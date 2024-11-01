<?php
global $wp_roles, $wpdb, $wp_rewrite;

USAM_Install::create_or_update_tables([USAM_TABLE_DOCUMENT_DISCOUNTS, USAM_TABLE_FEEDS, USAM_TABLE_FEED_META, USAM_TABLE_PRODUCTS_ORDER]);

$wpdb->query("UPDATE ".USAM_TABLE_SHIPPED_PRODUCTS." SET unit_measure='thing' WHERE `unit_measure`='0.000000'");
$products = $wpdb->get_results( "SELECT name, product_id FROM ".USAM_TABLE_SHIPPED_PRODUCTS." WHERE name='' OR name='0' GROUP BY product_id" );
foreach ( $products as $product ) 
{
	$name = get_the_title( $product->product_id );			
	if ( $name )
	{	
		$wpdb->query("UPDATE ".USAM_TABLE_SHIPPED_PRODUCTS." SET name='$name' WHERE `product_id`='$product->product_id' AND (name='' OR name='0')");
	}
}

require_once( USAM_FILE_PATH . '/includes/exchange/feed.class.php');
$items = get_option('usam_trading_platforms_rules');
$items = maybe_unserialize( $items );
if ( !empty($items) )
{
	foreach ( $items as $item )
	{
		$id = usam_insert_feed( $item );
		foreach ( $item as $key => $value )
		{
			if ( !in_array($key, ['name', 'platform', 'start_date', 'end_date',	'type_price', 'id','active']) )
			{
				usam_add_feed_metadata( $id, $key, $value );
			}		
		}
	}
}
include_once(USAM_FILE_PATH.'/includes/product/discount_rules_query.class.php');
require_once( USAM_FILE_PATH .'/includes/document/document_discount.class.php' );
$discounts = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_DISCOUNT_RULES." WHERE order_id!=0" );
foreach ( $discounts as $discount )
{
	$insert = (array)$discount;
	$insert['document_id'] = $insert['order_id'];
	$insert['rule_id'] = $insert['parent_id'];
	$insert['document_type'] = 'order';
	usam_insert_document_discount( $insert );
}
$wpdb->query("DELETE FROM `".USAM_TABLE_DISCOUNT_RULES."` WHERE order_id!=0"); 

$gateway = get_option( 'usam_directory' );
if ( !empty($gateway['secret_key']) )
	usam_insert_application(['active' => 1, 'service_code' => 'dadata', 'group_code' => 'directories', 'access_token' => $gateway['token'], 'password' => $gateway['secret_key']]);


$gateway = get_option( 'usam_ip_telephony' );
if ( !empty($gateway['password']) )
	usam_insert_application(['active' => 1, 'service_code' => 'zadarma', 'group_code' => 'telephony', 'login' => $gateway['login'], 'password' => $gateway['password']]);
		
		
delete_option('usam_trading_platforms_rules');
delete_option('usam_directory');
delete_option('usam_ip_telephony');