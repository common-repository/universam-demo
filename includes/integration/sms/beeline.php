<?php
// Name: Билайн шлюз
class USAM_SMS_Gateway_beeline extends USAM_SMS_Gateway
{		
	protected $API_URL = 'https://a2p-sms-https.beeline.ru/';
	protected function send( $phone_id, $message, $naming )
	{ 		
		$naming = mb_convert_encoding($naming, 'windows-1251', mb_detect_encoding($naming) );
		$params = ['action' => 'post_sms', 'target' => $phone_id, 'message' => $message, 'sender' => $naming, 'user' => $this->login, 'pass' => $this->password];
		$response = $this->send_request( $params, 'proto/http' );		
		$xmldoc = new SimpleXMLElement( $response );
		if ( isset($xmldoc->errors) )
			$this->set_error( $xmldoc->errors->error );	
		elseif ( isset($xmldoc->result) )
		{
			$sms_id = $xmldoc->result->sms['id']->__toString();		
			if ( !empty($sms_id) )
				return $sms_id;
		}
		return false;		
	}	
	
	protected function get_headers(  )
	{
		return $headers["Content-type"] = 'text/xml (UTF-8); content-encoding: gzip';
	}
}
?>