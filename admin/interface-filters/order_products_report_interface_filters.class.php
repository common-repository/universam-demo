<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/orders_interface_filters.class.php' );
class order_products_report_Interface_Filters extends Document_Orders_Interface_Filters
{	
	protected $search_box = false;	
	protected function get_filters( ) 
	{				
		return [
			'status' => ['title' => __('Статусы заказов', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'order']],
			'weekday' => ['title' => __('Дни недели', 'usam'), 'type' => 'checklists'], 
			'seller' => ['title' => __('Продавец', 'usam'), 'type' => 'checklists'], 
			'manager' => ['title' => __('Ответственный менеджер', 'usam'), 'type' => 'checklists'], 
			'paid' => ['title' => __('Оплата', 'usam'), 'type' => 'checklists'], 
			'code_price' => ['title' => __('Типы цен', 'usam'), 'type' => 'checklists'], 
			'payer' => ['title' => __('Тип плательщика', 'usam'), 'type' => 'checklists', 'query' => ['active' => 'all']]
		];
	}
}
?>