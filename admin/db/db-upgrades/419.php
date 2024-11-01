<?php
global $wpdb;
$wpdb->delete( $wpdb->termmeta, array('meta_key' => 'usam_sorting_menu_categories') );

$wpdb->query( "TRUNCATE TABLE `" . $wpdb->prefix . "usam_advertising_banner`" );


USAM_Install::create_or_update_tables( array( USAM_TABLE_BANNER_RELATIONSHIPS, USAM_TABLE_BANNERS, USAM_TABLE_SIGNATURES, USAM_TABLE_CHAT_BOT_COMMANDS, USAM_TABLE_CHAT, USAM_TABLE_BANNER_RELATIONSHIPS, USAM_TABLE_BANNERS, USAM_TABLE_SIGNATURES )  );
delete_option('usam_gallery_image');
delete_option('usam_category_image');
delete_option('usam_brand_image');


?>