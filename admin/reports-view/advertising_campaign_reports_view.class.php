<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_advertising_campaign_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = array(
			[['title' => __('Статистика', 'usam'), 'key' => 'advertising_campaign_total', 'view' => 'transparent']],
			[['title' => __('Переходы', 'usam'), 'key' => 'campaign_transitions', 'view' => 'graph']],
		);	
		return $reports;		
	}	
}
?>