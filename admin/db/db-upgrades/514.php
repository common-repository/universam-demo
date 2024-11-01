<?php
global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables([USAM_TABLE_TERM_META]);

$capabilities = array( 
	'view_parser' => array( 'administrator', 'shop_manager'),
);	
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
			$wp_role->add_cap( $capability_id );
	}
}


$metas = $wpdb->get_results( "SELECT * FROM $wpdb->termmeta WHERE meta_key = 'public' OR meta_key = 'product_order' OR meta_key = 'product_sort_by' OR meta_key = 'start_date_stock' OR meta_key = 'end_date_stock' OR meta_key = 'status_stock' OR meta_key = 'sale_area'  OR meta_key = 'company' OR meta_key = 'color' OR meta_key = 'template' OR meta_key = 'external_code' OR meta_key = 'display_type' OR meta_key = 'link' OR meta_key = 'do_not_show_in_features' OR meta_key = 'mandatory' OR meta_key = 'important' OR meta_key = 'field_type' OR meta_key = 'filter' OR meta_key = 'search'");
				
foreach ( $metas as $meta )
{		
	usam_add_term_metadata($meta->term_id, $meta->meta_key, $meta->meta_value );	
}
$wpdb->query("DELETE FROM $wpdb->termmeta WHERE meta_key = 'public' OR meta_key = 'product_order' OR meta_key = 'product_sort_by' OR meta_key = 'start_date_stock' OR meta_key = 'end_date_stock' OR meta_key = 'status_stock' OR meta_key = 'sale_area'  OR meta_key = 'company' OR meta_key = 'color' OR meta_key = 'template' OR meta_key = 'external_code' OR meta_key = 'display_type' OR meta_key = 'link' OR meta_key = 'do_not_show_in_features' OR meta_key = 'mandatory' OR meta_key = 'important' OR meta_key = 'field_type' OR meta_key = 'filter' OR meta_key = 'search'");