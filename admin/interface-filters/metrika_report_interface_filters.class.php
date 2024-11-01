<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/report_interface_filters.class.php' );
class metrika_report_Interface_Filters extends Report_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'weekday' =>  ['title' => __('Дни недели', 'usam'), 'type' => 'checklists'], 
			'code_price' =>  ['title' => __('Типы цен', 'usam'), 'type' => 'checklists'], 
			'status' => ['title' => __('Статусы заказов', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'order']]
		];
	}	
}
?>