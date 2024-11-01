<?php
require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
require_once( USAM_FILE_PATH .'/admin/includes/list_table/tasks_table.php' );		
class USAM_List_Table_affairs extends USAM_Table_Tasks 
{			
	function column_title( $item )
	{	
		$title = "<div class='event_type_name'>".usam_get_event_type_name( $item->type )." №  $item->id</div>";
		echo '<a class="row-title" href="'.usam_get_event_url( $item->id, $item->type ).'">'.$title.$item->title.'</a>';		
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

	function get_columns()
	{
        $columns = [
			'cb'            => '<input type="checkbox" />',							
			'color'         => '',				
			'title'         => __('Название', 'usam'),			
			'time'          => __('Срок', 'usam'),	
			'status'        => __('Статус', 'usam'),	
			'last_comment'  => __('Последний комментарий', 'usam'),				
			'object_type'   => __('Объект', 'usam')
        ];
		$user_ids = usam_get_subordinates();
		if ( $user_ids )
			$columns['manager'] = __('Ответственный', 'usam');
        return $columns;	
    }	
		
	function prepare_items() 
	{		
		$this->get_query_vars();				
		
		$this->query_vars['links_query'] = [['object_type' => ['company', 'contact']]];
		$this->query_vars['type'] = [];
		$types = usam_get_events_types();
		foreach( $types as $type => $v ) 
		{
			if ( current_user_can('view_'.$type) )
				$this->query_vars['type'][] = $type;
		}		
		if ( $this->query_vars['type'] )
		{		
			$this->get_vars_filter_task();		
			if ( empty($this->query_vars['include']) )
			{
				$selected = $this->get_filter_value( 'types_event' );
				if ( $selected )		
					$this->query_vars['type'] = array_map('sanitize_title', (array)$selected);				
				$this->query_vars['status'] = $this->status == 'work' ? $this->work_statuses :$this->status;	
				$this->get_vars_query_filter();
			}		
			$this->query_vars['add_fields'] = ['last_comment'];
			$this->query_vars['cache_meta'] = true;		
			$this->query_vars['cache_objects'] = true;	
			$this->query_vars['cache_contacts'] = true;
			$query = new USAM_Events_Query( $this->query_vars );
			$this->items = $query->get_results();
			if ( $this->per_page )
			{
				$total_items = $query->get_total();	
				$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
			}	
		}
	}
}