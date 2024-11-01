<?php
require_once( USAM_FILE_PATH . '/includes/crm/bank_accounts.class.php');
require_once( USAM_FILE_PATH . '/includes/crm/group.class.php' );

class USAM_Company 
{	
	 // строковые
	private static $string_cols = [
		'date_insert',
		'type',
		'industry',
		'name',	
		'status',	
		'last_order_date',
	];
	// цифровые
	private static $int_cols = [
		'id',	
		'manager_id',
		'parent_id',
		'open',		
		'number_orders'
	];
	private static $float_cols = [
		'total_purchased',
	];
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';
			
		if ( in_array( $col, self::$float_cols ) )
			return '%f';
		return false;
	}	
	private $data    = [];
	private $changed_data = [];
	private $fetched = false;	
	private $args    = ['col'   => '', 'value' => ''];
	private $exists  = false;
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( false === $value )
			return;

		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, ['id']) )
			return;
		
		$this->args = ['col' => $col, 'value' => $value];		
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_company' );			
		}
		elseif ( $col == 'user_id' )
		{
			$this->data = usam_get_companies(['user_id' => $value, 'number' => 1, 'cache_results' => true]);
			$this->update_cache();
		}
		// кэш существует
		if ( $this->data ) 
		{	
			$this->fetched = true;
			$this->exists = true;			
		}	
		else
			$this->fetch();	
	}

	/**
	 * Обновить кеш
	 */
	public function update_cache( ) 
	{		
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_company' );
		do_action( 'usam_company_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_company' );	
		wp_cache_delete( $this->get( 'id' ), 'usam_company_meta' );		
		wp_cache_delete( $this->get( 'id' ), 'usam_bank_accounts' );		
		
		do_action( 'usam_company_update_cache', $this );
	}

	/**
	 * Удаляет
	 */
	public function delete( ) 
	{		
		$id = $this->get('id');		
		return usam_delete_companies(['include' => array($id)]);
	}	

	/**
	 * Выбирает фактические записи из базы данных
	 */
	private function fetch() 
	{	
		global $wpdb;
		if ( $this->fetched )
			return;

		if ( ! $this->args['col'] || ! $this->args['value'] )
			return;

		extract( $this->args );

		$format = self::get_column_format( $col );		
		$this->exists = false;
		if ( $data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM " . USAM_TABLE_COMPANY . " WHERE {$col} = {$format}", $value ), ARRAY_A ) ) 
		{				
			$this->exists = true;
			$this->data = apply_filters( 'usam_company_data', $data );	
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}			
			$this->update_cache();
		}
		do_action( 'usam_company_fetched', $this );
		$this->fetched = true;
	}

	/**
	 * Проверить существует ли строка в БД
	 */
	public function exists() 
	{
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства из БД
	 */
	public function get( $key ) 
	{
		if ( empty($this->data) || !array_key_exists($key, $this->data) )
			$this->fetch();		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_company_get_property', $value, $key, $this );
	}

		/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_company_get_data', $this->data, $this );
	}
	
	/**
	 * Устанавливает свойство до определенного значения. Эта функция принимает ключ и значение в качестве аргументов, или ассоциативный массив, содержащий пары ключ-значение.
	 */
	public function set( $key, $value = null ) 
	{		
		if ( is_array( $key ) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = array( $key => $value );
		}
		$properties = apply_filters( 'usam_company_set_properties', $properties, $this );	

		$this->fetch();		
		if( !is_array( $this->data ) )
			$this->data = array();
		foreach ( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{
				$previous = $this->get( $key );	
				$this->data[$key] = $value;						
				if ( $value != $previous )
					$this->changed_data[$key] = $previous;	
			}
		}				
		return $this;
	}

	/**
	* Вернуть формат столбцов таблицы
	*/
	private function get_data_format( $data ) 
	{
		$formats = array();
		foreach ( $data as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )		
				$formats[$key] = $format;
		}
		return $formats;
	}	
	
	private function get_update_data( ) 
	{		
		$data = [];		
		foreach( $this->changed_data as $key => $value ) 
		{							
			if( self::get_column_format( $key ) !== false )
				$data[$key] = $this->data[$key];
		}
		return $data;
	}
		
	public function save()
	{
		global $wpdb;

		do_action( 'usam_company_pre_save', $this );	
		
		$result = false;		
		if ( $this->args['col'] ) 
		{	// обновление			
			if ( empty($this->changed_data) )
				return true;	
			
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_company_pre_update', $this );
			$this->data = apply_filters( 'usam_company_update_data', $this->data );			
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );		
			$result = $wpdb->update( USAM_TABLE_COMPANY, $data, [$this->args['col'] => $this->args['value']], $formats, [$where_format]);		
			if ( $result ) 
			{
				$this->delete_cache( );				
				if ( isset($this->changed_data['status']) ) 
					usam_update_object_count_status( $this->data['status'], 'company' );
				do_action( 'usam_company_update', $this );
			}
		} 
		else 
		{
			do_action( 'usam_company_pre_insert' );							
			if ( !isset($this->data['name']) )
				$this->data['name'] = '';
			else
				$this->data['name'] = trim($this->data['name']);	
			if ( empty($this->data['status']) )
				$this->data['status'] = 'customer';			
			if ( !isset($this->data['industry']) )
				$this->data['industry'] = 'other';	
			if ( !isset($this->data['type']) )
				$this->data['type'] = 'customer';			
								
			$this->data['date_insert'] = date( "Y-m-d H:i:s" );				
			$this->data = apply_filters( 'usam_company_insert_data', $this->data );				
			$formats = $this->get_data_format( $this->data );			
			$result = $wpdb->insert( USAM_TABLE_COMPANY, $this->data, $formats );	
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );				
				$this->exists = true;
				$this->args = ['col' => 'id',  'value' => $this->get( 'id' )];	
				usam_update_object_count_status( $this->data['status'], 'company' );				
				do_action( 'usam_company_insert', $this );
			}			
		} 				
		if ( $result ) 
			do_action( 'usam_company_save', $this );
		$this->changed_data = [];
		return $result;
	}		
}

