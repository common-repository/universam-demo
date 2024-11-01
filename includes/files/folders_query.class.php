<?php
// Класс работы с папками
class USAM_Folders_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $meta_query = false;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'name' => 's', 'parent_id' => 'd', 'user_id' => 'd', 'status' => 's', 'slug' => 's', 'count' => 'd', 'date_update' => 's'];

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
		if ( !empty($query) ) 
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
			'id'  => '',					
			'include' => array(),
			'exclude' => array(),
			'search' => '',
			'search_columns' => array(),
			'orderby' => 'name',
			'order' => 'ASC',
			'offset' => '',
			'number' => '',
			'paged' => 1,
			'count_total' => true,
			'cache_results' => false,		
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
		do_action( 'usam_pre_get_folders', $this );
		
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
			if ( $field == 'all' )
				$this->fields[] = USAM_TABLE_FOLDERS.".*";	
			elseif ( $field == 'count' )				
				$this->fields[] = "COUNT(*) AS count";	
			elseif ( $field == 'id=>name' )	
			{
				$this->fields[] = "id";			
				$this->fields[] = "name";						
			}
			elseif ( $field == 'id=>data' )	
				$this->fields[] = USAM_TABLE_FOLDERS.".*";	
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_FOLDERS.".$field";
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_FOLDERS.".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
						
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_FOLDERS;
		$this->query_where = "WHERE 1=1";	

		$date_parameters = array();
		foreach( ['second', 'minute', 'hour', 'day', 'w', 'monthnum', 'year'] as $k ) 
		{					
			if ( $qv[$k] !== '' )
				$date_parameters[$k] = $qv[$k];
		}
		if ( $date_parameters ) 
		{
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_FOLDERS, 'date_update' );
			$this->query_where .= $date_query->get_sql();
		} //users
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_FOLDERS, 'date_update' );
			$this->query_where .= $this->date_query->get_sql();
		}		
		
		if ( !empty( $qv['include'] ) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;
				
		if ( !empty($qv['slug']) ) 
		{
			if ( is_array($qv['slug']) ) 
				$slug = $qv['slug'];			
			elseif ( is_string($qv['slug']) )
				$slug = array_map( 'trim', explode( ',', $qv['slug'] ) );
			elseif ( is_numeric($qv['slug']) )
				$slug = $qv['slug'];
			if ( !empty($slug) )
				$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".slug IN ('".implode( "','",  $slug )."')";		
		}		
		if ( !empty($qv['slug__not_in']) ) 
		{			
			$slug__not_in = (array) $qv['slug__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".slug NOT IN ('".implode( "','",  $slug__not_in )."')";
		}		
		$object_join = false;
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
						$ordersby_array[] = USAM_TABLE_FOLDERS.'.'.$_value;
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

		if ( empty($qv['orderby']) ) 
		{			
			$ordersby = array( 'name' => $order );
		} 
		elseif ( is_array( $qv['orderby'] ) ) 
		{
			$ordersby = $qv['orderby'];
		} 
		else 
		{
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
			$orderby_array[] = USAM_TABLE_FOLDERS.".name $order";
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
		if ( isset($qv['search']) )
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
				$search_columns = array_intersect( $qv['search_columns'], array( 'id', 'name', 'slug' ) );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array('id', 'name', 'slug');				
				else
					$search_columns = array('name', 'slug');
			}	
			$search_columns = apply_filters( 'usam_folders_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( !empty( $include ) ) 
		{	
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".id NOT IN ($ids)";
		}		
		if ( isset($qv['user_id']) ) 
		{
			if ( is_array($qv['user_id']) )
			{
				if ( !empty($qv['user_id']) ) 
					$user_id = $qv['user_id'];
			}
			elseif( is_numeric($qv['user_id']) )
				$user_id = (array)$qv['user_id'];
			elseif( is_string($qv['user_id']) )
				$user_id = array_map( 'trim', explode( ',', $qv['user_id'] ) );				
			if ( !empty($user_id) )
			{
				$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".user_id IN (".implode(',',  wp_parse_id_list($user_id)).")";
			}
		}
		if ( isset($qv['status']) ) 
		{
			if ( $qv['status'] != 'all' ) 
			{
				$status = implode( "','", (array)$qv['status'] );
				$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".status IN ('$status')";
			}
		}
		else
			$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".status!='delete'";
		if ( isset($qv['parent_id']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['parent_id'] ) );
			$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".parent_id IN ($ids)";
		}	
		if ( !empty($qv['child_of']) ) 
		{
			$ids = $this->get_child_of( $qv['child_of'] );	
			if ( !empty($ids) )
				$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".id IN (".implode( ',', $ids ).")";
			else
				$this->query_where .= " AND 1=0";
		}	
		if ( !empty($qv['ancestor']) ) 
		{
			$ids = $this->get_ancestors( $qv['ancestor'] );			
			$ids[] = $qv['ancestor']; 
			$ids = implode( ',', $ids );
			$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".id IN ($ids)";
		}	
		if ( isset($qv['slug']) )
		{
			$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".slug IN ('".implode( "','", (array)$qv['slug'] )."')";
		}	
		if ( isset($qv['name']) )
		{
			$this->query_where .= " AND ".USAM_TABLE_FOLDERS.".name='".$qv['name']."'";
		}			
		do_action_ref_array( 'usam_pre_folders_query', array( &$this ) );
	}
		
	function get_child_of( $folder_id )
	{			
		$ancestors_folders = get_option( 'usam_ancestors_folders', array() );		
		$results = array();
		if( !empty($ancestors_folders[$folder_id]) )
		{ 
			$results = array_merge( $results, $ancestors_folders[$folder_id] );
			foreach ( $ancestors_folders[$folder_id] as $id ) 
			{
				$ids = $this->get_child_of( $id );
				$results = array_merge( $results, $ids );
			}			
		}
		return $results;
	}
	
	function get_ancestors( $folder_id )
	{				
		$ancestors_folders = get_option( 'usam_ancestors_folders', array() );		
		$results = array();
		if( !empty($ancestors_folders) )
		{
			foreach ( $ancestors_folders as $key => $folder_ids ) 
			{		
				if( in_array($folder_id, $folder_ids) )
				{
					$results[] = $key;
					$ids = $this->get_ancestors( $key );
					$results = array_merge( $ids, $results );
				}
			}			
		}
		return $results;
	}
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( $type == 'd' && isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_folder' );	
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
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_folders_query', 'SELECT FOUND_ROWS()' ) );				
		
		$r = array();
		if ( 'id=>data' == $qv['fields'] ) 
		{						
			foreach ( $this->results as $key => $result ) 
			{
				$r[ $result->id ] = $result;
			}			
			$this->results = $r;			
		}			
		elseif ( 'id=>name' == $qv['fields'] ) 
		{					
			foreach ( $this->results as $key => $result ) 
			{
				$r[ $result->id ] = $result->name;
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

		foreach ( $cols as $col ) 
		{
			if ( 'id' == $col )
				$searches[] = $wpdb->prepare( USAM_TABLE_FOLDERS.".$col = %s", $string );
			elseif ( 'name' == $col )
				$searches[] = $wpdb->prepare( USAM_TABLE_FOLDERS.".$col LIKE LOWER (%s)", "%".$wpdb->esc_like( $string )."%" );
			else 
				$searches[] = $wpdb->prepare( USAM_TABLE_FOLDERS.".$col LIKE %s", $like );
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
			$_orderby = USAM_TABLE_FOLDERS.'.'.$orderby;			
		elseif ( 'COUNT' == $orderby ) 
		{
			$_orderby = 'COUNT(*)';
		} 		
		elseif ( 'include' === $orderby  )
		{
			if ( !empty( $this->query_vars['include'] ) )
			{
				$include = wp_parse_id_list( $this->query_vars['include'] );
				$include_sql = implode( ',', $include );
				$_orderby = "FIELD( ".USAM_TABLE_FOLDERS.".id, $include_sql )";
			} 	
			elseif ( !empty($this->query_vars['child_of']) )
			{ 
				$child_of = $this->get_child_of( $this->query_vars['child_of'] );
				if ( $child_of )
				{
					$include_sql = implode( ',', $child_of );
					$_orderby = "FIELD( ".USAM_TABLE_FOLDERS.".id, $include_sql )";
				}
			} 
			elseif ( !empty($this->query_vars['ancestor']) )
			{ 
				$ids = $this->get_ancestors( $this->query_vars['ancestor'] );			
				$ids[] = $this->query_vars['ancestor']; 
				if ( $ids )
				{
					$include_sql = implode( ',', $ids );
					$_orderby = "FIELD( ".USAM_TABLE_FOLDERS.".id, $include_sql )";
				}
			}		
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

function usam_get_folders( $args = array() )
{
	$args['count_total'] = false;
	$query = new USAM_Folders_Query( $args );	
	$result = $query->get_results();	
	
	return $result;
}