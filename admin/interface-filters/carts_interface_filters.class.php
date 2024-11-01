<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class Carts_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'recalculation_date' => ['title' => __('Дата изменения', 'usam'), 'type' => 'date'],
			'payment' => ['title' => __('Способы оплаты', 'usam'), 'type' => 'checklists'],
			'shipping' => ['title' => __('Способы доставки', 'usam'), 'type' => 'checklists'],
			'storage' => ['title' => __('Склад выдачи', 'usam'), 'type' => 'checklists', 'query' => ['fields' => 'id=>title', 'issuing' => 1]],
			'sum' => ['title' => __('Сумма', 'usam'), 'type' => 'numeric'],
			'quantity' => ['title' => __('Количество товаров', 'usam'), 'type' => 'numeric'],
			'coupon' => ['title' => __('Код купона', 'usam'), 'type' => 'string'],
			'bonuses' => ['title' => __('Количество бонусов', 'usam'), 'type' => 'numeric']
		];
	}
}
?>