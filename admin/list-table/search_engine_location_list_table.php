<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_search_engine_location extends USAM_List_Table
{
	protected $orderby = 'search_engine';
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
	function column_search_engine( $item )
	{		
		$this->row_actions_table( $item['search_engine'], $this->standart_row_actions( $item['id'], 'search_engine_location' ) );
	}
	
	function column_location( $item )
	{		
		echo usam_get_full_locations_name( $item['location'] );		
	}
				   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'          => array('name', false),			
			'search_engine' => array('search_engine', false),			
			); 
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'               => '<input type="checkbox" />',	
			'search_engine'    => __('Поисковая система', 'usam'),				
			'location'         => __('Местоположение', 'usam'),								
        );				
        return $columns;
    }	

	function prepare_items() 
	{	
		$option = get_site_option('usam_search_engine_location');
		$grouping = maybe_unserialize( $option );	

		if ( empty($grouping) )
			$this->items = array();	
		else
			foreach( $grouping as $key => $item )
			{	
				if ( empty($this->records) || in_array($item['id'], $this->records) )
				{					
					$this->items[] = $item;
				}
			}			
		$this->total_items = count($this->items);	
		$this->forming_tables();	
	}
}
?>