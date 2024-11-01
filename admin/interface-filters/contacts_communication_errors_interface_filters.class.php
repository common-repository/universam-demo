<?php
require_once( USAM_FILE_PATH . '/admin/interface-filters/communication_errors_interface_filters.class.php' );
class contacts_communication_errors_Interface_Filters extends communication_errors_Interface_Filters
{	
	protected function get_filters( ) 
	{		
		return ['reason' => ['title' => __('Причина', 'usam'), 'type' => 'checklists']];
	}			
}
?>