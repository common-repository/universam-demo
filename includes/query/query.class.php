<?php
// Класс работы с дополнительными таблицами 
class USAM_Query 
{	
	public $queries = array();	
	public $relation;	
	public $primary_table;	
	protected $table_aliases = array();
	protected $clauses = array();
	protected $has_or_relation = false;

	public function __construct( $condition = false ) 
	{
		if ( !$condition )
			return;

		if ( isset($condition['relation'] ) && strtoupper( $condition['relation'] ) == 'OR' ) {
			$this->relation = 'OR';
		} else {
			$this->relation = 'AND';
		}

		$this->queries = $this->sanitize_query( $condition );
	}
	
	public function parse_query_vars( $qv ) 
	{
		$condition = array();
		$primary_meta_query = array();
		foreach ( array( 'col', 'compare', 'value' ) as $key ) 
		{
			if ( !empty( $qv[ "$key" ] ) ) {
				$primary_meta_query[ $key ] = $qv[ $key ];
			}
		}
		$existing_meta_query = isset($qv['condition'] ) && is_array( $qv['condition'] ) ? $qv['condition'] : array();
		if ( !empty( $primary_meta_query ) && !empty( $existing_meta_query ) ) 
		{
			$condition = array(	'relation' => 'AND', $primary_meta_query, $existing_meta_query );
		} 
		elseif ( !empty( $primary_meta_query ) ) 
		{
			$condition = array(	$primary_meta_query );
		} 
		elseif ( !empty( $existing_meta_query ) ) 
		{
			$condition = $existing_meta_query;
		}
		$this->__construct( $condition );
	}
	
	public function sanitize_query( $queries ) 
	{
		$clean_queries = array();

		if ( ! is_array( $queries ) ) {
			return $clean_queries;
		}

		foreach ( $queries as $key => $query ) {
			if ( 'relation' === $key ) {
				$relation = $query;

			} elseif ( ! is_array( $query ) ) {
				continue;

			// First-order clause.
			} elseif ( $this->is_first_order_clause( $query ) ) {
				if ( isset($query['value'] ) && array() === $query['value'] ) {
					unset( $query['value'] );
				}

				$clean_queries[ $key ] = $query;

			// Otherwise, it's a nested query, so we recurse.
			} else {
				$cleaned_query = $this->sanitize_query( $query );

				if ( !empty( $cleaned_query ) ) {
					$clean_queries[ $key ] = $cleaned_query;
				}
			}
		}

		if ( empty( $clean_queries ) ) {
			return $clean_queries;
		}

		// Sanitize the 'relation' key provided in the query.
		if ( isset($relation ) && 'OR' === strtoupper( $relation ) ) 
		{
			$clean_queries['relation'] = 'OR';
			$this->has_or_relation = true;	
		} 
		elseif ( 1 === count( $clean_queries ) )
		{
			$clean_queries['relation'] = 'OR';	
		} 
		else {
			$clean_queries['relation'] = 'AND';
		}
		return $clean_queries;
	}
	
	protected function is_first_order_clause( $query ) {
		return isset($query['key'] ) || isset($query['value'] );
	}
	
	public function get_sql( $primary_table ) 
	{		
		$this->primary_table     = $primary_table;
		$where = $this->get_sql_clauses();			
		return apply_filters_ref_array( 'usam_get_sql', array( $where, $this->queries, $primary_table ) );
	}
	
	
	protected function get_sql_clauses() 
	{		
		$queries = $this->queries;
		$where = $this->get_sql_for_query( $queries );

		if ( !empty( $where ) ) {
			$where = ' AND ' . $where;
		}
		return $where;
	}

	/**
	 * Generate SQL clauses for a single query array.
	 */
	protected function get_sql_for_query( &$query, $depth = 0 ) 
	{
		$indent = '';
		for ( $i = 0; $i < $depth; $i++ ) {
			$indent .= "  ";
		}

		foreach ( $query as $key => &$clause ) 
		{
			if ( 'relation' === $key ) 
			{
				$relation = $query['relation'];
			} 
			elseif ( is_array( $clause ) )
			{

				// This is a first-order clause.
				if ( $this->is_first_order_clause( $clause ) ) 
				{
					$sql_where = $this->get_sql_for_clause( $clause, $query, $key );
					$where_count = count( $sql_where );
					if ( ! $where_count ) {
						$where[] = '';
					} elseif ( 1 === $where_count ) {
						$where[] = $sql_where[0];
					} else {
						$where[] = '( ' . implode( ' AND ', $sql_where ) . ' )';
					}					
				} 
				else 
				{
					$sql_where = $this->get_sql_for_query( $clause, $depth + 1 );
					$where[] = $sql_where;					
				}
			}
		}				
		$where = array_filter( $where );

		if ( empty( $relation ) ) {
			$relation = 'AND';
		}
		$sql = '';
		if ( !empty( $where ) ) {
			$sql = '( ' . "\n  " . $indent . implode( ' ' . "\n  " . $indent . $relation . ' ' . "\n  " . $indent, $where ) . "\n" . $indent . ')';
		}		
		return $sql;
	}
	
