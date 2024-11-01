<?php
global $wpdb;
$wpdb->query( "ALTER TABLE `".USAM_TABLE_EVENT_ACTION_LIST."` CHANGE COLUMN `made` `status` tinyint unsigned NOT NULL DEFAULT '0'" );
$wpdb->query("UPDATE `".USAM_TABLE_FILES."` SET type='email' WHERE type='e-mail'");


$files = usam_get_files( array( 'type' => array('email', 'R') ) );			
foreach ( $files as $file ) 
{
	$email = usam_get_email( $file->object_id );			
	if ( empty($email) )
	{
		usam_delete_file( $file->id );
						
		$upload_dir = usam_get_upload_dir( $file->type, $file->object_id );
		usam_remove_dir( $upload_dir );
	}
} 	


global $wp_roles;
$roles =  array( 'administrator', 'shop_manager' );
$capabilities = array( 'view_my_files' => 1, 'view_files' => 1, 'view_warehouse_documents' => 1  ); 	

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
?>