<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class Contacting_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{		
		$filters = [
			'manager' => ['title' => __('Менеджер', 'usam'), 'type' => 'checklists', 'query' => ['source' => 'employee']], 
			'webform' => ['title' => __('Веб-формы', 'usam'), 'type' => 'checklists'],
			'group' => ['title' => __('Группа', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'contacting']],
		];
		return $filters;
	}		
}
?>