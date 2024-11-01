<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/events_interface_filters.class.php' );
class Affairs_Interface_Filters extends Events_Interface_Filters
{	
	protected function get_filters( ) 
	{		
		$filters = [
			'author' => ['title' => __('Автор', 'usam'), 'type' => 'checklists'],
			'types_event' => ['title' => __('Тип события', 'usam'), 'type' => 'checklists'], 
			'objects' => ['title' => __('Объект', 'usam'), 'type' => 'objects'],  
			'webform' => ['title' => __('Веб-формы', 'usam'), 'type' => 'checklists'],  
			'calendar' => ['title' => __('Календарь', 'usam'), 'type' => 'checklists'], 
			'group' => ['title' => __('Группа', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'event']],
		];	
		return $filters;
	}	
}
?>