<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/contact_table.php' );
class USAM_List_Table_contacts_online extends USAM_Contacts_Table
{	
	protected $order = 'DESC';
	function get_columns()
	{		
        $columns = array(           
			'name'           => __('Контакт', 'usam'),		
			'location'       => __('Город', 'usam'),	
			'visit'          => __('Количество визитов', 'usam'),	
			'contact_source' => __('Источник', 'usam')
        );	
		if ( current_user_can('sale') )
		{
			$columns['sum'] = __('Всего куплено', 'usam');
		}		
		$columns['date'] = __('Добавлен', 'usam');
        return $columns;
    }	
	
	function prepare_items() 
	{							
		$this->get_query_vars();
		$this->query_vars['fields'] = ['count_visit','all'];			
		$this->query_vars['cache_case'] = true;		
		$this->query_vars['cache_meta'] = true;				
		$this->query_vars['cache_thumbnail'] = true;
		$this->query_vars['source'] = 'all';	
		$this->query_vars['status'] = 'all';	
		$this->query_vars['online'] = 1;	
		if ( empty($this->query_vars['include']) )
		{		
			$this->get_vars_query_filter();
		}			
		$_contacts = new USAM_Contacts_Query( $this->query_vars );
		$this->items = $_contacts->get_results();				
		$this->total_items = $_contacts->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}
?>