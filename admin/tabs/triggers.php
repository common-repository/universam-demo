<?php
class USAM_Tab_triggers extends USAM_Page_Tab
{	
	public function get_title_tab()
	{			
		return __('Триггеры', 'usam');	
	}	
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'trigger', 'title' => __('Добавить', 'usam')]];			
	}
}