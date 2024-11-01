<?php
// Класс работы с сообщениями чата пользователей

class USAM_Chat_Messages_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];	
	private $db_fields = ['id', 'guid', 'dialog_id', 'contact_id' , 'message', 'type_message', 'date_insert'];
	
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
			'fields' => 'all',
			'add_fields' => '',
			'cache_results' => false			
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
		do_action( 'usam_pre_get_chat_messages', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );
				
		$dialog_join = false;		
		$join_statuses = false;
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
				$this->fields[] = USAM_TABLE_CHAT.".*";	
			elseif ( $field == 'status' )
			{
				$join_statuses = true;
				$this->fields[] = 'IFNULL(statuses.status,1) AS status';
			}
			elseif ( $field == 'count' )				
				$this->fields[] = "COUNT(*) AS count";		
			elseif ( in_array($field, $this->db_fields) )
				$this->fields[] = USAM_TABLE_CHAT.".$field";
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_CHAT.".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_CHAT;
		$this->query_where = "WHERE 1=1";		
		
	// Обрабатывать другие параметры даты
		$date_parameters = array();
		foreach ( ['second', 'minute', 'hour', 'day', 'w', 'monthnum', 'year', 'column'] as $k ) 
		{		
			if ( isset($qv[$k]) && '' !== $qv[$k] )
				$date_parameters[$k] = $qv[$k];
		}
		if ( $date_parameters ) 
		{
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_CHAT );
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_CHAT );
			$this->query_where .= $this->date_query->get_sql();
		}	
		if ( !empty($qv['include']) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;
		if ( !empty($qv['status']) ) 
		{
			if ( is_array($qv['status']) ) 
				$status = $qv['status'];			
			elseif ( is_string($qv['status']) && !empty($qv['status']) )
			{
				if ( $qv['status'] != 'all' )
					$status = array_map( 'trim', explode( ',', $qv['status'] ) );
			}				
			if ( !empty($status) ) 
			{
				$join_statuses = false;
				$this->query_where .= " AND status IN ('".implode( "','", $status )."')";	
			}
		}	
		if ( isset($qv['status__not_in']) ) 
		{
			$status__not_in = (array) $qv['status__not_in'];
			$join_statuses = false;
			$this->query_where .= " AND status NOT IN ('".implode( "','", $status__not_in )."')";	
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
			if ( ! $_value )
				continue;
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

			if ( ! $parsed )
				continue;
			$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
		}
		// If no valid clauses were found, order by id.
		if ( empty($orderby_array) ) {
			$orderby_array[] = USAM_TABLE_CHAT.".date_insert $order";
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
				$search_columns = array_intersect( $qv['search_columns'], array( 'id', 'message' ) );
			if ( ! $search_columns ) 
			{
				if ( false !== strpos( $search, '@') )
					$search_columns = array('phone');
				elseif ( is_numeric($search) )
					$search_columns = array('id', 'user_login' );
				elseif ( preg_match('|^https?://|', $search) && ! ( is_multisite() && wp_is_large_network( 'users' ) ) )
					$search_columns = array('user_url');
				else 
					$search_columns = array('id', 'message' );
			}	
			$search_columns = apply_filters( 'usam_chat_messages_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}
		if ( !empty( $include ) ) 
		{
			// Sanitized earlier.
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_CHAT.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_CHAT.".id NOT IN ($ids)";
		}   
		if ( isset($qv['from_id']) ) 
		{
			$id = absint($qv['from_id']);
			$this->query_where .= " AND ".USAM_TABLE_CHAT.".id < $id";	
		} 
		if ( isset($qv['to_id']) ) 
		{
			$id = absint($qv['to_id']);
			$this->query_where .= " AND ".USAM_TABLE_CHAT.".id > $id";
		}		
		if ( !empty($qv['contact_id']) ) 
		{		
			$ids = implode( ',', wp_parse_id_list( $qv['contact_id'] ) );
			$this->query_where .= " AND ".USAM_TABLE_CHAT.".contact_id IN ($ids)";					
		}	
		if ( !empty($qv['contact_id__not_in']) )
		{
			$ids = implode(',', wp_parse_id_list( $qv['contact_id__not_in'] ) );		
			$this->query_where .= " AND ".USAM_TABLE_CHAT.".contact_id NOT IN (".$ids.")";
		}
		if ( !empty($qv['dialog_id']) ) 
		{		
			$dialog_id = implode( ',', (array)$qv['dialog_id'] );		
			$this->query_where .= " AND ".USAM_TABLE_CHAT.".dialog_id IN ($dialog_id)";
		}	
		if ( !empty($qv['channel']) ) 
		{		
			$channel = implode( "','", (array)$qv['channel'] );		
			$this->query_where .= " AND dialog.channel IN ('$channel')";
			$dialog_join = true;
		}
		if ( !empty($qv['guid']) ) 
		{		
			$guid = implode( "','",  (array)$qv['guid'] );		
			$this->query_where .= " AND ".USAM_TABLE_CHAT.".guid IN ('$guid')";
		}	
		if ( $dialog_join )
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CHAT_DIALOGS." dialog ON (dialog.id=".USAM_TABLE_CHAT.".dialog_id)";	
		if ( $join_statuses ) 
		{
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_CHAT_MESSAGE_STATUSES." AS statuses ON (statuses.message_id = ".USAM_TABLE_CHAT.".id)";
			if ( strripos($this->query_fields, 'DISTINCT') === false )
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
		}
		do_action_ref_array( 'usam_pre_chat_messages_query', array( &$this ) );
	}
	
	public function handling_data_types( $data )
	{
		foreach (['id', 'contact_id', 'dialog_id'] as $column ) 
		{			
			if ( isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_chat_messages' );	
		return $data;
	}
	
	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;

		$this->request = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";	
		$count_fields = count($this->fields);
		if ( $count_fields > 1 || $qv['fields'] == 'all' && $qv['number'] != 1 ) 
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
			$this->total = (int)$wpdb->get_var( 'SELECT FOUND_ROWS()' );		
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

		foreach ( $cols as $col ) {
			if ( 'ID' == $col ) {
				$searches[] = $wpdb->prepare( "$col = %s", $string );
			} else {
				$searches[] = $wpdb->prepare( "$col LIKE %s", $like );
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
		if ( in_array($orderby, $this->db_fields) )
			$_orderby = USAM_TABLE_CHAT.'.'.$orderby;
		elseif ( 'date' == $orderby ) 
			$_orderby = USAM_TABLE_CHAT.'date_insert';
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) ) 
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_CHAT.".id, $include_sql )";
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

function usam_get_chat_messages( $query )
{	
	$query['count_total'] = false;
	$chat_messages = new USAM_Chat_Messages_Query( $query );	
	return $chat_messages->get_results();	
}