<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/feedback/chat_bot_commands_query.class.php');
class USAM_List_Table_chat_bot_commands extends USAM_List_Table 
{		
	function column_id( $item ) 
    {
		$name = $this->item_edit( $item->id, $item->id, 'chat_bot_command' );		
		$this->row_actions_table( $name, $this->standart_row_actions( $item->id, 'chat_bot_command' ) );	
	}	
	
	function column_template( $item ) 
	{	
		$templates = usam_get_chat_bot_command_metadata( $item->id, 'templates' );
		foreach ( $templates as $key => $template ) 
			echo "<p>$template</p>";
	}	
	
	function get_sortable_columns()
	{
		$sortable = array(
			'name'       => array('name', false),		
			'active'     => array('active', false),		
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(  
			'cb'             => '<input type="checkbox" />',
			'id'             => 'ID',
			'template'       => __('Шаблоны поиска', 'usam'),	
			'message'        => __('Ответ', 'usam'),	
			'active' 	     => __('Активность', 'usam'),			
			'time_delay' 	 => __('Задержка ответа', 'usam'),		
			'sort' 	         => __('Очередность', 'usam'),
        ); 	
        return $columns;
    }	
	
	function prepare_items() 
	{	
		$this->get_query_vars();			
		if ( empty($this->query_vars['include']) )
		{		
			$selected = $this->get_filter_value( 'chat_channel' );
			if ( $selected )
				$this->query_vars['channel'] = array_map( 'sanitize_title', $selected );			
		}				
		$departments = new USAM_Chat_Bot_Commands_Query( $this->query_vars );
		$this->items = $departments->get_results();				
		$this->total_items = $departments->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}