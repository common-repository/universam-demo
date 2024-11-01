<?php
/*
Арифметические операторы
	+ сложение
	- вычитание
	* умножение
	/ деление
	% деление по модулю
	** возведение в степень
	~ конкатенация строк
	
Операторы сравнения
	== (==) равно
	=== (===) идентично (равно по значению и по типу)
	!= (!=) не равно
	!== (!==) не идентично
	< меньше
	> больше
	<= (<=) меньше либо равно
	>= (>=) больше либо равно
	matches удовлетворяет регулярному выражению
	not matches не удовлетворяет регулярному выражению	
*/
class USAM_Expression_Parser
{
	protected $valid_arithmetic_operators = ['+', '-', '*', '/', '%', '**', '`'];
	protected $valid_comparison_operators = ['==', '===', '!=', '!==', '<', '>', '<=', '>=', 'matches', 'not matches', 'in', 'not in'];
	protected $valid_functions = ['abs', 'sin', 'cos', 'tan', 'log', 'exp', 'sqrt', 'count'];
	protected $valid_logical_operators = ['||', '&&', 'xor'];
			
	public function parser( $string, $data ) 
	{	
		if ( $string === '' )
			return true;	
		
	//	$s = preg_replace_callback('/[\d\.]+( )?[eE]( )?[\+\-]?( )?[\d\.]+ /', [$this, 'match'], $string);
		
	//	echo "ssssssss $s<br><br>";
		
	/*
		$pattern = '/([a-zA-Z0-9.](\sor\s)[a-zA-Z0-9.])+/i';
		if( preg_match_all($pattern, $string, $regs ) )
		{
			
		}
		
		echo "<br>$pattern<br>";
		print_r($regs);
		echo "<br><br>";
		
		echo "$string<br><br>";
		$pattern = '/((.*)( '.implode(" )(.*))|((.*)( ",$this->valid_logical_operators).' )(.*))/i';
		if( preg_match_all($pattern, $string, $regs ) )
		{
			
		}
		
		///\[.+?\]/
		$operators = array_map([$this, 'preg_quote'], $this->valid_logical_operators);
		
	//	$pattern = '/(([a-zA-Z0-9."><= ]+) ('.implode(') ([a-zA-Z0-9."><= ]+))|(([a-zA-Z0-9."><= ]+) (',$operators).') ([a-zA-Z0-9."><= ]+))/ui';
		
		$comparison_operators = array_map([$this, 'preg_quote'], $this->valid_comparison_operators);
		$p = '([a-zA-Z0-9\.]+[( '.implode(" | ",$comparison_operators).' )])';
		
	//	$p = '([a-zA-Z0-9\.\"]+)';
		
		//$p = '([a-zA-Z0-9\."><=]+)';
		
		$pattern = '/'.$p.'( '.implode(' | ',$operators).' )'.$p.'|([a-zA-Z0-9\.\"]+\z)/ui';		
		
		//$operators = $this->valid_logical_operators;		
	//	$pattern = '/([ ('.implode(') | (',$operators).') ])/ui';
		
		if( preg_match_all($pattern, $string, $regs, PREG_PATTERN_ORDER ) )
		{
			
		}
				
		
		echo "<br>$pattern<br>";
		print_r($regs);
		echo "<br><br>-----------------------------------------<br><br>";
		*/

		
		$array = $this->string_parsing( $string );
		$result = true;
		foreach ( $array as $i => $step ) 
		{			
			if ( in_array($step, $this->valid_logical_operators)  )
			{
				if ( $result && $step == '||' || !$result && $step == '&&' )
					return $result;			
			}
			else
			{ 
				$expression = $this->expression_parsing( $step );
				if ( $expression )
				{		
					if ( preg_match( '/^('.implode("|",$this->valid_functions).')/', $expression['key'] ) )
						$d = $this->apply_function( $data, $expression['key'] );	
					else
						$d = $this->get_value_variable( $data, $expression['key'] );	
					if ( $d === null )
					{
						$result = false;
						continue;							
					}	
					if ( preg_match( '/^('.implode("|",$this->valid_functions).')/', $expression['value'] ) )
						$value = $this->apply_function( $data, $expression['value'] );
					else
					{
						$value = trim($expression['value'], '"');					
						if ( $value === $expression['value'] )
							$value = (float)$value;						
					}
					if ( $value === null )
					{
						$result = false;
						continue;							
					}
					$compare = new USAM_Compare();
					if ( is_string($value) )					
						$result = $compare->compare_string($expression['operator'], $d, $value);
					else
						$result = $compare->compare_number($expression['operator'], $d, $value);				
				}
			}			
		}
		return $result;
	}
	
	//Получить массив условий и логических операторов
	private function string_parsing( $string ) 
	{
		$operators = array_map([$this, 'preg_quote'], $this->valid_logical_operators);
		$pattern = '/( '.implode(" )|( ",$operators).' )/ui';
		$array = preg_split($pattern, $string, -1, PREG_SPLIT_NO_EMPTY);		
		$s = $string;
		foreach ( $array as $value ) 
			$s = str_replace( $value, '', $s );
		$s = trim($s);
		$s = str_replace('  ', ' ', $s );
		$logical = explode(' ', $s);
		$results = [];
		foreach ( $array as $i => $value ) 
		{
			$results[] = $value;
			if ( isset($logical[$i]) )
				$results[] = $logical[$i];
		}
		return $results;
	}

	private function apply_function( $d, $variable ) 
	{	
		if ( preg_match( '/^('.implode("|",$this->valid_functions).')/', $variable, $functions ) )
		{
			$f = $functions[0];						
			$d = $this->get_value_variable( $d, substr(str_replace($f, '', $variable), 1, -1) );					
			$d = $f( $d );
		}			
		return $d;
	}
	
	private function get_value_variable( $d, $keys ) 
	{
		$keys = explode('.',$keys);			
		foreach ( $keys as $key )
		{			
			$d = $this->variable( $d, $key );				
			if ( $d === null )
				break;
		}
		return $d;
	}
	
	private function expression_parsing( $string ) 
	{
		$arithmetic_operators = array_map([$this, 'preg_quote'], $this->valid_arithmetic_operators);
		$pattern = '/(.*)('.implode(" | ",$this->valid_comparison_operators).' | '.implode(" | ",$arithmetic_operators).')(.*)/i';
		$result = false;
		if( preg_match($pattern, $string, $regs) )
		{
			$regs = array_map('trim', $regs);			
			$result = ['key' => $regs[1], 'operator' => $regs[2], 'value' => $regs[3]];
		}	
		return $result;
	}

	public function preg_quote( $str ) 
	{	
		return preg_quote($str, '/');
	}
	
	private function variable( $data, $key ) 
	{
		return isset($data[$key]) ? $data[$key] : null;
	}
}
?>