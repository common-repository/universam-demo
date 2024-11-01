<?php
class USAM_Tab_products_on_internet extends USAM_Tab
{	
	public function get_title_tab()
	{			
		return __('Ваш товар в интернете', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['action' => 'products_search', 'title' => __('Найти товар', 'usam') ], ['action' => 'cleaning', 'title' => __('Удалить всё', 'usam') ]];			
	}	
}