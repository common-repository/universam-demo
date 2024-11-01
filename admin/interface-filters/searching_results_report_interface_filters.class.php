<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/report_interface_filters.class.php' );
class searching_results_report_Interface_Filters extends Report_Interface_Filters
{	
	protected $search_box = true;
	protected function get_filters( ) 
	{				
		return [
			'weekday' => ['title' => __('Дни недели', 'usam'), 'type' => 'checklists'], 			
			'number_results' => ['title' => __('Показано результатов', 'usam'), 'type' => 'numeric'], 
		];
	}
}
?>