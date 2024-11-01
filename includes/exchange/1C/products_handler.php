<?php
if (!defined('ABSPATH')) exit;

abstract class USAM_Products_Handler
{
	protected $add = 0;	
	protected $update = 0;
	protected $variations = [];
	protected $errors = [];
	public $is_full = false;	
	
	function replace_product( $product_1c, $args = [] ) 
	{ 		
		$guid = $product_1c['Ид'];	
		$product_1c = apply_filters('usam_import_product_xml', $product_1c, $guid );
		if ( !$product_1c ) 
			return false;
		
		$product_id = usam_get_product_id_by_code( $guid );		
		$setting = get_option('usam_1c', ['product' => []]);		
		
		$product = [];	
		if ( isset($product_1c['Наименование']) )
			$product['post_title'] = stripcslashes(html_entity_decode($product_1c['Наименование'], ENT_QUOTES, 'UTF-8'));	
		if ( isset($product_1c['Описание']) )
			$product['post_excerpt'] = stripcslashes(html_entity_decode($product_1c['Описание'], ENT_QUOTES, 'UTF-8'));
			
		if ( !empty($setting['product']['post_status']) )
			$product['post_status'] = $setting['product']['post_status'];
		elseif ( isset($product_1c['Статус']) )
		{
			if ( $product_1c['Статус'] == 'trash' || $product_1c['Статус'] == 'Удален' )
				$product['post_status'] = 'trash';	
			elseif ( $product_1c['Статус'] == 'draft' || $product_1c['Статус'] == 'Черновик')
				$product['post_status'] = 'draft';
			elseif ($product_1c['Статус'] == 'archive' || $product_1c['Статус'] == 'Архив' )
				$product['post_status'] = 'archive';
			elseif ( $product_1c['Статус'] == 'publish' || $product_1c['Статус'] == 'Опубликован')
				$product['post_status'] = 'publish';				
		}		
		if ( usam_is_license_type('LITE') || usam_is_license_type('FREE') )
			$product['post_title'] .= ' демо лицензия';
						
		if ( isset($product_1c['Артикул']) )
			$product['productmeta']['sku'] = trim($product_1c['Артикул']);
		if ( isset($product_1c['Штрихкод']) )
			$product['productmeta']['barcode'] = trim($product_1c['Штрихкод']);
		if ( isset($product_1c['Вес']) )
			$product['productmeta']['weight'] = $product_1c['Вес'];			
		/*if ( isset($product_1c['Изготовитель']) )
		{
			$product['attributes']['country'] = usam_string_to_float(trim($product_1c['Изготовитель']['Наименование']));
		}*/
		if ( !empty($setting['product']['categories']) )
		{ 
			$category_ids = array();
			if ( !empty($product_1c['Группы']) )
			{
				foreach ( $product_1c['Группы'] as $category_guid ) 
				{
					$category_id = usam_term_id_by_meta('external_code', $category_guid, 'usam-category');
					if ( $category_id ) 
						$category_ids[] = $category_id;
				}
			}			
			$product['tax_input'] = ['usam-category' => $category_ids];	
		}
		if ( !empty($product_1c['ЗначенияРеквизитов']) ) 
		{
			foreach ($product_1c['ЗначенияРеквизитов'] as $requisite) 
			{
				$attribute_values = @$requisite['Значение'];
				if (!$attribute_values) 
					continue;
				switch ( $requisite['Наименование'] ) 
				{
					case 'Объем' :					
						$product['productmeta']['volume'] = usam_string_to_float($attribute_values[0]);
					break;					
					case 'Код' :				
						$product_1c['attributes']['1c_code'] = trim(sanitize_text_field($attribute_values[0]));
					break;
					case 'Планируемая дата поступления' :				
						$product_1c['attributes']['1c_receipt_date'] = sanitize_text_field($attribute_values[0]);
					break;					
					case 'ВидНоменклатуры' :	
						switch ( $attribute_values[0] ) 						
						{
							case 'Товар' :					
								$product['productmeta']['virtual'] = 'product';
							break;
							case 'Услуга' :					
								$product['productmeta']['virtual'] = 'service';
							break;
							case 'Подписка' :					
								$product['productmeta']['virtual'] = 'subscription';
							break;
							case 'Электронный товар' :					
								$product['productmeta']['virtual'] = 'electronic_product';
							break;
						}					
					break;				
				}
			}
		} 
		if ( !empty($product_1c['БазоваяЕдиница']) )	
		{				
			$units = usam_get_list_units( );
			$product['productmeta']['unit_measure'] = 'thing';
			if ( isset($product_1c['БазоваяЕдиница']['НаименованиеПолное']) )
			{
				$unit_measure = trim($product_1c['БазоваяЕдиница']['НаименованиеПолное']);			
				foreach( $units as $unit )
				{
					if ( $unit_measure == $unit['title'] )
					{
						$product['productmeta']['unit_measure'] = $unit['code'];
						break;
					}						
				}
			}
			else
			{
				$unit_measure = trim($product_1c['БазоваяЕдиница']);			
				foreach( $units as $unit )
				{
					if ( $unit_measure == $unit['external_code'] )
					{
						$product['productmeta']['unit_measure'] = $unit['code'];
						break;
					}						
				}			
			}
		}		
		if ( empty($setting['product']['excerpt']) )			
			unset($product['post_excerpt']);
		if ( empty($setting['product']['body']) )			
			unset($product['post_content']);
		
		$attributes = [];
		if ( !empty($setting['product']['attributes']) )
		{
			if ( isset($product_1c['attributes']) )
				$attributes = apply_filters('usam_1c_import_product_attributes', $product_1c['attributes'], $product_1c, $product_id, $this->is_full);
		}
		$product = array_merge( $product, $args );
		$post_data = $this->get_product_offer( $product_1c );	
		$product = array_merge( $product, $post_data );		
		$product['productmeta']['1c_unloading_date'] = date("Y-m-d H:i:s");
		$product = apply_filters('usam_import_product_1c', $product, $attributes, $product_id, $product_1c );	
		if ( !$product_id ) 
		{	
			if ( empty($product['post_title']) ) 
				return false;
			
			$post_name = sanitize_title($product['post_title']);
			$product['post_name'] = apply_filters('usam_import_product_slug', $post_name, $product_1c );
			if ( !isset($product['post_parent']) )
				$product['post_parent'] = 0;
			$product['productmeta']['code'] = $guid;			
			$_product = new USAM_Product( $product );	
			$product_id = $_product->insert_product( $attributes );		
			$this->add++;
			$is_added = true;	
		}
		else
		{ 		
			$is_added = false;						
			if ( empty($setting['product']['title']) )
				unset($product['post_title']);		
			
			$_product = new USAM_Product( $product_id );	
			$_product->set( $product ); 		
			$_product->update_product( );	
			$_product->calculate_product_attributes( $attributes );
			$this->update++;			
		}
		if ( !empty($setting['product']['attachments']) )
		{
			$attachments = [];
			if (!empty($product_1c['Картинка'])) 
			{
				$attachments = array_filter($product_1c['Картинка']);
				$attachments = array_fill_keys($attachments, array());
			}
			if( !empty($product_1c['ЗначенияРеквизитов']) ) 
			{
				$attachment_keys = ['ОписаниеФайла' => 'description'];
				foreach ($product_1c['ЗначенияРеквизитов'] as $requisite) 
				{
					if (!isset($attachment_keys[$requisite['Наименование']])) 
						continue;

					$attribute_values = @$requisite['Значение'];
					if (!$attribute_values) 
						continue;

					$attribute_value = $attribute_values[0];
					if ( strpos($attribute_value, "import_files/") !== false ) 
					{
						list($picture_path, $attribute_value) = explode('#', $attribute_value, 2);
						if ( isset($attachments[$picture_path]) ) 
						{
							$attachment_key = $attachment_keys[$requisite['Наименование']];
							$attachments[$picture_path][$attachment_key] = $attribute_value;
						}
					}
				}
			}		
			if ( $attachments ) 
			{// Загрузить картинки
				$product['image_gallery'] = $this->replace_post_attachments($product_id, $attachments); 
				if ( !empty($product['image_gallery']) ) 
					$_product->set( $product );	
			}
			$_product->insert_media();	
		}		
		do_action('usam_after_1С_replace_product', $product_id, $is_added, $product_1c );
		return $product_id;
	}
	
