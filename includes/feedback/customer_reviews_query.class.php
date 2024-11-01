<?php
// Класс работы с отзывами клиентов

class USAM_Customer_Reviews_Query 
{
	public $query_vars = array();
	private $results;	
	private $total = 0;
	public $request;
	public $meta_query = false;
	public $contact_join = false;	

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'date_insert' => 's', 'contact_id' => 'd', 'title' => 's', 'review_text' => 's', 'review_response' => 's', 'status' => 'd', 'rating' => 'd', 'page_id' => 'd'];

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
		require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
		require_once( USAM_FILE_PATH . '/includes/query/date.php' );
		if ( !empty( $query ) ) 
		{
			$this->prepare_query( $query );
			$this->query();
		}
	}	

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
			'include' => array(),
			'exclude' => array(),
			'search' => '',
			'search_columns' => array(),
			'orderby' => 'id',
			'order' => 'ASC',
			'offset' => 0,
			'number' => 0,
			'paged' => 1,
			'count_total' => true,
			'cache_results' => false,	
			'cache_meta' => false,	
			'cache_contacts' => false,	
			'cache_attachments' => false,			
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
		do_action( 'usam_pre_get_customer_reviews', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );
		
		$qv['paged'] = (int)$qv['paged'];
		$qv['number'] = (int)$qv['number'];
		$qv['offset'] = (int)$qv['offset'];
				
		$join = array();			
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
				$this->fields[] = USAM_TABLE_CUSTOMER_REVIEWS.".*";	
			elseif ( $field == 'count' )				
				$this->fields[] = "COUNT(*) AS count";				
			elseif ( $field == 'max' )
				$this->fields[] = "MAX(rating) AS max";
			elseif ( $field == 'aggregate' )
				$this->fields[] = "AVG(rating) AS aggregate";			
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_CUSTOMER_REVIEWS.".$field";
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_CUSTOMER_REVIEWS.".*";
		else
			$this->query_fields = implode(',', $this->fields );			

		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );

		$this->query_from = "FROM ".USAM_TABLE_CUSTOMER_REVIEWS;
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
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_CUSTOMER_REVIEWS );
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_CUSTOMER_REVIEWS );
			$this->query_where .= $this->date_query->get_sql();
		}
		if ( !empty($qv['include']) ) {
			$include = wp_parse_id_list( $qv['include'] );
		} else {
			$include = false;
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
				$this->query_where .= " AND ".USAM_TABLE_CUSTOMER_REVIEWS.".status IN ('".implode( "','", $status )."')";	
		}	
		if ( !empty($qv['status__not_in']) ) 
		{
			$status__not_in = (array) $qv['status__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_CUSTOMER_REVIEWS.".status NOT IN ('".implode("','", $status__not_in )."')";
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
				$this->contact_join = true;
				$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".user_id IN (".implode( ",", $user_id ).")";	
			}
		}	
		if ( isset($qv['user_id__not_in']) ) 
		{
			$user_id__not_in = (array) $qv['user_id__not_in'];
			$this->contact_join = true;
			$this->query_where .= " AND ".USAM_TABLE_CONTACTS.".user_id NOT IN (".implode(",", $user_id__not_in ).")";
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
			{
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

		// If no valid clauses were found, order by id.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = USAM_TABLE_CUSTOMER_REVIEWS.".id $order";
		}

		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );
		// limit
		if ( isset($qv['number'] ) && $qv['number'] > 0 ) 
		{
			if ( $qv['offset'] ) 			
				$this->query_limit = $wpdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
			else 
			{				
				$this->query_limit = $wpdb->prepare( "LIMIT %d, %d", $qv['number'] * ( $qv['paged'] - 1 ), $qv['number'] );
			}
		}
		$search = '';
		if ( isset($qv['search'] ) )
			$search = trim( $qv['search'] );

		if ( $search ) {
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
				$search_columns = array_intersect( $qv['search_columns'], array( 'id', 'name', 'mail', 'review_text' , 'review_response'  ) );
			if ( ! $search_columns ) 
			{
				if ( false !== strpos( $search, '@') )
					$search_columns = array('mail');
				elseif ( is_numeric($search) )
					$search_columns = array('id' );				
				else
					$search_columns = array('id', 'name', 'review_text', 'review_response');
			}	
			$search_columns = apply_filters( 'usam_customer_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( !empty( $include ) ) 
		{
			// Sanitized earlier.
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_CUSTOMER_REVIEWS.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_CUSTOMER_REVIEWS.".id NOT IN ($ids)";
		}
		if ( isset($qv['page_id']) ) 
		{		
			$page_id = implode( ',',  wp_parse_id_list( (array)$qv['page_id'] ) );		
			$this->query_where .= " AND ".USAM_TABLE_CUSTOMER_REVIEWS.".page_id IN ($page_id)";
		}	
		if ( isset($qv['page_id__not_in']) ) 
		{
			$page_id__not_in = (array)$qv['page_id__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_CUSTOMER_REVIEWS.".page_id NOT IN (".implode(",", $page_id__not_in ).")";
		}
		if ( isset($qv['post_type']) ) 
		{
			global $wpdb;
			$post_type = (array)$qv['post_type'];
			$this->query_join .= " INNER JOIN ".$wpdb->posts." ON (".USAM_TABLE_CUSTOMER_REVIEWS.".page_id = ".$wpdb->posts.".id)";
			$this->query_where .= " AND ".$wpdb->posts.".post_type IN ('".implode("','", $post_type )."')"; 
		}
		if ( !empty($qv['contacts']) ) 
		{		
			$contacts = implode( ',',  (array)$qv['contacts'] );		
			$this->query_where .= " AND ".USAM_TABLE_CUSTOMER_REVIEWS.".contact_id IN ($contacts)";
		}	
		if ( !empty($qv['media']) ) 
		{	
			$types = ['image/png', 'image/jpeg', 'image/webp'];			
			$this->query_join .= " INNER JOIN ".USAM_TABLE_FILES." ON (".USAM_TABLE_FILES.".type='review' AND ".USAM_TABLE_FILES.".object_id = ".USAM_TABLE_CUSTOMER_REVIEWS.".id)";
			$this->query_where .= " AND ".USAM_TABLE_FILES.".mime_type IN ('".implode("','",$types)."')";
			if ( strripos($this->query_fields, 'DISTINCT')  === false )
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
		}		
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty( $this->meta_query->queries ) ) 
		{
			$clauses = $this->meta_query->get_sql( 'review', USAM_TABLE_CUSTOMER_REVIEW_META, USAM_TABLE_CUSTOMER_REVIEWS, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where']; 
			if ( $this->meta_query->has_or_relation() && strripos($this->query_fields, 'DISTINCT')  === false )
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
		}	
		if( $this->contact_join )
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CONTACTS." ON (".USAM_TABLE_CONTACTS.".id = ".USAM_TABLE_CUSTOMER_REVIEWS.".contact_id)";		
		do_action_ref_array( 'usam_pre_customer_reviews_query', array( &$this ) );
	}
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( $type == 'd' && isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_review' );	
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
		
		if ( $qv['cache_meta'] )
		{
			$ids = array();	
			foreach ( $this->results as $result ) 
			{
				$ids[] = $result->id; 					
			}	
			usam_update_cache( $ids, [USAM_TABLE_CUSTOMER_REVIEW_META => 'review_meta'], 'review_id' );	
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
				usam_get_contacts(['include' => $ids, 'cache_results' => true, 'cache_thumbnail' => true, 'manager_id' => 'all']);
		}	
		if ( $qv['cache_attachments'] )
		{
			$ids = array();	
			foreach ( $this->results as $result ) 
			{
				if ( isset($result->id) )
					$ids[] = $result->id;
			}	
			usam_cache_attachments($ids, 'review');
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
			if ( 'title' == $col || 'review_text' == $col || 'review_response' == $col  ) {
				$searches[] = $wpdb->prepare( "$col LOWER ('%%s%')", $like );
			} else {				
				$searches[] = $wpdb->prepare( "$col = %s", $string );				
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

	/**
	 * Разобрать и очистить ключи 'orderby'
	 */
	protected function parse_orderby( $orderby ) 
	{
		$_orderby = '';
		if ( isset($this->db_fields[$orderby]) )
			$_orderby = USAM_TABLE_CUSTOMER_REVIEWS.'.'.$orderby;
		elseif ( 'date' == $orderby ) 
			$_orderby =  USAM_TABLE_CUSTOMER_REVIEWS.'.date_insert';
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) ) 
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_CUSTOMER_REVIEWS.".id, $include_sql )";
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

function usam_get_customer_reviews( $query = array() )
{	
	$query['count_total'] = false;
	$reviews = new USAM_Customer_Reviews_Query( $query );	
	return $reviews->get_results();	
}