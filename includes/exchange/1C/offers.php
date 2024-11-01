<?php
if (!defined('ABSPATH')) exit;

require_once(USAM_FILE_PATH . '/includes/exchange/1C/products_handler.php' );
class USAM_Offers_Element_Handler extends USAM_Products_Handler
{
	private $type_prices;
	private $storages;
	private $product;
	function start_element_handler($is_full, $names, $depth, $name, $attrs) 
	{		
		global $usam_attribute;
	
		$this->is_full = $is_full;
		if (@$names[$depth - 1] == 'Предложение' && $name == 'Склад') 
		{
			@$this->product['Склад'][] = $attrs;
		}
		elseif (@$names[$depth - 1] == 'Предложения' && $name == 'Предложение') 
		{
			$this->product = ['ХарактеристикиТовара' => [], 'ЗначенияСвойств' => [], 'Наименование' => ''];
		}
		elseif (@$names[$depth - 1] == 'Склады' && $name == 'Склад') 
		{
			$this->storages[] = [];
		}
		elseif (@$names[$depth - 1] == 'ТипыЦен' && $name == 'ТипЦены') 
		{
			$this->type_prices[] = [];
		}
		elseif (@$names[$depth - 1] == 'ХарактеристикиТовара' && $name == 'ХарактеристикаТовара') {
			$this->product['ХарактеристикиТовара'][] = [];
		}
		elseif (@$names[$depth - 1] == 'ЗначенияСвойств' && $name == 'ЗначенияСвойства') 
			$this->product['ЗначенияСвойств'][] = [];
		elseif (@$names[$depth - 1] == 'Свойства' && $name == 'Свойство') 	
			$usam_attribute = [];
		elseif (@$names[$depth - 1] == 'Цены' && $name == 'Цена') 
			$this->product['Цены'][] = [];		
	}

	function character_data_handler($is_full, $names, $depth, $name, $data)
	{
		global $usam_attribute;

		if (@$names[$depth - 2] == 'Предложения' && @$names[$depth - 1] == 'Предложение' && !in_array($name, array('БазоваяЕдиница', 'ЗначенияСвойств', 'ХарактеристикиТовара', 'Цены')))
			@$this->product[$name] .= $data;
		elseif (@$names[$depth - 2] == 'ХарактеристикиТовара' && @$names[$depth - 1] == 'ХарактеристикаТовара') 
		{
			$i = count($this->product['ХарактеристикиТовара']) - 1;
			@$this->product['ХарактеристикиТовара'][$i][$name] .= $data;
		}
		elseif (@$names[$depth - 2] == 'ЗначенияСвойств' && @$names[$depth - 1] == 'ЗначенияСвойства') 
		{
			$i = count($this->product['ЗначенияСвойств']) - 1;
			@$this->product['ЗначенияСвойств'][$i][$name] .= $data;
		}
		elseif (@$names[$depth - 2] == 'Свойства' && @$names[$depth - 1] == 'Свойство') 
		{ 
			@$usam_attribute[$name] .= $data;
		}	
		elseif (@$names[$depth - 2] == 'ВариантыЗначений' && @$names[$depth - 1] == 'Справочник') 
		{		
			@$usam_attribute[$name][] = $data;
		}	
		elseif (@$names[$depth - 2] == 'Цены' && @$names[$depth - 1] == 'Цена') 
		{ 
			$i = count($this->product['Цены'])-1;
			@$this->product['Цены'][$i][$name] .= $data;
		}
		elseif (@$names[$depth - 2] == 'Склады' && @$names[$depth - 1] == 'Склад') 
		{ 
			$i = count($this->storages)-1;
			@$this->storages[$i][$name] .= $data;
		}
		elseif (@$names[$depth - 2] == 'ТипыЦен' && @$names[$depth - 1] == 'ТипЦены') 
		{ 		
			$i = count($this->type_prices)-1;
			@$this->type_prices[$i][$name] .= $data;
		}
		elseif (@$names[$depth - 2] == 'Склад' && @$names[$depth - 1] == 'Адрес' && $name == 'Представление') 
		{ 
			$i = count($this->storages) - 1;
			@$this->storages[$i]['Адрес'] .= $data;		
		}
	}

	function end_element_handler($is_full, $names, $depth, $name)
	{
		global $usam_attribute;	
		if (@$names[$depth - 1] == 'Свойства' && $name == 'Свойство') 
		{
			if ( isset($usam_attribute['ДляПредложений']) && $usam_attribute['ДляПредложений'] == 'true' || $usam_attribute['ДляТоваров'] == 'true')	
				$this->add_property_values( $usam_attribute );
			$usam_attribute = [];
		}
		elseif (@$names[$depth - 1] == 'ЗначенияСвойства' && $name == 'Наименование') 
		{
			$i = count($this->product['ЗначенияСвойств']) - 1;
			$this->product['ЗначенияСвойств'][$i]['Наименование'] = preg_replace("/\s+\(.*\)$/", '', $this->product['ЗначенияСвойств'][$i]['Наименование']);		
		}
		elseif (@$names[$depth - 1] == 'ХарактеристикаТовара' && $name == 'Наименование') 
		{
			$i = count($this->product['ХарактеристикиТовара']) - 1;
			$this->product['ХарактеристикиТовара'][$i]['Наименование'] = preg_replace("/\s+\(.*\)$/", '', $this->product['ХарактеристикиТовара'][$i]['Наименование']);		
		}
		elseif (@$names[$depth - 1] == 'Предложения' && $name == 'Предложение') 
		{ 				
			$setting = get_option('usam_1c', ['product' => ['variation' => 0]]);			
			if( strpos($this->product['Ид'], '#') === false || empty($setting['product']['variation']) ) 
			{ 
				$product_id = usam_get_product_id_by_code( $this->product['Ид'] );					
				if ( $product_id ) 
				{ 
					$this->replace_offer_post_meta( $product_id, $this->product );					
					$this->update++;
				}
				else
					$this->replace_product( $this->product );	
				usam_clean_product_cache( $product_id );
			}
			else 
			{ 
				$product_guid = explode('#', $this->product['Ид']);	
				$this->product['Ид'] = $product_guid[0];
				$this->variations[$product_guid[1]][] = $this->product;
			} 
		}
		elseif (@$names[$depth - 1] == 'ПакетПредложений' && $name == 'ТипыЦен')
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
					usam_insert_type_price(['external_code' => strtolower($type_price['Ид']), 'title' => $type_price['Наименование']]);
			}
		}
		elseif (@$names[$depth - 1] == 'ПакетПредложений' && $name == 'Склады')
		{
			static $storages;
			if ( $storages === null )
				$storages = usam_get_storages(['fields' => 'code=>meta_key', 'active' => 'all']);			
			foreach ($this->storages as $storage) 
			{  
				if ( !isset($storages[$storage['Ид']]) )
				{ 
					$id = usam_insert_storage(['active' => 1, 'shipping' => 1, 'code' => strtolower($storage['Ид']), 'title' => $storage['Наименование']]);
					$address = isset($storage['Адрес']['Представление'])?trim($storage['Адрес']['Представление']):'';
					usam_update_storage_metadata( $id, 'address', $address);
				}
			}			
		}
		elseif (@$names[$depth - 1] == 'ПакетПредложений' && $name == 'Предложения') 
		{					
			$this->replace_suboffers();
		}
		elseif (!$depth && $name == 'КоммерческаяИнформация') 
		{ 
			do_action('usam_post_offers', $this->is_full );
		}
	}
}