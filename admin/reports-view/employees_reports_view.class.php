<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_employees_Reports_View extends USAM_Reports_View
{		
	protected $period = 'last_365_day';		
	protected function get_report_widgets( ) 
	{				
		$reports = [
			[['title' => __('Общие показатели дел и заданий', 'usam'), 'key' => 'employees_total', 'view' => 'transparent']],						
			[['title' => __('Динамика добавлений новых дел','usam'), 'key' => 'add_tasks', 'view' => 'graph'], ['title' => __('Динамика выполнения заданий','usam'), 'key' => 'completed_tasks', 'view' => 'graph']],
			[['title' => __('Количество дел у менеджеров', 'usam'), 'key' => 'assignments_department', 'view' => 'graph'], ['title' => __('Количество бонусов менеджеров', 'usam'), 'key' => 'number_bonuses_managers', 'view' => 'graph']],	
			[['title' => __('Телефония', 'usam'), 'key' => 'telephony_total', 'view' => 'transparent']],		
			[['title' => __('Количество звонков', 'usam'), 'key' => 'calls', 'view' => 'graph']], 
		];	
		return $reports;
	}		
}
?>