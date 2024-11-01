<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_leads_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = [
			[['title' => __('Количество в работе','usam'), 'key' => 'leads_managers_in_work', 'view' => 'graph'],['title' => __('Количество доведенных до заказа','usam'), 'key' => 'leads_brought_to_order', 'view' => 'graph']],
			[['title' => __('Воронка','usam'), 'key' => 'leads_funnel', 'view' => 'graph'], ['title' => __('Скорость обработки лидов','usam'), 'key' => 'lead_status_processing_speed', 'view' => 'graph']],
			[['title' => __('Некачественные лиды', 'usam'), 'key' => 'leads_substandard', 'view' => 'graph'], ['title' => __('Новые лиды', 'usam'), 'key' => 'leads_new', 'view' => 'graph']],
		];	
		return $reports;
	}			
}
?>