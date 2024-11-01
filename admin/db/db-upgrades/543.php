<?php
global $wp_roles, $wpdb, $wp_rewrite;

delete_option( 'usam_product_order' );

USAM_Install::create_or_update_tables([USAM_TABLE_CHAT_MESSAGE_STATUSES, USAM_TABLE_CHAT_USERS]);

$values = $wpdb->get_results( "SELECT id, value FROM ".usam_get_table_db('product_attribute_options')." WHERE slug=''" );	
foreach( $values as $value )
{
	$wpdb->update( usam_get_table_db('product_attribute_options'), ['slug' => sanitize_title($value->value)], ['id' => $value->id]);	
}

$search_parent = get_option('usam_search_product_property', []);
if ( $search_parent['sku'] !== '=' && $search_parent['sku'] !== 'like' )
{
	$search_parent['sku'] = $search_parent['sku'] == 1 ? '=' : '';
	update_option('usam_search_product_property', $search_parent);
}