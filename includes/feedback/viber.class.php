<?php
require_once( USAM_FILE_PATH .'/includes/feedback/social_network_handler.class.php' );
class USAM_Viber extends USAM_Social_Network_Handler
{		
	private $viber;
	protected $user_meta_key = 'viber_user_id';	
	private function insert_contact( $user )
	{	
		$data = ['name' => $user['name'], 'online' => date("Y-m-d H:i:s")];
		if( !empty($user['avatar']) )
		{			
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$foto_id = media_sideload_image( $user['avatar'], 0, '', 'id' );
			if ( is_numeric($foto_id) )
				$data['foto'] = $foto_id;
		}		
// [country] => RU		
		$data[$this->user_meta_key] = sanitize_text_field($user['id']);
		$contact_id = usam_insert_contact( $data );
		return $contact_id;
	}	
			
	private function set_system_message( $message, $sender_id )
	{			
		$result = false;
		$message = trim($message);
		if (stripos($message, 'user_id') !== false)	
			$args = $this->update_user_id( $message, $sender_id );
		else	
		{			
			$contact = $this->get_contact( $sender_id );
			if ( !empty($contact) )
			{	
				require_once( USAM_FILE_PATH . '/includes/personnel/command_handler.class.php');
				$args = self::command_handler( $contact, $message );	
				if ( !empty($args['buttons']) )
				{					
				//	$args['reply_markup'] = array( 'inline_keyboard' => $args['buttons'] );
				//	unset($args['buttons']);
				}
				elseif ( !empty($args['buttons2']) )
				{
				//	$args['reply_markup'] = array( 'keyboard' => array( $args['buttons2'] ), 'one_time_keyboard' => true, 'resize_keyboard' => true );
			//		unset($args['buttons2']);
				}			
				if ( !empty($args) )
					$args['contact_id'] = $contact['id'];	
			}
		}		
		if ( !empty($args) ) 
		{				
			$this->viber->send_message( $args );
			$result = true;	
		}
		return $result;
	}
	
	public function notifications( )
    {		
		$json = file_get_contents("php://input");
		$request = json_decode($json, true);
						
		if ( empty($_GET['sig']) )
			return false;		
		
		$social_networks = usam_get_social_network_profiles(['type_social' => 'viber']);
				
		foreach ( $social_networks as $social_network ) 
		{
			$hash = hash_hmac('sha256',$json, $social_network->access_token );		
			if (  $_GET['sig'] == $hash )
			{
				$this->profile = (array)$social_network;
				break;
			}
		}
		if ( empty($this->profile) )
			return false;
		
		require_once( USAM_APPLICATION_PATH . '/social-networks/viber_api.class.php' );	
		require_once(USAM_FILE_PATH.'/includes/feedback/chat_messages_query.class.php');
		$this->viber = new USAM_Viber_API( $this->profile );
			
		switch ( $request['event'] ) 
		{
			case 'webhook' :					
				$webhook_response['status'] = 0;
				$webhook_response['status_message'] = "ok";
				$webhook_response['event_types'] = 'delivered';
				echo json_encode($webhook_response);
				die;
			break;			
			case 'delivered' :	//когда сообщение было доставлено на устройство пользователя
				$guid = absint($request['message_token']);					
				$chat_messages = usam_get_chat_messages(['guid' => $guid, 'channel' => 'viber', 'number' => 1]);
				if ( !empty($chat_messages) )
					usam_update_chat_message( $chat_messages->id, ['status' => 1]);	
			break;
			case 'seen' :	//был открыт диалог, содержащий сообщение
				$guid = absint($request['message_token']);				
				$chat_messages = usam_get_chat_messages(['guid' => $guid, 'channel' => 'viber', 'number' => 1]);
				if ( !empty($chat_messages) )
					usam_update_chat_message( $chat_messages->id, ['status' => 2]);			
			break;
			case 'subscribed' :			// Подписались		
				$sender_id = $request['sender']['id'];			
			
			break;
			case 'message' :		// Пришло сообщение	
				$sender_id = $request['sender']['id'];				
				$message = trim(stripcslashes($request['message']['text']));
				if ( !$this->set_system_message($message, $sender_id) )			
				{				
					$contact = $this->get_contact( $sender_id );				
					if ( !empty($contact) )
						$contact_id = $contact['id'];
					else
						$contact_id = $this->insert_contact( $request['sender']  );		
					$this->insert_chat_message( $contact_id, $message, $request['message_token'] );
				}
			break;		
		}
	}
}
?>