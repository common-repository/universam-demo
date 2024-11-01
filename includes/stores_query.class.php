<?php
class USAM_Storages_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $meta_query = false;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'code' => 's', 'title' => 's', 'active' => 'd', 'issuing' => 'd', 'type' => 's', 'shipping' => 'd', 'location_id' => 'd', 'sort' => 'd', 'owner' => 's'];
	
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
		require_once( USAM_FILE_PATH . '/includes/query/conditions_query.php' );
		require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
		if ( !empty( $query ) ) 
		{
			$this->prepare_query( $query );
			$this->query();
		}
	}

	public static function fill_query_vars( $args ) 
	{			
		$defaults = array(			
			'id'  => '',
			'user' => '',
			'calendar'  => '',			
			'date_insert' => '',				
			'title' => '', 							
			'include' => array(),
			'exclude' => array(),
			'search' => '',		
			'search_columns' => array(),
			'orderby' => 'sort',
			'order' => 'ASC',
			'offset' => '',
			'number' => '',
			'paged' => 1,
			'count_total' => true,			
			'cache_results' => false,	
			'cache_meta' => false,		
			'cache_location' => false,			
			'images_cache' => false,				
			'fields' => 'all',		
			'add_fields' => '',				
		);		
		return wp_parse_args( $args, $defaults );
	}
	
	public function prepare_query( $query = array() ) 
	{
		global $wpdb;

		$distinct = false;
		if ( empty( $this->query_vars ) || !empty( $query ) ) 
		{
			$this->query_limit = null;
			$this->query_vars = $this->fill_query_vars( $query );
		}			
		do_action( 'usam_pre_storages', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		
		$join = array();		
		$this->fields = [];
		if ( 'all' == $qv['fields'] || 'code=>data' == $qv['fields'] || 'id=>data' == $qv['fields'] )
		{
			$this->query_fields = USAM_TABLE_STORAGES.".*";
		}
		elseif ( 'code=>meta_key' == $qv['fields'] )
		{
			$this->fields[] = USAM_TABLE_STORAGES.".code";	
			$this->fields[] = USAM_TABLE_STORAGES.".id";	
		}
		else
		{				
			$fields = is_array($qv['fields']) ? array_unique( $qv['fields'] ) : explode(',', $qv['fields']);
			if ( $qv['add_fields'] )
			{
				$add_fields = is_array($qv['add_fields']) ? array_unique( $qv['add_fields'] ) : explode(',', $qv['add_fields']);
				$fields = array_merge( $fields, $add_fields );	
			}			
			foreach ( $fields as $field ) 
			{		
				if ( $field == 'all' )
					$this->fields[] = USAM_TABLE_STORAGES.".*";		
				elseif ( $field == 'id=>title' )
				{
					$this->fields[] = USAM_TABLE_STORAGES.".id";		
					$this->fields[] = USAM_TABLE_STORAGES.".title";
				}
				elseif ( $field == 'count' )
					$this->fields[] = "COUNT(*) AS count";		
				elseif ( $field == 'meta_value' )
					$this->fields[] = USAM_TABLE_STORAGE_META.".meta_value";	
				elseif ( isset($this->db_fields[$field]) )
					$this->fields[] = USAM_TABLE_STORAGES.".$field";
			}			
		} 	
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_STORAGES.".*";
		else
			$this->query_fields = implode( ',', $this->fields );	
		if ( isset($qv['count_total']) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode(' ', $join);

		$this->query_from = "FROM ".USAM_TABLE_STORAGES;
		$this->query_where = "WHERE 1=1";				

		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( !empty( $qv['include'] ) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;			
		
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
			$this->query_groupby = 'GROUP BY ' . implode(', ', $ordersby_array);
		else
			$this->query_groupby = '';		
		
		// СОРТИРОВКА
		$qv['order'] = isset($qv['order']) ? strtoupper($qv['order']) : '';
		$order = $this->parse_order( $qv['order'] );

		if ( empty( $qv['orderby'] ) ) 
			$ordersby = ['id' => $order];
		elseif ( is_array($qv['orderby']) ) 
			$ordersby = $qv['orderby'];
		else 
		{   // Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
			$ordersby = preg_split('/[,\s]+/', $qv['orderby']);
		} 
		$orderby_array = array();
		foreach( $ordersby as $_key => $_value ) 
		{
			if ( ! $_value ) {
				continue;
			}		
			if ( 'length_event' == $_value ) 
			{
				$orderby_array[] = "UNIX_TIMESTAMP(".USAM_TABLE_STORAGES.'.end) - UNIX_TIMESTAMP('. USAM_TABLE_STORAGES.'.start) ' . $this->parse_order( $_value );	
				continue;				
			} 			
			if ( is_int($_key) ) 
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
			$orderby_array[] = USAM_TABLE_STORAGES.".id $order";
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
				$search_columns = array_intersect($qv['search_columns'], ['id', 'branch_number', 'code', 'title', 'address']);
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = ['id', 'branch_number'];				
				else
					$search_columns = ['title', 'code', 'address'];
			}	
			$search_columns = apply_filters( 'usam_storage_search_columns', $search_columns, $search, $this );
			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild ); 
		}
		if ( !empty($include) ) 
		{	// Sanitized earlier.
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_STORAGES.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_STORAGES.".id NOT IN ($ids)";
		}		
		if ( !empty($qv['id']) ) 
			$this->query_where .= " AND ".USAM_TABLE_STORAGES.".id IN (".implode( ',',  (array)$qv['id'] ).")";
		if ( isset($qv['active'] ) && $qv['active'] != 'all' ) 
		{
			$active = absint($qv['active']);
			$this->query_where .= " AND ".USAM_TABLE_STORAGES.".active=$active";		
		}
		if ( isset($qv['issuing']) ) 
		{
			$issuing = absint($qv['issuing']);
			$this->query_where .= " AND ".USAM_TABLE_STORAGES.".issuing=$issuing";		
		}
		if ( isset($qv['shipping']) ) 
		{
			$shipping = absint($qv['shipping']);
			$this->query_where .= " AND ".USAM_TABLE_STORAGES.".shipping=$shipping";		
		}
		if ( isset($qv['product_id']) ) 
		{
			$ids = implode(',', (array)$qv['product_id'] );
			$this->query_join .=  " INNER JOIN ".USAM_TABLE_STOCK_BALANCES." ON (".USAM_TABLE_STORAGES.".id = REPLACE(".USAM_TABLE_STOCK_BALANCES.".meta_key, 'storage_', ''))";	
			$this->query_where .= " AND ".USAM_TABLE_STOCK_BALANCES.".product_id IN ($ids) AND ".USAM_TABLE_STOCK_BALANCES.".meta_value>0";
		}
		if( isset($qv['location_id']) ) 
		{
			$location_ids = implode( ',', wp_parse_id_list( (array)$qv['location_id'] ) );
			$this->query_where .= " AND ".USAM_TABLE_STORAGES.".location_id IN ($location_ids)";
		}				
		if( !empty($qv['region']) ) 
		{
			$location = (int)$qv['region'];
			$locations = usam_get_address_locations( $location, 'code=>id' );		
			if ( isset($locations['region']) )
			{ 						
				$location_ids = usam_get_array_locations_down( $locations['region'] );	
				$location_ids = implode(',', $location_ids );
				$this->query_where .= " AND ".USAM_TABLE_STORAGES.".location_id IN ($location_ids)";
			}
		}		
		if ( isset($qv['code']) ) 
		{
			$code = implode( "','",  (array)$qv['code'] );
			$this->query_where .= " AND ".USAM_TABLE_STORAGES.".code IN ('$code')";
		}
		if ( isset($qv['owner']) && $qv['owner'] != 'all' ) 
		{
			$owner = implode("','", (array)$qv['owner'] );
			$this->query_where .= " AND ".USAM_TABLE_STORAGES.".owner IN ('$owner')";
		}	
		if ( isset($qv['type']) && $qv['type'] != 'all' ) 
		{
			$type = implode("','", (array)$qv['type'] );
			$this->query_where .= " AND ".USAM_TABLE_STORAGES.".type IN ('$type')";
		}		
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty($this->meta_query->queries) ) 
		{
			$clauses = $this->meta_query->get_sql( 'storage', USAM_TABLE_STORAGE_META, USAM_TABLE_STORAGES, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];
			if ( $this->meta_query->has_or_relation() )
				$distinct = true;
		}		
		$conditions_query = new USAM_Conditions_Query();
		$this->query_where .= $conditions_query->get_sql_clauses( $qv );		
		if ( $distinct ) 
			$this->query_fields = 'DISTINCT ' . $this->query_fields;			
		do_action_ref_array( 'usam_pre_storages_query', array( &$this ) );		
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
			wp_cache_set( $data->id, (array)$data, 'usam_storage' );
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
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_storages_query', 'SELECT FOUND_ROWS()' ) );	
											
		if ( $qv['cache_meta'] || $qv['images_cache'] )	
		{ 
			$ids = [];	
			foreach ( $this->results as $result ) 
			{				
				if( !empty($result->id) )
					$ids[] = $result->id; 
				elseif ( is_numeric($result) )
					$ids[] = $result; 
			}
			usam_update_cache( $ids, [USAM_TABLE_STORAGE_META => 'storage_meta'], 'storage_id' );
		}	
		if ( $qv['cache_location'] )		
		{ 
			$ids = array();	
			foreach ( $this->results as $result ) 
			{				
				if( !empty($result->location_id) )
					$ids[] = $result->location_id; 					
			}
			if ( $ids )
				usam_get_locations(['include' => $ids, 'cache_results' => true]);
		}
		if ( $qv['images_cache'] )		
		{ 
			$ids = array();	
			$storage_ids = array();	
			foreach ( $this->results as $result ) 
			{				
				if( !empty($result->id) )
				{
					$images = usam_get_storage_metadata( $result->id, 'images');
					$images = is_array($images) ? $images : [];
					$ids = array_merge($images, $ids);
					$storage_ids[$result->id] = $images;
				}
			}
			if ( $ids )
			{
				$attachments = (array)usam_get_posts(['post_type' => 'attachment', 'orderby' => 'menu_order', 'order' => 'ASC', 'update_post_meta_cache' => true, 'post__in' => $ids]);
				foreach ( $attachments as $k => $attachment ) 
				{
					unset($attachments[$k]->guid);
					unset($attachments[$k]->to_ping);
					unset($attachments[$k]->pinged);
					unset($attachments[$k]->ping_status);
					unset($attachments[$k]->comment_count);
					unset($attachments[$k]->post_password);
					unset($attachments[$k]->post_content_filtered);
					$attachments[$k]->full = wp_get_attachment_image_url($attachment->ID, 'full' );	
					$attachments[$k]->thumbnail = wp_get_attachment_image_url($attachment->ID, 'thumbnail' );
				}
				$cache = array();
				foreach( $storage_ids as $id => $images ) 
				{					
					if( empty($images) )
						$cache[$id][] = [];
					else
					{
						foreach( $attachments as $attachment ) 
							if( in_array($attachment->ID, $images) )
							$cache[$id][] = $attachment;
					}
				}		
				foreach( $cache as $id => $images ) 
				{
					wp_cache_set( $id, $images, 'usam_storage_images' );
				}
			}
		}		
		if( 'code=>data' == $qv['fields'] ) 
		{	
			foreach ($this->results as $key => $result ) 
			{					
				$r[$result->code] = $result;
				unset($this->results[$key]);
			}			
			$this->results = $r;			
		}
		elseif( 'id=>data' == $qv['fields'] ) 
		{	
			foreach ($this->results as $key => $result ) 
			{					
				$r[$result->id] = $result;
				unset($this->results[$key]);
			}			
			$this->results = $r;		 
		}	
		elseif( 'id=>title' == $qv['fields'] ) 
		{	
			foreach ($this->results as $key => $result ) 
			{					
				$r[$result->id] = $result->title;
				unset($this->results[$key]);
			}			
			$this->results = $r;		 
		}		
		elseif( 'code=>meta_key' == $qv['fields'] ) 
		{	
			foreach ($this->results as $key => $result ) 
			{					
				$r[$result->code] = 'storage_'.$result->id;					
				unset($this->results[$key]);
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
				$searches[] = $wpdb->prepare("$col = %s", $string);
			elseif ( 'branch_number' == $col )
			{
				$this->query_join .= " LEFT OUTER JOIN ".USAM_TABLE_STORAGE_META." AS {$col} ON ({$col}.storage_id = ".USAM_TABLE_STORAGES.".id AND {$col}.meta_key ='branch_number')";
				$searches[] = $wpdb->prepare($col.".meta_value='%s'", $string );
			}
			elseif ( 'address' == $col )
			{
				$this->query_join .= " LEFT OUTER JOIN ".USAM_TABLE_STORAGE_META." AS {$col} ON ({$col}.storage_id = ".USAM_TABLE_STORAGES.".id AND {$col}.meta_key ='address')";
				$searches[] = $wpdb->prepare("$col.meta_value LIKE LOWER ('%s')", "%".$wpdb->esc_like( $string )."%");
			}
			elseif ( isset($this->db_fields[$col]) )
				$searches[] = $wpdb->prepare("$col LIKE LOWER ('%s')", "%".$wpdb->esc_like( $string )."%");
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
			$_orderby = USAM_TABLE_STORAGES.'.'.$orderby;
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_STORAGES.".id, $include_sql )";
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

function usam_get_storages( $args = array() )
{ 	
	$r = [];
	foreach ( $args as $key => $value ) 
	{
		if( !isset($args['cache_results']) && !isset($args['cache_meta']) )
			$r[$key] = $value;
	}	
	if ( empty($r) )
	{
		$key_cache = 'usam_storages';
		$cache = wp_cache_get( $key_cache );			
		if ( $cache !== false )		
			return $cache;
	}	
	if ( !isset($args['active']) )
		$args['active'] = 1;
	
	if ( !isset($args['owner']) )
		$args['owner'] = '';
		
	$args['count_total'] = false;	
	$storages = new USAM_Storages_Query( $args );
	$results = $storages->get_results();
	if ( !empty($key_cache) )
		wp_cache_set( $key_cache, $results );	
	
	return $results;
}