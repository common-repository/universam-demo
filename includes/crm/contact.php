<?php
class USAM_Contact 
{	
	private static $string_cols = [
		'date_insert',		
		'appeal',	
		'contact_source',
		'status',
		'online',
		'secret_key',	
		'last_order_date',
	];
	// цифровые
	private static $int_cols = [
		'id',
		'user_id',
		'manager_id',	
		'open',		
		'company_id',
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
	private $args    = ['col' => '', 'value' => ''];
	private $exists  = false;
	
	public function __construct( $value = false, $col = 'id' ) 
	{
		if( false === $value )
		{
			$this->data = $this->default_data();
			return;
		}
		if( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if( ! in_array( $col, ['id', 'user_id', 'secret_key']) )
			return;

		$this->args = ['col' => $col, 'value' => $value];	
		if ( $col == 'user_id'  && $id = wp_cache_get( $value, 'usam_contact_userid' ) )
		{  
			$col = 'id';
			$value = $id;
		}
		elseif ( $col == 'secret_key'  && $id = wp_cache_get( $value, 'usam_contact_secret_key' ) )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $id;
		}		
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_contact' );
			if ( $this->data !== false )
			{
				$this->fetched = true;
				$this->exists = true;		
				return;
			}	
		}		
		$this->fetch();	
	}
	
	public function default_data( ) 
	{
		return ['id' => 0, 'total_purchased' => 0, 'last_order_date' => '', 'number_orders' => 0, 'status' => 'customer', 'user_id' => 0, 'manager_id' => 0, 'appeal' => '', 'online' => date( "Y-m-d H:i:s"), 'company_id' => 0, 'open' => 1, 'secret_key' => md5(uniqid(rand(),1)), 'contact_source' => '', 'date_insert' => date( "Y-m-d H:i:s")];
	}

	/**
	 * Обновить кеш
	 */
	public function update_cache( ) 
	{		
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_contact' );	
		if ( $user_id = $this->get( 'user_id' ) )
			wp_cache_set( $user_id, $id, 'usam_contact_userid' );	
		if ( $secret_key = $this->get( 'secret_key' ) )
			wp_cache_set( $secret_key, $id, 'usam_contact_secret_key' );					
		do_action( 'usam_contact_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_contact' );	
		wp_cache_delete( $this->get( 'secret_key' ), 'usam_contact_secret_key' );
		wp_cache_delete( $this->get( 'userid' ), 'usam_contact_userid' );
		wp_cache_delete( $this->get( 'id' ), 'usam_contact_meta' );			
		do_action( 'usam_contact_update_cache', $this );
	}