function usam_get_company( $value, $colum = 'id' )
{	
	if ( !$value )
		return [];
	
	if ( $colum == 'user_id' )
	{
		global $wpdb;
		$value = $wpdb->get_var("SELECT company_id FROM ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS." WHERE user_id='$value'");
		if ( !$value )
			return [];
		$colum = 'id';	
	}	
	$company = new USAM_Company($value, $colum);	
	$data = $company->get_data();	
	if ( empty($data) )
		return [];
	
	return $data;	
}

function usam_update_company( $id, $data, $metas = [], $new_accounts = [] )
{		
	if ( $id )
	{
		$company = new USAM_Company( $id );	
		$company->set( $data );	
		$result = $company->save();			
		if(	usam_update_company_metas( $id, $metas ) )
			$result = true; 
		if ( !empty($new_accounts) ) 
		{ 			
			$accounts = usam_get_company_bank_accounts( $id );
			if ( $accounts )
			{
				foreach ( $accounts as $account ) 
				{
					foreach ( $new_accounts as $k2 => $account2 ) 
					{	 
						if ( $account->number == $account2['number'] )
						{
							unset($new_accounts[$k2]);
							if( usam_update_bank_account( $account->id, $account2 ) )
								$result = true;
							break;
						}
					}
				}
			}
			if ( $new_accounts )
			{
				foreach ( $new_accounts as $account )
				{
					$account['company_id'] = $id;
					if( usam_insert_bank_account( $account ) )
						$result = true;
				}
			}
		}
	}
	return $result;
}

function usam_insert_company( $new_data, $metas = [], $new_accounts = [] )
{	
	global $wpdb;
	$company_id = 0;	
		
	$check = apply_filters( 'usam_check_company_database', null, $new_data, $metas, $new_accounts );
	if ( $check === null )
	{	
		if ( $metas )
		{
			$check_meta_keys = apply_filters( 'usam_check_company_by_field', ['inn', 'code_1c'], $new_data, $metas, $new_accounts );
			$check_metas = [];
			foreach ( $check_meta_keys as $key ) 
			{
				if ( !empty($metas[$key]) )
					$check_metas[$key] = "meta_key='$key' AND meta_value='".$metas[$key]."'";
			}
			if ( !empty($check_metas) )
				$company_id = $wpdb->get_var("SELECT company_id FROM ".USAM_TABLE_COMPANY_META." WHERE ".implode(' OR ',$check_metas) );
		}
	}
	elseif ( $check > 0 )
		$company_id = $check;		
	if ( $company_id )
		usam_combine_company( $company_id, $new_data, $metas );
	else
	{	
		if ( empty($new_data['name']) )
		{
			if ( !empty($metas['full_company_name']) )
				$new_data['name'] = $metas['full_company_name'];
			elseif ( !empty($metas['company_name']) )
				$new_data['name'] = $metas['company_name'];
		}			
		$company = new USAM_Company( $new_data );	
		$company->save();	
		$company_id = $company->get('id');	
		usam_update_company_metas( $company_id, $metas );		
	} 
	if ( !$company_id ) 
		return 0;	
			
	if ( !empty($new_accounts) ) 
	{ 
		foreach( $new_accounts as $account )
		{
			$account['company_id'] = $company_id;
			usam_insert_bank_account( $account );
		}
	}
	return $company_id;		 
}

function usam_update_company_metas( $id, $metas )
{	
	if ( !$id || empty($metas) ) 
		return false;
	
	static $properties = null;
	if ( $properties == null )
		$properties = usam_get_properties(['type' => 'company', 'active' => 1, 'fields' => 'code=>data']);	
	foreach ( $metas as $meta_key => $meta_value ) 
	{			
		if ( isset($properties[$meta_key]) )
			usam_save_property_meta( $id, $properties[$meta_key], $meta_value );
		else
		{
			if ( is_array($meta_value) )
				$meta_value = array_map('wp_unslash', $meta_value);					
			else
				$meta_value = trim( wp_unslash($meta_value) );
			usam_update_company_metadata( $id, $meta_key, $meta_value );
		}
	}
	do_action( 'usam_update_company_metas', $id, $metas );
	return true;
}

function usam_delete_company( $value )
{	
	$company = new USAM_Company( $value );
	return $company->delete();		 
}

function usam_combine_company( $company_id, $new_data, $metas = [] )
{
	$update = [];
	$company = usam_get_company( $company_id );
	if ( empty($company) )
		return false;
	foreach ( $new_data as $key => $data ) 
	{
		if ( !isset($company[$key]) )
			continue;
				
		if ( empty($data) )
			continue;
				
		if ( empty($company[$key]) && $company[$key] != $data )
			$update[$key] = $data;
	}	
	if ( !empty($update) )
		usam_update_company( $company_id, $update );
	usam_update_company_metas( $company_id, $metas );
	return true;		
}

function usam_get_company_logo( $value, $size = 'full' )
{		
	$thumbnail_id = usam_get_company_metadata( $value, 'logo' );
	if ( !$thumbnail_id )
		return usam_get_no_image_uploaded_file( $size );	
	
	$image_attributes = wp_get_attachment_image_src( $thumbnail_id, $size );
	if ( !empty($image_attributes[0]) )
		return $image_attributes[0];
	else
		return usam_get_no_image_uploaded_file( $size );
}

function usam_update_company_logo( $id, $thumbnail_id )
{	
	$old_thumbnail_id = usam_get_company_metadata( $id, 'logo' );
	if ( $old_thumbnail_id !== $thumbnail_id )
	{
		if ( $old_thumbnail_id )
			wp_delete_attachment($old_thumbnail_id, true);	
		
		$max_width = 300; 
		$max_height = 300;
		$filepath = get_attached_file( $thumbnail_id ); 
		$image = wp_get_image_editor( $filepath );				
		if ( ! is_wp_error($image) ) 					
		{
			$image->resize( $max_width, $max_height, false );			
			$image->save( $filepath );
		}		
		return usam_update_company_metadata( $id, 'logo', $thumbnail_id );
	}
	return true;
}

function usam_get_company_address( $company_id, $type = 'contact', $string = '%country%, %region%, %city%' )
{
	$customer_address = array();	
	$postcode = usam_get_company_metadata( $company_id, $type.'postcode' ); 
	if ( !empty($postcode) )
		$customer_address[] = $postcode;
	
	$location = usam_get_company_metadata( $company_id, $type.'location' ); 
	if ( !empty($location) )
		$customer_address[] = usam_get_full_locations_name($location, $string);		
	
	$address = usam_get_company_metadata( $company_id, $type.'address' ); 
	if ( !empty($address) )
		$customer_address[] = $address;		
	
	return implode(', ', $customer_address );	
}

function usam_get_company_url( $company_id )
{		
	if ( current_user_can('edit_company') )
		$link = admin_url("admin.php?page=crm&tab=companies&form=view&form_name=company&id=".$company_id);	
	else
		$link = '';
	return $link;
}

function usam_get_company_by_acc_number( $bank_account_id, $prefix = '' )
{
	$account = usam_get_bank_account( $bank_account_id );	
	$requisites = []; 
	if ( !empty($account) )
	{		
		$requisites = usam_get_company_requisites( $account['company_id'], $prefix );
		$prefix = !empty($prefix)?$prefix.'_':'';
		
		$requisites[$prefix.'bank_details'] = __('р/с','usam').' '.$account['number'].' '.$account['name'].' '.__('БИК', 'usam').' '.$account['bic'].' '.__('кор/с','usam').' '.$account['bank_ca'];
		foreach ( $account as $key => $value ) 	
		{
			if ( $key == 'company_id' || $key == 'id' )
				continue;
			if ( $key == 'bank_ca' )
				$requisites[$prefix.'bank_ca'] = $value;
			else
				$requisites[$prefix.'bank_'.$key] = $value;
		}			
	}
	return $requisites;
}

function usam_get_company_requisites( $company_id, $prefix = '' )
{	
	$requisites = []; 
	$prefix = !empty($prefix)?$prefix.'_':'';
			
	$company_data = usam_get_company( $company_id );
	foreach ( $company_data as $key => $data ) 	
	{ 				
		$requisites[$prefix.$key] = $data;			
	}		
	$metas = usam_get_company_metas( $company_id );	

	$postcode = !empty($metas['contactpostcode'])?$metas['contactpostcode'].', ':'';
	$address = !empty($metas['contactaddress'])?$metas['contactaddress']:'';
	
	$metas['full_contactaddress'] = $postcode.$address;
	$metas['inn'] = !empty($metas['inn'])?$metas['inn']:'';
	$metas['ppc'] = !empty($metas['ppc'])?$metas['ppc']:'';
	$metas['ogrn'] = !empty($metas['ogrn'])?$metas['ogrn']:'';
	$metas['okpo'] = !empty($metas['okpo'])?$metas['okpo']:'';
	$metas['full_company_name'] = !empty($metas['full_company_name'])?$metas['full_company_name']:'';
	$metas['full_legaladdress'] = !empty($metas['full_legaladdress'])?$metas['full_legaladdress']:'';
	foreach ( $metas as $key => $data ) 	
	{ 							
		$requisites[$prefix.$key] = $data;
		if ( $key == 'gm' )
		{
			$result = '';
			if ( $data )
			{
				$contact = explode(' ', $data);
				$result = $contact[0];
				if ( isset($contact[1]) )
				{
					$result .= ' '. mb_strtoupper(mb_substr($contact[1],0,1)).'.';
					if ( isset($contact[2]) )
						$result .= ' '.mb_strtoupper(mb_substr($contact[2],0,1)).'.';
				}
			}
			$requisites[$prefix.'gm_abb'] = $result;
		}			
	}	
	$requisites[$prefix.'company_details']  = $metas['full_company_name'].' '.__( "ИНН:", 'usam').' '.$metas['inn'].' '.__( "КПП:", 'usam').' '.$metas['ppc'].' '.$metas['full_legaladdress'];
	$requisites[$prefix.'contactcountry'] = '';
	$requisites[$prefix.'contactcity'] = '';
	$requisites[$prefix.'legalcountry'] = '';
	$requisites[$prefix.'legalcity'] = '';	
	if ( !empty($metas['contactlocation']) )
	{
		$locations = usam_get_address_locations( $metas['contactlocation'] );
		if ( !empty($locations) )
		{
			foreach($locations as $key => $name) 
				$requisites[$prefix.'contact'.$key] = $name;
			
			$city = !empty($locations['city'])?$locations['city']:'';
			$contactpostcode = isset($metas['contactpostcode'])?$metas['contactpostcode'].', ':'';
			$requisites[$prefix.'full_contactaddress'] = $contactpostcode.$locations['country'].', '.$city.', '.$metas['contactaddress'];				
		}
	}			
	$full_legaladdress = [];
	if ( !empty($metas['legalpostcode']) )
		$full_legaladdress[] = $metas['legalpostcode'];
	if ( !empty($metas['legallocation']) )
	{
		$locations = usam_get_address_locations( $metas['legallocation'] );
		if ( !empty($locations) )
		{
			foreach($locations as $key => $name) 
				$requisites[$prefix.'legal'.$key] = $name;
				
			if ( !empty($locations['country']) )
				$full_legaladdress[] = $locations['country'];
			
			if ( !empty($locations['city']) )
				$full_legaladdress[] = __('г.','usam').' '.$locations['city'];	
		}
	}		
	if ( !empty($metas['legaladdress']) )
		$full_legaladdress[] = $metas['legaladdress'];
	$requisites[$prefix.'full_legaladdress'] = implode(', ', $full_legaladdress );	
	return $requisites;
}

function usam_get_companies_industry( )
{
	return array( 
		'it' => __('Информационные технологии','usam'),
		'telecom' => __('Телекоммуникации и связь','usam'),
		'manufacture' => __('Производство','usam'),
		'banking' => __('Банковские услуги','usam'),
		'consulting' => __('Консалтинг','usam'),
		'finance' => __('Финансы','usam'),
		'government' => __('Правительство','usam'),
		'delivery' => __('Доставка','usam'),
		'entertainmet' => __('Развлечения','usam'),
		'notprofit' => __('Не для получения прибыли','usam'),	
		'pharmaceuticals' => __('Фармацевтика','usam'),		
		'other' => __('Другое','usam'),
	);	
}

function usam_get_companies_types( )
{
	return array( 
		'customer' => __('Клиент','usam'),
		'prospect' => __('Будущий клиент','usam'),		
		'contractor' => __('Поставщик','usam'),
		'partner' => __('Партнер','usam'),
		'reseller' => __('Реселлер','usam'),
		'competitor' => __('Конкурент','usam'),
		'investor' => __('Инвестор','usam'),
		'integrator' => __('Интегратор','usam'),
		'press' => __('СМИ','usam'),
		'own' => __('Своя','usam'),	
		'seller' => __('Продавец','usam'),
		'other' => __('Другое','usam'),
	);		
}

function usam_get_name_type_company( $type )
{	
	$types = usam_get_companies_types();
	if ( isset($types[$type]) )
		return $types[$type];
	else
		return '';
}

function usam_get_name_industry_company( $type )
{	
	$industry = usam_get_companies_industry();
	if ( isset($industry[$type]) )
		return $industry[$type];
	else
		return '';
}

function usam_get_company_groups( $id )
{	
	$cache_key = 'usam_company_groups';
	$cache = wp_cache_get( $id, $cache_key );
	if( $cache === false )	
	{		
		require_once( USAM_FILE_PATH.'/includes/crm/groups_query.class.php' );
		$cache = usam_get_groups(['objects' => $id, 'fields' => 'name', 'type' => 'company']);			
		wp_cache_set( $id, $cache, $cache_key );
	}			
	return $cache;
}

function usam_get_company_metas( $company_id, $formatted = 'db' ) 
{		
	if ( empty($company_id) )
		return false; 	
	
	$properties = usam_get_properties(['type' => 'company']);	
	$metas = [];
	foreach ( $properties as $property ) 
	{		
		if ( $property->field_type == 'checkbox' )
			$value = usam_get_array_metadata( $company_id, 'company', $property->code );
		else
			$value = usam_get_company_metadata( $company_id, $property->code );				
		$metas[$property->code] = $formatted == 'display' ? usam_get_formatted_property( $value, $property ) : $value;
	}
	return $metas;
}

function usam_get_company_phones( $company_id, $contact = false, $field_type = null )
{		
	static $properties;
	
	if ( !$field_type  )
		$field_type = ['mobile_phone', 'phone'];
	if ( $properties == null )
		$properties = usam_get_properties(['type' => 'company', 'active' => 1, 'field_type' => $field_type, 'fields' => 'code']);	
	
	$phones = array();
	foreach ( $properties as $property )
	{
		$value = usam_get_company_metadata( $company_id, $property );	
		if ( $value ) 
			$phones[] = $value;	
	}
	if ( $contact )
	{
		$contact_ids = usam_get_contacts( array( 'fields' => 'id', 'company_id' => $company_id, 'cache_meta' => true, 'source' => 'all' ) );
		foreach ( $contact_ids as $contact_id )
		{
			$contact_phones = usam_get_contact_phones( $contact_id );
			$phones = array_merge( $phones, $contact_phones );		
		}
	}
	return $phones;
}

function usam_get_company_emails( $company_id, $contact = false  )
{		
	static $properties;
	if ( $properties == null )
		$properties = usam_get_properties(['type' => 'company', 'active' => 1, 'field_type' => array('email'), 'fields' => 'code']);	
	
	$emails = array();
	foreach ( $properties as $property )
	{
		$value = usam_get_company_metadata( $company_id, $property );		
		if ( $value && is_email($value) ) 
			$emails[] = $value;	
	}
	if ( $contact )
	{
		$contact_ids = usam_get_contacts( array( 'fields' => 'id', 'company_id' => $company_id, 'cache_meta' => true, 'source' => 'all' ) );
		foreach ( $contact_ids as $contact_id )
		{
			$contact_emails = usam_get_contact_emails( $contact_id );
			$emails = array_merge( $emails, $contact_emails );		
		}
	}
	return $emails;
}

function usam_get_companies_emails( $company_ids, $contact = false )
{ 	
	$emails = usam_get_companies_communication( $company_ids, $contact, 'email' );
	return $emails;
}

function usam_get_companies_communication( $company_ids, $contact, $field_type )
{ 	
	$results = array();
	if ( !empty($company_ids) )
	{
		static $properties = null;
		if ( $properties == null )
			$properties = usam_get_properties(['type' => 'company', 'active' => 1, 'field_type' => $field_type, 'fields' => 'code']);
		if ( is_numeric($company_ids[0]) )
			$companies = usam_get_companies(['include' => $company_ids]);		
		else
			$companies = $company_ids;
		foreach ( $companies as $company )
		{ 					
			foreach ( $properties as $property )
			{
				$value = usam_get_company_metadata( $company->id, $property );
				if ( $value )
					$results[$value] = "$company->name ($value)";
			}
		}
		if ( $contact )
		{
			$contact_ids = usam_get_contacts( array( 'fields' => 'id', 'company_id' => $company_ids, 'cache_meta' => true, 'source' => 'all' ) );
			$communications = usam_get_contacts_communication( $contact_ids, $field_type );
			$results = array_merge( $results, $communications );	
		}
	}
	return $results;
}

function usam_get_company_metadata( $object_id, $meta_key = '', $single = true) 
{
	$result = usam_get_metadata('company', $object_id, USAM_TABLE_COMPANY_META, $meta_key, $single );
	switch( $meta_key )
	{
		case 'logo':	
		case 'location':		
			$result = (int)$result;
		break;
	}	
	return $result;
}

function usam_update_company_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('company', $object_id, $meta_key, $meta_value, USAM_TABLE_COMPANY_META, $prev_value );
}

