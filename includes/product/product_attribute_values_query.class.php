<?php
// Класс работы с вариантами атрибутов
class USAM_Product_Attribute_Values_Query 
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $meta_query = false;
	public $request;

	private $compat_fields = ['results', 'total'];
	private $fields = [];
	private $db_fields = ['id' => 'd', 'attribute_id' => 'd', 'slug' => 's', 'code' => 's', 'value' => 's', 'sort' => 'd'];

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
			'id'  => '',					
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
			'cache_term_meta' => false,			
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
		do_action( 'usam_pre_get_product_attribute_values', $this );
		
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
			if( $field == 'all' || 'attribute_id=>data' == $field )
				$this->fields[] = usam_get_table_db('product_attribute_options').".*";	
			elseif( $field == 'id=>value' )
				$this->fields[] = usam_get_table_db('product_attribute_options').".id, ".usam_get_table_db('product_attribute_options').".value";
			elseif ( $field == 'count' )				
				$this->fields[] = "COUNT(*) AS count";				
			elseif ( isset($this->db_fields[$field]) )
				$this->fields[] = usam_get_table_db('product_attribute_options').".$field";
		}
		if ( !count($this->fields) )
			$this->query_fields = usam_get_table_db('product_attribute_options').".*";
		else
			$this->query_fields = implode( ',', $this->fields );
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;			
		
		$this->query_join = implode( ' ', $join );
		$this->query_from = "FROM ".usam_get_table_db('product_attribute_options');
		$this->query_where = "WHERE 1=1";		
		
		if ( !empty( $qv['include'] ) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;
				
		if( !empty($qv['slug']) ) 
		{
			if ( is_array($qv['slug']) ) 
				$slug = $qv['slug'];			
			elseif ( is_string($qv['slug']) )
				$slug = array_map( 'trim', explode( ',', $qv['slug'] ) );
			elseif ( is_numeric($qv['slug']) )
				$slug = $qv['slug'];
			if ( !empty($slug) )
				$this->query_where .= " AND ".usam_get_table_db('product_attribute_options').".slug IN ('".implode( "','",  $slug )."')";		
		}		
		if ( !empty($qv['slug__not_in']) ) 
		{			
			$slug__not_in = (array) $qv['slug__not_in'];
			$this->query_where .= " AND ".usam_get_table_db('product_attribute_options').".slug NOT IN ('".implode( "','",  $slug__not_in )."')";
		}		
		$object_join = false;
		// Группировать
		$qv['groupby'] = isset($qv['groupby'] ) ? $qv['groupby'] : '';
		
		if ( $qv['groupby'] != '' )
		{
			if ( is_array($qv['groupby']) )					
				$groupby = $qv['groupby'];					
			else
				$groupby[] = $qv['groupby'];			
			$ordersby_array = array();		
			foreach ( $groupby as $_value ) 
			{				
				switch ( $_value ) 
				{					
					default:
						$ordersby_array[] = usam_get_table_db('product_attribute_options').'.'.$_value;
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
		{			
			$ordersby = array( 'id' => $order );
		} 
		elseif ( is_array( $qv['orderby'] ) ) 
		{
			$ordersby = $qv['orderby'];
		} 
		else 
		{
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
			$orderby_array[] = usam_get_table_db('product_attribute_options').".sort $order";
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
		if ( isset($qv['search']) )
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
				$search_columns = array_intersect( $qv['search_columns'], ['id', 'code', 'value'] );
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = ['id', 'code', 'value'];				
				else
					$search_columns = ['code', 'value'];
			}	
			$search_columns = apply_filters( 'usam_product_attribute_values_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		if ( !empty( $include ) ) 
		{	
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".usam_get_table_db('product_attribute_options').".id IN ($ids)";
		} 
		elseif ( !empty( $qv['exclude'] ) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".usam_get_table_db('product_attribute_options').".id NOT IN ($ids)";
		}		
		if ( isset($qv['product_id']) ) 
		{
			if ( is_array($qv['product_id']) )
			{
				if ( !empty($qv['product_id']) ) 
					$product_id = $qv['product_id'];
			}
			elseif( is_numeric($qv['product_id']) )
				$product_id = (array)$qv['product_id'];
			elseif( is_string($qv['product_id']) )
				$product_id = array_map( 'trim', explode( ',', $qv['product_id'] ) );				
			if ( !empty($product_id) )
			{
			//	$this->query_where .= " AND ".usam_get_table_db('product_attribute_options').".product_id IN (".implode(',',  wp_parse_id_list($product_id)).")";
			}
		}	
		if ( !empty($qv['code']) ) 
		{
			if ( is_array( $qv['code'] ) ) 
				$code = $qv['code'];			
			elseif ( is_string($qv['code']) && !empty($qv['code']) )
			{
				if ( $qv['code'] != 'all' )
					$code = array_map( 'intval', explode( ',', $qv['code'] ) );
			}
			elseif ( is_numeric($qv['code']) )
				$code = array($qv['code']);
				
			if ( !empty($code) ) 
				$this->query_where .= " AND ".usam_get_table_db('product_attribute_options').".code IN (".implode( ",", $code ).")";	
		}	
		if ( isset($qv['code__not_in']) ) 
		{
			$code__not_in = (array)$qv['code__not_in'];
			$this->query_where .= " AND ".usam_get_table_db('product_attribute_options').".code NOT IN (".implode(",", $code__not_in ).")";
		}
		if ( !empty($qv['attribute_external_code']) ) 
		{
			$attribute_id = usam_term_id_by_meta('external_code', $qv['attribute_external_code'], 'usam-product_attributes');
			if ( !empty($attribute_id) ) 
				$this->query_where .= " AND ".usam_get_table_db('product_attribute_options').".attribute_id=$attribute_id";
		}
		else
		{
			if ( !empty($qv['attribute_id']) ) 
			{
				if ( is_array( $qv['attribute_id'] ) ) 
					$attribute_id = $qv['attribute_id'];			
				elseif ( is_string($qv['attribute_id']) && !empty($qv['attribute_id']) )
				{
					if ( $qv['attribute_id'] != 'all' )
						$attribute_id = array_map( 'intval', explode( ',', $qv['attribute_id'] ) );
				}
				elseif ( is_numeric($qv['attribute_id']) )
					$attribute_id = array($qv['attribute_id']);
					
				if ( !empty($attribute_id) ) 
					$this->query_where .= " AND ".usam_get_table_db('product_attribute_options').".attribute_id IN (".implode( ",", $attribute_id ).")";	
			}	
			if ( isset($qv['attribute_id__not_in']) ) 
			{
				$attribute_id__not_in = (array)$qv['attribute_id__not_in'];
				$this->query_where .= " AND ".usam_get_table_db('product_attribute_options').".attribute_id NOT IN (".implode(",", $attribute_id__not_in ).")";
			}
		}		
		if ( !empty($qv['value']) ) 
		{		
			$value = implode( "','",  (array)$qv['value'] );
			$this->query_where .= " AND ".usam_get_table_db('product_attribute_options').".value IN ('".esc_sql($value)."')";
		}
		do_action_ref_array( 'usam_pre_product_attribute_values_query', array( &$this ) );
	}
	
	public function handling_data_types( $data )
	{
		foreach ($this->db_fields as $column => $type ) 
		{			
			if ( $type == 'd' && isset($data->$column) )	
				$data->$column = (int)$data->$column;
		}		
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
			$this->total = (int)$wpdb->get_var( apply_filters( 'found_product_attribute_values_query', 'SELECT FOUND_ROWS()' ) );			
		
		if ( $qv['cache_term_meta'] )
		{
			$ids = array();
			foreach ( $this->results as $result ) 
			{
				if ( !isset($result->attribute_id) )
					break;
				$ids[] = $result->attribute_id; 					
			}			
			if ( $ids )
				usam_update_cache( $ids, [USAM_TABLE_TERM_META => 'term_meta'], 'term_id' );
		}		
		$r = [];
		if ( 'attribute_id=>data' == $qv['fields'] ) 
		{					
			foreach ( $this->results as $key => $result ) 
			{
				$r[$result->attribute_id][] = $result;
			}			
			$this->results = $r;
		}
		elseif ( 'id=>value' == $qv['fields'] ) 
		{					
			foreach ( $this->results as $key => $result ) 
			{
				$r[$result->id] = $result->value;
			}			
			$this->results = $r;
		}			
	}

	/**
	 * Retrieve query variable.
	 */
	public function get( $query_var ) 
	{
		if ( isset($this->query_vars[$query_var]) )
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
				$searches[] = $wpdb->prepare( usam_get_table_db('product_attribute_options').".$col = %s", $string );
			elseif ( 'value' == $col )
				$searches[] = $wpdb->prepare( usam_get_table_db('product_attribute_options').".value LIKE LOWER ('%s')", $string.'%' );
			else 
				$searches[] = $wpdb->prepare( usam_get_table_db('product_attribute_options').".$col LIKE LOWER ('%s')", $like );
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
		if ( in_array( $orderby, ['slug', 'name', 'id', 'sort'] ) )
		{
			$_orderby = usam_get_table_db('product_attribute_options').'.'.$orderby;
		}		
		elseif ( 'COUNT' == $orderby ) 
		{
			$_orderby = 'COUNT(*)';
		} 		
		elseif ( 'include' === $orderby  )
		{
			if ( !empty( $this->query_vars['include'] ) )
			{
				$include = wp_parse_id_list( $this->query_vars['include'] );
				$include_sql = implode( ',', $include );
				$_orderby = "FIELD( ".usam_get_table_db('product_attribute_options').".id, $include_sql )";
			} 				
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

function usam_get_product_attribute_values( $args = [] )
{
	$args['count_total'] = false;
	$query = new USAM_Product_Attribute_Values_Query( $args );	
	$result = $query->get_results();		
	return $result;
}