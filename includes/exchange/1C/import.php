<?php
if (!defined('ABSPATH')) exit;

require_once ABSPATH . "wp-admin/includes/media.php";
require_once ABSPATH . "wp-admin/includes/file.php";
require_once ABSPATH . "wp-admin/includes/image.php";

require_once(USAM_FILE_PATH . '/includes/exchange/1C/products_handler.php' );
class USAM_Import_Element_Handler extends USAM_Products_Handler
{
	private $product;
	private $type_prices;
	private $storages;
	private $attributes = [];	
	private $groups = [];
	private $group_parent_ids = array('');	
	private $units;	
	
	function start_element_handler($is_full, $names, $depth, $name, $attrs) 
	{ 
		global $usam_attribute;

		$this->is_full = $is_full;
		 
		if (!$depth && $name != 'КоммерческаяИнформация') {
			usam_error("XML parser misbehavior.");
		}	
		elseif (@$names[$depth - 2] == 'Группы' && $name == 'Группы') 
		{		
			$group = end($this->groups);
			$this->group_parent_ids[] = @$group['Ид'];
		}		
		elseif (@$names[$depth - 1] == 'ТипыЦен' && $name == 'ТипЦены') 
		{
			$this->type_prices[] = array();
		}
		elseif (@$names[$depth - 1] == 'Склады' && $name == 'Склад') 
		{
			$this->storages[] = array();
		}
		elseif (@$names[$depth - 1] == 'Группы' && $name == 'Группа') 
		{			
			$this->groups[] = ['ИдРодителя' => end($this->group_parent_ids)];
		}		
		elseif (@$names[$depth - 1] == 'Классификатор' && $name == 'Свойства') {
		
		}
		elseif (@$names[$depth - 1] == 'Свойства' && $name == 'Свойство') {
			$usam_attribute = [];
		}	
		elseif (@$names[$depth - 1] == 'Группы' && $name == 'Ид') {
			$this->product['Группы'][] = '';
		}
		elseif (@$names[$depth - 1] == 'Товары' && $name == 'Товар') {
			$this->product = ['ХарактеристикиТовара' => [], 'ЗначенияСвойств' => [], 'ЗначенияРеквизитов' => []];
		}
		elseif (@$names[$depth - 1] == 'Товар' && $name == 'Группы') {
			$this->product['Группы'] = array();
		}	
		elseif (@$names[$depth - 1] == 'Товар' && $name == 'Картинка') 
		{
			if (!isset($this->product['Картинка'])) 
				$this->product['Картинка'] = array();
			$this->product['Картинка'][] = '';
		}
		elseif (@$names[$depth - 1] == 'Товар' && $name == 'БазоваяЕдиница') 
		{
			if ( isset($attrs['Код']) )
				$this->product['БазоваяЕдиница'] = ['Код' => $attrs['Код'], 'НаименованиеПолное' => $attrs['НаименованиеПолное']];
			else				
				$this->product['БазоваяЕдиница'] = ''; 
		}
		elseif (@$names[$depth - 1] == 'Товар' && $name == 'Изготовитель') {
			$this->product['Изготовитель'] = array();
		}
		elseif (@$names[$depth - 1] == 'ХарактеристикиТовара' && $name == 'ХарактеристикаТовара') {
			$this->product['ХарактеристикиТовара'][] = array();
		}	
		elseif (@$names[$depth - 1] == 'ЗначенияРеквизитов' && $name == 'ЗначениеРеквизита') {
			$this->product['ЗначенияРеквизитов'][] = array();
		}
		elseif (@$names[$depth - 1] == 'ЗначениеРеквизита' && $name == 'Значение') 
		{
			$i = count($this->product['ЗначенияРеквизитов']) - 1;
			if (!isset($this->product['ЗначенияРеквизитов'][$i]['Значение'])) 
				$this->product['ЗначенияРеквизитов'][$i]['Значение'] = array();
			$this->product['ЗначенияРеквизитов'][$i]['Значение'][] = '';
		}
		elseif (@$names[$depth - 1] == 'ЕдиницыИзмерения' && $name == 'ЕдиницаИзмерения') 
		{
			$this->units[] = [];
		}
	}

