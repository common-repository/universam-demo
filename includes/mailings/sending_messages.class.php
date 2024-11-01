<?php
// Класс отвечает за отправку рассылки
new USAM_Sending_Messages( );
final class USAM_Sending_Messages
{
	public  $message = array();
	public  $errors  = array();
	
	function __construct( )
	{					
		add_action( 'usam_ten_minutes_cron_task', array( $this, 'send_newsletters') ); 	
		add_action( 'usam_hourly_cron_task', array( $this, 'send_trigger_cron') ); 			
		add_action( 'usam_order_is_collected', array( $this, 'send_trigger_order_is_collected')); 				
		add_action( 'usam_order_paid', array( $this, 'send_trigger_order_paid') ); 	
		add_action( 'wp_login', array( $this, 'send_trigger_site_login'), 10, 2 ); 
		add_action( 'usam_update_order_status', array( $this, 'send_trigger_order_status_change'), 10, 4 ); 
		add_action( 'usam_event_status_changed', array( $this, 'send_trigger_event_status_changed'), 10, 4 ); 		
		add_action( 'usam_subscribe_for_newsletter', array( $this, 'send_trigger_subscribe_to_newsletter') ); 	
		add_action( 'usam_ubscribed_newsletter', array( $this, 'send_trigger_ubscribed_newsletter'), 10, 2 );
		add_action( 'usam_receiving_request_webform', array( $this, 'send_trigger_webform'), 10, 4 );
		add_action( 'usam_new_letter_received', [$this, 'send_trigger_response_letter'], 10, 2 );
		add_action( 'usam_product_arrived', [$this, 'send_trigger_product_arrived'], 10, 2 );		
		add_action( 'usam_update_document_shipped_track_id', [$this, 'customer_notification_tracking'], 10, 4 );			
	}
	
	public function send_newsletters( )
	{	
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );		
		
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'simple', 'date_query' => [['column' => 'start_date', 'before' => date("Y-m-d H:i:s"), 'inclusive' => true]], 'cache_results' => true]);	
		foreach ( $newsletters as $newsletter ) 
		{			
			$_newsletter = new USAM_Send_Newsletter( $newsletter->id );		
			$result = $_newsletter->send_newsletter();
		}		
	}	
		
