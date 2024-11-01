<?php
global $wp_roles, $wpdb;
USAM_Install::create_or_update_tables( [USAM_TABLE_STORAGES, USAM_TABLE_COUNTRY, USAM_TABLE_SUBSCRIBER_LISTS, USAM_TABLE_WEBFORMS, USAM_TABLE_USERS_BASKET] );

$capabilities = array( 
	'grid_document' => array( 'administrator', 'shop_manager', 'shop_crm' ),	
	'map_document' => array( 'administrator', 'shop_manager', 'shop_crm' ),	
	'report_document' => array( 'administrator', 'shop_manager', 'shop_crm' ),	
	'setting_document' => array( 'administrator', 'shop_manager' ),	
);	
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) )
		{								
			if ( !$wp_role->has_cap( $capability_id ) )
			{						
				$wp_role->add_cap( $capability_id );						
			}	
		}
	}
}

$roles = ['administrator', 'shop_manager', 'shop_crm'];
foreach( usam_get_details_documents( ) as $document_type => $document )
{	
	foreach( ['view', 'edit', 'delete', 'add', 'export', 'print', 'edit_status', 'email', 'sms'] as $key )
	{
		$capability_id = $key.'_'.$document_type;
		foreach ( $wp_roles->role_objects as $wp_role ) 
		{				
			if ( in_array($wp_role->name, $roles) )
			{								
				if ( !$wp_role->has_cap( $capability_id ) )
				{						
					$wp_role->add_cap( $capability_id );						
				}	
			}
		}
	}
}
$storages = usam_get_storages(['active' => 'all']);
foreach ( $storages as $storage ) 
{
	if ( $storage->description )
		usam_update_storage_metadata( $storage->id, 'description', $storage->description);
	usam_update_storage_metadata( $storage->id, 'schedule', $storage->schedule);
	usam_update_storage_metadata( $storage->id, 'email', $storage->email);
	usam_update_storage_metadata( $storage->id, 'phone', $storage->phone);	
	usam_update_storage_metadata( $storage->id, 'latitude', $storage->GPS_N);
	usam_update_storage_metadata( $storage->id, 'longitude', $storage->GPS_S);
	usam_update_storage_metadata( $storage->id, 'index', $storage->index);	
	if ( $storage->img )
		usam_update_storage_metadata( $storage->id, 'image', $storage->img);	
}
$wpdb->query( "ALTER TABLE `".USAM_TABLE_STORAGES."` DROP COLUMN `img`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_STORAGES."` DROP COLUMN `GPS_N`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_STORAGES."` DROP COLUMN `GPS_S`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_STORAGES."` DROP COLUMN `description`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_STORAGES."` DROP COLUMN `schedule`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_STORAGES."` DROP COLUMN `email`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_STORAGES."` DROP COLUMN `phone`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_STORAGES."` DROP COLUMN `author`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_STORAGES."` DROP COLUMN `address`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_STORAGES."` DROP COLUMN `index`" );
$wpdb->query("UPDATE ".USAM_TABLE_DELIVERY_SERVICE." SET handler='' WHERE `handler`='0'");


$services = usam_get_delivery_services( ['active' => 'all']);
foreach ( $services as $service ) 
{
	$locations = usam_get_delivery_service_metadata($service->id, 'locations');
	usam_delete_delivery_service_metadata($service->id, 'locations');	
	if ( !empty($locations) )
	{				
		foreach( $locations as $location)
		{
			usam_add_delivery_service_metadata($service->id, 'locations', $location);
		}
	}	
	if ( !usam_get_delivery_service_metadata( $service->id, 'storages' ) )
		usam_update_delivery_service( $service->id, ['delivery_option' => 0]);
	else
		usam_update_delivery_service( $service->id, ['delivery_option' => 1]);
}



$fields = array( 			
	['name' => __('Имя','usam'), 'code' => 'billingfirstname',  'field_type' => 'text', 'group' => 'billing', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'firstname', 'payer' => 1, 'delivery_contact' => 1]],
	['name' => __('Фамилия','usam'), 'code' => 'billinglastname', 'field_type' => 'text', 'group' => 'billing', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'lastname', 'payer' => 1, 'delivery_contact' => 1]],
	['name' => __('Мобильный Телефон','usam'), 'code' => 'billingmobilephone', 'field_type' => 'mobile_phone', 'group' => 'billing', 'mandatory' => 1, 'active' => 1, 'mask' => '#(###)###-##-##', 'metadata' =>['connection' => 'mobilephone']],
	['name' => __('Телефон','usam'), 'code' => 'billingphone', 'field_type' => 'phone', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'mask' => '', 'metadata' => ['connection' => 'phone']],
	['name' => __('Email','usam'), 'code' => 'billingemail', 'field_type' => 'email', 'group' => 'billing', 'mandatory' => 1, 'active' => 1,  'metadata' => ['connection' => 'email']],		
