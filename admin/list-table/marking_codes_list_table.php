<?php
require_once(USAM_FILE_PATH.'/includes/product/marking_codes_query.class.php');
require_once( USAM_FILE_PATH . '/admin/includes/list_table/marking_codes_list_table.php' );
class USAM_List_Table_marking_codes extends USAM_Table_marking_codes 
{		
	function get_columns()
	{
        $columns = array(           
			'cb'            => '<input type="checkbox" />',	
			'code'          => __('Код', 'usam'),			
			'store'         => __('Склад хранения', 'usam'),	
			'status'        => __('Статус', 'usam'),	
			'product_title'  => __('Товар', 'usam'),	
        );	
        return $columns;
    }
	
	public function prepare_items( )
    {
		$this->get_query_vars();		
		if ( empty($this->query_vars['include']) )
		{				
			
		} 		
		$this->query_vars['document_id'] = 0;
		$codes = new USAM_Marking_Codes_Query( $this->query_vars );	
		$this->items = $codes->get_results();		
			
		$total_items = $codes->get_total();
		$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page ) );
	}
}