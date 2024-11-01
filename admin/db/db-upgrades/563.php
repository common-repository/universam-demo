<?php
global $wp_roles, $wpdb, $wp_rewrite;

$usam_base_prefix = $wpdb->prefix.'usam_';
USAM_Install::create_or_update_tables();


$pbuyers = get_option('usam_products_for_buyers', []);
$blocks = [];
$templates = ['carousel' => 'products-carousel', 'by_categories' => 'products-by-category', 'simple_list' => 'grid-product'];
$id = 0;
foreach ( $pbuyers as $value )		
{
	$id++;
	if( $value['active'] && !empty($templates[$value['template']]) )
	{
		$blocks[] = ['type' => 'product-grid', 'id' => $id, 'name' => $value['title'], 'description' => '', 'template' => 'template-parts/blocks/'.$templates[$value['template']], 'device' => 0, 'active' => 1, 'hooks' => ['single_product_after'], 'options' => ['compilation' => $value['type'], 'number' => $value['number']]];
	}
}
update_option( 'usam_html_blocks', $blocks );

$capabilities = [
	'grid_crm' => ['administrator', 'shop_manager', 'personnel_officer', 'company_management', 'shop_crm', 'pickup_point_manager', 'employee'],	
	'calendar_crm' => ['administrator', 'shop_manager', 'personnel_officer', 'company_management', 'shop_crm', 'pickup_point_manager', 'employee'],
];				
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
			$wp_role->add_cap( $capability_id );
	}
}
