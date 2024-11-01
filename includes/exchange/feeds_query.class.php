<?php
// Класс работы с feeds
class USAM_Feeds_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'name' => 's', 'active' => 'd', 'start_date' => 's', 'end_date' => 's', 'platform' => 's', 'type_price' => 's'];
	
	public $query_fields;
	public $query_from;
	public $query_join;	
	public $query_where;
	public $meta_query;	
	public $query_orderby;
	public $query_groupby;	 
	public $query_limit;
	
	public $date_query;	
	
	public function __construct($query = null ) 
	{
		require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
		require_once( USAM_FILE_PATH . '/includes/query/date.php' );
		if ( !empty($query ) ) 
		{
			$this->prepare_query($query );
			$this->query();
		}
	}	

	/**
	 * Fills in missing query variables with default values.
	 */
	public static function fill_query_vars($args ) 
	{
		$defaults = array(				
			'meta_key' => '',
			'meta_value' => '',
			'meta_compare' => '',
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
			'cache_results' => false,	
			'cache_meta' => false,	
			'add_fields' => '',				
		);
		return wp_parse_args($args, $defaults );
	}
	
	public function prepare_query($query = array() ) 
	{
		global $wpdb;

		if ( empty($this->query_vars ) || !empty($query ) ) 
		{
			$this->query_limit = null;
			$this->query_vars = $this->fill_query_vars($query );
		}			
		do_action( 'usam_pre_get_feeds', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars($qv );	
				
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
				$this->fields[] = USAM_TABLE_FEEDS.".*";	
			elseif ( $field == 'count' )				
				$this->fields[] = "COUNT(*) AS count";				
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_FEEDS.".$field";
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_FEEDS.".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
				
		$this->query_join = implode( ' ', $join );

		$this->query_from = "FROM ".USAM_TABLE_FEEDS;
		$this->query_where = "WHERE 1=1";		
			
		if ( !empty($qv['include'] ) ) 		
			$include = wp_parse_id_list($qv['include'] );		 
		else 		
			$include = false;		
		// Группировать
		$qv['groupby'] = isset($qv['groupby'] ) ? $qv['groupby'] : '';
		
		if ($qv['groupby'] != '' )
		{
			if ( is_array($qv['groupby']) )					
				$groupby = $qv['groupby'];					
			else
				$groupby[] = $qv['groupby'];
			$ordersby_array = array();		
			foreach ($groupby as $_value ) 
			{				
				switch ($_value ) 
				{					
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
		$qv['order'] = isset($qv['order'] ) ? strtoupper($qv['order'] ) : '';
		$order = $this->parse_order($qv['order'] );

		if ( empty($qv['orderby'] ) ) 
		{	// Default order is by 'id'.			
			$ordersby = array( 'sort' => $order );
		} 
		elseif ( is_array($qv['orderby'] ) ) 
		{
			$ordersby = $qv['orderby'];
		} 
		else 
		{
			// Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
		}
		$orderby_array = array();
		foreach ($ordersby as $_key => $_value ) 
		{
			if ( ! $_value ) {
				continue;
			}			
			if ( is_int($_key ) ) 
			{				
				$_orderby = $_value;
				$_order = $order;
			} 
			else
			{				
				$_orderby = $_key;
				$_order = $_value;
			}				
			$parsed = $this->parse_orderby($_orderby );
			if ( ! $parsed ) {
				continue;
			}
			$orderby_array[] = $parsed . ' ' . $this->parse_order($_order );
		}		
		if ( empty($orderby_array ) ) 
		{
			$orderby_array[] = USAM_TABLE_FEEDS.".id $order";
		}
		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		if ( isset($qv['number'] ) && $qv['number'] > 0 ) 
		{
			if ($qv['offset'] ) {
				$this->query_limit = $wpdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
			} else {
				$this->query_limit = $wpdb->prepare( "LIMIT %d, %d", $qv['number'] * ($qv['paged'] - 1 ), $qv['number'] );
			}
		}
		$search = '';
		if ( isset($qv['search'] ) )
			$search = trim($qv['search'] );

		if ($search ) 
		{
			$leading_wild = ( ltrim($search, '*') != $search );
			$trailing_wild = ( rtrim($search, '*') != $search );
			if ($leading_wild && $trailing_wild )
				$wild = 'both';
			elseif ($leading_wild )
				$wild = 'leading';
			elseif ($trailing_wild )
				$wild = 'trailing';
			else
				$wild = false;
			if ($wild )
				$search = trim($search, '*');

			$search_columns = array();
			if ($qv['search_columns'] )
				$search_columns = array_intersect($qv['search_columns'], ['id', 'name']);
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array('id' );			
				else
					$search_columns = array('id', 'name' );
			}	
			$search_columns = apply_filters( 'usam_feeds_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql($search, $search_columns, $wild );
		}
		if ( !empty($include ) ) 
		{
			$ids = implode( ',', $include );			
			$this->query_where .= " AND ".USAM_TABLE_FEEDS.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list($qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_FEEDS.".id NOT IN ($ids)";
		}	
		if ( !empty($qv['platform'] ) )
		{		
			$platform = implode( "','", (array)$qv['platform'] );		
			$this->query_where .= " AND ".USAM_TABLE_FEEDS.".platform IN ('$platform')";
		}
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty($this->meta_query->queries) ) 
		{
			$clauses = $this->meta_query->get_sql( 'rule', USAM_TABLE_FEED_META, USAM_TABLE_FEEDS, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( $this->meta_query->has_or_relation() ) {
				$distinct = true;
			}
		}		
		do_action_ref_array( 'usam_pre_feeds_query', array( &$this ) );
	}
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( $type == 'd' && isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_feed' );	
		return $data;
	}

	/**
	 * Execute the query, with the current variables.	
	 */
	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;
	
		$count_fields = count($this->fields);
		$this->request = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";		
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
			
		if ( $qv['cache_meta'] )
		{				
			if ( $qv['number'] == 1 )
			{
				$ids = array( $this->results->id ); 			
			}
			else
			{
				$ids = array();
				foreach ( $this->results as $result ) 
				{
					if ( !empty($result->id) )
						$ids[] = $result->id; 					
				}	
			}
			usam_update_cache( $ids, [USAM_TABLE_FEED_META => 'feed_meta'], 'feed_id' );			
		}
	}

	/**
	 * Retrieve query variable.
	 */
	public function get($query_var ) 
	{
		if ( isset($this->query_vars[$query_var] ) )
			return $this->query_vars[$query_var];

		return null;
	}

	public function set($query_var, $value ) 
	{
		$this->query_vars[$query_var] = $value;
	}


	protected function get_search_sql($string, $cols, $wild = false ) 
	{
		global $wpdb;

		$searches = array();
		$leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
		$like = $leading_wild . $wpdb->esc_like($string ) . $trailing_wild;

		foreach ($cols as $col ) 
		{
			if ( 'id' == $col || 'code' == $col ) 
				$searches[] = $wpdb->prepare( USAM_TABLE_FEEDS.".$col = %s", $string );
			elseif ( 'name' == $col ) 
				$searches[] = USAM_TABLE_FEEDS.".$col LIKE LOWER ('%".$wpdb->esc_like( $string )."%')";
			else 
				$searches[] = USAM_TABLE_FEEDS.$wpdb->prepare( ".$col LIKE %s", $like );
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
	protected function parse_orderby($orderby ) 
	{
		global $wpdb;

		$_orderby = '';
		if ( in_array($orderby, ['name', 'id', 'start_date', 'active', 'exchange_option'] ) )
		{
			$_orderby = USAM_TABLE_FEEDS.'.'.$orderby;
		} 		
		elseif ( 'include' === $orderby && !empty($this->query_vars['include'] ) ) {
			$include = wp_parse_id_list($this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_FEEDS.".id, $include_sql )";
		} 		
		return $_orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 */
	protected function parse_order($order ) {
		if ( ! is_string($order ) || empty($order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper($order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}
	
	public function __get($name ) 
	{
		if ( in_array($name, $this->compat_fields ) ) 
		{
			return $this->$name;
		}
	}
	
	public function __set($name, $value )
	{
		if ( in_array($name, $this->compat_fields ) ) 
		{
			return $this->$name = $value;
		}
	}

	public function __isset($name ) 
	{
		if ( in_array($name, $this->compat_fields ) ) {
			return isset($this->$name );
		}
	}

	public function __unset($name ) 
	{
		if ( in_array($name, $this->compat_fields ) ) {
			unset($this->$name );
		}
	}

	public function __property($name, $arguments )
	{
		if ( 'get_search_sql' === $name ) {
			return property_user_func_array( array($this, $name ), $arguments );
		}
		return false;
	}
}

function usam_get_feeds($query = [] )
{	
	$query['count_total'] = false;
	$feeds = new USAM_Feeds_Query($query );	
	return $feeds->get_results();	
}	