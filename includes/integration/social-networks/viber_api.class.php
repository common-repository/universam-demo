<?php
// Phone Viber

class USAM_Viber_API
{
    private $url_api = "https://chatapi.viber.com/pa";
    private $token = "";
	private $option = array();
	
	public function __construct( $option ) 
	{
		$this->token = !empty($option['access_token'])?$option['access_token']:$this->token;
		$this->option = $option;
	}
	
	private function message_decode( $message )
	{
		$message = str_replace(['<b>','</b>'], '*', $message );
		$message = str_replace(['<i>','</i>'], '_', $message );
		$message = str_replace(['<h>','</h>'], '```', $message );
		
		$message = strip_tags($message);
		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
		$message = htmlspecialchars_decode($message);
		$message = str_replace('\n', chr(10), $message );
		return $message;
	}
	
	public function send_message( $args )
    {			
		if ( !empty($args['contact_id']) )
		{
			$params['receiver'] = usam_get_contact_metadata( $args['contact_id'], 'viber_user_id' );				
			if ( empty($this->option['from_group']) )	
			{
				$contact = usam_get_contact( $args['contact_id'] );
				$params['sender']['name'] = $contact['appeal'];
			}
		}
		elseif( !empty($args['receiver']) )
		{
			$params['receiver'] = $args['receiver'];
		}
		else
		{
			return false;
		}			
		if ( !empty($params['sender']['name']) )
			$params['sender']['name'] = $this->option['name'];
		
		if ( !isset($args['type']) )
			$params['type'] = 'text';
		else
			$params['type'] = $args['type'];
		
		if ( isset($args['message']) )
			$params['text'] = $this->message_decode($args['message']);			
		
		return $this->send_request('send_message', $params );
    }
	
	public function get_account_info( )
	{	
		$result = $this->send_request('get_account_info' );		
		return $result;
	}
	
	public function set_webhook( )
    {
		$args['url'] = home_url("/api/viber");
		$args['event_types'] = ["subscribed", "unsubscribed", "delivered", "message", "seen"];
		return $this->send_request('set_webhook', $args );
    }
		
	private function send_request($method, $params = array() )
    {
		if ( empty($this->token) )
			return false;
		
		$headers["Content-type"] = 'application/json';	
		$headers["Cache-Control"] = 'no-cache';			
		$params['auth_token'] = $this->token;		
		$data = wp_remote_post( "$this->url_api/{$method}", array('sslverify' => true, 'body' => json_encode($params), 'headers' => $headers ));	
	
		if ( is_wp_error($data) )
			return $data->get_error_message();
		$resp = json_decode($data['body'], true);			
	
		if ( isset($resp['status']) && $resp['status'] == 2 ) 
		{			
			if ( isset($resp['status_message']) )
				$this->set_error( $resp['status_message'] );	
			return false;
		}		
		return $resp;
    }
	
	protected function set_error( $error )
	{
		usam_log_file( __('Vber','usam').': '.$error );
	}
}
?>