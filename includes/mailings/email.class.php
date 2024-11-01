<?php
require_once( USAM_FILE_PATH . '/includes/mailings/email_folder.class.php' );
require_once( USAM_FILE_PATH . '/includes/mailings/email_folders_query.class.php' );
/**
 * Работа с электронными письмами
 */ 
class USAM_Email
{
	private static $string_cols = [
		'to_email',	
		'to_name',
		'from_name',
		'from_email',
		'title',				
		'date_insert',
		'sent_at',			
		'folder',		
		'body',		
		'type',
	];
	private static $int_cols = [
		'id',	
		'read',	
		'mailbox_id',
		'user_id',		
		'importance',	
	];
	private static $float_cols = [];		
	private $changed_data = array();
	private $previous_read     = '';	
	private $is_status_read    = false;	
	private $data     = array();		
	private $fetched  = false;
	private $args     = ['col'   => '', 'value' => ''];	
	private $exists   = false; // если существует строка в БД

	public function __construct( $value = false, $col = 'id' ) 
	{
		if ( empty($value) )
			return;
			
		if ( is_array( $value ) ) 
		{
			$this->set( $value );
			return;
		}
		if ( ! in_array( $col, array( 'id' ) ) )
			return;		
					
		$this->args = array( 'col' => $col, 'value' => $value );			
		if ( $col == 'id' ) 
		{			
			$this->data = wp_cache_get( $value, 'usam_email' );
		}		
		// кэш существует
		if ( $this->data ) 
		{
			$this->fetched = true;
			$this->exists = true;
			return;
		}
		else
			$this->fetch();
	}
	
	private static function get_column_format( $col ) 
	{
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';
		
		if ( in_array( $col, self::$float_cols ) )
			return '%f';
		return false;
	}
	
	/**
	 * Сохранить в кэш переданный объект
	*/
	public function update_cache( ) 
	{
		$id = $this->get( 'id' );	
		wp_cache_set( $id, $this->data, 'usam_email' );		
		do_action( 'usam_email_update_cache', $this );
	}

	/**
	 * Удалить кеш	 
	 */
	public function delete_cache( ) 
	{		
		wp_cache_delete( $this->get( 'id' ), 'usam_email' );	
		wp_cache_delete( $this->get( 'id' ), 'usam_email_attachments' );
		do_action( 'usam_email_delete_cache', $this );	
	}		

	public function delete( ) 
	{					
		if ( $this->exists() == false )
			return;
		
		$id = $this->get( 'id' );		
		usam_delete_emails(['include' => $id]);
		$this->delete_cache( );	
	}		
	
	/**
	 * Выбирает фактические записи из базы данных
	 */
	private function fetch() 
	{
		global $wpdb;
		if ( $this->fetched )
			return;

		if ( ! $this->args['col'] || ! $this->args['value'] )
			return;

		extract( $this->args );

		$format = self::get_column_format( $col );
		$sql = $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_EMAIL." WHERE {$col} = {$format}", $value );

