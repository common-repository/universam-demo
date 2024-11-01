<?php
require_once(USAM_FILE_PATH.'/includes/product/sets_query.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_sets extends USAM_List_Table
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
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, 'set' ) );		
	}	
	
	function column_status( $item )
	{	
		echo $item->status == 'publish'? '<span class="item_status_valid item_status">'.__('Опубликован', 'usam').'</span>':'<span class="status_blocked item_status">'.__('Черновик', 'usam').'</span>';		
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
        $columns = [           
			'cb'        => '<input type="checkbox" />',				
			'name'      => __('Название', 'usam'),
			'status'    => __('Статус', 'usam'),		
			'drag'      => '&nbsp;',							
        ];		
        return $columns;
    }
	
	function prepare_items() 
	{		
		$this->get_query_vars();						
		if ( empty($this->query_vars['include']) )
		{		
			
		}
		$query = new USAM_Sets_Query( $this->query_vars );
		$this->items = $query->get_results();					
		if ( $this->per_page )
		{
			$this->total_items = $query->get_total();	
			$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
		}		
	}
}
?>