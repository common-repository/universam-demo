<?php
/*
* =====================  Изменения в Media Gallery ================================================
*/
class USAM_Media_Gallery
{
	private $post_attacment;	
	function __construct( ) 
	{
		//add_filter( 'upload_dir', [$this, 'upload_dir'] );		
		add_filter( 'wp_update_attachment_metadata', [$this,'update_attachment'], 1,2 ); //Переименовать изображение при первой загрузке
		if ( get_site_option('usam_rename_attacment') )
			add_filter( 'attachment_fields_to_save', [$this,'attachment_fields_to_save'], 1, 2 ); // Переименовать загружаемые картинки. Имя сделать название артикула товара
		if ( defined('DONOT_UPLOAD_SAME_ATTACMENT') && DONOT_UPLOAD_SAME_ATTACMENT )
		{ 			
			add_filter( 'wp_handle_upload', [$this,'handle_upload'], 1, 2 );
			add_filter( 'wp_delete_file', [$this,'delete_file'], 1 );
		}
		add_filter( 'upload_mimes', [$this, 'extra_mime_types'] );
	}	
		
	function extra_mime_types( $mimes ) 
	{
		$mimes['svg'] = 'image/svg+xml';
		$mimes['webp'] = 'image/webp';
		return $mimes;
	}
			
	public function handle_upload( $data, $handle_sideload ) 
	{			
		$path_parts = pathinfo($data['file']);
		$directory = $path_parts['dirname']; 
		$filename = $path_parts['basename']; 
		$extension = $path_parts['extension']; 
		$name = explode('-',$filename);
		if ( count($name) > 1 )
		{
			$number = end($name);
			$number = str_replace('.'.$extension, '', $number);				
			if ( is_numeric($number) )
			{
				$old_filename = str_replace('-'.$number, '', $filename);
				if ( file_exists($directory.'/'.$old_filename) )  
				{				
					unlink($data['file']);
					$data['file'] = $directory.'/'.$old_filename;
					$data['url'] = str_replace($filename, $old_filename, $data['url']);						
				}
			}				
		}
		return $data;
	}	
	
	public function delete_file( $file ) 
	{	
		global $wpdb;
		$path_parts = pathinfo($file);
		$filename = $path_parts['basename']; 	
		foreach ( get_intermediate_image_sizes() as $_size ) 
		{
			$width = get_option( "{$_size}_size_w" );
			$height = get_option( "{$_size}_size_h" );
			if ( substr_compare($path_parts['filename'], "{$width}x{$height}", -strlen("{$width}x{$height}") ) === 0 )
				return $file;				
		}			
		$uploads = wp_upload_dir();	
		$subdir = !empty($uploads['subdir'])?trim($uploads['subdir'],'/').'/':'';			
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM ".$wpdb->postmeta." WHERE meta_key='_wp_attached_file' AND meta_value='{$subdir}{$filename}'" );	
		if ( $count )
			return false;
		return $file;
	}
			
	// Переименовать загружаемые картинки. Имя сделать название артикула товара
	function attachment_fields_to_save( $post_attacment, $attachment )
	{					
		if ( apply_filters( 'usam_update_attachment', true ) ) 
		{				
			$data = wp_get_attachment_metadata( $post_attacment['ID'] );
			$data = $this->get_data_file( $post_attacment, $data );
			wp_update_attachment_metadata( $post_attacment['ID'], $data );		
			$post_attacment = array_merge($post_attacment, $this->post_attacment);
		
			if ( isset($_REQUEST['_wp_original_http_referer'] ) && strpos( $_REQUEST['_wp_original_http_referer'], '/wp-admin/' ) === false )	
				$_REQUEST['_wp_original_http_referer'] = get_permalink( $post_attacment['ID'] );  
		}
		return $post_attacment;
	}
		
