<?php

global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables( array(USAM_TABLE_FOLDERS, USAM_TABLE_FILES) );


$wpdb->query( "ALTER TABLE `".USAM_TABLE_NOTIFICATIONS."` DROP COLUMN `date_completion`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_DOWNLOAD_STATUS."` DROP COLUMN `file_name`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_DOWNLOAD_STATUS."` DROP COLUMN `basket_id`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_DOWNLOAD_STATUS."` DROP COLUMN `unique_id`" );


$wpdb->query("DELETE FROM ".USAM_TABLE_PAGE_VIEWED." WHERE url LIKE '/favicon.ico' OR url LIKE '/feed' OR url LIKE '%index.php' OR url LIKE '%/api/vk' OR url LIKE '%/wp-content/%' OR url LIKE '/rss' OR url LIKE '/wp-json/wp%'");

$emails = usam_get_emails( array('fields' => array('mailbox_id', 'id') ) );
$files = usam_get_files( array('type' => array('email', 'R'), 'folder_id' => 0 ));

$parent_id = usam_get_folders( array('fields' => 'id', 'slug' => 'email', 'number' => 1) );
if ( empty($parent_id) )
	$parent_id = usam_insert_folder( array('name' => __("Почта","usam"), 'slug' => 'email') );

$folder_ids = array();
$mailboxes = usam_get_mailboxes( );
foreach ( $mailboxes as $mailbox )
{
	$folder_id = usam_get_folders( array('fields' => 'id', 'name' => $mailbox->email, 'number' => 1) );
	if ( $folder_id )
		$folder_ids[$mailbox->id] = $folder_id;
	else
		$folder_ids[$mailbox->id] = usam_insert_folder( array('name' => $mailbox->email, 'parent_id' => $parent_id) );
}
$_emails = array();
foreach ( $emails as $email )
{
	$_emails[$email->id] = $email->mailbox_id;	
}
foreach ( $files as $file )
{ 
	usam_update_file( $file->id, array( 'folder_id' => $folder_ids[$_emails[$file->object_id]] ) );	
}
?>