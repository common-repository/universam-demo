<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_companies_Reports_View extends USAM_Reports_View
{		
	protected $period = 'last_365_day';	
	protected function get_report_widgets( ) 
	{				
		$reports = [
			[['title' => __('Общие показатели', 'usam'), 'key' => 'companies_total', 'view' => 'transparent']],		
			[['title' => __('Закрытые заказы', 'usam'), 'key' => 'companies_closed_orders', 'view' => 'graph'], ['title' => __('Оформлено заказов', 'usam'), 'key' => 'companies_received', 'view' => 'graph']],
			[['title' => __('Количество по менеджерам', 'usam'), 'key' => 'companies_load_managers', 'view' => 'graph'], ['title' => __('Продажи по менеджерам', 'usam'), 'key' => 'companies_sales_managers', 'view' => 'graph']],
			[['title' => __('Рейтинг компаний по количеству обращений', 'usam'), 'key' => 'rating_the_number_requests_companies', 'view' => 'loadable_table'], ['title' => __('Компании с низким уровнем удовлетворенности', 'usam'), 'key' => 'low_satisfaction_companies', 'view' => 'loadable_table']],	
			[['title' => __('Продажи по отраслям компаний', 'usam'), 'key' => 'sum_by_industry', 'view' => 'graph'], ['title' => __('Компании по отраслям', 'usam'), 'key' => 'companies_by_industry', 'view' => 'graph']],				
			[['title' => __('Продажи по группам компаний', 'usam'), 'key' => 'sum_by_category', 'view' => 'graph'], ['title' => __('Компании по группам', 'usam'), 'key' => 'companies_by_category', 'view' => 'graph']],										
			[['title' => __('Динамика роста базы компаний', 'usam'), 'key' => 'companies_base', 'view' => 'graph'], ['title' => __('Города компаний', 'usam'), 'key' => 'city_companies', 'view' => 'loadable_table']],					
		];	
		return $reports;		
	}		
			
	public function city_companies_report_box()
	{	
		return array( __('Город','usam'), __('Компаний','usam') );
	}		
		
	public function rating_the_number_requests_companies_report_box()
	{	
		return array( __('Компания','usam'), __('Обращений','usam') );
	}
	
	public function low_satisfaction_companies_report_box()
	{	
		return array( __('Компания','usam'), __('Статус','usam') );
	}	
}
?>