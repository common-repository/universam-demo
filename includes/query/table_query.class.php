<?php
// Класс работы с дополнительными таблицами 
class USAM_Table_Query 
{	
	public $queries = array();	
	public $relation;
	public $meta_table;
	public $meta_id_column;
	public $primary_table;
	public $primary_id_column;
	protected $table_aliases = array();
	protected $clauses = array();
	protected $has_or_relation = false;

	/**
	 * Constructor.
	 *
	 * @since 3.2.0
	 * @since 4.2.0 Introduced support for naming query clauses by associative array keys.
	 *
	 *
	 * @param array $meta_query {
	 *     Array of meta query clauses. When first-order clauses or sub-clauses use strings as
	 *     their array keys, they may be referenced in the 'orderby' parameter of the parent query.
	 *
	 *     @type string $relation Optional. The MySQL keyword used to join
	 *                            the clauses of the query. Accepts 'AND', or 'OR'. Default 'AND'.
	 *     @type array {
	 *         Optional. An array of first-order clause parameters, or another fully-formed meta query.
	 *
	 *         @type string $key     Meta key to filter by.
	 *         @type string $value   Meta value to filter by.
	 *         @type string $compare MySQL operator used for comparing the $value. Accepts '=',
	 *                               '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE',
	 *                               'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'REGEXP',
	 *                               'NOT REGEXP', 'RLIKE', 'EXISTS' or 'NOT EXISTS'.
	 *                               Default is 'IN' when `$value` is an array, '=' otherwise.
	 *         @type string $type    MySQL data type that the meta_value column will be CAST to for
	 *                               comparisons. Accepts 'NUMERIC', 'BINARY', 'CHAR', 'DATE',
	 *                               'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', or 'UNSIGNED'.
	 *                               Default is 'CHAR'.
	 *     }
	 * }
	 */
	public function __construct( $meta_query = false ) 
	{
		if ( !$meta_query )
			return;

		if ( isset($meta_query['relation'] ) && strtoupper( $meta_query['relation'] ) == 'OR' ) {
			$this->relation = 'OR';
		} else {
			$this->relation = 'AND';
		}
		$this->queries = $this->sanitize_query( $meta_query );		
	}

