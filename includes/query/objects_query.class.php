<?php
/**
 * Класс работы с таблицей объектов
 */
class USAM_Object_Query 
{
	public $queries = array();
	public $relation;
	private static $no_results = array( 'join' => array( '' ), 'where' => array( '0 = 1' ) );
	protected $table_aliases = array();
	public $queried_terms = array();
	public $primary_table;
	public $primary_id_column;
	public $object_table;
	public $object_id_column;

	/**
	 * Constructor.
	 * @since 3.1.0
	 * @since 4.1.0 Added support for `$operator` 'NOT EXISTS' and 'EXISTS' values.
	 *
	 * @param array $tax_query {
	 *     Array of object_type query clauses.
	 *
	 *     @type string $relation Optional. The MySQL keyword used to join
	 *                            the clauses of the query. Accepts 'AND', or 'OR'. Default 'AND'.
	 *     @type array {
	 *         Optional. An array of first-order clause parameters, or another fully-formed tax query.
	 *
	 *         @type string           $object_type         Taxonomy being queried. Optional when field=term_taxonomy_id.
	 *         @type string|int|array $object_id            Term or object_id to filter by.
	 *         @type string           $field            Field to match $object_id against. Accepts 'term_id', 'slug',
	 *                                                 'name', or 'term_taxonomy_id'. Default: 'term_id'.
	 *         @type string           $operator         MySQL operator to be used with $object_id in the WHERE clause.
	 *                                                  Accepts 'AND', 'IN', 'NOT IN', 'EXISTS', 'NOT EXISTS'.
	 *                                                  Default: 'IN'.
	 *         @type bool             $include_children Optional. Whether to include child object_id.
	 *                                                  Requires a $object_type. Default: true.
	 *     }
	 * }
	 */
	public function __construct( $object_query ) 
	{
		if ( isset($object_query['relation'] ) )
			$this->relation = $this->sanitize_relation( $object_query['relation'] );
		else 
			$this->relation = 'AND';
		
		$this->queries = $this->sanitize_query( $object_query );
	}

	/**
	 * Ensure the 'object_query' argument passed to the class constructor is well-formed.
	 *
	 * Ensures that each query-level clause has a 'relation' key, and that
	 * each first-order clause contains all the necessary keys from `$defaults`.
	 *
	 * @since 4.1.0
	 *
	 * @param array $queries Array of queries clauses.
	 * @return array Sanitized array of query clauses.
	 */
	public function sanitize_query( $queries ) 
	{
		$cleaned_query = array();

		$defaults = array(
			'object_type' => '',
			'object_id' => array(),		
			'operator' => 'IN',
			'include_children' => true,
		);
		foreach ( $queries as $key => $query ) 
		{
			if ( 'relation' === $key ) 
				$cleaned_query['relation'] = $this->sanitize_relation( $query );	
			elseif ( self::is_first_order_clause( $query ) ) 
			{
				$cleaned_clause = array_merge( $defaults, $query );
				$cleaned_clause['object_id'] = (array) $cleaned_clause['object_id'];
				$cleaned_query[] = $cleaned_clause;

				/*
				 * Keep a copy of the clause in the flate
				 * $queried_terms array, for use in WP_Query.
				 */
				if ( !empty($cleaned_clause['object_type']) && 'NOT IN' !== $cleaned_clause['operator'] && 'IN' !== $cleaned_clause['operator'] ) 
				{
					$object_type = $cleaned_clause['object_type'];
					if ( ! isset($this->queried_terms[ $object_type ] ) ) {
						$this->queried_terms[ $object_type ] = array();
					}
					/*
					 * Backward compatibility: Only store the first 'object_id' and 'field' found for a given object_type.
					 */
					if ( !empty( $cleaned_clause['object_id'] ) && ! isset($this->queried_terms[ $object_type ]['object_id'] ) ) {
						$this->queried_terms[ $object_type ]['object_id'] = $cleaned_clause['object_id'];
					}
				}
			// Otherwise, it's a nested query, so we recurse.
			} 
			elseif ( is_array( $query ) ) {
				$cleaned_subquery = $this->sanitize_query( $query );

				if ( !empty( $cleaned_subquery ) ) 
				{	// All queries with children must have a relation.
					if ( ! isset($cleaned_subquery['relation'] ) ) {
						$cleaned_subquery['relation'] = 'AND';
					}
					$cleaned_query[] = $cleaned_subquery;
				}
			}
		}		
		return $cleaned_query;
	}

