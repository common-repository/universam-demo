<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
require_once( USAM_FILE_PATH . '/includes/customer/customer_account.class.php' );
class USAM_customer_account_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = array(
			array( array( 'title' => __('Статистика по использованию', 'usam'), 'key' => 'customer_account_total', 'view' => 'transparent' ) ),
		);	
		return $reports;		
	}
}
?>