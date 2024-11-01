<?php
// Класс отвечает за отправку рассылки
final class USAM_Send_Newsletter
{
	private $contact = [];
	private $args = [];
	private $mailing;
	private $errors;
	
	function __construct( $newsletter_id, $args = array(), $contact = array() )
	{
		$this->contact = $contact;
		$this->args = [
			'name' => '', 
			'full_name' => '', 			
			'lastname' => '', 
			'firstname' => '', 
			'patronymic' => '', 		
			'address' => '', 				
			'postal_code' => '', 
			'company_name' => '', 
			'current_date' => date('d.m.Y'), 
		];			
		if ( !empty($args) )
			$this->set_args( $args );
		
		$this->mailing = usam_get_newsletter( $newsletter_id );	
		$this->mailing['body'] = htmlspecialchars_decode(usam_get_newsletter_metadata( $newsletter_id, 'body' ));
	}
	
	private function mail( $email, $subject, $message, $headers = [], $attachments = [] )
	{		
		$attachments = array_merge( $this->get_attachments(), $attachments );
		$message = str_replace("</body>", "<span style='color:#ffffff; font-size:10px'>".random_int(1000, 9999999)."</span></body>", $message); 
		return wp_mail( $email, $subject, $message, $headers, $attachments );		
	}
	
	private function get_attachments()
	{	
		static $attachments = null;		
		if ( $attachments === null )
		{
			$attachments = [];
			$files = usam_get_files(['object_id' => $this->mailing['id'], 'type' => 'newsletter']);
			foreach ( $files as $file ) 
			{ 		
				$attachments[$file->title.'.'.pathinfo(USAM_UPLOAD_DIR.$file->file_path, PATHINFO_EXTENSION)] = USAM_UPLOAD_DIR.$file->file_path;
			}
			$rule_ids = usam_get_array_metadata($this->mailing['id'], 'newsletter', 'pricelist');
			if ( $rule_ids )
			{
				require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
				require_once( USAM_FILE_PATH . '/includes/product/price_list.class.php' );
				$rules = usam_get_exchange_rules(['include' => $rule_ids, 'schedule' => 1, 'cache_results' => true, 'cache_meta' => true]);
				$delete_files = [];
				foreach ( $rules as $rule )
				{
					$dir = USAM_FILE_DIR."newsletter_".$this->mailing['id']."/";	
					$filename = $rule->name.'.'.usam_get_type_file_exchange($rule->type_file, 'ext');
					$filepath = $dir.$filename;
					if ( !file_exists($filepath) )
					{					
						if ( !is_dir($dir) ) 		
						{
							if ( !mkdir($dir, 0777, true) ) 
								continue;
						} 	
						$file_generation = usam_get_exchange_rule_metadata( $rule->id, 'file_generation' );											
						$billet_file_path = USAM_UPLOAD_DIR."exchange/exporter_".$rule->id.".".usam_get_type_file_exchange( $rule->type_file, 'ext' );
						if ( $file_generation && file_exists($billet_file_path) )
							copy($billet_file_path, $filepath);
						else
						{
							$export = new USAM_Price_List( $rule->id );	
							$output = $export->start();						
							$f = fopen($filepath, 'w+');
							fwrite($f, $output);	
							fclose($f);	
							unset($output);
							$delete_files[] = $filepath;
						}
					}
					$attachments[$rule->name] = $filepath;
					wp_cache_delete( $rule->id, 'usam_exchange_rule' );	
					wp_cache_delete( $rule->id, 'rule_meta' );	
				}
				unset($rules);
				if ( $delete_files )
				{
					$anonymous_function = function() use ( $delete_files ) { 
						foreach ( $delete_files as $file )
						{
							if ( file_exists($file) )
								unlink($file);
						}
					}; 
					register_shutdown_function($anonymous_function); 		
				}
			}				
		}
		return apply_filters( 'usam_newsletter_attachments', $attachments, $this->mailing );
	}
			