function usam_delete_company_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('company', $object_id, $meta_key, USAM_TABLE_COMPANY_META, $meta_value, $delete_all );
}

function usam_add_company_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{	
	return usam_add_metadata('company', $object_id, $meta_key, $meta_value, USAM_TABLE_COMPANY_META, $prev_value );
}

function usam_get_columns_company_export() 
{	
	$columns = array(
		'status'     => __('Статус', 'usam'), 
		'name'       => __('Название компании', 'usam'), 
		'type'       => __('Код тип компании', 'usam'), 
		'type_name'  => __('Тип компании', 'usam'), 
		'industry'   => __('Код сферы деятельности', 'usam'), 
		'code'       => __('Код', 'usam'), 
		'industry_name' => __('Сфера деятельности', 'usam'), 
		'group'      => __('Код группы', 'usam'), 			
		'group_name' => __('Группы', 'usam'), 	
		'manager_id' => __('Номер менеджера', 'usam'), 		
		'manager'    => __('Имя менеджера', 'usam'), 
		'location'   => __('Код местоположения', 'usam'),
		'country'    => __('Страна', 'usam'),
		'city'       => __('Город', 'usam'),
		'logo'       => __('Ссылка на логотип', 'usam'),
		'employees'  => __('Кол-во сотрудников', 'usam'),
		'revenue'    => __('Годовой оборот', 'usam'),
		'description'=> __('Описание', 'usam'),
		'date_insert'=> __('Дата создания', 'usam'),	
		'type_price' => __('Тип цены', 'usam'),
	);	
	$fields = usam_get_properties(['type' => 'company', 'active' => 1, 'fields' => 'code=>name']);					
	$columns += $fields;
	return apply_filters('usam_columns_company_export', $columns);
}

