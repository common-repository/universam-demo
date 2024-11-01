<?php 	
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_installed_applications extends USAM_List_Table
{
	function column_service( $item ) 
    {			
		$name = usam_get_name_service($item->service_code);
		$actions = $this->standart_row_actions( $item->id, 'application' );		
		$this->row_actions_table( $name, $actions );	
	}
	
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'activate'  => __('Активировать', 'usam'),
			'deactivate' => __('Отключить', 'usam'),
			'delete'    => __('Удалить', 'usam'),
		);
		return $actions;
	}	

	function get_sortable_columns() 
	{
		$sortable = array(
			'service'  => array('service_code', false),	
			'active'  => array('active', false),	
		);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(        
			'cb'           => '<input type="checkbox" />',				
			'service'      => __('Сервис', 'usam'),
			'group_code'   => __('Группа', 'usam'),
			'active'       => __('Активность', 'usam'),						
        );				
		return $columns;
    }	
	
	function prepare_items() 
	{					
		$this->get_query_vars();		
		if ( empty($this->query_vars['include']) )
		{		
			
		}
		$query = new USAM_Integration_Services_Query( $this->query_vars );
		$this->items = $query->get_results();	
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
		}	
	}
}