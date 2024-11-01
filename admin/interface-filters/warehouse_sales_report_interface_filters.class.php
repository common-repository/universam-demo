<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/orders_interface_filters.class.php' );
class warehouse_sales_report_Interface_Filters extends Document_Orders_Interface_Filters
{	
	protected $search_box = false;
	protected function get_filters( ) 
	{				
		return [
			'weekday' => ['title' => __('Дни недели', 'usam'), 'type' => 'checklists'], 
			'shipping' => ['title' => __('Способы доставки', 'usam'), 'type' => 'checklists'], 
			'status' => ['title' => __('Статусы доставки', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'shipped']], 
		];
	}
}
?>