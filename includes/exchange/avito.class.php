<?php
// Класс выгрузки в Авито */
class USAM_Avito 
{
	private $token = null;

	public $enabled = false;
	protected $version = '1';
	protected $url_api = 'https://api.avito.ru/';

	public function __construct( ) 
	{ 	
		$avito = get_option( 'usam_avito' );	
		if ( !empty( $token ) ) 
		{			
			$this->token  = ( isset($avito['token'] ) ) ? $avito['token'] : null;
		}		
	}

	public function is_token( )
	{	
		return !empty($this->token)?true:false;
	}
	
	protected function get_url( $resource )
	{
		return "$this->url_api/v{$this->version}/{$resource}";
	}
	
	protected function send_request( $resource, $params = array() )
	{			
		if ( !$this->is_token() )
			return false;
		
		$headers["Accept"] = 'application/json';
		$headers["Content-type"] = 'application/json';	
		$headers["Authorization"] = 'Bearer ' . $this->token;		
		
		$url_api = $this->get_url( $resource );			
		$data = wp_remote_get( $url_api, array('sslverify' => true, 'body' => $params, 'headers' => $headers ));	
		
		if ( is_wp_error($data) )
			return $data->get_error_message();
		$resp = json_decode($data['body'], true);	
		if ( isset($resp['errors'] ) ) 
		{			
			if ( isset($resp['errors']['message']) )
				$this->set_error( $resp['errors']['message'] );	
			return false;
		}		
		return $resp;		
	}	

	function get_access_token( )
	{		 		
		$avito = get_option( 'usam_avito' );
		$params = array( 'grant_type' => 'client_credentials', 'client_id' => $avito['client_id'], 'client_secret' => $avito['client_secret'] );		
		
		$query = http_build_query($params);  
		$data = wp_remote_post($this->url_api.'/token?'.$query, array('sslverify' => true));

		if (is_wp_error($data))
			return $data->get_error_message();

		$resp = json_decode($data['body'],true);				
		if ( isset($resp['error'] ) ) 
		{			
			$this->set_error( $resp['error'] );	
			return false;
		}	
		if ( !empty($resp['access_token']) )		
		{
			$this->token = $resp['access_token'];
			return $resp['access_token'];
		}			
		return false;
	}
}