	/**
	 * Ensure the 'meta_query' argument passed to the class constructor is well-formed.
	 * Eliminates empty items and ensures that a 'relation' is set.	
	 */
	public function sanitize_query( $queries ) 
	{
		$clean_queries = array();

		if ( ! is_array( $queries ) ) {
			return $clean_queries;
		}

		foreach ( $queries as $key => $query ) 
		{			
			if ( 'relation' === $key ) {
				$relation = $query;
			} 
			elseif ( ! is_array( $query ) ) {
				continue;			
			} 
			elseif ( $this->is_first_order_clause( $query ) ) 
			{ 
				if ( isset($query['value'] ) && array() === $query['value'] ) {
					unset( $query['value'] );
				}
				$clean_queries[ $key ] = $query;			
			} 
			else
			{ 
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
		else 
		{
			$clean_queries['relation'] = 'AND';
		} 		
		return $clean_queries;
	}

	/**
	 * Determine whether a query clause is first-order.
	 *
	 * A first-order meta query clause is one that has either a 'key' or
	 * a 'value' array key.	
	 */
	protected function is_first_order_clause( $query ) {
		return isset($query['key'] ) || isset($query['value'] );
	}

	/**
	 * Constructs a meta query based on 'meta_*' query vars
	 */
	public function parse_query_vars( $qv ) 
	{
		$meta_query = array();
		/*
		 * For orderby=meta_value to work correctly, simple query needs to be
		 * first (so that its table join is against an unaliased meta table) and
		 * needs to be its own clause (so it doesn't interfere with the logic of
		 * the rest of the meta_query).
		 */
		$primary_meta_query = array();
		foreach ( array( 'key', 'compare', 'type' ) as $key ) 
		{
			if ( !empty( $qv["table_$key"] ) ) {
				$primary_meta_query[ $key ] = $qv["table_$key"];
			}
		}
		// WP_Query sets 'table_value' = '' by default.
		if ( isset($qv['table_value'] ) && '' !== $qv['table_value'] && ( ! is_array( $qv['table_value'] ) || $qv['table_value'] ) ) {
			$primary_meta_query['value'] = $qv['table_value'];
		}
		$existing_meta_query = isset($qv['table_query'] ) && is_array( $qv['table_query'] ) ? $qv['table_query'] : array();

		if ( !empty( $primary_meta_query ) && !empty( $existing_meta_query ) ) {
			$meta_query = array(
				'relation' => 'AND',
				$primary_meta_query,
				$existing_meta_query,
			);
		} elseif ( !empty( $primary_meta_query ) ) {
			$meta_query = array(
				$primary_meta_query,
			);
		} elseif ( !empty( $existing_meta_query ) ) {
			$meta_query = $existing_meta_query;
		}

		$this->__construct( $meta_query );
	}

	/**
	 * Return the appropriate alias for the given meta type if applicable.
	 */
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

	/**
	 * Generates SQL clauses to be appended to a main query.		
	 */
	public function get_sql( $type, $meta_table, $primary_table, $primary_id_column, $context = null ) 
	{
		$this->table_aliases = array();

		$this->meta_table     = $meta_table;
		if ( !empty($type) )
			$this->meta_id_column = sanitize_key( $type . '_id' );
		else
			$this->meta_id_column = 'id';

		$this->primary_table     = $primary_table;
		$this->primary_id_column = $primary_id_column;

		$sql = $this->get_sql_clauses();
		
		if ( false !== strpos( $sql['join'], 'LEFT JOIN' ) ) {
			$sql['join'] = str_replace( 'INNER JOIN', 'LEFT JOIN', $sql['join'] );
		}

		/**
		 * Filters the meta query's generated SQL.
		 */
		return apply_filters_ref_array( 'get_meta_sql', array( $sql, $this->queries, $type, $primary_table, $primary_id_column, $context ) );
	}

	/**
	 * Generate SQL clauses to be appended to a main query.
	 * }
	 */
	protected function get_sql_clauses() 
	{		
		$queries = $this->queries;		
		$sql = $this->get_sql_for_query( $queries );

		if ( !empty( $sql['where'] ) ) {
			$sql['where'] = ' AND ' . $sql['where'];
		}

		return $sql;
	}

	/**
	 * Generate SQL clauses for a single query array.
	 * }
	 */
	protected function get_sql_for_query( &$query, $depth = 0 ) 
	{
		$sql_chunks = array(
			'join'  => array(),
			'where' => array(),
		);

		$sql = array(
			'join'  => '',
			'where' => '',
		);

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
					$clause_sql = $this->get_sql_for_clause( $clause, $query, $key );

					$where_count = count( $clause_sql['where'] );
					if ( ! $where_count ) {
						$sql_chunks['where'][] = '';
					} elseif ( 1 === $where_count ) {
						$sql_chunks['where'][] = $clause_sql['where'][0];
					} else {
						$sql_chunks['where'][] = '( ' . implode( ' AND ', $clause_sql['where'] ) . ' )';
					}

					$sql_chunks['join'] = array_merge( $sql_chunks['join'], $clause_sql['join'] );
				// This is a subquery, so we recurse.
				} 
				else 
				{
					$clause_sql = $this->get_sql_for_query( $clause, $depth + 1 );

					$sql_chunks['where'][] = $clause_sql['where'];
					$sql_chunks['join'][]  = $clause_sql['join'];
				}
			}
		}
		// Filter to remove empties.
		$sql_chunks['join']  = array_filter( $sql_chunks['join'] );
		$sql_chunks['where'] = array_filter( $sql_chunks['where'] );

		if ( empty( $relation ) ) {
			$relation = 'AND';
		}

		// Filter duplicate JOIN clauses and combine into a single string.
		if ( !empty( $sql_chunks['join'] ) ) {
			$sql['join'] = implode( ' ', array_unique( $sql_chunks['join'] ) );
		}

