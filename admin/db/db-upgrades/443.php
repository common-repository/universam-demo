<?php

global $wp_roles, $wpdb;

global $wp_roles;
$roles =  array( 'administrator', 'shop_manager');
$capabilities = array( 'edit_product' => 1, 'read_product' => 1, 'delete_product' => 1, 'edit_products' => 1, 'edit_others_products' => 1, 'publish_products' => 1, 'read_private_products' => 1, 'delete_products' => 1, 'delete_private_products' => 1, 'delete_published_products' => 1, 'delete_others_products' => 1, 'edit_private_products' => 1, 'view_shipped' => 1, 'delete_shipped' => 1, 'view_payment' => 1, 'delete_payment' => 1, 'edit_published_products' => 1); 		
foreach ( $roles as $role ) 
{	
	if ( isset($wp_roles->role_objects[$role]) )
	{
		foreach ( $capabilities as $capability_id => $active ) 
		{						
			if ( $active )
			{				
				if ( !$wp_roles->role_objects[$role]->has_cap( $capability_id ) )
				{										
					$wp_roles->role_objects[$role]->add_cap( $capability_id );						
				}												
			}	
			else					
				$wp_roles->role_objects[$role]->remove_cap( $capability_id );			
		}
	}	
}
global $wp_roles;
$roles =  array( 'marketer' );
$capabilities = array( 'edit_product' => 1, 'read_product' => 1, 'edit_products' => 1, 'edit_others_products' => 1, 'edit_published_products' => 1); 	
foreach ( $roles as $role ) 
{	
	if ( isset($wp_roles->role_objects[$role]) )
	{
		foreach ( $capabilities as $capability_id => $active ) 
		{						
			if ( $active )
			{				
				if ( !$wp_roles->role_objects[$role]->has_cap( $capability_id ) )
				{										
					$wp_roles->role_objects[$role]->add_cap( $capability_id );						
				}												
			}	
			else					
				$wp_roles->role_objects[$role]->remove_cap( $capability_id );			
		}
	}	
}

global $wp_roles;
$roles =  array( 'shop_seo', 'shop_crm', 'company_management' );
$capabilities = array( 'view_shipped' => 1, 'view_payment' => 1, 'edit_product' => 1, 'read_product' => 1, 'edit_products' => 1, 'edit_others_products' => 1, 'read_private_products' => 1, 'edit_private_products' => 1, 'edit_published_products' => 1); 	
foreach ( $roles as $role ) 
{	
	if ( isset($wp_roles->role_objects[$role]) )
	{
		foreach ( $capabilities as $capability_id => $active ) 
		{						
			if ( $active )
			{				
				if ( !$wp_roles->role_objects[$role]->has_cap( $capability_id ) )
				{										
					$wp_roles->role_objects[$role]->add_cap( $capability_id );						
				}												
			}	
			else					
				$wp_roles->role_objects[$role]->remove_cap( $capability_id );			
		}
	}	
}