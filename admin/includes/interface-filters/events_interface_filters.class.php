<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class Events_Interface_Filters extends USAM_Interface_Filters
{	
	public function get_calendar_options() 
	{		
		return usam_get_calendars();
	}
	
	public function get_sort( ) 
	{
		return ['start-desc' => __('По времени начала &#8595;', 'usam'), 'start-asc' => __('По времени начала &#8593;', 'usam'), 'id-desc' => __('Сначала новые', 'usam'), 'id-asc' => __('Сначала старые', 'usam'), 'title-asc' => __('По названию А-Я', 'usam'), 'title-desc' => __('По названию Я-А', 'usam')];	
	}	
	
	public function get_role_options() 
	{	
		return [['id' => 'my', 'name' => __('Выполняю', 'usam')], ['id' => 'commission', 'name' => __('Поручили мне', 'usam')], ['id' => 'assignments', 'name' => __('Поручил', 'usam')]];
	}
}
?>