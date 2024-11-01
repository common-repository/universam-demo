<?php
class USAM_Property
{
	// строковые
	private static $string_cols = [
		'name',	
		'description',			
		'type',
		'code',		
		'mask',
		'field_type',
		'group',
	];
	// цифровые
	private static $int_cols = [
		'id',			
		'active',		
		'sort',		
		'mandatory',	
		'show_staff',		
	];		
	private $data = [];		
	private $fetched = false;
	private $args = ['col'   => '', 'value' => ''];	
	private $exists = false; // если существует строка в БД

	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, ['id'] ) )
			return;
					
		$this->args = ['col' => $col, 'value' => $value];	
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_property' );
		}			
		// кэш существует
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
	}
	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';

		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'usam_property' );	
		do_action( 'usam_property_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_property' );	
		do_action( 'usam_property_delete_cache', $this );	
	}

	/**
	 * Удаляет из базы данных
	 */
	public function delete(  ) 
	{		
		global  $wpdb;
		
		$id = $this->get('id');
		$data = $this->get_data();
		do_action( 'usam_property_before_delete', $data );		
		$result = $wpdb->query("DELETE FROM ".usam_get_table_db('properties')." WHERE id = '$id'");		
		
		$this->delete_cache( );		
		do_action( 'usam_property_delete', $id );
		
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
		$sql = $wpdb->prepare( "SELECT * FROM ".usam_get_table_db('properties')." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_property_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;			
			}
			$this->fetched = true;				
			$this->update_cache();
		}		
		do_action( 'usam_property_fetched', $this );	
		$this->fetched = true;			
	}

	/**
	 * Если строка существует в БД
	 */
	public function exists() 
	{		
		$this->fetch();
		return $this->exists;
	}
	/**
	 * Возвращает значение указанного свойства
	 */
	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_property_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_property_get_data', $this->data, $this );
	}

	
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
		$properties = apply_filters( 'usam_property_set_properties', $properties, $this );
	
		if ( ! is_array($this->data) )
			$this->data = array();
		$this->data = array_merge( $this->data, $properties );			
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
	 */
	private function get_data_format( ) 
	{
		$formats = array();
		foreach ( $this->data as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )		
				$formats[$key] = $format;	
			else
				unset($this->data[$key]);
		}
		return $formats;
	}	
	
	private function data_format( ) 
	{
		foreach ( $this->data as $key => $value ) 
		{			
			if ( in_array( $key, self::$string_cols ) && !is_array($value) )
				$this->data[$key] = stripcslashes($value);
		}		
	}
		
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_property_pre_save', $this );	
		$where_col = $this->args['col'];
		
		if ( isset($this->data['code']) )
			$this->data['code'] = str_replace('-', '_', $this->data['code']);		
		$result = false;	
		if ( $where_col ) 
		{	
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_property_pre_update', $this );			
			$where = array( $this->args['col'] => $where_val);

			$this->data = apply_filters( 'usam_property_update_data', $this->data );	
			$formats = $this->get_data_format( );
			$this->data_format( );	
			
			$result = $wpdb->update( usam_get_table_db('properties'), $this->data, $where, $formats, array( $where_format ) );	
			if ( $result ) 
				$this->delete_cache( );			
			do_action( 'usam_property_update', $this );
		} 
		else 
		{  
			do_action( 'usam_property_pre_insert' );			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );	
			
			if ( !isset($this->data['active']) )
				$this->data['active'] = 1;	
			
			if ( empty($this->data['type']) )
				return false;
			
			if ( empty($this->data['code']) && !empty($this->data['name']) )
				$this->data['code'] = sanitize_title($this->data['name']);
			$codes = $wpdb->get_col( "SELECT code FROM ".usam_get_table_db('properties')." WHERE code LIKE '".$this->data['code']."%' AND type='".$this->data['type']."'" );
			if ( !empty($codes) )
			{					
				$i = 0;
				$new_code = $this->data['code'];
				do 
				{					
					if ( $i )
						$new_code = $this->data['code'].'-'.$i;
					$b = false;
					foreach( $codes as $code )	
					{
						if ( $code == $new_code )
						{
							$b = true;
							break;
						}
					}						
					$i++;					
				} 
				while ( $b );
				$this->data['code'] = $new_code;
			}	
			$this->data = apply_filters( 'usam_property_insert_data', $this->data );
			$format = $this->get_data_format(  );
			$this->data_format( );	
				
			$result = $wpdb->insert( usam_get_table_db('properties'), $this->data, $format ); 
			if ( $result ) 
			{
				$this->set( 'id', $wpdb->insert_id );
				$this->args = array('col' => 'id',  'value' => $wpdb->insert_id );	
				do_action( 'usam_property_insert', $this );				
			}			
		} 		
		do_action( 'usam_property_save', $this );

		return $result;
	}
}

