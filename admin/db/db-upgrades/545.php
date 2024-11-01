<?php
global $wp_roles, $wpdb, $wp_rewrite;

delete_option( 'usam_product_order' );

USAM_Install::create_or_update_tables( array(USAM_TABLE_LEADS)  );


$wpdb->query( "ALTER TABLE `".USAM_TABLE_LEADS."` DROP COLUMN `number`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PARSING_SITES."` DROP COLUMN `user`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PARSING_SITES."` DROP COLUMN `login_page`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PARSING_SITES."` DROP COLUMN `password`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PARSING_SITES."` DROP COLUMN `user_code`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PARSING_SITES."` DROP COLUMN `password_code`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PARSING_SITES."` DROP COLUMN `category_attachment`" );


$results = $wpdb->get_results( "SELECT id, pop3server, pop3port, pop3user, pop3pass, pop3ssl, smtpserver, smtpport, smtpuser, smtppass, smtp_secure FROM ".USAM_TABLE_MAILBOX."" );	
foreach ( $results as $k => $result ) 
{
	usam_update_mailbox_metadata( $result->id, 'pop3server', $result->pop3server );
	usam_update_mailbox_metadata( $result->id, 'pop3port', $result->pop3port );
	usam_update_mailbox_metadata( $result->id, 'pop3user', $result->pop3user );	
	usam_update_mailbox_metadata( $result->id, 'pop3pass', $result->pop3pass );	
	usam_update_mailbox_metadata( $result->id, 'pop3ssl', $result->pop3ssl );	
	
	usam_update_mailbox_metadata( $result->id, 'smtpserver', $result->smtpserver );	
	usam_update_mailbox_metadata( $result->id, 'smtpport', $result->smtpport );	
	usam_update_mailbox_metadata( $result->id, 'smtpuser', $result->smtpuser );
	usam_update_mailbox_metadata( $result->id, 'smtppass', $result->smtppass );	
	usam_update_mailbox_metadata( $result->id, 'smtp_secure', $result->smtp_secure );
	unset($results[$k]);
}

$wpdb->query( "ALTER TABLE `".USAM_TABLE_MAILBOX."` DROP COLUMN `pop3server`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_MAILBOX."` DROP COLUMN `pop3port`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_MAILBOX."` DROP COLUMN `pop3user`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_MAILBOX."` DROP COLUMN `pop3pass`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_MAILBOX."` DROP COLUMN `pop3ssl`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_MAILBOX."` DROP COLUMN `smtpserver`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_MAILBOX."` DROP COLUMN `smtpport`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_MAILBOX."` DROP COLUMN `smtpuser`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_MAILBOX."` DROP COLUMN `smtppass`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_MAILBOX."` DROP COLUMN `smtp_secure`" );


$wpdb->query( "ALTER TABLE `".USAM_TABLE_EMAIL."` DROP INDEX `type`" );