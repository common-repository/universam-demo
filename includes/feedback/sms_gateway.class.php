<?php
// Phone Gateway
abstract class USAM_SMS_Gateway 
{
	protected $login = null;
	protected $password = null;
		
	function __construct(  ) 
	{		
		$sms_gateway = get_option( 'usam_sms_gateway_option' );
		$this->login = $sms_gateway['login'];
		$this->password = $sms_gateway['password'];					
	}
	
	public function send_message( $phone, $message, $naming = '' )
	{			
		if ( empty($phone) || empty($message) )
			return false;
		
		$result = false;
		require_once( USAM_FILE_PATH .'/includes/feedback/sms_query.class.php'  );		
		$sms = usam_get_sms_query(['date_query' => array( 'year' => date('Y'), 'month'  => date('n') ), 'fields' => 'id', 'folder' => 'sent']);					
				
		$max_number = get_option("usam_max_number_of_sms_month");	
		if ( empty($max_number) || count($sms) <= $max_number )
			$result = $this->send( $phone, $message, $naming );	
		else
			$this->set_error( sprintf( __('Достигнут установленный лимит в %d СМС отправлений в месяц. Увеличьте лимит или отключите.','usam'), $max_number) );
		return $result;
	}	
	
	protected function get_headers(  )
	{
		return array();
	}
	
	protected function send_request( $params, $function )
	{									
		$headers = $this->get_headers(); 
		$data = wp_remote_post($this->API_URL.$function, ['body' => $params, 'sslverify' => true, 'timeout' => 5, 'headers' => $headers]);
		if ( is_wp_error($data) )
		{
			$message = $data->get_error_message();
			$this->set_error( $message );	
			return false;
		}					
		return $data['body'];		
	}
	
	protected function set_error( $error )
	{
		usam_log_file( __('Ошибки смс шлюза:','usam').' '.$error );
	}
}

function usam_check_sms_gateway_connected()
{
	$sms_gateway = get_option( 'usam_sms_gateway' );	
	if ( !$sms_gateway )
		return false;
	$filename = USAM_APPLICATION_PATH ."/sms/{$sms_gateway}.php";
	if ( file_exists($filename) )
		return true;
	return false;
}

/**
 * Отправка смс сообщения
 */
function usam_send_sms( $phone, $message )
{	
	$sms_gateway = get_option( 'usam_sms_gateway' );	
	$filename = USAM_APPLICATION_PATH ."/sms/{$sms_gateway}.php";	
	if ( USAM_DISABLE_INTEGRATIONS )
	{
		usam_log_file( $phone."\n".$message, 'sms' );
		return true;
	}
	if ( file_exists($filename) ) 
	{
		$phone = preg_replace('/^89/',79, $phone);
	
		$title = get_option( 'usam_sms_gateway_name' ); 			
		require_once( $filename );		
		$class_name = "USAM_SMS_Gateway_$sms_gateway";	
		$gateway = new $class_name();			
		$number_message = $gateway->send_message( $phone, $message, $title );		
	}
	else
		$number_message = false;
	return $number_message;	
}

function usam_add_send_sms( $args, $links = [] )
{		
	if ( !empty($args['message']) && !empty($args['phone']))
	{
		$contact_ids = usam_get_contact_ids_by_field(['mobile_phone', 'phone'], $args['phone']);
		foreach ($contact_ids as $contact_id )
			$links[] = ['object_id' => $contact_id, 'object_type' => 'contact'];	
		
		$company_ids = usam_get_company_ids_by_field(['mobile_phone', 'phone'], $args['phone']);
		foreach ($company_ids as $company_id )
			$links[] = ['object_id' => $company_id, 'object_type' => 'company'];
	
		$args['phone'] = preg_replace('/^89/',79, $args['phone']);
		$number_message = usam_send_sms( $args['phone'], $args['message'] );	
		$args['folder'] = 'outbox';			
		if ( $number_message )
		{				
			$args['folder'] = 'sent';
			$args['sent_at'] = date( "Y-m-d H:i:s" );	
			$args['server_message_id'] = $number_message;	
		} 
		$id = usam_insert_sms( $args, $links );
	}
	else
		$id = false; 
	return $id;	
}
?>