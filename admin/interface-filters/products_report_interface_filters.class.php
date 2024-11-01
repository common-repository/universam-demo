<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class products_report_Interface_Filters extends USAM_Interface_Filters
{			
	protected function get_placeholder_filter_save( ) 
	{	
		return __('Название отчета', 'usam');
	}
	
	protected function get_filters( ) 
	{				
		return [
			'status' => ['title' => __('Статусы заказов', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'order']],
			'discount' => ['title' => __('Установленные скидки', 'usam'), 'type' => 'checklists', 'query' => ['discount_set' => 1, 'type_rule' => ['product','fix_price']]],
			'manager' => ['title' =>__('Менеджер', 'usam'), 'type' => 'select'], 
			'weekday' => ['title' => __('Дни недели', 'usam'), 'type' => 'checklists'], 			
		];
	}	
}
?>