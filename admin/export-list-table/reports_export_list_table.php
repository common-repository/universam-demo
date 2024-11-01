<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_export_list_table.class.php' );	
class USAM_Export_List_Table_reports extends USAM_Export_List_Table
{	
	public function column_date( $item ) 
	{	
		echo date_i18n('d.m.y', $item['date'] );	
	}
}