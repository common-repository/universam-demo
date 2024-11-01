<?php
/**
 * Класс уведомлений менеджера
*/
class USAM_Manager_Notification
{			
	private $template;
	public function __construct() 
	{
		add_action( 'usam_receiving_request_webform', array(&$this, 'notification_feedback'), 10, 4 );
		add_action( 'usam_review_insert', array(&$this, 'notification_review') );
		add_action( 'usam_submit_checkout', array(&$this, 'notification_order') );
		add_action( 'usam_document_shipped_insert', array(&$this, 'notification_shipped_document') );		
		add_action( 'usam_chat_message_insert', array(&$this, 'notification_chat_message') );
		add_action( 'usam_email_insert', array(&$this, 'notification_email_message') );		
		add_action( 'usam_update_stock', array(&$this, 'notification_stock'), 10, 3 );
		
		add_action( 'usam_event_status_changed', array(&$this, 'task_completed'), 10, 4 );			
		add_action( 'usam_ten_minutes_cron_task', [&$this, 'remind_event'], 99 ); 
		add_action( 'usam_set_event_user', [&$this, 'set_event_user'], 10, 2 );
		add_action( 'usam_comment_insert', [&$this, 'notification_comment']);		
	
		add_action( 'usam_update_document_status', [&$this, 'document_status_changed'], 10, 4 );	
		if ( !USAM_DISABLE_INTEGRATIONS ) 	
			add_action( 'usam_begining_work_day', [&$this, 'events_manager_notification']); 
	}	
	
