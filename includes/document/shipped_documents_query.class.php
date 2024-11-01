<?php
// Класс работы с документами доставки
class USAM_Shippeds_Document_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $meta_query = false;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'order_id' => 'd', 'manager_id' => 'd', 'courier' => 'd', 'name' => 's', 'number' => 's', 'customer_id' => 'd', 'customer_type' => 's', 'method' => 'd', 'storage_pickup' => 'd', 'storage' => 'd', 'totalprice' => 'f', 'type_price' => 's', 'price' => 'f', 'include_in_cost' => 'd', 'date_insert' => 's', 'status' => 's', 'track_id' => 's', 'tax_id' => 'd', 'tax_value' => 'f'];
	
	// SQL clauses
	public $query_fields;
	public $query_from;
	public $query_join;	
	public $query_where;
	public $query_orderby;
	public $query_groupby;	 
	public $query_limit;
	
	private $join_order = false;
	private $join_storage_pickup = false;	
	
	public $date_query;	
	
	public function __construct( $query = null ) 
	{
		require_once( USAM_FILE_PATH . '/includes/query/date.php' );
		require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
		require_once( USAM_FILE_PATH . '/includes/query/table_query.class.php' );
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
			'id'  => '',
			'order_id' => '',
			'name'  => '',
			'date_insert' => '',				
			'status' => '', 									
			'include' => array(),
			'exclude' => array(),
			'search' => '',
			'reserve' => '',			
			'search_columns' => array(),
			'orderby' => 'id',
			'order' => 'ASC',
			'offset' => '',
			'number' => '',
			'paged' => 1,
			'count_total' => true,
			'cache_results' => false,
			'cache_products' => false,		
			'cache_order' => false,				
			'cache_order_meta' => false,
			'cache_storages' => false,			
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
		do_action( 'usam_pre_get_shippeds_document', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );	
		$this->join_order = false;
		$this->join_storage_pickup = false;
		
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
				$this->fields[] = USAM_TABLE_SHIPPED_DOCUMENTS.".*";	
			elseif ( $field == 'count' )
				$this->fields[] = "COUNT(*) AS count";			
			elseif ( $field == 'storage_pickup_name' )		
			{
				$this->join_storage_pickup = true;				
				$this->fields[] = USAM_TABLE_STORAGES.".title AS storage_pickup_name";				
			}
			elseif ( $field == 'reserve' )		
			{
				$join[] = " LEFT JOIN (SELECT SUM(reserve) AS reserve, document_id FROM ".USAM_TABLE_SHIPPED_PRODUCTS." GROUP BY document_id) AS sp ON sp.document_id=".USAM_TABLE_SHIPPED_DOCUMENTS.".id";
				$this->fields[] = "IFNULL(sp.reserve,0) AS reserve";				
			}
			elseif ( $field == 'order_number' )		
			{
				$this->join_order = true;				
				$this->fields[] = USAM_TABLE_ORDERS.".number AS order_number";				
			}
			elseif ( $field == 'order_data' )		
			{
				$this->join_order = true;				
				$this->fields[] = USAM_TABLE_ORDERS.".data_insert AS order_data";				
			}			
			elseif ( $field == 'ordersum' )		
			{
				$this->join_order = true;
				$this->fields[] = "IFNULL(".USAM_TABLE_ORDERS.".totalprice,0) AS ordersum";				
			}		
			elseif ( $field == 'delivery_option' )		
			{
				$join[] = " LEFT JOIN ".USAM_TABLE_DELIVERY_SERVICE." ON ".USAM_TABLE_DELIVERY_SERVICE.".id=".USAM_TABLE_SHIPPED_DOCUMENTS.".method";
				$this->fields[] = USAM_TABLE_DELIVERY_SERVICE.".delivery_option";				
			}					
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = USAM_TABLE_SHIPPED_DOCUMENTS.".$field";
		}
		if ( !count($this->fields) )
			$this->query_fields = USAM_TABLE_SHIPPED_DOCUMENTS.".*";
		else
			$this->query_fields = implode( ',', $this->fields );	
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".USAM_TABLE_SHIPPED_DOCUMENTS;
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
			$date_query = new USAM_Date_Query( [$date_parameters], USAM_TABLE_SHIPPED_DOCUMENTS );
			$this->query_where .= $date_query->get_sql();
		}
		if ( !empty($qv['date_query']) ) 
		{
			$this->date_query = new USAM_Date_Query( $qv['date_query'], USAM_TABLE_SHIPPED_DOCUMENTS );
			$this->query_where .= $this->date_query->get_sql();
		}
	
		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( !empty( $qv['include'] ) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;
			
		if ( !empty($qv['status_type']) )
		{
			$this->query_join .= " INNER JOIN ".USAM_TABLE_OBJECT_STATUSES." ON (".USAM_TABLE_OBJECT_STATUSES.".internalname=".USAM_TABLE_SHIPPED_DOCUMENTS.".status AND ".USAM_TABLE_OBJECT_STATUSES.".type='shipped')";
			if ( $qv['status_type'] == 'unclosed' )
				$this->query_where .= " AND ".USAM_TABLE_OBJECT_STATUSES.".close=0";
			elseif ( $qv['status_type'] == 'closed' )
				$this->query_where .= " AND ".USAM_TABLE_OBJECT_STATUSES.".close=1";
		}
		if ( !empty($qv['status']) ) 
		{
			if ( is_array($qv['status']) ) 
				$status = $qv['status'];			
			elseif ( is_string($qv['status']) && !empty($qv['status']) )
			{
				if ( $qv['status'] != 'all' )
					$status = array_map( 'trim', explode( ',', $qv['status'] ) );
			}
			elseif ( is_numeric($qv['status']) )
				$status = array($qv['status']);
				
			if ( !empty($status) ) 
				$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".status IN ('".implode("','",  $status)."')";	
		}	
		if ( isset($qv['status__not_in']) ) 
		{
			$status__not_in = (array) $qv['status__not_in'];
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".status NOT IN ('".implode("','",  $status__not_in)."')";
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
		{  // Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
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
			$orderby_array[] = USAM_TABLE_SHIPPED_DOCUMENTS.".id $order";
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
				$search_columns = array_intersect( $qv['search_columns'], ['id', 'order_id', 'document_number', 'external_document', 'method', 'product_sku']);
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = ['product_sku', 'document_number','id', 'order_id'];				
				elseif ( stripos($search, ' ') === false )
					$search_columns = ['product_sku', 'document_number', 'method'];
				else
					$search_columns = array( 'id' );
			}	
			$search_columns = apply_filters( 'usam_shipped_documents_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( !empty( $include ) ) 
		{	// Sanitized earlier.
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".id NOT IN ($ids)";
		}						
		if ( !empty($qv['order_id']) ) 
		{
			$order_id = implode( ',',  (array)$qv['order_id'] );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".order_id IN ($order_id)";
		}		
		if ( !empty( $qv['method']) ) 
		{
			$method = implode( ',',  (array)$qv['method'] );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".method IN ($method)";
		}		
		if ( isset($qv['storage_pickup']) ) 
		{
			$storage_pickup = implode( ',',  (array)$qv['storage_pickup'] );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".storage_pickup IN ($storage_pickup)";
		}	
		if ( isset($qv['storage_pickup__not_in']) ) 
		{
			$storage_pickup = implode( ',',  (array)$qv['storage_pickup__not_in'] );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".storage_pickup NOT IN ($storage_pickup)";
		}		
		if ( isset($qv['storage']) ) 
		{
			$storage = implode( ',',  (array)$qv['storage'] );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".storage IN ($storage)";
		}			
		if ( isset($qv['storage__not_in']) ) 
		{
			$storage = implode( ',',  (array)$qv['storage__not_in'] );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".storage NOT IN ($storage)";
		}	
		if ( isset($qv['seller']) ) 
		{
			$seller = implode( ',',  (array)$qv['seller'] );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".seller_id IN ($seller)";
		}			
		if ( isset($qv['seller__not_in']) ) 
		{
			$seller = implode( ',',  (array)$qv['seller__not_in'] );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".seller_id NOT IN ($seller)";
		}		
		if ( isset($qv['include_in_cost']) ) 
		{
			$include_in_cost = $qv['include_in_cost'] == 1?1:0;
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".include_in_cost='$include_in_cost'";
		}				
		if ( isset($qv['courier']) ) 
		{
			$courier = implode( ',',  (array)$qv['courier'] );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".courier IN ($courier)";
		}
		if ( isset($qv['order_status']) ) 
		{
			$order_status = implode( "','",  (array)$qv['order_status'] );
			$this->join_order = true;
			$this->query_where .= " AND ".USAM_TABLE_ORDERS.".status IN ('$order_status')";
		}
		if ( !empty($qv['exchange']) ) 
		{			
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SHIPPED_DOCUMENT_META." AS exchange ON (exchange.document_id = ".USAM_TABLE_SHIPPED_DOCUMENTS.".id AND exchange.meta_key ='exchange')";	
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_SHIPPED_DOCUMENT_META." AS exchange1 ON (exchange1.document_id = ".USAM_TABLE_SHIPPED_DOCUMENTS.".id AND exchange1.meta_key ='exchange')";
			$this->query_where .= " AND (exchange.meta_key IS NULL OR exchange1.meta_value=0)"; 
		}
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
		{		
			if ( !empty($qv['seller_id']) ) 
			{ 
				$sellers = implode( "','",  (array)$qv['seller_id'] );		
				$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".seller_id IN ('$sellers')";  
			}
		}
		if ( !empty($qv['manager_id']) ) 
		{
			$ids = implode( "','", wp_parse_id_list( $qv['manager_id'] ) );
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".manager_id IN ('$ids')";
		}
		if ( usam_check_current_user_role( 'courier' ) )
		{
			$user_id = get_current_user_id();
			$this->query_where .= " AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".courier='$user_id'";			
		}
		$conditions_query = new USAM_Conditions_Query();
		$this->query_where .= $conditions_query->get_sql_clauses( $qv );		
		$this->meta_query = new USAM_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );	
		if ( !empty( $this->meta_query->queries ) ) 
		{
			$clauses = $this->meta_query->get_sql( 'document', USAM_TABLE_SHIPPED_DOCUMENT_META, USAM_TABLE_SHIPPED_DOCUMENTS, 'id', $this );
			$this->query_join   .= $clauses['join'];
			$this->query_where  .= $clauses['where'];		
			if ( $this->meta_query->has_or_relation() && strripos($this->query_fields, 'DISTINCT')  === false )
				$this->query_fields = 'DISTINCT ' . $this->query_fields;			
		}			
		$table_query = new USAM_Table_Query();
		$table_query->parse_query_vars( $qv );	
		if ( !empty($table_query->queries) ) 
		{
			$clauses = $table_query->get_sql( 'document', USAM_TABLE_SHIPPED_PRODUCTS, USAM_TABLE_SHIPPED_DOCUMENTS, 'id', $this );
			$this->query_join   .= $clauses['join'];
			$this->query_where  .= $clauses['where'];			
		}		
		if ( $this->join_order )
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_ORDERS." ON ".USAM_TABLE_ORDERS.".id=".USAM_TABLE_SHIPPED_DOCUMENTS.".order_id";
		if ( $this->join_storage_pickup )
			$this->query_join .= " LEFT JOIN ".USAM_TABLE_STORAGES." ON ".USAM_TABLE_STORAGES.".id=".USAM_TABLE_SHIPPED_DOCUMENTS.".storage_pickup";		
		do_action_ref_array( 'usam_pre_shippeds_document_query', array( &$this ) );
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
			wp_cache_set( $data->id, (array)$data, 'usam_document_shipped' );
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
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_shipped_documents_query', 'SELECT FOUND_ROWS()' ) );	
	
		if ( $qv['cache_order'] )
		{
			$object_ids = array();
			foreach ( $this->results as $result ) 
			{
				if( !empty($result->order_id) )
					$object_ids[] = $result->order_id;						
			}	
			if ( !empty($object_ids) )
				usam_get_orders(['include' => $object_ids, 'cache_results' => true]);
		}	
		if ( $qv['cache_order_meta'] )
		{
			$object_ids = array();
			foreach ( $this->results as $result ) 
			{
				if( !empty($result->order_id) )
					$object_ids[] = $result->order_id;						
			}	
			if ( !empty($object_ids) )
				usam_update_cache( $object_ids, [USAM_TABLE_ORDER_META => 'order_meta'], 'order_id' );
		}	
		if ( $qv['cache_storages'] )
		{
			$ids = array();	
			foreach ( $this->results as $result ) 
			{				
				$ids[] = $result->id; 					
			}	
			usam_get_storages(['include' => $ids]);
		}
		if ( $qv['cache_products'] )
		{
			$ids = array();	
			foreach ( $this->results as $result ) 
			{				
				$ids[] = $result->id; 					
			}	
			if ( !empty($ids) )
			{				
				$products = $wpdb->get_results("SELECT * FROM `". USAM_TABLE_SHIPPED_PRODUCTS ."` WHERE document_id IN (".implode( ",",$ids).")");
				$cache = array();
				$post_ids = array();
				foreach ( $products as $product ) 
				{
					$cache[$product->document_id][] = $product;
					$post_ids[] = $product->product_id;		
				}
				update_meta_cache('post', $post_ids);
				foreach ( $cache as $id => $products ) 
				{
					wp_cache_set( $id, $products, 'usam_products_shipped_document' );	
				}				
			}
		}
		if ( $qv['cache_meta'] )		
		{ 
			$ids = array();	
			foreach ( $this->results as $result ) 
			{				
				$ids[] = $result->id; 					
			}	
			usam_update_cache( $ids, [USAM_TABLE_SHIPPED_DOCUMENT_META => 'document_meta'], 'document_id' );
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
				$searches[] = USAM_TABLE_SHIPPED_DOCUMENTS.'.'.$wpdb->prepare( "$col = %d", $string );
			elseif ( 'product_sku' == $col ) 
			{ 
				$product_id = usam_get_product_id_by_sku( $string );
				if ( !empty($product_id) )
				{
					$this->query_join .=  " INNER JOIN ".USAM_TABLE_SHIPPED_PRODUCTS." AS product_sku ON (product_sku.document_id = ".USAM_TABLE_SHIPPED_DOCUMENTS.".id )";
					$searches[] = $wpdb->prepare( "product_sku.product_id = %d", $product_id );
				}
			}
			elseif ( 'external_document' == $col ) 
			{ 
				$this->query_join .=  " INNER JOIN ".USAM_TABLE_SHIPPED_DOCUMENT_META." AS dn ON (dn.document_id = ".USAM_TABLE_SHIPPED_DOCUMENTS.".id )";
				$searches[] = $wpdb->prepare( "dn.meta_key='external_document' AND dn.meta_value LIKE %s", $string );
				if ( strripos($this->query_fields, 'DISTINCT')  === false )
					$this->query_fields = 'DISTINCT ' . $this->query_fields;
			}	
			elseif ( 'document_number' == $col ) 
				$searches[] = $wpdb->prepare( "number LIKE %s", $like );
			else
				$searches[] = $wpdb->prepare( "$col LIKE %s", $like );
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
			$_orderby = USAM_TABLE_SHIPPED_DOCUMENTS.'.'.$orderby;
		elseif ( $orderby == 'date' )
			$_orderby = USAM_TABLE_SHIPPED_DOCUMENTS.'.date_insert';
		elseif ( 'document_number' == $orderby ) 
			$_orderby = 'number';
		elseif ( 'ordersum' == $orderby ) 
			$_orderby = $orderby;
		elseif ( 'delivery_option' == $orderby ) 
			$_orderby = USAM_TABLE_DELIVERY_SERVICE.".".$orderby;
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_SHIPPED_DOCUMENTS.".id, $include_sql )";
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

function usam_get_shipping_documents( $args = [] )
{
	$args['count_total'] = false;	
	$shipping_documents = new USAM_Shippeds_Document_Query( $args );	
	$results = $shipping_documents->get_results();	
	
	return $results;
}