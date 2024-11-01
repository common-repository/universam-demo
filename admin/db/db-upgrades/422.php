<?php

global $wp_roles, $wpdb;	

set_time_limit(1800);
$wpdb->query( "CREATE TABLE backup SELECT * FROM ".USAM_TABLE_SHIPPED_PRODUCTS."" );  
$wpdb->query( "DROP TABLE `".USAM_TABLE_SHIPPED_PRODUCTS."`" );

USAM_Install::create_or_update_tables( array(USAM_TABLE_SHIPPED_PRODUCTS) );

$wpdb->query( "INSERT `".USAM_TABLE_SHIPPED_PRODUCTS."` (document_id,product_id,quantity,reserve) SELECT `documents_id`,`basket_id`,`quantity`,`reserve` FROM backup" );
$wpdb->query( "DROP TABLE `backup`" );

$products = $wpdb->get_results( "SELECT id,product_id,price FROM ".USAM_TABLE_PRODUCTS_ORDER." " );		
$product_ids = array();
foreach ( $products as $product )
{
	$wpdb->query("UPDATE `".USAM_TABLE_SHIPPED_PRODUCTS."` SET product_id=$product->product_id, price=$product->price WHERE product_id = $product->id");
}

usam_recalculate_stock_products( );
USAM_Install::create_or_update_tables( array(USAM_TABLE_STORAGE_META, USAM_TABLE_USER_POSTS, USAM_TABLE_CHAT_DIALOGS, USAM_TABLE_SUBSCRIPTIONS, USAM_TABLE_SUBSCRIPTION_RENEWAL) );

$capabilities = array( 
	'view_bonus_cards' => array( 'administrator', 'shop_manager' ),	
	'view_customer_accounts' => array( 'administrator', 'shop_manager' ),	
	'view_carts' => array( 'administrator', 'shop_manager' ),	
	'view_couriers'   => array( 'shop_manager' ),	
	'view_seo_setting' => array( 'administrator', 'shop_seo' ),	
	'universam_settings'   => array( 'administrator' ),	
	'view_inventory_control' => array( 'administrator', 'shop_manager' ),	
);
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) )
		{								
			if ( !$wp_role->has_cap( $capability_id ) )
			{						
				$wp_role->add_cap( $capability_id );						
			}	
		}
	}
}

$wpdb->query( "DROP TABLE {$wpdb->prefix}usam_price_comparison" );


$option = get_option('usam_settings_sites_suppliers');
$sites = maybe_unserialize( $option );	
if ( !empty($sites) )
{
	foreach ( $sites as $site )
	{
		$site['site_type'] = 'supplier';
		$id = usam_insert_parsing_site( $site );
			
		$tags = array();
		foreach ( $site['setting'] as $key => $tag )
			$tags[$key] = array( 'tag' => $tag, 'number' => 0 );
		
		usam_update_parsing_site_metadata( $id, 'tags', $tags );
	}
}
//delete_option('usam_settings_sites_suppliers');
delete_option('usam_force_ssl');
delete_option('usam_online_store');


$wpdb->query( "RENAME TABLE {$wpdb->prefix}usam_user_products TO ".USAM_TABLE_USER_POSTS );
$contacts = $wpdb->get_results( "SELECT id,user_id FROM ".USAM_TABLE_CONTACTS." WHERE user_id!=0" );	
foreach ( $contacts as $contact )
{
	$wpdb->query("UPDATE `".USAM_TABLE_USER_POSTS."` SET contact_id=$contact->id WHERE user_id = $contact->user_id");
}

$wpdb->query( "ALTER TABLE `".USAM_TABLE_USER_POSTS."` DROP COLUMN `user_id`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_USER_POSTS."` DROP COLUMN `user_code`" );
$wpdb->delete( USAM_TABLE_USER_POSTS, array( 'user_list' => 'view' ) );


$result = $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_usam_storage' OR meta_key = '_usam_reserve'");

?>