	public function document_status_changed( $document_id, $current_status, $previous_status, $t ) 
	{	
		$author_id = $t->get('manager_id');
		$author = usam_get_contact( $author_id );	
		$document_contacts = usam_get_contacts_document( $document_id );			
		$document = usam_get_document( $document_id );	
		$type = $t->get('type');
		$object =  array('object_type' => $type, 'object_id' => $document_id); 		
		$user_ids = [];
		foreach( $document_contacts as $contact )
		{ 
			if ( $contact->contact_source == 'employee' && $contact->user_id ) 
			{		
				$user_ids[] = $contact->user_id;
				switch ( $type ) 
				{		
					case 'invoice_payment' :
						switch ( $current_status ) 
						{		
							case 'agreement' :	
								$this->add_notification( array('title' => sprintf( __('Вам требуется согласовать счет от %s','usam'), $contact->appeal) ) , $object, $contact->user_id ); 
							break;
						}
					break;			
					case 'decree' :
						switch ( $current_status ) 
						{		
							case 'subscribe' :					
								$this->add_notification( array('title' => sprintf( __('Приказ от №%s подписан.','usam'), $document['number']) ), $object, $contact->user_id ); 
							break;
						}
					break;			
				}	
			}
		}
		$notifications = $this->get_notifications( 'crm' );			
		foreach ( $notifications as $notification ) 
		{		
			foreach ( $notification["contacts"] as $contact ) 
			{
				if ( in_array($contact->user_id, $user_ids) )
				{							
					$message = '';		
					$attachments = array();
					switch ( $type ) 
					{		
						case 'invoice_payment' :
							switch ( $current_status ) 
							{		
								case 'agreement' :
									$title = sprintf( __('Вам требуется согласовать счет &#8220;%s&#8221;','usam'), $t->get('name') );									
									if ( !empty($notification['email']) )
									{
										$subject = sprintf( __('Требуется согласовать счет от инициатора %s','usam'), $author['appeal'] );
										$message  = "<table style='width:100%; border-spacing:0; margin-bottom:20px'>";														
										$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'><a href='".usam_get_document_url( $document )."' style='color:#5c6993;text-decoration: none;'>".__('Чтобы посмотреть счет нажмите здесь','usam')."</a></td></tr>";
										$message .= "</table>";
										$message .= $this->get_table_2column_template( sprintf(__('Дата создания %s','usam'),usam_local_date( $document['date_insert'], "d.m.Y" )), sprintf(__('Инициатор %s','usam'), $author['appeal']) );	
									}
								break;
							}
						break;			
						case 'decree' :
							switch ( $current_status ) 
							{		
								case 'subscribe' :
								/*	$files = usam_get_files( array('object_id' => $document_id, 'type' => 'document' ) );
									if ( !empty($files) )
									{
										$attachments = array();
										foreach ( $files as $file ) 
										{ 		
											$attachments[] = USAM_UPLOAD_DIR.$file->file_path;		
										}
									}	
									*/
									$bank_account = usam_get_bank_account( $document['bank_account_id'] );	
									$company_name = '';
									if ( !empty($bank_account) )
									{
										$company = usam_get_company( $bank_account['company_id'] );	
										$company_name = $company['name'];
									}						
									$pdf = usam_get_pdf_document( $document_id );	
									if ( $pdf )
										$attachments[$document['name'].'.pdf'] = $pdf;
									$title = sprintf( __('Приказ №%s подписан','usam'), $document['number'] );									
									if ( !empty($notification['email']) )
									{
										$subject = sprintf( __('Приказ №%s подписан','usam'), $document['number'] );
										$message = $this->get_section_title_template( sprintf( __('Приказ о %s ','usam'), $document['name'] ) );	
										$message .= "<table style='width:100%; border-spacing:0; margin-bottom:20px'>";
										if ( !empty($attachments) )
										{
											$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'>".__("Вы можете посмотреть приказ во вложении","usam")."</td></tr>";	
										}								
										$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'><a href='".usam_get_document_url( $document )."' style='color:#5c6993;text-decoration: none;'>".__('Чтобы посмотреть приказ нажмите здесь','usam')."</a></td></tr>";
										$message .= "</table>";
										$message .= "<table style='width:100%; border-spacing:0;'>";
										$message .= "<tr><td style='background-color: #6d6a94; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'>".sprintf(__('Приказ по фирме %s','usam'), $company_name )."</td></tr>";
										$message .= "</table>";
										$message .= $this->get_table_2column_template( sprintf(__('Дата создания %s','usam'),usam_local_date( $document['date_insert'], "d.m.Y" )), sprintf(__('Автор приказа %s','usam'), $author['appeal']) );	
									}											
								break;
							}
						break;			
					}
					if ( !empty($notification['email']) && $message )
					{						
						$message = $this->get_email_title_template( $title ).$message;	
						$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email']), $subject, $message, $attachments );
					}
					if ( !empty($notification['phone']) )
					{					
						usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $title ) );
					}
					if ( !empty($notification['messenger']) )
					{									
						$message = $title;
						$message .= "\n\n".__('Чтобы посмотреть перейдите по ссылке:','usam');
						$url = usam_get_document_url( $document );
						$message .= "\n{$url}";
						$this->send_messenger( $contact->id, $notification['messenger'], $message );
					}	
				}
			}
		}
	}
	
	public function notification_comment( $t ) 
	{		
		$notifications = $this->get_notifications( 'crm' );
		if ( !$notifications )
			return false;
		
		$url = '';
		$author_id = $t->get('user_id');	
		$user_ids = [];
		if ( $t->get('object_type') == 'event' )
		{
			$event = usam_get_event( $t->get('object_id') );
			if ( $event )
			{
				$types = usam_get_events_types( );
				$url = usam_get_event_url($event['id'], $event['type']);
				$title = sprintf(__('Новый комментарий у %s &#8220;%s&#8221;','usam'), $types[$event['type']]['genitive'], $event['title']);
				
				$user_ids = usam_get_event_users( $event['id'], false );
				$user_ids[] = $event['user_id'];				
			}
		}
		elseif ( $t->get('object_type') == 'order' )
		{
			$order = usam_get_order( $t->get('object_id') );		
			if ( $order )
			{
				if ( !$order['manager_id'] )
					return false;
				$detail = usam_get_details_document( 'order' );
				$title = sprintf(__('Новый комментарий у %s &#8220;%s&#8221;','usam'), $detail['genitive'], $t->get('object_id'));				
				$user_ids[] = $order['manager_id'];
			}
		}
		elseif ( $t->get('object_type') == 'document' )
		{
			$document = usam_get_document( $t->get('object_id') );			
			if ( $document )
			{
				if ( !$document['manager_id'] )
					return false;
				
				$detail = usam_get_details_document( $document['type'] );
				$title = sprintf(__('Новый комментарий у %s &#8220;%s&#8221;','usam'), $detail['genitive'], $t->get('object_id'));
				$user_ids[] = $document['manager_id'];
			}
		}			
		if ( !$user_ids )
			return false;		
		foreach ( $notifications as $notification ) 
		{			
			foreach ( $notification["contacts"] as $contact ) 
			{
				if ( $contact->user_id != $author_id && in_array($contact->user_id, $user_ids) )
				{				
					if ( !empty($notification['email']) )
					{							
						$message = $this->get_email_title_template( $title );	
						$message .= $this->get_section_title_template( sprintf(__('Комментарий от %s','usam'), usam_get_manager_name($author_id)), $t->get('message') );	
						$message .= "<table style='width:100%; border-spacing:0; margin-bottom:20px'>";														
						$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'><a href='$url' style='color:#5c6993;text-decoration: none;'>".__('Чтобы посмотреть нажмите здесь','usam')."</a></td></tr></table>";
									
						$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email']), __('Получен новый комментарий в CRM','usam'), $message );
					}
					if ( !empty($notification['phone']) )
					{					
						$sent = usam_add_send_sms(['phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $title]);
					}
					if ( !empty($notification['messenger']) )
					{									
						$message = $title;
						$message .= '\n\n'.sprintf( __('Автор %s','usam'), usam_get_manager_name($author_id) );
						$message .= '\n\n'.$t->get('message');
						$message .= "\n\n".__('Чтобы посмотреть перейдите по ссылке:','usam');
						$message .= "\n{$url}";
						$this->send_messenger( $contact->id, $notification['messenger'], $message );
					}
				}
			}
		}
	}
		
	public function events_manager_notification(  ) 
	{	
		$date = date( "Y-m-d H:i:s", current_time('timestamp', 1) );	
		$events = usam_get_events(['status' => 'started', 'date_query' => [['column' => 'start', 'before' => $date, 'inclusive' => true]]]);		
		if ( empty($events) )
			return false;		
		
		$users_events = [];
		foreach ( $events as $event )
		{
			$users = usam_get_event_users( $event->id );
			if ( !empty($users['participant']) )
			{
				foreach ( $users['participant'] as $user_id )
				{
					$users_events[$user_id][] = $event;	
				}	
			}
			else
				$users_events[$event->user_id][] = $event;
		}	
		$notifications = $this->get_notifications( 'crm' );					
		foreach ( $notifications as $notification ) 
		{				
			foreach ( $notification["contacts"] as $contact ) 
			{
				if ( !empty($users_events[$contact->user_id]) )
				{			
					if ( !empty($notification['email']) )
					{						
						$message = $this->get_email_title_template( __('Время пришло выполнять задания','usam') );
						$message .= $this->get_section_title_template( sprintf(__('Всего заданий - %s','usam'),count($users_events[$contact->user_id])) );						
						$events_message = '';
						foreach ( $users_events[$contact->user_id] as $event )
						{
							$events_message .= '<tr><td style="padding:5px 0;margin:0;"><strong>'.sprintf( __('&#8220;%s&#8221;','usam'),'<a href="'.usam_get_event_url( $event->id, $event->type ).'" style="color:#5c6993;text-decoration:none;margin:0;padding:0">'.$event->title.'</a>').'</strong>';			
							if( !empty($event->end) )
							{
								$events_message .= ' '.sprintf( __('срок до %s','usam'), usam_local_date( $event->end ) );
							}
							$events_message .= '</td></tr>';			
						}	
						$message .= "<table style='border-spacing:0; margin:30px 20px; padding:0'>$events_message</table>";
						$this->send_mail( usam_get_contact_metadata($contact->id, $notification['email']), __('Задания на сегодня','usam'), $message );
					}
					if ( !empty($notification['phone']) )
					{															
						$message = sprintf( __('Уведомление с сайта &#8220;%s&#8221;','usam'), get_bloginfo('name'));
						$message .= ' '.sprintf(__('Вам новое %s.', 'usam'), $event->title);
						$sent = usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $message ) );
					}
					if ( !empty($notification['messenger']) )
					{									
						$message = __('Время пришло выполнять задания','usam')."\n";
						$message .= sprintf(__('Всего заданий - %s','usam'),count($users_events[$contact->user_id]));	
						foreach ( $users_events[$contact->user_id] as $event )
						{
							$message .= sprintf( __('&#8220;%s&#8221; %s','usam'),$event->title, usam_get_event_url( $event->id, $event->type ));
							if( !empty($event->end) )
							{
								$message .= ' '.sprintf( __('срок до %s','usam'), usam_local_date( $event->end ) );
							}
							$message .= "\n";			
						}	
						$this->send_messenger( $contact->id, $notification['messenger'], $message );
					}	
				}			
			}
		}
	}
		
	public function remind_event() 
	{			
		$notifications = $this->get_notifications( 'crm' );	
		if ( !$notifications )
			return false;
		
		$events = usam_get_events(['meta_query' => [
			['key' => 'reminder_date_', 'compare_key' => 'LIKE', 'compare' => '<', 'type' => 'DATETIME', 'value' => date("Y-m-d H:i:s", current_time('timestamp', 1)+300)], 
			['relation' => 'OR', ['key' => 'notification', 'compare' => 'NOT EXISTS'], ['key' => 'notification', 'compare' => '=', 'value' => 0]]] 
		]);
		foreach ( $events as $event )
		{		
			usam_update_event_metadata( $event->id, 'notification', 1 );			
			$user_ids = usam_get_event_users( $event->id, false );
			$user_ids[] = $event->user_id;
			foreach ( $notifications as $notification ) 
			{							
				foreach ( $notification["contacts"] as $contact ) 
				{
					if ( in_array($contact->user_id, $user_ids) )
					{					
						$reminder_date = usam_get_event_reminder_date( $contact->user_id );
						if ( empty($reminder_date) )
							continue;
										
						if ( !empty($notification['email']) )
						{						
							$message = $this->get_email_title_template( sprintf( __('Напоминаю о &#8220;%s&#8221;','usam'),$event->title) );			
							if( !empty($event->description) )
							{
								$message .= "<table style='width:100%; border-spacing:0;margin:0; padding:0'>";														
								$message .= "<tr><td style='background-color: #6d6a94; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px; margin:0;'>".__('Описание','usam')."</td></tr>";
								$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 20px;margin:0;'>".$event->description."</td></tr>";
								$message .= "</table>";			
							}
							$end_date = !empty($event->end) ? sprintf( __('Выполнить в срок до %s','usam'), usam_local_date( $event->end ) ) : __("Без срока исполнения","usam");
							$message .= $this->get_table_2column_template( $end_date, sprintf( __('Автор %s','usam'), usam_get_manager_name($event->user_id) ) );
							$message .= "<table style='width:100%; border-spacing:0; margin-bottom:20px'>";														
							$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'><a href='".usam_get_event_url( $event->id, $event->type )."' style='color:#5c6993;text-decoration: none;'>".__('Чтобы посмотреть нажмите здесь','usam')."</a></td></tr>";
							$message .= "</table>";							
							$this->send_mail( usam_get_contact_metadata($contact->id, $notification['email']), __('Напоминание о событии','usam'), $message );
						}
						if ( !empty($notification['phone']) )
						{															
							$message = sprintf( __('Уведомление от &#8220;%s&#8221;','usam'), get_bloginfo('name'));
							$message .= ' '.sprintf(__('Напоминание о %s.', 'usam'), $event->title);
							$sent = usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $message ) );
						}
						if ( !empty($notification['messenger']) )
						{									
							$message = sprintf( __('Напоминаю о &#8220;%s&#8221;','usam'),$event->title)."\n";							
							$message .= !empty($event->end) ? sprintf( __('Выполнить в срок до %s','usam'), usam_local_date( $event->end ) )."\n" : '';
							$message .=  sprintf( __('Автор %s','usam'), usam_get_manager_name($event->user_id) )."\n";
							$message .= usam_get_event_url( $event->id, $event->type );							 		
							$this->send_messenger( $contact->id, $notification['messenger'], $message );
						}	
					}
				}
			}
		}
	}
	
	public function set_event_user( $event_id, $user_id ) 
	{ 
		$notifications = $this->get_notifications( 'crm' );	
		if ( !$notifications )
			return false;
		
		$event = usam_get_event( $event_id );			
		$author_name = __('Неизвестный','usam');
		if ( $event['user_id'] )
		{
			$author = usam_get_contact( $event['user_id'], 'user_id' );		
			if ( !empty($author) )
				$author_name = $author['appeal'];
		}				
		if ( $event['type'] == 'task'  )
		{						
			$this->add_notification(['title' => sprintf( __('Получено задание от %s','usam'), $author_name )] , ['object_type' => $event['type'], 'object_id' => $event_id], $user_id ); 			
		}
		elseif ( $event['type'] == 'event'  )
		{						
			$this->add_notification( array('title' => __('Вы добавлены в дело','usam') ), array('object_type' => $event['type'], 'object_id' => $event_id), $user_id ); 
		}
		elseif ( $event['type'] == 'project'  )
		{						
			$this->add_notification( array('title' => __('Вы добавлены в проект','usam') ), array('object_type' => $event['type'], 'object_id' => $event_id), $user_id ); 
		}	
		elseif ( $event['type'] == 'convocation'  )
		{						
			$this->add_notification( array('title' => __('Вы приглашены на собрание','usam') ) , array('object_type' => $event['type'], 'object_id' => $event_id), $user_id ); 
		}	
		$url = usam_get_event_url( $event['id'], $event['type'] );
		foreach ( $notifications as $notification ) 
		{		
			foreach ( $notification["contacts"] as $contact ) 
			{
				if ( $contact->user_id == $user_id )
				{
					$message = ''; 
					if ( $event['type'] == 'task'  )
					{								
						$title = sprintf(__('Вам новое поручение &#8220;%s&#8221; от %s','usam'), $event['title'], $author_name );
						if ( !empty($notification['email']) )
						{
							$message = $this->get_email_title_template( $title );
							if( !empty($event['description']) )
							{
								$message .= "<table style='width:100%; border-spacing:0;'>";														
								$message .= "<tr><td style='background-color: #6d6a94; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'>".__('Описание поручения','usam')."</td></tr>";
								$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 20px;'>".$event['description']."</td></tr>";
								$message .= "</table>";			
							}						
							$end_date = !empty($event['end']) ? sprintf(__('Выполнить в срок до %s','usam'), usam_local_date( $event['end'] ) ) : __("Без срока исполнения","usam");
							$message .= $this->get_table_2column_template( $end_date, sprintf(__('Поручение от %s','usam'), $author_name ) );
							$subject = sprintf(__('Новое задание &#8220;%s&#8221;','usam'), $event['title'] );	
						}
					}
					elseif ( $event['type'] == 'project' || $event['type'] == 'closed_project' )
					{								
						$title = sprintf( __('Проект &#8220;%s&#8221;. Организатор %s','usam'), $event['title'], $author_name );
						if ( !empty($notification['email']) )
						{
							$message = $this->get_email_title_template( $title );
							if( !empty($event['description']) )
							{
								$message .= "<table style='width:100%; border-spacing:0;'>";														
								$message .= "<tr><td style='background-color: #6d6a94; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'>".__('Описание проекта','usam')."</td></tr>";
								$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 20px;'>".$event['description']."</td></tr>";
								$message .= "</table>";			
							}					
							$end_date = !empty($event['end']) ? sprintf(__('Выполнить в срок до %s','usam'), usam_local_date( $event['end'] ) ) : __("Без срока исполнения","usam");
							$message .= $this->get_table_2column_template( $end_date, sprintf(__('Автор проекта %s','usam'), $author_name ) );
							$subject = __('Вы добавлены в проект','usam');	
						}
					}
					elseif ( $event['type'] == 'meeting' || $event['type'] == 'call')
					{								
						$title = sprintf(__('%s &#8220;%s&#8221;. Организатор %s','usam'), usam_get_event_type_name( $event['type'] ), $event['title'], $author_name );
						if ( !empty($notification['email']) )
						{
							$message = $this->get_email_title_template( $title );
							if( !empty($event['description']) )
							{
								$message .= "<table style='width:100%; border-spacing:0;'>";														
								$message .= "<tr><td style='background-color: #6d6a94; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'>".__('Описание','usam')."</td></tr>";
								$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 20px;'>".$event['description']."</td></tr>";
								$message .= "</table>";			
							}
							$end_date = !empty($event['end']) ? sprintf(__('Выполнить в срок до %s','usam'), usam_local_date( $event['end'] ) ) : __("Без срока исполнения","usam");
							$message .= $this->get_table_2column_template( $end_date, sprintf(__('Автор %s','usam'), $author_name ) );
							$subject = __('Вы стали участником','usam');	
						}
					}					
					elseif ( $event['type'] == 'convocation'  )
					{							
						$title = sprintf(__('Собрание &#8220;%s&#8221;. Организатор %s','usam'), $event['title'], $author_name );
						if ( !empty($notification['email']) )
						{							
							$message = $this->get_email_title_template( $title );
							$venue = usam_get_event_metadata( $event_id, 'venue');	
							if( !empty($venue) )
							{
								$message .= $this->get_section_title_template( __('Место проведения','usam'), $venue );	
							}							
							if( !empty($event['description']) )
							{
								$message .= "<table style='width:100%; border-spacing:0;'>";														
								$message .= "<tr><td style='background-color: #6d6a94; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'>".__('Описание собрания','usam')."</td></tr>";
								$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 20px;'>".$event['description']."</td></tr>";
								$message .= "</table>";			
							}						
							$date = sprintf(__('Будет проведено с %s по %s','usam'), usam_local_date( $event['start']), usam_local_date( $event['end']) );
							$message .= $this->get_table_2column_template( $date, sprintf(__('По инициативе %s','usam'), $author_name ) );
							$subject = __('Вы приглашены на собрание','usam');	
						}
					}
					if ( !empty($notification['email']) && $message )
					{
						$message .= "<table style='width:100%; border-spacing:0; margin-bottom:20px'>";														
						$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'><a href='".$url."' style='color:#5c6993;text-decoration: none;'>".__('Чтобы посмотреть нажмите здесь','usam')."</a></td></tr>";
						$message .= "</table>";		
						$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email']), $subject, $message );
					}
					if ( !empty($notification['phone']) )
					{					
						usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $title ) );
					}
					if ( !empty($notification['messenger']) )
					{									
						$message = $title;
						$message .= "\n{$url}"; 
						$this->send_messenger( $contact->id, $notification['messenger'], $message );
					}	
					break;
				}
			}
		}
	}
	
	public function task_completed( $current_status, $previous_status, $data, $t ) 
	{
		$url = usam_get_event_url( $data['id'], $data['type'] );		
		if ( $data['type'] == 'task' )
		{					
			$users = usam_get_event_users( $data['id'] );
			if ( empty($users['participant']) )
				return false;
			
			$send_users = $users['participant'];
			$send_users[] = $data['user_id'];	
			switch ( $current_status ) 
			{		
				case 'completed' :
					$this->add_notification( array('title' => __('Ваше поручение выполнено','usam')), ['object_type' => 'task', 'object_id' =>  $data['id']], $data['user_id'] ); 
					foreach ( $users['participant'] as $user_id ) 
					{
						$this->add_notification(['title' => __('Поручение выполнено','usam')], ['object_type' => 'task', 'object_id' =>  $data['id']], $user_id ); 
					}
				break;
			}		
			$notifications = $this->get_notifications( 'crm' );
			if ( empty($notifications) )
				return false;											
			
			$current_user_id = get_current_user_id();						
			foreach ( $notifications as $notification ) 
			{	
				foreach ( $notification["contacts"] as $contact ) 
				{
					if ( $current_user_id != $contact->user_id && in_array($contact->user_id, $send_users) )
					{									
						switch ( $data['type'] ) 
						{	
							case 'task' :	
								switch ( $current_status ) 
								{		
									case 'completed' :
										if ( !empty($notification['email']) )
										{										
											$message = $this->get_email_title_template( sprintf( __('Поручение &#8220;%s&#8221; выполнено','usam'), $data['title'] ) );						
											if( !empty($data['description']) )
											{
												$message .= "<table style='width:100%; border-spacing:0;'>";														
												$message .= "<tr><td style='background-color: #6d6a94; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'>".__('Описание поручения','usam')."</td></tr>";
												$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 20px;'>".$data['description']."</td></tr>";
												$message .= "</table>";			
											}				
											if ( !empty($users['participant']) )
											{
												$message .= "<table style='width:100%; border-spacing:0;'>";														
												$message .= "<tr><td style='background-color:#6d6a94; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding:0 20px;'>".__('Исполнители','usam')."</td></tr>";
												foreach ( $users['participant'] as $user_id ) 
												{		
													$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 20px;'>".$contact->appeal."</td></tr>";
												}
												$message .= "</table>";	
											}
											$message .= "<table style='width:100%; border-spacing:0; margin-bottom:20px'>";														
											$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'><a href='".$url."' style='color:#5c6993;text-decoration: none;'>".__('Чтобы посмотреть нажмите здесь','usam')."</a></td></tr></table>";	
											
											$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email']), __('Поручение выполнено','usam'), $message );
										}
										if ( !empty($notification['phone']) )
										{					
											$message = sprintf( __('Поручение &#8220;%s&#8221; выполнено','usam'), $data['title'] );
											$sent = usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $message ) );
										}	
										if ( !empty($notification['messenger']) )
										{									
											$message =  __('Изменение статуса поручения','usam')."\n";
											$message .= sprintf( __('Поручение: %s','usam'), $data['title'] )."\n";
											$message .=  __('Статус: выполнено','usam')."\n";
											$message .= $url;																						
											$this->send_messenger( $contact->id, $notification['messenger'], $message );
										}											
									break;
								}
							break;	
						}
					}
				}
			}
		}
	}
		
	public function notification_feedback( $webform, $event_id, $webform_data, $properties ) 
	{				
		if ( empty($webform_data) )
			return false;
		$notifications = $this->get_notifications( 'feedback' );
		$url = usam_get_event_url($event_id, 'contacting');		
		require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
		$contacting = usam_get_contacting( $event_id );	
		foreach ( $notifications as $notification ) 
		{	
			foreach( $notification["contacts"] as $contact ) 
			{	
				if ( !empty($notification['email']) )
				{										
					$message = $this->get_email_title_template( sprintf(__('Обращение из веб-формы %s','usam'), '«'.$webform['title'].'»' ) );	
					if ( !empty($webform['settings']['fields']) )
					{											
						$message .= "<table style='width:100%; border-spacing:0; margin:0 0 30px 0'>";	
						foreach( $webform['settings']['fields'] as $code => $field )
						{
							foreach( $properties as $key => $property )
							{
								if ( $property->code == $code )
								{
									$message .= "<tr><td style='color:#000;font:600 14px/16px Verdana, Arial, Tahoma;padding:5px;text-align:right; width:250px'>".$property->name."</td><td style='color:#000;font:400 14px/16px Verdana, Arial, Tahoma; padding:5px;'>".$webform_data[$property->code]."</td></tr>";
								}
							}
						}
						$message .= "</table>";							
					}		
					if( !empty($contacting['post_id']) )
					{
						$post = get_post( $contacting['post_id'] );								 
						if( $post )
						{
							if ( $post->object_type=='product' )
							{
								$message .= $this->get_section_title_template( __('Обращение из товара','usam') );								
								$message .= "<table style='width:100%; border-spacing:0; margin-bottom:20px'><tr>";		
								$message .= "<td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px; width:100px;'><img src='".usam_get_product_thumbnail( $post->ID, 'manage-products' )."' width='100'></td>";
								$message .= "<td style='height:40px; padding: 0 20px;'>";
								$message .= "<div class='event_object_title'><a href='".get_permalink( $post->ID )."' style='text-decoration: none; color:#5c6993; font:400 14px/18px Verdana, Arial, Tahoma;'>".$post->post_title."</a></div>";
								$message .= "<div class='product_sku' style='margin:5px 0 0 0;'><strong style='font:600 14px/18px Verdana, Arial, Tahoma'>".__('Артикул', 'usam')."</strong>: <span style='font:400 14px/18px Verdana, Arial'>".usam_get_product_meta( $post->ID, 'sku' )."</span></div>";
								$message .= "<div class='product_price' style='margin:5px 0 0 0;'><strong style='font:600 14px/18px Verdana, Arial, Tahoma'>".__('Цена', 'usam')."</strong>: <span style='font:400 14px/18px Verdana, Arial'>".usam_get_product_price_currency( $post->ID )."</span></div>";
								$message .= "</td></tr></table>";
							}	
							elseif ( $post->object_type=='post' )
							{
								$message .= $this->get_section_title_template( __('Обращение со страницы','usam') );								
								$message .= "<table style='width:100%; border-spacing:0; margin-bottom:20px'><tr>";																								
								$message .= "<td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'>";
								$message .= "<div class='event_object_title'><a href='".get_permalink( $post->ID )."'>".$post->post_title."</a>";
								$message .= "</td></tr></table>";
							}
						}
					}
					$message .= "<table style='width:100%; border-spacing:0; margin-bottom:20px'>";														
					$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'><a href='$url' style='color:#5c6993;text-decoration: none;'>".__('Чтобы посмотреть нажмите здесь','usam')."</a></td></tr></table>";		 
					$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email']), __('Получено обращение от покупателя','usam'), $message );
				}
				if ( !empty($notification['phone']) )
				{					
					$message = sprintf(__('Новое обращение из веб-формы %s','usam'), $webform['title']);
					$sent = usam_add_send_sms(['phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $message]);
				}	
				if ( !empty($notification['messenger']) )
				{									
					$message =  sprintf(__('Новое обращение из веб-формы %s','usam'), '«'.$webform['title'].'»' );
					$message .= '\n\n'.__('Чтобы посмотреть перейдите по ссылке:','usam');
					$message .= "\n{$url}";
					
					$message_webform = '';
					foreach( $webform['settings']['fields'] as $code => $field )
					{
						foreach( $properties as $key => $property )
						{
							if ( $property->code == $code )
								$message_webform .= "\n<b>{$property->name}:</b> ".$webform_data[$property->code];
						}
					}
					if ( $message_webform )
						$message .= "\n".$message_webform;
					if( !empty($contacting['post_id']) )
					{
						$post = get_post( $contacting['post_id'] );		
						if( $post )
						{
							if ( $post->object_type=='product' )
							{
								$message .= "\n\n<b>".mb_strtoupper( __('Обращение из товара','usam') )."</b>";
								$message .= "\n".$post->post_title;
								$message .= "\n".get_permalink( $post->ID );
								$message .= "\n".__('Артикул', 'usam').": ".usam_get_product_meta( $post->ID, 'sku' );
								$message .= "\n".__('Цена', 'usam').": ".usam_get_product_price_currency( $post->ID );
							}	
							elseif ( $post->object_type=='post' )
							{
								$message .= "\n\n<b>". mb_strtoupper(__('Обращение со страницы','usam'))."</b>";
								$message .= "\n".$post->post_title." ".get_permalink( $post->ID );
							}
						}
					}					
					$this->send_messenger( $contact->id, $notification['messenger'], $message );
				}				
			}
		}
	}
	
	public function notification_review( $t ) 
	{		
		$notifications = $this->get_notifications( 'feedback' );
		$url = admin_url('admin.php')."?page=feedback&tab=reviews";
		foreach ( $notifications as $notification ) 
		{
			foreach ( $notification["contacts"] as $contact ) 
			{
				if ( !empty($notification['email']) )
				{							
					$message = $this->get_email_title_template( __('Получен новый отзыв','usam') );	
					$message .= $this->get_section_title_template( __('Название отзыва','usam'), $t->get('title') );	
					$message .= "<table style='width:100%; border-spacing:0; margin-bottom:20px'>";														
					$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'><a href='$url' style='color:#5c6993;text-decoration: none;'>".__('Чтобы посмотреть нажмите здесь','usam')."</a></td></tr></table>";
								
					$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email'] ), __('Получено сообщение от покупателя','usam'), $message );
				}
				if ( !empty($notification['phone']) )
				{					
					$message = __('Получен новый отзыв посетителя.','usam');
					$sent = usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $message ) );
				}
				if ( !empty($notification['messenger']) )
				{									
					$message = __('Получен новый отзыв.');
					$message .= '\n\n'.__('Чтобы посмотреть перейдите по ссылке:','usam');
					$message .= "\n{$url}";
					$this->send_messenger( $contact->id, $notification['messenger'], $message );
				}
			}
		}
	}	
				
	public function notification_chat_message( $t ) 
	{				
		$contact_id = $t->get('contact_id');
		$contact = usam_get_contact( $contact_id );	 
		if ( $contact['contact_source'] == 'employee' )
			return false;		
		
		$notifications = $this->get_notifications( 'chat' );
		$url = admin_url('admin.php')."?page=feedback&tab=chat&sel=".$t->get('dialog_id');
		$code = "usam-chatmessage-".$t->get('dialog_id');	
		foreach ( $notifications as $notification ) 
		{
			foreach ( $notification["contacts"] as $contact ) 
			{ 
				if ( !empty($notification['email']) )
				{						
					$message = $this->get_email_title_template( __('Новое сообщение в чате:','usam') );
					$message .= "<table style='width:100%; border-spacing:0;'>";														
					$message .= "<tr><td style='background-color: #6d6a94; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'>".__('Сообщение','usam')."</td></tr>";
					$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 20px;'>".$t->get('message')."</td></tr>";
					$message .= "</table>";					
					$message .= "<table style='width:100%; border-spacing:0; margin-bottom:20px'>";														
					$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'><a href='$url' style='color:#5c6993;text-decoration: none;'>".__('Чтобы посмотреть нажмите здесь','usam')."</a></td></tr></table>";
					$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email']), __('Получено сообщение от покупателя','usam'), $message ); 
				}
				if ( !empty($notification['phone']) )
				{					
					$message = sprintf( __('Уведомление с сайта &#8220;%s&#8221;','usam'), get_bloginfo('name'));
					$message .= ' '.__('Новое сообщение в чате.');
					$sent = usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $message ) );
				}
				if ( !empty($notification['messenger']) )
				{												
					$message = __('Получено новое сообщение в чат:');
					$message .= '\n\n'.$t->get('message');
					$message .= '\n\n'.__('Чтобы открыть диалог нажмите:','usam');
					$message .= "\n{$url}";					
					$message .= "\n".__('Код для ответа','usam');
					$message .= "\n".$code;
					$this->send_messenger( $contact->id, $notification['messenger'], $message );
				}
			}
		}
	}
	
	public function notification_email_message( $t ) 
	{				
		if ( $t->get('type') != 'inbox_letter' )
			return false;
			
		if ( $t->get('folder') == 'attached' )
			return false;
				
		$mailbox_user_ids = usam_get_mailbox_users( $t->get('mailbox_id') );
		
		$notifications = $this->get_notifications( 'email' );
		$url = admin_url('admin.php')."?page=feedback&email_id=".$t->get('id');
		$code = "usam-emailmessage-".$t->get('id');
		foreach ( $notifications as $notification ) 
		{
			foreach ( $notification["contacts"] as $contact ) 
			{
				if ( !in_array($contact->user_id, $mailbox_user_ids) )
					continue;
				
				if ( !empty($notification['email']) )
				{						
					$message = $this->get_email_title_template( __('Новое письмо:','usam') );
					$message .= "<table style='width:100%; border-spacing:0;'>";														
					$message .= "<tr><td style='background-color: #6d6a94; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'>".__('Сообщение','usam')."</td></tr>";
					$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 20px;'>".$t->get('body')."</td></tr>";
					$message .= "</table>";					
					$message .= "<table style='width:100%; border-spacing:0; margin-bottom:20px'>";														
					$message .= "<tr><td style='color:#000; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;'><a href='$url' style='color:#5c6993;text-decoration: none;'>".__('Чтобы посмотреть нажмите здесь','usam')."</a></td></tr></table>";
					$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email']), __('Получено новое письмо','usam'), $message );
				}
				if ( !empty($notification['phone']) )
				{					
					$message = sprintf( __('Уведомление с сайта &#8220;%s&#8221;','usam'), get_bloginfo('name'));
					$message .= ' '.__('Новое письмо.');
					$message .= "\n".sprintf(__('от %s'),$t->get('from_name').' '.$t->get('from_email'));
					$sent = usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $message ) );
				}
				if ( !empty($notification['messenger']) )
				{									
					$message = __('Новое письмо с темой:');
					$message .= '\n\n'.$t->get('title');
					$message .= "\n".sprintf(__('от %s'),$t->get('from_name').' '.$t->get('from_email'));
					$message .= '\n\n'.__('Чтобы прочитать нажми:','usam');
					$message .= "\n{$url}";
					$message .= "\n".__('Код для ответа','usam');
					$message .= "\n".$code;
					$this->send_messenger( $contact->id, $notification['messenger'], $message );
				}
			}
		}
	}
	
	public function notification_order( $order_id ) 
	{		
		$t = new USAM_Order( $order_id );		
		$source = $t->get('source');
		if ( $source == 'manager' )
			return false;
		
		$notifications = $this->get_notifications( 'order' );
		foreach ( $notifications as $notification ) 
		{	
			foreach ( $notification["contacts"] as $contact ) 
			{
				$type_price = $t->get('type_price');			
				foreach ( $notification['events']['order']['conditions'] as $type => $value ) 
				{ 
					if ( $type == 'prices' && !( in_array('', $value) || in_array($type_price, $value) ) )
					{		
						return false;
					}				
				}								
				if ( !empty($notification['email']) )
				{						
					$order_notification = new USAM_Order_Admin_Notification( $t );		
					$message = $order_notification->get_html_message();
					$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email']), __('Получен новый заказ','usam'), $message );
				}			
				if ( !empty($notification['phone']) )
				{		
					$message = sprintf( __('Новый заказ №%s на сумму %s.'), $order_id, $t->get('totalprice'));
					$sent = usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $message ) );
				} 
				if ( !empty($notification['messenger']) )
				{									
					$message = sprintf( __('Новый заказ №%s на сумму %s.'), $order_id, $t->get('totalprice'));
					$message .= "\n".admin_url('admin.php')."?page=orders&form=view&form_name=order&id=".$order_id."";
					$this->send_messenger( $contact->id, $notification['messenger'], $message );
				}
			}
		}
	}
	
	public function notification_shipped_document( $t )
	{		
		$notifications = $this->get_notifications( 'shipped_document' );		
		$data = $t->get_data();
		if ( $data['courier'] )
		{
			$courier = usam_get_contact( $data['courier'], 'user_id' );
			$note = usam_get_shipped_document_metadata($data['id'], 'note');
			$date_delivery = usam_get_shipped_document_metadata( $data['id'], 'date_delivery' );
			foreach ( $notifications as $notification ) 
			{					
				foreach ( $notification["contacts"] as $contact ) 
				{											
					if ( $courier['id'] != $contact->id )
						continue;
					
					$property_types = usam_get_order_property_types( $data['order_id'] );		
					$message = sprintf( __('Вам назначена новая отгрузка %s. Адрес %s'), $t->get('id'), $property_types['delivery_address']['_name']);
					$message .= $date_delivery?"/n".get_date_from_gmt($date_delivery, "H:i"):'';			
					$message .= $note?"/n".__("УКАЗАНИЯ","usam").': '.$note:'';							
					if ( !empty($notification['email']) )
					{										
						$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email']), __('Получена новая отгрузка','usam'), $message );
					}			
					if ( !empty($notification['phone']) )
					{		
						$sent = usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $message ) );
					} 
					if ( !empty($notification['messenger']) )
					{
						$this->send_messenger( $contact->id, $notification['messenger'], $message );
					}
				}
			}
		}	
	}
	
	public function notification_stock( $product_id, $stock, $old_stock ) 
	{				
		if ( $stock < 3 )
		{
			$notifications = $this->get_notifications( 'low_stock' );		
			foreach ( $notifications as $notification ) 
			{						
				if ( $notification['events']['low_stock']['stock'] <= $stock )
				{
					continue;
				}				
				foreach ( $notification["contacts"] as $contact ) 
				{
					$compare = new USAM_Compare();		
					foreach ( $notification['events']['low_stock']['conditions'] as $type => $c ) 
					{ 
						if ( $type == 'category' )
						{							
							if ( !in_array('', $c) )
							{
								$result = $compare->compare_terms( $product_id, 'usam-category', $c );
								if ( !$result )
									break;
							}
						}			
						elseif ( $type == 'category_sale' )
						{	
							$result = $compare->compare_terms( $product_id, 'usam-category_sale', $c );
							if ( !$result )
								break;
						}		
						elseif ( $type == 'brads' )
						{	
							$result = $compare->compare_terms( $product_id, 'usam-brads', $c );
							if ( !$result )
								break;
						}				
					}			
					if ( !empty($notification['email']) )
					{	
						$message = $this->get_email_title_template( sprintf(__('У товара %s (артикул %s) не большой запас', 'usam'), get_the_title( $product_id ), usam_get_product_meta( $product_id, 'sku' )) );
						$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email']), __('Не большой запас', 'usam'), $message );
					}
					if ( !empty($notification['phone']) )
					{					
						$message = sprintf(__('%s - не большой запас'), get_the_title( $product_id ));		
						$sent = usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $message ) );
					}
				}
			}
		}
		elseif ( $old_stock > 0 && $stock <= 0 )
		{
			$notifications = $this->get_notifications( 'no_stock' );		
			foreach ( $notifications as $notification ) 
			{						
				foreach ( $notification["contacts"] as $contact ) 
				{
					$compare = new USAM_Compare();		
					foreach ( $notification['events']['no_stock']['conditions'] as $type => $c ) 
					{ 
						if ( $type == 'category' )
						{				
							if ( !in_array('', $c) )
							{  
								$result = $compare->compare_terms( $product_id, 'usam-category', $c );
								if ( !$result )
									break;
							}					
						}			
						elseif ( $type == 'category_sale' )
						{	
							$result = $compare->compare_terms( $product_id, 'usam-category_sale', $c );
							if ( !$result )
								break;
						}		
						elseif ( $type == 'brads' )
						{	
							$result = $compare->compare_terms( $product_id, 'usam-brads', $c );
							if ( !$result )
								break;
						}				
					}			
					if ( !empty($notification['email']) )
					{									
						$message = $this->get_email_title_template( sprintf(__('Товар %s (артикул %s) распродан', 'usam'), get_the_title( $product_id ), usam_get_product_meta( $product_id, 'sku' )) );
						$this->send_mail( usam_get_contact_metadata( $contact->id, $notification['email']), __('Товар распродан', 'usam'), $message );
					}
					if ( !empty($notification['phone']) )
					{					
						$message = sprintf(__('%s - распродан'), get_the_title( $product_id ));		
						$sent = usam_add_send_sms( array( 'phone' => usam_get_contact_metadata( $contact->id, $notification['phone']), 'message' => $message ) );
					}
				}
			}	
		}
	}

	private function get_notifications( $type ) 
	{
		static $results = array();
		
		if ( isset($results[$type]) )
			return $results[$type];
		
		$results[$type] = [];
		
		$option = get_site_option('usam_notifications');
		$notifications = maybe_unserialize( $option );	
		if ( empty($notifications) )
			return array();	
		
		foreach ( $notifications as $notification ) 
		{
			if ( $notification['active'] )
			{	
				if ( empty($notification['events'][$type]['email']) )
					unset($notification['email']);
				if ( empty($notification['events'][$type]['sms']) )
					unset($notification['phone']);
				if ( empty($notification['events'][$type]['messenger']) )
					unset($notification['messenger']);
				
				if ( !isset($notification['email']) && !isset($notification['phone']) && !isset($notification['messenger']) )
					continue;
				if ( !empty($notification["contacts"]) )
					$notification["contacts"] = usam_get_contacts(['source' => 'all', 'include' => $notification["contacts"], 'cache_meta' => true]);
				if ( !empty($notification["contacts"]) )
					$results[$type][] = $notification;				
			}
		}		
		return $results[$type];
	}	
	
	private function get_message( $message )
	{		
		$args = [		
			'mailcontent' => $message,		
			'signature' => get_bloginfo('name'),
		];		
		if ( $this->template == '' )
		{
			ob_start();	
			require_once( USAM_FILE_PATH . '/includes/mailings/notification_email_template.php'  );	
			$this->template = ob_get_clean();
		}	
		$shortcode = new USAM_Shortcode();
		return $shortcode->process_args( $args, $this->template );		
	}
	
	public function send_messenger( $contact_id, $messenger, $message, $attachments = array() ) 
	{					
		if ( !USAM_DISABLE_INTEGRATIONS ) 
		{
			$type_socials = ['vk_id' => 'vk_user', 'facebook_user_id' => 'facebook_user', 'viber_user_id' => 'viber', 'telegram_user_id' => 'telegram'];		
			$type_social = isset($type_socials[$messenger])?$type_socials[$messenger]:'';		
			$social_network = (array)usam_get_social_network_profiles(['type_social' => $type_social, 'number' => 1]);			
			$result = usam_send_message_to_messenger( $social_network, ['contact_id' => $contact_id, 'message' => $message]);
		}
		else
			usam_log_file( "contact_id=".$contact_id."\n".$message, 'messenger' );
	}
	
	public function add_notification( $data, $object, $user_ids ) 
	{
		if ( !is_array($user_ids) )
			$user_ids = array( $user_ids );
		
		$current_user_id = get_current_user_id();
		foreach ( $user_ids as $key => $user_id ) 
		{
			if ( $current_user_id == $user_id )
				unset($user_ids[$key]);
		}		
		if ( !empty($user_ids) )
			usam_add_notification( $data, $object, $user_ids );
	}
		
	public function send_mail( $address, $subject, $message, $attachments = array() ) 
	{			
		$message = $this->get_message( $message );		
		return usam_mail( $address, $subject, $message, '', $attachments );
	}	
	
	private function get_email_title_template( $header )
	{	
		$html = "<table style='background-color:#6d6a94;color: #fff; width:100%; margin:0 0 30px 0;'><tr style='padding:0px; margin:0px;>";	
		$html .= "<td style='text-align:center;width:81px;padding:30px 20px; margin:0px;'></td>";	
		$html .= "<td style='font: 400 20px/23px Verdana, Arial, Tahoma;padding:30px 20px;margin:0px;'>" . $header . "</td>";	
		$html .= "</tr></table>";
		return $html;
	}
	
	private function get_section_title_template( $text, $description = '' )
	{	
		$html = "<table style='margin:20px 0; padding:0px; width:100%; border-spacing:0; background-color:#f3f2f5; border-left: #9591ba 3px solid;'>";	
		$html .= "<tr><td style='padding:14px 0 0 20px; margin:0px; font:400 32px/40px Verdana, Arial, Tahoma;color:#000; text-align:left;'>".$text."</td></tr>";
		$html .= "<tr><td style='padding:0 0 19px 20px; margin:0px; font:400 17px/23px Verdana, Arial, Tahoma; color:#000;  text-align:left;'>";
		if ( $description )
			$html .= $description;		
		$html .= "</td></tr>";
		$html .= "</table>";
		return $html;
	}
	
	private function get_table_2column_template( $text1, $text2 )
	{	
		$html = "<table style='width:100%; border-spacing:0;'><tr>";								
		$html .= "<td style='background-color: #8582ab; border-right: #7874a2 2px solid; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px; margin:0px;'>".$text1."</td>";
		$html .= "<td style='background-color: #7d79a5; color:#fff; height:40px; font:400 14px/18px Verdana, Arial, Tahoma; padding: 0 20px;  margin:0px;'>".$text2."</td>";
		$html .= "</tr></table>";
		return $html;
	}
}
new USAM_Manager_Notification();
?>