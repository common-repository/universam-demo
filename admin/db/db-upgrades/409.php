<?php
global $wpdb;

delete_option('usam_record_affairs_managers');

USAM_Install::create_or_update_tables( array(USAM_TABLE_CHANGE_HISTORY, USAM_TABLE_CHANGE_HISTORY, USAM_TABLE_DOCUMENT_CONTACTS, USAM_TABLE_OBJECT_STATUSES, USAM_TABLE_DEPARTMENTS, USAM_TABLE_CAMPAIGNS, USAM_TABLE_FILTERS )  );

$wpdb->query( "ALTER TABLE `".USAM_TABLE_USERS_BASKET."` DROP COLUMN `date_modified`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_ORDERS."` DROP COLUMN `date_modified`" );

$wpdb->query( "RENAME TABLE {$wpdb->prefix}usam_email_file TO {$wpdb->prefix}usam_files" );

require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
require_once( USAM_FILE_PATH .'/includes/personnel/department.class.php' );
$option = get_option('usam_departments');
$departments = maybe_unserialize( $option );
$wpdb->query("UPDATE ".USAM_TABLE_CONTACT_META." SET meta_key='_department' WHERE `meta_key`='department'");	
foreach( $departments as $department )
{
	$department_id = usam_insert_department( $department );
	$wpdb->query("UPDATE ".USAM_TABLE_CONTACT_META." SET meta_value='$department_id', meta_key='department' WHERE `meta_key`='_department' AND `meta_value`='".$department['id']."'");
}	
$wpdb->query("UPDATE ".USAM_TABLE_CHAT." SET `source`='chat' WHERE `source`='' OR source='website'");

require_once( USAM_FILE_PATH . '/admin/db/db-install/system_default_data.php' );	
new USAM_Load_System_Default_Data( array('location_type') );

$props = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usam_order_props_value" );	
foreach( $props as $prop )	
{	
	if ( !empty($prop->value) )
		usam_update_order_metadata($prop->order_id, $prop->unique_name, $prop->value ) ;
}


$wpdb->query( "DROP TABLE {$wpdb->prefix}usam_feedback" );
$wpdb->query( "DROP TABLE {$wpdb->prefix}usam_means_communication_list" );
$wpdb->query( "DROP TABLE {$wpdb->prefix}usam_order_feedback" );
$wpdb->query( "DROP TABLE {$wpdb->prefix}usam_program_pyramid" );
?>