<?php
/**
 * Функции для работы с файлами
 */
/**
 * Копировать папку из $src в $dst
 */
function usam_recursive_copy( $src, $dst ) 
{
	$dir = opendir( $src );
	@mkdir( $dst );
	while ( false !== ( $file = readdir( $dir )) ) 
	{
		if ( $file != '.' && $file != '..' ) 
		{
			if ( is_dir( $src . '/' . $file ) )
				usam_recursive_copy( $src . '/' . $file, $dst . '/' . $file );			
			else
				@copy( $src . '/' . $file, $dst . '/' . $file );
		}
	}
	closedir( $dir );
}

function usam_remove_dir( $path, $delete_current_folder = true )
{
	if( file_exists($path) && is_dir($path) )
	{
		$dirHandle = opendir($path);
		while( false!==($file = readdir($dirHandle)) )
		{
			if( $file != '.' && $file != '..')
			{ 				
				$tmpPath = $path.'/'.$file;
				chmod($tmpPath, 0777);
				if( is_dir( $tmpPath ) )				
					usam_remove_dir( $tmpPath, true );
				else
					unlink($tmpPath);
			}
		}
		closedir($dirHandle);
		if ( $delete_current_folder )
			rmdir($path);
	} 	
}

function usam_zip_folder( $source, $destination, $include_sourse = true )
{
	$source = str_replace( '\\', '/', rtrim( realpath( $source ), '/' ) );

	if( ! file_exists($source) )
		return true;

	$zip = new ZipArchive();
	if( ! $zip->open( $destination, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE ) )
		return 'Error: ZipArchive not installed';

	if( $include_sourse )
		$zip->addEmptyDir( basename($source) );
	if( is_file($source) )
		$zip->addFile( $source );
	elseif( is_dir( $source ) )
	{
		foreach( new RecursiveIteratorIterator( new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST ) as $file_path => $file_obj )
		{
			if( preg_match('~/[.]{1,2}$~', $file_path) )
				continue;
			$name = basename($file_path);
			if( $name == '.' || $name == '..')
				continue;			
	
			$file_rel_path = str_replace( '\\', '/', $file_path );
			$file_rel_path = str_replace( "$source/", '', $file_rel_path );		
			if( $include_sourse )
				$file_rel_path = basename($source) .'/'. $file_rel_path;
			if( is_dir($file_path) )
				$zip->addEmptyDir( $file_rel_path );
			elseif( is_file( $file_path ) )
				$zip->addFile( $file_path, $file_rel_path );
		}
	}
	$zip->close();
	return true;
}

//получает файлы в директории $dirname
function usam_list_dir( $dirname, $result = 'name' ) 
{			
	$dirlist = [];
	if ( is_dir($dirname) && $dir = opendir($dirname)) 
	{		
		while ( ($file = readdir( $dir )) !== false ) 
		{	
			if ( $file != ".." && $file != "." && !stristr( $file, "~" ) && !( strpos( $file, "." ) === 0 ) )
				$dirlist[] = $result == 'name' ? $file : $dirname.'/'.$file;
		}
	} 
	return $dirlist;
}

//Получает расширение файла.
function usam_get_extension( $str ) 
{	
	$parts = explode( '/', $str );		
	$str = end( $parts );	
	
	if ( stripos ($str, '.') === false ) 
		return '';	
	
	$parts = explode( '.', $str );	
	return end( $parts );
}

function usam_check_upload_file( $filename ) 
{
	$allowed = ['png', 'jpg', 'jpeg', 'gif','rar','zip','pdf','doc','odt','docx','xls','xlsx', 'ppt', 'pptx','dif','xlsm','odc','ods','csv','txt','mp3','avi','mpeg','mpg','mp4','mkv','cdr','psd','xml','epub'];
	$allowed = apply_filters( 'usam_upload_type_file', $allowed );
	$extension = pathinfo($filename, PATHINFO_EXTENSION);
	if(in_array(strtolower($extension), $allowed))
		return true;	
	return false;
}

