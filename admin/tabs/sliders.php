<?php
class USAM_Tab_Sliders extends USAM_Page_Tab
{	
	public function get_title_tab()
	{			
		return __('Слайдер', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'slider', 'title' => __('Добавить', 'usam')]];			
	}
}