	function character_data_handler($is_full, $names, $depth, $name, $data)
	{
		global $usam_attribute;
		
		if (@$names[$depth - 2] == 'Группы' && @$names[$depth - 1] == 'Группа' && $name != 'Группы') {
			
			$i = count($this->groups)-1;
			@$this->groups[$i][$name] .= $data;
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
		elseif (@$names[$depth - 2] == 'Свойства' && @$names[$depth - 1] == 'Свойство') 
		{ 
			@$usam_attribute[$name] .= $data;
		}	
		elseif (@$names[$depth - 2] == 'ВариантыЗначений' && @$names[$depth - 1] == 'Справочник') 
		{		
			@$usam_attribute[$name][] = $data;
		}
		elseif (@$names[$depth - 2] == 'ХарактеристикиТовара' && @$names[$depth - 1] == 'ХарактеристикаТовара') {
			$i = count($this->product['ХарактеристикиТовара']) - 1;
			@$this->product['ХарактеристикиТовара'][$i][$name] .= $data;
		} 			
		elseif (@$names[$depth - 2] == 'БазоваяЕдиница' && @$names[$depth - 1] == 'Пересчет') 
		{			
			@$this->product['БазоваяЕдиница'][$name] .= $data; 
		}	
		elseif (@$names[$depth - 2] == 'Товар' && @$names[$depth - 1] == 'БазоваяЕдиница' && !isset($this->product['БазоваяЕдиница']['Код']) ) 
		{			
			@$this->product['БазоваяЕдиница'][$name] .= $data;	
		}	
		elseif (@$names[$depth - 2] == 'Товары' && @$names[$depth - 1] == 'Товар' && !in_array($name, ['Группы', 'Картинка', 'Изготовитель', 'ХарактеристикиТовара', 'ЗначенияСвойств', 'СтавкиНалогов', 'ЗначенияРеквизитов', 'БазоваяЕдиница']))
			@$this->product[$name] .= $data;
		elseif (@$names[$depth - 2] == 'Товар' && @$names[$depth - 1] == 'Группы' && $name == 'Ид') {
			$i = count($this->product['Группы']) - 1;
			@$this->product['Группы'][$i] .= $data;
		}
		elseif (@$names[$depth - 2] == 'Товар' && @$names[$depth - 1] == 'Изготовитель') {
			@$this->product['Изготовитель'][$name] .= $data;
		}			
		elseif (@$names[$depth - 2] == 'Товары' && @$names[$depth - 1] == 'Товар' && $name == 'Картинка')
		{
			$i = count($this->product['Картинка']) - 1;
			@$this->product['Картинка'][$i] .= $data;
		}		
		elseif (@$names[$depth - 2] == 'ЗначенияСвойств' && @$names[$depth - 1] == 'ЗначенияСвойства') 
		{
			if ($name != 'Значение')
			{
				$i = count($this->product['ЗначенияСвойств']);	 
				@$this->product['ЗначенияСвойств'][$i][$name] .= $data;
			}
			else
			{
				$i = count($this->product['ЗначенияСвойств'])-1;
				@$this->product['ЗначенияСвойств'][$i][$name] .= $data;
			}			
		}
		elseif (@$names[$depth - 2] == 'ЗначенияРеквизитов' && @$names[$depth - 1] == 'ЗначениеРеквизита')
		{
			$i = count($this->product['ЗначенияРеквизитов']) - 1;
			if ($name != 'Значение')
				@$this->product['ЗначенияРеквизитов'][$i][$name] .= $data;
			else 
			{
				$j = count($this->product['ЗначенияРеквизитов'][$i]['Значение']) - 1;
				@$this->product['ЗначенияРеквизитов'][$i]['Значение'][$j] .= $data;
			}
		}
		elseif (@$names[$depth - 2] == 'ЕдиницыИзмерения' && @$names[$depth - 1] == 'ЕдиницаИзмерения') 
		{ 
			$i = count($this->units)-1;
			@$this->units[$i][$name] .= $data;
		}				
	}

	function end_element_handler( $is_full, $names, $depth, $name) 
	{  
		global $wpdb, $usam_attribute;
		static $product_attributes = [], $attribute_values = [];
		if( @$names[$depth - 2] == 'Группы' && $name == 'Группы' )
		{ 
			array_pop($this->group_parent_ids);
		}
		if (@$names[$depth - 1] == 'Классификатор' && $name == 'Группы') 
		{ 				 
			$setting = get_option('usam_1c', ['product' => ['categories' => 0]]);	
			if ( !empty($setting['product']['categories']) )
			{				
				$codes = array();
				foreach ($this->groups as $sort => $group )
					$codes[] = $group['Ид'];			
				usam_terms_id_by_meta('external_code', $codes, 'usam-category'); 
				
				$term_ids = array();				
				foreach ($this->groups as $sort => $group )
				{
					if ( ! apply_filters('usam_import_group_xml', $group, $sort, $is_full) ) 
						continue;
					$term_ids[] = $this->replace_term($group['Ид'], $group['Наименование'], $group['ИдРодителя'], 'usam-category', $sort, $is_full);	
				}				
			/*	if ( !empty($term_ids) )
					usam_get_products( ['tax_query' => [['taxonomy' => 'usam-category', 'field' => 'id', 'terms' => $term_ids, 'operator' => 'IN']], 'posts_per_page' => 300, 'cache_results' => true, 'update_post_meta_cache' => true, 'update_post_term_cache' => true, 'stocks_cache' => false, 'prices_cache' => false, 'product_meta_cache' => true, 'product_attribute_cache' => true, 'product_images_cache' => true] );*/
			}
		}
		elseif (@$names[$depth - 1] == 'Классификатор' && $name == 'ТипыЦен')
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
		elseif (@$names[$depth - 1] == 'Классификатор' && $name == 'Склады')
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
		elseif (@$names[$depth - 1] == 'Классификатор' && $name == 'Товары') 
		{
			
		}
		elseif (@$names[$depth - 1] == 'Свойства' && $name == 'Свойство') 
		{	
			if ( !empty($usam_attribute) )
				$this->attributes[$usam_attribute['Ид']] = $usam_attribute;
		}		
		elseif (@$names[$depth - 1] == 'Классификатор' && $name == 'Свойства') 
		{ 			
			$codes = array();
			foreach ($this->attributes as $code => $attribute )
				$codes[] = $code;		
			$ids = get_terms(['fields' => 'id=>slug', 'taxonomy' => 'usam-product_attributes', 'hide_empty' => 0, 'usam_meta_query' => [['key' => 'external_code', 'value' => $codes, 'compare' => 'IN']]]);	
			foreach ($ids as $id => $slug)
			{
				$external_code = usam_get_term_metadata($id, 'external_code');
				wp_cache_set("usam_term_external_code-$external_code", $id);	
				if ( $external_code )
					$product_attributes[$external_code] = $slug;				
			}	
			$term_ids = array();			
			$codes = array();		
			foreach ($this->attributes as $code => $attribute )
			{
				$term_id = usam_term_id_by_meta('external_code', $attribute['Ид'], 'usam-product_attributes');					
				if ( empty($term_id) )
					$codes[] = $code;	
				
				$term_id = $this->add_property_values( $attribute );
				$term_ids[] = $term_id;
				$this->attributes[$code]['term_id'] = $term_id;									
			} 
			if ( $codes )
			{
				$ids = get_terms(['fields' => 'id=>slug', 'taxonomy' => 'usam-product_attributes', 'hide_empty' => 0, 'usam_meta_query' => [['key' => 'external_code', 'value' => $codes, 'compare' => 'IN']]]);
				foreach ($ids as $id => $slug)
				{
					$external_code = usam_get_term_metadata($id, 'external_code');
					if ( $external_code )
						$product_attributes[$external_code] = $slug;				
				}
			}				
			$product_attribute_values = usam_get_product_attribute_values(['attribute_id' => $term_ids]);	
			foreach ($product_attribute_values as $value )
			{
				$attribute_values[$value->attribute_id][$value->code] = $value;
			}	
			foreach ($this->attributes as $code => $attribute)
			{
				$values = array();	
				if ( isset($attribute['ИдЗначения']) )
				{ 					
					foreach ($attribute['ИдЗначения'] as $key => $id )
					{
						if ( isset($attribute['Значение'][$key]) && isset($attribute_values[$attribute['term_id']][$id]) )
							$values[$id] = $attribute_values[$attribute['term_id']][$id]->id;							
					}
					unset($this->attributes[$code]['ИдЗначения']);
					unset($this->attributes[$code]['Значение']);
				}				
				$this->attributes[$code]['values'] = $values;				
			}		
		}		
		elseif (@$names[$depth - 1] == 'Товары' && $name == 'Товар')
		{			
			if ( !empty($this->product['ЗначенияСвойств']) ) 
			{									
				if ( !$product_attributes )
				{
					$ids = get_terms(['fields' => 'id=>slug', 'taxonomy' => 'usam-product_attributes', 'hide_empty' => 0]);
					foreach ($ids as $id => $slug)
					{
						$external_code = usam_get_term_metadata($id, 'external_code');
						if ( $external_code )
							$product_attributes[$external_code] = $slug;
					}
				}	
				if ( !$attribute_values )
				{
					$product_attribute_values = usam_get_product_attribute_values();			
					foreach ($product_attribute_values as $value )
					{
						$attribute_values[$value->attribute_id][$value->code] = $value;
					}	
				}
				$this->product['attributes'] = array();	
				foreach ($this->product['ЗначенияСвойств'] as $property)
				{								
					if ( empty($property['Значение']) )
						continue;
					if ( isset($this->attributes[$property['Ид']]['term_id']) )
					{						
						if ( !empty($this->attributes[$property['Ид']]['values']) )
						{								
							if ( is_array($property['Значение']) )
							{								
								foreach ($property['Значение'] as $value_id )
									$this->product['attributes'][$product_attributes[$property['Ид']]][] = $this->attributes[$property['Ид']]['values'][$value_id];
							}
							else
								$this->product['attributes'][$product_attributes[$property['Ид']]][] = $this->attributes[$property['Ид']]['values'][$property['Значение']];
						}
						else
							$this->product['attributes'][$product_attributes[$property['Ид']]] = stripcslashes($property['Значение']);
					}		
					elseif ( !empty($product_attributes[$property['Ид']]) )
					{// Если свойств нет в файле	
						$term_id = usam_term_id_by_meta('external_code', $property['Ид'], 'usam-product_attributes');	
						$field_type = usam_get_term_metadata($term_id, 'field_type');						
						if ( $field_type == 'S')
							$value = isset($attribute_values[$term_id][$property['Значение']])?$attribute_values[$term_id][$property['Значение']]->value:$property['Значение'];
						else
							$value = $property['Значение'];	
						$this->product['attributes'][$product_attributes[$property['Ид']]] = stripcslashes($value);
					}
				} 
				unset($this->product['ЗначенияСвойств']);
			}			 
			$setting = get_option('usam_1c', ['product' => ['variation' => 0]]);
			if( strpos($this->product['Ид'], '#') === false || empty($setting['product']['variation']) ) 
			{	
				$product_id = $this->replace_product( $this->product );
				usam_clean_product_cache( $product_id );
			}
			else
			{ 
				$product_guid = explode('#', $this->product['Ид']);	
				$this->product['Ид'] = $product_guid[0];	
				$this->variations[$product_guid[1]][] = $this->product;
			}
		}
		elseif (@$names[$depth - 1] == 'Каталог' && $name == 'Товары') 
		{ 
			$this->replace_suboffers();		
		}
		elseif (@$names[$depth - 1] == 'Классификатор' && $name == 'ЕдиницыИзмерения')
		{ 
			$units = usam_get_list_units();				
			foreach ($this->units as $unit_1c) 
			{  
				foreach ($units as $unit) 
				{					
					if ( mb_strtoupper(trim($unit_1c['НаименованиеПолное'])) == mb_strtoupper($unit['title']) && !$unit['external_code'] )
					{
						$unit['external_code'] = trim($unit_1c['Код']);
						usam_edit_data( $unit, $unit['id'], 'usam_units_measure', false );	
					}
				}
			}
		}	
		elseif (!$depth && $name == 'КоммерческаяИнформация') 
		{ 		
			do_action('usam_1c_import_products', $is_full);
		}
	}
}