<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/properties_table.php' );
class USAM_List_Table_company_properties extends USAM_Properties_Table
{
	protected $property_type = 'company';
	
	function prepare_items() 
	{			
		$this->get_query_vars();			
		$this->query_vars['type'] = $this->property_type;
		$this->query_vars['active'] = 'all';
		if ( empty($this->query_vars['include']) )
		{							
			$selected = $this->get_filter_value( 'property_groups' );
			if ( $selected )
				$this->query_vars['group'] = array_map('sanitize_title', (array)$selected);
			
			$selected = $this->get_filter_value( 'registration' );
			if ( $selected )
				$this->query_vars['meta_query'][] = ['key' => 'registration', 'value' => (int)$selected, 'compare' => 'IN'];
		}	
		$query = new USAM_Properties_Query( $this->query_vars );		
		$this->items = $query->get_results();		
		$total_items = $query->get_total();
		$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
	}
}
?>