function usam_get_property( $id, $colum = 'id' )
{
	$property = new USAM_Property( $id, $colum );
	return $property->get_data( );	
}

function usam_delete_property( $id ) 
{
	$property = new USAM_Property( $id );
	$result = $property->delete( );
	return $result;
}

function usam_insert_property( $data ) 
{
	$property = new USAM_Property( $data );
	$property->save();
	return $property->get('id');
}

function usam_update_property( $id, $data ) 
{
	$property = new USAM_Property( $id );
	$property->set( $data );
	return $property->save();
}


function usam_get_property_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('property', $object_id, usam_get_table_db('property_meta'), $meta_key, $single );
}

function usam_update_property_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('property', $object_id, $meta_key, $meta_value, usam_get_table_db('property_meta'), $prev_value );
}

function usam_delete_property_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('property', $object_id, $meta_key, usam_get_table_db('property_meta'), $meta_value, $delete_all );
}

function usam_add_property_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_add_metadata('property', $object_id, $meta_key, $meta_value, usam_get_table_db('property_meta'), $prev_value );
}

function usam_save_property_meta( $id, $p, $value, $new = false )
{	
	if( ($p->field_type == 'email' || $p->field_type == 'phone' || $p->field_type == 'mobile_phone') && !current_user_can('view_communication_data') && current_user_can('store_section') )
		return false;
				
	$add_function = "usam_add_{$p->type}_metadata";
	$update_function = "usam_update_{$p->type}_metadata";
	$delete_function = "usam_delete_{$p->type}_metadata";
	$meta_value = usam_sanitize_property( $value, $p->field_type );				
	$result = false;
	$operation = 'edit';
	if ( $p->field_type == 'checkbox' )
	{
		$result = usam_save_meta( $id, $p->type, $p->code, $meta_value );						
	}
	elseif ( !empty($meta_value) )	
	{
		if( $new )
			$ok = $add_function( $id, $p->code, $meta_value );
		else
			$ok = $update_function( $id, $p->code, $meta_value );	
		if( $ok )
		{ 
			$result = true;
			if ( $p->field_type == 'files' )
			{			
				$group = usam_get_property_groups(['code' => $p->group, 'type' => $p->type, 'number' => 1]);
				$title = $group[0]->name;
				foreach ( $meta_value as $key => $file_id ) 
				{
					$_title = $key?"$title - $key":$title;
					usam_update_file( $file_id, ['object_id' => $id, 'type' => $p->type, 'title' => $_title]);
				}
			}
			elseif ( $p->field_type == 'file' )
			{
				$group = usam_get_property_groups(['code' => $p->group, 'type' => $p->type, 'number' => 1]);
				$title = $group[0]->name;
				usam_update_file( $meta_value, ['object_id' => $id, 'type' => $p->type, 'title' => $title]);
			}
		}		
	}
	elseif ( $new == false )
	{
		$result = $delete_function( $id, $p->code );
		$operation = 'delete';
	}	
	usam_insert_change_history(['object_id' => $id, 'object_type' => $p->type, 'sub_object_id' => $p->id, 'operation' => $operation, 'field' => 'property', 'value' => $meta_value, 'old_value' => '']);
		
	if ( $result )
		do_action( "usam_save_property_{$p->type}_meta", $id, $p, $value, $new );
	return $meta_value;
}

