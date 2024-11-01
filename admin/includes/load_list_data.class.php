<?php
/* =====================  Загрузка данных в список ================================================ */
require_once( USAM_FILE_PATH . '/admin/includes/filter_processing.class.php' );
class USAM_Load_List_Data 
{			
	private $start_date_interval = '';
	private $end_date_interval = '';	
	
	public function load( $type, $offset = 0 )
	{
		$f = new Filter_Processing();
		$date = $f->get_date_interval();		
		$this->start_date_interval = $date['from'] == ''?'':date('Y-m-d H:i:s', $date['from']);			
		$this->end_date_interval = $date['to'] == ''?'':date("Y-m-d H:i:s", $date['to']);		
		if ( method_exists($this, $type) )
			$results = $this->$type( $offset );
		else
			$results = apply_filters( 'usam_load_list_data_'.$type, false, $this->get_query_vars() );
		return $results;
	}
	
	private function get_filter_value( $key, $default = null ) 
	{ 	
		if ( isset($_POST[$key]) )
		{
			if ( is_array($_POST[$key]) )
				$select = stripslashes_deep($_POST[$key]);
			elseif ( stripos($_POST[$key], ',') !== false )
				$select = explode(',',stripslashes($_POST[$key]));
			else
				$select = stripslashes($_POST[$key]);
		}
		else
			$select = $default;		
		return $select;
	}
	
	private function get_query_vars( $query_vars = [] ) 
	{		
		$column_date = isset($query_vars['column_date']) ? $query_vars['column_date'] : 'date_insert';
		if ( $this->start_date_interval )
			$query_vars['date_query'][] = ['after' => get_gmt_from_date($this->start_date_interval, "Y-m-d H:i:s"), 'inclusive' => true, 'column' => $column_date];	
		if ( $this->end_date_interval )
			$query_vars['date_query'][] = ['before' => get_gmt_from_date($this->end_date_interval, "Y-m-d H:i:s"), 'inclusive' => true, 'column' => $column_date];			
		$selected = $this->get_filter_value( 'weekday' );
		if ( !empty($selected) )	
		{
			$weekday = array_map( 'intval', $selected );
			$query_vars['date_query'][] = array( 'dayofweek' => $weekday, 'compare' => 'IN', 'column' => $column_date );		
		}		
		return $query_vars;
	}
	
	//Рейтинг пользователей по количеству обращений
	private function rating_the_number_requests_companies( $offset )
	{	
		$companies = usam_get_companies(['offset' => $offset, 'number' => 10, 'fields' => array('count','name', 'id'), 'order' => 'DESC', 'groupby' => 'events', 'orderby' => 'count']);
		$results = array();
		foreach ( $companies as $company )
		{
			$results[] = ['primary' => '<a href="'. usam_get_company_url( $company->id ) .'" target="_blank">'.$company->name.'</a>', 'column' => $company->count, 'id' => $company->id ];
		}
		return $results;
	}
	
	private function low_satisfaction_companies( $offset )
	{
		$companies = usam_get_companies(['offset' => $offset, 'number' => 10, 'fields' => ['status','name', 'id'], 'order' => 'DESC', 'orderby' => 'status', 'status' => ['blocked', 'flagged']]);	
		$results = [];
		foreach ( $companies as $company )
		{
			$results[] = ['primary' => '<a href="'.usam_get_company_url( $company->id ).'" target="_blank">'.$company->name.'</a>', 'column' => usam_get_object_status_name( $company->status, 'company'), 'id' => $company->id];
		}
		return $results;
	}	
	
	private function city_companies( $offset )
	{	
		global $wpdb;
		$companies = $wpdb->get_results("SELECT COUNT(company_id) AS count, meta_value FROM ".USAM_TABLE_COMPANY_META." WHERE meta_key='contactlocation' GROUP BY meta_value ORDER BY count DESC");
		$results = array();
		if ( !empty($companies) )
		{
			$ids = array();
			foreach ( $companies as $company )
			{			
				$customers[$company->meta_value] = $company->count;		
				$ids[] = $company->meta_value;
			}						
			$locations = usam_get_locations(['offset' => 0, 'include' => $ids, 'number' => 10, 'orderby' => 'include']);
			foreach ( $locations as $location )
			{
				$results[] = array('primary' => '<a href="'.add_query_arg(['page' => 'crm', 'tab' => 'companies', 'view' => 'table', 'location' => $location->id], admin_url('admin.php') ) .'" target="_blank">'.$location->name.'</a>', 'column' => $customers[$location->id], 'id' => $location->id  );
			}
		}
		return $results;
	}	
	
