<?php
global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables([USAM_TABLE_LICENSES, USAM_TABLE_PRODUCTS_COMPETITORS]);

delete_option('usam_ftp_update_products');
delete_option('usam_ftp_upload_new_products');

global $wpdb;
$contacts = $wpdb->get_results("SELECT * FROM ".USAM_TABLE_CONTACTS." WHERE about_contact!=''" );
foreach( $contacts as $contact )
{		
	if ( $contact->about_contact )
		usam_update_contact_metadata($contact->id, 'about', $contact->about_contact);	
}

$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `about_contact`" );