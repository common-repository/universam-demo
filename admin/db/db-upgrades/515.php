<?php
global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables([USAM_TABLE_MAILING_LISTS, USAM_TABLE_PAYMENT_HISTORY_META]);


require_once( USAM_FILE_PATH .'/includes/feedback/mailing_list.php' );
$option = get_option('usam_list_of_subscribers', array()); 
$lists = maybe_unserialize( $option );	
$ids = [];	
foreach( $lists as $list ) 
{				
	$list = (array)$list;
	$list['name'] = $list['title'];
	$id = usam_insert_mailing_list( $list );
	$ids[$id] = $list['id'];
	$wpdb->query("UPDATE ".USAM_TABLE_NEWSLETTER_LISTS." SET list='".$list['id']."00' WHERE `list`='".$list['id']."'");
	$wpdb->query("UPDATE ".USAM_TABLE_SUBSCRIBER_LISTS." SET list='".$list['id']."00' WHERE `list`='".$list['id']."'");
}
 
foreach( $ids as $id => $list_id ) 
{
	$wpdb->query("UPDATE ".USAM_TABLE_NEWSLETTER_LISTS." SET list='".$id."' WHERE `list`='".$list_id."00'");
	$wpdb->query("UPDATE ".USAM_TABLE_SUBSCRIBER_LISTS." SET list='".$id."' WHERE `list`='".$list_id."00'");
}
usam_update_mailing_statuses();