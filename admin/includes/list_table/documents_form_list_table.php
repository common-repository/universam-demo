<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );	
class USAM_Table_Documents_Form extends USAM_Documents_Table 
{	
	function get_bulk_actions() { }	
	
	public function get_views() { }	

	function prepare_items() 
	{			
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = [$columns, $hidden, $sortable];
				
		$this->get_query_vars();
		if ( $this->document_viewing_allowed() )
		{ 
			$this->query_vars['fields'] = 'all';
			if ( $this->document_type )
				$this->query_vars['type'] = $this->document_type;			
			if ( isset($_REQUEST['tab'] ) && $_REQUEST['tab'] == 'companies' )
				$this->query_vars['companies'] = $this->id;
			else
				$this->query_vars['contacts'] = $this->id;
					
			if ( empty($this->query_vars['include']) )
				$this->get_vars_query_filter();
			$document = new USAM_Documents_Query( $this->query_vars );		
			$this->items = $document->get_results();		
			$total_items = $document->get_total();
			$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
		}
	}	
}