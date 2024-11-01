<?php
require_once( USAM_FILE_PATH .'/includes/personnel/business_processes_query.class.php' );	
require_once( USAM_FILE_PATH .'/includes/personnel/business_process.class.php' );	
class USAM_Tab_business_processes extends USAM_Page_Tab
{	
	public function get_title_tab() 
	{			
		return __('Бизнес процессы', 'usam');	
	}
}