<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );		
class USAM_List_Table_Sliders extends USAM_List_Table 
{
	private $product_slider_ids;	
	private $slider_items = 0;	
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'activate'  => __('Публиковать', 'usam'),
			'deactivate' => __('В черновик', 'usam'),	
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}	
		
	function get_sortable_columns() {
		$sortable = array(
			'title'      => array('title', false),
			'price'      => array('price', false),			
			'SKU'        => array('SKU', false),
			'date'       => array('date', false)
			);
		return $sortable;
	}
	
	function column_name( $item )
	{	
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, 'slider', ['copy' => __('Копировать', 'usam')] ) );
	}
		
	function get_columns(){
        $columns = array(           
			'cb'         => '<input type="checkbox" />',				
			'name'       => __('Название', 'usam'),
			'id'         => __('Код', 'usam'),			
			'active'     => __('Публикация', 'usam'),			
		//	'type'       => __('Тип', 'usam'),
        );
        return $columns;
    }
	
	public function prepare_items( )
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
		
		$sql_query = "SELECT SQL_CALC_FOUND_ROWS * FROM ".USAM_TABLE_SLIDER." WHERE {$where} ORDER BY {$this->orderby} {$this->order} {$this->limit}";
		$this->items = $wpdb->get_results($sql_query );	

		$this->total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );	
	
		$this->_column_headers = $this->get_column_info();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );		
	}
}