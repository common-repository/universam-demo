<?php
class USAM_Command_Handler
{
	public static function start( $contact_id, $message )
	{	
		preg_match('/usam[ ]?-[ ]?(.[^\s]*)/i', $message, $m);	
		if ( !empty($m[1]) )
		{
			$str = explode('-',$m[1]);		
			if ( isset($str[0]) )
			{
				$method = 'command_'.$str[0];	
				if ( method_exists(__CLASS__, $method) )
				{				
					$code = isset($str[1])?$str[1]:'';
					$message = str_replace($m[0],'',$message);						
					return self::$method( $contact_id, $message, $code );					
				}
			}
		}
		return false;
	}
	
	private static function command_chatmessage( $contact_id, $message, $dialog_id ) 
	{
		if ( $dialog_id )
		{
			$dialog = usam_get_chat_dialog( $dialog_id );					
			$chat_message = ['contact_id' => $contact_id, 'send_contact_id' => $contact_id, 'dialog_id' => $dialog_id, 'message' => $message, 'channel' => $dialog['channel'], 'channel_id' => $dialog['channel_id'], 'manager_id' => $contact_id];
			
			require_once( USAM_FILE_PATH . '/includes/feedback/chat_handler.class.php' );
			$chat = new USAM_Chat_Handler();
			return $chat->save_chat_message( $chat_message );
		}
	}
	
	private static function command_emailmessage( $contact_id, $message, $id ) 
	{		
		if ( $id )
		{
			$_email = new USAM_Email( $id );
			$email_data = $_email->get_data( );							
			return usam_send_mail(['body' => $message, 'title' => $email_data['title'], 'mailbox_id' => $email_data['mailbox_id'], 'to_name' => $email_data['from_name'], 'to_email' => $email_data['from_email']]);	
		}
	}
}
?>