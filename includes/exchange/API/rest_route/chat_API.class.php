<?php 
class USAM_Chat_API extends USAM_API
{	
//if ( current_user_can('write_to_chat') && false )		
	public static function get_messages( WP_REST_Request $request ) 
	{			
		global $wpdb;
		$parameters = self::get_parameters( $request );
		$contact_id = usam_get_contact_id();
		$messages = [];	
		$dialog_id = 0;
		if ( !empty($parameters['dialog_id']) )
		{
			if ( current_user_can('view_chat') )
				$dialog_id = $parameters['dialog_id'];				
			else
			{
				$contacts = usam_get_chat_users( $parameters['dialog_id'] );
				if ( in_array($contact_id, $contacts) )
					$dialog_id = $parameters['dialog_id'];
				else
					return [];
			}
		}
		if ( !$dialog_id )
		{//если диалог не найден
			$dialog_id = self::get_dialog_id();
		}		
		if ( $dialog_id )
		{					
			$parameters['dialog_id'] = $dialog_id;
			if ( !empty($parameters['read_ids']) )
			{
				$read_ids = wp_parse_id_list($parameters['read_ids']);
				unset($parameters['read_ids']);					
				usam_update_status_chat_messages( $dialog_id, $contact_id, $read_ids );
			}
			$messages = self::messages_query( $parameters );
			$messages = array_reverse($messages);   
		}
		$result = self::get_dialog_data( $dialog_id );
		$result['items'] = $messages;  
		return $result;
	}	
	
	private static function get_dialog_id() 
	{
		$contact_id = usam_get_contact_id();
		$contact = usam_get_contact();
		$dialog_id = 0;		
		if ( !empty($contact) )
		{
			require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialogs_query.class.php');
			$dialog_id = usam_get_chat_dialogs(['fields' => 'id', 'user' => $contact_id, 'number' => 1, 'orderby' => 'date', 'order' => 'DESC', 'cache_results' => true]);						
			if ( !$dialog_id )							
				$dialog_id = usam_insert_chat_dialog(['channel' => "chat", 'channel_id' => 0], ['user' => $contact_id]); 
		}
		return $dialog_id;
	}
	
	private static function get_dialog_data( $dialog_id ) 
	{			
		global $wpdb;
		$contact_id = usam_get_contact_id();
		$recipient = 0;
		$unread = 0;
		$dialog = [];		
		if ( $dialog_id )
		{			
			$dialog = usam_get_chat_dialog( $dialog_id );
			if ( !$dialog )
				return [];
			if ( $dialog['type'] == 'personal' )
			{
				if ( current_user_can('view_chat') )
					$recipient = usam_get_contacts(['source' => 'all', 'status' => 'all', 'dialog_id' => $dialog_id, 'exclude' => [$contact_id], 'number' => 1, 'cache_results' => true]);
				else
					$recipient = $dialog['manager_id'];	
			}
			else
			{
				$contacts = usam_get_contacts(['source' => 'all', 'status' => 'all', 'dialog_id' => $dialog_id, 'cache_results' => true]);
			}
			$manager = self::get_contact_data( $dialog['manager_id'] );	
			$unread = (int)$wpdb->get_var("SELECT not_read FROM `".USAM_TABLE_CHAT_USERS."` WHERE contact_id=$contact_id AND dialog_id=$dialog_id");			
		}
		else
			$manager = self::get_contact_data( 0 );		
		$sender = self::get_contact_data( usam_get_contact_id() );		
		$recipient = self::get_contact_data( $recipient );	
		return ['recipient' => $recipient, 'sender' => $sender, 'dialog' => $dialog, 'manager' => $manager, 'unread' => $unread];	
	}
	
