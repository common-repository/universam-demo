<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_partner_orders extends USAM_Documents_Table
{	
	protected $document_type = ['partner_order'];
	
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'    => __('Удалить', 'usam'),			
			'approved'  => __('Проведено', 'usam'),			
			'draft'     => __('Не проведено','usam'),			
		);
		return $actions;
	}
	
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',		
			'number'         => __('Номер', 'usam'),		
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