// ТРИГГЕРНАЯ РАССЫЛКА
	public function send_trigger_cron( )
	{			
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		require_once( USAM_FILE_PATH . '/includes/basket/users_basket_query.class.php' );
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger', 'meta_query' => [['key' => 'event_start', 'value' => ['adding_newsletter','basket_forgotten','order_status','sale_dont_buy','discount_favorites','subscription_end','available_bonuses']], 'compare' => 'IN']]);
		foreach( $newsletters as $newsletter ) 
		{		
			$event_start = usam_get_newsletter_metadata( $newsletter->id, 'event_start' );
			$callback = 'send_trigger_'.$event_start;				
			if ( method_exists($this, $callback) ) 
			{ 	
				$result = $this->$callback( (array)$newsletter );
				if ( $result )
					usam_update_newsletter($newsletter->id, ['number_sent' => $result]);
			}
		}		
	}		
	
	public function send_trigger_adding_newsletter( $newsletter )
	{
			
	}
	
	private function get_product_template( $product_id, $newsletter )
	{			
		global $post;		
		$post = get_post( $product_id );
		$product_html = '<div id="product_block">'.usam_get_email_template( $newsletter['template'], 'product' ).'</div>';
		return $product_html;
	}	
	
	public function get_products_html_table( $product_ids, $newsletter )
	{	
		global $post;
		if ( empty($product_ids) )
			return '';
		$out = '<table class="block_grid_products" style="position: relative;width:100%;table-layout: fixed;"><tr>';
		$i = 0;
		foreach ( $product_ids as $product_id ) 
		{			
			if ( $i == 3 )
			{
				$out .= "</tr><tr>";
				$i = 0;
			}			
			$i++;
			$out .= "<td><div class='cel_container product_container'>".$this->get_product_template( $product_id, $newsletter )."</td>";			
		}
		$out .= '</tr></table>';
		return $out;
	}	
	
	private function order_shortcode( $order_id )
	{
		$order_shortcode = new USAM_Order_Shortcode( $order_id );
		return $order_shortcode->get_html_args();	
	}
	
	private function product_shortcode( $product_id )
	{
		$post = get_post( $product_id );
		$args = [
			'product_title' => $post->post_title, 
			'product_content' => $post->post_content,  
			'product_excerpt' => $post->post_excerpt, 
			'product_sku' => usam_get_product_meta( $product_id, 'sku' ),  			
			'product_thumbnail' =>  usam_get_product_thumbnail( $product_id, 'product-thumbnails', $post->post_title ), 				
			'product_price' => usam_get_product_price( $product_id ), 
			'product_old_price' => usam_get_product_old_price( $product_id ), 
			'product_url' => usam_product_url( $product_id ), 
		];	
		return $args;
	}
	
		// проверить время отправления
	private function check_start_time( $newsletter )
	{
		$h = date_i18n('H');
		$i = date('i');
		$conditions = usam_get_newsletter_metadata( $newsletter['id'], 'conditions' );
		$time_start = !empty($conditions['time_start'])?$conditions['time_start']:"10:00";
		$time_start = explode(":", $time_start);
		if( $time_start[0] == $h && ( ( $time_start[1] == '00' && $i >= '00' && $i < '30' ) || ( $time_start[1] == '30' && $i >= '30' && $i <= '59')) )		
			return true;
		else
			return false;
	}
	
	public function send_trigger_discount_favorites( $newsletter )
	{
		$type_price = usam_get_customer_price_code();
		
		$number_sent = 0;
		require_once(USAM_FILE_PATH.'/includes/customer/user_posts_query.class.php');
		$user_products = new USAM_User_Posts_Query( array('user_list' => 'desired', 'user_id__not_in' => 0) );	
		$results = $user_products->get_results();
		$product_ids = array();		
		$favorites_products = array();		
		foreach ( $results as $result ) 
		{
			$product_ids[] = $result->product_id;		
			$favorites_products[$result->user_id][] = $result->product_id;
		}
		$args = ['fields' => 'ids', 'post__in' => $product_id, 'in_stock' => true, 'price_meta_query' => [['key' => 'old_price_'.$type_price, 'value' => 0, 'compare' => '>'], ['key' => 'price_'.$type_price, 'value' => '0',	'compare' => '!=']]];				
		$product_ids = usam_get_products( $args, true );	
		if ( empty($product_ids) )
			return $number_sent;
						
		$user_ids = array();	
		$favorites_discont_products = array();			
		foreach ( $favorites_products as $user_id => $ids ) 
		{
			$array = array_intersect($ids, $product_ids);
			if ( !empty($array) )
			{
				$user_ids[] = $user_id;				
				$favorites_discont_products[$user_id] = $array;				
			}
		}		
		unset($favorites_products);
		if ( empty($user_ids) )
			return $number_sent;	
		$users = get_users(['include' => $user_ids]);	
		foreach ( $users as $user ) 
		{				
			$args = [
				'products' => $this->get_products_html_table( $favorites_discont_products[$user->ID], $newsletter )
			];
			$_newsletter = new USAM_Send_Newsletter( $newsletter['id'], $args );
			if ( $_newsletter->send_email_trigger( $user->user_email ) )						
				$number_sent ++;			
		}
		return $number_sent;	
	}		
	
	// Статус заказа
	public function send_trigger_order_status( $newsletter )
	{			
		$number_sent = 0;
		if( $this->check_start_time( $newsletter ) )
		{ 					
			$conditions = usam_get_newsletter_metadata( $newsletter['id'], 'conditions' );
			$order_ids = usam_get_orders(['fields' => 'id',	'status' => $conditions['status']]);
			if ( empty($order_ids) )
				return 0;
			
			$emails = usam_get_order_props_value(['order_id' => $order_ids, 'meta_key' => 'billingemail']);				
			$number_sent = $newsletter['number_sent'];				
			if ( !empty($emails) )
			{								
				foreach( $emails as $email ) 
				{						
					$_newsletter = new USAM_Send_Newsletter( $newsletter['id'], $this->order_shortcode( $email->order_id ) );
					if ( $_newsletter->send_email_trigger( $email->value ) )						
						$number_sent ++;
				}
			}
		}
		return $number_sent;
	}
	
	public function send_trigger_available_bonuses( $newsletter )
	{
		$number_sent = 0;
		if( $this->check_start_time( $newsletter ) )			
		{ 
			$conditions = usam_get_newsletter_metadata( $newsletter['id'], 'conditions' );
			require_once( USAM_FILE_PATH . '/includes/customer/bonus_cards_query.class.php' );
			$cards = usam_get_bonus_cards(['fields' => 'user_id=>data', 'number' => 1000, 'status' => 'active', 'conditions' => ['key' => 'sum', 'compare' => '>=', 'value' => $conditions['sum']]]);		
			if( $cards )
			{					
				global $wpdb;		
				$items = $wpdb->get_results("SELECT cm.meta_value AS email, c.* FROM ".USAM_TABLE_CONTACT_META." AS cm 
				LEFT JOIN ".USAM_TABLE_CONTACTS." AS c ON (c.id=cm.contact_id AND cm.meta_key='email' AND cm.meta_value!='') 
				LEFT JOIN ".USAM_TABLE_NEWSLETTER_USER_STAT." AS nus ON (nus.communication=cm.meta_value AND (nus.status=1 OR nus.status=2) AND nus.newsletter_id='".$newsletter['id']."' AND nus.sent_at>='".date('Y-m-d H:i:s', strtotime('-'.$conditions['days'].' days'))."')
				WHERE c.user_id IN (".implode(",", array_keys($cards)).")");			
				if( !empty($items) )
				{							
					foreach( $items as $contact ) 
					{									
						$args = ['bonuses_sum' => $cards[$contact->user_id]->sum, 'card_code' => $cards[$contact->user_id]->code, 'card_percent' => $cards[$contact->user_id]->percent];
						$_newsletter = new USAM_Send_Newsletter( $newsletter['id'], $args, (array)$contact );
						if ( $_newsletter->send_email_trigger( $contact->email ) )						
							$number_sent ++;							
					}				
				}
			}
		}	
		return $number_sent;		
	}
		
	// Приближение окончания подписки
	public function send_trigger_subscription_end( $newsletter )
	{
		$number_sent = 0;
		if( $this->check_start_time( $newsletter ) )			
		{ 
			$conditions = usam_get_newsletter_metadata( $newsletter['id'], 'conditions' );		
			require_once( USAM_FILE_PATH . '/includes/document/subscriptions_query.class.php' );
			$query_vars = ['date_query' => [['before' => date('Y-m-d H:i:s', strtotime('+'.$conditions['days'].' days')), 'inclusive' => true], 'column' => 'end_date'], 'number' => 1000, 'status' => 'signed', 'count_total' => false, 'cache_results' => false, 'cache_products' => false];			
			$query = new USAM_Subscriptions_Query();	
			$query->prepare_query( $query_vars );		
			$query->query();			
			$results = $query->get_results( );
		
			if ( empty($results) )
				return $number_sent;
			
			$emails = array();
			foreach ( $results as $result ) 
			{
				if ( $result->customer_id )
				{
					if ( $result->customer_type == 'contact' )
						$emails[] = usam_get_contact_metadata( $result->customer_id, 'email' );
					else
						$emails[] = usam_get_company_metadata( $result->customer_id, 'email' );					
				}
			}			
			$number_sent = $newsletter['number_sent'];			
			if ( !empty($emails) )
			{							
				foreach ( $emails as $email ) 
				{									
					$_newsletter = new USAM_Send_Newsletter( $newsletter['id'] );
					if ( $_newsletter->send_email_trigger( $email ) )						
						$number_sent ++;							
				}				
			}				
		}
		return $number_sent;
	}
		
	// Забытая корзина
	public function send_trigger_basket_forgotten( $newsletter )
	{ 		
		$number_sent = 0;
		if( $this->check_start_time( $newsletter ) )	
		{ 
			$conditions = usam_get_newsletter_metadata( $newsletter['id'], 'conditions' );
			$basket_id = (int)usam_get_newsletter_metadata( $newsletter['id'], 'trigger_basket_id' );
			$query = [ 				
				'date_query' => [['before' => $conditions['days_basket_forgotten'].' days ago', 'inclusive' => true]],
				'number' => 1000, 'count_total' => false, 'cache_results' => false,	'cache_products' => false, 'conditions' => [['key' => 'totalprice', 'value' => 0, 'compare' => '>']], 'fields' => ['user_id', 'contact_id', 'id'],
			];		
			if ( !$basket_id )	
			{
				if ( empty($conditions['run_for_old_data']) )			
				{
					$day = $conditions['days_basket_forgotten']+1;
					$query['date_query'][] = ['after' => "$day days ago",'inclusive' => true];	
				}
			}	
			$orders = new USAM_Users_Basket_Query();	
			$orders->prepare_query( $query );		
			$orders->query_where .= " AND id>'".$basket_id."'";		
			$orders->query();			
			$results = $orders->get_results( );
			
			if ( empty($results) )
				return $number_sent;
			
			$ids = [];
			foreach ( $results as $result ) 
			{
				if ( $result->contact_id )
				{
					$ids[] = $result->contact_id;
					$basket_id = $result->id;
				}
			}
			if ( !$ids )
				return $number_sent;
			
			$contacts = usam_get_contacts(['include' => $ids, 'source' => 'all', 'status' => 'all', 'cache_meta' => true, 'cache_results' => true]);								
			$number_sent = $newsletter['number_sent'];			
			if ( !empty($contacts) )
			{
				foreach ( $contacts as $contact ) 
				{									
					$email = usam_get_contact_metadata( $contact->id, 'email' );
					$_newsletter = new USAM_Send_Newsletter( $newsletter['id'], [], (array)$contact );
					if ( $_newsletter->send_email_trigger( $email ) )						
						$number_sent ++;
				}				
			}			
			usam_update_newsletter_metadata( $newsletter['id'], 'trigger_basket_id', $basket_id );
		}
		return $number_sent;
	}
	
// Давно не покупал
	public function send_trigger_sale_dont_buy( $newsletter )
	{
		$number_sent = 0;
		$h = date_i18n('H');
		$i = date('i');
		$conditions = usam_get_newsletter_metadata( $newsletter['id'], 'conditions' );
		$time_start = !empty($conditions['time_start'])?$conditions['time_start']:"10:00";
		$time_start = explode(":", $time_start);
		if( $time_start[0] == $h && ( ( $time_start[1] == '00' && $i >= '00' && $i < '30' ) || ( $time_start[1] == '30' && $i >= '30' && $i <= '59')) )				
		{ 
			$query = array( 						
				'date_query' => [['before' => $conditions['days_dont_buy'].' days ago', 'inclusive' => true]],
				'order' => 'DESC',					
				'fields' => 'id',			
				'status' => 'closed',		
				'count_total' => false,					
			);						
			$order_id = (int)usam_get_newsletter_metadata( $newsletter['id'], 'trigger_order_id' );			
			if ( !$order_id )	
			{
				if ( empty($conditions['run_for_old_data']) )			
				{
					$day = $conditions['days_dont_buy']+1;
					$query['date_query'][] = array( 'after' => "$day days ago",'inclusive' => true, );	
				}
			}				
			$orders = new USAM_Orders_Query();	
			$orders->prepare_query( $query );		
			$orders->query_where .= " AND id>'".$order_id."'";		
			$orders->query();			
			$order_ids = $orders->get_results( );
		
			if ( empty($order_ids) )
				return $number_sent;						
	
			$order_id = end($order_ids);	
			$emails1 = usam_get_order_props_value(['order_id' => $order_ids, 'meta_key' => 'billingemail']);
			
			if ( empty($emails1) )
				return $number_sent;	
			
			$day = $conditions['days_dont_buy']-1;
			$query = array( 'date_query' => array( 'after' => $day.' days ago' ), 'fields' => 'id', 'status' => 'closed' );		
			$orders = new USAM_Orders_Query( $query );	
			$order_ids = $orders->get_results();					
			$emails = array();
			if ( !empty($order_ids) )
			{
				$emails2 = usam_get_order_props_value(['fields' => 'value', 'order_id' => $order_ids, 'meta_key' => 'billingemail']);	
				if ( !empty($emails2) )
				{
					foreach ( $emails1 as $email ) 
					{
						if ( !in_array($email->value, $emails2) )
							$emails[$email->value] = $email->order_id;
					}
				}		
				unset($emails2);
			}
			else
			{
				foreach ( $emails1 as $email ) 
				{
					$emails[$email->value] = $email->order_id;
				}
			}			
			unset($emails1);				
			$number_sent = $newsletter['number_sent'];			
			if ( !empty($emails) )
			{						
				foreach ( $emails as $email => $id ) 
				{							
					$_newsletter = new USAM_Send_Newsletter( $newsletter['id'], $this->order_shortcode( $id ) );
					if ( $_newsletter->send_email_trigger( $email ) )
						$number_sent ++;
				}				
			}			
			usam_update_newsletter_metadata( $newsletter['id'], 'trigger_order_id', $order_id );
		}
		return $number_sent;
	}
		
//Новому подписчику		
	public function send_trigger_subscribe_to_newsletter( $email )
	{ 		
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger', 'meta_query' => [['key' => 'event_start', 'value' => 'subscribe_to_newsletter', 'compare' => '=']]]);		
		if ( empty($newsletters) )
			return false;

		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		foreach ( $newsletters as $newsletter ) 
		{						
			$_newsletter = new USAM_Send_Newsletter( $newsletter->id );		
			$_newsletter->send_email_trigger( $email );	
		}					
	}
	
//Новому подписчику		
	public function send_trigger_ubscribed_newsletter( $email, $list_id )
	{ 			
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger','meta_query' => [['key' => 'event_start', 'value' => 'ubscribed_newsletter', 'compare' => '=']]]);
		if ( empty($newsletters) )
			return false;	
		
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		foreach ( $newsletters as $newsletter ) 
		{	
			$conditions = usam_get_newsletter_metadata( $newsletter->id, 'conditions' );
			if ( in_array($list_id, $conditions['lists']) )
			{
				$_newsletter = new USAM_Send_Newsletter( $newsletter->id );
				$_newsletter->send_email_trigger( $email );	
			}
		}					
	}
	
	//Отправил запрос через веб-форму		
	public function send_trigger_webform( $webform, $event_id, $webform_data, $properties )
	{ 				
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );		
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger','meta_query' => [['key' => 'event_start', 'value' => 'webform', 'compare' => '=']]]);	
		if ( empty($newsletters) )
			return false;
		
		$email = '';
		foreach ( $properties as $code => $property ) 
		{			
			if ( $property->field_type == 'email' && is_email($webform_data[$code]) )
				$email = $webform_data[$code];
			elseif ( $property->field_type == 'file' || $property->field_type == 'files' )
				continue;
			$args['webform_'.$code] = usam_get_formatted_property( $webform_data[$code], $property );
		}	
		if ( empty($email) )
			return false;		
		require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
		$contacting = usam_get_contacting( $event_id );	
		if ( !$contacting )
			return false;
	
		$args['event_id'] = $event_id;
		$args['event_date'] = usam_local_date( $contacting['date_insert'], "d.m.Y" );
		$args['event_status'] = usam_get_object_status_name( $contacting['status'], 'contacting' );	
		
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );			
		foreach ( $newsletters as $newsletter ) 
		{	
			$conditions = usam_get_newsletter_metadata( $newsletter->id, 'conditions' );	
			if ( !empty($conditions['webform']) && in_array($webform['id'], (array)$conditions['webform'] ) )
			{
				$_newsletter = new USAM_Send_Newsletter( $newsletter->id, $args );		
				$_newsletter->send_email_trigger( $email );	
			}
		}					
	}		

