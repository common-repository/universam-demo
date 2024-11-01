<?php
//http://wincode.org/php/working-with-pop3-in-php
require_once(USAM_FILE_PATH.'/includes/mailings/email-loading.php');
class USAM_POP3 extends USAM_Email_Loading
{
	public function check_settings()
	{	
		return !empty($this->mailbox['pop3server']) && !empty($this->mailbox['pop3pass']);
	}
	
	protected function connect_server()
	{			
		if ( !$this->check_server() )
		{
			try 
			{
				$server = $this->mailbox['pop3server'];
				if ( !empty($this->mailbox['pop3ssl']) ) 
					$server = 'ssl://'.$server;	
				$server = @fsockopen($server, $this->mailbox['pop3port'], $errno, $errstr, 30);
				if ( !$server ) 
					$this->set_error( sprintf(__("Сервер не найден. Ошибка: %s %s","usam"), $errno, $errstr) );
				else
				{		
					$this->server = $server;
					$result = fgets($this->server, 1024);			
					if (strpos($result, 'OK')) 
						return true;
					else 			
						$this->set_error( __('Ошибка подключения к pop3-серверу',"usam") );			
				}
			} 
			catch (PDOException $e) 
			{				
				$this->set_error( $e->getMessage() );
			}			
		}
		else
			return true;
		return false;
	}

// Отключение от сервера POP3
	public function disconnect()
	{
		if ( $this->server ) 
		{
			$this->send_cmd( "QUIT" );
			fclose( $this->server );
			$this->server = null;
		}
	}

	//пинг серверу
	public function check_server()
	{
		if ( $this->connect_open() ) 
		{
			$result = $this->send_cmd( "NOOP" );
			if ( strpos($result, 'OK') ) 
				return true;
		}
		else
			return false;
	}	
	
	//Сброс сеанса
	public function reset_session()
	{
		if ( $this->connect_open() ) 
			$this->send_cmd( "RSET" );
	}		
			
// Авторизация на сервере
	protected function authPop3Server()
	{
		$result = $this->send_cmd( "USER ".$this->mailbox['pop3user'] );			
		if (strpos($result, 'OK')) 
		{
			$result = $this->send_cmd( "PASS ".$this->mailbox['pop3pass'] );
			if ( strpos($result, 'OK') ) 
				return true;
			else
				$this->set_error( __('Неверный пароль или закрыт доступ по протоколу POP в вашем почтовом ящике.','usam') );
		} 
		else
			$this->set_error( __('Ошибка авторизации','usam') );
			
		return false;
	}
	
	
	public function send_cmd( $cmd )
	{		
		if ( $this->server && $cmd )
		{
			@fwrite($this->server, $cmd."\r\n");
			$result = fgets($this->server, 1024);
			$result = $this->strip_clf( $result );			
		}
		else
			$result = false;		
		return $result;
	}
	
	// Получить i строк письма c id
	public function get_top_message( $id, $i = 0 )
	{		
		$this->send_cmd( "TOP $id $i" );
		return $this->get_data();
	}	
	
	function strip_clf( $text = "" ) 
	{
		if( empty($text) )
			return $text;
		else 
		{
			$stripped = str_replace(array("\r","\n"),'',$text);
			return $stripped;
		}
	}
	
	//оказывает общую информацию и размер каждого письма. 
	public function get_list_mail()
	{		
		$result = $this->send_cmd("LIST");
		$lists = array();
		if ( strpos($result, 'OK') )
		{ 
			$text = $this->get_data( );				
			$rows = explode ("\n", $text);			
			foreach ( $rows as $row )
			{
				$lists[] = explode(" ", $row);				
			}
		} 
		else
			$this->set_error( __('Не удалось получить информацию из pop3 сервера!','usam') );		
		return $lists;
	}
	
