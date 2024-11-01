<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_distance extends USAM_List_Table
{	
	protected $pimary_id = 'from_location_id';
	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
	function column_from_location( $item )
	{			
		$title = usam_get_full_locations_name( $item->from_location_id );		
		$this->row_actions_table( $title, $this->standart_row_actions( $item->from_location_id.'-'.$item->to_location_id, 'distance' ) );
	}
	
	function column_to_location( $item )
	{			
		echo usam_get_full_locations_name( $item->to_location_id );	
	}
   
	function get_sortable_columns()
	{
		$sortable = array(		
			'distance'  => array('distance', false),	
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'               => '<input type="checkbox" />',				
			'from_location'    => __('Название', 'usam'),
			'to_location'      => __('Номер', 'usam'),
			'distance'         => __('Расстояние', 'usam'),			
        );		
        return $columns;
    }	
	
	public function get_number_columns_sql()
    {       
		return array('distance' );
    }
	
	function prepare_items() 
	{			
		global $wpdb;
	
		$this->get_standart_query_parent( );

		$search_terms = $this->search != '' ? explode( ' ', $this->search ): array();
		$search_sql = array();			
		foreach ( $search_terms as $term )
		{
			$search_sql[$term][] = "title LIKE '%".esc_sql( $term )."%'";
			if ( is_numeric( $term ) )
				$search_sql[$term][] = 'id = ' . esc_sql( $term );
			$search_sql[$term] = '(' . implode( ' OR ', $search_sql[$term] ) . ')';
		}
		$search_sql = implode( ' AND ', array_values( $search_sql ) );
		if ( $search_sql )
		{
			$this->where[] = $search_sql;
		}		
		$where = implode( ' AND ', $this->where );			
		
		$sql_query = "SELECT SQL_CALC_FOUND_ROWS * FROM ".USAM_TABLE_LOCATIONS_DISTANCE." WHERE {$where} ORDER BY {$this->orderby} {$this->order} {$this->limit}";
		$this->items = $wpdb->get_results($sql_query );	

		$this->total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );	
	
		$this->_column_headers = $this->get_column_info();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );		
	}	
}
?>