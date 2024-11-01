<?php
class USAM_Tab_balance_information extends USAM_Page_Tab
{					
	public function get_title_tab()
	{			
		return  __('Информация об остатках', 'usam');
	}		
	
	protected function get_tab_forms()
	{
		return array( array('form' => 'edit', 'form_name' => 'balance_information', 'title' => __('Добавить таблицу', 'usam') ) );
	}
}