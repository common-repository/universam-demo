<?php
class USAM_Tab_discount extends USAM_Tab
{	
	public function get_title_tab()
	{ 	
		if ( $this->table == 'discount' )	
			return __('Скидки на товар', 'usam');
		elseif ( $this->table == 'fix_price' )
			return __('Скидочная цена', 'usam');
	}	
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'product_discount', 'title' => __('Новая процентная скидка', 'usam')], ['form' => 'edit', 'form_name' => 'fix_price_discount', 'title' => __('Новая скидочная цена', 'usam')]];		
	}
	
	public function get_tab_sections() 
	{ 
		return ['discount' => ['title' => __('Процентная скидка','usam'), 'type' => 'table'], 'fix_price' => ['title' => __('Скидочная цена','usam'), 'type' => 'table']];
	}
}