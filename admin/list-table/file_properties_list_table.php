<?php 
require_once( USAM_FILE_PATH . '/admin/includes/list_table/properties_table.php' );
class USAM_List_Table_file_properties extends USAM_Properties_Table
{
	protected $property_type = 'file';
	protected $orderby = 'sort';
}
?>