		$this->exists = false;		
		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) 
		{			
			$this->exists = true;
			$this->data = apply_filters( 'usam_email_data', $data );			
			foreach ($this->data as $k => $value ) 
			{				
				if ( in_array( $k, self::$int_cols ) )
					$this->data[$k] = (int)$value;
				elseif ( in_array( $k, self::$float_cols ) )
					$this->data[$k] = (float)$value;				
			}
			$this->fetched = true;				
			$this->update_cache();
		}			
		do_action( 'usam_email_fetched', $this );	
		$this->fetched = true;			
	}
	
	public function exists() 
	{		
		$this->fetch();
		return $this->exists;
	}

	public function get( $key ) 
	{
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();
		
		if ( isset($this->data[$key] ) )
			$value = $this->data[$key];		
		else
			$value = null;
		return apply_filters( 'usam_email_get_property', $value, $key, $this );
	}	
	
	public function get_data()
	{
		if( empty($this->data) )
			$this->fetch();

		return apply_filters( 'usam_email_get_data', $this->data, $this );
	}

	public function set( $key, $value = null ) 
	{		
		if ( is_array( $key ) ) 
			$properties = $key;
		else 
		{
			if ( is_null( $value ) )
				return $this;
			$properties = array( $key => $value );			
		}			
		if ( ! is_array($this->data) )
			$this->data = array();	
				
		if ( array_key_exists( 'read', $properties ) ) 
		{	
			$this->previous_read = $this->get( 'read' );
			if ( $properties['read'] != $this->previous_read )
				$this->is_status_read = true;			
		}
		$properties = apply_filters( 'usam_email_set_properties', $properties, $this );	
		foreach ( $properties as $key => $value ) 
		{	
			$format = self::get_column_format( $key );
			if ( $format !== false )
			{				
				$previous = $this->get( $key );			
				if ( $value != $previous )
				{				
					$this->changed_data[$key] = $previous;					
				}
				$this->data[$key] = $value;
			}
		}							
		return $this;
	}

	private function get_data_format( $data ) 
	{
		$formats = array();
		foreach ( $data as $key => $value ) 
		{			
			$format = self::get_column_format( $key );
			if ( $format !== false )		
				$formats[$key] = $format;
		}
		return $formats;
	}	
	
	private function get_update_data( ) 
	{		
		$data = [];		
		foreach( $this->changed_data as $key => $value ) 
		{							
			if( self::get_column_format( $key ) !== false )
				$data[$key] = $this->data[$key];
		}
		return $data;
	}		
	
	private function get_folder()	
	{			
		$mailbox_id = $this->get('mailbox_id');
		$cache_key = 'usam_email_folder_attachments';
		$folder_id = wp_cache_get( $mailbox_id, $cache_key ); 
		if( $folder_id === false )
		{								
			$mailbox = usam_get_mailbox( $mailbox_id );	
			$folder_id = usam_get_folders(['fields' => 'id', 'name' => $mailbox['email'], 'number' => 1]);
			if ( empty($folder_id) )
			{
				$parent_id = usam_get_folders(['fields' => 'id', 'slug' => 'email', 'number' => 1]);
				if ( empty($parent_id) )
					$parent_id = usam_insert_folder(['name' => __("Почта","usam"), 'slug' => 'email']);
				$folder_id = usam_insert_folder(['name' => $mailbox['email'], 'parent_id' => $parent_id]);
			}
			wp_cache_set( $mailbox_id, $folder_id, $cache_key );
		}	
		return $folder_id;
	}
	
	public function set_attachments( $attachments ) 
	{		
		global $wpdb;
		
		$id = $this->get('id');
		if ( empty($id) || empty($attachments) )
			return false;
				
		$folder_id = $this->get_folder();
		foreach( $attachments as $attachment ) 
		{														
			if( is_numeric($attachment) )
				usam_attach_file( $attachment, ['object_id' => $id, 'type' => 'email', 'folder_id' => $folder_id]);	
			elseif( !empty($attachment['file_path']) )
			{ 
				$title = !empty($attachment['title'])?$attachment['title']:basename($attachment['file_path'], '.' . usam_get_extension( $attachment['file_path'] ) );
				$delete = !empty($attachment['delete']) && $attachment['delete'] ?$attachment['delete']:false;
				usam_add_file_from_files_library( $attachment['file_path'], ['object_id' => $id, 'title' => $title, 'type' => 'email', 'folder_id' => $folder_id], $delete );				
			}					
			elseif ( !empty($attachment['file_id']) )
			{										
				$type = !empty($attachment['type'])?$attachment['type']:'email';
				usam_attach_file( $attachment['file_id'], ['object_id' => $id, 'type' => $type, 'folder_id' => $folder_id]);	
			}				
		}			
	}
				
	/**
	 * Сохраняет в базу данных	
	 */
	public function save()
	{
		global $wpdb;

		do_action( 'usam_email_pre_save', $this );	
		
		if ( !empty($this->data['sent_at']) )
			$this->data['sent_at'] = date( "Y-m-d H:i:s", strtotime($this->data['sent_at']) );		

		if ( !empty($this->data['title']) )
			$this->data['title'] = trim(stripcslashes(wp_unslash($this->data['title'])));		

		if ( !empty($this->data['from_name']) )
			$this->data['from_name'] = trim(stripcslashes(wp_unslash($this->data['from_name'])));		

		if ( !empty($this->data['to_name']) )
			$this->data['to_name'] = trim(stripcslashes(wp_unslash($this->data['to_name'])));	
		
		if ( !empty($this->data['from_email']) )
			$this->data['from_email'] = trim($this->data['from_email']);	

		if ( !empty($this->data['to_email']) )
			$this->data['to_email'] = trim($this->data['to_email']);			
		
		$result = false;	
		if ( $this->args['col'] ) 
		{				
			if ( empty($this->changed_data) )
				return true;
					
			$where_format = self::get_column_format( $this->args['col'] );							
			do_action( 'usam_email_pre_update', $this );	

			$this->data = apply_filters( 'usam_email_update_data', $this->data );	
			$data = $this->get_update_data();
			$formats = $this->get_data_format( $data );		
			$result = $wpdb->update( USAM_TABLE_EMAIL, $data, [$this->args['col'] => $this->args['value']], $formats, $where_format );
			if ( $result )
			{				
				$this->update_cache();		
				$mailbox_id = $this->get('mailbox_id');
				if ( $mailbox_id && ( $this->is_status_read || isset($this->changed_data['folder']) || isset($this->changed_data['mailbox_id']) ) )
				{
					$email_folder = $this->get('folder');
					if ( $email_folder )
						$folder = usam_get_email_folders(['slug' => $email_folder, 'mailbox_id' => $mailbox_id, 'number' => 1, 'cache_results' => true]);
					if ( isset($this->changed_data['folder']) )
					{								
						$read = $this->get('read');	
						if ( !empty($folder) )
						{
							$count = $folder->count + 1;								
							$update = ['count' => $count];
							if ( $read == 0 )
								$update['not_read'] = $folder->not_read + 1;	
							
							usam_update_email_folder( $folder->id, $update );	
						}
						$previous_folder = usam_get_email_folders(['slug' => $this->changed_data['folder'], 'mailbox_id' => $mailbox_id, 'number' => 1, 'cache_results' => true]);
						if ( $previous_folder )
						{
							$count = $previous_folder->count - 1;								
							$update = ['count' => $count];
							if ( $this->is_status_read && $this->previous_read == 0 || $read == 0 )
								$update['not_read'] = $previous_folder->not_read - 1;						
						
							usam_update_email_folder( $previous_folder->id, $update );	
						}
					}
					elseif ( $this->is_status_read && !empty($folder) )
					{								
						$update = array();
						if ( $this->data['read'] == 1 )
							$update['not_read'] = $folder->not_read - 1;	
						else
							$update['not_read'] = $folder->not_read + 1;						
						usam_update_email_folder( $folder->id, $update );	
					}
					if ( isset($this->changed_data['mailbox_id']) )
					{
						$slug = isset($this->changed_data['folder'])?$this->changed_data['folder']:$email_folder;					
						$previous_folder = usam_get_email_folders( array('slug' => $slug, 'mailbox_id' => $this->changed_data['mailbox_id'], 'number' => 1, 'cache_results' => true) );	
						$count = $previous_folder->count - 1;
						$update = array( 'count' => $count );
						usam_update_email_folder( $previous_folder->id, $update );	
					}
				}				
			}
			do_action( 'usam_email_update', $this );
		} 
		else 
		{   
			do_action( 'usam_email_pre_insert' );		
			unset( $this->data['id'] );				
			if( empty($this->data['date_insert']) )
				$this->data['date_insert'] = date( "Y-m-d H:i:s" );				
			if ( !isset($this->data['from_email']) )
				$this->data['from_email'] = '';			
			if ( empty($this->data['folder']) )
				$this->data['folder'] = 'drafts';		
			if ( !isset($this->data['from_name']) )
				$this->data['from_name'] = '';					
			if ( !isset($this->data['to_email']) )
				$this->data['to_email'] = '';					
			if ( !isset($this->data['to_name']) )
				$this->data['to_name'] = '';
			if ( !isset($this->data['title']) )
				$this->data['title'] = '';	
			if ( !isset($this->data['body']) )
				$this->data['body'] = '';				
			if ( !isset($this->data['read']) )
				$this->data['read'] = 0;	
			if ( !isset($this->data['mailbox_id']) )
				$this->data['mailbox_id'] = 0;		
			if( empty($this->data['user_id']) )
				$this->data['user_id'] = get_current_user_id();		
			if ( empty($this->data['type']) )
				$this->data['type'] = 'sent_letter';							
			if ( !empty($this->data['mailbox_id']) )
			{
				$mailbox = usam_get_mailbox( $this->data['mailbox_id'] );
				if ( !empty($mailbox) )
				{
					if ( $this->data['type'] == 'sent_letter' )
					{		
						$this->data['from_name'] = $mailbox['name'];
						$this->data['from_email'] = $mailbox['email'];
					}
				}
			}
			else
				$this->data['mailbox_id'] = 0;		
			$this->data = apply_filters( 'usam_email_insert_data', $this->data );			
			$formats = $this->get_data_format( $this->data );	
			$result = $wpdb->insert( USAM_TABLE_EMAIL, $this->data, $formats );							
			if ( $result ) 
			{									
				$this->set( 'id', $wpdb->insert_id );			
				$this->args = ['col' => 'id',  'value' => $wpdb->insert_id];	
				$this->update_cache();
				
				$status_update_folder = apply_filters( 'usam_update_number_messages_folders', true );	
				if ( $status_update_folder && !empty($this->data['mailbox_id']) )
				{
					$folder = usam_get_email_folders(['slug' => $this->data['folder'], 'mailbox_id' => $this->data['mailbox_id'], 'number' => 1, 'cache_results' => true]);	
					if ( !empty($folder) ) 
					{
						$count = $folder->count + 1;	
						$update = array( 'count' => $count );		
						if ( $this->data['read'] == 0 && $this->data['type'] == 'inbox_letter' )
							$update['not_read'] = $folder->not_read + 1;	
						usam_update_email_folder( $folder->id, $update );	
					}
				}				
				do_action( 'usam_email_insert', $this );				
			}			
		} 		
		do_action( 'usam_email_save', $this );
		$this->changed_data = [];
		return $result;
	}
	
	public function php_mailer( $phpmailer ) 
	{
		$server = usam_get_mailbox_metadata( $this->data['mailbox_id'], 'smtpserver' );
		if ( !$server )
			return true;
			    
		$phpmailer->Mailer = 'smtp';
		$phpmailer->Host   = $server;
		$phpmailer->Port   = usam_get_mailbox_metadata( $this->data['mailbox_id'], 'smtpport' );
		
		$user = usam_get_mailbox_metadata( $this->data['mailbox_id'], 'smtpuser' );
		if ( $user )
		{ // использовать имя и пароль для аутентификации.		
			$phpmailer->SMTPAuth = true; 			
			$phpmailer->Username = $user;
			$phpmailer->Password = usam_get_mailbox_metadata( $this->data['mailbox_id'], 'smtppass' );
		}
		$smtp_secure = usam_get_mailbox_metadata( $this->data['mailbox_id'], 'smtp_secure' );
		if( $smtp_secure && $smtp_secure != 'none' )
			$phpmailer->SMTPSecure = $smtp_secure;
		if( $smtp_secure != 'tls' )
			$phpmailer->SMTPAutoTLS = false;
	}
	
	public function send_mail( $links = [] )
	{						
		$email_id = $this->get('id');		
		$title = $this->get('title');
		$message = $this->get('body');
		$to_email = $this->get('to_email');		
		$emails = explode(',',$to_email);					
		foreach ( $emails as $email ) 
		{
			$email = trim($email);
			if ( !preg_match("/[0-9a-z]+@[a-zа-я]/", $email) && !is_email($email) ) 
			{
				usam_log_file( sprintf(__('Электронная почта %s указана не верно','usam'), $email) );
				return false;
			}		
		}
		$insert_email['folder'] = 'outbox';
		$email_sent = false;	
		add_action( 'phpmailer_init', array($this, 'php_mailer'), 100 );		
		$mailbox = usam_get_mailbox( $this->data['mailbox_id'] );		
		if ( !empty($mailbox) )
		{			
			$from_name =  $insert_email['from_name'] = $mailbox['name'];
			$from_email = $insert_email['from_email'] = $mailbox['email'];
		}	
		else
		{
			$from_name = $this->get('from_name');
			$from_email = $this->get('from_email');				
		}								
		$headers = array();
		$headers[] = "From: $from_name <{$from_email}>";
		$headers[] = 'Content-Type: text/html; charset=utf-8';		
		
		$copy_email = usam_get_email_metadata( $email_id, 'copy_email' );
		if ( !empty($copy_email) )
			$headers[] = "Cc: $copy_email";		
		
		$reply_to_email = usam_get_email_metadata( $email_id, 'reply_to_email' );
		if ( !empty($reply_to_email) )
		{
			$reply_to_name = usam_get_email_metadata( $email_id, 'reply_to_name' );				
			$headers[] = "Reply-to: $reply_to_name <{$reply_to_email}>";	
		}
		else
		{
			$headers[] = "Reply-to: $from_name <{$from_email}>";
		}
	//	$headers[] = "Return-Path: $from_email";			
		$headers[] = "X-Universam-Mail-ID: $email_id";	

		if ( $this->get('importance') )
		{
			$headers[] = "X-Priority: 1";			
			$headers[] = "X-MSMail-Priority: High";	
			$headers[] = "Importance: High";		
		}				
		$open_link = add_query_arg(['mail_id' => $email_id, 'usam_action' => 'email_open'], home_url() );
		$message .= '<span class="checkcheck"><img alt="check" style="width:1px;height:1px;display:none;" src="'.$open_link.'"></span>';		
		$objects = usam_get_email_objects( $email_id );			
		if ( $objects )
		{
			foreach ( $objects as $object )
			{
				if ( $object->object_type == 'order' )
				{
					$headers[] = "X-Universam-Object-Type: $object->object_type";	
					$headers[] = "X-Universam-Object-ID: $object->object_id";
					$message .= '<span class="sendemailobject" style="display:none;font-size:1px;">'.$object->object_type.'-'.$object->object_id.'</span>';
					break;
				}
			}
		}	
		$message .= '<span class="sendemailid" style="display:none;font-size:1px;">'.$email_id.'</span>';

		$attachments = usam_get_email_attachments( $email_id );		
		$attachments_filepath = array();
		foreach ( $attachments as $attachment )
			$attachments_filepath[$attachment->title.'.'.pathinfo(USAM_UPLOAD_DIR.$attachment->file_path, PATHINFO_EXTENSION)] = USAM_UPLOAD_DIR.$attachment->file_path;			
		//$to_email	получатель письма
		$email_sent = wp_mail( $to_email, $title, $message, $headers, $attachments_filepath );	
		if ( $email_sent )
		{			
			$insert_email['folder'] = 'sent';
			$insert_email['sent_at'] = date( "Y-m-d H:i:s");
			if ( !empty($_REQUEST['do_action_send_email']) )
			{
				$do_action_send_email = sanitize_title($_REQUEST['do_action_send_email']);			
				do_action( 'usam_send_email_'.$do_action_send_email, $event_id );
			}
		}	
		$insert_email['read'] = 1;		
		$this->set( $insert_email );
		$this->save();
				
		$contact_ids = usam_get_contact_ids_by_field('email', $to_email);
		foreach ($contact_ids as $contact_id )
			$links[] = ['object_id' => $contact_id, 'object_type' => 'contact'];
		$company_ids = usam_get_company_ids_by_field('email', $to_email);
		foreach ($company_ids as $company_id )
			$links[] = ['object_id' => $company_id, 'object_type' => 'company'];
			
		require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
		$date = $this->get('date_insert');	
		usam_set_ribbon(['event_id' => $email_id, 'event_type' => 'email', 'date_insert' => $date], $links);
		return $email_sent;
	}
}

