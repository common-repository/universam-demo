<?php 
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class Document_Orders_Interface_Filters extends USAM_Interface_Filters
{			
	protected function get_filters( )
	{				
		$filters = [
			'date_group' => ['title' => __('Использовать дату', 'usam'), 'type' => 'select'], 
			'weekday' => ['title' => __('Дни недели', 'usam'), 'type' => 'checklists', 'show' => false], 
			'seller' => ['title' => __('Продавец', 'usam'), 'type' => 'checklists', 'show' => false], 			
			'manager' => ['title' => __('Ответственный менеджер', 'usam'), 'type' => 'checklists'], 
			'group' => ['title' => __('Группы', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'order'], 'show' => true],
			'paid' => ['title' => __('Оплата заказа', 'usam'), 'type' => 'checklists', 'show' => false], 
			'code_price' => ['title' => __('Типы цен', 'usam'), 'type' => 'checklists'], 
			'payer' => ['title' => __('Тип плательщика', 'usam'), 'type' => 'checklists', 'query' => ['active' => 'all'], 'show' => false],
			'payment' => ['title' => __('Способы оплаты', 'usam'), 'type' => 'checklists'], 
			'shipping' => ['title' => __('Способы доставки', 'usam'), 'type' => 'checklists'], 
			'storage_pickup' => ['title' => __('Склад выдачи', 'usam'), 'type' => 'autocomplete', 'request' => 'storages',  'query' => ['fields' => 'id=>title', 'issuing' => 1]], 
			'sum' => ['title' => __('Сумма', 'usam'), 'type' => 'numeric'], 
			'prod' => ['title' => __('Количество товаров', 'usam'), 'type' => 'numeric', 'show' => false], 
			'bonus' => ['title' => __('Количество бонусов', 'usam'), 'type' => 'numeric', 'show' => false], 
			'coupon_name' => ['title' => __('Код купона', 'usam'), 'type' => 'string', 'show' => false], 
			//'tax' => ['title' => __('Налог', 'usam'), 'type' => 'checklists'], 
			'shipping_sum' => ['title' => __('Стоимость доставки', 'usam'), 'type' => 'numeric', 'show' => false], 
			'document_discount' => ['title' => __('Скидки и акции','usam'), 'type' => 'checklists', 'query' => ['document_type' => 'order', 'orderby' => 'name', 'order' => 'ASC', 'groupby' => 'name']], 
			'campaign' => ['title' => __('Рекламные компании','usam'), 'type' => 'checklists', 'query' => ['fields' => 'id=>title']],
			'newsletter' => ['title' => __('Рассылки','usam'), 'type' => 'checklists', 'query' => ['fields' => 'id=>subject', 'status' => [5,6]], 'show' => false], 
			'brands' => ['title' => __('Бренды', 'usam'), 'type' => 'checklists'], 
			'category' => ['title' => __('Категории', 'usam'), 'type' => 'checklists'], 
			'counterparty' => ['title' => __('Контрагент', 'usam'), 'type' => 'counterparty'],
			'source' => ['title' => __('Источник', 'usam'), 'type' => 'checklists'],			
		];
		$filters += $this->get_properties( 'order' );
		return $filters;
	}
	
	//Дата группировки
	public function get_date_group_options() 
	{	
		return [['id' => 'insert',  'name' => __('Создание заказа', 'usam')], ['id' => 'paid',  'name' => __('Дата оплаты', 'usam')]];	
	}
	
	public function get_paid_options() 
	{	
		return [['id' => 2,  'name' => __('Оплаченные', 'usam')], ['id' => 1,  'name' => __('Частично оплачен', 'usam')], ['id' => 0,  'name' => __('Не оплаченные', 'usam')]];	
	}
	
	public function get_source_options() 
	{	
		$results = [];
		foreach ( usam_get_order_source() as $code => $title ) 
			$results[] = ['id' => $code, 'name' => $title];
		return $results;	
	}	
}
?>