<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/export_list_table.php' );
class USAM_List_Table_pricelist extends USAM_Export_Table
{	
	protected $rule_type = 'pricelist';	
	
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',			
			'name'       => __('Название правила', 'usam'),
			'active'     => __('Активность', 'usam'),
			'file'       => __('Файл', 'usam'),
			
        );		
        return $columns;
    }
	
	function column_active( $item ) 
    {	
		$this->logical_column( $item->schedule );
	}		
}
?>