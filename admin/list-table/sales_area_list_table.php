<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_sales_area extends USAM_List_Table
{
	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
	function column_name( $item )
	{		
		$this->row_actions_table( $item['name'], $this->standart_row_actions( $item['id'], 'sales_area' ) );
	}
				   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'           => array('name', false),			
			); 
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'               => '<input type="checkbox" />',	
			'name'             => __('Название', 'usam'),					
        );		
        return $columns;
    }	

	function prepare_items() 
	{	
		$this->get_query_vars();
		$this->items = usam_get_sales_areas( $this->query_vars );
		if ( $this->per_page )
		{
			$this->total_items = count(usam_get_sales_areas( ));
			$this->set_pagination_args( array('total_items' => $this->total_items, 'per_page' => $this->per_page) );
		}		
	}
}
?>