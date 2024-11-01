<?php
function usam_email_verification_mail( $email )
{
	if ( !is_email($email)) 
		return 'invalid_email';
	
	return apply_filters('usam_email_verification_mail', false, $email );
}

function usam_sanitize_property( $value, $type ) 
{		
	switch ( $type )
	{				
		case 'mobile_phone':					
			$value = preg_replace('/[^0-9]/', '', $value);
			if ( strlen($value) == 10 )
				$value = '7'.$value;			
		break;			
		case 'phone':	
		case 'location':
		case 'integer':
		case 'file':
			$value = preg_replace('/[^0-9]/', '', $value);
		break;	
		case 'files':
			$value = array_map('intval', (array)$value);
		break;
		case 'number':
			$value = usam_string_to_float( $value );
		break;	
		case 'email':
			$value = str_replace(" ", "", strtolower($value));
			if ( !is_email($value))
				usam_handle_communication_error( $value, 'email', 'invalid_email' );
		break;	
		case 'date':		
			$value = $value?date("Y-m-d H:i:s", strtotime($value)):'';
		break;
		default:
			if ( is_array($value) )
			{
				$new = [];
				foreach( $value as $v )
				{
					if ( !is_array($v) )
					{
						$v = trim(stripcslashes($v));		
						$new[] = html_entity_decode( $v, ENT_QUOTES,'UTF-8');
					}
					else
						$new[] = $v;
				}
				$value = $new;
			}
			elseif( $value )
			{			
				$value = trim(stripcslashes($value));		
				$value = html_entity_decode( $value, ENT_QUOTES,'UTF-8');
			}	
		break;
	}	
	return $value;
}

function usam_handle_communication_error( $communication, $type, $status = 'invalid_email' )
{
	require_once( USAM_FILE_PATH . '/includes/crm/communication_error.class.php' );
	$communication_errors = usam_get_communication_errors( array( 'search' => $communication, 'communication_type' => $type ) );
	if ( empty($communication_errors ) )
		usam_insert_communication_error( array( 'communication' => $communication, 'communication_type' => $type, 'reason' => $status ) );
}

function usam_check_communication_error( $communication, $type )
{ 
	if ( $communication == '' )
		return false;
	
	$cache_key = 'usam_check_communication_error_'.$type;
	$result = wp_cache_get( $communication, $cache_key );
	if( $result === false  )
	{		
		$result = usam_get_communication_errors(['communication' => $communication, 'status' => 0, 'communication_type' => $type, 'fields' => 'reason', 'number' => 1]);
		wp_cache_set( $communication, $result, $cache_key );
	}		
	if ( !empty($result) )
		return true;
	else
		return false;
}

function usam_get_text_communication_errors(  )
{ 
	return array( 
		'email' => array('invalid_email' => __("Ошибка в адресе почты","usam"), 'rejected_email' => __("Электронная почта не существует","usam"), 'temporarily_blocked' => __("Электронная почта временно занесена в серый список","usam"), 'invalid_domain' => __("Доменное имя не существует","usam"), 'no_connect' => __("Сбой подключения SMTP-сервера","usam"), 'exceeded_storage'  => __("SMTP-сервер отклонил письмо. Превышено выделение памяти","usam") )
	);
}

function usam_get_text_communication_error( $type, $reason )
{ 
	$errors = usam_get_text_communication_errors();
	if ( isset($errors[$type]) )
	{
		if ( isset($errors[$type][$reason]) )
			return $errors[$type][$reason];
	}
	return __("Неизвестная ошибка","usam");
}


function usam_get_html_communication_error( $type, $reason )
{ 
	if ( $reason  )
	{
		return "<div class='text_validation_error'>".usam_get_text_communication_error( $type, $reason )."</div>";
	}
	return '';
}
?>