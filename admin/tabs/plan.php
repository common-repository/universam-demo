<?php
class USAM_Tab_plan extends USAM_Tab
{	
	public function get_title_tab()
	{			
		return __('План продаж', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'plan', 'title' => __('Добавить план', 'usam')]];
	}
}