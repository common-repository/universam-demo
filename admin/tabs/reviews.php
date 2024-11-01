<?php
class USAM_Tab_reviews extends USAM_Tab
{
	protected $views = ['table'];	
	public function get_title_tab()
	{		
		if ( $this->view == 'report' )
			return __('Отчеты по отзывам', 'usam');				
		else
			return __('Отзывы посетителей', 'usam');	
	}		
		
	public function get_tab_sections() 
	{ 
		$tables = [];
		if ( $this->view == 'settings' )
		{ 
			$tables = $this->get_settings_tabs();
			array_unshift($tables, array('title' => __('Назад','usam') ) );		
		}		
		return $tables;
	}	
}
