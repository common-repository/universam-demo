<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_sql extends USAM_List_Table 
{	   
	function __construct( $args = array() )
	{	
		parent::__construct( $args );		
    }	
	
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
			
	function column_title( $item ) 
    {
		$this->row_actions_table( $item['title'], $this->standart_row_actions( $item['id'], 'sql' ) );	
	}	
	
	function column_size( $item ) 
    {
		if ( $item['size'] > 1024 ) 
		{
			$return = round($item['size'] / 1024, 1);
			$return .= ' '.__('Кб','usam');
		}
		else
			$return = $item['size'].' '. __('байт','usam');
		echo $return;
	}
	   	
	function get_sortable_columns() 
	{
		$sortable = array(
			'title'        => array('title', false),
			'size'         => array('size', false),
			'create_time'  => array('create_time', false),
			'update_time'  => array('update_time', false),	
		);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',	
			'title'          => __('Название', 'usam'),		
			'size'           => __('Размер', 'usam'),	
			'create_time'    => __('Дата создания', 'usam'),	
			'update_time'    => __('Дата изменения', 'usam'),	
			
        );		
        return $columns;
    }
	
	public function get_number_columns_sql()
    {       
		return array('size');
    }
	
	function prepare_items() 
	{			
		global $wpdb;
		$search_terms = $this->search != '' ? explode( ' ', $this->search ): array();
		$result = mysql_query("SHOW TABLES"); 	
		$this->items = array();		
		while ($row = mysql_fetch_row($result))
		{			
			$res = mysql_query("SHOW TABLE STATUS LIKE '".$row[0]."'");
			$row1 = mysql_fetch_assoc($res);
				
			$this->items[] = array( 'size' => intval($row1['Data_length']),
									'data_length' => intval($row1['Data_length']),
									'create_time' => $row1['Create_time'],
									'update_time' => $row1['Update_time'],
									'title' => $row[0],
									);		
		}	
		$this->total_items = count($this->items);
		$this->forming_tables();	
	}	
}