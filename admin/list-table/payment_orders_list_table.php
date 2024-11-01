<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_payment_orders extends USAM_Documents_Table
{	
	protected $document_type = ['payment_order'];	
	protected $manager_id = 'all';
	
	protected function get_row_actions( $item ) 
    { 		
		$actions = $this->standart_row_actions( $item->id, $item->type, ['copy' => __('Копировать', 'usam')] );		
		if ( $item->status == 'paid' || !current_user_can('delete_'.$item->type))
			unset($actions['delete']);		
		if ( !current_user_can('add_'.$item->type) )
			unset($actions['copy']);
		if ( !current_user_can('edit_'.$item->type) )
			unset($actions['edit']);
		return $actions;
	}	
		
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',	
			'name'           => __('Назначение платежа', 'usam'),		
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