// Изменение статуса обращения	
	public function send_trigger_event_status_changed( $current_status, $previous_status, $data, $t )
	{		
		if ( $data['type'] != 'contacting' )
			return false;
						
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger','meta_query' => [['key' => 'event_start', 'value' => 'appeal_change', 'compare' => '=']]]);	
		if ( empty($newsletters) )
			return false;
	
		$properties = usam_get_properties(['type' => 'webform', 'fields' => 'code=>data']);			
		$email = '';
		foreach( $properties as $code => $property ) 
		{						
			$metadata = usam_get_event_metadata($data['id'], 'webform_'.$property->code);
			if ( $property->field_type == 'email' && is_email($metadata) )
				$email = $metadata;
			elseif ( $property->field_type == 'file' || $property->field_type == 'files' )
				continue;
			$args['webform_'.$code] = usam_get_formatted_property( $metadata, $property );
		}		
		if ( empty($email) )
			return false;	
			
		$args['event_id'] = $data['id'];
		$args['event_date'] = usam_local_date( $data['date_insert'], "d.m.Y" );
		$args['event_status'] = usam_get_object_status_name( $data['status'], $data['type'] );		
			
		require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
		$webform_code = usam_get_event_metadata( $data['id'], 'webform');
		$webform = usam_get_webform( $webform_code, 'code' );
		
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );			
		foreach ( $newsletters as $newsletter ) 
		{						
			$conditions = usam_get_newsletter_metadata( $newsletter->id, 'conditions' );
			if ( empty($conditions['webform']) || empty($conditions['status']) )
				continue;
				
			if ( in_array($current_status, (array)$conditions['status']) && in_array($webform['id'], (array)$conditions['webform']) )
			{	
				$_newsletter = new USAM_Send_Newsletter( $newsletter->id, $args );		
				$_newsletter->send_email_trigger( $email );
			}
		}		
	}
