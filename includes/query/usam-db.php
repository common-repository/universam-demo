<?php
function usam_get_table_db( $table )
{
	global $wpdb;
	$usam_base_prefix = $usam_prefix = $wpdb->prefix.'usam_'; 
	if ( usam_is_multisite() )
		$usam_base_prefix = $wpdb->base_prefix.'usam_'; 

	if ( $table == 'product_attribute_options' )
		$table = 'product_attributes';
	$tables = [
		'linking_posts_multisite' => $usam_prefix,	
		'linking_terms_multisite' => $usam_prefix,	
		'properties' => $usam_prefix,
		'property_meta' => $usam_prefix,
		'property_groups' => $usam_prefix,
		'product_components' => $usam_prefix,
		'product_attributes' => $usam_prefix,//product_attribute_variant
		'product_attribute' => $usam_prefix,
		'posts_search' => $usam_prefix,
		'product_filters' => $usam_prefix,	
		'taxonomy_relationships' => $usam_prefix,			
	];
	$prefix = isset($tables[$table])?$tables[$table]:$usam_prefix;
	return "{$prefix}{$table}";
}
?>