	public function delete( ) 
	{
		global $wpdb;	
		
		$id = $this->get('id');		
		
		$result = usam_delete_contacts(['include' => [$id]]);
		$this->delete_cache( );	
		
		return $result;
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
		if ( $data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_CONTACTS . " WHERE {$col} = {$format}", $value ), ARRAY_A ) ) 
		{
			$this->exists = true;			
			$this->data = apply_filters( 'usam_contact_data', $data );		
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}			
			$this->update_cache( );	
		}
		do_action( 'usam_contact_fetched', $this );
		$this->fetched = true;
	}	

	// Проверить существует ли строка в БД
	public function exists() 
	{
		$this->fetch();
		return $this->exists;
	}
	
	// Возвращает значение указанного свойства из БД
	public function get( $key ) 
	{
		if ( empty($this->data) || ! array_key_exists($key, $this->data) )
			$this->fetch();		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_contact_get_property', $value, $key, $this );
	}

	// Возвращает строку заказа из базы данных в виде ассоциативного массива
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_contact_get_data', $this->data, $this );
	}
	
	public function get_changed_data()
	{
		return $this->changed_data;
	}	
		
	public function set( $key, $value = null ) 
	{	
		if ( is_array( $key ) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = [$key => $value];
		}
		$properties = apply_filters( 'usam_contact_set_properties', $properties, $this );		
		if ( ! is_array($this->data) )
			$this->data = [];
		
		foreach ( $properties as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{
				$previous = $this->get( $key );	
				$this->data[$key] = $value;						
				if ( $value != $previous )
				{				
					$this->changed_data[$key] = $previous;	
				}
			}
		}			
		return $this;
	}

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
	
	// Сохраняет в базу данных
	public function save()
	{
		global $wpdb;

		do_action( 'usam_contact_pre_save', $this );
		
		if ( isset($this->data['status']) && $this->data['status'] == 'temporary' )
		{
			if ( !empty($this->data['appeal']) || !empty($this->data['user_id']) || !empty($this->data['manager_id']) || !empty($this->data['company_id']) || !empty($this->data['number_orders']) )
				$this->data['status'] = 'customer';
		}				
		$result = false;				
		if ( $this->args['col'] ) 
		{	// обновление				
			if ( empty($this->changed_data) )
				return true;
			
			if ( isset($this->changed_data['user_id']) && !empty($this->data['user_id']) )
			{
				$result = $wpdb->get_col( $wpdb->prepare("SELECT id FROM ".USAM_TABLE_CONTACTS." WHERE user_id = '%d' LIMIT 1", $this->data['user_id']) );			
				if ( !empty($result) )
				{
					unset($this->data['user_id']);	
					unset($this->changed_data['user_id']);				
				}
			}			
			if ( isset($this->data['secret_key']) )
			{
				unset($this->data['secret_key']);
				unset($this->changed_data['secret_key']);		
			}		
			if ( isset($this->changed_data['contact_source']) )
			{
				if ( $this->changed_data['contact_source'] == 'employee' )
					$this->data['status'] = 'customer';	
				elseif ( $this->data['contact_source'] == 'employee' )
					$this->data['status'] = 'works';		
			}
			$where_format = self::get_column_format( $this->args['col'] );
			do_action( 'usam_contact_pre_update', $this );
			$this->data = apply_filters( 'usam_contact_update_data', $this->data );			
			$data = $this->get_update_data();
			if ( !$data )
				return false;
			$formats = $this->get_data_format( $data );			
			foreach( $data as $key => $value)
			{										
				if ( $key == 'online')
					if ( empty($value) )
						$set[] = "`{$key}`=NULL";
					else					
						$set[] = "`{$key}`='".date( "Y-m-d H:i:s", strtotime( $value ) )."'";
				else
					$set[] = "`{$key}`='{$value}'";						
			}	
			$result = $wpdb->query( $wpdb->prepare("UPDATE `".USAM_TABLE_CONTACTS."` SET ".implode( ', ', $set )." WHERE ".$this->args['col']." ='$where_format'", $this->args['value']) );
			if ( $result ) 
			{
				$id = $this->get('id');
				foreach( $this->changed_data as $key => $value ) 
				{
					if ( isset($this->data[$key]) && $key !== 'online' )
						usam_insert_change_history(['object_id' => $id, 'object_type' => 'contact', 'operation' => 'edit',	'field' => $key, 'value' => $this->data[$key], 'old_value' => $value]);	
				}
				$this->update_cache( );		
				if ( isset($this->changed_data['status']) ) 
					usam_update_object_count_status( $this->data['status'], 'contact' );	 
				do_action( 'usam_contact_update', $this, $this->changed_data );
			}			
		} 
		else 
		{  
			do_action( 'usam_contact_pre_insert' );	
			
			$this->data = array_merge( $this->default_data(), $this->data );			
			if ( isset($this->data['appeal']) )
				$this->data['appeal'] = trim( $this->data['appeal'] );			
			if ( empty($this->data['status']) )
				$this->data['status'] = 'customer';
			if ( !empty($this->data['user_id']) )
			{
				$contact = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".USAM_TABLE_CONTACTS." WHERE user_id = '%d' ORDER BY id LIMIT 1", $this->data['user_id']), ARRAY_A );					
				if ( !empty($contact) )
				{
					$this->args = ['col' => 'id', 'value' => $contact['id']];							
					$new_data = $this->data;
					$this->data = $contact;													
					$this->exists = true;
					$this->fetched = true;					
					$update = [];
					foreach( $this->data as $key => $value)
					{
						if ( !empty($new_data[$key]) && empty($value) )
						{
							$update[$key] = $new_data[$key];
							$this->data[$key] = $new_data[$key];
						}
					}			
					if ( !empty($update) )
					{						
						$where_format = self::get_column_format( $this->args['col'] );
						$formats = $this->get_data_format( $this->data );	
						$result = $wpdb->update( USAM_TABLE_CONTACTS, $this->data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
						if ( $result )
						{
							do_action( 'usam_contact_update', $this, $this->changed_data );
							do_action( 'usam_contact_save', $this );
						}
					}
					$this->update_cache();
					return $result;
				}
			}	
			$this->data = apply_filters( 'usam_contact_insert_data', $this->data );				
			$formats = $this->get_data_format( $this->data );		
			$result = $wpdb->insert( USAM_TABLE_CONTACTS, $this->data, $formats );	
			if ( $result ) 
			{					
				$this->set( 'id', $wpdb->insert_id );				
				$this->args = ['col' => 'id',  'value' => $this->get( 'id' )];	
				$this->update_cache();	
				usam_update_object_count_status( $this->data['status'], 'contact' );				
				do_action( 'usam_contact_insert', $this );
			}			
		} 		
		if ( $result ) 
			do_action( 'usam_contact_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_contact_url( $value, $colum = 'id' )
{		
	if ( current_user_can('edit_contact') )
	{
		if ( $colum == 'user_id' )
		{
			$contact = usam_get_contact( $value, $colum );
			if ( !isset($contact['id']) )
				return '';
			$value = $contact['id'];
		}
		$link = admin_url("admin.php?page=crm&tab=contacts&form=view&form_name=contact&id=".$value);
	}
	else
		$link = '';
	return $link;
}

function usam_get_employee_url( $value, $colum = 'id' )
{		
	if ( $colum == 'user_id' )
	{
		$contact = usam_get_contact( $value, $colum );
		if ( !isset($contact['id']) )
			return '';
		$value = $contact['id'];
	}	
	$link = admin_url("admin.php?page=site_company&tab=employees&form=view&form_name=employee&id=".$value);
	return $link;
}

function usam_get_contact_foto( $value = null, $colum = 'id', $size = [100, 100] )
{	
	if ( $value === null )
	{
		$value = usam_get_contact_id();
		$colum = 'id';
	}
	$thumbnail = usam_get_no_image_uploaded_file( $size );	
	if ( empty($value) )
		return $thumbnail;
	
	$thumbnail_id = 0;
	if ( $colum == 'user_id' )
	{
		$contact_data = usam_get_contact( $value, $colum );	
		if ( isset($contact_data['id']) )
			$thumbnail_id = usam_get_contact_metadata( $contact_data['id'], 'foto' );
	}
	else
		$thumbnail_id = usam_get_contact_metadata( $value, 'foto' );
	if ( !$thumbnail_id )
		return $thumbnail;
			
	$image_attributes = wp_get_attachment_image_src( $thumbnail_id, 'small-product-thumbnail' );
	if ( !empty($image_attributes[0]) )
		$thumbnail = $image_attributes[0];	
	
	return $thumbnail;
}

function usam_update_contact_foto( $id, $thumbnail_id )
{	
	$old_thumbnail_id = usam_get_contact_metadata( $id, 'foto' );	
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
		return usam_update_contact_metadata( $id, 'foto', $thumbnail_id );
	}
	return true;
}

function usam_get_full_contact_address( $contact_id, $format = '%country%, %city%' )
{
	$customer_address = '';
	$location = usam_get_contact_metadata( $contact_id, 'location' ); 
	if ( !empty($location) ) 
		$customer_address .= usam_get_full_locations_name($location, $format);		

	$address = usam_get_contact_metadata( $contact_id, 'address' ); 
	if ( !empty($address) )
		$customer_address .= ' '.$address;	
	return $customer_address;
}

function usam_get_contact( $value = null, $colum = 'id' )
{	
	if ( $value === null )
	{
		$value = usam_get_contact_id();
		$colum = 'id';
	}
	elseif ( !$value )
		return [];
		
	$contact = new USAM_Contact($value, $colum);	
	$contact_data = $contact->get_data();
	
	if( $contact_data )
	{
		$contact_data['full_name'] = trim(usam_get_contact_metadata( $contact_data['id'], 'full_name' ));
		if( $contact_data['appeal'] )
			$contact_data['name'] = $contact_data['appeal'];
		elseif( $contact_data['full_name'] )
			$contact_data['name'] = $contact_data['full_name'];
		else
			$contact_data['name'] = sprintf(__('Контакт %d без имени'), $contact_data['id']);
	}
	return $contact_data;	
}

function usam_get_contact_name( $contact_id )
{
	return trim(usam_get_contact_metadata( $contact_id, 'full_name' ));
}

function usam_update_contact( $id, $data )
{	
	$metas = [];
	if ( isset($data['full_name']) && !isset($data['lastname']) )
	{
		$names = explode(" ", trim($data['full_name']));
		$metas["lastname"] = trim($names[0]);
		$metas["firstname"] = isset($names[1]) ? trim($names[1]) : '';
		$metas["patronymic"] = isset($names[2]) ? trim($names[2]) : '';
	}
	$contact = new USAM_Contact();	
	$default = $contact->default_data();	
	$new_data = [];
	foreach( $data as $k => $value ) 	
		if( isset($default[$k]) )
			$new_data[$k] = $value;
		else
			$metas[$k] = $value;	
	$names = usam_create_contact_name( $id, $metas );
	if( isset($names['full_name']) )
		$metas['full_name'] = $names['full_name'];
	if ( !isset($new_data['appeal']) && isset($names['appeal']) )
		$new_data['appeal'] = $names['appeal'];		
	if ( !empty($new_data) ) 
	{
		$contact = new USAM_Contact( $id );	
		$contact->set( $new_data );	
		$contact->save();
	}
	usam_update_contact_metas($id, $metas);
	return true;
}

function usam_insert_contact( $data )
{	
	global $wpdb;	
	if ( isset($data['full_name']) && !empty($data["lastname"]) )
	{
		$names = explode(" ", trim($data['full_name']));
		$data["lastname"] = trim($names[0]);
		$data["firstname"] = isset($names[1]) ? trim($names[1]) : '';
		$data["patronymic"] = isset($names[2]) ? trim($names[2]) : '';
	}	
	$contact_id = 0;	
	$check = apply_filters( 'usam_check_contact_database', null, $data );
	if ( $check === null )
	{			
		$check_meta_keys = apply_filters( 'usam_check_contact_by_field', ['email', 'phone', 'mobilephone', 'code', 'code_1c'], $data );
		$check_metas = [];
		foreach ( $check_meta_keys as $key ) 
		{
			if ( !empty($data[$key]) )
				$check_metas[$key] = "meta_key='$key' AND meta_value='".$data[$key]."'";
		}
		if ( !empty($check_metas) )
			$contact_id = $wpdb->get_var("SELECT contact_id FROM ".USAM_TABLE_CONTACT_META." WHERE ".implode(' OR ',$check_metas) );
	}
	elseif ( $check > 0 )
		$contact_id = $check;		
	
	if ( $contact_id )
	{
		$update = usam_combine_contact( $contact_id, $data );
	}
	else
	{		
		$data = array_merge(['lastname' => '', 'firstname' => '', 'patronymic' => ''], $data );	
		if ( empty($data['appeal']) )
			$data['appeal'] = usam_get_formatting_contact_name( $data );			
		$data['full_name'] = usam_get_formatting_contact_full_name( $data );
		
		$contact = new USAM_Contact();	
		$default = $contact->default_data();	
		$new_data = [];
		$metas = [];
		foreach( $data as $k => $value ) 
		{
			if ( $value )
			{
				if( isset($default[$k]) )
					$new_data[$k] = $value;
				else
					$metas[$k] = $value;
			}
		}		
		if( empty($new_data) )
		{			
			$add = false;
			foreach( $metas as $k => $value ) 
				if( $k !== 'location' )
				{
					$add = true;
					break;
				}
			if( !$add )
				return false;
		}
		if ( empty($new_data) && !empty($metas) )
			$new_data = $default;		
		
		$contact = new USAM_Contact( $new_data );	
		$contact->save();
		$contact_id = $contact->get('id');		
		usam_update_contact_metas($contact_id, $metas, true);
	}		
	return $contact_id;		 
}

function usam_update_contact_metas( $id, $metas, $new = false )
{	
	if ( empty($metas) || !$id ) 
		return false;
	
	static $properties = null;
	if ( $properties == null )
		$properties = usam_get_properties(['type' => 'contact', 'active' => 1, 'fields' => 'code=>data']);	
	foreach( $metas as $meta_key => $meta_value ) 
	{			
		if ( isset($properties[$meta_key]) )
			usam_save_property_meta( $id, $properties[$meta_key], $meta_value, $new );
		else
		{
			if ( is_array($meta_value) )
				$meta_value = array_map('wp_unslash', $meta_value);					
			else
				$meta_value = trim( wp_unslash($meta_value) );
			if( $new )
				usam_add_contact_metadata( $id, $meta_key, $meta_value );
			else
				usam_update_contact_metadata( $id, $meta_key, $meta_value );
		}
	}	
	do_action( 'usam_update_contact_metas', $id, $metas );
	return true;
}

function usam_combine_contact( $contact_id, $new_data )
{		
	$contact = usam_get_contact( $contact_id );		
	if( $contact )
	{
		foreach( $new_data as $k => $value ) 
		{
			if( !isset($contact[$k]) || $k == 'online' )
				continue;
					
			if( !$value )
			{
				unset($new_data[$k]);
				continue;
			}					
			if( $contact[$k] )
				unset($new_data[$k]);
		}	
		if ( $contact['status'] == 'temporary' && empty($new_data['status']) )
			$new_data['status'] = 'customer';
		usam_update_contact( $contact_id, $new_data );
	}
	return $new_data;		
}

function usam_delete_contact( $value, $colum = 'id' )
{	
	$contact = new USAM_Contact( $value, $colum );
	return $contact->delete();		 
}

function usam_save_or_create_contact( $data = [] )
{			
	if( empty($data['status']) )
		$data['status'] = 'customer';
	$data['user_id'] = get_current_user_id();
	$data['online'] = date("Y-m-d H:i:s");		
	$contact_id  = usam_get_contact_id();	
	if ( empty($contact_id) )
	{ 
		if ( !empty($data['location']) )
			$data['location'] = usam_get_customer_location();
		$contact_id = usam_insert_contact( $data );			
		usam_save_cookie_contact_id( $contact_id );
	}
	else	
		usam_combine_contact( $contact_id, $data );
	return $contact_id;
}

function usam_check_temporary_contact( $value = null, $colum = 'id' )
{	
	$contact = usam_get_contact( $value, $colum );
	if ( !empty($contact) && $contact['status'] == 'temporary' )
	{
		return true;
	}
	return false;
}

function usam_check_contact_communications( $contact_id = null )
{
	if ( $contact_id === null )
		$contact_id = usam_get_contact_id( );
	
	$emails = usam_get_contact_emails( $contact_id );	
	if ( empty($emails) )
	{
		$phones = usam_get_contact_phones( $contact_id );	
		if ( empty($phones) )
			return false;
		else
			return true;
	}
	else
		return true;
}

function usam_merge_contact_data( $contact_id, $contact_duplicat_id )
{
	global $wpdb;
		
	if ( !$contact_id || !$contact_duplicat_id )
		return false;
	
	$new_data = usam_get_contact( $contact_duplicat_id );	
	if ( !$new_data || $new_data['status'] != 'temporary' )
		return false;

	$wpdb->query("UPDATE ".USAM_TABLE_VISITS." SET `contact_id`={$contact_id} WHERE contact_id = $contact_duplicat_id");
	$wpdb->query("UPDATE ".USAM_TABLE_ORDERS." SET `contact_id`={$contact_id} WHERE contact_id = $contact_duplicat_id");
	$wpdb->query("UPDATE ".USAM_TABLE_DOCUMENT_CONTACTS." SET `contact_id`={$contact_id} WHERE contact_id = $contact_duplicat_id");
	$wpdb->query("UPDATE ".USAM_TABLE_CHAT_USERS." SET `contact_id`={$contact_id} WHERE contact_id = $contact_duplicat_id");
	$wpdb->query("UPDATE ".USAM_TABLE_CONTACT_ADDRESS." SET `contact_id`={$contact_id} WHERE contact_id = $contact_duplicat_id");	
	$wpdb->query("UPDATE ".USAM_TABLE_CHAT." SET `contact_id`={$contact_id} WHERE contact_id = $contact_duplicat_id");
	$wpdb->query("UPDATE ".USAM_TABLE_CUSTOMER_REVIEWS." SET `contact_id`={$contact_id} WHERE contact_id = $contact_duplicat_id");
	$wpdb->query("UPDATE ".USAM_TABLE_DOCUMENTS." SET `customer_id`={$contact_id} WHERE customer_id = $contact_duplicat_id  AND customer_type='contact'");
	$wpdb->query("UPDATE ".USAM_TABLE_RIBBON_LINKS." SET `object_id`={$contact_id} WHERE object_id = $contact_duplicat_id AND object_type='contact'");	
				
	$metas = usam_get_contact_metadata( $contact_duplicat_id );
	foreach ( $metas as $meta ) 
		$new_data[$meta->meta_key] = $meta->meta_value;
	usam_combine_contact( $contact_id, $new_data );	
	usam_delete_contact( $contact_duplicat_id );	
	
	/*
	$contact_ids = array($contact_duplicat_id, $contact_id);	
	$baskets = usam_get_users_baskets( array( 'contact_id' => $contact_ids ) );
	$duplicat_basket_ids = array();
	$basket_id = 0;
	foreach ( $baskets as $basket ) 
	{
		if ( $basket->contact_id == $contact_id  )
		{
			$basket_id = $basket->id;
		}
		else
		{
			$duplicat_basket_ids[] = $basket->id;
		}
	}	
	if ( $basket_id )
		$wpdb->query("UPDATE ".USAM_TABLE_PRODUCTS_BASKET." SET `cart_id`={$basket_id} WHERE cart_id IN (".implode(',',$duplicat_basket_ids).")");
	elseif ( !empty($duplicat_basket_ids) )
		$wpdb->update( USAM_TABLE_USERS_BASKET, array( 'contact_id' => $contact_id ), array( 'id' => $duplicat_basket_ids[0] ) );
	*/
	return true;
}

function usam_get_contact_metas( $contact_id, $formatted = 'db' ) 
{		
	if ( empty($contact_id) )
		return false; 
		
	$properties = usam_get_properties(['type' => 'contact']);	
	$metas = [];
	foreach ( $properties as $property ) 
	{		
		if ( $property->field_type == 'checkbox' )
			$value = usam_get_array_metadata( $contact_id, 'contact', $property->code );
		else
			$value = usam_get_contact_metadata( $contact_id, $property->code );		
		$metas[$property->code] = $formatted == 'display' ? usam_get_formatted_property( $value, $property ) : $value;
	}	
	$contact['full_name'] = trim($metas['lastname'].' '.$metas['firstname'].' '.$metas['patronymic']);
	return $metas;
}

function usam_check_is_employee( $value = null, $colum = 'id' )
{
	$contact = usam_get_contact( $value, $colum );	
	if ( !empty($contact) && $contact['contact_source'] == 'employee' )
		return true;
	return false;
}

function usam_get_name_contact_source( $contact_source )
{	
	$option = get_option('usam_crm_contact_source', array() );
	$contact_sources = maybe_unserialize( $option );
	
	$result = '';
	foreach ( $contact_sources as $value ) 
	{
		if ( $value['id'] == $contact_source )
		{
			$result = $value['name'];
			break;
		}
	}
	return $result;
}

function usam_get_columns_contact_export()
{	
	$columns = array(
		'code'       => __('Код', 'usam'), 		
		'full_name'  => __('ФИО', 'usam'), 
		'lastname'   => __('Фамилия', 'usam'), 
		'firstname'  => __('Имя', 'usam'), 
		'patronymic' => __('Отчество', 'usam'), 		
		'appeal'     => __('Отображение', 'usam'), 
		'status'     => __('Статус', 'usam'), 
		'contact_source' => __('Код источника', 'usam'),
		'source_name'=> __('Имя источника', 'usam'),
		'sex'        => __('Пол', 'usam'), 
		'birthday'   => __('Дата рождения', 'usam'), 
		'company_name' => __('Компания', 'usam'), 
		'post'       => __('Должность', 'usam'), 
		'manager_id' => __('Номер менеджера', 'usam'),
		'manager'    => __('Имя менеджера', 'usam'),	
		'country'    => __('Страна', 'usam'),
		'city'       => __('Город', 'usam'),		
		'foto'       => __('Ссылка на фото', 'usam'),
		'description'=> __('Описание', 'usam'),
		'date_insert'=> __('Дата создания', 'usam'),	
		'type_price' => __('Тип цены', 'usam'),
	);	
	$fields = usam_get_properties( array( 'type' => array( 'contact' ), 'active' => 1, 'fields' => 'code=>name' ) );					
	$columns += $fields;	
	return apply_filters('usam_columns_contact_export', $columns);
}


function usam_get_columns_contact_import()
{	
	$columns = array(
		'code'       => __('Код', 'usam'), 		
		'full_name'  => __('ФИО', 'usam'), 
		'lastname'   => __('Фамилия', 'usam'), 
		'firstname'  => __('Имя', 'usam'), 
		'patronymic' => __('Отчество', 'usam'), 		
		'status'     => __('Статус', 'usam'), 
		'contact_source' => __('Код источника', 'usam'),
		'source_name'=> __('Имя источника', 'usam'),
		'sex'        => __('Пол', 'usam'), 
		'birthday'   => __('Дата рождения', 'usam'), 
		'company'    => __('Компания', 'usam'), 
		'post'       => __('Должность', 'usam'), 
		'manager_id' => __('Номер менеджера', 'usam'),
		'manager'    => __('Имя менеджера', 'usam'),
		'city'       => __('Город', 'usam'),		
		'foto'       => __('Ссылка на фото', 'usam'),
		'description'=> __('Описание', 'usam'),
		'date_insert'=> __('Дата создания', 'usam'),	
		'type_price' => __('Тип цены', 'usam'),
	);	
	$fields = usam_get_properties(['type' => ['contact'], 'active' => 1, 'fields' => 'code=>name']);					
	$columns += $fields;	
	return apply_filters('usam_columns_contact_import', $columns);
}

function usam_get_contact_phones( $contact_id )
{		
	static $properties = null;
	if ( $properties === null )
		$properties = usam_get_properties(['type' => 'contact', 'active' => 1, 'field_type' => ['mobile_phone', 'phone'], 'fields' => 'code']);	
	
	$phones = array();
	foreach ( $properties as $property )
	{
		$value = usam_get_contact_metadata( $contact_id, $property );	
		if ( $value ) 
			$phones[] = $value;	
	}
	return $phones;
}

function usam_get_contact_emails( $contact_id )
{			
	static $properties = null;
	if ( $properties === null )
		$properties = usam_get_properties(['type' => 'contact', 'active' => 1, 'field_type' => ['email'], 'fields' => 'code']);	
	
	$emails = array();
	foreach ( $properties as $property )
	{
		$value = usam_get_contact_metadata( $contact_id, $property );
		if ( $value && is_email($value) ) 
			$emails[] = $value;	
	}
	return $emails;
}

function usam_get_contacts_emails( $contact_ids )
{ 	
	$emails = usam_get_contacts_communication( $contact_ids, 'email' );
	return $emails;
}

function usam_delete_contacts( $args ) 
{	
	global $wpdb;	
	$args['manager_id'] = 'all';	
	$args['source'] = 'all'; 
	$args['cache_meta'] = true;
	$contacts = usam_get_contacts( $args );	
	if ( empty($contacts) )
		return false;	
	
	usam_update_object_count_status( false );
	$ids = [];
	foreach ( $contacts as $contact )
	{						
		usam_update_object_count_status( $contact->status, 'contact' );
		$ids[] = $contact->id;
		$thumbnail_id = usam_get_contact_metadata( $contact->id, 'foto' );
		if ( $thumbnail_id )
			wp_delete_attachment($thumbnail_id, true);	
		do_action( 'usam_contact_before_delete', (array)$contact );
	}
	$wpdb->query("DELETE FROM ".USAM_TABLE_CONTACT_META." WHERE contact_id IN (".implode(",", $ids).")");
	$wpdb->query("DELETE FROM ".USAM_TABLE_CONTACT_ADDRESS." WHERE contact_id IN (".implode(",", $ids).")");
	$wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_CONTACTS." WHERE contact_id IN (".implode(",", $ids).")");
	$wpdb->query("DELETE FROM ".USAM_TABLE_SEARCHING_RESULTS." WHERE contact_id IN (".implode(",", $ids).")");	
	$wpdb->query("DELETE FROM ".USAM_TABLE_USER_POSTS." WHERE contact_id IN (".implode(",", $ids).")");
	$wpdb->query("DELETE FROM ".USAM_TABLE_OPEN_REFERRAL_LINKS." WHERE contact_id IN (".implode(",", $ids).")");
	$wpdb->query("DELETE FROM ".USAM_TABLE_DOCUMENT_CONTACTS." WHERE contact_id IN (".implode(",", $ids).")");	
	$wpdb->query("DELETE FROM ".USAM_TABLE_RIBBON_LINKS." WHERE object_type='contact' AND object_id IN (".implode(",", $ids).")");

	usam_delete_events(['type' => ['sms', 'call', 'message'], 'object_type' => 'contact', 'object_id' => $ids]);	
	
	$group_ids = $wpdb->get_col("SELECT group_id FROM ".USAM_TABLE_GROUP_RELATIONSHIPS." AS r 
	LEFT JOIN ".USAM_TABLE_GROUPS." AS g ON (g.id=r.group_id) WHERE r.object_id IN (".implode(",", $ids).") AND g.type='contact'");
	if ( $group_ids )
		$wpdb->query("DELETE FROM ".USAM_TABLE_GROUP_RELATIONSHIPS." WHERE group_id IN (".implode(",", $group_ids).")");
	
	usam_delete_object_files( $ids, 'contact' );
	usam_delete_dialogs(['user' => $ids]);
	$wpdb->query( "DELETE FROM ".USAM_TABLE_CONTACTS." WHERE id IN ('".implode("','", $ids)."')" );		
	
	foreach ( $contacts as $contact )
	{
		wp_cache_delete( $contact->id, 'usam_contact' );	
		wp_cache_delete( $contact->id, 'usam_contact_meta' );			
		
		do_action( 'usam_contact_delete', $contact->id );		
	}
	usam_update_object_count_status( true );
	return count($ids);
}

function usam_get_contacts_communication( $contact_ids, $field_type )
{ 	
	$communications = array();
	if ( !empty($contact_ids) )
	{
		static $properties = null;
		if ( $properties == null )
			$properties = usam_get_properties(['type' => 'contact', 'active' => 1, 'field_type' => $field_type, 'fields' => 'code']);
		if ( is_numeric($contact_ids[0]) )
			$contacts = usam_get_contacts( array('include' => $contact_ids) );		
		else
			$contacts = $contact_ids;
		foreach ( $contacts as $contact )
		{ 					
			foreach ( $properties as $property )
			{
				$value = usam_get_contact_metadata( $contact->id, $property );
				if ( !empty($value) )
					$communications[$value] = "$contact->appeal ($value)";
			}
		}
	}
	return $communications;
}

function usam_create_contact_name( $contact_id, $data )
{
	$result = [];
	if( isset($data['lastname']) || isset($data['firstname']) || isset($data['patronymic']) )
	{
		$names = [];
		foreach(['lastname', 'firstname', 'patronymic'] as $key ) 
			if( !isset($data[$key]) )
				$names[$key] = usam_get_contact_metadata( $contact_id, $key );
			else
				$names[$key] = $data[$key];
		$result['appeal'] = usam_get_formatting_contact_name( $names );	
		$result['full_name'] = usam_get_formatting_contact_full_name( $names );
	}
	return $result;
}

function usam_get_formatting_contact_name( $data, $format = 'lastname_f_p' )
{
	$result = '';
	if ( $data['lastname'] == '' && $format == 'lastname_f_p' )
		$format = 'firstname_patronymic';
	elseif ( $data['firstname'] == '' && $format == 'lastname_f_p' )
		$format = 'lastname_firstname_patronymic';
	switch( $format )
	{
		case 'lastname_f_p':			
			$result = $data['lastname'];
			if ( $data['firstname'] != '' )
				$result .= ' '. mb_strtoupper(mb_substr($data['firstname'],0,1)).'.';	
			if ( $data['patronymic'] != '' )
				$result .= ' '.mb_strtoupper(mb_substr($data['patronymic'],0,1)).'.';			
		break;	
		case 'lastname_firstname_patronymic':
			$result = $data['lastname'].' '.$data['firstname'].' '.$data['patronymic'];
		break;
		case 'firstname_patronymic':
			$result = $data['firstname'].' '.$data['patronymic'];
		break;
		case 'firstname':
			$result = $data['firstname'];
		break;
		case 'lastname':
			$result = $data['lastname'];
		break;
	}
	return trim($result);
}

function usam_get_formatting_contact_full_name( $data, $format = 'lastname firstname patronymic' )
{
	$name_keys = ['lastname', 'firstname', 'patronymic']; 
	foreach( $data as $k => $v )
	{ 
		if ( in_array($k, $name_keys) )
			$format = str_replace($k, $v, $format);
	}
	foreach( $name_keys as $k )	
		$format = str_replace($k, '', $format);
	return trim($format);
}

function usam_get_contact_unread_notifications( $contact_id = null )
{
	if ( !$contact_id )
		$contact_id = usam_get_contact_id();
	return usam_get_contact_metadata( $contact_id, 'unread_notifications' );
}

function usam_get_contact_metadata( $object_id, $meta_key = '', $single = true) 
{	
	$result = usam_get_metadata('contact', $object_id, USAM_TABLE_CONTACT_META, $meta_key, $single );
	switch( $meta_key )
	{
		case 'compare':
		case 'desired':
		case 'like':
		case 'unread_notifications':
		case 'sellers_counter':		
		case 'foto':	
		case 'location':
			$result = (int)$result;
		break;
	}	
	return $result;
}

function usam_update_contact_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_update_metadata('contact', $object_id, $meta_key, $meta_value, USAM_TABLE_CONTACT_META, $prev_value );
}

function usam_delete_contact_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('contact', $object_id, $meta_key, USAM_TABLE_CONTACT_META, $meta_value, $delete_all );
}

