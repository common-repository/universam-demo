<?php
/*
 * Отображение управление персоналом
 */ 
class USAM_Tab extends USAM_Page_Tab
{		
	protected function localize_script_tab()
	{
		return array(				
			'bulk_actions_contacts_nonce' => usam_create_ajax_nonce( 'bulk_actions_contacts' ),	
			'add_bonus_nonce' => usam_create_ajax_nonce( 'add_bonus' ),	
		);
	}
} 