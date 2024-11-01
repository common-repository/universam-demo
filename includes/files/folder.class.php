<?php
class USAM_Folder
{	 
	private static $string_cols = array(	
		'name',
		'slug',
		'date_update',	
		'status',		
	);		
	private static $int_cols = array(
		'id',	
		'count',	
		'parent_id',
		'user_id',		
	);	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';
			
		return false;
	}
	private $changed_data = array();	
	private $data    = array();
	private $fetched = false;	
	private $args    = array( 'col'   => '', 'value' => '' );
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
		if ( ! in_array( $col, array( 'id' ) ) )
			return;

		$this->args = array( 'col' => $col, 'value' => $value );			
		// Если идентификатор указан, попытаться получить из кэша
		if ( $col == 'id' ) 
		{
			$this->data = wp_cache_get( $value, 'usam_folder' );			
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
		wp_cache_set( $id, $this->data, 'usam_folder' );
		do_action( 'usam_folder_update_cache', $this );
	}

	/**
	 * Удалить кеш
	 */
	public function delete_cache( ) 
	{				
		wp_cache_delete( $this->get( 'id' ), 'usam_folder' );	
		do_action( 'usam_folder_update_cache', $this );
	}
	
	public function delete( ) 
	{		
		$id = $this->get('id');		
		
		$data = $this->get_data();
		do_action( 'usam_folder_before_delete', $data );		
	
		usam_delete_folders(['include' => [$id], 'status' => 'all']);
		
		$this->delete_cache( );	
		do_action( 'usam_folder_delete', $id );
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
		$sql = $wpdb->prepare( "SELECT * FROM " . USAM_TABLE_FOLDERS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{	
			$this->exists = true;
			$this->data = apply_filters( 'usam_folder_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;				
			}
			$this->update_cache( );	
		}
		do_action( 'usam_folder_fetched', $this );
		$this->fetched = true;
	}	
	
	public function exists() 
	{
		$this->fetch();
		return $this->exists;
	}

	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_folder_get_property', $value, $key, $this );
	}

	public function get_data()
	{
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'usam_folder_get_data', $this->data, $this );
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
		$properties = apply_filters( 'usam_folder_set_properties', $properties, $this );		
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

		do_action( 'usam_folder_pre_save', $this );	
		
		$this->data['date_update'] = date( "Y-m-d H:i:s" );
			
		$where_col = $this->args['col'];
		$result = false;				
		if ( $where_col ) 
		{	// обновление			
			if ( empty($this->changed_data) )
				return true;
			
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'usam_folder_pre_update', $this );	

			$this->data = apply_filters( 'usam_folder_update_data', $this->data );	
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );			
			$result = $wpdb->update( USAM_TABLE_FOLDERS, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result ) 
			{ 
				$this->update_cache( );	
				do_action( 'usam_folder_update', $this );				
				if ( isset($this->changed_data['parent_id']) )
				{
					if( $this->changed_data['parent_id'] )
					{
						$parent_folder = usam_get_folder( $this->changed_data['parent_id'] );
						if( $parent_folder )
							usam_update_folder( $this->changed_data['parent_id'], ['count' => $parent_folder['count']-1] );
					}	
					if( $this->data['parent_id'] )
					{
						$parent_folder = usam_get_folder( $this->data['parent_id'] );
						if( $parent_folder )
							usam_update_folder( $this->data['parent_id'], ['count' => $parent_folder['count']+1] );
					}					
					$cache_folders = get_option( 'usam_ancestors_folders');
					if ( $this->data['parent_id'] )
					{
						$cache_folders[$this->data['parent_id']][] = $this->data['id'];	
					} 					
					if ( $this->changed_data['parent_id'] )
					{
						foreach ( $cache_folders[$this->changed_data['parent_id']] as $key => $folder_id ) 
						{
							if ( $folder_id == $this->changed_data['parent_id'] )
							{
								unset($this->changed_data['parent_id'][$key]);
								break;
							}
						}
					} 										
					update_option( 'usam_ancestors_folders', $cache_folders);
				}			
			}
		} 
		else 
		{ 
			do_action( 'usam_folder_pre_insert' );
			
			if ( empty($this->data['name']) )
				return false;		
			
			if ( empty($this->data['slug']) )
				$this->data['slug'] = sanitize_title($this->data['name']);	
			
			if ( empty($this->data['parent_id']) )
				$this->data['parent_id'] = 0;	
			
			if ( !isset($this->data['status']) )
				$this->data['status'] = 'closed';	
			
			$this->data['count'] = 0;	
			
			if ( !isset($this->data['user_id']) )
				$this->data['user_id'] = get_current_user_id();	
											
			$this->data = apply_filters( 'usam_folder_insert_data', $this->data );				
			$formats = $this->get_data_format( $this->data );		
			$result = $wpdb->insert( USAM_TABLE_FOLDERS, $this->data, $formats );	
			if ( $result ) 
			{				
				$this->set( 'id', $wpdb->insert_id );							
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];				
				$this->update_cache( );		
				do_action( 'usam_folder_insert', $this );		
				if ( $this->data['parent_id'] )
				{
					$cache_folders = get_option( 'usam_ancestors_folders');
					$cache_folders[$this->data['parent_id']][] = $wpdb->insert_id;	
					update_option( 'usam_ancestors_folders', $cache_folders);
				}
			}			
		} 
		do_action( 'usam_folder_save', $this );
		$this->changed_data = [];
		return $result;
	}
}

function usam_get_folder( $value, $colum = 'id' )
{	
	$folder = new USAM_Folder($value, $colum);	
	$folder_data = $folder->get_data();	
	return $folder_data;	
}