// Ответ на письмо
	public function send_trigger_response_letter( $letter, $mailbox )
	{
		if ( empty($letter['from_email']) )
			return false;
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger','meta_query' => [['key' => 'event_start', 'value' => 'response_letter', 'compare' => '=']]]);	
		if ( empty($newsletters) )
			return false;	
		
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		$args = array();
		foreach ( $newsletters as $newsletter ) 
		{						
			$conditions = usam_get_newsletter_metadata( $newsletter->id, 'conditions' );
			if ( in_array($mailbox['email'], $conditions['mailboxes']) )
			{
				$_newsletter = new USAM_Send_Newsletter( $newsletter->id, $args );		
				$_newsletter->send_email_trigger( $letter['from_email'] );
			}
		}		
	}

	public function send_trigger_product_arrived( $product_id, $all_stock )
	{	 
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger','meta_query' => [['key' => 'event_start', 'value' => 'product_arrived', 'compare' => '=']]]);	
		if ( empty($newsletters) )
			return false;					
				
		$args = $this->product_shortcode( $product_id );
			 
		$contacts = usam_get_contacts(['user_post' => [['list' => 'subscription', 'product_id' => $product_id]], 'cache_meta' => true, 'source' => 'all', 'status' => 'all']);
		if ( empty($contacts) )
			return false;
			 
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		foreach ( $newsletters as $newsletter ) 
		{						
			foreach ( $contacts as $contact )
			{
				$_newsletter = new USAM_Send_Newsletter( $newsletter->id, $args, (array)$contact );		
				$_newsletter->send_email_trigger( usam_get_contact_metadata( $contact->id, 'email' ), [], [['object_id' => $product_id, 'object_type' => 'product']] );
			}
		}		
	}		
		
