<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';

//USAM_Install::create_or_update_tables([USAM_TABLE_COMMENT_META]); 

$wpdb->query( "ALTER TABLE `".USAM_TABLE_BANNERS."` DROP COLUMN `action_type`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_BANNERS."` DROP COLUMN `action_code`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SLIDES."` DROP COLUMN `url`" );

$wpdb->query( "ALTER TABLE `".USAM_TABLE_BANNERS."` CHANGE COLUMN `banner_type` `type` varchar(250) NOT NULL DEFAULT ''" );