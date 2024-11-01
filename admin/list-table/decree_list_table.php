<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_decree extends USAM_Documents_Table
{	
	protected $document_type = ['decree'];
			
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox">',		
			'name'           => __('Название', 'usam'),		
			'company'        => __('По фирме', 'usam'),	
			'status'         => __('Статус', 'usam'),		
			'date'           => __('Дата', 'usam'),				
        );		
        return $columns;
    }
}
?>