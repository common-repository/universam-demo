<?php
global $wp_roles, $wpdb, $wp_rewrite;

USAM_Install::create_or_update_tables([USAM_TABLE_LEADS, USAM_TABLE_LEAD_META, USAM_TABLE_PRODUCTS_LEAD, USAM_TABLE_TAX_PRODUCT_LEAD]);

update_option('usam_shop_requisites_shortcode', []);

$wpdb->query("UPDATE ".usam_get_table_db('properties')." SET mask='#(###)###-##-##' WHERE `mask`='99999999999'");
$wpdb->query("UPDATE ".usam_get_table_db('properties')." SET mask='#(###)###-##-##' WHERE `mask`='9(999)999-99-99'");
$wpdb->query("UPDATE ".usam_get_table_db('properties')." SET mask='###-###-### ##' WHERE `mask`='999-999-999 99'");
$wpdb->query("UPDATE ".usam_get_table_db('properties')." SET mask='' WHERE `mask` LIKE '%99%'");

$fields = [
['name' => __('Мобильный телефон','usam'), 'code' => 'mobilephone','type' => 'employee', 'group' => 'communication', 'field_type' => 'mobile_phone', 'mask' => '#(###)###-##-##', ],
['name' => __('Email','usam'), 'code' => 'email','type' => 'employee', 'group' => 'communication', 'field_type' => 'email', 'mask' => '', ],	
['name' => __('Местоположение','usam'), 'code' => 'location','type' => 'employee', 'group' => 'residential_address', 'sort' => 1, 'field_type' => 'location', 'mask' => '', ],
['name' => __('Улица, дом, корпус, строение','usam'), 'code' => 'address', 'type' => 'employee', 'group' => 'residential_address', 'sort' => 2, 'field_type' => 'textarea', 'mask' => '', ],
['name' => __('Квартира','usam'), 'code' => 'address2','type' => 'employee', 'group' => 'residential_address',  'sort' => 3, 'field_type' => 'text', 'mask' => '', ],
['name' => __('Почтовый индекс','usam'), 'code' => 'postcode','type' => 'employee', 'group' => 'residential_address',  'sort' => 4, 'field_type' => 'postcode', 'mask' => '', ],
['name' => __('Широта','usam'), 'code' => 'latitude', 'type' => 'employee', 'group' => 'coordinates',  'sort' => 25, 'field_type' => 'text', 'mask' => '', ],
['name' => __('Долгота','usam'), 'code' => 'longitude', 'type' => 'employee', 'group' => 'coordinates',  'sort' => 26,  'field_type' => 'text', 'mask' => '', ],
		
['name' => 'ID ВКонтакте', 'code' => 'vk_id', 'type' => 'employee', 'group' => 'social_networks_id',  'sort' => 1, 'field_type' => 'text', 'mask' => '',  ],	
['name' => 'ID Facebook', 'code' => 'facebook_user_id', 'type' => 'employee', 'group' => 'social_networks_id',  'sort' => 1, 'field_type' => 'text', 'mask' => '',  ],		
['name' => 'ID Viber', 'code' => 'viber_user_id', 'type' => 'employee', 'group' => 'social_networks_id',  'sort' => 1, 'field_type' => 'text', 'mask' => '',  ],
['name' => 'ID Telegram', 'code' => 'telegram_user_id', 'type' => 'employee', 'group' => 'social_networks_id',  'sort' => 1, 'field_type' => 'text', 'mask' => '',  ],	
];
foreach ( $fields as $key => $field )
{
	$field['sort'] = isset($field['sort'])?$field['sort']:$key+1;
	$id = usam_insert_property( $field );		
}

$groups = [		
	['name' => __('Связаться','usam'), 'type' => 'employee', 'code' => 'communication', 'sort' => 9],	
	['name' => __('Адрес проживания','usam'), 'type' => 'employee', 'code' => 'residential_address', 'sort' => 10],	
	['name' => __('ID в социальных сетях','usam'), 'type' => 'employee', 'code' => 'social_networks_id', 'system' => 1, 'sort' => 50],			
	['name' => __('Координаты','usam'), 'type' => 'employee', 'code' => 'coordinates', 'system' => 1, 'sort' => 40],	
	['name' => __('Регистрационные данные','usam'), 'type' => 'employee', 'code' => 'registration', 'sort' => 44],		
];
foreach ( $groups as $group )
{
	if ( $wpdb->insert( usam_get_table_db('property_groups'), $group ) )
	{
		
	}
}


