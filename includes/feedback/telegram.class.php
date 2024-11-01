<?php
require_once( USAM_FILE_PATH .'/includes/feedback/social_network_handler.class.php' );
class USAM_Telegram extends USAM_Social_Network_Handler
{		
	private $telegram;
	protected $user_meta_key = 'telegram_user_id';
	protected $social = 'telegram';
	
	private function insert_contact( $user )
	{					
		$user_id = absint($user['id']);		
		$data = ['online' => date("Y-m-d H:i:s"), 'contact_source' => $this->social,'lastname' => !empty($user['last_name'])?trim(stripcslashes($user['last_name'])):"", 'firstname' => trim(stripcslashes($user['first_name']))];
		$photos = $this->telegram->get_user_photos(['user_id' => $user_id, 'limit' => 1]);
		if( !empty($photos) )
		{
			$photo_url = $this->telegram->get_photo_url( $photos['photos'][0][1]['file_id'] );
			if( !empty($photo_url) )
			{			
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$foto_id = media_sideload_image( $photo_url, 0, '', 'id' );
				if ( is_numeric($foto_id) )
					$data['foto'] = $foto_id;
			}		
		}	
		$data[$this->user_meta_key] = sanitize_text_field($user['id']);
		$contact_id = usam_insert_contact( $data );	
		return $contact_id;
	}
		
	private function set_system_message( $message, $sender_id )
	{					
		$result = false;	
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
					$args['reply_markup'] = array( 'inline_keyboard' => $args['buttons'] );
					unset($args['buttons']);
				}
				elseif ( !empty($args['buttons2']) )
				{
					$args['reply_markup'] = ['keyboard' => array( $args['buttons2'] ), 'one_time_keyboard' => true, 'resize_keyboard' => true];
					unset($args['buttons2']);
				}			
				if ( !empty($args) )
					$args['contact_id'] = $contact['id'];	
			}
		}		
		if ( !empty($args) ) 
		{			
			$this->telegram->send_message( $args );
			$result = true;	
		}
		return $result;
	}
	
	public function notifications(  )
    {					
		$json = file_get_contents("php://input");
		$request = json_decode($json, true);	
		
		if ( empty($_REQUEST['token']) )
			return false;	
					
		$token = sanitize_text_field($_REQUEST['token']);				
		$this->profile = (array)usam_get_social_network_profiles(['access_token' => $token, 'type_social' => 'telegram', 'number' => 1]);
		if ( empty($this->profile) )
			return false;			
		
		require_once( USAM_APPLICATION_PATH . '/social-networks/telegram_api.class.php' );
		$this->telegram = new USAM_Telegram_API( $this->profile );		
				
	//		$args = array('contact_id' => $contact['id'],  'message' => __("Выберете действие"), 'reply_markup' => array( 'keyboard' => array( array( array( 'text' => __("Заказы","usam") ) ) ), 'one_time_keyboard' => true, 'resize_keyboard' => true ));

		if ( !empty($request['callback_query']) && !empty($request['callback_query']['data']))
		{					
			if ( $request['callback_query']['data'] !== '/start' )
			{
				$sender_id = sanitize_text_field($request['callback_query']['from']['id']);		
				$contact = $this->get_contact( $sender_id );	
				if ( !empty($contact) )
				{
					$results = self::command_handler( $contact, $request['callback_query']['data'] );
					$args = array( 'contact_id' => $contact['id'] );				
					$args['message'] = !empty($results['error'])?$results['error']:$results['message'];				
					if ( !empty($results['buttons']) )
					{
						$args['reply_markup'] = array( 'inline_keyboard' => $results['buttons'] );						
					}		
					elseif ( !empty($results['buttons2']) )
					{
						$args['reply_markup'] = array( 'keyboard' => array( $results['buttons2'] ), 'one_time_keyboard' => true, 'resize_keyboard' => true );
					}				
					$result = $this->telegram->send_message( $args );	
				}
			}
		}
		elseif ( !empty($request['message']) && $request['message']['text'] !== '/start' )
		{
			$sender_id = sanitize_text_field($request['message']['from']['id']);
			$message = trim(stripcslashes($request['message']['text'])); 					
			if ( !$this->set_system_message($message, $sender_id) )			
			{								
				$contact = $this->get_contact( $sender_id );				
				if ( !empty($contact) )
					$contact_id = $contact['id'];
				else
					$contact_id = $this->insert_contact( $request['message']['from'] );		
				$this->insert_chat_message( $contact_id, $message, $update['update_id'] );
			}
		}	
    }
}
?>