<?php

global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables( array(USAM_TABLE_FILE_META, USAM_TABLE_FOLDERS, USAM_TABLE_PAYMENT_GATEWAY, usam_get_table_db('property_groups'), USAM_TABLE_VISIT_META, usam_get_table_db('properties'), USAM_TABLE_COMPANY) );


$users = get_users( array( 'fields' => 'ID', 'orderby' => 'nicename', 'fields' => array( 'ID','user_nicename'), 'meta_query' => array('relation' => 'OR', array('key' => 'usam_my_companies', 'compare' => 'EXISTS' ) ) ) );
foreach ( $users as $user_id ) 
{
	$companies = get_user_meta( $user_id, 'usam_my_companies', true );	
	if ( empty($companies) )
		break;
			
	foreach ( $companies as $id ) 
		usam_update_company( $id, array('user_id' => $user_id) );
		
	delete_user_meta( $user_id, 'usam_my_companies' );	
}


$results = $wpdb->get_results( "SELECT id, revenue FROM ".USAM_TABLE_COMPANY." WHERE revenue!=0" );	
foreach ( $results as $result ) 
{						
	usam_update_company_metadata( $result->id, 'revenue', $result->revenue);	
}	
$wpdb->query( "ALTER TABLE `".USAM_TABLE_COMPANY."` DROP COLUMN `revenue`" );

$results = $wpdb->get_results( "SELECT id FROM ".USAM_TABLE_SHIPPED_DOCUMENTS." WHERE totalprice=0" );	
$ids = array();	
foreach ( $results as $result ) 
{				
	$ids[] = $result->id; 					
}	
usam_update_cache( $ids, array( USAM_TABLE_SHIPPED_DOCUMENT_META => 'document_meta'), 'document_id' );
foreach ( $results as $result )
{
	$products = usam_get_products_shipped_document( $result->id );
	$totalprice = 0;
	foreach ( $products as $product )
	{
		$totalprice += $product->price*$product->quantity;
	}	
	if ( $totalprice )
		$wpdb->query("UPDATE `".USAM_TABLE_SHIPPED_DOCUMENTS."` SET totalprice=$totalprice WHERE id=$result->id");
}

$capabilities = array( 
	'view_inquiries' => array( 'administrator', 'shop_manager' ),
	'view_reputation' => array( 'administrator', 'shop_manager' ),
	'view_all_files' => array( 'administrator', 'shop_manager' ),	
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


?>