	//оказывает общую информацию и размер каждого письма. 
	public function get_list_mail_ids()
	{		
		if ( !$this->server ) 
			return false;
		$result = $this->send_cmd( "UIDL" );
		$lists = array();
		if ( strpos($result, 'OK') )
		{  
			$text = $this->get_data( );			
			$rows = explode ("\r\n", $text);			
			foreach ( $rows as $row )
			{
				$result = explode(' ', $row);
				if ( !empty($result[1]) )					
					$lists[$result[1]] = (int)$result[0];			
			}  
		} 
		else
		{
			$this->set_error( __('Не удалось получить информацию! Проверьте настройки. Возможно вам нужно разрешить доступ по протоколу POP3.','usam') );
			return false;
		}	
		return $lists;
	}	

	public function download_messages()	
	{  
		global $wpdb;
		$mail_ids = $this->get_list_mail_ids();		
		if ( $mail_ids === false )
			return false;		
		elseif ( empty($mail_ids) )
			return true;
			
		set_time_limit(1800);			
		$filters = self::get_email_filters();
		usam_cache_properties(['contact', 'company']);

		$mailbox_user_ids = usam_get_mailbox_users( $this->mailbox['id'] );		
		$letter_ids = $wpdb->get_col( "SELECT letter_id FROM ".USAM_TABLE_LOADED_MESSAGES_LOG." WHERE mailbox_id=".$this->mailbox['id'] );	
		if ( !$letter_ids )
		{
			$letter_ids = $wpdb->get_col( "SELECT meta.meta_value FROM ".USAM_TABLE_EMAIL_META." AS meta LEFT JOIN ".USAM_TABLE_EMAIL." AS email ON (email.id = meta.email_id AND email.mailbox_id=".$this->mailbox['id'].") WHERE meta.meta_key='server_message_id' AND meta.meta_value!=''" );	
			if ( $letter_ids )
			{
				foreach ( $letter_ids as $uid )
					$wpdb->insert( USAM_TABLE_LOADED_MESSAGES_LOG, ['letter_id' => $uid, 'mailbox_id' => $this->mailbox['id']]);
			}
		}			
		$i = 0;				
		foreach( $mail_ids as $uid => $id )
		{	  				
			if ( !in_array($uid, $letter_ids) ) 
			{					
				$mail = $this->get_mail( $id );		
				if ( !$mail )
					continue;				
				
				$type = $this->mailbox['email'] == $mail['from_email']? 'sent_letter' : 'inbox_letter';	
				$folder = $type == 'sent_letter'? 'sent' : 'inbox';				
				$data = ['title' => $mail['subject'], 'to_email' => $mail['to_email'], 'to_name' => $mail['to_name'], 'from_email' => $mail['from_email'], 'from_name' => $mail['from_name'], 'date_insert' => $mail['date'], 'read' => 0, 'mailbox_id' => $this->mailbox['id'], 'body' => $mail['body'], 'type' => $type, 'folder' => $folder];
				
				if ( $this->filtering( $filters, $data ) )
				{
					$wpdb->insert( USAM_TABLE_LOADED_MESSAGES_LOG, ['letter_id' => $uid, 'mailbox_id' => $this->mailbox['id']]);
					continue;			
				}
				$email_id = $this->insert_email( $uid, $data, $mail['attachments'] );							
				if( $email_id )
				{					
					if ( !empty($mail['reply_to_email']) && $mail['reply_to_email'] != $mail['from_email'] )
					{
						usam_update_email_metadata( $email_id, 'reply_to_email', $mail['reply_to_email'] );		
						if ( !empty($mail['reply_to_name']) )
							usam_update_email_metadata( $email_id, 'reply_to_name',  $mail['reply_to_name'] );		
					}
					if ( $type == 'inbox_letter' )
						usam_add_notification(['title' => sprintf(__('Получено письмо от %s','usam'), $mail['from_name']." - ".$mail['from_email'])], ['object_type' => 'inbox_letter', 'object_id' => $email_id], $mailbox_user_ids ); 
					$i++;	
					if ( !empty($mail['attached_messages']) )
					{
						foreach ( $mail['attached_messages'] as $attached )					
						{ 
							$args = ['title' => $attached['subject'], 'to_email' => $attached['to_email'], 'to_name' => $attached['to_name'], 'from_email' => $attached['from_email'], 'from_name' => $attached['from_name'], 'date_insert' => $attached['date'], 'read' => 0, 'folder' => 'attached', 'mailbox_id' => $this->mailbox['id'], 'body' => $attached['body'], 'object_type' => 'email', 'object_id' => $email_id, 'type' => $type];
							$id = usam_insert_email( $args );								
							if ( !empty($attached['reply_to_email']) && $attached['reply_to_email'] != $attached['to_email'] )
							{
								usam_update_email_metadata( $id, 'server_message_id', $uid );	
								usam_update_email_metadata( $id, 'reply_to_email', $attached['reply_to_email'] );		
								if ( !empty($attached['reply_to_name']) )
									usam_update_email_metadata( $id, 'reply_to_name',  $attached['reply_to_name'] );		
							}					
							if ( $mail['from_email'] == 'mailer-daemon@yandex.ru' )
								usam_handle_communication_error( $attached['to_email'], 'email', 'rejected_email' );
						}				
					}						
					if ( $i > 30 )
						break;			
				}							
			}
			unset($mail_ids[$uid]);
		} 		
		wp_cache_flush_group( 'usam_contact_ids_email' );
		wp_cache_flush_group( 'usam_company_ids_email' );
		$this->disconnect();		
		$this->set_log_file();
		return $i;		
	}	
	
// Получаем количество писем
	protected function startWork()
	{		
		$result = $this->send_cmd( "STAT" );	
		if ( strpos($result, 'OK') )
		{
			$pattern = '/OK ([0-9]+) [0-9]+/';
			preg_match($pattern, $result, $matches);
			$this->letterCount = intval($matches[1]);			
		} 
		else
			$this->set_error( __('Не удалось получить информацию от pop3-сервера!','usam') );
	}
	
