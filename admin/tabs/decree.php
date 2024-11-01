<?php
class USAM_Tab_decree extends USAM_Page_Tab
{		
	public function get_title_tab()
	{		
		return usam_get_document_name('decree', 'plural_name');
	}
		
	protected function get_tab_forms()
	{
		return [['action' => 'new', 'title' => __('Добавить', 'usam'), 'capability' => 'add_decree']];	
	}	
}
