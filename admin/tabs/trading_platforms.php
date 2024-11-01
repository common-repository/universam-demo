<?php
class USAM_Tab_Trading_Platforms extends USAM_Tab
{	
	public function get_title_tab()
	{			
		return __('Выгрузки товаров в торговые площадки', 'usam');	
	}
		
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'trading_platform', 'title' => __('Новая выгрузка', 'usam')]];					
	}
}