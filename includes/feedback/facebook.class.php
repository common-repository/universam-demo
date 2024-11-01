<?php
require_once( USAM_FILE_PATH .'/includes/feedback/social_network_handler.class.php' );
class USAM_Facebook extends USAM_Social_Network_Handler
{
	private $facebook;
	protected $user_meta_key = 'facebook_user_id';	
	private function insert_contact( $user )
	{					
		$data = ['firstname' => trim(stripcslashes($user['first_name'])), 'online' => date("Y-m-d H:i:s"), 'contact_source' => 'facebook'];
		$data['lastname'] = !empty($user['last_name'])?trim(stripcslashes($user['last_name'])):"";	
		$contact_id = usam_insert_contact( $data );	
		$user_id = absint($user['id']);
		usam_update_contact_metadata( $contact_id, $this->user_meta_key, $user_id );
		return $contact_id;
	}
		
	public function notifications(  )
    {		
		if (!empty($_REQUEST['hub_mode']) && $_REQUEST['hub_mode'] == 'subscribe' )
		{
			$option = get_option('usam_fb_api');
			$verify_token = !empty($option['verify_token'])?$option['verify_token']:'';
			if ( $_REQUEST['hub_verify_token'] == $verify_token )	
				echo $_REQUEST['hub_challenge'];
		} 
		else
		{
			$request = json_decode(file_get_contents('php://input'), true);					
			switch ((string)$request['field']) 
			{
				case 'message_sends':
					 	
				break;
			}
		}
	}
}
?>