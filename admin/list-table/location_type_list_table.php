<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_location_type extends USAM_List_Table
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
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, 'location_type' ) );		
	}		
   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'      => array('name', false),			
			'type'       => array('type', false),			
			'sort'       => array('sort', false),			
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'               => '<input type="checkbox" />',				
			'name'             => __('Название', 'usam'),		
			'code'             => __('Код', 'usam'),	
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
		global $wpdb;
				
		$this->get_standart_query_parent();		
		if ( $this->search != '' )
		{			
			$this->where[] = "name='".$this->search."'";			
		}
		$where = implode( ' AND ', $this->where );	
		
		$sql_query = "SELECT SQL_CALC_FOUND_ROWS * FROM ".USAM_TABLE_LOCATION_TYPE." WHERE $where ORDER BY {$this->orderby} {$this->order} {$this->limit}";
		$this->items = $wpdb->get_results($sql_query);	
		
		$this->total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );		
		$this->forming_tables();		
	}
}
?>