	protected function get_data()
	{ 
		$data = "";
		while ( !feof($this->server) )
		{
			$result = @fgets( $this->server );
			if ( $result === false )
			{
				$this->set_error( __('Не удалось получить сообщение. Ошибка в функции fgets.','usam') );
				return false;
			}
			$buffer = chop( $result );		
			if ( trim($buffer) == "." ) 
				break;			
			$data .= "$buffer\r\n";
		}
		return trim($data);
	}	
	
//С помощью этой функции можно узнать email, куда отвечать на письмо, откуда оно пришло, время получения, адрес отправителя, тема письма, тип письма, содержимое письма.	
	function header_decode($header) 
	{		
		$unassembled_headers = explode("\r\n", $header);	
		$headers = array();
		$lasthead = null;
		foreach ( $unassembled_headers as $key => $header ) 
		{ 
			if ( strpos( $header, ': ' ) === false && $lasthead !== null )
			{			
				$headers[$lasthead] .= $header;
			}
			else
			{				
				$headers[$key] = $header;
				$lasthead = $key;
			}						
		}
		$headers = array_values($headers);
		
		$decodedheaders = array();		
		for( $i=0; $i < count($headers); $i++ )
		{
			$thisheader = trim($headers[$i]);					
			$header_item = array();
			if ( strpos( $thisheader, ': ' ) !== false )
			{
				$header_item = explode(": ",$thisheader,2);	
			}
			elseif ( strpos( $thisheader, '=' ) !== false )
			{
				$header_item = explode("=",$thisheader,2);	
			}
			if ( !empty($header_item) )
			{
				$key = strtolower($header_item[0]);
				$values = explode(";",$header_item[1],2);	
				$decodedheaders[$key] = trim($values[0]);
				
			/*	if ( !empty($values[1]) )
				{
					$header_items = explode(" ",$values[1]);				
					foreach ( $header_items as $item ) 
					{
						$header_item = explode("=",$item,2);
						$key = strtolower($header_item[0]);
						$decodedheaders[$key] = trim($header_item[1]);						
					}
				}*/
				//$value = str_replace(";", "", $header_item[1]);			
				
			}
		/*	$thisheader = trim($headers[$i]);
			if(!empty($thisheader))
			{		
				if( preg_match('/^[A-Z0-9a-z_-]+:/i', $thisheader, $regs) )			
				{
					$dbpoint = strpos($thisheader,":");
					$headname = strtolower(substr($thisheader,0,$dbpoint));
					$headvalue = trim(substr($thisheader,$dbpoint+1));					
					$decodedheaders[$headname] = $headvalue;
					$lasthead = $headname;
				}
				elseif ( isset($lasthead) )
				{
					$decodedheaders[$lasthead] .= " $thisheader";
				}
			}*/
		}				
		return $decodedheaders;
	}	
		
