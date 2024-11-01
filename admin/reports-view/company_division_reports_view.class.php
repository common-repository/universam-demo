<?php 
require_once( USAM_FILE_PATH . '/admin/reports-view/company_reports_view.class.php' );
class USAM_company_division_Reports_View extends USAM_company_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = array(
			array( array( 'title' => __('Общие показатели', 'usam'), 'key' => 'company_total', 'view' => 'transparent' ) ),		
			array( array( 'title' => __('Купленные товары', 'usam'), 'key' => 'purchased_products_company', 'view' => 'loadable_table' ), array( 'title' => __('Записанные звонки', 'usam'), 'key' => 'recorded_calls_company', 'view' => 'loadable_table' ) ),
			[[ 'title' => __('Последний заказ', 'usam'), 'key' => 'company_last_order', 'view' => 'transparent']],
			array( array( 'title' => __('Общая статистика по рассылке', 'usam'), 'key' => 'company_results_newsletter', 'view' => 'transparent' ) ),				
			array( array( 'title' => __('Списки рассылок', 'usam'), 'key' => 'mailing_lists', 'view' => 'transparent' ) ),	
			array( array( 'title' => __('Отправленные рассылки', 'usam'), 'key' => 'send_newsletters_company', 'view' => 'loadable_table' ), array( 'title' => __('Открытые рассылки', 'usam'), 'key' => 'open_newsletters_company', 'view' => 'loadable_table' ) ),
		);	
		return $reports;		
	}
}
?>