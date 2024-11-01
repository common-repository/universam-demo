<?php
global $wp_roles, $wpdb;

$capabilities = array( 
	'list_crm' => array( 'administrator', 'shop_manager', 'shop_crm', 'marketer', 'company_management', 'pickup_point_manager', 'employee', 'personnel_officer' ),	
	'map_crm' => array( 'administrator', 'shop_manager', 'shop_crm', 'marketer', 'company_management', 'pickup_point_manager', 'employee', 'personnel_officer' ),	
	'report_crm' => array( 'administrator', 'shop_manager', 'shop_crm', 'marketer', 'company_management', 'pickup_point_manager', 'employee', 'personnel_officer' ),	
	'setting_crm' => array( 'administrator', 'shop_manager' ),	
	'edit_themes' => array( 'shop_manager' ),	
);	
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
			$wp_role->add_cap( $capability_id );
	}
}
//Контакты, компании и сотрудники
$roles = ['administrator', 'shop_manager', 'shop_crm', 'marketer', 'personnel_officer'];
foreach( ['contact', 'employee', 'company'] as $type )
{	
	foreach( ['edit', 'delete', 'add', 'export', 'import'] as $key )
	{
		$capability_id = $key.'_'.$type;
		foreach ( $wp_roles->role_objects as $wp_role ) 
		{				
			if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
				$wp_role->add_cap( $capability_id );
		}
	}
}
$roles = ['company_management', 'pickup_point_manager', 'employee'];
foreach( ['contact', 'employee', 'company'] as $type )
{	
	foreach( ['edit', 'add'] as $key )
	{
		$capability_id = $key.'_'.$type;
		foreach ( $wp_roles->role_objects as $wp_role ) 
		{				
			if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
				$wp_role->add_cap( $capability_id );
		}
	}
}
//Дела, задания, события
$roles = ['administrator', 'shop_manager', 'company_management', 'personnel_officer'];
foreach( usam_get_events_types( ) as $type => $event )
{	
	foreach(['view', 'edit', 'delete', 'add', 'edit_status'] as $key )
	{
		$capability_id = $key.'_'.$type;
		foreach ( $wp_roles->role_objects as $wp_role ) 
		{				
			if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
				$wp_role->add_cap( $capability_id );
		}
	}
}
$roles = ['pickup_point_manager', 'shop_crm', 'employee', 'marketer'];
foreach( usam_get_events_types( ) as $type => $event )
{	
	foreach(['view', 'edit', 'add', 'edit_status'] as $key )
	{
		$capability_id = $key.'_'.$type;
		foreach ( $wp_roles->role_objects as $wp_role ) 
		{				
			if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
				$wp_role->add_cap( $capability_id );
		}
	}
}
$return_email = get_option( 'usam_return_email' );
if ( $return_email )
{
	$mailbox = usam_get_mailbox( $return_email );
	update_option( 'usam_return_email', $mailbox['email'] );
}

