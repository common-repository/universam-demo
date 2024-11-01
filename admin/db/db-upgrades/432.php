<?php

global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables( array(USAM_TABLE_FOLDERS, USAM_TABLE_FILES) );

$wpdb->query("DELETE FROM ".USAM_TABLE_FOLDERS." WHERE status=0");
$wpdb->query("UPDATE `".USAM_TABLE_FOLDERS."` SET status='closed' WHERE status=1");

$wpdb->query("UPDATE `".$wpdb->postmeta."` SET meta_value='product' WHERE meta_key='".USAM_META_PREFIX."virtual' AND meta_value=0");
$wpdb->query("UPDATE `".$wpdb->postmeta."` SET meta_value='electronic_product' WHERE meta_key='".USAM_META_PREFIX."virtual' AND meta_value=1");
$wpdb->query("UPDATE `".$wpdb->postmeta."` SET meta_value='service' WHERE meta_key='".USAM_META_PREFIX."virtual' AND meta_value=2");
$wpdb->query("UPDATE `".$wpdb->postmeta."` SET meta_value='subscription' WHERE meta_key='".USAM_META_PREFIX."virtual' AND meta_value=3");

update_option('usam_types_products_sold', array( 'product' ) );