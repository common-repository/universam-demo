<?php
// Класс работы с товарами корзины
class USAM_Products_Basket_Query
{
	public $query_vars = array();
	private $results;
	private $total = 0;
	public $meta_query = false;
	public $request;

	private $compat_fields = array( 'results' );

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
		if ( $query !== null ) 
		{
			$this->prepare_query( $query );
			$this->query();
		}
	}

	public static function fill_query_vars( $args ) 
	{			
		$defaults = array(			
			'id'  => '',				
			'active' => 1, 
			'type' => '',			
			'product' => '',		
			'product__in' => array(),
			'product__not_in' => array(),						
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
			'fields' => 'all',			
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
		do_action( 'usam_pre_get_products_basket', $this );
		
		$qv =& $this->query_vars;
		$qv =  $this->fill_query_vars( $qv );		
		
		$join = array();	

		if ( 'all' == $qv['fields'] ) 		
			$this->query_fields = USAM_TABLE_PRODUCTS_BASKET.".*";
		else
		{
			if ( !is_array( $qv['fields'] ) )				
				$fields = array( $qv['fields'] );
			else
				$fields = $qv['fields'];
			
			$fields = array_unique( $fields );

			$this->query_fields = array();
			foreach ( $fields as $field ) 
			{	
				if ( $field == 'count' )
					$this->query_fields[] = "COUNT(*) AS count";			
				else
				{
					$field = 'id' === $field ? 'id' : sanitize_key( $field );
					$this->query_fields[] = USAM_TABLE_PRODUCTS_BASKET.".$field";
				}
			}
			$this->query_fields = implode( ',', $this->query_fields );
		} 
		
		if ( isset($qv['count_total'] ) && $qv['count_total'] )
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;

		
		$this->query_join = implode( ' ', $join );

		$this->query_from = "FROM ".USAM_TABLE_PRODUCTS_BASKET;
		$this->query_where = "WHERE 1=1";			
	
		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( !empty( $qv['include'] ) )
			$include = wp_parse_id_list( $qv['include'] );
		else
			$include = false;
		
		$product = array();
		if ( isset($qv['product'] ) ) 
		{
			if ( is_array( $qv['product'] ) ) 
				$product = $qv['product'];			
			elseif ( is_string( $qv['product'] ) && !empty( $qv['product'] ) )
				$product = array_map( 'trim', explode( ',', $qv['product'] ) );
			elseif ( is_numeric( $qv['product'] ) )
				$product = array($qv['product']);
		}
		$product__in = array();
		if ( isset($qv['product__in'] ) ) {
			$product__in = (array) $qv['product__in'];
		}
		$product__not_in = array();
		if ( isset($qv['product__not_in'] ) ) {
			$product__not_in = (array) $qv['product__not_in'];
		}
		if ( !empty( $product ) ) 
		{
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_BASKET.".product_id IN ('".implode( "','",  $product )."')";		
		}		
		if ( !empty($product__not_in) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_BASKET.".product_id NOT IN ('".implode( "','",  $product__not_in )."')";
		}
		if ( !empty( $product__in ) ) 
		{			
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_BASKET.".product_id IN ('".implode( "','",  $product__in )."')";
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
			$ordersby = ['id' => $order];
		elseif ( is_array($qv['orderby']) ) 
			$ordersby = $qv['orderby'];
		else 
		{// Значения 'orderby' могут быть списком, разделенным запятыми или пробелами
			$ordersby = preg_split( '/[,\s]+/', $qv['orderby'] );
		}		
		$ordersby = apply_filters( 'usam_products_basket_ordersby', $ordersby, $this );	
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
			$orderby_array[] = USAM_TABLE_PRODUCTS_BASKET.".id $order";
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
				$search_columns = array_intersect( $qv['search_columns'], ['id', 'name', 'product_id', 'sku', 'price', 'cart_id']);
			if ( ! $search_columns ) 
			{
				if ( is_numeric($search) )
					$search_columns = array('id','product_id','cart_id' );				
				else
					$search_columns = array('name', 'price', 'sku' );
			}	
			$search_columns = apply_filters( 'usam_products_basket_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}
		if ( !empty($include) ) 
		{	
			$ids = implode( ',', $include );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_BASKET.".id IN ($ids)";
		} 
		elseif ( !empty($qv['exclude']) ) 
		{
			$ids = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_BASKET.".id NOT IN ($ids)";
		}	
		if ( !empty( $qv['user_id'] ) ) 		
		{
			$this->query_join .=  "LEFT JOIN ".USAM_TABLE_USERS_BASKET." ON (".USAM_TABLE_USERS_BASKET.".id = ".USAM_TABLE_PRODUCTS_BASKET.".cart_id)";
			$ids = implode( ',', wp_parse_id_list( $qv['user_id'] ) );
			$this->query_where .= " AND ".USAM_TABLE_USERS_BASKET.".user_id IN ($ids)";
		}	
		if ( !empty($qv['cart_id']) ) 		
		{			
			$ids = implode( ',', wp_parse_id_list( $qv['cart_id'] ) );
			$this->query_where .= " AND ".USAM_TABLE_PRODUCTS_BASKET.".cart_id IN ($ids)";
		}
		do_action_ref_array( 'usam_pre_products_basket_query', array( &$this ) );
	}
	
	public function query()
	{
		global $wpdb;

		$qv =& $this->query_vars;

		$this->request = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_orderby $this->query_limit";

		if ( is_array( $qv['fields'] ) || 'all' == $qv['fields'] && $qv['number'] != 1 ) 		
			$this->results = $wpdb->get_results( $this->request );		
		elseif ( $qv['number'] == 1 && 'all' !== $qv['fields'] )		
			$this->results = $wpdb->get_var( $this->request );
		elseif ( $qv['number'] == 1 && 'all' == $qv['fields'] )		
			$this->results = $wpdb->get_row( $this->request );
		else 
			$this->results = $wpdb->get_col( $this->request );			
		
		if ( !$this->results )
			return;	
		
		if ( $qv['count_total'] )
			$this->total = $wpdb->get_var( apply_filters( 'found_products_basket_query', 'SELECT FOUND_ROWS()' ) );
		
		foreach ($this->results as $key => $result ) 
		{
			foreach (['id', 'cart_id', 'product_id', 'gift'] as $column ) 
			{			
				if ( isset($result->$column) )	
					$this->results[$key]->$column = (int)$result->$column;
			}
			foreach (['price', 'old_price', 'quantity'] as $column ) 
			{			
				if ( isset($result->$column) )	
					$this->results[$key]->$column = (float)$result->$column;
			}
		}
		/*if ( usam_is_multisite() && 'all' == $qv['fields'] )
		{
			$product_ids = []; 
			foreach ( $this->results as $result ) 
			{
				if ( isset($result->product_id) && isset($result->name) )
					$product_ids[] = $result->product_id;
				else
					break;
			}
			if ( $product_ids )
			{
				$products = $wpdb->get_results( "SELECT post_title, ID FROM ".$wpdb->posts." WHERE ID IN (".implode(',',$product_ids).")" ); 
				foreach ( $this->results as $key => $result ) 
				{
					foreach ( $products as $key2 => $product ) 
					{
						if ( $result->product_id == $product->ID )
						{
							$this->results[$key] = $product->post_title;
							unset($products[$key2]);
						}
					}
				}
			}				
		}*/			
		if ( 'all' == $qv['fields'] && $qv['cache_results'] )
		{
			if ( $qv['number'] == 1 )
			{
				$this->results->old_price = (float)$this->results->old_price;
				$this->results->price = (float)$this->results->price;
				wp_cache_set( $this->results->cart_id, $this->results, 'usam_cart_item_'.$this->results->product_id.'_'.$this->results->unit_measure );	
			}
			else
			{
				foreach ( $this->results as $result ) 
				{
					$result->old_price = (float)$result->old_price;
					$result->price = (float)$result->price;
					wp_cache_set( $result->cart_id, $result, 'usam_cart_item_'.$result->product_id.'_'.$result->unit_measure );						
				}
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

	protected function get_search_sql( $string, $cols, $wild = false ) 
	{
		global $wpdb;

		$searches = array();
		$leading_wild = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
		$like = $leading_wild . $wpdb->esc_like( $string ) . $trailing_wild;

		foreach ( $cols as $col ) 
		{
			if ( 'sku' == $orderby ) 
			{
				$product_id = usam_get_product_id_by_sku( $string );
				if ( !empty($product_id) )
					$searches[] = "product_id=$product_id";
			} 		
			elseif ( 'name' == $orderby ) 		
				$searches[] = $wpdb->prepare("name LIKE %s", $like );
			else 
				$searches[] = $wpdb->prepare( "$col = %s", $string );
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
		if ( in_array($orderby, ['id', 'name','date_insert', 'price', 'old_price', 'quantity']) )
		{
			$_orderby = USAM_TABLE_PRODUCTS_BASKET.'.'.$orderby;
		} 		
		elseif ( 'include' === $orderby && !empty( $this->query_vars['include'] ) )
		{
			$include = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby = "FIELD( ".USAM_TABLE_PRODUCTS_BASKET.".id, $include_sql )";
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

function usam_get_products_baskets( $args = array())
{	
	$args['count_total'] = false;
	$payments = new USAM_Products_Basket_Query( $args );	
	$results = $payments->get_results();		
	return $results;
}