<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';
USAM_Install::create_or_update_tables([USAM_TABLE_CONTACTS, USAM_TABLE_SHOWCASES]);

$results = $wpdb->get_results( "SELECT * FROM ".usam_get_table_db('properties')." WHERE (`type`='company' OR `type`='contact') AND profile = 1");
foreach ( $results as $result ) 
{	
	usam_update_property_metadata($result->id, 'profile', 1 );	
}
$wpdb->query( "ALTER TABLE ".usam_get_table_db('properties')." DROP COLUMN `profile`" );