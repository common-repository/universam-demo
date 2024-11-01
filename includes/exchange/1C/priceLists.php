<?php
if (!defined('ABSPATH')) exit;

require_once(USAM_FILE_PATH . '/includes/exchange/1C/products_handler.php' );
class USAM_priceLists_Element_Handler extends USAM_Products_Handler
{
	private $type_prices;	
	function start_element_handler($is_full, $names, $depth, $name, $attrs) 
	{ 
		$this->is_full = $is_full;		 
		if (!$depth && $name != 'КоммерческаяИнформация') {
			usam_error("XML parser misbehavior.");
		}		
		elseif (@$names[$depth - 1] == 'ТипыЦен' && $name == 'ТипЦены') 
		{
			$this->type_prices[] = array();
		}		
	}

	function character_data_handler($is_full, $names, $depth, $name, $data)
	{			
		if (@$names[$depth - 2] == 'ТипыЦен' && @$names[$depth - 1] == 'ТипЦены') 
		{ 		
			$i = count($this->type_prices)-1;
			@$this->type_prices[$i][$name] .= $data;
		}		
	}

	function end_element_handler( $is_full, $names, $depth, $name) 
	{  
		if (@$names[$depth - 1] == 'Классификатор' && $name == 'ТипыЦен')
		{  
			static $type_prices;
			if ( $type_prices == null )
			{
				$type_prices = array( );
				foreach (usam_get_prices() as $price) 
				{
					if ( isset($price['external_code']) )
						$type_prices[$price['external_code']] = $price['code'];
				}
			}	
			foreach ($this->type_prices as $type_price) 
			{				
				if ( !isset($type_prices[$type_price['Ид']]) )
				{ 
					usam_insert_type_price(['external_code' => strtolower($type_price['Ид']), 'title' => $type_price['Наименование']]);
				}
			}
		}
	}
}