//Получить данные контакта или компании из вебформы
function usam_get_CRM_data_from_webform( $data, $type = 'order' )
{ 
	$properties = usam_get_cache_properties( $type );
	$results = ['contact' => [], 'company' => []]; 	
	foreach ($properties as $code => $property)
	{
		$connection = usam_get_property_metadata($property->id, 'connection');
		if( !empty($connection) )
		{
			if( stripos($connection, 'company-') !== false )
			{
				$key = 'company';
				$connection = str_replace('company-', '',  $connection);				
			}
			elseif( $connection == 'full_name' )
			{
				if ( !empty($data[$code]) )
				{
					$results['contact']['full_name'] = $data[$code];
					$name = explode(' ',  $data[$code]);					
					$results['contact']['lastname'] = !empty($name[0])?$name[0]:'';
					$results['contact']['firstname'] = !empty($name[1])?$name[1]:'';
					$results['contact']['patronymic'] = !empty($name[2])?$name[2]:'';
				}
				continue;
			}
			else
				$key = 'contact';			
			if( !empty($data[$code]) )
				$results[$key][$connection] = $data[$code];		
		}
	}
	return $results;
}

//Получить данные вебформы из CRM
function usam_get_webform_data_from_CRM( $data, $type = 'order', $type_payer = '' )
{ 
	$args = ['type' => $type, 'fields' => 'id=>code', 'cache_meta' => true];
	if ( $type_payer )
		$args['type_payer'] = $type_payer;
	$properties = usam_get_properties( $args );		
	$results = []; 	
	foreach( $properties as $id => $code )
	{
		$connection = usam_get_property_metadata($id, 'connection');				
		if ( !empty($connection) )
		{
			$connection = preg_replace('/company-/', '', $connection, 1);	
			if ( !empty($data[$connection]) )
				$results[$code] = $data[$connection];
		}
	}
	return $results;
}

function usam_get_cache_properties( $type = 'order', $access = false ) 
{						
	$object_type = 'usam_properties';	
	$cache = wp_cache_get( $type, $object_type );		
	if( $cache === false )			
	{							
		$cache = usam_get_properties(['type' => $type, 'fields' => 'code=>data', 'cache_meta' => true]);
		wp_cache_set( $type, $cache, $object_type );						
	}	
	if( $access )
	{
		$user = wp_get_current_user();
		$roles = empty($user->roles)?['notloggedin']:$user->roles;
		foreach( $cache as $k => $property )
		{
			$property_roles = usam_get_array_metadata($property->id, 'property', 'role');				
			if( !empty($property_roles) && !array_intersect($roles, $property_roles) )
				unset($cache[$k]);
		}
	}
	return $cache;
}


function usam_cache_properties( $types = [] ) 
{						
	$object_type = 'usam_properties';
	foreach( $types as $k => $type )
	{		
		$cache = wp_cache_get( $type, $object_type );
		if ( $cache !== false )		
			unset($types[$k]);
	}
	if( !empty($types) )
	{
		$properties = usam_get_properties(['type' => $types, 'fields' => 'code=>data', 'cache_meta' => true]);
		$results = [];
		foreach( $properties as $code => $property )
			$results[$property->type][$property->code] = $property;
		foreach( $results as $type => $data )
			wp_cache_set( $type, $data, $object_type );
	}
}


function usam_get_property_value( $property, $company_id = 0 )
{	
	$value = '';
	if ( is_user_logged_in() )
	{
		$contact_id = usam_get_contact_id( );
		if ( $company_id == 0 && !empty($_GET['company_id']) )
		{
			$company_id = absint($_GET['company_id']);
		}		
		if ( $contact_id )
		{ 
			$connection = usam_get_property_metadata($property->id, 'connection');
			if ( $connection )
			{ 
				if ( stripos($connection, 'company-') === 0 )
				{
					$connection = preg_replace('/company-/', '', $connection, 1);
					$value = (string)usam_get_company_metadata($company_id, $connection );
				}
				elseif ( 'full_name' == $connection )
				{
					$contact = usam_get_contact( $contact_id ); 
					$value = trim($contact['lastname'].' '.$contact['firstname'].' '.$contact['patronymic']); 
				}				
				else
				{
					$contact = usam_get_contact( $contact_id ); 
					if ( isset($contact[$connection]) )
						$value = $contact[$connection];
					else
					{
						$value = (string)usam_get_contact_metadata($contact_id, $connection );
						if ( $property->field_type === false )
							$value = '';
					}
				}
			}
		}		
	}	
	return $value;
}

