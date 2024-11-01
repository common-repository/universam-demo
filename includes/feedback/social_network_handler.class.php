<?php
abstract class USAM_Social_Network_Handler
{
	protected $errors = [];
	protected $error;
	protected $user_meta_key = '';		
	protected $social = '';	
	protected $profile = [];	
	
	protected function get_contact( $user_id )
	{		
		if ( $user_id )
		{		
			$contacts = usam_get_contacts(['meta_key' => $this->user_meta_key, 'meta_value' => $user_id, 'cache_results' => true, 'limit' => 1, 'source' => 'all', 'status' => 'all']);		
			if ( !empty($contacts) )
				return (array)$contacts[0];
		}
		return [];
	}	
	
	protected function update_user_id( $contact_id, $sender_id )
	{		
		$contact_id = preg_replace("/[^0-9]/", '', $contact_id);			
		if ( $contact_id )
		{
			$user_id = usam_get_contact_metadata( $contact_id, $this->user_meta_key );
			if ( !$user_id )
				usam_update_contact_metadata( $contact_id, $this->user_meta_key, $sender_id );	
			$message = __("ID профиля добавлено контакту", "usam");
		}
		else
		{
			$message = __("ID профиля НЕ ДОБАВЛЕНО", "usam");
		}		
		return ['contact_id' => $contact_id, 'message' => $message];
	}
	
	protected static function command_handler( $contact, $data )
	{		
		$results = false;
		if ( stripos($data, '_usam-delivery') !== false )			
		{			
			if ( $contact['user_id'] )
			{
				$user = get_userdata( $contact['user_id'] );
				if( !empty($user->ID) )
				{
					if ( usam_check_current_user_role( 'courier', $user->ID ) )
					{
						require_once( USAM_FILE_PATH .'/includes/personnel/courier_command_handler.class.php' );
						$results = USAM_Courier_Command_Handler::start( $contact['id'], $data );
					}				
				}
			}
			
		}
		else
		{
			require_once( USAM_FILE_PATH . '/includes/personnel/command_handler.class.php');
			$results = USAM_Command_Handler::start( $contact['id'], $data );
		}		
		return $results;
	}
	
	protected function set_error( $error )
	{	
		$this->error = $error;
		if ( is_string($error) )
			$this->errors[]  =  sprintf( __('%s: Ошибка: %s'), $this->social, $error);
		else
			$this->errors[] = sprintf( __('%s: Приложение %s вызвало ошибку №%s. Текст ошибки: %s'), $this->social, $error['request_params'][0]['value'], $error['error_code'], $error['error_msg']);
	}
	
	public function set_log_file()
	{
		usam_log_file( $this->errors );
		$this->errors = array();
	}
	
	function auth()
	{ 					
		$contact = array();
		if ( isset($_GET['code']) ) 
		{				
			$code = sanitize_text_field($_REQUEST['code']);	
			$resp = $this->get_access_token( $code );				
			if ( !empty($resp['access_token']) )
			{
				$contact_id = $this->insert_contact( $resp );
				if ( !empty($contact_id) )
					$contact = usam_get_contact( $contact_id );
			}	
			do_action( 'usam_user_auth', $contact, $this->social_user_id, $this->user_meta_key );				
		}	
		return ['contact' => $contact, 'social_user_id' => $this->social_user_id, 'social_key' => $this->user_meta_key];		
	}	
	
	protected function get_option()
	{ 
		return [];
	}
	
	protected function get_auth_url($params)
	{ 
		return '';
	}
	
	function get_access_token( $code )
	{		
		$redirect_uri = apply_filters( 'usam_social_auth_url', home_url("api/{$this->social}-auth"), $this->social );
		$api = $this->get_option();		
		if ( empty($api['client_id']) || empty($api['client_secret']) )
		{
			$this->set_error( __('Не настроено API', 'usam') );	
			return false;
		}
		$url = $this->get_auth_url(['client_id' => $api['client_id'], 'client_secret' => $api['client_secret'], 'redirect_uri' => $redirect_uri, 'code' => $code]);	
		$data = wp_remote_post($url, ['sslverify' => true]);		
		if ( is_wp_error($data) )
		{
			$this->set_error( $data->get_error_message() );	
			return false;
		}
		$resp = json_decode($data['body'],true);
		if ( isset($resp['error']) )
		{			
			$this->set_error( $resp['error'] );	
			return false;
		}		
		return $resp;		
	}	
	
	protected function get_dialog( $contact_id )
	{		
		require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialogs_query.class.php');
		$dialog_id = usam_get_chat_dialogs(['fields' => 'id', 'number' => 1, 'contact_id' => $contact_id, 'orderby' => 'id', 'channel' => $this->profile['type_social'], 'channel_id' => $this->profile['id'], 'order' => 'DESC']);
		
		if ( empty($dialog_id) )			
			$dialog_id = usam_insert_chat_dialog(['channel' => $this->profile['type_social'], 'contact_id' => $contact_id, 'channel_id' => $this->profile['id']], [ $contact_id ]);
		return $dialog_id;
	}
	
	protected function insert_chat_message( $contact_id, $message, $guid )
	{		
		$dialog_id = $this->get_dialog( $contact_id );
		if ( '/start' != $message )
			usam_insert_chat_message(['contact_id' => $contact_id, 'dialog_id' => $dialog_id, 'message' => $message, 'status' => 1, 'guid' => absint($guid)]);
	}	
}
?>