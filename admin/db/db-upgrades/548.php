<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';
delete_option( 'usam_product_order' );

USAM_Install::create_or_update_tables([USAM_TABLE_CHAT_USERS]); 

$wpdb->query( "ALTER TABLE `".USAM_TABLE_COMPANY_ACC_NUMBER."` DROP COLUMN `note`" );


$capabilities = array( 
	'send_sms' => array( 'administrator', 'shop_manager' ),
	'send_email' => array( 'administrator', 'shop_manager' ),	
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