<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php');	
class USAM_List_Table_import_data extends USAM_List_Table
{
	protected $pimary_id = 'index_id';
	protected $orderby = 'index_id';
	protected $order = 'ASC';
	private $name_table = 'exchange_rule'; 
	protected $columns = [];
	
	function __construct( $args ) 
	{
		global $wpdb;
		$id = absint($_GET['n']);	
		$rule = usam_get_exchange_rule( $id );
		$this->name_table = $rule['type']."_".$rule['id'];
		$results = $wpdb->get_results("SHOW COLUMNS FROM ".$this->name_table);	
		foreach( $results as $result )
		{
			$this->columns[$result->Field] = $result->Field;
		}
		parent::__construct( $args );
	}
	
	function get_bulk_actions_display() 
	{
		$actions = [
			'delete'    => __('Удалить', 'usam')
		];
		return $actions;
	}
   
	function get_sortable_columns()
	{		
		return $this->columns;
	}
		
	function get_columns()
	{
        return $this->columns;
    }	
			
	function prepare_items() 
	{	
		global $wpdb;			
		$this->get_standart_query_parent( );	
		if ( $this->search != '' )
		{			
			$columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->name_table}");		
			$s = [];
			foreach( $columns as $column )
				$s[] = "{$column->Field} LIKE LOWER ('%".$this->search."%')";			
			$this->where[] = implode( ' OR ', $s );	
		}
		$where = implode( ' AND ', $this->where );	
		
		$sql_query = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->name_table} WHERE $where ORDER BY {$this->orderby} {$this->order} {$this->limit}";
		$this->items = $wpdb->get_results($sql_query);		
		$this->total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );
		
		$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
	}
}