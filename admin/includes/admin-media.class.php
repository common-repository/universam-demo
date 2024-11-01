<?php
class USAM_Admin_Media
{	
	public function __construct() 
	{
		add_action( 'manage_upload_columns', [&$this, 'add_column'], 99, 2 );
		add_action( 'manage_media_custom_column', [&$this, 'display_column'], 99, 2 );
	}
	
	public function add_column( $cols ) 
	{
		$cols['filesize'] = __( 'Размер', 'usam' );
		return $cols;
	}

	public function display_column( $column_name, $attachment_id ) 
	{
		if ( 'filesize' == $column_name ) 
		{
			$metadata = wp_get_attachment_metadata( $attachment_id );
			if ( isset($metadata['filesize']) )
			{
			/*	$filepath = get_attached_file( $attachment_id ); 
				$filesize = filesize( $filepath );	
				if ( $metadata['filesize'] !== $filesize )
				{
					$metadata['filesize'] =	$filesize; echo ' filesize ';
					wp_update_attachment_metadata( $attachment_id, $metadata );		
				}*/			
				echo size_format( $metadata['filesize'] );
			}
		}
	}

	public function add_sortable( $columns ) 
	{
		$columns['filesize'] = 'filesize';
		return $columns;
	}

	public function request( $vars ) 
	{		
		if ( isset( $vars->query_vars['orderby'] ) && 'filesize' == $vars->query_vars['orderby'] ) 
		{
			$vars->query_vars['meta_key'] = 'filesize';		
		}
	}
}
new USAM_Admin_Media;