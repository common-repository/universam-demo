<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/tasks_table.php' );		
class USAM_List_Table_Tasks extends USAM_Table_Tasks 
{	
	function get_columns()
	{
        $columns = [
			'cb'            => '<input type="checkbox" />',	
			'color'         => '',					
			'title'         => __('Название', 'usam'),		
			'status'        => __('Статус', 'usam'),	
			'time'          => __('Срок', 'usam'),	
			'manager'       => __('Автор', 'usam'),	
			'participants'  => __('Исполнители', 'usam'),	
			'last_comment'  => __('Последний комментарий', 'usam'),					
        ];
        return $columns;	
    }
	
	function column_title( $item )
	{			
		$title = $item->title;
		$actions = usam_get_event_actions( $item->id );		
		if( $actions )
		{
			$done = 0;
			foreach ( $actions as $action )
				if( $action->status == 1 )
					$done++;
			$title .= "<span class='event_actions'>$done/".count($actions)."</span>";
		}
		echo '<a class="row-title" href="'.usam_get_event_url( $item->id, $item->type ).'">'.$title.'</a>';		
	}
	
	protected function get_filter_tablenav( ) 
	{				
		return ['interval' => ''];
	}
	
	public function extra_tablenav( $which ) 
	{
		if ( 'top' == $which )
		{			
			echo '<div class="alignleft actions">';	
				$this->standart_button();									
			echo '</div>';
		}
	}  
	
	function prepare_items() 
	{	
		if ( !current_user_can('view_task') )
			return;
		
		if ( $this->status == 'started' )
			$this->order = 'ASC';
	
		$this->get_query_vars();
		$this->query_vars['type'] = 'task';		
		if ( empty($this->query_vars['include']) )
		{
			$this->query_vars['status'] = $this->status == 'work' ? $this->work_statuses : $this->status;	
			$this->get_vars_query_filter();				
		}
		$this->get_vars_filter_task();
		
		$selected = $this->get_filter_value('role');
		$roles = $selected ? array_map('sanitize_title', (array)$selected) : [];
		$user_id = get_current_user_id();
		if ( in_array('assignments', $roles) )
		{
			$this->query_vars['users__not_in']['participant'] = $user_id;
			$this->query_vars['author'] = $user_id;
		}
		if ( in_array('my', $roles) )
			$this->query_vars['user_work'] = $user_id;
		if ( in_array('commission', $roles) )
			$this->query_vars['users']['participant'] = $user_id;
			
		$this->query_vars['cache_meta'] = true;
		$this->query_vars['cache_contacts'] = true;
		$this->query_vars['cache_actions'] = true;		
		
		$this->query_vars['add_fields'] = ['last_comment'];
		$query = new USAM_Events_Query( $this->query_vars );
		$this->items = $query->get_results();
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
		}
	}	
}