	private function city_contacts_online( $offset )
	{
		return $this->location_contacts( $offset, ['online' => true, 'status' => 'all']);
	}	
	
	public function city_contacts( $offset )
	{		
		return $this->location_contacts($offset);
	}
	
	//Города посетителей
	private function location_contacts( $offset, $args = [] )
	{		
		$args['offset'] = $offset;
		$args['number'] = 10;
		$args['fields'] = ['meta_value', 'count'];
		$args['groupby'] = 'meta_value';
		$args['orderby'] = 'count';
		$args['order']   = 'DESC';
		$args['meta_key'] = 'location';
	
		$contacts = usam_get_contacts( $args );
		$results = array();
		if ( !empty($contacts) )
		{
			$ids = array();
			foreach ( $contacts as $contact )
			{			
				$customers[$contact->meta_value] = $contact->count;		
				$ids[] = $contact->meta_value;
			}						
			$locations = usam_get_locations(['offset' => 0, 'include' => $ids, 'number' => 10, 'orderby' => 'include']);
			foreach ( $locations as $location )
			{
				$results[] = ['primary' => '<a href="'.add_query_arg(['page' => 'crm', 'tab' => 'contacts', 'view' => 'table', 'location' => $location->id], admin_url('admin.php') ) .'" target="_blank">'.$location->name.'</a>', 'column' => $customers[$location->id], 'id' => $location->id];
			}
		}
		return $results;
	}
	
	//Пользователи онлайн
	private function online_contacts( $offset )
	{	
		$args = ['offset' => $offset, 'number' => 10, 'fields' => ['appeal','id'], 'order' => 'DESC', 'date_query' => [['after' => '1 hours ago'], 'column' => 'online'], 'cache_meta' => true, 'orderby' => 'id'];	
		$contacts = usam_get_contacts( $args );
		
		$ids = array();
		foreach ( $contacts as $contact )
		{						
			$location = usam_get_contact_metadata( $contact->id, 'location' );
			if ( !empty($location) )
				$ids[] = $location;
		}				
		$locations = usam_get_locations(['offset' => 0, 'include' => $ids, 'number' => 10]);	
		$locations_contact = array();
		foreach ( $locations as $location )
		{			
			$locations_contact[$location->id] = $location->name;	
		}	
		
		$results = array();
		foreach ( $contacts as $contact )
		{
			$location = usam_get_contact_metadata( $contact->id, 'location' );			
			$results[] = array('primary' => '<a href="'. usam_get_contact_url( $contact->id ) .'" target="_blank">'.$contact->appeal.'</a>', 'column' => !empty($locations_contact[$location])?$locations_contact[$location]:'', 'id' => $contact->id  );
		}
		return $results;
	}
	
	private function online_employee( $offset )
	{	
		$args = ['source' => 'employee', 'offset' => $offset, 'number' => 10, 'fields' => ['appeal','id'], 'order' => 'DESC', 'date_query' => [['after' => '1 hours ago'], 'column' => 'online'], 'cache_meta' => true, 'orderby' => 'id'];	
		$contacts = usam_get_contacts( $args );
		
		$ids = array();
		foreach ( $contacts as $contact )
		{						
			$location = usam_get_contact_metadata( $contact->id, 'location' );
			if ( !empty($location) )
				$ids[] = $location;
		}				
		$locations = usam_get_locations(['offset' => 0, 'include' => $ids, 'number' => 10]);
		$locations_contact = array();
		foreach ( $locations as $location )
		{			
			$locations_contact[$location->id] = $location->name;	
		}			
		$results = array();
		foreach ( $contacts as $contact )
		{
			$location = usam_get_contact_metadata( $contact->id, 'location' );			
			$results[] = array('primary' => '<a href="'. usam_get_contact_url( $contact->id ) .'" target="_blank">'.$contact->appeal.'</a>', 'column' => !empty($locations_contact[$location])?$locations_contact[$location]:'', 'id' => $contact->id  );
		}
		return $results;
	}
	
