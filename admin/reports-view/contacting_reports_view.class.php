<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_contacting_Reports_View extends USAM_Reports_View
{		
	protected $period = 'last_365_day';	
	protected function get_report_widgets( ) 
	{				
		$reports = [
		//	[['title' => __('Общие показатели', 'usam'), 'key' => 'companies_total', 'view' => 'transparent']],		
		//	[['title' => __('Закрытые заказы', 'usam'), 'key' => 'companies_closed_orders', 'view' => 'graph'], ['title' => __('Оформлено заказов', 'usam'), 'key' => 'companies_received', 'view' => 'graph']],
		//	[['title' => __('Количество по менеджерам', 'usam'), 'key' => 'companies_load_managers', 'view' => 'graph'], ['title' => __('Продажи по менеджерам', 'usam'), 'key' => 'companies_sales_managers', 'view' => 'graph']],			
			[['title' => __('Обращения по группам', 'usam'), 'key' => 'contacting_by_groups', 'view' => 'graph']],
			[['title' => __('Количество обращений', 'usam'), 'key' => 'number_contacting', 'view' => 'graph']],
		];	
		return $reports;		
	}
}
?>