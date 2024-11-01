<?php
global $wp_roles, $wpdb, $wp_rewrite;

USAM_Install::create_or_update_tables([USAM_TABLE_PRODUCTS_ORDER]);

$messages = $wpdb->get_results( "SELECT id, reply_message_id FROM ".USAM_TABLE_EMAIL." WHERE reply_message_id!='0'" );
foreach ( $messages as $message ) 
{
	usam_set_email_object( $message->id, ['object_id' => $message->reply_message_id, 'object_type' => 'email']);
}

$wpdb->query( "ALTER TABLE `".USAM_TABLE_PRODUCTS_ORDER."` DROP COLUMN `subscribed`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_EMAIL."` DROP COLUMN `reply_message_id`" );


require_once( USAM_FILE_PATH . '/admin/db/db-install/system_default_data.php' );	
new USAM_Load_System_Default_Data(['events_status', 'documents_status']);

global $wp_roles, $wpdb, $wp_rewrite;
$options = $wpdb->get_results( "SELECT id, value FROM ".usam_get_table_db('product_attribute_options')." WHERE slug=''" );
foreach ( $options as $option ) 
{
	$wpdb->update( usam_get_table_db('product_attribute_options'), ['slug' => sanitize_title($option->value)], ['id' => $option->id] );	
}

