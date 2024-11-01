<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/feedback/chat_bot_templates_query.class.php');
class USAM_List_Table_chat_bots extends USAM_List_Table 
{		
	function column_name( $item ) 
    {
		$name = "<a href='".admin_url("admin.php?page=marketing&tab=chat_bots&table=chat_bot_commands&n=".$item->id)."'>$item->name</a>";
		$name = $this->item_edit( $item->id, $name, 'chat_bot_template' );		
		$this->row_actions_table( $name, $this->standart_row_actions( $item->id, 'chat_bot_template' ) );	
	}	
	
	function column_channel( $item ) 
	{	
		$channels = array( 'all' => __('Все каналы','usam'), 'chat' => __('Чат сайта','usam'), 'vk' => __('Контакт','usam'), 'telegram' => 'Telegram' );
		echo isset($channels[$item->channel])?$channels[$item->channel]:'';
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
			'name'           => __('Название шаблона', 'usam'),				
			'active' 	     => __('Активность', 'usam'),				
			'date' 	         => __('Дата создания', 'usam'),	
			'channel'        => __('Канал', 'usam'),				
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
		$departments = new USAM_Chat_Bot_Templates_Query( $this->query_vars );
		$this->items = $departments->get_results();				
		$this->total_items = $departments->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}