<?php
global $wpdb;
		
delete_option('usam_view_category_featured_products');	
delete_option('show_fancy_notifications');	
remove_role( 'remote_agents' );


$wpdb->query( "CREATE TABLE backup SELECT * FROM ".USAM_TABLE_EVENT_USERS."" );  
$wpdb->query( "DROP TABLE `".USAM_TABLE_EVENT_USERS."`" );

USAM_Install::create_or_update_tables( array(USAM_TABLE_EVENT_USERS) );

$wpdb->query( "INSERT `".USAM_TABLE_EVENT_USERS."` (event_id,user_id,user_type) SELECT `event_id`,`user_id`,`user_type` FROM backup" );
$wpdb->query( "DROP TABLE `backup`" );

?>