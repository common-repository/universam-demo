<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_parser_Reports_View extends USAM_Reports_View
{		
	protected $period = 'last_365_day';	
	protected function get_report_widgets( ) 
	{				
		$reports = [		
			[['title' => __('Общее количество обработанных ссылок', 'usam'), 'key' => 'number_parser', 'view' => 'graph']],
			[['title' => __('Общее количество обновленных товаров', 'usam'), 'key' => 'product_number_parser', 'view' => 'graph']],			
		];	
		return $reports;		
	}
}
?>