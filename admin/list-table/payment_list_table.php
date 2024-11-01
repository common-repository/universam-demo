<?php
require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/usam_all_documents_list_table.class.php' );
class USAM_List_Table_Payment extends USAM_Table_ALL_Documents 
{
	protected $orderby = 'id';
	protected $order   = 'desc'; 
	protected $status = 'all';	
	protected $all_in_work = false;	
	
	public function __construct( $args = [] ) 
	{		
		parent::__construct( $args );
		
		$this->statuses = usam_get_object_statuses(['type' => 'payment', 'code=>data', 'cache_results' => true]);
		$this->status = $this->get_filter_value( 'status', $this->status );				
	}	
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => '', 'status' => $this->status];
	}

	public function prepare_items() 
	{
		$this->get_query_vars();
		if ( $this->viewing_allowed('payment') )
		{
			$this->query_vars['cache_results'] = true;			
			if ( empty($this->query_vars['include']) )
			{			
				if ( $this->status != 'all' ) 					
					$this->query_vars['status'] = $this->status;							
				
				$selected = $this->get_filter_value( 'payment' );	
				if ( $selected )
					$this->query_vars['gateway'] = array_map('intval', (array)$selected);			
				
				$user = $this->get_filter_value( 'users' );	
				if ( $user )
				{
					if ( is_numeric($user) )			
						$this->query_vars['user_ID'] = absint($user);
					else								
						$this->query_vars['user_login'] = sanitize_title($user);				
				}	
				$this->get_digital_interval_for_query( array('sum' ) );
			}		
			$query = new USAM_Payments_Query( $this->query_vars );
			$this->items = $query->get_results();
			$this->total_amount = $query->get_total_amount();			
			if ( $this->per_page )
			{
				$total_items = $query->get_total();			
				$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
			}	
		}
	}	
	
	public function get_number_columns_sql()
	{		
		return array('document_id', 'status', 'sum' );
	}
	
	public function get_columns()
	{
		$columns = array(
			'cb'         => '<input type="checkbox" />',				
			'document_number' => __('Номер документа', 'usam'),
			'sum'        => __('Сумма', 'usam'),
			'status'     => __('Статус', 'usam'),			
			'date_payed' => __('Дата оплаты', 'usam'),	
			'name'       => __('Способ оплаты', 'usam'),					
			'date'       => __('Дата', 'usam'),				
		);
		if ( !current_user_can( 'edit_payment' ) )
			unset($columns['cb']);
			
		return $columns;	
	}

	public function get_sortable_columns() 
	{
		if ( ! $this->sortable )
			return array();
		
		return array(
			'date'       => 'id',
			'date_payed' => 'date_payed',
			'document_number' => 'document_number',				
			'transactid' => 'transactid',
			'status'     => 'status',
			'sum'        => 'sum',
		);
	}
		
	public function column_document_number( $item ) 
	{ 
		if ( current_user_can( 'edit_payment' ) )
			$this->row_actions_table( $this->item_view($item->id, $item->number, 'payment'), $this->standart_row_actions( $item->id, 'payment' ) );
		else
			echo $item->number;				
	}
	
	public function column_date_payed( $item ) 
	{		
		echo usam_local_date( $item->date_payed, __( get_option( 'date_format', 'Y/m/d' ) )." H:i" );
	}	
	
	public function column_date( $item ) 
	{
		$format = get_option( 'date_format', 'Y/m/d' )." H:i";
		$timestamp = strtotime( $item->date_insert );		
		$full_time = date_i18n( $format, $timestamp );
		$time_diff = time() - $timestamp;		
		if ( $time_diff > 0 && $time_diff < 86400 ) // 24 * 60 * 60
			$h_time = sprintf( __('%s назад' ), human_time_diff( $timestamp, time() ) );
		else
			$h_time = $full_time;

		echo '<abbr title="'.$full_time.'">' . $h_time . '</abbr>';
	}

	public function column_sum( $item ) 
	{
		echo usam_get_formatted_price( $item->sum );
	}
		
	public function column_status( $item ) 
	{					
		if ( $item->status == 3 )
			$class = 'item_status item_status_valid';
		elseif ( $item->status == 2 )
			$class = 'item_status item_status_notcomplete';
		elseif ( $item->status == 1 )
			$class = 'item_status status_blocked';
		else
			$class = 'item_status item_status_attention';
		echo "<span class='$class'>".usam_get_object_status_name( $item->status, 'payment' )."</span>";
	}
	
	public function get_bulk_actions_display() 
	{		
		$actions = array(
			'delete' => __('Удалить', 'usam'),
			'1'      => __('Не оплачено', 'usam'),
			'2'      => __('Отклонено', 'usam'),
			'3'      => __('Оплачено', 'usam')			
		);		
		return $actions;
	}	
}