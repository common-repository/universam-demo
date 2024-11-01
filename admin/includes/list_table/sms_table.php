<?php
require_once( USAM_FILE_PATH .'/includes/feedback/sms_query.class.php'  );
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Table_SMS extends USAM_List_Table 
{
	protected $status = array( '0' => 'Прочитанные', '1' => 'Не прочитанные' );
	protected $folder = 'sent';
	function __construct( $args = array() )
	{	
		$this->folder = !empty($_REQUEST['f']) ? sanitize_title($_REQUEST['f']):'sent';	
		parent::__construct( $args );	
	}
	
	public function return_post()
	{
		return array( 'f' );
	}	
	
	public function extra_tablenav_display( $which ) 
	{
		static $pagination_args;
		if ( 'top' == $which ) 
		{	
			$pagination_args = $this->_pagination_args; 
			$this->_pagination_args = array(); 			
		}	
		else
		{
			$this->_pagination_args = $pagination_args;
		}
	}
	
	function column_folder( $item ) 
    {		
		if ( $item->folder == 'sent' )			
			echo '<span class="dashicons dashicons-undo"></span>';
		elseif ( $item->folder == 'inbox' )		
			echo '<span class="dashicons dashicons-redo"></span>';	
		elseif ( $item->folder == 'outbox' )		
			echo '<span class="outbox item_status item_status_attention" title ="'.__('Не отправлено', 'usam').'">!</span>';
	}
	
	function column_phone( $item ) 
    {				
		if ( $this->folder == 'drafts' ) 
			$actions = $this->standart_row_actions( $item->id, 'sms', ['send' => __('Отправить', 'usam')] );
		else
			$actions = array();	
		$this->row_actions_table($item->phone, $actions );	
	}
		
	function column_date( $item ) 
    {				
		if ( $item->folder == 'sent' )		
			echo usam_local_date( $item->sent_at, 'd.m.Y H:i' );		
		else
			echo usam_local_date( $item->date_insert, 'd.m.Y H:i' );
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
			'phone'      => array('phone', false),					
			'date'       => array('date', false),
			);
		return $sortable;
	}
		
	function get_columns()
	{		
		$columns = array(   
			'cb'            => '<input type="checkbox" />',				
			'folder'        => '',		
			'phone'         => __('Получатель', 'usam'),			
			'message'       => __('Сообщение', 'usam'),			
        );
		
		if ( $this->folder != 'outbox' )
			$columns['date'] = __('Дата', 'usam');		
	
        return $columns;
    }
	
	public function single_row( $item ) 
	{		
		global $email_id;	
		
		if ( $item->id == $email_id )
			$message_current = "message_current";
		else
			$message_current = "";
		
		echo "<tr data-id = '$item->id' class = '$message_current'>";
		$this->single_row_columns( $item );
		echo '</tr>';
	}
			
	function prepare_items() 
	{			
		global $email_id;
				
		$this->get_query_vars();
		$this->query_vars['cache_results'] = true;		
		$this->query_vars['folder'] = $this->folder;
		
		if ( $this->tab == 'companies' )
			$this->query_vars['phone'] = usam_get_company_phones( $this->id );
		else
			$this->query_vars['phone'] = usam_get_contact_phones( $this->id );			
		
		if ( empty($this->query_vars['phone']) )
			return false;
		
		if ( empty($this->query_vars['include']) )
		{				
				
		} 			
		$query = new USAM_SMS_Query( $this->query_vars );
		$this->items = $query->get_results();
		
		if ( !empty($_REQUEST['email_id']) )
			$email_id = absint($_REQUEST['email_id']);	
		elseif ( isset($this->items[0]) )		
			$email_id = $this->items[0]->id;
		
		if ( $this->per_page )
		{
			$this->total_items = $query->get_total();			
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}