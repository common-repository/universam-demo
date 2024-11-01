<?php
function usam_get_no_image_uploaded_file( $size = 'full' ) 
{ 
	static $image_make = false;
	$file = 'no-image-uploaded.png';
	if( !is_dir(USAM_NO_IMAGE_DIR) )
	{			
		if ( !mkdir(USAM_NO_IMAGE_DIR, 0755, true) ) 
			return false;		
		copy( USAM_CORE_THEME_PATH.'images/'.$file, USAM_NO_IMAGE_DIR .$file);
	}
	else
	{
		$file = '';
		$dirHandle = opendir(USAM_NO_IMAGE_DIR);
		while( false !== ($filename = readdir($dirHandle)) )
		{ 
			if ( strripos(USAM_NO_IMAGE_DIR.$filename, 'no-image-uploaded.') !== false )
			{
				$file = $filename;
				break;
			}
		}
		if ( !$file )
		{
			$file = 'no-image-uploaded.png';
			copy( USAM_CORE_THEME_PATH.'images/'.$file, USAM_NO_IMAGE_DIR .$file);
		}
	}	
	if ( $size == 'full' )
		return USAM_NO_IMAGE_URL.$file;
	elseif ( $size == 'single' )
	{			
		$single_view_image = get_site_option( 'usam_single_view_image', ['width' => 600, 'height' => 600]);
		$size = array( $single_view_image['width'], $single_view_image['height'] );		
	}		
	elseif ( $size == 'product-thumbnails' )
	{
		$product_image = get_site_option( 'usam_product_image', ['width' => 300, 'height' => 300]);
		$size = [ $product_image['width'], $product_image['height'] ];		
	}
	elseif ( $size == 'manage-products' )
	{	
		$size = [100, 100];		
	}		
	elseif ( !is_array($size) )
	{			
		$sizes = usam_get_image_sizes( false );
		if ( !isset($sizes[$size]) )
			return USAM_NO_IMAGE_URL.$file;	
		
		$size = array( $sizes[$size]['width'], $sizes[$size]['height'] );		
	}			
	$pathinfo = pathinfo(USAM_NO_IMAGE_DIR.$file);
	$_file = $pathinfo['filename'].'-'.$size[0].'x'.$size[1].'.'.$pathinfo['extension'];		

	if ( !file_exists(USAM_NO_IMAGE_DIR.$_file) )
	{
		if ( !$image_make )
		{	
			$image_make = true;
			$data = image_make_intermediate_size( USAM_NO_IMAGE_DIR.$file, $size[0], $size[1], false );
			if ( $data )
				$file = $data['file'];			
		}
	}
	else
		$file = $_file;
	return USAM_NO_IMAGE_URL.$file;	
}

function usam_get_image_sizes( $unset_disabled = true ) 
{
	$wais = & $GLOBALS['_wp_additional_image_sizes'];
	$sizes = array();
	foreach ( get_intermediate_image_sizes() as $_size )
	{
		if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) )
		{
			$sizes[ $_size ] = [
				'width'  => get_option( "{$_size}_size_w" ),
				'height' => get_option( "{$_size}_size_h" ),
				'crop'   => (bool) get_option( "{$_size}_crop" ),
			];
		}	
		elseif ( isset( $wais[$_size] ) )
		{
			$sizes[$_size] = [
				'width'  => $wais[ $_size ]['width'],
				'height' => $wais[ $_size ]['height'],
				'crop'   => $wais[ $_size ]['crop'],
			];
		}		
		if( $unset_disabled && ($sizes[ $_size ]['width'] == 0) && ($sizes[ $_size ]['height'] == 0) )
			unset( $sizes[ $_size ] );
	}
	return $sizes;
}

/**
* Восстановить размер миниатюры в случае если она отсутствует.
* @access private
*/
function _usam_regenerate_thumbnail_size( $thumbnail_id ) 
{ 
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
	
	if ( ! $metadata = wp_get_attachment_metadata( $thumbnail_id ) )
		$metadata = array();
	if ( empty($metadata['sizes']) )
		$metadata['sizes'] = array();

	$file = get_attached_file( $thumbnail_id );
	$generated = wp_generate_attachment_metadata( $thumbnail_id, $file );
	if ( empty($generated['sizes']) )
		return false;
	
	$metadata['sizes'] = array_merge( $metadata['sizes'], $generated['sizes'] );
	wp_update_attachment_metadata( $thumbnail_id, $metadata );
	return true;
}

/*
 * Создать размер изображение товара
 */
function usam_create_image_by_size( $attachment_id = 0, $width = null, $height = null ) 
{	
	if ( ( ( $width >= 10 ) && ( $height >= 10 ) ) && ( ( $width <= 1024 ) && ( $height <= 1024 ) ) )
		$intermediate_size = "usam-{$width}x{$height}";
	// Получить адрес изображения, если у нас есть достаточно информации	
	$image_url = false;	
	if ( !$attachment_id ) 
		return usam_get_no_image_uploaded_file([$width, $height]);
	if ( !empty($intermediate_size) ) 
	{ 
		//Получить всю необходимую информацию о вложениях		
		$file_path  = get_attached_file( $attachment_id );		
		if( file_exists($file_path) )
		{ 
			$uploads    = wp_upload_dir();
			$metadata = wp_get_attachment_metadata( $attachment_id );		
			if ( !empty($metadata['sizes']) && isset($metadata['sizes'][$intermediate_size]) && isset($metadata['sizes'][$intermediate_size]['file']) ) 
			{ // Определить, если у нас уже есть изображение такого размера				
				
				$image = image_get_intermediate_size($attachment_id, [$width, $height]);		
				if ( !empty($image['path']) && file_exists($uploads['basedir'].'/'.$image['path']) )
					$image_url = $image['url'];					
			}
		} 
	} 
	if ( $image_url === false )
		$image_url = home_url( "index.php?usam_action=scale_image&attachment_id={$attachment_id}&width=$width&height=$height" );	
    if( is_ssl() ) 
		str_replace('http://', 'https://', $image_url);
	
	return apply_filters( 'usam_create_image_by_size', $image_url );
}

