<?php

global $wp_roles, $wpdb;

delete_option('usam_share_this');

USAM_Install::create_or_update_tables( array(USAM_TABLE_SHIPPED_DOCUMENTS, USAM_TABLE_SHIPPED_DOCUMENT_META, usam_get_table_db('property_group_meta'), USAM_TABLE_PARSING_SITES, USAM_TABLE_BANNERS, USAM_TABLE_WEBFORMS, USAM_TABLE_SLIDER, USAM_TABLE_PRODUCTS_BASKET, USAM_TABLE_PRODUCTS_ORDER, USAM_TABLE_DOCUMENT_PRODUCTS, USAM_TABLE_SHIPPED_PRODUCTS, USAM_TABLE_OBJECT_STATUSES, USAM_TABLE_FILES, USAM_TABLE_USER_POSTS, USAM_TABLE_USERS_BASKET) );

$wpdb->query("DELETE FROM `".$wpdb->postmeta."` WHERE meta_key LIKE '_usam_options_price_%'"); 

update_option( 'usam_ancestors_locations', array() );

$capabilities = array( 
	'view_delivery' => array( 'administrator', 'shop_manager', 'courier' ),	
	'view_delivery_documents' => array( 'administrator', 'shop_manager', 'courier' ),		
	'view_couriers' => array( 'administrator', 'shop_manager' ),
	'view_price_analysis' => array( 'administrator', 'shop_manager' ),	
	'view_order_products_report' => array( 'administrator', 'shop_manager' ),
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
require_once( USAM_FILE_PATH . '/admin/db/db-install/system_default_data.php' );	
new USAM_Load_System_Default_Data( array( 'webform_properties' ) );
$wpdb->insert( usam_get_table_db('property_groups'), array( 'name' => __('Основное','usam'), 'type' => 'webform', 'code' => 'main' ) );

$wpdb->query("UPDATE ".USAM_TABLE_SHIPPED_DOCUMENTS." SET `status`='canceled' WHERE `status`='сanceled'");
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `date_allow_delivery`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `planned_date`" );

$files = $wpdb->get_col( "SELECT id FROM " . USAM_TABLE_FILES." WHERE code=''"  );
foreach( $files as $id)
{
	$wpdb->query("UPDATE `".USAM_TABLE_FILES."` SET code='".sha1(uniqid(mt_rand(), true))."' WHERE id='".$id."'");
}	
?>