<?php
require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_departments extends USAM_List_Table 
{	
	function get_bulk_actions_display() 
	{
		if ( !current_user_can('delete_department') )
			return false;
		
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
	function column_name( $item ) 
    {
		if ( current_user_can('edit_department') )
		{
			$name = $this->item_edit( $item->id, $item->name, 'department' );		
			$this->row_actions_table( $name, $this->standart_row_actions( $item->id, 'department' ) );	
		}
		else
			echo $item->name;
	}
	
	function column_company( $item ) 
	{	
		$company = usam_get_company( $item->company );			
		echo !empty($company['name'])?$company['name']:'';	
	}	
	
	function column_chief( $item ) 
	{	
		if ( $item->chief )
		{
			$user = usam_get_contact( $item->chief );			
			echo $user['appeal'];	
		}
	}	
	
	function get_sortable_columns()
	{
		$sortable = array(
			'name'       => array('name', false),		
			'active'     => array('active', false),		
			'sort'       => array('sort', false),		
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(  
			'cb'             => '<input type="checkbox" />',
			'name'           => __('Название отдела', 'usam'),	
			'company'        => __('Компания', 'usam'),	
			'chief' 	     => __('Руководитель', 'usam'),				
        ); 
		if ( !current_user_can('delete_department') )
			unset($columns['cb']);	
        return $columns;
    }	
	
	function prepare_items() 
	{	
		$this->get_query_vars();			
		if ( empty($this->query_vars['include']) )
		{		
			$selected = $this->get_filter_value( 'company' );
			if ( $selected )
				$this->query_vars['company'] = absint($selected);			
		}				
		$departments = new USAM_Departments_Query( $this->query_vars );
		$this->items = $departments->get_results();				
		$this->total_items = $departments->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}