	//Переименовать изображение при первой загрузке
	function update_attachment( $data, $attachment_ID )
	{   		
		if ( isset($_POST['type'] ) && 'product_file' === $_POST['type'] ) 
		{			
			$attachment = ['post_type' => "usam-product-file", 'post_status' => 'inherit', 'ID' => $attachment_ID];
			wp_update_post( $attachment );			
		}	
		elseif ( isset($_POST['type'] ) && 'product_attribute_file' === $_POST['type'] ) 
		{
						
		}
		else
		{ 			
			$max_width = get_site_option('usam_max_width');
			$max_height = get_site_option('usam_max_height');				
			$size = apply_filters( 'usam_max_size_foto', ['width' => $max_width, 'height' => $max_height] );		 
			if ( $size['height'] && $size['width'] && !empty($data['width']) && $data['width'] > $size['width'] && $data['height'] > $size['height'] ) 
			{	
				$post_attacment = get_post( $attachment_ID, ARRAY_A );
				if ( !empty($post_attacment['post_parent']) )			
				{
					$post = get_post( $post_attacment['post_parent'] );	
					if ( $post->post_type == 'usam-product' )
					{
						$filepath = get_attached_file( $post_attacment['ID'] ); 
						$image = wp_get_image_editor( $filepath );				
						if ( ! is_wp_error($image) ) 					
						{
							$image->resize( $size['width'], $size['height'], false );			
							$image->save( $filepath );
							$size = $image->get_size( $filepath );		
							$data['width'] = $size['width'];
							$data['height'] = $size['height'];
							$data['filesize'] = filesize( $filepath );
						}						
					}
				}			
			}
			if ( get_site_option('usam_rename_attacment') && apply_filters( 'usam_update_attachment', true ) ) 
			{						
				$post_attacment = get_post( $attachment_ID, ARRAY_A );
				$data = $this->get_data_file( $post_attacment, $data );	
				if ( !empty($this->post_attacment) && $this->post_attacment['post_title'] != $post_attacment['post_title'] )
				{
					$this->post_attacment['ID'] = $post_attacment['ID'];		
					wp_update_post( $this->post_attacment ); 
				}
			}		
		}		
		return $data;	
	}
		
	private function get_data_file( $post_attacment, $data )
	{ 
		$this->post_attacment = [];				
		$old_filepath = get_attached_file( $post_attacment['ID'] ); 
		if ( !file_exists($old_filepath) || empty($data['file']) )
			return $data;
		$path_parts = pathinfo($old_filepath);
		$directory = $path_parts['dirname']; 
		$old_filename = $path_parts['basename']; 
		
		$new_file_name = $this->get_format_attacment( $post_attacment );	
		if ( !$new_file_name )
			$new_file_name = $path_parts['filename'];		 
		if ( $path_parts['extension'] == 'jpg' || $path_parts['extension'] == 'png' || $path_parts['extension'] == 'jpeg' )
			$new_ext = 'webp';
		else
			$new_ext = $path_parts['extension'];	
		
		// Проверим, имя файла на соответствие желаемому стандарту			
		if ( stripos($path_parts['filename'], $new_file_name) !== false && $new_ext == $path_parts['extension'] )
			return $data;
		
		static $number = 0;
		if ( $number )
			$file_name = $new_file_name.'-'.$number.'.'.$new_ext;
		else
			$file_name = $new_file_name.'.'.$new_ext;
		$number++;
		
		if ( $path_parts['filename'] != $new_file_name )
			$file_name = wp_unique_filename( $directory, $file_name );		
		if ( isset($data['original_image']) )
			$data['original_image'] = $file_name;
					
		$original_file = pathinfo($data['file'], PATHINFO_BASENAME);	
		$data['file'] = str_replace($original_file, $file_name, $data['file'] );
				
		$new_filepath = $directory . '/' . $file_name;	
		$filepath = $directory . '/' . $file_name;				
		if ( defined('DONOT_UPLOAD_SAME_ATTACMENT') && DONOT_UPLOAD_SAME_ATTACMENT && $new_filepath != $filepath )
		{ //uploads_use_yearmonth_folders				
			unlink($old_filepath);
			$new_filepath = $filepath;	
		}
		elseif ( !file_exists($new_filepath) )
		{
			if ( usam_create_image_webp($old_filepath, $new_filepath) )						
				$data['filesize'] = filesize( $new_filepath );
			else
				return $data;
		}
		update_attached_file( $post_attacment['ID'], $new_filepath );
		
		$noext_old_filename = substr_replace($old_filename , $path_parts['extension'], strrpos($old_filename , '.') +1);
		$noext_sanitized_media_title = substr_replace($file_name , $new_ext, strrpos($file_name , '.') +1);
		$this->post_attacment['post_name'] = $noext_sanitized_media_title;
		if ( !empty($post_attacment['guid']) )
			$this->post_attacment['guid'] = str_replace( $noext_old_filename, $noext_sanitized_media_title, $post_attacment['guid'] );	
		if ( !empty($data['sizes']) )
			$data['sizes'] = $this->rename_size_filename( $data['sizes'], $directory, $noext_old_filename, $noext_sanitized_media_title );	
		return $data;
	}
		
