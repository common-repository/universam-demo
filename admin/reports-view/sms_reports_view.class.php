<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_sms_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = [
			[['title' => __('Отправленные СМС', 'usam'), 'key' => 'sent_sms', 'view' => 'graph']],	
		];	
		return $reports;
	}	
}
?>