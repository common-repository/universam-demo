<?php
// Класс API */
class USAM_Service_API 
{
	private $query = null;
	private $errors = array();
	private $api_url = 'https://wp-universam.ru/api';
	
	public function __construct()
	{		
		if ( !empty($_REQUEST['query']) && !empty($_REQUEST['software']) && $_REQUEST['software'] == 'universam'  )
		{			
			add_action( 'init', array($this, 'handler') );
		}		
	}		
	
	public function handler() 
	{			
		$this->query = sanitize_title($_REQUEST['query']);	
		$callback = "controller_{$this->query}";	
		if ( method_exists( $this, $callback )) 
		{
			$result = $this->$callback();
			
			echo json_encode( $result );		
			exit();
		}		
	}
	
	private function set_error( $error )
	{			
		$this->errors[] = sprintf( __('Universam API вызвало ошибку %s'), $error );
	}
	
	public function set_log_file()
	{
		usam_log_file( $this->errors );
		$this->errors = array();
	}	
	
	function send_request( $params )
	{ 				
		$license = get_option ( 'usam_license', array() );						
		$params['server_name']  = $_SERVER['SERVER_NAME'];
		$params['platform'] = 'wordpress';	
		if ( empty($params['license']) )
		{
			$params['software']     = 'universam';				
			$params['license']      = !empty($license['license'])?$license['license']:'';
			$params['version']      = USAM_VERSION;			
		}		
		$args = ['method' => 'POST', 'timeout' => 45, 'redirection' => 5, 'sslverify' => true, 'headers' => ['Authorization' => 'Bearer ' . $params['license']], 'body' => $params];					
		$response = wp_remote_post( $this->api_url, $args );
		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		if ( 200 !== $response_code && !empty( $response_message ) )
		{		
			$this->set_error( sprintf( __('Произошла API ошибка №%s. %s.', 'usam'),$response_code, $response_message) );	
			return false;
		}
		elseif ( 200 !== $response_code )
		{		
			$this->set_error( sprintf( __('Произошла API ошибка %s.', 'usam'),$response_code) );	
			return false;
		}	
		else 
		{ 
			$result = json_decode( wp_remote_retrieve_body( $response ), true );	
			if ( isset($result['error'] ) ) 
			{		
				$this->set_error( $result['error'] );	
				return false;
			}			
			return $result;
		}			
	}

	public function registration( $args = [] )
	{		
		$args['query'] = 'new_reg';
		$request = $this->send_request( $args );			
		if ( !empty($request['result']) ) 
			return $request;
		return false;
	}
		
	public function check_license(  )
	{		
		require_once( USAM_FILE_PATH . '/includes/licenses_query.class.php'  );
		$licenses = usam_get_licenses();
		foreach( $licenses as $key => $license )	
		{			
			$request = $this->send_request(['query' => 'check_license', 'license' => $license->license]);
			if ( isset($request['status']) )
			{
				$status = $_SERVER['SERVER_NAME'] == $request['domain']?$request['status']:0;	
				usam_update_license($license->id, ['license_end_date' => $request['license_end_date'], 'status' => $status]);				
			}
		}			
		$request = $this->send_request(['query' => 'check_license']);			
		$license = get_option('usam_license');
		if ( !empty($request['license']) && $request['license'] == $license['license'] ) 
		{	
			$license['status'] = $_SERVER['SERVER_NAME'] == $request['domain']?$request['status']:0;
			update_option ( 'usam_license', $license );	
		}					
		return $request;
	}
	
	public function get_license()
	{		
		$request = $this->send_request(['query' => 'license']);	
		$license = get_option('usam_license');
		if ( !empty($request['license']) && $request['license'] == $license['license'] )
		{	
			if ($_SERVER['SERVER_NAME'] != $request['domain'])
				$license['status'] = 0;
			else
				$license['status'] = $request['status'];

			if ( !empty($request['license_end_date']) ) 
				$license['license_end_date'] = $request['license_end_date'];
					
			$license['type'] = strtoupper($request['type']);	
			update_option ( 'usam_license', $license );	
		}	
		return $request;
	}
	
	public function universam_activation( )
	{				
		$request = $this->send_request(['query' => 'program_activation']);			
	}
	
	public function universam_deactivation( )
	{
		$params = array(						
			'query'    => 'program_deactivation',					
		);			
		$request = $this->send_request( $params );			
	}
		
