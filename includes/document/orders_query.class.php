<?php
// Класс работы с заказами 
class USAM_Orders_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $request;
	private $group_join = false;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'bank_account_id' => 'd', 'number' => 's', 'totalprice' => 'f', 'number_products' => 'd', 'cost_price' => 'f', 'type_price' => 's', 'paid' => 'd', 'date_paid' => 's', 'code' => 's', 'status' => 's', 'type_payer' => 's', 'user_ID' => 'd', 'contact_id' => 'd', 'company_id' => 'd', 'manager_id' => 'd', 'shipping' => 'f', 'date_insert' => 's', 'date_status_update' => 's', 'source' => 's'];

	// SQL clauses
	public $query_fields;
	public $query_from;
	public $query_join;	
	public $query_where;
	public $query_orderby;
	public $query_groupby;	 
	public $query_limit;
	public $meta_query;	
	public $date_query;	
	
	public function __construct( $query = null ) 
	{
		require_once( USAM_FILE_PATH . '/includes/query/date.php' );
		require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
		if ( !empty( $query ) ) 
		{
			$this->prepare_query( $query );
			$this->query();
		}
	}

	/**
	 * Fills in missing query variables with default values.
	 */
	public static function fill_query_vars( $args ) 
	{
		$defaults = array(
			'second' => '',
			'minute' => '',
			'hour' => '',
			'day' => '',
			'monthnum' => '',
			'year' => '',
			'w' => '',
			'paid' => '',	
			'source' => 'all',	
			'status' => '',
			'status__not_in' => 'delete',			
			'meta_key' => '',
			'meta_value' => '',
			'meta_compare' => '',
			'include' => array(),
			'exclude' => array(),
			'child_document' => array(),			
			'search' => '',
			'search_columns' => array(),
			'orderby' => 'id',
			'order' => 'ASC',
			'offset' => '',
			'number' => '',
			'paged' => 1,
			'count_total' => true,
			'cache_results' => false,		
			'cache_order_products' => false,		
			'cache_meta' => false,		
			'cache_order_shippeds' => false,	
			'cache_order_payments' => false,	
			'cache_contacts' => false,
			'cache_companies' => false,			
			'cache_managers' => false,			
			'fields' => 'all',	
			'add_fields' => '',					
			
		);
		return wp_parse_args( $args, $defaults );
	}
	
	public function prepare_query( $query = array() ) 
	{
		global $wpdb;
		
		if ( empty( $this->query_vars ) || !empty( $query ) ) {
			$this->query_limit = null;
			$this->query_vars = $this->fill_query_vars( $query );
		}			
		do_action( 'usam_pre_get_orders', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );
		
		$query_join_contacts = false;
		$query_join_contact_birthday = false;		
		$bonus_join = false;
		$this->group_join = false;
				
		$join = [];			
		$fields = is_array($qv['fields']) ? array_unique( $qv['fields'] ) : explode(',', $qv['fields']);
		if ( $qv['add_fields'] )
		{
			$add_fields = is_array($qv['add_fields']) ? array_unique( $qv['add_fields'] ) : explode(',', $qv['add_fields']);
			$fields = array_merge( $fields, $add_fields );	
		}		
		$this->fields = [];
		foreach ( $fields as $field ) 
		{		
			if ( $field == 'all' )
				$this->fields[] = USAM_TABLE_ORDERS.".*";	
			elseif ( $field == 'average_order' )
				$this->fields[] = "ROUND( SUM( ".USAM_TABLE_ORDERS.".totalprice)/COUNT(*),2) AS average_order";
			elseif ( $field == 'count' )
				$this->fields[] = "COUNT(*) AS count";
			elseif ( $field == 'user_login' )
				$this->fields[] = "( SELECT user_login FROM $wpdb->users WHERE ID = ".USAM_TABLE_ORDERS.".user_id ) AS user_login";
			elseif ( $field == 'sum' )				
				$this->fields[] = "SUM(".USAM_TABLE_ORDERS.".totalprice) AS sum";
			elseif ( $field == 'sum_paid' )				
				$this->fields[] = " SUM( IF(".USAM_TABLE_ORDERS.".paid=2,".USAM_TABLE_ORDERS.".totalprice,0) ) AS sum_paid";
			elseif ( $field == 'count_paid' )
				$this->fields[] = "SUM( IF(".USAM_TABLE_ORDERS.".paid=2, 1, 0) ) AS count_paid";				
			elseif ( $field == 'meta_value' )
				$this->fields[] = USAM_TABLE_ORDER_META.".meta_value";					
			elseif ( $field == 'last_comment' )	
			{
				$this->fields[] = "comment.message AS last_comment";	
				$this->fields[] = "comment.user_id AS last_comment_user";	
				$this->fields[] = "comment.date_insert AS last_comment_date";						
				$join[] = " LEFT JOIN ".USAM_TABLE_COMMENTS." AS comment ON (comment.object_id=".USAM_TABLE_ORDERS.".id AND comment.object_type='order' AND comment.id = (SELECT id FROM ".USAM_TABLE_COMMENTS." WHERE object_type='order' AND status!=1 AND object_id=".USAM_TABLE_ORDERS.".id ORDER BY id DESC LIMIT 1 ))";
			}
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_ORDERS.".$field";
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_ORDERS.".*";
		else
			$this->query_fields = implode( ',', $this->fields );	
		if ( isset($qv['count_total']) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_ORDERS;
		$this->query_where = "WHERE 1=1";		
		
	// Обрабатывать другие параметры даты
		$date_parameters = array();
		foreach( ['second', 'minute', 'hour', 'day', 'w', 'monthnum', 'year'] as $k ) 
		{					
			if ( $qv[$k] !== '' )
				$date_parameters[$k] = $qv[$k];
		}
		if ( $date_parameters ) 
		{
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_ORDERS );
			$this->query_where .= $date_query->get_sql();
		} 
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_ORDERS );
			$this->query_where .= $this->date_query->get_sql();
		}
		if ( !empty($qv['status_type']) )
		{
			$this->query_join .= " INNER JOIN ".USAM_TABLE_OBJECT_STATUSES." ON (".USAM_TABLE_OBJECT_STATUSES.".internalname=".USAM_TABLE_ORDERS.".status AND ".USAM_TABLE_OBJECT_STATUSES.".type='order')";
			if ( $qv['status_type'] == 'unclosed' )
				$this->query_where .= " AND ".USAM_TABLE_OBJECT_STATUSES.".close=0";
			elseif ( $qv['status_type'] == 'closed' )
				$this->query_where .= " AND ".USAM_TABLE_OBJECT_STATUSES.".close=1";
		}
		if ( !empty($qv['status']) ) 
		{
			if ( is_array( $qv['status'] ) ) 
				$status = $qv['status'];			
			elseif ( is_string($qv['status']) && !empty($qv['status']) )
			{
				if ( $qv['status'] != 'all' )
					$status = array_map( 'trim', explode( ',', $qv['status'] ) );
			}
			elseif ( is_numeric($qv['status']) )
				$status = array($qv['status']);
				
			if ( !empty($status) ) 
			{
				$this->query_where .= " AND ".USAM_TABLE_ORDERS.".status IN ('".implode( "','",  $status )."')";	
				$qv['status__not_in'] = '';
			}
		}
		if ( $qv['status__not_in'] ) 
		{
			if ( is_string($qv['status__not_in']) && $qv['status__not_in'] != 'delete' || empty($qv['include']) )
			{
				$status__not_in = (array) $qv['status__not_in'];
				$this->query_where .= " AND ".USAM_TABLE_ORDERS.".status NOT IN ('".implode("','",  $status__not_in )."')";
			}
		}
		if ( !empty($qv['manager_id']) ) 
		{
			if ( is_array( $qv['manager_id'] ) ) 
				$manager_id = $qv['manager_id'];			
			elseif ( is_string($qv['manager_id']) && !empty($qv['manager_id']) )
			{
				if ( $qv['manager_id'] != 'all' )
					$manager_id = array_map( 'intval', explode( ',', $qv['manager_id'] ) );
			}
			elseif ( is_numeric($qv['manager_id']) )
				$manager_id = array($qv['manager_id']);
				
			if ( !empty($manager_id) ) 
				$this->query_where .= " AND ".USAM_TABLE_ORDERS.".manager_id IN (".implode( ",", $manager_id ).")";	
		}	
		if ( isset($qv['manager_id__not_in']) ) 
		{
			$manager_id__not_in = (array)$qv['manager_id__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".manager_id NOT IN (".implode(",", $manager_id__not_in ).")";
		}
		if ( !empty($qv['code']) ) 
		{
			if ( is_array( $qv['code'] ) ) 
				$code = $qv['code'];			
			elseif ( is_string($qv['code']) && !empty($qv['code']) )
			{
				if ( $qv['code'] != 'all' )
					$code = array_map( 'trim', explode( ',', $qv['code'] ) );
			}
			elseif ( is_numeric($qv['code']) )
				$code = array($qv['code']);
				
			if ( !empty($code) ) 
				$this->query_where .= " AND ".USAM_TABLE_ORDERS.".code IN ('".implode( "','", $code )."')";	
		}	
		if ( isset($qv['code__not_in']) ) 
		{
			$code__not_in = (array)$qv['code__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".code NOT IN ('".implode("','", $code__not_in )."')";
		}
		// Группировать
		$qv['groupby'] = isset($qv['groupby'] ) ? $qv['groupby'] : '';		
		if ( $qv['groupby'] != '' )
		{
			$timezone = wp_timezone();
			$name_timezone = $timezone->getName();		
			if ( is_array($qv['groupby']) )					
				$groupby = $qv['groupby'];					
			else
				$groupby[] = $qv['groupby'];
			$ordersby_array = array();		
			foreach ( $groupby as $_value ) 
			{				
				switch ( $_value ) 
				{
					case 'date_paid_day' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "DAY(CONVERT_TZ(date_paid,'UTC','$name_timezone')))";
							$ordersby_array[] = "MONTH(CONVERT_TZ(date_paid,'UTC','$name_timezone')))";
							$ordersby_array[] = "YEAR((CONVERT_TZ(date_paid,'UTC','$name_timezone')))";						
						}
						else
						{
							$ordersby_array[] = "DAY(date_paid)";
							$ordersby_array[] = "MONTH(date_paid)";
							$ordersby_array[] = "YEAR(date_paid)";								
						}
					break;
					case 'date_paid_week' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "WEEKOFYEAR(CONVERT_TZ(date_paid,'UTC','$name_timezone'))";
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_paid,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "WEEKOFYEAR(date_paid)";
							$ordersby_array[] = "YEAR(date_paid)";		
						}										
					break;	
					case 'date_paid_month' :					
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "MONTH(CONVERT_TZ(date_paid,'UTC','$name_timezone'))";
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_paid,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "MONTH(date_paid)";
							$ordersby_array[] = "YEAR(date_paid)";		
						}	
					break;					
					case 'date_paid_year' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_paid,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "YEAR(date_paid)";		
						}	
					break;
					case 'date_status_update_day' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "DAY(CONVERT_TZ(date_status_update,'UTC','$name_timezone')))";
							$ordersby_array[] = "MONTH(CONVERT_TZ(date_status_update,'UTC','$name_timezone')))";
							$ordersby_array[] = "YEAR((CONVERT_TZ(date_status_update,'UTC','$name_timezone')))";						
						}
						else
						{
							$ordersby_array[] = "DAY(date_status_update)";
							$ordersby_array[] = "MONTH(date_status_update)";
							$ordersby_array[] = "YEAR(date_status_update)";								
						}
					break;
					case 'date_status_update_week' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "WEEKOFYEAR(CONVERT_TZ(date_status_update,'UTC','$name_timezone'))";
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_status_update,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "WEEKOFYEAR(date_status_update)";
							$ordersby_array[] = "YEAR(date_status_update)";		
						}										
					break;	
					case 'date_status_update_month' :					
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "MONTH(CONVERT_TZ(date_status_update,'UTC','$name_timezone'))";
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_status_update,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "MONTH(date_status_update)";
							$ordersby_array[] = "YEAR(date_status_update)";		
						}	
					break;					
					case 'date_status_update_year' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_status_update,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "YEAR(date_status_update)";		
						}	
					break;
					case 'day' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "DAY(CONVERT_TZ(date_insert,'UTC','$name_timezone')))";
							$ordersby_array[] = "MONTH(CONVERT_TZ(date_insert,'UTC','$name_timezone')))";
							$ordersby_array[] = "YEAR((CONVERT_TZ(date_insert,'UTC','$name_timezone')))";						
						}
						else
						{
							$ordersby_array[] = "DAY(date_insert)";
							$ordersby_array[] = "MONTH(date_insert)";
							$ordersby_array[] = "YEAR(date_insert)";								
						}
					break;
					case 'week' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "WEEKOFYEAR(CONVERT_TZ(date_insert,'UTC','$name_timezone'))";
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_insert,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "WEEKOFYEAR(date_insert)";
							$ordersby_array[] = "YEAR(date_insert)";		
						}										
					break;	
					case 'month' :					
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "MONTH(CONVERT_TZ(date_insert,'UTC','$name_timezone'))";
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_insert,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "MONTH(date_insert)";
							$ordersby_array[] = "YEAR(date_insert)";		
						}	
					break;					
					case 'year' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_insert,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "YEAR(date_insert)";		
						}	
					break;					
					default:
						$ordersby_array[] = $_value;
				}		
			}	
		}		
		if ( !empty($ordersby_array) )
			$this->query_groupby = 'GROUP BY ' . implode( ', ', $ordersby_array );
		else
			$this->query_groupby = '';	
		
		// СОРТИРОВКА
		$qv['order'] = isset($qv['order'] ) ? strtoupper( $qv['order'] ) : '';
		$order = $this->parse_order( $qv['order'] );

		if ( empty( $qv['orderby'] ) ) 
		{	// Default order is by 'id'.			
			$ordersby = array( 'id' => $order );
		} 
		elseif ( is_array( $qv['orderby'] ) ) 
		{
			$ordersby = $qv['orderby'];
		} 
		else 
		{	// Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
		}	
		$orderby_array = array();
		foreach ( $ordersby as $_key => $_value ) 
		{
			if ( ! $_value ) {
				continue;
			}			
			if ( is_int( $_key ) ) 
			{	// Integer key means this is a flat array of 'orderby' fields.
				$_orderby = $_value;
				$_order = $order;
			} 
			else 
			{ // Non-integer key means this the key is the field and the value is ASC/DESC.
				$_orderby = $_key;
				$_order = $_value;
			}
			$parsed = $this->parse_orderby( $_orderby );

			if ( ! $parsed ) {
				continue;
			}
			$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
		}
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = USAM_TABLE_ORDERS.".id $order";		}

		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );
		
		if ( isset($qv['number'] ) && $qv['number'] > 0 ) 
		{
			if ( $qv['offset'] ) 
				$this->query_limit = $wpdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
			else 
				$this->query_limit = $wpdb->prepare( "LIMIT %d, %d", $qv['number'] * ( $qv['paged'] - 1 ), $qv['number'] );
		} 
		$search = '';
		if ( isset($qv['search'] ) )
			$search = trim( $qv['search'] );

		if ( $search ) 
		{
			$leading_wild = ( ltrim($search, '*') != $search );
			$trailing_wild = ( rtrim($search, '*') != $search );
			if ( $leading_wild && $trailing_wild )
				$wild = 'both';
			elseif ( $leading_wild )
				$wild = 'leading';
			elseif ( $trailing_wild )
				$wild = 'trailing';
			else
				$wild = false;
			if ( $wild )
				$search = trim($search, '*');

			$search_columns = array();
			if ( $qv['search_columns'] )
				$search_columns = array_intersect( $qv['search_columns'], ['id', 'number','code','product_sku', 'product_name', 'user_login', 'customer_name', 'shippingaddress', 'company', 'billingemail', 'company_email']);
		
			if ( ! $search_columns ) 
			{
				if ( is_email( $search ) )
					$search_columns = array('billingemail', 'company_email');
				elseif ( is_numeric($search) )
					$search_columns = ['id', 'code', 'user_id', 'number', 'billingphone', 'billingmobilephone', 'product_sku', 'user_login'];
				elseif ( stripos($search, ' ') !== false )
					$search_columns = ['company', 'product_name', 'customer_name', 'shippingaddress'];
				else
					$search_columns = ['product_sku', 'number', 'code', 'product_name', 'billingfirstname', 'billinglastname', 'shippingaddress', 'company', 'user_login'];
			}
			$search_columns = apply_filters( 'usam_orders_search_columns', $search_columns, $search, $this );
			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}
		if ( !empty( $qv['include'] ) ) 
			$include = wp_parse_id_list( $qv['include'] );
		else 
			$include = false;
		
		if ( !empty( $include ) ) 
		{			
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".id NOT IN ($ids)";
		}					
		if ( !empty( $qv['type_prices'] ) ) 
		{
			$type_prices = implode( "','",  (array)$qv['type_prices'] );
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".type_price IN ('$type_prices')";				
		}	
		if ( isset($qv['user_id']) ) 
		{		
			$user_id = implode( ',',  (array)$qv['user_id'] );		
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".user_id IN ($user_id)";
		}	
		if ( isset($qv['contacts']) ) 
		{		
			$contact_ids = implode( ',',  array_map( 'intval',(array)$qv['contacts']) );		
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".contact_id IN ($contact_ids)";
		}
		if ( isset($qv['contacts__not_in']) ) 
		{			
			$contacts = implode( ',',  array_map( 'intval',(array)$qv['contacts__not_in']) );								
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".contact_id NOT IN ($contacts)";
		}			
		if ( isset($qv['companies']) ) 
		{			
			$companies = implode( ',',  array_map( 'intval',(array)$qv['companies']) );								
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".company_id IN ($companies)";
		}	
		if ( isset($qv['companies__not_in']) ) 
		{			
			$companies = implode( ',',  array_map( 'intval',(array)$qv['companies__not_in']) );								
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".company_id NOT IN ($companies)";
		}				
		if ( isset($qv['user_login'] ) && $qv['user_login'] != '' ) 
		{	
			$user = get_user_by('login', $qv['user_login'] );			
			if ( !empty($user->ID) && is_numeric( $user->ID ) )
				$this->query_where .= " AND ".USAM_TABLE_ORDERS.".user_ID = {$user->ID}";
			else
				$this->query_where .= " AND ".USAM_TABLE_ORDERS.".user_ID = '&' ";
		}	
		if ( !empty($qv['document_discount']) ) 
		{		
			$document_discount = implode( ',',  (array)$qv['document_discount'] );
			$this->query_join .= " INNER JOIN ".USAM_TABLE_DOCUMENT_DISCOUNTS." ON (".USAM_TABLE_DOCUMENT_DISCOUNTS.".document_id = ".USAM_TABLE_ORDERS.".id AND ".USAM_TABLE_DOCUMENT_DISCOUNTS.".document_type='order')";
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENT_DISCOUNTS.".id IN ($document_discount)"; 			
		}	
		if ( !empty($qv['newsletter']) ) 
		{		
			$newsletter = implode( ',',  (array)$qv['newsletter'] );		
			$query_join_contacts = true;
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CONTACT_META." ON (".USAM_TABLE_CONTACT_META.".contact_id = ".USAM_TABLE_CONTACTS.".id)";
			$this->query_join .= " INNER JOIN ".USAM_TABLE_NEWSLETTER_USER_STAT." ON (".USAM_TABLE_NEWSLETTER_USER_STAT.".communication = ".USAM_TABLE_CONTACT_META.".meta_value AND ".USAM_TABLE_ORDERS.".date_insert>".USAM_TABLE_NEWSLETTER_USER_STAT.".sent_at)";
			$this->query_where .= " AND ".USAM_TABLE_NEWSLETTER_USER_STAT.".newsletter_id IN(".$newsletter.")"; 
			if ( strripos($this->query_fields, 'DISTINCT')  === false )
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
			
		/*	$this->query_join .= " INNER JOIN ".USAM_TABLE_CONTACTS." ON (".USAM_TABLE_CONTACTS.".communication = ".USAM_TABLE_ORDERS.".id)";
			$this->query_join .= " INNER JOIN ".USAM_TABLE_NEWSLETTER_USER_STAT." ON (".USAM_TABLE_NEWSLETTER_USER_STAT.".communication = ".USAM_TABLE_CONTACT_META.".id)";
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CONTACT_META." ON (".USAM_TABLE_CONTACT_META.".communication = ".USAM_TABLE_CONTACTS.".id)";
			*/				
		}	
		if( !empty($qv['child_document']['id']) && !empty($qv['child_document']['type'])) 
		{
			$this->query_join .= " INNER JOIN ".USAM_TABLE_DOCUMENT_LINKS." ON (".USAM_TABLE_DOCUMENT_LINKS.".document_id = ".USAM_TABLE_ORDERS.".id AND ".USAM_TABLE_DOCUMENT_LINKS.".document_type='order' AND ".USAM_TABLE_DOCUMENT_LINKS.".link_type='link')";	
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENT_LINKS.".document_link_type='".$qv['child_document']['type']."' AND ".USAM_TABLE_DOCUMENT_LINKS.".document_link_id='".$qv['child_document']['id']."'";			
		}
		if ( !empty($qv['status_contacts']) ) 
		{					
			$query_join_contacts = true;
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".status IN('".implode( "','",  (array)$qv['status_contacts'] )."')";
		}
		if ( !empty($qv['source_contacts']) ) 
		{					
			$query_join_contacts = true;			
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".contact_source IN('".implode( "','",  (array)$qv['source_contacts'] )."')";
		}
		if ( !empty($qv['groups_contacts']) ) 
		{					
			$query_join_contacts = true;
			$this->query_where .= " AND ".USAM_TABLE_GROUP_RELATIONSHIPS.".group_id IN('".implode( "','",  (array)$qv['groups_contacts'] )."')";
		}
		if ( !empty($qv['gender_contacts']) ) 
		{							
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CONTACT_META." AS sex ON (sex.contact_id = ".USAM_TABLE_ORDERS.".contact_id AND sex.meta_key='sex')";
			$this->query_where .= " AND sex.meta_value IN('".implode( "','",  (array)$qv['gender_contacts'] )."')";
		}
		if ( !empty($qv['company_contacts']) ) 
		{					
			$query_join_contacts = true;
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".company_id IN('".implode( "','",  (array)$qv['company_contacts'] )."')";
		}	
		if ( !empty($qv['from_age_contacts']) ) 
		{					
			$query_join_contact_birthday = true;
			$y = date('Y') - absint($qv['from_age_contacts']);
			$this->query_where .= " AND birthday.meta_value>='".date( "Y-m-d H:i:s",mktime(0, 0, 0, 1, 1,$y))."'";
		}	
		if ( !empty($qv['to_age_contacts']) ) 
		{					
			$query_join_contact_birthday = true;
			$y = date('Y') - absint($qv['to_age_contacts']);
			$this->query_where .= " AND birthday.meta_value<='".date( "Y-m-d H:i:s",mktime(0, 0, 0, 1, 1,$y))."'";
		}					
		if ( $query_join_contacts ) 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CONTACTS." ON (".USAM_TABLE_CONTACTS.".id = ".USAM_TABLE_ORDERS.".contact_id)";
		if ( $query_join_contact_birthday ) 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CONTACT_META." AS birthday ON (birthday.contact_id = ".USAM_TABLE_ORDERS.".contact_id AND birthday.meta_key='birthday')";
		if ( !empty($qv['groups_contacts']) ) 
		{		
			$this->query_join .= " INNER JOIN ".USAM_TABLE_GROUP_RELATIONSHIPS." ON (".USAM_TABLE_GROUP_RELATIONSHIPS.".object_id = ".USAM_TABLE_CONTACTS.".id)";
		}
		if ( isset($qv['payment_gateway']) )
		{		
			$payment_gateway = implode( ',',  (array)$qv['payment_gateway'] );		
			$this->query_join .= " INNER JOIN ".USAM_TABLE_PAYMENT_HISTORY." ON (".USAM_TABLE_PAYMENT_HISTORY.".document_id = ".USAM_TABLE_ORDERS.".id)";
			$this->query_where .= " AND ".USAM_TABLE_PAYMENT_HISTORY.".gateway_id IN ($payment_gateway)"; 
		}		
		if ( !empty($qv['shipping_method']) || !empty($qv['shipping_storage']) || !empty($qv['storage_pickup']) ) 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_SHIPPED_DOCUMENTS." ON (".USAM_TABLE_SHIPPED_DOCUMENTS.".order_id = ".USAM_TABLE_ORDERS.".id)";
		if ( !empty( $qv['shipping_method'] ) ) 
		{		
			$shipping_method = implode( ',',  (array)$qv['shipping_method'] );			
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".method IN ($shipping_method)"; 
		}			
		if ( !empty( $qv['shipping_storage'] ) ) 
		{		
			$shipping_storage = implode( ',',  (array)$qv['shipping_storage'] );				
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".storage IN ($shipping_storage)"; 
		}	
		if ( !empty( $qv['storage_pickup'] ) ) 
		{		
			$storage_pickup = implode( ',',  (array)$qv['storage_pickup'] );					
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".storage_pickup IN ($storage_pickup)"; 
		}		
		if ( !empty( $qv['bank_account_id'] ) ) 
		{		
			$bank_account_id = implode( ',',  (array)$qv['bank_account_id'] );					
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".bank_account_id IN ($bank_account_id)"; 
		}			
		if ( $qv['paid'] !== '' ) 
		{
			$paid = implode( ',',  (array)$qv['paid'] );
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".paid IN ($paid)"; 
		}	
		if ( !empty($qv['exchange']) ) 
		{
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_ORDER_META." AS date_exchange ON (date_exchange.order_id = ".USAM_TABLE_ORDERS.".id AND date_exchange.meta_key ='date_exchange')";		
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_ORDER_META." AS exchange ON (exchange.order_id = ".USAM_TABLE_ORDERS.".id AND exchange.meta_key ='exchange')";	
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_ORDER_META." AS exchange1 ON (exchange1.order_id = ".USAM_TABLE_ORDERS.".id AND exchange1.meta_key ='exchange')";
			$this->query_where .= " AND (".USAM_TABLE_ORDERS.".date_status_update>CAST(date_exchange.meta_value AS DATETIME) OR exchange.meta_key IS NULL OR exchange1.meta_value=0)"; 
		}
		$group = array();
		if ( isset($qv['group'] ) ) 
		{
			if ( is_array( $qv['group'] ) ) 
				$group = $qv['group'];			
			elseif ( is_string( $qv['group'] ) && !empty( $qv['group'] ) )
				$group = array_map( 'trim', explode( ',', $qv['group'] ) );
		}
		$group__not_in = array();
		if ( isset($qv['group__not_in'] ) ) {
			$group__not_in = (array) $qv['group__not_in'];
		}		
		if ( !empty($group) ) 
		{				
			$this->group_join = true;
			$this->query_where .= " AND ".USAM_TABLE_GROUP_RELATIONSHIPS.".group_id IN (".implode( ',',  $group ).")";		
		}		
		if ( !empty($group__not_in) ) 
		{			
			$this->group_join = true;
			$this->query_where .= " AND ".USAM_TABLE_GROUP_RELATIONSHIPS.".group_id NOT IN (".implode( ',',  $group ).")";		
		}				
		if ( !empty($qv['type_payer']) ) 
		{
			$type_payer = implode( "','",  (array)$qv['type_payer'] );
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".type_payer IN ('$type_payer')";
		}		
		if ( $qv['source'] != 'all' ) 
		{
			$source = implode( "','",  (array)$qv['source'] );
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".source IN ('$source')"; 
		}			
		if ( !empty($qv['brands']) || !empty($qv['categories']) )
		{			
			$this->query_join .= " INNER JOIN {$wpdb->prefix}term_relationships AS tr INNER JOIN {$wpdb->prefix}term_taxonomy AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";
			$this->query_join .=  " INNER JOIN ".USAM_TABLE_PRODUCTS_ORDER." ON (".USAM_TABLE_PRODUCTS_ORDER.".order_id=".USAM_TABLE_ORDERS.".id)";
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_ORDER.".product_id=tr.object_id";
			if ( strripos($this->query_fields, 'DISTINCT') === false )
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
		}
		if ( !empty($qv['brands']) )
		{			
			$brands = array_map('intval', (array)$qv['brands']);
			$category_children = get_option( 'usam-brands_children', []);
			$selected = [];
			foreach ( $brands as $id )
			{
				$selected[] = $id;
				if ( isset($category_children[$id]) )
					$selected = array_merge( $selected, $category_children[$id] );	
			}		
			$selected = array_unique($selected);
			$this->query_where .= " AND tt.term_id IN (".implode( ',', $selected ).")"; 
		}	
		if ( !empty($qv['categories']) )
		{			
			$categories = array_map('intval', (array)$qv['categories']);
			$category_children = get_option( 'usam-category_children', []);
			$selected = [];
			foreach ( $categories as $id )
			{
				$selected[] = $id;				
				if ( isset($category_children[$id]) )
					$selected = array_merge( $selected, $category_children[$id] );	
			}		
			$selected = array_unique($selected);
			$this->query_where .= " AND tt.term_id IN (".implode( ',', $selected ).")";
		}
		if ( !empty($qv['conditions']) ) 
		{
			if ( isset($qv['conditions']['key']) )
				$qv['conditions'] = array($qv['conditions']);
			foreach ( $qv['conditions'] as $condition )
			{				
				$select = '';
				$condition['type'] = !empty($condition['type'])? $condition['type'] : 'NUMERIC';
				switch ( $condition['key'] )
				{													
					case 'code' :
					case 'date_insert' :
						$select = $condition['key'];	
						$condition['type'] = 'CHAR';						
					break;								
					case 'sum' :
					case 'totalprice' :
						$select = USAM_TABLE_ORDERS.".totalprice";					
					break;
					case 'number_products' :
					case 'prod' :
						$select = "number_products";			
					break;								
					case 'bonus' :								
						$select = USAM_TABLE_BONUS_TRANSACTIONS.".sum";		
						$bonus_join = true;
					break;	
					case 'tax' :						
						$select = "(SELECT SUM(tax) AS tax FROM ".USAM_TABLE_TAX_PRODUCT_ORDER." AS t WHERE t.order_id = ".USAM_TABLE_ORDERS.".id )";		
					break;					
					case 'discount' :
						$select = "discount";			
					break;			
					default:
						$select = USAM_TABLE_ORDERS.'.'.$condition['key'];	
					break;	
				}
				if ( $select == '' )
					continue;
								
				if ( ! in_array( $condition['compare'], array(
					'=', '!=', '>', '>=', '<', '<=',
					'LIKE', 'NOT LIKE',
					'IN', 'NOT IN',
					'BETWEEN', 'NOT BETWEEN',
					'EXISTS', 'NOT EXISTS',
					'REGEXP', 'NOT REGEXP', 'RLIKE'
				) ) ) 
				{
					$condition['compare'] = '=';
				}				
				$compare = $condition['compare'];	
				switch ( $condition['compare'] ) 
				{					
					case 'LIKE' :					
					case 'NOT LIKE' :
						$value = "('%".$condition['value']."%')";			
					break;	
					case 'IN' :					
					case 'NOT IN' :
						$value = "('".implode("','",$condition['value'])."')";
					break;
					default:						
						if ( $condition['type'] == 'NUMERIC' )
							$value = $condition['value'];
						else
							$value = "'".$condition['value']."'";
					break;					
				}			
				if ( empty($condition['relation']) )
					$relation = 'AND';
				else
					$relation = $condition['relation'];			
				$this->query_where .= " {$relation} ({$select} {$compare} {$value}) ";
			}			
		}
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty( $this->meta_query->queries ) ) 
		{
			$clauses = $this->meta_query->get_sql( 'order', USAM_TABLE_ORDER_META, USAM_TABLE_ORDERS, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];
			if ( $this->meta_query->has_or_relation() && strripos($this->query_fields, 'DISTINCT')  === false )
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
		}
		if ( $this->group_join  ) 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_GROUP_RELATIONSHIPS." ON (".USAM_TABLE_GROUP_RELATIONSHIPS.".object_id = ".USAM_TABLE_ORDERS.".id)";
		if ( $bonus_join )						
			$this->query_join .=  " INNER JOIN ".USAM_TABLE_BONUS_TRANSACTIONS." ON (".USAM_TABLE_BONUS_TRANSACTIONS.".order_id=".USAM_TABLE_ORDERS.".id)";		
		do_action_ref_array( 'usam_pre_orders_query', array( &$this ) );
	}
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( isset($data->$column) )
			{
				if ( $type == 'd' )
					$data->$column = (int)$data->$column;
				elseif ( $type == 'f' )
					$data->$column = (float)$data->$column;
			}
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_order' );	
		return $data;
	}
	
	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;
		$this->request = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";		
		$count_fields = count($this->fields);
		if ( $count_fields > 1 || $qv['fields'] == 'all' && $qv['number'] != 1 ) 
		{
			$this->results = $wpdb->get_results( $this->request );		
			foreach ($this->results as $k => $result ) 
			{
				$this->results[$k] = $this->handling_data_types( $result );
			}
		}				
		elseif ( $qv['number'] == 1 && $qv['fields'] == 'all' )
		{
			$this->results = $wpdb->get_row( $this->request );
			$this->results = (array)$this->handling_data_types( $this->results );
		}
		elseif ( $qv['number'] == 1 && $count_fields == 1 )		
			$this->results = $wpdb->get_var( $this->request );
		else 
		{
			$this->results = $wpdb->get_col( $this->request );
		}
		if ( !$this->results )
			return;
		
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->total = (int)$wpdb->get_var( 'SELECT FOUND_ROWS()' );		
		if ( $qv['cache_order_products'] || $qv['cache_meta'] || $qv['cache_order_shippeds'] || $qv['cache_order_payments'] )
		{
			$ids = array();
			if ( $qv['number'] == 1 )
				$ids[] = $this->results->id;
			elseif ( is_string($qv['fields']) && 'all' != $qv['fields'] ) 	
				$ids[] = $this->results;
			else
			{					
				foreach ( $this->results as $order ) 
				{
					if ( !isset($order->id) )
						break;
					$ids[] = $order->id; 					
				}	
			}			
			update_order_cache( $ids, $qv['cache_meta'], $qv['cache_order_shippeds'], $qv['cache_order_payments'], $qv['cache_order_products'] );
		}	
		if ( $qv['cache_contacts'] )
		{
			$ids = array();	
			foreach ( $this->results as $result ) 
			{
				if ( !empty($result->contact_id) )
					$ids[] = $result->contact_id; 					
			}				
			if ( !empty($ids) )			
				usam_get_contacts(['include' => $ids, 'cache_results' => true, 'cache_thumbnail' => true, 'source' => 'all']);
		}	
		if ( $qv['cache_companies'] )
		{
			$ids = array();	
			foreach ( $this->results as $result ) 
			{
				if ( !empty($result->company_id) )
					$ids[] = $result->company_id; 					
			}				
			if ( !empty($ids) )			
				usam_get_companies(['include' => $ids, 'cache_results' => true, 'cache_thumbnail' => true]);
		}	
		if ( $qv['cache_managers'] )
		{
			$ids = array();	
			foreach ( $this->results as $result ) 
			{
				if ( !empty($result->manager_id) )
					$ids[$result->manager_id] = $result->manager_id; 					
			}				
			if ( !empty($ids) )			
				usam_get_contacts(['user_id' => $ids, 'cache_results' => true, 'cache_thumbnail' => true, 'source' => 'all']);			
		}		
	}
	
	public function get( $query_var ) 
	{
		if ( isset($this->query_vars[$query_var] ) )
			return $this->query_vars[$query_var];

		return null;
	}
	
	public function set( $query_var, $value )
	{
		$this->query_vars[$query_var] = $value;
	}

	protected function get_search_sql( $string, $cols, $wild = false ) 
	{
		global $wpdb;

		$searches = array();
		$leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
		$like = $leading_wild . $wpdb->esc_like( $string ) . $trailing_wild;			
		$string = addslashes( $string );
		
		foreach ( $cols as $col )
		{			
			if ( 'id' == $col || 'user_id' == $col ) 
				$searches[] = $wpdb->prepare( USAM_TABLE_ORDERS.".$col = %d", $string );
			elseif ( 'number' == $col ) 
				$searches[] = USAM_TABLE_ORDERS.".$col LIKE LOWER ('%{$like}')";
			elseif ( 'code' == $col ) 
			{
				$searches[] = USAM_TABLE_ORDERS.".$col='{$string}'";
			}			
			elseif ( 'product_sku' == $col ) 
			{ 
				$product_id = usam_get_product_id_by_sku( $string );		
				if ( !empty($product_id) )
				{
					$searches[] = USAM_TABLE_ORDERS.".id IN ( SELECT DISTINCT order_id FROM ".USAM_TABLE_PRODUCTS_ORDER." WHERE product_id ='$product_id' )";	
				}
			}
			elseif ( 'product_name' == $col ) 
			{
				$searches[] = USAM_TABLE_ORDERS.".id IN ( SELECT DISTINCT order_id FROM ".USAM_TABLE_PRODUCTS_ORDER." WHERE name LIKE LOWER ('%{$string}%') )";			
			}
			elseif ( 'user_login' == $col ) 
			{
				$user = get_user_by('login', $string );
				if ( !empty($user->ID) )
					$searches[] = USAM_TABLE_ORDERS.".user_ID ={$user->ID}";
			}					
			elseif( 'billingemail' == $col || 'company_email' == $col || 'billingphone' == $col || 'billingmobilephone' == $col || 'company' == $col || 'billingfirstname' == $col || 'billinglastname' == $col )
			{				
				$table_as = 's'.$col;					
				$this->query_join .= $wpdb->prepare(" LEFT OUTER JOIN ".USAM_TABLE_ORDER_META." AS {$table_as} ON {$table_as}.order_id = ".USAM_TABLE_ORDERS.".id AND {$table_as}.meta_key ='%s'", $col);
				$searches[] = $wpdb->prepare($table_as . ".meta_value LIKE LOWER ('%s')", $like );
			}		
			elseif ( 'customer_name' == $col ) 
			{									
				$names = explode(' ', $like);			
				$this->query_join .= " LEFT OUTER JOIN ".USAM_TABLE_ORDER_META." AS name1 ON (name1.order_id = ".USAM_TABLE_ORDERS.".id AND name1.meta_key ='billinglastname')";
				$this->query_join .= " LEFT OUTER JOIN ".USAM_TABLE_ORDER_META." AS name2 ON (name2.order_id = ".USAM_TABLE_ORDERS.".id AND name2.meta_key ='billingfirstname')";
				$searches[] = $wpdb->prepare("name1.meta_value LIKE LOWER ('%s') AND name2.meta_value LIKE LOWER ('%s')", $names[0], $names[1] );								
				$searches[] = $wpdb->prepare("name1.meta_value LIKE LOWER ('%s') AND name2.meta_value LIKE LOWER ('%s')", $names[1], $names[0] );					
			} 
			elseif ( 'shippingaddress' == $col )
			{				
				$table_as = 's'.$col;					
				$this->query_join .= $wpdb->prepare(" LEFT OUTER JOIN ".USAM_TABLE_ORDER_META." AS {$table_as} ON {$table_as}.order_id=".USAM_TABLE_ORDERS.".id AND {$table_as}.meta_key ='%s'", $col);
				$searches[] = $wpdb->prepare($table_as . ".meta_value LIKE LOWER ('%s')", $like );
			}	
			else 
			{
				$searches[] = $wpdb->prepare( USAM_TABLE_ORDERS.".$col LIKE %s", $like );
			}
		}  
		return ' AND (' . implode(' OR ', $searches) . ')';
	}
	
	public function get_results() 
	{
		return $this->results;
	}

	/**
	 * Вернуть общее количество записей для текущего запроса.
	 */
	public function get_total() 
	{
		return $this->total;
	}
	
	public function get_total_amount() 
	{
		global $wpdb;
		
		$request = "SELECT SUM(".USAM_TABLE_ORDERS.".totalprice) AS sum $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby"; 
		$total_amount = (float)$wpdb->get_var( $request );
		return $total_amount;
	}

	/**
	 * Разобрать и очистить ключи 'orderby'
	 */
	protected function parse_orderby( $orderby ) 
	{
		global $wpdb;

		$_orderby = '';		
		if ( isset($this->db_fields[$orderby]) )
			$_orderby = USAM_TABLE_ORDERS.'.'.$orderby;	 
		elseif ( 'date' == $orderby ) 
			$_orderby = USAM_TABLE_ORDERS.'.date_insert';
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) ) 
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_ORDERS.".id, $include_sql )";
		} 
		elseif( 'count' == $orderby || 'sum' == $orderby )
		{
			$_orderby = $orderby;
		}
		return $_orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 */
	protected function parse_order( $order ) 
	{
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

	/**
	 * Make private properties readable for backwards compatibility.
	 */
	public function __get( $name ) 
	{
		if ( in_array( $name, $this->compat_fields ) ) {
			return $this->$name;
		}
	}

	/**
	 * Make private properties settable for backwards compatibility.
	 */
	public function __set( $name, $value ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			return $this->$name = $value;
		}
	}

	/**
	 * Make private properties checkable for backwards compatibility.
	 */
	public function __isset($name ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			return isset($this->$name );
		}
	}

	/**
	 * Make private properties un-settable for backwards compatibility.
	 */
	public function __unset( $name ) {
		if ( in_array( $name, $this->compat_fields ) ) {
			unset( $this->$name );
		}
	}

	/**
	 * Make private/protected methods readable for backwards compatibility.
	 */
	public function __call( $name, $arguments ) {
		if ( 'get_search_sql' === $name ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}
		return false;
	}
}

