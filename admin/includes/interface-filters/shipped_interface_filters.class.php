<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class Shipped_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'method' => ['title' => __('Способы доставки', 'usam'), 'type' => 'checklists'], 
			'storage' => ['title' => __('Склад списания', 'usam'), 'type' => 'checklists', 'query' => ['fields' => 'id=>title']],
			'storage_pickup' => ['title' => __('Склад выдачи', 'usam'), 'type' => 'autocomplete', 'request' => 'storages',  'query' => ['fields' => 'id=>title', 'issuing' => 1]], 
			'export' => ['title' => __('Статус выгрузки', 'usam'), 'type' => 'checklists'],
			'price' => ['title' => __('Стоимость доставки', 'usam'), 'type' => 'numeric'], 
		];
	}
}
?>