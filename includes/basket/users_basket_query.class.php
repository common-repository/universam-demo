<?php
// Класс работы с корзинами покупателей
class USAM_Users_Basket_Query
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
		require_once( USAM_FILE_PATH . '/includes/query/date.php' );
		require_once( USAM_FILE_PATH . '/includes/query/conditions_query.php' );
		if ( $query !== null ) 
		{
			$this->prepare_query( $query );
			$this->query();
		}
	}

	public static function fill_query_vars( $args ) 
	{			
		$defaults = [		
			'second' => '',
			'minute' => '',
			'hour' => '',
			'day' => '',
			'monthnum' => '',
			'year' => '',
			'w' => '',
			'id'  => '',				
			'active' => 1, 
			'type' => '',								
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
			'cache_meta' => false,				
			'cache_products' => false,				
			'fields' => 'all',			
		];		
		return wp_parse_args( $args, $defaults );
	}

	public function prepare_query( $query = array() ) 
	{
		global $wpdb;

		if ( empty( $this->query_vars ) || !empty( $query ) ) {
			$this->query_limit = null;
			$this->query_vars = $this->fill_query_vars( $query );
		}			
		do_action( 'usam_pre_get_users_basket', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		
		$join = [];	
		if ( 'all' == $qv['fields'] ) 		
			$this->query_fields = USAM_TABLE_USERS_BASKET.".*";
		else
		{
			if ( !is_array( $qv['fields'] ) )				
				$fields = [ $qv['fields'] ];
			else
				$fields = $qv['fields'];
			
			$fields = array_unique( $fields );

			$this->query_fields = array();
			foreach ( $fields as $field ) 
			{	
				if ( $field == 'count' )
					$this->query_fields[] = "COUNT(*) AS count";	
				elseif ( $field == 'sum' )				
					$this->query_fields[] = "SUM(".USAM_TABLE_USERS_BASKET.".totalprice) AS sum";			
				elseif ( $field == 'product_count' )				
					$this->query_fields[] = "SUM(".USAM_TABLE_USERS_BASKET.".quantity) AS product_count";							
				else
				{
					$field = 'id' === $field ? 'id' : sanitize_key( $field );
					$this->query_fields[] = USAM_TABLE_USERS_BASKET.".$field";
				}
			}
			$this->query_fields = implode( ',', $this->query_fields );
		} 	
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;

		
		$this->query_join = implode( ' ', $join );

		$this->query_from = "FROM ".USAM_TABLE_USERS_BASKET;
		$this->query_where = "WHERE 1=1";			
		
		// Обрабатывать другие параметры даты
		$date_parameters = [];
		if ( '' !== $qv['hour'] )
			$date_parameters['hour'] = $qv['hour'];

		if ( '' !== $qv['minute'] )
			$date_parameters['minute'] = $qv['minute'];

		if ( '' !== $qv['second'] )
			$date_parameters['second'] = $qv['second'];

		if ( $qv['year'] )
			$date_parameters['year'] = $qv['year'];

		if ( $qv['monthnum'] )
			$date_parameters['monthnum'] = $qv['monthnum'];

		if ( $qv['w'] )
			$date_parameters['week'] = $qv['w'];

		if ( $qv['day'] )
			$date_parameters['day'] = $qv['day'];		
				
		if ( $date_parameters ) 
		{
			$date_query = new USAM_Date_Query([$date_parameters], USAM_TABLE_USERS_BASKET);
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty( $qv['date_query'] ) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_USERS_BASKET );
			$this->query_where .= $this->date_query->get_sql();
		}	
	
		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( !empty( $qv['include'] ) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;
			
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
			$orderby_array[] = USAM_TABLE_USERS_BASKET.'.'.$parsed . ' ' . $this->parse_order( $_order );
		}

		// If no valid clauses were found, order by id.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = USAM_TABLE_USERS_BASKET.".id $order";
		}
		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

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
				$search_columns = array_intersect( $qv['search_columns'], ['id', 'user_login', 'product_name', 'product_sku']);
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = ['id', 'product_sku', 'user_login'];				
				else
					$search_columns = ['user_login', 'product_name', 'product_sku'];
			}	
			$search_columns = apply_filters( 'usam_users_basket_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( !empty( $include ) ) 
		{	// Sanitized earlier.
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_USERS_BASKET.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_USERS_BASKET.".id NOT IN ($ids)";
		}	
		if ( isset($qv['user_id']) )
		{					
			$user_id = implode( ',', wp_parse_id_list( (array)$qv['user_id'] ) );
			$this->query_where .= " AND ".USAM_TABLE_USERS_BASKET.".user_id IN ($user_id)";		
		}
		if ( isset($qv['contact_id']) )
		{					
			$contact_id = implode( ',', wp_parse_id_list( (array)$qv['contact_id'] ) );
			$this->query_where .= " AND ".USAM_TABLE_USERS_BASKET.".contact_id IN ($contact_id)";		
		}	
		if ( isset($qv['payment_methods']) )
		{					
			$payment_methods = implode( ',', wp_parse_id_list( (array)$qv['payment_methods'] ) );
			$this->query_where .= " AND ".USAM_TABLE_USERS_BASKET.".gateway_method IN ($payment_methods)";		
		}	
		if ( isset($qv['shipping_methods']) )
		{					
			$shipping_methods = implode( ',', wp_parse_id_list( (array)$qv['shipping_methods'] ) );
			$this->query_where .= " AND ".USAM_TABLE_USERS_BASKET.".shipping_method IN ($shipping_methods)";		
		}	
		if ( isset($qv['storage_pickup']) )
		{					
			$storage_pickup = implode( ',', wp_parse_id_list( (array)$qv['storage_pickup'] ) );
			$this->query_where .= " AND ".USAM_TABLE_USERS_BASKET.".storage_pickup IN ($storage_pickup)";		
		}
		if ( isset($qv['order_id']) )
		{					
			$order_id = implode( ',', wp_parse_id_list( (array)$qv['order_id'] ) );
			$this->query_where .= " AND ".USAM_TABLE_USERS_BASKET.".order_id IN ($order_id)";
		}		
		$conditions_query = new USAM_Conditions_Query();
		$this->query_where .= $conditions_query->get_sql_clauses( $qv, ['sum' => 'totalprice', 'coupon' => 'coupon_name']);
		do_action_ref_array( 'usam_pre_users_basket_query', array( &$this ) );
	}

	/**
	 * Execute the query, with the current variables.	
	 */
	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;

		$this->request = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";
		if ( is_array($qv['fields'] ) || 'all' == $qv['fields'] && $qv['number'] != 1 ) 		
			$this->results = $wpdb->get_results( $this->request );		
		elseif ( $qv['number'] == 1 && 'all' !== $qv['fields'] )		
			$this->results = $wpdb->get_var( $this->request );
		elseif ( $qv['number'] == 1 && 'all' == $qv['fields'] )		
			$this->results = $wpdb->get_row( $this->request, ARRAY_A );
		else 
			$this->results = $wpdb->get_col( $this->request );
	
		if ( !$this->results )
			return;	
		
		if ( $qv['count_total'] )
			$this->total = $wpdb->get_var( apply_filters( 'found_users_basket_query', 'SELECT FOUND_ROWS()' ) );
		
		if ( 'all' == $qv['fields'] && $qv['cache_results'] )
		{		
			if ( $qv['number'] == 1 )
				wp_cache_set( $this->results['id'], (array)$this->results, 'usam_users_basket' );	
			else
			{					
				foreach ( $this->results as $result ) 
				{
					wp_cache_set( $result->id, (array)$result, 'usam_users_basket' );						
				}
			}				
		}
		if ( $qv['cache_meta'] )
		{				
			if ( $qv['number'] == 1 )
				$ids = [ $this->results['id'] ]; 	
			else
			{
				$ids = array();
				foreach( $this->results as $result ) 
				{
					if ( isset($result->id) )
						$ids[] = $result->id; 	
					elseif ( $qv['fields'] === 'id' )
						$ids[] = $result; 	
				}	
			}
			usam_update_cache( $ids, [USAM_TABLE_USERS_BASKET_META => 'basket_meta'], 'basket_id' );			
		}
		if ( $qv['cache_products'] )
		{
			$ids = array();	
			foreach ( $this->results as $result ) 
			{
				$ids[] = $result->id;					
			}	
			usam_update_cache( $ids, [USAM_TABLE_PRODUCTS_BASKET => 'products_basket'], 'cart_id' );
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
				if ( !empty($product_id) )
				{
					$searches[] = USAM_TABLE_USERS_BASKET.".id IN ( SELECT DISTINCT cart_id FROM ".USAM_TABLE_PRODUCTS_BASKET." WHERE product_id ='$product_id' )";	
				}
			}
			elseif ( 'product_name' == $col ) 
			{
				$searches[] = USAM_TABLE_USERS_BASKET.".id IN ( SELECT DISTINCT cart_id FROM ".USAM_TABLE_PRODUCTS_BASKET." WHERE name LIKE LOWER ('{$like}') )";			
			}
			elseif ( 'user_login' == $col ) 
			{
				$user = get_user_by('login', $string );
				if ( !empty($user->ID) )
					$searches[] = USAM_TABLE_USERS_BASKET.".user_id ={$user->ID}";
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
	
	public function get_total_amount() 
	{
		global $wpdb;
		
		$request = "SELECT SUM(".USAM_TABLE_USERS_BASKET.".totalprice) AS sum $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby"; 
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
		if ( in_array($orderby, ['id', 'user_id', 'date_insert', 'recalculation_date', 'totalprice', 'quantity']) )
			$_orderby = $orderby;
		elseif ( 'date' == $orderby ) 
			$_orderby = 'date_insert';
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_USERS_BASKET.".id, $include_sql )";
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

function usam_get_users_baskets( $args = array())
{	
	$args['count_total'] = false;
	$users_baskets = new USAM_Users_Basket_Query( $args );	
	$results = $users_baskets->get_results();	
	
	return $results;
}