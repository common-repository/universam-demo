<?php
class USAM_Tab_keywords extends USAM_Tab
{		
	public function get_title_tab()
	{					
		return __('Семантическое ядро', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'keyword', 'title' => __('Добавить', 'usam')]];
	}
}