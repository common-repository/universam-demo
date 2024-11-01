<?php
/*
 * Отображение страницы CRM
 */ 
 	
class USAM_Tab extends USAM_Page_Tab
{				
	protected function localize_script_tab()
	{ 
		return [
			'change_task_participants_nonce'  => usam_create_ajax_nonce( 'change_task_participants' ),	
			'company_connection_nonce'      => usam_create_ajax_nonce( 'company_connection' ),				
			'change_subscriber_list_nonce'  => usam_create_ajax_nonce( 'change_subscriber_list' ),				
			'id' => isset($_GET['id'])?$_GET['id']:0,
			'add_event_text'  => __('Добавить событие', 'usam'),	
			'add_call_text'   => __('Добавить звонок', 'usam'),			
			'add_meeting_text' => __('Добавить встречу', 'usam'),	
			'add_contact_text' => __('Контакт', 'usam'),			
			'add_company_text' => __('Компания', 'usam'),						
			'combine_duplicate_nonce' => usam_create_ajax_nonce( 'combine_duplicate' ),					
		];		
	}
	
	public function add_to_footer() 
	{ 
		require_once( USAM_FILE_PATH . "/admin/includes/modal/add_event.php" );
	}
} 