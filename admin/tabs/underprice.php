<?php
class USAM_Tab_underprice extends USAM_Tab
{
	public function get_title_tab()
	{			
		return __('Наценки', 'usam');	
	}	
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'underprice', 'title' => __('Добавить', 'usam') ]];			
	}	
}