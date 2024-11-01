<?php 
require_once( USAM_FILE_PATH . '/includes/parser/parser.class.php' );
require_once(USAM_FILE_PATH.'/includes/parser/parsing_sites_query.class.php');

class USAM_Product_Parser
{
	private $product_id = 0;	
	protected $options = [];
	private $message = array();
	private $errors = array();
	
	public function __construct( $product_id = 0 ) 
	{	
		$this->product_id = $product_id;
	}
			
	public function media_handle_sideload( $url )
	{			
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$anonymous_function = function($parsed_args, $url) { $parsed_args['reject_unsafe_urls'] = false; return $parsed_args; };	
		add_filter( 'http_request_args', $anonymous_function, 10, 2 );
			
		$tmp = download_url( $url );			
		$attachment_id = false;
		if( !is_wp_error( $tmp ) )
		{ 
			$path_parts = pathinfo($url);		
			if ( function_exists('image_type_to_extension') )
				$fileextension = image_type_to_extension( @exif_imagetype( $url ) );
			$fileextension = $fileextension ? $fileextension : '.'.$path_parts['extension'];
			$file = $this->get_file_title( $path_parts['filename'] );	
			$post_excerpt = !empty($this->p_data['post_excerpt'])?$this->p_data['post_excerpt']:'';
			$attachment_id = media_handle_sideload(['name' => $file['file_name'].$fileextension, 'tmp_name' => $tmp], $this->product_id, $post_excerpt );
		}
		return $attachment_id;
	}
		
	public function get_website_data( $url )
	{
		$data = [];
		$this->options = $this->get_option_url( $url );								
		if ( $this->options )	
		{
			$parser = new USAM_Parser( $this->options );
			$data = $parser->get_website_data( $url );
			$parser->clear();
		}
		return $data;
	}
	
	private function get_option_url( $url )
	{
		if ( $url )
		{
			static $sites = null;
			if ( $sites === null )
				$sites = usam_get_parsing_sites(['site_type' => 'supplier']);
			$host = parse_url($url, PHP_URL_HOST);	
		//	$host = str_replace("www.","",$host);						
			foreach ( $sites as $site ) 
			{		
				if ( $host == $site->domain )
					return (array)$site;	
			}
		}
		return false;
	}	
	
	public function get_data( $url )
	{	
		if ( $url )
		{			
			$data = $this->get_website_data( $url );
			if ( $this->options === false )		
			{
				$data['error'] = __("Настройки сайта не найдены", "usam");			
				$data['status'] = 1;
			}
			elseif( $data )
			{													
				$data['type_price'] = $this->options['type_price'];
				$data['stock'] = USAM_UNLIMITED_STOCK;
				$data['storage_id'] = $this->options['store'];
				$data['status'] = 0;					
				if ( !empty($data['thumbnail']) )
				{
					$thumbnail_id = $this->media_handle_sideload( $data['thumbnail'] );
					if ( is_numeric($thumbnail_id))
						set_post_thumbnail( $this->product_id, $thumbnail_id );
				}
			}
			else
			{
				$data['error'] = sprintf( __("Страница не доступна. Скорей всего доступ к %s возможен только по паролю.", "usam"), $this->options['domain'] );
				$data['status'] = 1;
			}
		}	
		else
		{
			$data['error'] = __("А где ссылка?", "usam");
			$data['status'] = 1;
		}
		return $data;
	}	
}
?>