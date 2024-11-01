<?php
class USAM_Tab_showcases extends USAM_Page_Tab
{		
	public function get_title_tab()
	{		
		return __('Витрины', 'usam');
	}
		
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'showcase', 'title' => __('Добавить', 'usam') ]];	
	}	
}
