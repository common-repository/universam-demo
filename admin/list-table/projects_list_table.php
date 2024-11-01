<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/tasks_table.php' );		
class USAM_List_Table_projects extends USAM_Table_Tasks 
{	
	function column_title( $item )
	{		
		$title = '<a class="row-title" href="'.usam_get_event_url( $item->id, $item->type ).'">'.$item->title.'</a>';		
		$actions = $this->standart_row_actions( $item->id, 'project' );
		if ( !current_user_can('delete_'.$item->type))
			unset($actions['delete']);
		if ( !current_user_can('edit_'.$item->type))
			unset($actions['edit']);
		$this->row_actions_table( $title, $actions );	
	}
	
	function column_type( $item )
	{	
		echo usam_get_event_type_name( $item->type ); 
	}
	
	function get_columns()
	{
        $columns = array(   
			'cb'            => '<input type="checkbox">',			
			'title'         => __('Название', 'usam'),
			'type'          => __('Тип', 'usam'),				
			'description'   => __('Описание', 'usam'),
			'status'        => __('Статус', 'usam'),	
			'time'          => __('Срок', 'usam'),	
			'manager'       => __('Руководитель', 'usam'),	
			'participants'  => __('Участники', 'usam'),				
        );
        return $columns;	
    }	

	function prepare_items() 
	{	
		if ( $this->status == 'started' )
			$this->order = 'ASC';
	
		$this->get_query_vars();
		
		$this->query_vars['type'] = [];
		if ( current_user_can('view_project') )
			$this->query_vars['type'][] = 'project';
		if ( current_user_can('view_closed_project') )
			$this->query_vars['type'][] = 'closed_project';
		if ( $this->query_vars['type'] )
		{
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