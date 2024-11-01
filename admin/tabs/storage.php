<?php
class USAM_Tab_Storage extends USAM_Page_Tab
{
	public function get_title_tab()
	{			
		return __('Ваши склады, магазины или офисы продаж', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'storage', 'title' => __('Добавить', 'usam')]];			
	}
}