function usam_get_icon( $icon, $size = 30, $args = [] ) 
{
	$icon_url = USAM_SVG_ICON_URL."?ver=".USAM_VERSION_ASSETS."#{$icon}-usage";	
	$img = '';
	if ( $icon_url )
	{
		if ( is_array($size) )
		{
			$args = $size;
			$size = 30;
		}
		$attr = [];
		foreach ( $args as $k => $v )
		{
			$attr[] = "$k='$v'";
		}
		$img = "<img ".implode(' ',$attr)." class='svg_icon svg_icon_{$icon}' src='".$icon_url."' width = '$size' height='$size' loading='lazy'>";
	}		
	return $img;
}

function usam_get_system_svg_icon( $icon, $class = '', $args = [] ) 
{	
	$html = '';
	if ( is_array($class) )
	{
		$args =	$class;
		$class = '';
	}
	if ( isset($args['class']) )
		$args['class'] .= " svg_icon svg_icon_{$icon} $class";
	else
		$args['class'] = "svg_icon svg_icon_{$icon} $class";				
	$attr = [];
	foreach ( $args as $k => $v )
	{
		$attr[] = "$k='$v'";
	}
	$html = "<span ".implode(' ',$attr)."><svg shape-rendering='geometricPrecision' xmlns='http://www.w3.org/2000/svg'><use xlink:href='".USAM_SVG_ICON_URL."?ver=".USAM_VERSION_ASSETS."#{$icon}'></use></svg></span>";
	return $html;
}

function usam_system_svg_icon( $icon, $class = '', $args = [] ) 
{
	echo usam_get_system_svg_icon( $icon, $class, $args ); 
}

function usam_get_svg_icon( $icon, $class = '', $args = [] ) 
{
	$file_path = get_template_directory().'/assets/sprite.svg';	
	if ( file_exists( $file_path ) )
	{
		$file_url =  get_template_directory_uri().'/assets/sprite.svg';			
		if ( USAM_DEBUG_THEME )
			$file_url = str_replace(get_template_directory_uri(), WP_HOME, $file_url);	
	}
	else
	{
		$file_url = USAM_CORE_THEME_URL.'assets/sprite.svg';	
		if ( USAM_DEBUG_THEME )
			$file_url = str_replace(plugins_url('universam/theme'), WP_HOME, $file_url);
	}
	$html = '';
	if ( $file_url )
	{
		if ( is_array($class) )
		{
			$args =	$class;
			$class = '';
		}
		if ( isset($args['class']) )
			$args['class'] .= " svg_icon svg_icon_{$icon} $class";
		else
			$args['class'] = "svg_icon svg_icon_{$icon} $class";				
		$attr = [];
		foreach ( $args as $k => $v )
		{
			$attr[] = "$k='$v'";
		}
		$html = "<span ".implode(' ',$attr)."><svg shape-rendering='geometricPrecision' xmlns='http://www.w3.org/2000/svg'><use xlink:href='{$file_url}?ver=".USAM_VERSION_ASSETS."#{$icon}'></use></svg></span>";
	} 
	return $html;
}

function usam_svg_icon( $icon, $class = '', $args = [] ) 
{
	echo usam_get_svg_icon( $icon, $class, $args ); 
}

function usam_create_image_webp( $old_filepath, $new_filepath, $quality = null )
{
	if( !$quality )
		$quality = get_site_option( 'usam_image_quality', 100 );
	
	$result = false;
	if ( file_exists($old_filepath) && function_exists('imageWebp') )
	{
		$path_parts = pathinfo($old_filepath);
		if ( !isset($path_parts['extension']) )
			return false;
		if ( $path_parts['extension'] == 'jpg' || $path_parts['extension'] == 'jpeg' )
			$img = imageCreateFromJpeg( $old_filepath );
		elseif ( $path_parts['extension'] == 'png' )
		{
			$img = imageCreateFromPng( $old_filepath );
			imagepalettetotruecolor($img);
		}
		elseif ( $path_parts['extension'] == 'gif' )
			$img = imageCreateFromGif( $old_filepath );			
		else
			return false;		
		if( $img )
		{
			$result = imageWebp($img, $new_filepath, $quality);
			imagedestroy($img);	
			if ( $result )
				unlink($old_filepath);
		}
	}
	return $result;
}

function usam_create_image_png( $old_filepath, $new_filepath, $quality = null )
{
	if( !$quality )
		$quality = get_site_option( 'usam_image_quality', 100 );
	
	$result = false;
	if ( file_exists($old_filepath) )
	{
		$path_parts = pathinfo($old_filepath);
		if ( $path_parts['extension'] == 'webp' )
			$img = imagecreatefromwebp( $old_filepath );			
		else
			return false;
		$result = imagePNG($img, $new_filepath, $quality);
		imagedestroy($img);	
		if ( $result )
			unlink($old_filepath);
	}
	return $result;
}
?>