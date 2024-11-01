<?php
require_once( USAM_FILE_PATH . '/admin/includes/map_view.class.php' );
class USAM_orders_Map_View extends USAM_Map_View
{		
	protected $status = 'all_in_work';
	public function prepare_items( ) 
	{		
		$statuses = usam_get_object_statuses( array('type' => 'order', 'cache_results' => true) );				
		$this->query_vars = $this->get_query_vars();
		$this->query_vars['cache_meta'] = true;						
		if ( empty($this->query_vars['search']) )
		{		
			$selected = $this->get_filter_value( 'seller' );
			if ( $selected )
				$this->query_vars['bank_account_id'] = array_map('intval', (array)$selected);	
			
			$selected = $this->get_filter_value( 'manager' );
			if ( $selected ) 
				$this->query_vars['manager_id'] = array_map('intval', (array)$selected);

			$selected = $this->get_filter_value( 'paid' );
			if ( $selected )
				$this->query_vars['paid'] = array_map('intval', (array)$selected);
					
			if ( $this->status != 'all' ) 
			{			
				if ( $this->status == 'all_in_work' )
				{
					$this->query_vars['status'] = array();				
					foreach ( $statuses as $status )		
						if ( !$status->close )
							$this->query_vars['status'][] = $status->internalname;
				}
				else
				{				
					$this->query_vars['status'] = $this->status;
				}
			}
			else
				$this->query_vars['status__not_in'] = '';
			
			$selected = $this->get_filter_value( 'code_price' );
			if ( $selected )
				$this->query_vars['type_prices'] = array_map('sanitize_title', (array)$selected);		
			
			$selected = $this->get_filter_value( 'payer' );
			if ( $selected )
				$this->query_vars['type_payer'] = array_map('intval', (array)$selected);
			
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
			
			$selected = $this->get_filter_value( 'category' );
			if ( $selected ) 
				$this->query_vars['categories'] = array_map('intval', (array)$selected);
				
			$selected = $this->get_filter_value( 'brands' );
			if ( $selected ) 
				$this->query_vars['brands'] = array_map('intval', (array)$selected);				
			$selected = $this->get_filter_value( 'campaign' );
			if ( $selected )
			{
				$campaign = array_map('intval', (array)$selected);					
				$this->query_vars['meta_query'][] = ['key' => 'campaign_id', 'value' => $campaign, 'compare' => 'IN'];	
			}
			$selected = $this->get_filter_value( 'newsletter' );
			if ( $selected )
				$this->query_vars['newsletter'] = array_map('intval', (array)$selected);
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
			$this->get_digital_interval_for_query(['sum', 'prod', 'coupon_v', 'bonus', 'tax', 'shipping_sum']);
			$this->get_string_for_query(['coupon_name'], 'meta_query');
		}				
		$view_group = usam_get_user_order_view_group( );
		if ( !empty($view_group) )
		{ 
			if ( !empty($view_group['type_prices']) )
				$this->query_vars['type_prices'] = $view_group['type_prices'];			
		}
		$this->get_meta_for_query('order');
		$points = [];
		$orders = usam_get_orders( $this->query_vars );
		if ( empty($orders) )
			return $points;		
		
		foreach ( $orders as $order ) 
		{
			$order_url = usam_get_url_order( $order->id );
			$latitude = (string)usam_get_order_metadata( $order->id, 'latitude' );
			$longitude = (string)usam_get_order_metadata( $order->id, 'longitude' );			
			$property_types = usam_get_order_property_types( $order->id );				
			$address = !empty($property_types['delivery_address'])?$property_types['delivery_address']['_name']:'';	
			$contact = !empty($property_types['delivery_contact'])?$property_types['delivery_contact']['_name']:'';				
						
			$points[] = array( 'id' => $order->id, 'title' => '№'.$order->id.' '.usam_get_formatted_price($order->totalprice, array('type_price' => $order->type_price)), 'description' => "<div class='map__pointer'><div class='map__pointer_text'><div class='map__pointer_name'><a href='$order_url'><span>№ $order->id</span><span>".usam_get_formatted_price($order->totalprice, array('type_price' => $order->type_price))."</span></a></div><div class='map__pointer_notes'>".esc_html( usam_limit_words(usam_get_order_metadata($order->id, 'note')))."</div><div class='map__pointer_date'>".usam_local_date($order->date_insert)."</div><div class='map__pointer_row'>$contact</div><div class='map__pointer_address'>$address</div></div></div>", 'latitude' => $latitude, 'longitude' => $longitude );	
		}		
		return $points;
	}
}
?>