<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Users_Table extends USAM_List_Table
{	
	protected $order = 'DESC';			
	public function extra_tablenav( $which ) { }	
	
	function column_contact( $item ) 
    {
		echo "<div class='user_block'>";
		echo "<div class='user_foto image_container usam_foto'><img src='".usam_get_contact_foto( $item->id )."'></div>";
		echo '<div class="user_name">'.$item->appeal.'</div>';	
		echo "</div>";
	}
	
	function column_user_login( $item ) 
    {
		if ( $item->user_id )
		{
			$user = get_user_by('id', $item->user_id );
			echo $user->user_login;
		}
	}
	
	function no_items() 
	{
		_e('Пользователей в базе не найдено.', 'usam');
	}	
	
	function column_select( $item ) 
	{
		echo "<a href='' id='add_user' data-id='$item->user_id'>".__('Выбрать','usam')."</a>";
	}	
	
	function get_columns()
	{		
        $columns = array(           					
			'contact'        => __('Контакт', 'usam'),				
			'user_login'     => __('Логин', 'usam'),		
			'select'         => '',			
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
			'cache_users' => true,	
			'source' => 'all',
			'cache_thumbnail' => true			
		);		
		$query = new USAM_Contacts_Query( $query );
		$this->items = $query->get_results();		
		$this->total_items = $query->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}
?>