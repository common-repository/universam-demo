<?php
/*=========================================================== Чат ==================================================*/
class USAM_Chat_Handler
{		
	public function open_dialog( $dialog_id ) 
	{	
		$contact_id = usam_get_contact_id( );				
		$dialog = usam_get_chat_dialog( $dialog_id );
		if( $dialog['manager_id'] == 0 && $contact_id )
		{					
			$contact = usam_get_contact( $contact_id );						
			if ( $contact['contact_source'] == 'employee' )
			{
				$online_consultant = usam_get_contact_metadata( $contact_id, 'online_consultant' );	
				if ( $online_consultant )
				{
					$message = __("Консультант подключился в беседу", "usam");
					$chat_message = array( 'contact_id' => $contact_id, 'dialog_id' => $dialog_id, 'message' => $message, 'status' => 1 );	
					usam_insert_chat_message( $chat_message );
					usam_update_chat_dialog( $dialog_id, ['manager_id' => $contact_id]);
				}
			}
		} 				
	}		
			
	public function search_answer( $chat_message ) 
	{ 
		$dialog = usam_get_chat_dialog( $chat_message['dialog_id'] );
		$chat_bot_options = get_option("usam_chat_bot");
		require_once( USAM_FILE_PATH . '/includes/feedback/chat_bot_commands_query.class.php');
		require_once( USAM_FILE_PATH . '/includes/feedback/chat_bot_templates_query.class.php');
		require_once( USAM_FILE_PATH . '/includes/feedback/chat_bot_command.class.php' );
		
		preg_match_all("/.*?[.?!](?:\s|$)/s", strip_tags($chat_message['message']), $offers);
		
		$answer = '';
		$templates = usam_get_chat_bot_templates(['channel' => ['all', $dialog['channel']], 'active' => 1]);		
		if ( !empty($templates) )
		{
			$ids = array();
			$chat_bot_templates = array();
			foreach ( $templates as $template ) 
			{
				$ids[] = $template->id;		
				$chat_bot_templates[$template->id] = $template;
			}	
			$commands = usam_get_chat_bot_commands(['active' => 1, 'template_id' => $ids]);						
			foreach ( $offers[0] as $offer_key => $offer )
			{				
				$result = false;
				foreach ( $commands as $command )
				{
					$templates_command = usam_get_chat_bot_command_metadata( $command->id, 'templates' );		
					if ( !empty($templates_command) )
					{
						foreach ( $templates_command as $template_command )
						{ 
							preg_match_all('/%(.+?)%/m', $template_command, $mt);	
							if ( !empty($mt[1]) )
							{ 
								$pattern = "/(".implode(")(.+?)(",$mt[1]).")/u";				
								if ( preg_match($pattern, $offer ) )
								{
									$answer .= $command->message.' ';	
									$result = true;
									break 2;		
								}
							}
							elseif (strcasecmp($template_command, $offer) == 0) 
							{
								$answer .= $command->message.' ';
								$result = true;
								break 2;					
							}								
						}
					}
				} 
				if ( !$result )
					return false;
			}	
			if ( $answer != '' )
			{
				sleep( $command->time_delay );							
				$chat_message['message'] = $answer;		
				$chat_message['contact_id'] = !empty($chat_bot_options['contact_id'])?$chat_bot_options['contact_id']:0;	
				$chat_message['send_contact_id'] = !empty($chat_bot_options['send_contact_id'])?$chat_bot_options['send_contact_id']:0;
				return $this->save_chat_message( $chat_message );		
			}			
		}
		return false;
	}
	
	public function save_chat_message( $chat_message ) 
	{			
		$dialog = usam_get_chat_dialog( $chat_message['dialog_id'] );
		if ( $dialog['channel'] == 'chat' )
		{
			preg_match_all('!(http|ftp|scp)(s)?:\/\/[a-zA-Z0-9.?%=&-_/]+!', $chat_message['message'], $matches);
			if ( !empty($matches[0]) )
			{
				foreach ( $matches[0] as $matche )
				{			
					$chat_message['message'] = str_replace($matche,"<a href='$matche'>$matche</a>", $chat_message['message']);
				}
			}
			$chat_message['status'] = 1;			
		}
		$chat_message['date_insert'] = date("Y-m-d H:i:s");
		$message_id = usam_insert_chat_message( $chat_message );
		$chat_message['id'] = $message_id;		
		if ( $dialog['manager_id'] == 0 )
		{
			$contact = usam_get_contact( $chat_message['contact_id'] );			
			if ( $contact['contact_source'] == 'employee' )
			{
				$dialog_update['manager_id'] = $chat_message['contact_id'];
				usam_update_chat_dialog( $chat_message['dialog_id'], $dialog_update );
			}	
		}
		if ( !empty($chat_message['send_contact_id']) )
		{
			$args = ['message' => $chat_message['message'], 'group_id' => $dialog['channel_id'], 'id' => $message_id, 'contact_id' => $chat_message['send_contact_id']];		
			$social_network = usam_get_social_network_profile( $dialog['channel_id'] );				
			$guid = usam_send_message_to_messenger( $social_network, $args );	
			if ( !empty($guid) )
				usam_update_chat_message($message_id, ['guid' => $guid, 'status' => 1]);
		}
		return $chat_message;
	}
}
?>