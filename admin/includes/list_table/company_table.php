<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Companies_Table extends USAM_List_Table
{	
	protected $order = 'DESC';
	
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',			
			'name'           => __('Компания', 'usam'),		
			'status'         => __('Статус покупателя', 'usam'),				
			'affairs'        => __('Дела', 'usam')		
        );
		if ( current_user_can('sale') )
		{
			$columns['sum'] = __('Всего куплено', 'usam');
		}
        return $columns;
    }

	function company_standart_actions( $item )
	{
		$actions = $this->standart_row_actions( $item->id, 'company' );
		if ( !current_user_can('delete_company'))
			unset($actions['delete']);
		if ( !current_user_can('edit_company'))
			unset($actions['edit']);
		return $actions;			
	}	
		
	protected function get_filter_tablenav( ) 
	{		
		return ['interval' => ''];			
	}	
	
	function column_status( $item )
	{
		if ( $item->type == 'customer' )
			usam_display_status( $item->status, 'company' );
	}
	
	function column_user_login( $item ) 
    {
		if ( $item->user_id )
		{
			$user = get_user_by('id', $item->user_id );
			echo $user->user_login;
		}
	}
	
	function column_affairs( $item ) 
    {		
		$emails = usam_get_company_emails( $item->id );	
		$phones = usam_get_company_phones( $item->id );		
		$actions = [
			'add_meeting' => '<a class="usam-add_meeting-link" href="#">'.__('Встреча', 'usam').'</a>',
			'add_task' => '<a class="usam-add_task-link" href="#">'.__('Задача', 'usam').'</a>',		
		];	
		if( current_user_can('view_communication_data') )
		{
			if ( !empty($emails) )
				$actions['send_message'] = "<a class='js-open-message-send' data-emails='".implode(',',$emails)."' data-name='$item->name'>".__('Сообщение', 'usam')."</a>";
			if ( !empty($phones) )
			{
				$actions['send_sms'] = "<a class='js-open-sms-send' data-phones='".implode(',',$phones)."' data-name='$item->name'>".__('SMS', 'usam')."</a>";
				$actions['add_phone'] = "<a class='js-communication-phone' data-phones='".implode(',',$phones)."' data-name='$item->name'>".__('Звонок', 'usam')."</a>";
			}
		}
		$this->row_actions_table( usam_get_form_customer_case( $item->id, 'company' ), $actions );	
	}	
		
	function column_industry( $item ) 
    {
		echo usam_get_name_industry_company( $item->industry );
	}
	
	function column_type( $item ) 
    {
		echo usam_get_name_type_company( $item->type );
	}	

	function column_last_order( $item ) 
    {		
		if ( $item->last_order_id )
		{ 
			echo usam_get_link_order( $item->last_order_id )."<br>";	
			echo usam_local_date( $item->last_order_date )."<br>";	
			echo usam_get_formatted_price( $item->last_order_sum, array( 'type_price' => $item->last_order_type_price ) );
		}
	}
	
	function column_sum( $item ) 
    {		
		if ( $item->number_orders )
		{
			echo usam_currency_display($item->total_purchased )."<br>";
			echo __('Заказов', 'usam').' - '.usam_currency_display( $item->number_orders, ['decimal_point' => false] );
		}
	}
	
	public function single_row( $item ) 
	{		
		echo '<tr id = "company-'.$item->id.'" data-customer_id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	 
	function get_sortable_columns()
	{
		$sortable = array(
			'name'   => array('name', false),		
			'status' => array('status', false),					
			'date'   => array('date_insert', false),	
			'type'   => array('type', false),
			'industry' => array('industry', false),
			'group'  => array('group', false),			
			'sum'       => array('total_purchased', false),				
		);
		return $sortable;
	}
	
	function get_vars_query_filter()
	{
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected )
			$this->query_vars['manager_id'] = array_map('intval', (array)$selected);		
		$selected = $this->get_filter_value( 'accounts' );
		if ( $selected )
			$this->query_vars['accounts'] = (bool)$selected;		
		$selected = $this->get_filter_value( 'status' );
		if ( $selected )
			$this->query_vars['status'] = array_map('sanitize_title', (array)$selected);			
		$selected = $this->get_filter_value( 'industry' );
		if ( $selected )
			$this->query_vars['industry'] = array_map('sanitize_title', (array)$selected);			
		$selected = $this->get_filter_value( 'companies_types' );
		if ( $selected )
			$this->query_vars['type'] = array_map('sanitize_title', (array)$selected);			
		$selected = $this->get_filter_value( 'group' );
		if ( $selected )
			$this->query_vars['group'] = array_map('intval', (array)$selected);	
		$selected = $this->get_filter_value( 'status_subscriber' );
		if ( $selected )
			$this->query_vars['status_subscriber'] = array_map('sanitize_title', (array)$selected);
		$selected = $this->get_filter_value( 'mailing_lists' );
		if ( $selected )
		{
			$selected = array_map('intval', (array)$selected);			
			if ( array_search(0, $selected) === false )
				$this->query_vars['list_subscriber'] = $selected;	
			else
				$this->query_vars['not_subscriber'] = true;		
		}		
		$selected = $this->get_filter_value( 'code_price' );
		if ( $selected )
			$this->query_vars['meta_query'][] = array( 'key' => 'type_price', 'value' => (array)$selected, 'compare' => 'IN' );	
		$this->get_date_interval_for_query(['last_order_date']);
		$this->get_digital_interval_for_query(['total_purchased','number_orders']);
		$this->get_meta_for_query('company');	
	}	
}
?>