	public function get_cast_for_type( $type = '' ) 
	{
		if ( empty( $type ) )
			return 'CHAR';

		$meta_type = strtoupper( $type );

		if ( ! preg_match( '/^(?:BINARY|CHAR|DATE|DATETIME|SIGNED|UNSIGNED|TIME|NUMERIC(?:\(\d+(?:,\s?\d+)?\))?|DECIMAL(?:\(\d+(?:,\s?\d+)?\))?)$/', $meta_type ) )
			return 'CHAR';

		if ( 'NUMERIC' == $meta_type )
			$meta_type = 'SIGNED';

		return $meta_type;
	}	
	
	public function get_sql_for_clause( &$clause, $parent_query, $clause_key = '' ) 
	{
		global $wpdb;

		$sql_where = array();

		if ( isset($clause['compare'] ) ) {
			$clause['compare'] = strtoupper( $clause['compare'] );
		} else {
			$clause['compare'] = isset($clause['value'] ) && is_array( $clause['value'] ) ? 'IN' : '=';
		}

		if ( ! in_array( $clause['compare'], array(
			'=', '!=', '>', '>=', '<', '<=',
			'LIKE', 'NOT LIKE',
			'IN', 'NOT IN',
			'BETWEEN', 'NOT BETWEEN',
			'EXISTS', 'NOT EXISTS',
			'REGEXP', 'NOT REGEXP', 'RLIKE'
		) ) ) {
			$clause['compare'] = '=';
		}

		$meta_compare = $clause['compare'];		
		
		$_meta_type = isset($clause['type'] ) ? $clause['type'] : '';
		$meta_type  = $this->get_cast_for_type( $_meta_type );
		$clause['cast'] = $meta_type;

		if ( array_key_exists( 'value', $clause ) ) 
		{
			$meta_value = $clause['value'];
			if ( in_array( $meta_compare, array( 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ) ) ) 
			{
				if ( ! is_array( $meta_value ) ) {
					$meta_value = preg_split( '/[,\s]+/', $meta_value );
				}
			} 
			else 
				$meta_value = trim( $meta_value );
			switch ( $meta_compare ) 
			{
				case 'IN' :
				case 'NOT IN' :
					$meta_compare_string = '(' . substr( str_repeat( ',%s', count( $meta_value ) ), 1 ) . ')';
					$where = $wpdb->prepare( $meta_compare_string, $meta_value );
					break;

				case 'BETWEEN' :
				case 'NOT BETWEEN' :
					$meta_value = array_slice( $meta_value, 0, 2 );
					$where = $wpdb->prepare( '%s AND %s', $meta_value );
					break;

				case 'LIKE' :
				case 'NOT LIKE' :
					$meta_value = '%' . $wpdb->esc_like( $meta_value ) . '%';
					$where = $wpdb->prepare( '%s', $meta_value );
				break;		
				case 'EXISTS' :
					$meta_compare = '=';
					$where = $wpdb->prepare( '%s', $meta_value );
				break;				
				case 'NOT EXISTS' :
					$where = '';
					break;

				default :
					$where = $wpdb->prepare( '%s', $meta_value );
				break;

			}			
			if ( $where ) 
			{			
				$key = $clause['col'];
				if ( 'CHAR' === $meta_type ) 				
					$sql_where[] = "{$this->primary_table}.{$key} {$meta_compare} {$where}";
				else 
					$sql_where[] = "CAST({$this->primary_table}.{$key} AS {$meta_type}) {$meta_compare} {$where}";					
			}
		}			
		if ( 1 < count( $sql_where ) ) {
			$sql_where = array( '( ' . implode( ' AND ', $sql_where ) . ' )' );
		}		
		return $sql_where;
	}
	
	
	public function sanitize_query16( $queries ) 
	{
		foreach ( $qv['conditions'] as $condition )
		{					
			$select = '';
			switch ( $condition['key'] )
			{					
				case 'key' :
				case 'order_id' :					
				case 'value' :
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
			) ) ) {
				$condition['compare'] = '=';
			}				
			$value = $condition['value'];
			
			if ( empty($condition['relation']) )
				$relation = 'AND';
			else
				$relation = $condition['relation'];
			
			$this->query_where .= " $relation ( $select ".$condition['compare']." '$value' ) ";
		}		
	}
	
	public function sanitize_query1( $queries ) 
	{
		foreach ( $qv['conditions'] as $condition )
		{					
			$select = '';
			switch ( $condition['key'] )
			{					
				case 'key' :
				case 'order_id' :					
				case 'value' :
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
			) ) ) {
				$condition['compare'] = '=';
			}				
			$value = $condition['value'];
			
			if ( empty($condition['relation']) )
				$relation = 'AND';
			else
				$relation = $condition['relation'];
			
			$this->query_where .= " $relation ( $select ".$condition['compare']." '$value' ) ";
		}		
	}
}