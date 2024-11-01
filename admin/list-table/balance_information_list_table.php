<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_balance_information extends USAM_List_Table 
{	
	function __construct( $args = array() )
	{	
		parent::__construct( $args );	
    }
	
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}	
	
	function column_title( $item ) 
    {					
		$this->row_actions_table( $item['name'], $this->standart_row_actions($item['id'], 'balance_information') );	
	}	
		 
	function get_sortable_columns()
	{
		$sortable = array(
			'title'    => array('title', false),			
		);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'          => '<input type="checkbox" />',
			'title'       => __('Название', 'usam'),		
        );		
        return $columns;
    }
	
	function prepare_items() 
	{			
		$rules = maybe_unserialize( get_option('usam_balance_information', [] ) );		
		$this->items = array();
		if ( !empty($rules) )
			foreach( $rules as $rule )
			{			
				if ( empty($this->records) || !empty($this->records) && in_array($rule['id'], $this->records))
				{
					$this->items[] = $rule;	
				}	
			}		
		$this->total_items = count($this->items);
		$this->forming_tables();
	}
}
?>