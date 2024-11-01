<?php
global $wpdb;

USAM_Install::create_or_update_tables( array( USAM_TABLE_PRODUCTS_ORDER, USAM_TABLE_CHAT_DIALOGS, USAM_TABLE_ORDERS, USAM_TABLE_TAX_PRODUCT_DOCUMENT  )  );
require_once( USAM_FILE_PATH . '/includes/customer/capabilities_schema.php' );	

global $wpdb;
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CHAT_DIALOGS."` DROP COLUMN `sessionid`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CHAT_DIALOGS."` DROP COLUMN `product_id`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CHAT_DIALOGS."` DROP COLUMN `name`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CHAT."` DROP COLUMN `ip`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CHAT_DIALOGS."` DROP COLUMN `dialog_id`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CHAT."` CHANGE COLUMN `topic` `dialog_id` bigint(15) unsigned NOT NULL DEFAULT '0'" );

USAM_Install::create_or_update_tables( array( USAM_TABLE_CHAT_DIALOGS, USAM_TABLE_CHAT, USAM_TABLE_SOCIAL_NETWORK_PROFILE_META, USAM_TABLE_CHAT_BOT_TEMPLATES, USAM_TABLE_CHAT_BOT_COMMANDS, USAM_TABLE_CHAT_BOT_COMMAND_META, USAM_TABLE_LOCATION_META )  );

$wpdb->query("UPDATE ".USAM_TABLE_CHAT." SET `status`=1 WHERE `status`=2");
$wpdb->query("UPDATE ".USAM_TABLE_CHAT." SET `status`=0 WHERE `status`=1");

?>