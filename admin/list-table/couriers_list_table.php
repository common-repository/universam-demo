<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/contact_table.php' );
class USAM_List_Table_couriers extends USAM_Contacts_Table
{				
	function column_name( $item ) 
    { 
		echo "<div class='user_block'>";
		echo "<a href='".usam_get_employee_url( $item->id )."' class='image_container usam_foto'><img src='".esc_url( usam_get_contact_foto( $item->id ) )."' loading='lazy'></a>";
		echo "<div>";
		$name = '<a class="row-title js-object-value" href="'.usam_get_employee_url( $item->id ).'">'.$item->appeal.'</a>';	
		$name .= "<div class='item_labels'>";
		ob_start();
		usam_display_status( $item->status, 'courier' );
		$name .= ob_get_clean();	
		$name .= "</div>";		
		$this->row_actions_table( $name, $this->contact_standart_actions( $item ) );
		echo "<div>";
		echo "</div>";
	}	
	
	function contact_standart_actions( $item )
	{			
		$actions = $this->standart_row_actions( $item->id, 'employee' );
		if ( !current_user_can('delete_employee'))
			unset($actions['delete']);
		if ( !current_user_can('edit_employee'))
			unset($actions['edit']);
		return $actions;			
	}
	
	function column_status( $item )
	{
		usam_display_status( $item->status, 'courier' );
	}		
			
	function get_sortable_columns()
	{
		$sortable = array(
			'name'        => array('name', false),			
			'date'        => array('date_insert', false),		
			'chat'        => array('chat', false),	
			'online'      => array('online', false),				
			);
		return $sortable;
	}
	
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',
			'name'           => __('ФИО', 'usam'),			
			'online'         => __('Онлайн', 'usam'),					
        );				
        return $columns;
    }	
	
	function prepare_items() 
	{			
		$this->get_query_vars();			
		$this->query_vars['cache_case'] = true;			
		$this->query_vars['cache_thumbnail'] = true;	
		$this->query_vars['role__in'] = ['courier'];
		$this->query_vars['source'] = 'all';
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