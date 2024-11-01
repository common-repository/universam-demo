<?php
class USAM_Company_Finances_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $request;

	private $compat_fields = array( 'results', 'total' );

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
			'column' => '',	
			'active' => '',
			'active__in' => array(),
			'active__not_in' => array(),		
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
			'fields' => 'all',				
			'cache_results' => false	
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
		do_action( 'usam_pre_get_company_finances', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars($qv );	
				
		$join = array();		
	
		if ( 'all' == $qv['fields']) 		
			$this->query_fields = USAM_TABLE_COMPANY_FINANCE.".*";
		else
		{
			if ( !is_array($qv['fields']) )	
				$fields = array($qv['fields']);
			else
			{ 
				$fields = $qv['fields'];				
			}		
			$fields = array_unique($fields );

			$this->query_fields = array();
			foreach ($fields as $field ) 
			{							
				if ( 'id' == $field ) 
					$field = 'id';				
				else
					$field = sanitize_key($field );
				$this->query_fields[] = USAM_TABLE_COMPANY_FINANCE.".$field";
			}				
			$this->query_fields = implode( ',', $this->query_fields );
		} 		
		if ( isset($qv['count_total']) && $qv['count_total'])
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		
		$this->query_join = implode( ' ', $join );

		$this->query_from = "FROM ".USAM_TABLE_COMPANY_FINANCE;
		$this->query_where = "WHERE 1=1";		
			
		if ( !empty($qv['include']) ) 		
			$include = wp_parse_id_list($qv['include']);		 
		else 		
			$include = false;
						
		$qv['groupby'] = isset($qv['groupby']) ? $qv['groupby'] : '';
		if ($qv['groupby'] != '' )
		{
			if ( is_array($qv['groupby']) )					
				$groupby = $qv['groupby'];					
			else
				$groupby[] = $qv['groupby'];
			$ordersby_array = array();		
			foreach ($groupby as $_value ) 
			{				
				switch ($_value ) 
				{					
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
		{			
			$ordersby = array( 'sort' => $order );
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
			$orderby_array[] = USAM_TABLE_COMPANY_FINANCE.".id $order";
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
				$search_columns = array_intersect($qv['search_columns'], array( 'id', 'year', 'code' ) );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array('id' );			
				else
					$search_columns = array('id', 'year', 'code');
			}	
			$search_columns = apply_filters( 'usam_company_finances_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql($search, $search_columns, $wild );
		}
		if ( !empty($include ) ) 
		{
			$ids = implode( ',', $include );			
			$this->query_where .= " AND ".USAM_TABLE_COMPANY_FINANCE.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list($qv['exclude']) );
			$this->query_where .= " AND ".USAM_TABLE_COMPANY_FINANCE.".id NOT IN ($ids)";
		}			
		if ( isset($qv['year']) )
		{		
			$year = (array)$qv['year'];
			$this->query_where .= " AND ".USAM_TABLE_COMPANY_FINANCE.".year IN (".implode( ",", $year ).")";
		}			
		if ( !empty($qv['code']) )
		{		
			$code = (array)$qv['code'];
			$this->query_where .= " AND ".USAM_TABLE_COMPANY_FINANCE.".code IN (".implode( ",", $code ).")";
		}
		if ( !empty($qv['value']) )
		{		
			$value = (array)$qv['value'];
			$this->query_where .= " AND ".USAM_TABLE_COMPANY_FINANCE.".value IN (".implode( ",", $value ).")";
		}
		if ( isset($qv['company_id']) )
		{		
			$company_id = (array)$qv['company_id'];
			$this->query_where .= " AND ".USAM_TABLE_COMPANY_FINANCE.".company_id IN (".implode( ",", $company_id ).")";
		}		
		do_action_ref_array( 'usam_pre_company_finances_query', array( &$this ) );
	}

	/**
	 * Execute the query, with the current variables.	
	 */
	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;
	
		$this->request = "SELECT DISTINCT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";
	
		if ( is_array($qv['fields']) || 'all' == $qv['fields']) 		
			$this->results = $wpdb->get_results($this->request );		
		elseif ($qv['number'] == 1 )		
			$this->results = $wpdb->get_var($this->request );
		else
			$this->results = $wpdb->get_col($this->request );
		
		if ( !$this->results )
			return;	
		
		if ( isset($qv['count_total']) && $qv['count_total'])
			$this->total = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
		
		if ($qv['cache_results'] && 'all' == $qv['fields'])
		{ 
			if ($qv['number'] == 1 )
				wp_cache_set($this->results->id, (array)$this->results, 'usam_company_finance' );	
			else
			{					
				foreach ($this->results as $result ) 
					wp_cache_set($result->id, (array)$result, 'usam_property_group' );
			}			
		}					
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
				$searches[] = $wpdb->prepare( USAM_TABLE_COMPANY_FINANCE.".$col = %s", $string );
			} 			
			else 
			{
				$searches[] = USAM_TABLE_COMPANY_FINANCE.$wpdb->prepare( ".$col LIKE %s", $like );
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
		if ( in_array($orderby, ['code', 'year', 'id'] ) )
		{
			$_orderby = USAM_TABLE_COMPANY_FINANCE.'.'.$orderby;
		} 		
		elseif ( 'include' === $orderby && !empty($this->query_vars['include']) ) {
			$include = wp_parse_id_list($this->query_vars['include']);
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_COMPANY_FINANCE.".id, $include_sql )";
		} 		
		return $_orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 */
	protected function parse_order($order ) {
		if ( ! is_string($order ) || empty($order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper($order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
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

	public function __property($name, $arguments )
	{
		if ( 'get_search_sql' === $name ) {
			return property_user_func_array( array($this, $name ), $arguments );
		}
		return false;
	}
}

function usam_get_company_finances($query_args = array() )
{	
	$query_args['count_total'] = false;
	$query = new USAM_Company_Finances_Query($query_args );	
	return $query->get_results();	
}	