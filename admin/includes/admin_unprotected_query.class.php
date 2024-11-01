<?php
/**
 *  ========================== Обработка запросов ================================================
 */ 	
require_once( USAM_FILE_PATH .'/includes/ajax.php' );
class USAM_Admin_Unprotected_Query extends USAM_Callback
{				
	protected $query = 'unprotected_query';
	protected $verify_nonce = false;
	
	public function __construct() 
	{	
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) 
			add_action( 'wp_ajax_unprotected_query', array($this, 'handler_ajax') );	
		else
			add_action( 'init', array($this, 'handler') );		
	}
	
	function controller_mail_editor_shortcode() 
	{	
		require_once( USAM_FILE_PATH . '/admin/includes/tinymce/window-mail-editor-scortcode.php' );			
		exit;
	}
			
	function controller_instagram_token() 
	{			
		$url = admin_url('admin.php?service_api=instagram');		
		if ( !empty($_REQUEST['code']) )
		{ 			
			$code = sanitize_text_field($_REQUEST['code']);			
			require_once( USAM_APPLICATION_PATH . '/social-networks/instagram_api.class.php' );
			$vkontakte = new USAM_Instagram_API();			
			$token = $vkontakte->get_access_token( $code );	
			
			$instagram_api = get_option('usam_instagram_api' );	
			$instagram_api['token'] = $token;				
			update_option('usam_instagram_api', $instagram_api );
		}				
		wp_redirect( $url );
		exit;
	}	
}
new USAM_Admin_Unprotected_Query();
?>