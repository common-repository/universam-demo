<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/report_interface_filters.class.php' );
class Company_report_Interface_Filters extends Report_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'company' => ['title' => __('Выберите компанию','usam'), 'type' => 'company'], 
			'weekday' => ['title' => __('Дни недели','usam'), 'type' => 'checklists'],  
			'manager' => ['title' => __('Менеджер','usam'), 'type' => 'checklists'], 
		];
	}		
}
?>