function usam_get_columns_company_import() 
{	
	$columns = array(
		'status'     => __('Статус', 'usam'), 
		'name'       => __('Название компании', 'usam'), 
		'type'       => __('Код тип компании', 'usam'), 
		'type_name'  => __('Тип компании', 'usam'), 
		'industry'   => __('Код сферы деятельности', 'usam'), 
		'code'       => __('Код', 'usam'), 
		'industry_name' => __('Сфера деятельности', 'usam'), 
		'group'      => __('Код группы', 'usam'), 			
		'group_name' => __('Группы', 'usam'), 	
		'manager_id' => __('Номер менеджера', 'usam'), 		
		'manager'    => __('Имя менеджера', 'usam'), 
		'location'   => __('Код местоположения', 'usam'),
		'city'       => __('Город', 'usam'),
		'logo'       => __('Ссылка на логотип', 'usam'),
		'employees'  => __('Кол-во сотрудников', 'usam'),
		'revenue'    => __('Годовой оборот', 'usam'),
		'description'=> __('Описание', 'usam'),
		'date_insert'=> __('Дата создания', 'usam'),	
		'type_price' => __('Тип цены', 'usam'),
	);	
	$fields = usam_get_properties( array( 'type' => 'company', 'active' => 1, 'fields' => 'code=>name' ) );					
	$columns += $fields;
	return apply_filters('usam_columns_company_import', $columns);
}

