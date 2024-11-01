<?php 
require_once( USAM_FILE_PATH . '/admin/includes/list_table/properties_table.php' );
class USAM_List_Table_webform_properties extends USAM_Properties_Table
{
	protected $property_type = 'webform';
	protected $orderby = 'sort';
}
?>