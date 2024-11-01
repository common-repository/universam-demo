<?php
// Класс работы с продавцами интернет-магазина
class USAM_Sellers_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'name' => 's', 'customer_id' => 'd', 'rating' => 'd', 'manager_id' => 'd', 'number_products' => 'd', 'seller_type' => 's', 'date_insert' => 's'];

	// SQL clauses	
	public $query_fields;
	public $query_from;
	public $query_join;	
	public $query_where;
	public $query_orderby;
	public $query_groupby;	 
	public $query_limit;
	public $group_join = false;
	public $crm_join = false;	
	
	public $date_query;	
	public $meta_query;
	
	public function __construct( $query = null ) 
	{
		require_once( USAM_FILE_PATH . '/includes/query/date.php' );
		require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
		require_once( USAM_FILE_PATH . '/includes/query/conditions_query.php' );
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
			'status' => '',
			'source' => '',			
			'meta_key' => '',
			'meta_value' => '',
			'meta_compare' => '',
			//'include' => array(),
			'exclude' => array(),
			'search' => '',
			'search_columns' => array(),
			'orderby' => 'name',
			'order' => 'ASC',
			'offset' => '',
			'number' => '',
			'paged' => 1,
			'count_total' => true,
			'fields' => 'all',		
			'add_fields' => '',	
			'cache_case' => false,					
			'cache_results' => true,	
			'cache_thumbnail' => false,				
			'cache_meta' => false,	
			'cache_users' => false,						
		);
		return wp_parse_args( $args, $defaults );
	}	
	
	public function prepare_query( $query = array() ) 
	{
		global $wpdb;

		if ( empty( $this->query_vars ) || !empty( $query ) ) 
		{
			$this->query_limit = null;
			$this->query_vars = $this->fill_query_vars( $query );
		}			
		do_action( 'usam_pre_get_contacts', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );	
			
		$order_last_join = false;
		$count_visit_join = false;				
		
		$join = [];
		$this->fields = [];
		if ( 'id=>name' == $qv['fields'] ) 
		{
			$this->fields[] = USAM_TABLE_SELLERS.".id";
			$this->fields[] = USAM_TABLE_SELLERS.".name";
		}
		else
		{					
			$fields = is_array($qv['fields']) ? array_unique( $qv['fields'] ) : explode(',', $qv['fields']);
			if ( $qv['add_fields'] )
			{
				$add_fields = is_array($qv['add_fields']) ? array_unique( $qv['add_fields'] ) : explode(',', $qv['add_fields']);
				$fields = array_merge( $fields, $add_fields );	
			}			
			foreach ( $fields as $field )
			{		
				if ( $field == 'all' )
				{
					$this->fields[] = USAM_TABLE_SELLERS.".*";
				}
				elseif ( $field == 'count' )
					$this->fields[] = "COUNT(*) AS count";				
				elseif ( $field == 'count_visit' )
				{							
					$count_visit_join = true;	
					$this->fields[] = "IFNULL(count_visit.count ,0) AS count_visit";
				}
				elseif ( $field == 'meta_value' )
				{
					$this->fields[] = USAM_TABLE_CONTACT_META.".meta_value";	
					$this->fields[] = USAM_TABLE_COMPANY_META.".meta_value";	
				}
				elseif ( $field == 'status' )
				{
					$this->fields[] = "IFNULL(".USAM_TABLE_COMPANY.".$field,".USAM_TABLE_CONTACTS.".$field) AS $field";
					$this->crm_join = true;
				}
				else
				{
					$field = 'id' === $field ? 'id' : sanitize_key( $field );	
					$this->fields[] = "IFNULL(".USAM_TABLE_COMPANY.".$field,".USAM_TABLE_CONTACTS.".$field) AS $field";	
				}
			}			
		} 	
		$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_where = "WHERE 1=1";		
		$this->query_from = "FROM ".USAM_TABLE_SELLERS;		
					
				
		$distinct = false;
		
	// Обрабатывать другие параметры даты
		$date_parameters = array();
		foreach ( ['second', 'minute', 'hour', 'day', 'w', 'monthnum', 'year', 'column'] as $k ) 
		{		
			if ( isset($qv[$k]) && '' !== $qv[$k] )
				$date_parameters[$k] = $qv[$k];
		}
		if ( $date_parameters ) 
		{
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_SELLERS );
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_CONTACTS );
			$this->query_where .= $this->date_query->get_sql();
		}	
		if ( !empty($qv['include']) ) 		
			$include = wp_parse_id_list( $qv['include'] );		 
		elseif ( isset($qv['include'] ) ) 		
			$include = array( 0 );		 
		else 		
			$include = false;
		if ( !empty( $include ) ) 
		{
			$ids = implode( ',', $include );			
			$this->query_where .= " AND ".USAM_TABLE_SELLERS.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_SELLERS.".id NOT IN ($ids)";
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
				if ( !in_array('temporary', $status) )
					$qv['status__not_in'] = 'temporary';
				$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".status IN ('".implode( "','", $status )."')";	
				$this->query_where .= " AND ".USAM_TABLE_COMPANY.".status IN ('".implode( "','", $status )."')";	
			}
		}			
		if ( isset($qv['status__not_in']) ) 
		{
			$status__not_in = (array) $qv['status__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".status NOT IN ('".implode("','", $status__not_in )."')";
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".status NOT IN ('".implode("','", $status__not_in )."')";
		}	
		if ( isset($qv['user_list']) ) 
		{			
			if ( !is_array($qv['user_list']) ) 
			{
				$contact_id = usam_get_contact_id();	
				$qv['user_list'] = ['list' => $qv['user_list'], 'contact_id' => $contact_id];
			}
			$this->query_join .= " INNER JOIN ".USAM_TABLE_USER_SELLERS." ON (".USAM_TABLE_USER_SELLERS.".seller_id = ".USAM_TABLE_SELLERS.".id AND ".USAM_TABLE_USER_SELLERS.".user_list = '".$qv['user_list']['list']."')";
			$this->query_where .= " AND ".USAM_TABLE_USER_SELLERS.".contact_id IN (".implode( ",", (array)$qv['user_list']['contact_id'] ).")";
		}	
		if ( isset($qv['list']) ) 
		{					
			$this->query_join .= " INNER JOIN ".USAM_TABLE_USER_SELLERS." ON (".USAM_TABLE_USER_SELLERS.".seller_id = ".USAM_TABLE_SELLERS.".id AND ".USAM_TABLE_USER_SELLERS.".user_list IN ('".implode("','", (array)$qv['list'] )."'))";
		}	
		if ( isset($qv['customer_id']) ) 
		{ 
			$customer_id = implode( ',',  (array)$qv['customer_id'] );		
			$this->query_where .= " AND ".USAM_TABLE_SELLERS.".customer_id IN ($customer_id)";
		}		
		if ( isset($qv['seller_type']) ) 
		{ 
			$seller_type = implode( "','",  (array)$qv['seller_type'] );		
			$this->query_where .= " AND ".USAM_TABLE_SELLERS.".seller_type IN ('$seller_type')";
		}			
	
		/*
		if ( !empty($qv['source']) ) 
		{
			if ( is_array( $qv['source'] ) ) 
				$source = $qv['source'];			
			elseif ( is_string($qv['source']) && !empty($qv['source']) )
			{
				if ( $qv['source'] != 'all' )
					$source = array_map( 'trim', explode( ',', $qv['source'] ) );
			}
			elseif ( is_numeric($qv['source']) )
				$source = array($qv['source']);
				
			if ( !empty($source) ) 
			{
				if ( !in_array('employee', $source) )
					$qv['source__not_in'] = 'employee';
				$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".contact_source IN ('".implode( "','", $source )."')";	
			}
		}		
		elseif ( empty($qv['source__not_in']) && empty($qv['include']) )
			$qv['source__not_in'] = 'employee';
		if ( isset($qv['source__not_in']) ) 
		{
			$source__not_in = (array) $qv['source__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".contact_source NOT IN ('".implode("','", $source__not_in )."')";
		}
		*/	
		$status_subscriber = array();
		if ( isset($qv['status_subscriber'] ) ) 
		{
			if ( is_array( $qv['status_subscriber'] ) ) 
				$status_subscriber = $qv['status_subscriber'];			
			elseif ( is_string( $qv['status_subscriber'] ) && !empty( $qv['status_subscriber'] ) )
				$status_subscriber = array_map( 'trim', explode( ',', $qv['status_subscriber'] ) );
		}
		$status_subscriber__not_in = array();
		if ( isset($qv['status_subscriber__not_in'] ) ) {
			$status_subscriber__not_in = (array) $qv['status_subscriber__not_in'];
		}

		if ( !empty( $status_subscriber ) ) 
		{		
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACT_META." AS cm_status_subscriber ON (cm_status_subscriber.contact_id = ".USAM_TABLE_CONTACTS.".id)";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." ON (cm_status_subscriber.meta_value = ".USAM_TABLE_SUBSCRIBER_LISTS.".communication)";
				
			$this->query_where .= " AND ".USAM_TABLE_SUBSCRIBER_LISTS.".status IN ('".implode( "','",  $status_subscriber )."')";	
			$distinct = true;			
		}
		if ( !empty($status_subscriber__not_in) ) 
		{			
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACT_META." AS cm_status_subscriber__not_in ON (status_subscriber__not_in.contact_id = ".USAM_TABLE_CONTACTS.".id)";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." ON (status_subscriber__not_in.meta_value = ".USAM_TABLE_SUBSCRIBER_LISTS.".communication)";
				
			$this->query_where .= " AND ".USAM_TABLE_SUBSCRIBER_LISTS.".status NOT IN ('".implode( "','",  $status_subscriber__not_in )."')";	
			$distinct = true;
		}		
	
		$list_subscriber = array();
		if ( isset($qv['list_subscriber'] ) ) 
		{
			if ( is_array( $qv['list_subscriber'] ) ) 
				$list_subscriber = $qv['list_subscriber'];			
			elseif ( is_string( $qv['list_subscriber'] ) && !empty( $qv['list_subscriber'] ) )
				$list_subscriber = array_map( 'trim', explode( ',', $qv['list_subscriber'] ) );
		}
		$list_subscriber__not_in = array();
		if ( isset($qv['list_subscriber__not_in'] ) ) {
			$list_subscriber__not_in = (array) $qv['list_subscriber__not_in'];
		}

		if ( !empty($list_subscriber) ) 
		{		
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACT_META." AS list_subscriber ON (list_subscriber.contact_id = ".USAM_TABLE_CONTACTS.".id)";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." ON (list_subscriber.meta_value = ".USAM_TABLE_SUBSCRIBER_LISTS.".communication)";				
			$this->query_where .= " AND ".USAM_TABLE_SUBSCRIBER_LISTS.".list IN (".implode( ',',  $list_subscriber ).")";
			$distinct = true;
		}
		if ( !empty($list_subscriber__not_in) ) 
		{
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACT_META." AS list_subscriber__not_in ON (list_subscriber__not_in.contact_id = ".USAM_TABLE_CONTACTS.".id)";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." ON (list_subscriber__not_in.meta_value = ".USAM_TABLE_SUBSCRIBER_LISTS.".communication)";				
			$this->query_where .= " AND ".USAM_TABLE_SUBSCRIBER_LISTS.".list NOT IN (".implode( ',',  $list_subscriber__not_in ).")";
			$distinct = true;
		}	
		if ( !empty($qv['not_subscriber']) ) 
		{			
			$this->query_where .= " AND NOT EXISTS (SELECT 1
				FROM ".USAM_TABLE_CONTACT_META." INNER JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." ON (".USAM_TABLE_CONTACT_META.".meta_value=".USAM_TABLE_SUBSCRIBER_LISTS.".communication) 
				WHERE ".USAM_TABLE_CONTACT_META.".contact_id=".USAM_TABLE_CONTACTS.".id )";
			$distinct = true;
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
					case 'meta_key' :
						$ordersby_array[] = USAM_TABLE_CONTACT_META.".meta_value";					
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

		if ( empty($qv['orderby']) ) 
			$ordersby = ['name' => $order];
		elseif ( is_array($qv['orderby']) ) 
			$ordersby = $qv['orderby'];
		else 
		{ // Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
		}		
		$orderby_array = [];	
		foreach ( $ordersby as $_key => $_value ) 
		{
			if ( ! $_value )
				continue;
			
			if ( is_int( $_key ) ) 
			{				
				$_orderby = $_value;
				$_order = $order;
			} 
			else
			{				
				$_orderby = $_key;
				$_order = $_value;
			}
			$parsed = $this->parse_orderby( $_orderby );
			if ( ! $parsed ) {
				continue;
			}
			$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
		}		
		if ( empty($orderby_array) ) 
		{
			$orderby_array[] = USAM_TABLE_CONTACTS.".name $order";
		}
		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		if ( isset($qv['number'] ) && $qv['number'] > 0 ) 
		{
			if ( $qv['offset'] ) {
				$this->query_limit = $wpdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
			} else {
				$this->query_limit = $wpdb->prepare( "LIMIT %d, %d", $qv['number'] * ( $qv['paged'] - 1 ), $qv['number'] );
			}
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

			$search_columns = ['id', 'login', 'user_email', 'user_phone', 'name', 'group'];
			if ( $qv['search_columns'] )
				$search_columns = array_intersect( $qv['search_columns'], $search_columns);
		
			if ( false !== strpos( $search, '@') && in_array('user_email', $search_columns) )
				$search_columns = ['user_email'];
			elseif ( is_numeric($search) )
				$search_columns = array_intersect(['id', 'user_phone', 'login', 'group', 'user_id'], $search_columns);
			else
				$search_columns = array_intersect(['name', 'login', 'group'], $search_columns);
				
			$search_columns = apply_filters( 'usam_contacts_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );			
		}		
		if ( !empty( $qv['company_id'] ) ) 
		{		
			$company_id = implode( ',',  (array)$qv['company_id'] );		
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".company_id IN ($company_id)";
		}		
		if ( !empty($qv['user_id']) ) 
		{
			if ( is_array($qv['user_id']) ) 
				$user_id = $qv['user_id'];			
			elseif ( is_string($qv['user_id']) && !empty($qv['user_id']) )
			{
				if ( $qv['user_id'] != 'all' )
					$user_id = array_map( 'trim', explode( ',', $qv['user_id'] ) );
			}
			elseif ( is_numeric($qv['user_id']) )
				$user_id = array($qv['user_id']);
				
			if ( !empty($user_id) ) 
				$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".user_id IN (".implode( ",", $user_id ).")";	
		}	
		if ( isset($qv['user_id__not_in']) ) 
		{
			$user_id__not_in = (array) $qv['user_id__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".user_id NOT IN (".implode(",", $user_id__not_in ).")";
		}					
		if ( isset($qv['manager_id']) && $qv['manager_id'] !== 'all' ) 
		{ 
			$manager = implode( ',',  (array)$qv['manager_id'] );		
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".manager_id IN ($manager)";
		}	
	/*	if ( $qv['manager_id'] === null && !isset($qv['open']) && $qv['source'] != 'employee' ) 
		{			
			$department_ids = usam_get_subordinates();
			if ( $department_ids )
			{			
				$department_ids[] = 0;
				$this->query_where .= " AND (".USAM_TABLE_CONTACTS.".manager_id IN ('".implode( "','", $department_ids )."') OR ".USAM_TABLE_CONTACTS.".open='1')";	
			}
			else
			{ 
				$user_ids = array( 0 );
				$user_ids[] = get_current_user_id();
				$this->query_where .= " AND (".USAM_TABLE_CONTACTS.".manager_id IN ('".implode( "','", $user_ids )."') OR ".USAM_TABLE_CONTACTS.".open='1')";
			}
		}	*/					
		if ( isset($qv['online']) ) 
		{			
			if ( $qv['online'] ) 
				$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".online >='".date( 'Y-m-d H:i:s', USAM_CONTACT_ONLINE)."'";
			else
				$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".online <= '".date( 'Y-m-d H:i:s', USAM_CONTACT_ONLINE)."'";
		}	
		if ( isset($qv['abandoned_baskets']) ) 
		{			
			$this->query_where .= " AND basket.recalculation_date <= '".date( 'Y-m-d H:i:s', $qv['abandoned_baskets'])."'";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_USERS_BASKET." AS basket ON (basket.contact_id=".USAM_TABLE_CONTACTS.".id)";
		}		
		if ( !empty($qv['conditions']) ) 
		{				
			if ( isset($qv['conditions']['key']) )
				$qv['conditions'] = array($qv['conditions']);
			
			$conditions_query = new USAM_Conditions_Query();
			$this->query_where .= $conditions_query->get_sql_clauses( $qv );
		}
		if ( $order_last_join )
		{
			$this->query_join .= "LEFT JOIN ".USAM_TABLE_ORDERS." AS last_order ON (last_order.contact_id=".USAM_TABLE_CONTACTS.".id AND last_order.id = (SELECT id FROM ".USAM_TABLE_ORDERS." WHERE company_id=0 AND contact_id=".USAM_TABLE_CONTACTS.".id ORDER BY id DESC LIMIT 1 ))";	
		}				
		if ( $count_visit_join )
		{
			$this->query_join .= " LEFT JOIN (SELECT COUNT(id) AS count, contact_id FROM ".USAM_TABLE_VISITS." GROUP BY contact_id) AS count_visit ON (count_visit.contact_id=".USAM_TABLE_CONTACTS.".id)";
		}				
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty($this->meta_query->queries) ) 
		{
			$clauses = $this->meta_query->get_sql( 'contact', USAM_TABLE_CONTACT_META, USAM_TABLE_CONTACTS, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( $this->meta_query->has_or_relation() ) {
				$distinct = true;
			}
		}	
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty($this->meta_query->queries) ) 
		{
			$clauses = $this->meta_query->get_sql( 'company', USAM_TABLE_COMPANY_META, USAM_TABLE_COMPANY, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( $this->meta_query->has_or_relation() ) {
				$distinct = true;
			}
		}		
		if ( $distinct ) 
			$this->query_fields = 'DISTINCT ' . $this->query_fields;
		
		if ( $this->group_join  ) 
		{
			$this->query_join .= " INNER JOIN ".USAM_TABLE_GROUP_RELATIONSHIPS." ON (".USAM_TABLE_GROUP_RELATIONSHIPS.".object_id = ".USAM_TABLE_CONTACTS.".id)";
			$this->query_join .= " INNER JOIN ".USAM_TABLE_GROUP_RELATIONSHIPS." ON (".USAM_TABLE_GROUP_RELATIONSHIPS.".object_id = ".USAM_TABLE_COMPANY.".id)";			
		}
		if ( $this->crm_join  ) 
		{
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_COMPANY." ON (".USAM_TABLE_SELLERS.".customer_id = ".USAM_TABLE_COMPANY.".id AND ".USAM_TABLE_SELLERS.".seller_type='company')";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACTS." ON (".USAM_TABLE_SELLERS.".customer_id = ".USAM_TABLE_CONTACTS.".id AND ".USAM_TABLE_SELLERS.".seller_type='contact')";	
		}
		do_action_ref_array( 'usam_pre_contacts_query', array( &$this ) );
	}
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( $type == 'd' && isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_seller' );	
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
			if ( !$this->results )
				return;	
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
		
		if ( $qv['cache_meta'] || $qv['cache_thumbnail'] )	// необходимо для кеширования фото	
		{ 
			$ids = $this->get_ids();
			usam_update_cache( $ids, [USAM_TABLE_CONTACT_META => 'contact_meta'], 'contact_id' );
			usam_update_cache( $ids, [USAM_TABLE_COMPANY_META => 'company_meta'], 'company_id' );
		}		
		if ( $qv['number'] != 1 && isset($this->results[0]->id) )		
		{ 
			if ( $qv['cache_thumbnail'] && isset($this->results[0]->logo) )		
			{
				$thumb_ids = [];	
				foreach ( $this->results as $result ) 
				{				
					if ( $result->seller_type == 'company' )
						$thumbnail_id = usam_get_company_metadata( $result->id, 'logo' );
					else
						$thumbnail_id = usam_get_contact_metadata( $result->id, 'foto' );
					if ( $thumbnail_id )
						$thumb_ids[] = $thumbnail_id; 					
				}						
				if ( !empty($thumb_ids) )
					_prime_post_caches( $thumb_ids, false, true );
			}	
			if ( $qv['cache_case'] )		
			{
				usam_update_affairs_cache( $this->get_ids(), 'contact' );
			}
			if ( $qv['cache_users'] )		
			{
				$ids = array();	
				foreach ( $this->results as $result ) 
				{							
					$ids[] = $result->user_id; 					
				}	
				if ( !empty($ids) )
					$users = get_users(['include' => $ids]);
			}			
		}
		$r = array();
		if ( 'id=>name' == $qv['fields']) 
		{					
			foreach ($this->results as $key => $result ) 
			{
				$r[$result->id] = $result->appeal;
			}			
			$this->results = $r;
		}
	}
	
	public function get_ids()
	{ 
		$ids = [];	
		foreach ( $this->results as $result ) 
		{				
			if ( isset($result->id) )
				$ids[] = $result->id;
			else
				$ids[] = $result;
		}
		return $ids;
	}

	/**
	 * Retrieve query variable.
	 */
	public function get( $query_var ) {
		if ( isset($this->query_vars[$query_var] ) )
			return $this->query_vars[$query_var];

		return null;
	}

	public function set( $query_var, $value ) {
		$this->query_vars[$query_var] = $value;
	}


	protected function get_search_sql( $string, $cols, $wild = false ) 
	{
		global $wpdb;

		$searches = array();
		$leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
		$like = $leading_wild . $wpdb->esc_like( $string ) . $trailing_wild;
		foreach ( $cols as $col ) 
		{
			if ( 'id' == $col ) 
			{
				$searches[] = $wpdb->prepare( USAM_TABLE_CONTACTS.".$col = %s", $string );
			} 
			elseif ( 'user_email' == $col || 'user_phone' == $col )
			{ 
				$like = $leading_wild . addslashes( $string ) . $trailing_wild;
				$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACT_META." ON (".USAM_TABLE_CONTACT_META.".contact_id = ".USAM_TABLE_CONTACTS.".id)";
				$searches[] = USAM_TABLE_CONTACT_META.".meta_value LIKE '$string'";
			}							
			elseif ( 'name' == $col ) 
				$searches[] = $wpdb->prepare( USAM_TABLE_SELLERS.".name LIKE LOWER('%s')", "%$string%" );
			elseif ( 'group' == $col )
			{			
				$string = addslashes( $string );
				$this->query_join .= " LEFT JOIN ".USAM_TABLE_GROUP_RELATIONSHIPS." ON (".USAM_TABLE_GROUP_RELATIONSHIPS.".object_id = ".USAM_TABLE_CONTACTS.".id)";
				$this->query_join .= " LEFT JOIN ".USAM_TABLE_GROUPS." ON (".USAM_TABLE_GROUPS.".id = ".USAM_TABLE_GROUP_RELATIONSHIPS.".group_id)";			
				$searches[] = USAM_TABLE_GROUPS.".name LIKE LOWER('$string')";
			} 			
			else 
			{
				$searches[] = $wpdb->prepare( "$col LIKE %s", $like );
			}
		}
		if ( !empty($searches) )
			return ' AND (' . implode(' OR ', $searches) . ')';
		else
			return '';
	}

	/**
	 * Return the list of users.
	 */
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

	/**
	 * Разобрать и очистить ключи 'orderby'
	 */
	protected function parse_orderby( $orderby ) 
	{
		global $wpdb;

		$_orderby = '';
		if ( in_array( $orderby, ['id', 'name', 'seller_type', 'customer_id', 'date_insert', 'rating', 'number_products', 'manager_id']) )
		{
			$_orderby = USAM_TABLE_SELLERS.'.'.$orderby;
		}
		elseif ( 'count' == $orderby ) 
		{
			$_orderby = 'COUNT(id)';
		}		
		elseif ( 'meta_value' == $orderby ) 
		{
			$_orderby = USAM_TABLE_SELLER_META.".meta_value";
		}	
		elseif ( 'meta_value_num' == $orderby ) 
		{
			$_orderby = "CAST(".USAM_TABLE_SELLER_META.".meta_value AS signed)";
		}		
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) ) 
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_SELLERS.".id, $include_sql )";
		} 
		return $_orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 */
	protected function parse_order( $order ) {
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
	public function __get( $name ) {
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

function usam_get_sellers( $query = [] )
{	
	$query['count_total'] = false;
	$class = new USAM_Sellers_Query( $query );		
	return $class->get_results();	
}	