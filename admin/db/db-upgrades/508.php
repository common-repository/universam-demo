<?php
global $wp_roles, $wpdb;

$wpdb->query( "ALTER TABLE `".USAM_TABLE_BANNERS."` DROP COLUMN `image_url`" );

global $wp_rewrite;
$wp_rewrite->flush_rules();
			
USAM_Install::create_or_update_tables( array(USAM_TABLE_OBJECT_STATUSES) );
if ( get_option( 'usam_shop_phone' ) )
{
	$new['name'] =  'Основной номер сайта';			
	$new['phone'] = preg_replace("/[^0-9]/", '', get_option( 'usam_shop_phone' ));			
	$new['format'] = '+9(999)999-99-99';		
	$new['whatsapp'] = 0;
	$new['viber'] = 0;		
	$new['sort'] = 100;	
	usam_add_data( [$new], 'usam_phones' );	
	update_option( 'usam_shop_phone', $new['phone'] );
}
			
if ( get_option('usam_registration_require') )			
	update_option('registration_upon_purchase', 'require');
if ( get_option('usam_automatic_registration') )			
	update_option('registration_upon_purchase', 'automatic');

if ( get_site_option('usam_popup_adding_to_cart') == '1')			
	update_option('usam_popup_adding_to_cart', 'sidebar');
