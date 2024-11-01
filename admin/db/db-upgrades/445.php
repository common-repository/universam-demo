<?php
global $wp_roles, $wpdb;

delete_option('usam_comments_which_products');

require_once( USAM_FILE_PATH . '/admin/db/db-install/system_default_data.php' );	
new USAM_Load_System_Default_Data( array( 'units_measure' ) );

USAM_Install::create_or_update_tables( array(USAM_TABLE_DOCUMENT_CONTENT, USAM_TABLE_VISIT_META, USAM_TABLE_COMPANY_ACC_NUMBER, USAM_TABLE_PRODUCT_META, usam_get_table_db('product_components')) );

$post_metas = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_DOCUMENT_META." WHERE meta_key = 'description' OR meta_key = 'conditions' OR meta_key = 'document_content'" );
foreach ( $post_metas as $meta )
{			
	usam_update_document_content($meta->document_id, $meta->meta_key, $meta->meta_value );	
}
$wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_META." WHERE meta_key = 'description' OR meta_key = 'conditions' OR meta_key = 'document_content'");


$post_metas = $wpdb->get_results( "SELECT * FROM ".$wpdb->postmeta." WHERE meta_key = '_usam_product_metadata'" );
foreach ( $post_metas as $meta )
{			
	$product_meta = maybe_unserialize($meta->meta_value);
	if ( !empty($product_meta['license_agreement']) )
		usam_update_product_meta($meta->post_id, 'license_agreement', $product_meta['license_agreement'] );
	if ( !empty($product_meta['unit_measure']) )
		usam_update_product_meta($meta->post_id, 'unit_measure', $product_meta['unit_measure'] );
	if ( !empty($product_meta['unit']) )
		usam_update_product_meta($meta->post_id, 'unit', $product_meta['unit'] );	
	if ( !empty($product_meta['height']) )
		usam_update_product_meta($meta->post_id, 'height', $product_meta['height'] );		
	if ( !empty($product_meta['width']) )
		usam_update_product_meta($meta->post_id, 'width', $product_meta['width'] );
	if ( !empty($product_meta['length']) )
		usam_update_product_meta($meta->post_id, 'length', $product_meta['length'] );
}

$wpdb->query( "INSERT `".USAM_TABLE_PRODUCT_META."` (product_id,meta_key,meta_value) SELECT post_id,meta_key,meta_value FROM ".$wpdb->postmeta." WHERE meta_key LIKE '_usam_webspy_link' OR meta_key LIKE '_usam_under_order' OR meta_key LIKE '_usam_weight' OR meta_key LIKE '_usam_volume' OR meta_key LIKE '_usam_virtual' OR meta_key LIKE '_usam_code' OR meta_key LIKE '_usam_sku' OR meta_key LIKE '_usam_barcode'" );
$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_META." SET meta_key='webspy_link' WHERE `meta_key`='_usam_webspy_link'");
$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_META." SET meta_key='under_order' WHERE `meta_key`='_usam_under_order'");
$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_META." SET meta_key='contractor' WHERE `meta_key`='_usam_contractor'");
$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_META." SET meta_key='weight' WHERE `meta_key`='_usam_weight'");
$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_META." SET meta_key='volume' WHERE `meta_key`='_usam_volume'");
$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_META." SET meta_key='virtual' WHERE `meta_key`='_usam_virtual'");
$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_META." SET meta_key='code' WHERE `meta_key`='_usam_code'");
$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_META." SET meta_key='sku' WHERE `meta_key`='_usam_sku'");
$wpdb->query("UPDATE ".USAM_TABLE_PRODUCT_META." SET meta_key='barcode' WHERE `meta_key`='_usam_barcode'");

$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_weight'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_volume'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_virtual'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_code'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_sku'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_barcode'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_webspy_link'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_under_order'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_contractor'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_increase_sales_time'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_date_externalproduct'");


$post_metas = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_CONTACT_META." WHERE meta_key = 'checkout_details'" );
foreach ( $post_metas as $meta )
{			
	$checkout_details = maybe_unserialize( $meta->meta_value );
	if ( !empty($checkout_details) )
	{
		foreach ( $checkout_details as $key => $value )
			usam_update_customer_checkout($key, $value, $meta->contact_id);	
	}
}
$wpdb->query("DELETE FROM ".USAM_TABLE_CONTACT_META." WHERE meta_key = 'checkout_details'");

$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_increase_sales_time'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_price%'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_old_price%'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_underprice%'");

$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_usam_total_balance'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_stock%'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_storage%'");
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_reserve%'");


USAM_Install::create_or_update_tables( array(USAM_TABLE_CONTACT_META, USAM_TABLE_COMPANY_META, USAM_TABLE_ORDER_META) );

//$wpdb->query("DELETE FROM ".USAM_TABLE_PAGE_VIEWED." WHERE url LIKE '/wp-includes/js%' OR url LIKE '/wp-json/%' OR url LIKE '/your-account/%' OR url LIKE '%/login%' OR url LIKE '/search%' OR url LIKE '/transaction-results%' OR url LIKE '/wp-json/wp%'");	