	function compile_body( $body, $enctype ) 
	{	
		if(strtolower($enctype) == "base64") 
			$body = base64_decode($body);
		elseif(strtolower($enctype) == "quoted-printable")
			$body = quoted_printable_decode($body);
		
		return $body;		
	}
		
	protected function get_attachment( $email, $disposition = 'attachment' )
	{ 					
		$attachment = array();
		$attachment_headers = $email['header_decode'];			
		if ( empty($attachment_headers["content-type"]) || $attachment_headers["content-type"] == 'text/plain' || $attachment_headers["content-type"] == 'text/html' )
			return $attachment;	
	
		if( !isset($attachment_headers["content-transfer-encoding"]) )
			$file = '';
		elseif(strtolower($attachment_headers["content-transfer-encoding"]) == "base64")
			$file = base64_decode($email["body"]);
		elseif(strtolower($attachment_headers["content-transfer-encoding"]) == "quoted-printable")
			$file = quoted_printable_decode($email["body"]);
		elseif(strtolower($attachment_headers["content-transfer-encoding"]) == "8bit")
			$file = $email["body"];
		elseif(strtolower($attachment_headers["content-transfer-encoding"]) == "7bit")
			$file = $email["body"];
		elseif(strtolower($attachment_headers["content-transfer-encoding"]) == "binary")
			$file = $email["body"];	 	

		if( preg_match('/filename=[(\" | )]?(.[^\"]*)/i', $email['header'], $regs) )
			$filename = $this->decode_mime_string( trim(trim($regs[1]),'"') );
		elseif( preg_match('/name=[(\" | )]?(.[^\"]*)/i', $email['header'], $regs) ) 		
			$filename = $this->decode_mime_string( trim(trim($regs[1]),'"') );
		else 
			$filename = 'no_name';

		if( isset($attachment_headers['content-id']) && preg_match("|<(.*?)>|is", $attachment_headers['content-id'], $regs) ) 	
			$content_id = $regs[1];
		else
			$content_id = '';
					
		$filename = $this->decode_text( $filename, $email["charset"] );	
		
		 //заголовок Content-Disposition является индикатором того, что ожидаемый контент ответа будет отображаться в браузере, как вэб-страница или часть вэб-страницы, или же как вложение, которое затем может быть скачано и сохранено локально.
	//	if ( !empty($attachment_headers["content-disposition"]) 
			//&& ( $attachment_headers["content-disposition"] == 'form-data' || $attachment_headers["content-disposition"] == 'attachment' ) 
	//	) 
		if ( !empty($attachment_headers["content-disposition"]) )
			$type = $attachment_headers["content-disposition"] == 'inline' ? 'R' : 'email';
		else
			$type = $disposition == 'form-data' ? 'R' : 'email';			
		if ( $file != '' )
			$attachment = ['filename' => $filename, 'file' => $file, 'type' => $type, 'content_id' => $content_id]; 					
		return $attachment;			
	}
	
	//Первое, что нужно сделать с полученным письмом — разделить его на информацию и тело (headers & body). 	
	function fetch_structure( $email )
	{
		$results = ['header_decode' => []];	
		$separador = "\r\n\r\n";		
		$header = trim(substr($email,0,strpos($email,$separador)));		
		$bodypos = strlen($header)+strlen($separador);	
		$results["header"] = $header;
		$results["body"] = substr($email,$bodypos,strlen($email)-$bodypos);				
		
		if ( $header )
			$results['header_decode'] = $this->header_decode( $header );
		
		if ( $header )
		{
			$results["charset"] = '';
			if( preg_match('/charset[ ]?=[ ]?(.[^\s]*)/i', $header, $regs) ) 			
				$results["charset"] = trim(trim($regs[1]),'"');	
			$results["boundary"] = '';					
			if( preg_match('/boundary[ ]?=[(\" | )]?(.[^\"]*)/i', $header, $regs)) 	//\S			
				$results["boundary"] = trim(trim($regs[1]),'"');				
		}
		return $results;
	}
		
