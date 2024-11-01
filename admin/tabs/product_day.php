<?php
class USAM_Tab_product_day extends USAM_Tab
{		
	public function get_title_tab()
	{			
		return __('Товар дня', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'product_day', 'title' => __('Добавить очередь', 'usam') ]];			
	}
}