// Получить форматированное 
function usam_get_formatted_property( $value, $property ) 
{
	$display = '';
	switch ( $property->field_type )
	{							
		case "file":
			if ( $value )
			{
				$file = usam_get_file( $value );
				$url = get_bloginfo('url').'/file/'.$file['code'];				
				return "<div class='usam_attachments'><div class='usam_attachments__file'><a href='".$url."' target='_blank'><div class='attachment_icon'><img src='".usam_get_file_icon( $file['id'] )."'/></div></a><div class='filename'>".$file['file_name']."</div><div class='attachment__file_data__filesize'><a download href='".$url."' title ='".__('Сохранить этот файл себе на компьютер','usam')."' target='_blank'>".__('Скачать','usam')."</a></div></div></div>";
			}
		break;
		case "files":
			$display = $value;
		break;
		case 'phone':	
		case 'mobile_phone':	
			$display = usam_get_phone_format( $value );
		break;
		case 'company':	
			if ( is_numeric($value) )
			{
				$company = usam_get_company( $value );
				$display = isset($company['name'])?$company['name']:'';
			}
			else
				return $value;
		break;		
		case 'location':	
			$display = usam_get_full_locations_name( $value, '%street%, %city%, %subregion% %country%', true ); 
		break;
		case "checkbox":
			$options = usam_get_property_metadata($property->id, 'options');	
			if ( !empty($options) ) 
			{
				$results = [];
				if ( is_array($value) )
				{
					foreach ( $options as $option ) 
					{
						if ( in_array($option['code'], $value) )
							$results[] = esc_html__( $option['name'] );
					}
				}
				$display = implode(", ", $results);
			}
		break;						
		case "select":						
			$options = usam_get_property_metadata($property->id, 'options');
			if ( !empty($options) ) 
			{
				foreach ( $options as $option ) 
				{
					if ( $option['code'] == $value )
					{
						$display = esc_html__( $option['name'] );
						break;
					}
				}
			}
		break;
		case "radio":						
			$options = usam_get_property_metadata($property->id, 'options');
			if ( !empty($options) ) 
			{
				foreach ( $options as $option ) 
				{
					if ( $option['code'] == $value )
					{
						$display = esc_html__( $option['name'] );
						break;
					}
				}
			}
		break;
		case "rating":	
			$results = usam_get_rating( $value );		
		break;
		case 'link':			
			if ( $value )
			{
				$url = stripos($value, 'http') === false ? "http://{$value}":$value;
				$display = "<a href='$url'>$value</a>";			
			}
		break;
		case 'shops':			
			$value = absint($value);
			$storage = usam_get_storage( $value );
			if( !empty($storage['title']) )
			{
				$display = $storage['title'];
			}
		break;	
		case 'agreement':		
		case 'personal_data':
		case 'one_checkbox':			
			$display = $value ? __("Да","usam"):__("Нет","usam");
		break;
		case 'date':					
			$display = $value ? date("Y-m-d", strtotime($value) ) : '';							
		break;
		case 'location_type':					
			if(is_numeric($value))
			{
				$location = usam_get_location( $value );
				$display = $location['name'];
			}
			else
				$display = $value;								
		break;
		default:
			$display = esc_html($value);
	}
	return $display;
}


function usam_get_object_property_value( $id, $p ) 
{
	if ( $p->field_type == 'checkbox' )
		$p->value = usam_get_array_metadata( $id, $p->type, $p->code );
	else
	{
		$function = "usam_get_{$p->type}_metadata";
		$p->value = $function( $id, $p->code );
	}
	return usam_format_property_api( $p );
}