function usam_find_company_in_directory( $args ) 
{
	return apply_filters( 'usam_find_company_in_directory', [], $args );
}

function usam_get_company_selections( $id ) 
{	
	global $wpdb;
	$results = $wpdb->get_col("SELECT connection_id FROM ".USAM_TABLE_COMPANY_CONNECTIONS." WHERE company_id='$id'");	
	if ( $results )
		$results = array_map('intval', $results);
	return $results;
}

function usam_set_company_selections( $company_id, $new_ids, $append = false ) 
{	
	global $wpdb;
	
	$i = 0;
	if ( !$company_id )
		return $i;
	if ( !is_array($new_ids) )
		$new_ids = array($new_ids);		
	$election_ids = usam_get_company_selections( $company_id );
	foreach ( $new_ids as $key => $connection_id ) 
	{ 
		if ( $connection_id && !in_array($connection_id, $election_ids) )
		{
			$insert = $wpdb->insert( USAM_TABLE_COMPANY_CONNECTIONS, ['company_id' => $company_id, 'connection_id' => $connection_id], ['%d', '%d']);
			if ( $insert )
				$i++;
			unset($new_ids[$key]);
		}
	}
	if ( $append == false )
	{
		$results = array_diff($election_ids, $new_ids);
		if ( !empty($results) )
			usam_delete_company_selections( $company_id, $results );
	}
	return $i;
}

