<?php
final class Filter_Processing  
{				
	private $data = [];
	function __construct( $data = [] ) 
	{
		$this->data = $data ? $data : $_REQUEST;						
	}
	
	public function get_filter_value( $key, $default = null, $type = '' ) 
	{ 
		$filter = [];
		if ( !empty($this->data['filter_id']) )
		{ // для конструктора отчетов
			require_once( USAM_FILE_PATH . '/admin/includes/filter.class.php' );
			$id = absint($this->data['filter_id']);	
			$class = new USAM_Filter( $id );
			$filter_data = $class->get_data();
			if ( $filter_data )
				$filter = $filter_data['setting']; 
		}
		if ( !empty($filter[$key]) )
			$select = $filter[$key];
		elseif( isset($this->data[$key]) )
		{								
			if ( is_array($this->data[$key]) )
				$select = stripslashes_deep($this->data[$key]);
			elseif ( stripos($this->data[$key], 'GMT') !== false )
				$select = stripslashes(str_replace('GMT ', 'GMT+', $this->data[$key]));
			elseif ( stripos($this->data[$key], ',') !== false )
				$select = explode(',',stripslashes($this->data[$key]));
			else
				$select = stripslashes($this->data[$key]);	
		}
		else
			$select = $default;		
		return $select;
	}
	
	public function get_date_interval( $period = '' ) 
	{ 
		$period = $this->get_filter_value( 'period', $period );			
		$date_from = $this->get_filter_value( 'date_from' );
		if( $date_from )
			$date_from = strtotime($date_from);
		else 
		{ 			
			switch ( $period ) 
			{					
				case 'today':
					$date_from = mktime(0, 0, 0, date("m") , date("d"), date("Y"));
				break;
				case 'yesterday':
					$date_from = mktime(0, 0, 0, date("m") , date("d")-1, date("Y"));
				break;
				case 'last_7_day':
				case 'week':
					$date_from = mktime(0, 0, 0, date("m") , date("d")-7, date("Y"));				
				break;
				case 'last_30_day':
					$date_from = mktime(0, 0, 0, date("m") , date("d")-30, date("Y"));				
				break;
				case 'last_60_day':
					$date_from = mktime(0, 0, 0, date("m") , date("d")-60, date("Y"));				
				break;
				case 'last_90_day':
					$date_from = mktime(0, 0, 0, date("m") , date("d")-60, date("Y"));				
				break;
				case 'last_365_day':
					$date_from = mktime(0, 0, 0, date("m") , date("d")-365, date("Y"));				
				break;				
				case 'last_1825_day':
					$date_from = mktime(0, 0, 0, date("m") , date("d")-1825, date("Y"));				
				break;	
				case 'last_3650_day':
					$date_from = mktime(0, 0, 0, date("m") , date("d")-3650, date("Y"));				
				break;			
				case 'month':
					$year = (int)$this->get_filter_value( 'year' );
					$month = (int)$this->get_filter_value( 'month' );
					if ( !$month )
						$month = date("m") - 1;
					if ( !$year )
						$year = date("Y") - 1;
					$date_from = mktime(0, 0, 0, $month, 1, $year);	
				break;	
				case 'year':					
					$year = (int)$this->get_filter_value( 'year' );						
					if ( !$year )
						$year = date("Y")-1;
					$date_from = mktime(0, 0, 0, 1 , 1, $year );		
				break;					
				case 'quarter':
					$year = (int)$this->get_filter_value( 'year' );
					if ( !$year )
						$year = date("Y")-1;
					$quarter = (int)$this->get_filter_value( 'quarter' );
					$date_from = usam_get_beginning_quarter( $quarter*3, $year );
				break;
				case 'current_quarter':
					$date_from = usam_get_beginning_quarter( date("m"), date("Y") );
				break;
				case 'current_week':
					$w = date("w");	
					switch ( $w ) 
					{					
						case 0:
							$day = 6;													
						break;	
						case 1:		
							$day = 7;								
						break;				
						default:
							$day = $w-1;												
						break;
					}					
					$date_from = mktime(0, 0, 0, date("m") , date("d")-$day, date("Y"));
				break;
				case 'current_month':
					$date_from = mktime(0, 0, 0, date("m"), 1, date("Y"));				
				break;							
				case 'current_year':
					$date_from = mktime(0, 0, 0, 1 , 1, date("Y"));
				break;
			}			
		}
		$date_to = $this->get_filter_value( 'date_to' );
		if( $date_to )
			$date_to = strtotime($date_to);
		else 
		{					
			switch ( $period ) 
			{					
				case 'today':
				case 'last_7_day':
				case 'last_30_day':
				case 'last_60_day':
				case 'last_90_day':
				case 'last_365_day':
				case 'last_1825_day':	
				case 'last_3650_day':				
					$date_to = mktime(23, 59, 59, date('m'),date('d'),date('Y'));
				break;			
				case 'yesterday':
					$date_to = mktime(23, 59, 59, date("m") , date("d")-1, date("Y"));
				break;			
				case 'month':
				case 'year':
					$date_to = strtotime("+1 {$period}s", $date_from)-1;				
				break;
				case 'week':				
					$date_to = strtotime("+7 days", $date_from);
				break;
				case 'quarter':
					$date_to = strtotime("+3 months", $date_from)-1;
				break;
				case 'current_week':
					$w = date("w");	
					switch ( $w ) 
					{					
						case 0:
							$day = 0;													
						break;	
						case 1:		
							$day = 7;								
						break;				
						default:
							$day = 7-$w;												
						break;
					}					
					$date_to = mktime(23, 59, 59, date("m") , date("d")+$day, date("Y"));
				break;
				case 'current_month':
					$number = cal_days_in_month(CAL_GREGORIAN, date("m"), date("Y"));
					$date_to = mktime(23, 59, 59, date("m"), $number, date("Y"));				
				break;
				case 'current_year':
					$date_to = mktime(23, 59, 59, 12, 31, date("Y"));
				break;
			}						
		}			
		return ['from' => $date_from, 'to' => $date_to];
	}	
	
