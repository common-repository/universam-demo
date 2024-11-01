<?php

global $wpdb;

USAM_Install::create_or_update_tables( array(USAM_TABLE_STORAGE_META, USAM_TABLE_USER_POSTS, USAM_TABLE_CHAT_DIALOGS, USAM_TABLE_DATA_ORDER_PRODUCTS, USAM_TABLE_STOCK_MANAGEMENT_DATA, USAM_TABLE_SHIPPED_PRODUCTS, USAM_TABLE_PRODUCTS_BASKET, USAM_TABLE_DOCUMENT_PRODUCTS) );
USAM_Install::create_or_update_tables( array(USAM_TABLE_PARSING_SITES, USAM_TABLE_USERS_BASKET, USAM_TABLE_VISITS, USAM_TABLE_DOCUMENTS) );

require_once(USAM_FILE_PATH.'/includes/parser/parsing_sites_query.class.php');
$site_ids = usam_get_parsing_sites( array( 'fields' => 'id' ) );	
if ( !empty($site_ids) )
{		
	$products = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '".USAM_META_PREFIX."product_metadata'" );	
	foreach($products as $product)
	{		
		$product_meta = maybe_unserialize($product->meta_value);
		if ( !empty($product_meta['webspy_link']) )
			usam_update_product_meta( $product->post_id, 'webspy_link', $product_meta['webspy_link'] );	
	}
}
?>