<?php
/**
 * Передача данных яндекса
 */
require_once( USAM_FILE_PATH . '/includes/seo/yandex/yandex.class.php' );

class USAM_Yandex_Metrika_Management extends USAM_Yandex
{	
	protected $version = '1';
	protected $format = '';
	protected $url_api = 'https://api-metrika.yandex.net/management';
		
	function get_counters( $params = [] )
	{			
		$result = $this->send_request( "counters", $params );			
		if ( !empty($result['counters']) )
			return $result['counters'];
		
		return [];
	}
}
?>