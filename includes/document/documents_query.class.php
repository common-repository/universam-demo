<?php
// Класс работы с коммерческими предложениями, счетами
class USAM_Documents_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $request;
	public $meta_query = false;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'bank_account_id' => 'd', 'name' => 's', 'number' => 's', 'totalprice' => 'f', 'type_price' => 's', 'status' => 's', 'customer_id' => 'd', 'customer_type' => 's', 'manager_id' => 'd', 'date_insert' => 's', 'type' => 's', 'external_document' => 's'];

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
			'column' => '',
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
			'orderby' => 'id',
			'order' => 'ASC',
			'offset' => '',
			'number' => '',
			'paged' => 1,
			'count_total' => true,
			'fields' => 'all',	
			'add_fields' => '',				
			'cache_results' => false,	
			'cache_meta' => false,	
			'cache_bank_accounts' => false,		
			'cache_products' => false,	
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
		do_action( 'usam_pre_get_documents_crm', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );					
		
		$join = array();		
		$group_join = false;
		
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
				$this->fields[] = USAM_TABLE_DOCUMENTS.".*";	
			elseif ( $field == 'count' )
				$this->fields[] = "COUNT(*) AS count";			
			elseif ( $field == 'sum' )				
				$this->fields[] = "SUM(".USAM_TABLE_DOCUMENTS.".totalprice) AS sum";
			elseif ( $field == 'meta_value' )
				$this->fields[] = USAM_TABLE_DOCUMENT_META.".meta_value";
			elseif ( $field == 'last_comment' )	
			{
				$this->fields[] = "comment.message AS last_comment";	
				$this->fields[] = "comment.user_id AS last_comment_user";	
				$this->fields[] = "comment.date_insert AS last_comment_date";						
				$join[] = " LEFT JOIN ".USAM_TABLE_COMMENTS." AS comment ON (comment.object_id=".USAM_TABLE_DOCUMENTS.".id AND comment.object_type='lead' AND comment.id = (SELECT id FROM ".USAM_TABLE_COMMENTS." WHERE object_type='lead' AND status!=1 AND object_id=".USAM_TABLE_DOCUMENTS.".id ORDER BY id DESC LIMIT 1 ))";
			}				
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_DOCUMENTS.".$field";
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_DOCUMENTS.".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;	
		
		$this->query_join = implode( ' ', $join );
		
		$distinct = false;
		$this->query_from = "FROM ".USAM_TABLE_DOCUMENTS;
		$this->query_where = "WHERE 1=1";		
		
	// Обрабатывать другие параметры даты
		$date_parameters = array();
		foreach( ['second', 'minute', 'hour', 'day', 'w', 'monthnum', 'year'] as $k ) 
		{					
			if ( $qv[$k] !== '' )
				$date_parameters[$k] = $qv[$k];
		}
		if ( $date_parameters ) 
		{
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_DOCUMENTS );
			$this->query_where .= $date_query->get_sql();
		} 
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_DOCUMENTS );
			$this->query_where .= $this->date_query->get_sql();
		}		
		if ( !empty( $qv['include'] ) ) 		
			$include = wp_parse_id_list( $qv['include'] );		 
		else 		
			$include = false;
	
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
				$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".status IN ('".implode( "','", $status )."')";	
		}	
		if ( isset($qv['status__not_in']) ) 
		{
			$status__not_in = (array) $qv['status__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".status NOT IN ('".implode("','", $status__not_in )."')";
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
		if ( empty( $orderby_array ) ) 
		{
			$orderby_array[] = USAM_TABLE_DOCUMENTS.".id $order";
		}
		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		if ( isset($qv['number'] ) && $qv['number'] > 0 ) 
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

			$search_columns = array();
			if ( $qv['search_columns'] )
				$search_columns = array_intersect( $qv['search_columns'], array( 'id', 'name', 'content', 'number', 'product_name') );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array('id', 'content', 'number' );			
				else
					$search_columns = array( 'name', 'content', 'number', 'product_name');
			}	
			$search_columns = apply_filters( 'usam_documents_crm_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}
		if ( !empty( $include ) ) 
		{
			$ids = implode( ',', $include );			
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".id NOT IN ($ids)";
		}					
		$group = array();
		if ( isset($qv['group'] ) ) 
		{
			if ( is_array( $qv['group'] ) ) 
				$group = $qv['group'];			
			elseif ( is_string( $qv['group'] ) && !empty( $qv['group'] ) )
				$group = array_map( 'trim', explode( ',', $qv['group'] ) );
		}
		$group__not_in = array();
		if ( isset($qv['group__not_in'] ) ) {
			$group__not_in = (array) $qv['group__not_in'];
		}		
		if ( !empty($group) ) 
		{				
			$group_join = true;
			$this->query_where .= " AND ".USAM_TABLE_GROUP_RELATIONSHIPS.".group_id IN (".implode( ',',  $group ).")";		
		}		
		if ( !empty($group__not_in) ) 
		{			
			$group_join = true;
			$this->query_where .= " AND ".USAM_TABLE_GROUP_RELATIONSHIPS.".group_id NOT IN (".implode( ',',  $group ).")";		
		}		
		if ( !empty( $qv['company_own'] ) ) 
		{		
			$ids = implode( ',',  (array)$qv['company_own'] );		
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".bank_account_id IN ($ids)";
		}
		if ( !empty($qv['bank_account'] ) ) 
		{		
			$ids = implode( ',',  (array)$qv['bank_account'] );		
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".bank_account_id IN ($ids)";
		}
		if ( !empty( $qv['customer_id'] ) ) 
		{		
			$customer_ids = implode( ',', (array)$qv['customer_id'] );		
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".customer_id IN ($customer_ids)";
		}
		if ( !empty( $qv['customer_type'] ) ) 
		{		
			$customer_type = implode( "','", (array)$qv['customer_type'] );		
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".customer_type IN ('$customer_type')";
		}
		if ( !empty( $qv['contacts'] ) ) 
		{		
			$contact_ids = implode( ',', (array)$qv['contacts'] );		
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".customer_id IN ($contact_ids) AND customer_type='contact'";
		}
		if ( !empty( $qv['companies'] ) )
		{		
			$company_ids = implode( ',', (array)$qv['companies'] );		
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".customer_id IN ($company_ids) AND customer_type='company'";
		}		
		if ( !empty($qv['type']) ) 
		{
			if ( is_array( $qv['type'] ) ) 
				$type = $qv['type'];			
			elseif ( is_string($qv['type']) && !empty($qv['type']) )
			{
				if ( $qv['type'] != 'all' )
					$type = array_map( 'trim', explode( ',', $qv['type'] ) );
			}
			elseif ( is_numeric($qv['type']) )
				$type = array($qv['type']);
				
			if ( !empty($type) ) 
				$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".type IN ('".implode( "','", $type )."')";	
		}	
		if ( isset($qv['type__not_in']) ) 
		{
			$type__not_in = (array) $qv['type__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".type NOT IN ('".implode("','", $type__not_in )."')";
		}
		if ( !empty( $qv['manager_id'] ) ) 
		{
			$managers = is_array($qv['manager_id']) ? wp_parse_id_list($qv['manager_id']): array( $qv['manager_id'] );
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".manager_id IN (".implode( ", ", $managers ).")";
		}
		if ( !empty($qv['child_document']) && !empty($qv['child_document']['id']) && !empty($qv['child_document']['type']) ) 
		{
			$link = !empty($qv['child_document']['link_type'])? $qv['child_document']['link_type'] : 'link';
			$this->query_join .= " INNER JOIN ".USAM_TABLE_DOCUMENT_LINKS." ON (".USAM_TABLE_DOCUMENT_LINKS.".document_link_id = ".USAM_TABLE_DOCUMENTS.".id)";	
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENT_LINKS.".document_id=".$qv['child_document']['id']." AND ".USAM_TABLE_DOCUMENT_LINKS.".document_type='".$qv['child_document']['type']."' AND ".USAM_TABLE_DOCUMENT_LINKS.".link_type='$link'";
		}
		if ( !empty($qv['parent_document']) && !empty($qv['parent_document']['id']) && !empty($qv['parent_document']['type']) ) 
		{
			$link = !empty($qv['parent_document']['link_type'])? $qv['parent_document']['link_type'] : 'link';
			$this->query_join .= " INNER JOIN ".USAM_TABLE_DOCUMENT_LINKS." ON (".USAM_TABLE_DOCUMENT_LINKS.".document_id = ".USAM_TABLE_DOCUMENTS.".id)";	
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENT_LINKS.".document_link_id=".$qv['parent_document']['id']." AND ".USAM_TABLE_DOCUMENT_LINKS.".document_link_type='".$qv['parent_document']['type']."' AND ".USAM_TABLE_DOCUMENT_LINKS.".link_type='$link'";
		}		
		if ( !empty($qv['external_document']) ) 
		{
			$external_document = is_array($qv['external_document'])?$qv['external_document']: array( $qv['external_document'] );	
			$this->query_where .= " AND ".USAM_TABLE_DOCUMENTS.".external_document IN ('".implode( "', '", $external_document )."')";
		}	
		if ( !empty($qv['user_id']) ) 
		{
			$user_ids = is_array($qv['user_id']) ? wp_parse_id_list($qv['user_id']): array( $qv['user_id'] );
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_CONTACTS." ON (".USAM_TABLE_CONTACTS.".id = ".USAM_TABLE_DOCUMENTS.".customer_id AND customer_type='contact')";
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_COMPANY." ON (".USAM_TABLE_COMPANY.".id = ".USAM_TABLE_DOCUMENTS.".customer_id AND customer_type='company')";
			
			$this->query_where .= " AND (".USAM_TABLE_CONTACTS.".user_id IN (".implode( ", ", $user_ids ).") OR ".USAM_TABLE_COMPANY.".user_id IN (".implode( ", ", $user_ids )."))";
		}				
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty( $this->meta_query->queries ) ) 
		{
			$clauses = $this->meta_query->get_sql( 'document', USAM_TABLE_DOCUMENT_META, USAM_TABLE_DOCUMENTS, 'id', $this );
			$this->query_join .= $clauses['join'];
			$this->query_where .= $clauses['where']; 
			if ( $this->meta_query->has_or_relation() ) {
				$distinct = true;
			}
		}
		$conditions_query = new USAM_Conditions_Query();
		$this->query_where .= $conditions_query->get_sql_clauses( $qv, ['sum' => 'totalprice'] );		
		if ( $distinct ) 
			$this->query_fields = 'DISTINCT ' . $this->query_fields;
		if ( $group_join  ) 
			$this->query_join .= " INNER JOIN ".USAM_TABLE_GROUP_RELATIONSHIPS." ON (".USAM_TABLE_GROUP_RELATIONSHIPS.".object_id = ".USAM_TABLE_DOCUMENTS.".id)";	
		do_action_ref_array( 'usam_pre_documents_crm_query', array( &$this ) );
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
					$data->$column = (float)$data->$column;
			}
		}
		if ( $this->query_vars['cache_results'] && isset($data->id) )
			wp_cache_set( $data->id, (array)$data, 'usam_document' );	
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
			$this->total = (int)$wpdb->get_var( 'SELECT FOUND_ROWS()' );		
		
		if ( !empty($this->results) && ($qv['cache_meta'] || $qv['cache_bank_accounts']) || $qv['cache_products'] )
		{
			if ( $qv['cache_bank_accounts'] )
			{				
				$company_ids = array();	
				foreach ( $this->results as $result ) 
				{
					if ( !empty($result->customer_id) && !empty($result->customer_type) && $result->customer_type == 'company' )
						$company_ids[] = $result->customer_id; 					
				}					
				if ( !empty($company_ids) )
				{
					usam_get_companies(['include' => $company_ids, 'cache_results' => true]);
					require_once(USAM_FILE_PATH.'/includes/crm/bank_accounts_query.class.php');	
					$bank_accounts = usam_get_bank_accounts(['company_id' => $company_ids]);
					$results_cache = array();					
					foreach ( $bank_accounts as $bank_account )
					{
						wp_cache_set( $bank_account->id, (array)$bank_account, 'usam_bank_account' );
					}
				}			
			}				
			$ids = array();	
			foreach ( $this->results as $result ) 
			{
				if ( !empty($result->id) )
					$ids[] = $result->id; 					
			}	
			if ( !empty($ids) )
			{
				$tables = array();
				if ( $qv['cache_meta'] )
				{				
					$tables[USAM_TABLE_DOCUMENT_META] = 'document_meta';
				}	
				if ( $qv['cache_products'] )
				{				
					$tables[USAM_TABLE_DOCUMENT_PRODUCTS] = 'document_products';
				}				
				if ( !empty($tables) )
					usam_update_cache( $ids, $tables, 'document_id' );	
			}
		}
	}

	public function get( $query_var )
	{
		if ( isset($this->query_vars[$query_var] ) )
			return $this->query_vars[$query_var];

		return null;
	}

	public function set( $query_var, $value ) {
		$this->query_vars[$query_var] = $value;
	}

	public function get_total_amount() 
	{
		global $wpdb;
		
		$request = "SELECT SUM(".USAM_TABLE_DOCUMENTS.".totalprice) AS sum $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby"; 
		$total_amount = (float)$wpdb->get_var( $request );
		return $total_amount;
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
				$searches[] = $wpdb->prepare( USAM_TABLE_DOCUMENTS.".$col = %s", $string );
			} 			
			elseif ( 'content' == $col ) 
			{
				$like = "%" . $wpdb->esc_like( $string ) . "%";
				$searches[] = $wpdb->prepare( USAM_TABLE_DOCUMENTS.".id IN (SELECT DISTINCT ".USAM_TABLE_DOCUMENT_CONTENT.".document_id FROM ".USAM_TABLE_DOCUMENT_CONTENT." WHERE meta_key ='document_content' AND meta_value LIKE LOWER ('%s') )", $like );		
			} 	
			elseif ( 'product_name' == $col ) 
			{
				$this->query_join .= " LEFT JOIN ".USAM_TABLE_DOCUMENT_PRODUCTS." ON (".USAM_TABLE_DOCUMENTS.".id = ".USAM_TABLE_DOCUMENT_PRODUCTS.".document_id)";
				$searches[] = $wpdb->prepare( USAM_TABLE_DOCUMENT_PRODUCTS.".name LIKE LOWER (%s)", "%".$wpdb->esc_like( $string )."%" );						
			}	
			elseif ( $col == 'name' )
				$searches[] = $wpdb->prepare( USAM_TABLE_DOCUMENTS.".$col LIKE LOWER (%s)", "%".$wpdb->esc_like( $string )."%" );			
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
	
	protected function parse_orderby( $orderby ) 
	{
		global $wpdb;

		$_orderby = '';
		if ( isset($this->db_fields[$orderby]) )
			$_orderby = USAM_TABLE_DOCUMENTS.'.'.$orderby;
		elseif ( $orderby == 'date' )
		{
			$_orderby = USAM_TABLE_DOCUMENTS.'.date_insert';
		} 		 		
		elseif ( 'meta_value' == $orderby || $this->get( 'meta_key' ) == $orderby ) 
			$_orderby = USAM_TABLE_DOCUMENT_META.".meta_value";
		elseif ( 'meta_value_num' == $orderby )
			$_orderby = USAM_TABLE_DOCUMENT_META.".meta_value+0";
		elseif( 'count' == $orderby || 'sum' == $orderby )
		{
			$_orderby = $orderby;
		} 
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) ) 
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_DOCUMENTS.".id, $include_sql )";
		} 		
		return $_orderby;
	}

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

function usam_get_documents( $query = array() )
{	
	$query['count_total'] = false;
	$documents_crm = new USAM_Documents_Query( $query );	
	return $documents_crm->get_results();	
}	