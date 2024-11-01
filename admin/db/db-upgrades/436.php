<?php

global $wp_roles, $wpdb;

$wpdb->query( "ALTER TABLE `".USAM_TABLE_OBJECT_STATUSES."` DROP COLUMN `id`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_OBJECT_STATUSES."` DROP COLUMN `type`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_OBJECT_STATUSES."` CHANGE COLUMN `order_type` `type` varchar(20) NULL DEFAULT ''" );
$wpdb->query( "CREATE TABLE backup SELECT * FROM ".USAM_TABLE_OBJECT_STATUSES."" );  
$wpdb->query( "DROP TABLE `".USAM_TABLE_OBJECT_STATUSES."`" );

USAM_Install::create_or_update_tables( array(USAM_TABLE_OBJECT_STATUSES) );

$wpdb->query( "INSERT `".USAM_TABLE_OBJECT_STATUSES."` (internalname,name,active,description,color,short_name,visibility,pay,close,type,subject_email,email,sms) SELECT internalname,name,active,description,color,short_name,visibility,pay,close,type,subject_email,email,sms FROM backup" );
$wpdb->query( "DROP TABLE `backup`" );


USAM_Install::create_or_update_tables( array(USAM_TABLE_OBJECT_STATUSES, USAM_TABLE_SHIPPED_DOCUMENTS) );


$wpdb->query("DELETE FROM ".$wpdb->usermeta." WHERE meta_key='_usam_customer_profile'");

require_once( USAM_FILE_PATH . '/admin/db/db-install/system_default_data.php' );	
new USAM_Load_System_Default_Data(['events_status']);
