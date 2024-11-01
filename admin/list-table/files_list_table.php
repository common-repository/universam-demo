<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/files_list_table.php' );
class USAM_List_Table_files extends USAM_Table_files
{		
	function prepare_items() 
	{			
		$this->get_query_vars();		
		$this->query_vars['status'] = $this->status;	
		$this->query_vars['type__not_in'] = 'temporary';
		if ( empty($this->query_vars['include']) )
		{							
			if ( $this->status == 'all' )
				$this->query_vars['status__not_in'] = 'delete';			
//1000			
			$this->get_digital_interval_for_query(['size']);			
		} 
		$query = new USAM_Files_Query( $this->query_vars );
		$this->items = $query->get_results();		
		$this->total_items = $query->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}