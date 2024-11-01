<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/report_interface_filters.class.php' );
class groups_report_Interface_Filters extends Report_Interface_Filters
{	
	protected $search_box = true;
	protected function get_filters( ) 
	{				
		return [
			'weekday' => ['title' => __('Дни недели', 'usam'), 'type' => 'checklists'], 
			'taxonomy' => ['title' => __('Группы', 'usam'), 'type' => 'select'], 
			'code_price' => ['title' => __('Типы цен', 'usam'), 'type' => 'checklists'], 
			'storage' => ['title' => __('Склад списания', 'usam'), 'type' => 'checklists', 'query' => ['fields' => 'id=>title']],
		];
	}	
}
?>