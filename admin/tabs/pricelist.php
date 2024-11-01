<?php
class USAM_Tab_pricelist extends USAM_Tab
{		
	public function get_title_tab()
	{			
		return __('Прайс-лист', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'pricelist', 'title' => __('Добавить', 'usam')]];
	}		
}