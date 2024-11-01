<?php
/**
 * Передача данных яндекса
 */
require_once( USAM_FILE_PATH . '/includes/seo/yandex/yandex.class.php' );
class USAM_Yandex_Translate extends USAM_Yandex
{	
	protected $version = '1.5';
	protected $url_api = 'https://translate.yandex.net/api';		
	function translate_text( $params )
	{					
		$result = $this->send_request( "tr.json/translate", $params );	
		if ( !empty($result['text']) )
			return $result['text'];
		
		return '';
	}
}
?>