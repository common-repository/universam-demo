<?php
function usam_clean_product_cache( $product_id )
{
	wp_cache_delete($product_id, "usam_product_attribute");
	wp_cache_delete($product_id, "usam_product_meta");
	wp_cache_delete($product_id, "usam_post_meta");
	wp_cache_delete($product_id, "usam_product_price");	
	wp_cache_delete($product_id, "usam_product_stock");
	wp_cache_delete($product_id, "usam_product_images");
	wp_cache_delete($product_id, "usam_user_product_list");	
	wp_cache_delete($product_id, "usam_current_product_discount");	
	wp_cache_delete($product_id, "usam_associated_product");
	wp_cache_delete($product_id, "usam_product_attributes_slug");	
	wp_cache_delete($product_id, "usam_product_sku");
	wp_cache_delete($product_id, "usam_product_code");	
	clean_post_cache($product_id );
}

function usam_get_link_pricelist( $id ) 
{
	return home_url()."?usam_action=download_price&id={$id}";
}	

function usam_check_product_type_sold( $type, $product_id = null )
{
	if ( $product_id == null )
		$product_id = get_the_ID();	
	
	return usam_get_product_type_sold( $product_id ) == $type?true:false;
}

function usam_is_product_under_order( $product_id = null )
{
	if ( $product_id == null )
		$product_id = get_the_ID();	
	
	$result = usam_get_product_meta( $product_id, 'under_order' );
	return apply_filters('usam_product_under_order', $result, $product_id );
}

function usam_get_product_type_sold( $product_id = null )
{
	if ( $product_id == null )
		$product_id = get_the_ID();	
	
	$types = get_option('usam_types_products_sold', ['product', 'services']);
	if ( count($types) < 2 )
		return current($types);
	else
	{
		$type = usam_get_product_meta($product_id, 'virtual');		
		return $type ? $type : current($types);
	}
}

function usam_get_product_bonuses( $product_id = null, $code_price = null )
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	if ( $code_price === null )	
		$code_price = usam_get_customer_price_code();
	
	$bonuses = usam_get_product_bonus_settings( $product_id ); 	
	$bonuses['value'] = (int)$bonuses['value'];
	$bonuses['type'] = isset($bonuses['type']) ? $bonuses['type'] : 'f';
	if ( $bonuses['type'] == 'f' )
		$bonus = $bonuses['value'];
	else							
		$bonus = round($bonuses['value']*usam_get_product_price($product_id, $code_price)/100,0);
	return $bonus;
}

function usam_get_product_bonus_settings( $product_id = null, $code_price = null )
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	if ( $code_price === null )	
		$code_price = usam_get_customer_price_code();
			
	$bonuses = usam_get_product_property( $product_id, 'bonuses' );	
	if ( !empty($bonuses[$code_price]) )
	{
		$bonuses[$code_price]['value'] = (int)$bonuses[$code_price]['value'];
		return $bonuses[$code_price];
	}
	return ['value' => 0, 'type' => 'p'];
}
				
// Получить объем
function usam_get_product_volume( $product_id = null, $out_unit = 'm'  )
{	
	if ( $product_id == null )
		$product_id = get_the_ID();
		
	$volume = usam_get_product_property( $product_id, 'volume' );	
	$volume = usam_convert_volume( $volume, $out_unit );	
	return $volume;	
}

function usam_get_product_weight( $product_id, $out_unit = false, $display_name_weight_units = true )
{
	$weight = usam_string_to_float(usam_get_product_meta( $product_id, 'weight' ));		
	if ( $weight ) 
	{
		$weight_unit = get_option( 'usam_weight_unit', 'kg' );
		if ( $out_unit )
		{			
			$weight = usam_convert_weight( $weight, $out_unit, $weight_unit );
			$weight_unit = $out_unit;			
		}
		if ( $display_name_weight_units )
			$weight = $weight.' '.usam_get_name_weight_units( $weight_unit );
	}
	return $weight;
}

function usam_is_weighted_product( $product_id = null )
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$unit_measure_code = usam_get_product_property( $product_id, 'unit_measure_code' );
	if ( $unit_measure_code == 'kilogram' || $unit_measure_code == 'gram' || $unit_measure_code == 'meter' || $unit_measure_code == 'liter' )
		return true;
	return false;
}

function usam_get_product_property( $product_id, $property, $id_main_site = true )
{			
	$result = '';
	switch ( $property ) 
	{
		case 'content' :
			$result = get_the_content( $product_id );
		break;			
		case 'description' :
		case 'excerpt' :
			$post = get_post( $product_id );
			$result = isset($post->post_excerpt) ? $post->post_excerpt : '';
		break;				
		case 'product_type' :
			$result = usam_get_product_type( $product_id );
		break;	
		case 'post_title' :
			$result = get_the_title( $product_id );		
		break;		
		case 'thumbnail' :
			$result = usam_get_product_thumbnail_src( $product_id, 'manage-products' );
		break;
		case 'full_image' :
			$result = usam_get_product_thumbnail_src( $product_id, 'full' );
		break;		
		case 'image' :
			$result = usam_get_product_thumbnail( $product_id, 'manage-products' );
		break;		
		case 'brand_name' :
		case 'brand' :
			$result = usam_get_product_brand_name( $product_id );
		break;
		case 'price_currency' :
			$result = usam_get_product_price_currency( $product_id );
		break;
		case 'old_price_currency' :
			$result = usam_get_product_price_currency( $product_id, true );
		break;		
		case 'discount' :
			$result = usam_get_percent_product_discount( $product_id );
		break;			
		case 'price' :
			$result = usam_get_product_price( $product_id );
		break;		
		case 'category_name' :
			$result = usam_get_product_category_name( $product_id );
		break;
		case 'sku' :
		case 'code' :
		case 'webspy_link' :		
		case 'barcode' :
			if ( $id_main_site )
				$product_id = usam_get_post_id_main_site( $product_id );
			$result = (string)usam_get_product_meta( $product_id, $property );
		break;	
		case 'width' :
		case 'length' :
		case 'height' :
		case 'volume' :
			if ( $id_main_site )
				$product_id = usam_get_post_id_main_site( $product_id );
			$result = usam_string_to_float( usam_get_product_meta($product_id, $property) );
		break;
		case 'unit' :
			if ( $id_main_site )
				$product_id = usam_get_post_id_main_site( $product_id );
			$result = usam_string_to_float( usam_get_product_meta($product_id, $property ) );
			$result = $result === 0 ? 1 : $result;
		break;
		case 'contractor' :
			if ( $id_main_site )
				$product_id = usam_get_post_id_main_site( $product_id );
			$contractor_id = usam_get_product_meta( $product_id, $property );
			if ( $contractor_id )
				$company = usam_get_company( $contractor_id );	
			$result = isset($company['name'])?$company['name']:'';
		break;		
		case 'seller' :
			$seller = usam_get_seller_product( $product_id );
			$result = isset($seller['name'])?$seller['name']:'';
		break;			
		case 'barcode_picture' :
			$result = usam_get_product_barcode( $product_id );
		break;	
		case 'is_desired' :
		case 'is_compare' :
			$k = str_replace('is_', '', $property);
			$result = usam_chek_user_product_list($k) ? usam_checks_product_from_customer_list($k, $product_id ) : false;			
		break;
		case 'purchased' :	
		case 'basket' :
		case 'comment' :		
		case 'desired' :
		case 'compare' :
		case 'subscription' :		
		case 'rating' :
		case 'rating_count' :
		case 'views' :
			$result = usam_get_post_meta( $product_id, $property, $id_main_site );
		break;					
		case 'weight_name' :
			$result = usam_get_product_weight( $product_id );
		break;	
		case 'weight' :
			$result = usam_get_product_weight( $product_id, '', false );
		break;		
		case 'weight_unit' :
			$result = get_option( 'usam_weight_unit', false );	
		break;		
		case 'dimensions' :
			if ( $id_main_site )
				$product_id = usam_get_post_id_main_site( $product_id );
			$result = usam_string_to_float(usam_get_product_meta($product_id, 'width' )).' х '. usam_string_to_float(usam_get_product_meta($product_id, 'height' )).' х '. usam_string_to_float(usam_get_product_meta( $product_id, 'length' ));
		break;			
		case 'unit_measure_code' :
			if ( $id_main_site )
				$product_id = usam_get_post_id_main_site( $product_id );
			$unit_measure = usam_get_product_meta( $product_id, 'unit_measure' );		
			$result = !empty($unit_measure)?$unit_measure:'thing';		
		break;		
		case 'name_unit_measure' :
		case 'unit_measure' :
			$result = usam_get_product_unit_name( $product_id, 'short' );
		break;
		case 'additional_units' :
			$meta = get_post_meta( $product_id, USAM_META_PREFIX.'product_metadata', true );	
			$result = !empty($meta['additional_units'])?$meta['additional_units']:array();		
		break;	
		case 'units' :
			$additional_units = usam_get_product_property($product_id,'additional_units', $id_main_site);	
			$result = [];
			if ( $additional_units )
			{				
				if ( $id_main_site )
					$product_id = usam_get_post_id_main_site( $product_id );
				$result[] = ['code' => usam_get_product_meta($product_id, 'unit_measure' ), 'in' => usam_get_product_property( $product_id, 'unit_measure' ), 'unit' => 1];
				foreach ( $additional_units as &$additional_unit )
				{					
					$unit = usam_get_unit_measure($additional_unit['unit_measure']);	
					if ( $unit )
						$result[] = ['code' => $unit['code'], 'in' => $unit['in'], 'unit' => (int)$additional_unit['unit']];	
				}
			}	
		break;			
		case 'possible_unit_measure' :
			if ( $id_main_site )
				$product_id = usam_get_post_id_main_site( $product_id );
			$unit_measure = usam_get_product_meta($product_id, 'unit_measure');
			$result = [];
			$result[] = !empty($unit_measure)?$unit_measure:'thing';				
			$additional_units = usam_get_product_property($product_id,'additional_units');	
			if ( !empty($additional_units) )
			{
				foreach ( $additional_units as $additional_unit )
					$result[] = $additional_unit['unit_measure'];
			}
		break;		
		case 'showcases' :		
			if ( $id_main_site )
				$product_id = usam_get_post_id_main_site( $product_id );
			$result = usam_get_array_metadata($product_id, 'product', 'showcases', 'number');				
		break;
		case 'storages' :
			$storages = usam_get_storages(['cache_results' => true]);					
			$result = [];
			foreach ( $storages as $storage )
			{	
				$storage->title = htmlspecialchars($storage->title);
				$storage->address = htmlspecialchars(usam_get_storage_metadata($storage->id, 'address'));
				$storage->stock = usam_get_stock_in_storage($storage->id, $product_id);	
				$storage->reserve = usam_get_reserve_in_storage($storage->id, $product_id);
				$result[] = $storage;
			}
		break;		
		case 'tabs' :
			$meta = get_post_meta( $product_id, USAM_META_PREFIX.'product_metadata', true );	
			$result = !empty($meta['tabs'])?$meta['tabs']:array();	
		break;	
		case 'bonuses' :
			$meta = get_post_meta( $product_id, USAM_META_PREFIX.'product_metadata', true );
			$result = !empty($meta['bonuses'])?$meta['bonuses']:array();		
		break;	
		case 'increase_sales_time' :
			$result = (string)usam_get_product_meta($product_id, 'increase_sales_time');
			if( $result )
				$result = get_date_from_gmt( date("Y-m-d H:i:s", $result), "Y-m-d H:i" );
		break;
		default:				
			if ( stripos($property, 'storage_') !== false )
			{				
				$storages = usam_get_storages();					
				foreach ( $storages as $storage )
				{	
					if ( $property == 'storage_'.$storage->code)
					{
						$result = usam_get_product_stock($product_id, 'storage_'.$storage->id );
						break;
					}
				}
			}
			elseif ( stripos($property, 'price_') !== false )
				$result = usam_get_product_price($product_id, $property );
			elseif ( stripos($property, 'attribute_') !== false )
			{
				$term_id = (int)str_replace('attribute_', '', $property);
				$term = get_term($term_id, 'usam-product_attributes');
				if ( $term )
					$result = usam_get_product_attribute_display( $product_id, $term->slug );	
			}
			else	
				$result = usam_get_product_attribute_display( $product_id, $property );	
		break;
			
	}	
	return $result;
}

function usam_get_columns_product_table()
{
	$columns = ['barcode' => __('Штрих-код','usam'), 'unit_measure' => __('Eд.','usam'), 'category_name' => __('Категория','usam'), 'brand_name' => __('Бренд','usam'), 'contractor' => __('Поставщик','usam')];
	if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
		$columns['seller'] = __('Продавец', 'usam');
	$product_attributes = get_terms(['hide_empty' => 0, 'orderby' => 'name', 'taxonomy' => 'usam-product_attributes', 'update_term_meta_cache' => false]);
	foreach ( $product_attributes as $product_attribute )
	{
		$columns[$product_attribute->slug] = $product_attribute->name;
	}
	return $columns;
}

