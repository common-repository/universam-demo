<?php
class USAM_Products_Competitors_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $meta_query = false;
	public $request;
		
	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'title' => 's', 'sku' => 's', 'thumbnail' => 's', 'url' => 's', 'current_price' => 'f', 'old_price' => 'f', 'status' => 's', 'competitor_category_id' => 'd', 'product_id' => 'd', 'site_id' => 'd', 'old_price_date' => 's', 'date_insert' => 's', 'date_update' => 's'];

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
			'id'  => '',			
			'date_insert' => '',				
			'product_id'=> '',
			'rule_id' => '',	
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
			'fields' => 'all',		
			'add_fields' => '',		
			'cache_results' => false,			
			'cache_product_meta' => false,		
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
		do_action( 'usam_pre_get_products_competitors', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		
		$join = array();
		$distinct = false;	
		$this->fields = [];	
		$fields = is_array($qv['fields']) ? array_unique( $qv['fields'] ) : explode(',', $qv['fields']);
		if ( $qv['add_fields'] )
		{
			$add_fields = is_array($qv['add_fields']) ? array_unique( $qv['add_fields'] ) : explode(',', $qv['add_fields']);
			$fields = array_merge( $fields, $add_fields );	
		}
		foreach ( $fields as $field ) 
		{
			if ( $field == 'all' )
				$this->fields[] = USAM_TABLE_PRODUCTS_COMPETITORS.".*";		
			elseif ( $field == 'count' )
				$this->fields[] = "COUNT(*) AS count";				
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_PRODUCTS_COMPETITORS.'.'.$field;
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_PRODUCTS_COMPETITORS.".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_PRODUCTS_COMPETITORS;
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
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_PRODUCTS_COMPETITORS );
			$this->query_where .= $date_query->get_sql();
		} //users
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_PRODUCTS_COMPETITORS );
			$this->query_where .= $this->date_query->get_sql();
		}		
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
				$product_id = array( $qv['product_id'] );
		}
		$product_id__in = array();
		if ( isset($qv['product_id__in'] ) ) {
			$product_id__in = (array) $qv['product_id__in'];
		}

		$product_id__not_in = array();
		if ( isset($qv['product_id__not_in'] ) ) {
			$product_id__not_in = (array) $qv['product_id__not_in'];
		}

		if ( !empty( $product_id ) ) 
		{
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_COMPETITORS.".product_id IN (".implode( ',',  $product_id ).")";		
		}		
		if ( !empty($product_id__not_in) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_COMPETITORS.".product_id NOT IN (".implode( ',',  $product_id__not_in ).")";
		}
		if ( !empty( $product_id__in ) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_COMPETITORS.".product_id IN (".implode( ',',  $product_id__in ).")";
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
			$orderby_array[] = USAM_TABLE_PRODUCTS_COMPETITORS.".id $order";
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
				$search_columns = array_intersect( $qv['search_columns'], ['id', 'product_id', 'title', 'sku']);
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = ['id', 'product_id'];				
				else
					$search_columns = ['title', 'sku'];
			}	
			$search_columns = apply_filters( 'usam_products_competitors_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}	
		if ( !empty( $include ) ) 
		{	
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_COMPETITORS.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_COMPETITORS.".id NOT IN ($ids)";
		}				
		if ( !empty($qv['competitor_category_id']) ) 
		{			
			$competitor_category_id = implode( ',', (array)$qv['competitor_category_id'] );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_COMPETITORS.".competitor_category_id IN ($competitor_category_id)";
		}	
		if ( !empty($qv['site_id']) ) 
		{			
			$site_id = implode( ',', (array)$qv['site_id'] );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_COMPETITORS.".site_id IN ($site_id)";
		}
		if ( !empty($qv['url']) ) 
		{			
			$url = implode( "','", (array)$qv['url'] );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_COMPETITORS.".url IN ('$url')";
		}
		if ( !empty($qv['meta_price_query']) )
		{
			foreach ( $qv['meta_price_query'] as $key => $meta_query )
			{
				if ( isset($meta_query['key']) && $meta_query['key'] == 'difference' ) 
				{							
					$type_price = usam_get_manager_type_price();
					$this->query_join .= " LEFT JOIN ".USAM_TABLE_PRODUCT_PRICE." AS pr$key ON (".USAM_TABLE_PRODUCTS_COMPETITORS.".product_id = pr$key.product_id AND pr$key.meta_key = 'price_{$type_price}')";
					$this->query_where .= " AND CAST(".USAM_TABLE_PRODUCTS_COMPETITORS.".current_price*100/pr$key.meta_value AS SIGNED)-100 ".$meta_query['compare']." ".$meta_query['value'];
				}
			}	
		}		
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty( $this->meta_query->queries ) ) 
		{
			$clauses = $this->meta_query->get_sql( 'product', USAM_TABLE_PRODUCT_PRICE, USAM_TABLE_PRODUCTS_COMPETITORS, 'product_id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];
			if ( $this->meta_query->has_or_relation() && strripos($this->query_fields, 'DISTINCT')  === false ) {
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
			}
		}		
		$conditions_query = new USAM_Conditions_Query();
		$this->query_where .= $conditions_query->get_sql_clauses( $qv, ['price' => USAM_TABLE_PRODUCTS_COMPETITORS.'.current_price', 'growth' => 'current_price-old_price', 'decline' => 'old_price-current_price']);			
		if ( $distinct )
			$this->query_fields = "DISTINCT ".$this->query_fields;
		do_action_ref_array( 'usam_pre_products_competitors_query', [&$this] );
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
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_product_competitor' );	
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
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_products_competitors_query', 'SELECT FOUND_ROWS()' ) );
		
		if ( 'all' == $qv['fields'] )
		{		
			if ( $qv['cache_product_meta'] )
			{
				$post_ids = array();	
				foreach ( $this->results as $result ) 
				{
					$post_ids[] = $result->product_id;
				}	
				update_meta_cache('post', $post_ids);	
			}	
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
				$searches[] = $wpdb->prepare( "$col = %s", $string );
			if ( 'title' == $col || 'sku' == $col )
				$searches[] = $wpdb->prepare( USAM_TABLE_PRODUCTS_COMPETITORS.".$col LIKE LOWER('%s')", "%$string%" );
			else
				$searches[] = $wpdb->prepare( "$col LIKE %s", $like );
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
			$_orderby = USAM_TABLE_PRODUCTS_COMPETITORS.'.'.$orderby;
		elseif ( $orderby == 'date' )
			$_orderby = USAM_TABLE_PRODUCTS_COMPETITORS.'.date_insert';
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_PRODUCTS_COMPETITORS.".id, $include_sql )";
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

function usam_get_products_competitors( $query = array() )
{ 
	$query['count_total'] = false;
	$query = new USAM_Products_Competitors_Query( $query );	
	$results = $query->get_results();		
	return $results;
}
