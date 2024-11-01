<?php
// Класс работы с заметками
class USAM_Notes_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'note' => 's', 'user_id' => 'd', 'date_insert' => 's', 'status' => 'd', 'sort' => 'd'];

	public $query_fields;
	public $query_from;
	public $meta_query;	
	public $query_join;	
	public $query_where;
	public $query_orderby;
	public $query_groupby;	 
	public $query_limit;
	
	public $date_query;	
	
	public function __construct($query = null ) 
	{
		require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
		require_once( USAM_FILE_PATH . '/includes/query/date.php' );
		if ( !empty($query ) ) 
		{
			$this->prepare_query($query );
			$this->query();
		}
	}	

	/**
	 * Fills in missing query variables with default values.
	 */
	public static function fill_query_vars($args ) 
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
			'active' => '',
			'active__in' => array(),
			'active__not_in' => array(),
			'meta_key' => '',
			'meta_value' => '',
			'meta_compare' => '',
			'include' => array(),
			'exclude' => array(),
			'search' => '',
			'search_columns' => array(),
			'orderby' => 'id',
			'order' => 'DESC',
			'offset' => '',
			'number' => '',
			'paged' => 1,
			'count_total' => true,
			'fields' => 'all',	
			'add_fields' => '',				
			'cache_results' => false,	
			'cache_group' => false,	
			'cache_meta' => false			
		);
		return wp_parse_args($args, $defaults );
	}
	
	public function prepare_query($query = array() ) 
	{
		global $wpdb;

		if ( empty($this->query_vars ) || !empty($query ) ) 
		{
			$this->query_limit = null;
			$this->query_vars = $this->fill_query_vars($query );
		}			
		do_action( 'usam_pre_get_notes', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars($qv );	
				
		$join = array();			
		$fields = is_array($qv['fields']) ? array_unique( $qv['fields'] ) : explode(',', $qv['fields']);
		if ( $qv['add_fields'] )
		{
			$add_fields = is_array($qv['add_fields']) ? array_unique( $qv['add_fields'] ) : explode(',', $qv['add_fields']);
			$fields = array_merge( $fields, $add_fields );	
		}	
		foreach ( $fields as $field ) 
		{
			if ( $field == 'all' )
				$this->fields[] = USAM_TABLE_NOTES.".*";		
			elseif ( $field == 'count' )
				$this->fields[] = "COUNT(*) AS count";				
			elseif ( 'id' == $field ) 
				$this->fields[] = USAM_TABLE_NOTES.'.id';				
			else
				$this->fields[] = USAM_TABLE_NOTES.'.'.sanitize_key($field );
		}	
		$this->query_fields = implode( ',', $this->fields );	
		if ( isset($qv['count_total']) && $qv['count_total'])
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;

		
		$this->query_join = implode( ' ', $join );

		$this->query_from = "FROM ".USAM_TABLE_NOTES;
		$this->query_where = "WHERE 1=1";		
			
		if ( !empty($qv['include']) ) 		
			$include = wp_parse_id_list($qv['include']);		 
		else 		
			$include = false;			
		// Группировать
		$qv['groupby'] = isset($qv['groupby']) ? $qv['groupby'] : '';
		
		if ($qv['groupby'] != '' )
		{
			$timezone = wp_timezone();
			$name_timezone = $timezone->getName();	
			if ( is_array($qv['groupby']) )					
				$groupby = $qv['groupby'];					
			else
				$groupby[] = $qv['groupby'];
			$ordersby_array = array();		
			foreach ($groupby as $_value ) 
			{				
				switch ($_value ) 
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
		$qv['order'] = isset($qv['order']) ? strtoupper($qv['order']) : '';
		$order = $this->parse_order($qv['order']);

		if ( empty($qv['orderby']) ) 
		{	// Default order is by 'id'.			
			$ordersby = array( 'id' => $order );
		} 
		elseif ( is_array($qv['orderby']) ) 
		{
			$ordersby = $qv['orderby'];
		} 
		else 
		{
			// Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby']);
		}
		$orderby_array = array();
		foreach ($ordersby as $_key => $_value ) 
		{
			if ( ! $_value ) {
				continue;
			}			
			if ( is_int($_key ) ) 
			{				
				$_orderby = $_value;
				$_order = $order;
			} 
			else
			{				
				$_orderby = $_key;
				$_order = $_value;
			}			
			$parsed = $this->parse_orderby($_orderby );
			if ( ! $parsed ) {
				continue;
			}
			$orderby_array[] = $parsed . ' ' . $this->parse_order($_order );
		}		
		if ( empty($orderby_array ) ) 
		{
			$orderby_array[] = USAM_TABLE_NOTES.".id $order";
		}
		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		if ( isset($qv['number']) && $qv['number'] > 0 ) 
		{
			if ($qv['offset']) {
				$this->query_limit = $wpdb->prepare("LIMIT %d, %d", $qv['offset'], $qv['number']);
			} else {
				$this->query_limit = $wpdb->prepare( "LIMIT %d, %d", $qv['number'] * ($qv['paged'] - 1 ), $qv['number']);
			}
		}
		$search = '';
		if ( isset($qv['search']) )
			$search = trim($qv['search']);

		if ($search ) 
		{
			$leading_wild = ( ltrim($search, '*') != $search );
			$trailing_wild = ( rtrim($search, '*') != $search );
			if ($leading_wild && $trailing_wild )
				$wild = 'both';
			elseif ($leading_wild )
				$wild = 'leading';
			elseif ($trailing_wild )
				$wild = 'trailing';
			else
				$wild = false;
			if ($wild )
				$search = trim($search, '*');

			$search_columns = array();
			if ($qv['search_columns'])
				$search_columns = array_intersect($qv['search_columns'], array( 'id', 'name', 'code' ) );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array('id' );			
				else
					$search_columns = array('id', 'name', 'code');
			}	
			$search_columns = apply_filters( 'usam_notes_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql($search, $search_columns, $wild );
		}
		if ( !empty($include ) ) 
		{
			$ids = implode( ',', $include );			
			$this->query_where .= " AND ".USAM_TABLE_NOTES.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list($qv['exclude']) );
			$this->query_where .= " AND ".USAM_TABLE_NOTES.".id NOT IN ($ids)";
		}	
		if ( !empty($qv['status']) )
		{		
			$status = implode( ",", (array)$qv['status']);		
			$this->query_where .= " AND ".USAM_TABLE_NOTES.".status IN ($status)";
		}				
		if ( !empty($qv['user_id']) )
		{		
			$user_id = wp_parse_id_list( (array)$qv['user_id']);
			$this->query_where .= " AND ".USAM_TABLE_NOTES.".user_id IN ('".implode("','", $user_id)."')";
		}		
		do_action_ref_array( 'usam_pre_notes_query', array( &$this ) );
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
			wp_cache_set( $data->id, (array)$data, 'usam_note' );	
		return $data;
	}

	/**
	 * Execute the query, with the current variables.	
	 */
	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;	
		$this->request = "SELECT DISTINCT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";		
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
			if ( !$this->results )
				return;
		}
		elseif ( $qv['number'] == 1 && $count_fields == 1 )		
			$this->results = $wpdb->get_var( $this->request );
		else 
			$this->results = $wpdb->get_col( $this->request );
		if ( !$this->results )
			return;	
		
		if ( isset($qv['count_total']) && $qv['count_total'])
			$this->total = (int)$wpdb->get_var( 'SELECT FOUND_ROWS()' );		
	}

	/**
	 * Retrieve query variable.
	 */
	public function get($query_var ) 
	{
		if ( isset($this->query_vars[$query_var]) )
			return $this->query_vars[$query_var];

		return null;
	}

	public function set($query_var, $value ) 
	{
		$this->query_vars[$query_var] = $value;
	}


	protected function get_search_sql($string, $cols, $wild = false ) 
	{
		global $wpdb;

		$searches = array();
		$leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
		$like = $leading_wild . $wpdb->esc_like($string ) . $trailing_wild;

		foreach ($cols as $col ) 
		{
			if ( 'id' == $col || 'code' == $col ) 
			{
				$searches[] = $wpdb->prepare( USAM_TABLE_NOTES.".$col = %s", $string );
			} 		
			elseif ( 'name' == $col || 'description' == $col )
			{
				$searches[] = USAM_TABLE_NOTES.$wpdb->prepare( ".$col LIKE %s", '%'.$wpdb->esc_like($string ).'%' );
			} 			
			else 
			{
				$searches[] = USAM_TABLE_NOTES.$wpdb->prepare( ".$col LIKE %s", $like );
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
	protected function parse_orderby($orderby ) 
	{
		global $wpdb;

		$_orderby = '';
		if ( isset($this->db_fields[$orderby]) )
			$_orderby = USAM_TABLE_NOTES.'.'.$orderby;
		elseif ( 'include' === $orderby && !empty($this->query_vars['include']) ) {
			$include = wp_parse_id_list($this->query_vars['include']);
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_NOTES.".id, $include_sql )";
		} 		
		return $_orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 */
	protected function parse_order($order ) 
	{
		if ( ! is_string($order ) || empty($order ) )
			return 'DESC';		
		if ( 'ASC' === strtoupper($order ) ) 
			return 'ASC';
		else 
			return 'DESC';
	}
	
	public function __get($name ) 
	{
		if ( in_array($name, $this->compat_fields ) ) 
		{
			return $this->$name;
		}
	}
	
	public function __set($name, $value )
	{
		if ( in_array($name, $this->compat_fields ) ) 
		{
			return $this->$name = $value;
		}
	}

	public function __isset($name ) 
	{
		if ( in_array($name, $this->compat_fields ) ) {
			return isset($this->$name );
		}
	}

	public function __unset($name ) 
	{
		if ( in_array($name, $this->compat_fields ) ) {
			unset($this->$name );
		}
	}

	public function __note($name, $arguments )
	{
		if ( 'get_search_sql' === $name ) {
			return note_user_func_array( array($this, $name ), $arguments );
		}
		return false;
	}
}

function usam_get_notes($query = array() )
{	
	if ( !isset($query['status']) )		
		$query['status'] = 0;	
	
	$query['count_total'] = false;
	$notes = new USAM_Notes_Query($query );	
	return $notes->get_results();	
}	