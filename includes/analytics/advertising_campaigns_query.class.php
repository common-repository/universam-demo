<?php
// Класс работы с рекламными компаниями
class USAM_Advertising_Campaigns_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $meta_query = false;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'title' => 's', 'description' => 's', 'code' => 's', 'redirect' => 's', 'source' => 's', 'medium' => 's', 'term' => 's', 'content' => 's', 'transitions' => 'd',  'date_insert' => 's'];
	
	// SQL clauses
	public $query_fields;
	public $query_from;
	public $query_join;	
	public $query_where;
	public $query_orderby;
	public $query_groupby;	 
	public $query_limit;
	
	public $date_query;	
	
	public function __construct( $query = null ) 
	{
		require_once( USAM_FILE_PATH . '/includes/query/date.php' );
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
			'id'  => '',			
			'date_insert'  => '',
			'folder' => '',						
			'include' => array(),
			'exclude' => array(),
			'search' => '',
			'search_columns' => array(),
			'orderby' => 'id',
			'order' => 'ASC',
			'offset' => '',
			'number' => '',
			'paged' => 1,
			'count_total' => true,
			'cache_results' => false,
			'cache_attachments' => false,			
			'fields' => 'all',	
			'add_fields' => ''			
		);		
		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Prepare the query variables.
	 *
	 * @since 3.1.0
	 * @since 4.1.0 Added the ability to order by the `include` value.
	 * @since 4.2.0 Added 'meta_value_num' support for `$orderby` parameter. Added multi-dimensional array syntax
	 *              for `$orderby` parameter.
	 * @since 4.3.0 Added 'has_published_posts' parameter.
	 * @since 4.4.0 Added 'paged', 'from_email__in', and 'from_email__not_in' parameters. The 'status' parameter was updated to
	 *              permit an array or comma-separated list of values. The 'number' parameter was updated to support
	 *              querying for all users with using -1.
	
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @global int  $blog_id
	 *
	 * @param string|array $query {
	 *     Optional. Array or string of Query parameters.	
	 *     @type string|array $status                An array or a comma-separated list of status names that users must match
	 *                                             to be included in results. Note that this is an inclusive list: users
	 *                                             must match *each* status. Default empty.
	 *     @type array        $from_email__in            An array of status names. Matched users must have at least one of these
	 *                                             processes. Default empty array.
	 *     @type array        $from_email__not_in        
	 *                                             processes will not be included in results. Default empty array.
	 *     @type string       $meta_key            User meta key. Default empty.
	 *     @type string       $meta_value          User meta value. Default empty.
	 *     @type string       $meta_compare        Comparison operator to test the `$meta_value`. Accepts '=', '!=',
	 *                                             '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
	 *                                             'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS', 'REGEXP',
	 *                                             'NOT REGEXP', or 'RLIKE'. Default '='.
	 *     @type array        $include             An array of user IDs to include. Default empty array.
	 *     @type array        $exclude             An array of user IDs to exclude. Default empty array.
	 *     @type string       $search              Search keyword. Searches for possible string matches on columns.
	 *                                             When `$search_columns` is left empty, it tries to determine which
	 *                                             column to search in based on search string. Default empty.
	 *     @type array        $search_columns      Array of column names to be searched. Accepts 'ID', 'login',
	 *                                             'nicename', 'email', 'url'. Default empty array.
	 *     @type string|array $orderby             Field(s) to sort the retrieved users by. May be a single value,
	 *                                             an array of values, or a multi-dimensional array with fields as
	 *                                             keys and orders ('ASC' or 'DESC') as values. Accepted values are
	 *                                             'ID', 'display_name' (or 'name'), 'include', 'user_login'
	 *                                             (or 'login'), 'user_nicename' (or 'nicename'), 'user_email'
	 *                                             (or 'email'), 'user_url' (or 'url'), 'user_registered'
	 *                                             or 'registered'), 'post_count', 'meta_value', 'meta_value_num',
	 *                                             the value of `$meta_key`, or an array key of `$meta_query`. To use
	 *                                             'meta_value' or 'meta_value_num', `$meta_key` must be also be
	 *                                             defined. Default 'user_login'.
	 *     @type string       $order               Designates ascending or descending order of users. Order values
	 *                                             passed as part of an `$orderby` array take precedence over this
	 *                                             parameter. Accepts 'ASC', 'DESC'. Default 'ASC'.
	 *     @type int          $offset              Number of users to offset in retrieved results. Can be used in
	 *                                             conjunction with pagination. Default 0.
	 *     @type int          $number              Number of users to limit the query for. Can be used in
	 *                                             conjunction with pagination. Value -1 (all) is supported, but
	 *                                             should be used with caution on larger sites.
	 *                                             Default empty (all users).
	 *     @type int          $paged               When used with number, defines the page of results to return.
	 *                                             Default 1.
	 *     @type bool         $count_total         Whether to count the total number of users found. If pagination
	 *                                             is not needed, setting this to false can improve performance.
	 *                                             Default true.
	 *     @type string|array $fields              Which fields to return. Single or all fields (string), or array
	 *                                             of fields. Accepts 'ID', 'display_name', 'user_login',
	 *                                             'user_nicename', 'user_email', 'user_url', 'user_registered'.
	 *                                             Use 'all' for all fields and 'all_with_meta' to include
	 *                                             meta fields. Default 'all'.
	 */
	public function prepare_query( $query = array() ) 
	{
		global $wpdb;

		if ( empty( $this->query_vars ) || !empty( $query ) ) {
			$this->query_limit = null;
			$this->query_vars = $this->fill_query_vars( $query );
		}			
		do_action( 'usam_pre_get_advertising_campaigns', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		
		
		$join = [];			
		if ( 'id=>title' == $qv['fields']) 			
			$fields = ['id' , 'title'];	
		else
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
				$this->fields[] = USAM_TABLE_CAMPAIGNS.".*";
			elseif ( $field == 'count' )
				$this->fields[] = "COUNT(*) AS count";	
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_CAMPAIGNS.".$field";			
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_CAMPAIGNS.".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_CAMPAIGNS;
		$this->query_where = "WHERE 1=1";		
		
	// Обрабатывать другие параметры даты
		$date_parameters = array();
		foreach( ['second', 'minute', 'hour', 'day', 'w', 'monthnum', 'year'] as $k ) 
		{					
			if ( '' !== $qv[$k] )
				$date_parameters[$k] = $qv[$k];
		}
		if ( $date_parameters ) 
		{
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_CAMPAIGNS );
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_CAMPAIGNS );
			$this->query_where .= $this->date_query->get_sql();
		}
		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( !empty( $qv['include'] ) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;
			
		$code = array();
		if ( isset($qv['code'] ) ) 
		{
			if ( is_array( $qv['code'] ) ) 
				$code = $qv['code'];			
			elseif ( is_string( $qv['code'] ) && !empty( $qv['code'] ) )
				$code = array_map( 'trim', explode( ',', $qv['code'] ) );
			elseif ( is_numeric( $qv['code'] ) )
				$code = $qv['code'];
		}		
		$code__not_in = array();
		if ( isset($qv['code__not_in'] ) ) {
			$code__not_in = (array) $qv['code__not_in'];
		}
		if ( !empty( $code ) ) 
		{
			$this->query_where .= " AND ".USAM_TABLE_CAMPAIGNS.".code IN ('".implode( "','",  $code )."')";		
		}		
		if ( !empty($code__not_in) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_CAMPAIGNS.".code NOT IN ('".implode( "','",  $code__not_in )."')";
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
			{	// Integer key means this is a flat array of 'orderby' fields.
				$_orderby = $_value;
				$_order = $order;
			} 
			else 
			{	// Non-integer key means this the key is the field and the value is ASC/DESC.
				$_orderby = $_key;
				$_order = $_value;
			}
			$parsed = $this->parse_orderby( $_orderby );
			if ( ! $parsed ) {
				continue;
			}
			$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
		}

		// If no valid clauses were found, order by id.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = USAM_TABLE_CAMPAIGNS.".id $order";
		}
		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		// limit
		if ( isset($qv['number'] ) && $qv['number'] > 0 ) {
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

			$search_columns = array();
			if ( $qv['search_columns'] )			
				$search_columns = array_intersect( $qv['search_columns'], ['id', 'title', 'description', 'code', 'source', 'medium'] );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array('id' );				
				else
					$search_columns = ['title', 'description', 'code', 'source', 'medium'];
			}	
			$search_columns = apply_filters( 'usam_advertising_campaigns_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( !empty( $include ) ) 
		{	
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_CAMPAIGNS.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_CAMPAIGNS.".id NOT IN ($ids)";
		}
		if ( !empty($qv['source']) ) 
		{		
			$source = implode("','",  (array)$qv['source'] );		
			$this->query_where .= " AND ".USAM_TABLE_CAMPAIGNS.".source IN ('$source')";
		}	
		if ( !empty($qv['medium']) ) 
		{		
			$medium = implode( "','",  (array)$qv['medium'] );		
			$this->query_where .= " AND ".USAM_TABLE_CAMPAIGNS.".medium IN ('$medium')";
		}		
		$conditions_query = new USAM_Conditions_Query();
		$this->query_where .= $conditions_query->get_sql_clauses( $qv );
		do_action_ref_array( 'usam_pre_advertising_campaigns_query', array( &$this ) );
	}
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( $type == 'd' && isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_advertising_campaign' );	
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
			$this->total = $wpdb->get_var( apply_filters( 'found_advertising_campaigns_query', 'SELECT FOUND_ROWS()' ) );	

		if ( 'id=>title' == $qv['fields'] ) 
		{					
			foreach ($this->results as $key => $result ) 
				$r[$result->id] = $result->title;
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

	/**
	 * Set query variable.
	 */
	public function set( $query_var, $value ) {
		$this->query_vars[$query_var] = $value;
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns
	 */
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
				$searches[] = $wpdb->prepare( USAM_TABLE_CAMPAIGNS.".$col = %s", $string );
			elseif ( 'title' == $col || 'description' == $col )
				$searches[] = USAM_TABLE_CAMPAIGNS.'.'.$wpdb->prepare( "$col LIKE LOWER ('%s')", "%".$wpdb->esc_like( $string )."%" );
			else
				$searches[] = $wpdb->prepare( USAM_TABLE_CAMPAIGNS.".$col LIKE %s", $like );
		}
		return ' AND (' . implode(' OR ', $searches) . ')';
	}

	public function get_results() 
	{
		return $this->results;
	}
	
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
			$_orderby = USAM_TABLE_CAMPAIGNS.'.'.$orderby;	
		elseif ( $orderby == 'date' )
			$_orderby = USAM_TABLE_CAMPAIGNS.'.date_insert';	
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_CAMPAIGNS.".id, $include_sql )";
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
	public function __set( $name, $value ) 
	{
		if ( in_array( $name, $this->compat_fields ) ) {
			return $this->$name = $value;
		}
	}

	/**
	 * Make private properties checkable for backwards compatibility.
	 */
	public function __isset($name ) 
	{
		if ( in_array( $name, $this->compat_fields ) ) {
			return isset($this->$name );
		}
	}

	/**
	 * Make private properties un-settable for backwards compatibility.
	 */
	public function __unset( $name ) 
	{
		if ( in_array( $name, $this->compat_fields ) ) {
			unset( $this->$name );
		}
	}

	/**
	 * Make private/protected methods readable for backwards compatibility.
	 */
	public function __call( $name, $arguments )
	{
		if ( 'get_search_sql' === $name ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}
		return false;
	}
}

function usam_get_advertising_campaigns( $args = array() )
{
	$args['count_total'] = false;
	$class = new USAM_Advertising_Campaigns_Query( $args );	
	$result = $class->get_results();	
	
	return $result;
}