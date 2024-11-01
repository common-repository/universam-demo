<?php
class USAM_Tab_sets extends USAM_Tab
{		
	public function get_title_tab()
	{			
		return __('Наборы товаров', 'usam');	
	}

	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'set', 'title' => __('Добавить', 'usam') ]];			
	}	
}