	function replace_suboffers( ) 
	{ 				
		static $variations = null;
		if ( !count($this->variations) )
			return;	
		if( $variations === null )
			$variations = get_terms(['taxonomy' => 'usam-variation', 'hide_empty' => 0]);
		foreach( $this->variations as $parent_guid => &$product_variations )
		{
			$post_parent_id = usam_get_product_id_by_code( $parent_guid );
			if( !$post_parent_id )
			{					
				$product = $product_variations[0];
				$product['Ид'] = $parent_guid;
				$post_parent_id = $this->replace_product( $product );	
				unset($product);
			}
			if( !$post_parent_id )
				continue;	
			$all_combination = [];
			foreach( $product_variations as $var_k => &$suboffer )
			{						
				$product_id = $this->replace_product( $suboffer, ['post_parent' => $post_parent_id, 'product_type' => 'variation', 'post_status' => 'publish'] );			
				if ( isset($suboffer['attributes']) )
					$attributes = apply_filters('usam_1c_import_product_variations_attributes', $suboffer['attributes'], $suboffer, $product_id, $this->is_full);
				else
					$attributes = [];						
				$combination = [];			
				if ( $attributes )
				{				
					foreach( $variations as $variation )
					{
						if ( isset($attributes[$variation->slug]) && $variation->parent )
						{
							$combination[] = $variation->term_id;
							$combination[] = $variation->parent;
						}
					}
				}
				elseif ( !empty($suboffer['ХарактеристикиТовара']) )
				{					
					foreach( $variations as $variation )
					{
						foreach( $suboffer['ХарактеристикиТовара'] as $k => $item )
						{
							if ( $variation->name == trim($item['Значение']) && $variation->parent )
							{
								$combination[] = $variation->term_id;
								$combination[] = $variation->parent;
								unset($suboffer['ХарактеристикиТовара'][$k]);
								break;
							}
						}
					}				
				}
				elseif( isset($suboffer['Наименование']) && preg_match("/\((.*?)\)/i", $suboffer['Наименование'], $matches) )
				{			
					foreach( $variations as $variation )
					{
						if ( $variation->name == $matches[1] && $variation->parent )
						{
							$combination[] = $variation->term_id;
							$combination[] = $variation->parent;
							break;
						}
					}
				}
				if ( $product_id && $combination )
				{										
					$combination = array_map('intval', $combination );
					wp_set_object_terms($product_id, $combination, 'usam-variation');				
					$all_combination = array_merge( $all_combination, $combination );	
				}	
				unset($product_variations[$var_k]);				
			}
			if ( $all_combination )
			{
				wp_set_object_terms( $post_parent_id, $all_combination, 'usam-variation');				
				wp_set_object_terms( $post_parent_id, 'variable', 'usam-product_type' );
			}
			unset($this->variations[$parent_guid]);
		}	
	}

