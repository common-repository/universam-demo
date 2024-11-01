<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_reconciliation_acts extends USAM_Documents_Table
{	
	protected $document_type = ['reconciliation_act'];	
		
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',	
			'name'           => __('Название', 'usam'),		
			'company'        => __('Ваша фирма', 'usam'),				
			'counterparty'   => __('Контрагент', 'usam'),	
			'status'         => __('Статус', 'usam'),				
			'totalprice'     => __('Сумма', 'usam'),
			'date'           => __('Дата', 'usam'),				
        );		
        return $columns;
    }
}
?>