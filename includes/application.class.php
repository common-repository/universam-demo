<?php
// Общий класс для приложений
abstract class USAM_Application
{
	protected $token = null;
	protected $login = null;
	protected $password = null;
	protected $option = [];
	protected $individual_settings = [];
	protected $id = null;
	protected $API_URL = "";
	protected $version = "";
	protected $test = false;
	protected $namespace = 'usam/v1';
	protected $route = '';
	protected $errors = [];	
	protected $expiration = 7200;
		
	function __construct( $option ) 
	{			
		if( $option === 0 )
			$this->option = $this->get_default_option();
		else
		{			
			$default = $this->get_default_option();	
			if( is_numeric($option) )
				$this->option = usam_get_application( $option );
			else
				$this->option = $option;				
			$metas = usam_get_application_metadata( $this->option['id'] );
			if ( is_array($metas) )
			{
				foreach($metas as $metadata )
				{
					if ( isset($default[$metadata->meta_key]) && is_array($default[$metadata->meta_key]) )
						$this->option[$metadata->meta_key][] = maybe_unserialize( $metadata->meta_value );
					else
						$this->option[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );				
				}
			}			
			$this->option = array_merge($default, $this->option);
		}		
		$this->option = array_merge(['service_code' => '', 'group_code' => '', 'access_token' => '', 'login' => '', 'password' => '', 'id' => 0], $this->option);		
		$this->token = $this->option['access_token'];	
		$this->login = $this->option['login'];	
		$this->password = $this->option['password'];	
		$this->id = $this->option['id'];		
		$this->route = '/'.$this->option['service_code'].'/(?P<serviceid>\d+)';
	}	
	
	protected function get_default_option( ) 
	{
		return [];
	}
	
	public function get_data( )
	{ 
		return $this->option;
	}		
		
	public function notifications( ) { }	
	
	protected function get_url( $function )
	{
		if ( $this->version )
			$url = $this->API_URL."/v{$this->version}/$function";
		else
			$url = $this->API_URL."/$function";
		return $url;
	}
	
	public function is_token( )
	{	
		return $this->token?true:false;
	}
		
	protected function send_request( $function, $params )
	{				
		if ( !$this->is_token() )
			return false;		
		$url = $this->get_url( $function );			
		$data = wp_remote_post( $url, $params );	
		if( is_wp_error($data) )
		{
			$message = $data->get_error_message();
			$this->set_error( $message );	
			return false;
		} 
		if ( !empty($data['response']) && $data['response']['code'] == 403 )
		{
			if( is_string($data['body']) )
				$this->set_error( $this->option['service_code'].' '.$data['body'] );	
			else
				$this->set_error( sprintf('Доступ закрыт для %s', $url) );	
			return false;
		}
		$resp = json_decode($data['body'],true); 	
		if ( $this->log_errors( $resp, $function ) ) 
			return false;
		else
			return $resp;		
		return $resp;		
	}
	
	protected function get_token_args( )
	{ 
		return ['method' => 'POST'];
	}
	
	protected function get_token_url( )
	{ 
		return '';
	}
	
	protected function processing_received_token( $result )
	{ 
		if ( isset($result['access_token']) )
		{ 
			$expiration = !empty($results['expires_in'])?$results['expires_in']:$this->expiration;
			set_transient( 'application_access_token_'.$this->option['id'], $result['access_token'], $expiration );
			return $result['access_token'];
		}
		return false;
	}
		
	protected function get_token( )
	{ 					
		$access_token = get_transient( 'application_access_token_'.$this->option['id'] );			
		if ( !empty($access_token) )
			return $access_token;	
						
		$args = $this->get_token_args();
		$args['user-agent'] = 'UNIVERSAM';	
		$url = $this->get_token_url();
		$data = wp_remote_post( $url, $args ); 		
		if ( is_wp_error($data) )
		{
			$message = $data->get_error_message();
			$this->set_error( $message );	
			return false;
		} 
		$result = json_decode($data['body'], true);
		return $this->processing_received_token( $result );
	}
	
	function download_url( $url, $headers = [] ) 
	{	
		if ( !$url )
			return new WP_Error( 'http_no_url', __( 'Invalid URL Provided.' ) );
		
		$url_filename = basename( parse_url( $url, PHP_URL_PATH ) );
		$tmpfname = wp_tempnam( $url_filename );
		if ( ! $tmpfname ) 
			return new WP_Error( 'http_no_file', __( 'Could not create temporary file.' ) );
		
		$fp = fopen($tmpfname, 'w');		
		$ch = curl_init();	
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, 0);			
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0' ); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($ch, CURLOPT_FILE, $fp); 
		$data = curl_exec($ch);	
		fclose($fp);
		if ( curl_errno($ch) )
		{
			$error = sprintf('cURL error %s: %s', curl_errno($ch), curl_error($ch));
			unlink( $tmpfname );
			curl_close($ch);
			return new WP_Error( 'cURL_error', $error );
		}	
		curl_close($ch);
		return $tmpfname;
	}
	
	protected function log_errors( $resp, $function )
	{
		if ( isset($resp['error']) ) 
		{		
			if ( isset($resp['error'][0]) )
				$this->set_error( $resp['error'][0], $function );	
			else
				$this->set_error( $resp['error'], $function );	
			return true;
		}	
		return false;
	}
	
	protected function set_error( $error, $function = '' )
	{	
		if( is_wp_error($error) ) 
			$error = $error->get_error_message();	
		if ( $function )
			$this->errors[] = sprintf( __('Приложение вызвало ошибку в запросе %s. Текст ошибки: %s'), $function, $error );
		else
			$this->errors[] = sprintf( __('Приложение вызвало ошибку. Текст ошибки: %s'), $error );
		usam_log_file( __('Приложение','usam').' '.$this->option['service_code'].' '.$error );
	}
	
	protected function remove_hook( $schedule ) 
	{	
		$hook = 'usam_application_'.$schedule.'_schedule_'.$this->option['service_code'];
		wp_clear_scheduled_hook($hook, [$this->id]);
	}
	
	protected function add_hook( $schedule ) 
	{		
		$hook = 'usam_application_'.$schedule.'_schedule_'.$this->option['service_code'];
		$schedule = usam_get_application_metadata( $this->id, $schedule.'_schedule' );			
		if ( $schedule )
		{	
			$time = usam_get_application_metadata( $this->id, $schedule.'_schedule_time' );
			$time = $time?$time:'00:00';
			$ve = get_option('gmt_offset') < 0 ? '+' : '-';
			wp_schedule_event( strtotime($time.' ' . $ve . get_option('gmt_offset') . ' HOURS'), $schedule, $hook, [$this->id] );
		}
	}
	
	protected function add_hook_ten_minutes( $schedule ) 
	{
		$hook = 'usam_application_'.$schedule.'_schedule_'.$this->option['service_code'];
		$ve = get_option('gmt_offset') < 0 ? '+' : '-';		
		wp_schedule_event( strtotime('08:00 tomorrow ' . $ve . get_option('gmt_offset') . ' HOURS'), 'ten_minutes', $hook, [$this->id] );
	}	
	
	public function get_errors()
	{
		return $this->errors;
	}
	
	//регистрация вебхуков
	public function webhook_registration( ) { }	
	//Форма для индивидуальных настроек, выводится в админке
	public function display_form( ){ }	
	public function get_form_buttons( ){ }		
	public function display_form_left( ){ }	
	public function save_form( ) {	}
	public function service_load( ) { }
	public function rest_api( $request ) { }	
}
?>