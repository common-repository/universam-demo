<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Manager_Table extends USAM_List_Table
{	
	protected $order = 'DESC';	
	protected $orderby = 'name';	
		
	public function extra_tablenav( $which ) { }	
	
	public function single_row( $item ) 
	{		
		echo '<tr id = "contact-'.$item->id.'" data-customer_id = "'.$item->user_id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	function column_contact( $item ) 
    {
		echo "<div class='user_block'>";
		echo "<div class='user_foto image_container usam_foto'><img src='".usam_get_contact_foto( $item->id )."'></div>";
		echo '<div class="user_name">'.$item->appeal.'</div>';	
		echo "</div>";
	}
	
	function column_post( $item ) 
    {		
		echo htmlspecialchars(usam_get_contact_metadata($item->id, 'post'));
	}
		
	function column_action( $item ) 
	{
		echo "<a href='' id='add_user' data-id='$item->user_id'>".__('Выбрать','usam')."</a>";
	}	
	
	function get_columns()
	{		
        $columns = array(           					
			'contact'        => __('Контакт', 'usam'),				
			'post'           => __('Должность', 'usam'),		
			'action'         => '',			
        );		 
        return $columns;
    }
		
	function prepare_items() 
	{		
		$query = array( 
			'fields' => 'all',	
			'order' => $this->order, 
			'orderby' => $this->orderby, 		
			'paged' => $this->get_pagenum(),	
			'number' => $this->per_page,	
			'search' => $this->search,
			'user_id__not_in' => 0,	
			'source' => 'employee',	
			'cache_meta' => true
		);			
		if ( !empty($_GET['company']) )
		{
			$query['company_id'] = absint($_GET['company']);			
		}				
		$_contacts = new USAM_Contacts_Query( $query );
		$this->items = $_contacts->get_results();
		$this->total_items = $_contacts->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );			
	}
}
?>