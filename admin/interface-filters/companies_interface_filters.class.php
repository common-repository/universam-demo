<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class companies_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		$filters = [
			'status' => ['title' => __('Статус', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'company']], 
			'group' => ['title' => __('Группа', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'company']],
			'companies_types' => ['title' => __('Тип компании', 'usam'), 'type' => 'checklists'], 
			'industry' => ['title' => __('Сфера деятельности', 'usam'), 'type' => 'checklists'], 
			'mailing_lists' => ['title' => __('Список рассылки', 'usam'), 'type' => 'checklists', 'query' => ['not_added' => 1]], 
			'status_subscriber' => ['title' => __('Статус подписки', 'usam'), 'type' => 'checklists'], 
			'accounts' => ['title' => __('Личные кабинеты', 'usam'), 'type' => 'checkbox'], 			
			'total_purchased' => ['title' => __('Всего куплено', 'usam'), 'type' => 'numeric'], 
			'number_orders' => ['title' => __('Количество заказов', 'usam'), 'type' => 'numeric'], 				
			'manager' => ['title' => __('Менеджеры', 'usam'), 'type' => 'checklists', 'query' => ['source' => 'employee']], 
			'code_price' => ['title' => __('Типы цен','usam'), 'type' => 'checklists']
		];	
		$filters += $this->get_properties( 'company' );
		return $filters;
	}
}
?>