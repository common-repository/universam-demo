<?php
class USAM_Conditions_Query
{
	public $query_where = '';
	
	public function get_operators( $compare ) 
	{
		$compare = strtoupper( $compare );
		if ( !in_array($compare, ['=', '!=', '>', '>=', '<', '<=', 
			'LIKE', 'NOT LIKE', 
			'IN', 'NOT IN',
			'BETWEEN', 'NOT BETWEEN',
			'EXISTS', 'NOT EXISTS',
			'REGEXP', 'NOT REGEXP', 'RLIKE'
		] ) ) 
		{
			$compare = '=';
		}
		return $compare;
	}	
	
	public function get_cast_for_type( $type = '' )
	{
		if ( empty($type ) )
			return 'CHAR';

		$type = strtoupper( $type );
		if ( ! preg_match( '/^(?:BINARY|CHAR|DATE|DATETIME|SIGNED|UNSIGNED|TIME|NUMERIC(?:\(\d+(?:,\s?\d+)?\))?|DECIMAL(?:\(\d+(?:,\s?\d+)?\))?)$/', $type ) )
			return 'CHAR';

		if ( 'NUMERIC' == $type )
			$type = 'SIGNED';

		return $type;
	}
	
	public function get_sql_clauses(  $qv, $columns = [] ) 
	{
		$this->query_where = '';
		if ( !empty($qv['conditions']) ) 
		{ 
			$conditions = isset($qv['conditions']['key'])?array($qv['conditions']):$qv['conditions'];
			$query = $this->get_sql_for_query( $conditions, $columns );
			if ( $query )
				$this->query_where = ' AND '.$query;
		}
		return $this->query_where;
	}
	
	private function get_sql_for_query( $conditions, $columns )
	{ 
		$sql_chunks = [];		
		$relation = 'AND';				
		foreach ($conditions as $k => $condition)
		{				
			if ( $k === 'relation' ) 
			{
				$condition = strtoupper($condition);
				if ( $condition == 'OR' || $condition == 'AND' )
					$relation = $condition;	
			}
			elseif ( is_array($condition) )
			{
				if ( !isset($condition['key']) )
				{
					$sql = $this->get_sql_for_query( $condition, $columns );
					$sql_chunks[] = $sql;
				}
				else
				{
					$sql_chunks[] = $this->get_sql_for_clause( $condition, $columns );
				}
			}			
		}		
		$sql = '('.implode(' '.$relation.' ', $sql_chunks).')';
		return $sql;
	}
	
	public function get_sql_for_clause( &$condition, $columns ) 
	{		
		$select = $condition['key'];
		if ( !empty($columns) )
		{
			if( isset($columns[$condition['key']]) )
				$select = $columns[$condition['key']];
			else
				$select = $condition['key'];
		}		
		if ( !empty($condition['type']) )					
			$type = $this->get_cast_for_type( $condition['type'] );
		elseif ( $select == 'id' )
			$type = 'NUMERIC';
		else
			$type = 'CHAR';
						
		$compare = $this->get_operators( $condition['compare'] );
		
		if ( in_array($compare, ['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'], true) ) 
		{
			if ( !is_array($condition['value']) )
				$condition['value'] = preg_split( '/[,\s]+/', $condition['value'] );
		}
		else
			$condition['value'] = trim( $condition['value'] );
	
		switch ( $compare ) 
		{						 
			case 'LIKE' :		
			case 'NOT LIKE' :
				$value = "('%".$condition['value']."%')";			
			break;
			case 'IN' :					
			case 'NOT IN' :
				$value = "('".implode("','",$condition['value'])."')";
			break;
			case 'NOT BETWEEN' :
			case 'BETWEEN' :						
				if ( $type == 'SIGNED' )
					$value = $condition['value'][0].' AND '.$condition['value'][1];
				else
					$value = "'".$condition['value'][0]."' AND '".$condition['value'][1]."'";
			break;
			default:						
				if ( $type == 'SIGNED' )
					$value = $condition['value'];	
				else
					$value = "'".$condition['value']."'";
			break;					
		}		
		if ( $type == 'SIGNED' )
			$select = "CAST({$select} AS {$type})";				
		return "{$select} {$compare}{$value}";
	}		
}
?>