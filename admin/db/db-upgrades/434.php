<?php

global $wp_roles, $wpdb;

delete_option('usam_facebook_like');
delete_option('usam_vk_like');

USAM_Install::create_or_update_tables( array(USAM_TABLE_CAMPAIGNS) );
		
$capabilities = array( 
	'view_buyers_report' => array( 'administrator', 'shop_manager' ),
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