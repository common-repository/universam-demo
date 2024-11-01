<?php
global $wp_roles, $wpdb;

delete_option('usam_cashbox');

$wpdb->query( "ALTER TABLE `".USAM_TABLE_APPLICATIONS."` DROP COLUMN `app_id`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PAYMENT_HISTORY."` DROP COLUMN `direction`" );
$wpdb->query("UPDATE ".USAM_TABLE_PAYMENT_HISTORY." SET payment_type='card' WHERE `payment_type`='money'");

$roles = ['administrator', 'shop_manager'];
foreach( ['view', 'edit', 'delete', 'add', 'export', 'print', 'edit_status', 'email', 'sms'] as $key )
{
	foreach( ['check', 'check_return'] as $doc )
	{
		$capability_id = $key.'_'.$doc;
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
}
$roles = ['shop_crm'];
foreach( ['view', 'add', 'export', 'print', 'email', 'sms'] as $key )
{
	foreach( ['check', 'check_return'] as $doc )
	{
		$capability_id = $key.'_'.$doc;
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
}