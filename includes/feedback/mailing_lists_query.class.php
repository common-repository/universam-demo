<?php
// Класс работы со списками рассылок
class USAM_Mailing_Lists_Query 
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
			'id'  => '',			
			'date_insert'  => '',
			'sent_at' => '',		
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
			'cache_attachments' => false,			
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
		do_action( 'usam_pre_get_mailing_lists', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		
		$join = array();			

		if ( is_array($qv['fields']) ) 
		{
			$qv['fields'] = array_unique( $qv['fields'] );

			$this->query_fields = array();
			foreach ( $qv['fields'] as $field ) 
			{	
				if ( $field == 'count' )
					$this->query_fields[] = "COUNT(*) AS count";							
				else
				{
					$field = 'id' === $field ? 'id' : sanitize_key( $field );
					$this->query_fields[] = USAM_TABLE_MAILING_LISTS.".$field";
				}
			}
			$this->query_fields = implode( ',', $this->query_fields );
		} 
		elseif ( 'all' == $qv['fields'] ) 		
			$this->query_fields = USAM_TABLE_MAILING_LISTS.".*";		
		elseif ( $qv['fields'] == 'id=>name' ) 
			$this->query_fields = USAM_TABLE_MAILING_LISTS.".name,".USAM_TABLE_MAILING_LISTS.".id";	
		else 
		{
			$field = sanitize_key($qv['fields']);
			$this->query_fields = USAM_TABLE_MAILING_LISTS.".$field";
		}

		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;

		
		$this->query_join = implode( ' ', $join );

		$this->query_from = "FROM ".USAM_TABLE_MAILING_LISTS;
		$this->query_where = "WHERE 1=1";		
		
	// Обрабатывать другие параметры даты
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
			$date_query = new USAM_Date_Query( array( $date_parameters ), USAM_TABLE_MAILING_LISTS, 'date_insert' );
			$this->query_where .= $date_query->get_sql();
		}	
		if ( !empty( $qv['date_query'] ) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_MAILING_LISTS, 'date_insert' );
			$this->query_where .= $this->date_query->get_sql();
		}			
		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( !empty( $qv['include'] ) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;
			
		$type = array();
		if ( isset($qv['type'] ) ) 
		{
			if ( is_array( $qv['type'] ) ) 
				$type = $qv['type'];			
			elseif ( is_string( $qv['type'] ) && !empty( $qv['type'] ) )
				$type = array_map( 'trim', explode( ',', $qv['type'] ) );
			elseif ( is_numeric( $qv['type'] ) )
				$type = $qv['type'];
		}
		$type_in = array();
		if ( isset($qv['type_in'] ) ) {
			$type_in = (array) $qv['type_in'];
		}
		$type_not_in = array();
		if ( isset($qv['type_not_in'] ) ) {
			$type_not_in = (array) $qv['type_not_in'];
		}
		if ( !empty( $type ) ) 
		{
			$this->query_where .= " AND ".USAM_TABLE_MAILING_LISTS.".type IN ('".implode( "','",  $type )."')";		
		}		
		if ( !empty($type_not_in) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_MAILING_LISTS.".type NOT IN ('".implode( "','",  $type_not_in )."')";
		}
		if ( !empty( $type_in ) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_MAILING_LISTS.".type IN ('".implode( "','",  $type_in )."')";
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
			$orderby_array[] = USAM_TABLE_MAILING_LISTS.'.'.$parsed . ' ' . $this->parse_order( $_order );
		}

		// If no valid clauses were found, order by id.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = USAM_TABLE_MAILING_LISTS.".id $order";
		}
		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		// limit
		if ( isset($qv['number'] ) && $qv['number'] > 0 ) 
		{
			if ( $qv['offset'] )
				$this->query_limit = $wpdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
			else
				$this->query_limit = $wpdb->prepare( "LIMIT %d, %d", $qv['number'] * ( $qv['paged'] - 1 ), $qv['number'] );
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
				$search_columns = array_intersect( $qv['search_columns'], array( 'id', 'name' ) );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array('id' );				
				else
					$search_columns = array( 'name' );
			}	
			$search_columns = apply_filters( 'usam_mailing_lists_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}
		if ( !empty($include) ) 
		{	
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_MAILING_LISTS.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_MAILING_LISTS.".id NOT IN ($ids)";
		}			
		if ( isset($qv['view'] ) ) 
		{			
			$view = (int)$qv['view'];
			$this->query_where .= " AND ".USAM_TABLE_MAILING_LISTS.".view=$view";
		}	
		if ( !empty($qv['parent_id']) ) 
		{	
			$ids = implode( ',', (array)$qv['parent_id'] );
			$this->query_where .= " AND ".USAM_TABLE_MAILING_LISTS.".parent_id IN ($ids)";
		}
		if ( isset($qv['newsletter_id'] ) ) 
		{		
			$ids = implode( ',', wp_parse_id_list( (array)$qv['newsletter_id'] ) );
			$this->query_join .= " INNER JOIN ".USAM_TABLE_NEWSLETTER_LISTS." ON (".USAM_TABLE_NEWSLETTER_LISTS.".list = ".USAM_TABLE_MAILING_LISTS.".id)";
			$this->query_where .= " AND ".USAM_TABLE_NEWSLETTER_LISTS.".newsletter_id IN ($ids)";
		}		
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty( $this->meta_query->queries ) ) 
		{
			$clauses = $this->meta_query->get_sql( 'list', USAM_TABLE_MAILING_LIST_META, USAM_TABLE_MAILING_LISTS, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( $this->meta_query->has_or_relation() ) {
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
			}
		}	
		$conditions_query = new USAM_Conditions_Query();
		$this->query_where .= $conditions_query->get_sql_clauses( $qv );
		do_action_ref_array( 'usam_pre_mailing_lists_query', array( &$this ) );
	}

	/**
	 * Execute the query, with the current variables.	
	 */
	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;

		$this->request = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";
		if ( is_array($qv['fields']) || 'all' == $qv['fields'] || 'id=>name' == $qv['fields'] ) 
		{			
			$this->results = $wpdb->get_results( $this->request );		
			foreach ($this->results as $key => $result ) 
			{
				foreach (['id'] as $column ) 
				{			
					if ( isset($result->$column) )	
						$this->results[$key]->$column = (int)$result->$column;
				}
			}
		}
		elseif ( $qv['number'] == 1 )		
			$this->results = $wpdb->get_var( $this->request );
		else
			$this->results = $wpdb->get_col( $this->request );	

		if ( !$this->results )
			return;	
		
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_mailing_lists_query', 'SELECT FOUND_ROWS()' ) );				
			
		if ( 'all' == $qv['fields'] )
		{			
			if ( $qv['cache_results'] )
			{					
				if ( $qv['number'] == 1 )
					wp_cache_set( $this->results->id, (array)$this->results, 'usam_mailing_list' );	
				else
				{					
					foreach ( $this->results as $result ) 
					{
						wp_cache_set( $result->id, (array)$result, 'usam_mailing_list' );						
					}
				}	
			}
		}
		if ( 'id=>name' == $qv['fields'] ) 
		{		
			$r = array();
			foreach ( $this->results as $key => $result ) 
			{
				$r[$result->id] = $result->name;
			}			
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

		foreach ( $cols as $col ) {
			if ( 'id' == $col ) {
				$searches[] = $wpdb->prepare( USAM_TABLE_MAILING_LISTS.".$col = %s", $string );
			} else {
				$searches[] = $wpdb->prepare( USAM_TABLE_MAILING_LISTS.".$col LIKE %s", $like );
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

		if ( in_array( $orderby, array( 'name', 'date_insert' ) ) )
		{
			$_orderby = $orderby;
		}	
		elseif ( 'id' == $orderby ) 
		{
			$_orderby = 'id';
		} 		
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_MAILING_LISTS.".id, $include_sql )";
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

function usam_get_mailing_lists( $args = [] )
{
	$args['count_total'] = false;
	$query = new USAM_Mailing_Lists_Query( $args );	
	$result = $query->get_results();	
	
	return $result;
}