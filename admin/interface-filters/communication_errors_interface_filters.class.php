<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class communication_errors_Interface_Filters extends USAM_Interface_Filters
{	
	protected $filters_saved = false;
	protected function get_filters( ) 
	{				
		return ['reason' => ['title' => __('Причина', 'usam'), 'type' => 'checklists']];
	}	
}
?>