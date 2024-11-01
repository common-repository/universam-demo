<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' ); 
require_once( USAM_FILE_PATH . '/includes/document/subscription_renewal_query.class.php' );
require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php'  );
class USAM_List_Table_subscription_renewal extends USAM_List_Table 
{	
	private $status_sum = array( );	
	protected $order = 'desc'; 		
	
	function get_bulk_actions_display() 
	{			
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
	function column_id( $item ) 
    {
		$this->row_actions_table( $item->id, $this->standart_row_actions($item->id, 'subscription_renewal') );	
    }
				
	function column_status( $item ) 
    {
		echo usam_get_status_name_renew_subscription( $item->status );		
    }
	
	function column_document( $item ) 
    {
		if ( $item->document_id )
		{
			$document = usam_get_document( $item->document_id );	
			echo "<a href='".esc_url( add_query_arg(['form' => 'view', 'form_name' => $document['type'], 'id' => $document['id']], $this->url ) )."'>".$document['name']."</a><br>№ ".$document['number'];
		}
    }
		
	public function single_row( $item ) 
	{		
		echo '<tr id = "subscription_renewal-'.$item->id.'" data-id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	 
	function get_sortable_columns()
	{
		$sortable = [
			'id'                => array('id', false),
			'status'            => array('status', false),
			'interval'          => array('date_insert', false),	
			'document'          => array('document_id', false),	
		];
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = [
			'cb'       => '<input type="checkbox" />',		
			'id'       => __('Номер', 'usam'),		
			'sum'      => __('Сумма', 'usam'),		
			'status'   => __('Статус', 'usam'),
			'document' => __('Документ оплаты', 'usam'),				
			'interval' => __('Время действия', 'usam'),					
        ]; 
        return $columns;
    }
	
	function prepare_items() 
	{				
		$this->get_query_vars();			
		$this->query_vars['subscription_id'] = $this->id;
		$this->query_vars['cache_documents'] = true;
		if ( empty($this->query_vars['include']) )
		{			
					
		} 
		$subscriptions = new USAM_Subscription_Renewal_Query( $this->query_vars );
		$this->items = $subscriptions->get_results();		
		$this->total_items = $subscriptions->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}	