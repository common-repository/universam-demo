<?php
global $wp_roles, $wpdb;

$wpdb->query( "CREATE TABLE backup SELECT * FROM ".USAM_TABLE_DOCUMENT_LINKS."" );  
$wpdb->query( "DROP TABLE `".USAM_TABLE_DOCUMENT_LINKS."`" );

USAM_Install::create_or_update_tables( array(USAM_TABLE_DOCUMENT_LINKS) );

$wpdb->query( "INSERT `".USAM_TABLE_DOCUMENT_LINKS."` (document_id,document_link_id) SELECT `document_id1`,`document_id2` FROM backup" );
$wpdb->query( "DROP TABLE `backup`" );


$capabilities = array( 
	'view_notes' => array( 'administrator', 'shop_manager', 'shop_crm', 'marketer', 'company_management', 'pickup_point_manager', 'employee', 'personnel_officer' ),	
	'view_reconciliation_act' => array( 'administrator', 'shop_manager', 'shop_crm', 'marketer', 'company_management', 'pickup_point_manager', 'employee', 'personnel_officer' ),	
);	
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
			$wp_role->add_cap( $capability_id );
	}
}

$roles = ['administrator', 'shop_manager', 'shop_crm'];
foreach( ['view', 'edit', 'delete', 'add', 'export', 'print', 'edit_status', 'email', 'sms'] as $key )
{
	$capability_id = $key.'_reconciliation_act';
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
$delivery_services = usam_get_delivery_services(['handler' => 'cdek']);
foreach ( $delivery_services as $service ) 
{
	$tariff_codes = usam_get_delivery_service_metadata($service->id, 'tariff_codes');
	if ( !empty($tariff_codes) )
		usam_save_array_metadata($service->id, 'delivery_service', 'tariff_codes', $tariff_codes);	
}