	function add_property_values( $attribute ) 
	{ 
		$term_id = 0;
		if ( !empty($attribute['Наименование']) )
		{
			$term_id = usam_term_id_by_meta('external_code', $attribute['Ид'], 'usam-product_attributes');		
			if ( $attribute['ТипЗначений'] == 'Строка' )
				$field_type = 'T';
			if ( $attribute['ТипЗначений'] == 'Число' )
				$field_type = 'O';
			elseif ( $attribute['ТипЗначений'] == 'Время' )			
				$field_type = 'TIME';
			elseif ( $attribute['ТипЗначений'] == 'Справочник' )
				$field_type = 'S';	//M несколько					
			else
				$field_type = 'T';		
			if ( empty($term_id) )
			{
				$attr = get_term_by('name', __('Основные','usam'), 'usam-product_attributes' );
				if ( empty($attr) )
				{
					$term = wp_insert_term( __('Основные','usam'), 'usam-product_attributes', ['parent' => 0]);
					if ( is_wp_error($term) || !isset($term['term_id']) )	
						return;	
					$parent_id = $term['term_id'];
				}
				else
					$parent_id = $attr->term_id;
								
				$term = term_exists( $attribute['Наименование'], 'usam-product_attributes' );
				if ( empty($term) )
				{
					$term = wp_insert_term( trim($attribute['Наименование']), 'usam-product_attributes', ['parent' => $parent_id]);	
					if ( is_wp_error($term) || !isset($term['term_id']) )	
						return;	
				}		
				$term_id = $term['term_id'];
				usam_update_term_metadata($term_id, 'external_code', $attribute['Ид']);
				if ( !empty($attribute['Значение']) )
					$field_type = 'S';	
			}
			else
			{					
				if ( $field_type == 'S' )
				{
					$product_attribute_values = usam_get_product_attribute_values(['attribute_id' => $term_id]);			
					$current_ready_options = array();
					foreach( $product_attribute_values as $option )	
					{
						$current_ready_options[$option->id] = $option->value;
					}
					if ( !empty($current_ready_options) && !empty($attribute['Значение']) )
					{
						foreach ( $attribute['Значение'] as $key => $value ) 
						{
							if ( in_array($value, $current_ready_options ) )
								unset($attribute['Значение'][$key]);
						}			
					}				
				}
			}	
			if ( $field_type == 'S' )		
			{
				if ( !empty($attribute['Значение']) )
				{
					foreach ( $attribute['Значение'] as $key => $value ) 
					{
						$value = stripslashes( $value );
						$code = isset($attribute['ИдЗначения'][$key])?$attribute['ИдЗначения'][$key]:'';
						usam_insert_product_attribute_variant(['code' => $code, 'value' => $value, 'sort' => $key+1, 'attribute_id' => $term_id]);
					}		
				}
			}
			usam_update_term_metadata($term_id, 'field_type', $field_type );			
		}
		return $term_id;
	}