function usam_get_columns_product_export() 
{	
	$columns = [
		'product_id'	  => __('ID товара','usam'),
		'post_title'	  => __('Имя товара','usam'),
		'post_name'		  => __('Приставка к URL записи','usam'),		
		'post_parent'	  => __('Родитель товара','usam'),
		'product_type'	  => __('Тип товара','usam'),
		'post_status'	  => __('Статус товара','usam'),
		'menu_order'	  => __('Сортировка товара','usam'),
		'post_date'	      => __('Дата товара','usam'),
		'post_author'	  => __('Автор товара','usam'),
		'post_excerpt'	  => __('Краткое описание','usam'),		
		'post_content'	  => __('Описание товара','usam'),
		'comment_status'  => 'comment_status',		
		'variations'	  => __('Названия вариаций','usam'),		
		'url'	          => __('Ссылка','usam'),
		'thumbnail'	      => __('Миниатюра','usam'),	
		'exel_image'	  => __('Вставить миниатюру в exel','usam'),			
		'images'	      => __('Фотографии','usam'),			
		'sku'			  => __('Артикул','usam'),		
		'reserve'		  => __('Резерв','usam'),
		'total_balance'	  => __('Общий остаток','usam'),
		'stock'		      => __('Доступный остаток','usam'),
		'code'		      => __('Внешний код','usam'),
		'unit'		      => __('Коэффициент единицы измерения','usam'),
		'unit_measure'    => __('Единица измерения','usam'),
		'weight'		  => __('Вес коробки','usam'),
		'weight_unit'     => __('Единица измерения веса коробки','usam'),
		'barcode'		  => __('Штрихкод','usam'),
		'views'           => __('Просмотры','usam'),
		'rating'		  => __('Рейтинг','usam'),
		'rating_count'    => __('Количество проголосовавших','usam'),	
		'box_length'	  => __('Длина коробки','usam'),	
		'box_width'	      => __('Ширина коробки','usam'),				
		'box_height'	  => __('Высота коробки','usam'),	
		'under_order'	  => __('Под заказ','usam'),	
		'webspy_link'	  => __('Ссылка на поставщика','usam'),		
	];	
	$taxonomies = get_taxonomies(['object_type' => ['usam-product'], 'public' => true, 'show_in_rest' => true], 'objects');
	foreach ( $taxonomies as $tax )
	{
		$name = str_replace('usam-','',$tax->name);
		$columns[$name] = $tax->label;
	}
	foreach ( $taxonomies as $tax )
	{
		$name = str_replace('usam-','',$tax->name);
		$columns[$name.'_slug'] = __('Ярлык','usam').' '.$tax->label;
	}	
	$storages = usam_get_storages();					
	foreach ( $storages as $storage )
	{	
		$columns['storage_'.$storage->code] = __('Склад','usam').' - '.$storage->title;
	}	
	$prices = usam_get_prices();					
	foreach ( $prices as $price )
	{	
		$columns['price_'.$price['code']] = __('Тип цены','usam').' - '.$price['title'];
	}	
	$product_attributes = get_terms(['hide_empty' => 0, 'orderby' => 'name', 'taxonomy' => 'usam-product_attributes']);
	foreach( $product_attributes as $term )
	{
		if ( $term->parent != 0 && $term->slug != 'brand' )
			$columns['attribute_'.$term->term_id] =__('Характеристика товара','usam').' - '.$term->name;
	}
	foreach (usam_get_data_integrations( 'trading-platforms', ['name' => 'Name', 'icon' => 'Icon'] ) as $key => $item)	
	{
		if ( $key === 'avito' )
			$columns[$key.'_id'] = $item['name'].' id';
	}
	return apply_filters('usam_product_columns_export', $columns);
}

function usam_get_columns_pricelist() 
{
	return usam_get_columns_product_export();
}

function usam_get_columns_product_import() 
{	
	$columns = [
		'post_title'	  => __('Имя товара','usam'),
		'post_name'		  => __('Приставка к URL записи','usam'),		
		'post_parent'	  => __('Родитель товара','usam'),
		'product_type'	  => __('Тип товара','usam'),
		'post_status'	  => __('Статус товара','usam'),
		'menu_order'	  => __('Сортировка товара','usam'),
		'post_date'	      => __('Дата товара','usam'),
		'post_author'	  => __('Автор товара','usam'),
		'post_excerpt'	  => __('Краткое описание','usam'),		
		'post_content'	  => __('Описание товара','usam'),
		'comment_status'  => 'comment_status',		
		'variations'	  => __('Названия вариаций','usam'),		
		'url'	          => __('Ссылка','usam'),
		'thumbnail'	      => __('Миниатюра','usam'),			
		'images'	      => __('Фотографии','usam'),
		'sku'			  => __('Артикул','usam'),	
		'code'		      => __('Внешний код','usam'),
		'unit'		      => __('Коэффициент единицы измерения','usam'),
		'unit_measure'    => __('Единица измерения (шт, метры и т.д)','usam'),
		'weight'		  => __('Вес коробки','usam'),
		'weight_unit'     => __('Единица измерения веса коробки','usam'),
		'barcode'		  => __('Штрихкод','usam'),
		'views'           => __('Просмотры','usam'),
		'rating'		  => __('Рейтинг','usam'),
		'rating_count'    => __('Количество проголосовавших','usam'),	
		'box_length'	  => __('Длина коробки','usam'),		
		'box_width'	      => __('Ширина коробки','usam'),		
		'box_height'	  => __('Высота коробки','usam'),
		'additional_units' => __('Значения дополнительных единиц измерения','usam'),
		'codes_additional_unit'	=> __('Коды дополнительных единиц измерения','usam'),
		'crosssell'       => __('Сопутствующая продукция','usam'),
		'similar'         => __('Аналоги','usam'),
		'warehouse_code'  => __('Код склада','usam'),
		'warehouse_stock' => __('Запас на складе','usam'),
		'not_limited'     => __('Запас не ограничен','usam'),
		'under_order'	  => __('Под заказ','usam'),
		'webspy_link'	  => __('Ссылка на поставщика','usam'),	
	];	
	$taxonomies = get_taxonomies(['object_type' => ['usam-product'], 'public' => true, 'show_in_rest' => true], 'objects');
	foreach ( $taxonomies as $tax )
	{
		$name = str_replace('usam-','',$tax->name);
		$columns[$name] = $tax->label;
	}
	foreach ( $taxonomies as $tax )
	{
		$name = str_replace('usam-','',$tax->name);
		$columns[$name.'_slug'] = __('Ярлык','usam').' '.$tax->label;
	}
	$storages = usam_get_storages();					
	foreach ( $storages as $storage )
	{	
		$columns['storage_'.$storage->code] = __('Склад','usam').' - '.$storage->title;
	}		
	$prices = usam_get_prices( );					
	foreach ( $prices as $price )
	{	
		$columns['price_'.$price['code']] = __('Тип цены','usam').' - '.$price['title'];		
	}		
	$columns['price'] = __('Цена (если в файле есть тип цены)','usam');
	$columns['external_code_price'] = __('Код типа цены','usam');	
	
	$rules = usam_get_discount_rules(['type_rule' => 'fix_price']);
	foreach ( $rules as $rule )
	{	
		$columns['fix_price_'.$rule->id] = __('Правило','usam').' - '.$rule->name;
	}
	$product_attributes = get_terms(['hide_empty' => 0, 'orderby' => 'name', 'taxonomy' => 'usam-product_attributes']);	
	foreach( $product_attributes as $term )
	{
		if ( $term->parent != 0 && $term->slug != 'brand' )
		{				
			$columns['attribute_'.$term->term_id] =__('Характеристика товара','usam').' - '.$term->name;
		}			
	}	
	foreach (usam_get_data_integrations( 'trading-platforms', ['name' => 'Name', 'icon' => 'Icon'] ) as $key => $item)	
	{
		if ( $key === 'avito' )
			$columns[$key.'_id'] = $item['name'].' id';
	}
	return apply_filters('usam_product_columns_import', $columns);
}

function usam_product_brand( $product_id = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	$brand = get_the_terms($product_id, 'usam-brands' );
	if ( !empty($brand[0]) )
		return $brand[0];	
	else
		return array();
}

function usam_product_category( $product_id = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$term = get_the_terms($product_id, 'usam-category' );
	if ( !empty($term[0]) )
		return $term[0];	
	else
		return array();
}

function usam_get_product_brand_name( $product_id ) 
{
	$brand = usam_product_brand( $product_id );
	$brand_name = !empty($brand->name)?$brand->name:'';
	return $brand_name;	
}

function usam_get_product_category_name( $product_id ) 
{
	$category = get_the_terms($product_id, 'usam-category' );	
	$category_name = !empty($category[0]) && !empty($category[0]->name)?$category[0]->name:'';
	return $category_name;	
}

// Узнать тип товара
function usam_get_product_type( $product_id ) 
{		
	$product_type_terms = get_the_terms( $product_id, 'usam-product_type' );		
	if ( !empty($product_type_terms[0]) ) 
		$product_type = $product_type_terms[0]->slug;	
	else
	{	
		$product_type = 'simple';
		wp_set_object_terms( $product_id, $product_type, 'usam-product_type' );		
	}
	return $product_type;
}

function usam_get_product_price_currency( $product_id = null, $old_price = false, $code_price = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();
		
	if ( $old_price )	
		$price = usam_get_product_old_price( $product_id, $code_price );			
	else
		$price = usam_get_product_price( $product_id, $code_price );	

	if ( $price == 0  )
		return '';
		
	$price = apply_filters( 'usam_do_convert_price', $price, $product_id, $old_price, $code_price );	

	$price = usam_get_formatted_price( $price, ['type_price' => $code_price]);
	/*$product_type = usam_get_product_type( $product_id );	
	if ( $product_type == 'variable' ) 
	{
		$from_text = apply_filters( 'usam_product_variation_text', __('от %s', 'usam') );
		$price = sprintf( $from_text, $price );
	} 	*/
	return $price;
}

function usam_product_price_currency( $old_price = false ) 
{	
	$product_id = get_the_ID();	
	echo usam_get_product_price_currency( $product_id, $old_price );
}

/**
 * Получить цену товара
 */
function usam_get_product_price( $product_id = null, $code_price = null, $unit_measure = null, $id_main_site = true ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	if ( empty($code_price) )	
		$code_price = usam_get_customer_price_code();

	if ( $id_main_site )
		$product_id = usam_get_post_id_main_site( $product_id );
	$price = (float)usam_get_product_metaprice( $product_id, 'price_'.$code_price );
	if ( $unit_measure !== null )
	{		
		$unit_measure_code = usam_get_product_property($product_id, 'unit_measure_code');	
		if ( $unit_measure_code !== $unit_measure )		
		{
			$unit = usam_get_product_unit($product_id, $unit_measure);	
			$main_unit = usam_get_product_property($product_id, 'unit'); 
			$price = ($price/$main_unit)*$unit;
		}	
	}
	return $price;
}

/**
 * Получить старую цену товара
 */
function usam_get_product_old_price( $product_id = null, $code_price = null, $unit_measure = null, $id_main_site = true ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	if ( $code_price === null )	
		$code_price = usam_get_customer_price_code();
	if ( $id_main_site )
		$product_id = usam_get_post_id_main_site( $product_id );
	$price = (float)usam_get_product_metaprice( $product_id, 'old_price_'.$code_price );
	if ( $unit_measure !== null )
	{
		$unit_measure_code = usam_get_product_property($product_id, 'unit_measure_code');	
		if ( $unit_measure_code !== $unit_measure )		
		{
			$unit = usam_get_product_unit($product_id, $unit_measure);	
			$main_unit = usam_get_product_property($product_id, 'unit');
			$price = ($price/$main_unit)*$unit;
		}	
	}
	return $price;
}

function usam_is_product_discount( $product_id = null, $code_price = null ) 
{
	$price = usam_get_product_price( $product_id, $code_price );	
	$old_price = usam_get_product_old_price( $product_id, $code_price );	
	if( $old_price != 0 && $old_price > $price )
		return true;
	else
		return false;
}

function usam_get_product_discount( $product_id = null, $code_price = null ) 
{				
	$price = usam_get_product_price( $product_id, $code_price );	
	$old_price = usam_get_product_old_price( $product_id, $code_price );
	if ( $old_price )
		$discount = $old_price - $price;	
	else
		$discount = 0;
	$display_discount = usam_get_formatted_price( $discount, ['type_price' => $code_price]);	
	return $display_discount;
}

function usam_get_percent_product_discount( $product_id = null, $code_price = null ) 
{				
	$price = usam_get_product_price( $product_id, $code_price );	
	$old_price = usam_get_product_old_price( $product_id, $code_price );
	if ( $old_price )
		$discount = round( ( $old_price - $price ) / $old_price * 100, 0);	
	else
		$discount = 0;
	return $discount;
}

