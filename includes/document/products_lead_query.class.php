<?php
//Товары лида
class USAM_Products_Lead_Query 
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
		require_once( USAM_FILE_PATH . '/includes/query/table_query.class.php' );
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
			'name'  => '',
			'date_insert' => '',							
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
			'cache_order' => false,		
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
		do_action( 'usam_pre_get_products_lead', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		
		$join = array();	
		$lead_join = false;
		if ( 'all' == $qv['fields'] ) 		
			$this->query_fields = USAM_TABLE_PRODUCTS_LEAD.".*";
		else
		{
			if ( !is_array( $qv['fields'] ) )				
				$fields = array( $qv['fields'] );
			else
				$fields = array_unique( $qv['fields'] );

			$this->query_fields = array();
			foreach ( $fields as $field ) 
			{	
				if ( $field == 'all' )
					$this->fields[] = USAM_TABLE_PRODUCTS_LEAD.".*";
				elseif ( $field == 'count' )
					$this->query_fields[] = "COUNT(*) AS count";
				elseif ( $field == 'quantity' && $qv['groupby'] == 'product_id' )
					$this->query_fields[] = "SUM(quantity) AS quantity";	
				elseif ( $field == 'sum' && $qv['groupby'] == 'product_id' )
					$this->query_fields[] = "SUM(price) AS sum";					
				elseif ( $field == 'maxprice' )
					$this->query_fields[] = "MAX(price) AS maxprice";				
				elseif ( $field == 'minprice' )
					$this->query_fields[] = "MIN(price) AS minprice";									
				else
				{
					$field = 'id' === $field ? 'id' : sanitize_key( $field );
					$this->query_fields[] = USAM_TABLE_PRODUCTS_LEAD.".$field";
				}
			}
			$this->query_fields = implode( ',', $this->query_fields );
		} 
		
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_PRODUCTS_LEAD;
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
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_PRODUCTS_LEAD );
			$this->query_where .= $date_query->get_sql();
		} 
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_PRODUCTS_LEAD );
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
		{	// Default order is by 'id'.			
			$ordersby = array( 'id' => $order );
		} 
		elseif ( is_array( $qv['orderby'] ) ) 
		{
			$ordersby = $qv['orderby'];
		} 
		else 
		{  // Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
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
			$orderby_array[] = USAM_TABLE_PRODUCTS_LEAD.".id $order";
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
				$search_columns = array_intersect( $qv['search_columns'], array( 'id', 'lead_id', 'name' ) );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array('id', 'lead_id' );				
				else
					$search_columns = array('name' );
			}	
			$search_columns = apply_filters( 'usam_products_lead_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( !empty( $include ) ) 
		{	// Sanitized earlier.
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_LEAD.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_LEAD.".id NOT IN ($ids)";
		}						
		if ( isset($qv['lead_id']) ) 
		{
			$lead_id = implode( ',',  (array)$qv['lead_id'] );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_LEAD.".lead_id IN ($lead_id)";
		}						
		if ( !empty($qv['lead_status']) ) 
		{
			if ( is_array($qv['lead_status']) ) 
				$lead_status = $qv['lead_status'];			
			elseif ( is_string($qv['lead_status']) && !empty($qv['lead_status']) )
			{
				if ( $qv['lead_status'] != 'all' )
					$lead_status = array_map( 'trim', explode( ',', $qv['lead_status'] ) );
			}
			elseif ( is_numeric($qv['lead_status']) )
				$lead_status = array($qv['lead_status']);
				
			if ( !empty($lead_status) ) 
			{
				$this->query_where .= " AND ".USAM_TABLE_LEADS.".status IN ('".implode("','",  $lead_status)."')";	
				$lead_join = true;
			}
		}	
		if ( isset($qv['lead_status__not_in']) ) 
		{
			$lead_status__not_in = (array) $qv['lead_status__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_LEADS.".status NOT IN ('".implode("','",  $lead_status__not_in)."')";
			$lead_join = true;
		}
		if ( isset($qv['companies']) ) 
		{
			$companies = implode( "','",  (array)$qv['companies'] );
			$this->query_where .= " AND ".USAM_TABLE_LEADS.".company_id IN ('$companies')";
			$lead_join = true;
		}
		if ( isset($qv['bank_account_id']) ) 
		{
			$bank_account_id = implode( "','",  (array)$qv['bank_account_id'] );
			$this->query_where .= " AND ".USAM_TABLE_LEADS.".bank_account_id IN ('$bank_account_id')";
			$lead_join = true;
		}	
		if ( isset($qv['lead_manager']) ) 
		{
			$lead_manager = implode( "','",  (array)$qv['lead_manager'] );
			$this->query_where .= " AND ".USAM_TABLE_LEADS.".manager IN ('$lead_manager')";
			$lead_join = true;
		}	
		if ( isset($qv['lead_paid']) ) 
		{
			$paid = implode( "','",  (array)$qv['lead_paid'] );
			$this->query_where .= " AND ".USAM_TABLE_LEADS.".paid IN ('$paid')";
			$lead_join = true;
		}	
		if ( isset($qv['lead_type_price']) ) 
		{
			$type_price = implode( "','",  (array)$qv['lead_type_price'] );
			$this->query_where .= " AND ".USAM_TABLE_LEADS.".type_price IN ('$type_price')";
			$lead_join = true;
		}		
		if ( isset($qv['lead_payer']) ) 
		{
			$payer = implode( "','",  (array)$qv['lead_payer'] );
			$this->query_where .= " AND ".USAM_TABLE_LEADS.".payer IN ('$payer')";
			$lead_join = true;
		}		
		if ( isset($qv['companies']) ) 
		{
			$companies = implode( "','",  (array)$qv['companies'] );
			$this->query_where .= " AND ".USAM_TABLE_LEADS.".company_id IN ('$companies')";
			$lead_join = true;
		}
		if ( isset($qv['contacts']) ) 
		{
			$contacts = implode( "','",  (array)$qv['contacts'] );
			$this->query_where .= " AND ".USAM_TABLE_LEADS.".contact_id IN ('$contacts')";
			$lead_join = true;
		}	
		if ( isset($qv['product_ids']) ) 
		{
			$product_ids = implode( "','",  (array)$qv['product_ids'] );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_LEAD.".product_id IN ('$product_ids')";
		}		
		$conditions_query = new USAM_Conditions_Query();
		$this->query_where .= $conditions_query->get_sql_clauses( $qv );
		if ( $lead_join )
			$this->query_join .= " INNER JOIN ".USAM_TABLE_LEADS." ON (".USAM_TABLE_LEADS.".id = ".USAM_TABLE_PRODUCTS_LEAD.".lead_id)";		
		do_action_ref_array( 'usam_pre_products_lead_query', array( &$this ) );
	}
	
	public function handling_data_types( $data )
	{
		foreach (['id', 'product_id', 'lead_id', 'product_day', 'bonus'] as $column ) 
		{			
			if ( isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		foreach (['price', 'old_price', 'quantity', 'purchase_price'] as $column ) 
		{			
			if ( isset($data->$column) )	
				$data->$column = (float)$data->$column;
		}
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
		if ( is_array( $qv['fields'] ) || 'all' == $qv['fields'] && $qv['number'] != 1 ) 
		{
			$this->results = $wpdb->get_results( $this->request );		
			foreach( $this->results as $k => $result )
			{				
				$this->results[$k] = $this->handling_data_types( $result );
			}
		}
		elseif ( $qv['number'] == 1 && 'all' !== $qv['fields'] )		
			$this->results = $wpdb->get_var( $this->request );
		elseif ( $qv['number'] == 1 && 'all' == $qv['fields'] )		
			$this->results = $wpdb->get_row( $this->request );
		else 
			$this->results = $wpdb->get_col( $this->request );		
		
		if ( !$this->results )
			return;
		
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_products_lead_query', 'SELECT FOUND_ROWS()' ) );
		
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
				usam_get_products(['post__in' => $product_ids, 'update_post_term_cache' => true], true );
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
			{
				$searches[] = $wpdb->prepare( "$col = %s", $string );
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

	/**
	 * Вернуть общее количество записей для текущего запроса.
	 */
	public function get_total() 
	{
		return $this->total;
	}

	protected function parse_orderby( $orderby ) 
	{
		global $wpdb;
		$_orderby = '';
		if ( in_array( $orderby, ['id', 'name', 'date_insert', 'lead_id', 'quantity']) )
		{
			$_orderby = USAM_TABLE_PRODUCTS_LEAD.'.'.$orderby;
		} 
		elseif ( 'sum' == $orderby ) 
		{
			$_orderby = 'sum';
		} 	
		elseif ( 'amount_quantity' == $orderby ) 
		{
			$_orderby = 'SUM(quantity)';
		} 		
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_PRODUCTS_LEAD.".id, $include_sql )";
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

function usam_get_products_lead_query( $args )
{
	$args['count_total'] = false;	
	$products = new USAM_Products_Lead_Query( $args );	
	return $products->get_results();
}