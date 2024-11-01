<?php
class USAM_Regenerate_Thumbnails
{	
	public $skipped_thumbnails = array();
	public $attachment;
	public $fullsizepath;

	public static function get_instance( $attachment_id )
	{
		$attachment = get_post( $attachment_id );
		if ( !$attachment ) 
			return new WP_Error('regenerate_thumbnails_regenerator_attachment_doesnt_exist', __( 'Вложений с таким идентификатором не существует.', 'usam' ), array('status' => 404));
		if ( 'attachment' !== get_post_type( $attachment ) )
			return new WP_Error('regenerate_thumbnails_regenerator_not_attachment', __( 'Не является вложением.', 'usam' ), array('status' => 400));
		if ( self::is_site_icon( $attachment ) )
			return new WP_Error('regenerate_thumbnails_regenerator_is_site_icon', __( 'Это вложение является значком сайта, поэтому миниатюры трогать не следует.', 'usam' ), array('status' => 415, 'attachment' => $attachment));		
		return new USAM_Regenerate_Thumbnails( $attachment );
	}

	private function __construct( WP_Post $attachment ) {
		$this->attachment = $attachment;
	}

	/**
	 * Returns whether the attachment is or was a site icon.
	 */
	public static function is_site_icon( WP_Post $attachment ) {
		return ( 'site-icon' === get_post_meta( $attachment->ID, '_wp_attachment_context', true ) );
	}

	/**
	 * Get the path to the fullsize attachment.
	 */
	public function get_fullsizepath()
	{
		if ( $this->fullsizepath )
			return $this->fullsizepath;		
		if ( function_exists( 'wp_get_original_image_path' ) )
			$this->fullsizepath = wp_get_original_image_path( $this->attachment->ID );
		else
			$this->fullsizepath = get_attached_file( $this->attachment->ID );
		if ( false === $this->fullsizepath || ! file_exists( $this->fullsizepath ) ) 
		{
			return new WP_Error( 'regenerate_thumbnails_regenerator_file_not_found', sprintf(__( "Полноразмерный файл изображения не может быть найден в вашем каталоге загрузки в <code>%s</code>. Без него невозможно создать новые эскизы изображений.", 'usam' ), _wp_relative_upload_path($this->fullsizepath )), ['status' => 404, 'fullsizepath' => _wp_relative_upload_path( $this->fullsizepath ), 'attachment' => $this->attachment]);
		}
		return $this->fullsizepath;
	}

