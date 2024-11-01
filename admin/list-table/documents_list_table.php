<?php 
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_documents extends USAM_Documents_Table
{	
	protected $manager_id = 0;			
	function get_bulk_actions_display() 
	{	
		$actions = [
			'delete'    => __('Удалить', 'usam'),			
		];		
		return $actions;
	}	
	
	public function get_views() { }
		
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',		
			'name'           => __('Название', 'usam'),		
			'type'           => __('Тип документа', 'usam'),		
			'company'        => __('Ваша фирма', 'usam'),				
			'counterparty'   => __('Контрагент', 'usam'),	
			'status'         => __('Статус', 'usam'),				
			'totalprice'     => __('Сумма', 'usam'),	
			'date'           => __('Дата', 'usam'),		
        );		
        return $columns;
    }
	
	function prepare_items() 
	{			
		$this->get_query_vars();	
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
		$this->query_vars['type'] = [];
		$details_documents = usam_get_details_documents();	
		foreach ( $details_documents as $type => $document )
		{
			if ( !current_user_can('view_'.$type) )
				$this->query_vars['type__not_in'][] = $type;
		}			
		$this->query_vars['status__not_in'] = ['declained', 'approved', 'subscribe', 'notsigned', 'signed'];
		if ( empty($this->query_vars['include']) )
		{					
			$this->get_vars_query_filter();
		}	
		$this->query_vars['cache_bank_accounts'] = true;
		$this->query_vars['cache_meta'] = true;
	
		$documents = new USAM_Documents_Query( $this->query_vars );		
		$this->items = $documents->get_results();		
		$total_items = $documents->get_total();
		$this->total_amount = $documents->get_total_amount();		
		$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page ) );
	}
}
?>