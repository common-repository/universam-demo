<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class People_Interface_Filters extends USAM_Interface_Filters
{		
	protected function get_filters( ) 
	{				
		$filters = [
			'status' => ['title' => __('Статус', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'contact']], 
			'group' => ['title' => __('Группа', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'contact']], 
			'contacts_source' => ['title' => __('Источник', 'usam'), 'type' => 'checklists'],	
			'gender' => ['title' => __('Пол', 'usam'), 'type' => 'checklists'], 
			'age' => ['title' => __('Возраст', 'usam'), 'type' => 'numeric'], 			
			'manager' => ['title' => __('Менеджеры', 'usam'), 'type' => 'checklists'], 
			'mailing_lists' => ['title' => __('Список рассылки', 'usam'), 'type' => 'checklists', 'query' => ['not_added' => 1]], 
			'status_subscriber' => ['title' => __('Статус подписки', 'usam'), 'type' => 'checklists'], 			
			'company' => ['title' => __('Компания', 'usam'), 'type' => 'autocomplete', 'request' => 'companies'],
			'campaign' => ['title' => __('Рекламные компании','usam'), 'type' => 'checklists', 'query' => ['fields' => 'id=>title']],
			'storage_pickup' => ['title' => __('Склад выдачи', 'usam'), 'type' => 'autocomplete', 'request' => 'storages',  'query' => ['fields' => 'id=>title', 'issuing' => 1]], 
			'abandoned_baskets' => ['title' => __('Брошенные корзины', 'usam'), 'type' => 'checklists'],
			'online' => ['title' => __('Последний визит', 'usam'), 'type' => 'date'], 
			'total_purchased' => ['title' => __('Всего куплено', 'usam'), 'type' => 'numeric'], 
			'last_order_date' => ['title' => __('Последний заказ', 'usam'), 'type' => 'date'], 
			'number_orders' => ['title' => __('Количество заказов', 'usam'), 'type' => 'numeric'], 
			'bonus' => ['title' => __('Бонусы на карте', 'usam'), 'type' => 'numeric'], 
			'accounts' => ['title' => __('Личные кабинеты', 'usam'), 'type' => 'checkbox'], 
		];
		$filters += $this->get_properties( 'contact' );
		return $filters;
	}
	
	protected function get_placeholder_filter_save( ) 
	{	
		return __('Название сегмента', 'usam');
	}	
	
	protected function get_filter_save_title( ) 
	{	
		return __('Мои сегменты', 'usam');
	}		
	
	public function get_abandoned_baskets_options() 
	{	
		return [['id' => '3day', 'name' => __('более 3 дней', 'usam')], ['id' => '7day', 'name' => __('более 7 дней', 'usam')], ['id' => '14day', 'name' => __('более 14 дней', 'usam')], ['id' => '30day', 'name' => __('более 30 дней', 'usam')], ['id' => '90day', 'name' => __('более 90 дней', 'usam')]];
	}
	
	public function get_gender_options() 
	{	
		return [['id' => 'm', 'name' => __('Мужчины', 'usam')], ['id' => 'f', 'name' => __('Женщины', 'usam')]];
	}
	
	public function get_newsletter_options() 
	{	
		$statuses = usam_get_customer_newsletter_statuses();		
		$results = array();
		foreach( $statuses as $type => $name )
			$results[] = ['id' => $type, 'name' => $name];
		return $results;
	}	
}
?>