function usam_delete_company_selections( $company_id, $connection_ids ) 
{	
	global $wpdb;	
	if ( !is_array($connection_ids) )
		$connection_ids = array($connection_ids);		
	return $wpdb->query( "DELETE FROM ".USAM_TABLE_COMPANY_CONNECTIONS." WHERE company_id='$company_id' AND connection_id IN (".implode(',',$connection_ids).")" );		
}

//Получить личные кабинеты компании
function usam_get_company_personal_accounts( $company_id ) 
{	
	global $wpdb;
	$results = $wpdb->get_col("SELECT user_id FROM ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS." WHERE company_id='$company_id'");
	return $results;
}

function usam_add_company_personal_account( $company_id, $user_id, $check = true ) 
{	
	global $wpdb;
	
	if ( !$company_id || !$user_id)
		return false;	
	
	$user_ids = [];
	if ( $check )
		$user_ids = usam_get_company_personal_accounts( $company_id );
	if ( !in_array($user_id, $user_ids) )
		return $wpdb->insert( USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS, ['company_id' => $company_id, 'user_id' => $user_id], ['%d', '%d']);
	return true;
}

function usam_delete_company_personal_accounts( $company_id, $user_ids ) 
{	
	global $wpdb;	
	if ( !is_array($user_ids) )
		$user_ids = array($user_ids);		
	return $wpdb->query( "DELETE FROM ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS." WHERE company_id='$company_id' AND user_id IN (".implode(',',$user_ids).")" );		
}

function usam_delete_companies( $args ) 
{	
	global $wpdb;		
	$args['source'] = 'all'; 
	$args['cache_meta'] = true;
	$companies = usam_get_companies( $args );	
	if ( empty($companies) )
		return false;	
	
	usam_update_object_count_status( false );
	$ids = [];
	foreach ( $companies as $company )
	{
		usam_update_object_count_status( $company->status, 'company' );
		$ids[] = $company->id;				
		$thumbnail_id = usam_get_company_metadata( $company->id, 'logo' );
		if ( $thumbnail_id )
			wp_delete_attachment($thumbnail_id, true);	
		do_action( 'usam_company_before_delete', (array)$company );	
	}	
	$wpdb->query("DELETE FROM ".USAM_TABLE_COMPANY_ACC_NUMBER." WHERE company_id IN (".implode(",", $ids).")");
	$wpdb->query("DELETE FROM ".USAM_TABLE_COMPANY_FINANCE." WHERE company_id IN (".implode(",", $ids).")");
	$wpdb->query("DELETE FROM ".USAM_TABLE_COMPANY_META." WHERE company_id IN (".implode(",", $ids).")");
	$wpdb->query("DELETE FROM ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS." WHERE company_id IN (".implode(",", $ids).")");
	$wpdb->query("DELETE FROM ".USAM_TABLE_COMPANY_CONNECTIONS." WHERE company_id IN (".implode(",", $ids).") OR connection_id IN (".implode(",", $ids).")");
	$wpdb->query("DELETE FROM ".USAM_TABLE_RIBBON_LINKS." WHERE object_type='company' AND object_id IN (".implode(",", $ids).")");	
	
	$group_ids = $wpdb->get_col("SELECT group_id FROM ".USAM_TABLE_GROUP_RELATIONSHIPS." AS r 
	LEFT JOIN ".USAM_TABLE_GROUPS." AS g ON (g.id=r.group_id) WHERE r.object_id IN (".implode(",", $ids).") AND g.type='company'");
	if ( $group_ids )
		$wpdb->query("DELETE FROM ".USAM_TABLE_GROUP_RELATIONSHIPS." WHERE group_id IN (".implode(",", $group_ids).")");
					
	usam_delete_events(['type' => ['sms', 'call', 'message'], 'object_type' => 'company', 'object_id' => $ids]);	
	usam_delete_object_files( $ids, 'company' );	
	$wpdb->query("DELETE FROM ".USAM_TABLE_COMPANY." WHERE id IN (".implode(",", $ids).")");
	foreach ( $ids as $id )
	{
		wp_cache_delete( $id, 'usam_company' );		
		wp_cache_delete( $id, 'usam_company_meta' );		
		wp_cache_delete( $id, 'usam_bank_accounts' );
		
		do_action( 'usam_company_delete', $id );		
	}
	usam_update_object_count_status( true );
	return count($ids);
}

function usam_get_company_id_by_meta( $key, $value ) 
{
	global $wpdb;	
	if ( !$value ) 
		return;
	
	$cache_key = "usam_company_ids_$key-$value";
	$company_id = wp_cache_get( $value, $cache_key );
	if ($company_id === false) 
	{	
		$company_id = (int)$wpdb->get_var($wpdb->prepare("SELECT company_id FROM ".USAM_TABLE_COMPANY_META." WHERE meta_key='%s' AND meta_value='%s' LIMIT 1", $key, $value));
		wp_cache_set($value, $company_id, $cache_key);
	}
	else
		$company_id = (int)$company_id;
	return $company_id;
}

function usam_get_company_ids_by_field( $field_type, $value ) 
{
	global $wpdb;	
	if ( !$value ) 
		return [];	
	
	$properties = usam_get_cache_properties('company');
	$results = [];
	$field_type = !is_array($field_type) ? [$field_type] : $field_type;
	foreach( $field_type as $key )
	{
		$cache_key = "usam_company_$key";
		$ids = wp_cache_get( $value, $cache_key );
		if( $ids === false ) 
		{					
			$pr = [];
			foreach( $properties as $property )
			{
				if ( $property->field_type == $key )
					$pr[] = $property->code;
			}
			$ids = [];
			if ( $pr )
				$ids = $wpdb->get_col($wpdb->prepare("SELECT company_id FROM ".USAM_TABLE_COMPANY_META." WHERE meta_key IN ('".implode("','", $pr)."') AND meta_value='%s' LIMIT 1", $value));	
			wp_cache_set($value, $ids, $cache_key);
		}
		$results = array_merge( $results, $ids );
	}
	$results = array_unique($results);	
	return $results;
}