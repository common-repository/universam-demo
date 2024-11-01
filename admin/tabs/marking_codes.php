<?php
class USAM_Tab_marking_codes extends USAM_Page_Tab
{		
	public function get_title_tab()
	{			
		return __('Маркировочные коды товаров на складе', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return array( array('form' => 'edit', 'form_name' => 'marking_code', 'title' => __('Добавить', 'usam') ) );			
	}
}