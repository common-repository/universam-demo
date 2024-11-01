<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_invoice_payment extends USAM_Documents_Table
{	
	protected $document_type = ['invoice_payment'];
			
	function get_columns()
	{		
        $columns = [
			'cb'             => '<input type="checkbox" />',		
			'name'           => __('За что платим', 'usam'),		
			'company'        => __('Ваша фирма', 'usam'),				
			'counterparty'   => __('Продавец', 'usam'),	
			'status'         => __('Статус', 'usam'),				
			'totalprice'     => __('Сумма', 'usam'),		
			'date'           => __('Дата', 'usam'),			
        ];		
        return $columns;
    }
}
?>