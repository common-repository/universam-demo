<?php
class USAM_Tab_Checks extends USAM_Page_Tab
{	
	public function get_title_tab()
	{
		return __('Кассовые чеки', 'usam');
	}
	
	protected function get_tab_forms()
	{	
		return [['action' => 'new', 'title' => __('Добавить', 'usam'), 'capability' => 'add_check']];	
	}	
}
