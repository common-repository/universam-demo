<?php
// Класс работы с обращениями посетителей
class USAM_Contactings_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $meta_query = false;
	public $object_query = false;	
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'manager_id' => 'd', 'contact_id' => 'd', 'date_insert' => 's', 'post_id' => 'd', 'date_completion' => 's', 'importance' => 'd', 'status' => 's'];

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
			'id'  => '',	
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
			'cache_results' => false,	
			'cache_comments' => false,	
			'cache_contacts' => false,
			'cache_last_comment_contacts' => false,			
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
		do_action( 'usam_pre_get_contactings', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		
		$group_join = false;
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
				$this->fields[] = USAM_TABLE_CONTACTINGS.".*";	
			elseif ( $field == 'count' )				
				$this->fields[] = "COUNT(*) AS count";		
			elseif ( $field == 'group_id' )
			{
				$group_join = true;
				$this->fields[] = USAM_TABLE_GROUP_RELATIONSHIPS.".group_id";
			}
			elseif ( $field == 'last_comment' )	
			{
				$this->fields[] = "comment.message AS last_comment";	
				$this->fields[] = "comment.user_id AS last_comment_user";	
				$this->fields[] = "comment.date_insert AS last_comment_date";	
				$join[] = " LEFT JOIN ".USAM_TABLE_COMMENTS." AS comment ON (comment.object_id=".USAM_TABLE_CONTACTINGS.".id AND comment.object_type='contacting' AND comment.id = (SELECT id FROM ".USAM_TABLE_COMMENTS." WHERE object_type='contacting' AND status!=1 AND object_id=".USAM_TABLE_CONTACTINGS.".id ORDER BY id DESC LIMIT 1 ))";
			}
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_CONTACTINGS.".$field";
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_CONTACTINGS.".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_CONTACTINGS;
		$this->query_where = "WHERE 1=1";		
		$distinct = false;
	// Обрабатывать другие параметры даты
		$date_parameters = array();
		foreach ( ['second', 'minute', 'hour', 'day', 'w', 'monthnum', 'year', 'column'] as $k ) 
		{		
			if ( isset($qv[$k]) && '' !== $qv[$k] )
				$date_parameters[$k] = $qv[$k];
		}
		if ( $date_parameters ) 
		{
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_CONTACTINGS );
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_CONTACTINGS );
			$this->query_where .= $this->date_query->get_sql();
		}	
		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( !empty($qv['include'] ) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;
			
		if ( !empty($qv['status_type']) )
		{
			$this->query_join .= " INNER JOIN ".USAM_TABLE_OBJECT_STATUSES." ON (".USAM_TABLE_OBJECT_STATUSES.".internalname=".USAM_TABLE_CONTACTINGS.".status)";		
			if ( $qv['status_type'] == 'unclosed' )
				$this->query_where .= " AND ".USAM_TABLE_OBJECT_STATUSES.".close=0 AND ".USAM_TABLE_OBJECT_STATUSES.".type='contacting'";
			elseif ( $qv['status_type'] == 'closed' )
				$this->query_where .= " AND ".USAM_TABLE_OBJECT_STATUSES.".close=1 AND ".USAM_TABLE_OBJECT_STATUSES.".type='contacting'";
			$distinct = true;
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
				$this->query_where .= " AND ".USAM_TABLE_CONTACTINGS.".status IN ('".implode( "','", $status )."')";	
		}	
		if ( isset($qv['status__not_in']) ) 
		{
			$status__not_in = (array) $qv['status__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_CONTACTINGS.".status NOT IN ('".implode("','", $status__not_in )."')";
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
					case 'completion_day' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "DAY(CONVERT_TZ(date_completion,'UTC','$name_timezone')))";
							$ordersby_array[] = "MONTH(CONVERT_TZ(date_completion,'UTC','$name_timezone')))";
							$ordersby_array[] = "YEAR((CONVERT_TZ(date_completion,'UTC','$name_timezone')))";						
						}
						else
						{
							$ordersby_array[] = "DAY(date_completion)";
							$ordersby_array[] = "MONTH(date_completion)";
							$ordersby_array[] = "YEAR(date_completion)";								
						}
					break;
					case 'completion_week' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "WEEKOFYEAR(CONVERT_TZ(date_completion,'UTC','$name_timezone'))";
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_completion,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "WEEKOFYEAR(date_completion)";
							$ordersby_array[] = "YEAR(date_completion)";		
						}										
					break;	
					case 'completion_month' :					
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "MONTH(CONVERT_TZ(date_completion,'UTC','$name_timezone'))";
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_completion,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "MONTH(date_completion)";
							$ordersby_array[] = "YEAR(date_completion)";		
						}	
					break;					
					case 'completion_year' :
						if ( USAM_SQL_TIME_ZONE )
							$ordersby_array[] = "YEAR(CONVERT_TZ(date_completion,'UTC','$name_timezone'))";	
						else
							$ordersby_array[] = "YEAR(date_completion)";
					break;					
					case 'group' :					
						$group_join = true;
						$ordersby_array[] = USAM_TABLE_GROUP_RELATIONSHIPS.".group_id";
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
			$ordersby = $qv['orderby'];
		else 
		{
			// Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
		}
		$orderby_array = array();
		foreach ( $ordersby as $_key => $_value ) 
		{
			if ( ! $_value )
				continue;					
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
			if ( ! $parsed )
				continue;
			$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
		}

		// If no valid clauses were found, order by id.
		if ( empty( $orderby_array ) )
			$orderby_array[] = USAM_TABLE_CONTACTINGS.".id $order";
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
				$search_columns = array_intersect( $qv['search_columns'], ['id', 'user_login']);
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = ['id', 'user_login'];				
				else
					$search_columns = ['user_login'];
			}	
			$search_columns = apply_filters( 'usam_contactings_search_columns', $search_columns, $search, $this );
			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( !empty($include) ) 
		{	
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_CONTACTINGS.".id IN ($ids)";
		} 
		elseif( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_CONTACTINGS.".id NOT IN ($ids)";
		}		
		$group = array();
		if ( isset($qv['group'] ) ) 
		{
			if ( is_array( $qv['group'] ) ) 
				$group = $qv['group'];			
			elseif ( is_string( $qv['group'] ) && !empty( $qv['group'] ) )
				$group = array_map( 'trim', explode( ',', $qv['group'] ) );
		}
		$group__not_in = [];
		if ( isset($qv['group__not_in']) ) {
			$group__not_in = (array) $qv['group__not_in'];
		}				
		if ( !empty($group) ) 
		{				
			$group_join = true;
			$this->query_where .= " AND ".USAM_TABLE_GROUP_RELATIONSHIPS.".group_id IN (".implode(',',  $group ).")";		
		}		
		if ( !empty($group__not_in) ) 
		{			
			$group_join = true;
			$this->query_where .= " AND ".USAM_TABLE_GROUP_RELATIONSHIPS.".group_id NOT IN (".implode(',',  $group ).")";		
		}			
		if ( isset($qv['contacts']) ) 
		{		
			$contact_ids = implode( ',',  array_map( 'intval',(array)$qv['contacts']) );		
			$this->query_where .= " AND ".USAM_TABLE_CONTACTINGS.".contact_id IN ($contact_ids)";
		}
		if ( isset($qv['contacts__not_in']) ) 
		{			
			$contacts = implode( ',',  array_map( 'intval',(array)$qv['contacts__not_in']) );								
			$this->query_where .= " AND ".USAM_TABLE_CONTACTINGS.".contact_id NOT IN ($contacts)";
		}						
		if ( !empty($qv['manager_id']) ) 
		{
			if ( is_array( $qv['manager_id'] ) ) 
				$manager_id = $qv['manager_id'];			
			elseif ( is_string($qv['manager_id']) && !empty($qv['manager_id']) )
			{
				if ( $qv['manager_id'] != 'all' )
					$manager_id = array_map( 'intval', explode( ',', $qv['manager_id'] ) );
			}
			elseif ( is_numeric($qv['manager_id']) )
				$manager_id = array($qv['manager_id']);
				
			if ( !empty($manager_id) ) 
				$this->query_where .= " AND ".USAM_TABLE_CONTACTINGS.".manager_id IN (".implode( ",", $manager_id ).")";	
		}	
		if ( isset($qv['manager_id__not_in']) ) 
		{
			$manager_id__not_in = (array)$qv['manager_id__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_CONTACTINGS.".manager_id NOT IN (".implode(",", $manager_id__not_in ).")";
		}	
		if ( !empty($qv['links_query']) ) 
		{
			$this->query_join .= " INNER JOIN ".USAM_TABLE_RIBBON." ON (".USAM_TABLE_RIBBON.".event_id=".USAM_TABLE_CONTACTINGS.".id)";			
			$this->object_query = new USAM_Object_Query( $qv['links_query'] );	
			$clauses = $this->object_query->get_sql( USAM_TABLE_RIBBON, USAM_TABLE_RIBBON_LINKS, 'id', 'ribbon_id' );
			
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];
		}						
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty( $this->meta_query->queries ) ) 
		{
			$clauses = $this->meta_query->get_sql( 'contacting', USAM_TABLE_CONTACTING_META, USAM_TABLE_CONTACTINGS, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where']; 
			if ( $this->meta_query->has_or_relation() )
				$distinct = true;
		}		
		if ( $distinct ) 
			$this->query_fields = 'DISTINCT ' . $this->query_fields;			
		
		if ( $group_join  ) 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_GROUP_RELATIONSHIPS." ON (".USAM_TABLE_GROUP_RELATIONSHIPS.".object_id = ".USAM_TABLE_CONTACTINGS.".id)";
		do_action_ref_array( 'usam_pre_contactings_query', array( &$this ) );
	}
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( $type == 'd' && isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_contacting' );	
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
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_tasks_query', 'SELECT FOUND_ROWS()' ) );	
				
		if ( $qv['cache_meta'] || $qv['cache_comments'] || $qv['cache_contacts'] || $qv['cache_last_comment_contacts'] )
		{
			$ids = [];	
			foreach ( $this->results as $result ) 
			{
				if ( !empty($result->id) )
					$ids[] = $result->id; 					
			}				
			if ( $qv['cache_meta'] )
				usam_update_cache( $ids, [USAM_TABLE_CONTACTING_META => 'contacting_meta'], 'contacting_id' );					
			if ( $qv['cache_last_comment_contacts'] )		
			{
				$user_ids = [];										
				foreach ( $this->results as $result ) 
				{
					if ( !empty($result->user_id) )
						$user_ids[] = $result->user_id; 					
				}			
				if ( !empty($user_ids) )
				{
					$user_ids = array_unique( $user_ids );				
					$contacts = usam_get_contacts(['user_id' => $user_ids, 'cache_results' => true, 'cache_thumbnail' => true, 'manager_id' => 'all']);	
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
				$searches[] = USAM_TABLE_CONTACTINGS.".".$wpdb->prepare( "$col = %s", $string );
			} 					
			elseif ( 'user_login' == $col ) 
			{
				$user = get_user_by('login', $string );
				if ( !empty($user->ID) )
					$searches[] = USAM_TABLE_CONTACTINGS.".user_id ={$user->ID}";
			}		
			else 
			{
				$searches[] = USAM_TABLE_CONTACTINGS.".".$wpdb->prepare( "$col LIKE %s", $like );
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

	protected function parse_orderby( $orderby ) 
	{
		$_orderby = '';
		if ( isset($this->db_fields[$orderby]) )
			$_orderby = USAM_TABLE_CONTACTINGS.'.'.$orderby;
		elseif ( 'comment_count' == $orderby ) 
		{
			$this->query_join .= "LEFT JOIN ".USAM_TABLE_COMMENTS." AS comment ON (comment.object_id=".USAM_TABLE_CONTACTINGS.".id AND comment.object_type ='contacting' AND comment.status!=2)";
			$_orderby = 'IF(comment.id>0,1,0) DESC, COUNT(comment.id)';
			
			if ( empty($this->query_groupby) )
				$this->query_groupby = 'GROUP BY '.USAM_TABLE_CONTACTINGS.'.id';
			else
				$this->query_groupby .= ','.USAM_TABLE_CONTACTINGS.'.id';		
		} 
		elseif ( 'count' == $orderby ) 
			$_orderby = 'COUNT(id)';
		elseif ( 'comment_date' == $orderby ) 
		{
			$this->query_join .= "LEFT JOIN ".USAM_TABLE_COMMENTS." AS comment ON (comment.object_id=".USAM_TABLE_CONTACTINGS.".id AND comment.object_type ='contacting' AND comment.status!=2)";			
			$_orderby = 'COALESCE(MAX(comment.date_insert), '.USAM_TABLE_CONTACTINGS.'.date_insert)';			
			if ( empty($this->query_groupby) )
				$this->query_groupby = 'GROUP BY '.USAM_TABLE_CONTACTINGS.'.id';
			else
				$this->query_groupby .= ','.USAM_TABLE_CONTACTINGS.'.id';		
		} 		
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_CONTACTINGS.".id, $include_sql )";
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

function usam_get_contactings( $args = array() )
{ 
	$args['count_total'] = false;	
	$class = new USAM_Contactings_Query( $args );	
	$result = $class->get_results();		
	return $result;
}