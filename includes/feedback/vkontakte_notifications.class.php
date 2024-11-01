<?php
require_once( USAM_FILE_PATH .'/includes/feedback/vkontakte_auth.class.php' );
class USAM_VKontakte_Notifications extends USAM_VKontakte_Auth
{					
	protected function get_or_create_contact( $user_id )
	{			
		$user_id = absint($user_id);
		$contact = $this->get_contact( $user_id );
		if ( !$contact )
			return $this->insert_contact( $user_id );
		else
			return $contact['id'];
	}
	
	protected function get_dialog_vk( $contact_id, $to_contact_id )
	{		
		require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialogs_query.class.php');
		$dialog_id = usam_get_chat_dialogs(['fields' => 'id', 'number' => 1, 'contact_id' => $contact_id, 'orderby' => 'id', 'channel' => 'vk', 'channel_id' => $this->profile['id'], 'order' => 'DESC']);		
		if ( empty($dialog_id) )			
			$dialog_id = usam_insert_chat_dialog(['channel' => 'vk', 'contact_id' => $contact_id, 'channel_id' => $this->profile['id']], [$to_contact_id, $contact_id]);
		return $dialog_id;
	}
	
	protected function save_chat_message( $from_vk_user_id, $to_vk_user_id, $object_message, $status = 0 )
	{			
		$contact_id = $this->get_or_create_contact( $from_vk_user_id );	
		$to_contact_id = $this->get_or_create_contact( $to_vk_user_id );
		$message_id = absint($object_message->id);
		$message = trim(stripcslashes($object_message->text));
		$dialog_id = $this->get_dialog_vk( $contact_id, $to_contact_id );
		$chat_message_id = usam_insert_chat_message(['contact_id' => $contact_id, 'dialog_id' => $dialog_id, 'message' => $message, 'guid' => $message_id, 'status' => $status]);
		$this->save_attachments( $object_message, $chat_message_id  );
	}
	
	protected function save_attachments( $object_message, $chat_message_id  )
	{			
		if ( !empty($object_message->attachments) )
		{						
			$folder_id = usam_get_folder_object( __("Вконтакте","usam"), 'chat', __("Чат","usam") );
			foreach ($object_message->attachments as $attachment ) 
			{		
				if ( $attachment->type == 'photo' )
				{
					$photo = array_pop($attachment->photo->sizes);
					usam_add_file_url_from_files_library($photo->url, ['type' => 'chat', 'folder_id' => $folder_id, 'object_id' => $chat_message_id]);			
				}
			}
		}
	}
		
