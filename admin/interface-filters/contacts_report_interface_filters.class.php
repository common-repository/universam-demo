<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/people_interface_filters.class.php' );
class Contacts_report_Interface_Filters extends People_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'manager' => ['title' => __('Менеджеры', 'usam'), 'type' => 'checklists'], 
			'status' => ['title' => __('Статус', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'contact']], 
			'groups' => ['title' => __('Группы', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'contact']],
			'contacts_source' => ['title' => __('Источник', 'usam'), 'type' => 'checklists'],	
			'age' => ['title' => __('Возраст', 'usam'), 'type' => 'numeric'], 			
			'gender' => ['title' => __('Пол', 'usam'), 'type' => 'checklists'], 				
			'company' => ['title' => __('Компания', 'usam'), 'type' => 'autocomplete', 'request' => 'companies'],
		];
	}	
}
?>