<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_product_day extends USAM_List_Table 
{	
	function __construct( $args = array() )
	{	
		parent::__construct( $args );	
    }
	
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'change_product_day' => __('Сменить', 'usam'),
			'delete'             => __('Удалить', 'usam')			
		);
		return $actions;
	}	
	
	function column_name( $item ) 
    {
		$this->row_actions_table( $this->item_edit( $item['id'], $item['name'], 'product_day' ), $this->standart_row_actions( $item['id'], 'product_day', ['copy' => __('Копировать', 'usam')] ) );	
	}
	
	function get_sortable_columns()
	{
		$sortable = array(
			'title'   => array('name', false),		
			'status'  => array('status', false),		
			'date'    => array('date_insert', false),
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'          => '<input type="checkbox" />',
			'name'        => __('Название', 'usam'),
			'active'      => __('Активность', 'usam'),					
			'type_prices' => __('Типы цен', 'usam'),		
			'interval'    => __('Интервал', 'usam'),
			'date'        => __('Дата создания', 'usam'),		
        );		
        return $columns;
    }
	
	function prepare_items() 
	{			
		$option = get_site_option('usam_product_day_rules');
		$rules = maybe_unserialize( $option );		
		$this->items = array();
		if ( !empty($rules) )
			foreach( $rules as $role )
			{			
				if ( empty($this->records) || !empty($this->records) && in_array($role['id'], $this->records))
				{
					$this->items[] = $role;	
				}	
			}		
		$this->total_items = count($this->items);
		$this->forming_tables();
	}
}
?>