<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_carts extends USAM_List_Table 
{	
	private $status_sum = array( );	
	protected $order = 'desc'; 
	private $statuses;
	protected $status = 'all';
		
    function __construct( $args = array() )
	{	
		parent::__construct( $args );
		
		if ( !empty($_REQUEST['status']) )
			$this->status = $_REQUEST['status'];
		
		$this->statuses = usam_get_statuses_bonus_card();	
    }	
	
	function get_bulk_actions_display() 
	{			
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
	protected function get_filter_tablenav( ) 
	{		
		return array('interval' => '' );		
	}	
	
	function column_id( $item ) 
    {		
		$actions = array(); 
		if ( $this->current_action() != 'delete' )
			$actions['delete'] = '<a class="usam-delete-link" href="'.$this->get_nonce_url( add_query_arg(['action' => 'delete', 'cb' => $item->id], $this->url ) ).'">'.__('Удалить', 'usam').'</a>';
		$this->row_actions_table( $this->item_view($item->id, $item->id, 'cart'), $actions );	
    }
	
	function column_recalculation( $item ) 
    {
		echo usam_local_formatted_date( $item->recalculation_date );
    } 
	
	function column_totalprice( $item ) 
    {
		echo usam_get_formatted_price( $item->totalprice ); 
    } 
	
	public function storage_dropdown() 
	{		
		$selected = $this->get_filter_value( 'storage_pickup' );
		usam_get_storage_dropdown( $selected, ['name' => 'storage_pickup']);
	}
	
	public function pagination( $which )
	{
		ob_start();
		parent::pagination( $which );
		$output = ob_get_clean();
		
		$total_amount = ' - ' . sprintf( __('Всего: %s', 'usam'), usam_get_formatted_price( $this->total_amount ) );
		$total_amount = str_replace( '$', '\$', $total_amount );
		$output = preg_replace( '/(<span class="displaying-num">)([^<]+)(<\/span>)/', '${1}${2}'.' '.$total_amount.'${3}', $output );
		echo $output;
	}	
	 
	function get_sortable_columns()
	{
		$sortable = array(
			'customer'          => array('user_id', false),
			'id'                => array('id', false),
			'quantity'          => array('quantity', false),
			'totalprice'        => array('totalprice', false),
			'recalculation'     => array('recalculation_date', false),			
			'date'              => array('date_insert', false),		
			);
		return $sortable;
	}

	function get_columns()
	{
        $columns = array(  
			'cb'                => '<input type="checkbox">',			
			'id'                => __('Код', 'usam'),				
			'customer'          => __('Клиент', 'usam'),		
			'quantity'          => __('Товаров', 'usam'),	
			'totalprice'        => __('Стоимость', 'usam'),
			'recalculation'     => __('Изменение', 'usam'),	
			'date'              => __('Дата создания', 'usam'),			
        ); 
        return $columns;
    }
		
	function prepare_items() 
	{				
		require_once( USAM_FILE_PATH . '/includes/basket/users_basket_query.class.php' );
		$this->get_query_vars();	
		if ( empty($this->query_vars['include']) )
		{	
			$selected = $this->get_filter_value( 'payment' );
			if ( $selected )
				$this->query_vars['payment_methods'] = absint($selected);		

			$selected = $this->get_filter_value( 'shipping' );
			if ( $selected )
				$this->query_vars['shipping_methods'] = absint($selected);
				
			$selected = $this->get_filter_value( 'storage_pickup' );
			if ( $selected )
				$this->query_vars['storage_pickup'] = absint($selected);			
			
			$selected = $this->get_filter_value( 'user_id' );
			if ( $selected )		
			{	
				$this->query_vars['user_id'] = sanitize_title($selected);		
			}			
			$this->get_digital_interval_for_query(['sum', 'quantity', 'bonuses', 'taxes']);
			$this->get_string_for_query(['coupon']);			
			$this->get_date_interval_for_query(['recalculation_date']);
		}
		$query = new USAM_Users_Basket_Query( $this->query_vars );
		$this->items = $query->get_results();	
		$this->results_line['totalprice'] = $this->total_amount = $query->get_total_amount();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
		}
		foreach ( $this->items as $i => $item )
		{
			foreach ( $item as $key => $value )
			{
				if ( !isset($this->results_line[$key]) )
					$this->results_line[$key] = '';							
			}
		}	
	}
}