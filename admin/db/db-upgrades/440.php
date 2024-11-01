<?php

global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables( array(USAM_TABLE_ORDERS, usam_get_table_db('properties'), USAM_TABLE_PRODUCT_MARKING_CODES) );

$products = $wpdb->get_results( "SELECT COUNT(*) AS count, order_id FROM ".USAM_TABLE_PRODUCTS_ORDER." GROUP BY order_id" );
foreach( $products as $product )
{			
	$wpdb->query("UPDATE ".USAM_TABLE_ORDERS." SET number_products=".$product->count." WHERE id='$product->order_id'");
}

global $wp_roles;
$roles =  array( 'administrator', 'shop_manager' );
$capabilities = array( 'view_marking_codes' => 1,  ); 	
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
