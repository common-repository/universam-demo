<?php
global $wp_roles, $wpdb, $wp_rewrite;
		

USAM_Install::create_or_update_tables([USAM_TABLE_OPEN_REFERRAL_LINKS, USAM_TABLE_PARSING_SITE_URL, USAM_TABLE_ACCOUNT_TRANSACTIONS, USAM_TABLE_WEBFORMS, usam_get_table_db('product_attribute_options')]);

$emails = $wpdb->get_results( "SELECT id, object_type, object_id FROM ".USAM_TABLE_EMAIL." WHERE object_id!=0 AND object_type!=''" );
foreach( $emails as $k => $email )
{
	usam_insert_email_object( $email->id, ['object_id' => $email->object_id, 'object_type' => $email->object_type] );
	unset($emails[$k]);
}

$wpdb->query( "ALTER TABLE `".USAM_TABLE_EMAIL."` DROP COLUMN `object_type`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_EMAIL."` DROP COLUMN `object_id`" );

usam_upload_theme(['domino']);
