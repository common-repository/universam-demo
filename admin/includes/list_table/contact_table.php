<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Contacts_Table extends USAM_List_Table
{	
	protected $order = 'DESC';	
	public function extra_tablenav( $which ) { }	
	
	protected function get_filter_tablenav( ) 
	{	
		return ['interval' => ''];
	}
		
	function column_affairs( $item ) 
    {		
		$emails = usam_get_contact_emails( $item->id );			
		$phones = usam_get_contact_phones( $item->id );		
		$actions = [
			'add_meeting'    => '<a class="usam-add_meeting-link" href="'.$this->get_nonce_url( add_query_arg( array('action' => 'add_meeting', 'id' => $item->id), $this->url ) ).'">'.__('Встреча', 'usam').'</a>',			
			'add_task'   => '<a class="usam-add_task-link" href="'.$this->get_nonce_url( add_query_arg( array('action' => 'add_task', 'id' => $item->id), $this->url ) ).'">'.__('Задача', 'usam').'</a>',
			'add_message_chat' => "<a href='".admin_url("admin.php?page=feedback&tab=chat&contact_id=$item->id")."' >".__('Чат', 'usam')."</a>",
		];	
		if( current_user_can('view_communication_data') )
		{
			if ( !empty($emails) )
				$actions['send_message'] = "<a class='js-open-message-send' data-emails='".implode(',',$emails)."' data-name='$item->appeal'>".__('Сообщение', 'usam')."</a>";
			if ( !empty($phones) )
			{
				$actions['send_sms'] = "<a class='js-open-sms-send' data-phones='".implode(',',$phones)."' data-name='$item->appeal'>".__('SMS', 'usam')."</a>";
				$actions['add_phone'] = "<a class='js-communication-phone' data-phones='".implode(',',$phones)."' data-name='$item->appeal'>".__('Звонок', 'usam')."</a>";
			}
		}
		$this->row_actions_table( usam_get_form_customer_case( $item->id, 'contact' ), $actions );	
	}	
	
	function column_name( $item ) 
    { 
		$url = usam_get_contact_url( $item->id );	
		$name = "<div class='user_block'>";
		$name .= "<a href='$url' class ='image_container usam_foto'><img src='".esc_url( usam_get_contact_foto( $item->id ) )."' loading='lazy'></a>";
		$name .= "<div>";			
		$name .= '<a class="row-title js-object-value" href="'.$url.'">'.$item->appeal.'</a>';	
		$name .= "<div class='item_labels'>";
		ob_start();
		if ( $item->contact_source == 'employee' )
			usam_display_status( $item->status, 'employee' );
		else
			usam_display_status( $item->status, 'contact' );
		$name .= ob_get_clean();	
		$name .= "</div>";			
		$name .= "<div class='user_capability'>";		
		$name .= "</div>";
		$name .= "</div>";
		$name .= "</div>";		
		echo $name;
	}
	
	function contact_standart_actions( $item )
	{			
		$type = $item->contact_source == 'employee'?'employee':'contact';
		$actions = $this->standart_row_actions( $item->id, $type );
		if ( !current_user_can('delete_'.$type))
			unset($actions['delete']);
		if ( !current_user_can('edit_'.$type))
			unset($actions['edit']);
		return $actions;			
	}
	
	function column_contact( $item ) 
    {			
		$url = usam_get_contact_url( $item->id );	
		echo "<div class='user_block'>";
		echo "<a href='$url' class='image_container usam_foto'><img src='".esc_url( usam_get_contact_foto( $item->id ) )."' alt=''></a>";
		echo "<a href='$url' class='js-object-value'>".$item->appeal."</a>";
		echo "</div>";
	}	
	
	function column_location( $item ) 
    {			
		$location_id = usam_get_contact_metadata( $item->id, 'location' ); 	
		if ( $location_id )
		{
			$location = usam_get_location( $location_id );	
			if ( !empty($location) )
				echo $location['name'];
		}
	}	
	
	function column_visit( $item ) 
    {		
		echo (int)$item->count_visit;
	}
	
	function column_contact_source( $item ) 
    {		
		echo usam_get_name_contact_source( $item->contact_source );
	}
	
	function column_online( $item ) 
    {		
		if ( !empty($item->online) )
		{
			if ( strtotime($item->online) >= USAM_CONTACT_ONLINE )
				echo '<span class="item_status item_status_valid">'.__('Онлайн', 'usam').'</span>';
			else
				printf( __('Был %s', 'usam'), usam_local_date($item->online));
		}
	}	
	
	function column_last_order( $item ) 
    {		
		if ( $item->last_order_id )
		{ 
			echo usam_get_link_order( $item->last_order_id )."<br>";	
			echo usam_local_date( $item->last_order_date )."<br>";	
			echo usam_get_formatted_price( $item->last_order_sum, ['type_price' => $item->last_order_type_price]);
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
		
	function column_communication( $item ) 
	{
		$email = usam_get_contact_metadata( $item->id, 'email' );			
		if ( !empty($email) )
		{			
			echo "<div class = 'js-select-email select_communication'>".$email."</div>";
		}
	}	
	
	function column_communication_phone( $item ) 
	{ 
		$phone = usam_get_contact_metadata( $item->id, 'mobilephone' );	
		if ( !empty($phone) )
		{			
			echo "<div class = 'js_select_phone select_communication'>".$phone."</div>";
		}
	}
	
	function column_post( $item ) 
    {		
		echo htmlspecialchars(usam_get_contact_metadata($item->id, 'post'));
	}
	
	public function single_row( $item ) 
	{		
		echo '<tr id = "contact-'.$item->id.'" data-customer_id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	function column_status( $item )
	{
		if ( $item->contact_source == 'employee' )
			usam_display_status( $item->status, 'employee' );
		else
			usam_display_status( $item->status, 'contact' );
	}	
	
	function get_sortable_columns()
	{
		$sortable = [
			'name'        => array('name', false),			
			'status'      => array('status', false),		
			'affairs'     => array('affairs', false),	
			'date'        => array('date_insert', false),		
			'sum'         => array('total_purchased', false),		
			'online'      => array('online', false),				
		];
		return $sortable;
	}	
	
	function get_vars_query_filter()
	{	
		$selected = $this->get_filter_value( 'status' );
		if ( $selected )
			$this->query_vars['status'] = array_map('sanitize_title', (array)$selected);
		$selected = $this->get_filter_value( 'group' );
		if ( $selected )
			$this->query_vars['group'] = array_map('intval', (array)$selected);
		$selected = $this->get_filter_value( 'manager' );			
		if ( $selected )
			$this->query_vars['manager_id'] = array_map('intval', (array)$selected);		
		$selected = $this->get_filter_value( 'contacts_source' );	
		if ( $selected )
			$this->query_vars['source'] = array_map('sanitize_title', (array)$selected);
		
		$selected = $this->get_filter_value( 'accounts' );
		if ( $selected )
			$this->query_vars['accounts'] = (bool)$selected;	
		
		$selected = $this->get_filter_value( 'basket' );
		if ( $selected )
		{
			switch ( $selected ) 
			{		
				case '7day' :			
					$this->query_vars['abandoned_baskets'] = strtotime('-7 days');
				break;	
				case '14day' :				
					$this->query_vars['abandoned_baskets'] = strtotime('-14 days');
				break;	
				case '30day' :				
					$this->query_vars['abandoned_baskets'] = strtotime('-30 days');
				break;
				case '90day' :				
					$this->query_vars['abandoned_baskets'] = strtotime('-90 days');
				break;
				case '3day' :				
					$this->query_vars['abandoned_baskets'] = strtotime('-3 days');
				break;		
			}				
		}		
		$selected = $this->get_filter_value( 'gender' );
		if ( $selected )
		{
			$selected = array_map('sanitize_title', (array)$selected);		
			foreach( $selected as $sex )
				$this->query_vars['meta_query'][] = ['key' => 'sex', 'value' => $sex, 'compare' => '='];
		}
		$selected = $this->get_filter_value( 'age' );
		if ( $selected )
		{
			if ( !empty($selected) )
				$values = explode('|',$selected);
			if ( !empty($values[0]) )
			{
				$from_age = date('Y') - absint($values[0]);
				$this->query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $from_age.'-12-31', 'compare' => '<=', 'type' => 'DATE'];
			}
			if ( !empty($values[1]) )
			{
				$to_age = date('Y') - absint($values[1]);
				$this->query_vars['meta_query'][] = ['key' => 'birthday', 'value' => $to_age.'-01-01', 'compare' => '>=', 'type' => 'DATE'];
			}
		}		
		$selected = $this->get_filter_value( 'visit8' );
		if ( $selected )
		{
			$query_vars['date_query'][] = array( 'after' => date('Y-m-d H:i:s', $date_interval['from']), 'inclusive' => true );	
			$query_vars['date_query'][] = array( 'before' => date('Y-m-d H:i:s', $date_interval['to']), 'inclusive' => true );		
			
			
			if ( !empty($selected) )
				$values = explode('|',$selected);
			if ( !empty($values[0]) )
			{
				$from_age = date('Y') - absint($values[0]);
				$this->query_vars['date_query'][] = ['key' => 'birthday', 'value' => $from_age.'-12-31', 'compare' => '>=', 'type' => 'DATE'];
			}
			if ( !empty($values[1]) )
			{
				$to_age = date('Y') - absint($values[1]);
				$this->query_vars['date_query'][] = ['key' => 'birthday', 'value' => $to_age.'-1-1', 'compare' => '<=', 'type' => 'DATE'];
			}
		}		
		$selected = $this->get_filter_value( 'campaign' );
		if ( $selected )
			$this->query_vars['campaign'] = array_map('intval', (array)$selected);	
		$selected = $this->get_filter_value( 'storage_pickup' );
		if ( $selected )				
			$this->query_vars['storage_pickup'] = array_map('intval',(array)$selected);	
		$selected = $this->get_filter_value( 'code_price' );
		if ( $selected )
			$this->query_vars['meta_query'][] = ['key' => 'type_price', 'value' => (array)$selected, 'compare' => 'IN'];	
		$selected = $this->get_filter_value( 'company' );
		if ( $selected )
			$this->query_vars['company_id'] = array_map('intval', (array)$selected);		
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
		$this->get_date_interval_for_query(['last_order_date', 'online']);
		$this->get_digital_interval_for_query(['total_purchased', 'number_orders', 'bonus']);	
		$this->get_meta_for_query('contact');
	}
	
	function prepare_items() 
	{
		$this->get_query_vars();
		$this->query_vars['cache_case'] = true;		
		$this->query_vars['cache_meta'] = true;				
		$this->query_vars['cache_thumbnail'] = true;
		$this->query_vars['source__not_in'] = 'employee';							
		if ( empty($this->query_vars['include']) )
		{
			$this->query_vars['status__not_in'] = 'temporary';	
			$this->get_vars_query_filter();
		}	
		else
			$this->query_vars['status'] = 'all';	
		$_contacts = new USAM_Contacts_Query( $this->query_vars );
		$this->items = $_contacts->get_results();				
		$this->total_items = $_contacts->get_total();		
		$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
	}
}
?>