function usam_get_product_attachment_props( $attachment_id = null, $product = false ) 
{
	$props = array(
		'title'   => '',
		'caption' => '',
		'url'     => '',
		'alt'     => '',
		'src'     => '',
		'srcset'  => false,
		'sizes'   => false,
	);
	if ( $attachment = get_post( $attachment_id ) ) 
	{
		$props['title']   = trim( strip_tags( $attachment->post_title ) );
		$props['caption'] = trim( strip_tags( $attachment->post_excerpt ) );
		$props['url']     = wp_get_attachment_url( $attachment_id );
		$props['alt']     = trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );

		// Large version.
		$src                 = wp_get_attachment_image_src( $attachment_id, 'full' );
		$props['full_src']   = $src[0];
		$props['full_src_w'] = $src[1];
		$props['full_src_h'] = $src[2];

		// Thumbnail version.
		$src                 = wp_get_attachment_image_src( $attachment_id, 'product-thumbnails' );
		$props['thumb_src']   = $src[0];
		$props['thumb_src_w'] = $src[1];
		$props['thumb_src_h'] = $src[2];

		// Image source.
		$src             = wp_get_attachment_image_src( $attachment_id, 'medium-single-product' );
		$props['src']    = $src[0];
		$props['src_w']  = $src[1];
		$props['src_h']  = $src[2];
		$props['srcset'] = wp_get_attachment_image_srcset( $attachment_id, 'medium-single-product' );
		$props['sizes']  = wp_get_attachment_image_sizes( $attachment_id, 'medium-single-product' );

		// Alt text fallbacks
		$props['alt'] = empty( $props['alt'] ) ? $props['caption']                                               : $props['alt'];
		$props['alt'] = empty( $props['alt'] ) ? trim( strip_tags( $attachment->post_title ) )                   : $props['alt'];
		$props['alt'] = empty( $props['alt'] ) && $product ? trim( strip_tags( get_the_title( $product->ID ) ) ) : $props['alt'];
	}
	return $props;
}

function usam_product_thumbnail( $product_id = null, $size = 'product-thumbnails', $title = '', $lzy = true ) 
{
	echo usam_get_product_thumbnail( $product_id, $size, $title, $lzy );
}
/**
 * Получает html код миниатюры изображения товара
 */
function usam_get_product_thumbnail( $product_id = null, $size = false, $alt = '', $lzy = true ) 
{ 
	global $lazy_loading;
	if ( $product_id == null )
	{
		$product_id = get_the_ID();	
		$alt = get_the_title( $product_id );		
	}	
	if ( $size == false )
	{
		if ( is_single() )
			$size = 'single';
		else
			$size = 'product-thumbnails';
	}	
	$thumbnail = usam_the_product_thumbnail($product_id, $size );	
	if( $thumbnail )
	{
		if ( isset($lazy_loading) )
			$lzy = $lazy_loading? true : false;		
		if ( $lzy )
		{
			$out = "<img id='thumb_product_{$product_id}' src='".$thumbnail['src']."' alt='{$alt}' itemprop='image' class='product_thumbnail' width='".$thumbnail['width']."' height='".$thumbnail['height']."' loading='lazy'>";
		}
		else
			$out = "<img id ='thumb_product_{$product_id}' src='".$thumbnail['src']."' alt='{$alt}' itemprop='image' class='product_thumbnail' width='".$thumbnail['width']."' height='".$thumbnail['height']."'>";
	}
	else
	{					
		$src = usam_get_no_image_uploaded_file( $size );	
		$src = apply_filters( 'usam_product_no_image', $src, $product_id, $size );		
		$out = '<img id ="thumb_product_'.$product_id.'" alt="'.__("Нет изображения","usam").'" src="'.$src.'" loading="lazy"/>';
	}
	return $out;
}

/**
 * Получает миниатюрное изображение для продукта
 */
function usam_the_product_thumbnail( $product_id = null, $size = false ) 
{	
	$thumbnail = false;		
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$thumbnail_id = (int)get_post_meta( $product_id, '_thumbnail_id', true );
	if ( ! $thumbnail_id )	
	{ // Если нет миниатюрами не найдено для элемента, получить его post_parent
		$product = get_post( $product_id );
		if ( !empty($product->post_parent) )	
			$thumbnail_id = get_post_thumbnail_id( $product->post_parent );
	}	
	if ( empty($thumbnail_id) )	
		return;	
	
	if ( $size == false )
	{
		if ( is_single() )
			$size = 'single';
		else
			$size = 'product-thumbnails';
	}		
	if ( in_the_loop() || is_admin() && $GLOBALS['wp_query'] && !empty($GLOBALS['wp_query']->posts) )
	{		
		update_post_thumbnail_cache( ); 
	}		
	if ( is_array($size) && $size[0] && $size[1] )
	{		
		$width = $size[0];
		$height = $size[1];

		// вычисления высоты на основе соотношения первоначальных размеров
		if ( $height == 0 || $width == 0 )
		{
			$attachment_meta = get_post_meta( $thumbnail_id,'_wp_attachment_metadata', false );
			$original_width = $attachment_meta[0]['width'];
			$original_height = $attachment_meta[0]['height'];
			if( $width != 0 )
			{
				$height = ( $original_height / $original_width ) * $width;
				$height = round( $height, 0 );
			} 
			elseif ( $height != 0 ) 
			{
				$width = ( $original_width / $original_height ) * $height;
				$width = round( $width, 0 );
			}
		}
		$src = usam_create_image_by_size( $thumbnail_id, $width, $height );		
		$thumbnail = ['src' => $src, 'width' => $width, 'height' => $height];
	}	
	elseif( $size == 'single' || $size == 'medium-single-product') 
	{
		$src = wp_get_attachment_image_src( $thumbnail_id, 'medium-single-product' );
		if ( $src )
			$thumbnail = ['src' => $src[0], 'width' => $src[1], 'height' => $src[2]];
	} 
	elseif ( $size == 'manage-products' || $size == 'small-product-thumbnail')
	{
		$src = wp_get_attachment_image_src( $thumbnail_id, 'small-product-thumbnail' ); 
		if ( $src )
			$thumbnail = ['src' => $src[0], 'width' => $src[1], 'height' => $src[2]];
	}	
	else
	{ 
		$src = wp_get_attachment_image_src( $thumbnail_id, $size );
		if ( $src )
			$thumbnail = ['src' => $src[0], 'width' => $src[1], 'height' => $src[2]];
	}	
	if ( $thumbnail )
	{
		if ( is_ssl() )
			$thumbnail['src'] = str_replace( 'http://', 'https://', $thumbnail['src'] );		
			
		return $thumbnail; 
	}
	else 
		return false;
}

function usam_get_product_thumbnail_src( $product_id = 0, $size = false ) 
{
	$thumbnail = usam_the_product_thumbnail($product_id, $size );	
	if ( empty($thumbnail['src']) )
	{
		$image = usam_get_no_image_uploaded_file( $size );
		$image = apply_filters( 'usam_product_no_image', $image, $product_id, $size );		
	}
	else
		$image = $thumbnail['src'];		
	return $image;
}

// Получить фотографии товара
function usam_get_product_images( $product_id, $number = 0 )
{
	$cache_key = "usam_product_images";
	$attachments = wp_cache_get( $product_id, $cache_key );	
	if ($attachments === false) 
	{	
		$attachments = (array)usam_get_posts(['post_type' => 'attachment', 'post_parent' => $product_id, 'orderby' => 'menu_order', 'order' => 'ASC']);
		foreach ( $attachments as $k => $attachment )
		{
			unset($attachments[$k]->guid);
			unset($attachments[$k]->to_ping);
			unset($attachments[$k]->pinged);
			unset($attachments[$k]->ping_status);
			unset($attachments[$k]->comment_count);
			unset($attachments[$k]->post_password);
			unset($attachments[$k]->post_content_filtered);
		}			
		wp_cache_set($product_id, $attachments, $cache_key);
	}
	if ( $number )
	{
		$number--;
		$results = array();
		foreach ( $attachments as $key => $attachment ) 
		{
			$results[] = $attachment;
			if ( $key == $number )
				break;
		}
		return $results;
	}
	return $attachments;
}

function usam_get_product_images_urls( $product_id = null, $size = 'full' )
{	
	if ( $product_id == null )
		$product_id = get_the_ID();
	$urls = [];
	$attachments = usam_get_product_images( $product_id );
	if ( !empty($attachments) ) 
	{ 
		foreach ( $attachments as $attachment ) 
			$urls[] = wp_get_attachment_image_url($attachment->ID, $size );	
	}	
	return $urls;	
}

function usam_get_product_video( $product_id = null )
{	
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	static $terms = null;
	if ( $terms === null )
		$terms = get_terms(['fields' => 'id=>slug','hide_empty' => 0, 'taxonomy' => 'usam-product_attributes', 'usam_meta_query' => [['key' => 'field_type','value' => ['YOUTUBE'], 'compare' => 'IN']]]);	
	$video = [];
	foreach( $terms as $slug )
	{
		$attribute_value = usam_get_product_attribute( $product_id, $slug );
		if ( $attribute_value )
			$video[] = $attribute_value;
	}
	return $video;	
}
/*
 * Получает URL продукта
 */
function usam_product_url( $product_id = null )
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$post = get_post( $product_id );
	if ( isset($post->post_parent) && $post->post_parent > 0) 
		return get_permalink($post->post_parent);
	else 
		return get_permalink($product_id);	
}

function usam_product_has_new( $product_id = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$post = get_post( $product_id );
	$day = (int)get_option("usam_number_days_product_new", 14);
	if ( $day )
	{
		$t = mktime(0,0,0,date('m'),date('d'),date('Y'));								
		$t = $t-$day*86400;
		if (strtotime($post->post_date)>=$t) 
		{
			return true;
		}
	}
	return false;
}

/**
 * Проверка, имеет ли продукт вариации или нет.
 */
function usam_product_has_variations( $product_id = 0 )
{	
	if ( ! $product_id )
		$product_id = get_the_ID();
	
	$product_type = usam_get_product_type( $product_id );
	if ( $product_type == 'variable' )
	{
		return true;
	}
	return false;
}


function usam_get_product_variations( $id = 0 )
{
	static $has_variations = array();

	if ( ! $id )
		$id = get_the_ID();

	if ( ! isset($has_variations[$id]) ) 
	{
		$children = get_children(['post_parent' => $id, 'post_type' => 'usam-product', 'numberposts' => 1, 'post_status' => ['inherit', 'publish']]);		
		$has_variations[$id] = !empty( $children );
	}
	return $has_variations[$id];
}

/**
 * Возвращает информацию о наличии запаса
 */
function usam_product_has_stock( $product_id = null, $code = null ) 
{		
	if ( $product_id == null )
		$product_id = get_the_ID();	
	
	$type = usam_get_product_type_sold( $product_id );
	if ( $type != 'product' && $type != 'service' )
		return true;

	$stock = usam_product_remaining_stock( $product_id, $code );
	if ( $stock > 0 )
		return true;
	else	
		return false;
}
/**
 * Вернуть остаток товара
 */
function usam_product_remaining_stock( $product_id = null, $code = null, $unit_measure = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();	

	$type = usam_get_product_type_sold( $product_id );		
	if ( $type != '' && $type != 'product' && $type != 'service' )
		return USAM_UNLIMITED_STOCK;

	if ( $code == null )
		$code = usam_get_customer_balance_code();	
	$stock = usam_get_product_stock( $product_id, $code );	
	if ( $stock >= USAM_UNLIMITED_STOCK )
		$stock = USAM_UNLIMITED_STOCK;
	else
	{
		if ( usam_is_weighted_product( $product_id ) )
			$stock = usam_string_to_float( $stock );
		else
			$stock = (int)$stock;		
		
		$main_unit = usam_get_product_property($product_id, 'unit');
		$stock = $stock*$main_unit;
		if ( $unit_measure !== null )
		{			
			$unit_measure_code = usam_get_product_property($product_id, 'unit_measure_code');	
			if ( $unit_measure_code !== $unit_measure )
			{
				$unit = usam_get_product_unit($product_id, $unit_measure);					
				$stock = $stock/$unit;
			}
		}		
	}
	return $stock;
}

function usam_get_stock_in_storage( $storage_id, $product_id = null, $unit_measure = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();

	$stock = usam_get_product_stock($product_id, 'storage_'.$storage_id);
	if ( $stock >= USAM_UNLIMITED_STOCK )
		$stock = USAM_UNLIMITED_STOCK;
	elseif ( usam_is_weighted_product( $product_id ) )
		$stock = usam_string_to_float( $stock );
	else
		$stock = (int)$stock;
	if ( $unit_measure != null )
		$stock = usam_get_formatted_quantity_product_unit_measure( $stock, $unit_measure );
	return $stock;
}

function usam_get_formatted_quantity_product_unit_measure( $quantity, $unit_measure )
{	
	$unit = usam_get_unit_measure( $unit_measure );	
	if ( empty($unit) )
		$unit = usam_get_unit_measure('thing');
	$quantity = usam_get_formatted_quantity_product($quantity, $unit_measure);		
	return $quantity.' '.$unit['short'];
}

function usam_get_reserve_in_storage( $storage_id, $product_id = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();
		
	$stock = usam_get_stock_in_storage($storage_id, $product_id);
	if ( $stock == USAM_UNLIMITED_STOCK )
		return 0;
	else
	{
		$reserve = usam_get_product_stock($product_id, 'reserve_'.$storage_id);
		if ( usam_is_weighted_product( $product_id ) )
			$reserve = usam_string_to_float( $reserve );
		else
			$reserve = (int)$reserve;
	}	
	return $reserve;
}

// Получить единицу измерения
function usam_get_product_unit_name( $product_id, $key = 'title' )
{
	$unit_measure = usam_get_product_meta( $product_id, 'unit_measure' );
	$unit = usam_get_unit_measure( $unit_measure );
	if ( !empty($unit) )
		return $unit[$key];
	else
	{
		$unit = usam_get_unit_measure('thing');			
		return $unit[$key];
	}
}

