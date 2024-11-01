<?php
class USAM_Tab_loyalty_programs extends USAM_Tab
{	
	public function get_title_tab()
	{			
		return __('Программы лояльности', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'loyalty_program', 'title' => __('Добавить', 'usam')]];			
	}
}