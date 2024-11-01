<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/orders_interface_filters.class.php' );
class leads_Interface_Filters extends Document_Orders_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'manager' => ['title' => __('Ответственный менеджер', 'usam'), 'type' => 'checklists'], 
			'weekday' => ['title' => __('Дни недели', 'usam'), 'type' => 'checklists'], 
			'type_price' => ['title' => __('Типы цен', 'usam'), 'type' => 'checklists'], 
			'payer' => ['title' => __('Тип плательщика', 'usam'), 'type' => 'checklists', 'query' => ['active' => 'all']],
			'sum' => ['title' => __('Сумма', 'usam'), 'type' => 'numeric'], 
			'prod' => ['title' => __('Количество товаров', 'usam'), 'type' => 'numeric']
		];	
	}	
}
?>