<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_chat_Reports_View extends USAM_Reports_View
{		
	protected $period = 'last_365_day';	
	protected function get_report_widgets( ) 
	{				
		$reports = [		
			[['title' => __('Чаты по источникам', 'usam'), 'key' => 'chats_source', 'view' => 'graph']],
			[['title' => __('Количество сообщений', 'usam'), 'key' => 'number_chat', 'view' => 'graph']],
		];	
		return $reports;		
	}
}
?>