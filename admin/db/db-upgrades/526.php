<?php
global $wp_roles, $wpdb, $wp_rewrite;
		

USAM_Install::create_or_update_tables([USAM_TABLE_PARSING_SITE_URL, USAM_TABLE_PARSING_SITES, USAM_TABLE_BANNERS]);

			
$categories = get_terms(["taxonomy" => "usam-category", "hide_empty" => 0]);	
foreach( $categories as $category )
{
	usam_add_term_metadata( $category->term_id, 'hide_visitors', 0, true );
}

$fields = [		
	['name' => __('ФИО','usam'), 'code' => 'full_name','type' => 'contact', 'group' => 'registration', 'sort' => 1, 'field_type' => 'text', 'metas' => ['registration' => 1]],
	['name' => __('Логин','usam'), 'active' => 0, 'code' => 'log','type' => 'contact', 'group' => 'registration', 'field_type' => 'text',  'metas' => ['registration' => 1]],
	['name' => __('Пароль','usam'), 'active' => 1, 'code' => 'pass1','type' => 'contact', 'group' => 'registration', 'field_type' => 'pass', 'metas' => ['registration' => 1]],
	['name' => __('Повторите пароль','usam'), 'active' => 0, 'code' => 'pass2','type' => 'contact', 'group' => 'registration', 'field_type' => 'pass', 'metas' => ['registration' => 1]],
	
	['name' => __('Согласие на обработку моих персональных данных','usam'), 'type' => 'contact', 'code' => 'consent', 'field_type' => 'one_checkbox', 'group' => 'residential_address', 'mandatory' => 1, 'active' => 1, 'metas' => ['registration' => 1, 'profile' => 1]],
];		
foreach ( $fields as $key => $field )
{
	$field['sort'] = isset($field['sort'])?$field['sort']:$key+100;
	$id = usam_insert_property( $field );		
	if ( $id && !empty($field['metas']) )
	{
		foreach ( $field['metas'] as $meta_key => $meta_value )
			usam_add_property_metadata($id, $meta_key, $meta_value);
	}		
}	
$properties = usam_get_properties(['type' => 'contact', 'active' => 'all', 'code' => ['mobilephone', 'email']]);		
foreach ( $properties as $key => $property )
{
	usam_add_property_metadata($property->id, 'registration', 1);
}

$groups = [['name' => __('Регистрационные данные','usam'), 'type' => 'contact', 'code' => 'registration', 'sort' => 44]];
foreach ( $groups as $group )
{			
	$wpdb->insert( usam_get_table_db('property_groups'), $group );
}