// Кешировать документы заказа
function update_order_cache( $object_ids, $cache_meta, $cache_shipped, $cache_payment, $cache_products )
{		
	if ( empty($object_ids) )
		return false;
	
	$tables = [];	
	if ( $cache_meta )
		$tables[USAM_TABLE_ORDER_META] = 'order_meta';	
	
	if ( $cache_shipped )
		$tables[USAM_TABLE_SHIPPED_DOCUMENTS] = 'shipped_documents_order';	
	
	$result_cache = usam_update_cache( $object_ids, $tables, 'order_id' );
	if ( $cache_shipped && !empty($result_cache['shipped_documents_order']) )
	{
		$ids = [];
		$document_ids = [];
		foreach ( $object_ids as $object_id )
		{
			foreach ( $result_cache['shipped_documents_order'][$object_id] as $item )
			{
				if ( $item->storage && ! $cache = wp_cache_get( $item->storage, 'usam_storage' ) )
					$ids[] = $item->storage;
				if ( $item->storage_pickup && ! $cache = wp_cache_get( $item->storage_pickup, 'usam_storage' ) )
					$ids[] = $item->storage_pickup;
				$document_ids[] = $item->id;
			}
		}
		usam_update_cache( $document_ids, [USAM_TABLE_SHIPPED_DOCUMENT_META => 'document_meta'], 'document_id' );
		usam_get_storages(['include' => $ids, 'cache_results' => true]);
	}
	if ( $cache_payment )
	{
		$ids = [];
		foreach ( $object_ids as  $object_id )
		{
			if ( ! $cache = wp_cache_get( $object_id, 'usam_payment_order' ) )
				$ids[] = $object_id;
		}	
		require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
		$payments = usam_get_payments(['order' => 'DESC', 'document_id' => $ids]);	
		$results_cache = [];
		foreach ( $payments as $payment )
		{
			$results_cache[$payment->document_id][] = $payment;
		} 
		foreach ( $ids as $id )
		{
			if ( isset($results_cache[$id]) )
				wp_cache_set( $id, $results_cache[$id], 'usam_payment_order' );
			else
				wp_cache_set( $id, array(), 'usam_payment_order' );		
		}
	}
}

function usam_get_orders( $query = array() )
{	
	$query['count_total'] = false;
	$orders = new USAM_Orders_Query( $query );	
	return $orders->get_results();	
}