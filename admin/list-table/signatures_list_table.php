<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_signatures extends USAM_List_Table 
{	
	public $orderby = 'id';
		
	function column_signature( $item ) 
    {
		$this->row_actions_table( $item->signature, $this->standart_row_actions( $item->id, 'signature' ) );	
	}
	
	function column_mailbox_id( $item ) 
    {
		if ( $item->mailbox_id )
		{
			$mailbox = usam_get_mailbox( $item->mailbox_id );
			if ( isset($mailbox['email']) )
				echo $mailbox['email'];
		}
		else
			_e('Все мои ящики','usam');
	}
			   
    function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam'),
		);
		return $actions;
	}
	
	function get_sortable_columns() 
	{
		$sortable = array(						
			'mailbox_id'       => array('mailbox_id', false),
			);
		return $sortable;
	}
		
	function get_columns()
	{	
		$columns = array(   
			'cb'          => '<input type="checkbox" />',	
			'signature'   => __('Подпись', 'usam'),			
			'mailbox_id'  => __('Для ящика', 'usam'),			
        );		
        return $columns;
    }
			
	function prepare_items() 
	{		
		require_once( USAM_FILE_PATH .'/includes/mailings/signature_query.class.php'  );
		
		$this->get_query_vars();
		$this->query_vars['manager_id'] = get_current_user_id();	
		if ( empty($this->query_vars['include']) )
		{							
		
		}
		$query_filters = new USAM_Signatures_Query( $this->query_vars );
		$this->items = $query_filters->get_results();	
		
		if ( $this->per_page )
		{
			$this->total_items = $query_filters->get_total();			
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}