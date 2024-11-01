<?php
// Name: МТС шлюз
class USAM_SMS_Gateway_MTS extends USAM_SMS_Gateway
{
	private $content_method = 'httppost';		
	protected function send( $msid, $message, $naming )
	{			
		$password = md5( $this->password  );		
		switch ( $this->content_method ) 
		{					
			case 'httpget':	
				$headers["Content-Type"] = 'application/x-www-form-urlencoded';
				$url = 'http://www.mcommunicator.ru/m2m/m2m_api.asmx/SendMessage';				
				$args = array(
					'method' => 'GET',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking' => true,
					'headers' => $headers,
					'body' => array( 'msid' => $msid, 'message' => $message, 'naming' => $naming, 'login' => $this->login, 'password' => $password )
				);					
				$response = wp_remote_post( $url, $args );				
			break;			
			case 'httppost':				
				$url = 'http://www.mcommunicator.ru/m2m/m2m_api.asmx/SendMessage';				
				$headers["Content-Type"] = 'application/x-www-form-urlencoded';
				$args = array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking' => true,
					'headers' => $headers,
					'body' => array( 'msid' => $msid, 'message' => $message, 'naming' => $naming, 'login' => $this->login, 'password' => $password ),
					'cookies' => array()
				);						
				$response = wp_remote_post( $url, $args );
			break;						
		}
		if ( !empty($response) && $response['response']['code'] != 200 )
		{
			$response_message = wp_remote_retrieve_response_message( $response );
			$this->set_error( $response_message );
		}	
		return true;
	}	
}
?>