// Изменение статуса заказа
	public function send_trigger_order_status_change( $order_id, $current_status, $previous_status, $order )
	{
		if( !apply_filters( 'usam_prevent_notification_change_status', true ) ) 	
			return false;
		
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger','meta_query' => [['key' => 'event_start', 'value' => 'order_status_change', 'compare' => '=']]]);	
		if ( empty($newsletters) )
			return false;
			
		$email = usam_get_order_customerdata( $order_id, 'email' );
		
		if ( empty($email) )
			return false;
		
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );		
		$order_shortcode = new USAM_Order_Shortcode( $order_id );
		$args = $order_shortcode->get_html_args();		
		
		$attachments = [];					
		foreach ( $newsletters as $newsletter ) 
		{						
			$conditions = usam_get_newsletter_metadata( $newsletter->id, 'conditions' );
			if ( in_array($current_status, (array)$conditions['status']) )
			{	
				if ( !empty($conditions['invoice']) )
				{
					$file_path = usam_set_invoice_to_pdf( $order_id );			
					if ( file_exists( $file_path ) )			
						$attachments[sprintf( __("Счет на оплату заказа №%s","usam"), $order_id).'.pdf'] = $file_path;
				}
				$_newsletter = new USAM_Send_Newsletter( $newsletter->id, $args );		
				$_newsletter->send_email_trigger( $email, $attachments, [['object_id' => $order_id, 'object_type' => 'order']] );				
			}
		}		
	}

