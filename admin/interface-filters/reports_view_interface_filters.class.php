<?php 
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class Reports_View_Interface_Filters extends USAM_Interface_Filters
{	
	protected $period = 'last_30_day';
	protected $search_box = false;
	protected function get_filters( ) 
	{				
		return [
			'period' => ['title' => __('Период', 'usam'), 'type' => 'period'], 
			'weekday' => ['title' => __('Дни недели', 'usam'), 'type' => 'checklists'], 			
			'manager' => ['title' => __('Ответственный менеджер', 'usam'), 'type' => 'checklists'], 
		];		
	}
}
?>