	public function get_date_interval_for_query( $columns_search )
	{
		$date_query = [];
		foreach ( $columns_search as $column ) 
		{
			$selected = $this->get_filter_value( $column );
			if ( $selected === '' || $selected === null )	
				continue;
						 
			$date_query['column'] = $column;
			$values = explode('|',$selected);
			foreach ($values as $k => $value) 
			{			
				if ( $value )
				{
					$value = date("Y-m-d H:i:s", strtotime($value));				
					$key = $k ? "before" : "after";							
					$date_query[$key] = $value;
				}
			}		
		}	
		if ( $date_query )
			$date_query['inclusive'] = true;
		return $date_query;		
	}

	public function get_compare( $condition ) 
	{
		switch ( $condition['compare'] ) 
		{
			case '>':
				$compare = ">";										
			break;	
			case '<':
				$compare = "<";				
			break;	
			case '!=':
				$compare = "!=";				
			break;				
			case 'in':
				$compare = "IN";				
			break;
			case 'not in':
				$compare = "NOT IN";				
			break;
			case 'not_contain':
				$compare = "NOT LIKE";				
			break;		
			case 'contains':
				$compare = "LIKE";				
			break;
			case 'begins':
				$compare = "REGEXP";
				$condition['value'] = '^'.$condition['value'];
			break;					
			case 'ends':
				$compare = "REGEXP";	
				$condition['value'] = $condition['value'].'$';
			break;
			case 'not_exists':
				$compare = "NOT EXISTS";
				unset($condition['value']);
			break;		
			case 'exists':
				$compare = "EXISTS";	
				unset($condition['value']);				
			break;							
			default:
				$compare = "=";				
			break;	
		}		
		$condition['compare'] = $compare;		
		return $condition;
	}	
	
	public function get_string_for_query( $columns_search )
	{			
		$conditions = [];
		foreach ( $columns_search as $k => $column ) 
		{	
			$value = $this->get_filter_value( "v_$column" );	
			$compare = $this->get_filter_value( "c_$column" );		
			if ( $compare === null && $value === null )	
				continue;		
			if( is_array($value) )
				$compare = $compare === 'in' || $compare === 'not in' || $compare === 'exists' || $compare === 'not_exists' ? $compare : 'in';					
			$key = is_numeric($k) ? $column : $k;						
			$conditions[] = $this->get_compare(['key' => $key, 'value' => $value, 'compare' => $compare]); 			
		}
		return $conditions;
	}
	
	public function get_digital_interval_for_query( $columns_search )
	{	
		$conditions = [];		
		foreach ( $columns_search as $k => $column )
		{
			$selected = $this->get_filter_value( $column );	
			if ( $selected === '' || $selected === null )	
				continue;
				
			$values = explode('|',$selected);
			foreach ($values as $j => $value) 
			{			
				$value = absint($value);
				$compare = $j ? "<=" : ">=";	
				$key = is_numeric($k) ? $column : $k;
				$conditions[] = ['key' => $key, 'value' => $value, 'compare' => $compare, 'type' => 'NUMERIC'];
			}		
		}
		return $conditions;		
	}
	
	
	public function get_meta_for_query( $type, $query_vars )
	{
		$columns_string_meta = [];		
		$properties = usam_get_cache_properties( $type );
		foreach ( $properties as $property ) 
		{			
			switch ( $property->field_type )
			{				
				case "location":
					$locations = $this->get_filter_value( 'v_property_'.$property->id );	
					if ( $locations )
					{
						$locations = (array)$locations;
						$values = [];
						foreach ( $locations as $location ) 
						{
							$values[] = $location;
							$ids = usam_get_array_locations_down( $location );							 
							$values = array_merge($values, $ids);								
						}
						$query_vars[] = ['key' => $property->code, 'value' => $values, 'compare' => 'IN'];
					}
				break;			
				default:
					$columns_string_meta[$property->code] = 'property_'.$property->id;
				break;
			}			
		}
		if( $columns_string_meta )
			$query_vars[] = $this->get_string_for_query( $columns_string_meta );
		return $query_vars;
	}
}
?>