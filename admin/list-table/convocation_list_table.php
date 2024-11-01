<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/tasks_table.php' );		
class USAM_List_Table_convocation extends USAM_Table_Tasks 
{		
	function get_columns()
	{
        $columns = array(   
			'cb'            => '<input type="checkbox" />',			
			'title'         => __('Название', 'usam'),	
			'description'   => __('Описание', 'usam'),
			'status'        => __('Статус', 'usam'),	
			'time'          => __('Срок', 'usam'),	
			'manager'       => __('Руководитель', 'usam'),			
        );
        return $columns;	
    }	

	function prepare_items() 
	{			
		if ( current_user_can('view_convocation') )
		{
			if ( $this->status == 'started' )
				$this->order = 'ASC';
		
			$this->get_query_vars();
		
			$this->query_vars['type'] = ['convocation'];		
			if ( empty($this->query_vars['include']) )
			{
				$this->query_vars['status'] = $this->status == 'work' ? $this->work_statuses :$this->status;		
				$this->get_vars_query_filter();
			}
			
			$this->query_vars['user_work'] = get_current_user_id();	
			$this->query_vars['cache_meta'] = true;		
			$this->query_vars['cache_contacts'] = true;				
			
			$query = new USAM_Events_Query( $this->query_vars );
			$this->items = $query->get_results();
			if ( $this->per_page )
			{
				$total_items = $query->get_total();	
				$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
			}
		}		
	}	
}