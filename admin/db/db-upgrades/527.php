<?php
global $wp_roles, $wpdb, $wp_rewrite;
		

delete_option( 'usam_update_price_settings_excel');
delete_option( 'usam_price_list_setting');
delete_option( 'usam_shipwire');
delete_option('usam_view_category_featured_products');
delete_option( 'usam_show_availability_stock');


$wpdb->query("UPDATE ".usam_get_table_db('properties')." SET mask=''");
$wpdb->query("UPDATE ".usam_get_table_db('properties')." SET mask='#(###)###-##-##' WHERE `code`='billingmobilephone'");

