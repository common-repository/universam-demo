<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Groups_Table extends USAM_List_Table
{	
	protected $orderby = 'sort';	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
	function column_name( $item )
	{		
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, $this->group_type.'_group' ) );
	}
				   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'      => array('name', false),			
			'id'        => array('id', false),		
			'sort'       => array('sort', false),			
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
	
	public function get_number_columns_sql()
    {       
		return array('sort' );
    }
	
	function prepare_items() 
	{	
		$this->get_query_vars();					
		$this->query_vars['type'] = $this->group_type;
		if ( empty($this->query_vars['include']) )
		{	
						
		}
		$query = new USAM_Groups_Query( $this->query_vars );
		$this->items = $query->get_results();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}
?>