	protected function get_product_offer( $offer, $attributes = [] ) 
	{
		static $storages = null, $prices = null;
		$post_data = [];				
		if ( !empty($offer['Цены']) )
		{
			if ( $prices == null )
			{
				$type_prices = usam_get_prices( );
				$prices = array( );
				foreach ($type_prices as $price) 
				{
					if ( isset($price['external_code']) )
						$prices[$price['external_code']] = $price['code'];
				}
			}					
			foreach( $offer['Цены'] as $t_price ) 
			{
				if ( isset($prices[$t_price['ИдТипаЦены']]) )
				{
					$price = isset($t_price['ЦенаЗаЕдиницу']) ? usam_string_to_float($t_price['ЦенаЗаЕдиницу']) : null;		
					if ( !is_null($price) ) 
					{
						$coefficient = isset($t_price['Коэффициент']) ? usam_string_to_float($t_price['Коэффициент']) : null;					
						if (!is_null($coefficient)) 
							$price *= $coefficient;
					} 				
					$post_data['prices']['price_'.$prices[$t_price['ИдТипаЦены']]] = $price;
				}
			}
		}
		if ( $attributes ) 
		{		
			
		}
		if ( !empty($offer['Склад']) )
		{
			if ( $storages === null )
				$storages = usam_get_storages(['fields' => 'code=>meta_key']);	
			foreach ( $offer['Склад'] as $storage ) 
			{			
				if ( isset($storages[$storage['ИдСклада']]) )
					$post_data['product_stock'][$storages[$storage['ИдСклада']]] = $storage['КоличествоНаСкладе'];
			}
		}	
		return $post_data;
	}
		
	protected function replace_offer_post_meta( $product_id, $offer, $attributes = [] ) 
	{	
		$post_data = $this->get_product_offer( $offer, $attributes );
		if ( !empty($post_data) )
		{
			$_product = new USAM_Product( $product_id );			
			$_product->set( $post_data );	
			if ( !empty($post_data['prices']) )
				$_product->save_prices( );	
			if ( !empty($post_data['product_stock']) )
			{
				$_product->save_stocks();
				usam_update_product_meta( $product_id, 'balance_update', date("Y-m-d H:i:s") );
			}	
			do_action('usam_post_offer_meta', $product_id, $offer, $this->is_full);
		}
	}
	
	function replace_term( $guid, $name, $parent_guid, $taxonomy, $order, $is_full) 
	{
		$term_id = usam_term_id_by_meta('external_code', $guid, $taxonomy);
		if ( $term_id ) 
			$term = get_term($term_id, $taxonomy);

		$parent = $parent_guid ? usam_term_id_by_meta('external_code', $parent_guid, $taxonomy) : null;
		if ( empty($term) ) 
		{
			$term = term_exists( $name, $taxonomy, $parent );
			if ( empty($term) )
			{		
				$term = wp_insert_term($name, $taxonomy, ['parent' => $parent]);		
				usam_check_wpdb_error();
				usam_check_wp_error($term);
			}
			$term_id = $term['term_id'];
			usam_update_term_metadata($term_id, 'external_code', $guid);
			wp_cache_set("usam_term_external_code-{$guid}", $term_id);
		}
		else
		{ 
			$args = array();
			if (trim($name) != $term->name) 
				$args['name'] = $name;
			if ( $parent != $term->parent ) 
				$args['parent'] = $parent;		
			if ( count($args) > 0 )
			{
				$result = wp_update_term($term_id, $taxonomy, $args);
				usam_check_wp_error($result);
			}
		}
		if ( $is_full ) 
			usam_update_term_metadata( $term_id, 'sort', $order );
		return $term_id;
	}

