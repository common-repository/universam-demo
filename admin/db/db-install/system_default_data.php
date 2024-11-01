<?php
class USAM_Load_System_Default_Data
{		
	private $db_path;
	function __construct( $type_data ) 
	{			
		$this->db_path = USAM_FILE_PATH . "/admin/db/db-install/";
		if ( !is_array($type_data) )
			$type_data = array($type_data);
		
		foreach ( $type_data as $action )
		{ 
			$method = 'controller_'.$action;	
			if ( method_exists($this, $method) )
			{
				$this->$method();					
			}
		}
	}
	
	// Очистить таблицу
	private function truncase( $table ) 
	{
		global $wpdb;
		$wpdb->query("TRUNCATE TABLE $table");
	}
	
	function controller_properties( )
	{
		$this->truncase( usam_get_table_db('properties') );
		$this->controller_order_properties();
		$this->controller_crm_properties();
		$this->controller_webform_properties();		
	}
	
	//свойства
	function controller_order_properties( )
	{
		global $wpdb;	
		$wpdb->delete( usam_get_table_db('properties'), ['type' => 'order']);
		$fields = [
			['name' => __('Имя','usam'), 'code' => 'billingfirstname', 'field_type' => 'text', 'group' => 'billing', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'firstname', 'payer' => 1, 'delivery_contact' => 1]],
			['name' => __('Фамилия','usam'), 'code' => 'billinglastname','field_type' => 'text', 'group' => 'billing', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'lastname', 'payer' => 1, 'delivery_contact' => 1]],
			['name' => __('Мобильный Телефон','usam'), 'code' => 'billingmobilephone','field_type' => 'mobile_phone', 'group' => 'billing', 'mandatory' => 1, 'active' => 1, 'mask' => '#(###)###-##-##', 'metadata' => ['connection' => 'mobilephone']],
			['name' => __('Телефон','usam'), 'code' => 'billingphone','field_type' => 'phone', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'phone']],
			['name' => __('Email','usam'), 'code' => 'billingemail','field_type' => 'email', 'group' => 'billing', 'mandatory' => 1, 'active' => 1,  'metadata' => ['connection' => 'email']],		
		//	['name' => __('Страна','usam'), 'code' => 'billingcountry','field_type' => 'location_type', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'country')],
		//	['name' => __('Область','usam'), 'code' => 'billingregion', 'field_type' => 'location_type', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'region'],
	//		['name' => __('Город','usam'), 'code' => 'billingcity','field_type' => 'location_type', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'city']],
			['name' => __('Почтовый индекс','usam'), 'code' => 'billingpostcode','field_type' => 'text', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'postcode', 'payer_address' => 1, 'delivery_address' => 1]],
			['name' => __('Местоположение','usam'), 'code' => 'billinglocation','field_type' => 'location', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'location', 'payer_address' => 1, 'delivery_address' => 1]],					
			['name' => __('Адрес','usam'), 'code' => 'billingaddress','field_type' => 'address', 'group' => 'billing', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'address', 'payer_address' => 1, 'delivery_address' => 1]],		
			
			['name' => __('Имя','usam'), 'code' => 'shippingfirstname', 'field_type' => 'text', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'firstname', 'payer' => 1, 'delivery_contact' => 1]],
			['name' => __('Фамилия','usam'), 'code' => 'shippinglastname','field_type' => 'text', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'lastname', 'payer' => 1, 'delivery_contact' => 1]],
			['name' => __('Местоположение','usam'), 'code' => 'shippinglocation','field_type' => 'location', 'group' => 'shipping', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'location', 'payer_address' => 1, 'delivery_address' => 1]],
		//	['name' => __('Страна','usam'), 'code' => 'shippingcountry','field_type' => 'location_type', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'country']],
		//	['name' => __('Область','usam'), 'code' => 'shippingregion', 'field_type' => 'location_type', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0, 'metadata' => ['connection' => 'region']],
		//	['name' => __('Город','usam'), 'code' => 'shippingcity','field_type' => 'location_type', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0, ['connection' => 'city']],
			['name' => __('Почтовый индекс','usam'), 'code' => 'shippingpostcode','field_type' => 'text', 'group' => 'shipping', 'mandatory' => 0, 'active' => 1, 'metadata' => ['connection' => 'postcode', 'payer_address' => 1, 'delivery_address' => 1]],
			['name' => __('Адрес','usam'), 'code' => 'shippingaddress','field_type' => 'address', 'group' => 'shipping', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'address', 'payer_address' => 1, 'delivery_address' => 1]],
			['name' => __('Комментарий к заказу','usam'), 'code' => 'shippingnotesclient','field_type' => 'textarea', 'group' => 'shipping', 'mandatory' => 0, 'active' => 0],		
			
			['name' => __('Название компании','usam'), 'code' => 'company', 'field_type' => 'company', 'group' => 'company', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-company_name', 'payer' => 1, 'delivery_contact' => 1]],
			['name' => __('ИНН','usam'), 'code' => 'inn', 'field_type' => 'text','group' => 'company', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-inn']],
			['name' => __('КПП','usam'), 'code' => 'ppc','field_type' => 'text', 'group' => 'company', 'mandatory' => 0, 'active' => 1, 'metadata' => ['connection' => 'company-ppc']],
			['name' => __('Местоположение','usam'), 'code' => 'legallocation','field_type' => 'location', 'group' => 'company', 'mandatory' => 1, 'active' => 1, 'sort' => 24, 'metadata' => ['connection' => 'company-legallocation', 'payer_address' => 1]],				
			['name' => __('Юридический адрес','usam'), 'code' => 'legaladdress','field_type' => 'address', 'group' => 'company', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-legaladdress', 'payer_address' => 1]],		
			
			['name' => __('Контактное лицо','usam'), 'code' => 'contact_person','field_type' => 'text', 'group' => 'contact_information', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'full_name', 'delivery_contact' => 1]],
			['name' => __('Директор','usam'), 'code' => 'gm','field_type' => 'text', 'group' => 'contact_information', 'mandatory' => 0, 'active' => 1, 'metadata' => ['connection' => 'gm']],
			['name' => __('Email','usam'), 'code' => 'company_email','field_type' => 'email', 'group' => 'contact_information', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-email']],
			['name' => __('Телефон','usam'), 'code' => 'company_phone', 'field_type' => 'phone', 'group' => 'contact_information', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-phone']],		
			['name' => __('Почтовый индекс','usam'), 'code' => 'company_shippingpostcode','field_type' => 'text', 'group' => 'contact_information', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-contactpostcode', 'delivery_address' => 1]],
			['name' => __('Местоположение','usam'), 'code' => 'company_shippinglocation','field_type' => 'location', 'group' => 'company_shipping', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-contactlocation', 'delivery_address' => 1]],			
			['name' => __('Адрес','usam'), 'code' => 'company_shippingaddress','field_type' => 'address', 'group' => 'company_shipping', 'mandatory' => 1, 'active' => 1, 'metadata' => ['connection' => 'company-contactaddress', 'delivery_address' => 1]],
			['name' => __('Комментарий к заказу','usam'), 'code' => 'company_shippingnotesclient','field_type' => 'textarea', 'group' => 'company_shipping', 'mandatory' => 0, 'active' => 0],
		];
		foreach ( $fields as $k => $field )
		{
			$field['sort'] = $k+1;
			$field['type'] = 'order';
			$id = usam_insert_property( $field );			
			if ( !empty($field['metadata']) )
			{
				foreach ( $field['metadata'] as $meta_key => $meta_value )
					usam_update_property_metadata($id, $meta_key, $meta_value );
			}
		}	
	}
	
		//свойства
	function controller_crm_properties( )
	{
		global $wpdb;	
		$wpdb->delete( usam_get_table_db('properties'), array('type' => 'contact') );
		$wpdb->delete( usam_get_table_db('properties'), array('type' => 'company') );
		$wpdb->delete( usam_get_table_db('properties'), array('type' => 'employee') );		
		$fields = [							
			['name' => __('Фамилия','usam'), 'code' => 'lastname', 'type' => 'contact', 'group' => 'info',  'field_type' => 'text', 'mask' => '', 'show_staff' => 0, 'metas' => ['profile' => 1] ],
			['name' => __('Имя','usam'), 'code' => 'firstname', 'type' => 'contact', 'group' => 'info',  'field_type' => 'text', 'mask' => '', 'show_staff' => 0, 'metas' => ['profile' => 1] ],
			['name' => __('Отчество','usam'), 'code' => 'patronymic', 'type' => 'contact', 'group' => 'info', 'field_type' => 'text', 'mask' => '', 'show_staff' => 0, 'metas' => ['profile' => 1] ],
			['name' => __('День рождения','usam'), 'code' => 'birthday', 'type' => 'contact', 'group' => 'info', 'field_type' => 'date', 'mask' => '', 'show_staff' => 0, 'metas' => ['profile' => 1] ],
			['name' => __('Пол','usam'), 'code' => 'sex', 'type' => 'contact', 'group' => 'info', 'field_type' => 'text', 'mask' => '', 'show_staff' => 0],				
			
			['name' => __('Мобильный телефон','usam'), 'code' => 'mobilephone','type' => 'contact', 'group' => 'communication', 'field_type' => 'mobile_phone', 'mask' => '#(###)###-##-##', 'metas' => ['registration' => 1, 'profile' => 1]],
			['name' => __('Рабочий телефон','usam'), 'code' => 'workphone','type' => 'contact', 'group' => 'communication', 'field_type' => 'phone', 'mask' => '#(###)###-##-##'],
			['name' => __('Email','usam'), 'code' => 'email','type' => 'contact', 'group' => 'communication', 'field_type' => 'email', 'mask' => '', 'metas' => ['registration' => 1, 'profile' => 1]],		
			['name' => __('Местоположение','usam'), 'code' => 'location','type' => 'contact', 'group' => 'residential_address', 'field_type' => 'location', 'mask' => '', 'metas' => ['profile' => 1] ],
			['name' => __('Улица, дом, корпус, строение','usam'), 'code' => 'address', 'type' => 'contact', 'group' => 'residential_address', 'field_type' => 'textarea', 'mask' => '', 'metas' => ['profile' => 1] ],
			['name' => __('Квартира / офис','usam'), 'code' => 'address2','type' => 'contact', 'group' => 'residential_address', 'field_type' => 'text', 'mask' => '', 'metas' => ['profile' => 1] ],
			['name' => __('Почтовый индекс','usam'), 'code' => 'postcode','type' => 'contact', 'group' => 'residential_address', 'field_type' => 'postcode', 'mask' => '', 'metas' => ['profile' => 1] ],
			['name' => __('Широта','usam'), 'code' => 'latitude', 'type' => 'contact', 'group' => 'coordinates', 'field_type' => 'text', 'mask' => ''],
			['name' => __('Долгота','usam'), 'code' => 'longitude', 'type' => 'contact', 'group' => 'coordinates',  'field_type' => 'text', 'mask' => ''],
			['name' => __('Сайт','usam'), 'code' => 'site', 'type' => 'contact', 'group' => 'internet', 'field_type' => 'link', 'mask' => ''],
			
			/*
			['name' => __('Серия','usam'), 'code' => 'passport_series', 'type' => 'contact', 'group' => 'passport', 'field_type' => 'text', 'mask' => ''],
			['name' => __('Номер','usam'), 'code' => 'passport_id', 'type' => 'contact', 'group' => 'passport', 'field_type' => 'text', 'mask' => ''],
			['name' => __('Выдан','usam'), 'code' => 'passport_issued', 'type' => 'contact', 'group' => 'passport', 'field_type' => 'text', 'mask' => ''],
			['name' => __('Дата выдачи','usam'), 'code' => 'date_passport', 'type' => 'contact', 'group' => 'passport', 'field_type' => 'date', 'mask' => ''],
			['name' => __('Скан','usam'), 'code' => 'scan_passport', 'type' => 'contact', 'group' => 'passport', 'field_type' => 'file', 'mask' => ''],
			
			['name' => __('Снилс','usam'), 'code' => 'snills', 'type' => 'contact', 'group' => 'document', 'field_type' => 'text', 'mask' => '###-###-### ##'],*/
			
			['name' => 'ВКонтакте', 'code' => 'vk', 'type' => 'contact', 'group' => 'social_networks',  'sort' => 1,  'field_type' => 'text', 'mask' => '' ],	
			['name' => __('Одноклассники','usam'), 'code' => 'ok', 'type' => 'company', 'group' => 'social_networks',  'sort' => 2,  'field_type' => 'text', 'mask' => ''],
			['name' => 'Facebook', 'code' => 'facebook', 'type' => 'contact', 'group' => 'social_networks',  'sort' => 2,  'field_type' => 'text', 'mask' => '' ],	
			['name' => 'Twitter', 'code' => 'twitter', 'type' => 'contact', 'group' => 'social_networks',  'sort' => 2,  'field_type' => 'text', 'mask' => '' ],
			['name' => 'Telegram', 'code' => 'telegram', 'type' => 'contact', 'group' => 'social_networks',  'sort' => 3,  'field_type' => 'text', 'mask' => '' ],	
			['name' => 'Instagram', 'code' => 'instagram', 'type' => 'contact', 'group' => 'social_networks',  'sort' => 4,  'field_type' => 'text', 'mask' => '' ],
			['name' => 'Viber', 'code' => 'viber', 'type' => 'contact', 'group' => 'social_networks',  'sort' => 5,  'field_type' => 'text', 'mask' => '' ],
			['name' => 'Skype', 'code' => 'skype', 'type' => 'contact', 'group' => 'social_networks',  'sort' => 6,  'field_type' => 'text', 'mask' => '' ],
			['name' => 'ICQ', 'code' => 'icq', 'type' => 'contact', 'group' => 'social_networks',  'sort' => 7,  'field_type' => 'text', 'mask' => '' ],
			['name' => 'MSN', 'code' => 'msn', 'type' => 'contact', 'group' => 'social_networks',  'sort' => 8,  'field_type' => 'text', 'mask' => '' ],
			['name' => 'Jabber', 'code' => 'jabber', 'type' => 'contact', 'group' => 'social_networks',  'sort' => 9,  'field_type' => 'text', 'mask' => '' ],
			
			['name' => __('Любимый магазин','usam'), 'code' => 'favorite_shop', 'type' => 'contact', 'group' => 'residential_address', 'sort' => 1, 'field_type' => 'shops', 'mask' => '', 'active' => 0, 'metas' => ['profile' => 1]],
			
			['name' => 'ID ВКонтакте', 'code' => 'vk_id', 'type' => 'contact', 'group' => 'social_networks_id', 'field_type' => 'text', 'mask' => '' ],	
			['name' => 'ID Facebook', 'code' => 'facebook_user_id', 'type' => 'contact', 'group' => 'social_networks_id', 'field_type' => 'text', 'mask' => '' ],		
			['name' => 'ID Viber', 'code' => 'viber_user_id', 'type' => 'contact', 'group' => 'social_networks_id', 'field_type' => 'text', 'mask' => '' ],
			['name' => 'ID Telegram', 'code' => 'telegram_user_id', 'type' => 'contact', 'group' => 'social_networks_id',  'field_type' => 'text', 'mask' => '' ],		

/*  employee */
			['name' => __('Мобильный телефон','usam'), 'code' => 'mobilephone','type' => 'employee', 'group' => 'communication', 'field_type' => 'mobile_phone', 'mask' => '#(###)###-##-##'],
			['name' => __('Домашний телефон','usam'), 'code' => 'phone','type' => 'employee', 'group' => 'communication', 'field_type' => 'phone', 'mask' => '#(###)###-##-##'],
			['name' => __('Рабочий телефон','usam'), 'code' => 'workphone','type' => 'employee', 'group' => 'communication', 'field_type' => 'phone', 'mask' => '#(###)###-##-##'],
			['name' => __('Добавочный телефон','usam'), 'code' => 'extensionphone','type' => 'employee', 'group' => 'communication', 'field_type' => 'text', 'mask' => ''],
			['name' => __('Email','usam'), 'code' => 'email','type' => 'employee', 'group' => 'communication', 'field_type' => 'email', 'mask' => ''],
			['name' => __('Рабочий email','usam'), 'code' => 'workemail','type' => 'employee', 'group' => 'communication', 'field_type' => 'email', 'mask' => ''],			
			['name' => __('Местоположение','usam'), 'code' => 'location','type' => 'employee', 'group' => 'residential_address', 'sort' => 1, 'field_type' => 'location', 'mask' => ''],
			['name' => __('Улица, дом, корпус, строение','usam'), 'code' => 'address', 'type' => 'employee', 'group' => 'residential_address', 'sort' => 2, 'field_type' => 'textarea', 'mask' => ''],
			['name' => __('Квартира','usam'), 'code' => 'address2','type' => 'employee', 'group' => 'residential_address',  'sort' => 3, 'field_type' => 'text', 'mask' => ''],
			['name' => __('Почтовый индекс','usam'), 'code' => 'postcode','type' => 'employee', 'group' => 'residential_address',  'sort' => 4, 'field_type' => 'postcode', 'mask' => ''],
			['name' => __('Широта','usam'), 'code' => 'latitude', 'type' => 'employee', 'group' => 'coordinates',  'sort' => 25, 'field_type' => 'text', 'mask' => ''],
			['name' => __('Долгота','usam'), 'code' => 'longitude', 'type' => 'employee', 'group' => 'coordinates',  'sort' => 26,  'field_type' => 'text', 'mask' => ''],
			
			['name' => __('Серия','usam'), 'code' => 'passport_series', 'type' => 'employee', 'group' => 'passport', 'field_type' => 'text', 'mask' => '' ],
			['name' => __('Номер','usam'), 'code' => 'passport_id', 'type' => 'employee', 'group' => 'passport',  'field_type' => 'text', 'mask' => '' ],
			['name' => __('Выдан','usam'), 'code' => 'passport_issued', 'type' => 'employee', 'group' => 'passport', 'field_type' => 'text', 'mask' => '' ],
			['name' => __('Дата выдачи','usam'), 'code' => 'date_passport', 'type' => 'employee', 'group' => 'passport',  'field_type' => 'date', 'mask' => '' ],
			['name' => __('Скан','usam'), 'code' => 'scan_passport', 'type' => 'employee', 'group' => 'passport', 'field_type' => 'file', 'mask' => '' ],
			
			['name' => __('Снилс','usam'), 'code' => 'snills', 'type' => 'employee', 'group' => 'document', 'field_type' => 'text', 'mask' => '###-###-### ##' ],
					
			['name' => 'ID ВКонтакте', 'code' => 'vk_id', 'type' => 'employee', 'group' => 'social_networks_id', 'field_type' => 'text', 'mask' => '' ],	
			['name' => 'ID Facebook', 'code' => 'facebook_user_id', 'type' => 'employee', 'group' => 'social_networks_id', 'field_type' => 'text', 'mask' => '' ],		
			['name' => 'ID Viber', 'code' => 'viber_user_id', 'type' => 'employee', 'group' => 'social_networks_id', 'field_type' => 'text', 'mask' => '' ],
			['name' => 'ID Telegram', 'code' => 'telegram_user_id', 'type' => 'employee', 'group' => 'social_networks_id', 'field_type' => 'text', 'mask' => '' ],	
	
/*  company */			
			['name' => __('Телефон','usam'), 'code' => 'phone','type' => 'company', 'group' => 'communication', 'field_type' => 'phone', 'mask' => '#(###)###-##-##', 'metas' => ['registration' => 1, 'profile' => 1]],
			['name' => __('Дополнительный телефон','usam'), 'code' => 'phone2','type' => 'company', 'group' => 'communication', 'field_type' => 'mobile_phone', 'mask' => '#(###)###-##-##'],
			['name' => __('Email','usam'), 'code' => 'email','type' => 'company', 'group' => 'communication', 'field_type' => 'email', 'mask' => '', 'metas' => ['registration' => 1, 'profile' => 1]],		
			['name' => __('Пароль','usam'), 'active' => 1, 'code' => 'pass1','type' => 'company', 'group' => 'registration', 'field_type' => 'pass', 'show_staff' => 0, 'metas' => ['registration' => 1, 'profile' => 1]],			
		
		//Фактический адрес 
			['name' => __('Местоположение','usam'), 'code' => 'contactlocation','type' => 'company', 'group' => 'actual_address', 'sort' => 80, 'field_type' => 'location', 'mask' => '', 'metas' => ['registration' => 1, 'profile' => 1]],			
			['name' => __('Адрес','usam'), 'code' => 'contactaddress', 'type' => 'company', 'group' => 'actual_address',  'sort' => 90, 'field_type' => 'address', 'mask' => '', 'metas' => ['registration' => 1, 'profile' => 1]],	
			['name' => __('Почтовый индекс','usam'), 'code' => 'contactpostcode','type' => 'company', 'group' => 'actual_address', 'sort' => 100, 'field_type' => 'postcode', 'mask' => '', 'metas' => ['registration' => 1, 'profile' => 1]],	
			['name' => __('Офис','usam'), 'code' => 'contactoffice','type' => 'company', 'group' => 'actual_address', 'sort' => 100, 'field_type' => 'text', 'mask' => '', 'metas' => ['registration' => 1, 'profile' => 1]],
		//Юридический адрес 	
			['name' => __('Местоположение','usam'), 'code' => 'legallocation','type' => 'company', 'group' => 'legal_address', 'sort' => 80, 'field_type' => 'location', 'mask' => '', 'metas' => ['profile' => 1]],	
			['name' => __('Адрес','usam'), 'code' => 'legaladdress','type' => 'company', 'group' => 'legal_address',  'sort' => 90, 'field_type' => 'address', 'mask' => '', 'metas' => ['profile' => 1]],
			['name' => __('Почтовый индекс','usam'), 'code' => 'legalpostcode','type' => 'company', 'group' => 'legal_address',  'sort' => 100, 'field_type' => 'postcode', 'mask' => '', 'metas' => ['profile' => 1]],
			['name' => __('Офис','usam'), 'code' => 'legaloffice','type' => 'company', 'group' => 'legal_address',  'sort' => 100, 'field_type' => 'text', 'mask' => '', 'metas' => ['profile' => 1]],	
		//Реквизиты компании
			['name' => __('Сокращенное наименование','usam'), 'code' => 'company_name', 'type' => 'company', 'group' => 'requisites', 'sort' => 20, 'field_type' => 'text', 'mask' => '', 'metas' => ['profile' => 1]],
			['name' => __('Полное наименование','usam'), 'code' => 'full_company_name', 'type' => 'company', 'group' => 'requisites', 'sort' => 21,  'field_type' => 'text', 'mask' => '', 'metas' => ['registration' => 1, 'profile' => 1]],	
			['name' => __('ИНН','usam'), 'code' => 'inn', 'type' => 'company','group' => 'requisites',  'sort' => 22,  'field_type' => 'text', 'mask' => '', 'metas' => ['registration' => 1, 'profile' => 1]],
			['name' => __('КПП','usam'), 'code' => 'ppc','type' => 'company', 'group' => 'requisites',  'sort' => 23,  'field_type' => 'text', 'mask' => '', 'metas' => ['registration' => 1, 'profile' => 1]],	
			['name' => __('ОГРН','usam'), 'code' => 'ogrn', 'type' => 'company','group' => 'requisites',  'sort' => 24,  'field_type' => 'text', 'mask' => '', 'metas' => ['profile' => 1]],
			['name' => __('ОКВЭД','usam'), 'code' => 'okved', 'type' => 'company','group' => 'requisites',  'sort' => 25,  'field_type' => 'text', 'mask' => '', 'metas' => ['profile' => 1]],
			['name' => __('Дата регистрации','usam'), 'code' => 'date_registration', 'type' => 'company','group' => 'requisites', 'sort' => 26, 'field_type' => 'date', 'mask' => '', 'metas' => ['profile' => 1]],
			['name' => __('ОКПО','usam'), 'code' => 'okpo','type' => 'company', 'group' => 'requisites',  'sort' => 27,  'field_type' => 'text', 'mask' => '', 'metas' => ['profile' => 1]],
			['name' => __('ОКТМО','usam'), 'code' => 'oktmo', 'type' => 'company', 'group' => 'requisites',  'sort' => 28,  'field_type' => 'text', 'mask' => '', 'metas' => ['profile' => 1]],
			['name' => __('Директор','usam'), 'code' => 'gm', 'type' => 'company', 'group' => 'requisites',  'sort' => 29, 'field_type' => 'text', 'mask' => '', 'metas' => ['profile' => 1]],
			['name' => __('Гл. бухгалтер','usam'), 'code' => 'accountant', 'type' => 'company', 'group' => 'requisites',  'sort' => 30,  'field_type' => 'text', 'mask' => ''],				
			['name' => __('Сайт','usam'), 'code' => 'site', 'type' => 'company', 'group' => 'internet', 'sort' => 1,  'field_type' => 'link', 'mask' => ''],
			['name' => 'ВКонтакте', 'code' => 'vk', 'type' => 'company', 'group' => 'social_networks', 'sort' => 1,  'field_type' => 'text', 'mask' => ''],	
			['name' => 'Facebook', 'code' => 'facebook', 'type' => 'company', 'group' => 'social_networks',  'sort' => 2,  'field_type' => 'text', 'mask' => ''],
			['name' => __('Одноклассники','usam'), 'code' => 'ok', 'type' => 'company', 'group' => 'social_networks',  'sort' => 2,  'field_type' => 'text', 'mask' => ''],
			['name' => 'Facebook', 'code' => 'facebook', 'type' => 'company', 'group' => 'social_networks',  'sort' => 2,  'field_type' => 'text', 'mask' => ''],			
			['name' => 'Twitter', 'code' => 'twitter', 'type' => 'company', 'group' => 'social_networks',  'sort' => 2,  'field_type' => 'text', 'mask' => ''],			
			['name' => 'Telegram', 'code' => 'telegram', 'type' => 'company', 'group' => 'social_networks',  'sort' => 3,  'field_type' => 'text', 'mask' => ''],	
			['name' => 'Instagram', 'code' => 'instagram', 'type' => 'company', 'group' => 'social_networks',  'sort' => 4,  'field_type' => 'text', 'mask' => ''],
			['name' => 'Viber', 'code' => 'viber', 'type' => 'company', 'group' => 'social_networks',  'sort' => 5,  'field_type' => 'text', 'mask' => ''],
			['name' => 'Skype', 'code' => 'skype', 'type' => 'company', 'group' => 'social_networks',  'sort' => 6,  'field_type' => 'text', 'mask' => ''],
			['name' => 'ICQ', 'code' => 'icq', 'type' => 'company', 'group' => 'social_networks',  'sort' => 7,  'field_type' => 'text', 'mask' => ''],
			['name' => 'MSN', 'code' => 'msn', 'type' => 'company', 'group' => 'social_networks',  'sort' => 8,  'field_type' => 'text', 'mask' => ''],
			['name' => 'Jabber', 'code' => 'jabber', 'type' => 'company', 'group' => 'social_networks', 'field_type' => 'text', 'mask' => ''],				

			['name' => 'ID ВКонтакте', 'code' => 'vk_id', 'type' => 'company', 'group' => 'social_networks_id', 'field_type' => 'text', 'mask' => ''],
			['name' => 'ID Facebook', 'code' => 'facebook_id', 'type' => 'company', 'group' => 'social_networks_id', 'field_type' => 'text', 'mask' => ''],	

	//Координаты	
			['name' => __('Широта','usam'), 'code' => 'latitude', 'type' => 'company', 'group' => 'coordinates',  'field_type' => 'text', 'mask' => ''],
			['name' => __('Долгота','usam'), 'code' => 'longitude', 'type' => 'company', 'group' => 'coordinates', 'field_type' => 'text', 'mask' => ''],
			
			['name' => __('ФИО','usam'), 'code' => 'full_name','type' => 'contact', 'group' => 'registration', 'field_type' => 'text', 'show_staff' => 0, 'metas' => ['registration' => 1]],
			['name' => __('Логин','usam'), 'active' => 0, 'code' => 'log','type' => 'contact', 'group' => 'registration', 'field_type' => 'text', 'show_staff' => 0, 'metas' => ['registration' => 1]],
			['name' => __('Пароль','usam'), 'active' => 1, 'code' => 'pass1','type' => 'contact', 'group' => 'registration', 'field_type' => 'pass', 'show_staff' => 0, 'metas' => ['registration' => 1]],
			['name' => __('Повторите пароль','usam'), 'active' => 0, 'code' => 'pass2', 'type' => 'contact', 'group' => 'registration', 'field_type' => 'pass', 'show_staff' => 0, 'metas' => ['registration' => 1]],
			
			['name' => __('Согласие на обработку моих персональных данных','usam'), 'type' => 'contact', 'code' => 'consent', 'field_type' => 'one_checkbox', 'group' => 'residential_address', 'mandatory' => 1, 'active' => 1, 'metas' => ['registration' => 1, 'profile' => 1]],
		];		
		foreach ( $fields as $key => $field )
		{
			$field['sort'] = isset($field['sort'])?$field['sort']:$key+1;
			$id = usam_insert_property( $field );
			if ( $id && !empty($field['metas']) )
			{
				foreach ( $field['metas'] as $meta_key => $meta_value )
					usam_add_property_metadata($id, $meta_key, $meta_value);
			}		
		}			
	}				
	
	function controller_units_measure( )
	{ 
		$units = [
			['id' => 1, 'code' => 'thing', 'title' => __('Штука','usam'), 'short' => __('шт','usam'), 'accusative' => __('штуку','usam'), 'in' => __('в штуках','usam'), 'plural' => __('штуки','usam'), 'external_code' => '', 'international_code' => 'PCE', 'numerical' => 796],
			['id' => 2,'code' => 'meter','title' => __('Метр','usam'), 'short' => __('м','usam'), 'accusative' => __('метр','usam'), 'in' => __('в метрах','usam'), 'plural' => __('метры','usam'), 'external_code' => '', 'international_code' => '', 'numerical' => '006'],
			['id' => 3,'code' => 'meter2','title' => __('Квадратный метр','usam'), 'short' => __('кв м','usam'), 'accusative' => __('квадратный метр','usam'), 'in' => __('в квадратных метрах','usam'), 'plural' => __('Квадратные метры','usam'), 'external_code' => '', 'international_code' => 'MTK', 'numerical' => '055'],	
			['id' => 4,'code' => 'meter3','title' => __('Кубический метр','usam'), 'short' => __('куб м','usam'), 'accusative' => __('кубический метр','usam'), 'in' => __('в кубических метрах','usam'), 'plural' => __('Кубические метры','usam'), 'external_code' => '', 'international_code' => 'MTQ', 'numerical' => 113],		
			['id' => 5,'code' => 'liter','title' => __('Литр','usam'),  'short' => __('л','usam'), 'accusative' => __('литр','usam'), 'in' => __('в литрах','usam'), 'plural' => __('литры','usam'), 'external_code' => '', 'international_code' => 'LTR', 'numerical' => 112],
			['id' => 6,'code' => 'gram','title' => __('Грамм','usam'),  'short' => __('гр','usam'), 'accusative' => __('грамм','usam'), 'in' => __('в граммах','usam'), 'plural' => __('граммы','usam'), 'external_code' => '', 'international_code' => 'GRM', 'numerical' => 163],		
			['id' => 7,'code' => 'kilogram','title' => __('Килограмм','usam'),  'short' => __('кг','usam'), 'accusative' => __('килограмм','usam'), 'in' => __('в килограммах','usam'), 'plural' => __('килограммы','usam'), 'external_code' => '', 'international_code' => 'KGM', 'numerical' => 166],		
			['id' => 8,'code' => 'set','title' => __('Набор','usam'),  'short' => __('набор','usam'), 'accusative' => __('набор','usam'), 'in' => __('в наборе','usam'), 'plural' => __('наборы','usam'), 'external_code' => '', 'international_code' => 'SET', 'numerical' => 704],
			['id' => 9,'code' => 'packaging', 'title' => __('Упаковка','usam'),  'short' => __('упак','usam'), 'accusative' => __('упаковку','usam'), 'in' => __('в упаковке','usam'), 'plural' => __('упаковки','usam'), 'external_code' => '', 'international_code' => 'NMP', 'numerical' => 778],
			['id' => 10,'code' => 'box','title' => __('Коробка','usam'),  'short' => __('кор','usam'), 'accusative' => __('коробку','usam'), 'in' => __('в коробке','usam'), 'plural' => __('коробки','usam'), 'external_code' => '', 'international_code' => 'КОР', 'numerical' => 8751],
			['id' => 12,'code' => 'spool','title' => __('Бобина','usam'),  'short' => __('боб','usam'), 'accusative' => __('бобину','usam'), 'in' => __('в бобине','usam'), 'plural' => __('бобины','usam'), 'external_code' => '', 'international_code' => 'NBB', 'numerical' => 616],
			['id' => 13,'code' => 'sheet','title' => __('Лист','usam'),  'short' => __('лист','usam'), 'accusative' => __('лист','usam'), 'in' => __('в листах','usam'), 'plural' => __('листы','usam'), 'external_code' => '', 'international_code' => 'LEF', 'numerical' => 625],
			['id' => 14,'code' => 'roll','title' => __('Рулон','usam'),  'short' => __('рул','usam'), 'accusative' => __('рулон','usam'), 'in' => __('в рулонах','usam'), 'plural' => __('рулоны','usam'), 'external_code' => '', 'international_code' => 'NPL', 'numerical' => 736],
			['id' => 15,'code' => 'tutu','title' => __('Пачка','usam'),  'short' => __('пач','usam'), 'accusative' => __('пачку','usam'), 'in' => __('в пачках','usam'), 'plural' => __('пачки','usam'), 'external_code' => '', 'international_code' => '', 'numerical' => 728],
			['id' => 16,'code' => 'element','title' => __('Элемент','usam'),  'short' => __('элем','usam'), 'accusative' => __('элемент','usam'), 'in' => __('в элементах','usam'), 'plural' => __('элементы','usam'), 'external_code' => '', 'international_code' => 'NCL', 'numerical' => 745],
			['id' => 18,'code' => 'bottle','title' => __('Бутылка','usam'),  'short' => __('бут','usam'), 'accusative' => __('бутылку','usam'), 'in' => __('в бутылках','usam'), 'plural' => __('бутылки','usam'), 'external_code' => '', 'international_code' => '', 'numerical' => 868],
			['id' => 19,'code' => 'vial','title' => __('Флакон','usam'),  'short' => __('флакон','usam'), 'accusative' => __('флакон','usam'), 'in' => __('в флаконах','usam'), 'plural' => __('флаконы','usam'), 'external_code' => '', 'international_code' => '', 'numerical' => 872]
		];
		update_option('usam_units_measure', maybe_serialize($units) );
	}
	
	function controller_webform_properties( )
	{ 
		global $wpdb;	
		$wpdb->delete( usam_get_table_db('properties'), ['type' => 'webform']);
		$fields = array( 			
			['name' => __('Ваше имя','usam'), 'code' => 'firstname', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => 'firstname'],
			['name' => __('Фамилия','usam'), 'code' => 'lastname','field_type' => 'text', 'group' => 'main', 'mandatory' => 1, 'active' => 1,'connection' => 'lastname'],
			['name' => __('ФИО','usam'), 'code' => 'full_name', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1,'connection' => 'full_name'],
			['name' => __('Мобильный Телефон','usam'), 'code' => 'mobilephone', 'field_type' => 'mobile_phone', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => 'mobilephone', 'mask' => '#(###)###-##-##'],
			['name' => __('Телефон','usam'), 'code' => 'phone', 'field_type' => 'phone', 'group' => 'main', 'mandatory' => 0, 'active' => 1,'connection' => 'phone', 'mask' => '#(###)###-##-##'],
			
			['name' => __('Серия','usam'), 'code' => 'passport_series', 'group' => 'passport',  'sort' => 1,  'field_type' => 'text', 'mask' => '','connection' => 'passport_series'],
			['name' => __('Номер','usam'), 'code' => 'passport_id', 'group' => 'passport',  'sort' => 1,  'field_type' => 'text', 'mask' => '','connection' => 'passport_id'],
			['name' => __('Выдан','usam'), 'code' => 'passport_issued', 'group' => 'passport',  'sort' => 1,  'field_type' => 'text', 'mask' => '','connection' => 'passport_issued'],
			['name' => __('Дата выдачи','usam'), 'code' => 'date_passport', 'group' => 'passport',  'sort' => 1,  'field_type' => 'date', 'mask' => '','connection' => 'date_passport'],
			['name' => __('Скан','usam'), 'code' => 'scan_passport', 'group' => 'passport',  'sort' => 1,  'field_type' => 'file', 'mask' => '' ,'connection' => 'scan_passport'],
			['name' => __('Снилс','usam'), 'code' => 'snills', 'group' => 'document', 'sort' => 1, 'field_type' => 'text', 'mask' => '###-###-### ##' ,'connection' => 'snills'],
			['name' => __('Email','usam'), 'code' => 'email', 'field_type' => 'email', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => 'email'],		
			['name' => __('Компания','usam'), 'code' => 'company', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => 'company-full_company_name'],
			['name' => __('Сcылка','usam'), 'code' => 'url', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
			['name' => __('Откуда узнали','usam'), 'code' => 'source', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
			['name' => __('Тема обращения','usam'), 'code' => 'topic', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
			['name' => __('Местоположение','usam'), 'code' => 'location', 'field_type' => 'location', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => 'location'],		
			['name' => __('Почтовый индекс','usam'), 'code' => 'postcode', 'field_type' => 'postcode', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => 'postcode'],
			['name' => __('Адрес','usam'), 'code' => 'address', 'field_type' => 'address', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => 'address'],
			['name' => __('Согласие на обработку моих персональных данных','usam'), 'code' => 'consent', 'field_type' => 'one_checkbox', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => 'consent'],
			['name' => __('Файлы','usam'), 'code' => 'files', 'field_type' => 'files', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
			['name' => __('Сообщение','usam'), 'code' => 'message', 'field_type' => 'textarea', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
			
			['name' => __('Название отзыва','usam'), 'code' => 'review_title', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
			['name' => __('Отзыв','usam'), 'code' => 'review', 'field_type' => 'textarea', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => ''],
			['name' => __('Ваша оценка','usam'), 'code' => 'rating', 'field_type' => 'rating', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => ''],
			['name' => __('Я согласен c обработкой персональных данных и публикацией отзыва на сайте','usam'), 'code' => 'consent_publication_review', 'field_type' => 'one_checkbox', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => 'consent'],			
		); 
		foreach ( $fields as $key => $field )
		{
			$field['sort'] = $key+1;
			$field['type'] = 'webform';
			$id = usam_insert_property( $field );			
			if ( !empty($field['connection']) )
				usam_update_property_metadata($id, 'connection', $field['connection'] );
		}	
	}
	
	// Группы свойств
	function controller_properties_groups()
	{		
		global $wpdb;	
		
		$this->truncase( usam_get_table_db('property_groups') );
		$groups = [
			['name' => __('Связаться','usam'), 'type' => 'company', 'code' => 'communication', 'sort' => 9],	
			['name' => __('Фактический адрес','usam'), 'type' => 'company', 'code' => 'actual_address', 'sort' => 10],
			['name' => __('Юридический адрес','usam'), 'type' => 'company', 'code' => 'legal_address', 'sort' => 20],
			['name' => __('Реквизиты компании','usam'), 'type' => 'company', 'code' => 'requisites', 'sort' => 30],							
			['name' => __('Площадки','usam'), 'type' => 'company', 'code' => 'internet', 'sort' => 40],
			['name' => __('Социальные сети','usam'), 'type' => 'company', 'code' => 'social_networks', 'sort' => 50],	
			['name' => __('ID в социальных сетях','usam'), 'type' => 'company', 'code' => 'social_networks_id', 'system' => 1, 'sort' => 50],
			['name' => __('Координаты','usam'), 'type' => 'company', 'code' => 'coordinates', 'system' => 1, 'sort' => 60],					
			
			['name' => __('Обо мне','usam'), 'type' => 'contact', 'code' => 'info', 'sort' => 0],	
			['name' => __('Связаться','usam'), 'type' => 'contact', 'code' => 'communication', 'sort' => 9],
			['name' => __('Адрес проживания','usam'), 'type' => 'contact', 'code' => 'residential_address', 'sort' => 10],	
		//	['name' => __('Паспорт','usam'), 'type' => 'contact', 'code' => 'passport', 'sort' => 11],	
		//	['name' => __('Документы','usam'), 'type' => 'contact', 'code' => 'document', 'sort' => 12],										
			['name' => __('Площадки','usam'), 'type' => 'contact', 'code' => 'internet', 'sort' => 20],
			['name' => __('Социальные сети','usam'), 'type' => 'contact', 'code' => 'social_networks', 'sort' => 30],	
			['name' => __('ID в социальных сетях','usam'), 'type' => 'contact', 'code' => 'social_networks_id', 'system' => 1, 'sort' => 50],			
			['name' => __('Координаты','usam'), 'type' => 'contact', 'code' => 'coordinates', 'system' => 1, 'sort' => 40],	
			['name' => __('Регистрационные данные','usam'), 'type' => 'contact', 'code' => 'registration', 'sort' => 44],			
			
			['name' => __('Связаться','usam'), 'type' => 'employee', 'code' => 'communication', 'sort' => 9],	
			['name' => __('Адрес проживания','usam'), 'type' => 'employee', 'code' => 'residential_address', 'sort' => 10],	
			['name' => __('Паспорт','usam'), 'type' => 'employee', 'code' => 'passport', 'sort' => 11],	
			['name' => __('Документы','usam'), 'type' => 'employee', 'code' => 'document', 'sort' => 12],	
			['name' => __('ID в социальных сетях','usam'), 'type' => 'employee', 'code' => 'social_networks_id', 'system' => 1, 'sort' => 50],			
			['name' => __('Координаты','usam'), 'type' => 'employee', 'code' => 'coordinates', 'system' => 1, 'sort' => 40],	
			['name' => __('Регистрационные данные','usam'), 'type' => 'employee', 'code' => 'registration', 'sort' => 44],			
			
			['name' => __('Личные данные','usam'), 'type' => 'order', 'type_payer' => 1, 'code' => 'billing', 'sort' => 10],
			['name' => __('Данные для доставки','usam'), 'type' => 'order', 'type_payer' => 1, 'code' => 'shipping', 'sort' => 20],
			['name' => __('Данные компании','usam'), 'type' => 'order', 'type_payer' => 2, 'code' => 'company', 'sort' => 30],
			['name' => __('Контактная информация','usam'), 'type' => 'order', 'type_payer' => 2, 'code' => 'contact_information', 'sort' => 40],
			['name' => __('Информация для доставки','usam'), 'type' => 'order', 'type_payer' => 2, 'code' => 'company_shipping', 'sort' => 40],
			
			['name' => __('Основное','usam'), 'type' => 'webform', 'code' => 'main', 'sort' => 11],	
			['name' => __('Паспорт','usam'), 'type' => 'webform', 'code' => 'passport', 'sort' => 12],
			['name' => __('Документы','usam'), 'type' => 'webform', 'code' => 'document', 'sort' => 12],
		];
		foreach ( $groups as $group )
		{			
			if ( isset($group['type_payer']) )
			{
				$type_payer = $group['type_payer'];
				unset($group['type_payer']);
			}
			else
				$type_payer  = 0;
			if ( $wpdb->insert( usam_get_table_db('property_groups'), $group ) )
			{
				if ( $type_payer )
					usam_add_property_group_metadata($wpdb->insert_id, 'type_payer', $type_payer);			
			}
		}
	}

	// Типы местоположений
	function controller_location_type( )
	{
		global $wpdb;	
		
		$this->truncase( USAM_TABLE_LOCATION_TYPE );
		$type_location = [
			array('name' => __('Страна','usam'), 'code' => 'country', 'sort' => 70, 'level'=> 10 ),
			array('name' => __('Округ','usam'), 'code' => 'district', 'sort' => 60, 'level'=> 20 ),
			array('name' => __('Область','usam'), 'code' => 'region', 'sort' => 50, 'level'=> 30 ),
			array('name' => __('Район','usam'), 'code' => 'subregion', 'sort' => 40, 'level'=> 40 ),
			array('name' => __('Город','usam'), 'code' => 'city', 'sort' => 10, 'level'=> 50 ),								
			array('name' => __('Село','usam'), 'code' => 'village', 'sort' => 20, 'level'=> 60 ),
			array('name' => __('Городской район','usam'), 'code' => 'urban_area', 'sort' => 100, 'level'=> 70 ),
			array('name' => __('Улица','usam'), 'code' => 'street', 'sort' => 30, 'level'=> 80 ),
		];
		foreach ( $type_location as $value )
		{
			$wpdb->query("INSERT INTO `".USAM_TABLE_LOCATION_TYPE."` (`name`,`code`,`sort`,`level`) VALUES ('".$value['name']."', '".$value['code']."', '".$value['sort']."', '".$value['level']."')");
		}		
	}
		
	// Веб-формы
	function controller_webforms()
	{		
		require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
		$this->truncase( USAM_TABLE_WEBFORMS );
		$webforms = [
			['title' => __('Быстрая покупка', 'usam'), 'description' => __('Наш сотрудник Вам позвонит в самое ближайшее время и уточнить способ доставки товара', 'usam'), 'active' => 1, 'code' => 'quick_purchase', 'action' => 'order', 'template' => 'product', 'settings' => ['button_name' => __('Быстрая покупка', 'usam'), 'fields' => [
				'name' => ['require' => 1],
				'mobilephone' => ['require' => 1],		
				'email' => ['require' => 1],
			]]],
			['title' => __('Есть дешевле?', 'usam'), 'description' => __('Этот продукт продается в другом месте по более низкой цене? Пожалуйста, заполните форму ниже и мы будем работать с нашими поставщиками для того чтобы представить вам более выгодную цену. Если заявленный товар идентичен Вашему товару и найдено более выгодное предложение, то мы постараемся вам сделать скидку на товар.', 'usam'), 'active' => 1, 'code' => 'price_comparison', 'template' => 'product', 'settings' => ['button_name' => __('Есть дешевле?', 'usam'), 'fields' => [
				'name' => ['require' => 1], 'url' => ['require' => 1],	'email' => ['require' => 1],
			] ]],
			['title' => __('Вопрос о товаре', 'usam'), 'description' => __('Отправьте ваш вопрос в Центр поддержки клиентов. Наши специалисты ответят вам в самое ближайшее время.<br>Вся информация будет просмотрена в течение 48 часов (пн-пт).', 'usam'), 'active' => 1, 'code' => 'product_info', 'template' => 'product', 'settings' => ['button_name' => __('Вопрос о товаре', 'usam'), 'fields' => ['name' => ['require' => 1], 'email' => ['require' => 1], 'message' => ['require' => 1]]]],
			['title' => __('Сообщить об ошибке', 'usam'), 'description' => '', 'active' => 1, 'code' => 'product_error', 'template' => 'product', 
			'settings' => ['button_name' => __('Сообщить об ошибке', 'usam'), 'fields' => ['name' => ['require' => 1], 'email' => ['require' => 1], 'message' => ['require' => 1] ]]],
			['title' => __('Сообщить о поступлении товара', 'usam'), 'description' => '', 'active' => 1, 'code' => 'product-receipt', 'template' => 'product', 'settings' => ['button_name' => __('Сообщить о поступлении', 'usam'), 'fields' => ['name' => ['require' => 1], 'email' => ['require' => 1], 'message' => ['require' => 1] ]]],
			['title' => __('Заказать товар', 'usam'), 'description' => __('Этот товар есть на других наших складах. Оставьте свой заказ и мы привезем его специально для вас. Подробности о вариантах получения и способах оплаты вам сообщит наш менеджер.', 'usam'), 'active' => 1, 'code' => 'buy_product', 'template' => 'product', 'settings' => ['button_name' => __('Заказать товар', 'usam'), 'fields' => ['name' => ['require' => 1], 'email' => ['require' => 1], 'message' => ['require' => 0] ]]],	
			['title' => __('Обратный звонок', 'usam'), 'active' => 1, 'code' => 'back_call', 'template' => 'contact-form', 'button_name' => __('Обратный звонок', 'usam'), 'settings' => ['fields' => ['mobilephone' => ['require' => 1] ]]],
			['title' => __('Отзыв', 'usam'), 'active' => 1, 'code' => 'review', 'action' => 'review', 'template' => 'contact-form', 'settings' => ['button_name' => __('Оставить отзыв', 'usam'), 'fields' => [ 
				'review_title' => ['require' => 1], 'name' => ['require' => 1], 'mobilephone' => ['require' => 0], 'email' => ['require' => 0], 'rating' => ['require' => 1], 'review' => ['require' => 1], 'consent_publication_review' => ['require' => 1]
			]]],
			['title' => __('Отзыв о заказе', 'usam'), 'active' => 1, 'code' => 'review-order', 'action' => 'review', 'template' => 'contact-form', 'settings' => ['button_name' => __('Добавить отзыв', 'usam'), 'fields' => [ 
				'rating' => ['require' => 1], 'review' => ['require' => 1]],
			]],
		];
		foreach ( $webforms as $value )
		{
			usam_insert_webform( $value );
		}
	}
	
	//Заполняет и обновляет данные со списком валют
	function controller_currency()
	{		
		$this->truncase( USAM_TABLE_CURRENCY );
		require_once($this->db_path . "currency_list.php");		
		foreach ( $list as $key => $currency )
			usam_insert_currency( $currency );		
	}
	
	//Заполняет и обновляет данные со списком стран
	function controller_country()
	{			
		$this->truncase( USAM_TABLE_COUNTRY );
		require_once($this->db_path . "country_list.php");
		foreach ( $list as $key => $country )
		{
			usam_insert_country( $country );					
		}			
		usam_link_locations_and_countries( );
	}
	
	function controller_search_engine_regions()
	{
		$table = ['file_path' => "search_engine_regions.csv", 'column' => ['id', 'location_id', 'active', 'code', 'name', 'search_engine', 'sort']];
		$this->load_tables( USAM_TABLE_SEARCH_ENGINE_REGIONS, $table );		
	}
		
	function controller_location(  )
	{
		if ( !usam_is_multisite() || is_main_site() )
			usam_install_locations( ['RU'] );			
	}
	
	function controller_events_status( ) 
	{
		$events = usam_get_events_types();
		$types = array_keys($events);
		$this->controller_object_status( $types );
	}	
	
	function controller_documents_status( ) 
	{
		$documents = usam_get_details_documents();
		unset($documents['order']);
		$types = array_keys($documents);
		$this->controller_object_status( $types );
	}
	
	function controller_object_status( $types = [] ) 
	{ 
		if ( !usam_is_multisite() || is_main_site() )
		{
			global $wpdb;
			if ( !$types )
				$this->truncase( USAM_TABLE_OBJECT_STATUSES );
			else
				$wpdb->query("DELETE FROM ".USAM_TABLE_OBJECT_STATUSES." WHERE type IN ('".implode("','", (array)$types)."')");		
			$object_status = [
				['internalname' => 'incomplete_sale', 'name' => __('Незаконченная продажа', 'usam'), 'short_name' => __('Незаконченные', 'usam'), 'pay' => 1, 'visibility' => 0, 'close'  => 0, 'type' => 'order'],
				['internalname' => 'received', 'name' => __('Ваш заказ принят!', 'usam'), 'short_name' => __('Новые', 'usam'), 'color' => '#99ccff', 'description' => '', 'pay' => 1, 'visibility' => 0, 'close' => 0, 'type' => 'order'],
				['internalname' => 'job_dispatched', 'name' => __('Идет обработка', 'usam'), 'short_name' => __('В обработке', 'usam'), 'color' => '#ffe502', 'pay' => 1, 'visibility' => 1, 'close' => 0, 'type' => 'order'],			
				['internalname' => 'waiting_payment', 'name' => __('Ожидание оплаты', 'usam'), 'short_name' => __('Ожидание оплаты', 'usam'), 'pay' => 1, 'visibility' => 1, 'close' => 0, 'type' => 'order'],			
				['internalname' => 'accepted_payment', 'name' => __('Оплата принята', 'usam'), 'short_name' => __('Оплаченные', 'usam'), 'color' => '#ffcc66', 'visibility' => 0, 'close'  => 0, 'type' => 'order'],
				['internalname' => 'customer_waiting', 'name' => __('Готов к выдаче', 'usam'), 'short_name' => __('Готов к выдаче', 'usam'), 'description' => __('Заказ находится в пункте выдачи и ожидает клиента','usam'), 'color' => '#ffcccc',	'pay' => 0,	'visibility' => 1, 'close' => 0,'type' => 'order'],
				['internalname' => 'preparing_delivery', 'short_name' => __('Подготовка доставки', 'usam'), 'name' => __('Подготовка доставки', 'usam'), 'description' => __('Заказ подготавливается к передаче курьеру или транспортной компании для отправки', 'usam'), 'visibility' => 1, 'close'  => 0, 'type' => 'order'],
				['internalname' => 'sending', 'short_name' => __('Отправленные', 'usam'), 'name' => __('Заказ отправлен', 'usam'), 'description'  => __('Ваш заказ отправлен по указанному адресу', 'usam'), 'visibility' => 1, 'close' => 0, 'type' => 'order'],
				['internalname' => 'courier', 'short_name' => __('Передан курьеру', 'usam'), 'name' => __('Передан курьеру', 'usam'), 'description'  => __('Ваш заказ передан курьеру', 'usam'), 'visibility' => 1, 'close'  => 0, 'type' => 'order'],		
				['internalname' => 'canceled', 'name' => __('Заказ отменен', 'usam'), 'short_name' => __('Отмененные', 'usam'),	'color' => '#d5d8db', 'close' => 1, 'visibility' => 1, 'type' => 'order'],
				['internalname' => 'credit_approved', 'name' => __('Кредит одобрен', 'usam'), 'short_name' => __('Кредит одобрен', 'usam'),	'color' => '#99ccff', 'description' => '', 'pay'  => 0,	'visibility' => 0, 'close'  => 1, 'type' => 'order'],	
				['internalname' => 'credit_rejected', 'name' => __('Кредит не одобрен', 'usam'), 'short_name' => __('Кредит не одобрен', 'usam'), 'color' => '#99ccff',	'pay' => 0, 'visibility' => 0, 'close' => 0, 'type' => 'order',		],				
				['internalname' => 'bank_review', 'name' => __('На рассмотрении у банка', 'usam'), 'short_name' => __('На рассмотрении', 'usam'), 'color' => '#99ccff',	'pay'  => 0, 'visibility' => 0, 'close' => 0, 'type' => 'order'],
				['internalname' => 'not_picked', 'short_name' => __('Не забрал', 'usam'), 'name' => __('Клиент не забрал', 'usam'),	'description' => __('Клиент не забрал заказ со склада', 'usam'), 'visibility' => 1, 'close' => 1, 'type' => 'order'],
				['internalname' => 'closed', 'name' => __('Заказ закрыт', 'usam'), 'short_name' => __('Закрытые', 'usam'), 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'visibility' => 1, 'close' => 1, 'type' => 'order'],	
				['internalname' => 'delete', 'name' => __('Заказ удален', 'usam'), 'short_name' => __('Удаленные', 'usam'), 'color' => '', 'visibility' => 0, 'close'  => 1, 'type' => 'order'],	
		// Документы оплаты		
				['internalname' => '1', 'name' => __('Не оплачено', 'usam'), 'short_name' => __('Не оплаченные', 'usam'), 'close' => 0, 'type' => 'payment', 'visibility' => 1, 'color' => '#ff9393', 'text_color' => '#a42727'],
				['internalname' => '2', 'name' => __('Отклонено', 'usam'), 'short_name' => __('Отклоненные', 'usam'), 'close' => 1, 'type' => 'payment', 'visibility' => 0, 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],
				['internalname' => '3', 'name' => __('Оплачено', 'usam'), 'short_name' => __('Оплаченные', 'usam'), 'close' => 1, 'type' => 'payment', 'visibility' => 1, 'color' => '#e6f0c0', 'text_color' => '#4b711d'],
				['internalname' => '4', 'name' => __('Платеж возвращен', 'usam'), 'short_name' => __('Возвращенные', 'usam'), 'close' => 1, 'type' => 'payment', 'visibility' => 1, 'color' => '#7db1c9', 'text_color' => '#2b5568'],
				['internalname' => '5', 'name' => __('Ошибка оплаты', 'usam'), 'short_name' => __('Ошибка оплаты', 'usam'), 'close' => 1, 'type' => 'payment', 'visibility' => 0, 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],
				['internalname' => '6', 'name' => __('В ожидании', 'usam'), 'short_name' => __('В ожидании', 'usam'), 'close' => 0, 'type' => 'payment', 'visibility' => 0, 'color' => '#faeac0', 'text_color' => '#967012'],
				['internalname' => '7', 'name' => __('В обработке', 'usam'), 'short_name' => __('В обработке', 'usam'), 'close' => 0, 'type' => 'payment', 'visibility' => 0, 'color' => '#faeac0', 'text_color' => '#967012'],
				['internalname' => '8', 'name' => __('Денежные средства зарезервированы', 'usam'), 'short_name' => __('Зарезервированные', 'usam'), 'close' => 0, 'type' => 'payment', 'visibility' => 0, 'color' => '#faeac0', 'text_color' => '#967012'],	

	// Документы отгрузки		
				['internalname' => 'pending', 'name' => __('Ожидает обработку', 'usam'), 'short_name' => __('Ожидает обработку', 'usam'), 'close' => 0, 'type' => 'shipped', 'visibility' => 1, 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],
				['internalname' => 'packaging', 'name' => __('Комплектация', 'usam'), 'short_name' => __('Комплектация', 'usam'), 'close' => 0, 'type' => 'shipped', 'visibility' => 1, 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],
				['internalname' => 'arrival', 'name' => __('Ожидаем приход товара', 'usam'), 'short_name' => __('Ожидаем приход', 'usam'), 'close' => 0, 'type' => 'shipped', 'visibility' => 1, 'color' => '#faeac0', 'text_color' => '#967012'],
				['internalname' => 'expect_tc', 'name' => __('Ожидание забора', 'usam'), 'short_name' => __('Ожидание забора', 'usam'), 'close' => 0, 'type' => 'shipped', 'visibility' => 1, 'color' => '#faeac0', 'text_color' => '#967012'],
				['internalname' => 'collected', 'name' => __('Ожидает вручения', 'usam'), 'short_name' => __('Ожидает вручения', 'usam'), 'close' => 0, 'type' => 'shipped', 'visibility' => 1, 'color' => '#faeac0', 'text_color' => '#967012'],
				['internalname' => 'referred', 'name' => __('Передан в службу доставки', 'usam'), 'short_name' => __('Передан в службу доставки', 'usam'), 'close' => 0, 'type' => 'shipped', 'visibility' => 1, 'color' => '#7db1c9', 'text_color' => '#2b5568', 'text_color' => '#2b5568'],
				['internalname' => 'courier', 'name' => __('Передан курьеру', 'usam'), 'short_name' => __('Передан курьеру', 'usam'), 'close' => 0, 'type' => 'shipped', 'visibility' => 1, 'color' => '#7db1c9', 'text_color' => '#2b5568', 'text_color' => '#2b5568'],
				['internalname' => 'shipped', 'name' => __('Отгружена', 'usam'), 'short_name' => __('Отгруженные', 'usam'), 'close' => 1, 'type' => 'shipped', 'visibility' => 1, 'color' => '#e6f0c0', 'text_color' => '#4b711d'],		
				['internalname' => 'canceled', 'name' => __('Отменен', 'usam'), 'short_name' => __('Отменен', 'usam'), 'close' => 1, 'type' => 'shipped', 'visibility' => 1, 'color' => '#ff9393', 'text_color' => '#a42727'],		
				['internalname' => 'delivery_problem', 'name' => __('Проблема с доставкой', 'usam'), 'short_name' => __('Проблема с доставкой', 'usam'), 'close' => 0, 'type' => 'shipped', 'visibility' => 1, 'color' => '#ff9393', 'text_color' => '#a42727'],				
		//Лиды
				['internalname' => 'not_processed', 'name' => __('Не обработан', 'usam'), 'short_name' => __('Не обработанные', 'usam'), 'close' => 0, 'type' => 'lead', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],
				['internalname' => 'in_work', 'name' => __('В работе', 'usam'), 'short_name' => __('В работе', 'usam'), 'close' => 0, 'type' => 'lead'],
				['internalname' => 'sent_offer', 'name' => __('Отправлено предложение', 'usam'), 'short_name' => __('Отправлено предложение', 'usam'), 'close' => 0, 'type' => 'lead'],
				['internalname' => 'order', 'name' => __('Завершить и создать заказ', 'usam'), 'short_name' => __('Завершены', 'usam'), 'close' => 1, 'type' => 'lead', 'color' => '#e6f0c0', 'text_color' => '#4b711d'],
				['internalname' => 'poor_quality', 'name' => __('Некачественный', 'usam'), 'short_name' => __('Некачественные', 'usam'), 'close' => 1, 'type' => 'lead', 'color' => '#ff9393', 'text_color' => '#a42727'],	
				//* CRM *//			
				['internalname' => 'not_started', 'name' => __('Запланирована', 'usam'), 'short_name' => __('Запланированы', 'usam'), 'close'  => 0, 'type' => 'task', 'visibility' => 0],		
				['internalname' => 'started', 'name' => __('Выполняется', 'usam'), 'short_name' => __('Выполняются', 'usam'), 'close'  => 0, 'type' => 'task', 'visibility' => 1],	
				['internalname' => 'stopped', 'name' => __('Приостановлено', 'usam'), 'short_name' => __('Приостановленные', 'usam'), 'close'  => 0, 'type' => 'task', 'visibility' => 1],			
				['internalname' => 'completed', 'name' => __('Завершена', 'usam'), 'short_name' => __('Завершены', 'usam'), 'close'  => 1, 'type' => 'task', 'visibility' => 1],		
				['internalname' => 'canceled', 'name' => __('Отменена', 'usam'), 'short_name' => __('Отмененные', 'usam'), 'close'  => 1, 'type' => 'task', 'visibility' => 1],	
				['internalname' => 'control', 'name' => __('На проверке', 'usam'), 'short_name' => __('На проверке', 'usam'), 'close'  => 0, 'type' => 'task', 'visibility' => 0],	
				['internalname' => 'returned', 'name' => __('Возвращена', 'usam'), 'short_name' => __('Возвращенные', 'usam'), 'close'  => 0, 'type' => 'task', 'visibility' => 0],
				['internalname' => 'controlled', 'name' => __('Проконтролирована', 'usam'), 'short_name' => __('Проконтролированные', 'usam'), 'close' => 1,'type' => 'task', 'visibility' => 0],			
				
				['internalname' => 'not_started', 'name' => __('Запланировано', 'usam'), 'short_name' => __('Запланированы', 'usam'), 'close'  => 0, 'type' => 'affair'],	
				['internalname' => 'started', 'name' => __('Выполняется', 'usam'), 'short_name' => __('Выполняются', 'usam'), 'close'  => 0, 'type' => 'affair'],	
				['internalname' => 'completed', 'name' => __('Завершена', 'usam'), 'short_name' => __('Завершены', 'usam'), 'close'  => 1, 'type' => 'affair'],			
				['internalname' => 'canceled', 'name' => __('Отменена', 'usam'), 'short_name' => __('Отмененные', 'usam'), 'close'  => 1, 'type' => 'affair'],		
				['internalname' => 'not_started', 'name' => __('Запланирован', 'usam'), 'short_name' => __('Запланированы', 'usam'), 'close'  => 0, 'type' => 'call'],	
				['internalname' => 'started', 'name' => __('Выполняется', 'usam'), 'short_name' => __('Выполняются', 'usam'), 'close'  => 0, 'type' => 'call'],		
				['internalname' => 'completed', 'name' => __('Завершен', 'usam'), 'short_name' => __('Завершены', 'usam'), 'close' => 1, 'type' => 'call'],
				['internalname' => 'canceled', 'name' => __('Отменена', 'usam'), 'short_name' => __('Отмененные', 'usam'), 'close' => 1, 'type' => 'call'],	
				['internalname' => 'not_started', 'name' => __('Запланировано', 'usam'), 'short_name' => __('Запланированы', 'usam'), 'close'  => 0, 'type' => 'contacting'],
				['internalname' => 'started', 'name' => __('Выполняется', 'usam'), 'short_name' => __('Выполняются', 'usam'), 'close'  => 0, 'type' => 'contacting'],
				['internalname' => 'completed', 'name' => __('Завершена', 'usam'), 'short_name' => __('Завершены', 'usam'), 'close'  => 1, 'type' => 'contacting'],	
				['internalname' => 'canceled', 'name' => __('Отменена', 'usam'), 'short_name' => __('Отмененные', 'usam'), 'close'  => 1, 'type' => 'contacting'],
				['internalname' => 'not_started', 'name' => __('Запланирована', 'usam'), 'short_name' => __('Запланированы', 'usam'), 'close'  => 0, 'type' => 'meeting'],	
				['internalname' => 'started', 'name' => __('Проходит', 'usam'), 'short_name' => __('Проходят', 'usam'), 'close'  => 0, 'type' => 'meeting'],			
				['internalname' => 'completed', 'name' => __('Завершена', 'usam'), 'short_name' => __('Завершены', 'usam'), 'close'  => 1, 'type' => 'meeting'],	
				['internalname' => 'canceled', 'name' => __('Отменена', 'usam'), 'short_name' => __('Отмененные', 'usam'), 'close'  => 1, 'type' => 'meeting'],				
				['internalname' => 'not_started', 'name' => __('Запланирована', 'usam'), 'short_name' => __('Запланированы', 'usam'), 'close'  => 0, 'type' => 'convocation'],
				['internalname' => 'started', 'name' => __('Проходит', 'usam'), 'short_name' => __('Проходят', 'usam'), 'close'  => 0, 'type' => 'convocation'],
				['internalname' => 'completed', 'name' => __('Завершена', 'usam'), 'short_name' => __('Завершены', 'usam'), 'close'  => 1, 'type' => 'convocation'],
				['internalname' => 'canceled', 'name' => __('Отменена', 'usam'), 'short_name' => __('Отмененные', 'usam'), 'close'  => 1, 'type' => 'convocation'],		
				['internalname' => 'not_started', 'name' => __('Запланирован', 'usam'), 'short_name' => __('Запланированы', 'usam'), 'close'  => 0, 'type' => 'project'],
				['internalname' => 'started', 'name' => __('В работе', 'usam'), 'short_name' => __('Выполняются', 'usam'), 'close'  => 0, 'type' => 'project'],
				['internalname' => 'completed', 'name' => __('Завершен', 'usam'), 'short_name' => __('Завершены', 'usam'), 'close'  => 1, 'type' => 'project'],
				['internalname' => 'canceled', 'name' => __('Отменен', 'usam'), 'short_name' => __('Отмененные', 'usam'), 'close'  => 1, 'type' => 'project'],		
				['internalname' => 'not_started', 'name' => __('Запланирован', 'usam'), 'short_name' => __('Запланированы', 'usam'), 'close'  => 0, 'type' => 'closed_project'],
				['internalname' => 'started', 'name' => __('В работе', 'usam'), 'short_name' => __('Выполняются', 'usam'), 'close'  => 0, 'type' => 'closed_project'],
				['internalname' => 'completed', 'name' => __('Завершен', 'usam'), 'short_name' => __('Завершены', 'usam'), 'close'  => 1, 'type' => 'closed_project'],
				['internalname' => 'canceled', 'name' => __('Отменен', 'usam'), 'short_name' => __('Отмененные', 'usam'), 'close'  => 1, 'type' => 'closed_project'],
				 				
				['internalname' => 'temporary', 'name' => __('Временный', 'usam'), 'short_name' => __('Временные', 'usam'), 'visibility' => 0, 'close'  => 0, 'type' => 'contact'],
				['internalname' => 'blocked', 'name' => __('Блокированный', 'usam'), 'short_name' => __('Блокированные', 'usam'), 'color' => '#d9d9d9', 'text_color' => '#6f6b6b', 'type' => 'contact'],	
				['internalname' => 'flagged', 'name' => __('Не перспективный', 'usam'), 'short_name' => __('Не перспективные', 'usam'), 'color' => '#ff9393', 'text_color' => '#a42727', 'type' => 'contact'],	
				['internalname' => 'customer', 'name' => __('Нейтральный', 'usam'), 'short_name' => __('Нейтральные', 'usam'), 'color' => '#7db1c9', 'text_color' => '#2b5568', 'text_color' => '#2b5568', 'type' => 'contact'],
				['internalname' => 'prospect', 'name' => __('Перспективный', 'usam'), 'short_name' => __('Перспективные', 'usam'), 'color' => '#faeac0', 'text_color' => '#967012', 'type' => 'contact'],	
				['internalname' => 'favourite', 'name' => __('Любимый', 'usam'), 'short_name' => __('Любимые', 'usam'), 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'type' => 'contact'],	
		
				['internalname' => 'blocked', 'name' => __('Блокированный', 'usam'), 'short_name' => __('Блокированные', 'usam'), 'color' => '#d9d9d9', 'text_color' => '#6f6b6b', 'type' => 'company'],	
				['internalname' => 'flagged', 'name' => __('Не перспективный', 'usam'), 'short_name' => __('Не перспективные', 'usam'), 'color' => '#ff9393', 'text_color' => '#a42727', 'type' => 'company'],	
				['internalname' => 'customer', 'name' => __('Нейтральный', 'usam'), 'short_name' => __('Нейтральные', 'usam'), 'color' => '#7db1c9', 'text_color' => '#2b5568', 'text_color' => '#2b5568', 'type' => 'company'],	
				['internalname' => 'prospect', 'name' => __('Перспективный', 'usam'), 'short_name' => __('Перспективные', 'usam'), 'color' => '#faeac0', 'text_color' => '#967012', 'type' => 'company'],	
				['internalname' => 'favourite', 'name' => __('Любимый', 'usam'), 'short_name' => __('Любимые', 'usam'), 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'type' => 'company'],		
			
				['internalname' => 'works', 'name' => __('Работает', 'usam'), 'short_name' => __('Работают', 'usam'), 'type' => 'employee', 'color' => '#e6f0c0', 'text_color' => '#4b711d'],	
				['internalname' => 'on_holiday', 'name' => __('В отпуске', 'usam'), 'short_name' => __('В отпуске', 'usam'), 'type' => 'employee', 'color' => '#7db1c9', 'text_color' => '#2b5568', 'text_color' => '#2b5568'],	
				['internalname' => 'hurt', 'name' => __('Болеет', 'usam'), 'short_name' => __('Болеют', 'usam'), 'type' => 'employee', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'output', 'name' => __('Выходной', 'usam'), 'short_name' => __('Выходной', 'usam'), 'type' => 'employee', 'color' => '#faeac0', 'text_color' => '#967012'],	
				['internalname' => 'business_trip', 'name' => __('В командировке', 'usam'), 'short_name' => __('В командировке', 'usam'), 'type' => 'employee', 'color' => '#ff9393', 'text_color' => '#a42727'],
				
				['internalname' => 'works', 'name' => __('Работаю', 'usam'), 'short_name' => __('Работают', 'usam'), 'type' => 'courier', 'color' => '#e6f0c0', 'text_color' => '#4b711d'],
				['internalname' => 'not_working', 'name' => __('Не работаю', 'usam'), 'short_name' => __('Не работают', 'usam'), 'type' => 'courier', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],
				['internalname' => 'warehouse', 'name' => __('На склад', 'usam'), 'short_name' => __('На склад', 'usam'), 'type' => 'courier', 'color' => '#7db1c9', 'text_color' => '#2b5568', 'text_color' => '#2b5568'],	
				['internalname' => 'service', 'name' => __('В сервис', 'usam'), 'short_name' => __('В сервис', 'usam'), 'type' => 'courier', 'color' => '#ff9393', 'text_color' => '#a42727'],	
				['internalname' => 'lunch', 'name' => __('На обед', 'usam'), 'short_name' => __('На обед', 'usam'), 'type' => 'courier', 'color' => '#faeac0', 'text_color' => '#967012'],	
												
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'suggestion', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'sent', 'name' => __('Отправлен клиенту', 'usam'), 'short_name' => __('Отправленные клиенту', 'usam'), 'type' => 'suggestion', 'color' => '#faeac0', 'text_color' => '#967012'],	
				['internalname' => 'received', 'name' => __('Рассматривается клиентом', 'usam'), 'short_name' => __('Рассматривается клиентом', 'usam'), 'type' => 'suggestion', 'color' => '#faeac0', 'text_color' => '#967012'],	
				['internalname' => 'approved', 'name' => __('Утверждено', 'usam'), 'short_name' => __('Утвержденные', 'usam'), 'type' => 'suggestion', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				['internalname' => 'unanswered', 'name' => __('Ответ не получен', 'usam'), 'short_name' => __('Ответ не получен', 'usam'), 'type' => 'suggestion', 'color' => '#ff9393', 'text_color' => '#a42727'],	
				['internalname' => 'declained', 'name' => __('Отклонено', 'usam'), 'short_name' => __('Отклоненные', 'usam'), 'type' => 'suggestion', 'color' => '#ff9393', 'text_color' => '#a42727', 'close'  => 1],	
												
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'decree', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'agreement', 'name' => __('На согласовании', 'usam'), 'short_name' => __('На согласовании', 'usam'), 'type' => 'decree', 'color' => '#faeac0', 'text_color' => '#967012'],	
				['internalname' => 'subscribe', 'name' => __('Подписан', 'usam'), 'short_name' => __('Подписанные', 'usam'), 'type' => 'decree', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				['internalname' => 'declained', 'name' => __('Отклонено', 'usam'), 'short_name' => __('Отклоненные', 'usam'), 'type' => 'decree', 'color' => '#ff9393', 'text_color' => '#a42727', 'close'  => 1],
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'invoice_payment', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'agreement', 'name' => __('На согласовании', 'usam'), 'short_name' => __('На согласовании', 'usam'), 'type' => 'invoice_payment', 'color' => '#faeac0', 'text_color' => '#967012'],	
				['internalname' => 'subscribe', 'name' => __('Подписан', 'usam'), 'short_name' => __('Подписанные', 'usam'), 'type' => 'invoice_payment', 'color' => '#e6f0c0', 'text_color' => '#4b711d'],	
				['internalname' => 'declained', 'name' => __('Отклонен', 'usam'), 'short_name' => __('Отклоненные', 'usam'), 'type' => 'invoice_payment', 'color' => '#ff9393', 'text_color' => '#a42727', 'close'  => 1],
				['internalname' => 'paid', 'name' => __('Оплачен', 'usam'), 'short_name' => __('Оплаченные', 'usam'), 'type' => 'invoice_payment', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				['internalname' => 'notpaid', 'name' => __('Не оплачен', 'usam'), 'short_name' => __('Не оплаченные', 'usam'), 'type' => 'invoice_payment', 'color' => '#ff9393', 'text_color' => '#a42727'],	
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'proxy', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'proxy', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'act', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'sent', 'name' => __('Отправлен клиенту', 'usam'), 'short_name' => __('Отправленные клиенту', 'usam'), 'type' => 'invoice', 'color' => '#faeac0', 'text_color' => '#967012'],
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'act', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'receipt', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'receipt', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'movement', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'movement', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'buyer_refund', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'buyer_refund', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'payment_order', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'payment_order', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'payment_received', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'payment_received', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
								
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'partner_order', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'partner_order', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'reconciliation_act', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'reconciliation_act', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'check', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'check', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'check_return', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'check_return', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'order_contractor', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'approved', 'name' => __('Проведено', 'usam'), 'short_name' => __('Проведенные', 'usam'), 'type' => 'order_contractor', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],							
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'invoice', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'sent', 'name' => __('Отправлен клиенту', 'usam'), 'short_name' => __('Отправленные клиенту', 'usam'), 'type' => 'invoice', 'color' => '#faeac0', 'text_color' => '#967012'],	
				['internalname' => 'paid', 'name' => __('Оплачен', 'usam'), 'short_name' => __('Оплаченные', 'usam'), 'type' => 'invoice', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				['internalname' => 'notpaid', 'name' => __('Не оплачен', 'usam'), 'short_name' => __('Не оплаченные', 'usam'), 'type' => 'invoice', 'color' => '#ff9393', 'text_color' => '#a42727', 'close'  => 1],
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'invoice_offer', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'sent', 'name' => __('Отправлен клиенту', 'usam'), 'short_name' => __('Отправленные клиенту', 'usam'), 'type' => 'invoice_offer', 'color' => '#faeac0', 'text_color' => '#967012'],	
				['internalname' => 'paid', 'name' => __('Оплачен', 'usam'), 'short_name' => __('Оплаченные', 'usam'), 'type' => 'invoice_offer', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				['internalname' => 'notpaid', 'name' => __('Не оплачен', 'usam'), 'short_name' => __('Не оплаченные', 'usam'), 'type' => 'invoice_offer', 'color' => '#ff9393', 'text_color' => '#a42727', 'close'  => 1],
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'contract', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'received', 'name' => __('Рассматривается клиентом', 'usam'), 'short_name' => __('Рассматривается клиентом', 'usam'), 'type' => 'contract', 'color' => '#faeac0', 'text_color' => '#967012'],	
				['internalname' => 'subscribe', 'name' => __('Подписан', 'usam'), 'short_name' => __('Подписанные', 'usam'), 'type' => 'contract', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				['internalname' => 'notsigned', 'name' => __('Не подписан', 'usam'), 'short_name' => __('Не подписанные', 'usam'), 'type' => 'contract', 'color' => '#ff9393', 'text_color' => '#a42727', 'close'  => 1],
				
				['internalname' => 'draft', 'name' => __('Черновик', 'usam'), 'short_name' => __('Черновики', 'usam'), 'type' => 'additional_agreement', 'color' => '#d9d9d9', 'text_color' => '#6f6b6b'],	
				['internalname' => 'received', 'name' => __('Рассматривается клиентом', 'usam'), 'short_name' => __('Рассматривается клиентом', 'usam'), 'type' => 'additional_agreement', 'color' => '#faeac0', 'text_color' => '#967012'],	
				['internalname' => 'subscribe', 'name' => __('Подписан', 'usam'), 'short_name' => __('Подписанные', 'usam'), 'type' => 'additional_agreement', 'color' => '#e6f0c0', 'text_color' => '#4b711d', 'close'  => 1],	
				['internalname' => 'notsigned', 'name' => __('Не подписан', 'usam'), 'short_name' => __('Не подписанные', 'usam'), 'type' => 'additional_agreement', 'color' => '#ff9393', 'text_color' => '#a42727', 'close'  => 1]
			];	
			foreach ( $object_status as $key => $status )
			{ 
				if ( empty($types) || in_array($status['type'], $types) )
				{
					$status['sort'] = $key+1;
					$status['active'] = isset($status['active'])?$status['active']:1;
					$status['number'] = usam_get_number_objects_status( $status['internalname'], $status['type'] );
					usam_insert_object_status( $status );
				}
			}	
		}
	}
		
	public function controller_product_attributes( )
	{
		$new_product_attributes = [
			array('title' => 'Основные', 'img' => '', 'args' => array('description' => '', 'parent' => 0, 'slug' => 'main'), 'sub' => array(
				array('title' => 'Страна', 'img' => '', 'args' => array('description' => '', 'parent' => 0,'slug' => 'country'), 'meta' => array('filter' => 1) ),
				array('title' => 'Бренд', 'img' => '', 'args' => array('description' => '', 'parent' => 0,  'slug' => 'brand'), 'meta' => array('filter' => 1) ),
				array('title' => 'Коллекция', 'img' => '', 'args' => array('description' => '', 'parent' => 0,'slug' => 'collection'), 'meta' => array('filter' => 1), ),
				array('title' => 'Материал', 'img' => '', 'args' => array('description' => '', 'parent' => 0,	'slug' => 'material'), 'meta' => array('field_type' => 'S', 'filter' => 1), 'ready_options' => array( 
					array('code' => '', 'value' => __('Железо','usam') ),
					array('code' => '', 'value' => __('Бронза','usam') ),
					array('code' => '', 'value' => __('Метал','usam') ),
					array('code' => '', 'value' => __('Каучук','usam') ),
					array('code' => '', 'value' => __('Пластик','usam') ),
					array('code' => '', 'value' => __('Дерево','usam') ),
					array('code' => '', 'value' => __('Стекло','usam') ),					
					)
				),	
				array('title' => 'Цвет', 'img' => '', 'args' => ['description' => '', 'parent' => 0, 'slug' => 'color'], 'meta' => ['field_type' => 'COLOR_SEVERAL', 'filter' => 1],
					'ready_options' => array( 
						['value' => __('белый','usam'), 'code' => '#ffffff', 'sort' => 0],
						['value' => __('золотистый','usam'), 'code' => '#ffd700', 'sort' => 0],
						['value' => __('черный','usam'), 'code' => '#000000', 'sort' => 0],
						['value' => __('зеленый','usam'), 'code' => '#81d742', 'sort' => 0],
						['value' => __('синий','usam'), 'code' => '#1e73be', 'sort' => 0],
						['value' => __('красный','usam'), 'code' => '#dd3333', 'sort' => 0],
						['value' => __('фиолетовый','usam'), 'code' => '#8224e3', 'sort' => 0],
						['value' => __('бежевый','usam'), 'code' => '#ddc092', 'sort' => 0],
						['value' => __('желтый','usam'), 'code' => '#ffff00', 'sort' => 0],
						['value' => __('серый','usam'), 'code' => '#e2e2e2', 'sort' => 0],
						['value' => __('оранжевый','usam'), 'code' => '#ff8000', 'sort' => 0],
						['value' => __('розовый','usam'), 'code' => '#ffc0cb', 'sort' => 0],
						['value' => __('молочный','usam'), 'code' => '#f2f3f4', 'sort' => 0],
						['value' => __('голубой','usam'), 'code' => '#23c2db', 'sort' => 0],
						['value' => __('бордовый','usam'), 'code' => '#b00000', 'sort' => 0],
						['value' => __('коричневый','usam'), 'code' => '#964b00', 'sort' => 0],
					),				
				),	
				array('title' => 'Гарантия', 'img' => '', 'args' => array('description' => '', 'parent' => 0,	'slug' => 'warranty '), ),	
				array('title' => 'Срок службы', 'img' => '', 'args' => array('description' => '', 'parent' => 0,	'slug' => 'lifetime'), ),	
			)),
			array('title' => 'Габариты', 'img' => '', 'args' => array('description' => '', 'parent' => 0,	'slug' => 'dimensions'), 'sub' => array(	
				array('title' => 'Длина', 'img' => '', 'args' => array('description' => '', 'parent' => 0,'slug' => 'length') ),
				array('title' => 'Ширина', 'img' => '', 'args' => array('description' => '', 'parent' => 0,'slug' => 'width'), ),	
				array('title' => 'Высота', 'img' => '', 'args' => array('description' => '', 'parent' => 0,'slug' => 'height'), ),
				array('title' => 'Диаметр', 'img' => '', 'args' => array('description' => '', 'parent' => 0,'slug' => 'diameter'), ),
				array('title' => 'Объем', 'img' => '', 'args' => array('description' => '', 'parent' => 0,'slug' => 'volume'), ),	
				array('title' => 'Вес, кг', 'img' => '', 'args' => array('description' => '', 'parent' => 0,'slug' => 'weight'), 'meta' => ['field_type' => 'O', 'filter' => 1]),	
			))
		];		
		$this->save_product_attributes( $new_product_attributes );		
	}
	
	public function controller_marketplace_product_attributes( )
	{
		$new_product_attributes = [
			['title' => 'Основные', 'img' => '', 'args' => ['description' => '', 'parent' => 0,	 'slug' => 'main'], 'sub' => [
				['title' => 'Состояние товара', 'args' => ['description' => '', 'parent' => 0, 'slug' => 'condition'], 'meta' => ['field_type' => 'BUTTONS', 'filter' => 1], 'ready_options' => [
					['value' => __('Новый','usam'), 'code' => 'new', 'sort' => 0],
					['value' => __('Б/у','usam'), 'code' => 'used', 'sort' => 0]
				]
				],
			/*	['title' => 'Товар доступен', 'args' => ['description' => '', 'parent' => 0,'slug' => 'availability'], 'meta' => ['field_type' => 'BUTTONS', 'filter' => 1], 'ready_options' => [
					['value' => __('В наличии','usam'), 'code' => 'in_stock', 'sort' => 0],
					['value' => __('Под заказ','usam'), 'code' => 'on_order', 'sort' => 0]
				]],*/
			]],			
		];		
		$this->save_product_attributes( $new_product_attributes );		
	}
	
	private function save_product_attributes( $attributes )
	{
		foreach ( $attributes as $attribute ) 
		{
			$main_term = get_term_by( 'slug', $attribute['args']['slug'], 'usam-product_attributes' );	
			if ( $main_term )
				$parent = isset($main_term->term_id) ? $main_term->term_id : 0;
			else
			{
				$term = wp_insert_term( $attribute['title'], 'usam-product_attributes', $attribute['args'] );					
				if ( is_wp_error($term) ) 	
					continue;
				else
					$parent = $term['term_id'];
			}	
			foreach ( $attribute['sub'] as $attr ) 
			{			
				$attr['args']['parent'] = $parent;
				$term2 = wp_insert_term( $attr['title'], 'usam-product_attributes', $attr['args'] );	
				if ( !is_wp_error($term2) ) 		
				{	
					if ( !empty($attr['meta']) )
					{
						foreach ( $attr['meta'] as $meta_key => $meta_value ) 
							usam_update_term_metadata( $term2['term_id'], $meta_key, $meta_value );
					}
					if ( !empty($attr['ready_options']) )
					{
						foreach ( $attr['ready_options'] as $option ) 
						{
							$option['attribute_id'] = $term2['term_id'];
							usam_insert_product_attribute_variant( $option );	
						}
					}
				}
			}
		}	
	}
	
	function load_tables( $table, $data )
	{
		global $wpdb;
		
		$this->truncase( $table );
		$list = usam_read_txt_file( $this->db_path.$data['file_path'], ',');		
		foreach ( $list as $value )
		{	
			$insert = array();
			$start_insert = false;
			foreach ( $data['column'] as $number => $key )
			{
				$insert[$key] = !empty($value[$number])?$value[$number]:0;
				if ( $start_insert == false && !empty($insert[$key]) )	
					$start_insert = true;
			}		
			if ( $start_insert )
				$result = $wpdb->insert( $table, $insert );
		}	
	}	
}
?>