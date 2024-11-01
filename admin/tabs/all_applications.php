<?php
class USAM_Tab_all_applications extends USAM_Page_Tab
{		
	protected $views = ['grid'];
		
	public function get_title_tab()
	{			
		return __('Все приложения', 'usam');	
	}
}
?>