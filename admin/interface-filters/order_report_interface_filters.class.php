<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/orders_interface_filters.class.php' );
class order_report_Interface_Filters extends Document_Orders_Interface_Filters
{	
	protected $search_box = false;		
	protected function get_filters( )
	{				
		$filters = parent::get_filters();
		$filters['status'] = ['title' => __('Статусы заказов', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'order']];
		return $filters;
	}
}
?>