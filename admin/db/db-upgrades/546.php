<?php
global $wp_roles, $wpdb, $wp_rewrite;

delete_option( 'usam_product_order' );

USAM_Install::create_or_update_tables([USAM_TABLE_SMS, USAM_TABLE_EMAIL, USAM_TABLE_CAMPAIGN_TRANSITIONS, USAM_TABLE_TRIGGERS,USAM_TABLE_TRIGGER_META, USAM_TABLE_RIBBON, USAM_TABLE_RIBBON_LINKS]);
		
require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
$results = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_SMS." WHERE object_id != '0'" );	
foreach ( $results as $k => $result ) 
{
	usam_insert_ribbon(['event_id' => $result->id, 'event_type' => 'sms', 'date_insert' => $result->date_insert], ['object_id' => $result->object_id, 'object_type' => $result->object_type]);
}
require_once( USAM_FILE_PATH.'/includes/crm/comment.class.php' );
$results = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_COMMENTS." WHERE status='0'" );
foreach ( $results as $k => $result ) 
{
	usam_insert_ribbon(['event_id' => $result->id, 'event_type' => 'comment', 'date_insert' => $result->date_insert], ['object_id' => $result->object_id, 'object_type' => $result->object_type]);
}

require_once(USAM_FILE_PATH.'/includes/mailings/email.class.php');
$results = $wpdb->get_results( "SELECT ".USAM_TABLE_EMAIL.".date_insert, ".USAM_TABLE_EMAIL.".id, ".USAM_TABLE_EMAIL_RELATIONSHIPS.".object_id, ".USAM_TABLE_EMAIL_RELATIONSHIPS.".object_type FROM ".USAM_TABLE_EMAIL." INNER JOIN ".USAM_TABLE_EMAIL_RELATIONSHIPS." ON (".USAM_TABLE_EMAIL_RELATIONSHIPS.".email_id=".USAM_TABLE_EMAIL.".id) WHERE ".USAM_TABLE_EMAIL_RELATIONSHIPS.".object_type!='email'" );	
foreach ( $results as $k => $result ) 
{	
	usam_insert_ribbon(['event_id' => $result->id, 'event_type' => 'email', 'date_insert' => $result->date_insert], ['object_id' => $result->object_id, 'object_type' => $result->object_type]);	
}

usam_delete_events(['type' => ['sent_letter','inbox_letter']]);
$wpdb->query( "DELETE FROM " . USAM_TABLE_OBJECT_STATUSES . " WHERE type IN ('".implode("','", ['sent_letter','inbox_letter'])."')" );	
							
$usam_base_prefix = $wpdb->prefix.'usam_';
$results = $wpdb->get_results( "SELECT ".USAM_TABLE_EVENTS.".date_insert, ".USAM_TABLE_EVENTS.".type, ".USAM_TABLE_EVENTS.".id, r.object_id, r.object_type FROM ".USAM_TABLE_EVENTS." INNER JOIN {$usam_base_prefix}event_relationships AS r ON (r.event_id=".USAM_TABLE_EVENTS.".id)" );	
foreach ( $results as $k => $result ) 
{	
	usam_insert_ribbon(['event_id' => $result->id, 'event_type' => $result->type, 'date_insert' => $result->date_insert], ['object_id' => $result->object_id, 'object_type' => $result->object_type]);	
}

$results = $wpdb->get_results( "SELECT meta_value, contact_id FROM ".USAM_TABLE_CONTACT_META." WHERE meta_key='advertising_campaign'" );
foreach ( $results as $k => $result ) 
	$result = $wpdb->insert( USAM_TABLE_CAMPAIGN_TRANSITIONS, ['campaign_id' => $result->meta_value, 'contact_id' => $result->contact_id, 'date_insert' => date("Y-m-d H:i:s")] );


$wpdb->query("UPDATE ".USAM_TABLE_ORDER_META." SET meta_key='campaign_id' WHERE `meta_key`='advertising_campaign'");
$wpdb->query("UPDATE ".USAM_TABLE_CONTACT_META." SET meta_key='campaign_id' WHERE `meta_key`='advertising_campaign'");
$wpdb->query("UPDATE ".USAM_TABLE_VISIT_META." SET meta_key='campaign_id' WHERE `meta_key`='advertising_campaign'");