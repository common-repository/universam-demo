<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class time_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{		
		return ['user_work' => ['title' => __('Исполнитель', 'usam'), 'type' => 'checklists']];
	}	
}
?>