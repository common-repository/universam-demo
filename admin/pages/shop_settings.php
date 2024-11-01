<?php
/*
 * Настройки магазина 
 */ 
class USAM_Tab extends USAM_Page_Tab
{			
	protected  $display_save_button = true;		
		
	protected function localize_script_tab()
	{		
		return array(						
			'save_blank_nonce'                    => usam_create_ajax_nonce( 'save_blank' ),			
			'edit_blank_nonce'                    => usam_create_ajax_nonce( 'edit_blank' ),			
			'get_capabilities_nonce'              => usam_create_ajax_nonce( 'get_capabilities' ),				
			'text_or'                             => __('ИЛИ', 'usam'),
			'text_and'                            => __('И', 'usam'),
		);
	}	
} 