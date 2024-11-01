<?php
/**
 * Импорт товаров
 */
class USAM_Product_Importer
{	
	private $rule;
	private $data;
	private $add = 0;
	private $update = 0;
	private $image = 0;	
	private $primary = 'sku';
	
	public function __construct( $id ) 
	{			
		if ( is_array($id) )
			$this->rule = $id;
		else
		{
			require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
			$this->rule = usam_get_exchange_rule( $id );			
			$metas = usam_get_exchange_rule_metadata( $id );
			foreach($metas as $metadata )
				$this->rule[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );			
		}
		$default = ['splitting_array' => '|'];
		$this->rule = array_merge( $default, $this->rule );
		if ( !$this->rule['splitting_array'] )
			$this->rule['splitting_array'] = '|';
	}
	
	public function start( $data ) 
	{			
		if ( empty($data) ) 
			return false;		
		
		$this->data = apply_filters( 'usam_product_importer_data', $data, $this->rule );			
		
		add_filter( 'block_local_requests', '__return_false' );
		add_filter( 'https_ssl_verify', '__return_false' );
		
		$anonymous_function = function($parsed_args, $url) { $parsed_args['reject_unsafe_urls'] = false; return $parsed_args; };	
		add_filter( 'http_request_args', $anonymous_function, 10, 2 ); //Отключить проверку ссылки при загрузке картинок
									
		$this->add = 0;
		$this->update = 0;
		
		usam_start_import_products();
		
		$records = $this->import();	
		
		usam_end_import_products();		
		
		return array( 'add' => $this->add, 'update' => $this->update, 'records' => $records );
	}

