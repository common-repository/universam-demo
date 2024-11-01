<?php
class USAM_Tab_basket extends USAM_Tab
{		
	public function get_title_tab()
	{			
		return __('Правила скидок для корзин', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'basket_discount', 'title' => __('Добавить', 'usam')]];			
	}	
}
?>