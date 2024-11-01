<?php
if (!defined('ABSPATH')) exit;

require_once(USAM_FILE_PATH . '/includes/exchange/1C/products_handler.php' );
class USAM_Units_Element_Handler extends USAM_Products_Handler
{
	private $units;	
	
	function start_element_handler($is_full, $names, $depth, $name, $attrs) 
	{ 
		$this->is_full = $is_full;		 
		if (!$depth && $name != 'КоммерческаяИнформация') {
			usam_error("XML parser misbehavior.");
		}			
		elseif (@$names[$depth - 1] == 'ЕдиницыИзмерения' && $name == 'ЕдиницаИзмерения') 
		{
			$this->units[] = [];
		}
	}

	function character_data_handler($is_full, $names, $depth, $name, $data)
	{				
		if (@$names[$depth - 2] == 'ЕдиницыИзмерения' && @$names[$depth - 1] == 'ЕдиницаИзмерения') 
		{ 
			$i = count($this->units)-1;
			@$this->units[$i][$name] .= $data;
		}				
	}

	function end_element_handler( $is_full, $names, $depth, $name) 
	{  		
		if (@$names[$depth - 1] == 'Классификатор' && $name == 'ЕдиницыИзмерения')
		{ 
			$units = usam_get_list_units();				
			foreach ($this->units as $unit_1c) 
			{  
				$add = true;
				$title = isset($unit_1c['НаименованиеПолное']) ? trim($unit_1c['НаименованиеПолное']) :'';	
				foreach ($units as $unit) 
				{					
					if ( $unit_1c['Код'] == $unit['external_code'] )
					{						
						$add = false;
						break;
					}					
					elseif ( mb_strtoupper($title) == mb_strtoupper($unit['title']) )
					{
						$unit['external_code'] = trim($unit_1c['Код']);
						if ( isset($unit_1c['НаименованиеКраткое']) )
							$unit['short'] = trim($unit_1c['НаименованиеКраткое']);
						usam_edit_data( $unit, $unit['id'], 'usam_units_measure', false );
						$add = false;
						break;
					}				
				}							
				if ( $add )
				{
					$item = ['title' => $title, 'short' => trim($unit_1c['НаименованиеКраткое']), 'code' => trim($unit_1c['Код']), 'accusative' => '', 'in' => '', 'plural' => '', 'external_code' => trim($unit_1c['Код']), 'international_code' => ''];
					$id = usam_add_data( $item, 'usam_units_measure' );
				}
			}
		}
	}
}