//	['name' => __('Страна','usam'), 'code' => 'billingcountry', 'field_type' => 'location_type', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'country')],
//	['name' => __('Область','usam'), 'code' => 'billingregion',  'field_type' => 'location_type', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'region'],
//		['name' => __('Город','usam'), 'code' => 'billingcity', 'field_type' => 'location_type', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'city']],
	['name' => __('Почтовый индекс','usam'), 'code' => 'billingpostcode', 'field_type' => 'text', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'postcode', 'payer_address' => 1, 'delivery_address' => 1]],
	['name' => __('Местоположение','usam'), 'code' => 'billinglocation', 'field_type' => 'location', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'location', 'payer_address' => 1, 'delivery_address' => 1]],					
	['name' => __('Адрес','usam'), 'code' => 'billingaddress', 'field_type' => 'address', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'address', 'payer_address' => 1, 'delivery_address' => 1]],		
	
	['name' => __('Имя','usam'), 'code' => 'shippingfirstname',  'field_type' => 'text', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'firstname', 'payer' => 1, 'delivery_contact' => 1]],
	['name' => __('Фамилия','usam'), 'code' => 'shippinglastname', 'field_type' => 'text', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'lastname', 'payer' => 1, 'delivery_contact' => 1]],
	['name' => __('Местоположение','usam'), 'code' => 'shippinglocation', 'field_type' => 'location', 'group' => 'shipping', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'location', 'payer_address' => 1, 'delivery_address' => 1]],
