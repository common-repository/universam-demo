<?php
class USAM_Shortcode
{
	private function if_condition( $condition, $a, $b ) 
	{	
		$condition = trim($condition);
		$a = trim($a);
		$b = trim($b);
		switch ( $condition ) 
		{
			case '=' :				
				if ( $a == $b )
					return true;
			break;	
			case '>' :	
				if ( $a > $b )
					return true;
			break;	
			case '<' :	
				if ( $a < $b )
					return true;
			break;	
			case '<=' :	
				if ( $a <= $b )
					return true;
			break;	
			case '!=' :			
				if ( $a != $b )
					return true;
			break;	
			case '=>' :	
				if ( $a >= $b )
					return true;
			break;
		}
		return false;
	}

	public function process_args( $args, $text, $replacement = 'all' ) 
	{ 	
		if ( empty($args) )
			return $text;
		
		if ( empty($text) )
			return '';
	
		$args['weekday' ] = date('w');
		$args['hour' ] = date('G');
		
		if ( $replacement == 'all' )
		{
			preg_match_all('/%([\w-]*)%/m', $text, $mt, PREG_PATTERN_ORDER);
			
			$values = array();
			$keys = array();
			foreach ( $mt[1] as $key ) 	
			{
				$values[] = isset($args[$key])?$args[$key]:'';
				$keys[] = "%{$key}%";
			}				
		}
		else
		{
			$values = array_values($args);
			$keys = array();
			foreach ( $args as $key => $value ) 
				$keys[] = "%$key%";
		}										
		$text = str_replace( $keys, $values, $text );	
	
		$arrTable = array();			
		preg_match_all( '/\[([^\[\]]+)\]/', $text, $matches, PREG_SET_ORDER );				
		if ( !empty($matches) )
		{		
			$current_date = date('Y-m-d H:i:s');		
			foreach ( $matches as $arr ) 
			{
				if ( isset($arr[1]) )
				{
					$contains = false;
					$out = '';
					$massiv_str = explode(" ", $arr[1]);	
					$massiv_str = preg_split('/\s/', trim($arr[1]));					
					switch ( $massiv_str[0] ) 
					{
						case 'current_day' :	
							$d = new DateTime( $current_date );							
							$d->modify($massiv_str[1]." day");
							$out = $d->format("d.m.Y");
							$contains = true;
						break;	
						case 'if' :
							$signs = ['=', '>', '<', '>=', '<=', '!=' ];
							$result = false;
							preg_match_all('/\{(.+?)\}/s', $arr[1], $str2 ); 
							preg_match('/if(.+?){/s', $arr[1], $condition_args ); 						
							if ( !empty($str2[1]) && !empty($condition_args[1]) )
							{ 								
								$condition = trim($condition_args[1]);
								foreach ( $signs as $sign ) 
								{												
									$condition_value = explode( $sign, $condition );	
									$shortcode = trim($condition_value[0]);									
									if ( isset($condition_value[1])&& isset($args[$shortcode]) )
									{				
										if ( $this->if_condition( $sign, $args[$shortcode], $condition_value[1] ))
										{ 											
											$out = $str2[1][0];												
											$result = true;
											break;
										}
									}
								}	
								if ( $result == false && isset($str2[1][1]) )
									$out = $str2[1][1];	
							}							
							$contains = true;							
						break;
					}	
					if ( $contains )
					{			
						$text = str_replace( $arr[0], $out, $text );
					}
				}
			}
		}		
		return $text;
	}
}	