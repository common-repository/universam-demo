<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_view_grouping extends USAM_List_Table
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
		$this->row_actions_table( $item['name'], $this->standart_row_actions( $item['id'], 'view_grouping' ) );
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
		$option = get_site_option('usam_order_view_grouping');
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