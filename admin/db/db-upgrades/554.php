<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';


USAM_Install::create_or_update_tables([USAM_TABLE_STORAGES]);

$capabilities = [
	'view_applications' => ['administrator', 'shop_manager'],	
	'view_all_applications' => ['administrator', 'shop_manager'],	
	'view_installed_applications' => ['administrator', 'shop_manager'],	
];				
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
			$wp_role->add_cap( $capability_id );
	}
}

$wpdb->query("UPDATE ".usam_get_table_db('properties')." SET code='full_name' WHERE `code`='username'");
$wpdb->query("UPDATE ".usam_get_table_db('properties')." SET code='full_name' WHERE `code`='name' AND type = 'webform'");
$id = $wpdb->get_var("SELECT id FROM ".usam_get_table_db('properties')." WHERE `code`='full_name' AND type = 'webform'");
usam_update_property_metadata($id, 'connection', 'full_name' );


$properties = [
	['name' => __('Фамилия','usam'), 'code' => 'lastname', 'type' => 'contact', 'group' => 'info',  'field_type' => 'text', 'mask' => '', 'profile' => 1, 'show_staff' => 0, 'metas' => ['profile' => 1]],
	['name' => __('Имя','usam'), 'code' => 'firstname', 'type' => 'contact', 'group' => 'info',  'field_type' => 'text', 'mask' => '', 'profile' => 1, 'show_staff' => 0, 'metas' => ['profile' => 1]],
	['name' => __('Отчество','usam'), 'code' => 'patronymic', 'type' => 'contact', 'group' => 'info',   'field_type' => 'text', 'mask' => '', 'profile' => 1, 'show_staff' => 0, 'metas' => ['profile' => 1]],		
	['name' => __('День рождения','usam'), 'code' => 'birthday', 'type' => 'contact', 'group' => 'info',  'field_type' => 'date', 'mask' => '', 'show_staff' => 0, 'metas' => ['profile' => 1]],
	['name' => __('Пол','usam'), 'code' => 'sex', 'type' => 'contact', 'group' => 'info', 'field_type' => 'text', 'mask' => '', 'show_staff' => 0, 'metas' => ['profile' => 1]],
];			
foreach ( $properties as $k => $property ) 
{
	$property['sort'] = $k+1;
	$id = usam_insert_property( $property );
}
$group = ['name' => __('Обо мне','usam'), 'type' => 'contact', 'code' => 'info', 'sort' => 0];
$wpdb->insert( usam_get_table_db('property_groups'), $group );

$contacts = $wpdb->get_results( "SELECT id, lastname, firstname, patronymic FROM ".USAM_TABLE_CONTACTS." WHERE (lastname!='' OR firstname!='' OR patronymic!='') AND appeal=''" );
foreach( $contacts as $k => $contact )
{	
	$names = (array)$contact;	
	usam_update_contact( $contact->id, ['appeal' => usam_get_formatting_contact_name( $names )] );
}

$contacts = $wpdb->get_results( "SELECT id, status FROM ".USAM_TABLE_CONTACTS." WHERE (lastname!='' OR firstname!='' OR patronymic!='') AND status='temporary'" );
foreach( $contacts as $k => $contact )
{	
	usam_update_contact( $contact->id, ['status' => 'customer'] );		
}

$contacts = $wpdb->get_results( "SELECT id, lastname, firstname, patronymic FROM ".USAM_TABLE_CONTACTS." WHERE lastname!='' OR firstname!='' OR patronymic!=''" );
foreach( $contacts as $k => $contact )
{
	if ( $contact->lastname )
		usam_add_contact_metadata( $contact->id, 'lastname', $contact->lastname );	
	if ( $contact->firstname )
		usam_add_contact_metadata( $contact->id, 'firstname', $contact->firstname );
	if ( $contact->patronymic )
		usam_add_contact_metadata( $contact->id, 'patronymic', $contact->patronymic );	
	
	$names = (array)$contact;	
	$full_name = usam_get_formatting_contact_full_name( $names );
	usam_add_contact_metadata( $contact->id, 'full_name', $full_name );		
	unset($contacts[$k]);
}	

$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `lastname`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `firstname`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `patronymic`" );


$terms = get_terms(['fields' => 'ids', 'taxonomy' => 'usam-category', 'parent' => 0, 'meta_query' => [['key' => 'thumbnail', 'value' => 0, 'compare' => '!=']]]);
foreach( $terms as $term_id )
{
	$attachment_id = (int)get_term_meta($term_id, 'thumbnail', true);
	update_term_meta( $term_id, 'images',  [$attachment_id] );		
}