//	['name' => __('Страна','usam'), 'code' => 'shippingcountry', 'field_type' => 'location_type', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'country']],
//	['name' => __('Область','usam'), 'code' => 'shippingregion',  'field_type' => 'location_type', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'region']],
//	['name' => __('Город','usam'), 'code' => 'shippingcity', 'field_type' => 'location_type', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0, ['connection' => 'city']],
	['name' => __('Почтовый индекс','usam'), 'code' => 'shippingpostcode', 'field_type' => 'text', 'group' => 'shipping', 'mandatory' => 0, 'active' => 1, 'metadata' => ['connection' => 'postcode', 'payer_address' => 1, 'delivery_address' => 1]],
	['name' => __('Адрес','usam'), 'code' => 'shippingaddress', 'field_type' => 'address', 'group' => 'shipping', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'address', 'payer_address' => 1, 'delivery_address' => 1]],
	['name' => __('Заметки к заказу','usam'), 'code' => 'shippingnotesclient', 'field_type' => 'textarea', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0],		
	
	['name' => __('Название компании','usam'), 'code' => 'company',  'field_type' => 'company', 'group' => 'company', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-company_name', 'payer' => 1, 'delivery_contact' => 1]],
	['name' => __('ИНН','usam'), 'code' => 'inn',  'field_type' => 'text','group' => 'company', 'mandatory' => 1, 'active' => 1, 'mask' => '', 'metadata' => ['connection' => 'company-inn']],
	['name' => __('КПП','usam'), 'code' => 'ppc', 'field_type' => 'text', 'group' => 'company', 'mandatory' => 0, 'active' => 1, 'mask' => '', 'metadata' => ['connection' => 'company-ppc']],
	['name' => __('Местоположение','usam'), 'code' => 'company_adrlocation', 'field_type' => 'location', 'group' => 'company', 'mandatory' => 1, 'active' => 1, 'sort' => 24, 'metadata' => ['connection' => 'company-legallocation', 'payer_address' => 1]],				
	['name' => __('Юридический адрес','usam'), 'code' => 'company_adr', 'field_type' => 'address', 'group' => 'company', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-legaladdress', 'payer_address' => 1]],		
	
	['name' => __('Контактное лицо','usam'), 'code' => 'contact_person', 'field_type' => 'text', 'group' => 'contact_information', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'full_name', 'delivery_contact' => 1]],
	['name' => __('Директор','usam'), 'code' => 'gm', 'field_type' => 'text', 'group' => 'contact_information', 'mandatory' => 0, 'active' => 1, 'metadata' => ['connection' => 'gm']],
	['name' => __('Email','usam'), 'code' => 'company_email', 'field_type' => 'email', 'group' => 'contact_information', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-email']],
	['name' => __('Телефон','usam'), 'code' => 'company_phone',  'field_type' => 'phone', 'group' => 'contact_information', 'mandatory' => 1, 'active' => 1, 'mask' => '',  'metadata' => ['connection' => 'company-phone']],		
	['name' => __('Почтовый индекс','usam'), 'code' => 'company_shippingpostcode', 'field_type' => 'text', 'group' => 'contact_information', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-contactpostcode', 'delivery_address' => 1]],
	['name' => __('Местоположение','usam'), 'code' => 'company_shippinglocation', 'field_type' => 'location', 'group' => 'company_shipping', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-contactlocation', 'delivery_address' => 1]],			
	['name' => __('Адрес','usam'), 'code' => 'company_shippingaddress', 'field_type' => 'address', 'group' => 'company_shipping', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-contactaddress', 'delivery_address' => 1]],
	['name' => __('Заметки к заказу','usam'), 'code' => 'company_shippingnotesclient', 'field_type' => 'textarea', 'group' => 'company_shipping', 'mandatory' => 0, 'active' => 0],
);
$properties = usam_get_properties( ['type' => ['order'], 'orderby' => ['group', 'type','name']]);
foreach ( $properties as $property )
{
	foreach ( $fields as $key => $field )
	{				
		if ( !empty($field['metadata']) && !empty($field['metadata']['connection']) && $property->code == $field['code'] )
		{
			usam_update_property_metadata($property->id, 'connection', $field['metadata']['connection'] );
		}
	}
}
$fields = array( 			
	['name' => __('Ваше имя','usam'), 'code' => 'firstname',  'field_type' => 'text', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => 'firstname'],
	['name' => __('Фамилия','usam'), 'code' => 'lastname', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 1, 'active' => 1,'connection' => 'lastname'],
	['name' => __('ФИО','usam'), 'code' => 'name', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1,'connection' => 'full_name'],
	['name' => __('Мобильный Телефон','usam'), 'code' => 'mobilephone', 'field_type' => 'mobile_phone', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => 'mobilephone', 'mask' => '#(###)###-##-##'],
	['name' => __('Телефон','usam'), 'code' => 'phone', 'field_type' => 'phone', 'group' => 'main', 'mandatory' => 0, 'active' => 1,'connection' => 'phone', 'mask' => ''],
	
	['name' => __('Серия','usam'), 'code' => 'passport_series', 'group' => 'passport',  'sort' => 1,  'field_type' => 'text', 'mask' => '', 'connection' => 'passport_series'],
	['name' => __('Номер','usam'), 'code' => 'passport_id', 'group' => 'passport',  'sort' => 1,  'field_type' => 'text', 'mask' => '', 'connection' => 'passport_id'],
	['name' => __('Выдан','usam'), 'code' => 'passport_issued', 'group' => 'passport',  'sort' => 1,  'field_type' => 'text', 'mask' => '', 'connection' => 'passport_issued'],
	['name' => __('Дата выдачи','usam'), 'code' => 'date_passport', 'group' => 'passport',  'sort' => 1,  'field_type' => 'date', 'mask' => '', 'connection' => 'date_passport'],
	['name' => __('Скан','usam'), 'code' => 'scan_passport', 'group' => 'passport',  'sort' => 1,  'field_type' => 'file', 'mask' => '', 'connection' => 'scan_passport'],
	['name' => __('Снилс','usam'), 'code' => 'snills', 'type' => 'contact', 'group' => 'document', 'sort' => 1, 'field_type' => 'text', 'mask' => '', 'connection' => 'snills'],
	['name' => __('Email','usam'), 'code' => 'email', 'field_type' => 'email', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => 'email'],		
	['name' => __('Компания','usam'), 'code' => 'company', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => 'company-full_company_name'],
	['name' => __('Сcылка','usam'), 'code' => 'url', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
	['name' => __('Откуда узнали','usam'), 'code' => 'source', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
	['name' => __('Тема обращения','usam'), 'code' => 'topic', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
	['name' => __('Местоположение','usam'), 'code' => 'location', 'field_type' => 'location', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => 'location'],		
	['name' => __('Почтовый индекс','usam'), 'code' => 'postcode', 'field_type' => 'postcode', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => 'postcode'],
	['name' => __('Адрес','usam'), 'code' => 'address', 'field_type' => 'address', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => 'address'],
	['name' => __('Согласие на обработку моих персональных данных','usam'), 'code' => 'consent', 'field_type' => 'one_checkbox', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => 'consent'],
	['name' => __('Файлы','usam'), 'code' => 'files', 'field_type' => 'files', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
	['name' => __('Сообщение','usam'), 'code' => 'message', 'field_type' => 'textarea', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
);
$properties = usam_get_properties( ['type' => ['webform'], 'orderby' => ['group', 'type','name']]);
foreach ( $properties as $property )
{
	foreach ( $fields as $key => $field )
	{				
		if ( !empty($field['connection']) && $property->code == $field['code'] )
		{
			usam_update_property_metadata($property->id, 'connection', $field['connection'] );
		}
	}
}