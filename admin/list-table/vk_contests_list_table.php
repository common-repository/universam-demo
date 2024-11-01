<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_vk_contests extends USAM_List_Table
{		
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}	
	
	function column_title( $item ) 
    {
		$this->row_actions_table( $item['title'], $this->standart_row_actions( $item['id'], 'contest' ) );	
	}	
	
	function column_winners( $item )
    {				
		if (!empty($item['winners']))
		foreach ( $item['winners'] as $winner )
		{
			echo $winner['last_name'].' '.$winner['first_name'].'('.$winner['last_name'].')';
		}
    }   	
	 
	function get_sortable_columns()
	{
		$sortable = array(
			'title'     => array('title', false),		
			'status'    => array('status', false),		
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',
			'title'      => __('Название', 'usam'),
			'active'     => __('Активность', 'usam'),
			'message'    => __('Описание', 'usam'),		
			'winners'    => __('Победители', 'usam'),					
			'start_date' => __('Дата начала', 'usam'),		
			'end_date'   => __('Дата окончания', 'usam'),		
        );		
        return $columns;
    }
	
	function prepare_items() 
	{		
		$option = get_site_option('usam_vk_contest');
		$items = maybe_unserialize( $option );
		if ( empty($items) )
			$this->items = array();	
		else
			foreach( $items as $key => $item )
			{	
				if ( empty($this->record) || in_array($item['id'], $this->records) )
				{						
					$this->items[] = $item;
				}
			}
		$this->total_items = count($this->items);		
		$this->forming_tables();
	}
}
?>