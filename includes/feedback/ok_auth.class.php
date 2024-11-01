<?php
require_once( USAM_FILE_PATH .'/includes/feedback/social_network_handler.class.php' );
class USAM_OK_Auth extends USAM_Social_Network_Handler
{		
	private $profile = array();
	protected $user_meta_key = 'ok_id';
	protected $social = 'ok';
		
	protected function get_user( $data )
	{		
		$sign = md5("application_key={$api['application_key']}format=jsonmethod=users.getCurrentUser" . md5("{$data['access_token']}{$api['client_secret']}"));
		$api = get_option('usam_odnoklassniki');
		$params = array(
			'method'          => 'users.getCurrentUser',
			'access_token'    => $data['access_token'],
			'application_key' => $api['application_key'],
			'format'          => 'json',
			'sig'             => $sign
		); 
		$info = file_get_contents('http://api.odnoklassniki.ru/fb.do?' . urldecode(http_build_query($params)));
		$info = json_decode($info, true);
		return $info;	
	}
			
	protected function insert_contact( $args )
	{			
		$user = $this->get_user( $args );				
		if ( !empty($user['uid']) )
		{
			$this->social_user_id = $user['uid'];			
			$contact = $this->get_contact( $this->social_user_id );	
			if ( empty($contact) )
			{							
				if ( !empty($user['last_name']) )
					$data = ['lastname' => $user['last_name'], 'firstname' => $user['first_name']];
				else
					$data = ['lastname' => $user['family_name'], 'firstname' => $user['given_name']];	
				$data['online'] = date("Y-m-d H:i:s");
				$data['contact_source'] = $this->social;	
				if( !empty($user['pic_1']) )
				{
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';
					$foto_id = media_sideload_image( $user['pic_1'], 0, '', 'id' );
					if( !is_wp_error($foto_id) )
						$data['foto'] = $foto_id;
				}	
				if ( !empty($user['birthday']) )
					$data['birthday'] = date( "Y-m-d H:i:s",strtotime($user['birthday']));
				if ( !empty($user['gender']) )
					$data['sex'] = $user['gender']=='male'?'f':'m';
				if ( !empty($user['location']) )
				{
					$location = usam_get_locations(['search' => $user['location']['city'], 'fields' => 'id', 'number' => 1]);
					if ( $location )
						$data['location'] = $location;		
				}
				$data[$this->user_meta_key] = $user['uid'];
				$contact_id = usam_insert_contact( $data );	
			}
		}
		else
			$contact_id = 0;
		return $contact_id;
	}	
	
	protected function get_option()
	{ 
		return get_option('usam_odnoklassniki');
	}
	
	protected function get_auth_url($params)
	{ 
		$params['grant_type'] = 'authorization_code';
		$query = http_build_query($params);
		return 'http://api.odnoklassniki.ru/oauth/token.do?'.$query;
	}
}
?>