	public function regenerate( $args = array() ) 
	{
		global $wpdb;

		$args = wp_parse_args( $args, ['only_regenerate_missing_thumbnails' => true, 'delete_unregistered' => false]);
		$fullsizepath = $this->get_fullsizepath();
		if ( is_wp_error( $fullsizepath ) ) 
		{
			$fullsizepath->add_data(['attachment' => $this->attachment]);
			return $fullsizepath;
		}
		$old_metadata = wp_get_attachment_metadata( $this->attachment->ID );
		if ( $args['only_regenerate_missing_thumbnails'] )
			add_filter( 'intermediate_image_sizes_advanced', array( $this, 'filter_image_sizes_to_only_missing_thumbnails' ), 10, 2 );
		
		require_once( ABSPATH . 'wp-admin/includes/admin.php' );
		
		add_filter( 'usam_update_attachment', '__return_false' );
		$new_metadata = wp_create_image_subsizes( $fullsizepath, $this->attachment->ID );
		unset( $new_metadata['image']['data'] );	
		add_filter( 'usam_update_attachment', '__return_true' );			
		if ( $args['only_regenerate_missing_thumbnails'] ) 
		{
			foreach ( $this->skipped_thumbnails as $thumbnail ) 
			{
				if ( ! empty( $old_metadata['sizes'][ $thumbnail ] ) )
					$new_metadata['sizes'][ $thumbnail ] = $old_metadata['sizes'][ $thumbnail ];
			}
			$this->skipped_thumbnails = [];
			remove_filter( 'intermediate_image_sizes_advanced', array( $this, 'filter_image_sizes_to_only_missing_thumbnails' ), 10 );
		}		
		$wp_upload_dir = dirname( $fullsizepath ) . DIRECTORY_SEPARATOR;	
		if ( $args['delete_unregistered'] ) 
		{ 
			$intermediate_image_sizes = get_intermediate_image_sizes();
			if ( !empty($old_metadata['sizes']) )
			{
				foreach( $old_metadata['sizes'] as $old_size => $old_size_data ) 
				{
					if ( in_array( $old_size, $intermediate_image_sizes ) )
						continue;
					wp_delete_file( $wp_upload_dir . $old_size_data['file'] );
					unset( $new_metadata['sizes'][ $old_size ] );
				}
			}
			if ( !empty($new_metadata['file']) )
			{
				$relative_path = dirname( $new_metadata['file'] ) . DIRECTORY_SEPARATOR;				
				$whitelist = $wpdb->get_col( $wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value REGEXP %s ", '^' . preg_quote( $relative_path ) . '[^' . preg_quote( DIRECTORY_SEPARATOR ) .']+-[0-9]+x[0-9]+\.'));
				$whitelist = array_map( 'basename', $whitelist );
			}
			else
				$whitelist = [];
			$filelist = [];
			foreach ( scandir( $wp_upload_dir ) as $file ) 
			{
				if ( '.' == $file || '..' == $file || ! is_file( $wp_upload_dir . $file ) ) {
					continue;
				}
				$filelist[] = $file;
			}
			$registered_thumbnails = [];
			if ( !empty($new_metadata['sizes']) )
			{
				foreach ( $new_metadata['sizes'] as $size )
					$registered_thumbnails[] = $size['file'];
			}
			$fullsize_parts = pathinfo( $fullsizepath );

			foreach($filelist as $file)
			{
				if ( in_array($file, $whitelist) || in_array($file, $registered_thumbnails) )
					continue;
				if ( ! preg_match( '#^' . preg_quote( $fullsize_parts['filename'], '#' ) . '-[0-9]+x[0-9]+\.' . preg_quote( $fullsize_parts['extension'], '#' ) . '$#', $file ) )
					continue;
				wp_delete_file( $wp_upload_dir . $file );
			}
		} 
		elseif ( ! empty($old_metadata) && ! empty($old_metadata['sizes']) && is_array($old_metadata['sizes']) ) 
		{
			foreach ( $old_metadata['sizes'] as $old_size => $old_size_data ) 
			{
				if ( empty( $new_metadata['sizes'][ $old_size ] ) ) 
				{
					$new_metadata['sizes'][ $old_size ] = $old_metadata['sizes'][ $old_size ];
					continue;
				}
				$new_size_data = $new_metadata['sizes'][$old_size];
				if ( $new_size_data['width'] !== $old_size_data['width'] && $new_size_data['height'] !== $old_size_data['height'] && file_exists( $wp_upload_dir . $old_size_data['file'] )	)
					$new_metadata['sizes'][ $old_size . '_old_' . $old_size_data['width'] . 'x' . $old_size_data['height'] ] = $old_size_data;			
			}
		}
		$max_width = get_site_option('usam_max_width');
		$max_height = get_site_option('usam_max_height');	
		if ( $max_height && $max_width && $new_metadata['width'] > $max_width && $new_metadata['height'] > $max_height ) 
		{			
			if ( $this->attachment->post_parent )
			{
				$post = get_post( $this->attachment->post_parent );	
				if ( isset($post->post_type) && $post->post_type == 'usam-product' )
				{
					$image = wp_get_image_editor( $fullsizepath );				
					if ( ! is_wp_error($image) ) 					
					{
						$image->resize( $max_width, $max_height, false );			
						$image->save( $fullsizepath );
						$size = $image->get_size( $fullsizepath );		
						$new_metadata['width'] = $size['width'];
						$new_metadata['height'] = $size['height'];
						$new_metadata['filesize'] = filesize( $fullsizepath );
					}
					clean_post_cache( $post );
				}
			}
		}
		wp_update_attachment_metadata( $this->attachment->ID, $new_metadata );
		return $new_metadata;
	}

	public function filter_image_sizes_to_only_missing_thumbnails( $sizes, $fullsize_metadata ) 
	{	
		if ( ! $sizes )
			return $sizes;

		$fullsizepath = $this->get_fullsizepath();
		if ( is_wp_error( $fullsizepath ) )
			return $sizes;

		$editor = wp_get_image_editor( $fullsizepath );
		if ( is_wp_error( $editor ) )
			return $sizes;	
 
		$metadata = wp_get_attachment_metadata( $this->attachment->ID );
		foreach ( $sizes as $size => $size_data ) 
		{
			if ( empty( $metadata['sizes'][ $size ] ) )
				continue;

			if ( ! isset( $size_data['width'] ) && ! isset( $size_data['height'] ) )
				continue;

			if ( ! isset( $size_data['width'] ) ) {
				$size_data['width'] = null;
			}
			if ( ! isset( $size_data['height'] ) ) {
				$size_data['height'] = null;
			}

			if ( ! isset( $size_data['crop'] ) ) {
				$size_data['crop'] = false;
			}
			$thumbnail = $this->get_thumbnail($editor, $fullsize_metadata['width'],	$fullsize_metadata['height'], $size_data['width'], $size_data['height'], $size_data['crop']);
			if ( false === $thumbnail || ($thumbnail['width'] === $metadata['sizes'][ $size ]['width'] && $thumbnail['height'] === $metadata['sizes'][ $size ]['height'] && file_exists( $thumbnail['filename'] ) )	) 
			{
				$this->skipped_thumbnails[] = $size;
				unset( $sizes[ $size ] );
			}
		}
		return apply_filters( 'usam_regenerate_thumbnails_missing_thumbnails', $sizes, $fullsize_metadata, $this );
	}

	/**
	 * Generate the thumbnail filename and dimensions for a given set of constraint dimensions.
	 */
	public function get_thumbnail( $editor, $fullsize_width, $fullsize_height, $thumbnail_width, $thumbnail_height, $crop ) 
	{
		$dims = image_resize_dimensions( $fullsize_width, $fullsize_height, $thumbnail_width, $thumbnail_height, $crop );
		if ( ! $dims ) {
			return false;
		}
		list( , , , , $dst_w, $dst_h ) = $dims;
		$suffix   = "{$dst_w}x{$dst_h}";
		$file_ext = strtolower( pathinfo( $this->get_fullsizepath(), PATHINFO_EXTENSION ) );

		return ['filename' => $editor->generate_filename( $suffix, null, $file_ext ), 'width' => $dst_w, 'height' => $dst_h];
	}

	public function update_usages_in_posts( $args = array() ) 
	{
		return array();

		$args = wp_parse_args( $args, ['post_type' => array(), 'post_ids' => array(), 'posts_per_loop' => 10]);

		if ( empty( $args['post_type'] ) ) {
			$args['post_type'] = array_values( get_post_types( array( 'public' => true ) ) );
			unset( $args['post_type']['attachment'] );
		}
		
		$offset        = 0;
		$posts_updated = array();
		while ( true ) 
		{
			$posts = get_posts(['numberposts' => $args['posts_per_loop'], 'offset' => $offset, 'orderby' => 'ID', 'order' => 'ASC', 'include' => $args['post_ids'], 'post_type' => $args['post_type'], 's' => 'wp-image-' . $this->attachment->ID, 'update_post_meta_cache' => false, 'update_post_term_cache' => false]);

			if ( ! $posts ) {
				break;
			}

			$offset += $args['posts_per_loop'];

			foreach ( $posts as $post ) {
				$content = $post->post_content;
				$search  = array();
				$replace = array();

				// Find all <img> tags for this attachment and update them.
				preg_match_all(
					'#<img [^>]+wp-image-' . $this->attachment->ID . '[^>]+/>#i',
					$content,
					$matches,
					PREG_SET_ORDER
				);
				if ( $matches ) 
				{
					foreach ( $matches as $img_tag )
					{
						preg_match( '# class="([^"]+)?size-([^" ]+)#i', $img_tag[0], $thumbnail_size );

						if ( $thumbnail_size ) {
							$thumbnail = image_downsize( $this->attachment->ID, $thumbnail_size[2] );

							if ( ! $thumbnail ) {
								continue;
							}

							$search[] = $img_tag[0];

							$img_tag[0] = preg_replace( '# src="[^"]+"#i', ' src="' . esc_url( $thumbnail[0] ) . '"', $img_tag[0] );
							$img_tag[0] = preg_replace(
								'# width="[^"]+" height="[^"]+"#i',
								' width="' . esc_attr( $thumbnail[1] ) . '" height="' . esc_attr( $thumbnail[2] ) . '"',
								$img_tag[0]
							);

							$replace[] = $img_tag[0];
						}
					}
				}
				$content = str_replace( $search, $replace, $content );
				$search  = array();
				$replace = array();

				// Update the width in any [caption] shortcodes.
				preg_match_all(
					'#\[caption id="attachment_' . $this->attachment->ID . '"([^\]]+)? width="[^"]+"\]([^\[]+)size-([^" ]+)([^\[]+)\[\/caption\]#i',
					$content,
					$matches,
					PREG_SET_ORDER
				);
				if ( $matches ) {
					foreach ( $matches as $match ) {
						$thumbnail = image_downsize( $this->attachment->ID, $match[3] );

						if ( ! $thumbnail ) {
							continue;
						}

						$search[]  = $match[0];
						$replace[] = '[caption id="attachment_' . $this->attachment->ID . '"' . $match[1] . ' width="' . esc_attr( $thumbnail[1] ) . '"]' . $match[2] . 'size-' . $match[3] . $match[4] . '[/caption]';
					}
				}
				$content = str_replace( $search, $replace, $content );
				$updated_post_object = (object) array('ID' => $post->ID, 'post_content' => $content );
				$posts_updated[ $post->ID ] = wp_update_post( $updated_post_object, true );
			}
		}

		return $posts_updated;
	}

	public function get_attachment_info() 
	{
		$fullsizepath = $this->get_fullsizepath();
		if ( is_wp_error( $fullsizepath ) ) {
			$fullsizepath->add_data( array( 'attachment' => $this->attachment ) );

			return $fullsizepath;
		}

		$editor = wp_get_image_editor( $fullsizepath );
		if ( is_wp_error( $editor ) ) {
			// Display a more helpful error message.
			if ( 'image_no_editor' === $editor->get_error_code() ) {
				$editor = new WP_Error( 'image_no_editor', __( 'The current image editor cannot process this file type.', 'usam' ) );
			}

			$editor->add_data( array('attachment' => $this->attachment,	'status' => 415 ) );
			return $editor;
		}
		$metadata = wp_get_attachment_metadata( $this->attachment->ID );

		if ( false === $metadata || ! is_array( $metadata ) ) {
			return new WP_Error(
				'regenerate_thumbnails_regenerator_no_metadata',
				__( 'Unable to load the metadata for this attachment.', 'usam' ),
				array(
					'status'     => 404,
					'attachment' => $this->attachment,
				)
			);
		}

		if ( ! isset( $metadata['sizes'] ) ) {
			$metadata['sizes'] = array();
		}

		// PDFs don't have width/height set.
		$width  = ( isset( $metadata['width'] ) ) ? $metadata['width'] : null;
		$height = ( isset( $metadata['height'] ) ) ? $metadata['height'] : null;

		require_once( ABSPATH . '/wp-admin/includes/image.php' );

		$preview = false;
		if ( file_is_displayable_image( $fullsizepath ) ) {
			$preview = wp_get_attachment_url( $this->attachment->ID );
		} elseif (
			is_array( $metadata['sizes'] ) &&
			is_array( $metadata['sizes']['full'] ) &&
			! empty( $metadata['sizes']['full']['file'] )
		) {
			$preview = str_replace(
				wp_basename( $fullsizepath ),
				$metadata['sizes']['full']['file'],
				wp_get_attachment_url( $this->attachment->ID )
			);

			if ( ! file_exists( $preview ) ) {
				$preview = false;
			}
		}

		$response = array(
			'name'               => ( $this->attachment->post_title ) ? $this->attachment->post_title : sprintf( __( 'Attachment %d', 'usam' ), $this->attachment->ID ),
			'preview'            => $preview,
			'relative_path'      => _wp_get_attachment_relative_path( $fullsizepath ) . DIRECTORY_SEPARATOR . wp_basename( $fullsizepath ),
			'edit_url'           => get_edit_post_link( $this->attachment->ID, 'raw' ),
			'width'              => $width,
			'height'             => $height,
			'registered_sizes'   => array(),
			'unregistered_sizes' => array(),
		);

		$wp_upload_dir = dirname( $fullsizepath ) . DIRECTORY_SEPARATOR;

		$registered_sizes = RegenerateThumbnails()->get_thumbnail_sizes();

		if ( 'application/pdf' === get_post_mime_type( $this->attachment ) ) {
			$registered_sizes = array_intersect_key( $registered_sizes,	array('thumbnail' => true, 'medium' => true, 'large' => true) );
		}
		foreach ( $registered_sizes as $size ) {
			// Width and height are needed to generate the thumbnail filename.
			if ( $width && $height ) {
				$thumbnail = $this->get_thumbnail( $editor, $width, $height, $size['width'], $size['height'], $size['crop'] );

				if ( $thumbnail ) {
					$size['filename']   = wp_basename( $thumbnail['filename'] );
					$size['fileexists'] = file_exists( $thumbnail['filename'] );
				} else {
					$size['filename']   = false;
					$size['fileexists'] = false;
				}
			} elseif ( ! empty( $metadata['sizes'][ $size['label'] ]['file'] ) ) {
				$size['filename']   = wp_basename( $metadata['sizes'][ $size['label'] ]['file'] );
				$size['fileexists'] = file_exists( $wp_upload_dir . $metadata['sizes'][ $size['label'] ]['file'] );
			} else {
				$size['filename']   = false;
				$size['fileexists'] = false;
			}

			$response['registered_sizes'][] = $size;
		}

		if ( ! $width && ! $height && is_array( $metadata['sizes']['full'] ) ) {
			$response['registered_sizes'][] = array(
				'label'      => 'full',
				'width'      => $metadata['sizes']['full']['width'],
				'height'     => $metadata['sizes']['full']['height'],
				'filename'   => $metadata['sizes']['full']['file'],
				'fileexists' => file_exists( $wp_upload_dir . $metadata['sizes']['full']['file'] ),
			);
		}

		// Look at the attachment metadata and see if we have any extra files from sizes that are no longer registered.
		foreach ( $metadata['sizes'] as $label => $size ) {
			if ( ! file_exists( $wp_upload_dir . $size['file'] ) ) {
				continue;
			}

			foreach ( $response['registered_sizes'] as $registered_size ) {
				if ( $size['file'] === $registered_size['filename'] ) {
					continue 2;
				}
			}

			if ( ! empty( $registered_sizes[ $label ] ) ) {
				$label = sprintf( __( '%s (old)', 'usam' ), $label );
			}
			$response['unregistered_sizes'][] = ['label' => $label, 'width' => $size['width'],	'height' => $size['height'], 'filename' => $size['file'], 'fileexists' => true];
		}
		return $response;
	}
}
