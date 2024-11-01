<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/report_interface_filters.class.php' );
class product_work_report_Interface_Filters extends Report_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'manager' => ['title' =>__('Менеджер', 'usam'), 'type' => 'select'], 
			'weekday' => ['title' => __('Дни недели', 'usam'), 'type' => 'checklists'], 
		];
	}	
}
?>