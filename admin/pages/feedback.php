<?php
add_filter( 'usam_display_tab_name', 'usam_display_feedback_tab_name', 10, 2 );
function usam_display_feedback_tab_name( $title, $tab_id )
{
	switch ( $tab_id ) 
	{		
		case 'email' :
			$user_id = get_current_user_id();
			$mailboxes = usam_get_mailboxes( array( 'fields' => 'id', 'user_id' => $user_id ) );
			$email = 0;
			if ( !empty($mailboxes) )
			{					
				$reads = usam_get_email_folders( array('fields' => 'not_read', 'read' => 0, 'slug' => 'inbox', 'mailbox_id' => $mailboxes) );
				foreach ( $reads as $number ) 
					$email += $number;		
			}
			$title .= usam_get_style_number_message( $email );
		break;		
		case 'reviews' :
			global $wpdb;
			$reviews = (int)$wpdb->get_var("SELECT  COUNT(*) FROM `".USAM_TABLE_CUSTOMER_REVIEWS."` WHERE `status`= '1'");
			$title .= usam_get_style_number_message( $reviews );
		break;	
		case 'contacting' :		
			require_once(USAM_FILE_PATH.'/includes/crm/contactings_query.class.php');
			$title .= usam_get_style_number_message( usam_get_contactings(['fields' => 'count', 'status' => ['not_started', 'started'], 'number' => 1]) );
		break;	
		case 'chat' :
			$title .= usam_get_style_number_message( usam_get_number_new_message_dialogues() );
		break;				
	}
	return $title;
}
/*
 * Отображение страницы Обратная связь
 */ 
class USAM_Tab extends USAM_Page_Tab
{		
	protected function localize_script_tab()
	{	
		return array(				
			'add_email_folder_nonce'        => usam_create_ajax_nonce( 'add_email_folder' ),	
			'read_email_folder_nonce'       => usam_create_ajax_nonce( 'read_email_folder' ),	
			'delete_duplicate_nonce'        => usam_create_ajax_nonce( 'delete_duplicate' ),				
			'clear_email_folder_nonce'      => usam_create_ajax_nonce( 'clear_email_folder' ),	
			'remove_email_folder_nonce'     => usam_create_ajax_nonce( 'remove_email_folder' ),			
			'change_email_folder_nonce'     => usam_create_ajax_nonce( 'change_email_folder' ), 				
			'display_sms_nonce'             => usam_create_ajax_nonce( 'display_sms' ),			
			'get_mail_template_nonce'       => usam_create_ajax_nonce( 'get_mail_template' ),	
			'test_mailbox_nonce'            => usam_create_ajax_nonce( 'test_mailbox' ),
			'message_add_folder'            => __('Папка добавлена','usam') 			
		);		
	}
} 	