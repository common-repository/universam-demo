<?php
class USAM_Tab_buyer_refunds extends USAM_Page_Tab
{
	public function get_title_tab()
	{
		return __('Возвраты товаров от покупателей', 'usam');
	}
	
	protected function get_tab_forms()
	{
		return [['action' => 'new', 'title' => __('Добавить', 'usam')]];	
	}
}