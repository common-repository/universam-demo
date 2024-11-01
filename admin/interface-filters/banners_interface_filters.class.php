<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class banners_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'banner_location' => ['title' => __('Расположение в шаблоне', 'usam'), 'type' => 'checklists'], 
		];	
	}	
}
?>