//Отправка сообщений о дате готовности заказа
	public function send_trigger_order_is_collected( $shipped_document_id )
	{			
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger','meta_query' => [['key' => 'event_start', 'value' => 'order_is_collected', 'compare' => '=']]]);
		if ( empty($newsletters) )
			return false;
		
		$document = usam_get_shipped_document( $shipped_document_id );
		$email = usam_get_order_customerdata( $document['order_id'], 'email' );
		
		if ( empty($email) )
			return false;
		
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		
		$order_shortcode = new USAM_Order_Shortcode( $document['order_id'] );
		$args = $order_shortcode->get_html_args();	
		$readiness_date = usam_get_shipped_document_metadata( $shipped_document_id, 'readiness_date' );
		$args['shipping_readiness_date'] = usam_local_date( $readiness_date, "d.m.Y" );	
		$args['shipping_readiness_time'] = usam_local_date( $readiness_date, 'H:i' );		
		$i = 0;
		foreach ( $newsletters as $newsletter ) 
		{				
			$_newsletter = new USAM_Send_Newsletter( $newsletter->id, $args );		
			if ( $_newsletter->send_email_trigger( $email, [], [['object_id' => $document['order_id'], 'object_type' => 'order'], ['object_id' => $document['id'], 'object_type' => 'shipped']] ) )
				$i++;
		}	
		return $i;		
	}	
	
	public function customer_notification_tracking( $shipped_document_id )
	{			
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger','meta_query' => [['key' => 'event_start', 'value' => 'trackingid', 'compare' => '=']]]);					
		if ( empty($newsletters) )
			return false;
		
		$document = usam_get_shipped_document( $shipped_document_id );
		$email = usam_get_order_customerdata( $document['order_id'], 'email' );
		
		if ( empty($email) )
			return false;
		
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
				
		$order_shortcode = new USAM_Order_Shortcode( $document['order_id'] );
		$args = $order_shortcode->get_html_args();			
		$i = 0;
		foreach ( $newsletters as $newsletter ) 
		{				
			$_newsletter = new USAM_Send_Newsletter( $newsletter->id, $args );		
			if ( $_newsletter->send_email_trigger( $email, [], [['object_id' => $document['order_id'], 'object_type' => 'order'], ['object_id' => $document['id'], 'object_type' => 'shipped']] ) )
				$i++;
		}
		return $i;
	}	
	
