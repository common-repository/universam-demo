<?php
// Класс работы с продуктами клиентов
class USAM_User_Posts_Query
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $meta_query = false;
	public $request;

	private $compat_fields = array( 'results' );

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
		if ( $query !== null ) 
		{
			$this->prepare_query( $query );
			$this->query();
		}
	}

	public static function fill_query_vars( $args ) 
	{			
		$defaults = array(			
			'id'  => '',				
			'active' => 1, 
			'type' => '',			
			'product_id' => '',		
			'product_id__in' => array(),
			'product_id__not_in' => array(),						
			'include' => array(),
			'exclude' => array(),
			'search' => '',
			'reserve' => '',			
			'search_columns' => array(),
			'orderby' => 'date_insert',
			'order' => 'DESC',
			'offset' => '',
			'number' => '',
			'paged' => 1,
			'count_total' => false,
			'cache' => false,			
			'products_cache' => false,					
			'fields' => 'all',
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
		do_action( 'usam_pre_get_user_posts', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		
		$join = array();	

		if ( 'all' == $qv['fields'] ) 		
			$this->query_fields = USAM_TABLE_USER_POSTS.".*";
		else
		{
			if ( !is_array( $qv['fields'] ) )				
				$fields = array( $qv['fields'] );
			else
				$fields = $qv['fields'];
			
			$fields = array_unique( $fields );

			$this->query_fields = array();
			foreach ( $fields as $field ) 
			{	
				if ( $field == 'count' )
					$this->query_fields[] = "COUNT(*) AS count";			
				else
				{
					$field = 'id' === $field ? 'id' : sanitize_key( $field );
					$this->query_fields[] = USAM_TABLE_USER_POSTS.".$field";
				}
			}
			$this->query_fields = implode( ',', $this->query_fields );
		} 
		
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;

		
		$this->query_join = implode( ' ', $join );

		$this->query_from = "FROM ".USAM_TABLE_USER_POSTS;
		$this->query_where = "WHERE 1=1";			
	
		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( !empty( $qv['include'] ) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;
			
		$product_id = array();
		if ( isset($qv['product_id'] ) ) 
		{
			if ( is_array( $qv['product_id'] ) ) 
				$product_id = $qv['product_id'];			
			elseif ( is_string( $qv['product_id'] ) && !empty( $qv['product_id'] ) )
				$product_id = array_map( 'trim', explode( ',', $qv['product_id'] ) );
			elseif ( is_numeric( $qv['product_id'] ) )
				$product_id = array( $qv['product_id']);
		}
		$product_id__in = array();
		if ( isset($qv['product_id__in'] ) ) {
			$product_id__in = (array) $qv['product_id__in'];
		}
		$product_id__not_in = array();
		if ( !empty( $qv['product_id__not_in'] ) ) {
			$product_id__not_in = (array) $qv['product_id__not_in'];
		}
		if ( !empty( $product_id ) ) 
		{
			$this->query_where .= " AND ".USAM_TABLE_USER_POSTS.".product_id IN (".implode( ',',  $product_id ).")";		
		}		
		if ( !empty($product_id__not_in) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_USER_POSTS.".product_id NOT IN (".implode( ',', $product_id__not_in ).")";
		}
		if ( !empty( $product_id__in ) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_USER_POSTS.".product_id IN (".implode( ',',  $product_id__in ).")";
		}		
		
		$contact_id = array();
		if ( isset($qv['contact_id'] ) ) 
		{
			if ( is_array( $qv['contact_id'] ) ) 
				$contact_id = $qv['contact_id'];			
			elseif ( is_string( $qv['contact_id'] ) && !empty( $qv['contact_id'] ) )
				$contact_id = array_map( 'trim', explode( ',', $qv['contact_id'] ) );
			elseif ( is_numeric( $qv['contact_id'] ) )
				$contact_id = array( $qv['contact_id'] );
		}
		$contact_id__in = array();
		if ( isset($qv['contact_id__in'] ) ) {
			$contact_id__in = (array) $qv['contact_id__in'];
		}

		$contact_id__not_in = array();
		if ( isset($qv['contact_id__not_in'] ) ) {
			$contact_id__not_in = (array) $qv['contact_id__not_in'];
		}
		if ( !empty( $contact_id ) ) 
		{
			$this->query_where .= " AND ".USAM_TABLE_USER_POSTS.".contact_id IN (".implode( ',',  $contact_id ).")";		
		}		
		if ( !empty($contact_id__not_in) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_USER_POSTS.".contact_id NOT IN (".implode( ',',  $contact_id__not_in ).")";
		}
		if ( !empty( $contact_id__in ) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_USER_POSTS.".contact_id IN (".implode( ',',  $contact_id__in ).")";
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
		{			
			$ordersby = array( 'sort' => $order );
		} 
		elseif ( is_array( $qv['orderby'] ) ) 
		{
			$ordersby = $qv['orderby'];
		} 
		else 
		{// Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
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
			{	// Non-integer key means this the key is the field and the value is ASC/DESC.
				$_orderby = $_key;
				$_order = $_value;
			}
			$parsed = $this->parse_orderby( $_orderby );
			if ( ! $parsed ) {
				continue;
			}
			$orderby_array[] = USAM_TABLE_USER_POSTS.'.'.$parsed . ' ' . $this->parse_order( $_order );
		}

		// If no valid clauses were found, order by id.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = USAM_TABLE_USER_POSTS.".id $order";
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
				$search_columns = array_intersect( $qv['search_columns'], ['id', 'product_name', 'product_sku', 'user'] );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = ['id'];				
				else
					$search_columns = ['user', 'product_sku', 'product_name'];
			}	
			$search_columns = apply_filters( 'usam_user_posts_search_columns', $search_columns, $search, $this );
			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( !empty( $include ) ) 
		{	// Sanitized earlier.
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_USER_POSTS.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_USER_POSTS.".id NOT IN ($ids)";
		}		
		if ( !empty($qv['user_list']) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_USER_POSTS.".user_list='".$qv['user_list']."'";
		}					
		do_action_ref_array( 'usam_pre_user_posts_query', array( &$this ) );
	}

	/**
	 * Execute the query, with the current variables.	
	 */
	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;

		$this->request = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";
	
		if ( is_array( $qv['fields'] ) || 'all' == $qv['fields'] && $qv['number'] != 1 ) 		
			$this->results = $wpdb->get_results( $this->request );		
		elseif ( $qv['number'] == 1 && 'all' !== $qv['fields'] )		
			$this->results = $wpdb->get_var( $this->request );
		elseif ( $qv['number'] == 1 && 'all' == $qv['fields'] )		
			$this->results = $wpdb->get_row( $this->request );
		else 
			$this->results = $wpdb->get_col( $this->request );
			
		if ( !$this->results )
			return;	
		
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_user_posts_query', 'SELECT FOUND_ROWS()' ) );		
		
		if ( 'all' == $qv['fields'] && $qv['cache'] )
		{
			if ( $qv['number'] == 1 )
				wp_cache_set( $this->results->id, (array)$this->results, 'usam_user_post' );	
			else
			{					
				foreach ( $this->results as $result ) 
				{
					wp_cache_set( $result->id, (array)$result, 'usam_user_post' );				
				}
			}
		}				
		if ( $qv['products_cache'] )
		{ 
			$product_ids = array();				
			foreach ( $this->results as $result ) 
			{
				if ( !isset($result->product_id) )
					break;
				$product_ids[] = $result->product_id;						
			}
			if ( !empty($product_ids) )
			{ 
				$args = array( 'fields' => 'ids', 'post__in' => $product_ids, 'cache_results' => true );				
				$product_ids = usam_get_products( $args, true );	
			}
		}			
	}
	
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
			{
				$searches[] = $wpdb->prepare( "$col = %s", $string );
			} 
			elseif ( 'product_sku' == $col )
			{
				$product_id = usam_get_product_id_by_sku( $string );
				$searches[] = $wpdb->prepare( "product_id='%d'", $product_id );				
			}
			elseif ( 'product_name' == $col )
			{		
				$searches[] = $wpdb->prepare( "product_id IN (SELECT ID FROM $wpdb->posts WHERE post_title LIKE '%s' AND post_type = 'usam-product')", $like );
			}			
			else
			{
				$searches[] = $wpdb->prepare( "$col LIKE %s", $like );
			}
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

		if ( in_array( $orderby, ['id', 'product_id', 'contact_id', 'date_insert']) )
		{
			$_orderby = $orderby;
		}		
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_USER_POSTS.".id, $include_sql )";
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

	public function __unset( $name ) 
	{
		if ( in_array( $name, $this->compat_fields ) ) {
			unset( $this->$name );
		}
	}

	public function __call( $name, $arguments )
	{
		if ( 'get_search_sql' === $name ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}
		return false;
	}
}

function usam_get_user_posts( $args = array() )
{	
	$args['count_total'] = false;	
	$class = new USAM_User_Posts_Query( $args );	
	$results = $class->get_results();	
	
	return $results;
}

function usam_check_user_product_by_list( $product_id, $list )
{	
	if ( !is_numeric($product_id) )
		return false;
	
	$cache_key = 'usam_user_product_list';
	$cache = wp_cache_get( $product_id, $cache_key );
	if( $cache === false )	
	{		
		$cache = usam_get_user_posts(['product_id' => $product_id]);
		wp_cache_set( $product_id, $cache, $cache_key );
	}	
	$result = [];
	foreach ( $cache as $product ) 
	{
		if ( $product->user_list == $list )
			return true;
	}
	return false;
}