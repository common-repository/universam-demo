<?php
class USAM_Compare
{		
	// Сравнить строки
	public function compare_string( $logic, $n1, $n2 ) 
	{		
		$n1 = (string)$n1;
		$n2 = (string)$n2;
	
		$return = false;
		switch( $logic )
		{
			case '=':
			case '==':
			case 'equal'://равно		
				if ( $n1 === $n2 ) $return = true; 
			break;
			case '!=':
			case 'not_equal': // не равно
				if ( $n1 !== $n2 ) $return = true;
			break;
			case 'contains': //содержит
				$pos = strpos($n1, $n2);
				if ($pos !== false)
					$return = true;		
			break;
			case 'not_contain'://не содержит
				$pos = strpos($n1, $n2);
				if ($pos === false)
					$return = true;		
			break;	
			case 'begins'://начинается с
				preg_match("/^".$n1."/", $n2, $match);
				if (!empty($match))
					$return = true;
			break;
			case 'ends'://заканчивается на
				preg_match("/".$n1."$/", $n2, $match);
				if (!empty($match))
					$return = true;
			break;				
			default:
				$return = false;
			break;
		}
		return $return;
	}	
	
	// Сравнить числа
	public function compare_number( $logic, $n1, $n2 ) 
	{		
		$n1 = (float)$n1;
		$n2 = (float)$n2;
		
		$return = false;
		switch( $logic )
		{
			case '=':
			case '==':
			case 'equal'://равно		
				if ( $n1 == $n2 ) $return = true; 
			break;
			case '!=':
			case 'not_equal': // не равно
				if ( $n1 != $n2 ) $return = true;
			break;
			case '>':
			case 'greater'://больше					
				if ($n1 > $n2)	$return = true;
			break;
			case '<':
			case 'less'://меньше
				if ($n1 < $n2) $return = true;
			break;
			case '>=':
			case 'eg'://больше либо равно					
				if ($n1 >= $n2)	$return = true;
			break;
			case '<=':
			case 'el'://меньше либо равно
				if ($n1 <= $n2) $return = true;
			break;
			default:
				$return = false;
			break;
		}
		return $return;
	}	
	
	// Проверить массив на условие
	public function compare_array( $logic, $array, $number ) 
	{		
		$return = false;
		switch( $logic )
		{
			case 'equal'://равно
				if ( in_array($number, $array) ) $return = true;
			break;
			case 'not_equal': // не равно
				if ( !in_array($number, $array) ) $return = true;
			break;		
			default:
				$return = false;
			break;
		}
		return $return;
	}	
	
	// Проверить массив на условие
	public function compare_arrays( $logic, $array1, $array2 ) 
	{		
		$return = false;
		if ( is_array($array1) && is_array($array2) )
		{
			$result = array_intersect($array1, $array2);
			switch( $logic )
			{
				case 'equal'://равно				
					if ( !empty($result) ) $return = true;
				break;
				case 'not_equal': // не равно
					if ( empty($result) ) $return = true;
				break;		
				default:
					$return = false;
				break;
			}
		}
		return $return;
	}
	
		// Проверить термины на условие
	public function compare_terms( $product_id, $term, $c ) 
	{		
		$result = false;
		$product_id = (int)$product_id;		
		$ids = usam_get_product_term_ids( $product_id, $term );	
		if ( is_array($c['value']) )
			$result = $this->compare_arrays($c['logic'], $ids, $c['value'] );
		else
		{
			$value = (int)$c['value'];					
			$result = $this->compare_array($c['logic'], $ids, $value );					
		}		
		return $result;
	}
}

function usam_conditions_user( $conditions ) 
{
	$user_id = get_current_user_id();
	$compare = new USAM_Compare();		
	foreach( $conditions as $key => $condition ) 
	{
		switch( $key )
		{
			case 'roles':		
				if ( !empty($condition) ) 
				{ 
					if ( $user_id == 0 ) 
					{						
						$result = $compare->compare_array('equal', $condition, 'notloggedin');
						if ( !$result )
							return false;
					}
					else
					{
						$user = get_userdata( $user_id );					
						$result = array_intersect($user->roles, $condition);
						if ( empty($result) ) 
							return false;
					}
				}	
			break;
			case 'sales_area':		
				if ( !empty($condition) ) 
				{
					$area = usam_get_customer_sales_area();
					if ( !$area || !in_array($area, $condition ) )
						return false;		
				}
			break;
		}
	}	
	return true;	
}
?>