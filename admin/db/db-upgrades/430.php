<?php

global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables( array(USAM_TABLE_NOTIFICATIONS, USAM_TABLE_NOTIFICATION_RELATIONSHIPS) );

$result = $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_usam_price_date'");


$results = usam_get_events( array('type' => 'notification') );	
$ids = array();
foreach ( $results as $result ) 
{		
	$ids[] = $result->id;	
}	
if ( !empty($ids) )
{
	$wpdb->query("DELETE FROM ".USAM_TABLE_EVENTS." WHERE id IN (".implode(',',$ids).")");	
}
?>