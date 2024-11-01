<?php
require_once( USAM_FILE_PATH . '/includes/directory/country_query.class.php'  );	
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_countries extends USAM_List_Table
{
	protected $pimary_id = 'code';	
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}		
			
	function column_name( $item )
	{
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->code, 'country' ) );
	}	
   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'          => array('name', false),			
			'code'          => array('code', false),	
			'numerical'     => array('numerical', false),
			'currency'      => array('currency', false),
			'phone_code'    => array('phone_code', false),
			'language_code' => array('language_code', false),
			'language'      => array('language', false),			
		);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(   			
			'cb'           => '<input type="checkbox" />',		
			'name'         => __('Название', 'usam'),
			'code'         => __('ISO код', 'usam'),			
			'numerical'    => __('Числовой ISO код', 'usam'),
			'currency'     => __('Код валюты', 'usam'),
			'phone_code'   => __('Код телефона', 'usam'),		
			'language'     => __('Язык', 'usam'),			
			'language_code'=> __('Код языка', 'usam'),			
        );		
        return $columns;
    }	
			
	function prepare_items() 
	{	
		$this->get_query_vars();				
		$this->query_vars['active'] = 'all';	
		
		$query = new USAM_Country_Query( $this->query_vars );	
		$this->items = $query->get_results();					
		$this->total_items = $query->get_total();
		$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
	}
}
?>