	//Рейтинг пользователей по количеству обращений
	private function rating_the_number_requests_contacts( $offset )
	{		
		$args = array( 'offset' => $offset, 'number' => 10, 'fields' => array('count','appeal','id'), 'order' => 'DESC', 'groupby' => 'events', 'orderby' => 'count' );	
		$contacts = usam_get_contacts( $args );
			
		$results = array(); 	
		foreach ( $contacts as $contact )
		{
			$contact_name = !empty($contact->appeal)?$contact->appeal:$contact->id;
			$results[] = array('primary' => '<a href="'.usam_get_contact_url( $contact->id ).'" target="_blank">'.$contact_name.'</a>', 'column' => $contact->count, 'id' => $contact->id  );
		}
		return $results;
	}
	
	private function low_satisfaction_contacts( $offset )
	{			
		$args = array( 'offset' => $offset, 'number' => 10, 'fields' => array('status','appeal','id'), 'order' => 'DESC', 'orderby' => 'status', 'status' => array( 'blocked', 'flagged' ) );	
		$contacts = usam_get_contacts( $args );	
		$results = array();
		foreach ( $contacts as $contact )
		{
			$results[] = array('primary' => '<a href="'.usam_get_contact_url( $contact->id ) .'" target="_blank">'.$contact->appeal.'</a>', 'column' => usam_get_object_status_name( $contact->status, 'contact'), 'id' => $contact->id  );
		}
		return $results;
	}
	
	private function viewed_products( $offset )
	{	
		$id = absint($_POST['id']);			
		require_once( USAM_FILE_PATH . '/includes/analytics/pages_viewed_query.class.php' );
		$viewed = usam_get_pages_viewed(['offset' => $offset, 'number' => 10, 'contact_id' => $id, 'post-type' => 'usam-product']);		
		$results = [];	
		foreach ( $viewed as $view )
		{
			$sku = "<div class='list_row__description'>".__('Артикул', 'usam').': <span class="js-copy-clipboard">'.usam_get_product_meta( $view->post_id, 'sku' ).'</span></div>';
			$results[] = ['primary' => "<a href='".get_edit_post_link( $view->post_id )."'>".usam_get_product_thumbnail($view->post_id, 'manage-products')."</a><a href='".get_edit_post_link($view->post_id)."' target='_blank'>".get_the_title( $view->post_id )."</a>$sku", 'column' =>  usam_local_date($view->date_insert), 'id' => $view->post_id];
		}
		return $results;
	}	
	
	private function visits( $offset )
	{			
		$id = absint($_POST['id']);			
		require_once( USAM_FILE_PATH . '/includes/analytics/visits_query.class.php' );
		$visits = usam_get_visits(['offset' => $offset, 'number' => 10, 'contact_id' => $id, 'add_fields' => ['device']]);		
		$results = array();	
		foreach ( $visits as $visit )
		{
			$device = $visit->device == 'mobile'?__('Мобильные','usam'):__('ПК','usam');
			$results[] = ['primary' => sprintf(__("Время на сайте: %s. Просмотров: %s.","usam"), human_time_diff( strtotime($visit->date_update), strtotime($visit->date_insert) ), $visit->views)."<div class='list_row__description'>".sprintf(__("Тип устройства: %s","usam"),$device)."</div>", 'column' => usam_local_date($visit->date_insert), 'id' => $visit->id];
		}
		return $results;
		return $results;
	}	
		
// Получить избранное	
	private function desired_list_contact( $offset )
	{			
		$results = $this->user_products( $offset, 'desired' );	
		return $results;
	}	
	
	private function product_subscriptions_contact( $offset )
	{			
		$results = $this->user_products( $offset, 'subscription' );	
		return $results;
	}	
	
