<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/contact_table.php' );
class USAM_List_Table_contacts_advertising_campaign extends USAM_Contacts_Table
{		
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',
			'name'           => __('Контакт', 'usam'),
        );		
		if ( current_user_can('sale') )
		{
			$columns['sum'] = __('Всего куплено', 'usam');
		}
		$columns['online'] = __('Онлайн', 'usam');
        return $columns;
    }	
	
	function prepare_items() 
	{
		$this->get_query_vars();
		$this->query_vars['cache_meta'] = true;				
		$this->query_vars['cache_thumbnail'] = true;
		$this->query_vars['source__not_in'] = 'employee';
		$this->query_vars['campaign'] = [ $this->id ];
		if ( empty($this->query_vars['include']) )
		{		
			$this->query_vars['status__not_in'] = 'temporary';					
			if ( !isset($_REQUEST['action']) || $_GET['action'] != 'delete' )
			{
				if ( $this->window == 'my' )
					$this->query_vars['manager_id'] = get_current_user_id();	
			}
			$this->get_vars_query_filter();
		}	
		else
			$this->query_vars['status'] = 'all';	
		$_contacts = new USAM_Contacts_Query( $this->query_vars );
		$this->items = $_contacts->get_results();				
		$this->total_items = $_contacts->get_total();		
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}
?>