	/**
	 * Sanitize a 'relation' operator.
	 *
	 * @since 4.1.0
	 *
	 * @param string $relation Raw relation key from the query argument.
	 * @return string Sanitized relation ('AND' or 'OR').
	 */
	public function sanitize_relation( $relation ) {
		if ( 'OR' === strtoupper( $relation ) ) {
			return 'OR';
		} else {
			return 'AND';
		}
	}

	/**
	 * Determine whether a clause is first-order.
	 *
	 * A "first-order" clause is one that contains any of the first-order
	 * clause keys ('object_id', 'object_type', 'include_children', 'field',
	 * 'operator'). An empty clause also counts as a first-order clause,
	 * for backward compatibility. Any clause that doesn't meet this is
	 * determined, by process of elimination, to be a higher-order query.
	 *
	 * @since 4.1.0
	 *
	 * @static
	 *
	 * @param array $query Tax query arguments.
	 * @return bool Whether the query clause is a first-order clause.
	 */
	protected static function is_first_order_clause( $query ) 
	{
		return is_array( $query ) && ( empty( $query ) || array_key_exists( 'object_id', $query ) || array_key_exists( 'object_type', $query ) || array_key_exists( 'include_children', $query ) || array_key_exists( 'operator', $query ) );
	}

	/**
	 * Generates SQL clauses to be appended to a main query.
	 * }
	 */
	public function get_sql( $primary_table, $object_table, $primary_id_column, $object_id_column ) 
	{
		$this->primary_table = $primary_table;
		$this->primary_id_column = $primary_id_column;
		
		$this->object_table = $object_table;
		$this->object_id_column = $object_id_column;

		return $this->get_sql_clauses();
	}

	/**
	 * Generate SQL clauses to be appended to a main query.
	 *
	 * Called by the public WP_Tax_Query::get_sql(), this method
	 * is abstracted out to maintain parity with the other Query classes.
	 *
	 * @since 4.1.0
	 *
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to the main query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_clauses() {
		/*
		 * $queries are passed by reference to get_sql_for_query() for recursion.
		 * To keep $this->queries unaltered, pass a copy.
		 */
		$queries = $this->queries;
		$sql = $this->get_sql_for_query( $queries );

		if ( !empty( $sql['where'] ) ) {
			$sql['where'] = ' AND ' . $sql['where'];
		}

