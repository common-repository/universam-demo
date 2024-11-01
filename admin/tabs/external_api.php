<?php
class USAM_Tab_external_api extends USAM_Page_Tab
{
	protected $views = ['table'];	
	
	public function get_title_tab()
	{			
		return __('Подключенные интеграции', 'usam');	
	}	
}