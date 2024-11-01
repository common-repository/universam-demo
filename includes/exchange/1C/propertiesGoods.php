<?php
if (!defined('ABSPATH')) exit;

require_once(USAM_FILE_PATH . '/includes/exchange/1C/products_handler.php' );
class USAM_propertiesGoods_Element_Handler extends USAM_Products_Handler
{
	private $attributes = [];	
	
	function start_element_handler($is_full, $names, $depth, $name, $attrs) 
	{ 
		global $usam_attribute;

		$this->is_full = $is_full;
		 
		if (!$depth && $name != 'КоммерческаяИнформация') {
			usam_error("XML parser misbehavior.");
		}			
		elseif (@$names[$depth - 1] == 'Классификатор' && $name == 'Свойства') {
		
		}
		elseif (@$names[$depth - 1] == 'Свойства' && $name == 'Свойство') {
			$usam_attribute = [];
		}	
	}

	function character_data_handler($is_full, $names, $depth, $name, $data)
	{
		global $usam_attribute;
				
		if (@$names[$depth - 2] == 'Свойства' && @$names[$depth - 1] == 'Свойство') 
		{ 
			@$usam_attribute[$name] .= $data;
		}	
		elseif (@$names[$depth - 2] == 'ВариантыЗначений' && @$names[$depth - 1] == 'Справочник') 
		{		
			@$usam_attribute[$name][] = $data;
		}			
	}

	function end_element_handler( $is_full, $names, $depth, $name) 
	{  
		global $usam_attribute;
		
		if (@$names[$depth - 1] == 'Свойства' && $name == 'Свойство') 
		{	
			if ( !empty($usam_attribute) )
				$this->attributes[$usam_attribute['Ид']] = $usam_attribute;
		}		
		elseif (@$names[$depth - 1] == 'Классификатор' && $name == 'Свойства') 
		{ 			
			$this->update_product_attributes( $this->attributes );
		}			
	}
}