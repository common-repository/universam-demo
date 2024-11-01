<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/crm/calls_query.class.php' );
class USAM_List_Table_telephony extends USAM_List_Table
{		
	protected $order = 'DESC';
		
	protected function get_filter_tablenav( ) 
	{		
		return array( 'interval' => '' );			
	}		
		
	public function get_views() {	}
	
	function column_phone( $item ) 
    {
		$actions = $this->standart_row_actions( $item->id );
		$actions['download'] = '<a href="'.add_query_arg( array('action' => 'download'), $this->item_url( $item->id ) ).'">'.__('Скачать', 'usam').'</a>';
		$this->row_actions_table( $item->phone, $actions );	
	}	
	
	function column_status( $item )
	{
		$statuses = usam_get_statuses_telephony();		
		echo isset($statuses[$item->status])?$statuses[$item->status]:'';
	}
	
	function column_type( $item ) 
    {
		$types = array( 'outgoing' => __('Исходящий','usam'), 'incoming' => __('Входящий','usam') );
		echo isset($types[$item->call_type])?$types[$item->call_type]:'';
	}
	
	function column_time( $item ) 
    {			
		$hours = floor($item->time / 3600);
		$minutes = floor($item->time / 60);
		$sec = $item->time % 60;
		printf('%02d:%02d:%02d', $hours, $minutes, $sec);
	}
	
	function column_price( $item ) 
    {		
		echo $item->price;
	}
		 
	function get_sortable_columns()
	{
		$sortable = array(
			'phone'    => array('phone', false),		
			'status'   => array('status', false),					
			'date'     => array('date_insert', false),	
			'type'     => array('call_type', false),
			'time'     => array('time', false),
			'price'    => array('price', false),		
			'manager'  => array('manager_id', false),		
			);
		return $sortable;
	}
	
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',			
			'phone'          => __('Номер телефона', 'usam'),		
			'type'           => __('Тип звонка', 'usam'),				
			'time'           => __('Время звонка', 'usam'),	
			'date'           => __('Дата вызова', 'usam'),	
			'status'         => __('Статус', 'usam'),
			'price'          => __('Стоимость', 'usam'),				
			'manager'        => __('Сотрудник', 'usam'),				
        );				
        return $columns;
    }
		
	function prepare_items() 
	{				
		$this->get_query_vars();			
				
		$subordinates = usam_get_subordinates( );
		if ( $subordinates ) 
			$this->query_vars['manager_id'] = $subordinates;	
		else
			$this->query_vars['manager_id'] = get_current_user_id();
	
		if ( empty($this->query_vars['include']) )
		{				
			$selected = $this->get_filter_value( 'status' );
			if ( $selected )
				$this->query_vars['status'] = array_map('sanitize_title', $selected);
		} 
		$calls = new USAM_Calls_Query( $this->query_vars );
		$this->items = $calls->get_results();		
		$this->total_items = $calls->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}
?>