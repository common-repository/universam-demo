<?php
require_once( USAM_FILE_PATH .'/includes/feedback/social_network_handler.class.php' );
class USAM_Skype extends USAM_Social_Network_Handler
{	
	private $skype;	
	protected $user_meta_key = 'skype_user_id';	
	private function insert_contact( $user )
	{		
		$data = ['firstname' => $user['name'], 'online' => date("Y-m-d H:i:s"), $this->user_meta_key => $user_id];	
		$user_id = sanitize_text_field($user['id']);		
		$contact_id = usam_insert_contact( $data );
		return $contact_id;
	}
	
	private function set_system_message( $message, $sender_id )
	{			
		if (stripos($message, 'user_id') !== false)	
			$args = $this->update_user_id( $message, $sender_id );
		else
		{		
			$contact = $this->get_contact( $sender_id );
			if ( !empty($contact) )
			{	
				require_once( USAM_FILE_PATH . '/includes/personnel/command_handler.class.php');
				$args = self::command_handler( $contact, $message );	
			/*	if ( !empty($args['buttons']) )
				{					
					$args['reply_markup'] = array( 'inline_keyboard' => $args['buttons'] );
					unset($args['buttons']);
				}
				elseif ( !empty($args['buttons2']) )
				{
					$args['reply_markup'] = ['keyboard' => array( $args['buttons2'] ), 'one_time_keyboard' => true, 'resize_keyboard' => true];
					unset($args['buttons2']);
				}			
				if ( !empty($args) )
					$args['contact_id'] = $contact['id'];	*/
			}
		}		
		if ( !empty($args) ) 
		{			
			$this->skype->send_message( $args );
			$result = true;	
		}		
		return $result;
	}
	
	public function notifications(  )
    {
		$request = json_decode(file_get_contents('php://input'), true);	
			
		$this->profile = (array)usam_get_social_network_profiles(['type_social' => 'skype', 'code' => $request['recipient']['id'], 'number' => 1]);			
		if ( empty($this->profile) )
			return false;			
	
		require_once( USAM_APPLICATION_PATH . '/social-networks/skype_api.class.php' );	
		$this->skype = new USAM_Skype_API( $this->profile );		
	
		switch ((string)$request['type']) 
		{
			case 'message':				
				$contact = $this->get_contact( $request['from']['id'] );					
				if ( !empty($contact) )
					$contact_id = $contact['id'];
				else
					$contact_id = $this->insert_contact( $request['sender']  );		
				$this->insert_chat_message( $contact_id, (string)$request['channelData']['text'], '' );
				
				$args['id'] = $request['id'];
				$args['from'] = $request['recipient'];
				$args['message'] = '77777777777';
				$args['recipient'] = $request['from'];
				$args['conversation_id'] = $request['conversation']['id'];
				$args['serviceUrl'] = $request['serviceUrl'];
				
				$this->skype->send_message( $args );
			break;
			default:
				$message = 'Unknown type';
			break;
		}
	}
}
?>