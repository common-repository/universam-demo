<?php
// Справочники
require_once( USAM_FILE_PATH . '/includes/application.class.php' );	
class USAM_Email_Verification extends USAM_Application
{
	public function get_job( ) 
	{ 
		return get_transient( 'usam_email_verification_job' );	
	}	
	
	public function emails_verification( $emails = [] ) 
	{	
		if ( empty($emails) )
		{
			global $wpdb;		
			$emails = $wpdb->get_col("SELECT meta.meta_value FROM `".USAM_TABLE_CONTACT_META."` AS meta LEFT OUTER JOIN `".USAM_TABLE_COMMUNICATION_ERRORS."` AS com_error ON (meta.meta_value=com_error.communication) WHERE meta.meta_key LIKE '%email%' AND meta.meta_value!='' AND com_error.id IS NULL LIMIT 1");
			if ( empty($emails) )
				return false;
		}
		$file_name = 'email_list.csv';
		$file_path = USAM_FILE_DIR.".$file_name";
		$string = implode("\n",$emails);
		$f = fopen($file_path,"w+");
		fwrite($f, $string );
		fclose($f);		
		$result = $this->send_emails_verification( $file_path );
		if ( $result )
			set_transient( 'usam_email_verification_job', $result, DAY_IN_SECONDS );			
		unlink( $file_path );		
		return $result;
	}	
	
	protected function send_emails_verification( $file_path )
	{
		return false;
	}
	
	public function filter_email_verification_mail( $result, $email )	
	{
		if ( !$result )
			return $this->email_verification( $email );
		else
			return $result;
	}
	
	public function service_load()
	{ 	
		add_filter('usam_email_verification_mail', [$this,'filter_email_verification_mail'], 10, 2);
	}
}
?>