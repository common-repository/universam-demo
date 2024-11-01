<?php
// Класс работы с диалогами пользователей

class USAM_Chat_Dialogs_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];	
	private $db_fields = ['id' => 'd', 'channel' => 's', 'channel_id' => 'd', 'manager_id' => 'd', 'date_insert' => 's'];
	private $join_contacts = false;

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
			'status' => '',
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
			'cache_results' => false,			
			'cache_contacts' => false,
			'cache_statuses' => false,				
			'fields' => 'all',	
			'add_fields' => ''			
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
		do_action( 'usam_pre_get_chat_dialogs', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );
				
		$join_end_message = false;
		$join = array();	
		$this->query_where = "WHERE 1=1";	
		
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
				$this->fields[] = USAM_TABLE_CHAT_DIALOGS.".*";
			elseif ( $field == 'count' )
				$this->fields[] = "COUNT(*) AS count";	
			elseif ( $field == 'all' )
				$this->fields[] = USAM_TABLE_CHAT_DIALOGS.".*";						
			elseif ( $field == 'end_message' )	
			{				
				$join_end_message = true;
				$this->fields[] = "c1.message AS end_message";
				$this->fields[] = "statuses.status AS end_status";
				$this->fields[] = "c1.date_insert AS end_date";
				$this->fields[] = "c1.contact_id AS contact_message";
				$this->fields[] = USAM_TABLE_CHAT_USERS.".not_read AS not_read";
			}
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_CHAT_DIALOGS.".$field";
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_CHAT_DIALOGS.".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;			
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_CHAT_DIALOGS;			
		
	// Обрабатывать другие параметры даты
		$date_parameters = array();
		foreach ( ['second', 'minute', 'hour', 'day', 'w', 'monthnum', 'year', 'column'] as $k ) 
		{		
			if ( isset($qv[$k]) && '' !== $qv[$k] )
				$date_parameters[$k] = $qv[$k];
		}
		if ( $date_parameters ) 
		{
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_CHAT_DIALOGS );
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_CHAT_DIALOGS );
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
					case 'end_date' :
						$ordersby_array[] = $_value;
						$join_end_message = true;						
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

		if ( empty($qv['orderby']) ) 
		{	// Default order is by 'id'.			
			$ordersby = ['date_insert' => $order];
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
			{
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
			$orderby_array[] = USAM_TABLE_CHAT_DIALOGS.".date_insert $order";
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

		if ( $search ) {
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
				$search_columns = array_intersect( $qv['search_columns'], ['id', 'message', 'user_login']);
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = ['id', 'user_login'];			
				else 
					$search_columns = ['id', 'message'];
			}	
			$search_columns = apply_filters( 'usam_chat_dialogs_search_columns', $search_columns, $search, $this );
			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}
		if ( !empty($qv['include']) )	
		{
			$include = wp_parse_id_list( $qv['include'] );
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_CHAT_DIALOGS.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_CHAT_DIALOGS.".id NOT IN ($ids)";
		} 			
		if ( !empty($qv['user']) ) 
		{		
			$users = implode( ',',  (array)$qv['user'] );	
			$this->query_where .= " AND ".USAM_TABLE_CHAT_DIALOGS.".contact_id IN ($users)";
		}	
		if ( !empty($qv['manager']) ) 
		{		
			$managers = implode( ',',  (array)$qv['manager'] );	
			$this->query_where .= " AND ".USAM_TABLE_CHAT_DIALOGS.".manager_id IN ($managers)";
		}	
		if ( !empty($qv['user_to']) ) 
		{
			$this->join_contacts = true;
			$this->query_fields .= ', user_to.contact_id';
			$this->query_where .= " AND user_to.contact_id IN (".implode( ',', wp_parse_id_list((array)$qv['user_to'] )).")";
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CHAT_USERS." AS user_to ON (user_to.dialog_id = ".USAM_TABLE_CHAT_DIALOGS.".id)"; 
		}
		if ( !empty($qv['channel']) ) 
		{		
			$channel = implode( "','",  (array)$qv['channel'] );		
			$this->query_where .= " AND ".USAM_TABLE_CHAT_DIALOGS.".channel IN ('$channel')";
		}	
		if ( !empty($qv['channel_id']) ) 
		{		
			$channel_id = implode( ',',  wp_parse_id_list( (array)$qv['channel_id'] ) );		
			$this->query_where .= " AND ".USAM_TABLE_CHAT_DIALOGS.".channel_id IN ($channel_id)";
		}	
		if ( !empty($qv['unanswered']) ) 
		{
			$this->join_contacts = true;
			$this->query_where .= " AND users.not_read>0";
		}
		if ( $join_end_message ) 
		{
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_CHAT." AS c1 ON (c1.dialog_id=".USAM_TABLE_CHAT_DIALOGS.".id AND c1.id = (SELECT id FROM ".USAM_TABLE_CHAT." WHERE dialog_id=".USAM_TABLE_CHAT_DIALOGS.".id ORDER BY id DESC LIMIT 1))";
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CHAT_MESSAGE_STATUSES." AS statuses ON (statuses.message_id = c1.id AND statuses.contact_id=c1.contact_id)";
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CHAT_USERS." ON (".USAM_TABLE_CHAT_USERS.".dialog_id = ".USAM_TABLE_CHAT_DIALOGS.".id AND statuses.contact_id=".USAM_TABLE_CHAT_USERS.".contact_id )";
		}
		if ( $this->join_contacts ) 
		{
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CHAT_USERS." AS users ON (users.dialog_id = ".USAM_TABLE_CHAT_DIALOGS.".id)";
			if ( strripos($this->query_fields, 'DISTINCT') === false )
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
		}		
		do_action_ref_array( 'usam_pre_chat_dialogs_query', array( &$this ) );		
	}	
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( $type == 'd' && isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_chat_dialogs' );	
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
			$this->results = $wpdb->get_col( $this->request );
		if ( !$this->results )
			return;	
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->total = (int)$wpdb->get_var( 'SELECT FOUND_ROWS()' );		
	
		if ( $qv['cache_contacts'] )		
		{																
			$contact_ids = array();
			foreach ( $this->results as $result ) 
			{
				if ( !empty($result->contact_id) )
					$contact_ids[] = $result->contact_id; 				
			}			
			if ( !empty($contact_ids) )
			{
				$contact_ids = array_unique( $contact_ids );					
				$contacts = usam_get_contacts(['include' => $contact_ids, 'cache_results' => true, 'cache_meta' => true, 'cache_thumbnail' => true, 'manager_id' => 'all']);	
			}				
		}	
		if ( $qv['cache_statuses'] )		
		{		
	
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
				$searches[] = USAM_TABLE_CHAT_DIALOGS.'.'.$wpdb->prepare( "$col = %s", $string );		
			elseif ( 'user_login' == $col ) 
			{
				$user = get_user_by('login', $string );				
				if ( !empty($user->ID) )
				{
					$contact = usam_get_contact($user->ID, 'user_id' );
					if ( !empty($contact->id) )
					{
						$this->join_contacts = true;
						$searches[] = "users.contact_id ={$contact->id}";
					}
				}
			}				
			elseif ( 'message' == $col ) 
			{				
				$like = "%" . $wpdb->esc_like( $string ) . "%";
				$searches[] = $wpdb->prepare( USAM_TABLE_CHAT_DIALOGS.".id IN ( SELECT DISTINCT dialog_id FROM ".USAM_TABLE_CHAT." WHERE message LIKE LOWER ('{$like}'))", $like );	
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

	/**
	 * Разобрать и очистить ключи 'orderby'
	 */
	protected function parse_orderby( $orderby ) 
	{
		global $wpdb;

		$_orderby = '';			
		if ( isset($this->db_fields[$orderby]) )
			$_orderby = USAM_TABLE_CHAT_DIALOGS.'.'.$orderby;
		elseif ( 'date' == $orderby ) 
			$_orderby = USAM_TABLE_CHAT_DIALOGS.'.date_insert';
		elseif ( 'manager' == $orderby ) 
			$_orderby = USAM_TABLE_CHAT_DIALOGS.'.manager_id';			
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) ) 
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_CHAT_DIALOGS.".id, $include_sql )";
		} 
		elseif ( 'end_date' === $orderby ) 
			$_orderby = "c1.date_insert";
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

function usam_get_chat_dialogs( $query )
{	
	$query['count_total'] = false; 
	$chat_dialogs = new USAM_Chat_Dialogs_Query( $query );	
	return $chat_dialogs->get_results();	
}