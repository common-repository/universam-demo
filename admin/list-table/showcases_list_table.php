<?php
require_once( USAM_FILE_PATH .'/includes/exchange/showcases_query.class.php' );
require_once( USAM_FILE_PATH .'/includes/exchange/showcase.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_showcases extends USAM_List_Table
{	
	function get_bulk_actions_display() 
	{
		$actions = [
			'delete'    => __('Удалить', 'usam'),		
		];
		return $actions;	
	}
	
	function column_name( $item )
	{	
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, 'showcase' ) );
	}
	
	function column_status( $item )
	{	
		echo usam_get_showcase_status_name( $item->status );
	}
			  
	function get_sortable_columns() 
	{
		$sortable = [
			'id'       => array('id', false),
			'domain'   => array('domain', false),
			'number_products' => array('number_products', false),
		];
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = [
			'cb'          => '<input type="checkbox" />',
			'name'        => __('Название', 'usam'),			
			'domain'      => __('Домен', 'usam'),					
			'number_products' => __('Количество товаров', 'usam'),	
			'status'      => __('Статус', 'usam'),				
        ];
        return $columns;
    }
	
	function prepare_items() 
	{					
		$this->get_query_vars();			
		$this->query_vars['cache_meta'] = true;
		if ( empty($this->query_vars['include']) )
		{			
			$selected = $this->get_filter_value( 'status' );
			if ( $selected !== null )
				$this->query_vars['status'] = array_map('sanitize_title', (array)$selected);
			else
				$this->query_vars['status'] = 'all';		
		} 
		$query = new USAM_Showcases_Query( $this->query_vars );
		$this->items = $query->get_results();		
		$this->total_items = $query->get_total();
		$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
	}
}