<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';
USAM_Install::create_or_update_tables();

$capabilities = [
	'grid_crm' => ['administrator', 'shop_manager', 'personnel_officer', 'company_management', 'shop_crm', 'pickup_point_manager', 'employee'],	
	'calendar_crm' => ['administrator', 'shop_manager', 'personnel_officer', 'company_management', 'shop_crm', 'pickup_point_manager', 'employee'],
	'applications_section' => ['administrator', 'shop_manager'],
];				
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
			$wp_role->add_cap( $capability_id );
	}
}

$results = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_DELIVERY_SERVICE." WHERE price>0" );	
foreach( $results as $result )
	usam_update_delivery_service_metadata( $result->id, 'price', $result->price );
		
$wpdb->query( "ALTER TABLE `".USAM_TABLE_DELIVERY_SERVICE."` DROP COLUMN `price`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_BONUS_TRANSACTIONS."` DROP COLUMN `order_id`" );