// Обновить 
function usam_update_email( $id, $data )
{
	$_email = new USAM_Email( $id );
	$_email->set( $data );
	return $_email->save();
}

// Получить 
function usam_get_email( $id, $format_message = true )
{
	$_email = new USAM_Email( $id );
	$email_data = $_email->get_data( );	
	
	if ( $format_message && !empty($email_data['body']) )
	{
		$dir = USAM_UPLOAD_URL."e-mails/".$email_data['id']."/";
		if( preg_match_all('/src="(.[^\s]*)"/i', $email_data['body'], $regs, PREG_SET_ORDER ) ) 		
		{	
			$replacement = array();
			foreach ( $regs as $reg ) 
			{	
				if ( !in_array($reg[1], $replacement) && !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $reg[1]) )	
				{
					$replacement[] = $reg[1];
					if ( is_numeric($reg[1]) )
						$email_data['body'] = str_replace($reg[1], get_bloginfo('url').'/show_file/'.$reg[1], $email_data['body'] );
				}				
			}
		}	
	}			
	return $email_data;	
}

// Вставить 
function usam_insert_email( $data, $attachments = [], $links = [] )
{
	$_email = new USAM_Email( $data );
	if ( $_email->save() )
	{
		$id = $_email->get('id');
		if ( !empty($attachments) )
			$_email->set_attachments( $attachments );		
		if ( !empty($links) )
		{
			require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
			usam_insert_ribbon(['event_id' => $id, 'event_type' => 'email'], $links);
		}		
	}
	else
		$id = false;
	return $id;
}
// Получить вложения
function usam_get_email_attachments( $id, $type = 'email' )
{
	$cache_key = 'usam_email_attachments';
	$data = wp_cache_get( $id, $cache_key ); 
	if( $data === false )
	{					
		$data = usam_get_files(['object_id' => $id, 'type' => $type]);		
		wp_cache_set( $id, $data, $cache_key );
	}	
	return apply_filters( $cache_key, $data );	
}

