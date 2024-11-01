<?php
class USAM_Tab_crosssell extends USAM_Tab
{	
	public function get_title_tab()
	{			
		return __('Перекрестные продажи', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'crosssell', 'title' => __('Добавить', 'usam')]];					
	}
}