	public function set_free_license( )
	{
		if( $this->set_license( 'free' ) === false ) 
		{
			$license = ['license' => '000000000', 'name' => home_url(), 'domain' => $_SERVER['SERVER_NAME'], 'type' => 'FREE', 'status' => 1, 'date_to' => date( "Y-m-d"), 'date_issue' => date( "Y-m-d") ];
			update_option ( 'usam_license', $license );
		}		
	}
	
	public function set_temporary_license( )
	{
		return $this->set_license( 'temporary' );
	}
	
	private function set_license( $type )
	{
		$params = ['query' => 'get_license', 'type' => $type, 'email' => get_option('admin_email')];
		$request = $this->send_request( $params );	
		
		$result = false;	
		$license = array( 'license' => '', 'name' => '', 'domain' => '', 'type' => '', 'status' => 0, 'date_to' => '', 'date_issue' => '' );
		if ( !empty($request['result']) ) 
		{		 
			$license = ['license' => strtoupper($request['license']), 'name' => $request['name'], 'domain' => $_SERVER['SERVER_NAME'], 'type' => strtoupper($request['type']), 'status' => $request['status'], 'date_to' => ''];
			if ( !empty($request['date_to']) )
				$license['date_to'] = date( "Y-m-d", strtotime($request['date_to']));
			
			if ( !empty($request['date_issue']) )
				$license['date_issue'] = date( "Y-m-d H:i:s", strtotime($request['date_issue']));
			
			$result = true;
		}			
		update_option ( 'usam_license', $license );
		return $result;
	}
		
	public function sent_support_message( $data )
	{
		global $wpdb;
		$php_version = phpversion();
		$locale      = get_locale();		
		
		$params = [						
			'query'             => 'message_to_developers',			
			'php'               => $php_version,
			'locale'            => $locale,
			'mysql'             => $wpdb->db_version(),					
			'message'           => $data['message'],
			'subject'           => $data['subject'],			
		];	
		$result = $this->send_request( $params );
		if ( empty($response['error']) )
			return true;
		return false;
	}
	
	public function controller_message_from_developers( )
	{
		$result = false;
		if ( !empty($_REQUEST['message']) )
		{
			$message = sanitize_text_field(stripcslashes($_REQUEST['message']));
			$insert = array( 'message' => $message, 'outbox' => 1  );			
			$result = usam_insert_support_message( $insert );
			
			usam_add_notification( array('title' => __('Получено новое сообщение от разработчика платформы Универсам', 'usam') ), array('object_type' => 'support', 'object_id' => $result) );
		}
		return $result;
	}
	
	public function collected_links( $link )
	{		
		$request = $this->send_request(['query' => 'collected_links', 'link' => $link]);		
		return $request;
	}
	
	public function confirm_deactivation( $reason, $message )
	{					
		$params = array(					
			'query'    => 'confirm_deactivation',	
			'email'    => get_option( 'admin_email' ),
			'reason'   => $reason,	
			'message'  => $message,	
		);			
		$request = $this->send_request( $params );		
		return $request;
	}
	
	public function get_themes(  )
	{		
		$request = $this->send_request(['query' => 'get_themes', 'orderby' => 'price', 'order' => 'ASC']);		
		return $request;
	}
	
	public function tracker( $data )
	{				
		$params = [					
			'query'    => 'tracker',	
			'data'     => $data,			
		];			
		$request = $this->send_request( $params );		
		return $request;
	}
}
$api = new USAM_Service_API();

function send_request_central_universam( $params )
{ 
	$url  = get_option("usam_central_platform_site","");
	if ( empty($url) )
		return false;
	
	$params['server_name']  = $_SERVER['SERVER_NAME'];	
	$params['version']      = USAM_VERSION;						
	$response = wp_remote_post( $url, ['method' => 'POST', 'timeout' => 45, 'redirection' => 5, 'sslverify' => true, 'headers' => [], 'body' => $params] );	

	$response_code    = wp_remote_retrieve_response_code( $response );
	$response_message = wp_remote_retrieve_response_message( $response );
	if ( 200 !== $response_code && !empty( $response_message ) )
	{		
		$error = sprintf( __('Произошла ошибка при отправке запроса к центральной базе. №%s. %s.', 'usam'),$response_code, $response_message);	
		usam_log_file( $error );
		return false;
	}
	elseif ( 200 !== $response_code )
	{		
		$error = sprintf( __('Произошла ошибка при отправке запроса к центральной базе %s.', 'usam'),$response_code);	
		usam_log_file( $error );
		return false;
	}	
	else 
	{ 
		$out = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset($out['error'] ) ) 
		{		
			usam_log_file( $out['error'] );
			return false;
		}
		elseif ( isset($out['obj']) ) 
			return $out['obj'];
		else
			return $out;
	}			
}