	// Отправка триггерной рассылки
	public function send_email_trigger( $email, $attachments = [], $links = [] )
	{				
		static $number = 0;
		if ( !is_email($email) || usam_check_communication_error($email, 'email') ) 
			return false;
		
		if ( $number > 0 )
			sleep(3);
		$anonymous_function = function($a) { return "text/html"; };	
		add_filter( 'wp_mail_content_type', $anonymous_function);	
		add_action( 'phpmailer_init', 'usam_php_mailer');	
		
		$headers = $this->get_header();			
		
		$stat_id = usam_set_mailing_user_stat(['newsletter_id' => $this->mailing['id'], 'communication' => $email]);			
		$message = $this->get_message( $stat_id );			
		$this->newsletter_shortcode( $email );
		$message = $this->process_args( $message );
		$subject = $this->process_args( $this->mailing['subject'] );
		
		$url = $this->get_subscribed_url();		
		$headers[] = "List-Unsubscribe: <".add_query_arg(['stat_id' => $stat_id, 'usam_action' => 'mailing_unsub'], $url ).">\r\n"; 		
		$message .= '<img style="width:1px;height:1px;" src="'.add_query_arg(['stat_id' => $stat_id, 'usam_action' => 'mailing_open'], $url ).'"/>';
		$sent = $this->mail( $email, $subject, $message, $headers, $attachments, $links );		
		if ( $sent )
		{
			usam_update_user_stat_newsletter( $stat_id, ['sent_at' => date("Y-m-d H:i:s"), 'status' => 1]);
			if ( $links )
			{
				$attachments = array_merge( $this->get_attachments(), $attachments );
				$files = [];
				foreach ( $attachments as $attachment_title => $attachment )
					$files[] = ['file_path' => $attachment, 'title' => $attachment_title];	
				usam_insert_email(['title' => $subject, 'body' => $message, 'to_email' => $email, 'mailbox_id' => $this->mailing['mailbox_id'], 'folder' => 'sent', 'sent_at' => date("Y-m-d H:i:s")], $files, $links);
			}
		}
		remove_action( 'phpmailer_init', 'usam_php_mailer' );	
		remove_filter('wp_mail_content_type', $anonymous_function );			
		$number++;
		return $sent;
	}	
	
	public function send_sms_trigger( $phone, $links = [] )
	{
		if ( empty($phone) || usam_check_communication_error($phone, 'phone') ) 
			return false;	
		$this->newsletter_shortcode( $phone, 'mobile_phone' );
		$message = $this->process_args( $this->mailing['body'] );				
		$sent = usam_add_send_sms(['phone' => $phone, 'message' => $message], $links);	
		return $sent;
	}	
	
	public function send_sms( $stat )
	{				
		$i = 0;
		foreach ( $stat as $item ) 
		{			
			$this->newsletter_shortcode( $item->communication, 'mobilephone' );			
			$message = $this->process_args( $this->mailing['body'] ); 			
			$sent = usam_send_sms( $item->communication, $message );
			if ( $sent )
			{		
				usam_update_user_stat_newsletter( $item->id, ['sent_at' => date( "Y-m-d H:i:s" ), 'status' => 1]);
				$i++;
			}
			else
			{
				$this->set_error( sprintf( __('Неизвестная ошибка отправки на %s','usam'),$item->communication ) );
			}	
		}			
		$this->set_log_file();
		if ( $i )
		{
			$number_sent = $this->mailing['number_sent'] + $i;
			$this->update(['number_sent' => $number_sent]);
		}	
		return $i;		
	}	
	
	public function send_mail( $stat )
	{	
		$url = $this->get_subscribed_url();				
		$headers = $this->get_header();	
	
		if ( !$headers )
			return false;		

		add_action( 'phpmailer_init', 'usam_php_mailer');			
		$anonymous_function = function($a) { return "text/html"; };	
		add_filter( 'wp_mail_content_type', $anonymous_function);	
		$i = 0;	
		$error = false;
		foreach ( $stat as $item ) 
		{	
			if ( !is_email($item->communication) || usam_check_communication_error($item->communication, 'email') )	
			{
				usam_update_user_stat_newsletter( $item->id, ['status' => 9] ); // Ошибка
				$i++;
			}
			else
			{			
				$mail_headers = $headers;
				$unsub_link = add_query_arg(['stat_id' => $item->id, 'usam_action' => 'mailing_unsub'], $url );			
				$mail_headers[] = "List-Unsubscribe: <".$unsub_link.">"; 
			
				$message = $this->get_message( $item->id );	
				$this->newsletter_shortcode( $item->communication );	
				
				$message = $this->process_args( $message ); 		
				$subject = $this->process_args( $this->mailing['subject'] );				
				$html = $this->get_button( $item->id );
				$message = str_replace("</body>", $html."</body>", $message);				
				if ( $this->mail($item->communication, $subject, $message, $mail_headers) )
				{
					$update = array( 'sent_at' => date( "Y-m-d H:i:s" ), 'status' => 1 );
					usam_update_user_stat_newsletter( $item->id, $update );
					$error = false;
					$i++;					
				}
				else
				{
					$this->set_error( sprintf( __('Неизвестная ошибка отправки на %s','usam'),$item->communication) );
					if ( $error )
						break; // две ошибки подряд прервать отправку.
					$error = true;
				}
				sleep(3);	
			}
		}		
		remove_action( 'phpmailer_init', 'usam_php_mailer' );	
		remove_filter('wp_mail_content_type', $anonymous_function );
	
		$this->set_log_file();
		if ( $i )
		{
			$number_sent = $this->mailing['number_sent'] +  $i;
			$this->update(['number_sent' => $number_sent]);
		}
		return $i;
	}
	
