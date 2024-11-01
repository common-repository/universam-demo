<?php
require_once( USAM_FILE_PATH .'/includes/feedback/social_network_handler.class.php' );
class USAM_Google_Auth extends USAM_Social_Network_Handler
{		
	protected $user_meta_key = 'google_user_id';	
	protected $social = 'google';	
	protected $social_user_id = 0;		
	
	protected function get_user( $data )
	{		
		$params = array(
			'access_token' => $data['access_token'],
			'id_token'     => $data['id_token'],
			'token_type'   => 'Bearer',
			'expires_in'   => 3599
		); 
		$info = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?' . urldecode(http_build_query($params)));
		$info = json_decode($info, true);
		return $info;	
	}
			
	protected function insert_contact( $args )
	{			
		$user = $this->get_user( $args );				
		if ( !empty($user['id']) )
		{
			$this->social_user_id = $user['id'];			
			$contact = $this->get_contact( $this->social_user_id );	
			if ( empty($contact) )
			{				
				if ( !empty($user['last_name']) )
					$data = ['lastname' => $user['last_name'], 'firstname' => $user['first_name']];
				else
					$data = ['lastname' => $user['family_name'], 'firstname' => $user['given_name']];
				$data['online'] = date("Y-m-d H:i:s");
				$data['contact_source'] = $this->social;	
				if( !empty($user['picture']) )
				{
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';
					$foto_id = media_sideload_image( $user['picture'], 0, '', 'id' );
					if( !is_wp_error($foto_id) )
						$data['foto'] = $foto_id;
				}					
				if ( !empty($user['email']) )
					$data['email'] = $user['email'];		
				$data[$this->user_meta_key] = $user['id'];
				$contact_id = usam_insert_contact( $data );	
			}
		}
		else
			$contact_id = 0;
		return $contact_id;
	}	
	
	protected function get_option()
	{ 
		return  get_option('usam_google');
	}
	
	protected function get_auth_url($params)
	{ 
		$params['grant_type'] = 'authorization_code';
		$query = http_build_query($params);
		return 'https://accounts.google.com/o/oauth2/token?'.$query;
	}
}
?>