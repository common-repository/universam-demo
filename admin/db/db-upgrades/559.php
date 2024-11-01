<?php
global $wp_roles, $wpdb, $wp_rewrite;


$capabilities = [
	'view_site_company' => ['administrator', 'shop_manager'],	
	'import_order' => ['administrator', 'shop_manager'],	
	'view_showcases' => ['administrator', 'shop_manager'],	
	'view_communication_data' => ['administrator', 'shop_manager', 'shop_crm', 'personnel_officer', 'employee'],					
	'help_section' => array( 'administrator', 'shop_manager' ),		
	'services_section' => array( 'administrator', 'shop_manager' ),	
];				
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
			$wp_role->add_cap( $capability_id );
	}
}


$terms = get_terms(['hide_empty' => 0, 'status' => 'all', 'meta_query' => [['key' => 'thumbnail', 'compare' => 'EXISTS'], ['key' => 'thumbnail', 'value' => 0, 'compare' => '!=']]]);
foreach ( $terms as $term ) 
{	
	$images = get_term_meta($term->term_id, 'images', true);
	if( !$images )
	{
		$thumbnail = get_term_meta( $term->term_id, 'thumbnail', true );	
		update_term_meta($term->term_id, 'images', [$thumbnail] );
	}
}