function usam_get_formatted_quantity_product( $quantity, $unit_measure ) 
{	
	if( $unit_measure != 'kilogram' && $unit_measure != 'liter' && $unit_measure != 'meter' )
		$quantity = (int)$quantity;	
	return $quantity;	
}

function usam_get_product_unit( $product_id, $unit_measure = null )
{
	$unit_measure_code = usam_get_product_property($product_id, 'unit_measure_code');
	if ( $unit_measure === null )	
		$unit_measure = $unit_measure_code;

	if ( $unit_measure_code == $unit_measure )
		$unit = usam_get_product_property($product_id, 'unit');
	else
	{ 
		$unit = 1;
		$additional_units = usam_get_product_property( $product_id, 'additional_units' );
		if ( !empty($additional_units) )
		{
			foreach( $additional_units as $additional_unit )
			{
				if ( $additional_unit['unit_measure'] == $unit_measure )
				{
					$unit = $additional_unit['unit'];
					break;
				}
			}
		}	
	}
	return $unit?$unit:1;	
}

// Вывести штрих-код
function usam_get_product_barcode( $product_id )
{
	$barcode = usam_get_product_meta( $product_id, 'barcode' );
	$barcode_html = '';		
	if ( $barcode != null )
	{				
		require_once( USAM_FILE_PATH . '/admin/includes/barcode/barcode.php' );
		$b = new USAM_Barcode();
		$bars = $b->encode($barcode, 'EAN');		
		$barcode_html = $b->outhtml($barcode, $bars['bars'], 1, 0, ['top'=>0, 'bottom'=>0, 'left'=>0, 'right'=>0]);
	}	
	return $barcode_html;
}	

/**
 * Получить артикул товара
 */
function usam_product_sku( $id = null ) 
{
	if ( $id = null )		
		$id = get_the_ID();

	$product_sku = usam_get_product_meta( $id, 'sku' );
	return esc_attr( $product_sku );
}

function usam_products_stock_updates( $products, $storage_id, $add = false ) 
{		
	$products_ids = array();					
	foreach( $products as $product) 
	{				
		$products_ids[] = $product->product_id;
		
		$current_storage_stock = usam_get_product_stock($product->product_id, 'storage_'.$storage_id );					
		if ( USAM_UNLIMITED_STOCK <= (int)$current_storage_stock ) 
			continue;					
		
		if( $add )
			$remaining_stock = $current_storage_stock + $product->quantity;			
		else
			$remaining_stock = $current_storage_stock - $product->quantity;			
		
		usam_update_product_stock($product->product_id, 'storage_'.$storage_id, $remaining_stock);					
	}
	if ( !empty($products_ids) )
		usam_recalculate_stock_products( $products_ids );	
}	

// Пересчитать остатки у заданных товаров
function usam_recalculate_stock_products( $products_ids = [] )
{	
	$args = array();
	if ( !empty($products_ids) )
	{
		if ( count($products_ids) < 100 )				
		{
			update_postmeta_cache( $products_ids );
			foreach ( $products_ids as $product_id )
			{				
				usam_recalculate_stock_product( $product_id );
			}			
			return false;
		}
		else
			$args['post__in'] = $products_ids;
	}
	$total = usam_get_total_products( $args );	
	if ( !empty($total) )	
	{
		if ( empty($args) )
			$key = 'recalculate_stock';
		else
			$key = 'recalculate_stock_'.time();		
		usam_create_system_process( __("Пересчет остатков","usam" ), $args, 'recalculate_stock_products', $total, $key );
	}
}

// Пересчитать остаток у заданного товара
function usam_recalculate_stock_product( $product_id )
{
	global $wpdb;
	
	$all_stock = 0;
	$total_balance = 0;
	
	$sales_area = usam_get_sales_areas();
	$product_type = usam_get_product_type( $product_id );	
	$selected_sales_areas = ['stock' => [], 'reserve' => [] ];	
	$storages = usam_get_storages(['cache_meta' => true]);
	if ( $product_type == 'variable' )
	{ 
		global $wpdb;		
		$sql = "SELECT pm.meta_value, pm.meta_key FROM {$wpdb->posts} AS p INNER JOIN ".USAM_TABLE_STOCK_BALANCES." AS pm ON pm.product_id = p.ID WHERE p.post_type='usam-product' AND p.post_parent={$product_id}";
		$metas = $wpdb->get_results( $sql );		
		foreach ( $metas as $meta )
		{
			foreach ( $sales_area as $sale_area )
			{		
				if ( $meta->meta_key == 'stock_'.$sale_area['id'] )
				{					
					if ( isset($selected_sales_areas['stock'][$sale_area['id']]) )						
						$selected_sales_areas['stock'][$sale_area['id']] += $meta->meta_value;	
					else
						$selected_sales_areas['stock'][$sale_area['id']] = $meta->meta_value;					
					break;
				}				
			}
			if ( $meta->meta_key == 'stock' )
				$all_stock += $meta->meta_value;
			
			if ( $meta->meta_key == 'total_balance' )
				$total_balance += $meta->meta_value;
		}
	}	
	else
	{		
		foreach ( $storages as $storage)
		{						
			$meta_value = usam_get_product_stock($product_id, 'storage_'.$storage->id);							
			if ( $storage->shipping == 1 )
			{
				$reserve = usam_get_reserve_in_storage( $storage->id, $product_id );
				if ( !empty($sales_area) )
				{
					foreach ( $sales_area as $sale_area )
					{								
						if ( usam_get_storage_metadata( $storage->id, 'sale_area_'.$sale_area['id'] ) )
						{							
							if ( isset($selected_sales_areas['stock'][$sale_area['id']]) )
								$selected_sales_areas['stock'][$sale_area['id']] += $meta_value-$reserve;
							else
								$selected_sales_areas['stock'][$sale_area['id']] = $meta_value-$reserve;						
						}
					}	
				}
				$all_stock += $meta_value-$reserve;
				$total_balance += $meta_value;
			}	
		}			
	}		
	foreach ( $selected_sales_areas['stock'] as $sale_area_id => $stock )
	{	
		$stock_db = usam_get_product_stock($product_id, 'stock_'.$sale_area_id ); 
		if ( $stock_db != $stock )		
		{
			$stock = $stock > USAM_UNLIMITED_STOCK ? USAM_UNLIMITED_STOCK : $stock;
			usam_update_product_stock($product_id, 'stock_'.$sale_area_id, $stock );			
		}
	}
	$all_stock = $all_stock > USAM_UNLIMITED_STOCK ? USAM_UNLIMITED_STOCK : $all_stock;
	$all_stock_db = usam_get_product_stock($product_id, 'stock' );
	if ( $all_stock_db != $all_stock )
	{	
		usam_update_product_stock($product_id, 'stock', $all_stock );		
		do_action( 'usam_update_stock', $product_id, $all_stock, $all_stock_db );
		if ( $all_stock_db === 0.00 && $all_stock > 0.00 )
			do_action( 'usam_product_arrived', $product_id, $all_stock );
	}	
	$total_balance = $total_balance > USAM_UNLIMITED_STOCK ? USAM_UNLIMITED_STOCK : $total_balance;
	$total_balance_db = usam_get_product_stock($product_id, 'total_balance' );
	if ( $total_balance_db != $total_balance )
	{		
		usam_update_product_stock($product_id, 'total_balance', $total_balance );			
	}
	if ( $product_type == 'variation' ) 
	{
		$product = get_post( $product_id );		
		if ( !empty($product->post_parent) )
			usam_recalculate_stock_product( $product->post_parent );
	}
}

// Рассчитать прибыль
function usam_calculate_profit( $product_id, $t_price ) 
{		
	$setting_price = usam_get_setting_price_by_code( $t_price );
	if ( $setting_price['type'] == 'P' )
		return false;
	
	$prices = usam_get_prices(['type' => 'P']);	
	if ( empty($prices) )
		return false;
	
	$product_price = usam_get_product_price( $product_id, $t_price );	
	$profit = false;
	foreach ( $prices as $price)
	{
		$purchasing_price = usam_get_product_price( $product_id, $price['code'] );	
		if ( $purchasing_price )
		{
			$profit = $product_price - $purchasing_price;
			break;
		}
	}
	return $profit;
}

add_filter('post_type_link', 'usam_product_link', 10, 3 );
/* Создает ссылку на товар. Используется при обновлении товара и при получении ссылки на товар*/
function usam_product_link( $permalink, $post, $leavename )
{
	global $wp_query, $usam_query, $wp_current_filter;

	if ($post->post_type != 'usam-product')
		return $permalink;
	
	$permalink_structure = get_option('permalink_structure');
	if ( empty($permalink_structure) )
		return $permalink;
	
	$permalink_structure = get_option( 'usam_permalinks' );		
	$product_permalink = empty($permalink_structure['product_base']) ? '' : trim($permalink_structure['product_base']);	
	if ( $product_permalink != '' ) 
	{ 			
		$name = $leavename ? '' : $post->post_name;
		$rewritereplace = array( '%postname%' => $name );
		if (strpos($product_permalink, 'product_cat') !== false) 
		{
			$product_categories = get_the_terms( $post->ID, 'usam-category' ); 
			$count_categories = empty($product_categories)?0:count( $product_categories );
			if ( $count_categories == 0 ) 
				$category_slug = 'uncategorized';
			elseif ( $count_categories > 1 ) 
			{   // Если продукт связан с несколькими категориями, определить, какие из них выбрать
				$product_category_slugs = array( );
				foreach ( (array)$product_categories as $product_category )		
					$product_category_slugs[] = $product_category->slug;
				reset($product_categories);					
				if ( (isset($wp_query->query_vars['products']) && $wp_query->query_vars['products'] != null) && in_array($wp_query->query_vars['products'], $product_category_slugs) )
					$product_category = $wp_query->query_vars['products'];
				else 
				{
					$link = current($product_categories)->slug;					
					if ( ! in_array( 'wp_head', $wp_current_filter) && isset($usam_query->query_vars['usam-category'] ) ) 
					{
						$current_cat = $usam_query->query_vars['usam-category'];
						if ( in_array( $current_cat, $product_category_slugs ) )
							$link = $current_cat;
					}
					$product_category = $link;
				}
				$category_slug = $product_category;		
			} 
			else 
			{	// Если продукт связан только с одной категории, у нас есть только один выбор
				if ( empty($product_categories) || !isset(current($product_categories)->slug) )
					$category_slug = null;	
				else
					$category_slug = current($product_categories)->slug;			
			}		
			if ( get_option( 'usam_category_hierarchical_url', 0 ) )
			{ 
				$term = get_term_by( 'slug', $category_slug, 'usam-category' );
				if ( is_object($ter) ) 
				{
					$term_chain = array( $term->slug );
					while( $term->parent ) 
					{							
						$term = get_term($term->parent, 'usam-category');
						if ( apply_filters('usam_product_link_show_item_term', true, $term) )
							array_unshift( $term_chain, $term->slug );
					}			
					$category_slug = implode( '/', $term_chain );
				}
			}
			if( isset($category_slug ) && empty( $category_slug ) )
				$category_slug = 'product';
			
			$rewritereplace['%product_cat%'] = $category_slug;
			$product_permalink = $product_permalink."/%postname%/";
		}	
		elseif (strpos($product_permalink, 'sku') !== false) 
			$rewritereplace['%sku%'] = usam_get_product_meta($post->ID, 'sku');
		else
			$product_permalink = $product_permalink."/%postname%/";
		$permalink = str_replace( array_keys($rewritereplace), $rewritereplace, $product_permalink );
		$permalink = user_trailingslashit( $permalink, 'single' );	
		$permalink = home_url( $permalink );		
	}
	return apply_filters( 'usam_product_permalink', $permalink, $post->ID );
}

