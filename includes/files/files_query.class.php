<?php
// Класс работы с файлами
class USAM_Files_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $meta_query = false;	
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'code' => 's', 'object_id' => 'd', 'user_id' => 'd', 'type' => 's', 'folder_id' => 'd', 'title' => 's', 'name' => 's', 'file_path' => 's', 'mime_type' => 's', 'date_insert' => 's', 'date_update' => 's', 'size' => 'd', 'uploaded' => 'd', 'status' => 's'];
	
	// SQL clauses
	public $query_distinct;
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
		require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
		require_once( USAM_FILE_PATH . '/includes/query/conditions_query.php' );
		require_once( USAM_FILE_PATH . '/includes/query/date.php' );				
		if ( !empty( $query ) ) 
		{
			$this->prepare_query( $query );
			$this->query();
		}
	}

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
			'user' => '',
			'calendar'  => '',			
			'date_insert' => '',	
			'start'  => '',			
			'end' => '',						
			'title' => '', 							
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
			'suppress_filters' => true,				
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
		do_action( 'usam_pre_get_files', $this );
		
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
				$this->fields[] = USAM_TABLE_FILES.".*";
			elseif ( $field == 'count' )
				$this->fields[] = "COUNT(*) AS count";	
			elseif ( $field == 'download_id' )
				$this->fields[] = USAM_TABLE_DOWNLOAD_STATUS.".id AS download_id";	
			elseif ( $field == 'ip_number' )
				$this->fields[] = USAM_TABLE_DOWNLOAD_STATUS.".ip_number AS ip_number";	
			elseif ( $field == 'downloads' )
				$this->fields[] = USAM_TABLE_DOWNLOAD_STATUS.".downloads AS downloads";	
			elseif ( $field == 'sum_size' )
				$this->fields[] = "SUM(size) AS sum_size";	
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_FILES.".$field";			
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_FILES.".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );

		$this->query_from = "FROM ".USAM_TABLE_FILES;
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
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_FILES );
			$this->query_where .= $date_query->get_sql();
		} //users
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_FILES );
			$this->query_where .= $this->date_query->get_sql();
		}
		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( !empty( $qv['include'] ) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;
			
		if ( isset($qv['type']) ) 
		{
			if ( is_array( $qv['type'] ) ) 
				$type = $qv['type'];			
			elseif ( is_string($qv['type']) && !empty($qv['type']) )
			{
				if ( $qv['type'] != 'all' )
					$type = array_map( 'trim', explode( ',', $qv['type'] ) );
			}
			elseif ( is_numeric( $qv['type'] ) )
				$type = array( $qv['type'] );
				
			if ( !empty($type) ) 
				$this->query_where .= " AND ".USAM_TABLE_FILES.".type IN ('".implode( "','",  $type )."')";	
		}	
		if ( isset($qv['type__not_in']) )
		{
			$type__not_in = (array)$qv['type__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_FILES.".type NOT IN ('".implode( "','",  $type__not_in )."')";
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
			if ( 'length_event' == $_value ) 
			{
				$orderby_array[] = "UNIX_TIMESTAMP(".USAM_TABLE_FILES.'.end) - UNIX_TIMESTAMP('. USAM_TABLE_FILES.'.start) ' . $this->parse_order( $_value );	
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
			$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
		}

		// If no valid clauses were found, order by id.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = USAM_TABLE_FILES.".id $order";
		}
		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		// limit
		if ( isset($qv['number']) && $qv['number'] > 0 ) {
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
				$search_columns = array_intersect($qv['search_columns'], ['id', 'user_id', 'name', 'title']);
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array( 'id', 'user_id' );				
				else
					$search_columns = array( 'name', 'title'  );
			}	
			$search_columns = apply_filters( 'usam_files_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( !empty($include) ) 
		{	
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_FILES.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_FILES.".id NOT IN ($ids)";
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
				$this->query_where .= " AND ".USAM_TABLE_FILES.".user_id IN (".implode(',',  wp_parse_id_list($user_id)).")";
		}			
		if ( !empty($qv['name']) ) 
		{
			$name = implode( "','",  (array)$qv['name'] );
			$this->query_where .= " AND ".USAM_TABLE_FILES.".name IN ('$name')";
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
				$this->query_where .= " AND ".USAM_TABLE_FILES.".status IN ('".implode( "','",  $status )."')";	
		}	
		else
			$this->query_where .= " AND ".USAM_TABLE_FILES.".status!='delete'";	
		if ( isset($qv['status__not_in']) ) 
		{
			$status__not_in = (array) $qv['status__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_FILES.".status NOT IN ('".implode("','",  $status__not_in)."')";
		}	
		if ( isset($qv['object_id']) ) 
		{ 
			if ( !is_array($qv['object_id']) )
				$qv['object_id'] = (array)$qv['object_id'];
									
			$object_id = implode( ',', $qv['object_id'] );
			$this->query_where .= " AND ".USAM_TABLE_FILES.".object_id IN ($object_id)";
		}	
		if ( isset($qv['object_id__not_in']) ) 
		{
			$object_id__not_in = (array) $qv['object_id__not_in'];				
			$this->query_where .= " AND ".USAM_TABLE_FILES.".object_id NOT IN (".implode( ",",  $object_id__not_in ).")";
		}		
		if ( isset($qv['folder_path']) ) 
		{ 			
			$folders = explode("/\/",$qv['folder_path']);
			$parent_id = 0;
			$folder_id = 0;
			foreach ( $folders as $folder_name ) 
			{				
				$folder = usam_get_folders(['name' => $folder_name, 'parent_id' => $parent_id, 'number' => 1]);
				if ( !$folder )						
					break;
				else
				{
					$parent_id = $folder['parent_id'];
					$folder_id = $folder['id'];
				}
			}	
			if ( $folder_id )
				$this->query_where .= " AND ".USAM_TABLE_FILES.".folder_id = $folder_id";
			else
				$this->query_where .= " AND 1=0";
		}
		if ( isset($qv['folder_id']) ) 
		{ 
			if ( !is_array($qv['folder_id']) )
				$qv['folder_id'] =  (array)$qv['folder_id'];
			
			$folder_id = implode( ',', $qv['folder_id'] );
			$this->query_where .= " AND ".USAM_TABLE_FILES.".folder_id IN ($folder_id)";
		}			
		if ( isset($qv['folder_child_of']) ) 
		{ 
			$ids = $this->get_child_of( $qv['folder_child_of'] );			
			$ids[] = $qv['folder_child_of'];	
			$this->query_where .= " AND ".USAM_TABLE_FILES.".folder_id IN (".implode( ',', $ids ).")";
		}	
		if ( !empty($qv['user_file']) ) 
		{			
			$contact = usam_get_contact( $qv['user_file'], 'user_id' );
			if ( !empty($contact) )
			{
				$groups = usam_get_groups_object($contact['id'], 'contact');
				if ( !empty($groups) )
				{
					$this->query_join .=  " LEFT JOIN ".USAM_TABLE_FILE_META." ON (".USAM_TABLE_FILE_META.".file_id=".USAM_TABLE_FILES.".id AND ".USAM_TABLE_FILE_META.".meta_key='group')";
					$this->query_where .= " AND (".USAM_TABLE_FILE_META.".meta_value IN (".implode( ',', $groups ).") OR ".USAM_TABLE_FILES.".user_id=".$qv['user_file'].")";
				}
			}
			if ( empty($groups) )
				$this->query_where .= " AND ".USAM_TABLE_FILES.".user_id=".$qv['user_file']."";
		}		
		if ( isset($qv['purchased_user_files']) ) 
		{
			$ids = implode( ',',  wp_parse_id_list( (array)$qv['purchased_user_files'] ) );
			$this->query_join .=  " INNER JOIN ".USAM_TABLE_DOWNLOAD_STATUS." ON (".USAM_TABLE_DOWNLOAD_STATUS.".fileid = ".USAM_TABLE_FILES.".id)";
			$this->query_join .=  " INNER JOIN ".USAM_TABLE_ORDERS." ON (".USAM_TABLE_ORDERS.".id = ".USAM_TABLE_DOWNLOAD_STATUS.".order_id)";	
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".user_id='$ids'";			
			if ( isset($qv['active']) ) 
			{
				$this->query_where .= " AND ".USAM_TABLE_DOWNLOAD_STATUS.".active='".$qv['active']."' AND ".USAM_TABLE_DOWNLOAD_STATUS.".downloads>0";		
			}
		}
		if ( isset($qv['order_id']) ) 
		{
			$ids = implode( ',',  wp_parse_id_list( (array)$qv['order_id'] ) );
			$this->query_join .=  " INNER JOIN ".USAM_TABLE_DOWNLOAD_STATUS." ON (".USAM_TABLE_DOWNLOAD_STATUS.".fileid = ".USAM_TABLE_FILES.".id)";
			$this->query_where .= " AND ".USAM_TABLE_DOWNLOAD_STATUS.".order_id IN ($ids)";
		}
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty( $this->meta_query->queries ) ) 
		{
			$clauses = $this->meta_query->get_sql( 'file', USAM_TABLE_FILE_META, USAM_TABLE_FILES, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];
			if ( $this->meta_query->has_or_relation() && strripos($this->query_fields, 'DISTINCT')  === false ) {
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
			}
		}
		$conditions_query = new USAM_Conditions_Query();
		$this->query_where .= $conditions_query->get_sql_clauses( $qv );	
		if ( !$qv['suppress_filters'] ) 
		{
			$this->query_where = apply_filters_ref_array( 'files_where_request', [$this->query_where, &$this] );
			$this->query_groupby = apply_filters_ref_array( 'files_groupby_request', [$this->query_groupby, &$this] );
			$this->query_join = apply_filters_ref_array( 'files_join_request', [$this->query_join, &$this] );
			$this->query_orderby = apply_filters_ref_array( 'files_orderby_request', [$this->query_orderby, &$this] );		
			$this->query_distinct = apply_filters_ref_array( 'files_distinct_request', [$this->query_distinct, &$this] );
			$this->query_fields = apply_filters_ref_array( 'files_fields_request', [$this->query_fields, &$this] );
			$this->query_limit = apply_filters_ref_array( 'files_limits_request', [$this->query_limit, &$this] );
		}
		do_action_ref_array( 'usam_pre_files_query', array( &$this ) );
	}
	
	function get_child_of( $folder_id )
	{				
		$ancestors_folders = get_option( 'usam_ancestors_folders', [] );		
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
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( $type == 'd' && isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_file' );	
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
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_files_query', 'SELECT FOUND_ROWS()' ) );	
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
				$searches[] = $wpdb->prepare( USAM_TABLE_FILES.".$col = %s", $string );
			elseif ( $col == 'title' )
				$searches[] = $wpdb->prepare( USAM_TABLE_FILES.".$col LIKE LOWER (%s)", "%".$wpdb->esc_like( $string )."%" );
			else 
				$searches[] = $wpdb->prepare( USAM_TABLE_FILES.".$col LIKE %s", $like );			
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
			$_orderby = USAM_TABLE_FILES.'.'.$orderby;
		elseif ( $orderby == 'date' )
			$_orderby = USAM_TABLE_FILES.'.date_insert';	
		elseif ( $orderby == 'modified' )		
			$_orderby = USAM_TABLE_FILES.'.date_update';	
		elseif ( $orderby == 'uploaded' )
			$_orderby = USAM_TABLE_FILES.'.uploaded';			
		elseif ( 'meta_value' == $orderby ) 		
			$_orderby = USAM_TABLE_FILE_META.".meta_value";
		elseif ( 'meta_value_num' == $orderby ) 
			$_orderby = "CAST(".USAM_TABLE_FILE_META.".meta_value AS signed)";
		elseif ( 'include' === $orderby && !empty($this->query_vars['include']) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_FILES.".id, $include_sql )";
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

function usam_get_files( $args = array() )
{
	$args['count_total'] = false;	
	$tasks = new USAM_Files_Query( $args );	
	$result = $tasks->get_results();	
	
	return $result;
}


function usam_cache_attachments( $ids, $type )
{
	$key = "usam_{$type}_attachments";
	$object_ids = array();	
	foreach ( $ids as $id ) 
	{ 
		if ( ! $cache = wp_cache_get( $id, $key ) )
			$object_ids[] = $id; 					
	}	
	if ( !empty($object_ids) )
	{ 
		$files = usam_get_files(['object_id' => $object_ids, 'type' => $type]);	
		$results_cache = array();
		foreach ( $files as $file )
		{
			$results_cache[$file->object_id][] = $file;
		} 
		foreach ( $object_ids as $id )
		{
			if ( isset($results_cache[$id]) )
				wp_cache_set( $id, $results_cache[$id], $key );
			else
				wp_cache_set( $id, array(), $key );				
		}
	}
}