<?php
/*
Ниже перечислены поля, которые могут присутствовать в заголовке сообщения и их описание.

Date - Дата и время создания сообщения, когда сообщение готово и может быть отослано.
From - Разделенные запятой почтовые адреса авторов сообщения. В случае, если адресов несколько, должно быть поле Sender.
Sender - Почтовый адрес отправителя. Если поле From содержит один адрес, то поле Sender может отсутствовать. Если значения полей Sender и From совпадают, то поле Sender должно отсутствовать.
Reply-to - Почтовый адрес, на который автор сообщения желал бы получить ответ.
To - Почтовые адреса основных адресатов. Если адресов несколько - они разделяются запятыми.
Cc - Копии. Почтовые адреса других адресатов. Если адресов несколько - они разделяются запятыми.
Bcc - Слепые копии. Почтовые адреса адресатов, которые будут не видны другими адресатами, получающим это сообщение. Если адресов несколько - они разделяются запятыми.
Message-id - Поле предоставляет уникальный идентификатор сообщения. Идентификатор уникален для всего мира.
In-Reply-To - Содержит идентификатор оригинального сообщения, на которое делается ответ.
References - Содержит идентификатор оригинального сообщения, на которое делается ответ.
Subject - Тема сообщения.
Comments - Содержит дополнительные комментарии к сообщению.
Keywords - Ключевые слова и важные слова, которые могут быть полезны адресату.
Resent-Date, Resent-From, Resent-Sender, Resent-To, Resent-Cc, Resent-Bcc, Resent-Message-Id - Используются при пересылке сообщения. Эти поля содержат информацию, измененную тем, кто производил пересылку.
Return-Path - Почтовый адрес, проставляемый SMTP-сервером на стадии финальной отсылки. Чаще всего используется для доставки отчета с описанием возникшей ошибки.
Received - Используется для идентификации SMTP-серверов, которые принимали участие в отправке сообщения от отправителя к получателю. Каждый SMTP-сервер добавляет свое поле.
Encrypted - Указывает на то, что сообщение было подвергнуто шифрованию.
MIME-Version - Содержит версию MIME. Дополнительную информацию можно получить из документов RFC 2045, RFC 2046, RFC 2047, RFC 2048, RFC 2049.
Content-Type - Значением этого поля является наиболее полная информация о содержимом сообщения, которая позволяет почтовому клиенту выбрать соответствующий механизм обработки.
Content-Transfer-Encoding - Указывается способ помещения двоичных данных в тело сообщения.
Поля начинающиеся с X- - Дополнительное незарегистрированное поле. Разные почтовые клиенты могут использовать разные незарегистрированные поля для собственных нужд.
*/
class USAM_Email_Loading
{
	protected $server = null;
	protected $mailbox = [];	
	protected $letterCount = 0; // Количество писем на сервере	
	protected $errors = [];

	public function __construct( $mailbox_id )
	{		
		if ( usam_is_license_type('LITE') || usam_is_license_type('FREE') )
			return false;
				
		$mailbox_id = (int)$mailbox_id;
		$this->mailbox = usam_get_mailbox( $mailbox_id );
		$metas = usam_get_mailbox_metadata( $mailbox_id );
		foreach($metas as $metadata )
			$this->mailbox[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);	
		if ( $this->check_settings() )
		{			
			if ( $this->connect_server() ) 
			{	
				if ( $this->authPop3Server() ) 
					$this->startWork();	
			}
		}		
	}
	
	public function check_settings()
	{
		return false;
	}
	
	public function set_error( $error )
	{
		if ( !empty($this->mailbox['pop3user']) )
			$this->errors[] = sprintf( __('POP3. Пользователь: %s. Ошибка: %s'), $this->mailbox['pop3user'], $error );
		else
			$this->errors[] = sprintf( __('POP3. Ошибка: %s'), $error );
	}
	
	public function set_log_file()
	{
		usam_log_file( $this->errors );
	}
	
	public function get_message_errors()
	{	
		return $this->errors;
	}	
	
	public function connect_open()
	{	
		return $this->server ? true : false;
	}
		
	protected function filtering( $filters, $mail )	
	{		
		foreach ( $filters as $filter )
		{				
			if ( $filter->mailbox_id && $mail['mailbox_id'] != $filter->mailbox_id )
			{		
				continue;
			}		
			$value = '';
			switch ( $filter->if ) 
			{
				case 'sender' : 
					$value = $mail['from_email'];
				break;
				case 'recipient' : 
					$value = $mail['to_email'];
				break;
				case 'copy' : 
					$value = $mail['reply_to_email'];
				break;
				case 'subject' : 
					$value = $mail['title'];
				break;
				case 'size' : 
					$value = $mail['size'];
				break;
			}						
			$compare = new USAM_Compare();
			if ( $compare->compare_string($filter->condition, $value, $filter->value) )
			{
				switch ( $filter->action ) 
				{
					case 'read' : 
						$mail['read'] = 1;
					break;
					case 'important' : // Пометить письмо важным
						$mail['importance'] = 1;
					break;
					case 'delete' : 
						return true;
					break;
					case 'folder' : 
						$mail['folder'] = $filter->folder;
					break;
				}
				break;				
			}
		}
		return false;
	}
		
	protected static function get_email_filters()	
	{
		$object_type = 'usam_email_filters';	
		if( ! $filters = wp_cache_get( $object_type ) )			
		{				
			require_once( USAM_FILE_PATH . '/includes/mailings/email_filters_query.class.php' );
			$filters = usam_get_email_filters( );
			wp_cache_set( $object_type, $filters );						
		}
		return $filters;
	}
		
