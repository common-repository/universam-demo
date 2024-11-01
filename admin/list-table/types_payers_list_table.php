<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_types_payers extends USAM_List_Table 
{	   
	protected $order = 'ASC';
	function get_bulk_actions_display() 
	{
		$actions = [
			'delete'    => __('Удалить', 'usam')
		];
		return $actions;
	}
	
	function column_name( $item ) 
    {
		$name = $this->item_edit( $item['id'], $item['name'], 'type_payer' );		
		$this->row_actions_table( $name, $this->standart_row_actions( $item['id'], 'type_payer' ) );	
	}
	
	function column_type( $item ) 
    {
		if ( $item['type'] == 'company' )
			_e( 'Юридическое лицо', 'usam');
		else
			_e( 'Физическое лицо', 'usam');	
	}
	
	function get_sortable_columns()
	{
		$sortable = array(
			'name'       => array('name', false),		
			'active'     => array('active', false),		
			'sort'       => array('sort', false),		
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(  
			'cb'             => '<input type="checkbox" />',
			'name'           => __('Название', 'usam'),	
			'active' 	     => __('Активность', 'usam'),		
			'type'           => __('Тип плательщика', 'usam'),					
			'sort'           => __('Сортировка', 'usam'),					
        ); 
        return $columns;
    }	
	
	function prepare_items() 
	{				
		$this->get_query_vars();
		$this->query_vars['active'] = 'all';
		$types_payers = usam_get_group_payers( $this->query_vars );
		
		$this->items = array();
		foreach ( $types_payers as $key => $item )
		{			
			if ( empty($this->records) || in_array($item['id'], $this->records) )
			{
				$this->items[$key] = $item;	
			}
		}
		$this->total_items = count($this->items);			
		$this->forming_tables();		
	}
}