	// Обновить
	public function update( $update )
	{			
		usam_update_newsletter( $this->mailing['id'], $update );	
	}
	
	private function get_subscribed_url( )
	{			
		return $subscribed_url = usam_get_user_account_url('your-subscribed');
	}		
	
	// Полностью отправлено
	public function completely_sent( )
	{	
		$update = array( 'sent_at' => date( "Y-m-d H:i:s" ), 'status' => 6 );
		$this->update( $update );	
	}
// Добавить кнопки	
	private function get_button( $stat_id )
	{
		$url = $this->get_subscribed_url();	
		$open_link = add_query_arg(array('stat_id' => $stat_id, 'usam_action' => 'mailing_open'), $url );
		$unsub_link = add_query_arg(array('stat_id' => $stat_id, 'usam_action' => 'mailing_unsub'), $url );
		$edit_link = add_query_arg(array('stat_id' => $stat_id, 'usam_action' => 'mailing_edit'), $url );
		
		$html = '<p class="unsubscribe_container" style="font-family: Verdana, Geneva, sans-serif;font-size: 12px;color: #000000;color: #000000 !important;background-color: #FFFFFF;border: 0;text-align: center;padding-top: 8px;padding-right: 8px;padding-bottom: 8px;padding-left: 8px;">
	<a target="_blank" style="color: #000000;color: #000000 !important;background-color: #FFFFFF;border: 0;" href="'.$unsub_link.'">'.__('Отписаться','usam').'</a>&nbsp;-&nbsp;
	<a target="_blank" style="color: #000000;color: #000000
!important;background-color: #FFFFFF;border: 0;" href="'.$edit_link.'">'.__('Изменить параметры подписки на рассылку','usam').'</a><br><br>
</p><img style="width:1px;height:1px;" src="'.$open_link.'"/>';
		return $html;
	}

	private function get_message( $stat_id = 0 )
	{		
		$message = $this->mailing['body'];
		if ( empty($message) )
			return '';
		
		if ( empty($stat_id) )
			return $message;	
	
		$dom = new DOMDocument;
		@$dom->loadHTML( $message );
		foreach ($dom->getElementsByTagName('a') as $node) 
		{
			if( $node->hasAttribute( 'href' ) ) 
			{
				$url = $node->getAttribute( 'href' );	
				$url = add_query_arg(['stat_id' => $stat_id, 'usam_action' => 'm_click'], $url );				
				$node->setAttribute( 'href', $url );	
			}
		}
		$message = $dom->saveHTML();
		return $message;		
	}
	
	private function get_header( )
	{		
		$mailbox = usam_get_mailbox( $this->mailing['mailbox_id'] );
		if ( empty($mailbox) )
			return false;
					
		$headers[] = "From: ".$mailbox['name']." <".$mailbox['email'].">";		
		$headers[] = "Reply-To: ".$mailbox['name']." <".$mailbox['email'].">"; // Ответ возвращать
		$headers[] = 'Content-Type: text/html; charset=utf-8';		
	
	//	$headers[] = "Disposition-Notification-To: <".$this->mailing['from_email'].">";         // подтверждение о прочтении
	  //$headers[] = "X-Confirm-Reading-To: <".$this->mailing['from_email'].">";                // подтверждение о прочтении
	//	$headers[] = "Return-Receipt-To: <".$this->mailing['from_email'].">"; 	                // подтверждение о доставке	
		$headers[] = "X-email_id: <".$this->mailing['id'].">"; 
	//	$headers[] = "X-Priority: <3>";
	//	$headers[] = "List-id: <".$this->mailing['id'].">";
		return $headers;
	}
	
	public function send_mail_preview( $email )
	{							
		$message = $this->get_message();
		$mailbox = usam_get_primary_mailbox();
		
		$headers = array();
		$headers[] = "From: ".$mailbox['name']." <".$mailbox['email'].">";		
		$headers[] = "Reply-To: ".$mailbox['name']." <".$mailbox['email'].">"; // Ответ возвращать
		$headers[] = 'Content-Type: text/html; charset=utf-8';
	
		if ( !$email || empty($message) )
			return false;		
		
		add_action( 'phpmailer_init', 'usam_php_mailer' );			
		$sent = $this->mail( $email, $this->mailing['subject'], $message, $headers );		
		remove_action( 'phpmailer_init', 'usam_php_mailer' );	
		return $sent;
	}
	
