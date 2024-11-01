<?php
// Класс работы с местоположениями 
class USAM_Locations_Query 
{
	public $query_vars = array();
	public $meta_query = array();
	private $results;
	private $total = 0;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'name' => 's', 'code' => 's', 'parent' => 'd', 'sort' => 'd'];

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
			'cache_results' => false,			
			'cache_meta' => false,				
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
		do_action( 'usam_pre_get_locations', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		$distinct = false;		
		
		$join = array();	
		$this->fields = [];
		if ( 'id=>data' == $qv['fields'] )
			$this->fields[] = USAM_TABLE_LOCATION.".*";
		else
		{
			if ( $qv['fields'] == 'code=>id' ) 
				$fields = ['code', 'id'];
			elseif ( 'id=>code' == $qv['fields']) 			
				$fields = ['code' , 'id'];			
			elseif ( 'id=>name' == $qv['fields']) 			
				$fields = ['id' , 'name'];	
			elseif ( 'code=>name' == $qv['fields']) 			
				$fields = ['code', 'name'];	
			else
				$fields = is_array($qv['fields']) ? array_unique( $qv['fields'] ) : explode(',', $qv['fields']);		
			if ( $qv['add_fields'] )
			{
				$add_fields = is_array($qv['add_fields']) ? array_unique( $qv['add_fields'] ) : explode(',', $qv['add_fields']);
				$fields = array_merge( $fields, $add_fields );	
			}				
			foreach ( $fields as $field ) 
			{
				if ( $field == 'all' )
					$this->fields[] = USAM_TABLE_LOCATION.".*";		
				elseif ( $field == 'count' )
					$this->fields[] = "COUNT(*) AS count";				
				elseif ( 'id' == $field ) 
					$this->fields[] = USAM_TABLE_LOCATION.'.id';				
				else
					$this->fields[] = USAM_TABLE_LOCATION.'.'.sanitize_key($field );
			}				
		} 		
		$this->query_fields = implode( ',', $this->fields );	
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );

		$this->query_from = "FROM ".USAM_TABLE_LOCATION;
		$this->query_where = "WHERE 1=1";		
		
		if ( !empty( $qv['include'] ) ) {
			$include = wp_parse_id_list( $qv['include'] );
		} else {
			$include = false;
		}				
		// Группировать
		$qv['groupby'] = isset($qv['groupby'] ) ? $qv['groupby'] : '';
		
		if ( $qv['groupby'] != '' )
		{
			if ( is_array($qv['groupby']) )					
				$groupby = $qv['groupby'];					
			else
				$groupby[] = $qv['groupby'];			
		}		
		if ( !empty($groupby) )
			$this->query_groupby = 'GROUP BY ' . implode( ', ', $groupby );
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
			$ordersby = $qv['orderby'];
		else 
		{	// Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
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
			$orderby_array[] = USAM_TABLE_LOCATION.".id $order";		}

		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );
		
		if ( isset($qv['number'] ) && $qv['number'] > 0 ) 
		{
			if ( $qv['offset'] ) 
			{
				$this->query_limit = $wpdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
			} 
			else 
			{
				$this->query_limit = $wpdb->prepare( "LIMIT %d, %d", $qv['number'] * ( $qv['paged'] - 1 ), $qv['number'] );
			}
		} 
		$search = '';
		if ( isset($qv['search'] ) )
			$search = trim( $qv['search'] );

		if ( $search ) 
		{
			$distinct = true;
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
					$search_columns = array( 'name');
			}	
			$search_columns = apply_filters( 'usam_location_search_columns', $search_columns, $search, $this );
			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}
		if ( !empty($include) ) 
		{			
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_LOCATION.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_LOCATION.".id NOT IN ($ids)";
		}	
		if ( isset($qv['parent']) ) 
		{		
			$parent = implode( "','",  (array)$qv['parent'] );		
			$this->query_where .= " AND ".USAM_TABLE_LOCATION.".parent IN ('".$parent."')";
		}			
		if ( !empty($qv['code']) && $qv['code'] !== 'all' ) 
		{		
			$code = implode( "','",  (array)$qv['code'] );		
			$this->query_where .= " AND ".USAM_TABLE_LOCATION.".code IN ('".$code."')";
		}		
		elseif ( !empty($qv['not_in__code']) ) 
		{		
			$code = implode( "','",  (array)$qv['not_in__code'] );		
			$this->query_where .= " AND ".USAM_TABLE_LOCATION.".code NOT IN ('".$code."')";
		}	
		elseif ( !empty($qv['code_to']) ) 
		{						
			$type_location = usam_get_types_location( 'code' );
			$code_to = array();
			foreach ( $type_location as $code => $type )
			{					
				$code_to[] = $code;	
				if ( $code == $qv['code_to'] )
					break;
			}
			if ( !empty($code_to) )
			{						
				$this->query_where .= " AND ".USAM_TABLE_LOCATION.".code IN ('".implode( "','", $code_to )."')";
			}
		}		
		if ( isset($qv['point_delivery']) && $qv['point_delivery'] ) 
		{
			$this->query_join .=  " INNER JOIN ".USAM_TABLE_STORAGES." ON (".USAM_TABLE_STORAGES.".location_id = ".USAM_TABLE_LOCATION.".id AND ".USAM_TABLE_STORAGES.".issuing=1 AND ".USAM_TABLE_STORAGES.".active=1)";
			$distinct = true;
		}	
		if ( !empty($qv['storage_type']) ) 
		{
			$types = implode( "','",  (array)$qv['storage_type'] );
			$this->query_join .=  " INNER JOIN ".USAM_TABLE_STORAGES." ON (".USAM_TABLE_STORAGES.".location_id = ".USAM_TABLE_LOCATION.".id AND ".USAM_TABLE_STORAGES.".active=1)";
			$this->query_where .= " AND ".USAM_TABLE_STORAGES.".type IN ('".$types."')";
			$distinct = true;
		}
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty( $this->meta_query->queries ) ) 
		{
			$clauses = $this->meta_query->get_sql( 'location', USAM_TABLE_LOCATION_META, USAM_TABLE_LOCATION, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where']; 

			if ( $this->meta_query->has_or_relation() ) {
				$distinct = true;
			}
		}	
		$conditions_query = new USAM_Conditions_Query();
		$this->query_where .= $conditions_query->get_sql_clauses( $qv );
		if ( $distinct )
			$this->query_fields = "DISTINCT $this->query_fields";
		do_action_ref_array( 'usam_pre_location_query', array( &$this ) );
	}
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( isset($data->$column) )
			{
				if ( $type == 'd' )
					$data->$column = (int)$data->$column;
			}
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_location' );	
		return $data;
	}
	
	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;
		$this->request = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";	
		$count_fields = count($this->fields);
		if ( $count_fields > 1 || ($qv['fields'] == 'all' || 'code=>data' == $qv['fields'] || 'id=>data' == $qv['fields']) && $qv['number'] != 1 ) 
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
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_location_query', 'SELECT FOUND_ROWS()' ) );		
		
		if ( $qv['cache_meta'] )
		{
			$ids = array();	
			foreach ( $this->results as $result ) 
			{
				if ( isset($result->id) )
					$ids[] = $result->id; 
				elseif ( $qv['fields'] == 'id' )
					$ids[] = $result; 			
				else
					break;
			}					
			usam_update_cache( $ids, [USAM_TABLE_LOCATION_META => 'location_meta'], 'location_id' );	
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
		elseif ( 'code=>data' == $qv['fields'] ) 
		{		
			$r = array(); 
			foreach ( $this->results as $key => $result ) 
			{
				$r[$result->code] = $result;
			}			
			$this->results = $r;			
		}
		elseif ( 'code=>name' == $qv['fields'] ) 
		{		
			$r = array();
			foreach ( $this->results as $key => $result ) 
			{
				$r[$result->code] = $result->name;
			}			
			$this->results = $r;			
		}
		elseif ( 'code=>id' == $qv['fields'] ) 
		{		
			$r = array();
			foreach ( $this->results as $key => $result ) 
			{
				$r[$result->code] = $result->id;
			}			
			$this->results = $r;			
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
			if ( 'id' == $col ) 
				$searches[] = $wpdb->prepare( USAM_TABLE_LOCATION.".$col = %s", $string );
			elseif ( 'name' == $col )
			{
				$switcher = usam_switcher( $string );
				$searches[] = $wpdb->prepare( USAM_TABLE_LOCATION.".$col LIKE LOWER (%s)", $wpdb->esc_like( $string )."%" );
				$searches[] = $wpdb->prepare( USAM_TABLE_LOCATION.".$col LIKE LOWER (%s)", $wpdb->esc_like( $switcher )."%" );
			}
			else 
				$searches[] = $wpdb->prepare( USAM_TABLE_LOCATION.".$col LIKE LOWER ('%s')", $like );
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
		global $wpdb;

		$_orderby = '';
		if ( isset($this->db_fields[$orderby]) )
			$_orderby = USAM_TABLE_LOCATION.'.'.$orderby;
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) ) 
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_LOCATION.".id, $include_sql )";
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

function usam_get_locations( $query_vars = array() )
{	
	$query_vars['count_total'] = false;
	$query = new USAM_Locations_Query( $query_vars );	
	return $query->get_results();	
}