<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_purchase_rules extends USAM_List_Table
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
		$this->row_actions_table( $item['name'], $this->standart_row_actions( $item['id'], 'purchase_rule' ) );	
	}	  
	 
	function get_sortable_columns()
	{
		$sortable = array(
			'name'     => array('name', false),		
			'active'    => array('active', false),		
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',
			'name'       => __('Название правила', 'usam'),
			'active'     => __('Активность', 'usam'),		
        );		
        return $columns;
    }
	
	function prepare_items() 
	{		
		$option = get_site_option('usam_purchase_rules');	
		$rules = maybe_unserialize( $option );		
		$this->items = array();
		if ( !empty($rules) )
			foreach( $rules as $role )
			{			
				if ( empty($this->records) || !empty($this->records) && in_array($role['id'], $this->records))
				{
					$this->items[] = $role;	
				}	
			}		
		$this->total_items = count($this->items);
		$this->forming_tables();
	}
}
?>