	private function import( ) 
	{		
		$columns = $this->data[0];
		$is_attributes = false;
		$is_storage = false;
		$is_price = false;
		foreach($columns as $column => $value)
		{
			if ( stripos($column, 'attribute_') !== false)
				$is_attributes = true;
			elseif ( stripos($column, 'storage_') !== false)
				$is_storage = true;
			elseif( stripos($column, 'price_') !== false)
				$is_price = true;
		}		
		if ( $is_attributes )
			$product_attributes = get_terms(['hide_empty' => 0, 'orderby' => 'name', 'taxonomy' => 'usam-product_attributes']);
						
		$storages = usam_get_storages(['fields' => 'code=>meta_key', 'active' => 'all']);	
		$prices = usam_get_prices(['type' => 'all']);
		$external_code_prices = [];
		if ( isset($columns['external_code_price'])  )
		{
			foreach($prices as $price)
			{
				$external_code_prices[$price['external_code']] = $price['code'];
			}			
		}
		$variations_ids = [];
		$product_ids = [];			
			
		$codes = [];		
		if ( isset($columns['code']) )
		{
			$this->primary = 'code';
			if ( isset($columns['sku']) )
			{
				global $wpdb;
				if ( !$wpdb->get_var("SELECT product_id FROM ".USAM_TABLE_PRODUCT_META." WHERE meta_key='code' AND meta_value!=''") )
					$this->primary = 'sku';
			}
			foreach($this->data as $number => $row)
			{ 				
				if ( !empty($row['code']) )
					$codes[] = $row['code'];	
			}
			$codes = usam_get_product_ids_by_code( $codes );
		}		
		/*if ( isset($this->data[0]['warehouse_code']) && isset($this->data[0]['warehouse_stock']) )
		{
			foreach($this->data as $number => $row)
			{
				if ( isset($storages[$row['warehouse_code']]) )
					$product['product_stock'][$storages[$row['warehouse_code']]] = $row['warehouse_stock'];	
			}		
		}*/				
		if ( $this->check_wpdb_error() )
			return 0;
		if ( empty($this->rule['type_import']) || $this->rule['type_import'] != 'insert' )
		{			
			if ( !empty($codes) )
			{
				$ids = array_values($codes);		
				unset($codes);
				if ( isset($columns['crosssell']) || isset($columns['similar']) )
					usam_get_associated_products( $ids );		
				$args = ['post__in' => $ids, 'cache_results' => true, 'update_post_term_cache' => true, 'update_post_meta_cache' => true, 'product_meta_cache' => true];
				if ( $is_storage )
					$args['stocks_cache'] = true;
				if ( $is_price )
				{
					$args['prices_cache'] = true;
					usam_cache_current_product_discount( $ids );			
				}	
				if ( $is_attributes )		
					$args['product_attribute_cache'] = true;					
				usam_get_products( $args );	
				unset($ids);
			}		
		}		
		$taxonomies = get_taxonomies(['object_type' => ['usam-product']], 'objects');
		$units = usam_get_list_units();			
		$related_products = [];
		$i = 0;
		$start_time = time();
		foreach($this->data as $number => $row)
		{ 		
			$product = [];					
			foreach (['post_title','post_name','post_excerpt','post_content','menu_order','comment_status'] as $column )
			{
				if ( isset($row[$column]) )
					$product[$column] = $row[$column];
			}
			if ( isset($row['post_date']) )
				$product['post_date'] = $row['post_date'];
			if ( isset($row['post_status']) )
			{
				$stati = get_post_stati();
				if ( isset($stati[$row['post_status']]) )
					$product['post_status'] = $row['post_status'];
			}
			elseif ( !empty($this->rule['post_status']) )
				$product['post_status'] = $this->rule['post_status'];
			if ( isset($row['post_author']) )
				$product['post_author'] = $row['post_author'];
			elseif ( !empty($this->rule['user_id']) )
				$product['post_author'] = $this->rule['user_id'];				
			if (!empty($row['weight']) )
			{			
				$product['productmeta']['weight'] = usam_string_to_float($row['weight']);
				if ( !empty($row['weight_unit']) )
					$product['productmeta']['weight'] = usam_convert_weight( $product['productmeta']['weight'], $row['weight_unit'] );	
			}	
			foreach (['sku','virtual','code','barcode', 'webspy_link', 'avito_id'] as $column )
			{
				if ( isset($row[$column]) )
					$product['productmeta'][$column] = $row[$column];
			}	
			foreach (['views','rating','rating_count'] as $column )
			{				
				if ( isset($row[$column]) )
					$product['postmeta'][$column] = abs((int)preg_replace('~\D+~','',$row[$column]));
				elseif ( isset($this->rule['product_'.$column]) && $this->rule['product_'.$column] !== '')
					$product['postmeta'][$column] = $this->rule['product_'.$column];
			}			
			if ( isset($row['unit']) )
				$product['productmeta']['unit'] = usam_string_to_float($row['unit']);
			if ( isset($row['unit_measure']) )
			{
				$unit = usam_get_unit_measure($row['unit_measure']);
				if ( !empty($unit) )
					$product['productmeta']['unit_measure'] = $row['unit_measure'];	
			}
			if ( isset($row['box_length']) )
				$product['productmeta']['length'] = usam_string_to_float($row['box_length']);
			if ( isset($row['box_width']) )
				$product['productmeta']['width'] = usam_string_to_float($row['box_width']);
			if ( isset($row['box_height']) )
				$product['productmeta']['height'] = usam_string_to_float($row['box_height']);
			if ( isset($row['under_order']) )
				$product['productmeta']['under_order'] = usam_string_to_float($row['under_order']);	
			
			if ( !empty($row['thumbnail']) )
				$product['thumbnail'] = $row['thumbnail'];	
			if ( !empty($row['images']) )
				$product['media_url'] = explode($this->rule['splitting_array'],$row['images']);	
			if ( !empty($row['not_limited']) )
			{
				foreach( $storages as $code => $meta_key )
					$product['product_stock'][$meta_key] = USAM_UNLIMITED_STOCK;
			}	
			else
			{
				foreach( $storages as $code => $meta_key )
				{
					$column = 'storage_'.$code;
					if ( isset($row[$column]) )
						$product['product_stock'][$meta_key] = usam_string_to_float($row[$column]);
				}
				if ( isset($row['warehouse_code']) && isset($row['warehouse_stock']) )
				{
					if ( isset($storages[$row['warehouse_code']]) )
						$product['product_stock'][$storages[$row['warehouse_code']]] = $row['warehouse_stock'];	
				}
				if ( isset($this->rule['change_stock']) && is_numeric($this->rule['change_stock']) && !isset($product['product_stock']) )
				{
					foreach( $storages as $code => $meta_key )
					{
						$product['product_stock'][$meta_key] = $this->rule['change_stock'];		
					}
				}
			}
			foreach( $prices as $price )
			{
				$column = "price_".$price['code'];
				if ( isset($row[$column]) )
				{
					$product['prices']['price_'.$price['code']] = usam_string_to_float($row[$column]);						
					if ( !empty($this->rule['change_price']) && in_array($column, $this->rule['columns']) )
					{
						if ( $this->rule['change_price'] )
							$product['prices']['price_'.$price['code']] += $product['prices']['price_'.$price['code']]*$this->rule['change_price']/100;
						else
							$product['prices']['price_'.$price['code']] -= $product['prices']['price_'.$price['code']]*absint($this->rule['change_price'])/100;
					}
					if ( !empty($this->rule['change_price2']) && in_array($column, $this->rule['columns2']) )
					{
						if ( $this->rule['change_price2'] )
							$product['prices']['price_'.$price['code']] += $product['prices']['price_'.$price['code']]*$this->rule['change_price2']/100;
						else
							$product['prices']['price_'.$price['code']] -= $product['prices']['price_'.$price['code']]*absint($this->rule['change_price2'])/100;						
					}		
				}				
			}	
			foreach ($row as $key => $value )
			{		
				if ( stripos($key, 'fix_price_') !== false)
					$product['prices'][$key] = $value;
			}
			if ( isset($row['external_code_price']) && isset($row['price']) )
			{
				if ( isset($external_code_prices[$row['external_code_price']]) )
				{					
					$row['price'] = usam_string_to_float($row['price']);
					if ( !empty($this->rule['change_price']) && in_array($column, $this->rule['columns']) )
					{
						if ( $this->rule['change_price'] )
							$row['price'] += $row['price']*$this->rule['change_price']/100;
						else
							$row['price'] -= $row['price']*absint($this->rule['change_price'])/100;
					}
					if ( !empty($this->rule['change_price2']) && in_array($column, $this->rule['columns2']) )
					{
						if ( $this->rule['change_price2'] )
							$row['price'] += $row['price']*$this->rule['change_price2']/100;
						else
							$row['price'] -= $row['price']*absint($this->rule['change_price2'])/100;	
					}
					$product['prices']['price_'.$external_code_prices[$row['external_code_price']]] = $row['price'];
				}
			}				
			foreach( $taxonomies as $tax )
			{
				$name = str_replace('usam-','',$tax->name);
				if ( !empty($row[$name]) )
				{
					$terms = explode($this->rule['splitting_array'],$row[$name]);
					if ( $name == 'category' )
						$terms = apply_filters( 'usam_product_import_categories', $terms, $this->rule, $row );
					$terms = apply_filters( 'usam_product_import_'.$name, $terms, $this->rule, $row );	
					$parent = 0;
					foreach( $terms as $term )
						$parent = $this->set_term(trim($term), $tax->name, $parent);
					$product['tax_input'][$tax->name][] = $parent;
				}
				elseif ( !empty($row[$name.'_slug']) )
				{				
					$term = get_term_by( 'slug', $row[$name.'_slug'], $tax->name );
					$product['tax_input'][$tax->name] = [ $term->term_id ];
				}				
				elseif ( !empty($this->rule[$tax->name]) )
					$product['tax_input'][$tax->name] = [ $this->rule[$tax->name] ];	
			}				
			$attribute_values = [];	 
			if ( $is_attributes )
			{
				foreach( $product_attributes as $attribute )
				{							
					$column = "attribute_".$attribute->term_id;
					if ( isset($row[$column]) )
					{
						$field_type = usam_get_term_metadata($attribute->term_id, 'field_type');
						if ( $field_type == 'M' || $field_type == 'COLOR_SEVERAL' )
							$attribute_values[$attribute->slug] = explode($this->rule['splitting_array'], $row[$column]);
						else
							$attribute_values[$attribute->slug] = $row[$column];						
					}
				} 
			}
			if ( isset($row['contractor']) )
				$product['productmeta']['contractor'] = $row['contractor'];
			elseif ( !empty($this->rule['contractor']) )
				$product['productmeta']['contractor'] = $this->rule['contractor'];			
			$variations_product_ids = array();
			if ( !empty($row['variations']) )
			{
				$variations_string = explode("=", $row['variations'] );					
				foreach ( $variations_string as $variation_string )	
				{
					if ( empty($variation_string) )
						continue;
					
					$strings = explode(":", $variation_string );		
					$parent_term = get_term_by('name', $strings[0], 'usam-variation');	
					if ( empty($parent_term) )
					{
						$parent_term = wp_insert_term( $strings[0], 'usam-variation' );
						$parent_term_id = $parent_term['term_id'];
					}
					else
						$parent_term_id = $parent_term->term_id;					
					$product['variations'][$parent_term_id] = [];	
					$variations = explode($this->rule['splitting_array'], $strings[1] );
					foreach ( $variations as $variation )	
					{
						$strings = explode("@", $variation );
						$variations_product_ids[$strings[1]] = $strings[0];
						$parent_term = get_term_by( 'name', $strings[0], 'usam-variation' );						
						if ( empty($parent_term) )
						{
							$parent_term = wp_insert_term( $strings[0], 'usam-variation', ['parent' => $parent_term_id]);
							$term_id = $parent_term['term_id'];							 
						}
						else
							$term_id = $parent_term->term_id;						
						$product['variations'][$parent_term_id][$term_id] = 1;						
					}	
				}				
			}				
			if ( isset($row['product_type']) )
			{
				$product['product_type'] = $row['product_type'];		
				if ( $product['product_type'] == 'variation' && !empty($variations_ids) && isset($row['product_id']) && isset($variations_ids[$row['product_id']]) )
					$product['ID'] = $variations_ids[$row['product_id']];	
			}
			if ( isset($row['post_parent']) && isset($product_ids[$row['post_parent']]))			
				$product['post_parent'] = $product_ids[$row['post_parent']];			
			$product = apply_filters( 'usam_product_import_data', $product, $this->rule, $row );				
			if ( $product )
			{
				$attribute_values = apply_filters('usam_attribute_import_data', $attribute_values, $product, $this->rule, $row);
				$product_id = $this->insert_product( $product, $attribute_values );		
				if ( $product_id )				
				{		
					if( !empty($row['crosssell']) )				
						$related_products[$product_id]['crosssell'] = explode($this->rule['splitting_array'],$row['crosssell']);
					if( !empty($row['similar']) )
						$related_products[$product_id]['similar'] = explode($this->rule['splitting_array'],$row['similar']);
					if ( !empty($row['codes_additional_unit']) )
					{
						$product_additional_units = [];
						$codes_additional_unit = explode($this->rule['splitting_array'],$row['codes_additional_unit']);
						if ( !empty($row['additional_units']) )
							$additional_units = explode($this->rule['splitting_array'],$row['additional_units']);	
						$p_unit_measure = usam_get_product_meta($product_id,'unit_measure');
						$p_unit = usam_get_product_meta( $product_id, 'unit' );						
						foreach( $codes_additional_unit as $key => $additional_unit )
						{
							$additional_unit_measure = '';
							foreach( $units as $code => $unit )
							{
								if ( $additional_unit == $unit['short'] )
								{
									$additional_unit_measure = $code;
									break;
								}						
							}
							if ( $additional_unit_measure )
							{
								$add_unit = isset($additional_units[$key])?$additional_units[$key] : 1;	
								if ( $p_unit_measure != $additional_unit_measure || $add_unit != $p_unit )
									$product_additional_units[] = ['unit' => $add_unit, 'unit_measure' => $additional_unit_measure];
							}
						}		
						usam_update_product_metadata( $product_id, 'additional_units', $product_additional_units );
					}			
					if ( !empty($variations_product_ids) )
					{
						$args = ['post_parent' => $product_id, 'post_status' => ['draft', 'pending', 'publish'], 'numberposts' => -1, 'order' => "ASC", 'fields' => 'ids'];				
						$ids = usam_get_products( $args );
						$terms = wp_get_object_terms($ids, 'usam-variation', ['fields' => 'all_with_object_id']);
						if ( !empty($terms) )
						{ 			
							$variations = array();
							$parent_terms = array();
							foreach ($terms as $term)
							{		
								foreach ($variations_product_ids as $id => $name )
								{
									if ( $term->name == $name )
										$variations_ids[$id] = $term->object_id;
								}
							}					
						}				
					}
					do_action( 'usam_after_product_import', $product_id, $product, $row, $this->rule );
					
					if ( isset($row['product_id']) )
						$product_ids[$row['product_id']] = $product_id;		
					usam_clean_product_cache( $product_id );					
				}
			}
			unset($this->data[$number]);
			
			$i = $number+1;
			if ( $this->rule['max_time'] < time() - $start_time )
				break;	
			if ( !defined('USAM_AMOUNT_IMPORTED_DATA') && $this->image > 25 )
				break;
		}
		unset($product_ids);
		unset($row);
		$this->update_related_products( $related_products );
		
		return $i;
	}	
	
