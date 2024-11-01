<?php
final class USAM_Exchange_Events
{	
	public function __construct()
	{				
		add_action( 'usam_new_letter_received', [$this, 'load_letter'], 10, 2 );
	}	

	public function load_letter( $email, $mailbox )
	{
		$attachments = usam_get_email_attachments( $email['id'] );
		if ( !empty($attachments) )
		{
			require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rules_query.class.php'  );
			require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
			$query = ['exchange_option' => 'email', 'file_data' => $email['from_email'], 'meta_query' => ['relation' => 'OR',['key' => 'to_email', 'value' => $email['to_email'], 'compare' => '='], ['key' => 'to_email', 'value' => '', 'compare' => '=']]];			
			$rules = usam_get_exchange_rules( $query );
			foreach($rules as $k => $rule)
			{
				$subject = usam_get_exchange_rule_metadata( $rule->id, 'subject' );	
				if ( $subject == '' || stripos($email['title'], $subject) !== false )
				{	
					$rule = (array)$rule;
					$rule['exchange_option'] = 'file';
					foreach($attachments as $attachment)
					{						
						$rule['file_data'] = (array)$attachment;
						usam_start_exchange( $rule );
					}
				}
			}
		}		
	}	
}
new USAM_Exchange_Events();
?>