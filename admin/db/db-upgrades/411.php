<?php
delete_option('usam_product_importer_rules');
delete_option('usam_orders_export_rules');
delete_option('usam_contacts_export_rules');
delete_option('usam_companies_export_rules');
delete_option('usam_product_exporter_rules');

USAM_Install::create_or_update_tables( array(USAM_TABLE_OBJECT_STATUSES, USAM_TABLE_SLIDER) );

require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
$statuses = usam_get_object_statuses(['type' => 'order']);	
foreach ( $statuses as $status )
{			
	if ( !empty($status->internalname) )
	{
		$option_key_subject = USAM_OPTION_PREFIX.$status->internalname.'_subject';
		$option_key_message = USAM_OPTION_PREFIX.$status->internalname.'_message';
		$option_key_sms_message = USAM_OPTION_PREFIX.$status->internalname.'_sms';		
		
		$subject = get_option($option_key_subject);
		$message = get_option($option_key_message);
		$sms = get_option($option_key_sms_message);	
		usam_update_object_status( $status->id, array('subject_email' => $subject, 'email' => $message, 'sms' => $sms) );	
	
		delete_option($option_key_subject);
		delete_option($option_key_message);
		delete_option($option_key_sms_message);		
	}
}
?>