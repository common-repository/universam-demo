<?php
/**
 * Google Analytics Reporting
 */
class USAM_Google_Analytics_Reporting
{ 
	protected $token;
	protected $client_id;
	protected $errors = [];
	protected $url_api = ' https://analyticsreporting.googleapis.com';
	protected $version = 'v4';
	
	public function __construct()
	{
		$yandex = get_option( 'usam_google' );		
		$this->token = isset($yandex['pagespeed'])?$yandex['pagespeed']:'';		
	}
	
	public function is_token( )
	{	
		return !empty($this->token)?true:false;
	}
	
	protected function set_error( $error )
	{	
		if ( is_string($error)  )		
			$this->errors[] = sprintf( __('Запрос на google вызвал ошибку. Текст ошибки: %s'), $error );	
		else		
			$this->errors[] = sprintf( __('Запрос на google вызвал ошибку №%s. Текст ошибки: %s'), $error['error_code'], $error['error_message']);
	}
	
	public function get_errors( )
	{	
		return $this->errors;
	}
	
	protected function set_log_file( )
	{	
		usam_log_file( $this->errors );
		$this->errors = array();
	}
	
	protected function get_url( $resource )
	{
		return "$this->url_api/$this->version/{$resource}";
	}
	
	protected function send_request( $resource, $params = array() )
	{	
		$params['key'] = $this->token;		
		$headers["Accept"] = 'application/json';
		$headers["Content-type"] = 'application/json';		
		
		$url_api = $this->get_url( $resource );			
		$data = wp_remote_get( $url_api, array('sslverify' => true, 'body' => $params, 'headers' => $headers ));	
		if ( is_wp_error($data) )
		{
			$this->set_error( $data->get_error_message() );	
			return false;
		}
		$resp = json_decode($data['body'], true);			
		if ( isset($resp['errors'] ) ) 
		{			
			if ( isset($resp['errors']['message']) )
				$this->set_error( $resp['errors']['message'] );	
			return false;
		}		
		return $resp;		
	}
	
	public function get_report_requests( $args )
	{				
		$result = $this->send_request( 'reports:batchGet', array( 'reportRequests' => $args ) );		
		return $result;
	}	
}
?>