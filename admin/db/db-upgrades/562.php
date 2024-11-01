<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';
USAM_Install::create_or_update_tables([USAM_TABLE_BONUS_TRANSACTIONS]);


$wpdb->query("UPDATE ".USAM_TABLE_BONUS_TRANSACTIONS." SET object_id=order_id, object_type='order' WHERE order_id>0");
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CUSTOMER_REVIEWS."` DROP COLUMN `user_id`" );
