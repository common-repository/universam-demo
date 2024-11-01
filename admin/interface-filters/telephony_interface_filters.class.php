<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class telephony_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return ['status' => ['title' => __('Статус', 'usam'), 'type' => 'checklists']];
	}
	
	protected function get_status_options() 
	{	
		$statuses = usam_get_statuses_telephony();
		foreach ( $statuses as $id => $name )
		{
			$results[] = ['id' => $id, 'name' => $name];
		}		
		return $results;
	}		
}
?>