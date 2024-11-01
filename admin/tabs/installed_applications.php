<?php
class USAM_Tab_installed_applications extends USAM_Page_Tab
{		
	protected $views = ['grid', 'table'];
		
	public function get_title_tab()
	{			
		return __('Установленные приложения', 'usam');	
	}
}
?>