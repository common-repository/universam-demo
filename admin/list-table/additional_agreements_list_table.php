<?php 
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_additional_agreements extends USAM_Documents_Table
{	
	protected $document_type = ['additional_agreement'];	
	public function get_views() {}
	
	function get_columns()
	{		
        $columns = [
			'cb'             => '<input type="checkbox" />',				
			'name'           => __('Название', 'usam'),	
			'status'         => __('Статус', 'usam'),				
			'totalprice'     => __('Сумма', 'usam'),	
			'date'           => __('Дата', 'usam'),				
        ];		
        return $columns;
    }
	
	function prepare_items() 
	{			
		$this->get_query_vars();			
		if ( $this->document_viewing_allowed() )
		{			
			if ( $this->status == 'all_in_work' )
			{
				$this->query_vars['status'] = [];				
				foreach ( $this->statuses as $key => $status )	
				{
					if ( !$status->close )
						$this->query_vars['status'][] = $key;
				}
			}
			elseif ( $this->status != 'all' )
				$this->query_vars['status'] = $this->status;
			if ( empty($this->query_vars['include']) )
				$this->get_vars_query_filter();
			$this->query_vars['meta_key'] = 'contract';
			$this->query_vars['meta_value'] = $this->id;		
			$this->query_vars['cache_bank_accounts'] = true;
			$this->query_vars['cache_meta'] = true;
		
			$documents = new USAM_Documents_Query( $this->query_vars );		
			$this->items = $documents->get_results();		
			$this->total_items = $documents->get_total();
			
			$this->total_amount = $documents->get_total_amount();	
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
		}		
	}
}
?>