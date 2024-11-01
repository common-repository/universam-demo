<?php
class USAM_File
{	// строковые
	private static $string_cols = [
		'title',		
		'name',	
		'code',		
		'type',					
		'file_path',		
		'mime_type',
		'date_insert',	
		'date_update',		
		'status',			
	];
	// цифровые
	private static $int_cols = [
		'id',		
		'object_id',
		'user_id',		
		'size',	
		'uploaded',		
		'folder_id',	
	];
	private $data = [];	
	private $changed_data = [];		
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
		if ( ! in_array( $col, ['id', 'code']) )
			return;
		
		if ( $col == 'code'  && $id = wp_cache_get( $value, 'usam_file_code' ) )
		{   // если код_сеанса находится в кэше, вытащить идентификатор
			$col = 'id';
			$value = $id;
		}					
		$this->args = array( 'col' => $col, 'value' => $value );	
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_file' );
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
		if ( $code = $this->get( 'code' ) )
			wp_cache_set( $code, $id, 'usam_file_code' );	
		wp_cache_set( $id, $this->data, 'usam_file' );	
		do_action( 'usam_file_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		$id = $this->get( 'id' );
		if ( !$id )
			return false;
		
		wp_cache_delete( $id, 'usam_file' );	
		wp_cache_delete( $this->get( 'code' ), 'usam_file_code' );	
		do_action( 'usam_file_delete_cache', $this );	
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
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_FILES." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_file_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;		
			}
			$this->fetched = true;				
			$this->update_cache( );
		} 
		do_action( 'usam_file_fetched', $this );	
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
		return apply_filters( 'usam_file_get_property', $value, $key, $this );
	}
	
	/**
	 * Возвращает строку заказа из базы данных в виде ассоциативного массива
	 */
	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_file_get_data', $this->data, $this );
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
		if ( isset($properties['code']) )
			unset($properties['code']);
		
		$properties = apply_filters( 'usam_file_set_properties', $properties, $this );			
		if ( ! is_array($this->data) )
			$this->data = array();
		foreach ( $properties as $key => $value ) 
		{	
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{				
				$previous = $this->get( $key );			
				if ( $value != $previous )
				{				
					$this->changed_data[$key] = $previous;					
				}
				$this->data[$key] = $value;
			}
		}		
		return $this;
	}

	/**
	 * Возвращает массив, содержащий отформатированные параметры
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
	
	private function get_folder( ) 
	{			
		$folder_id = 0; 	
		if ( $this->data['type'] && $this->data['type'] != 'loaded' )
		{
			static $usam_folder = null; 
			static $cahe_folders = []; 
			if ( $usam_folder === null )
			{
				$usam_folder = usam_get_folders(['fields' => 'id', 'slug' => 'universam', 'number' => 1]);
				if ( empty($usam_folder) )
					$usam_folder = usam_insert_folder(['name' => 'Universam', 'slug' => 'universam']);	  
			} 	  
			if ( isset($cahe_folders[$this->data['type']]) )
				$folder_id = $cahe_folders[$this->data['type']];
			else
			{
				$folders = usam_get_system_folders();		  
				if ( isset($folders[$this->data['type']]) )
				{ 
					$folder_id = usam_get_folders(['fields' => 'id', 'slug' => $this->data['type'], 'number' => 1]);
					if ( empty($folder_id) )
						$folder_id = usam_insert_folder(['name' => $folders[$this->data['type']], 'slug' => $this->data['type'], 'parent_id' => $usam_folder]);
					$cahe_folders[$this->data['type']] = $folder_id;			  
				}
			}
			if ( !$folder_id )
				$folder_id = $usam_folder;
		}
		return $folder_id;	
	}
		
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;	
		do_action( 'usam_file_pre_save', $this );		
		if ( !empty($this->data['name']) )
		{
			$filetype = wp_check_filetype( $this->data['name'] );
			$this->data['mime_type'] = $filetype['type'];
		}		
		if ( !empty($this->data['file_path']) )		
			$this->data['file_path'] = str_replace( USAM_UPLOAD_DIR, '', $this->data['file_path'] );
		
		$this->data['date_update'] = date( "Y-m-d H:i:s" );
		$result = false;			
		if ( $this->args['col'] ) 
		{			
			if ( empty($this->changed_data) )
				return true;		
				
			$where_format = self::get_column_format( $this->args['col'] );
			if ( isset($this->changed_data['type']) && isset($this->data['folder_id']) && $this->data['folder_id'] == 0 )
			{ 
				$this->changed_data['folder_id'] = $this->data['folder_id'];
				$this->data['folder_id'] = $this->get_folder();				
			}			
			do_action( 'usam_file_pre_update', $this );
			
			$this->data = apply_filters( 'usam_file_update_data', $this->data );
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );	
			$result = $wpdb->update( USAM_TABLE_FILES, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{ 
				$this->update_cache( );
				if ( isset($this->changed_data['folder_id']) || isset($this->changed_data['status']) )
				{		
					if ( isset($this->changed_data['folder_id']) )
					{
						if ( $this->changed_data['folder_id'] )
						{
							$folders = usam_get_folders(['ancestor' => $this->changed_data['folder_id']]);
							foreach ( $folders as $folder ) 
							{						
								$count = $folder->count - 1;							
								usam_update_folder( $folder->id, array( 'count' => $count ) );	
							}	
						} 
						if ( $this->data['folder_id'] )
						{
							$folders = usam_get_folders(['ancestor' => $this->data['folder_id']]);
							foreach ( $folders as $folder ) 
							{						
								$count = $folder->count + 1;							
								usam_update_folder( $folder->id, array( 'count' => $count ) );	
							}	
						}				
					}
					else if ( isset($this->changed_data['status']) )
					{
						if ( $this->changed_data['status'] == 'delete' )
						{
							$folders = usam_get_folders(['ancestor' => $this->changed_data['folder_id']]);
							foreach ( $folders as $folder ) 
							{						
								$count = $folder->count + 1;							
								usam_update_folder( $folder->id, array( 'count' => $count ) );	
							}	
						} 
						if ( $this->data['status'] == 'delete' )
						{
							$folders = usam_get_folders(['ancestor' => $this->data['folder_id']]);
							foreach ( $folders as $folder ) 
							{						
								$count = $folder->count - 1;							
								usam_update_folder( $folder->id, array( 'count' => $count ) );	
							}	
						}						
					}
				}				
			}
			do_action( 'usam_file_update', $this );			
		} 
		else 
		{   
			do_action( 'usam_file_pre_insert' );			
			
			if ( !isset($this->data['date_insert']) )
				$this->data['date_insert'] = date( "Y-m-d H:i:s" );				
			
			if ( isset($this->data['id']) )
				unset( $this->data['id'] );			
						
			if ( empty($this->data['title']) )
				$this->data['title'] = '';	
			
			if ( empty($this->data['status']) )
				$this->data['status'] = 'closed';						
			
			if ( empty($this->data['name']) )
				$this->data['name'] = sanitize_file_name( $this->data['title'] );	
			
			if ( empty($this->data['type']) )
				$this->data['type'] = 'attachment';		
			
			if ( empty($this->data['user_id']) )
				$this->data['user_id'] = get_current_user_id();	
			
			if ( empty($this->data['folder_id']) )
				$this->data['folder_id'] = $this->get_folder();			
			if ( empty($this->data['object_id']) )
				$this->data['object_id'] = 0;	
			
			if ( !empty($this->data['file_path']) )
			{
				$file_path = USAM_UPLOAD_DIR.$this->data['file_path'];
				if ( file_exists($file_path) )
					$this->data['size'] = filesize($file_path);		
				else
					return false;
			}			
			$this->data['code'] = sha1(uniqid(mt_rand(), true));
					
			$this->data = apply_filters( 'usam_file_insert_data', $this->data );
			$formats = $this->get_data_format( $this->data );								
			$result = $wpdb->insert( USAM_TABLE_FILES, $this->data, $formats ); 
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );
				$this->update_cache( );
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];	
				if ( $this->data['folder_id'] )
				{									
					$folders = usam_get_folders(['ancestor' => $this->data['folder_id']]);	// все папки вверх				
					foreach ( $folders as $folder ) 
					{						
						$count = $folder->count + 1;	
						$update = array( 'count' => $count );							
						usam_update_folder( $folder->id, $update );	
					}					
				}				
			}
			do_action( 'usam_file_insert', $this );
		} 		
		do_action( 'usam_file_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_file( $id, $colum = 'id' )
{
	$_file = new USAM_File( $id, $colum );
	$data = $_file->get_data( );	
	if ( $data )
		$data['file_name'] = $data['title'].'.'.pathinfo(USAM_UPLOAD_DIR.$data['file_path'], PATHINFO_EXTENSION);
	return $data;
}

function usam_delete_file( $id, $delete = false ) 
{
	return usam_delete_files(['include' => [$id]], $delete);
}

// Вставить
function usam_insert_file( $data ) 
{
	$_file = new USAM_File( $data );
	$_file->save();
	return $_file->get('id');
}

function usam_update_file( $id, $data ) 
{		
	$_file = new USAM_File( $id );
	if ( isset($data['status']) && $data['status'] === 'delete' )
	{
		$status = $_file->get( 'status' );
		if ( $status === 'delete' )
			return $_file->delete();
	}	
	$_file->set( $data );
	return $_file->save();
}

function usam_delete_object_files( $object_ids, $type ) 
{	
	if( !empty($object_ids) && $type )
	{
		if ( !is_array($object_ids) )
			$object_ids = (array)$object_ids;	
		usam_delete_files( ['object_id' => $object_ids, 'type' => $type], true );			
		foreach ( $object_ids as $object_id ) 
		{
			if ( $object_id )
			{
				$upload_dir = usam_get_upload_dir( $type, $object_id );
				if ( $upload_dir )
				{
					$dirHandle = opendir($upload_dir);
					if( !$dirHandle )
						continue;
					$empty = true;
					while( false!==($file = readdir($dirHandle)) )
					{						
						$file_path = $upload_dir.$file;
						if ( $file == '.htaccess' )
						{
							unlink($file_path);
						}
						elseif( is_file($file_path) )
						{ 				
							$empty = false;
							break;
						}
					}
					closedir($dirHandle);
					if ( $empty )				
						rmdir($upload_dir);
				}
			}
		}
	}
}

function usam_delete_files( $args, $delete = false ) 
{		
	global $wpdb;		
	$args['fields'] = ['id', 'status', 'file_path', 'folder_id'];
	$args['status'] = isset($args['status'])?$args['status']:'all';	
	$args['number'] = 500000;
	$files = usam_get_files( $args );	
	
	if ( empty($files) )
		return false;	
	
	$delete_ids = array();
	$update_ids = array();
	$folder_ids = array();
	$sizes = usam_get_registered_image_sizes();
	foreach ( $files as $file )
	{		
		if ( $file->status == 'delete' || $delete )
		{
			do_action( 'usam_file_before_delete', (array)$file );
			
			$delete_ids[] = $file->id;
			$filepath = USAM_UPLOAD_DIR.$file->file_path;				
			$pathinfo = pathinfo($filepath);	
			foreach( $sizes as $size => $size_data ) 
			{
				$filename_size = $pathinfo['filename'].'-'.$size_data['width'].'x'.$size_data['height'];
				if ( isset($pathinfo['extension']) )
					$filename_size .= '.'.$pathinfo['extension'];
				$filepath_thumbnail = str_replace($pathinfo['basename'], $filename_size, $filepath);	
				if ( is_file($filepath_thumbnail) )
					unlink( $filepath_thumbnail );
			}	
			if ( file_exists($filepath)) 
				unlink( $filepath );
		}
		else
		{
			$update_ids[] = $file->id;
			$folder_ids[$file->folder_id] = $file->folder_id;			
		}
	}	
	if ( !empty($delete_ids) )
	{
		$wpdb->query("DELETE FROM ".USAM_TABLE_FILE_META." WHERE file_id IN (".implode(',',$delete_ids).")");	
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_FILES." WHERE id IN (".implode(',',$delete_ids).")");						
	}
	if ( !empty($update_ids) )
	{
		$wpdb->query("UPDATE ".USAM_TABLE_FILES." SET date_update='".date("Y-m-d H:i:s")."', status='delete' WHERE `id` IN (".implode(',',$update_ids).")");		
		foreach ( $folder_ids as $folder_id )
		{
			$folders = usam_get_folders(['ancestor' => $folder_id]);	// все папки вверх				
			foreach ( $folders as $folder ) 
			{						
				if ( $folder_id == $folder->id )
					continue;				
				usam_update_folder( $folder->id, ['count' => ($folder->count - 1)] );	
			}
			$ids = usam_get_folders(['child_of' => $folder_id, 'fields' => 'id']);	
			$ids[] = $folder_id;
			$count = (int)$wpdb->get_var("SELECT COUNT(*) FROM `".USAM_TABLE_FILES."` WHERE status!='delete' AND folder_id IN (".implode(',',$ids).")");	
			usam_update_folder( $folder_id, ['count' => $count]);
		}
	}
	return count($delete_ids) + count($update_ids);
}
		
function usam_get_system_folders(  ) 
{ 
	$folders = ['review' => __("Отзывы","usam"), 'product' => __("Товары","usam"),'document' => __("Документы","usam"), 'events' => __("CRM","usam"), 'feedback' => __("Обращения","usam"), 'email' => __("Почта","usam"), 'seal' => __("Подписи и печати","usam")];
	$types = usam_get_events_types();
	foreach ( $types as $key => $type ) 
	{
		$folders[$key] = $type['plural_name'];
	}	
	return $folders;
}

function usam_get_upload_dir( $type, $object_id = 0 ) 
{
	$mode = 0775;
	$htaccess = "order deny,allow\n\r";
	$htaccess .= "deny from all\n\r";
	$htaccess .= "allow from none\n\r";
	switch ( $type ) 
	{
		case 'R' :
		case 'email' :
			$dir = 'e-mails';
		break;
		case 'document' :
			$dir = 'documents';
		break;	
		case 'chat' :
			$dir = 'chat';
		break;		
		case 'seal' :
			$dir = 'seal';
			$mode = 0777;
			$htaccess = '';
		break;
		case 'downloadables' :			
		default:				
			$types_event = usam_get_events_types( );
			if ( isset($types_event[$type]) )	
				$dir = 'events';
			elseif ( $type != '' )
				$dir = $type;	
			else
			{	
				$dir = 'downloadables';
				$object_id = get_current_user_id();
			}
		break;
	}
	$upload_dir = USAM_UPLOAD_DIR.$dir.'/';
	if ( $object_id )
	{
		$upload_dir .= $object_id.'/';
		$htaccess = '';
	}
	if( !is_dir($upload_dir) )
	{
		if (!mkdir($upload_dir, $mode, true)) 
		{		
			if ( $htaccess )
			{
				$file_handle = @fopen( $upload_dir . ".htaccess", 'w+' );
				@fwrite( $file_handle, $htaccess );
				@fclose( $file_handle );
				@chmod( $file_handle, 0665 );
			}
		}
	}
	return $upload_dir;
}

function usam_add_file_url_from_files_library( $url, $args ) 
{
	require_once( ABSPATH . "wp-admin" . '/includes/file.php');	
	$tmp = download_url( $url );			
	$attachment_id = false;
	if( ! is_wp_error($tmp) )
	{ 
		$path_parts = pathinfo($url);	
		$args['type'] = !empty($args['type'])?$args['type']:'loaded';
		$args['object_id'] = !empty($args['object_id'])?$args['object_id']:0;
		$upload_dir = usam_get_upload_dir( $args['type'], $args['object_id'] );
		$new_filename = wp_unique_filename( $upload_dir, $path_parts['basename'] );
		$args['file_path'] = $upload_dir.$new_filename;
		$result = copy( $tmp, $args['file_path'] );		
		if ( $result )
		{			
			$args['name'] = $path_parts['filename'];	
			$args['title'] = !empty($args['title'])?$args['title']:$path_parts['filename'];					
			$attachment_id = usam_insert_file( $args );
		}
		unlink($tmp);	
	}
	else
		usam_log_file( __('Загрузка файла в библиотеку','usam').': '. $tmp->get_error_message() );
	return $attachment_id;
}

function usam_add_file_from_files_library( $file_path, $args, $delete = false ) 
{
	if ( !file_exists($file_path)) 
		return false;

	$filename = basename($file_path);			
	$type = !empty($args['type'])?$args['type']:'';
	$status = !empty($args['status'])?$args['status']:'closed';
	$object_id = !empty($args['object_id'])?$args['object_id']:0;

	$upload_dir = usam_get_upload_dir( $type, $object_id );	
	$new_filename = wp_unique_filename( $upload_dir, $filename );
	$new_file_path = $upload_dir.$new_filename;	
	if ( !copy($file_path, $new_file_path) )										
		return false;	
	
	$file_ext = usam_get_extension( $new_file_path );	
	$title = !empty($args['title'])?$args['title']:basename($filename, '.' . $file_ext );
			
	$id = usam_insert_file(['object_id' => $object_id, 'title' => $title, 'name' => $filename, 'file_path' => $new_file_path, 'type' => $type, 'status' => $status]);	
	if ( $delete ) 	
		unlink($file_path);		
	return $id;
}

function usam_attach_file( $file_id, $args = [], $metas = [] ) 
{ 
	$file_id = absint($file_id);
	$update = usam_get_file( $file_id );				
	$result = false;	
	if ( !empty($update) )
	{					
		$type = !empty($args['type']) ? $args['type'] : 'loaded';
		$object_id = !empty($args['object_id']) ? $args['object_id'] : 0;
		$update = array_merge( $update, $args );		
		$upload_dir = usam_get_upload_dir( $type, $object_id );				
		if ( USAM_UPLOAD_DIR.$update['file_path'] != $upload_dir.$update['name'] )										
		{	
			$new_filename = wp_unique_filename( $upload_dir, $update['name'] );
			$file_path = $upload_dir.$new_filename;		
			if ( !@copy(USAM_UPLOAD_DIR.$update['file_path'], $file_path) )		
				return $result;
			unlink(USAM_UPLOAD_DIR.$update['file_path']);
		}		
		else
			$file_path = USAM_UPLOAD_DIR.$update['file_path'];
		$update['file_path'] = $file_path;				
		$update['type'] = $type;
		$result = usam_update_file( $update['id'], $update );
		foreach ( $metas as $key => $value )
		{
			if ( $value )
				usam_update_file_metadata($file_id, $key, $value );
			else
				usam_delete_file_metadata($file_id, $key );
		}
	}
	return $result;
}

function usam_get_file_metadata( $object_id, $meta_key = '', $single = true) 
{	
	$value = usam_get_metadata('file', $object_id, USAM_TABLE_FILE_META, $meta_key, $single );	
	return $value;
}

function usam_update_file_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('file', $object_id, $meta_key, $meta_value, USAM_TABLE_FILE_META, $prev_value );
}

function usam_delete_file_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('file', $object_id, $meta_key, USAM_TABLE_FILE_META, $meta_value, $delete_all );
}

function usam_add_file_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('file', $object_id, $meta_key, $meta_value, USAM_TABLE_FILE_META, $prev_value );
}

function usam_get_statuses_files( )
{
	$statuses = ['closed' => __('Закрытый','usam'), 'open' => __('Открытый','usam'), 'limited' => __('Ограниченно доступный','usam'), 'delete' => __('Удаленный','usam')];	
	return $statuses;
}

function usam_get_file_status_name( $key_status ) 
{
	$statuses = usam_get_statuses_files( );	
	if ( isset($statuses[$key_status]) )
		return $statuses[$key_status];
	else
		return '';
}

function usam_get_types_files( )
{
	$types = array( 'loaded' => __('Загружен в библиотеку','usam') );
	return apply_filters( 'usam_register_types_files', $types );	
}

function usam_get_file_link( $code, $thumbnail = false )
{
	$link = get_bloginfo('url').'/file/'.$code;
	if ( $thumbnail )
		$link .= '?size=thumbnail';
	return $link;
}

function usam_get_registered_image_sizes()
{ 
	$sizes = [
		'thumbnail' => ['width' => 200, 'height' => 200]
	];
	return $sizes;
}
	

function usam_get_given_image_size( $filepath, $required_size = 'thumbnail' )
{
	$result = '';
	$pathinfo = pathinfo($filepath);
	$sizes = usam_get_registered_image_sizes();
	if ( isset($sizes[$required_size]) )
	{
		$filename_size = $pathinfo['filename'].'-'.$sizes[$required_size]['width'].'x'.$sizes[$required_size]['height'];
		if ( isset($pathinfo['extension']) )
			$filename_size .= '.'.$pathinfo['extension'];
		$filepath_thumbnail = str_replace($pathinfo['basename'], $filename_size, $filepath);
		if ( is_file($filepath_thumbnail) )
			$result = $filepath_thumbnail;
	}	
	if ( !$result )
	{	
		if ( $required_size == 'thumbnail' )
			usam_create_image_sizes( $filepath );	
		$result = $filepath;
		foreach( $sizes as $size => $size_data ) 
		{
			$filename_size = $pathinfo['filename'].'-'.$size_data['width'].'x'.$size_data['height'];
			if ( isset($pathinfo['extension']) )
				$filename_size .= '.'.$pathinfo['extension'];
			$filepath_thumbnail = str_replace($pathinfo['basename'], $filename_size, $filepath);
			if ( is_file($filepath_thumbnail) )
				$result = $filepath_thumbnail;
		}
	}
	return $result;
}
?>