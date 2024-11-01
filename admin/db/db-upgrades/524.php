<?php
global $wp_roles, $wpdb, $wp_rewrite;

$contacts = $wpdb->get_results( "SELECT id, post, sex, foto, birthday FROM ".USAM_TABLE_CONTACTS." " );
foreach( $contacts as $k => $contact )
{
	if ( $contact->post )
		usam_update_contact_metadata( $contact->id, 'post', $contact->post );	
	if ( $contact->sex )
		usam_update_contact_metadata( $contact->id, 'sex', $contact->sex );
	if ( $contact->foto )
		usam_update_contact_metadata( $contact->id, 'foto', $contact->foto );
	if ( $contact->birthday )
		usam_update_contact_metadata( $contact->id, 'birthday', $contact->birthday );
	unset($contacts[$k]);
}
$comp = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_COMPANY." " );
foreach( $comp as $k => $company )
{
	if ( $company->description )
		usam_update_company_metadata( $company->id, 'description', $company->description );	
	if ( $company->logo )		
		usam_update_company_metadata( $company->id, 'logo', $company->logo );
	if ( $company->employees )
		usam_update_company_metadata( $company->id, 'employees', $company->employees );	
	unset($comp[$k]);
}
$orders = $wpdb->get_results( "SELECT id, coupon_name FROM ".USAM_TABLE_ORDERS." WHERE coupon_name!=''" );
foreach( $orders as $k => $order )
{
	usam_update_order_metadata( $order->id, 'coupon_name', $order->coupon_name );	
	unset($orders[$k]);
}

$wpdb->query( "ALTER TABLE `".USAM_TABLE_ORDERS."` DROP COLUMN `coupon_name`" );

$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `post`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `foto`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `sex`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_CONTACTS."` DROP COLUMN `birthday`" );

$wpdb->query( "ALTER TABLE `".USAM_TABLE_COMPANY."` DROP COLUMN `employees`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_COMPANY."` DROP COLUMN `logo`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_COMPANY."` DROP COLUMN `description`" );


//$wpdb->query( "UPDATE ".$wpdb->options." SET option_value= REPLACE(option_value, 'sidebar_product', 'product') WHERE option_name='sidebars_widgets'");
//$wpdb->query( "UPDATE ".$wpdb->options." SET option_value = REPLACE(option_value, 'sidebar-search', 'search') WHERE option_name='sidebars_widgets'");

USAM_Install::create_or_update_tables([USAM_TABLE_LEADS, USAM_TABLE_OBJECT_STATUS_META]);

require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
$object_statuses = usam_get_object_statuses(['type' => ['order','lead','shipped','payment'], 'cache_results' => true]);
foreach( $object_statuses as $status )
	usam_update_object_count_status( $status->internalname, $status->type );
	
usam_insert_object_status(['internalname' => 'delete', 'name' => __('Заказ удален', 'usam'), 'short_name' => __('Удаленные', 'usam'), 'color' => '', 'active' => 1, 'visibility' => false, 'close'  => true, 'type' => 'order']);
 