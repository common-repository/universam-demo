<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/events_interface_filters.class.php' );
class Tasks_Interface_Filters extends Events_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'role' => ['title' => __('Роль', 'usam'), 'type' => 'checklists'], 		
			'author' => ['title' => __('Автор', 'usam'), 'type' => 'checklists'],			
			'user_work' => ['title' => __('Исполнитель', 'usam'), 'type' => 'checklists'], 
			'objects' => ['title' => __('Объект', 'usam'), 'type' => 'objects'],    
			'group' => ['title' => __('Группа', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'task']],
			'calendar' => ['title' => __('Календарь', 'usam'), 'type' => 'checklists'], 			
		];
	}		
}
?>