function usam_add_contact_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{	
	return usam_add_metadata('contact', $object_id, $meta_key, $meta_value, USAM_TABLE_CONTACT_META, $prev_value );
}

function usam_get_contact_id_by_meta( $key, $value ) 
{
	global $wpdb;	
	if ( !$value ) 
		return;
	
	$cache_key = "usam_contact_$key";
	$contact_id = wp_cache_get( $value, $cache_key );
	if ($contact_id === false) 
	{	
		$contact_id = (int)$wpdb->get_var($wpdb->prepare("SELECT contact_id FROM ".USAM_TABLE_CONTACT_META." WHERE meta_key='%s' AND meta_value='%s' LIMIT 1", $key, $value));
		wp_cache_set($value, $contact_id, $cache_key);
	}
	else
		$contact_id = (int)$contact_id;
	return $contact_id;
}

function usam_get_contact_ids_by_field( $field_type, $value ) 
{
	global $wpdb;	
	if ( !$value ) 
		return [];	
	
	$properties = usam_get_cache_properties('contact');
	$results = [];
	$field_type = !is_array($field_type) ? [$field_type] : $field_type;	
	foreach( $field_type as $key )
	{
		$cache_key = "usam_contact_ids_$key";
		$ids = wp_cache_get( $value, $cache_key );
		if( $ids === false ) 
		{						
			$pr = [];
			foreach ( $properties as $property )
			{
				if ( $property->field_type == $key )
					$pr[] = $property->code;
			}
			$ids = [];
			if ( $pr )
				$ids = $wpdb->get_col($wpdb->prepare("SELECT contact_id FROM ".USAM_TABLE_CONTACT_META." WHERE meta_key IN ('".implode("','", $pr)."') AND meta_value='%s' LIMIT 1", $value));	
			wp_cache_set($value, $ids, $cache_key);
		}
		$results = array_merge( $results, $ids );
	}
	$results = array_unique($results);	
	return $results;
}