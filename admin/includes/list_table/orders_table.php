<?php	
require_once( USAM_FILE_PATH .'/admin/includes/usam_all_documents_list_table.class.php' );
class USAM_Table_Orders extends USAM_Table_ALL_Documents 
{			
	protected $status = 'all_in_work';
	protected $order = 'desc';			

	public function __construct( $args = [] ) 
	{			
		parent::__construct( $args );				
		$this->statuses = usam_get_object_statuses(['type' => 'order', 'fields' => 'code=>data', 'cache_results' => true]);	
    }	
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => '', 'status' => $this->status];
	}
		
	// массовые действия Журнала продаж
	public function get_bulk_actions() 
	{
		if ( ! $this->bulk_actions )
			return [];

		$actions = [];		
		if ( current_user_can('edit_order') )
			$actions['bulk_actions'] = __('Открыть массовые действия', 'usam');		
		if ( current_user_can('delete_order') )			
			$actions['delete'] = __('Удалить', 'usam');		
		return $actions;
	}
	
	protected function bulk_actions( $which = '' ) {}
	
	public function get_vars_query_filter() 
	{			
		$selected = $this->get_filter_value( 'seller' );
		if ( $selected )
			$this->query_vars['bank_account_id'] = array_map('intval', (array)$selected);	
				
		$selected = $this->get_filter_value( 'paid' );
		if ( $selected )
			$this->query_vars['paid'] = array_map('intval', (array)$selected);
				
		if ( $this->status != 'all' ) 
		{			
			if ( $this->status == 'all_in_work' )
			{
				$this->query_vars['status'] = [];				
				foreach ( $this->statuses as $key => $status )	
				{
					if ( !$status->close )
						$this->query_vars['status'][] = $key;
				}
			}
			else			
				$this->query_vars['status'] = $this->status;
		}
		else
			$this->query_vars['status__not_in'] = ['', 'delete'];
		
		$selected = $this->get_filter_value( 'code_price' );
		if ( $selected )
			$this->query_vars['type_prices'] = array_map('sanitize_title', (array)$selected);		
		
		$selected = $this->get_filter_value( 'payer' );
		if ( $selected )
			$this->query_vars['type_payer'] = array_map('intval', (array)$selected);
			
		$selected = $this->get_filter_value( 'category' );
		if ( $selected ) 
			$this->query_vars['categories'] = array_map('intval', (array)$selected);
			
		$selected = $this->get_filter_value( 'brands' );
		if ( $selected ) 
			$this->query_vars['brands'] = array_map('intval', (array)$selected);					
		
		$selected = $this->get_filter_value( 'payment' );
		if ( $selected )
			$this->query_vars['payment_gateway'] = array_map('intval', (array)$selected);
		
		$selected = $this->get_filter_value( 'shipping' );
		if ( $selected )
			$this->query_vars['shipping_method'] = array_map('intval', (array)$selected);
		$selected = $this->get_filter_value( 'storage_pickup' );
		if ( $selected )
			$this->query_vars['storage_pickup'] = array_map('intval', (array)$selected);		
			
		$selected = $this->get_filter_value( 'document_discount' );
		if ( $selected )
			$this->query_vars['document_discount'] = array_map('intval', (array)$selected);		
	
		$selected = $this->get_filter_value( 'campaign' );
		if ( $selected )
		{
			$campaign = array_map('intval', (array)$selected);					
			$this->query_vars['meta_query'][] = ['key' => 'campaign_id', 'value' => $campaign, 'compare' => 'IN'];	
		}
		$selected = $this->get_filter_value( 'newsletter' );
		if ( $selected )
			$this->query_vars['newsletter'] = array_map('intval', (array)$selected);	
		$selected = $this->get_filter_value('group');
		if ( $selected )
			$this->query_vars['group'] = array_map('intval', (array)$selected);		
		$selected = $this->get_filter_value('contacts');
		if ( $selected )
			$this->query_vars['contacts'] = array_map('intval', (array)$selected);
		$selected = $this->get_filter_value('companies');
		if ( $selected )
			$this->query_vars['companies'] = array_map('intval', (array)$selected);	
		$selected = $this->get_filter_value( 'users' );
		if ( $selected )
		{
			if ( is_numeric($selected) )			
				$this->query_vars['user_ID'] = absint($selected);
			else								
				$this->query_vars['user_login'] = sanitize_title($selected);				
		}
		$this->get_digital_interval_for_query(['sum', 'prod', 'bonus', 'tax', 'shipping_sum']);
		$this->get_string_for_query(['coupon_name'], 'meta_query');
		$this->get_meta_for_query('order');
	}	
		
	public function prepare_items() 
	{
		$columns = $this->get_columns();
		$this->get_query_vars();	
		if ( $this->viewing_allowed('order') )
		{			
			$this->query_vars['cache_meta'] = true;
			$this->query_vars['cache_contacts'] = true;
			$this->query_vars['cache_companies'] = true;		
			$this->query_vars['cache_managers'] = true;
			$this->query_vars['cache_order_shippeds'] = true;
			$this->query_vars['cache_order_payments'] = true;
			$this->query_vars['add_fields'] = ['last_comment'];				
			if ( isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'companies' )
				$this->query_vars['companies'] = $this->id;		
			else
				$this->query_vars['contacts'] = $this->id;	
			$this->query_vars['meta_query'] = array();
			if ( empty($this->query_vars['include']) )
			{							
				$this->get_vars_query_filter();
			}				
	//Ограничения просмотра				
		/*	$view_group = usam_get_user_order_view_group( );
			if ( !empty($view_group) )
			{ 
				if ( !empty($view_group['type_prices']) )
					$this->query_vars['type_prices'] = $view_group['type_prices'];			
			}*/
			$query = new USAM_Orders_Query( $this->query_vars );	
			$this->items = $query->get_results();		
			$this->total_amount = $query->get_total_amount();		
			if ( $this->per_page )
			{
				$total_items = $query->get_total();	
				$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
			}
		}		
	}	
	
	public function get_sortable_columns() 
	{
		if ( ! $this->sortable )
			return array();
		return [
			'date_paid' => 'date_paid',
			'date'      => 'id',		
			'id'        => 'id',
			'status'    => 'status',
			'totalprice' => 'totalprice',	
			'manager'   => 'manager_id',						
		];
	}
	
	public function single_row( $item ) 
	{		
		$style = '';			
		if ( !empty($this->statuses[$item->status]) && !empty($this->statuses[$item->status]->color) )
		{
			$style = "style='border-left: 4px solid {$this->statuses[$item->status]->color};'";	
		}
		echo '<tr class ="row row-'.$item->status.'" id = "row-'.$item->id.'" '.$style.'>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	public function column_customer( $item )
	{
		if ( usam_is_type_payer_company( $item->type_payer ) )			
		{
			$text = usam_get_order_metadata( $item->id, 'company' );
			if ( is_numeric($text) )
			{
				$company = usam_get_company( $text );
				$text = isset($company['name'])?stripcslashes($company['name']):'';
			}
		}
		else
		{
			$firstname = usam_get_order_metadata( $item->id, 'billingfirstname' );
			$lastname = usam_get_order_metadata( $item->id, 'billinglastname' );
			$text = trim(stripcslashes($firstname." ".$lastname));
		}			
		$email = usam_get_order_customerdata( $item->id, 'email' );
		$phone = usam_get_order_customerdata( $item->id, 'mobile_phone' );
		?>
		<strong><?php echo '<a href="'.esc_url( admin_url( 'admin.php?page=orders&tab=orders&s='.$text )).'" title="'.esc_attr__('Найти заказы клиента', 'usam').'">'.$text.'</a>'; ?></strong><br>
		<?php
		if( current_user_can('view_communication_data') )
		{
			?>
			<div class="communication_icon">		
				<a class="dashicons dashicons-email-alt <?php echo ( $email ) ? 'active_icon':'' ?> js-open-message-send" data-emails="<?php echo $email ?>" data-type="order" data-id="<?php echo $item->id ?>" title="<?php _e("Отправить письмо","usam"); ?>"></a>
				<a class="dashicons dashicons-phone <?php echo ($phone)?'active_icon':'' ?> js-communication-phone" data-phone="<?php echo $phone ?>" data-type="order" data-id="<?php echo $item->id ?>" title="<?php _e("Позвонить","usam"); ?>"></a>			
				<a class="dashicons dashicons-email-alt2 <?php echo ($phone)?'active_icon':'' ?> js-open-sms-send" data-phones="<?php echo $phone ?> " data-type="order" data-id="<?php echo $item->id ?>" title="<?php _e("Отправить СМС","usam"); ?>"></a>
			</div>
			<?php
		}		
		
	}

	public function column_id( $item ) 
	{
		if ( $item->paid == 2)
			$title = '<a class="row-title item_status_valid item_status" href="'.esc_url( usam_get_url_order( $item->id ) ).'" title="'.__("Заказ полностью оплачен","usam").'">'.$item->number.'</a>';
		elseif ( $item->paid == 1)
			$title = '<a class="row-title item_status_notcomplete item_status" href="'.esc_url( usam_get_url_order( $item->id ) ).'" title="'.__("Заказ полностью оплачен","usam").'">'.$item->number.'</a>';
		else
			$title = '<a class="row-title" href="'.esc_url( usam_get_url_order( $item->id ) ).'" title="'.__("Не оплачен","usam").'">'.$item->number.'</a>';
		
		$title .= "<strong class='item_status status_blocked document_totalprice'>".usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price])."</strong> <small class='document_number_products'>".sprintf( _n('1 товар', '%s товаров', $item->number_products, 'usam'), number_format_i18n( $item->number_products ) ).'</small>';	
		$title .= '<div class="document_date">'.__("от","usam").' '.usam_local_formatted_date( $item->date_insert ).'</div>';
		
		if ( current_user_can( 'edit_order' ) )	
		{
			$actions = ['copy' => __('Копировать', 'usam')];						
			if ( $item->status == 'closed' )
				$actions['order_return'] = __('Возврат заказа', 'usam');
			$actions = $this->standart_row_actions( $item->id, 'order', $actions );
			if ( usam_check_object_is_completed( $item->status, 'order' ) && $item->status != 'delete' || !current_user_can( 'delete_order' ) )
				unset($actions['delete']);	
			$this->row_actions_table( $title, $actions );	
		}
		else
			echo $title;
	}
	
	public function column_date_paid( $item ) 
	{			
		if ( $item->paid == 2)
			echo '<span class="item_status_valid item_status" title="'.__("Заказ полностью оплачен","usam").'">'.usam_local_formatted_date($item->date_paid, get_option('date_format', 'Y/m/d')).'</span>';
		elseif ( $item->paid == 1)
			echo '<span class="item_status_notcomplete item_status">'.__("Частично оплачен","usam").'</span>';
		else
			echo '<span class="item_status_attention item_status">'.__("Не оплачен","usam").'</span>';
	}
	
	function column_date( $item )
    {					
		parent::column_date( $item );
		if ( !usam_check_object_is_completed( $item->status, 'order' ) )
		{ 
			$date = human_time_diff( time(), strtotime($item->date_insert) );	
			echo "<br><strong>".__('Обработка', 'usam').": $date</strong>";
		}	
	}

	public function column_totalprice( $item ) 
	{
		$text = usam_get_formatted_price( $item->totalprice, array('type_price' => $item->type_price) )."<br /><small>".sprintf( _n('1 товар', '%s товаров', $item->number_products, 'usam'), number_format_i18n( $item->number_products ) ).'</small>';		
		echo '<a class="row-title" href="'.esc_url( usam_get_url_order( $item->id ) ).'" title="'.esc_attr__('Посмотреть детали заказа', 'usam').'">'.$text.'</a>';
	}
	
	public function column_status( $item ) 
	{				
		if ( usam_check_object_is_completed( $item->status, 'order' ) || !current_user_can('edit_status_order') )
		{	
			echo '<div class="status_container">';
			usam_display_status( $item->status, 'order' );
			echo '</div>';
			if ( $item->status == 'canceled' )
			{
				$text = usam_get_order_metadata($item->id, 'cancellation_reason');
				if ( $text )
				{
					echo '<div class="cancellation_reason">';
					echo "<strong>".__('Причина', 'usam').":</strong>";
					echo "<p>$text</p>";
					echo '</div>';
				}
			}
		}
		else
		{			
			?>
			<div class="js-order-id" order_id="<?php echo $item->id; ?>">
				<select class="js-order-status">
				<?php
				$statuses = usam_get_object_statuses_by_type( 'order', $item->status );
				foreach ( $statuses as $status ) 
				{							
					$style = $status->color != ''?'style="color:#fff; background-color:'.$status->color.'"':'';
					echo '<option '.$style.' value="'.esc_attr( $status->internalname ).'" '.selected($status->internalname, $item->status, false) . '>'.esc_html( $status->name ). '</option>';
				}
				?>
				</select>			
				<div class="description_reason_cancellation hide">
					<textarea class='button js-reason-cancellation' placeholder="<?php _e('Напишите причину','usam'); ?>"></textarea>
					<div class='event_buttons'>
						<button type='submit' class='button button-primary js-canceled-order'><?php _e('Отменить заказ', 'usam'); ?></button>
						<button type='submit' class='button js-show-status-selection'><?php _e('Вернуть статусы', 'usam'); ?></button>
					</div>
				</div>	
			</div>
			<?php				
		}			
		if ( !empty($item->manager_id) )
		{
			$url = add_query_arg(['manager' => $item->manager_id, 'page' => $this->page, 'tab' => $this->tab], wp_get_referer() );	
			?><strong><?php _e('Ответственный','usam'); ?>:</strong> <a href="<?php echo $url; ?>"><?php echo stripcslashes(usam_get_manager_name( $item->manager_id )); ?></a><?php	
		}
		if ( !usam_check_object_is_completed( $item->status, 'order' ) )
		{ 
			$date = human_time_diff( time(), strtotime($item->date_insert) );	
			echo "<div>".__('Обработка', 'usam').": $date</div>";
		}
	}
	
	protected function column_notes( $item ) 
	{		
		echo $this->format_description( usam_get_order_metadata($item->id, 'note') );
	}
		
	public function column_shipping( $item )
	{			
		$documents = usam_get_shipping_documents_order( $item->id );
		$i = 0;		
		if ( usam_is_type_payer_company( $item->type_payer ) )	
			$address = usam_get_order_metadata( $item->id, 'company_shippingaddress' );
		else
			$address = usam_get_order_metadata( $item->id, 'shippingaddress' );
		$count = count($documents);
		foreach ( $documents as $document )
		{	
			$i++;
			if ( $i > 1 )
				echo '<hr size="1" width="90%">';
			$storage_pickup = '';
			if ( !empty($document->storage_pickup) )
			{	
				$storage = usam_get_store_field( $document->storage_pickup, 'title' );
				$storage_pickup = "<div class='address'><strong>".__("Выдача","usam").":</strong> <span class='address_text'>".stripcslashes($storage).'</span></div>';
			}		
			elseif ( $address )			
				$storage_pickup = '<div class="address"><strong>'.__("Адрес","usam").':</strong> <a class="address_text" target="_blank" rel="noopener" href="'.esc_url("https://maps.yandex.ru/?text={$address}").'">'.stripcslashes($address).'</a></div>';
								
			$date_delivery = usam_get_shipped_document_metadata( $document->id, 'date_delivery' );
			$date_delivery = $date_delivery?"<br><strong>".__("Доставка","usam").":</strong> ".usam_local_formatted_date( $date_delivery ):'';			
			echo $document->name?"<strong>".stripcslashes($document->name)."</strong>":"";
			echo "$storage_pickup<div>".usam_get_formatted_price($document->price, ['type_price' => $item->type_price])." ";
			usam_display_status( $document->status, 'shipped' );
			echo " $date_delivery</div>";			
			if ( $i == 1 && $count > $i )
			{
				echo '<hr size="1" width="90%">';
				$n = $count - $i;
				echo "<p>".sprintf( _n( 'Есть еще %s документ.','Есть еще %s документов.', $n, 'usam'), $n )."</p>";
				break;
			}
		}			
	}
	
	public function column_payment( $item )
	{					
		$documents = usam_get_payment_documents_order( $item->id );	
		$i = 0;
		$count = count($documents);
		foreach ( $documents as $document )
		{
			$i++;
			if ( $i > 1 )
				echo '<hr size="1" width="90%">';
			echo $document->name?"<strong>".stripcslashes($document->name)."</strong><br>":"";
			echo usam_get_formatted_price($document->sum, ['type_price' => $item->type_price])." "; 
			usam_display_status( $document->status, 'payment' );
			echo empty($document->date_payed)?'':' <span class="document_date">'.usam_local_formatted_date( $document->date_payed, get_option( 'date_format', 'Y/m/d' ) )."</span>";
			if ( $i == 1 && $count > $i )
			{
				echo '<hr size="1" width="90%">';
				$n = $count - $i;
				echo "<p>".sprintf( _n( 'Есть еще %s документ.','Есть еще %s документов.', $n, 'usam'), $n )."</p>";
				break;
			}
		}
	}
}