	public function parse_email( $email )
	{				
		if ( empty($email["header_decode"]) )
			return false;		
		$headers = $email['header_decode'];			
		if ( empty($headers["content-type"]) )
			return false;			
		
		$return = true;
		$body = '';
		$attachments = array();
		$attached_messages = array();	
		$content_type = $headers["content-type"];	
		switch( $headers["content-type"] ) 
		{
			case 'text/plain' : // если это обычный текст		
				if ( !empty($headers["content-transfer-encoding"]) )
					$body = $this->compile_body($email["body"], $headers["content-transfer-encoding"] );	
				else
					$body = $email["body"];
				$body = $this->decode_text( $body, $email["charset"] );
				$body =  nl2br( $body );	
			break;			
			case 'text/css' :
			case 'text/html' :	// если это html
				if ( !empty($headers["content-transfer-encoding"]) )
					$body = $this->compile_body($email["body"], $headers["content-transfer-encoding"] );	
				else
					$body = $email["body"];					
				$body = $this->decode_text( $body, $email["charset"] ); 
				if( preg_match_all('/src="(.[^\s]*)"/i', $body, $regs, PREG_SET_ORDER ) ) 		
				{					
					foreach ( $regs as $reg ) 
					{						
						$text = str_replace('cid:', '', $reg[1] );
						$result = explode("@", $text);							
						if ( isset($result[0]) )
						{
							$body = str_replace($reg[1], $result[0], $body );
						}
					}
				}						
			break;		
			case 'message/rfc822' :		
				$attached_messages[] = $this->get_message_structure( $email["body"] );	
			break;		
			case 'multipart/report' :					
			case 'multipart/alternative' : 		
				$types_attachments = explode("--".$email["boundary"], $email["body"]);
				$text_html = '';
				$text_plain = '';			
				foreach ( $types_attachments as $k => $attachment )
				{						
					$attachment = $this->fetch_structure( $attachment );					
					$result = $this->parse_email( $attachment );				
					if ( $result !== false )
					{  
						if ( !empty($result['attached_messages']) )
							$attached_messages = array_merge( $attached_messages, $result['attached_messages'] );						
						if ( $result['type'] == 'text/html' )						
							$text_html .= $result['body'];
						elseif ( $result['type'] == 'text/plain' )
							$text_plain = $result['body'];				
							
						if ( !empty($result['attachments']) && $text_html )
							$attachments = array_merge( $attachments, $result['attachments'] );	
					}		
				}	
				if ( $text_html )						
					$body = $text_html;
				elseif ( $text_plain )
					$body = $text_plain;				
			break;			
			case 'message/rfc822' : //тело письма содержит вложенное письмо в стандарте RFC 822
			case 'multipart/mixed' : //сообщение содержит вложения
				$body = ''; 	
				$types_attachments = explode("--".$email["boundary"], $email["body"]);
				foreach ( $types_attachments as $attach )
				{							
					$attach = trim($attach);
					if ( empty($attach) )
						continue;
					
					$attach = $this->fetch_structure( $attach );
					$attachment = $this->get_attachment( $attach );									
					if ( empty($attachment) )
					{								
						$result = $this->parse_email( $attach );							
						if ( !empty($result['attachments']) )
							$attachments = array_merge( $attachments, $result['attachments'] );	
						if ( !empty($result['body']) )
							$body .= $result['body'];	
					}			
					else
						$attachments[] = $attachment;
				} 
			break;
			case 'multipart/related' :	// сообщение содержит связанные части
				$types_attachments = explode("--".$email["boundary"], $email["body"]);		
				$body = '';				
				foreach ( $types_attachments as $attach )
				{
					$attach = $this->fetch_structure( $attach );
				
					if ( empty($attach['header_decode']) || empty($attach['header_decode']["content-type"]) )
						continue;		
				
					if ( $attach['header_decode']["content-type"] == 'text/html' )
					{	
						$content_type = 'text/html'; 					
						if ( $attach["boundary"] == '' )
						{
							$result = $this->parse_email( $attach );
							if ( !empty($result['attachments']) )
								$attachments = array_merge( $attachments, $result['attachments'] );								
							$body .= $result['body'];	
						}
						else
						{
							$parts = explode("--".$attach["boundary"], $attach["body"]);													
							foreach ( $parts as $part )
							{	
								$part = $this->fetch_structure( $part );
								$result = $this->parse_email( $part );														
								if ( $result !== false )
								{
									if ( $result['type'] == 'text/html' )						
										$text_html = $result['body'];
									elseif ( $result['type'] == 'text/plain' )
										$text_plain = $result['body'];		
								}								
							}		
							if ( !empty($text_html) )						
								$body = $text_html;
							elseif ( !empty($text_plain) )
								$body = $text_plain;								
						}
					}
					elseif ( $attach['header_decode']["content-type"] == 'multipart/alternative' || $attach['header_decode']["content-type"] == 'multipart/report' )
					{		
						$result = $this->parse_email( $attach );						
						if ( $result !== false )
						{						
							$body = $result['body'];
							$attachments = array_merge( $attachments, $result['attachments'] );	
						}						
					}
					else		
						$attachments[] = $this->get_attachment( $attach, 'form-data' );	
				}					
			break;
			case 'message/rfc822' :
			
			break;
			case 'image/gif' :
			case 'image/jpeg' :
			case 'audio/x-mpeg' : 
			case 'video/mpeg-2' : 
			case 'application/msword' : 
			case 'application/mspowerpoint' : 
			case 'application/zip' :
				$attachments[] = $this->get_attachment( $email["body"] );	
			break;
			case 'message/delivery-status' : //Статус доставки
				$delivery_status = isset($headers["Original-Envelope-ID"])?$headers["Original-Envelope-ID"]:0; 
			break;			
			default:
				$return = false;
		}
		if ( $return )
		{
			if ( $email["charset"] != '' )
				$body = preg_replace("'charset=".$email["charset"]."'si","charset=UTF-8", $body);			
			$body = usam_remove_email_system_tags( $body );		
			$return = ['body' => $body, 'type' => $content_type, 'attachments' => $attachments, 'attached_messages' => $attached_messages];				
		}
		return $return;
	}
	
//цикл по всем письмам	
	public function get_mail( $id )
	{					
		$this->send_cmd( "RETR ".$id );		
		$text = $this->get_data();		
		if ( empty($text) )
			return false;	
	
		$message = $this->get_message_structure( $text );
		$message['id'] = $id;	
		return $message;
	}
	
