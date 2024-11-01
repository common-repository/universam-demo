<?php
global $wp_roles, $wpdb, $wp_rewrite;

delete_option( 'usam_product_order' );

USAM_Install::create_or_update_tables([USAM_TABLE_CHAT_MESSAGE_STATUSES, USAM_TABLE_CHAT_USERS]);

$wpdb->query("DELETE FROM `".USAM_TABLE_TERM_META."` WHERE meta_key='product_order'"); 

$contacts = $wpdb->get_results( "SELECT contact_id, status, id FROM ".USAM_TABLE_CHAT."" );	
foreach( $contacts as $chat )
{
	$wpdb->insert( USAM_TABLE_CHAT_MESSAGE_STATUSES, ['message_id' => $chat->id, 'status' => $chat->status, 'contact_id' => $chat->contact_id]);	
}
$contacts = $wpdb->get_results( "SELECT contact_id, id FROM ".USAM_TABLE_CHAT_DIALOGS."" );	
foreach( $contacts as $chat )
{
	$wpdb->insert( USAM_TABLE_CHAT_USERS, ['dialog_id' => $chat->id, 'contact_id' => $chat->contact_id]);	
}

$wpdb->query( "ALTER TABLE `".USAM_TABLE_CHAT."` DROP COLUMN `status`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CHAT_DIALOGS."` DROP COLUMN `contact_id`" );


//$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` CHANGE COLUMN `appeal` `name` varchar(255) NOT NULL DEFAULT ''" );


$capabilities = array( 
	'view_departments' => ['administrator', 'shop_manager', 'employee', 'personnel_officer'],
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

$roles = ['administrator', 'personnel_officer'];
foreach( ['contact', 'employee', 'company', 'department'] as $type )
{	
	foreach( ['edit', 'delete', 'add', 'export', 'import'] as $key )
	{
		$capability_id = $key.'_'.$type;
		foreach ( $wp_roles->role_objects as $wp_role ) 
		{				
			if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
				$wp_role->add_cap( $capability_id );
		}
	}
}