	// Переименовать названия файла разных размеров
	private function rename_size_filename( $sizes, $directory, $noext_old_filename, $noext_sanitized_media_title )
	{			
		foreach ( $sizes as $size => $meta_size ) 
		{
			$meta_old_filepath = $directory . '/' . $meta_size['file'];	
			if ( file_exists($meta_old_filepath) )
			{
				$meta_new_filename = str_replace( $noext_old_filename, $noext_sanitized_media_title, $meta_size['file'] );
				$meta_new_filepath = $directory . '/' . $meta_new_filename;	
				$meta_new_filepath_webp = substr_replace($meta_new_filepath , 'webp', strrpos($meta_new_filepath , '.') +1);			
				if ( usam_create_image_webp($meta_old_filepath, $meta_new_filepath_webp) )
					$meta_new_filename = substr_replace($meta_new_filename , 'webp', strrpos($meta_new_filename , '.') +1);
				elseif ( !file_exists($meta_new_filepath) || is_writable($meta_new_filepath) )
				   rename($meta_old_filepath, $meta_new_filepath); 
				$sizes[$size]['mime-type'] = 'image/webp';
				$sizes[$size]['file'] = $meta_new_filename;	
			}
			else
				unset($sizes[$size]);
		} 
		return $sizes;
	}
	
	private function get_format_attacment( $post_attacment )
	{
		if ( !empty($post_attacment['post_parent']) )		
			$post = get_post( $post_attacment['post_parent'] );	
		elseif ( !isset($post_attacment['post_parent']) )		
		{
			$post_attacment_db = get_post( $post_attacment['ID'] );	
			if ( !empty($post_attacment_db->post_parent) )
				$post = get_post( $post_attacment_db->post_parent );			
		}
		$file_name = '';		
		$this->post_attacment['post_title'] = $post_attacment['post_title'];			
		if ( !empty($post) )
		{			
			if ( $post->post_type == 'usam-product' )	
			{
				if ( $post_attacment['post_type'] == 'usam-product-file' )
				{
				
				}
				else
				{
					$format_file_name = get_site_option('usam_format_file_name_attacment');
					$format_file_title = get_site_option('usam_format_file_title_attacment');
					
					$attributes = usam_get_product_attributes();
					$shortcodes = array();
					foreach ( $attributes as $attribute )
					{
						$shortcodes[$attribute->slug] = usam_get_product_attribute( $post->ID, $attribute->slug );			
					}	
					$shortcodes["sku"] = usam_get_product_meta( $post->ID, 'sku' );		
					$shortcodes["post_name"] = $post->post_name;	
					$shortcodes["post_title"] = $post->post_title;
					$shortcodes["id"] = $post->ID;	
					$file_name = $format_file_name?$format_file_name:$file_name;
					$this->post_attacment['post_title'] = $format_file_title?$format_file_title:$post_attacment['post_title'];
					foreach ( $shortcodes as $key => $name )
					{
						$file_name = str_replace( $key, $name, $file_name );	
						$this->post_attacment['post_title'] = str_replace( $key, $name, $this->post_attacment['post_title'] );	
					}
				}
			}			
			if ( $file_name )
				$file_name = sanitize_file_name( usam_sanitize_title_with_translit( $file_name ));
		} 		
		return $file_name;
	}	
	
	// Изменить путь
	public function upload_dir( $pathdata ) 
	{ 	
		if ( isset($_POST['type'] ) ) 
		{
			if ( 'product_file' === $_POST['type'] ) 
			{ 		
				//$pathdata['path']   = untrailingslashit();
			//	$pathdata['url']    = untrailingslashit();
			//	$pathdata['subdir'] = '';
			}			
		}		
		return $pathdata;
	}
}
$media_gallery = new USAM_Media_Gallery();

	// Узнаем цвет изображения
		/*	$colors_to_show = 25;
			require_once( USAM_FILE_PATH . '/includes/media/colors.inc.php' );	
			$pal = new GetMostCommonColors( $old_filepath );
			$colors = $pal->get_group_color( $colors_to_show );	
			
			$array = array_flip($colors); 
			unset ($array['white']); 
			$colors = array_flip($array); */
?>