	protected function get_message_structure( $text )
	{	
		$email = $this->fetch_structure( $text );		
		$headers = $email['header_decode'];	
		$message = $this->parse_email( $email );		
		if ( $message !== false )
		{	// Очистить от тега base				
			$result['body'] = preg_replace("'<base[^>]*?>'si","", $message['body']);
			$result['body'] = str_replace( array( "\n\r" ), '<br>', $result['body'] );		
			$result['attachments'] = $message['attachments'];	
			$result['attached_messages'] = $message['attached_messages'];	
		}
		else
			$result['body'] = '';
							
		$result['type'] = $headers["content-type"];		
		$result['date'] = !empty($headers["date"])?date('Y-m-d H:i:s', strtotime($headers["date"]) ):date('Y-m-d H:i:s');
		$result['message_id'] = isset($headers["Message-Id"])?$headers["Message-Id"]:'';	// номер письма
	
		if ( isset($headers["subject"]) )
			$result['subject'] = $this->decode_mime_string( $headers["subject"] );// Определяем тему сообщения	
		else
			$result['subject'] = '';
					
		if ( isset($headers['from']) )	
			$from = $this->get_name_and_email( $headers['from'] );	//имя и адрес отправителя	
		else
			$from = $this->get_name_and_email( $headers['return-path'] );	//адрес возврата в случае неудачи, когда невозможно доставить письмо по адресу назначения.	
		$result['from_email'] = $from['email'];
		$result['from_name'] = $from['name'];			
		if ( isset($headers['reply-to']) )	
		{
			$reply_to = $this->get_name_and_email( $headers["reply-to"] );	//имя и адрес, куда следует адресовать ответы на это письмо	
			$result['reply_to_email'] = $reply_to['email'];	
			$result['reply_to_name'] = $reply_to['name'];	
		}			
		$result['to_email'] = '';
		$result['to_name'] = '';
		if ( !empty($headers['to']) )
		{
			$to = $this->get_name_and_email( $headers['to'] );		 //имя и адрес получателя.					
			$result['to_email'] = $to['email'];
			$result['to_name'] = $to['name'];
		}
				
// Определяем размер сообщения
		preg_match( '/OK ([0-9]+) octets+/', $email["header"], $matches);	
		if ( !empty($matches[1]) )
			$result['size'] = ceil($matches[1] / 1024);		
		return $result;		
	}
	
