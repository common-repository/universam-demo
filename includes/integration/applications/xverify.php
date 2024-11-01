<?php
/*
	Name: xverify.com
	Description: Валидация электронных адресов необходимая при массовых рассылках
	Price: free
	Group: email-verification
*/
require_once( USAM_FILE_PATH . '/includes/email_verification.class.php' );	
class USAM_Application_xverify extends USAM_Email_Verification
{	
	protected $API_URL = "http://www.xverify.com/services";
	public function email_verification( $email )
	{ 	
		$domain = parse_url(get_bloginfo('url'), PHP_URL_HOST);				
		$params = array( 'email' => $email, 'apikey' => $this->token, 'type' => 'json', 'domain' => $domain );		
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
		$result = $this->send_request( "emails/verify", $args );
		if ( !empty($result['status']) )
		{ 
			if ( $result['status'] != 'valid”' )
				usam_handle_communication_error( $email, 'email', 'invalid_email' );
			else
				return true;
		}
		return false;
	}	
	
	protected function send_emails_verification( $file_path )
	{ 				
		
		return false;
	}	
	
	public function notifications( )
	{ 
		if ( !empty($result['status']) && $result['status'] == 'completed' && $result['id'] == $this->get_job() && !empty($_POST['download_urls']) )
		{
		
	
		}
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
		</div>
		<?php
	}
}
?>