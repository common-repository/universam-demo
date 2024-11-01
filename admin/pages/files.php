<?php
/*
 * Отображение страницы Файлы
 */ 
class USAM_Tab extends USAM_Page_Tab
{				
	protected function localize_script_tab()
	{ 	
		return array(			
			'download_folder_nonce' => usam_create_ajax_nonce( 'download_folder' ),		
			'download_file_nonce' => usam_create_ajax_nonce( 'download_file' ),			
		);
	}
}