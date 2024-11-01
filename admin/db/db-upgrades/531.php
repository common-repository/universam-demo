<?php
global $wp_roles, $wpdb, $wp_rewrite;

USAM_Install::create_or_update_tables([USAM_TABLE_LEADS, USAM_TABLE_OBJECT_STATUS_META]);

$types = usam_get_details_documents();
$types = array_keys( $types );
$events_types = usam_get_events_types();
$events_types = array_keys( $events_types );
$types = array_merge( $events_types, $types );	

require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
$object_statuses = usam_get_object_statuses(['type' => $types, 'cache_results' => true]);
foreach( $object_statuses as $status )
	usam_update_object_count_status( $status->internalname, $status->type );
	
update_option('usam_shop_requisites_shortcode', []);
