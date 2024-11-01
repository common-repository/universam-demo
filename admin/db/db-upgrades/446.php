<?php

global $wp_roles, $wpdb;
//$wpdb->query( "RENAME TABLE {$wpdb->prefix}product_attributes TO {$wpdb->prefix}product_attribute_variant" );

USAM_Install::create_or_update_tables( array(usam_get_table_db('product_attribute')) );

$wpdb->query("TRUNCATE TABLE ".usam_get_table_db('product_attribute'));

$post_metas = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_product_attributes_%'" );
$terms = get_terms(['fields' => 'id=>slug','hide_empty' => 0, 'taxonomy' => 'usam-product_attributes']);
foreach ( $post_metas as $meta )
{
	if ( !$meta->meta_value )
		continue;
	
	$id = str_replace("_usam_product_attributes_", "", $meta->meta_key);	
	if ( !empty($terms[$id]) )
	{ 	
		$meta_value = maybe_unserialize( $meta->meta_value );
		if ( is_array($meta_value) )
		{
			foreach ( $meta_value as $value )
				usam_add_product_attribute($meta->post_id, $terms[$id], $value );	
		}
		else
			usam_add_product_attribute($meta->post_id, $terms[$id], $meta_value );	
	}
}
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_usam_product_attributes_%'");


$option = get_option('usam_crosssell_conditions', array() );						
$rules = maybe_unserialize($option);				
foreach( $rules as $key => $rule )
{
	if (isset($rule['conditions']))
	{
		foreach( $rule['conditions'] as $k => $condition )
		{
			if ($condition['type'] == 'attr')
			{
				$term = get_term($condition['value'], 'usam-product_attributes');
				$rules[$key]['conditions'][$k]['value'] = $term->slug;
			}
		}
	}				
}
update_option('usam_crosssell_conditions',  maybe_serialize($rules) );