	private function compare_list_contact( $offset )
	{			
		$results = $this->user_products( $offset, 'compare' );	
		return $results;
	}	
	
	private function user_products( $offset, $list )
	{			
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);	
			require_once(USAM_FILE_PATH.'/includes/customer/user_posts_query.class.php');
			$user_products = usam_get_user_posts(['user_list' => $list, 'contact_id' => $id]);			
			if ( !empty($user_products) )		
			{
				$product_ids = array();
				$date_addition = array();
				foreach ( $user_products as $product )
				{
					$product_ids[] = $product->product_id;			
					$date_addition[$product->product_id] = usam_local_date($product->date_insert);							
				}				
				$args = array( 'post__in' => $product_ids, 'update_post_term_cache' => false, 'stocks_cache' => false, 'prices_cache' => false );	
				$products = usam_get_products( $args );
				foreach ( $products as $product )
				{
					$sku = "<div class='list_row__description'>".__('Артикул', 'usam').': <span class="js-copy-clipboard">'.usam_get_product_meta( $product->ID, 'sku' ).'</span></div>';
					$results[] = array('primary' => "<a href='".get_edit_post_link( $product->ID )."'>".usam_get_product_thumbnail( $product->ID, 'manage-products' )."</a><a href='".get_edit_post_link( $product->ID )."' target='_blank'>$product->post_title</a>$sku", 'column' => $date_addition[$product->ID], 'id' => $product->ID  );
				}
			}
		}
		return $results;
	}
	
	private function send_newsletters_contact( $offset )
	{			
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);	
			global $wpdb;
			$properties = usam_get_properties( array( 'type' => 'contact', 'active' => 1, 'field_type' => array('mobile_phone', 'phone', 'email'), 'fields' => 'code' ) );
			$communications = $wpdb->get_col( "SELECT meta_value FROM ".USAM_TABLE_CONTACT_META." WHERE contact_id ='$id' AND meta_key IN('".implode("','",$properties)."')");
			if ( !empty($communications) )		
			{
				$args = array( 'offset' => $offset, 'number' => 10, 'order' => 'DESC', 'orderby' => 'sent_at', 'status' => array( 5,6), 'communication' => $communications );	
				$results = $this->get_newsletters( $args );	
			}
		}
		return $results;
	}
	
	private function send_newsletters_company( $offset )
	{			
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);	
			global $wpdb;
			$properties = usam_get_properties( array( 'type' => 'company', 'active' => 1, 'field_type' => array('mobile_phone', 'phone', 'email'), 'fields' => 'code' ) );
			$communications = $wpdb->get_col( "SELECT meta_value FROM ".USAM_TABLE_COMPANY_META." WHERE company_id ='$id' AND meta_key IN('".implode("','",$properties)."')");
			if ( !empty($communications) )		
			{
				$args = array( 'offset' => $offset, 'number' => 10, 'order' => 'DESC', 'orderby' => 'sent_at', 'status' => array( 5,6), 'communication' => $communications );	
				$results = $this->get_newsletters( $args );	
			}
		}
		return $results;
	}

	private function open_newsletters_company( $offset )
	{			
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);	
			global $wpdb;
			$properties = usam_get_properties( array( 'type' => 'company', 'active' => 1, 'field_type' => array('mobile_phone', 'phone', 'email'), 'fields' => 'code' ) );
			$communications = $wpdb->get_col( "SELECT meta_value FROM ".USAM_TABLE_COMPANY_META." WHERE company_id ='$id' AND meta_key IN('".implode("','",$properties)."')");
			if ( !empty($communications) )		
			{
				$args = array( 'offset' => $offset, 'number' => 10, 'order' => 'DESC', 'orderby' => 'sent_at', 'status' => array( 5,6), 'status_newsletter' => 2, 'communication' => $communications );	
				$results = $this->get_newsletters( $args );	
			}
		}
		return $results;
	}	
	
	private function open_newsletters_contact( $offset )
	{			
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);		
						 
			global $wpdb;
			$properties = usam_get_properties( array( 'type' => 'contact', 'active' => 1, 'field_type' => array('mobile_phone', 'phone', 'email'), 'fields' => 'code' ) );
			$communications = $wpdb->get_col( "SELECT meta_value FROM ".USAM_TABLE_CONTACT_META." WHERE contact_id ='$id' AND meta_key IN('".implode("','",$properties)."')");
			if ( !empty($communications) )		
			{
				$args = ['offset' => $offset, 'number' => 10, 'order' => 'DESC', 'orderby' => 'sent_at', 'status' => array( 5,6), 'status_newsletter' => 2, 'communication' => $communications];	
				$results = $this->get_newsletters( $args );	
			}
		}
		return $results;
	}
	
	private function get_newsletters( $args )
	{		
		$results = [];	
		require_once( USAM_FILE_PATH . '/includes/mailings/newsletter_query.class.php' );
		$newsletters = usam_get_newsletters( $args );		
		foreach ( $newsletters as $newsletter )
		{ 
			$text = $newsletter->class == 'trigger'?__("Триггерная рассылка","usam"):'';
			$results[] = ['primary' => '<a href="'.add_query_arg(['page' => 'newsletter', 'form' => 'view', 'id' => $newsletter->id], admin_url('admin.php') ) .'" target="_blank">'.$newsletter->subject."</a><br>$text", 'column' => usam_local_date($newsletter->sent_at), 'id' => $newsletter->id];
		}		
		return $results;
	}
		
