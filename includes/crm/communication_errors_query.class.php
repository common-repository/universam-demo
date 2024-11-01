<?php
// Класс работы с ошибочными телефонами, электронными адресами
class USAM_Communication_Errors_Query 
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
		do_action( 'usam_pre_get_communication_errors', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		
		$join = array();			

		if ( is_array( $qv['fields'] ) ) 
		{
			$qv['fields'] = array_unique( $qv['fields'] );

			$this->query_fields = array();
			foreach ( $qv['fields'] as $field ) 
			{	
				if ( $field == 'all' )
					$this->query_fields[] = USAM_TABLE_COMMUNICATION_ERRORS.".*";	
				elseif ( $field == 'count' )
					$this->query_fields[] = "COUNT(*) AS count";					
				elseif ( $field == 'contact' )
				{
					$this->query_fields[] = USAM_TABLE_CONTACTS.".id AS contact_id";	
					$join[] = " INNER JOIN ".USAM_TABLE_CONTACT_META." ON (".USAM_TABLE_CONTACT_META.".meta_value = ".USAM_TABLE_COMMUNICATION_ERRORS.".communication)";	
					$join[] = " INNER JOIN ".USAM_TABLE_CONTACTS." ON (".USAM_TABLE_CONTACTS.".id = ".USAM_TABLE_CONTACT_META.".contact_id)";						
				}
				elseif ( $field == 'company' )
				{
					$this->query_fields[] = USAM_TABLE_COMPANY.".id AS company_id";	
					$join[] = " INNER JOIN ".USAM_TABLE_COMPANY_META." ON (".USAM_TABLE_COMPANY_META.".meta_value = ".USAM_TABLE_COMMUNICATION_ERRORS.".communication)";	
					$join[] = " INNER JOIN ".USAM_TABLE_COMPANY." ON (".USAM_TABLE_COMPANY.".id = ".USAM_TABLE_COMPANY_META.".company_id)";						
				}
				else
				{
					$field = 'id' === $field ? 'id' : sanitize_key( $field );
					$this->query_fields[] = USAM_TABLE_COMMUNICATION_ERRORS.".$field";
				}
			}
			$this->query_fields = implode( ',', $this->query_fields );
		} 
		elseif ( 'all' == $qv['fields'] ) 		
			$this->query_fields = USAM_TABLE_COMMUNICATION_ERRORS.".*";		
		else 
		{
			$field = 'id' === $qv['fields'] ? 'id' : sanitize_key( $qv['fields'] );
			$this->query_fields = USAM_TABLE_COMMUNICATION_ERRORS.".$field";
		}

		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_COMMUNICATION_ERRORS;
		$this->query_where = "WHERE 1=1";		
		
		$date_parameters = array();
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
			$date_query = new USAM_Date_Query( array( $date_parameters ), USAM_TABLE_COMMUNICATION_ERRORS );
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty( $qv['date_query'] ) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_COMMUNICATION_ERRORS );
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
			if ( is_array($qv['groupby']) )					
				$groupby = $qv['groupby'];					
			else
				$groupby[] = $qv['groupby'];			
			$ordersby_array = array();		
			foreach ( $groupby as $_value ) 
			{				
				switch ( $_value ) 
				{
					default:
						$ordersby_array[] = USAM_TABLE_COMMUNICATION_ERRORS.'.'.$_value;
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
			$orderby_array[] = USAM_TABLE_COMMUNICATION_ERRORS.".id $order";
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
				$search_columns = array_intersect( $qv['search_columns'], array( 'id', 'communication' ) );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array('id', 'communication' );				
				else
					$search_columns = array( 'communication' );
			}	
			$search_columns = apply_filters( 'usam_communication_errors_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( !empty( $include ) ) 
		{	
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_COMMUNICATION_ERRORS.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_COMMUNICATION_ERRORS.".id NOT IN ($ids)";
		}	
		if ( !empty( $qv['communication_type'] ) ) 
		{
			$communication_type = is_array($qv['communication_type']) ? $qv['communication_type']: array( $qv['communication_type'] );	
			$this->query_where .= " AND ".USAM_TABLE_COMMUNICATION_ERRORS.".communication_type IN ('".implode( "', '", $communication_type )."')";
		}	
		if ( !empty( $qv['communication'] ) ) 
		{
			$communication = is_array($qv['communication']) ? $qv['communication']: array( $qv['communication'] );	
			$this->query_where .= " AND ".USAM_TABLE_COMMUNICATION_ERRORS.".communication IN ('".implode( "','", $communication )."')";
		}	
		if ( !empty( $qv['reason'] ) ) 
		{
			$reason = is_array($qv['reason']) ? $qv['reason']: array( $qv['reason'] );	
			$this->query_where .= " AND ".USAM_TABLE_COMMUNICATION_ERRORS.".reason IN ('".implode( "', '", $reason )."')";
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
				$this->query_where .= " AND ".USAM_TABLE_COMMUNICATION_ERRORS.".status IN ('".implode( "','", $status )."')";	
		}	
		if ( isset($qv['status__not_in']) ) 
		{
			$status__not_in = (array) $qv['status__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_COMMUNICATION_ERRORS.".status NOT IN ('".implode("','", $status__not_in )."')";
		}
		do_action_ref_array( 'usam_pre_communication_errors_query', array( &$this ) );
	}

	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;

		$this->request = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";		
		if ( is_array( $qv['fields'] ) || 'all' == $qv['fields'] ) 					
			$this->results = $wpdb->get_results( $this->request );		
		elseif ( $qv['number'] == 1 )		
			$this->results = $wpdb->get_var( $this->request );
		else
			$this->results = $wpdb->get_col( $this->request );		

		if ( !$this->results )
			return;	
		
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_communication_errors_query', 'SELECT FOUND_ROWS()' ) );				
			
		if ( 'all' == $qv['fields'] )
		{			
			if ( $qv['cache_results'] )
			{					
				if ( $qv['number'] == 1 )
					wp_cache_set( $this->results->id, (array)$this->results, 'usam_communication_error' );	
				else
				{					
					foreach ( $this->results as $result ) 
					{
						wp_cache_set( $result->id, (array)$result, 'usam_communication_error' );						
					}
				}	
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
				$searches[] = $wpdb->prepare( USAM_TABLE_COMMUNICATION_ERRORS.".$col = %s", $string );
			}
			elseif ( 'communication' == $col ) 
			{
				$searches[] = $wpdb->prepare( USAM_TABLE_COMMUNICATION_ERRORS.".$col LIKE %s", "%".$wpdb->esc_like( $string )."%" );
			}
			else 
			{
				$searches[] = $wpdb->prepare( USAM_TABLE_COMMUNICATION_ERRORS.".$col LIKE %s", $like );
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
		if ( in_array( $orderby, array( 'communication' ) ) )
		{
			$_orderby = USAM_TABLE_COMMUNICATION_ERRORS.'.'.$orderby;
		}	
		elseif ( 'id' == $orderby ) 
		{
			$_orderby = USAM_TABLE_COMMUNICATION_ERRORS.'.id';
		} 	
		elseif ( 'COUNT' == $orderby ) 
		{
			$_orderby = 'COUNT(*)';
		} 		
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_COMMUNICATION_ERRORS.".id, $include_sql )";
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

function usam_get_communication_errors( $args = array() )
{
	$args['count_total'] = false;
	$communication_errors = new USAM_Communication_Errors_Query( $args );	
	$result = $communication_errors->get_results();	
	
	return $result;
}