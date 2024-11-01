<?php
/*
	Name: Встроенный модуль
	Description: Валидация электронных адресов необходимая при массовых рассылках
	Price: free
	Group: email-verification
*/
require_once( USAM_FILE_PATH . '/includes/email_verification.class.php' );	
class USAM_Application_universam extends USAM_Email_Verification
{	
	public function email_verification( $email )
	{ 
		$host = substr (strstr ($email, '@'), 1);
		$host .= ".";		
		if ( getmxrr ($host, $mxhosts[0], $mxhosts[1]) == true)  
			array_multisort ($mxhosts[1], $mxhosts[0]);
		else 
		{ 
			$mxhosts[0] = array( $host );
		}	
		$port = 25;
		$localhost = $_SERVER['HTTP_HOST'];
		$sender = 'info@' . $localhost;
		
		if ( !function_exists ("fsockopen") )
			return false;
		
		$result = false;
		$id = 0; 	
		while ( $id < count($mxhosts[0]) )
		{ 		
			$connection = @fsockopen ($mxhosts[0][$id], 25, $errno, $error, 10);	
			if ( $connection )
			{ 				
				fputs ($connection,"HELO $localhost\r\n"); // 250
				$data = fgets ($connection,1024);
				$response = substr ($data,0,1);
				if ($response == '2') // 200, 250 etc.
				{
					fputs ($connection,"MAIL FROM:<$sender>\r\n");
					$data = fgets($connection,1024);
					$response = substr ($data,0,1);
					if ($response == '2') // 200, 250 etc.
					{
						fputs ($connection,"RCPT TO:<$email>\r\n");
						$data = fgets($connection,1024);
						$response = substr ($data,0,1);
						if ($response == '2') // 200, 250 etc.
						{
							fputs ($connection,"DATA\r\n");							
							$data = fgets($connection,1024);	
							$response = substr ($data,0,1);		
							if ($response == '2') // 200, 250 etc.
								$result = true;
							else
								$result = 'exceeded_storage';
						}
						else
							$result = 'temporarily_blocked';
					}
					else
						$result = 'rejected_email';						
				}
				fputs ($connection,"QUIT\r\n");
				fclose ($connection);
				if ( $result === true ) 
				{
					return true;
				}
			}
			$id++;
		}  
		return $result;
	}	
	
	public function emails_verification( $emails = array() ) 
	{	
		if ( !empty($emails) )
		{
			usam_create_system_process( __("Проверка электронных адресов", "usam" ), array( 'id' => $this->id, 'emails' => $emails), 'verify_emails', count($emails), 'verify_emails' );
		}
		else
		{
			global $wpdb;	
			$count = $wpdb->get_var("SELECT COUNT(*) FROM `".USAM_TABLE_CONTACT_META."` AS meta LEFT OUTER JOIN `".USAM_TABLE_COMMUNICATION_ERRORS."` AS com_error ON (meta.meta_value=com_error.communication) WHERE meta.meta_key LIKE '%email%' AND meta.meta_value!='' AND com_error.id IS NULL");
			if ( $count )
			{ 
				usam_create_system_process( __("Проверка электронных адресов контактов", "usam" ), 0, 'contacts_verify_email', $count, 'contacts_verify_email' );
			}
		}
	}
}
?>