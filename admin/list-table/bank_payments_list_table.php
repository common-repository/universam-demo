<?php 
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_form_list_table.php' );
class USAM_List_Table_payment_orders extends USAM_Table_Documents_Form
{	
	protected $document_type = ['payment_order', 'payment_received'];

	function column_id( $item )
	{	
		$url = add_query_arg( array( 'id' => $item->id ), admin_url('admin.php?page=crm&tab=invoice&form=edit&form_name='.$item->type) );	
		echo "<a href='$url'>{$item->number}</a>";	
	}	
	
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',		
			'id'             => __('Номер', 'usam'),				
			'name'           => __('Название', 'usam'),		
			'status'         => __('Статус', 'usam'),				
			'totalprice'     => __('Сумма', 'usam'),		
			'date'           => __('Дата', 'usam'),				
        );		
        return $columns;
    }
}
?>