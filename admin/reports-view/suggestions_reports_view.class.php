<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_Suggestions_Reports_View extends USAM_Reports_View
{				
	protected function get_report_widgets( ) 
	{				
		$reports = [			
			[['title' => __('В работе', 'usam'), 'key' => 'suggestions_draft', 'view' => 'graph'], ['title' => __('Отправлено клиенту', 'usam'), 'key' => 'suggestions_sent', 'view' => 'graph']],	
			[['title' => __('Утвержденные', 'usam'), 'key' => 'suggestions_approved', 'view' => 'graph'], ['title' => __('Отклоненные', 'usam'), 'key' => 'suggestions_declained', 'view' => 'graph']],	
		];	
		return $reports;
	}
}
?>