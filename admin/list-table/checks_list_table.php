<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_checks extends USAM_Documents_Table
{	
	protected $document_type = ['check'];	
	protected $manager_id = 'all';
	protected $status = 'all';
		
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',	
			'number'         => __('Номер', 'usam'),		
			'company'        => __('Фирма продавец', 'usam'),	
			'status'         => __('Статус', 'usam'),				
			'totalprice'     => __('Сумма', 'usam'),	
			'date'           => __('Дата', 'usam'),	
			'manager'        => __('Продавец', 'usam'),				
        );		
        return $columns;
    }
	
	function column_status( $item ) 
	{		
		usam_display_status( $item->status, $item->type );
	}
}
?>