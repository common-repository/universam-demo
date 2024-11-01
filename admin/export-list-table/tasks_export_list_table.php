<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_export_event_list_table.class.php' );	
class USAM_Export_List_Table_tasks extends USAM_Export_Event_List_Table
{		
	function get_print_page( )
	{
		echo "@page {size:landscape};";
	} 	
}