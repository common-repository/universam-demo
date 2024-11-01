<?php
/**
 * Класс уведомлений
 * @since 3.7
*/
abstract class USAM_Sending_Notification
{		
	protected $plaintext_message = '';
	protected $html_message = '';
	protected $sms_message = '';
	
	protected $object_type = '';
	protected $object_id = '';
	
	protected $email = '';
	protected $phone = '';	
	
	protected function process_plaintext_args() 
	{
		$message = $this->get_raw_message();
		$args = $this->get_plaintext_args();
		
		$shortcode = new USAM_Shortcode();
		return $shortcode->process_args( $args, $message );
	}
	
// из аргументов собрать строку для письма
	protected function process_html_args() 
	{					
		$message = $this->get_raw_message();		
		$args = $this->get_html_args();
	
		$shortcode = new USAM_Shortcode();
		$html = $shortcode->process_args( $args, $message );
		
		$html = wpautop( $html );		
		$html = str_replace( "<br />", '<br /><br />', $html);	
		return $html;
	}
	
	protected function process_sms_message_args() 
	{
		$message = $this->get_raw_sms_message();
		$args = $this->get_plaintext_args();	
		$shortcode = new USAM_Shortcode();
		return $shortcode->process_args( $args, $message );
	}
	
	protected function get_plaintext_args() 
	{				
		return $this->get_args();
	}
	
	protected function get_html_args() 
	{				
		return $this->get_args();
	}
	
	protected function get_args() 
	{	
		return array();
	}

	public function get_raw_message() 
	{	
		return '';
	}
	
	public function get_raw_sms_message() 
	{	
		return '';
	}	
	
	public function get_subject() 
	{
		return '';
	}
	
	public function set_address( $email ) 
	{
		$this->email = $email;
	}

	public function get_address() 
	{
		return $this->email;
	}
	
	public function set_phone( $phone ) 
	{
		$this->phone = $phone;
	}
	
	public function get_phone() 
	{ 
		return $this->phone;
	}
	
	public function get_attachments() 
	{
		return array();
	}

	public function get_html_message() 
	{
		$mailbox = usam_get_primary_mailbox();	
		$style = new USAM_Mail_Styling( $mailbox['id'] );
		$message = $style->get_message( $this->html_message );
		return $message;
	}

	public function get_plaintext_message() 
	{
		return $this->plaintext_message;
	}

	public function get_sms_message() 
	{
		return $this->sms_message;
	}	
	
//отправка писем
	public function send_mail() 
	{		
		$to_email = $this->get_address();
		if ( empty($to_email) )
			return;
		
		$message = $this->get_html_message();
		if ( empty($message) )
			return;
		
		$subject       = $this->get_subject();
		$attachments = $this->get_attachments();		
	
		add_action( 'phpmailer_init', [$this, '_action_phpmailer_init_multipart'], 10, 1 );
	
		$mailbox = usam_get_primary_mailbox();	
		if ( empty($mailbox['id']) )
		{
			usam_log_file( 'Отправка уведомлений не возможна. Не указана основная электронная почта' );	
			return;		
		}				
		$args = $this->get_html_args();	
		$shortcode = new USAM_Shortcode();
		$subject = $shortcode->process_args( $args, $subject );
		
		$links = [];
		if ( $this->object_type != '' && $this->object_id != '' )
			$links[] = ['object_id' => $this->object_id, 'object_type' => $this->object_type];	
		
		$_email = new USAM_Email(['body' => $message, 'title' => $subject, 'to_email' => $to_email, 'mailbox_id' => $mailbox['id']]);
		$_email->save();	
		$_email->set_attachments( $attachments );
		$email_id = $_email->get('id');
		$email_sent = $_email->send_mail( $links );	
		
		remove_action( 'phpmailer_init', [$this, '_action_phpmailer_init_multipart'], 10, 1 );
		return $email_sent;		
	}
	
	//отправка смс
	public function send_sms() 
	{		
		$phone = $this->get_phone();		
		
		if ( empty( $phone ) )
			return;

		$message = $this->get_sms_message();			
		if ( empty($message) )
			return;		
				
		return usam_add_send_sms(['phone' => $phone, 'message' => $message], [['object_id' => $this->object_id, 'object_type' => $this->object_type]]);
	}
	
	public function _action_phpmailer_init_multipart( $phpmailer )
	{
		$phpmailer->AltBody = $this->plaintext_message;
	}
}
?>