<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_email_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = [	
			[['title' => __('Полученные письма', 'usam'), 'key' => 'inbox_letters', 'view' => 'graph'], ['title' => __('Отправленные письма', 'usam'), 'key' => 'sent_letters', 'view' => 'graph']],	
		];	
		return $reports;
	}		
}
?>