function usam_fileupload( $file, $directory = null, $args = [] )
{ 			
	$max_filesize = wp_convert_hr_to_bytes(ini_get('upload_max_filesize'));
	$results = ['status' => 'error', 'error_message' => __('Файл не передан','usam')];
	if( isset($file['tmp_name']) && file_exists($file['tmp_name']) )
	{
		$title = preg_replace('/\.\w+$/', '', $file['name']);
		$filename = sanitize_file_name(usam_sanitize_title_with_translit($file['name'])); 
		$filesize = filesize($file['tmp_name']);		
		if ( $filesize < $max_filesize )
		{									
			if( usam_check_upload_file($file['name']) )
			{	 				
				$user_id = get_current_user_id();
				if ( !$directory )
					$directory = $user_id;				
				if ( !is_dir(USAM_FILE_DIR.$directory) )
					mkdir(USAM_FILE_DIR.$directory, 0775);
				
				$new_filename = wp_unique_filename( USAM_FILE_DIR.$directory, $filename );			
				$filepath = USAM_FILE_DIR.$directory.'/'.$new_filename;
				if( move_uploaded_file($file['tmp_name'], $filepath))
				{					
					$data = @getimagesize($filepath); 	
					if( !empty($data['mime']) && preg_match('{image/(.*)}is', $data['mime'], $p) ) 
					{		 
						$size = apply_filters( 'usam_max_size_foto', ['width' => get_site_option('usam_max_width'), 'height' => get_site_option('usam_max_height')] );	
						if ( $size['height'] && $size['width'] && !empty($data[0]) && ($data[0] > $size['width'] || $data[1] > $size['height']) ) 
						{		
							$image = wp_get_image_editor( $filepath );
							if ( ! is_wp_error($image) ) 					
							{
								$image->resize( $size['width'], $size['height'], false );			
								$image->save( $filepath ); 
							}	
						}	
						usam_create_image_sizes( $filepath );
					}		
					$args = is_array($args)?$args:[];
					$args = array_merge(['object_id' => 0, 'title' => $title, 'name' => $filename, 'file_path' => $filepath, 'type' => 'temporary'], $args );						
					$file_id = usam_insert_file( $args );
					$results = ['status' => 'success', 'icon' => usam_get_file_icon( $file_id ), 'shortname' => usam_get_formatted_filename( $filename ), 'title' => $title, 'file_name' => $filename, 'id' => $file_id, 'size' => $filesize];
				}
				else
					$results['error_message'] = __('Ошибка копирования','usam');
			}
			else
				$results['error_message'] = __('В этом формате нельзя загружать. Загрузите в другом формате.','usam');
		}
		else
			$results['error_message'] = __('Большой файл','usam');
	}	
	return $results;
}

function usam_download_file_from_media_gallery( $file_id )
{ 
	$file_data = get_post( $file_id );	
	if ( ! $file_data )
		wp_die( __('Неверный идентификатор файла.', 'usam') );
	
	$file_path = get_attached_file($file_id);	
	if ( is_file( $file_path ) )
	{ 
		$file_ext = usam_get_extension( $file_path ); 		
		usam_download_file( $file_path, $file_data->post_title.'.'.$file_ext );		
		exit();
	}
	else
		wp_die(__('Файл не существует!', 'usam'));
}

function usam_download_file( $file_path, $file_name = '' )
{ 
	if( !ini_get('safe_mode') ) 
		set_time_limit(0);
	
	if ( $file_name == '' )
		$file_name = basename( $file_path );	
	$filetype = wp_check_filetype( $file_name );							
	header( 'Content-Type: ' . $filetype['type'] );
	header( 'Content-Length: ' . filesize($file_path) );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Content-Disposition: inline; filename="'.stripslashes($file_name).'"' ); //attachment inline
	if ( isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] != '') ) 
	{			
		header( "Pragma: public" );
		header( "Expires: 0" );
		header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		header( "Cache-Control: public" );
	} 
	else
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );		
	header( "Pragma: public" );
	header( "Expires: 0" );	
	readfile($file_path);
}

function usam_readfile_chunked( $filename, $retbytes = true ) 
{
	$chunksize = 1 * (1024 * 1024); // how many bytes per chunk
	$buffer = '';
	$cnt = 0;
	$handle = fopen( $filename, 'rb' );
	if ( $handle === false ) {
		return false;
	}
	while ( !feof( $handle ) ) 
	{
		$buffer = fread( $handle, $chunksize );
		echo $buffer;
		ob_flush();
		flush();
		if ( $retbytes ) {
			$cnt += strlen( $buffer );
		}
	}
	$status = fclose( $handle );
	if ( $retbytes && $status ) {
		return $cnt; // return num. bytes delivered like readfile() does.
	}
	return $status;
}

function usam_get_max_upload_size()
{
	return size_format( wp_max_upload_size(), 0 );
}

