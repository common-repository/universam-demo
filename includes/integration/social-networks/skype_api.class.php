<?php
class USAM_Skype_API
{
	protected $name = null;
	protected $app_id = null;
	protected $secret_key = null;
	protected $version = '2';
	
	const API_URL = 'https://apis.skype.com';
	//const API_URL = 'https://api.botframework.com/.default';
		
	function __construct( $option ) 
	{				
		$this->app_id = !empty($option['app_id'])?$option['app_id']:$this->app_id;	
		$this->secret_key = !empty($option['access_token'])?$option['access_token']:$this->secret_key;			
	}
	
	private function message_decode( $message )
	{	
		$message = strip_tags($message);
		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
		$message = htmlspecialchars_decode($message);  	
		$message = str_replace('\n', chr(10), $message );	
		return $message;
	}
			
	function get_access_token( )
	{						
		if ( $this->secret_key == null || $this->app_id == null )
			return false;
				
		$access_token = get_transient( 'skype_access_token' );
		if ( !empty($access_token) )
			return $access_token;
		
		$params = array( 'client_id' => $this->app_id, 'client_secret' => $this->secret_key, 'scope' => 'https://graph.microsoft.com/.default', 'grant_type' => 'client_credentials' );//https://
	
		$headers["Content-Type"] = 'application/x-www-form-urlencoded';
		$headers["Cache-Control"] = 'no-cache';	
		
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,		
			'body' => $params,			
			'cookies' => array(),
			'sslverify' => true
		);	
		$data = wp_remote_post('https://login.microsoftonline.com/common/oauth2/v2.0/token', $args );
		$resp = json_decode($data['body'],true);		
		if ( isset($resp['error_description'] ) ) 
		{			
			$this->set_error( $resp['error_description'] );	
		}	
		elseif ( isset($resp['access_token']) )
		{ 
			set_transient( 'skype_access_token', $resp['access_token'], $resp['expires_in']-60 );
			return $resp['access_token'];
		}
		return false;		
	}	
			
	public function send_message( $args )
	{			
		$params = array(
			'type' => 'message', //Мы отвечаем обычным сообщением
			'text' => $this->message_decode($args['message']),
			'textFormat' => 'plain', //Говорим, что ответ - это простой текст
			'locale' => 'ru-RU', 
			'replyToId' => (string)$args['id'],  
			'recipient' => $args['recipient'],
			'conversation' => array(
				'id' => (string)$args['conversation_id'] //id беседы
			)
		);	
		if ( !empty($args['contact_id']) )
			$params['from'] = usam_get_contact_metadata( $args['contact_id'], 'skype_user_id' ); 		
		else
			$params['from'] = $args['from']; 	
		$url = rtrim($args['serviceUrl'], '/') . '/v3/conversations/' . $args['conversation_id'] . '/activities/' . urlencode($args['id']);	
		$result = $this->send_request( $params, $url );
		return $result;
	}	
		
	// Отправить запрос
	function send_request( $params, $url )
	{			
		$auth_data = $this->get_access_token();			
		if ( empty($auth_data) )
			return false;				
			
		$headers["Authorization"] = 'Bearer ' . $auth_data['access_token'];	
		$headers["Content-Type"] = 'application/json;charset=UTF-8';
		
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => json_encode($params),
			'sslverify' => true
		);			
		//self::API_URL.$url
		$data = wp_remote_post( $url, $args );		
		if (is_wp_error($data))
			return $data->get_error_message();		
			
		$resp = json_decode( $data['body'],true );		
		if ( isset($resp['error'] ) ) 
		{			
			$this->set_error( $resp['error'] );	
			return false;
		}		
		return $resp['response'];		
	}
	
	protected function set_error( $error )
	{
		usam_log_file( $error );
	}
}
?>