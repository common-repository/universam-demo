<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_underprice extends USAM_List_Table 
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
		$this->row_actions_table( $this->item_edit($item['id'], $item['title'], 'underprice'), $this->standart_row_actions($item['id'], 'underprice') );	
	}	
		 
	function get_sortable_columns()
	{
		$sortable = array(
			'title'    => array('title', false),		
			'value'    => array('value', false),		
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'          => '<input type="checkbox" />',
			'title'       => __('Название правила', 'usam'),		
			'value'       => __('Наценка', 'usam'),			
        );		
        return $columns;
    }
	
	function prepare_items() 
	{			
		$option = get_site_option('usam_underprice_rules');
		$rules = maybe_unserialize( $option );		
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