<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_subscription_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = array(
			[['title' => __('Общая статистика', 'usam'), 'key' => 'subscription_total', 'view' => 'transparent']],			
			[['title' => __('Основание','usam'), 'key' => 'documents_paid_subscription', 'view' => 'graph']],
		);
		return $reports;
	}	
}
?>