// Получить корзины
	private function basket( $offset )
	{			
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);				
			$contact = usam_get_contact( $id );				
			if ( $contact['user_id'] )
			{
				require_once( USAM_FILE_PATH . '/includes/basket/products_basket_query.class.php'   );
				$products_baskets = usam_get_products_baskets( array( 'fields' => array( 'quantity', 'product_id', 'date_insert' ), 'user_id' => $contact['user_id'] ));
				if ( !empty($products_baskets) )		
				{
					$product_ids = array();
					$date_addition = array();
					foreach ( $products_baskets as $product )
					{
						$product_ids[] = $product->product_id;			
						$date_addition[$product->product_id] = array( 'quantity' => $product->quantity, 'date' => usam_local_date($product->date_insert) );							
					}				
					$args = array( 'post__in' => $product_ids, 'update_post_term_cache' => false, 'stocks_cache' => false, 'prices_cache' => false );	
					$products = usam_get_products( $args );
					foreach ( $products as $product )
					{
						$sku = "<div class='list_row__description'>".__('Артикул', 'usam').': <span class="js-copy-clipboard">'.usam_get_product_meta( $product->ID, 'sku' ).'</span></div>';
						$results[] = array('primary' => "<a href='".get_edit_post_link( $product->ID )."'>".usam_get_product_thumbnail( $product->ID, 'manage-products' )."</a><a href='".get_edit_post_link( $product->ID )."' target='_blank'>$product->post_title</a> (".$date_addition[$product->ID]['quantity'].")$sku" , 'column' => $date_addition[$product->ID]['date'], 'id' => $product->ID  );
					}
				}
			}
		}
		return $results;
	}
	
	private function purchased_products_company( $offset )
	{			
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);				
							
			require_once(USAM_FILE_PATH.'/includes/document/products_order_query.class.php');
			$products = usam_get_products_order_query(['offset' => $offset, 'companies' => $id, 'number' => 10, 'order_status' => 'closed', 'order' => 'DESC']);	
			foreach ( $products as $product )
			{
				$sku = "<div class='list_row__description'>".__('Артикул', 'usam').': <span class="js-copy-clipboard">'.usam_get_product_meta( $product->id, 'sku' ).'</span></div>';
				$results[] = array('primary' => "<a href='".get_edit_post_link( $product->product_id )."'>".usam_get_product_thumbnail( $product->product_id, 'manage-products' )."</a><a href='".get_edit_post_link( $product->product_id )."' target='_blank'>$product->name</a> (".$product->quantity.")$sku" , 'column' => usam_local_date($product->date_insert), 'id' => $product->product_id  );
			}
		}
		return $results;
	}	
	
	private function purchased_products_contact( $offset )
	{			
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);				
			
			require_once(USAM_FILE_PATH.'/includes/document/products_order_query.class.php');
			$args = array( 'offset' => $offset, 'contacts' => $id, 'number' => 10, 'order_status' => 'closed', 'order' => 'DESC' );	
			$products = usam_get_products_order_query( $args );
			foreach ( $products as $product )
			{
				$sku = "<div class='list_row__description'>".__('Артикул', 'usam').': <span class="js-copy-clipboard">'.usam_get_product_meta( $product->id, 'sku' ).'</span></div>';
				$results[] = array('primary' => "<a href='".get_edit_post_link( $product->product_id )."'>".usam_get_product_thumbnail( $product->product_id, 'manage-products' )."</a><a href='".get_edit_post_link( $product->product_id )."' target='_blank'>$product->name</a> (".$product->quantity.")$sku" , 'column' => usam_local_date($product->date_insert), 'id' => $product->product_id  );
			}
		}
		return $results;
	}	
	
	private function recorded_calls_company( $offset )
	{			
		$results = array();
		if ( !empty($_POST['id']) )
		{
			$company_id = absint($_POST['id']);		
			$phones = usam_get_company_phones( $company_id );
			$results = $this->recorded_calls( $phones, $offset);
		}
		return $results;
	}
	
	private function recorded_calls_contact( $offset )
	{			
		$results = array();
		if ( !empty($_POST['id']) )
		{
			$contact_id = absint($_POST['id']);		
			$phones = usam_get_contact_phones( $contact_id );
			$results = $this->recorded_calls( $phones, $offset);
		}
		return $results;
	}	
	
	private function recorded_calls( $phones, $offset )
	{			
		$results = array();	
		require_once( USAM_FILE_PATH . '/includes/crm/calls_query.class.php' );		
		$args = array( 'offset' => $offset, 'phone' => $phones, 'number' => 10, 'status' => 'completed', 'order' => 'DESC' );	
		$calls = usam_get_calls( $args );	
		if ( empty($calls) )
			return $results;
		$ids = array();
		foreach ( $calls as $call )
			$ids[] = $call->id;
		
		$results_files = usam_get_files(['object_id' => $ids, 'type' => 'phone']);
		
		$files = array();
		foreach ( $results_files as $file )
			$files[$file->object_id] = $file->code;
			
		foreach ( $calls as $call )
		{					
			if ( isset($files[$call->id]) )
			{
				$hours = floor($call->time / 3600);
				$minutes = floor($call->time / 60);
				$sec = $call->time % 60;	
			
				$url = get_bloginfo('url').'/file/'.$files[$call->id];				
				$results[] = array('primary' => "<a href='".$url."' target='_blank'>".usam_local_date($call->date_insert)."</a>", 'column' => sprintf('%02d:%02d:%02d', $hours, $minutes, $sec), 'id' => $call->id  );
			}
		}
		return $results;
	}		
	
	private function reviews_contact( $offset )
	{				
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);		
			
			$args = array( 'offset' => $offset, 'contacts' => $id, 'number' => 10, 'status' => array(1,2), 'order' => 'DESC' );	
			require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
			$reviews = usam_get_customer_reviews( $args );			
			foreach ( $reviews as $review )
			{
				$review_text = !empty($review->title)?$review->title:usam_limit_words($review->review_text);
				$results[] = array('primary' => "<a href='".admin_url("admin.php?page=feedback&tab=reviews&id={$review->id}&form=edit&form_name=review" )."' target='_blank'>$review_text</a><br>".usam_get_rating( $review->rating ) , 'column' => usam_local_date($review->date_insert), 'id' => $review->id  );
			}
		}
		return $results;
	}	
	
	private function orders_paid_bonus_cards( $offset )
	{			
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);				
			
			require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );
			$order_ids = usam_get_bonuses( array('code' => $id, 'fields' => 'order_id', 'type_transaction' => 1 ) );				
			if ( !empty($order_ids) )
			{
				$args = array( 'offset' => $offset, 'include' => $order_ids, 'number' => 10, 'status' => 'closed', 'order' => 'DESC' );	
				$orders = usam_get_orders( $args );	
				foreach ( $orders as $order )
				{
					$results[] = array('primary' => "<a href='".usam_get_url_order( $order->id )."'>№$order->id (".usam_get_formatted_price( $order->totalprice, array('type_price' => $order->type_price) ).")</a>" , 'column' => usam_local_date($order->date_insert), 'id' => $order->id  );
				}
			}
		}
		return $results;
	}	
	
	private function orders_bonus_cards( $offset )
	{			
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);			
			require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );
			$order_ids = usam_get_bonuses( array('code' => $id, 'fields' => 'order_id', 'type_transaction' => 0 ) );				
			if ( !empty($order_ids) )
			{
				$orders = usam_get_orders(['offset' => $offset, 'include' => $order_ids, 'number' => 10, 'status' => 'closed', 'order' => 'DESC']);	
				foreach ( $orders as $order )
				{
					$results[] = ['primary' => "<a href='".usam_get_url_order( $order->id )."'>№$order->id (".usam_get_formatted_price( $order->totalprice, array('type_price' => $order->type_price) ).")</a>" , 'column' => usam_local_date($order->date_insert), 'id' => $order->id];
				}
			}
		}
		return $results;
	}	
	
	private function orders_city( $offset )
	{				
		$properties = usam_get_properties(['fields' => 'code', 'type' => 'order', 'field_type' => 'location']);
		
		$args = $this->get_query_vars(['offset' => $offset, 'number' => 10, 'fields' => ['meta_value', 'count'], 'groupby' => 'meta_value', 'orderby' => 'count', 'order' => 'DESC', 'meta_key' => $properties]);
		
		$orders = usam_get_orders( $args );		
		$results = [];
		if ( !empty($orders) )
		{
			$ids = [];
			$customers = [];
			foreach ( $orders as $order )
			{			
				$customers[$order->meta_value] = $order->count;		
				$ids[] = $order->meta_value;
			}						
			$locations = usam_get_locations(['offset' => 0, 'include' => $ids, 'orderby' => 'include']);
			foreach ( $locations as $location )
			{
				$results[] = ['primary' => $location->name, 'column' => $customers[$location->id], 'id' => $location->id];
			}
		}
		return $results;		
	}	
	
	private function orders_best_company( $offset )
	{					
		$args = $this->get_query_vars(['offset' => $offset, 'number' => 10, 'fields' => ['company_id', 'sum'], 'groupby' => 'company_id', 'orderby' => 'sum', 'order' => 'DESC', 'conditions' => ['key' => 'company_id', 'compare' => '>', 'value' => 0]]);		
		$orders = usam_get_orders( $args );		
		$results = [];
		if ( !empty($orders) )
		{
			$ids = [];
			$customers = [];
			foreach ( $orders as $order )
			{			
				$customers[$order->company_id] = $order->sum;		
				$ids[] = $order->company_id;
			}			
			$companies = usam_get_companies(['include' => $ids, 'orderby' => 'include']);		
			foreach ( $companies as $company )
			{
				$results[] = ['primary' => $company->name, 'column' => usam_get_formatted_price( $customers[$company->id] ), 'id' => $company->id];
			}			
		}
		return $results;		
	}	
	
	private function best_payment_company( $offset )
	{			
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );
		$query_vars = $this->get_query_vars(['offset' => $offset, 'number' => 10, 'fields' => ['customer_id', 'sum'], 'groupby' => 'customer_id', 'orderby' => 'sum', 'order' => 'DESC', 'type' => 'payment_received', 'customer_type' => 'company', 'conditions' => ['key' => 'customer_id', 'compare' => '>', 'value' => 0]]);
		$documents = usam_get_documents( $query_vars );			
		$results = [];
		if ( !empty($documents) )
		{
			$ids = [];
			$customers = [];
			foreach ( $documents as $document )
			{			
				$customers[$document->customer_id] = $document->sum;		
				$ids[] = $document->customer_id;
			}			
			$companies = usam_get_companies(['include' => $ids, 'orderby' => 'include']);		
			foreach ( $companies as $company )
			{
				$results[] = ['primary' => "<a href=".usam_get_company_url($company->id).">".$company->name."</a>", 'column' => usam_get_formatted_price( $customers[$company->id] ), 'id' => $company->id];
			}			
		}
		return $results;		
	}
	
	private function documents_paid_subscription( $offset )
	{					
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);			
			require_once( USAM_FILE_PATH . '/includes/document/subscription_renewal_query.class.php' );
			$ids = usam_get_subscription_renewal_query(['code' => $id, 'fields' => 'document_id', 'status' => 'paid']);				
			if ( !empty($ids) )
			{
				$documents = usam_get_documents(['offset' => $offset, 'include' => $ids, 'number' => 10, 'order' => 'DESC']);	
				foreach ( $documents as $document )
				{
					$results[] = ['primary' => "<a href='".usam_get_document_url( $document )."'>№ $document->number (".usam_get_formatted_price($document->totalprice, ['type_price' => $document->type_price]).")</a>" , 'column' => usam_local_date($document->date_insert), 'id' => $document->id];
				}
			}
		}
		return $results;
	}	
	
	private function order_products_viewed( $offset )
	{					
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);			
			require_once( USAM_FILE_PATH . '/includes/document/subscription_renewal_query.class.php' );
			$visit_id = usam_get_visits(['fields' => 'id', 'meta_key' => 'order_id', 'meta_value' => $id]);				
			if ( $visit_id )
			{
				require_once( USAM_FILE_PATH . '/includes/analytics/pages_viewed_query.class.php' );
				$viewed = usam_get_pages_viewed(['offset' => $offset, 'number' => 10, 'visit_id' => $visit_id, 'post-type' => 'usam-product', 'groupby' => 'post_id']);		
				$results = [];	
				foreach ( $viewed as $view )
				{
					$sku = "<div class='list_row__description'>".__('Артикул', 'usam').': <span class="js-copy-clipboard">'.usam_get_product_meta( $view->post_id, 'sku' ).'</span></div>';
					$results[] = ['primary' => "<a href='".get_edit_post_link( $view->post_id )."'>".usam_get_product_thumbnail($view->post_id, 'manage-products')."</a><a href='".get_edit_post_link($view->post_id)."' target='_blank'>".get_the_title( $view->post_id )."</a>$sku", 'column' => usam_get_product_price_currency( $view->post_id ), 'id' => $view->post_id];
				}
			}
		}
		return $results;
	}	
	
	private function order_category_viewed( $offset )
	{					
		$results = array();	
		if ( !empty($_POST['id']) )
		{
			$id = absint($_POST['id']);			
			require_once( USAM_FILE_PATH . '/includes/document/subscription_renewal_query.class.php' );
			$visit_id = usam_get_visits(['fields' => 'id', 'meta_key' => 'order_id', 'meta_value' => $id]);				
			if ( $visit_id )
			{
				require_once( USAM_FILE_PATH . '/includes/analytics/pages_viewed_query.class.php' );
				$viewed = usam_get_pages_viewed(['offset' => $offset, 'number' => 10, 'visit_id' => $visit_id, 'taxonomy' => 'usam-category', 'groupby' => 'term_id']);		
				$results = [];	
				foreach ( $viewed as $view )
				{
					$term = get_term($view->term_id, 'usam-category');
					$results[] = ['primary' => "<a href='".admin_url('edit.php?post_type=usam-product&category='.$view->term_id)."' target='_blank'>".$term->name."</a>", 'column' => '', 'id' => $view->term_id];
				}
			}
		}
		return $results;
	}

	private function number_downloads( $offset )
	{
		$args = $this->get_query_vars(['offset' => $offset, 'number' => 10, 'fields' => ['title', 'id','uploaded'], 'order' => 'DESC', 'orderby' => 'uploaded']);		
		$files = usam_get_files( $args );	
		$results = [];
		foreach ( $files as $file )
		{
			$results[] = ['primary' => '<a href="'.admin_url("admin.php?page=files&tab=files&form=view&form_name=file&id=".$file->id).'" target="_blank">'.$file->title.'</a>', 'column' => $file->uploaded, 'id' => $file->id];
		}
		return $results;
	}
}
?>