	function replace_post_attachments( $product_id, $attachments ) 
	{
		$data_dir = USAM_EXCHANGE_DIR . "catalog";
		$attachment_path_by_hash = array();
		foreach ($attachments as $attachment_path => $attachment) 
		{
			$attachment_path = "$data_dir/$attachment_path";
			if (!file_exists($attachment_path)) 
				continue;

			$attachment_hash = md5_file($attachment_path);
			$attachment_path_by_hash[$attachment_hash] = $attachment_path;		
		}
		$post_attachments = usam_get_product_images( $product_id );	
		wp_cache_delete($product_id, "usam_product_images");
		$attachment_ids = [];
		foreach ($post_attachments as $post_attachment) 
		{
			$post_attachment_path = get_attached_file($post_attachment->ID, true);
			if ( file_exists($post_attachment_path) ) 
			{
				$post_attachment_hash = md5_file($post_attachment_path);
				if ( isset($attachment_path_by_hash[$post_attachment_hash]) )
				{				
					@unlink($attachment_path_by_hash[$post_attachment_hash]);
					unset($attachment_path_by_hash[$post_attachment_hash]);				
					$attachment_ids[] = $post_attachment->ID;
					continue;
				}
			}			
			if ( wp_delete_attachment($post_attachment->ID, true) === false ) 
				usam_error("Failed to delete post attachment");
		}
		if ( !empty($attachment_path_by_hash) )
		{		
			$attachment_hash_by_path = array_flip($attachment_path_by_hash);	
			foreach ($attachments as $attachment_path => $attachment) 
			{
				$attachment_path = "$data_dir/$attachment_path";
				if ( file_exists($attachment_path)) 
				{				
					if ( !empty($attachment_hash_by_path[$attachment_path]) ) 
					{							
						$attachment_id = @media_handle_sideload(['tmp_name' => $attachment_path, 'name' => basename($attachment_path)], $product_id, @$attachment['description']);
						usam_check_wp_error($attachment_id);
						  
						$uploaded_attachment_path = get_attached_file($attachment_id);
						if ($uploaded_attachment_path) 
							copy($uploaded_attachment_path, $attachment_path); 
						
						$attachment_ids[] = $attachment_id;	
					}
					@unlink($attachment_path);	
				}
			}
		}
		return $attachment_ids;
	}
	
	function update_prices( $product_id, $prices_1c ) 
	{ 
		static $prices = null;						
		if ( $prices_1c )
		{
			if ( $prices == null )
				$prices = usam_get_prices(['fields' => 'external_code=>code']);
			$product_prices = [];		
			foreach ($prices_1c as $t_price) 
			{
				if ( isset($prices[$t_price['ИдТипаЦены']]) )
				{					
					$price = isset($t_price['ЦенаЗаЕдиницу']) ? usam_string_to_float($t_price['ЦенаЗаЕдиницу']) : null;						
					if ( !is_null($price) ) 
					{
						$coefficient = isset($t_price['Коэффициент']) ? usam_string_to_float($t_price['Коэффициент']) : null;					
						if (!is_null($coefficient)) 
							$price *= $coefficient;
					} 				
					$product_prices['price_'.$prices[$t_price['ИдТипаЦены']]] = $price;
				}
			}
			usam_edit_product_prices( $product_id, $product_prices );
		}	
	}
	
	function update_stocks( $product_id, $stocks_1c ) 
	{ 			
		static $storages = null;		
		
		if ( $stocks_1c )
		{
			if ( $storages === null )
				$storages = usam_get_storages(['fields' => 'code=>meta_key']);			
			$product_stock = [];
			foreach ( $stocks_1c as $storage ) 
			{			
				if ( isset($storages[$storage['Ид']]) )
					$product_stock[$storages[$storage['Ид']]] = $storage['Количество'];
			}
			$_product = new USAM_Product( $product_id );
			$_product->set(['product_stock' => $product_stock]);
			$_product->save_stocks();
		}			
	}
	
	function update_product_attributes( $attributes ) 
	{ 
		$product_attributes = [];			
		$term_ids = array();			
		$codes = array();		
		foreach ($attributes as $code => $attribute )
		{
			$term_id = usam_term_id_by_meta('external_code', $attribute['Ид'], 'usam-product_attributes');					
			if ( empty($term_id) )
				$codes[] = $code;	
			
			$term_id = $this->add_property_values( $attribute );
			$term_ids[] = $term_id;
			$attributes[$code]['term_id'] = $term_id;									
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
		foreach ($attributes as $code => $attribute)
		{
			$values = array();	
			if ( isset($attribute['ИдЗначения']) )
			{ 					
				foreach ($attribute['ИдЗначения'] as $key => $id )
				{
					if ( isset($attribute['Значение'][$key]) && isset($attribute_values[$attribute['term_id']][$id]) )
						$values[$id] = $attribute_values[$attribute['term_id']][$id]->id;							
				}
				unset($attributes[$code]['ИдЗначения']);
				unset($attributes[$code]['Значение']);
			}				
			$attributes[$code]['values'] = $values;				
		}	
		return $product_attributes;
	}
	
	public function get_results( ) 
	{
		return ['add' => $this->add, 'update' => $this->update];
	}
	
	protected function set_log_file( $info )
	{	
		usam_log_file( $info, '1C' );
	}
}