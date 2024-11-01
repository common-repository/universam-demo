<?php
global $wp_roles, $wpdb, $wp_rewrite;
		
$capabilities = array( 
	'print_product' => array( 'administrator', 'shop_manager', 'pickup_point_manager' ),	
);	
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
			$wp_role->add_cap( $capability_id );
	}
}

$result = $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key='".USAM_META_PREFIX."reviews_enable'");

$wpdb->query( "ALTER TABLE `".USAM_TABLE_WEBFORMS."` DROP COLUMN `result_message`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_WEBFORMS."` DROP COLUMN `button_name`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_WEBFORMS."` DROP COLUMN `description`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_WEBFORMS."` DROP COLUMN `modal_button_name`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_WEBFORMS."` DROP COLUMN `button_color`" );


