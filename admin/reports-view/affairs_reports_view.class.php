<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_affairs_Reports_View extends USAM_Reports_View
{		
	protected $period = 'last_365_day';	
	protected function get_report_widgets( ) 
	{				
		$reports = array(
			array( array('title' => __('Общие показатели', 'usam'), 'key' => 'affairs_total', 'view' => 'transparent' ) ),	
			array( array('title' => __('Сотрудники онлайн', 'usam'), 'key' => 'online_employee', 'view' => 'loadable_table' ), array( 'title' => __('Количество дел у менеджеров', 'usam'), 'key' => 'employee_affairs', 'view' => 'graph' ) ),
			array( array('title' => __('Динамика добавлений новых дел','usam'), 'key' => 'add_affairs', 'view' => 'graph'), array('title' => __('Динамика выполнения','usam'), 'key' => 'completed_affairs', 'view' => 'graph') ),
		);	
		return $reports;		
	}
	
	public function online_employee_report_box()
	{	
		return array( __('Сотрудник','usam'), __('Город','usam') );
	}	
}
?>