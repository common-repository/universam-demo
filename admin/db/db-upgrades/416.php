<?php
global $wpdb;

$wpdb->query( "ALTER TABLE `".USAM_TABLE_CHAT."` DROP COLUMN `source`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_COUPON_CODES."` DROP COLUMN `use_once`" );

$wpdb->query( "ALTER TABLE `".USAM_TABLE_EMAIL."` DROP COLUMN `size`" );
$wpdb->query( "DROP TABLE '{$wpdb->prefix}usam_product_views'" );
$wpdb->query( "DROP TABLE '{$wpdb->prefix}usam_transport_location'" );

$wpdb->query( "ALTER TABLE `".USAM_TABLE_EMAIL."` CHANGE COLUMN `subject` `title` varchar(250) NOT NULL DEFAULT ''" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SMS."` CHANGE COLUMN `number` `server_message_id` varchar(250) NOT NULL DEFAULT ''" );

$wpdb->query( "ALTER TABLE `".USAM_TABLE_EMAIL."` DROP COLUMN `reply_to_email`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_EMAIL."` DROP COLUMN `reply_to_name`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_EMAIL."` DROP COLUMN `copy_email`" );


$ids = $wpdb->get_col( "SELECT id FROM " . USAM_TABLE_EVENTS." WHERE type IN ('email', 'work')"  );
foreach ( $ids as $id ) 
{		
	$wpdb->query("DELETE FROM ".USAM_TABLE_EVENT_ACTION_LIST." WHERE event_id = '$id'");	
	$wpdb->query("DELETE FROM ".USAM_TABLE_EVENT_USERS." WHERE event_id = '$id'");	
	$wpdb->query("DELETE FROM ".USAM_TABLE_EVENT_META." WHERE event_id = '$id'");	
	$wpdb->query("DELETE FROM ".USAM_TABLE_EVENTS." WHERE id = '$id'");	
}


USAM_Install::create_or_update_tables( array( USAM_TABLE_CONTACTS, USAM_TABLE_PAGE_VIEWED, USAM_TABLE_VISITS, USAM_TABLE_DOCUMENTS, USAM_TABLE_APPLICATIONS, USAM_TABLE_APPLICATION_META, USAM_TABLE_COMMUNICATION_ERRORS, USAM_TABLE_SOCIAL_NETWORK_PROFILES, USAM_TABLE_EMAIL )  );

$wpdb->query("UPDATE ".USAM_TABLE_EMAIL." SET `type`='inbox_letter' WHERE `server_message_id`!=0");

delete_option('usam_communication_errors');
delete_option('usam_ok_groups');
delete_option('usam_verified_email_addresses');
delete_option('usam_email_verification');
delete_option('usam_crm_company_fields');
delete_option('usam_crm_company_fields_group');
update_option( 'usam_set_events', '' );
delete_option( 'usam_vk_publish_product_day' );

$contacts = $wpdb->get_col( "SELECT id FROM " . USAM_TABLE_CONTACTS." WHERE secret_key=''"  );
foreach( $contacts as $id)
{
	$wpdb->query("UPDATE `".USAM_TABLE_CONTACTS."` SET secret_key='".md5(uniqid(rand(),1))."' WHERE id='".$id."'");
}	
		
global $wp_roles;
$roles =  array( 'administrator', 'shop_manager' );
$capabilities = array( 'view_monitor' => 1, 'view_odnoklassniki' => 1, 'view_chat_bots' => 1, 'view_files' => 1 , 'view_my_files' => 1  ); 	

foreach ( $roles as $role ) 
{	
	if ( isset($wp_roles->role_objects[$role]) )
	{
		foreach ( $capabilities as $capability_id => $active ) 
		{						
			if ( $active )
			{				
				if ( !$wp_roles->role_objects[$role]->has_cap( $capability_id ) )
				{										
					$wp_roles->role_objects[$role]->add_cap( $capability_id );						
				}												
			}	
			else					
				$wp_roles->role_objects[$role]->remove_cap( $capability_id );			
		}
	}	
}


$option = get_option('usam_vk_groups', '' );
$groups = maybe_unserialize( $option );	
if ( !empty($groups) )
{
	foreach ( $groups as $group ) 
	{
		$group['type_page'] = 'group';
		$profiles[] = $group;
	}	
}
$option = get_option('usam_vk_profile', '' );
$users = maybe_unserialize( $option );					
if ( !empty($users) )
{
	foreach ( $users as $profile ) 
	{
		$profile['type_page'] = 'user';
		$profiles[] = $profile;
	}
}		
foreach ( $profiles as $profile ) 
{
if ( $profile['type_page'] == 'group' )
{
	$insert = array( 'code' => $profile['page_id'], 'type_social' => 'vk_group', 'access_token' => $profile['access_token'], 'name' => $profile['name'], 'photo' => $profile['photo'], 'birthday' => $profile['birthday'], 'uri' => $profile['screen_name'], 'contact_group' => $profile['contact_group'] );
	$metas = array( 'message_group_join' => $profile['message_group_join'], 'message_group_unsure' => $profile['message_group_unsure'], 'message_group_leave' => $profile['message_group_leave'], 'message_group_request' => $profile['message_group_request'], 'message_group_approved' => $profile['message_group_approved'], 'message_group_accepted' => $profile['message_group_accepted'], 'publish_reviews' => $profile['publish_reviews'], 'group_access_token' => $profile['group_access_token'], 'secret_key' => $profile['secret_key'], 'confirmation' => $profile['confirmation'] );
}
else 
{
	$insert = array( 'code' => $profile['page_id'], 'type_social' => 'vk_user', 'access_token' => $profile['access_token'], 'name' => $profile['first_name'].' '.$profile['last_name'], 'photo' => $profile['photo_50'] );
}
$id = usam_insert_social_network_profile($insert);
if ( $id && !empty($metas) )
{			
	foreach ( $metas as $meta_key => $meta_value )
	{													
		$meta_key = sanitize_text_field($meta_key);
		$meta_value = sanitize_text_field($meta_value);
		usam_update_social_network_profile_metadata( $id, $meta_key, $meta_value );		
	}
	
}
}

delete_option( 'usam_vk_profile' );
delete_option( 'usam_vk_groups' );

?>