<?php
global $wpdb;
USAM_Install::create_or_update_tables( array(USAM_TABLE_CONTACT_META, USAM_TABLE_PAYMENT_GATEWAY_META, USAM_TABLE_TAX_PRODUCT_DOCUMENT)  );

$props = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_CONTACTS."" );	
foreach( $props as $prop )	
{	
	if ($prop->location_id) 
		usam_update_contact_metadata($prop->id, 'location', $prop->location_id ) ;
	if ($prop->address) 
		usam_update_contact_metadata($prop->id, 'address', $prop->address );
	if ($prop->address2) 
		usam_update_contact_metadata($prop->id, 'address2', $prop->address2 );
	if ($prop->postal_code) 
		usam_update_contact_metadata($prop->id, 'postcode', $prop->postal_code );
}

$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `location_id`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `address`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `address2`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `postal_code`" );

$gateways = usam_get_payment_gateways();

$transaction_results = get_option( 'usam_page_transaction_results' );		
if ( !empty($transaction_results['completed']) )
{
	foreach( $gateways as $gateway )	
	{
		usam_update_payment_gateway_metadata($gateway->id, 'message_completed', $transaction_results['completed'] );		
	}
}
?>