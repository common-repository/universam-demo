<?php
if (!defined('ABSPATH')) exit;

require_once(USAM_FILE_PATH . '/includes/exchange/1C/products_handler.php' );
class USAM_prices_Element_Handler extends USAM_Products_Handler
{
	private $offer = [];
	function start_element_handler($is_full, $names, $depth, $name, $attrs) 
	{		
		$this->is_full = $is_full;
		if (@$names[$depth - 1] == 'Предложения' && @$names[$depth] == 'Предложение' )
			$this->offer = ['Цены' => []];
		elseif (@$names[$depth - 1] == 'Цены' && $name == 'Цена') 
			$this->offer['Цены'][] = [];
	}

	function character_data_handler($is_full, $names, $depth, $name, $data)
	{
		if (@$names[$depth - 2] == 'Предложения' && @$names[$depth - 1] == 'Предложение' && !in_array($name, array('Цены'))) {
			@$this->offer[$name] .= $data;
		}
		elseif (@$names[$depth - 2] == 'Цены' && @$names[$depth - 1] == 'Цена') 
		{ 
			$i = count($this->offer['Цены'])-1;
			@$this->offer['Цены'][$i][$name] .= $data;
		}
	}

	function end_element_handler($is_full, $names, $depth, $name)
	{		
		if (@$names[$depth - 1] == 'Предложения' && $name == 'Предложение') 
		{ 
			$this->replace_prices( );
		}
	}
	
	function replace_prices( ) 
	{ 
		$product_id = usam_get_product_id_by_code( $this->offer['Ид'] );
		if ( empty($product_id) ) 
			return false;	
		if ( !empty($this->offer['Цены']) )
		{
			$this->update_prices( $product_id, $this->offer['Цены'] );
			$this->update++;
		}	
	}
}