<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_advertising_campaigns_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = [
			[['title' => __('Переходы', 'usam'), 'key' => 'campaigns_transitions', 'view' => 'graph']],
		];	
		return $reports;		
	}	
}
?>