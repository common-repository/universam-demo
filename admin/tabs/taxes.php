<?php
class USAM_Tab_Taxes extends USAM_Tab
{		
	public function get_title_tab()
	{					
		return __('Управление налогами', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return array( array('form' => 'edit', 'form_name' => 'tax', 'title' => __('Добавить', 'usam') ) );			
	}
}