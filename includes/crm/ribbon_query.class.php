<?php
// Класс работы с лентой
class USAM_Ribbon_Query 
{
	public $query_vars = [];
	private $results;
	private $total = 0;
	public $request;
	public $object_query = false;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'event_id' => 'd', 'event_type' => 's', 'date_insert' => 's'];
	
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
		require_once( USAM_FILE_PATH . '/includes/query/objects_query.class.php' );
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
			'user_id' => '',
			'status' => 0,			
			'meta_key' => '',
			'meta_value' => '',
			'meta_compare' => '',
			'include' => array(),
			'exclude' => array(),
			'search' => '',
			'search_columns' => array(),
			'orderby' => 'date_insert',
			'order' => 'DESC',
			'offset' => '',
			'number' => '',
			'paged' => 1,
			'count_total' => true,
			'fields' => 'all',				
			'add_fields' => '',	
			'cache_results' => false,				
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
		do_action( 'usam_pre_get_ribbon', $this );
		
		$distinct = false;
		$links_join = false;
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );	
					
		$join = [];			
		$fields = is_array($qv['fields']) ? array_unique( $qv['fields'] ) : explode(',', $qv['fields']);
		if ( $qv['add_fields'] )
		{
			$add_fields = is_array($qv['add_fields']) ? array_unique( $qv['add_fields'] ) : explode(',', $qv['add_fields']);
			$fields = array_merge( $fields, $add_fields );	
		}			
		$this->fields = [];
		foreach ( $fields as $field ) 
		{
			if ( $field == 'count' )
				$this->fields[] = "COUNT(*) AS count";	
			elseif ( $field == 'all' )
				$this->fields[] = USAM_TABLE_RIBBON.".*";
			elseif ( $field == 'object_id' )
			{
				$this->fields[] = USAM_TABLE_RIBBON_LINKS.".$field";
				$links_join = true;
			}
			elseif ( $field == 'object_type' )
			{
				$this->fields[] = USAM_TABLE_RIBBON_LINKS.".$field";
				$links_join = true;
			}
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_RIBBON.".$field";			
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_RIBBON.".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_RIBBON;
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
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_RIBBON );
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_RIBBON );
			$this->query_where .= $this->date_query->get_sql();
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
		
		$qv['order'] = isset($qv['order'] ) ? strtoupper( $qv['order'] ) : '';
		$order = $this->parse_order( $qv['order'] );

		if ( empty($qv['orderby']) ) 			
			$ordersby = ['date_insert' => $order];
		elseif ( is_array( $qv['orderby'] ) ) 
			$ordersby = $qv['orderby'];
		else 
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
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
		if ( empty($orderby_array) ) 
			$orderby_array[] = USAM_TABLE_RIBBON.".date_insert $order";
		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		if ( isset($qv['number']) && $qv['number'] > 0 ) 
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

			$search_columns = [];
			if ( $qv['search_columns'] )
				$search_columns = array_intersect( $qv['search_columns'], ['id'] );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = ['id'];			
				else
					$search_columns = ['id'];
			}	
			$search_columns = apply_filters( 'usam_ribbon_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}
		if ( !empty($qv['include']) )
		{
			$ids = implode( ',', wp_parse_id_list( $qv['include'] ) );			
			$this->query_where .= " AND ".USAM_TABLE_RIBBON.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_RIBBON.".id NOT IN ($ids)";
		}	
		if ( isset($qv['event_id']) )
		{
			$event_id = implode( ',',  wp_parse_id_list((array)$qv['event_id']) );		
			$this->query_where .= " AND ".USAM_TABLE_RIBBON.".event_id IN ($event_id)";
		}		
		if ( !empty($qv['event_type']) ) 
		{		
			$event_type = implode( "','",  (array)$qv['event_type'] );		
			$this->query_where .= " AND ".USAM_TABLE_RIBBON.".event_type IN ('$event_type')";
		}
		if ( !empty($qv['object_type']) && !empty($qv['object_id']) ) 
		{
			$object_id = is_array($qv['object_id']) ? $qv['object_id']: array( $qv['object_id'] );		
			$object_id = array_map('intval', $object_id);			
			$qv['object_query'][] = ['object_type' => $qv['object_type'], 'object_id' => $object_id];		
		}
		if ( !empty($qv['object_query']) ) 
		{
			$this->object_query = new USAM_Object_Query( $qv['object_query'] );	
			$clauses = $this->object_query->get_sql( USAM_TABLE_RIBBON, USAM_TABLE_RIBBON_LINKS, 'id', 'ribbon_id' );	
			
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];
		}
		if ( $distinct ) 
			$this->query_fields = 'DISTINCT ' . $this->query_fields;		
		if ( $links_join  ) 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_RIBBON_LINKS." ON (".USAM_TABLE_RIBBON_LINKS.".ribbon_id = ".USAM_TABLE_RIBBON.".id)";
		do_action_ref_array( 'usam_pre_ribbon_query', array( &$this ) );
	}

	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( $type == 'd' && isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_ribbon' );	
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
				$searches[] = $wpdb->prepare( USAM_TABLE_RIBBON.".$col = %s", $string );
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
		if ( isset($this->db_fields[$orderby]) )
			$_orderby = USAM_TABLE_RIBBON.'.'.$orderby;	
		elseif ( 'date' == $orderby ) 
			$_orderby = USAM_TABLE_RIBBON.'.date_insert';
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) ) 
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_RIBBON.".id, $include_sql )";
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
	
	public function __get( $name ) 
	{
		if ( in_array( $name, $this->compat_fields ) ) 
		{
			return $this->$name;
		}
	}
	
	public function __set( $name, $value )
	{
		if ( in_array( $name, $this->compat_fields ) ) 
		{
			return $this->$name = $value;
		}
	}

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

function usam_get_ribbon_query( $query = array() )
{	
	$query['count_total'] = false;	
	$ribbons = new USAM_Ribbon_Query( $query );	
	return $ribbons->get_results();	
}	