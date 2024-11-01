<?php
class USAM_Tab_blanks extends USAM_Tab
{
	public function get_title_tab()
	{			
		return __('Бланки', 'usam');	
	}
	
	protected function get_tab_forms()
	{	
		return [['form' => 'edit', 'form_name' => 'seal', 'title' => __('Добавить печать', 'usam')]];
	}
}