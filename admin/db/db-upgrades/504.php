<?php
global $wp_roles, $wpdb;

$wpdb->query( "CREATE TABLE backup SELECT * FROM ".USAM_TABLE_CURRENCY_RATES."" );  
$wpdb->query( "DROP TABLE `".USAM_TABLE_CURRENCY_RATES."`" );

USAM_Install::create_or_update_tables( array(USAM_TABLE_CURRENCY_RATES) );

$wpdb->query( "INSERT `".USAM_TABLE_CURRENCY_RATES."` (basic_currency,currency,rate) SELECT `basic_currency`,`currency`,`rate` FROM backup" );
$wpdb->query( "DROP TABLE `backup`" );

USAM_Install::create_or_update_tables( [USAM_TABLE_COMMENTS,USAM_TABLE_WEBFORMS] );

$ids = $wpdb->get_col("SELECT meta.email_id FROM ".USAM_TABLE_EMAIL." AS e RIGHT JOIN  ".USAM_TABLE_EMAIL_META." AS meta ON (e.id=meta.email_id) WHERE e.id IS NULL");
if ( !empty($ids) )	
	$wpdb->query("DELETE FROM ".USAM_TABLE_EMAIL_META." WHERE email_id IN ('".implode("','", $ids)."')");
/*
$ids = $wpdb->get_col("SELECT file.id FROM ".USAM_TABLE_EMAIL." AS e RIGHT JOIN  ".USAM_TABLE_FILES." AS file ON (e.id=file.object_id) WHERE e.id IS NULL AND file.type='email'");
if ( !empty($ids) )	
	usam_delete_files(['include' => $ids], true);

$ids = $wpdb->get_col("SELECT file.id FROM ".USAM_TABLE_EMAIL." AS e RIGHT JOIN  ".USAM_TABLE_FILES." AS file ON (e.id=file.object_id) WHERE e.id IS NULL AND file.type='R'");
if ( !empty($ids) )	
	usam_delete_files(['include' => $ids], true);

*/
