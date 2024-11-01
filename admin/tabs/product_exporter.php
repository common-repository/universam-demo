<?php
class USAM_Tab_Product_Exporter extends USAM_Page_Tab
{	
	protected $display_save_button = false;	
	
	public function get_title_tab()
	{			
		return __('Экспорт товаров', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'product_export', 'title' => __('Добавить шаблон', 'usam')]];
	}
}