// Оплата заказа
	public function send_trigger_order_paid( $_order )
	{			
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );		
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger','meta_query' => [['key' => 'event_start', 'value' => 'order_paid', 'compare' => '=']]]);					
		if ( empty($newsletters) )
			return false;
		
		$order_id = $_order->get('id');
		$email = usam_get_order_customerdata( $order_id, 'email' );
		
		if ( empty($email) )
			return false;
		
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );
		
		$order_shortcode = new USAM_Order_Shortcode( $order_id );
		$args = $order_shortcode->get_html_args();		
		foreach ( $newsletters as $newsletter ) 
		{				
			$_newsletter = new USAM_Send_Newsletter( $newsletter->id, $args );		
			$_newsletter->send_email_trigger( $email, [], [['object_id' => $order_id, 'object_type' => 'order']] );						
		}			
	}

// Заход на сайт
	public function send_trigger_site_login( $user_login, $user )
	{		
		if ( usam_check_is_employee($user->ID, 'user_id') )
			return false;
		
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );	
		$newsletters = usam_get_newsletters(['status' => 5, 'class' => 'trigger', 'meta_query' => [['key' => 'event_start', 'value' => 'sender_user_auth', 'compare' => '=']]]);					
		if ( empty($newsletters) )
			return false;
	
		require_once( USAM_FILE_PATH . '/includes/mailings/send_newsletter.class.php' );			
		foreach ( $newsletters as $newsletter ) 
		{		
			$_newsletter = new USAM_Send_Newsletter( $newsletter->id );		
			$_newsletter->send_email_trigger( $user->user_email );			
		}			
	}	
}


function usam_get_user_stat_mailing( $id )
{	
	global $wpdb;	
	$id = (int)$id;	
	$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".USAM_TABLE_NEWSLETTER_USER_STAT." WHERE id = '%d'", $id ), ARRAY_A );	
	return $result;
}

function usam_update_user_stat_newsletter( $id, $update )
{	
	global $wpdb;
	
	$id = (int)$id;	
	
	if ( empty($id) )
		return false;

	$format_default = array( 'sent_at' => '%s', 'opened_at' => '%s', 'clicked' => '%d', 'status' => '%d', 'unsub' => '%d' );	
	$formats = array();	
	foreach ( $update as $key => $value ) 
	{
		if ( isset($format_default[$key]) )	
			$formats[] = $format_default[$key];	
		else
			unset($update[$key]);
	}		
	$result = $wpdb->update( USAM_TABLE_NEWSLETTER_USER_STAT, $update, array( 'id' => $id ), $formats, array( '%d' ) );	
	return $result;
}

