<?php
//https://snipp.ru/php/imap-php

require_once(USAM_FILE_PATH.'/includes/mailings/email-loading.php');
class USAM_IMAP extends USAM_Email_Loading
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
				$type = 'imap';
				$server = $this->mailbox['pop3server'].':'.$this->mailbox['pop3port'].'/'.$type;
				if ( $mailbox['pop3ssl'] )
					$server .= '/ssl';
				
				$server = @imap_open('{'.$server.'}INBOX', $mailbox['email'], $mailbox['pop3pass']);
				if ( !$server ) 
					$this->set_error( sprintf(__("Сервер не найден %s","usam"), $server) );
				else
				{		
					$this->server = $server;
					return true;		
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

	public function disconnect()
	{
		if ( $this->connect_open() ) 
		{ 			
			imap_close( $this->server );
			$this->server == null;
		}
	}

	//пинг серверу
	public function check_server()
	{
		if ( $this->connect_open() && imap_ping($this->server) ) 
			return true;
		return false;
	}	
		
	//оказывает общую информацию и размер каждого письма. 
	public function get_list_mail()
	{		
		fputs($this->server, "LIST\r\n");
		$connect_result = fgets($this->server, 1024);	
		// Получаем общую информацию, распарсив регулярным выражением
		// Возвращаемую сервером строку
		$lists = array();
		if ( strpos($connect_result, 'OK') )
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
		
		return imap_search($imap, 'ALL');
	}	
	
	public function download_messages()	
	{  
		if ( !$this->server ) 
			return false;
		$mail_ids = imap_search($this->server, 'ALL');
		if ( $mail_ids === false )
			return false;		
		elseif ( empty($mail_ids) )
			return true;
			
		ksort($mail_ids);	
		set_time_limit(1800);						
		
		$filters = self::get_email_filters( );

		$mailbox_user_ids = usam_get_mailbox_users( $this->mailbox_id );
		$i = 0;		
		foreach ( $mail_ids as $key_id => $id )
		{			
			if ( $key_id > $this->letter_id )	
			{						
				$mail = $this->get_mail( $id );				
				if ( !$mail )
					continue;
				
				$this->letter_id = $key_id;						
				$type = $this->email == $mail['from_email']? 'sent_letter' : 'inbox_letter';	
				$folder = $type == 'sent_letter'? 'sent' : 'inbox';				
				$insert_email = ['title' => $mail['subject'], 'to_email' => $mail['to_email'], 'to_name' => $mail['to_name'], 'from_email' => $mail['from_email'], 'from_name' => $mail['from_name'], 'date_insert' => $mail['date'], 'read' => 0, 'mailbox_id' => $this->mailbox_id, 'body' => $mail['body'], 'type' => $type, 'folder' => $folder];				
				
				if ( $this->filtering( $filters, $insert_email ) )
				{
					usam_update_mailbox( $this->mailbox_id, ['letter_id' => $key_id]);
					continue;			
				}
				$email_id = $this->insert_email( $uid, $insert_email, $mail['attachments'] );	
				if ( $email_id )
				{ 								
					usam_update_mailbox( $this->mailbox_id, ['letter_id' => $key_id]);						
					if ( !empty($mail['reply_to_email']) && $mail['reply_to_email'] != $mail['from_email'] )
					{
						usam_update_email_metadata( $email_id, 'reply_to_email', $mail['reply_to_email'] );		
						if ( !empty($mail['reply_to_name']) )
							usam_update_email_metadata( $email_id, 'reply_to_name',  $mail['reply_to_name'] );		
					}
					if ( $type == 'inbox_letter' )
					{						
						usam_add_notification(['title' => sprintf(__('Получено письмо от %s','usam'), $mail['from_name']." - ".$mail['from_email'])], ['object_type' => 'inbox_letter', 'object_id' => $email_id], $mailbox_user_ids ); 
					}					
					$i++;	
					if ( !empty($mail['attached_messages']) )
					{
						foreach ( $mail['attached_messages'] as $attached )					
						{ 
							$args = ['title' => $attached['subject'], 'to_email' => $attached['to_email'], 'to_name' => $attached['to_name'], 'from_email' => $attached['from_email'], 'from_name' => $attached['from_name'], 'date_insert' => $attached['date'], 'read' => 0, 'folder' => 'attached', 'mailbox_id' => $this->mailbox_id, 'body' => $attached['body'], 'object_type' => 'email', 'object_id' => $email_id, 'type' => $type];
							$id = usam_insert_email( $args );								
							if ( !empty($attached['reply_to_email']) && $attached['reply_to_email'] != $attached['to_email'] )
							{
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
		} 		
		$this->disconnect();		
		$this->set_log_file();
		return $i;		
	}
		
	protected function get_data()
	{ 
		$data = "";
		while (!feof($this->server))
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
			if( preg_match('/boundary[ ]?=[ ]?(["]?.*)/i', $header, $regs)) 	//\S			
				$results["boundary"] = trim(trim($regs[1]),'"');				
		}
		return $results;
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

		if( preg_match('/filename[ ]?=[ ]?(.[^\s]*)/i', $email['header'], $regs) ) 		
			$filename = $this->decode_mime_string( trim(trim($regs[1]),'"') );
		elseif( preg_match('/name[ ]?=[ ]?(.[^\s]*)/i', $email['header'], $regs) ) 		
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
			$attachment = array( 'filename' => $filename, 'file' => $file, 'type' => $type, 'content_id' => $content_id ); 					
		return $attachment;			
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
		switch ( $headers["content-type"] ) 
		{
			case 'text/plain' : // если это обычный текст		
				if ( !empty($headers["content-transfer-encoding"]) )
					$body = $this->compile_body($email["body"], $headers["content-transfer-encoding"] );	
				else
					$body = $email["body"];
				$body = $this->decode_text( $body, $email["charset"] );
				$body =  nl2br( $body );	
			break;				
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
				foreach ( $types_attachments as $attachment )
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
			default:
				$return = false;
		}
		if ( $return )
		{
			if ( $email["charset"] != '' )
				$body = preg_replace("'charset=".$email["charset"]."'si","charset=UTF-8", $body);			
			$body = usam_remove_email_system_tags( $body );		
			$return = array( 'body' => $body, 'type' => $content_type, 'attachments' => $attachments, 'attached_messages' => $attached_messages );			
		}
		return $return;
	}
	
//цикл по всем письмам	
	public function get_mail( $id )
	{					
		$header = imap_header($this->server, $id);
		$header = json_decode(json_encode($header), true);
		$subject = mb_decode_mimeheader($header['subject']); 
	
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
		$to = $this->get_name_and_email( $headers['to'] );		 //имя и адрес получателя.			
		
		$result['to_email'] = $to['email'];
		$result['to_name'] = $to['name'];
				
// Определяем размер сообщения
		preg_match( '/OK ([0-9]+) octets+/', $email["header"], $matches);	
		if ( !empty($matches[1]) )
			$result['size'] = ceil($matches[1] / 1024);		
		return $result;		
	}
	
	protected function get_name_and_email( $header )
	{			
		$return = array( 'email' => '', 'name' => '' );						
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
			
		if ( !$this->mailbox['delete_server_day'] )
			return false;
		
		$datetime = strtotime("-".$this->mailbox['delete_server_day']." day");

		ksort($mail_ids);	
		set_time_limit(1800);
		$i = 0;
		foreach ( $mail_ids as $key_id => $id )
		{				
			$key_id = (int) $key_id;	
			
			$header = $this->get_top_message( $id );			
			$headers = $this->header_decode( $header );				
			$email_date = strtotime($headers["date"]);
			if ( $email_date <= $datetime )
			{
				fputs($this->server, "DELE ".$id."\r\n");	
				$i++;
			}
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
				$result = fputs($this->server, "DELE ".$mail_ids[$id]."\r\n");	
		}		
		return $result;
	}
	
	// Получить i строк письма c id
	public function get_top_message( $id, $i = 0 )
	{		
		fputs($this->server, "TOP $id $i\r\n");
		$result = $this->get_data( );	
		return $result;
	} 	
}
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

/*
function usam_system_process7( ) 
{
	$mailboxes = new USAM_POP3( 4 );	
	$mailboxes->download_messages();	
}
if ( isset($_GET['ma']) )
	add_action('admin_init','usam_system_process7', 5 );
/**/
?>