// Удалить 
function usam_delete_email( $id )
{
	$_email = new USAM_Email( $id );
	return $_email->delete();
}

function usam_get_email_objects( $email_id )
{
	$cache_key = 'usam_email_objects';
	$cache = wp_cache_get( $email_id, $cache_key );
	if( $cache === false )
	{	
		global $wpdb;	
		$cache = $wpdb->get_results( "SELECT object_id, object_type FROM ".USAM_TABLE_EMAIL_RELATIONSHIPS." WHERE email_id = '$email_id'" );						
		wp_cache_set( $email_id, $cache, $cache_key );
	}
	foreach ( $cache as $k => $value )
		$cache[$k]->object_id = (int)$cache[$k]->object_id;
	return $cache;
}

//Проверить перед вставкой объект
function usam_set_email_object( $email_id, $new_object )
{
	if ( !$email_id || !$new_object )
		return false;
	
	$objects = usam_get_email_objects( $email_id );
	foreach ( $objects as $object )
	{
		if ( $object->object_id == $new_object['object_id'] && $object->object_type == $new_object['object_type'] )
			return true;
	} 
	return usam_insert_email_object( $email_id, $new_object );
}

function usam_insert_email_object( $email_id, $object )
{
	global $wpdb;		
	
	if ( !$email_id || !$object )
		return false;
	$object['email_id'] = $email_id;	
	$format = [];
	foreach ( $object as $key => $value )
	{
		if ( $key == 'object_type' )
			$format[] = '%s';
		else
			$format[] = '%d';
	}	
	$result = $wpdb->insert( USAM_TABLE_EMAIL_RELATIONSHIPS, $object, $format );
	wp_cache_delete( $email_id, 'usam_email_objects' );
	return $wpdb->insert_id;	
}

