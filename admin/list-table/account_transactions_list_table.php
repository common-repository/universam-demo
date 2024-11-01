<?php 
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/customer/account_transactions_query.class.php'  );
class USAM_List_Table_account_transactions extends USAM_List_Table 
{	
	private $status_sum = array( );	
	protected $order = 'desc'; 		

	protected function get_filter_tablenav( ) 
	{		
		return ['interval' => ''];		
	}			
	
	public function get_views() 
	{		
		$transaction = isset($_REQUEST['transaction'])?absint($_REQUEST['transaction']):'all';
		$url = remove_query_arg( array( 'paged', 'action', 'action2', 'm',  'paged', 's', 'orderby','order','transaction'), $this->url );	
		if ( !empty($this->query_vars) )	
		{
			$query_vars = $this->get_views_query_vars();
			if ( isset($query_vars['type_transaction']) )
				unset($query_vars['type_transaction']);
			
			$query_vars['groupby'] = 'type_transaction';
			$query_vars['fields'] = array('type_transaction', 'count');
			$results = usam_get_account_transactions( $query_vars );
		}
		else
			$results = array();
		
		$type_transactions = array();		
		$total_count = 0;	
		if ( !empty($results) )
		{			
			foreach ( $results as $status )
			{
				$type_transactions[$status->type_transaction] = $status->count;						
			}
			$total_count = array_sum( $type_transactions );
		} 
		$all_text = sprintf(_nx('Всего <span class="count">(%s)</span>', 'Всего <span class="count">(%s)</span>', $total_count, 'events', 'usam'), number_format_i18n($total_count) );
		$all_class = $transaction === 'all' && $this->search == '' ? 'class="current"' : '';	
		$views = array(	'all' => sprintf('<a href="%s" %s>%s</a>', esc_url( $url ), $all_class, $all_text ), );				
	
		foreach ( array( 0, 1) as $type_transaction )		
		{			
			if ( empty($type_transactions[$type_transaction]) )
				continue;
			$title = $type_transaction ? __("Списание","usam") : __("Зачисление","usam");
			$text = $text = sprintf( $title.' <span class="count">(%s)</span>', number_format_i18n( $type_transactions[$type_transaction] )	);
			$href = add_query_arg( 'transaction', $type_transaction, $url );
			$class = $transaction === $type_transaction ? 'class="current"' : '';			
			$views[$type_transaction] = sprintf( '<a href="%s" %s>%s</a>', esc_url( $href ), $class, $text );			
		}
		return $views;
	}		
		
	function column_id( $item ) 
    {
		$this->row_actions_table( $item->id, $this->standart_row_actions( $item->id ) );
    }
	
	function column_type_transaction( $item ) 
    {
		if ( $item->type_transaction )
			echo '<span class="item_status item_status_valid">'.__("Списание","usam").'</span>';
		else
			echo '<span class="item_status item_status_notcomplete">'.__("Зачисление","usam").'</span>';
    }	
		
	public function single_row( $item ) 
	{		
		echo '<tr id = "account-'.$item->id.'" data-id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	 
	function get_sortable_columns()
	{
		$sortable = array(
			'id'                => array('id', false),
			'type_transaction'  => array('type_transaction', false),
			'date'              => array('date_insert', false),	
			'sum'               => array('sum', false),			
		);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(  
			'id'               => __('Номер', 'usam'),	
			'type_transaction' => __('Тип транзакции', 'usam'),				
			'sum'              => __('Сумма', 'usam'),					
			'description'      => __('Основание', 'usam'),		
			'date'             => __('Дата', 'usam'),					
        ); 
        return $columns;
    }
	
	function prepare_items() 
	{				
		$this->get_query_vars();		
		if ( empty($this->query_vars['include']) )
		{				
			if ( isset($_REQUEST['transaction']) && $_REQUEST['transaction'] != 'all' )
				$this->query_vars['type_transaction'] = absint($_REQUEST['transaction']);		
		} 			
		$query = new USAM_Account_Transactions_Query( $this->query_vars );
		$this->items = $query->get_results();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}			
	}
}