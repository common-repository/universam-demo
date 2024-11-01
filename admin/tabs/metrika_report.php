<?php
class USAM_Tab_metrika_report extends USAM_Tab
{		
	public function get_tab_sections() 
	{  //view_metrika_report
		$tables = ['metrika_report' => ['title' => __('Посещаемость Яндекс','usam'), 'type' => 'table']
		//, 'attendance_report' => array( 'title' => __('Посещаемость','usam'), 'type' => 'table' ), 'sources_report' => array( 'title' => __('Источники','usam'), 'type' => 'table') 
		];
		return $tables;
	}
	
	public function get_title_tab()
	{					
		if ( $this->table == 'sources_report') 
			return __('Источники', 'usam');	
		elseif ( $this->table == 'metrika_report') 
			return __('Посещаемость из Яндекс Метрики и конверсия', 'usam');	
		else
			return __('Посещаемость сайта', 'usam');	
	}
}