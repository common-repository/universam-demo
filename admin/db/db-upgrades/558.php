<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';
USAM_Install::create_or_update_tables([USAM_TABLE_CONTACTINGS, USAM_TABLE_CONTACTING_META, USAM_TABLE_STORAGES, USAM_TABLE_CUSTOM_PRODUCT_TABS]);

$wpdb->query("UPDATE ".USAM_TABLE_CUSTOMER_REVIEW_META." SET meta_key='webform_full_name' WHERE `meta_key`='webform_name'");
$wpdb->query("UPDATE ".USAM_TABLE_CUSTOM_PRODUCT_TABS." SET title=name WHERE `title`=''");

require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );
$results = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_EVENTS." WHERE `type`='contacting' ORDER BY date_insert ASC");
$ids = [];
usam_update_object_count_status( false );
foreach ( $results as $result ) 
{	
	$insert = (array)$result;
	$insert['contact_id'] = 0;
	$links = usam_get_ribbon_links( $result->id, $result->type );	
	foreach ( $links as $link ) 
	{
		if( $link->object_type == 'contact' )
			$insert['contact_id'] = $link->object_id;
		elseif( $link->object_type == 'page' )
			$insert['post_id'] = $link->object_id;
		elseif( $link->object_type == 'product' )
			$insert['post_id'] = $link->object_id;
		elseif( $link->object_type == 'post' )
			$insert['post_id'] = $link->object_id;			
	}
	$id = usam_insert_contacting( $insert );
	$ids[] = $result->id;	
	$metadata = usam_get_event_metadata( $result->id );
	foreach ( $metadata as $meta ) 	
		usam_add_contacting_metadata($id, $meta->meta_key, maybe_unserialize($meta->meta_value));		
}
usam_update_object_count_status( true );	
if ( $ids )
	usam_delete_events(['include' => $ids]);