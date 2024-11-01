<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_constructor extends USAM_List_Table
{	
	public  $orderby = 'date';	
	public  $order   = 'DESC';	
	
	function no_items() 
	{
		_e( 'Вы еще не создали отчеты.', 'usam');
	}	
		
	function get_bulk_actions_display() 
	{
		$actions = array(			
			'delete'    => __('Удалить', 'usam'),
		);
		return $actions;
	}
	
	public function column_name( $item ) 
	{		
		if( preg_match("/{$this->page}_([^&]*)_table/",$item->screen_id, $matches) )
			$url = add_query_arg( array('filter_id' => $item->id, 'table' => $matches[1] ), $this->url  );
		else
			$url = '';
		return "<a href='$url'>$item->name</a>";
	}
	
	function get_columns()
	{
        $columns = array(   
			'cb'         => '<input type="checkbox" />',				
			'name'       => __('Название', 'usam'),		
			'customer'   => __('Автор', 'usam'),				
        );
        return $columns;	
    }	
	
	function prepare_items() 
	{		
		$this->get_query_vars();				
		
		$this->query_vars['page'] = 'report';
		
		require_once( USAM_FILE_PATH . '/admin/includes/filters_query.class.php' );
		$query = new USAM_Admin_Filters_Query( $this->query_vars );
		$this->items = $query->get_results();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}
?>