// Процесс дублирования товаров
function usam_duplicate_product_process( $post, $new_parent_id = false ) 
{
	global $wpdb;
	$productmeta = [];
	$metas = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM ".USAM_TABLE_PRODUCT_META." WHERE product_id = %d", $post->ID  ) );	
	if ( !empty($metas) ) 
	{									
		foreach ( $metas as $meta )
		{
			$productmeta[$meta->meta_key] = $meta->meta_value;
		}
	}	
	$meta = [];	
	$post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", $post->ID  ) );				
	if ( !empty($post_meta_infos) ) 
	{		
		foreach ( $post_meta_infos as $meta_info )
		{						
			$meta_key = $meta_info->meta_key;
			$meta_value = addslashes( $meta_info->meta_value );						
			switch( $meta_key )
			{
				case '_edit_lock':		
				case '_edit_last':		
					$save_action = false;
				break;	
				case '_thumbnail_id':									
					$thumbnail_id = $meta_value;								
					$save_action = false;
				break;																		
				case '_usam_product_metadata':
					$meta_value = maybe_unserialize( $meta_info->meta_value );								
					$save_action = true;
				break;
				default:
					if ( stripos($meta_key, '_usam_') !== false) 
						$save_action = true;
					else
						$save_action = false;
				break;
			}						
			if ( $save_action )
			{
				$meta_key = str_replace("_usam_", "", $meta_key);
				$meta[$meta_key] = $meta_value;
			}
		}					
	}		
	$product_attributes = [];
	$attributes = $wpdb->get_results( $wpdb->prepare("SELECT meta_key, meta_value FROM ".usam_get_table_db('product_attribute')." WHERE product_id = %d", $post->ID) );				
	if ( !empty($attributes) ) 
	{									
		foreach ( $attributes as $attribute )
		{
			if ( isset($product_attributes[$attribute->meta_key]) )
			{
				if ( !is_array($product_attributes[$attribute->meta_key]) )
					$product_attributes[$attribute->meta_key] = [$product_attributes[$attribute->meta_key]];
				$product_attributes[$attribute->meta_key][] = $attribute->meta_value;
			}
			else
				$product_attributes[$attribute->meta_key] = $attribute->meta_value;
		}
	}
	$post_content = str_replace( "'", "''", $post->post_content );
	$post_content_filtered = str_replace( "'", "''", $post->post_content_filtered );
	$post_excerpt = str_replace( "'", "''", $post->post_excerpt );
	$post_title = str_replace( "'", "''", $post->post_title ) . " (Duplicate)";
	$post_name = str_replace( "'", "''", $post->post_name );
	$comment_status = str_replace( "'", "''", $post->comment_status );
	$ping_status = str_replace( "'", "''", $post->ping_status );

	$product = [
		'post_status'           => $post->post_status,
		'post_type'             => $post->post_type,
		'ping_status'           => $ping_status,
		'post_parent'           => $new_parent_id ? $new_parent_id : $post->post_parent,
		'menu_order'            => $post->menu_order,
		'to_ping'               =>  $post->to_ping,
		'pinged'                => $post->pinged,
		'post_excerpt'          => $post_excerpt,
		'post_title'            => $post_title,
		'post_content'          => $post_content,
		'post_content_filtered' => $post_content_filtered,
		'post_mime_type'        => $post->post_mime_type,
		'meta'                  => $meta,
		'productmeta'           => $productmeta,
	];
	if ( 'attachment' == $post->post_type )
		$product['guid'] = $post->guid;	

	$_product = new USAM_Product( $product );		
	$new_post_id = $_product->insert_product( $product_attributes );
	
	usam_duplicate_taxonomies( $post->ID, $new_post_id, $post->post_type );// Копировать taxonomies	
//	usam_duplicate_children( $post->ID, $new_post_id );// Ноходит дочерние записи (в том числе файлы продуктов и изображения продуктов), их значения мета, и дублирует их.
	return $new_post_id;
}

/**
 * Скопируйте таксономии поста в другой пост
 */
function usam_duplicate_taxonomies( $id, $new_id, $post_type )
{
	$taxonomies = get_object_taxonomies( $post_type ); //array("category", "post_tag");
	foreach ( $taxonomies as $taxonomy ) 
	{
		$post_terms = wp_get_object_terms( $id, $taxonomy );
		for ( $i = 0; $i < count( $post_terms ); $i++ ) {
			wp_set_object_terms( $new_id, $post_terms[$i]->slug, $taxonomy, true );
		}
	}
}
/**
 * Дубликаты дети продукта и дети мета
 */
function usam_duplicate_children( $old_parent_id, $new_parent_id ) 
{
	$child_posts = usam_get_posts(['post_parent' => $old_parent_id, 'post_type' => 'any', 'order' => 'ASC']);
	foreach ( $child_posts as $child_post )
	    usam_duplicate_product_process( $child_post, $new_parent_id );
}

// Получить типы цен
function usam_get_prices( $args = [] )
{
	$option = get_site_option('usam_type_prices');
	$prices = maybe_unserialize( $option );	
	if ( empty($prices) ) 
		return array();

	if ( isset($args['type']) && $args['type'] == 'all' )
		unset($args['type']);

	if ( !empty($args) )
	{
		$results = array();
		foreach( $prices as $setting_price )
		{							
			if ( isset($args['ids']) && !in_array($setting_price['id'], $args['ids']) )
				continue;
			
			if ( isset($args['available']) && (!isset($setting_price['available']) || $args['available'] != $setting_price['available']) )
				continue;
			
			if ( isset($args['type']) && strtoupper($args['type']) != strtoupper($setting_price['type']) )
				continue;
			
			if ( isset($args['code']) && $args['code'] != $setting_price['code'] )
				continue;
			
			if ( isset($args['external_code']) && $args['external_code'] != $setting_price['external_code'] )
				continue;
			
			if ( isset($args['currency']) && $args['currency'] != $setting_price['currency'] )
				continue;
			
			if ( isset($args['base_type']) && $setting_price['type'] == 'R' && $args['base_type'] != $setting_price['base_type'] )
				continue;
			
			$results[] = $setting_price;		
		}
	}
	else
		$results = $prices;
	
	$order = isset($args['order'])&&$args['order']=='ASC'?'ASC':'DESC';	
	if ( isset($args['orderby']) )
	{		
		if ( is_array($args['orderby']) )
			$orderby = $args['orderby'];
		else
			$orderby = [$args['orderby'] => $order];	
	}
	else
		$orderby = ['sort' => $order];		
	
	usort($results, function( $a, $b ) use ( $orderby )
	{
		$res = 0;
		foreach( $orderby as $k => $v )
		{			
			if( !isset($a[$k]) || $a[$k] == $b[$k] ) continue;

			$res = ( $a[$k] < $b[$k] ) ? -1 : 1;
			if( $v == 'DESC' ) 
				$res= -$res;
			break;
		}
		return $res;
	});	
	if ( !empty( $args['fields'] ) ) 
	{
		if ( $args['fields'] == 'external_code=>code' ) 
		{			
			$prices = array( );
			foreach ($results as $price) 
			{
				if ( !empty($price['external_code']) )
					$prices[$price['external_code']] = $price['code'];
				else
					$prices[] = $price['code'];
			}
			$results = $prices;					
		}		
		elseif ( $args['fields'] == 'code=>title' ) 
		{			
			$prices = array( );
			foreach ($results as $price) 
				$prices[$price['code']] = $price['title'];
			$results = $prices;					
		}	
		elseif ( $args['fields'] == 'code=>data' ) 
		{			
			$prices = array( );
			foreach ($results as $price) 
				$prices[$price['code']] = $price;
			$results = $prices;					
		}	
	}				
	return $results;
}

// Получить имя цены по коду
function usam_get_name_price_by_code( $code_price )
{	
	$prices = usam_get_prices(['code' => $code_price]);	
	$name = '';	
	if ( isset($prices[0]) )
		$name = $prices[0]['title'];
	return $name;
}

// Получить валюту цены по коду
function usam_get_currency_price_by_code( $code_price = null )
{	
	if ( $code_price === null )
		$code_price = usam_get_customer_price_code();
	
	$prices = usam_get_prices(['code' => $code_price]);	
	$currency = 'RUB';	
	if ( isset($prices[0]) )
		$currency = $prices[0]['currency'];
	return $currency;
}

function usam_get_currency_sign_price_by_code( $code_price = null )
{	
	$currency = usam_get_currency_price_by_code( $code_price );
	return usam_get_currency_sign( $currency );
}

function usam_get_setting_price_by_code( $price_code = null, $key = 'code' )
{	
	if ( $price_code === null )
		$price_code = usam_get_customer_price_code();
	
	$prices = usam_get_prices([$key => $price_code]);
	$result = [];
	if ( isset($prices[0]) )
	{
		$result = $prices[0];
		$result['rounding'] = (int)$result['rounding'];
	}
	return $result;
}

function usam_get_product_id_by_sku( $value ) 
{	
	return usam_get_product_id_by_meta( 'sku', $value );
}

function usam_get_product_id_by_code( $value ) 
{	
	return usam_get_product_id_by_meta( 'code', $value );
}

function usam_get_product_id_by_meta( $key, $value ) 
{
	global $wpdb;	
	if ( !$value ) 
		return;
	
	$cache_key = "usam_product_$key";
	$product_id = wp_cache_get( $value, $cache_key );
	if ($product_id === false) 
	{	
		$product_id = (int)$wpdb->get_var($wpdb->prepare("SELECT product_id FROM ".USAM_TABLE_PRODUCT_META." WHERE meta_key='%s' AND meta_value='%s' LIMIT 1", $key, $value));
		wp_cache_set($value, $product_id, $cache_key);
	}
	else
		$product_id = (int)$product_id;
	return $product_id;
}

function usam_get_product_ids_by_code( $codes, $meta_key = 'code' )
{
	global $wpdb;	
	if ( !$codes )
		return [];
	$product_codes = [];
	$cache_key = "usam_product_$meta_key";
	foreach($codes as $k => $code )
	{				
		if ( !$code )
			continue;
		$product_id = wp_cache_get( $code, $cache_key );
		if ($product_id !== false) 
		{
			$product_codes[$code] = $product_id;
			unset($codes[$k]);
		}
	} 	
	if ( empty($codes) )
		return $product_codes;
	$results = $wpdb->get_results("SELECT meta_value, product_id FROM ".USAM_TABLE_PRODUCT_META." WHERE meta_value IN ('".implode("','", $codes )."') AND meta_key='{$meta_key}'");
	$c = [];
	foreach($results as $result )	
	{
		$c[$result->meta_value] = $result->product_id;
	}	
	foreach( $codes as $code )
	{				
		if ( !isset($c[$code]) )
			wp_cache_set($code, 0, $cache_key );
		else
		{
			$product_codes[$code] = $c[$code];		
			wp_cache_set($code, $product_codes[$code], $cache_key );
		}
	} 	
	return $product_codes;
}

function usam_update_product_metadata( $product_id, $key, $value ) 
{	
	$product_meta = get_metadata('post', $product_id, USAM_META_PREFIX.'product_metadata', true);
	if ( !is_array($product_meta) )
		$product_meta = [];
	$product_meta[$key] = $value;
	return update_post_meta( $product_id, USAM_META_PREFIX.'product_metadata', $product_meta );		
}

//товарные остатки
function usam_add_product_stock($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	if ( !usam_is_multisite() || is_main_site() )
		return usam_add_metadata('product', $object_id, $meta_key, $meta_value, USAM_TABLE_STOCK_BALANCES, $prev_value, 'stock' );
	else
		return true;
}

function usam_get_product_stock( $object_id, $meta_key = '', $single = true) 
{	
	$object_id = usam_get_post_id_main_site( $object_id );
	$value = usam_get_metadata('product', $object_id, USAM_TABLE_STOCK_BALANCES, $meta_key, $single, 'stock' );
	if ( $meta_key != '' && $single )
		$value = (float)$value;
	return $value;
}

function usam_update_product_stock($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	if ( !usam_is_multisite() || is_main_site() )
		return usam_update_metadata('product', $object_id, $meta_key, (float)$meta_value, USAM_TABLE_STOCK_BALANCES, $prev_value, 'stock' );
	else
		return true;
}

function usam_delete_product_stock( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	if ( !usam_is_multisite() || is_main_site() )
		return usam_delete_metadata('product', $object_id, $meta_key, USAM_TABLE_STOCK_BALANCES, $meta_value, $delete_all, 'stock' );
	else
		return true;
}
//Цены товара из базы
function usam_add_product_metaprice($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('product', $object_id, $meta_key, $meta_value, USAM_TABLE_PRODUCT_PRICE, $prev_value, 'price' );
}

function usam_get_product_metaprice($object_id, $meta_key = '', $single = true) 
{			
	return usam_get_metadata('product', $object_id, USAM_TABLE_PRODUCT_PRICE, $meta_key, $single, 'price' );
}

function usam_update_product_metaprice($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('product', $object_id, $meta_key, (float)$meta_value, USAM_TABLE_PRODUCT_PRICE, $prev_value, 'price' );
}

function usam_delete_product_metaprice( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('product', $object_id, $meta_key, USAM_TABLE_PRODUCT_PRICE, $meta_value, $delete_all, 'price' );
}

//Разные товарные свойства
function usam_add_product_meta($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	if ( !usam_is_multisite() || is_main_site() )
		return usam_add_metadata('product', $object_id, $meta_key, $meta_value, USAM_TABLE_PRODUCT_META, $prev_value );
	else
		return true;
}

function usam_get_product_meta( $object_id, $meta_key = '', $single = true) 
{			
	$value = usam_get_metadata('product', $object_id, USAM_TABLE_PRODUCT_META, $meta_key, $single );
	switch ( $meta_key ) 
	{
		case 'sku' :
		case 'code' :
			$value = (string)$value;
		break;		
		case 'contractor' :
		case 'under_order' :
			$value = (int)$value;
		break;
	}	
	return $value;
}

function usam_update_product_meta($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	if ( !usam_is_multisite() || is_main_site() )
		return usam_update_metadata('product', $object_id, $meta_key, $meta_value, USAM_TABLE_PRODUCT_META, $prev_value );
	else
		return true;
}

function usam_delete_product_meta( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	if ( !usam_is_multisite() || is_main_site() )
		return usam_delete_metadata('product', $object_id, $meta_key, USAM_TABLE_PRODUCT_META, $meta_value, $delete_all );
	else
		return true;
}	

function usam_add_product_attribute($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('product', $object_id, $meta_key, $meta_value, usam_get_table_db('product_attribute'), $prev_value, 'attribute' );
}