	protected function get_folder()	
	{			
		$cache_key = 'usam_email_folder_attachments';
		$folder_id = wp_cache_get( $this->mailbox['id'], $cache_key ); 
		if( $folder_id === false )	
		{
			$folder_id = usam_get_folder_object( $this->mailbox['email'], 'email', __("Почта","usam") );
			wp_cache_set( $this->mailbox['id'], $folder_id, $cache_key );
		}
		return $folder_id;
	}	
		
	public function get_letter_count()
	{
		return $this->letterCount;
	}
		
	protected function decode_text( $return_text, $search ) 
	{			
		if ( strripos($search, "koi8") !== false) 
		{
			$return_text = iconv ('KOI8-R', 'UTF-8', $return_text);			
		}   
		elseif ( strripos( $search, "Windows-1251" ) !== false )
		{
			$return_text = iconv ('windows-1251', 'UTF-8', $return_text);			
		}
		elseif ( strripos( $search, "iso-8859-5" ) !== false )
		{  		
			$return_text = iconv ('iso-8859-5', 'UTF-8', $return_text);	
		}				
		return $return_text;
	}
	
	protected function insert_email( $uid, $data, $attachments ) 
	{				
		$objects = [];
		$links = [];				
		if( preg_match_all('/<(p|span) class="checkcheck"(.*?)<\/(p|span)>/si', $data['body'], $matches) )
		{
			foreach ($matches[0] as $str ) 
				$data['body'] = str_replace($str, "", $data['body']);
		}
		if( preg_match('/<(p|span) class="sendemailid".*?>([0-9]+)<\/(p|span)>/si', $data['body'], $matches) )
		{			
			$reply_message_id = (int)$matches[2];
			$objects[] = ['object_id' => $reply_message_id, 'object_type' => 'email'];			
			$data['body'] = str_replace($matches[0], "", $data['body']);
		}		
		if( preg_match('/<(p|span) class="sendemailobject".*?>([a-z]+-[0-9]+)<\/(p|span)>/si', $data['body'], $matches) )
		{				
			$result = explode('-', $matches[2]);
			if ( !empty($result[0]) && !empty($result[1]) && is_numeric($result[1]) ) 
				$links[] = ['object_id' => $result[1], 'object_type' => $result[0]];
			$data['body'] = str_replace($matches[0], "", $data['body']);
		}			
		$contact_ids = usam_get_contact_ids_by_field('email', $data['from_email']);
		foreach ($contact_ids as $contact_id )
			$links[] = ['object_id' => $contact_id, 'object_type' => 'contact'];	
		
		$company_ids = usam_get_company_ids_by_field('email', $data['from_email']);
		foreach ($company_ids as $company_id )
			$links[] = ['object_id' => $company_id, 'object_type' => 'company'];					
		$email_id = usam_insert_email($data, [], $links);	
		if ( $email_id )
		{
			if ( !empty($objects) )
			{
				foreach ( $objects as $object )
					usam_set_email_object( $email_id, $object);
			}			
			global $wpdb;
			usam_update_email_metadata( $email_id, 'server_message_id', $uid );	
			$wpdb->insert( USAM_TABLE_LOADED_MESSAGES_LOG, ['letter_id' => $uid, 'mailbox_id' => $this->mailbox['id']]);	
			if ( !empty($attachments) )
			{														
				$dir = "e-mails/$email_id/";
				if( !is_dir(USAM_UPLOAD_DIR.$dir) )
				{
					if (!mkdir(USAM_UPLOAD_DIR.$dir, 0777, true)) 
						return false;
				}							
				$update = false;						
				$folder_id = $this->get_folder();
				foreach ( $attachments as $attachment )
				{
					if ( empty($attachment['file']) )
						continue;
					
					if ( empty($attachment['filename']) )
						$attachment['filename'] = $data['title'];											
											
					$filename = sanitize_file_name( usam_sanitize_title_with_translit($attachment['filename']) );
					$filename = wp_unique_filename( USAM_UPLOAD_DIR.$dir, $filename ); // Уникальное имя							
					
					file_put_contents(USAM_UPLOAD_DIR.$dir.$filename, $attachment['file'] ); 
					if ( !empty($attachment['filename']) )
						$title = $attachment['filename'];
					else
					{
						$file_ext = usam_get_extension( USAM_UPLOAD_DIR.$dir.$filename );
						$title = basename($attachment['filename'], '.' . $file_ext );
					}	
					$file_id = usam_insert_file(['object_id' => $email_id, 'title' => $title, 'name' => $filename, 'type' => $attachment['type'], 'file_path' => $dir.$filename, 'folder_id' => $folder_id]);
					if ( $attachment['type'] == 'R' )
					{
						$parent = explode('@', $attachment['content_id']);			
						$data['body'] = str_replace( 'src="'.$parent[0].'"', 'src="'.$file_id.'"', $data['body'] );									
						$update = true;
					}
				} 
				if ( $update )
					usam_update_email($email_id, ['body' => $data['body']]);
			}
			$data['id'] = $email_id;
			do_action( 'usam_new_letter_received', $data, $this->mailbox );
			if ( $this->mailbox['delete_server'] && empty($this->mailbox['delete_server_day']) )
				$this->delete_messages( $uid );	
			
			wp_cache_delete( $email_id, 'usam_email' );	
			wp_cache_delete( $email_id, 'usam_email_attachments' );
		}
		return $email_id;
	}
}
?>