function usam_format_property_api( $p ) 
{	
	if ( $p->value === false )
		$p->value = '';	
	if ( $p->field_type == 'select' || $p->field_type == 'radio' || $p->field_type == 'checkbox' )
	{
		$p->options = [];
		$options = usam_get_property_metadata($p->id, 'options');
		foreach ( $options as $option )
		{
			$p->options[] = ['id' => $option['code'], 'name' => $option['name'], 'group' => $option['group']];
		}
	}
	elseif( $p->field_type == 'email' || $p->field_type == 'phone' || $p->field_type == 'mobile_phone' )
	{		
		$p->reason = usam_check_communication_error( $p->value, $p->field_type );
		$p->communication_error = $p->reason?usam_get_text_communication_error( $p->field_type, $p->reason ):'';
		if( $p->value && !current_user_can('view_communication_data') && current_user_can('store_section') )
		{
			$p->hidden = true;
			$p->private = $p->value;
			$p->value = usam_get_hiding_data( $p->value, $p->field_type );
			if( $p->field_type == 'phone' || $p->field_type == 'mobile_phone' )
				$p->mask = str_replace("#","X",$p->mask);			
		}
		else
			$p->hidden = false;
	}	
	elseif ( $p->field_type == 'agreement' )
	{
		$p->name_agreement = usam_get_property_metadata($p->id, 'name_agreement');
		$p->agreement = usam_get_property_metadata($p->id, 'agreement');	
	}		
	elseif ( $p->field_type == 'shops' )
	{
		$p->options = [];
		$options = usam_get_property_metadata($p->id, 'options');
		$storages = usam_get_storages(['issuing' => 1]);
		foreach ( $storages as $storage )
		{
			$p->options[] = ['id' => $storage->id, 'name' => $storage->title];
		}
	}
	elseif ( $p->field_type == 'one_checkbox' || $p->field_type == 'personal_data' )
	{
		$p->value = (bool)$p->value;
	}		
	elseif ( $p->field_type == 'location'  )
	{
		$p->search = usam_get_full_locations_name( $p->value, '%street% %village% %urban_area% %city% %subregion% %region% %country%' );
	}
	elseif ( $p->field_type == 'date' )
	{	
		if ( $p->value )
			$p->value = date('d.m.Y', strtotime($p->value));
	}		
	elseif ( $p->field_type == 'file' )
	{			 
		$p->file = new stdClass();
		$file = usam_get_file( $p->value );
		if ( $file )
		{
			$p->file = $file;
			$filepath = USAM_UPLOAD_DIR.$file['file_path'];				
			$p->file['size'] = file_exists($filepath)? size_format( filesize($filepath) ):'';
			$p->file['icon'] = usam_get_file_icon( $file['id'] );
			$p->file['url'] =  get_bloginfo('url').'/file/'.$file['code'];
		}
		else
			$p->value = '';
	}
	elseif ( $p->field_type == 'files' )
	{			 
		$property_files = [];
		if ( is_array($p->value) && !empty($p->value) )
		{	
			$files = usam_get_files(['include' => $p->value]);
			foreach ( $files as $file )
			{	
				$filepath = USAM_UPLOAD_DIR.$file->file_path;				
				$file->size = file_exists($filepath)? size_format( filesize($filepath) ):'';
				$file->icon = usam_get_file_icon( $file->id );
				$file->url = get_bloginfo('url').'/file/'.$file->code;
				$property_files[] = $file;
			}
		}
		else
			$p->value = [];
		$p->files = $property_files;
	}
	else
	{
		if( is_array($p->value) )
			$p->value = array_map('htmlspecialchars_decode', $p->value);
		else
			$p->value = htmlspecialchars_decode($p->value);
	}
	$p->error = 0;
	return $p;
}

function usam_save_file_property( $files, $field_type, $object ) 
{	
	if ( !empty($files) )
	{
		if ( !is_array($files) )
			$files = (array)$files;
		if ( $field_type == 'file' || $field_type == 'files' )
		{		
			$files = usam_get_files(['include' => $files, 'cache_results' => true]);
			foreach( $files as $file )
			{
				if ( !$file->folder_id )
				{
					$parent_id = usam_get_folders(['fields' => 'id', 'slug' => $object['type'], 'number' => 1]);
					if ( empty($parent_id) )
					{											
						$result = usam_get_object( (object)$object );	
						$object['folder_id'] = usam_insert_folder(['name' => $result['name'], 'slug' => $object['type']]);
					}
				}
				usam_update_file($file->id, $object );	
			}
			return $files;
		}
	}
	return [];		
}


