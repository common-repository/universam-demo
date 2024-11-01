<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/orders_interface_filters.class.php' );
class coupon_report_Interface_Filters extends Document_Orders_Interface_Filters
{	
	protected $search_box = false;	
	protected function get_filters( ) 
	{				
		return [
			'shipping' => ['title' => __('Способы доставки', 'usam'), 'type' => 'checklists'], 
			'storage' => ['title' => __('Склад выдачи', 'usam'), 'type' => 'checklists', 'query' => ['fields' => 'id=>title', 'issuing' => 1]],
		];	
	}		
}
?>