<?php
global $wpdb;
USAM_Install::create_or_update_tables( array(usam_get_table_db('product_components'), USAM_TABLE_PRODUCT_MARKING_CODES) );

$post_metas = $wpdb->get_results( "SELECT * FROM ".$wpdb->postmeta." WHERE meta_key = '_usam_product_metadata'" );
foreach ( $post_metas as $meta )
{			
	$product_meta = maybe_unserialize($meta->meta_value);
	if ( !empty($product_meta['components']) )
	{
		foreach ( $product_meta['components'] as $component )
			usam_add_product_component( array('component' => $component['title'], 'quantity' => $component['c'], 'product_id' => $meta->post_id) );
	}	
}
//_usam_publish_date_vk_group_70373354
//_usam_post_id_vk_group_70373354