function usam_get_product_attribute( $object_id, $meta_key = '', $single = true) 
{		
	if ( is_bool($meta_key) )
	{
		$single = $meta_key;
		$meta_key = '';
	}
	return usam_get_metadata('product', $object_id, usam_get_table_db('product_attribute'), $meta_key, $single, 'attribute' );
}

function usam_update_product_attribute($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('product', $object_id, $meta_key, $meta_value, usam_get_table_db('product_attribute'), $prev_value, 'attribute' );
}

function usam_delete_product_attribute( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('product', $object_id, $meta_key, usam_get_table_db('product_attribute'), $meta_value, $delete_all, 'attribute' );
}

function usam_attribute_stores_values( $field_type )
{
	if ( is_numeric($field_type) )
		$field_type = usam_get_term_metadata($field_type, 'field_type');
	if ( in_array($field_type,['M', 'S', 'N', 'COLOR', 'COLOR_SEVERAL', 'BUTTONS', 'AUTOCOMPLETE']) )
		return true;
	return false;
}

//Кешировать значения атрибутов
function usam_cache_products_attributes( $ids = null )
{
	if ( $ids === null )
	{
		$ids = array();
		while (usam_have_products()) :	usam_the_product(); 					
			$ids[] = get_the_ID();
		endwhile;	
	}
	usam_update_cache( $ids, [usam_get_table_db('product_attribute') => 'product_attribute'], 'product_id' );
}

function usam_get_attribute_values( $attribute_id )
{		
	static $attribute_ids = null;
	$object_type = 'usam_attribute_values';
	if ( $attribute_ids === null )
	{
		$terms = usam_get_product_attributes();
		$ids = array();
		foreach( $terms as $attribute )
		{ 
			if ( usam_attribute_stores_values( $attribute->term_id ) )
				$ids[] = $attribute->term_id;
		}
		$attribute_ids = [];
		foreach( $ids as $id )
		{
			if( wp_cache_get( $id, $object_type ) === false )
				$attribute_ids[] = $id;
		}		
		if( $attribute_ids )
		{
			$attribute_values = array();
			foreach( usam_get_product_attribute_values(['attribute_id' => $attribute_ids, 'orderby' => 'value']) as $attribute_value )
			{
				$attribute_values[$attribute_value->attribute_id][] = $attribute_value;
			}			
			foreach( $attribute_ids as $id )
			{
				if ( !isset($attribute_values[$id]) )
					$attribute_values[$id] = array();
				wp_cache_set( $id, $attribute_values[$id], $object_type );
			}	
		}		
	}
	$cache = wp_cache_get($attribute_id, $object_type );	
	if( $cache === false )
	{		
		$cache = usam_get_product_attribute_values(['attribute_id' => [ $attribute_id ], 'orderby' => 'value']);	
		wp_cache_set( $attribute_id, $cache, $object_type );
	}		
	return $cache;
}

function usam_get_product_attributes()
{
	$object_type = 'usam_product_attributes';
	$cache = wp_cache_get( $object_type );	
	if( $cache === false )
	{
		global $wpdb;
		$cache = $wpdb->get_results( "SELECT t.*, tt.parent, tt.description FROM {$wpdb->terms} AS t  INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id LEFT JOIN ".USAM_TABLE_TERM_META." AS sort ON (t.term_id = sort.term_id AND sort.meta_key='sort') INNER JOIN ".USAM_TABLE_TERM_META." AS status ON (t.term_id = status.term_id AND status.meta_key='status' AND status.meta_value IN ('publish')) WHERE tt.taxonomy IN ('usam-product_attributes') ORDER BY CAST(sort.meta_value AS SIGNED) ASC");
		$ids = array();
		foreach	( $cache as $k => $term ) 
		{			
			$cache[$k]->term_id = (int)$term->term_id;
			$cache[$k]->parent = (int)$term->parent;
			$ids[] = $term->term_id;
		}
		if( $ids )
			usam_update_cache( $ids, [USAM_TABLE_TERM_META => 'term_meta'], 'term_id' );
		wp_cache_set( $object_type, $cache );
	}		
	return $cache;
}


function usam_get_product_attributes_display( $product_id = null, $args = [] )
{	
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$default = ['is_important' => false, 'show_all' => false, 'cache' => false];
	$args = array_merge( $default, $args );
	extract( $args );	
	
	$object_type = 'usam_product_attributes_slug';
	if ( !$is_important && !$show_all )
	{		
		$results = wp_cache_get($product_id, $object_type );
		if( $results !== false )
			return $results;
	}	
	$product_attributes_value = [];	
	$terms = usam_get_product_attributes();
	foreach( $terms as $attribute )
	{ 
		$do_not_show_in_features = usam_get_term_metadata($attribute->term_id, 'do_not_show_in_features');
		if ( $do_not_show_in_features && !$show_all )
			continue;
			
		if ( $is_important )
		{ // Получить только важные параметры
			$important = usam_get_term_metadata($attribute->term_id, 'important');				
			if ( empty($important) )
				continue;
		}			
		$value = [];
		if ( $attribute->slug == 'brand' || $attribute->slug == 'contractor' || $attribute->slug == 'sku' )	
		{
			$product_attribute = usam_get_product_property($product_id, $attribute->slug);	
			if ( !$product_attribute )
				continue;
			$value[] = $product_attribute;
		}
		else
		{
			$field_type = usam_get_term_metadata($attribute->term_id, 'field_type');	
			$product_attribute = usam_get_product_attribute($product_id, $attribute->slug);	
			if ( !$product_attribute )
				continue;
								
			switch ( $field_type ) 
			{
				case 'C' ://Флажок один		
					if ( $product_attribute )			
						$value[] = __('Да','usam');				
					else
						$value[] = __('Нет','usam');	
				break;	
				case 'A' :
					$contact = usam_get_contact( $product_attribute );
					$value[] = isset($contact['appeal']) ? $contact['appeal'] : '';
				break;			
				case 'LDOWNLOAD' :			
					$value[] = "<a href='".esc_url(urldecode($product_attribute))."'>".__("Скачать", "usam")."</a>";	
				break;
				case 'LDOWNLOADBLANK' :			
					$value[] = "<a href='".esc_url(urldecode($product_attribute))."' target='_blank'>".__("Скачать", "usam")."</a>";	
				break;	
				case 'L' :			
					$value[] = "<a href='".esc_url(urldecode($product_attribute))."'>".htmlspecialchars( urldecode($product_attribute) )."</a>";	
				break;
				case 'LBLANK' :			
					$value[] = "<a href='".esc_url(urldecode($product_attribute))."' target='_blank'>".htmlspecialchars( urldecode($product_attribute) )."</a>";	
				break;			
				case 'F' :		
					if ( is_numeric($product_attribute) )
					{
						$attachment = get_post( $product_attribute );
						$value[] = "<a href='".get_permalink( $attachment->ID )."'>$attachment->post_title</a>";
					}
				break;			
				case 'S' :			
				case 'N' :
				case 'BUTTONS':
				case 'AUTOCOMPLETE':				
				case 'COLOR' :			
					$attribute_values = usam_get_attribute_values( $attribute->term_id );
					foreach( usam_get_product_attribute($product_id, $attribute->slug, false) as $attr )
					{
						$ok = true;
						foreach( $attribute_values as $option )
						{
							if ( $option->id == $attr->meta_value )	
							{
								$value[$option->slug] = $option->value;
								$ok = false;
								break;
							}
						}
						if ( $ok && !is_numeric($attr->meta_value) )
							$value[] = $attr->meta_value;
					}	
				break;
				case 'PRICES' :
					$value[] = $product_attribute;
				break;			
				case 'COLOR_SEVERAL' :
				case 'M' :	//Флажок несколько			
					$attribute_values = usam_get_attribute_values( $attribute->term_id );	
					foreach( usam_get_product_attribute($product_id, $attribute->slug, false) as $attr )
					{					
						foreach( $attribute_values as $option )
						{
							if ( $option->id == $attr->meta_value )	
							{
								$value[$option->slug] = $option->value;
								break;
							}
						}
					}			
				break;
				case 'YOUTUBE' :	
				case 'TIME' :					
				break;
				case 'DESCRIPTION' :
				default:
					$value[] = $product_attribute;
				break;
			}
		}
		$product_attributes_value[$attribute->term_id] = $value;
	}			 
	$product_attributes = [];	
	foreach( $terms as $term )
	{							
		if ( $term->parent == 0 )
		{				
			$attributes = [];	
			foreach( $terms as $attr )
			{					
				if ( $term->term_id == $attr->parent && !empty($product_attributes_value[$attr->term_id]) )
				{ 						
					$field_type = usam_get_term_metadata($attr->term_id, 'field_type');					
					$attribute = ['term_id' => $attr->term_id, 'parent' => $attr->parent, 'name' => $attr->name, 'slug' => $attr->slug, 'description' => $attr->description, 'value' => $product_attributes_value[$attr->term_id], 'field_type' => $field_type];
					$attribute = apply_filters( 'usam_product_attribute_display', $attribute, $product_id, $attr );					
					if ( $attribute )
						$attributes[$attr->slug] = $attribute;
				}
			}	
			if ( !empty($attributes) )
			{ 
				$product_attributes[$term->slug] = ['term_id' => $term->term_id, 'parent' => $term->parent, 'name' => $term->name, 'slug' => $term->slug, 'description' => $term->description, 'attributes_count' => count($attributes)];	
				$product_attributes = array_merge($product_attributes, $attributes);
			}
		}		
	}
	if ( !$is_important && !$show_all && $cache )
		wp_cache_set( $product_id, $product_attributes, $object_type );
	return $product_attributes;
}

function usam_get_product_attribute_display( $product_id, $slug, $single = true )
{		
	$attributes = usam_get_product_attributes_display( $product_id, ['is_important' => false, 'cache' => true]);
	if ( isset($attributes[$slug]) && isset($attributes[$slug]['value']))
	{
		if ( $single )
			return implode(", ", $attributes[$slug]['value']);
		else
			return $attributes[$slug]['value'];
	}
	else
		return $single ? '' : [];
}

function usam_get_product_attribute_code( $product_id, $slug )
{			
	$term = get_term_by( 'slug', $slug, 'usam-product_attributes' );
	if( !usam_attribute_stores_values( $term->term_id ) )
		return false;
	$field_type = usam_get_term_metadata($term->term_id, 'field_type');
	$single = !($field_type == 'COLOR_SEVERAL' || $field_type == 'M');
	
	$db_values = usam_get_product_attribute($product_id, $slug, $single);
	$values = usam_get_attribute_values( $term->term_id );
	$code = [];
	foreach( $values as $value )
	{
		if( $single )
		{
			if( $value->id == $db_values )
				$code[] = $value->code;
		}
		else
		{
			foreach( $db_values as $db_value )
				if( $value->id == $db_value->meta_value )
					$code[] = $value->code;
		}
	}
	if( !$single )	
		return $code;
	elseif ( $code )
		return $code[0];
	else
		return $code;
}

function usam_insert_product_attribute_variant( $insert )
{
	global $wpdb;	
	
	$attributes = usam_get_product_attribute_values(['attribute_id' => $insert['attribute_id'], 'value' => $insert['value'], 'number' => 1]);
	if ( !empty($attributes) )
		return $attributes['id'];
	$insert['slug'] = empty($insert['slug']) ? sanitize_title($insert['value']) : $insert['slug'];
	$formats_db = ['value' => '%s', 'slug' => '%s', 'attribute_id' => '%d', 'sort' => '%d', 'code' => '%s'];
		
	$formats = [];
	foreach ( $insert as $key => $value ) 
	{	
		if ( isset($formats_db[$key]) )		
			$formats[$key] = $formats_db[$key];	
		else
			unset($update[$key]);		
	}		
	$result = $wpdb->insert( usam_get_table_db('product_attribute_options'), $insert, $formats );
	if ( $result )
		return $wpdb->insert_id;
	return $result;
}

function usam_update_product_attribute_variant( $id, $update )
{
	global $wpdb;	
	
	if ( isset($update['slug']) && $update['slug'] === '' )
	{ 
		if ( !empty($update['value']) )
			$update['slug'] = sanitize_title($update['value']);
		else
			unset($update['slug']);
	}
	$where  = ['id' => $id];
	$formats_db = ['attribute_id' => '%d', 'value' => '%s', 'slug' => '%s', 'sort' => '%d', 'code' => '%s'];
	$formats = [];
	foreach ( $update as $key => $value ) 
	{	
		if ( isset($formats_db[$key]) )		
			$formats[$key] = $formats_db[$key];	
		else
			unset($update[$key]);
	}		
	$result = $wpdb->update( usam_get_table_db('product_attribute_options'), $update, $where, $formats, ['%d']);	
	return $result;
}

function usam_get_product_attributes_comparison( $product_id )
{
	$product_attributes_values = usam_get_product_attributes_display( $product_id );
	$product_attributes = [];
	foreach ( $product_attributes_values as $attribute )
	{
		$compare_products = usam_get_term_metadata($attribute['term_id'], 'compare_products');
		$do_not_show_in_features = usam_get_term_metadata($attribute['term_id'], 'do_not_show_in_features');
		if ( !$do_not_show_in_features && !$compare_products && isset($attribute['value']) )
		{
			$product_attributes[$attribute['term_id']] = $attribute;
		}						
	}
	return $product_attributes;
}