function usam_get_form_attachments_files_library( $args = [] )
{			
	$default = ['ids' => null, 'type' => '', 'upload' => true, 'multiple' => true, 'input_name' => 'fileupload', 'required' => false];
	$args = array_merge($default, $args);		
	$input_name = $args['multiple']?$args['input_name'].'[]':$args['input_name'];	
	$class = $args['required']?"js-attachments-required":"";
	$out = "<div class='usam_attachments js-attachments $class' file_input_name='".$input_name."'>";		
	if ( $args['upload'] )
	{ 			
		$fileupload_url = add_query_arg(['usam_ajax_action' => 'file_upload', 'action' => 'usam_ajax', 'nonce' => usam_create_ajax_nonce('file_upload')], get_bloginfo('url'));		
		if ( is_ssl() )
			$fileupload_url = str_replace('http://', 'https://', $fileupload_url);
		$out .= "<div class ='js-file-drop attachments__drop' fileupload_url ='".$fileupload_url."'>".__('Прикрепить', 'usam')."<br />".__('файл', 'usam')."</div> 
			<input type='file' name='upl' ".($args['multiple']?'multiple':'')." />";
	} 
	if ( isset($args['object_id']) && $args['type'] == 'file' )
		$files = usam_get_files(['include' => [$args['object_id']]]);
	elseif ( isset($args['object_id']) && $args['type'] )
	{
		if ( $args['object_id'] )
			$files = usam_get_files(['object_id' => $args['object_id'], 'type' => $args['type']]);
		else
			$files = [];
	}
	elseif ( isset($args['user_id']) )
		$files = usam_get_files(['user_id' => $args['user_id']]);
	elseif ( $args['type'] )
		$files = usam_get_files(['type' => $args['type']]);
	elseif ( !empty($args['ids']) )
	{
		$ids = is_array($args['ids'])?$args['ids']:array($args['ids']);
		$files = usam_get_files(['include' => $ids]);
	}
	if ( !empty($files) )
	{				
		foreach ( $files as $file ) 
		{ 					
			$filepath = USAM_UPLOAD_DIR.$file->file_path;
			$url = get_bloginfo('url').'/file/'.$file->code;
			$size = file_exists($filepath)?size_format( filesize($filepath) ):'';
			$out .= "<div class='usam_attachments__file js_delete_block' data-id='".$file->id."'>";
			if ( $args['upload'] )
				$out .= "<a class='js_delete_action'></a>";
				
			$out .= "<a href='".$url."' title ='".$file->title."' target='_blank'><div class='attachment_icon'><img src='".usam_get_file_icon( $file->id )."'/></div></a>
				<div class='attachment__file_data'>
					<div class='filename'>".usam_get_formatted_filename( $file->title )."</div>
					<div class='filesize'><a download href='".$url."' title ='".__('Сохранить этот файл себе на компьютер','usam')."' target='_blank'>".__('Скачать','usam')."</a>".$size."</div>
				</div>
				<input type='hidden' name='".$input_name."' value='".$file->id."'/></div>";
		}
	}				
	$out .= "</div>";
	return $out;
}

function usam_update_attachments( $id, $type, $args = [] )
{
	if ( $id )
	{
		$attachments = !empty($_REQUEST['fileupload'])?array_map('intval',(array)$_REQUEST['fileupload']):[];		
		usam_delete_files(['exclude' => $attachments, 'object_id' => $id, 'type' => $type]);
		if ( !empty($attachments) )	
		{					
			$args['object_id'] = $id;
			$args['type'] = $type;		
			foreach ( $_REQUEST['fileupload'] as $file_id ) 
				usam_attach_file( $file_id, $args );
		}		
	}		
}	

function usam_get_formatted_filename( $title ) 
{
	$sumbol = 12;				
	if ( iconv_strlen($title, "utf-8//IGNORE") > $sumbol )
	{
		$result = mb_substr($title, 0, $sumbol)."..";
		$result .= '.'.pathinfo($title, PATHINFO_EXTENSION);
		return $result;
	}
	else
		return $title;	
}

function usam_get_file_icon( $file_id, $place = 'universam' ) 
{
	if ( $place == 'wordpress' )
		$filepath = get_attached_file( $file_id );	
	elseif ( $place == 'filepath' )
		$filepath = $file_id;
	else
	{
		$file = usam_get_file( $file_id );
		if ( !empty($file['file_path']) )
			$filepath = USAM_UPLOAD_DIR.$file['file_path'];	
		else
			$filepath = '';
	} 
	if ( is_file($filepath) )
	{
		if( filesize($filepath) > 11 && function_exists('exif_imagetype') )
			$imagetype = exif_imagetype( $filepath );
		else
			$imagetype = '';
		$extension = usam_get_extension( $filepath );
		if ( in_array($imagetype, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP)) )
		{
			if ( $place == 'wordpress' )
				$icon = wp_get_attachment_url( $file_id );	
			else
				$icon = get_bloginfo('url').'/show_file/'.$file_id.'?size=thumbnail';	
		}
		elseif ( in_array($extension, array('pdf', 'xls', 'xlsx', 'doc', 'docx', 'ppt', 'pptx','rar', 'zip', 'exe', 'mp3', 'mp4', 'psd', 'ods')) )
			$icon = USAM_SVG_ICON_URL.'#'.$extension.'-usage';
		else
			$icon = USAM_SVG_ICON_URL.'#document-usage';
	}
	else
		$icon = USAM_SVG_ICON_URL.'#file_delete-usage';
	return $icon;
}

function usam_create_image_sizes( $filepath ) 
{
	$image = wp_get_image_editor( $filepath );
	if ( ! is_wp_error($image) ) 					
	{	
		$sizes = usam_get_registered_image_sizes();
		$pathinfo = pathinfo($filepath);	
		foreach( $sizes as $size => $size_data ) 
		{
			$filename_size = $pathinfo['filename'].'-'.$size_data['width'].'x'.$size_data['height'];
			if ( isset($pathinfo['extension']) )
				$filename_size .= '.'.$pathinfo['extension'];
			$filepath_size = str_replace($pathinfo['basename'], $filename_size, $filepath);
			if ( !is_file($filepath_size) )
			{
				$image->resize( $size_data['width'], $size_data['height'], false );
				$image->save( $filepath_size );
			}
		}
	}
}