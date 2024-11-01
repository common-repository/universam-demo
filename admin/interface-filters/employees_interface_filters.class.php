<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/people_interface_filters.class.php' );
class employees_Interface_Filters extends People_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'status' => ['title' => __('Статус', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'employee']], 
			'company' => ['title' => __('Компания', 'usam'), 'type' => 'autocomplete', 'request' => 'companies'], 
			'department' => ['title' =>__('Отдел', 'usam'), 'type' => 'checklists'], 
			'gender' => ['title' =>__('Пол', 'usam'), 'type' => 'checklists'], 
			'age' => ['title' =>__('Возраст', 'usam'), 'type' => 'numeric'],					
			'chat' => ['title' => __('Консультируют клиентов', 'usam'), 'type' => 'checkbox'],
			'location' => ['title' => __('Местоположение', 'usam'), 'type' => 'autocomplete', 'request' => 'locations'], 
		];
	}
}
?>