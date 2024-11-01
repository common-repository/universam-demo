<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_languages extends USAM_List_Table
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
		$this->row_actions_table( $item['name'], $this->standart_row_actions( $item['id'], 'language' ) );
	}
				   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'           => array('name', false),			
			); 
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'               => '<input type="checkbox" />',	
			'name'             => __('Название', 'usam'),					
			'code'             => __('Код', 'usam'),			
        );		
        return $columns;
    }	

	function prepare_items() 
	{	
		$languages = maybe_unserialize(get_site_option('usam_languages'));
		if ( empty($languages) )
			$this->items = array();	
		else
			foreach( $languages as $key => $item )
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