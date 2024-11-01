<?php
/**
 * Google Page Speed
 */
require_once( USAM_FILE_PATH . '/includes/seo/google/google.class.php' );
class USAM_Google_Page_Speed extends USAM_Google_Cloud_Platform
{ 
	protected $url_api = 'https://www.googleapis.com/pagespeedonline';
	protected $version = 'v5';
			
	public function get_pagespeed( $url )
	{				
		ini_set("max_execution_time", 4600 );	
		$result = $this->send_request( 'runPagespeed', array( 'url' => $url ) );					
		if ( $result == false)
		{
			sleep(15);
			$result = $this->send_request( 'runPagespeed', array( 'url' => $url ) );			
		}
		return $result;
	}	
	
	protected function send_request( $resource, $params = array() )
	{	
		if ( !$this->is_token() )
		{ 
			$this->set_error( __('Не указан ключ от Google API', 'usam') );	
			return false;
		}	
		$params['key'] = $this->token;			
		$url_api = $this->get_url( $resource );	
		$url_api .= '?';	
		foreach ($params as $key => $value) 	
		{
			$url_api .= "&{$key}={$value}";	
		}	
		$options = array("ssl" => array("verify_peer" => false, "verify_peer_name" => false)); 
		$data = @file_get_contents($url_api, false, stream_context_create($options));		
		$resp = json_decode($data, true);			
		if ( isset($resp['errors'] ) ) 
		{			
			if ( isset($resp['errors']['message']) )
				$this->set_error( $resp['errors']['message'] );	
			return false;
		}		
		return $resp;		
	}		
}
?>