<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class sellers_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		$filters = [			
		//	'group' => ['title' => __('Группа', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'company']],				
		//	'total_purchased' => ['title' => __('Всего куплено', 'usam'), 'type' => 'numeric'], 
			//'number_orders' => ['title' => __('Количество заказов', 'usam'), 'type' => 'numeric'],
			'number_products' => ['title' => __('Количество товаров', 'usam'), 'type' => 'numeric'],
		];	
		return $filters;
	}
}
?>