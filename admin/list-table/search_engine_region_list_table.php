<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_search_engine_region extends USAM_List_Table
{
	protected $orderby = 'sort';
	protected $order = 'ASC';
	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
	function column_location( $item )
	{		
		$location = usam_get_full_locations_name( $item->location_id );		
		$this->row_actions_table( $location, $this->standart_row_actions( $item->id, 'search_engine_region' ) );
	}
	
	function column_search_engine( $item )
	{		
		switch ( $item->search_engine ) 
		{
			case 'g' :
				esc_html_e( 'Google', 'usam');
			break;
			case 'y' :
				esc_html_e( 'Яндекс', 'usam');
			break;
		}
	}
				   
	function get_sortable_columns()
	{
		$sortable = array(
			'location'   => array('location', false),			
			'code'       => array('code', false),		
			'active'     => array('active', false),	
			'search_engine' => array('search_engine', false),	
		); 
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'            => '<input type="checkbox" />',	
			'location'      => __('Местоположение', 'usam'),			
			'active'        => __('Активность', 'usam'),			
			'name'          => __('Местоположение в поисковой системе', 'usam'),	
			'code'          => __('Код местоположения', 'usam'),	
			'search_engine' => __('Поисковик', 'usam'),				
        );		
        return $columns;
    }	

	function prepare_items() 
	{	
		global $wpdb;	

		$this->get_standart_query_parent( );		
	
		if ( $this->search != '' )
		{			
			$this->where[] = "name='".$this->search."'";			
		}
		$where = implode( ' AND ', $this->where );	
		
		$sql_query = "SELECT SQL_CALC_FOUND_ROWS * FROM ".USAM_TABLE_SEARCH_ENGINE_REGIONS." WHERE $where ORDER BY {$this->orderby} {$this->order} {$this->limit}";
		$this->items = $wpdb->get_results($sql_query);		
		$this->total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );
		
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}
?>