		// Generate a single WHERE clause with proper brackets and indentation.
		if ( !empty( $sql_chunks['where'] ) ) {
			$sql['where'] = '( ' . "\n  " . $indent . implode( ' ' . "\n  " . $indent . $relation . ' ' . "\n  " . $indent, $sql_chunks['where'] ) . "\n" . $indent . ')';
		}
		return $sql;
	}

	/**
	 * Generate SQL JOIN and WHERE clauses for a first-order query clause.
	 */
	public function get_sql_for_clause( &$clause, $parent_query, $clause_key = '' ) 
	{
		global $wpdb;

		$sql_chunks = array(
			'where' => array(),
			'join' => array(),
		);

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
		$join = '';
		
		$alias = $this->find_compatible_table_alias( $clause, $parent_query );		
		if ( false === $alias ) 
		{			
			$i = count( $this->table_aliases );
			$alias = $i ? 'mt' . $i : $this->meta_table;

			// JOIN clauses for NOT EXISTS have their own syntax.
			if ( 'NOT EXISTS' === $meta_compare ) 
			{
				$join .= " LEFT JOIN $this->meta_table";
				$join .= $i ? " AS $alias" : '';
				$join .= " ON ($this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column )";

			// All other JOIN clauses.
			} 
			else 
			{
				$join .= " INNER JOIN $this->meta_table";
				$join .= $i ? " AS $alias" : '';
				$join .= " ON ( $this->primary_table.$this->primary_id_column = $alias.$this->meta_id_column )";
			}

			$this->table_aliases[] = $alias;
			$sql_chunks['join'][] = $join;
		}

		// Save the alias to this clause, for future siblings to find.
		$clause['alias'] = $alias;

		// Determine the data type.
		$_meta_type = isset($clause['type'] ) ? $clause['type'] : '';
		$meta_type  = $this->get_cast_for_type( $_meta_type );
		$clause['cast'] = $meta_type;

		// Fallback for clause keys is the table alias. Key must be a string.
		if ( is_int( $clause_key ) || ! $clause_key ) {
			$clause_key = $clause['alias'];
		}

		// Ensure unique clause keys, so none are overwritten.
		$iterator = 1;
		$clause_key_base = $clause_key;
		while ( isset($this->clauses[ $clause_key ] ) ) {
			$clause_key = $clause_key_base . '-' . $iterator;
			$iterator++;
		}

		// Store the clause in our flat array.
		$this->clauses[ $clause_key ] =& $clause;
	
		// meta_value.
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

			switch ( $meta_compare ) {
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
				$key = $clause['key'];
				if ( 'CHAR' === $meta_type ) 				
					$sql_chunks['where'][] = "{$alias}.{$key} {$meta_compare} {$where}";
				else 
					$sql_chunks['where'][] = "CAST({$alias}.{$key} AS {$meta_type}) {$meta_compare} {$where}";
			}
		}	
		if ( 1 < count( $sql_chunks['where'] ) ) {
			$sql_chunks['where'] = array( '( ' . implode( ' AND ', $sql_chunks['where'] ) . ' )' );
		}
		return $sql_chunks;
	}

	public function get_clauses() 
	{
		return $this->clauses;
	}

	/**
	 * Identify an existing table alias that is compatible with the current
	 * query clause.	
	 */
	protected function find_compatible_table_alias( $clause, $parent_query ) 
	{
		$alias = false;

		foreach ( $parent_query as $sibling ) 
		{			
			// If the sibling has no alias yet, there's nothing to check.
			if ( empty( $sibling['alias'] ) ) {
				continue;
			}

			// We're only interested in siblings that are first-order clauses.
			if ( ! is_array( $sibling ) || ! $this->is_first_order_clause( $sibling ) ) {
				continue;
			}

			$compatible_compares = array();

			// Clauses connected by OR can share joins as long as they have "positive" operators.
			if ( 'OR' === $parent_query['relation'] ) {
				$compatible_compares = array( '=', 'IN', 'BETWEEN', 'LIKE', 'REGEXP', 'RLIKE', '>', '>=', '<', '<=' );

			// Clauses joined by AND with "negative" operators share a join only if they also share a key.
			} elseif ( isset($sibling['key'] ) && isset($clause['key'] ) && $sibling['key'] === $clause['key'] ) {
				$compatible_compares = array( '!=', 'NOT IN', 'NOT LIKE' );
			}

			$clause_compare  = strtoupper( $clause['compare'] );
			$sibling_compare = strtoupper( $sibling['compare'] );
			if ( in_array( $clause_compare, $compatible_compares ) && in_array( $sibling_compare, $compatible_compares ) ) {
				$alias = $sibling['alias'];
				break;
			}
		}		
		return apply_filters( 'meta_query_find_compatible_table_alias', $alias, $clause, $parent_query, $this ) ;
	}

	/**
	 * Checks whether the current query has any OR relations.	
	 */
	public function has_or_relation() {
		return $this->has_or_relation;
	}
}