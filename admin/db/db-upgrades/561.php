<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';
USAM_Install::create_or_update_tables([USAM_TABLE_PRODUCT_DAY]);

$wpdb->query( "ALTER TABLE `".USAM_TABLE_PRODUCT_DAY."` CHANGE COLUMN `value` `discount` decimal(11,2) unsigned NOT NULL DEFAULT '0'" );

$args = ['fields' => 'count', 'number' => 1, 'meta_cache' => true, 'user_id' => 0, 'company_id' => 0, 'conditions' => [['key' => 'appeal', 'value' => '', 'compare' => '='], ['key' => 'number_orders', 'value' => '', 'compare' => '=']]];	
$count = usam_get_contacts( $args );
if( $count )
	usam_create_system_process( __('Удалить пустые контакты','usam'), [], 'delete_empty_contacts', $count, 'delete_empty_contacts' );	
