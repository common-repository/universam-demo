<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_movements extends USAM_Documents_Table
{	
	protected $document_type = ['movement'];
	
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'    => __('Удалить', 'usam'),			
			'approved'  => __('Проведено', 'usam'),			
			'draft'     => __('Не проведено','usam'),			
		);
		return $actions;
	}	

	function column_from_storage( $item ) 
    {
		$storage_id = usam_get_document_metadata($item->id, 'from_storage');
		echo usam_get_store_field( $storage_id, 'title' );	
	}	
	
	function column_for_storage( $item ) 
    {
		$storage_id = usam_get_document_metadata($item->id, 'for_storage');
		echo usam_get_store_field( $storage_id, 'title' );	
	}
	
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',		
			'number'         => __('Номер', 'usam'),	
			'company'        => __('Ваша фирма', 'usam'),				
			'from_storage'   => __('Со склада', 'usam'),				
			'for_storage'    => __('На склад', 'usam'),			
			'status'         => __('Статус', 'usam'),				
			'totalprice'     => __('Сумма', 'usam'),				
			'date'           => __('Дата', 'usam'),				
        );		
        return $columns;
    }
}
?>