function usam_spam_email( $ids )
{
	if ( is_numeric($ids) )
		$ids = array( $ids );
		
	require_once( USAM_FILE_PATH . '/includes/mailings/email_filter.class.php' );		
	$from_emails = array();
	foreach ( $ids as $id )	
	{
		$email = usam_get_email( $id );					
		if ( !in_array($email['from_email'], $from_emails ) )
		{
			$insert['if'] = 'sender';
			$insert['condition'] = 'equal';
			$insert['value'] = $email['from_email'];
			$insert['action'] = 'delete';
			$insert['mailbox_id'] = $email['mailbox_id'];
			usam_insert_email_filter( $insert );	
			$from_emails[] = $email['from_email'];
		}
	}
	if ( !empty($from_emails) )
	{
		$ids = usam_get_emails(['from_email' => $from_emails, 'fields' => 'id', 'mailbox' => 'user']);					
		foreach ( $ids as $id )	
		{
			usam_delete_email( $id );
		}	
		return count($ids);
	}
	return false;
}

function usam_add_contact_from_email( $ids )
{
	if ( is_numeric($ids) )
		$ids = array( $ids );
				
	$items = usam_get_emails(['include' => $ids]);	
	$emails = []; 
	foreach ( $items as $item )	
		$emails[] = $item->from_email;
	$emails = usam_get_emails(['emails' => $emails]);	
	$objects = []; 
	$ids = [];
	foreach ( $emails as $email )	
	{
		$contact_id = usam_insert_contact(['full_name' => $email->from_name, 'contact_source' => 'email', 'email' => $email->from_email]);		
		$ids[$email->id] = $contact_id;	
		$objects[$email->id] = $email;		
	}	
	unset($emails);
	require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
	require_once( USAM_FILE_PATH . '/includes/crm/ribbon_query.class.php' );	
	$ribbon = usam_get_ribbon_query(['event_id' => array_keys($ids), 'event_type' => 'email']);	
	foreach ( $ribbon as $value )	
	{
		usam_add_ribbon_link(['ribbon_id' => $value->id, 'object_id' => $ids[$value->event_id], 'object_type' => 'contact'], false );	
		unset($ids[$value->event_id]);
		unset($objects[$value->event_id]);
	}	
	foreach ( $ids as $id => $contact_id )
		usam_insert_ribbon(['event_id' => $id, 'event_type' => 'email', 'date_insert' => $objects[$id]->date_insert], ['object_id' => $contact_id, 'object_type' => 'contact']);
}

