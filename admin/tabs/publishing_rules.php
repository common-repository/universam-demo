<?php
class USAM_Tab_publishing_rules extends USAM_Tab
{		
	public function get_title_tab()
	{			
		return __('Правила публикации', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'publishing_rule', 'title' => __('Добавить правило', 'usam')]];					
	}
}