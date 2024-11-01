<?php
global $wp_roles, $wpdb;

require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
$documents = usam_get_shipping_documents(['fields' => ['id','export']]);
foreach( $documents as $document )
{		
	if ( $document->export )
		usam_update_shipped_document_metadata($document->id, 'exchange', 1);	
}
$documents = usam_get_orders(['fields' => ['id','notes']]);
foreach( $documents as $document )
{		
	if ( $document->notes )
		usam_update_order_metadata($document->id, 'note', 1);	
}

$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `export`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `notes`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_DOCUMENTS."` DROP COLUMN `notes`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_ORDERS."` DROP COLUMN `notes`" );