function usam_send_mails( $mailbox_id = '' ) 
{				
	$args = ['cache_results' => true, 'cache_meta' => true, 'cache_attachments' => true, 'cache_communication_errors' => true, 'folder' => 'outbox', 'number' => 10, 'order' => 'ASC', 'orderby' => 'date_insert'];		
	if ( $mailbox_id )		
		$args['mailbox_id'] = $mailbox_id;
	$emails = usam_get_emails( $args );	
	foreach ($emails as $email ) 
	{
		$_email = new USAM_Email( $email->id );
		$_email->send_mail();
	}
}

function usam_send_mail( $data, $attachments = [], $links = [] ) 
{	
	$result = false;
	$id = usam_insert_email( $data, $attachments, $links );
	if( $id )
	{
		$_email = new USAM_Email( $id );	
		$_email->send_mail();	
		$result = $_email->get_data();		
	}
	return $result;	
}

// Обработка получения и отправки сообщений
function usam_download_email_pop3_server( $mailbox_id ) 
{			
	$mailboxes = new USAM_POP3( $mailbox_id );	
	if ( $mailboxes->connect_open() )
		$result = $mailboxes->download_messages();
	else
		$result = false;
	return $result;
}

function usam_email_replace_body( $matches ) 
{	
	$count = substr_count( $matches[0], '>' );
	$replace = str_repeat('&emsp;', $count);	
	  
	return "<br>".$replace;
}

