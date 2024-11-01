<?php
global $wp_roles, $wpdb, $wp_rewrite;

USAM_Install::create_or_update_tables([usam_get_table_db('properties'), USAM_TABLE_SYSTEM_REPORTS, USAM_TABLE_GROUPS]);

$contacts = $wpdb->get_results( "SELECT contact_id, user_list, COUNT(*) AS counter FROM ".USAM_TABLE_USER_POSTS." GROUP BY contact_id" );	
foreach( $contacts as $contact )
{
	usam_update_contact_metadata($contact->contact_id, $contact->user_list, $contact->counter);	
}

$wpdb->query( "RENAME TABLE {$wpdb->prefix}usam_user_products TO ".USAM_TABLE_USER_POSTS );

$capabilities = array( 
	'manage_product_attribute' => ['administrator', 'shop_manager'],	
	'edit_product_attribute' => ['administrator', 'shop_manager'],	
	'delete_product_attribute' => ['administrator', 'shop_manager'],	
	'manage_product_category' => ['administrator', 'shop_manager'],	
	'edit_product_category' => ['administrator', 'shop_manager'],	
	'delete_product_category' => ['administrator', 'shop_manager'],		
	'manage_product_selection' => ['administrator', 'shop_manager'],	
	'edit_product_selection' => ['administrator', 'shop_manager'],	
	'delete_product_selection' => ['administrator', 'shop_manager'],		
	'manage_product_catalog' => ['administrator', 'shop_manager'],	
	'edit_product_catalog' => ['administrator', 'shop_manager'],	
	'delete_product_catalog' => ['administrator', 'shop_manager'],	
);
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
			$wp_role->add_cap( $capability_id );
	}
}

$wpdb->query("UPDATE ".USAM_TABLE_TERM_META." SET meta_key='status', meta_value='publish' WHERE `meta_key`='hide_visitors' AND `meta_value`='0'");	
$wpdb->query("UPDATE ".USAM_TABLE_TERM_META." SET meta_key='status', meta_value='hidden' WHERE `meta_key`='hide_visitors' AND `meta_value`='1'");


$wpdb->query("UPDATE ".USAM_TABLE_TERM_META." SET meta_key='status', meta_value='hidden' WHERE `meta_key`='status_stock' AND `meta_value`='0'");	
$wpdb->query("UPDATE ".USAM_TABLE_TERM_META." SET meta_key='status', meta_value='publish' WHERE `meta_key`='status_stock' AND `meta_value`='1'");

$terms = get_terms(['taxonomy' => ["category", "usam-brands", 'usam-variation', 'usam-product_attributes', 'usam-catalog', 'usam-selection'], 'hide_empty' => 0]);
foreach ( $terms as $term ) 
{
	usam_add_term_metadata( $term->term_id, 'status', 'publish', true );	
}


$capabilities = array( 
	'view_departments' => ['administrator', 'shop_manager', 'employee', 'personnel_officer'],
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

$roles = ['administrator', 'personnel_officer'];
foreach( ['contact', 'employee', 'company', 'department'] as $type )
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





