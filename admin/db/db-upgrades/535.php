<?php
global $wp_roles, $wpdb, $wp_rewrite;

USAM_Install::create_or_update_tables([USAM_TABLE_DOCUMENT_LINKS, USAM_TABLE_DOCUMENT_PRODUCTS, USAM_TABLE_PRODUCTS_ORDER, USAM_TABLE_PRODUCTS_BASKET, USAM_TABLE_DOCUMENTS, USAM_TABLE_SHIPPED_DOCUMENTS]);

$usam_base_prefix = $wpdb->prefix.'usam_';
$events = $wpdb->get_results( "SELECT * FROM ".$usam_base_prefix."event_content" );
foreach ( $events as $event ) 
{
	if ( $event->meta_value )
		usam_update_event_metadata( $event->event_id, $event->meta_key, $event->meta_value );
}
$wpdb->query( "DROP TABLE `".$usam_base_prefix."event_content`" );

$docs = $wpdb->get_results( "SELECT id, date_external_document FROM ".USAM_TABLE_DOCUMENTS." WHERE date_external_document!=''" );
foreach ( $docs as $doc ) 
{
	usam_update_document_metadata( $doc->id, 'external_document_date', $doc->date_external_document);
}
$wpdb->query( "ALTER TABLE `".USAM_TABLE_DOCUMENTS."` DROP COLUMN `date_external_document`" );

$links = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_DOCUMENT_LINKS." WHERE document_type=''" );
foreach ( $links as $link ) 
{
	$document = usam_get_document( $link->document_id );
	$document_link = usam_get_document( $link->document_link_id );
	$wpdb->query("UPDATE ".USAM_TABLE_DOCUMENT_LINKS." SET document_type='".$document['type']."', document_link_type='".$document_link['type']."' WHERE `link_id`='$link->link_id'");
}

$documents = $wpdb->get_results( "SELECT order_id, id FROM ".USAM_TABLE_SHIPPED_DOCUMENTS." " );
foreach( $documents as $document )
{
	usam_add_document_link(['document_id' => $document->order_id, 'document_type' => 'order', 'document_link_id' => $document->id, 'document_link_type' => 'shipped']);
}
$documents = $wpdb->get_results( "SELECT document_id, id FROM ".USAM_TABLE_PAYMENT_HISTORY." " );
foreach( $documents as $document )
{
	usam_add_document_link(['document_id' => $document->document_id, 'document_type' => 'order', 'document_link_id' => $document->id, 'document_link_type' => 'payment']);
	usam_update_shipped_document($document->id, ['number' => usam_get_document_number( 'shipped' )]);
}
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PAYMENT_HISTORY."` DROP COLUMN `number`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PAYMENT_HISTORY."` CHANGE COLUMN `document_number` `number` varchar(250) NOT NULL DEFAULT ''" );
$wpdb->query("UPDATE ".USAM_TABLE_SHIPPED_DOCUMENT_META." SET meta_key='external_document' WHERE `meta_key`='document_number'");
$wpdb->query("UPDATE ".USAM_TABLE_SHIPPED_DOCUMENT_META." SET meta_key='external_document_date' WHERE `meta_key`='document_date'");


$wpdb->query("UPDATE `".USAM_TABLE_SHIPPED_DOCUMENTS."` SET number=id");
$wpdb->query("UPDATE `".USAM_TABLE_ORDERS."` SET number=id");

$number_counter = get_option('usam_document_number_counter', []);
$number_counter['order'] = $wpdb->get_var("SELECT id FROM ".USAM_TABLE_ORDERS." ORDER BY id DESC");
$number_counter['shipped'] = $wpdb->get_var("SELECT id FROM ".USAM_TABLE_SHIPPED_DOCUMENTS." ORDER BY id DESC");
update_option("usam_document_number_counter", $number_counter);

$statuses = [
	['internalname' => 'works', 'name' => __('Работаю', 'usam'), 'short_name' => __('Работают', 'usam'), 'type' => 'courier', 'color' => '#e6f0c0'],
	['internalname' => 'not_working', 'name' => __('Не работаю', 'usam'), 'short_name' => __('Не работают', 'usam'), 'type' => 'courier', 'color' => '#d9d9d9'],
	['internalname' => 'warehouse', 'name' => __('На склад', 'usam'), 'short_name' => __('На склад', 'usam'), 'type' => 'courier', 'color' => '#7db1c9'],	
	['internalname' => 'service', 'name' => __('В сервис', 'usam'), 'short_name' => __('В сервис', 'usam'), 'type' => 'courier', 'color' => '#ff9393'],	
	['internalname' => 'lunch', 'name' => __('На обед', 'usam'), 'short_name' => __('На обед', 'usam'), 'type' => 'courier', 'color' => '#faeac0'],	
];	
foreach ( $statuses as $key => $status )
{ 
	$status['sort'] = $key+1;
	$status['active'] = isset($status['active'])?$status['active']:1;
	usam_insert_object_status( $status );
}

$emails = $wpdb->get_results( "SELECT opened_at, id FROM ".USAM_TABLE_EMAIL." WHERE opened_at IS NOT NULL" );
foreach( $emails as $email )
{
	usam_update_email_metadata( $email->id, 'opened_at', $email->opened_at );		
}
$wpdb->query( "ALTER TABLE `".USAM_TABLE_EMAIL."` DROP COLUMN `opened_at`" );

