<?php
if (!defined('ABSPATH')) exit;


require_once(USAM_FILE_PATH . '/includes/exchange/1C/products_handler.php' );
class USAM_rests_Element_Handler extends USAM_Products_Handler
{
	private $offer = array();
	function start_element_handler($is_full, $names, $depth, $name, $attrs) 
	{	
		$this->is_full = $is_full;
		if (@$names[$depth - 1] == 'Предложения' && @$names[$depth] == 'Предложение' )
			$this->offer = ['Остаток' => []];
	}

	function character_data_handler($is_full, $names, $depth, $name, $data)
	{
		if (@$names[$depth - 2] == 'Предложения' && @$names[$depth - 1] == 'Предложение' && !in_array($name, array('Остатки'))) {
			@$this->offer[$name] .= $data; 
		}
		elseif (@$names[$depth - 2] == 'Остаток' && @$names[$depth - 1] == 'Склад') 
		{ 
			$i = count($this->offer['Остаток']);
			$i = $names[$depth] == 'Количество'?$i-1:$i;
			@$this->offer['Остаток'][$i][$name] .= $data;	
		}		
	}

	function end_element_handler($is_full, $names, $depth, $name)
	{		
		if (@$names[$depth - 1] == 'Предложения' && $name == 'Предложение') 
		{ 
			$this->replace_rests();
		}
	}
	
	function replace_rests( ) 
	{ 					
		$product_id = usam_get_product_id_by_code( $this->offer['Ид'] );
		if ( empty($product_id) ) 
			return false;
		
		if ( !empty($this->offer['Остаток']) )
		{
			$this->update_stocks($product_id, $this->offer['Остаток']);
			$this->update++;
		}			
	}
}