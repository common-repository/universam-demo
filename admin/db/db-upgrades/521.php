<?php
global $wp_roles, $wpdb, $wp_rewrite;
	
				
delete_option('usam_search_option');	
USAM_Install::create_or_update_tables([USAM_TABLE_NEWSLETTERS]);

if ( !usam_is_multisite() || is_main_site() )
{

	$wpdb->query("UPDATE ".USAM_TABLE_NEWSLETTERS." SET class='simple' WHERE `class`='S'");
	$wpdb->query("UPDATE ".USAM_TABLE_NEWSLETTERS." SET class='trigger' WHERE `class`='T'");

	require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
	$newsletters = usam_get_newsletters();
	foreach ( $newsletters as $newsletter )
	{ 
		$data = unserialize(base64_decode($newsletter->data));		
		
		usam_update_newsletter_metadata( $newsletter->id, 'body', $newsletter->body );	
		if ( !empty($data['body']) )
			usam_update_newsletter_metadata( $newsletter->id, 'content_blocks', $data['body'] );
		if ( !empty($data['styles']) )
			usam_update_newsletter_metadata( $newsletter->id, 'content_styles', $data['styles'] );
	}	
	$triggered = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usam_newsletter_triggered");
	foreach ( $triggered as $trigger )
	{ 
		usam_update_newsletter_metadata( $trigger->newsletter_id, 'event_start', $trigger->event_start );
		usam_update_newsletter_metadata( $trigger->newsletter_id, 'conditions', unserialize($trigger->condition) );
		
		$data = unserialize($trigger->data);
		if ( $data )
		{
			foreach ( $data as $key => $value )
				usam_update_newsletter_metadata( $trigger->newsletter_id, 'trigger_'.$key, $value );
		}
	}
	$wpdb->query( "ALTER TABLE `".USAM_TABLE_NEWSLETTERS."` DROP COLUMN `body`" );
	$wpdb->query( "ALTER TABLE `".USAM_TABLE_NEWSLETTERS."` DROP COLUMN `data`" );

	$wpdb->query( "DROP TABLE {$wpdb->prefix}usam_newsletter_triggered" );
}