	public function notifications()
	{ 
		$json = file_get_contents('php://input'); 
		$request = json_decode($json);			
		if ( isset($request->type) )
		{			
			require_once(USAM_FILE_PATH.'/includes/feedback/chat_dialogs_query.class.php');
			require_once(USAM_FILE_PATH.'/includes/feedback/chat_messages_query.class.php');
			require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );			
			$request->group_id = absint($request->group_id);
			$this->profile = usam_get_social_network_profiles(['type_social' => 'vk_group', 'code' => $request->group_id, 'number' => 1]);					
			if ( empty($this->profile) )
				return false;			
			$secret_key = usam_get_social_network_profile_metadata( $this->profile['id'], 'secret_key' );
			if ( !isset($request->secret) || $secret_key != $request->secret )
				return false;			
			switch ( $request->type ) 
			{
				case 'confirmation' :	
					$confirmation = usam_get_social_network_profile_metadata( $this->profile['id'], 'confirmation' );
					exit( $confirmation );		
				break;
				case 'message_new' :	//входящее сообщение											
					if ( usam_is_license_type('BUSINESS') || usam_is_license_type('ENTERPRISE') )
					{						
						$object_message = isset($request->object->message)?$request->object->message:$request->object;	
						$message_id = absint($object_message->id);
						$from_vk_user_id = absint($object_message->from_id);	//admin_author_id		peer_id	
						$to_vk_user_id = !empty($object_message->admin_author_id)?absint($object_message->admin_author_id):0;
						$chat_messages = usam_get_chat_messages(['channel' => 'vk', 'guid' => $message_id]);			
						if ( empty($chat_messages) )														
							$this->save_chat_message( $from_vk_user_id, $from_vk_user_id, $object_message );
					}
				break;	
				case 'message_reply' : //если менеджер ответе вКонтакте											
					$object_message = isset($request->object->message)?$request->object->message:$request->object;		
					if ( !empty($object_message->admin_author_id) )
					{ // Сообщение отправлено из контакта, иначе не будет admin_author_id
						$message_id = absint($object_message->id);
						$from_vk_user_id = absint($object_message->admin_author_id);		
						$to_vk_user_id = absint($object_message->peer_id);	
						
						$chat_messages = usam_get_chat_messages(['channel' => 'vk', 'guid' => $message_id]);			
						if ( empty($chat_messages) )												
							$this->save_chat_message( $from_vk_user_id, $to_vk_user_id, $object_message );
					}
				break;
				case 'message_typing_state' : //уведомления о наборе текста сообщения
					$vk_user_id = absint($request->object->to_id);					
					$contact = $this->get_contact( $vk_user_id );
					if ( !empty($contact) )				
					{							
						$dialog_id = usam_get_chat_dialogs(['fields' => 'id', 'number' => 1, 'contact_id' => $contact['id'], 'channel' => 'vk', 'channel_id' => $this->profile['id'], 'orderby' => 'id', 'order' => 'DESC']);
						if ( $dialog_id )
						{
							global $wpdb;
							$read_ids = $wpdb->get_col("SELECT c.id FROM `".USAM_TABLE_CHAT."` AS c INNER JOIN ".USAM_TABLE_CHAT_MESSAGE_STATUSES." AS s ON (c.id=s.message_id) WHERE c.contact_id!=".$contact['id']." AND c.dialog_id=$dialog_id AND s.status=1");
							usam_update_status_chat_messages( $dialog_id, $contact['id'], $read_ids );				
						}
					}				
					else
						$contact_id = $this->insert_contact( $vk_user_id );
				break;
				case 'group_join' ://добавление участника или заявки на вступление в сообщество
				case 'group_leave' ://удаление участника из сообщества
					$vk_user_id = absint($request->object->user_id);
					$vk_group_id = absint($request->group_id);
					if ( !empty($request->object->join_type) )
					{
						$join_type = sanitize_text_field($request->object->join_type);
						$message = usam_get_social_network_profile_metadata( $this->profile['id'], 'message_group_'.$join_type );
					}
					else
						$message = usam_get_social_network_profile_metadata( $this->profile['id'], 'message_'.$request->type );
					
					$contact = $this->get_contact( $vk_user_id );					
					if ( !empty($contact) )				
					{		
						$dialog_id = usam_get_chat_dialogs(['type' => 'group', 'fields' => 'id', 'number' => 1, 'contact_id' => $contact['id'], 'channel' => 'vk', 'channel_id' => $this->profile['id'], 'orderby' => 'id', 'order' => 'DESC']);
						$contact_id = $contact['id'];
					}				
					else
					{
						$contact_id = $this->insert_contact( $vk_user_id );
					}		
					if ( !empty($this->profile['contact_group']) )
					{
						if ( $request->type == 'group_join' )					
							usam_set_groups_object( $contact_id, 'contact', $this->profile['contact_group'], true );
						else
							usam_delete_groups_object( $contact_id, $this->profile['contact_group'] );
					}
					if ( $message )
					{
						if ( empty($dialog_id) )				
							$dialog_id = usam_insert_chat_dialog(['type' => 'group', 'name' => $this->profile['name'], 'contact_id' => $contact_id, 'channel' => 'vk', 'channel_id' => $this->profile['id']], [$contact_id]);
						
						$object_message = isset($request->object->message)?$request->object->message:$request->object;							
						$message_id = absint($object_message->id);
						usam_insert_chat_message(['contact_id' => 0, 'dialog_id' => $dialog_id, 'message' => $message, 'guid' => $message_id]);
						$vkontakte = new USAM_VKontakte_API( $vk_group_id );	
						$result = $vkontakte->send_message(['message' => $message, 'id' => $message_id]);				
						if ( !empty($result['message_id']) )
							usam_update_chat_message(['guid' => $result['message_id']]);	
					}
				break;
				case 'wall_repost' : //репост записи из сообщества	
				
				
				break;				
				case 'market_comment_new' : //новый комментарий к товару						
					$request->object->from_id = absint($request->object->from_id);
					$request->object->item_id = absint($request->object->item_id);
					$id = absint($request->object->id);						
					
					require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
					$reviews = usam_get_customer_reviews(['meta_key' => 'vk_market_comment_id', 'meta_value' => $id]);
					if ( empty($reviews) )
					{
						$product_ids = usam_get_products(['productmeta_query' => [['key' => 'vk_market_id_'.$request->group_id, 'type' => 'numeric', 'value' => $request->object->item_id, 'compare' => '=' ]], 'fields' => 'ids']);
						$insert = ['review_text' => trim(stripcslashes($request->object->text)), 'page_id' => $product_ids[0]];
						
						$contact = $this->get_contact( $request->object->from_id );						
						if ( !empty($contact) )	
							$insert['contact_id'] = $contact['id'];		
						$review_id = usam_insert_review( $insert ); 	
						if ( !empty($review_id) )	
						{
							usam_update_review_metadata( $review_id, 'vk_group', $request->group_id );
							usam_update_review_metadata( $review_id, 'vk_product_id', $request->object->item_id );
							usam_update_review_metadata( $review_id, 'vk_market_comment_id', $id ); //идентификатор комментария.
							usam_update_review_metadata( $review_id, 'vk_comment_author', $request->object->from_id );//идентификатор автора комментария.					
												
							if ( !empty($request->object->reply_to_user) )
								usam_update_review_metadata( $review_id, 'vk_comment_reply_to_user', $request->object->reply_to_user ); //идентификатор пользователя или сообщества, в ответ которому оставлен текущий комментарий (если применимо).
							if ( !empty($request->object->reply_to_comment) )
								usam_update_review_metadata( $review_id, 'vk_comment_reply_to_comment', $request->object->reply_to_comment );
							
							usam_add_notification(['title' => sprintf(__('Новый отзыв из вКонтакте в &laquo;%s&raquo;', 'usam'), $this->profile['name'])], ['object_type' => 'review', 'object_id' => $review_id]);
						}
					}
				break;
				case 'market_comment_edit' :
					$id = absint($request->object->id);						
					if ( $id )
					{
						require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
						$reviews = usam_get_customer_reviews(['meta_key' => 'vk_market_comment_id', 'meta_value' => $id, 'status' => 1]);
						if ( !empty($reviews) )
							usam_update_review( $reviews[0]->id, ['review_text' => trim(stripcslashes($request->object->text))] ); 	
					}
				break;
				case 'market_comment_delete' :
					$id = absint($request->object->id);						
					if ( $id )
					{
						require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
						$reviews = usam_get_customer_reviews(['meta_key' => 'vk_market_comment_id', 'meta_value' => $id, 'status' => 1]);
						if ( !empty($reviews) )	
							usam_delete_review( $reviews[0]->id ); 	
					}
				break;
				case 'market_delete' :
					$id = absint($request->object->id);
					if ( $id )
					{
						$args = ['post_status' => 'publish', 'cache_product' => false, 'productmeta_query' => [['key' => 'vk_market_id_'.$this->profile['code'], 'type' => 'numeric', 'value' => $id, 'compare' => '=']] ];	
						$products = usam_get_products( $args );
						foreach( $products as $product ) 
						{
							usam_update_product_meta( $product->ID, 'vk_market_publish_date_'.$this->profile['code'], '' );
							usam_update_product_meta( $product->ID, 'vk_market_id_'.$this->profile['code'], 0 );						
						}
					}					
				break;
				case 'wall_reply_new' :
					$id = absint($request->object->id);					
					if ( $id )
					{
						require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
						$reviews = usam_get_customer_reviews(['meta_key' => 'vk_comment_id', 'meta_value' => $id]);
						if ( empty($reviews) )
						{ 
							$vk_post_id = absint($request->object->post_id);	
							$post_id = get_posts(['postmeta_query' => [['key' => 'post_id_vk_group_'.$request->group_id, 'type' => 'numeric', 'value' => $vk_post_id, 'compare' => '=']], 'fields' => 'ids']);
													
							$contact = $this->get_contact( $request->object->from_id );						
							$insert = array( 'review_text' => trim(stripcslashes($request->object->text)), 'page_id' => $post_id[0] );
							if ( !empty($contact) )	
								$insert['contact_id'] = $contact['id'];	
							$review_id = usam_insert_review( $insert ); 	
							if ( !empty($review_id) )	
							{
								usam_update_review_metadata( $review_id, 'vk_group', $request->group_id );
								usam_update_review_metadata( $review_id, 'vk_post_id', $vk_post_id );
								usam_update_review_metadata( $review_id, 'vk_comment_id', $id ); //идентификатор комментария.
								usam_update_review_metadata( $review_id, 'vk_comment_author', $request->object->from_id );//идентификатор автора комментария.					
													
								if ( !empty($request->object->reply_to_user) )
									usam_update_review_metadata( $review_id, 'vk_comment_reply_to_user', $request->object->reply_to_user ); //идентификатор пользователя или сообщества, в ответ которому оставлен текущий комментарий (если применимо).
								if ( !empty($request->object->reply_to_comment) )
									usam_update_review_metadata( $review_id, 'vk_comment_reply_to_comment', $request->object->reply_to_comment );
								
								usam_add_notification( array('title' => sprintf(__('Добавление комментария на стене вКонтакте в %s', 'usam'), $this->profile['name']) ), array('object_type' => 'review', 'object_id' => $review_id) );
							}	
						}
					}
				break;
				case 'wall_reply_edit' :
					$id = absint($request->object->id);						
					require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
					$reviews = usam_get_customer_reviews( array('meta_key' => 'vk_comment_id', 'meta_value' => $id, 'status' => 1) );					
					if ( !empty($reviews) )
					{
						$update = array( 'review_text' => trim(stripcslashes($request->object->text)) );						
						usam_update_review( $reviews[0]->id, $update ); 							
					}				
				break;
				case 'wall_reply_delete' :
					$id = absint($request->object->id);							
					if ( $id )
					{
						require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
						$reviews = usam_get_customer_reviews(['meta_key' => 'vk_comment_id', 'meta_value' => $id, 'status' => 1]);
						if ( !empty($reviews) )
						{								
							 usam_delete_review( $reviews[0]->id ); 							
						}	
					}
				break;
				case 'market_order_new' :
					$vk_id = absint($request->object->id);
					if ( $vk_id )
					{
						$order = usam_get_orders(['meta_key' => 'vk_id', 'meta_value' => $vk_id, 'number' => 1]);
						if ( !$order )								
							$this->insert_order( $request->object );	
					}
				break;
				case 'market_order_edit' :
					$vk_id = absint($request->object->id);				
					if ( $vk_id )
					{
						$order = usam_get_orders(['meta_key' => 'vk_id', 'meta_value' => $vk_id, 'number' => 1]);
						if ( !empty($order) )
						{	
							$status = $this->get_status( $request->object->status );	
							usam_update_order( $order['id'], ['status' => $status] ); 							
						}
						else
							$this->insert_order( $request->object );
					}
				break;				
			}
		}	
		exit( 'ok' );
	}
	
	protected function insert_order( $object )
	{			
		$vk_id = absint($object->id);						
		$status = $this->get_status( $object->status );				
		$contact_id = $this->get_or_create_contact( $object->user_id );	
		
		$contact = usam_get_contact( $contact_id );		
		$user_id = !empty($contact)?$contact['user_id']:0;	
		$date = date("Y-m-d H:i:s", absint($object->date));
		$products = [];
		foreach ( $object->preview_order_items as $vk_product )
		{
			$product_id = usam_get_product_id_by_meta( 'vk_market_id_'.$this->profile['code'], $vk_product->item_id );
			$amount = (float)$vk_product->price->amount;
			$products[] = ['product_id' => $product_id, 'price' => $amount/100, 'quantity' => usam_string_to_float($vk_product->quantity)];				
		}
		$order = ['source' => 'vk', 'type_price' => $this->profile['type_price'], 'status' => $status, 'contact_id' => $contact_id, 'user_ID' => $user_id, 'date_insert' => $date];
		$metas = ['vk_id' => $vk_id, 'vk_group_id' => $this->profile['id'], 'vk_id' => $vk_id];
		$order_id = usam_insert_order( $order, $products, $metas );		
		if( $order_id )
		{					
			$payers = usam_get_group_payers(['type' => 'contact']);
			$metas = usam_get_contact_metas( $contact_id );	
			$customer_data = usam_get_webform_data_from_CRM( $metas, 'order', $payers[0]['id'] );			
			if ( $object->comment )
				$customer_data['shippingnotesclient'] = sanitize_textarea_field(stripslashes($object->comment));			
			$customer_data['shippingaddress'] = sanitize_textarea_field(stripslashes($object->address));			
			usam_add_order_customerdata( $order_id, $customer_data );
			do_action('usam_document_order_save', $order_id);				
		}	
	}	
	
	protected function get_status( $vk_status )
	{			
		$vk_status = absint($vk_status);	
		$statuses = [0 => 'received', 1 => 'preparing_delivery', 2 => 'job_dispatched', 3 => 'sending', 4 => 'closed', 5 => 'canceled', 6 => 'canceled'];
		return isset($statuses[$vk_status])?$statuses[$vk_status]:'job_dispatched';
	}	
}
$vk = new USAM_VKontakte_Notifications();
$vk->notifications();	
?>