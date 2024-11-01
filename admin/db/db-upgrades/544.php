<?php
global $wp_roles, $wpdb, $wp_rewrite;

delete_option( 'usam_product_order' );

USAM_Install::create_or_update_tables( array(USAM_TABLE_SHIPPED_DOCUMENTS,USAM_TABLE_PAYMENT_HISTORY, USAM_TABLE_MAILBOX_META, USAM_TABLE_LOADED_MESSAGES_LOG)  );

$roles = ['administrator', 'shop_manager', 'shop_crm', 'marketer', 'company_management', 'employee', 'personnel_officer'];
foreach( usam_get_details_documents() as $document_type => $document )
{		
	$capability_id = 'view_'.$document_type.'_lists';
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) )
		{								
			if ( !$wp_role->has_cap( $capability_id ) )
				$wp_role->add_cap( $capability_id );	
		}
	}
}			
$roles = ['administrator', 'shop_manager', 'shop_crm'];
foreach( usam_get_details_documents( ) as $document_type => $document )
{	
	foreach( ['department_view', 'company_view', 'any_view', 'department_edit', 'company_edit', 'any_edit', 'delete_any'] as $key )
	{
		$capability_id = $key.'_'.$document_type;
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
$results = $wpdb->get_results( "SELECT id, tax_name, tax_is_in_price, tax_rate FROM ".USAM_TABLE_SHIPPED_DOCUMENTS." WHERE tax_name!=''" );	
foreach ( $results as $k => $result ) 
{
	usam_add_shipped_document_metadata( $result->id, 'tax_name', $result->tax_name );
	usam_add_shipped_document_metadata( $result->id, 'tax_is_in_price', $result->tax_is_in_price );
	usam_add_shipped_document_metadata( $result->id, 'tax_rate', $result->tax_rate );	
	unset($results[$k]);
}
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `tax_name`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `tax_is_in_price`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `tax_rate`" );

$letters = $wpdb->get_results( "SELECT id, server_message_id, mailbox_id FROM ".USAM_TABLE_EMAIL." WHERE server_message_id!=0" );
if ( $letters )
{
	foreach ( $letters as $k => $letter )
	{
		$wpdb->insert( USAM_TABLE_LOADED_MESSAGES_LOG, ['letter_id' => $letter->server_message_id, 'mailbox_id' => $letter->mailbox_id]);		
		usam_update_email_metadata( $letter->id, 'server_message_id', $letter->server_message_id );
		unset($letters[$k]);
	}
}
$wpdb->query( "ALTER TABLE `".USAM_TABLE_MAILBOX."` DROP COLUMN `letter_id`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_EMAIL."` DROP COLUMN `server_message_id`" );