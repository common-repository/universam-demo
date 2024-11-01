<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php');
require_once( USAM_FILE_PATH . '/includes/customer/bonus_cards_query.class.php');
class USAM_Table_bonus_cards extends USAM_List_Table 
{	
	protected $pimary_id = 'code';
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
	
	protected function get_filter_tablenav( ) 
	{		
		return ['interval' => ''];			
	}
	
	function get_bulk_actions_display() 
	{			
		$actions = array(
			'delete'    => __('Удалить', 'usam'),
			'bulk_actions' => __('Добавить бонусы ', 'usam'),
		);
		return $actions;
	}
	
	public function get_views() 
	{
		global $wpdb;
		
		$url = remove_query_arg( array('post_status', 'paged', 'action2', 'm', 'paged', 's', 'orderby','order') );	
		if ( !empty($this->query_vars) )
		{
			$this->query_vars['fields'] = array('status', 'count');
			$this->query_vars['groupby'] = 'status';
			if( isset($this->query_vars['status']) )
				unset($this->query_vars['status']);
			if( isset($this->query_vars['paged']) )
				unset($this->query_vars['paged']);
			if( isset($this->query_vars['number']) )
				unset($this->query_vars['number']);	
			if( isset($this->query_vars['date_query']) )
				unset($this->query_vars['date_query']);				
			$results = usam_get_bonus_cards( $this->query_vars ); 
		}	
		else
			$results = array();
		$statuses = array();		
		$total_count = 0;	
		if ( !empty($results) )
		{			
			foreach ( $results as $status )
			{
				$statuses[$status->status] = $status->count;						
			}
			$total_count = array_sum( $statuses );
		} 
		$all_text = sprintf(_nx('Всего <span class="count">(%s)</span>', 'Всего <span class="count">(%s)</span>', $total_count, 'events', 'usam'), number_format_i18n($total_count) );
		$all_class = $this->status == 'all' && $this->search == '' ? 'class="current"' : '';			
		$href = add_query_arg( 'status', 'all', $url );
		$views = array(	'all' => sprintf('<a href="%s" %s>%s</a>', esc_url( $href ), $all_class, $all_text ), );				
	
		foreach ( $this->statuses as $key => $name )
		{			
			if ( empty($statuses[$key]) )
				continue;
			$text = $text = sprintf( $name.' <span class="count">(%s)</span>', number_format_i18n( $statuses[$key] )	);
			$href = add_query_arg( 'status', $key, $url );
			$class = $this->status == $key ? 'class="current"' : '';			
			$views[$key] = sprintf( '<a href="%s" %s>%s</a>', esc_url( $href ), $class, $text );			
		}
		return $views;
	}

	function column_code( $item ) 
    {		
		$this->row_actions_table( $this->item_view($item->code, $item->code, 'bonus_card'), $this->standart_row_actions( $item->code, 'bonus_card' ) );	
    }

	function column_sum( $item )
    {
		?><span class="<?php echo $item->sum>=0?'item_status_valid':'item_status_attention'; ?> item_status"><?php echo usam_currency_display( $item->sum ); ?></span><?php
    }	 
	
	function column_status( $item )
    {
		?><span class="<?php echo $item->status == 'active'?'item_status_valid':'status_blocked'; ?> item_status"><?php echo usam_get_bonus_card_status_name( $item->status ); ?></span><?php
    }
	
	function column_type( $item ) 
    {	
		echo usam_get_bonus_type($item->type); 
    }	
	 
	function column_default( $item, $column_name ) 
    {
		echo $item->$column_name; 
    }   
	
	public function single_row( $item ) 
	{		
		echo '<tr id = "bonus-'.$item->code.'" data-code = "'.$item->code.'">';
		$this->single_row_columns( $item );		
		echo '</tr>';
	}
	 
	function get_sortable_columns()
	{
		$sortable = array(
			'customer'          => array('user_id', false),
			'code'              => array('code', false),
			'sum'               => array('sum', false),
			'status'            => array('status', false),
			'percent'           => array('percent', false),
			'date'              => array('date_insert', false),			
			);
		return $sortable;
	}
	
	function prepare_items() 
	{				
		$this->get_query_vars();				
		
		if ( $this->status != 'all' )
			$this->query_vars['status'] = $this->status;			
					
		$selected = $this->get_filter_value( 'user_id' );
		if ( $selected )		
		{	
			$this->query_vars['user_id'] = sanitize_title($selected);		
		}		
		if ( empty($this->query_vars['include']) )
		{		
			$this->get_digital_interval_for_query( array('sum' ) );
		}
		$query = new USAM_Bonus_Cards_Query( $this->query_vars );
		$this->items = $query->get_results();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}			
	}
}