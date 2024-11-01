<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class departments_Interface_Filters extends USAM_Interface_Filters
{		
	protected function get_filters( ) 
	{				
		return [
			'company' => ['title' => __('Компания', 'usam'), 'type' => 'company'], 
		];		
	}	
}
?>