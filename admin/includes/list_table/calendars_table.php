<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Table_Calendars extends USAM_List_Table
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
		$this->row_actions_table( $item['name'], $this->standart_row_actions( $item['id'], 'calendar' ) );
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
			'sort'             => __('Сортировка', 'usam'),			
        );		
        return $columns;
    }	
	
	public function get_number_columns_sql()
    {       
		return array('sort' );
    }
	
	function prepare_items() 
	{	
		$calendars = usam_get_calendars( );	

		if ( empty($calendars) )
			$this->items = array();	
		else
			foreach( $calendars as $key => $item )
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