	public function set_term( $value, $taxonomy, $parent = 0 )
	{		
		if( empty($value) )
			return 0;
		$term_id = 0;
		$term = term_exists( $value, $taxonomy, $parent );
		if ( !empty($term['term_id']) )
		{
			$term = get_term( $term['term_id'], $taxonomy );	
			if ( $parent != $term->parent )
				wp_update_term($term->term_id, $taxonomy, ['parent' => $parent]);
			$term_id = (int)$term->term_id;
		}
		else
		{
			$term = wp_insert_term( $value, $taxonomy, ['parent' => $parent] );			
			if ( !is_wp_error($term) )
				$term_id = (int)$term['term_id'];			
			if ( $parent )
				clean_taxonomy_cache($taxonomy);
		}	
		return $term_id;
	}
	
	private function insert_product( $product, $attribute_values = array() ) 
	{ 
		$product_id = false;			
		if ( empty($product) )
			return false;
			
		if ( !empty($product['productmeta'][$this->primary]) )
			$product_id = usam_get_product_id_by_meta( $this->primary, $product['productmeta'][$this->primary] );
		if ( !$product_id && isset($product['ID']) )
		{
			$post = get_post($product['ID']);
			if ( !empty($post) )
				$product_id = $product['ID'];
		}			
		$number_downloaded_urls = 0;
		if ( !$product_id && ( empty($this->rule['type_import']) || $this->rule['type_import'] == 'insert' ) )
		{			
			if ( !empty($product['media_url']) && empty($product['thumbnail']) )
				$product['thumbnail'] = array_shift($product['media_url']);
			$_product = new USAM_Product( $product );	
			$product_id = $_product->insert_product( $attribute_values );	
			if ( $product_id )
			{
				$this->add++;				
				if ( !empty($product['thumbnail']) || !empty($product['media_url']) )
				{								
					$_product->insert_media();		
					$number_downloaded_urls = $_product->get_number_downloaded_urls();	
				}
			}		
		}
		elseif ( $product_id && (empty($this->rule['type_import']) || $this->rule['type_import'] == 'update' ) )			
		{				
			$_product = new USAM_Product( $product_id );	
			$_product->set( $product );				
			if ( $_product->update_product( $attribute_values ) )
				$this->update++;			
			if ( !empty($product['thumbnail']) || !empty($product['media_url']) )
			{
				$_product->insert_media();	
				$number_downloaded_urls = $_product->get_number_downloaded_urls();	
			}
			
		}	
		$this->image += $number_downloaded_urls;
			
		if ( $product_id )
			usam_update_product_meta( $product_id, 'rule_'.$this->rule['id'], date("Y-m-d H:i:s") );
		return $product_id;
	}
		
	private function update_related_products( $related_products )
	{								
		if ( !empty($related_products) )
		{
			usam_get_associated_products( array_keys($related_products) );		
			if ( $this->check_wpdb_error() )
				return false;			
			$all_codes = array();
			foreach($related_products as $product_id => $lists)		
			{ 
				foreach($lists as $list => $codes)
					$all_codes = array_merge($all_codes, $codes);
			}
			$code = apply_filters( 'usam_update_related_products_by', $this->primary, $related_products, $this->rule );	
			if ( !$code )
				return true;
			$all_codes = usam_get_product_ids_by_code( $all_codes, $code );
			if ( $this->check_wpdb_error() )
				return false;	
			foreach($related_products as $product_id => $lists)		
			{ 
				foreach($lists as $list => $codes)
				{
					$product_ids = array();
					foreach($codes as $code)
					{
						if ( !empty($all_codes[$code]) )
							$product_ids[] = $all_codes[$code];
					}
					if ( !empty($product_ids) )
					{
						usam_add_associated_products( $product_id, $product_ids, $list );
					}
				}				
			}
		}
	}

	function check_wpdb_error() 
	{
		global $wpdb;
		if ( !$wpdb->last_error ) 
			return false;
		return true;
	}		
}
?>