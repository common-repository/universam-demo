<?php
// Класс работы с письмами
class USAM_Email_Query 
{
	public $query_vars = [];
	public $object_query = [];	
	private $results;
	private $total = 0;
	public $meta_query = false;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'from_email' => 's', 'from_name' => 's', 'to_email' => 's', 'to_name' => 's', 'title' => 's', 'body' => 's', 'date_insert' => 's', 'sent_at' => 's', 'read' => 'd', 'folder' => 's', 'importance' => 'd', 'mailbox_id' => 'd', 'type' => 's', 'user_id' => 'd'];
	
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
		require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
		require_once( USAM_FILE_PATH . '/includes/query/objects_query.class.php' );
		require_once( USAM_FILE_PATH . '/includes/query/date.php' );
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
			'from_email'  => '',		
			'date_insert'  => '',
			'sent_at' => '',		
			'folder' => '',			
			'from_email__in' => array(),
			'from_email__not_in' => array(),			
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
			'cache_attachments' => false,		
			'cache_object' => false,		
			'cache_communication_errors' => false,	
			'add_fields' => '',				
			'fields' => 'all',			
		);		
		return wp_parse_args( $args, $defaults );
	}

	public function prepare_query( $query = array() ) 
	{
		global $wpdb;

		if ( empty($this->query_vars) || !empty($query) ) {
			$this->query_limit = null;
			$this->query_vars = $this->fill_query_vars( $query );
		}			
		do_action( 'usam_pre_get_emails', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		
		$join = array();			
		$file_join = false;
		$file_left_join = false;
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
				$this->fields[] = USAM_TABLE_EMAIL.".*";
			elseif ( $field == 'count' )
				$this->fields[] = "COUNT(*) AS count";	
			elseif ( $field == 'size' )
			{
				$this->fields[] = "file.size";			
				$file_left_join = true;
			}
			elseif ( $field == 'subject' )
				$this->query_fields[] = "IF(".USAM_TABLE_EMAIL.".title='',".USAM_TABLE_EMAIL.".body, ".USAM_TABLE_EMAIL.".title)";	
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_EMAIL.".$field";			
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_EMAIL.".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );

		$this->query_from = "FROM ".USAM_TABLE_EMAIL;
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
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_EMAIL );
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_EMAIL );
			$this->query_where .= $this->date_query->get_sql();
		}				
		$from_email = array();
		if ( isset($qv['from_email'] ) ) 
		{
			if ( is_array( $qv['from_email'] ) ) 
				$from_email = $qv['from_email'];			
			elseif ( is_string( $qv['from_email'] ) && !empty( $qv['from_email'] ) )
				$from_email = array_map( 'trim', explode( ',', $qv['from_email'] ) );
			elseif ( is_numeric( $qv['from_email'] ) )
				$from_email = $qv['from_email'];
		}
		$from_email_in = array();
		if ( isset($qv['from_email_in'] ) ) {
			$from_email_in = (array) $qv['from_email_in'];
		}
		$from_email_not_in = array();
		if ( isset($qv['from_email_not_in'] ) ) {
			$from_email_not_in = (array) $qv['from_email_not_in'];
		}
		if ( !empty( $from_email ) ) 
		{
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".from_email IN ('".implode( "','",  $from_email )."')";		
		}		
		if ( !empty($from_email_not_in) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".from_email NOT IN ('".implode( "','",  $from_email_not_in )."')";
		}
		if ( !empty( $from_email_in ) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".from_email IN ('".implode( "','",  $from_email_in )."')";
		}
		
		$folder = array();
		if ( isset($qv['folder'] ) ) 
		{
			if ( is_array( $qv['folder'] ) ) 
				$folder = $qv['folder'];			
			elseif ( is_string( $qv['folder'] ) && !empty( $qv['folder'] ) )
				$folder = array_map( 'trim', explode( ',', $qv['folder'] ) );
			elseif ( is_numeric( $qv['folder'] ) )
				$folder = $qv['folder'];
		}		
		$folder_not_in = array();
		if ( isset($qv['folder_not_in'] ) ) {
			$folder_not_in = (array) $qv['folder_not_in'];
		}
		if ( !empty($folder) ) 
		{
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".folder IN ('".implode( "','",  $folder )."')";		
		}		
		if ( !empty($folder_not_in) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".folder NOT IN ('".implode( "','",  $folder_not_in )."')";
		}		
		if ( !empty($qv['sent_at']) && $qv['sent_at'] == 'yes' ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".sent_at IS NOT NULL";
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
							$ordersby_array[] = "DAY(CONVERT_TZ(sent_at,'UTC','$name_timezone')))";
							$ordersby_array[] = "MONTH(CONVERT_TZ(sent_at,'UTC','$name_timezone')))";
							$ordersby_array[] = "YEAR((CONVERT_TZ(sent_at,'UTC','$name_timezone')))";						
						}
						else
						{
							$ordersby_array[] = "DAY(sent_at)";
							$ordersby_array[] = "MONTH(sent_at)";
							$ordersby_array[] = "YEAR(sent_at)";								
						}
					break;
					case 'week' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "WEEKOFYEAR(CONVERT_TZ(sent_at,'UTC','$name_timezone'))";
							$ordersby_array[] = "YEAR(CONVERT_TZ(sent_at,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "WEEKOFYEAR(sent_at)";
							$ordersby_array[] = "YEAR(sent_at)";		
						}										
					break;	
					case 'month' :					
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "MONTH(CONVERT_TZ(sent_at,'UTC','$name_timezone'))";
							$ordersby_array[] = "YEAR(CONVERT_TZ(sent_at,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "MONTH(sent_at)";
							$ordersby_array[] = "YEAR(sent_at)";		
						}	
					break;					
					case 'year' :
						if ( USAM_SQL_TIME_ZONE )
						{
							$ordersby_array[] = "YEAR(CONVERT_TZ(sent_at,'UTC','$name_timezone'))";						
						}
						else
						{
							$ordersby_array[] = "YEAR(sent_at)";		
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
		elseif ( is_array($qv['orderby']) ) 
			$ordersby = $qv['orderby'];
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
			$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
		}

		// If no valid clauses were found, order by id.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = USAM_TABLE_EMAIL.".id $order";
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
				$search_columns = array_intersect( $qv['search_columns'], array( 'id', 'from_email', 'from_name', 'to_email', 'to_name', 'title', 'body' ) );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array('id' );				
				elseif ( is_email($search) )
					$search_columns = array( 'from_email', 'to_email' );
				else
					$search_columns = array( 'from_name', 'to_name', 'title', 'body' );
			}	
			$search_columns = apply_filters( 'usam_email_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}
		if ( !empty($qv['include']) )			
		{	
			$ids = implode( ',', wp_parse_id_list( $qv['include'] ) );
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".id NOT IN ($ids)";
		}		
		if ( isset($qv['server_message_id']) ) 
		{	
			$ids = implode( ',', wp_parse_id_list($qv['server_message_id']) );
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".server_message_id IN ($ids)";
		} 	
		if ( isset($qv['type']) ) 
		{	
			$types = is_array($qv['type'])?$qv['type']:array( $qv['type'] );
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".type IN ('".implode( "','", $types)."')";
		} 	
		if ( isset($qv['read']) ) 
		{			
			$read = (int)$qv['read'];
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".read=$read";
		}					
		if ( isset($qv['mailbox_id']) ) 
		{					
			$ids = is_array($qv['mailbox_id'])?$qv['mailbox_id']:array($qv['mailbox_id']);
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".mailbox_id IN (".implode( ',',$ids).")";
		}
		elseif ( isset($qv['mailbox']) ) 
		{
			if ( $qv['mailbox'] === 'user' || $qv['mailbox'] === 0 )
			{
				$mailboxes = usam_get_mailboxes(['fields' => 'id', 'user_id' => get_current_user_id()]);
				if ( empty($mailboxes) )
					$ids = 0;
				else
					$ids = implode( ',', $mailboxes );
				$this->query_where .= " AND ".USAM_TABLE_EMAIL.".mailbox_id IN ($ids)";
			}
			elseif ( $qv['mailbox'] !== 'all' ) 
			{ 								
				$mailboxes = usam_get_mailboxes(['fields' => 'id', 'user_id' => get_current_user_id()]);
				$ids = array_intersect ( $mailboxes, wp_parse_id_list($qv['mailbox']));							
				if ( !empty($ids) )
					$this->query_where .= " AND ".USAM_TABLE_EMAIL.".mailbox_id IN (".implode( ',',$ids).")";
				else
					$this->query_where .= " AND ".USAM_TABLE_EMAIL.".mailbox_id=0";
			}			
		}		
		if ( !empty($qv['user_id']) ) 
		{
			if ( is_array($qv['user_id']) ) 
				$user_id = $qv['user_id'];			
			elseif ( is_string($qv['user_id']) && !empty($qv['user_id']) )
			{
				if ( $qv['user_id'] != 'all' )
					$user_id = array_map( 'trim', explode( ',', $qv['user_id'] ) );
			}
			elseif ( is_numeric($qv['user_id']) )
				$user_id = array($qv['user_id']);
				
			if ( !empty($user_id) ) 
				$this->query_where .= " AND ".USAM_TABLE_EMAIL.".user_id IN (".implode( ",", $user_id ).")";	
		}	
		if ( isset($qv['user_id__not_in']) ) 
		{
			$user_id__not_in = (array) $qv['user_id__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".user_id NOT IN (".implode(",", $user_id__not_in ).")";
		}
		if ( isset($qv['emails']) ) 
		{					
			$emails = is_array($qv['emails'])?$qv['emails']:array($qv['emails']);
			$in_emails = implode( "','", $emails );
			$this->query_where .= " AND (".USAM_TABLE_EMAIL.".from_email IN ('".$in_emails."') OR ".USAM_TABLE_EMAIL.".to_email IN ('".$in_emails."') )";
		}
		if ( isset($qv['contacts']) ) 
		{					
			$contacts = is_array($qv['contacts'])?$qv['contacts']:array($qv['contacts']);
			$this->query_join .= " INNER JOIN ".USAM_TABLE_CONTACT_META." ON (".USAM_TABLE_CONTACT_META.".meta_value = from_email OR ".USAM_TABLE_CONTACT_META.".meta_value = to_email)";
			$this->query_where .= " AND (".USAM_TABLE_CONTACT_META.".contact_id IN (".implode( ",", $contacts ).") )";
		}	
		if ( isset($qv['companies']) ) 
		{					
			$companies = is_array($qv['companies'])?$qv['companies']:array($qv['companies']);
			$this->query_join .= " INNER JOIN ".USAM_TABLE_COMPANY_META." ON (".USAM_TABLE_COMPANY_META.".meta_value = from_email OR ".USAM_TABLE_COMPANY_META.".meta_value = to_email)";
			$this->query_where .= " AND (".USAM_TABLE_COMPANY_META.".company_id IN (".implode( ",", $companies ).") )";
		}
		if ( isset($qv['from_name']) ) 
		{					
			$from_name = is_array($qv['from_name'])?$qv['from_name']:array($qv['from_name']);
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".from_name IN ('".implode( "','", $from_name )."')";
		}
		if ( isset($qv['importance']) ) 
		{		
			$importance = (int)$qv['importance'];
			$this->query_where .= " AND ".USAM_TABLE_EMAIL.".importance='$importance'";
		}	
		if ( !empty($qv['conditions']) ) 
		{		
			$conditions = isset($qv['conditions']['key'])?array($qv['conditions']):$qv['conditions'];	
			foreach ( $conditions as $condition )
			{						
				switch ( $condition['key'] )
				{
					case 'size' :
						$file_join = true;
						$select = "file.size";	
					break;					
					default :
						$select = $condition['key'];			
					break;					
				}
				if ( $select == '' )
					continue;
								
				if ( ! in_array( $condition['compare'], array(
					'=', '!=', '>', '>=', '<', '<=',
					'LIKE', 'NOT LIKE',
					'IN', 'NOT IN',
					'BETWEEN', 'NOT BETWEEN',
					'EXISTS', 'NOT EXISTS',
					'REGEXP', 'NOT REGEXP', 'RLIKE'
				) ) ) 
				{
					$condition['compare'] = '=';
				}				
				$compare = $condition['compare'];	
				switch ( $condition['compare'] ) 
				{					
					case 'LIKE' :					
					case 'NOT LIKE' :
						$value = "('%".$condition['value']."%')";						
					break;	
					case 'IN' :					
					case 'NOT IN' :
						$value = "('".implode("','",$condition['value'])."')";
					break;
					default:						
						$value = "'".$condition['value']."'";						
					break;			
				}	
				if ( empty($condition['relation']) )
					$relation = 'AND';
				else
					$relation = $condition['relation'];
					
				$this->query_where .= " {$relation} ( {$select}{$compare}{$value} ) ";
			}			
		}							
		if ( $file_join ) 
			$this->query_join .= " INNER JOIN ( SELECT SUM(size) AS size, object_id FROM ".USAM_TABLE_FILES." WHERE type IN ('email', 'R') GROUP BY object_id) AS file ON (".USAM_TABLE_EMAIL.".id = file.object_id )";	
		elseif ( in_array('size', $ordersby) || $file_left_join )
			$this->query_join .= " LEFT JOIN ( SELECT SUM(size) AS size, object_id FROM ".USAM_TABLE_FILES." WHERE type IN ('email', 'R') GROUP BY object_id) AS file ON (".USAM_TABLE_EMAIL.".id = file.object_id )";	
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty($this->meta_query->queries) ) 
		{
			$clauses = $this->meta_query->get_sql( 'email', USAM_TABLE_EMAIL_META, USAM_TABLE_EMAIL, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( $this->meta_query->has_or_relation() ) {
				$distinct = true;
			}
		}			
		if ( !empty($qv['object_query']) ) 
		{
			$this->object_query = new USAM_Object_Query( $qv['object_query'] );	
			$clauses = $this->object_query->get_sql( USAM_TABLE_EMAIL, USAM_TABLE_EMAIL_RELATIONSHIPS, 'id', 'email_id' );	
			
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where'];
		}
		do_action_ref_array( 'usam_pre_emails_query', array( &$this ) );
	}
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( $type == 'd' && isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_email' );	
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
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_emails_query', 'SELECT FOUND_ROWS()' ) );		
							
		if ( $qv['number'] == 1 )
			return false;
		
		if ( $qv['cache_attachments'] )
		{ 
			$ids = array();	
			foreach ( $this->results as $result ) 
			{
				if ( ! $cache = wp_cache_get( $result->id, 'usam_email_attachments' ) )
					$ids[] = $result->id; 					
			}	
			if ( !empty($ids) )
				usam_cache_attachments($ids, 'email');
		}
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
			usam_update_cache( $ids, [USAM_TABLE_EMAIL_META => 'email_meta'], 'email_id' );			
		}
		if ( $qv['cache_object'] )
		{
			$cache = usam_update_cache( $ids, [USAM_TABLE_EMAIL_RELATIONSHIPS => 'email_objects'], 'email_id' );	
			$order_ids = [];	
			$company_ids = [];	
			$contact_ids = [];	
			if ( !empty($cache) )
			{					
				foreach ( $ids as $id ) 
				{
					if ( !empty($cache['usam_email_objects'][$id]) )
						foreach ( $cache['usam_email_objects'][$id] as $objects ) 
						{
							foreach ( $objects as $object ) 
							{
								switch ( $object->object_type ) 
								{
									case 'order' :
										$order_ids[] = $object->object_id; 	
									break;
									case 'company' :
										$company_ids[] = $object->object_id; 	
									break;
									case 'contact' :
										$contact_ids[] = $object->object_id; 	
									break;
								}
							}
						}
				}	
			}				
			if ( !empty($order_ids) )
				usam_get_orders(['include' => $order_ids, 'cache_results' => true]);
			if ( !empty($company_ids) )
				usam_get_companies(['include' => $company_ids, 'cache_results' => true]);
			if ( !empty($contact_ids) )			
				usam_get_contacts(['include' => $contact_ids, 'cache_results' => true]);
		}		
		if ( $qv['cache_communication_errors'] )
		{
			$emails = array();
			foreach ( $this->results as $result ) 
			{
				$emails[$result->to_email] = $result->to_email;			
			}
			if ( !empty($emails) )
			{
				$errors = usam_get_communication_errors( array( 'communication' => $emails, 'status' => 0, 'communication_type' => 'email' ) );						
				$cache = array();
				foreach ( $errors as $error ) 
				{					
					$cache[$error->communication] = $error->reason;
				}	
				$cache_key = 'usam_check_communication_error_email';
				foreach ( $emails as $email ) 
				{					
					$reason = isset($cache[$email])?$cache[$email]:'';				
					wp_cache_set( $email, $reason, $cache_key );
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
				$searches[] = $wpdb->prepare( USAM_TABLE_EMAIL.".id=%d", $string );
			} 
			elseif ( $wild == false && ('from_name' == $col || 'to_name' == $col || 'reply_to_name' == $col || 'title' == $col || 'body' == $col) )
			{
				$searches[] = $wpdb->prepare( USAM_TABLE_EMAIL.".$col LIKE LOWER (%s)", "%".$wpdb->esc_like( $string )."%" );
			}
			else 
			{
				$searches[] = $wpdb->prepare( USAM_TABLE_EMAIL.".$col LIKE %s", $like );
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
			$_orderby = USAM_TABLE_EMAIL.'.'.$orderby;	
		elseif ( 'date' == $orderby ) 
			$_orderby = USAM_TABLE_EMAIL.'.date_insert';
		elseif ( 'size' == $orderby ) 
		{
			$_orderby = 'file.'.$orderby;
		} 	
		elseif ( 'read' == $orderby ) 
		{
			$_orderby = USAM_TABLE_EMAIL.'.read ASC, '.USAM_TABLE_EMAIL.'.sent_at';
		} 			
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_EMAIL.".id, $include_sql )";
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

function usam_get_emails( $args = [] )
{
	$args['count_total'] = false;
	$emails = new USAM_Email_Query( $args );	
	$result = $emails->get_results();		
	return $result;
}