<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_tasks_Reports_View extends USAM_Reports_View
{		
	protected $period = 'last_365_day';	
	protected function get_report_widgets( ) 
	{				
		$reports = [				
			[['title' => __('Задания по отделам', 'usam'), 'key' => 'tasks_by_departments', 'view' => 'graph'], ['title' => __('Авторы задания по отделам', 'usam'), 'key' => 'autor_tasks_by_departments', 'view' => 'graph']],
			[['title' => __('Задания по группам', 'usam'), 'key' => 'tasks_by_groups', 'view' => 'graph']],
			[['title' => __('Количество заданий', 'usam'), 'key' => 'number_tasks', 'view' => 'graph']],			
		];	
		return $reports;		
	}
}
?>