function usam_send_mail_by_id( $args, $attachments = [], $links = [] )
{
	if ( empty($args['message']) && empty($attachments) )
		return false;	
	
	if ( empty($args['mailbox_id']) || !is_numeric($args['mailbox_id']) )
		$mailbox = usam_get_primary_mailbox();
	else
		$mailbox = usam_get_mailbox( $args['mailbox_id'] );
	if ( !empty($mailbox['email']) )
	{
		$args['mailbox_id'] = $mailbox['id'];
		$args['to_email'] = $args['email'];
		$args['to_name'] = $args['name'];
		
		$style = new USAM_Mail_Styling( $mailbox['id'] );
		$args['body'] = $style->get_message( $args['message'] );								
		$_email = new USAM_Email( $args );
		$_email->save();		
		if ( !empty($attachments) )
			$_email->set_attachments( $attachments );		
		$email_sent = $_email->send_mail();		
	}		
	else
	{
		$email_sent = false;
		usam_log_file('Не указана основная почта в реквизитах');
	}
	return $email_sent;	
}

/**
 * Отправка почты
 */
function usam_mail( $address, $subject, $message, $headers = '', $attachments = array() )
{
	if ( empty($message) )
		return false;	
	
	if ( $headers == '' )
	{
		$mailbox = usam_get_primary_mailbox();
		if ( empty($mailbox['email']) )
		{
			usam_log_file('Не указана основная почта в реквизитах');
			return false;
		}
		$headers = "From: ".$mailbox['name']." <".$mailbox['email'].">" . "\r\n";

		$style = new USAM_Mail_Styling( $mailbox['id'] );
		$message = $style->get_message( $message );			
	}
	add_action( 'phpmailer_init', 'usam_php_mailer', 100 );
	$anonymous_function = function($a) { return "text/html"; };	
	add_filter( 'wp_mail_content_type', $anonymous_function);		
	$email_sent = wp_mail( $address, $subject, $message, $headers, $attachments );
	remove_filter('wp_mail_content_type', $anonymous_function );
	remove_action( 'phpmailer_init', 'usam_php_mailer', 100 );
	return $email_sent;	
}

function usam_php_mailer( $phpmailer ) 
{	
	$mailbox = usam_get_mailbox( $phpmailer->From, 'email' );
	$server = usam_get_mailbox_metadata( $mailbox['id'], 'smtpserver' );
	if ( !$server )
		return true;
				
	$phpmailer->Mailer = 'smtp';
	$phpmailer->Host   = $server;
	$phpmailer->Port   = usam_get_mailbox_metadata( $mailbox['id'], 'smtpport' );
	
	$user = usam_get_mailbox_metadata( $mailbox['id'], 'smtpuser' );
	if ( $user )
	{ // использовать имя и пароль для аутентификации.		
		$phpmailer->SMTPAuth = true; 			
		$phpmailer->Username = $user;
		$phpmailer->Password = usam_get_mailbox_metadata( $mailbox['id'], 'smtppass' );
	}
	$smtp_secure = usam_get_mailbox_metadata( $mailbox['id'], 'smtp_secure' );
	if( $smtp_secure && $smtp_secure != 'none' )
		$phpmailer->SMTPSecure = $smtp_secure;
	if( $smtp_secure != 'tls' )
		$phpmailer->SMTPAutoTLS = false;		
}

