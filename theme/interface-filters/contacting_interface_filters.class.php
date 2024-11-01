<?php
require_once( USAM_FILE_PATH . '/includes/theme/theme_interface_filters.class.php' );
class Contacting_Interface_Filters extends USAM_Theme_Interface_Filters
{		
	protected function get_filters( ) 
	{			
		return [
			'date' => ['title' => __('Интервал', 'usam'), 'type' => 'date']
		];
	}	
}
?>