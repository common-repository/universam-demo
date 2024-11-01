<?php
require_once( USAM_FILE_PATH .'/includes/feedback/social_network_handler.class.php' );
class USAM_Facebook_Auth extends USAM_Social_Network_Handler
{		
	protected $social = 'facebook';	
	protected $user_meta_key = 'facebook_user_id';	
	protected $social_user_id = 0;		
		
	protected function get_user( $data )
	{		
		$params = ['access_token' => $data['access_token'], 'fields' => 'id,email,first_name,last_name,picture']; 
		$info = file_get_contents('https://graph.facebook.com/me?' . urldecode(http_build_query($params)));
		$info = json_decode($info, true);
		return $info;	
	}
			
	protected function insert_contact( $data )
	{			
		$user = $this->get_user( $data );				
		if ( !empty($user['id']) )
		{
			$this->social_user_id = $user['id'];			
			$contact = $this->get_contact( $this->social_user_id );		
			if ( empty($contact) )
			{
				$new_contact = ['online' => date("Y-m-d H:i:s"), 'contact_source' => $this->social, 'lastname' => $user['last_name'], 'firstname' => $user['first_name']];	
				if( !empty($user['picture']) && !empty($user['picture']['data']) )
				{
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';
					$foto_id = media_sideload_image( $user['picture']['data']['url'], 0, '', 'id' );
					if( !is_wp_error($foto_id) )
						$new_contact['foto'] = $foto_id;
				}					
				if ( !empty($user['email']) )
					$new_contact['email'] = $user['email'];		
				$new_contact[$this->user_meta_key] = $user['id'];
				$contact_id = usam_insert_contact( $new_contact );	
			}
		}
		else
			$contact_id = 0;
		return $contact_id;
	}	
	
	protected function get_option()
	{ 
		return  get_option('usam_fb_api');
	}
	
	protected function get_auth_url($params)
	{ 
		$query = http_build_query($params);
		return 'https://www.facebook.com/dialog/oauth?'.$query;
	}	
}
?>