<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_proxy extends USAM_Documents_Table
{	
	protected $document_type = ['proxy'];	
	
	function column_status( $item ) 
	{		
		usam_display_status( $item->status, $item->type );
	}
	
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',		
			'name'           => __('Название', 'usam'),		
			'company'        => __('Ваша фирма', 'usam'),				
			'counterparty'   => __('Контрагент', 'usam'),		
			'totalprice'     => __('Сумма', 'usam'),	
			'date'           => __('Дата', 'usam'),	
			'manager'        => __('Получатель', 'usam'),				
        );		
        return $columns;
    }
}
?>