<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_parser_url extends USAM_List_Table
{	
	function get_bulk_actions_display() 
	{
		$actions = [
			'delete'       => __('Удалить', 'usam'),
			'not_processed' => __('Не обработан', 'usam')
		];
		return $actions;
	}	

	public function return_post()
	{
		return ['id'];
	}	
		   
	function get_sortable_columns()
	{
		$sortable = [
			'status' => ['status', false],
			'date' => ['date_insert', false],		
		];
		return $sortable;
	}
		
	function column_url( $item ) 
    {
		echo "<a href='$item->url' target='_blank'>$item->url</a>";
    }

	function column_status( $item ) 
    {
		echo $item->status ? '<span class="item_status_valid item_status">'.__("Обработан","usam").'</span>':'<span class="status_blocked item_status">'.__("Не обработан","usam").'</span>';
    }	
		
	function get_columns()
	{
        $columns = [   			
			'cb'      => '<input type="checkbox" />',		
			'url'     => __('Ссылка', 'usam'),
			'status'  => __('Статус', 'usam'),			
			'date'  => __('Дата', 'usam'),				
        ];		
        return $columns;
    }	
			
	function prepare_items() 
	{	
		global $wpdb;	

		$this->get_standart_query_parent();	
		if ( $this->id )
			$this->where[] = "site_id=$this->id";	
		if ( $this->search != '' )
		{			
			$this->where[] = "url LIKE LOWER ('%".$this->search."%')";
		}
		$where = implode( ' AND ', $this->where );	
		$this->items = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS * FROM ".USAM_TABLE_PARSING_SITE_URL." WHERE $where ORDER BY {$this->orderby} {$this->order} {$this->limit}");	
		$this->total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );
		$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
	}
}
?>