<?php
/**
 * Передача данных яндекса
 */
require_once( USAM_FILE_PATH . '/includes/seo/yandex/yandex.class.php' );

class USAM_Yandex_Market extends USAM_Yandex
{	
	protected $version = '2';
	protected $url_api = 'https://api.partner.market.yandex.ru';
	
	protected function get_headers( )
	{
		$headers["Accept"] = 'application/json';
		$headers["Content-type"] = 'application/json';			
		return $headers;
	}
	
	function get_region( $params )
	{	
		$params['oauth_token'] = $this->get_token();
		$params['oauth_client_id'] = $this->option['client_id'];
		
		$result = $this->send_request( "regions", $params );		
		if ( !empty($result['regions']) )
			return $result['regions'];
		
		return array();
	}	
}
?>