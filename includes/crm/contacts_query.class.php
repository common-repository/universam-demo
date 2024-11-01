<?php
// Класс работы с контактами
class USAM_Contacts_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $request;
	public $distinct = false;	
	public $full_name_join = false;		

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'total_purchased' => 'f', 'last_order_date' => 's', 'number_orders' => 'd', 'status' => 's', 'user_id' => 'd', 'manager_id' => 'd', 'appeal' => 's', 'online' => 's', 'company_id' => 'd', 'open' => 'd', 'secret_key' => 's', 'contact_source' => 's', 'date_insert' => 's'];
	
	// SQL clauses	
	public $query_fields;
	public $query_from;
	public $query_join;	
	public $query_where;
	public $query_orderby;
	public $query_groupby;	 
	public $query_limit;
	private $group_join = false;
	private $newsletter_stat_join = false;	
	
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
			'blog_id' => get_current_blog_id(),
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
			'orderby' => 'id',
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
			'cache_department' => false,		
			'cache_users' => false,				
		);
		return wp_parse_args( $args, $defaults );
	}	
	
	public function prepare_query( $query = array() ) 
	{
		global $wpdb, $wp_roles;

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
		$users_left_join = false;
		$users_inner_join = false;	
		
		$join = [];
		$this->fields = [];
		if ( 'id=>name' == $qv['fields'] ) 
		{
			$this->fields[] = USAM_TABLE_CONTACTS.".id";
			$this->fields[] = USAM_TABLE_CONTACTS.".appeal";
		}
		elseif ( 'user_id=>name' == $qv['fields'] ) 
		{
			$this->fields[] = USAM_TABLE_CONTACTS.".user_id";
			$this->fields[] = USAM_TABLE_CONTACTS.".appeal";
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
					$this->fields[] = USAM_TABLE_CONTACTS.".*";		
				elseif ( $field == 'count' )
					$this->fields[] = "COUNT(*) AS count";				
				elseif ( $field == 'last_order' )				
				{					
					$order_last_join = true;
					
					$this->fields[] = "IFNULL( last_order.id,0) AS last_order_id";
					$this->fields[] = "IFNULL( last_order.totalprice,0) AS last_order_sum";
					$this->fields[] = "IFNULL( last_order.type_price,'') AS last_order_type_price";
				}		
				elseif ( $field == 'newsletter_stat' )
				{							
					$this->newsletter_stat_join = true;	
					$this->fields[] = USAM_TABLE_NEWSLETTER_USER_STAT.".communication";
					$this->fields[] = USAM_TABLE_NEWSLETTER_USER_STAT.".sent_at";
					$this->fields[] = USAM_TABLE_NEWSLETTER_USER_STAT.".opened_at";
					$this->fields[] = USAM_TABLE_NEWSLETTER_USER_STAT.".clicked";
					$this->fields[] = USAM_TABLE_NEWSLETTER_USER_STAT.".unsub";
					$this->fields[] = USAM_TABLE_NEWSLETTER_USER_STAT.".status AS sending_status";
				}			
				elseif ( $field == 'user_registered' )
				{							
					$users_left_join = true;	
					$this->fields[] = "IFNULL(users.user_registered ,0) AS user_registered";
				}
				elseif ( $field == 'count_visit' )
				{							
					$count_visit_join = true;	
					$this->fields[] = "visit.meta_value AS count_visit";
				}
				elseif ( $field == 'full_name' )
				{							
					$this->full_name_join = true;	
					$this->fields[] = "full_name.meta_value AS full_name";
				}				
				elseif ( $field == 'meta_value' )
					$this->fields[] = USAM_TABLE_CONTACT_META.".meta_value";
				elseif ( isset($this->db_fields[$field]) )
					$this->fields[] = USAM_TABLE_CONTACTS.".$field";
			}			
		} 	
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_CONTACTS.".*";
		else
			$this->query_fields = implode( ',', $this->fields );	
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_where = "WHERE 1=1";		
		$this->query_from = "FROM ".USAM_TABLE_CONTACTS;	
		$this->distinct = false;
		
	// Обрабатывать другие параметры даты
		$date_parameters = array();
		foreach( ['second', 'minute', 'hour', 'day', 'w', 'monthnum', 'year'] as $k ) 
		{					
			if ( '' !== $qv[$k] )
				$date_parameters[$k] = $qv[$k];
		}
		if ( $date_parameters ) 
		{
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_CONTACTS );
			$this->query_where .= $date_query->get_sql();
		} //users
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_CONTACTS );
			$this->query_where .= $this->date_query->get_sql();
		}	
		if ( !empty( $qv['include'] ) ) 		
			$include = wp_parse_id_list( $qv['include'] );		 
		elseif ( isset($qv['include'] ) ) 		
			$include = array( 0 );		 
		else 		
			$include = false;
		if ( !empty($include) ) 
		{
			$ids = implode( ',', $include );			
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".id NOT IN ($ids)";
		}					
		if ( !empty($qv['status']) ) 
		{
			if ( $qv['status'] != 'all' )
				
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
			}
		}	
		elseif ( empty($qv['status__not_in']) && empty($qv['include']) )
			$qv['status__not_in'] = 'temporary';
		if ( isset($qv['status__not_in']) ) 
		{
			$status__not_in = (array) $qv['status__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".status NOT IN ('".implode("','", $status__not_in )."')";
		}	
		if ( !empty($qv['source']) ) 
		{
			if ( is_array($qv['source']) ) 
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
		$storage_pickup = array();
		if ( isset($qv['storage_pickup']) ) 
		{
			if ( is_array( $qv['storage_pickup'] ) ) 
				$storage_pickup = $qv['storage_pickup'];			
			elseif ( is_string( $qv['storage_pickup'] ) && !empty( $qv['storage_pickup'] ) )
				$storage_pickup = array_map( 'trim', explode( ',', $qv['storage_pickup'] ) );
			else
				$storage_pickup = array( absint($qv['storage_pickup']) );
		}	
		$storage_pickup__not_in = array();
		if ( isset($qv['storage_pickup__not_in'] ) ) {
			$storage_pickup__not_in = (array) $qv['storage_pickup__not_in'];
		}		
		if ( !empty($storage_pickup) ) 
		{			
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_ORDERS." ON (".USAM_TABLE_ORDERS.".contact_id = ".USAM_TABLE_CONTACTS.".id)";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SHIPPED_DOCUMENTS." ON (".USAM_TABLE_SHIPPED_DOCUMENTS.".order_id = ".USAM_TABLE_ORDERS.".id)";
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".storage_pickup IN ('".implode( "','",  $storage_pickup )."')";		
		}		
		if ( !empty($storage_pickup__not_in) ) 
		{			
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_ORDERS." ON (".USAM_TABLE_ORDERS.".contact_id = ".USAM_TABLE_CONTACTS.".id)";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SHIPPED_DOCUMENTS." ON (".USAM_TABLE_SHIPPED_DOCUMENTS.".order_id = ".USAM_TABLE_ORDERS.".id)";
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".storage_pickup NOT IN ('".implode( "','",  $storage_pickup__not_in )."')";
		}				
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
			$this->distinct = true;			
		}
		if ( !empty($status_subscriber__not_in) ) 
		{			
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACT_META." AS cm_status_subscriber__not_in ON (status_subscriber__not_in.contact_id = ".USAM_TABLE_CONTACTS.".id)";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." ON (status_subscriber__not_in.meta_value = ".USAM_TABLE_SUBSCRIBER_LISTS.".communication)";
				
			$this->query_where .= " AND ".USAM_TABLE_SUBSCRIBER_LISTS.".status NOT IN ('".implode( "','",  $status_subscriber__not_in )."')";	
			$this->distinct = true;
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
			$this->distinct = true;
		}
		if ( !empty($list_subscriber__not_in) ) 
		{
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACT_META." AS list_subscriber__not_in ON (list_subscriber__not_in.contact_id = ".USAM_TABLE_CONTACTS.".id)";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." ON (list_subscriber__not_in.meta_value = ".USAM_TABLE_SUBSCRIBER_LISTS.".communication)";				
			$this->query_where .= " AND ".USAM_TABLE_SUBSCRIBER_LISTS.".list NOT IN (".implode( ',',  $list_subscriber__not_in ).")";
			$this->distinct = true;
		}	
		if ( !empty($qv['not_subscriber']) ) 
		{			
			$this->query_where .= " AND NOT EXISTS (SELECT 1
				FROM ".USAM_TABLE_CONTACT_META." INNER JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." ON (".USAM_TABLE_CONTACT_META.".meta_value=".USAM_TABLE_SUBSCRIBER_LISTS.".communication) 
				WHERE ".USAM_TABLE_CONTACT_META.".contact_id=".USAM_TABLE_CONTACTS.".id )";
			$this->distinct = true;
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
			$ordersby_array = [];
			foreach ( $groupby as $_value )
			{						
				$groupbydate = false;
				foreach(['date_insert' => '', 'users.user_registered' => 'user_registered'] as $column => $key )
				{							
					if ( $key )
					{
						if ( stripos($_value, $key) === false )
							continue;					
						$v = str_replace('_'.$key, '', $_value);
					}
					else
						$v = $_value;	
					switch ( $v ) 
					{				
						case 'day' :
							if ( USAM_SQL_TIME_ZONE )
							{
								$ordersby_array[] = "DAY(CONVERT_TZ($column,'UTC','$name_timezone')))";
								$ordersby_array[] = "MONTH(CONVERT_TZ($column,'UTC','$name_timezone')))";
								$ordersby_array[] = "YEAR((CONVERT_TZ($column,'UTC','$name_timezone')))";						
							}
							else
							{
								$ordersby_array[] = "DAY($column)";
								$ordersby_array[] = "MONTH($column)";
								$ordersby_array[] = "YEAR($column)";								
							}
							$groupbydate = true;
						break;
						case 'week' :
							if ( USAM_SQL_TIME_ZONE )
							{
								$ordersby_array[] = "WEEKOFYEAR(CONVERT_TZ($column,'UTC','$name_timezone'))";
								$ordersby_array[] = "YEAR(CONVERT_TZ($column,'UTC','$name_timezone'))";						
							}
							else
							{
								$ordersby_array[] = "WEEKOFYEAR($column)";
								$ordersby_array[] = "YEAR($column)";		
							}	
							$groupbydate = true;							
						break;	
						case 'month' :					
							if ( USAM_SQL_TIME_ZONE )
							{
								$ordersby_array[] = "MONTH(CONVERT_TZ($column,'UTC','$name_timezone'))";
								$ordersby_array[] = "YEAR(CONVERT_TZ($column,'UTC','$name_timezone'))";						
							}
							else
							{
								$ordersby_array[] = "MONTH($column)";
								$ordersby_array[] = "YEAR($column)";		
							}	
							$groupbydate = true;
						break;					
						case 'year' :
							if ( USAM_SQL_TIME_ZONE )
								$ordersby_array[] = "YEAR(CONVERT_TZ($column,'UTC','$name_timezone'))";	
							else
								$ordersby_array[] = "YEAR($column)";
							$groupbydate = true;
						break;
					}
				}		
				if ( !$groupbydate )
				{
					switch ( $_value ) 
					{						
						case 'events' :					
							$this->query_join .= " INNER JOIN ".USAM_TABLE_RIBBON_LINKS." ON (".USAM_TABLE_RIBBON_LINKS.".object_id = ".USAM_TABLE_CONTACTS.".id AND ".USAM_TABLE_RIBBON_LINKS.".object_type='contact')";
							$ordersby_array[] = USAM_TABLE_RIBBON_LINKS.".object_id";
						break;	
						case 'meta_key' :
							$ordersby_array[] = USAM_TABLE_CONTACT_META.".meta_value";					
						break;					
						default:
							$ordersby_array[] = $_value;
					}		
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
		{		
			$ordersby = array( 'name' => $order );
		} 
		elseif ( is_array( $qv['orderby'] ) ) 
		{
			$ordersby = $qv['orderby'];
		} 
		else 
		{ // Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
		}		
		$orderby_array = [];
		
		if ( in_array('name', $ordersby) )
			$ordersby[] = 'appeal';
		foreach ( $ordersby as $_key => $_value ) 
		{
			if ( ! $_value ) {
				continue;
			}
			
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
		if ( empty( $orderby_array ) ) 
		{
			$orderby_array[] = USAM_TABLE_CONTACTS.".id $order";
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

			$search_columns = ['id', 'login', 'email', 'mobilephone', 'name', 'group'];
			if ( $qv['search_columns'] )
				$search_columns = array_intersect( $qv['search_columns'], $search_columns);
		
			if ( false !== strpos( $search, '@') && in_array('email', $search_columns) )
				$search_columns = ['email'];
			elseif ( is_numeric($search) )
				$search_columns = array_intersect(['id', 'mobilephone', 'login', 'group', 'user_id'], $search_columns);
			else
				$search_columns = array_intersect(['appeal', 'name', 'login', 'group'], $search_columns);
				
			$search_columns = apply_filters( 'usam_contacts_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );			
		}		
		if ( !empty($qv['company_id']) ) 
		{		
			$company_id = implode( ',',  (array)$qv['company_id'] );		
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".company_id IN ($company_id)";
		}
		if ( !empty($qv['campaign']) ) 
		{
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CAMPAIGN_TRANSITIONS." ON (".USAM_TABLE_CAMPAIGN_TRANSITIONS.".contact_id = ".USAM_TABLE_CONTACTS.".id)";
			$this->query_where .= " AND ".USAM_TABLE_CAMPAIGN_TRANSITIONS.".campaign_id IN (".implode( ",", (array)$qv['campaign'] ).")";
			$this->distinct = true;
		}		
		if ( !empty($qv['company_account']) ) 
		{
			$this->query_join .= " INNER JOIN ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS." ON (".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS.".user_id = ".USAM_TABLE_CONTACTS.".user_id)";
			$this->query_where .= " AND ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS.".company_id IN (".implode( ",", (array)$qv['company_account'] ).")";
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
		if ( isset($qv['accounts']) ) 
		{					
			if ( $qv['accounts'] ) 
				$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".user_id>0";	
			else
				$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".user_id=0";	
		}
		$roles = array();
		if ( isset($qv['role']) ) 
		{
			if ( is_array( $qv['role'] ) ) 
				$roles = $qv['role'];			
			elseif ( is_string( $qv['role'] ) && !empty( $qv['role'] ) )
				$roles = array_map( 'trim', explode( ',', $qv['role'] ) );
		}
		$role__in = array();
		if ( isset($qv['role__in'] ) ) {
			$role__in = (array)$qv['role__in'];
		}
		$role__not_in = array();
		if ( isset($qv['role__not_in'] ) ) {
			$role__not_in = (array) $qv['role__not_in'];
		}
				
		// Capabilities.
		$available_roles = array();
		if ( ! empty( $qv['capability'] ) || ! empty($qv['capability__in']) || ! empty($qv['capability__not_in']) ) 
		{
			$wp_roles->for_site( $qv['blog_id'] );
			$available_roles = $wp_roles->roles;
		}
		$capabilities = array();
		if ( ! empty($qv['capability']) ) 
		{ //должен иметь все из указанных возможностей
			if ( is_array($qv['capability'] ) )
				$capabilities = $qv['capability'];
			elseif ( is_string($qv['capability'] ) )
				$capabilities = array_map( 'trim', explode( ',', $qv['capability'] ) );
		}		
		$capability__in = array();
		if ( ! empty( $qv['capability__in'] ) ) //должен иметь хотя бы одну из указанных возможностей
			$capability__in = (array) $qv['capability__in'];
		$capability__not_in = array();
		if ( ! empty( $qv['capability__not_in'] ) ) {
			$capability__not_in = (array) $qv['capability__not_in'];
		}
		foreach ( $available_roles as $role => $role_data )
		{
			$role_caps = array_keys( array_filter( $role_data['capabilities'] ) );			
			$add = true;	
			foreach ( $capabilities as $cap ) 
			{
				if ( !in_array( $cap, $role_caps, true ) ) 
				{
					$add = false;
					break;
				}
			}
			if ( $add )
				$roles[] = $role;
			foreach ( $capability__in as $cap ) 
			{
				if ( in_array( $cap, $role_caps, true ) ) 
				{
					$role__in[] = $role;
					break;
				}
			}

			foreach ( $capability__not_in as $cap ) 
			{
				if ( in_array( $cap, $role_caps, true ) ) 
				{
					$role__not_in[] = $role;
					break;
				}
			}
		}				
		$roles        = array_unique( $roles );
		$role__in     = array_unique( $role__in );
		$role__not_in = array_unique( $role__not_in );		
		if ( !empty($roles) ) 
		{			
			$this->query_join .= " LEFT JOIN ".$wpdb->usermeta." AS usermeta ON (usermeta.user_id=".USAM_TABLE_CONTACTS.".user_id)";
			$role_query = [];
			foreach ( $roles as $role )
				$role_query[] = 'usermeta.meta_value LIKE "%'.'\"' . $role . '\"'.'%"';
			$this->query_where .= " AND (".implode( " OR ",  $role_query ).")  AND usermeta.meta_key='".$wpdb->get_blog_prefix( $qv['blog_id'] )."capabilities'";
			$this->distinct = true;
		}
		if ( !empty($role__in) ) 
		{
			$this->query_join .= " LEFT JOIN ".$wpdb->usermeta." AS usermeta ON (usermeta.user_id=".USAM_TABLE_CONTACTS.".user_id)";
			$role_query = [];
			foreach ( $role__in as $role )
				$role_query[] = 'usermeta.meta_value LIKE "%'.'\"' . $role . '\"'.'%"';
			$this->query_where .= " AND (".implode( " OR ",  $role_query ).")  AND usermeta.meta_key='".$wpdb->get_blog_prefix( $qv['blog_id'] )."capabilities'";
			$this->distinct = true;
		}		
		if ( !empty($role__not_in) ) 
		{					
			$this->query_join .= " LEFT JOIN ".$wpdb->usermeta." AS usermeta ON (usermeta.user_id=".USAM_TABLE_CONTACTS.".user_id)";
			$role_query = [];
			foreach ( $role__not_in as $role )
				$role_query[] = 'usermeta.meta_value NOT LIKE "%'.'\"' . $role . '\"'.'%"';
			$this->query_where .= " AND (".implode( " AND ",  $role_query ).")  AND usermeta.meta_key='".$wpdb->get_blog_prefix( $qv['blog_id'] )."capabilities'";
			$this->distinct = true;
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
		if ( isset($qv['user_registered']) )
		{
			$users_left_join = false;
			$users_inner_join = true;
		}		
		if( isset($qv['open']) ) 
		{		
			$open = $qv['open'] == 1?1:0 ;
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".open='$open'";
		}						
		if( isset($qv['online']) ) 
		{			
			if ( $qv['online'] ) 
				$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".online >='".date( 'Y-m-d H:i:s', USAM_CONTACT_ONLINE)."'";
			else
				$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".online <= '".date( 'Y-m-d H:i:s', USAM_CONTACT_ONLINE)."'";
		}	
		if( !empty($qv['dialog_id']) ) 
		{ 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CHAT_USERS." AS chat_users ON (chat_users.contact_id=".USAM_TABLE_CONTACTS.".id AND dialog_id=".$qv['dialog_id'].")";			
		}		
		if( !empty($qv['document_ids']) ) 
		{ 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_DOCUMENT_CONTACTS." AS dcs ON (dcs.contact_id=".USAM_TABLE_CONTACTS.".id)";	
			$this->query_where .= " AND dcs.document_id IN (".implode(',',  (array)$qv['document_ids'] ).")";			
		}	
		if( !empty($qv['newsletter_id']) ) 
		{ 
			$this->newsletter_stat_join = true;
			$this->query_where .= " AND ".USAM_TABLE_NEWSLETTER_USER_STAT.".newsletter_id IN (".implode(',',  (array)$qv['newsletter_id'] ).")";			
		}
		if( !empty($qv['action_newsletter']) ) 
		{ 
			$this->newsletter_stat_join = true;
			switch( $qv['action_newsletter'] ) 
			{
				case 'clicked' :
					$this->query_where .= " AND ".USAM_TABLE_NEWSLETTER_USER_STAT.".clicked > 0";
				break;
				case 'open' :
					$this->query_where .= " AND ".USAM_TABLE_NEWSLETTER_USER_STAT.".opened_at IS NOT NULL";
				break;
				case 'not_open' :
					$this->query_where .= " AND ".USAM_TABLE_NEWSLETTER_USER_STAT.".opened_at IS NULL";
				break;
				case 'unsubscribed' :
					$this->query_where .= " AND ".USAM_TABLE_NEWSLETTER_USER_STAT.".unsub = 1";
				break;
			}
		}		
		if ( !empty($qv['user_post']) ) 
		{
			foreach ( $qv['user_post'] as $i => &$user_post )
			{
				$this->query_join .= " LEFT JOIN ".USAM_TABLE_USER_POSTS." AS up_{$i} ON (up_{$i}.contact_id=".USAM_TABLE_CONTACTS.".id)";
				if ( !empty($user_post['list']) ) 
					$this->query_where .= " AND up_{$i}.user_list='".$user_post['list']."'";
				if ( !empty($user_post['product_id']) ) 
					$this->query_where .= " AND up_{$i}.product_id='".$user_post['product_id']."'";
			}
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
		
			foreach ( $qv['conditions'] as &$condition )
			{					
				switch ( $condition['key'] )
				{							
					case 'bonus' :					
						$this->query_join .= " INNER JOIN ".USAM_TABLE_BONUS_CARDS." bonus_card ON (bonus_card.user_id=".USAM_TABLE_CONTACTS.".user_id)";							
						$condition['key'] = "bonus_card.sum";
					break;
					default:
						$condition['key'] = USAM_TABLE_CONTACTS.'.'.$condition['key'];
					break;
				}	
			}			
			$conditions_query = new USAM_Conditions_Query();
			$this->query_where .= $conditions_query->get_sql_clauses( $qv );
		}
		if ( $order_last_join )
		{
			$this->query_join .= "LEFT JOIN ".USAM_TABLE_ORDERS." AS last_order ON (last_order.contact_id=".USAM_TABLE_CONTACTS.".id AND last_order.id = (SELECT id FROM ".USAM_TABLE_ORDERS." WHERE company_id=0 AND contact_id=".USAM_TABLE_CONTACTS.".id ORDER BY id DESC LIMIT 1 ))";	
		}				
		if ( $count_visit_join )
		{
			$this->distinct = true;	
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CONTACT_META." AS visit ON (visit.contact_id=".USAM_TABLE_CONTACTS.".id AND visit.meta_key='visit')";			
		}			
		if ( $this->full_name_join )
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CONTACT_META." AS full_name ON (full_name.contact_id=".USAM_TABLE_CONTACTS.".id AND full_name.meta_key='full_name')";		
		if ( $users_left_join )
			$this->query_join .= " LEFT JOIN ".$wpdb->users." AS users ON (users.id=".USAM_TABLE_CONTACTS.".user_id)";
		elseif ( $users_inner_join )
			$this->query_join .= " INNER JOIN ".$wpdb->users." AS users ON (users.id=".USAM_TABLE_CONTACTS.".user_id)";
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if( !empty($this->meta_query->queries) ) 
		{
			$clauses = $this->meta_query->get_sql( 'contact', USAM_TABLE_CONTACT_META, USAM_TABLE_CONTACTS, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( $this->meta_query->has_or_relation() )
				$this->distinct = true;			
		}			
		if ( $this->group_join  ) 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_GROUP_RELATIONSHIPS." ON (".USAM_TABLE_GROUP_RELATIONSHIPS.".object_id = ".USAM_TABLE_CONTACTS.".id)";
		if ( $this->newsletter_stat_join  ) 
		{
			$this->distinct = true;	
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CONTACT_META." AS meta_communication ON (meta_communication.contact_id=".USAM_TABLE_CONTACTS.".id)";	
			$this->query_join .= " INNER JOIN ".USAM_TABLE_NEWSLETTER_USER_STAT." ON (".USAM_TABLE_NEWSLETTER_USER_STAT.".communication = meta_communication.meta_value)";
		}	
		if ( $this->distinct ) 
			$this->query_fields = 'DISTINCT ' . $this->query_fields;		
		do_action_ref_array( 'usam_pre_contacts_query', array( &$this ) );
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
					$data->$column = (int)$data->$column;
			}
		}
		foreach(['unsub', 'clicked'] as $column ) 
		{			
			if ( isset($data->$column) )
				$data->$column = (int)$data->$column;			
		}
		if ( isset($data->appeal) && $data->appeal === '' )
			$data->appeal = __('Без имени', 'usam');
		if ( $this->query_vars['cache_results'] && isset($data->id) )
		{			
			wp_cache_set( $data->id, (array)$data, 'usam_contact' );		
			if ( isset($data->user_id) )
				wp_cache_set( $data->user_id, $data->id, 'usam_contact_userid' );	
		}
		return $data;
	}

	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;		
		$this->request = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";	
		$count_fields = count($this->fields);
		if ( $count_fields > 1 || strripos($this->query_fields, '*') !== false && $qv['number'] != 1 )
		{
			$this->results = $wpdb->get_results( $this->request );		
			foreach ($this->results as $k => $result ) 
				$this->results[$k] = $this->handling_data_types( $result );
		}
		elseif ( $qv['number'] == 1 && $qv['fields'] == 'all' )
		{
			$this->results = $wpdb->get_row( $this->request );
			$this->results = (array)$this->handling_data_types( $this->results );
		}
		elseif ( $qv['number'] == 1 && $count_fields == 1 )		
			$this->results = $wpdb->get_var( $this->request );
		else 
			$this->results = $wpdb->get_col( $this->request );	
		if ( !$this->results )
			return;	
				
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->total = (int)$wpdb->get_var( 'SELECT FOUND_ROWS()' );			
		
		if ( $qv['cache_meta'] || $qv['cache_thumbnail'] || $qv['cache_department'] )	// необходимо для кеширования фото	
			usam_update_cache( $this->get_ids(), [USAM_TABLE_CONTACT_META => 'contact_meta'], 'contact_id' );
		if ( $qv['cache_department'] )
		{
			$department_ids = array();
			foreach ( $this->results as $result ) 
			{				
				$department_id = usam_get_contact_metadata( $result->id, 'department' );
				if ( $department_id )
					$department_ids[] = $department_id; 					
			} 
			if ( $department_ids )
				usam_get_departments(['include' => $department_ids, 'cache_results' => true]);
		}
		if ( $qv['cache_thumbnail'] )		
		{
			$thumb_ids = array();
			foreach ( $this->results as $result ) 
			{				
				if( isset($result->id) )
				{
					$thumbnail_id = usam_get_contact_metadata( $result->id, 'foto' );
					if ( $thumbnail_id )
						$thumb_ids[] = $thumbnail_id;	
				}
			} 
			if ( !empty($thumb_ids) )
				_prime_post_caches( $thumb_ids, false, true );
		}
		if ( $qv['number'] != 1 && isset($this->results[0]->id) )		
		{ 
			if ( $qv['cache_case'] )		
			{
				usam_update_affairs_cache( $this->get_ids(), 'contact' );
			}
			if ( $qv['cache_users'] )		
			{
				$ids = array();	
				foreach ( $this->results as $result ) 
					$ids[] = $result->user_id; 		
				if ( !empty($ids) )
					$users = get_users(['include' => $ids]);
			}			
		}
		$r = array();
		if( 'id=>name' == $qv['fields']) 
		{					
			foreach ($this->results as $key => $result ) 
				$r[$result->id] = $result->appeal;
			$this->results = $r;
		}
		elseif( 'user_id=>name' == $qv['fields']) 
		{					
			foreach ($this->results as $key => $result ) 
				$r[$result->user_id] = $result->appeal;
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
			elseif ( 'login' == $col )
			{
				$user = get_user_by( 'login', $string );
				if ( !empty($user->ID) )
					$searches[] = USAM_TABLE_CONTACTS.".user_id='$user->ID'";	
			}	
			elseif ( 'user_id' == $col )
			{
				$searches[] = USAM_TABLE_CONTACTS.".user_id='$string'";	
			}				
			elseif ( 'name' == $col )
			{ 			
				$this->distinct = true;	
				$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACT_META." AS full_name ON (full_name.contact_id=".USAM_TABLE_CONTACTS.".id AND full_name.meta_key='full_name')";
				$names = explode(' ', $string);	
				if( count($names) > 2 )
				{
					$searches[] = "full_name.meta_value LIKE LOWER('{$names[0]} {$names[1]} %')";
					$searches[] = "full_name.meta_value LIKE LOWER('{$names[1]} {$names[0]} %')";
				}
				else
				{					
					$searches[] = "full_name.meta_value LIKE LOWER('$string%')";
					$searches[] = "full_name.meta_value LIKE LOWER('% $string%')";
				}
			} 			
			elseif ( 'email' == $col || 'mobilephone' == $col )
			{ 
				$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACT_META." AS $col ON ($col.contact_id = ".USAM_TABLE_CONTACTS.".id AND $col.meta_key='$col')";
				$searches[] = "$col.meta_value LIKE '$string'";
				$this->distinct = true;	
			}
			elseif ( 'lastname' == $col || 'firstname' == $col || 'patronymic' == $col  ) 
			{
				$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACT_META." AS $col ON ($col.contact_id = ".USAM_TABLE_CONTACTS.".id AND $col.meta_key='$col')";
				$searches[] = "$col.meta_value LIKE LOWER('$string')";
				$this->distinct = true;	
			}
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
		if ( isset($this->db_fields[$orderby]) )
			$_orderby = USAM_TABLE_CONTACTS.'.'.$orderby;
		elseif ( $orderby == 'date' )
			$_orderby = USAM_TABLE_CONTACTS.'.date_insert';
		elseif ( $orderby == 'sum' )
			$_orderby = USAM_TABLE_CONTACTS.'.total_purchased';
		elseif ( in_array($orderby, ['opened_at' , 'clicked' , 'unsub']) )
			$_orderby = USAM_TABLE_NEWSLETTER_USER_STAT.'.'.$orderby;
		elseif ( 'count' == $orderby ) 
			$_orderby = 'COUNT(id)';			
		elseif ( 'department' == $orderby ) 
		{
			$this->distinct = true;
			$_orderby = "CAST(".USAM_TABLE_CONTACT_META.".meta_value AS signed)";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACT_META." ON (".USAM_TABLE_CONTACT_META.".contact_id = ".USAM_TABLE_CONTACTS.".id AND ".USAM_TABLE_CONTACT_META.".meta_key='department')";
		}
		elseif ( 'user_registered' == $orderby ) 
		{
			$_orderby = 'users.user_registered';
		}		
		elseif ( 'meta_value' == $orderby ) 
		{
			$_orderby = USAM_TABLE_CONTACT_META.".meta_value";
		}	
		elseif ( 'meta_value_num' == $orderby ) 
		{
			$_orderby = "CAST(".USAM_TABLE_CONTACT_META.".meta_value AS signed)";
		}		
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) ) 
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_CONTACTS.".id, $include_sql )";
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

function usam_get_contacts( $query = [] )
{	
	$query['count_total'] = false;
	$contacts = new USAM_Contacts_Query( $query );		
	return $contacts->get_results();	
}	

function usam_get_employees( $query = array() )
{
	$query['source'] = 'employee';
	$query['count_total'] = false;
	$contacts = new USAM_Contacts_Query( $query );		
	return $contacts->get_results();
}