<?php

global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables( array(USAM_TABLE_TERM_META) );


$metas = $wpdb->get_results( "SELECT * FROM $wpdb->termmeta WHERE meta_key = 'usam_type_attribute' OR meta_key = 'usam_mandatory' OR meta_key = 'usam_display_type' OR meta_key = 'usam_product_sort_by' OR meta_key = 'usam_product_order' OR meta_key = 'usam_order_props_group' OR meta_key = 'usam_status_stock'  OR meta_key = 'usam_sale_area'  OR meta_key = 'usam_start_date_stock'  OR meta_key = 'usam_end_date_stock' OR meta_key = 'usam_company' OR meta_key = 'usam_filter' OR meta_key = 'usam_search' OR meta_key = 'usam_important' OR meta_key = 'usam_sort' OR meta_key = 'usam_template' OR meta_key = 'usam_color' OR meta_key = 'usam_do_not_show_in_features' OR meta_key = 'usam_do_not_show_in_features' OR meta_key = 'usam_do_not_show_in_features' OR meta_key = 'usam_do_not_show_in_features' OR meta_key = 'usam_sort_order'");
				
foreach ( $metas as $meta )
{		
	$meta_key = str_replace('usam_', '',  $meta->meta_key);
	if ( $meta_key == 'sort_order' )
		usam_add_term_metadata($meta->term_id, 'sort', $meta->meta_value );
	elseif ( $meta_key == 'sort' )
		$wpdb->query("UPDATE $wpdb->termmeta SET `meta_key`='sorting_products' WHERE `meta_key`='{$meta->meta_key}'");
	else
	{
		$wpdb->query("UPDATE $wpdb->termmeta SET `meta_key`='{$meta_key}' WHERE `meta_key`='{$meta->meta_key}'");		
	}		
}
$wpdb->query("DELETE FROM $wpdb->termmeta WHERE meta_key = 'sort_order' OR meta_key = 'usam_sort_order'");