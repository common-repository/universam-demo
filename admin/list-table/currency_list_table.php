<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_currency extends USAM_List_Table
{
	protected $pimary_id = 'code';	
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}		
			
	function column_name( $item )
	{
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->code, 'currency' ) );
	}	
   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'            => array('name', false),			
			'code'            => array('code', false),	
			'numerical'       => array('numerical', false),
		);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(   			
			'cb'           => '<input type="checkbox" />',		
			'name'         => __('Название', 'usam'),
			'code'         => __('ISO код', 'usam'),
			'numerical'    => __('ISO код', 'usam'),
			'symbol'       => __('Символ', 'usam'),
			'symbol_html'  => __('HTML код', 'usam'),						
        );		
        return $columns;
    }	
			
	function prepare_items() 
	{	
		global $wpdb;	

		$this->get_standart_query_parent( );
	
		if ( $this->search != '' )
		{			
			$this->where[] = "name LIKE LOWER ('%".$this->search."%') OR code LIKE LOWER ('%".$this->search."%')";			
		}
		$where = implode( ' AND ', $this->where );	
		
		$sql_query = "SELECT SQL_CALC_FOUND_ROWS * FROM ".USAM_TABLE_CURRENCY." WHERE $where ORDER BY {$this->orderby} {$this->order} {$this->limit}";
		$this->items = $wpdb->get_results($sql_query);		
		$this->total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );
		
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}
?>