function usam_delete_product_attribute_variant( $id, $colum = 'id' )
{
	global $wpdb;	
	
	$where  = array( $colum => $id );	
	$result = $wpdb->delete( usam_get_table_db('product_attribute_options'), $where, array('%d') );
	return $result;
}

function usam_get_attributes( $product_id )
{	
	$category_ids = usam_get_product_term_ids( $product_id );		
	$relationships = usam_get_taxonomy_relationships( 'usam-category' );
	
	$attribute_ids = [];
	foreach( $relationships as $term )
	{
		$attribute_ids[$term->term_id1][] = $term->term_id2;
	}	
	$attributes = usam_get_product_attributes();
	$return_attributes = [];	
	foreach( $attributes as $attr )
	{		
		if ( !isset($attribute_ids[$attr->term_id]) ) 
		{
			$return_attributes[] = $attr;
		}
		else 
		{
			$result = array_intersect($attribute_ids[$attr->term_id], $category_ids);
			if ( !empty($result) )
				$return_attributes[] = $attr;
		}
	}
	return $return_attributes;
}

function usam_get_product_filters( $product_id )
{
	global $wpdb;
	
	$object_type = 'usam_product_filters';
	$results = wp_cache_get($product_id, $object_type );	
	if( $results === false )
	{	
		$results = $wpdb->get_col( "SELECT filter_id FROM `".usam_get_table_db('product_filters')."` WHERE product_id=$product_id" );		
		wp_cache_set( $product_id, $results, $object_type );
	}	
	return $results;
}

function usam_cache_products_filters( $ids )
{
	global $wpdb;
	if ( $ids )
	{
		$results = $wpdb->get_results( "SELECT * FROM `".usam_get_table_db('product_filters')."` WHERE product_id IN (".implode(",",$ids).")" );
		$cache = array();
		foreach ( $ids as $product_id )
		{
			$cache[$product_id] = array();
			foreach ( $results as $result )
			{
				if ( $result->product_id == $product_id )
					$cache[$product_id][] = $result->filter_id;
			}
		}
		foreach ( $cache as $product_id => $result )
		{
			wp_cache_set( $product_id, $result, 'usam_product_filters' );
		}	
	}
}

function usam_get_crosssell_conditions( $args = [] )
{				
	$conditions = maybe_unserialize(get_site_option('usam_crosssell_conditions'));		
	$rules = [];
	if ( !empty($conditions) )	
	{
		$args = array_merge(['active' => 1], $args );	
		foreach ( $conditions as $rule )
		{
			$result = true;
			if ( isset($args['active']) && $args['active'] != 'all' && $rule['active'] != $args['active'] )
				$result = false;
			if ( isset($args['id']) && $rule['id'] != $args['id'] )
				$result = false;
			if ( $result )
				$rules[] = $rule;
		}		
	}
	return $rules;
}

function usam_process_calculate_increase_sales_product( $id = null, $day = 5 )
{				
	if ( $id )
		$rules = usam_get_crosssell_conditions(['id' => $id]);
	else
		$rules = usam_get_crosssell_conditions();
	if ( !empty($rules) )
	{
		$args = ['post_status' => 'publish'];		
		if ( $day )
		{
			$tomorrow  = mktime(0, 0, 0, date("m"), date("d")-5, date("Y"));
			$args['productmeta_query'] = ['relation' => 'OR', ['key' => 'increase_sales_time', 'value' => $tomorrow, 'compare' => '<', 'type' => 'NUMERIC'], ['key' => 'increase_sales_time', 'compare' => 'NOT EXISTS']];		
		}
		$i = usam_get_total_products( $args );	
		if ( $i )
			usam_create_system_process( __("Расчет товаров для увеличения продаж", "usam" ), ['args' => $args, 'rules' => $rules], 'calculate_increase_sales_product', $i, 'calculate_increase_sales_product', 2 );
	}
}

function usam_content_table_in_array( $content )
{
	$out = array();
	$html_no_attr = preg_replace("#(</?\w+)(?:\s(?:[^<>/]|/[^<>])*)?(/?>)#ui", '$1$2', $content ); // очистить от классов и стилей
	$content =	preg_replace('~(<(.*)[^<>]*>\s*<\\2>)+~i','',$html_no_attr );						// удалить пустые строки таблицы
	
	preg_match_all('#<td>(.+?)</td>#s', $content, $matches); 						
	$result = array_chunk($matches[1], 2);		
	foreach ( $result as $record )
	{	
		if ( empty($record[1]) || empty($record[0]) )
			continue;
		$out[] = array( 'name'  => mb_strtolower(trim(strip_tags($record[0]))),
						'value' => mb_strtolower(trim(strip_tags($record[1])))
					);						
	}
	return $out;
}

// Получить скидки товара
function usam_get_current_product_discount( $product_id ) 
{	
	$product_id = absint($product_id);		
	$results = wp_cache_get( $product_id, 'usam_current_product_discount' );			
	if ( $results === false )			
	{							
		global $wpdb;
		$discounts = $wpdb->get_results( "SELECT discount_id, code_price FROM ".USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS." WHERE product_id ='$product_id'" );	
		$results = array();
		foreach ( $discounts as $value )
		{
			$results[$value->code_price][] = $value->discount_id;
		}
		wp_cache_set( $product_id, $results, 'usam_current_product_discount' );		
	}		
	return $results;	
}

function usam_cache_current_product_discount( $product_ids ) 
{		
	global $wpdb;
	if ( !empty($product_ids) )
		$discounts = $wpdb->get_results( "SELECT discount_id, code_price, product_id FROM ".USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS." WHERE product_id IN (".implode(",",$product_ids).")" );
	
	$results = array();
	if ( !empty($discounts) )
	{
		foreach ( $discounts as $value )
		{
			$results[$value->product_id][$value->code_price][] = $value->discount_id;
		}
	}
	foreach ( $product_ids as $product_id )
	{
		if ( !isset($results[$product_id]) )
			$results[$product_id] = array();
		
		wp_cache_set( $product_id, $results[$product_id], 'usam_current_product_discount' );		
	}
	return $results;	
}

// Получить скидки товара
function usam_get_products_discounts( $args ) 
{	
	global $wpdb;	
	$where = array('1=1');
	if ( !empty($args['code_price']) )
	{
		$code_price = is_array($args['code_price'])?$args['code_price']:array($args['code_price']);
		$where[] = "code_price IN ('".implode("','",$code_price)."')";
	}
	if ( !empty($args['product_id']) )
	{
		$product_id = is_array($args['product_id'])?$args['product_id']:array($args['product_id']);
		$where[] = "product_id IN ('".implode("','",$product_id)."')";
	}
	if ( !empty($args['discount_id']) )
	{
		$discount_id = is_array($args['discount_id'])?$args['discount_id']:array($args['discount_id']);
		$where[] = "discount_id IN ('".implode("','",$discount_id)."')";
	}	
	$discounts = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS." WHERE ".implode(" AND ",$where)." ORDER BY product_id" );	
	
	$result = array();
	foreach ( $discounts as $value )
	{
		$result[$value->product_id][] = $value;
	}		
	return $result;	
}

// Получить товары у которых установлена скидка
function usam_get_product_discount_ids( $discount_id, $number = 0 ) 
{
	global $wpdb;	
	
	if ( is_array($discount_id) )
		$discont = array_map('intval', $discount_id);
	else	
	{
		$discont = array( absint($discount_id) );		
	}		
	$limit = $number?"LIMIT ".absint($number):'';
	$result = $wpdb->get_col( "SELECT DISTINCT product_id FROM ".USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS." WHERE discount_id IN ('".implode("','",$discont)."') $limit" );
	return $result;
}

/**
 * Создание вариаций товара
 */
function usam_edit_product_variations( $product_id, $post_data ) 
{		
	remove_action( 'transition_post_status', '_usam_action_transition_post_status', 10, 3 );
		
	$product_type_object = get_post_type_object('usam-product');
	if (!current_user_can($product_type_object->cap->edit_post, $product_id))
		return;

	$parent = get_post_field( 'post_parent', $product_id );
	if( !empty($parent) )
		return;	
			
	if (!isset($post_data['variations']))
		$post_data['variations'] = array();
		
	$variations = (array)$post_data['variations'];
	unset($post_data['variations']);
    
	require_once( USAM_FILE_PATH . '/includes/product/variation_combinator.class.php' );
	$combinator = new USAM_Variation_Combinator( $variations ); // Создать массивы для вариации наборов
	$variation_sets = $combinator->return_variation_sets(); // Получить массив, содержащий изменение набор идентификаторов
	$variation_values = $combinator->return_variation_values(); // Извлечь массив, содержащий комбинации каждого варианта набора, связаны с этим продуктом.
	$combinations = $combinator->return_combinations();	

	$variation_sets_and_values = array_merge($variation_sets, $variation_values);
	$variation_sets_and_values = apply_filters('usam_edit_product_variation_sets_and_values', $variation_sets_and_values, $product_id);
	
	wp_set_object_terms( $product_id, $variation_sets_and_values, 'usam-variation');
	wp_set_object_terms( $product_id, 'variable', 'usam-product_type' );
	
	$child_product = get_post( $product_id, ARRAY_A );	
	$id_main_site = usam_get_post_id_main_site( $product_id );	
	if ( !isset($post_data['prices']) )
	{
		$metadata = usam_get_product_metaprice( $id_main_site );	
		if ( !empty($metadata) )
		{			
			foreach ( $metadata as $value )
				$child_product['prices'][$value->meta_key] = $value->meta_value;
		}
	}
	else
		$child_product['prices'] = $post_data['prices'];
	
	if ( !isset($post_data['stocks']) )
	{
		$metadata = usam_get_product_stock( $product_id );	
		if ( !empty($metadata) )
		{			
			foreach ( $metadata as $value )
				$child_product['product_stock'][$value->meta_key] = $value->meta_value;
		}		
	}
	else
		$child_product['product_stock'] = $post_data['stocks'];
	if ( !isset($post_data['productmeta']) )
	{
		$metadata = usam_get_product_meta( $product_id );	
		if ( !empty($metadata) )
		{			
			foreach ( $metadata as $value )
				$child_product['productmeta'][$value->meta_key] = $value->meta_value;
		}	
		$child_product['productmeta']['code'] = '';
	}
	else
		$child_product['productmeta'] = $post_data['productmeta'];
	$child_product['post_author'] = get_current_user_id();
	$child_product['post_status'] = 'publish';
	$child_product['post_parent'] = $product_id;	
	$child_product['product_type'] = 'variation';		
	
	$childs = usam_get_products(['post_parent' => $product_id, 'post_status' => 'all', 'numberposts' => -1, 'cache_product' => false]);
	$product_children = array();
	foreach( $combinations as $k => $combination ) 
	{
		$term_names = [];
		$term_ids = array();
		$product_values = $child_product;		
		$product_values['productmeta']['sku'] = $child_product['productmeta']['sku'].'-'.($k+1);

		$combination_terms = get_terms(['taxonomy' => 'usam-variation', 'hide_empty' => 0, 'include' => implode(",", $combination), 'orderby' => 'parent' ]);	
		foreach($combination_terms as $term) 
		{
			$term_ids[] = $term->term_id;
			$term_names[] = $term->name;
		}
		$product_values['post_title'] .= " (".implode(", ", $term_names).")";
		$product_values['post_name'] = sanitize_title($product_values['post_title']);
		$selected_post = null;
		foreach($childs as $child) 
		{
			if ( $child->post_name == $product_values['post_name'] )
			{
				$selected_post = $child;
				break;
			}
		}		
		$child_product_id = usam_get_id_product_variation( $product_id, $term_ids );
		if( $selected_post == null ) 
		{						
			if(  $child_product_id == false )
			{	
				$product_values = apply_filters( 'insert_child_product_meta', $product_values, $product_id );		
				$_product_v = new USAM_Product( $product_values );			
				$child_product_id = $_product_v->insert_product( );		
			}
		}
		elseif( $selected_post->ID != $child_product_id ) 
		{
			$child_product_id = $selected_post->ID;			
		} 		
		$product_children[] = $child_product_id;
		if( $child_product_id > 0 ) 		
		{ 			
			wp_set_object_terms($child_product_id, $term_ids, 'usam-variation');			
		}
	}	
	if( !empty($childs) )
	{
		$childs_ids = wp_list_pluck( $childs, 'ID' );
		$old_ids_to_delete = array_diff($childs_ids, $product_children);
		$old_ids_to_delete = apply_filters('usam_edit_product_variations_deletion', $old_ids_to_delete);
		if( is_array($old_ids_to_delete) && !empty($old_ids_to_delete) ) 
		{
			$count = count($old_ids_to_delete);
			if ( $count < 5 )
			{
				foreach($old_ids_to_delete as $object_ids)
					wp_delete_post($object_ids);
			}
			else
				usam_create_system_process( __('Удаление вариантов товара','usam'), ['post__in' => $old_ids_to_delete, 'post_parent' => $product_id, 'post_type' => 'usam-product'], 'delete_post', $count, 'delete_variations_posts_'.time() );
		}
	} 
}


