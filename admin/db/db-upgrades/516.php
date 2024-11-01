<?php
global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables([USAM_TABLE_MAILING_LISTS, USAM_TABLE_PAYMENT_HISTORY_META]);


$wpdb->query( "ALTER TABLE `".USAM_TABLE_PRODUCTS_BASKET."` DROP COLUMN `sku`" );

delete_option("universam_version");
delete_option("usam_check_mime_types");
delete_option("usam_donot_upload_same_attacment");
delete_option("usam_search_exclude_posts");
delete_option("usam_search_exclude_pages");
delete_option("usam_search_exclude_p_tags");
delete_option("usam_search_exclude_p_categories");
delete_option("usam_list_of_subscribers");
delete_option('usam_share_this');
delete_option("usam_enable_comments");

$newsletters = [
	[
		'subject' => get_option("usam_trackingid_subject"), 
		'body' => nl2br(get_option("usam_trackingid_message")),
		'event_start' => 'trackingid', 
		'class' => 'T',
		'status' => 5,
		'data' => ['body' => [['text' => ['value' => nl2br(get_option("usam_trackingid_message"))], 'image' => ['src' => '', 'width' => '', 'height' => '', 'alignment' => '', 'static' => ''], 'position' => 1, 'type' => 'content' ]]]
	],
];		
$template = 'white-blue2';		
foreach( $newsletters as $newsletter )
{			
	$newsletter['template'] = $template;	
	$mailtemplate = usam_get_email_template( $template );			
	$newsletter['body'] = str_replace('%mailcontent%',$newsletter['body'], $mailtemplate);
		
	$id = usam_insert_newsletter( $newsletter );
	if ( $id && $newsletter['class'] == 'T' )
		usam_update_newsletter_metadata( $id, 'event_start', $newsletter['event_start'] );
}	

delete_option("usam_trackingid_subject");
delete_option("usam_trackingid_message");
delete_option("usam_payment_invoice_subject");
delete_option("usam_message_payment_invoice");

