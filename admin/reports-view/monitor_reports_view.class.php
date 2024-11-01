<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_monitor_Reports_View extends USAM_Reports_View
{			
	protected $period = 'last_7_day';	
	protected function get_report_widgets( ) 
	{				
		$reports = array(
			[['title' => __('Общие показатели', 'usam'), 'key' => 'monitor_total', 'view' => 'transparent']],
			[['title' => __('Источник','usam'), 'key' => 'contacts_source_online', 'view' => 'graph']],
			[['title' => __('Устройства', 'usam'), 'key' => 'device_online', 'view' => 'graph'],['title' => __('Распределение по полу', 'usam'), 'key' => 'sex_online', 'view' => 'graph']],	
			[['title' => __('Города клиентов', 'usam'), 'key' => 'city_contacts_online', 'view' => 'loadable_table']],
			[['title' => __('Динамика роста базы контактов', 'usam'), 'key' => 'contact_base', 'view' => 'graph'],['title' => __('Источники', 'usam'), 'key' => 'sources_visit', 'view' => 'graph']],	
		);	
		return $reports;		
	}
		
	public function city_contacts_online_report_box()
	{	
		return array( __('Города','usam'), __('Клиентов','usam') );
	}	
}
?>