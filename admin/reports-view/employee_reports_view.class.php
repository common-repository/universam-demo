<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_employee_Reports_View extends USAM_Reports_View
{			
	protected function get_report_widgets( ) 
	{					
		$reports = array(
			array( array( 'title' => __('Статистика по продажам', 'usam'), 'key' => 'employee_total', 'view' => 'transparent' ) ),		
			array( array( 'title' => __('Записанные звонки', 'usam'), 'key' => 'recorded_calls_contact', 'view' => 'loadable_table' ), ),
		);	
		return $reports;		
	}	

	public function recorded_calls_contact_report_box()
	{	
		return array( __('Дата','usam'), __('Время','usam') );
	}
}
?>