	public function send_newsletter()
	{	
		global $wpdb;			
		
		if ( $this->mailing['type'] == 'mail' )
			$limit = 10;
		elseif ( $this->mailing['type'] == 'sms' )
			$limit = 1000;
		else
			$limit = 20;		
		$stat = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_NEWSLETTER_USER_STAT." WHERE status = '0' AND newsletter_id='".$this->mailing['id']."' LIMIT $limit" );		
		$result = false;	
		if ( !empty($stat) )
		{ 
			if ( $this->mailing['type'] == 'mail' )
				$result = $this->send_mail( $stat );
			elseif ( $this->mailing['type'] == 'sms' )
				$result = $this->send_sms( $stat );
		}				
		$count = count($stat);			
		if ( $count == 0 || $count < $limit )
		{
			$this->completely_sent( );			
		}
		return $result;
	}	
	
	private function set_args( $args )
	{
		$this->args = array_merge ($this->args, $args);
	}
	
	private function newsletter_shortcode( $email, $meta_key = 'email' )
	{
		if( !$this->contact )
			$this->contact = usam_get_contacts(['meta_value' => $email, 'meta_key' => $meta_key, 'source' => 'all', 'status' => 'all', 'number' => 1]);
		if ( !empty($this->contact) )	
		{		
			$birthday = usam_get_contact_metadata( $this->contact['id'], 'birthday' );
			if ( $birthday )
				$birthday = usam_local_date( $birthday, "d.m.Y" );			
			$args = [
				'name' => $this->contact['appeal'], 			
				'birthday' => $birthday, 	
				'address' => usam_get_full_contact_address( $this->contact['id'] ),				
				'postal_code' => usam_get_contact_metadata( $this->contact['id'], 'postcode' ), 
			];	
			foreach(['lastname', 'firstname', 'patronymic', 'full_name'] as $key )			
				$args[$key] = (string)usam_get_contact_metadata( $this->contact['id'], $key );
			$location = usam_get_contact_metadata( $this->contact['id'], 'location' );
			$locations = usam_get_address_locations( $location );
			if ( is_array($locations) )
				$args += $locations;			
	
			if ( stripos($this->mailing['body'], 'basket') !== false )
			{	
				require_once( USAM_FILE_PATH . '/includes/basket/users_basket_query.class.php' );
				require_once( USAM_FILE_PATH . '/includes/basket/products_basket_query.class.php' );
				$basket = usam_get_users_baskets(['contact_id' => $this->contact['id'], 'cache_results' => true, 'cache_meta' => true, 'number' => 1]);
				$args['basket'] = '';
				if ( $basket )
				{					
					$products = usam_get_products_baskets(['cart_id' => $basket['id'], 'orderby' => 'id', 'order' => 'ASC']);
					$file_name = usam_get_module_template_file( 'mailtemplate', $this->mailing['template'], 'basket.php' );
					ob_start();	
					include $file_name;			
					$args['basket'] = ob_get_clean();
				}
			}
		}
		else
		{
			$company = usam_get_companies(['meta_value' => $email, 'meta_key' => 'email', 'number' => 1]);	
			if ( !empty($company) )	
			{				
				$args = [ 
					'company_name' => $company['name'],
					'name' => $company['name'],
				];
			}
			else
			{										
				usam_insert_contact(['contact_source' => $meta_key, $meta_key => $email]); 
				$args = [];
			}
		}		
		$this->set_args( $args );
	}		
	
	private function process_args( $message )
	{ 		
		static $coupons_rules = null;
		if ( $coupons_rules === null )
			$coupons_rules = usam_get_coupons_rules(['rule_type' => 'send_newsletter', 'active' => 1, 'number' => 1]);						
		$this->args['coupon_code'] = '';
		if ( !empty($coupons_rules) )
		{
			foreach ( $coupons_rules as $rule ) 
			{
				$coupon = usam_get_coupon( $rule['coupon_id'] );	
				if ( !empty($coupon) )
				{		
					$coupon['value'] = $rule['discount'];
					$coupon['is_percentage'] = $rule['discount_type'];					
					$coupon['coupon_code'] = $value = usam_generate_coupon_code( $rule['format'], $rule['type_format'] );
					$coupon['description']   = sprintf( __('Из правила %s', 'usam'), '"'.$rule['title'].'"' );
					$coupon['active']        = 1;		
					$coupon['coupon_type']   = 'coupon';
					if ( usam_insert_coupon( $coupon ) )
					{
						$this->args['coupon_code'] = $coupon['coupon_code'];
						break;
					}
				}
			}
		}
		$shortcode = new USAM_Shortcode();
		$this->args = apply_filters( 'usam_triget_args', $this->args, $this->mailing );		
		return $shortcode->process_args( $this->args, $message, 'no' );
	}	

	public function set_error( $error )
	{
		$this->errors[] = sprintf( __('Отправка рассылки. Ошибка: %s'), $error );
	}
	
	public function set_log_file()
	{
		usam_log_file( $this->errors );
	}	
}
?>