function usam_remove_email_system_tags( $message )
{
	$ids = array( 'open_link', 'object_type', 'mail_id');
	foreach( $ids as $id)
	{
		$matches = usam_get_tag( 'id', $id, $message, 'span' );				
		if ( $matches[0])
		{
			foreach($matches[0] as $value) 	
			{		
				$message = str_ireplace($value, "", $message);
			}
		}
	}
	return $message;
}

function usam_delete_emails( $args, $delete = false ) 
{	
	global $wpdb;	
	
	$args['fields'] = ['id', 'type', 'folder', 'mailbox_id'];
	$args['number'] = 50000;
	$args['cache_meta'] = true;
	
	$emails = usam_get_emails( $args );					
	if ( empty($emails) )
		return 0;
	
	$result = count($emails);
		
	$objects = [];
	$ids = [];
	$update_ids = [];
	$server_message_ids = [];
	foreach( $emails as $key => $email)
	{
		$objects[$email->mailbox_id][$email->folder] = $email->folder;	 
		if( $email->folder == 'deleted' || $delete )
		{
			$ids[] = $email->id;			
			$mailbox = usam_get_mailbox( $email->mailbox_id );		
			if ( $mailbox['delete_server_deleted'] )	
				$server_message_ids[$email->mailbox_id][] = usam_get_email_metadata( $email->id, 'server_message_id' );		
			do_action( 'usam_email_before_delete', (array)$email );
		}		
		else
			$update_ids[] = $email->id;
		unset($emails[$key]);
	}		 
	if ( !empty($update_ids) )
	{
		$wpdb->query("UPDATE ".USAM_TABLE_EMAIL." SET `folder`='deleted' WHERE id IN (".implode(",", $update_ids).")");
		foreach($update_ids as $id)
		{
			usam_update_email_metadata($id, 'date_delete', date("Y-m-d H:i:s") );
		}
	}
	if ( !empty($ids) )
	{	
		usam_delete_object_files( $ids, 'R');	
		usam_delete_object_files( $ids, 'email');
		require_once( USAM_FILE_PATH . '/includes/crm/ribbon.class.php' );	
		usam_delete_ribbon(['event_id' => $ids, 'event_type' => 'email']);
		$wpdb->query("DELETE FROM ".USAM_TABLE_EMAIL_META." WHERE email_id IN ('".implode("','", $ids)."')");	
		$wpdb->query("DELETE FROM ".USAM_TABLE_EMAIL_RELATIONSHIPS." WHERE email_id IN ('".implode("','", $ids)."')");	
		$wpdb->query("DELETE FROM ".USAM_TABLE_EMAIL." WHERE id IN ('".implode("','", $ids)."')");			
	}
	$email_folders = array();	
	foreach( usam_get_email_folders(['mailbox_id' => array_keys($objects), 'cache_results' => true]) as $folder)
	{
		$email_folders[$folder->mailbox_id][$folder->slug] = $folder->id;		
	} 
	if ( !empty($email_folders) )
	{
		foreach( $objects as $mailbox_id => $folders)
		{
			foreach($folders as $folder)
			{ 
				if ( isset($args['folder']) )
					usam_update_email_folder( $email_folders[$mailbox_id][$folder], ['count' => 0, 'not_read' => 0]);	
				else
				{
					$results = usam_get_emails(['fields' => 'read', 'mailbox_id' => $mailbox_id, 'folder' => $folder]);
					$not_read = 0;
					foreach( $results as $read)
					{
						if ( $read == 0 )
							$not_read++;
					}
					$update = array( 'count' => count($results), 'not_read' => $not_read );
					usam_update_email_folder( $email_folders[$mailbox_id][$folder], $update );
				}
			}		
		}
	}
	foreach( $server_message_ids as $mailbox_id => $uid )
	{
		$pop3 = new USAM_POP3( $mailbox_id );
		$pop3->delete_messages( $uid );
		$pop3->disconnect( );
		unset($server_message_ids[$mailbox_id]);
	}
	return $result;	
}

function usam_get_email_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('email', $object_id, USAM_TABLE_EMAIL_META, $meta_key, $single );
}

function usam_update_email_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('email', $object_id, $meta_key, $meta_value, USAM_TABLE_EMAIL_META, $prev_value );
}

function usam_delete_email_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('email', $object_id, $meta_key, USAM_TABLE_EMAIL_META, $meta_value, $delete_all );
}

function usam_mail_error_log( $wp_error ) 
{ 
	foreach( $wp_error->errors as $error) 	
		usam_log_file( $error, 'e-mail' );
}
add_action( 'wp_mail_failed', 'usam_mail_error_log', 10 );
?>