	protected function get_name_and_email( $header )
	{			
		$return = ['email' => '', 'name' => ''];						
		$header = $this->decode_mime_string( $header );
		if ( preg_match_all('|<(.*)>|Uis', $header, $result) )	
		{
			$str = array();
			foreach ( $result[1] as $email )
			{
				if ( is_email($email) )
					$str[]= $email;
			}				
			$return['email'] = implode(',',$str);			
			$name = trim(str_replace("\"", "", strip_tags( $header ) ));
			$return['name'] = $this->decode_mime_string( $name );
		}
		else
			$return = array( 'email' => $header, 'name' => '' );
		return $return;
	}
	
	//Декодировать заголовок	
	function decode_mime_string( $text ) 
	{		
		if(($pos = strpos($text,"=?")) === false) 
			return $text;		
		$string = str_replace(array("\r\n", "\r", "\n", " "), '', $text );	
		
		$newresult = '';
		while(!($pos === false))
		{
			$newresult .= trim(substr($string,0,$pos));
			$string = substr($string,$pos+2,strlen($string));
			$intpos = strpos($string,"?");
			$charset = substr($string,0,$intpos);
			$enctype = strtolower(substr($string,$intpos+1,1));
			$string = substr($string,$intpos+3,strlen($string));
			$endpos = strpos($string,"?=");
			$mystring = substr($string,0,$endpos);
			$string = substr($string,$endpos+2,strlen($string));
			if($enctype == "q") 
				$mystring = quoted_printable_decode(str_replace("_"," ",$mystring));
			else if ($enctype == "b") 
				$mystring = base64_decode($mystring);			
			$newresult .= trim($mystring);			
			$pos = strpos($string,"=?");
		}
		$text = $this->decode_text( $newresult.$string, $text );		
		return $text;
	}	
	
	public function delete_message_before_date( )	
	{				
		$mail_ids = $this->get_list_mail_ids();	
		if ( $mail_ids === false )
			return false;		
		elseif ( empty($mail_ids) )
			return true;
			
		if ( empty($this->mailbox['delete_server_day']) )
			return false;
		
		$datetime = strtotime("-".$this->mailbox['delete_server_day']." day");

		ksort($mail_ids);	
		set_time_limit(1800);
		$i = 0;
		foreach ( $mail_ids as $uid => $id )
		{							
			$header = $this->get_top_message( $id );			
			$headers = $this->header_decode( $header );				
			$email_date = strtotime($headers["date"]);
			if ( $email_date <= $datetime )
			{
				$this->send_cmd( "DELE ".$id );	
				$i++;
			}
			if ( $i >= 1000 )
				continue;
		} 	
		$this->disconnect();
		return $i;		
	}
	
	public function delete_messages( $ids )
	{		
		$mail_ids = $this->get_list_mail_ids();		
		if ( $mail_ids === false )
			return false;		
		elseif ( empty($mail_ids) )
			return true;			
	
		if ( !is_array($ids) )
			$ids = [$ids];
		$result = false;  				
		foreach( $ids as $id )
		{				
			if ( !empty($mail_ids[$id]) )				
				$result = $this->send_cmd( "DELE ".$mail_ids[$id] );	
		} 
		return $result;
	}	
}

/*
function usam_system_process7( ) 
{
	$mailboxes = new USAM_POP3( 4 );	
	$mailboxes->download_messages();	
}
if ( isset($_GET['email_id']) )
	add_action('admin_init','usam_system_process7', 5 );
/**/
?>