function usam_update_folder( $id, $data )
{	
	if ( !empty($data) ) 
	{
		$folder = new USAM_Folder( $id );
		if ( isset($data['status']) && $data['status'] === 'delete' )
		{
			$status = $folder->get( 'status' );				
			if ( $status === 'delete' )
				return $folder->delete( );
		}	
		$folder->set( $data );
		return $folder->save();
	}
	return true;
}

function usam_insert_folder( $value )
{	
	$folder = new USAM_Folder( $value );	
	$folder->save();
	$folder_id = $folder->get('id');	
	return $folder_id;		 
}

function usam_delete_folder( $id ) 
{
	$folder = new USAM_Folder( $id );
	$result = $folder->delete( );
	return $result;
}

function usam_delete_folders( $args, $delete = false ) 
{	
	global $wpdb;	
	$args['fields'] = ['id', 'status'];
	$folders = usam_get_folders( $args );	
	if ( empty($folders) )
		return false;		
	
	$delete_ids = array();
	$update_ids = array();
	foreach ( $folders as $folder ) 
	{
		if ( $folder->status == 'delete' || $delete )
			$delete_ids[] = $folder->id;	
		else
			$update_ids[] = $folder->id;
	}		
	if ( !empty($delete_ids) )
	{		
		$folder_ids = array( );
		foreach ( $delete_ids as $id ) 
		{
			$folders = (array)usam_get_folders(['fields' => 'id', 'child_of' => $id]);		
			$folder_ids = array_merge( $folder_ids, $folders );	
			$folder_ids[] = $id;			
		}	
		usam_delete_files(['folder_id' => $folder_ids], true );
		$result = $wpdb->query("DELETE FROM ".USAM_TABLE_FOLDERS." WHERE id IN (".implode(',',$folder_ids).")");
		usam_cache_folders( );
	}	
	if ( !empty($update_ids) )
	{
		$folder_ids = array( );
		foreach ( $update_ids as $id ) 
		{
			$folders = (array)usam_get_folders(['fields' => 'id', 'child_of' => $id]);		
			$folder_ids = array_merge( $folder_ids, $folders );	
			$folder_ids[] = $id;			
		}	
		usam_delete_files(['folder_id' => $folder_ids]);	
		$wpdb->query("UPDATE ".USAM_TABLE_FOLDERS." SET status='delete' WHERE `id` IN (".implode(',',$folder_ids).")");		
	}
	return count($delete_ids);
}

function usam_cache_folders( )
{		
	$folders = usam_get_folders(['cache_results' => true]);	
	$cache_folders = [];
	foreach( $folders as $folder )
	{		
		if ( $folder->parent_id )
		{			
			$cache_folders[$folder->parent_id][] = $folder->id;				
		}
	}
	update_option( 'usam_ancestors_folders', $cache_folders);
	return $cache_folders;
}

function usam_save_folder( $folder_id ) 
{
	$folder = usam_get_folder( $folder_id );
	mkdir( USAM_FILE_DIR.$folder['name'] );
	foreach ( usam_get_files( array('folder_id' => $folder['id']) ) as $file ) 
	{			
		if ( !copy(USAM_UPLOAD_DIR.$file->file_path, USAM_FILE_DIR.$folder['name'].'/'.$file->title.'.' . pathinfo(USAM_UPLOAD_DIR.$file->file_path, PATHINFO_EXTENSION)) );
			return false;
	}
	$ancestors_folders = get_option( 'usam_ancestors_folders', array() );		
	if ( !empty($ancestors_folders) )
	{
		foreach ( $ancestors_folders[$folder_id] as $ids ) 
		{
			foreach ( $ids as $id ) 
				usam_save_folder( $id );
		}
	}
	return USAM_FILE_DIR.$folder['name'];
}

function usam_zip_archive_folder( $folder_id, $i = 0 ) 
{
	static $zip = null, $folders = [];
	$folder = usam_get_folder( $folder_id );	
	if ( $zip === null )
	{
		$zip = new ZipArchive();
		if ( $zip->open(USAM_FILE_DIR.$folder['name'].'.zip', ZIPARCHIVE::CREATE) !== true ) 
			return false;
	}
	$folders[] = $folder['name'];	
	$folder_name = implode('/', $folders);	
	foreach ( usam_get_files(['folder_id' => $folder['id']] ) as $file ) 
	{					
		$zip->addFile( USAM_UPLOAD_DIR.$file->file_path, $folder_name.'/'.$file->title.'.'.pathinfo(USAM_UPLOAD_DIR.$file->file_path, PATHINFO_EXTENSION) );	
	}		
	$ancestors_folders = get_option( 'usam_ancestors_folders', array() );
	if ( !empty($ancestors_folders[$folder_id]) )
	{
		foreach ( $ancestors_folders[$folder_id] as $id ) 
		{
			$i++;			
			usam_zip_archive_folder( $id, $i );
			array_pop($folders); 
			$i--;
		}
	}
	if ( $i === 0 )
	{ 
		$zip->close(); 
		$zip = null;
		return USAM_FILE_DIR.$folder['name'].'.zip';
	}
}


function usam_get_folder_object( $folder_name, $slug = null, $group_folder_name = null )	
{						
	$folder_id = usam_get_folders(['fields' => 'id', 'name' => $folder_name, 'number' => 1]);
	if ( empty($folder_id) )
	{
		$args = ['name' => $folder_name];
		if ( $group_folder_name )
		{
			$parent_id = usam_get_folders(['fields' => 'id', 'slug' => $slug, 'number' => 1]);
			if ( empty($parent_id) )
				$parent_id = usam_insert_folder(['name' => $group_folder_name, 'slug' => $slug]);
			$args['parent_id'] = $parent_id;
		}
		elseif ( $slug )
			$args['slug'] = $slug;
		$folder_id = usam_insert_folder( $args );
	}
	return $folder_id;
}