<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_loyalty_programs extends USAM_List_Table 
{
	function column_type($item)
	{		
		echo usam_get_site_trigger( $item['rule_type'] ); 
	}
	
	function column_name( $item )
	{	
		$this->row_actions_table( $this->item_edit($item['id'], $item['name'], 'loyalty_program'), $this->standart_row_actions( $item['id'], 'loyalty_program', ['copy' => __('Копировать', 'usam')]) );
	}		
   	
	function get_sortable_columns() 
	{
		$sortable = array(
			'active'  => array('active', false),
			'start_date' => array('start_date', false),
			'end_date'   => array('end_date', false),			
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'              => '<input type="checkbox" />',		
			'name'            => __('Название правила', 'usam'),
			'active'          => __('Активность', 'usam'),
			'type'            => __('Триггер', 'usam'),
			'value'           => __('Количество', 'usam'),			
			'start_date'      => __('Начало', 'usam'),
			'end_date'        => __('Окончание', 'usam')
        );
        return $columns;
    }
	
	function prepare_items() 
	{			
		$this->get_query_vars();
		$this->items = usam_get_bonuses_rules( $this->query_vars );
		if ( $this->per_page )
		{
			$this->total_items = count(usam_get_bonuses_rules( ));
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}