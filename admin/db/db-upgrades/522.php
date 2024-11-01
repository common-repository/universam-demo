<?php
global $wp_roles, $wpdb, $wp_rewrite;
	
				
delete_option('usam_search_option');	
delete_option('usam_add_users_to_mailing_list');	

USAM_Install::create_or_update_tables([USAM_TABLE_SHIPPED_PRODUCTS]);

global $wp_roles, $wpdb;
$products = $wpdb->get_col( "SELECT product_id FROM ".USAM_TABLE_SHIPPED_PRODUCTS." GROUP BY product_id" );
foreach( $products as $product_id )
{
	$name = get_the_title( $product_id );		
	if ( $name )
		$wpdb->update( USAM_TABLE_SHIPPED_PRODUCTS, ['name' => $name], ['product_id' => $product_id] );
}

$documents = $wpdb->get_results( "SELECT doc_number, doc_data, id, readiness_date, date_delivery FROM ".USAM_TABLE_SHIPPED_DOCUMENTS." " );
foreach( $documents as $document )
{
	if ( $document->doc_number )
		usam_update_shipped_document_metadata( $document->id, 'external_document', $document->doc_number );
	if ( $document->doc_data )
		usam_update_shipped_document_metadata( $document->id, 'external_document_date', $document->doc_data );
	if ( $document->readiness_date )
		usam_update_shipped_document_metadata( $document->id, 'readiness_date', $document->readiness_date );	
	if ( $document->date_delivery )
		usam_update_shipped_document_metadata( $document->id, 'date_delivery', $document->date_delivery );	
}
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `doc_number`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `doc_data`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `readiness_date`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `date_delivery`" );