function usam_format_product_attributes_api( $product_id, $property, $attribute_values )
{	
	foreach( ['important', 'mandatory', 'do_not_show_in_features', 'rating', 'switch_to_selection', 'filter', 'sorting_products', 'compare_products', 'search', 'admin_column'] as $key )
		$property->$key = (int)usam_get_term_metadata($property->term_id, $key);	
	$property->field_type = usam_get_term_metadata($property->term_id, 'field_type');
	if ( $property->field_type == 'COLOR_SEVERAL' || $property->field_type == 'M')
	{								
		$property->options = isset($attribute_values[$property->term_id]) ? $attribute_values[$property->term_id] : [];
		$metas = usam_get_product_attribute($product_id, $property->slug, false);	
		$property->value = [];
		if ( !empty($metas) )
			foreach( $metas as $meta )
			{
				$property->value[] = (int)$meta->meta_value;
			}
		foreach( $property->options as $i => $option )
			$property->options[$i]['checked'] = in_array($option['id'], $property->value);
	}							
	elseif ( $property->field_type == 'AUTOCOMPLETE' )
	{						
		if( $product_id )
			$property->value = (string)usam_get_product_attribute($product_id, $property->slug);
		$property->search = '';
		if ( $property->value && !is_numeric($property->value) && !empty($attribute_values[$property->term_id]) )
		{	
			foreach( $attribute_values[$property->term_id] as $option )
			{
				if ( $option['name'] == $property->value )
				{									
					$property->value = $option['id'];
					break;
				}
			}
		}
		foreach( $attribute_values[$property->term_id] as $option )
		{
			if ( $option['id'] == $property->value )
			{
				$property->search = $option['name'];
				break;
			}
		}
	}
	elseif( usam_attribute_stores_values( $property->term_id ) )
	{						
		$property->value = (string)usam_get_product_attribute($product_id, $property->slug);
		if ( $property->value && !is_numeric($property->value) && !empty($attribute_values[$property->term_id]) )
		{	
			foreach( $attribute_values[$property->term_id] as $option )
			{
				if ( $option['name'] == $property->value )
				{									
					$property->value = $option['id'];
					break;
				}
			}
		}
		$property->options = isset($attribute_values[$property->term_id]) ? $attribute_values[$property->term_id] : [];
	}
	elseif ( $property->field_type == 'F' )
	{								
		$property->value = (int)usam_get_product_attribute($product_id, $property->slug);
		$post = get_post( $property->value );
		$property->file = new stdClass();
		if ( $post )
		{
			$property->file->id = $post->ID;	
			$property->file->title = $post->post_title;
			$property->file->url = wp_get_attachment_image_url( $post->ID, 'medium' );					
		}
	}
	elseif ( $property->field_type == 'PRICES' )
	{
		$type_prices = usam_get_prices();
		$property->options = [];
		foreach( $type_prices as $type_price )
		{
			$property->options[] = ['id' => $type_price['code'], 'name' => $type_price['title']];
		}
		$property->value = (string)usam_get_product_attribute($product_id, $property->slug);								
	
	}
	elseif ( $property->field_type == 'A' )
	{						
		$contacts = usam_get_contacts(['source' => 'employee', 'orderby' => 'name']);
		$property->options = [];
		foreach( $contacts as $contact )
		{
			$property->options[] = ['id' => $contact->id, 'name' => $contact->appeal];
		}
		$property->value = (string)usam_get_product_attribute($product_id, $property->slug);
	}							
	else
		$property->value = (string)usam_get_product_attribute($product_id, $property->slug);	
	return $property;		
}

function usam_get_field_types()
{ 	
	$field_types = [
		'text'         => __('Текст', 'usam'),
		'textarea'     => __('Текстовая область', 'usam'),			
		'email'        => __('Электронная почта', 'usam'),
		'mobile_phone' => __('Мобильный телефон', 'usam'),
		'phone'        => __('Телефон', 'usam'),			
		'link'         => __('Ссылка', 'usam'),	
		'one_checkbox' => __('Галочка', 'usam'),		
		'personal_data' => __('Согласие на обработку персональных данных', 'usam'),
		'agreement'     => __('Согласие с условиями', 'usam'),		
		'button'       => __('Кнопка', 'usam'),	
		'click_show'   => __('Нажать и показать', 'usam'),	
		'select'       => __('Выбор из списка', 'usam'),
		'radio'        => __('Выбор переключателем', 'usam'),
		'checkbox'     => __('Выбор нескольких значений', 'usam'),			
		'location'     => __('Местоположение', 'usam'),
		'location_type' => __('Текстовое местоположение', 'usam'),	
		'company'       => __('Поиск компаний', 'usam'),						
		'address'      => __('Адрес', 'usam'),
		'postcode'     => __('Почтовый код', 'usam'),
		'shops'        => __('Список магазинов', 'usam'),		
		'date'         => __('Дата', 'usam'),
		'file'         => __('Файл', 'usam'),
		'files'        => __('Файлы', 'usam'),
		'rating'       => __('Рейтинг', 'usam'),
		'none'         => __('Только название поля', 'usam'),
		'pass'         => __('Пароль', 'usam'),
	];
	return $field_types;
}
?>