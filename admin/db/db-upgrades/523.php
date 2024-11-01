<?php
global $wp_roles, $wpdb, $wp_rewrite;
	
				
delete_option('usam_search_option');	
delete_option('usam_add_users_to_mailing_list');	

$wpdb->query( "ALTER TABLE `".USAM_TABLE_COUPON_CODES."` CHANGE COLUMN `customer` `user_id` bigint(15) unsigned NOT NULL DEFAULT '0'" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_COUPON_CODES."` CHANGE COLUMN `start` `start_date` datetime NULL" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_COUPON_CODES."` CHANGE COLUMN `expiry` `end_date` datetime NULL" );
USAM_Install::create_or_update_tables([USAM_TABLE_COUPON_CODE_META]);
$coupons = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_COUPON_CODES." " );
foreach( $coupons as $coupon )
{
	if ( $coupon->condition )
		usam_update_coupon_metadata( $coupon->id, 'conditions', unserialize($coupon->condition) );	
}

USAM_Install::create_or_update_tables([USAM_TABLE_SHIPPED_PRODUCTS]);

$wpdb->query("DELETE FROM `".USAM_TABLE_TERM_META."` WHERE meta_key='order_props_group'"); 
  

//$wpdb->query( "ALTER TABLE `".USAM_TABLE_COUPON_CODES."` DROP COLUMN `condition`" );
 