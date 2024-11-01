<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';


global $wpdb;
$visits = $wpdb->get_results( "SELECT id, referer, device, contact_id FROM ".USAM_TABLE_VISITS." LIMIT 15000" );	
$ids = [];
foreach($visits as $visit)
{	
	if( $visit->contact_id )
		$ids[$visit->contact_id] = isset($ids[$visit->contact_id])?$ids[$visit->contact_id]+1:1;
	if( $visit->referer )
		usam_add_visit_metadata( $visit->id, 'referer', rtrim($visit->referer, '/') );
	usam_add_visit_metadata( $visit->id, 'device', $visit->device?$visit->device:'PC' );
	wp_cache_delete( $visit->id, 'usam_visit_meta' );
}
foreach($ids as $contact_id => $count)
	usam_add_contact_metadata( $contact_id, 'visit', $count );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_VISITS."` DROP COLUMN `referer`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_VISITS."` DROP COLUMN `device`" );