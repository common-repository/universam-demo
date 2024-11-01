<?php
require_once(USAM_FILE_PATH.'/includes/product/marking_codes_query.class.php');
require_once( USAM_FILE_PATH . '/admin/includes/list_table/marking_codes_list_table.php' );
class USAM_List_Table_marking_codes_form extends USAM_Table_marking_codes 
{	
	function get_columns()
	{
        $columns = array(           
			'code'          => __('Код', 'usam'),			
			'product_title'  => __('Товар', 'usam'),	
        );	
        return $columns;
    }
	
	public function prepare_items( )
    {
		$this->get_query_vars();		
		$this->query_vars['document_id'] = $this->id;
		if ( empty($this->query_vars['include']) )
		{				
			
		} 
		$codes = new USAM_Marking_Codes_Query( $this->query_vars );	
		$this->items = $codes->get_results();		
			
		$total_items = $codes->get_total();
		$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page ) );
	}
}