		return $sql;
	}

	/**
	 * Generate SQL clauses for a single query array.
	 *
	 * If nested subqueries are found, this method recurses the tree to
	 * produce the properly nested SQL.
	 *
	 * @since 4.1.0
	 *
	 * @param array $query Query to parse (passed by reference).
	 * @param int   $depth Optional. Number of tree levels deep we currently are.
	 *                     Used to calculate indentation. Default 0.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a single query array.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	protected function get_sql_for_query( &$query, $depth = 0 ) 
	{
		$sql_chunks = array( 'join'  => array(), 'where' => array(), );
		$sql = array( 'join'  => '', 'where' => '',	);

		$indent = '';
		for ( $i = 0; $i < $depth; $i++ ) {
			$indent .= "  ";
		}

		foreach ( $query as $key => &$clause ) 
		{
			if ( 'relation' === $key )
				$relation = $query['relation'];
			elseif ( is_array( $clause ) ) 
			{
				if ( $this->is_first_order_clause( $clause ) ) 
				{				
					$clause_sql = $this->get_sql_for_clause( $clause, $query );
					$where_count = count( $clause_sql['where'] );
					if ( ! $where_count ) {
						$sql_chunks['where'][] = '';
					} elseif ( 1 === $where_count ) {
						$sql_chunks['where'][] = $clause_sql['where'][0];
					} else {
						$sql_chunks['where'][] = '( ' . implode( ' AND ', $clause_sql['where'] ) . ' )';
					}

					$sql_chunks['join'] = array_merge( $sql_chunks['join'], $clause_sql['join'] );
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
	 * Generate SQL JOIN and WHERE clauses for a "first-order" query clause.
	 *
	 * @since 4.1.0
	 *
	 * @global wpdb $wpdb The WordPress database abstraction object.
	 *
	 * @param array $clause       Query clause (passed by reference).
	 * @param array $parent_query Parent query array.
	 * @return array {
	 *     Array containing JOIN and WHERE SQL clauses to append to a first-order query.
	 *
	 *     @type string $join  SQL fragment to append to the main JOIN clause.
	 *     @type string $where SQL fragment to append to the main WHERE clause.
	 * }
	 */
	public function get_sql_for_clause( &$clause, $parent_query ) 
	{
		global $wpdb;

		$sql = array( 'where' => array(), 'join'  => array(), );

		$join = $where = '';
		$this->clean_query( $clause );

		$object_id = is_array($clause['object_id']) ? implode( ',', $clause['object_id']):$clause['object_id'];
		$object_type =  is_array($clause['object_type']) ? implode( "','", $clause['object_type']):$clause['object_type'];
		$operator = strtoupper( $clause['operator'] );		

		/*
		 * Перед созданием другого соединения в таблице, посмотрите, есть ли в этом разделе отдельный брат с существующим соединением, которое можно использовать совместно.
		 */
		$alias = $this->find_compatible_table_alias( $clause, $parent_query );
		if ( false === $alias ) 
		{
			$i = count( $this->table_aliases ); 
			$alias = $i ? 'tt' . $i : $this->object_table;			
			$this->table_aliases[] = $alias;			
			$clause['alias'] = $alias;			
			if ( 'NOT EXISTS' === $operator ) 
			{
				$join .= " LEFT JOIN $this->object_table";		
			} 
			else 
			{
				$join .= " INNER JOIN $this->object_table";				
			}
			$join .= $i ? " AS $alias" : '';
			$join .= " ON ($this->primary_table.$this->primary_id_column = $alias.$this->object_id_column )";
		}				
		if ( 'IN' == $operator || 'NOT IN' == $operator ) 
		{					
			if ( !empty($object_id) )
			{
				$where .= "$alias.object_id $operator ($object_id)";
				if ( !empty($object_type) )
					$where .= " AND ";
			}
			if ( !empty($object_type) )
				$where .= "$alias.object_type IN ('$object_type')";			
		} 		
		elseif ( 'AND' == $operator ) 
		{
			if ( empty( $object_id ) ) {
				return $sql;
			}
			$join = '';
			$where = '';
		} 
		elseif ( 'EXISTS' === $operator )
		{
			$where = "$alias.object_id $operator ($object_id)";
			if ( !empty($object_type) )
				$where .= " AND $alias.object_type IN ('$object_type')";
		}
		elseif ( 'NOT EXISTS' === $operator )
		{			
			$where = "";
		}
		$sql['join'][]  = $join;
		$sql['where'][] = $where;	
		return $sql;
	}

	/**
	 * Identify an existing table alias that is compatible with the current query clause.
	 *
	 * We avoid unnecessary table joins by allowing each clause to look for
	 * an existing table alias that is compatible with the query that it
	 * needs to perform.
	 *
	 * An existing alias is compatible if (a) it is a sibling of `$clause`
	 * (ie, it's under the scope of the same relation), and (b) the combination
	 * of operator and relation between the clauses allows for a shared table
	 * join. In the case of WP_Tax_Query, this only applies to 'IN'
	 * clauses that are connected by the relation 'OR'.
	 *
	 * @since 4.1.0
	 *
	 * @param array       $clause       Query clause.
	 * @param array       $parent_query Parent query of $clause.
	 * @return string|false Table alias if found, otherwise false.
	 */
	protected function find_compatible_table_alias( $clause, $parent_query ) 
	{
		$alias = false;

		// Sanity check. Only IN queries use the JOIN syntax .
		if ( ! isset($clause['operator'] ) || 'IN' !== $clause['operator'] ) {
			return $alias;
		}
		// Since we're only checking IN queries, we're only concerned with OR relations.
		if ( ! isset($parent_query['relation'] ) || 'OR' !== $parent_query['relation'] ) {
			return $alias;
		}
		$compatible_operators = array( 'IN' );
		foreach ( $parent_query as $sibling ) 
		{
			if ( ! is_array( $sibling ) || ! $this->is_first_order_clause( $sibling ) ) {
				continue;
			}

			if ( empty( $sibling['alias'] ) || empty( $sibling['operator'] ) ) {
				continue;
			}

			// The sibling must both have compatible operator to share its alias.
			if ( in_array( strtoupper( $sibling['operator'] ), $compatible_operators ) ) {
				$alias = $sibling['alias'];
				break;
			}
		}
		return $alias;
	}

	/**
	 * Validates a single query.
	 *
	 * @since 3.2.0
	 *
	 * @param array $query The single query. Passed by reference.
	 */
	private function clean_query( &$query )
	{
		if ( empty( $query['object_type'] ) ) 
		{						
			$query['include_children'] = false;
		} 		
		$query['object_id'] = array_unique( (array) $query['object_id'] );		
	}
}