function usam_update_newsletter_lists( $newsletter_id, $lists )
{	
	global $wpdb;
	
	$wpdb->query( $wpdb->prepare("DELETE FROM ".USAM_TABLE_NEWSLETTER_LISTS." WHERE newsletter_id ='%d'", $newsletter_id) );
	if ( is_array($lists) )
		foreach ($lists as $list_id )
		{
			$result = $wpdb->insert( USAM_TABLE_NEWSLETTER_LISTS, array( 'newsletter_id' => $newsletter_id, 'list' => $list_id ), array( '%d', '%d', ) );
		}	
}

function usam_get_newsletter_list( $newsletter_id )
{	
	global $wpdb;		
	$lists = $wpdb->get_col( $wpdb->prepare( "SELECT list FROM ".USAM_TABLE_NEWSLETTER_LISTS." WHERE newsletter_id = '%d'", $newsletter_id ) );
	if ( $lists )
		$lists = array_map('intval', $lists);
	return $lists;
}

// Добавить список в статистику
function usam_add_list_newsletter_user_stat( $newsletter_id, $type )
{ 
	$lists = usam_get_newsletter_list( $newsletter_id );
	if ( empty($lists) )
		return false;
	
	global $wpdb;	
	$communications = $wpdb->get_col("SELECT s_list.communication FROM ".USAM_TABLE_SUBSCRIBER_LISTS." AS s_list 
	LEFT JOIN ".USAM_TABLE_NEWSLETTER_USER_STAT." AS stat ON (s_list.communication = stat.communication AND stat.newsletter_id=$newsletter_id AND stat.sent_at IS NULL) 	
	WHERE s_list.list IN ('".implode( "','", $lists )."') AND s_list.status!=2 AND s_list.type='{$type}' AND stat.communication IS NULL");			
	if ( $communications )
	{
		foreach ( $communications as $communication ) 	
		{			
			if ( $type == 'email' && !is_email($communication) )	
				continue;
			usam_set_mailing_user_stat(['newsletter_id' => $newsletter_id, 'communication' => $communication]);
		} 		
		return count($communications);
	}
	else
		return false;
}


// Добавить электронный адрес в статистику
function usam_set_mailing_user_stat( $args )
{
	global $wpdb;	

	$insert = array();
	if ( isset($args['newsletter_id']) )
		$insert['newsletter_id'] = $args['newsletter_id'];	
	else
		return false;
	
	if ( isset($args['communication']) )
		$insert['communication'] = $args['communication'];	
	else
		return false;
				
	if ( isset($args['sent_at']) )
		$insert['sent_at'] = $args['sent_at'];	
	
	if ( isset($args['opened_at']) )
		$insert['opened_at'] = $args['opened_at'];	
	
	if ( isset($args['status']) )
		$insert['status'] = $args['status'];	
	
	$formats = array();
	foreach( $insert as $key => $value )
	{
		switch ( $key ) 
		{
			case 'id':
			case 'newsletter_id':					
				$formats[] = '%d';							
			break;				
			case 'communication':
			case 'sent_at':
			case 'opened_at':
			case 'status':				
				$formats[] = '%s';	
			break;
			default:
				unset($insert[$key]);
		}			
	}	
	$result = $wpdb->insert( USAM_TABLE_NEWSLETTER_USER_STAT, $insert, $formats );	
	return $wpdb->insert_id;
}

function usam_update_location_subscriber( $email )
{			
	$location = usam_get_customer_location();
	if ( $location )
	{			
		$contacts = usam_get_contacts(['meta_value' => $email, 'meta_key' => 'email']);	
		if ( !empty($contacts) )
			foreach ( $contacts as $contact ) 
			{
				usam_add_contact_metadata( $contact->id, 'location', $location, true );	
			}
	}	
}

function usam_get_newsletter_sending_statuses( )
{
	$statuses = [0 => __('Не отправлено','usam'), 1 => __('Отправлено','usam'), 2 => __('Посмотрели','usam'), 9 => __('Ошибка','usam'), 8 => __('Не отправлено','usam')];	
	return $statuses;
}

function usam_get_newsletter_statuses( )
{	
	$statuses = [0 => __('Не подтвержден','usam'), 1 => __('Подписан','usam'), 2 => __('Отписался','usam')];	
	return $statuses;
}

function usam_get_status_name_newsletter( $status )
{	
	$statuses = usam_get_newsletter_statuses( );
	if ( isset($statuses[$status]) )
		return $statuses[$status];
	return '';
}
?>