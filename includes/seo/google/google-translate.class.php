<?php
/**
 * Google переводчик
 */
require_once( USAM_FILE_PATH . '/includes/seo/google/google.class.php' );
class USAM_Google_Translate extends USAM_Google_Cloud_Platform
{ 
	protected $token;
	protected $errors = array();
	protected $url_api = 'https://translate.googleapis.com';
//	protected $version = 'v3beta1';
		
	public function translate_text( $text )
	{				
		$result = $this->send_request('language/translate/v2', ['contents' => $text]);		
		if ( !empty($result['data']) )
			return $result['data']['translations']['translatedText'];
		return $result;
	}	
}
?>