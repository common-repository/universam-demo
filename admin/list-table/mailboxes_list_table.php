<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_mailboxes extends USAM_List_Table
{	
	protected $orderby = 'sort';
	protected $order   = 'ASC';
		
	public function __construct( $args = array() ) 
	{		
		parent::__construct( $args );
		USAM_Admin_Assets::sort_fields( 'mailboxes' );
    }	
	
	function get_bulk_actions_display() 
	{			
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}	
	
	function column_name( $item ) 
    {
		$actions = $this->standart_row_actions( $item->id, 'mailbox' );			
		$server = usam_get_mailbox_metadata( $item->id, 'pop3server' );		
		if ( $server == 'pop.yandex.ru' )
		{
			$url = usam_get_url_yandex_service( 'trusted-pdd-partner', 'https://mail.yandex.ru' );
			$actions['yandex'] = "<a href='$url'>".__('Яндекс почта', 'usam')."</a>";
		}
		$this->row_actions_table( $item->name, $actions );	
	}	

	function get_sortable_columns()
	{
		$sortable = array(
			'title'   => array('title', false),		
			'code'    => array('code', false),		
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'        => '<input type="checkbox" />',		
			'name'      => __('Имя', 'usam'),						
			'email'     => __('Почта', 'usam'),
			'drag'     => '&nbsp;',			
        );		
        return $columns;
    }	
	
	function prepare_items() 
	{	
		$this->get_query_vars();	
		$this->items = usam_get_mailboxes( $this->query_vars );
		if ( $this->per_page )
		{
			global $wpdb;
			$this->total_items = (int)$wpdb->get_var( 'SELECT FOUND_ROWS()' );			
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}
?>