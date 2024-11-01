<?php
require_once( USAM_FILE_PATH .'/includes/feedback/social_network_handler.class.php' );
class USAM_VKontakte_Auth extends USAM_Social_Network_Handler
{		
	protected $profile = [];
	protected $user_meta_key = 'vk_id';
	protected $social = 'vk';
		
	protected function get_user( $id )
	{		
		require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
		$vkontakte = new USAM_VKontakte_API( $this->profile );
		return $vkontakte->get_user( $id );	
	}
	
	protected function update_contact( $id, $contact_id )
	{
		$contact = usam_get_contact( $contact_id );
		$user = $this->get_user($id);
		if ( $user )
		{
			$data = ['online' => date("Y-m-d H:i:s"), 'lastname' => trim(stripcslashes($user['last_name'])), 'firstname' => trim(stripcslashes($user['first_name']))];
			if( !empty($user['birthday']) )
				$data['birthday'] = $user['birthday'];		
			if ( !empty($user['location']) )
				$data['location'] = $user['location'];		
			if ( !empty($user['mobilephone']) )
				$data['mobilephone'] = $user['mobilephone'];	
			if ( !empty($user['phone']) )
				$data['phone'] = $user['phone'];			
			if ( !empty($user['vk']) )
				$data['vk'] = "https://vk.com/".$user['vk'];		
			if ( !empty($user['sex']) )
				$data['sex'] = $user['sex']==1?'f':'m';			
			return usam_combine_contact( $contact_id, $data );		
		}
		return false;
	}
	
	protected function insert_contact( $id )
	{			
		$user = $this->get_user($id);
		$data = ['online' => date("Y-m-d H:i:s"), 'contact_source' => $this->social, 'lastname' => trim(stripcslashes($user['last_name'])), 'firstname' => trim(stripcslashes($user['first_name']))];
		if( !empty($user['foto']) )
		{
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$foto_id = media_sideload_image( $user['foto'], 0, '', 'id' );
			if( !is_wp_error($foto_id) )
				$data['foto'] = $foto_id;
		}	
		if( !empty($user['birthday']) )
			$data['birthday'] = $user['birthday'];			
		if ( !empty($user['location']) )
			$data['location'] = $user['location'];		
		if ( !empty($user['mobilephone']) )
			$data['mobilephone'] = $user['mobilephone'];	
		if ( !empty($user['phone']) )
			$data['phone'] = $user['phone'];			
		if ( !empty($user['domain']) )
			$data['domain'] = "https://vk.com/".$user['domain'];	
		if ( !empty($user['sex']) )
			$data['sex'] = $user['sex']==1?'f':'m';
		$data['vk_id'] = $id;
		$contact_id = usam_insert_contact( $data );				
		return $contact_id;
	}	
	
	public function auth()
	{ 			
		$contact = [];	
		$resp['user_id'] = 0;
		if ( isset($_GET['code']) ) 
		{				
			$code = sanitize_text_field($_REQUEST['code']);	
			$resp = $this->get_access_token( $code );				
			if ( !empty($resp['access_token']) && !empty($resp['user_id']) )
			{
				$contact = $this->get_contact( $resp['user_id'] );
				if ( empty($contact) )
				{
					$this->profile = ['type_social' => 'vk_user', 'access_token' => $resp['access_token'], 'code' => ''];
					$contact_id = $this->insert_contact( $resp['user_id'] );
				}
				else
					$this->update_contact( $resp['user_id'], $contact['id'] );
				if ( !empty($contact_id) )
					$contact = usam_get_contact( $contact_id );
			}
			else
				$resp['user_id'] = 0;
			do_action( 'usam_user_auth', $contact, $resp['user_id'], $this->user_meta_key );
		}
		return ['contact' => $contact, 'social_user_id' => $resp['user_id'], 'social_key' => $this->user_meta_key];
	}		
	
	protected function get_option()
	{ 
		return get_option('usam_vk_api');
	}
	
	protected function get_auth_url($params)
	{ 
		$query = http_build_query($params);
		return 'https://oauth.vk.com/access_token?'.$query;
	}
}
?>