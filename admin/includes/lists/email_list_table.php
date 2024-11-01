<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Email_Table extends USAM_List_Table
{	
	protected $order = 'DESC';	
		
	public function extra_tablenav( $which ) { }	
		
	function column_contact( $item ) 
    {
		echo '<span class="js-object-value">'.$item->from_name.'</span>';
	}
	
	public function single_row( $item ) 
	{		
		echo '<tr id = "contact-'.$item->id.'" data-customer_id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
		
	function column_email( $item ) 
	{		
		echo "<div class = 'js-select-email select_communication'>".$item->from_email."</div>";
	}	
	
	function column_mailbox( $item ) 
	{
		$mailbox = usam_get_mailbox($item->mailbox_id);
		echo $mailbox['email'];
	}	
	
	function get_sortable_columns()
	{
		$sortable = array(
			'mailbox'      => array('mailbox_id', false),
		);
		return $sortable;
	}
		
	function get_columns()
	{		
        $columns = array(           					
			'contact' => __('Контакт', 'usam'),					
			'email'   => __('Адрес электронной почты', 'usam'),				
			'mailbox' => __('Ящик', 'usam'),					
        );		
        return $columns;
    }
	
	
	function prepare_items() 
	{		
		$user_id = get_current_user_id();
	
		$this->get_query_vars();
		$this->query_vars['groupby'] = 'from_email';
		$this->query_vars['fields'] = 'all';							
		$this->query_vars['manager_id'] = array( 0, $user_id);			
	
		$_contacts = new USAM_Email_Query( $this->query_vars );
		$this->items = $_contacts->get_results();			
		$this->total_items = $_contacts->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}
?>