<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/contact_table.php' );
class USAM_Contact_Table extends USAM_Contacts_Table
{				
	function column_select( $item ) 
    {		
		echo "<a href='' id='add_user' data-id='$item->id'>".__('добавить','usam')."</a>";
	}
	
	function get_columns()
	{		
        $columns = array(           					
			'contact'        => __('Контакт', 'usam'),					
			'contact_source' => __('Источник', 'usam'),		
        );		
		if ( empty($_REQUEST['screen']) || $_REQUEST['screen'] == 'address_book' )
			$columns['communication'] = __('Адрес электронной почты', 'usam');		
		elseif ( $_REQUEST['screen'] == 'phone_book' )
			$columns['communication_phone'] = __('Телефон', 'usam');
	
		if ( empty($_REQUEST['screen']) || $_REQUEST['screen'] != 'address_book' && $_REQUEST['screen'] != 'phone_book' )
			$columns['select'] =  __('Выбрать', 'usam');
        return $columns;
    }
		
	function prepare_items() 
	{		
		$this->get_query_vars();	
		$this->query_vars['source'] = 'all';	
		if ( !empty($_GET['contact']) && !empty($_GET['company']) && $_GET['contact'] == 'company' )
		{
			$this->query_vars['company_id'] = absint($_GET['company']);			
		}		
		$_contacts = new USAM_Contacts_Query( $this->query_vars );
		$this->items = $_contacts->get_results();			
		$this->total_items = $_contacts->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}
?>