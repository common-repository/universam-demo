<?php
global $wp_roles, $wpdb, $wp_rewrite;

USAM_Install::create_or_update_tables([USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS]);

$docs = $wpdb->get_results( "SELECT id, user_id FROM ".USAM_TABLE_COMPANY." WHERE user_id!=0" );
foreach ( $docs as $company ) 
{	
	usam_add_company_personal_account( $company->id, $company->user_id );
}
$wpdb->query( "ALTER TABLE `".USAM_TABLE_COMPANY."` DROP COLUMN `user_id`" );