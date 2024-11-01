<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_payment_orders_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = [
			[['title' => __('Общий итог', 'usam'), 'key' => 'total_payment_orders', 'view' => 'transparent']],		
			[['title' => __('Поступления ', 'usam'), 'key' => 'payment_received', 'view' => 'graph'], ['title' => __('Оплаты ', 'usam'), 'key' => 'payment_order', 'view' => 'graph']],
			[['title' => __('Лучший платильщик', 'usam'), 'key' => 'best_payment_company', 'view' => 'loadable_table']],
		];	
		return $reports;
	}
	
	public function best_payment_company_report_box()
	{	
		return array( __('Компания','usam'), __('Оплачено','usam') );
	}	
}
?>