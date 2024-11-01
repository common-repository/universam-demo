<?php 
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class Report_Interface_Filters extends USAM_Interface_Filters
{		
	protected $search_box = false;
	
	protected function get_placeholder_filter_save( ) 
	{	
		return __('Название отчета', 'usam');
	}
}
?>