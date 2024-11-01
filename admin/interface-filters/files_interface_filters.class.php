<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class files_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{						
		$filters = [			
			'size' => ['title' => __('Размер', 'usam'), 'type' => 'numeric'],
		];
		return $filters;		
	}
	
	public function get_sort()
	{
		return ['date-desc' => __('По дате &#8595;', 'usam'), 'date-asc' => __('По дате &#8593;', 'usam'), 'modified-desc' => __('По дате изменения &#8595;', 'usam'), 'modified-asc' => __('По дате изменения &#8593;', 'usam'), 'id-desc' => __('Сначала новые', 'usam'), 'id-asc' => __('Сначала старые', 'usam'), 'title-asc' => __('По названию А-Я', 'usam'), 'title-desc' => __('По названию Я-А', 'usam'), 'size-desc' => __('По размеру &#8595;', 'usam'), 'size-asc' => __('По размеру &#8593;', 'usam'), 'uploaded-desc' => __('По скачиваниям &#8595;', 'usam'), 'uploaded-asc' => __('По скачиваниям &#8593;', 'usam')];	
	}	
}
?>