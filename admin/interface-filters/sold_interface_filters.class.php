<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class sold_Interface_Filters extends USAM_Interface_Filters
{
	protected function get_filters( ) 
	{				
		return [
			'status' => ['title' => __('Статусы заказов', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'order']],
			'shipping' => ['title' => __('Способ доставки', 'usam'), 'type' => 'checklists'], 
			'storage' => ['title' => __('Склад списания', 'usam'), 'type' => 'checklists', 'query' => ['fields' => 'id=>title']],
			'product_price' => ['title' => __('Цена товаров', 'usam'), 'type' => 'numeric'], 
			'product_quantity' => ['title' => __('Количество товаров', 'usam'), 'type' => 'numeric'], 
			'order_price' => ['title' => __('Сумма заказа', 'usam'), 'type' => 'numeric'], 
			'order_quantity' => ['title' => __('Количество товаров в заказе', 'usam'), 'type' => 'numeric']
		];	
	}	
}
?>