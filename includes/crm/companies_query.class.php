<?php
// Класс работы с компаниями
class USAM_Companies_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'total_purchased' => 'f', 'last_order_date' => 's', 'number_orders' => 'd', 'status' => 's', 'name' => 's', 'manager_id' => 'd', 'parent_id' => 'd', 'open' => 'd', 'type' => 's', 'industry' => 's', 'date_insert' => 's'];

	// SQL clauses
	public $query_fields;
	public $query_from;
	public $query_join;	
	public $query_where;
	public $query_orderby;
	public $query_groupby;	 
	public $query_limit;
	public $distinct = false;	
	private $group_join = false;	
	private $personal_accounts_join = false;	
	private $newsletter_stat_join = false;		
	
	public $meta_query;
	public $date_query;	
	
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
			'column' => '',
			'monthnum' => '',
			'year' => '',
			'w' => '',
			'type' => '',
			'type__not_in' => array(),
			'industry' => '',
			'industry__in' => array(),
			'industry__not_in' => array(),
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
			'cache_bank_accounts' => false,	
			'cache_case' => false,				
			'cache_results' => false,	
			'cache_meta' => false,
			'cache_thumbnail' => false,			
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
		do_action( 'usam_pre_get_companies', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );	
					
		$order_last_join = false;
		$this->newsletter_stat_join = false;	
				
		$join = [];				
		$this->fields = [];
		if ( 'id=>name' == $qv['fields'] ) 	
		{
			$this->fields[] = USAM_TABLE_COMPANY.".id";
			$this->fields[] = USAM_TABLE_COMPANY.".name";	
		}
		else
		{
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
					$this->fields[] = USAM_TABLE_COMPANY.".*";		
				elseif ( $field == 'count' )
					$this->fields[] = "COUNT(*) AS count";	
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
				elseif ( $field == 'last_order' )				
				{					
					$order_last_join = true;
					
					$this->fields[] = "IFNULL( last_order.id,0) AS last_order_id";
					$this->fields[] = "IFNULL( last_order.totalprice,0) AS last_order_sum";
					$this->fields[] = "IFNULL( last_order.type_price,'') AS last_order_type_price";
				}
				elseif ( $field == 'id=>data' )
					$this->fields[] = USAM_TABLE_COMPANY.".*";	
				elseif ( $field == 'group_id' )
				{
					$this->group_join = true;
					$this->fields[] = USAM_TABLE_GROUP_RELATIONSHIPS.".group_id";
				}
				elseif ( isset($this->db_fields[$field]) )
					$this->fields[] = USAM_TABLE_COMPANY.".$field";
			}
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_COMPANY.".*";
		else
			$this->query_fields = implode( ',', $this->fields );				
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_COMPANY;
		$this->query_where = "WHERE 1=1";		
		
	// Обрабатывать другие параметры даты
		$date_parameters = array();
		foreach ( ['second', 'minute', 'hour', 'day', 'w', 'monthnum', 'year', 'column'] as $k ) 
		{		
			if ( isset($qv[$k]) && '' !== $qv[$k] )
				$date_parameters[$k] = $qv[$k];
		}
		if ( $date_parameters ) 
		{
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_COMPANY );
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_COMPANY );
			$this->query_where .= $this->date_query->get_sql();
		}
		if ( !empty( $qv['include'] ) ) 		
			$include = wp_parse_id_list( $qv['include'] );		 
		elseif ( isset($qv['include'] ) ) 		
			$include = array( 0 );		 
		else 		
			$include = false;
	
		$type = array();
		if ( isset($qv['type'] ) ) 
		{
			if ( is_array( $qv['type'] ) ) 
				$type = $qv['type'];			
			elseif ( is_string( $qv['type'] ) && !empty( $qv['type'] ) )
				$type = array_map( 'trim', explode( ',', $qv['type'] ) );
		}		
		$type__not_in = array();
		if ( isset($qv['type__not_in'] ) ) {
			$type__not_in = (array) $qv['type__not_in'];
		}
		if ( !empty( $type ) ) 
		{
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".type IN ('".implode( "','",  $type )."')";	
		}		
		if ( !empty($type__not_in) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".type NOT IN ('".implode( "','",  $type__not_in )."')";
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
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_ORDERS." ON (".USAM_TABLE_ORDERS.".company_id = ".USAM_TABLE_COMPANY.".id)";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SHIPPED_DOCUMENTS." ON (".USAM_TABLE_SHIPPED_DOCUMENTS.".order_id = ".USAM_TABLE_ORDERS.".id)";
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".storage_pickup IN ('".implode( "','",  $storage_pickup )."')";		
		}		
		if ( !empty($storage_pickup__not_in) ) 
		{			
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_ORDERS." ON (".USAM_TABLE_ORDERS.".company_id = ".USAM_TABLE_COMPANY.".id)";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SHIPPED_DOCUMENTS." ON (".USAM_TABLE_SHIPPED_DOCUMENTS.".order_id = ".USAM_TABLE_ORDERS.".id)";
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".storage_pickup NOT IN ('".implode( "','",  $storage_pickup__not_in )."')";
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
				$this->query_where .= " AND ".USAM_TABLE_COMPANY.".status IN ('".implode( "','", $status )."')";	
		}	
		if ( isset($qv['status__not_in']) ) 
		{
			$status__not_in = (array) $qv['status__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".status NOT IN ('".implode("','", $status__not_in )."')";
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
			$this->query_join .= " INNER JOIN ".USAM_TABLE_COMPANY_META." AS mc ON (mc.company_id = ".USAM_TABLE_COMPANY.".id)";
			$this->query_join .= " INNER JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." AS sl ON (mc.meta_value = sl.communication)";
				
			$this->query_where .= " AND sl.status IN ('".implode("','",  $status_subscriber )."')";		
		}
		if ( !empty($status_subscriber__not_in) ) 
		{			
			$this->query_join .= " INNER JOIN ".USAM_TABLE_COMPANY_META." AS mc_not_in ON (mc_not_in.company_id = ".USAM_TABLE_COMPANY.".id)";
			$this->query_join .= " INNER JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." AS sl_not_in ON (mc_not_in.meta_value = sl_not_in.communication)";
				
			$this->query_where .= " AND sl_not_in.status NOT IN ('".implode("','",  $status_subscriber__not_in )."')";	
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

		if ( !empty( $list_subscriber ) ) 
		{		
			$this->query_join .= " INNER JOIN ".USAM_TABLE_COMPANY_META." ON (".USAM_TABLE_COMPANY_META.".company_id = ".USAM_TABLE_COMPANY.".id)";
			$this->query_join .= " INNER JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." ON (".USAM_TABLE_COMPANY_META.".meta_value = ".USAM_TABLE_SUBSCRIBER_LISTS.".communication)";
				
			$this->query_where .= " AND ".USAM_TABLE_SUBSCRIBER_LISTS.".list IN (".implode( ',',  $list_subscriber ).")";		
		}
		if ( !empty($list_subscriber__not_in) ) 
		{ 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_COMPANY_META." ON (".USAM_TABLE_COMPANY_META.".company_id = ".USAM_TABLE_COMPANY.".id)";
			$this->query_join .= " INNER JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." ON (".USAM_TABLE_COMPANY_META.".meta_value = ".USAM_TABLE_SUBSCRIBER_LISTS.".communication)";
				
			$this->query_where .= " AND ".USAM_TABLE_SUBSCRIBER_LISTS.".list NOT IN (".implode( ',',  $list_subscriber__not_in ).")";	
		}	
		if ( !empty($qv['not_subscriber']) ) 
		{			
			$this->query_where .= " AND NOT EXISTS (SELECT 1
				FROM ".USAM_TABLE_COMPANY_META." INNER JOIN ".USAM_TABLE_SUBSCRIBER_LISTS." ON (".USAM_TABLE_COMPANY_META.".meta_value=".USAM_TABLE_SUBSCRIBER_LISTS.".communication) 
				WHERE ".USAM_TABLE_COMPANY_META.".company_id=".USAM_TABLE_COMPANY.".id )";
		}		
		
		$industry = array();
		if ( isset($qv['industry'] ) ) 
		{
			if ( is_array( $qv['industry'] ) ) 
				$industry = $qv['industry'];			
			elseif ( is_string( $qv['industry'] ) && !empty( $qv['industry'] ) )
				$industry = array_map( 'trim', explode( ',', $qv['industry'] ) );
		}
		$industry__in = array();
		if ( isset($qv['industry__in'] ) ) {
			$industry__in = (array) $qv['industry__in'];
		}
		$industry__not_in = array();
		if ( isset($qv['industry__not_in'] ) ) {
			$industry__not_in = (array) $qv['industry__not_in'];
		}
		if ( !empty( $industry ) ) 
		{
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".industry IN ('".implode( "','",  $industry )."')";	
		}		
		if ( !empty($industry__not_in) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".industry NOT IN (".implode( ',',  $industry__not_in ).")";
		}
		if ( !empty( $industry__in ) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".industry IN (".implode( ',',  $industry__in ).")";
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
					case 'group' :					
						$this->group_join = true;
						$ordersby_array[] = USAM_TABLE_GROUP_RELATIONSHIPS.".group_id";
					break;	
					case 'events' :					
						$this->query_join .= " INNER JOIN ".USAM_TABLE_RIBBON_LINKS." ON (".USAM_TABLE_RIBBON_LINKS.".object_id = ".USAM_TABLE_COMPANY.".id AND ".USAM_TABLE_RIBBON_LINKS.".object_type='company')";
						$ordersby_array[] = USAM_TABLE_RIBBON_LINKS.".object_id";
					break;					 
					default:
						$ordersby_array[] = USAM_TABLE_COMPANY.'.'.$_value;
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
		{
			// Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
		}	
		$orderby_array = array();
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
			$orderby_array[] = USAM_TABLE_COMPANY.".id $order";
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
			$search = trim($qv['search']);

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
				$search_columns = array_intersect( $qv['search_columns'], ['id', 'name','inn', 'email', 'phone', 'site', 'login'] );	
			if ( !$search_columns ) 
			{
				if ( false !== strpos( $search, '@') )
					$search_columns = array('email', 'login');
				elseif ( is_numeric($search) )
					$search_columns = array('id', 'name', 'phone', 'inn', 'login');		
				elseif ( $search[0] == '+' )			
					$search_columns = array( 'phone' );
				elseif ( false !== strpos( $search, '.') )	
					$search_columns = array('site' );					
				else			
					$search_columns = array('name', 'login');
			}				
			$search_columns = apply_filters( 'usam_companies_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}
		if ( !empty($include) ) 
		{
			$ids = implode( ',', $include );			
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".id NOT IN ($ids)";
		}			
		if ( isset($qv['name'] ) ) 
		{		
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".name=LOWER ('".$qv['name']."')";
		}			
		if ( isset($qv['manager_id']) ) 
		{
			$manager = is_array($qv['manager_id']) ? $qv['manager_id']: array( $qv['manager_id'] );		
			$manager = array_map('intval', $manager);			
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".manager_id IN ('".implode( "', '", $manager )."')";
		}	
		if ( isset($qv['open']) ) 
		{		
			$open = $qv['open'] == 1?1:0 ;
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".open='$open'";
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
			{
				$this->personal_accounts_join = true;
				$this->query_where .= " AND ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS.".user_id IN (".implode( ",", $user_id ).")";
			}
		}	
		if ( isset($qv['accounts']) ) 
		{					
			if ( $qv['accounts'] ) 
				$this->personal_accounts_join = true;
			else
			{
				$this->query_join .= " LEFT JOIN ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS." ON (".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS.".company_id = ".USAM_TABLE_COMPANY.".id)";
				$this->query_where .= " AND ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS.".company_id IS NULL";
			}
		}	
		if ( isset($qv['parents']) ) 
		{		
			$parent_ids = implode( "','", array_map('intval', (array)$qv['parents']) );	
			$this->query_where .= " AND ".USAM_TABLE_COMPANY.".parent_id IN ('$parent_ids')";
		}	
		if ( isset($qv['connection']) ) 
		{		
			$connection = implode( "','", array_map('intval', (array)$qv['connection']) );	
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_COMPANY_CONNECTIONS." AS connection ON (connection.connection_id=".USAM_TABLE_COMPANY.".id)";
			$this->query_where .= " AND connection.company_id IN ($connection)";
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
		if ( isset($qv['name']) ) 
		{
			$this->query_where .= " AND (".USAM_TABLE_COMPANY.".name='".$qv['name']."' OR ".USAM_TABLE_COMPANY.".id IN (SELECT DISTINCT ".USAM_TABLE_COMPANY_META.".company_id FROM ".USAM_TABLE_COMPANY_META." WHERE meta_key IN ('company_name', 'full_company_name') AND meta_value = '".$qv['name']."'))";
		}
		if ( isset($qv['sellers']) && $qv['sellers'] )
			$this->query_join .= " INNER JOIN ".USAM_TABLE_SELLERS." AS sellers ON (sellers.customer_id=".USAM_TABLE_COMPANY.".id AND sellers.seller_type='company')";
		$conditions_query = new USAM_Conditions_Query();
		$this->query_where .= $conditions_query->get_sql_clauses( $qv );		
		if ( $order_last_join )
		{
			$this->query_join .= "LEFT JOIN ".USAM_TABLE_ORDERS." AS last_order ON (last_order.company_id=".USAM_TABLE_COMPANY.".id AND last_order.id = (SELECT id FROM ".USAM_TABLE_ORDERS." WHERE company_id=".USAM_TABLE_COMPANY.".id ORDER BY id DESC LIMIT 1 ))";	
		}		
		if ( $this->group_join  ) 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_GROUP_RELATIONSHIPS." ON (".USAM_TABLE_GROUP_RELATIONSHIPS.".object_id = ".USAM_TABLE_COMPANY.".id)";
		if ( $this->personal_accounts_join  ) 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS." ON (".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS.".company_id = ".USAM_TABLE_COMPANY.".id)";
		if ( $this->newsletter_stat_join  ) 
		{
			$this->distinct = true;	
			$this->query_join .= " INNER JOIN ".USAM_TABLE_COMPANY_META." AS meta_communication ON (meta_communication.company_id=".USAM_TABLE_COMPANY.".id)";	
			$this->query_join .= " INNER JOIN ".USAM_TABLE_NEWSLETTER_USER_STAT." ON (".USAM_TABLE_NEWSLETTER_USER_STAT.".communication = meta_communication.meta_value)";
		}
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty( $this->meta_query->queries ) ) 
		{
			$clauses = $this->meta_query->get_sql( 'company', USAM_TABLE_COMPANY_META, USAM_TABLE_COMPANY, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( $this->meta_query->has_or_relation() )
				$this->distinct = true;	
		}	
		if ( $this->distinct ) 
			$this->query_fields = 'DISTINCT ' . $this->query_fields;		
		do_action_ref_array( 'usam_pre_companies_query', array( &$this ) );
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
		foreach(['unsub', 'clicked'] as $column ) 
		{			
			if ( isset($data->$column) )
				$data->$column = (int)$data->$column;			
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_company' );	
		return $data;
	}

	/**
	 * Execute the query, with the current variables.	
	 */
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
					
		if ( $qv['number'] != 1 && isset($this->results[0]->id) )		
		{ 
			$ids = array();	
			foreach ( $this->results as $result ) 
				$ids[] = $result->id; 
			if ( $qv['cache_case'] )		
			{
				usam_update_affairs_cache( $ids, 'company' );
			}
			if ( $qv['cache_bank_accounts'] )		
			{
				require_once(USAM_FILE_PATH.'/includes/crm/bank_accounts_query.class.php');	
				$bank_accounts = usam_get_bank_accounts(['company_id' => $ids]);
				$results_cache = array();
				foreach ( $bank_accounts as $bank_account )
				{
					$results_cache[$bank_account->company_id][] = $bank_account;
				} 
				foreach ( $ids as $id )
				{
					if ( isset($results_cache[$id]) )											
						wp_cache_set( $id, $results_cache[$id], 'usam_bank_accounts' );
					else
						wp_cache_set( $id, [], 'usam_bank_accounts' );	
				}
			}
			if ( $qv['cache_meta'] || $qv['cache_thumbnail'] )		
			{ 
				usam_update_cache( $ids, [USAM_TABLE_COMPANY_META => 'company_meta'], 'company_id' );
			}		
			if ( $qv['cache_thumbnail'] )		
			{
				$thumb_ids = [];	
				foreach ( $this->results as $result ) 
				{				
					$thumbnail_id = usam_get_company_metadata( $result->id, 'logo' );
					if ( $thumbnail_id )
						$thumb_ids[] = $thumbnail_id; 					
				}
				if ( !empty($thumb_ids) )
					_prime_post_caches( $thumb_ids, false, true );
			}				
		}	
		$r = [];
		if ( 'id=>name' == $qv['fields']) 
		{					
			foreach ($this->results as $key => $result ) 
				$r[$result->id] = $result->name;
			$this->results = $r;
		}	
		elseif ( 'id=>data' == $qv['fields']) 
		{					
			foreach ($this->results as $key => $result ) 
				$r[$result->id] = $result;
			$this->results = $r;
		}		
	}

	/**
	 * Retrieve query variable.
	 */
	public function get( $query_var ) 
	{
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
				$searches[] = $wpdb->prepare( USAM_TABLE_COMPANY.".$col = %s", $string );
			elseif ( 'login' == $col )
			{
				$user = get_user_by( 'login', $string );
				if ( !empty($user->ID) )
					$searches[] = USAM_TABLE_COMPANY.".user_id='$user->ID'";	
			}	
			elseif ( 'name' == $col )
				$searches[] = USAM_TABLE_COMPANY.'.'.$wpdb->prepare( "$col LIKE LOWER ('%s')", "%".$wpdb->esc_like( $string )."%" );
			elseif ( 'phone' == $col || 'email' == $col || 'site' == $col )
			{				
				if ( 'email' == $col )
					$like = $leading_wild . addslashes( $string ) . $trailing_wild;					
				$searches[] = $wpdb->prepare( USAM_TABLE_COMPANY.".id IN (SELECT DISTINCT ".USAM_TABLE_COMPANY_META.".company_id FROM ".USAM_TABLE_COMPANY_META." WHERE meta_value LIKE %s)", $like );		
			}	
			elseif ( 'inn' == $col )
			{
				$searches[] = $wpdb->prepare( USAM_TABLE_COMPANY.".id IN (SELECT DISTINCT ".USAM_TABLE_COMPANY_META.".company_id FROM ".USAM_TABLE_COMPANY_META." WHERE meta_value LIKE %s AND meta_key='$col')", $like );	
			}	
			elseif ( 'acc_number' == $col )
			{
				$searches[] = $wpdb->prepare( USAM_TABLE_COMPANY.".id IN (SELECT DISTINCT ".USAM_TABLE_COMPANY_ACC_NUMBER.".company_id FROM ".USAM_TABLE_COMPANY_ACC_NUMBER." WHERE number LIKE %s)", $like );	
			}	
			elseif ( 'group' == $col )
			{
				$this->query_join .= " LEFT JOIN ".USAM_TABLE_GROUP_RELATIONSHIPS." ON (".USAM_TABLE_GROUP_RELATIONSHIPS.".object_id = ".USAM_TABLE_COMPANY.".id)";
				$this->query_join .= " LEFT JOIN ".USAM_TABLE_GROUPS." ON (".USAM_TABLE_GROUPS.".id = ".USAM_TABLE_GROUP_RELATIONSHIPS.".group_id)";	
				$searches[] = USAM_TABLE_GROUPS.".name LIKE LOWER('".addslashes( $string )."')";
			} 
			else 
			{
				$searches[] = $wpdb->prepare( USAM_TABLE_COMPANY.".$col LIKE %s", $like );
			}
		}		
		return ' AND (' . implode(' OR ', $searches) . ')';
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
			$_orderby = USAM_TABLE_COMPANY.'.'.$orderby;
		elseif ( $orderby == 'date' )
			$_orderby = USAM_TABLE_COMPANY.'.date_insert';
		elseif ( $orderby == 'sum' )
			$_orderby = USAM_TABLE_COMPANY.'.total_purchased';
		elseif ( in_array($orderby, ['opened_at' , 'clicked' , 'unsub']) )
			$_orderby = USAM_TABLE_NEWSLETTER_USER_STAT.'.'.$orderby;
		elseif ( 'meta_value' == $orderby ) 
			$_orderby = USAM_TABLE_COMPANY_META.".meta_value";
		elseif ( 'meta_value_num' == $orderby ) 
			$_orderby = "CAST(".USAM_TABLE_COMPANY_META.".meta_value AS signed)";
		elseif ( 'count' == $orderby ) 
			$_orderby = 'COUNT(id)';
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) ) {
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_COMPANY.".id, $include_sql )";
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

function usam_get_companies( $query_vars = [] )
{	
	$query_vars['count_total'] = false;
	$query = new USAM_Companies_Query( $query_vars );		
	return $query->get_results();	
}	