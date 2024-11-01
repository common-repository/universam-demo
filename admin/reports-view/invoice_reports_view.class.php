<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_Invoice_Reports_View extends USAM_Reports_View
{				
	protected function get_report_widgets( ) 
	{				
		$reports = [			
			[['title' => __('В работе', 'usam'), 'key' => 'invoices_draft', 'view' => 'graph'], ['title' => __('Отправлено клиенту', 'usam'), 'key' => 'invoices_sent', 'view' => 'graph']],	
			[['title' => __('Оплачено', 'usam'), 'key' => 'invoices_paid', 'view' => 'graph'], ['title' => __('Не оплачено', 'usam'), 'key' => 'invoices_notpaid', 'view' => 'graph']],	
		];	
		return $reports;
	}
}
?>