/**
 * Обновить условия вариации, присвоенные родительскому продукту, на основе изменений, которые он имеет. 
 */
function usam_refresh_parent_product_terms( $parent_id ) 
{
	$children = get_children( array( 'post_parent' => $parent_id, 'post_status' => array( 'publish', 'inherit' ), ) );
	$children_ids = wp_list_pluck( $children, 'ID' );

	$children_terms = wp_get_object_terms( $children_ids, 'usam-variation' );
	$new_terms = array();
	foreach ( $children_terms as $term ) 
	{
		if ( $term->parent )
			$new_terms[] = $term->parent;
	}
	$children_term_ids = wp_list_pluck( $children_terms, 'term_id' );
	$new_terms = array_merge( $new_terms, $children_term_ids );
	$new_terms = array_unique( $new_terms );
	$new_terms = array_map( 'absint', $new_terms );
	wp_set_object_terms( $parent_id, $new_terms, 'usam-variation' );
}

/**
 * Убедитесь в том, родительские продукты, назначенного термина обновления, когда статусы его вариации "меняются" 
 */
function _usam_action_transition_post_status( $new_status, $old_status, $post ) 
{  
	if ( $post->post_type != 'usam-product' || ! $post->post_parent )
		return;
	usam_refresh_parent_product_terms( $post->post_parent );
}
add_action( 'transition_post_status', '_usam_action_transition_post_status', 10, 3 );
/**
 * Убедитесь, что назначенные термины родительского продукта обновлены, когда его варианты удалены или разбиты
 */
function _usam_action_refresh_variation_parent_terms( $post_id ) 
{
	$post = get_post( $post_id );
	if ( $post->post_type != 'usam-product' || ! $post->post_parent )
		return;
	usam_refresh_parent_product_terms( $post->post_parent );
}
add_action( 'deleted_post', '_usam_action_refresh_variation_parent_terms', 10, 1 );


function usam_get_product_sorting_options( )
{	
	return ['name-asc' => __('по имени', 'usam'), 'sku-asc' => __('по артикулу', 'usam'), 'price-asc' => __('по возрастанию цены', 'usam'), 'price-desc' => __('по убыванию цены', 'usam'), 'percent-desc' => __('по скидкам', 'usam'), 'dragndrop-asc' => __('вручную', 'usam'), 'rating-desc' => __('рейтингу', 'usam'), 'popularity-desc' => __('по популярности', 'usam'), 'date-desc' => __('Новинки', 'usam')];
}

function usam_check_type_product_sold( $type )
{
	$types = get_option('usam_types_products_sold', array( 'product', 'services' ));
	if ( in_array($type, $types) )
		return true;
	else
		return false;
}

function usam_insert_type_price( $new )
{
	$new['title'] = sanitize_text_field($new['title']);
	$new['base_type'] = isset($new['base_type'])?sanitize_title($new['base_type']):'0';	
	$new['code'] = !empty($new['code'])?sanitize_title($new['code']):strtolower(usam_rand_string( 6 ));		
	$new['code'] = strtolower($new['code']);
	$new['type'] = isset($new['type']) && $new['type'] == 'P'?'P':'R';		
	$new['locations'] = !empty($new['locations']) ? array_map('intval', $new['locations']) : array();
	$new['roles'] = !empty($new['roles']) ? stripslashes_deep($new['roles']) : array();													
	$new['underprice'] =  !empty($new['underprice'])?(int)$new['underprice']:0;		
	$new['sort'] = isset($new['sort'])?absint($new['sort']):100;
	$new['external_code'] = isset($new['external_code'])?sanitize_text_field($new['external_code']):'';	
	$new['currency'] = isset($new['currency'])?sanitize_text_field($new['currency']):'RUB';		
	$new['rounding'] = isset($new['rounding'])?sanitize_text_field($new['rounding']):2;				
	$new['date'] = date( "Y-m-d H:i:s" );			
	$new['available'] = !empty($new['available'])?1:0;
	$id = usam_add_data( $new, 'usam_type_prices' );	
	do_action( 'usam_type_price_insert', $id );	
	return $id;
}

/**
 * Добавление связанных товаров, например, аксессуары к товару
 */
function usam_add_associated_products( $product_id, $product_ids, $list, $add = false )
{
	global $wpdb;
	if ( !$product_id )
		return false;	
	
	$associated_products = usam_get_associated_product_by_list( $product_id, $list );		
	if ( !empty($product_ids) )
	{		
		$ids = array_diff($product_ids, $associated_products);			
		foreach ( $ids as $id ) 
		{
			if ( $id )
			{
				$sql = "INSERT INTO `".USAM_TABLE_ASSOCIATED_PRODUCTS."` (`product_id`,`associated_id`,`list` ) VALUES ('%d','%d','%s') ON DUPLICATE KEY UPDATE `list`='%s'";
				$insert = $wpdb->query( $wpdb->prepare($sql, $product_id, $id, $list, $list ));	
			}
		}		
	}	
	if ( !$add )
	{
		$associated_products = array_diff($associated_products, [0]);	
		$product_ids = array_diff($associated_products, $product_ids);		
		if ( !empty($product_ids) )
		{		
			$result = $wpdb->query( "DELETE FROM ".USAM_TABLE_ASSOCIATED_PRODUCTS." WHERE product_id='$product_id' AND list='$list' AND associated_id IN (".implode(",",$product_ids).")" );	
		}
	}
	return true;
}

function usam_delete_associated_products( $product_id, $list )
{
	global $wpdb;	
		
	$result = false;
	if ( $product_id && $list  )
		$result = $wpdb->query( "DELETE FROM ".USAM_TABLE_ASSOCIATED_PRODUCTS." WHERE product_id='$product_id' AND list='$list'" );		 
	return $result;
}

function usam_get_associated_product_by_list( $product_id, $list )
{ 
	global $wpdb;	
		
	$object_type = 'usam_associated_product';
	$cache = wp_cache_get($product_id, $object_type );	
	if( $cache === false )
	{		
		$cache = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_ASSOCIATED_PRODUCTS." WHERE product_id=$product_id" );
		wp_cache_set( $product_id, $cache, $object_type );
	}	
	$results = array();
	foreach($cache as $result )
	{				
		if ($result->list == $list)
			$results[] = $result->associated_id;
	}		
	return $results;
}
 
function usam_get_associated_products( $product_ids )
{	
	global $wpdb;
	if ( empty($product_ids) )
		return false;
	$product_ids = array_map('intval', $product_ids);
	$associateds = $wpdb->get_results("SELECT * FROM ".USAM_TABLE_ASSOCIATED_PRODUCTS." WHERE product_id IN (".implode(",", $product_ids).")");	
	$results = array();
	foreach($associateds as $product )
	{				
		$results[$product->product_id][] = $product;
	}		
	foreach($product_ids as $product_id )
	{
		if ( !isset($results[$product_id]) )
			$results[$product_id] = array();		
		wp_cache_set($product_id, $results[$product_id], 'usam_associated_product');		
	}	
	return $results;	
}

function usam_add_product_component( $insert ) 
{
	global $wpdb;		
	$formats_db = array('product_id' => '%d', 'quantity' => '%d', 'component' => '%s');
	$formats = array();
	if ( isset($insert['id']) )
		unset($insert['id']);
	foreach ( $insert as $key => $value ) 
	{	
		if ( isset($formats_db[$key]) )		
			$formats[$key] = $formats_db[$key];	
		else
			return false;
	}		
	return $wpdb->insert( usam_get_table_db('product_components'), $insert, $formats );
} 

function usam_get_product_component( $id ) 
{	
	global $wpdb;	
	return $wpdb->get_row( "SELECT * FROM ".usam_get_table_db('product_components')." WHERE id=$id", ARRAY_A );
}

function usam_update_product_component($id, $update) 
{ 
	global $wpdb;
	
	$where  = array( 'id' => $id );
	
	$formats_db = array('product_id' => '%d', 'quantity' => '%d', 'component' => '%s');
	$formats = array();
	foreach ( $update as $key => $value ) 
	{	
		if ( isset($formats_db[$key]) )		
			$formats[$key] = $formats_db[$key];		
	}
	return $wpdb->update( usam_get_table_db('product_components'), $update, $where, $formats, array('%d') );
}

function usam_delete_product_component( $id, $colum = 'id' ) 
{ 
	global $wpdb;		
	return $wpdb->delete( usam_get_table_db('product_components'), array( $colum => $id ), array('%d') );
}

function usam_get_product_components( $product_id ) 
{
	global $wpdb;
	if ( empty($product_id) )
		return false;
	$product_id = absint($product_id);
	return $wpdb->get_results("SELECT * FROM ".usam_get_table_db('product_components')." WHERE product_id=$product_id");
}

function usam_get_balance_information( $product_id = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();	
			
	$rules = maybe_unserialize( get_option('usam_balance_information', [] ) );	
	$stock = usam_product_remaining_stock( $product_id );
	$title = '';
	foreach( $rules as $key => $rule ) 
	{			
		$result_term = true;											
		foreach (['category_sale' => 'category_sale', 'catalogs' => 'catalog', 'brands' => 'brands'] as $key => $term_name)
		{
			if ( !empty($rule[$key]) )
			{								
				$result_term = false;
				$terms = get_the_terms( $product_id , 'usam-'.$term_name );
				if (  !empty($terms) )
				{ 									
					foreach ( $terms as $term )
					{
						if ( in_array($term->term_id, $rule[$key]) )
						{												
							$result_term = true;
							break;
						}						
					}					
				}
				if ( $result_term == false )
					break;
			}
		} 
		if ( !empty($rule['contractors']) && $result_term )
		{
			$result_term = false;
			$contractor = usam_get_product_meta($product_id, 'contractor');
			if ( in_array($contractor, $rule['contractors']) )
				$result_term = true;
		}						
		if ( !empty($rule['category']) && $result_term )
		{
			$result_term = false;
			if ( $categories === null )															
				$categories = usam_get_product_term_ids( $product_id, 'usam-category' );									
			if ( !empty($categories) )
			{		
				foreach ( $categories as $category_id )
				{
					if ( in_array($category_id, $rule['category']) )
					{									
						$result_term = true;
						break;
					}						
				}
			}
		}	
		if ( $result_term == false )
			continue;	
		krsort($rule['layers']);
		foreach( $rule['layers'] as $quantity => $info ) 
		{
			if ( $stock >= $quantity )
			{
				$title = $info;
				break;
			}
		}
	}
	return $title;	
}

function usam_get_product_uuid( $product_id = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$uuid = usam_get_product_meta( $product_id, 'uuid' );
	if ( !$uuid )
	{
		$uuid = wp_generate_uuid4();
		usam_update_product_meta( $product_id, 'uuid', $uuid);
	}
	return $uuid;
}

function usam_get_seller_product( $product_id = null ) 
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	$seller = [];
	$post = get_post( $product_id );
	if ( $post )
	{		
		if( user_can( $post->post_author, 'seller_company' ) )
			$seller = usam_get_company( $post->post_author, 'user_id' );
		else			
			$seller = usam_get_contact( $post->post_author, 'user_id' );		
	}
	return $seller;
}

function usam_get_names_related_products() 
{
	return ['popularity' => __('Популярные товары', 'usam'), 'collection' => __('Коллекция', 'usam'), 'also_bought' => __('С этим товаром покупали', 'usam'),'history_views' => __('История просмотра товаров', 'usam'), 'related_products' => __('Перекрестные продажи', 'usam'), 'same_category' => __('Товары в той же категории', 'usam'), 'upsell' => __('UP SELL', 'usam'), 'similar' => __('Аналоги', 'usam')];	
}

function usam_update_counter_sellers_products_quantity( $seller_id )
{
	static $calculate = true, $sellers = [];	
	global $wpdb;
	
	if ( $seller_id === '' )
		return false;
	
	if ( is_string($seller_id) )
		$sellers[$seller_id] = $seller_id;
	else
		$calculate = $seller_id;
	if ( $calculate )
	{
		foreach ( $sellers as $seller_id )
		{
			$count = usam_get_total_products(['productmeta_query' => [['key' => 'seller_id', 'value' => $seller_id, 'compare' => '=']], 'post_status' => 'publish']);
			usam_update_seller($seller_id, ['number_products' => $count]);
			unset($sellers[$seller_id]);
		}
	}	
	return $sellers;
}

function usam_get_product_tabs( $product_id = null )
{
	if ( $product_id == null )
		$product_id = get_the_ID();
	
	require_once( USAM_FILE_PATH . '/includes/product/custom_product_tabs_query.class.php' );
	$tabs = usam_get_custom_product_tabs(['active' => 1, 'product_id' => $product_id]);
	foreach( $tabs as $k => $item ) 
	{
		$item->content = usam_get_product_tab_template( $item );
		if( !$item->content )	
			unset($tabs[$k]);
	} 	
	return $tabs;
}

function usam_add_product_showcase( $product_id, $new_showcases )
{
	$old = usam_get_array_metadata($product_id, 'product', 'showcases', 'number');
	$delete = array_diff($old, $new_showcases);
	$add = array_diff($new_showcases, $old);
	usam_save_array_metadata($product_id, 'product', 'showcases', $new_showcases);	
	do_action( 'usam_product_showcase', $product_id, $add, $delete );
}

?>