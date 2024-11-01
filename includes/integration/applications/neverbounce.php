<?php
/*
	Name: neverbounce.com
	Description: Валидация электронных адресов необходимая при массовых рассылках
	Price: free
	Group: email-verification
*/
require_once( USAM_FILE_PATH . '/includes/email_verification.class.php' );	
class USAM_Application_neverbounce extends USAM_Email_Verification
{	
	protected $version = 4;
	protected $test = true;	
	protected $API_URL = "https://api.neverbounce.com";
	public function email_verification( $email )
	{ 
		$params = ['email' => $email, 'key' => $this->token];					
		$args = array(
			'method' => 'GET',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'body' => $params,
		);	
		$result = $this->send_request( 'single/check', $args );
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
		$file_id = usam_add_file_from_files_library( $file_path, array( 'status' => 'open', 'title' => __("Проверка электронной почты","usam") ) );
		$params = array( 'input' =>  home_url('file/'.$file_id), 'key' => $this->token, 'filename' => 'email_list.csv', 'auto_parse' => 0, 'auto_start' => 0, 'input_location' => 'remote_url' );				
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'body' => $params,
		);			
		$result = $this->send_request( 'jobs/create', $args );			
		if ( !empty($result['status']) && $result["status"] == "success" )
		{ 	
			return $result['job_id'];
		}
		return false;
	}	
	
	public function notifications( )
	{ 
		
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