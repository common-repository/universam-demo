<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_payments_received extends USAM_Documents_Table
{	
	protected $document_type = ['payment_received'];	
	protected $manager_id = 'all';
	
	protected function get_row_actions( $item ) 
    { 		
		if ( $item->status != 'paid' )
			$actions = $this->standart_row_actions( $item->id, $item->type );	
		else 
			$actions = array();
		return $actions;
	}	
	
	function column_status( $item ) 
	{		
		usam_display_status( $item->status, $item->type );
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