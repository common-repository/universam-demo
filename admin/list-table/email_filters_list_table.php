<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_email_filters extends USAM_List_Table 
{	
	public $orderby = 'id';
	
	function __construct( $args = array() )
	{	
		parent::__construct( $args );
    }			

	function column_condition( $item ) 
    {
		$if = ['sender' => __('Адрес отправителя','usam'), 'recipient' => __('Адрес получателя','usam'), 'copy' => __('Копия','usam'), 'subject' => __('Тема','usam'), 'size' => __('Размер письма','usam')];		
		$text = $if[$item->if].' '.usam_get_name_condition( $item->condition ).' '.$item->value;
		$this->row_actions_table( $text, $this->standart_row_actions( $item->id, 'email_filter' ) );	
	}	
	
	function column_action( $item ) 
    {
		$actions = ['read' => __('Пометить письмо прочитанным','usam'), 'important' => __('Пометить письмо важным','usam'), 'delete' => __('Удалить на всегда','usam'), 'folder' => __('Переместить в папку','usam')];
		echo $actions[$item->action];
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
			'condition'    => array('condition', false),				
			'action'       => array('action', false),
			);
		return $sortable;
	}
		
	function get_columns()
	{	
		$columns = array(   
			'cb'          => '<input type="checkbox" />',	
			'condition'   => __('Условие', 'usam'),		
			'action'      => __('Действие', 'usam'),			
			'mailbox_id'  => __('Для ящика', 'usam'),			
        );		
        return $columns;
    }
			
	function prepare_items() 
	{					
		require_once( USAM_FILE_PATH . '/includes/mailings/email_filters_query.class.php' );
		
		$this->get_query_vars();
		$user_id = get_current_user_id();	
		$mailbox_ids = usam_get_mailboxes(['fields' => 'id', 'user_id' => $user_id]);	
		if ( empty($mailbox_ids) )
			return;
		$mailbox_ids[] = 0;
		$this->query_vars['mailbox_id'] = $mailbox_ids;	
		if ( empty($this->query_vars['include']) )
		{							
		
		}				
		$query_filters = new USAM_Email_Filters_Query( $this->query_vars );
		$this->items = $query_filters->get_results();	
		
		if ( $this->per_page )
		{
			$this->total_items = $query_filters->get_total();			
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}