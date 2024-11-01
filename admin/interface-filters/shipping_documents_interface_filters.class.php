<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/shipped_interface_filters.class.php' );
class shipping_documents_Interface_Filters extends Shipped_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'shipping' => ['title' => __('Способы доставки', 'usam'), 'type' => 'checklists'], 
			'storage' => ['title' => __('Склад списания', 'usam'), 'type' => 'checklists', 'query' => ['fields' => 'id=>title']],
			'storage_pickup' => ['title' => __('Склад выдачи', 'usam'), 'type' => 'checklists', 'query' => ['fields' => 'id=>title', 'issuing' => 1]],
			'export' => ['title' => __('Статус выгрузки', 'usam'), 'type' => 'checklists'],
			'price' => ['title' => __('Стоимость доставки', 'usam'), 'type' => 'numeric'], 
		];
	}	
}
?>