	public static function get_dialog( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );					
		if ( !$parameters['dialog_id'] )
			return [];
		if ( !current_user_can('view_chat') )
		{
			$contact_id = usam_get_contact_id();
			$contacts = usam_get_chat_users( $parameters['dialog_id'] );
			if ( !in_array($contact_id, $contacts) )
				return [];
		}
		return self::get_dialog_data( $parameters['dialog_id'] );
	}
	
	public static function save_dialog( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$args = [];
		if ( empty($parameters['manager_id']) )
			$args['manager_id'] = usam_get_contact_id();
		if ( usam_update_chat_dialog( $id, $args ) )
		{
			$response = self::get_dialog_data( $id );	
			if ( !empty($args['manager_id']) )
			{
				$manager = usam_get_contact( $args['manager_id'] );
				self::insert_message(['contact_id' => $args['manager_id'], 'dialog_id' => $id, 'message' => sprintf(__("К беседе подключился менеджер %s", "usam"), $manager['appeal'])]);
			}
			$response['items'] = self::messages_query(['dialog_id' => $id]);
			return $response;
		}
		else
			return false;
	}	
	
	public static function get_dialogs( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		self::$query_vars = self::get_query_vars( $parameters, $parameters );
									
		require_once(USAM_FILE_PATH.'/includes/crm/notifications_query.class.php');
		if ( isset(self::$query_vars['user']) && self::$query_vars['user'] == 'my' || !current_user_can('view_chat') )
			self::$query_vars['user'] = usam_get_contact_id();
		
		require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialogs_query.class.php');
		$query = new USAM_Chat_Dialogs_Query( self::$query_vars );			
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			foreach ( $items as $k => &$item )
			{						
				if ( isset($item->contact_message) )
					$item->author = self::get_contact_data( $item->contact_message );
				if ( !empty($parameters['fields']) )
				{
					if ( in_array('objects', $parameters['fields']) )
					{
						
					}
				}
				if ( !current_user_can('view_chat') )
				{
					unset($item->manager_id);
					unset($item->channel_id);
					unset($item->channel_id);
				}				
			}
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];			
		return $results;
	}	
	
	private static function format_message( $messages )
	{	
		$result = array();
		if ( $messages )
		{
			$contact_id = usam_get_contact_id( );				
			$ids = [];
			foreach ( $messages as $message ) 
			{
				$ids[] = $message->id;
			}
			$attachments = usam_get_files(['object_id' => $ids, 'type' => 'chat']);			
			foreach ( $messages as &$message ) 
			{			
				$message->author = self::get_contact_data( $message->contact_id );			
				if ( empty($message->author['appeal']) )
					$message->author['appeal'] = $contact_id==$message->contact_id?__("Вы","usam"):__("Имя неизвестно","usam");
				$message->my = $contact_id==$message->contact_id;
				$message->attachments = [];
				foreach ( $attachments as $attachment ) 
				{
					if ( $attachment->object_id == $message->id )
						$message->attachments[] = ['title' => $attachment->title, 'url' => get_bloginfo('url').'/show_file/'.$attachment->id];
				}
				$result[] = $message ;
			}
		}
		return $result;
	}	
	
	private static function messages_query( $args ) 
	{			
		require_once(USAM_FILE_PATH.'/includes/feedback/chat_messages_query.class.php');	
		$args['add_fields'] = 'status';	
		if ( empty($args['from_id']) )
			$args['number'] = 20;
		$messages = usam_get_chat_messages( $args );		
		return self::format_message( $messages );	
	}

	private static function insert_message( $chat_message ) 
	{			
		if ( empty($chat_message['contact_id']) )
			$chat_message['contact_id'] = usam_get_contact_id();	
		
		require_once( USAM_FILE_PATH . '/includes/feedback/chat_handler.class.php' );
		$handler = new USAM_Chat_Handler();					
		$chat_message = $handler->save_chat_message( $chat_message );
		$handler->search_answer( $chat_message );
		return $chat_message;
	}
	
	public static function save_message( WP_REST_Request $request )
	{
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		return usam_update_chat_message( $id, ['message' => $parameters['message']] );		
	}
	
	public static function add_message( WP_REST_Request $request )
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$response = ['dialog_id' => 0, 'items' => []];		
		$message = !empty($parameters['message'])?$parameters['message']:'';
		$message = trim(stripcslashes($message));
		if ( $message != '' )
		{				
			$contact_id = usam_get_contact_id();	
			$links = [];
			if ( !empty($parameters['object_id']) && !empty($parameters['object_type']) )
			{
				$links[] = ['object_id' => $parameters['object_id'], 'object_type' => $parameters['object_type']];	 	
				$to_contact_id = 0;
				switch ( $parameters['object_type'] ) 
				{
					case 'contact' :
						$to_contact_id = $parameters['object_id'];
					break;
					case 'order' :
						$order = usam_get_order( $parameters['object_id'] );
						$to_contact_id = $order['contact_id'];
					break;
					case 'lead' :
						$lead = usam_get_lead( $parameters['object_id'] );
						$to_contact_id = $lead['contact_id'];
					break;
				}
				if ( $to_contact_id )
				{
					require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialogs_query.class.php');
					$dialog_id = usam_get_chat_dialogs(['fields' => 'id', 'user_to' => $to_contact_id, 'number' => 1, 'orderby' => 'date', 'order' => 'DESC', 'cache_results' => true]);		
					if ( !$dialog_id )							
						$dialog_id = usam_insert_chat_dialog(['channel' => "chat", 'channel_id' => 0], ['user' => $to_contact_id]); 
				}
				else
					return false;
			}
			else
			{							
				if ( empty($parameters['dialog_id']) )
					$dialog_id = self::get_dialog_id();
				else
					$dialog_id = $parameters['dialog_id'];				
			}		
			$dialog = usam_get_chat_dialog( $dialog_id );
			if ( !$dialog )
				return [];			
			if ( $dialog['type'] == 'personal' )
				$send_contact = usam_get_contacts(['source' => 'all', 'status' => 'all', 'dialog_id' => $dialog_id, 'exclude' => [$contact_id], 'number' => 1, 'cache_results' => true]);
			else
				$send_contact = usam_get_contacts(['source' => 'all', 'status' => 'all', 'dialog_id' => $dialog_id, 'exclude' => [$contact_id], 'number' => 1, 'cache_results' => true]);			
			if ( $contact_id )	
			{
				$chat_message = self::insert_message(['contact_id' => $contact_id, 'send_contact_id' => isset($send_contact['id'])?$send_contact['id']:0, 'dialog_id' => $dialog_id, 'message' => $message]);	
				if ( $links && !empty($chat_message['id']) )
				{
					require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
					usam_insert_ribbon(['event_id' => $chat_message['id'], 'event_type' => 'chat'], $links);	
				}
			}			
			if ( !empty($parameters['update']) && $parameters['update'])
			{				
				$args = ['dialog_id' => $dialog_id];
				if ( !empty($parameters['message_id']) )
					$args['to_id'] = $parameters['message_id'];		
				$response['items'] = self::messages_query( $args );	 
				$response['dialog_id'] = $dialog_id;	
			}
			elseif ( !empty($chat_message) )
			{
				$contact = self::get_contact_data( $chat_message['contact_id'] );
				foreach ( $contact as $key => $value )				
					$chat_message['author'][$key] = $value;
				return $chat_message;
			}
		}			
		return $response;	
	}	
			
	public static function save_contactform( WP_REST_Request $request )
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		$response = ['items' => [], 'contact' => [], 'dialog_id' => 0, 'consultants' => false];			
		if ( empty($parameters['message']))
			return $response;				
		$message = trim(stripcslashes($parameters['message']));			
		if ( $message != '' )
		{				
			$contact = ['contact_source' => 'chat'];	
			if ( !empty($parameters['email']) && is_email($parameters['email']) )
				$contact['email'] = trim($parameters['email']);				
			if ( !empty($parameters['phone']) )
				$contact['mobilephone'] = absint($parameters['phone']);
			if ( !empty($parameters['name']) )
				$contact['full_name'] = trim($parameters['name']);
						
			$contact_id = usam_save_or_create_contact( $contact );			
			$dialog_id = usam_insert_chat_dialog(['channel' => "chat"], [$contact_id] );		
		
			$chat_message = (object)self::insert_message(['contact_id' => $contact_id, 'dialog_id' => $dialog_id, 'message' => $message]);		
			$response = self::get_dialog_data( $dialog_id );	
			$response['items'] = self::format_message([$chat_message]); 		
		}		
		return $response;
	}
}
?>