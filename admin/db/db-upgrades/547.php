<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';
delete_option( 'usam_product_order' );

$wpdb->query( "RENAME TABLE {$usam_base_prefix}chat_dialog_users TO ".USAM_TABLE_CHAT_USERS );
/*

$wpdb->query( "INSERT `".USAM_TABLE_CHAT_USERS."` (dialog_id,contact_id,not_read) SELECT `dialog_id`,`contact_id`,`not_read` FROM {$usam_base_prefix}chat_dialog_users" );
$wpdb->query( "DROP TABLE `{$usam_base_prefix}chat_dialog_users`" );
*/

$wpdb->query( "DROP TABLE `".$usam_base_prefix."business_process_template_element`" );
$wpdb->query( "DROP TABLE `".$usam_base_prefix."business_processes_templates`" );
$wpdb->query( "DROP TABLE `".$usam_base_prefix."business_processes`" );
$wpdb->query( "DROP TABLE `".$usam_base_prefix."business_process_meta`" );
$wpdb->query( "DROP TABLE `".$usam_base_prefix."event_relationships`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SMS."` DROP COLUMN `object_type`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SMS."` DROP COLUMN `object_id`" );



$users = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_CHAT_USERS."" );	
$employee_ids = usam_get_contacts(['fields' => 'id', 'source' => 'employee']);
foreach ( $users as $i => $user ) 
{
	if ( !in_array($user->contact_id, $employee_ids ) )
		usam_update_chat_dialog($user->dialog_id, ['contact_id' => $user->contact_id]);
	unset($users[$i]);
}



$capabilities = array( 
	'view_automation' => array( 'administrator', 'shop_manager' ),
	'view_triggers' => array( 'administrator', 'shop_manager' ),	
	//'write_to_chat' => array( 'administrator', 'shop_manager' ),	
	
);
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) )
		{								
			if ( !$wp_role->has_cap( $capability_id ) )
			{						
				$wp_role->add_cap( $capability_id );						
			}	
		}
	}
}