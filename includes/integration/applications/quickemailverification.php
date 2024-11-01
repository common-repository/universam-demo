<?php
/*
	Name: quickemailverification.ru
	Description: Валидация электронных адресов необходимая при массовых рассылках
	Price: free
	Group: email-verification	
*/
require_once( USAM_FILE_PATH . '/includes/email_verification.class.php' );	
class USAM_Application_quickemailverification extends USAM_Email_Verification
{	
	protected $version = 1;
	protected $test = false;	
	protected $API_URL = "http://api.quickemailverification.com";
	public function email_verification( $email )
	{ 
		$params = array( 'email' => $email, 'apikey' => $this->token );
		
		$headers["Content-type"] = 'application/json';				
		$args = array(
			'method' => 'GET',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => $params,
		);		
		if ( $this->test )
			$function = "verify/sandbox";
		else
			$function = "verify";
		$result = $this->send_request( $function, $args );			
		if ( !empty($result['result']) )
		{ 
			if ( $result['result'] != 'valid' )
				usam_handle_communication_error( $email, 'email', $result['reason'] );
			else
				return true;
		}
		return false;
	}	
	
	protected function send_emails_verification( $file_path )
	{ 				
		if ( version_compare( PHP_VERSION, '5.5', '>=' ) )
			$params['upload'] = new CURLFile( $file_path );
		else 
			$params['upload'] = '@' . $file_path;	

		$headers = array();
		$headers[] = "Authorization:token ".$this->token; 
		$headers[] = "X-QEV-Filename:email_list.csv";
		$headers[] = 'X-QEV-Callback:'.get_bloginfo('url').'/api/quickemailverification';	 		

		$url = $this->get_url( 'bulk-verify' );
		
		$ch = curl_init( $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
		$result = curl_exec($ch);
		$result = json_decode($result, true);
		curl_close($ch);		 				
		if ( !empty($result['success']) && $result['success'] && !empty($result['id']) )
		{ 
			return $result['id'];
		}
		return false;
	}	
	
	public function register_routes( ) 
	{ 
		register_rest_route( $this->namespace, $this->route.'/(?P<method>.+)', array(		
			array(
				'permission_callback' => false,
				'methods'  => 'GET,POST',
				'callback' => 'usam_application_rest_api',	
				'args' => array()
			),			
		));	
	}
	
	public function rest_api( $request ) 
	{		
		if ( !empty($request['status']) && $request['status'] == 'completed' && $request['id'] == $this->get_job() && !empty($request['download_urls']) )
		{
	
	
		}
	/*		 
		
		"id": "7138f61de8da380c345374c08bd64599",
    "filename": "email_list.csv",
    "status": "completed",
    "created_date": "2017-08-18T04:06:08.000Z",
    "started_date": "2017-08-18T04:06:08.000Z",
    "completed_date": "2017-08-18T04:06:45.000Z",
    "processing_time": 37,
    "stats": {
        "total_email": 100,
        "progress": "100%",
        "result_counts": {
            "safetosend": 68,
            "valid": 82,
            "invalid": 16,
            "unknown": 2
        }
    },
    "download_urls": {
        "safetosend": "https://api.quickemailverification.com/v1/bulk-verify/download/safetosend/7138f61de8da380c345374c08bd64599?apikey=94msdf823ns8234fdm23004m380234s242i9smkm234s0934m23402342901",
        "valid": "https://api.quickemailverification.com/v1/bulk-verify/download/valid/7138f61de8da380c345374c08bd64599?apikey=94msdf823ns8234fdm23004m380234s242i9smkm234s0934m23402342901",
        "invalid": "https://api.quickemailverification.com/v1/bulk-verify/download/invalid/7138f61de8da380c345374c08bd64599?apikey=94msdf823ns8234fdm23004m380234s242i9smkm234s0934m23402342901",
        "unknown": "https://api.quickemailverification.com/v1/bulk-verify/download/unknown/7138f61de8da380c345374c08bd64599?apikey=94msdf823ns8234fdm23004m380234s242i9smkm234s0934m23402342901",
        "fullreport": "https://api.quickemailverification.com/v1/bulk-verify/download/fullreport/7138f61de8da380c345374c08bd64599?apikey=94msdf823ns8234fdm23004m380234s242i9smkm234s0934m23402342901"
    },
    "success": true,
    "message": ""
	*/
		return new WP_REST_Response( true, 200 );	
	}	
	
	function display_form( ) 
	{
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='messenger_secret_key'><?php esc_html_e( 'Токен', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['access_token'], array('name' => 'access_token', 'id' => 'messenger_secret_key') ); ?>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Ссылка для загрузки результата', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option"><span class="js-copy-clipboard"><?php echo get_rest_url(null,$this->namespace.'/'.$this->option['service_code'].'/'.$this->id); ?></span></div>
			</div>	
		</div>
		<?php
	}
}
?>