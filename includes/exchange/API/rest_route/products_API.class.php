<?php 
require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/api-handler-assistant.php' );
class USAM_Products_API extends USAM_API_handler_assistant
{			
	public static function save_products_prices( WP_REST_Request $request )
	{
		$parameters = $request->get_json_params();	
		if( !$parameters )
			$parameters = $request->get_body_params();
			
		$prices = usam_get_prices(['fields' => 'external_code=>code']);
		$products = [];	
		$product_data_ids = [];		
		foreach( $parameters['items'] as $parameter )
		{		
			if( isset($parameter['code_price']) )
				$type_price = $parameter['code_price'];
			elseif( isset($prices[$parameter['external_code_price']]) )
				$type_price = $prices[$parameter['external_code_price']];
			else
				continue;		
			if( !empty($parameter['code']) )
				$products[$parameter['code']]['price_'.$type_price] = usam_string_to_float( $parameter['price'] );
			elseif( !empty($parameter['product_id']) )
				$product_data_ids[$parameter['product_id']]['price_'.$type_price] = usam_string_to_float( $parameter['price'] );
				
		}
		$results = [];
		if( !empty($product_data_ids) )	
		{
			foreach( $product_data_ids as $product_id => $prices )
				$results[$product_id] = usam_edit_product_prices( $product_id, $prices );
		}
		else
		{
			if( count($products) < 1000 )
			{
				$codes = [];
				foreach( $products as $code => $product )
				{
					$codes[] = $code;
				}		
				usam_get_product_ids_by_code( $codes );
			}					
			foreach( $products as $code => $prices )
			{
				$id = usam_get_product_id_by_code( $code );
				if ( $id )
				{
					$results[$code] = usam_edit_product_prices( $id, $prices );
				}
			}
		}
		return $results;
	}	
	
	public static function save_prices( WP_REST_Request $request )
	{
		$product_id = $request->get_param( 'product_id' );	
		$parameters = $request->get_json_params();	
		if( !$parameters )
			$parameters = $request->get_body_params();
			
		$type_prices = usam_get_prices(['fields' => 'external_code=>code']);
		$prices = [];	
		foreach( $parameters['prices'] as $parameter )
		{		
			if( isset($parameter['code_price']) )
				$type_price = $parameter['code_price'];
			elseif( isset($type_prices[$parameter['external_code_price']]) )
				$type_price = $type_prices[$parameter['external_code_price']];
			else
				continue;		
			
			$prices['price_'.$type_price] = usam_string_to_float( $parameter['price'] );				
		}
		return usam_edit_product_prices( $product_id, $prices );
	}
	
	public static function get_product_attributes( WP_REST_Request $request )
	{
		global $wpdb;
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );	
		
		$where = '1=1';
		if ( !empty($query_vars['slug']) )
			$where .= " AND meta_key='".$query_vars['slug']."'";
		if ( !empty($query_vars['search']) )
			$where .= " AND meta_value LIKE LOWER ('".$query_vars['search']."%')";
		$items = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS DISTINCT meta_value AS name FROM ".usam_get_table_db('product_attribute')." WHERE $where LIMIT 50");		
		return ['items' => $items, 'count' => (int)$wpdb->get_var( 'SELECT FOUND_ROWS()' ) ];
	}	
	
	public static function get_product_components( WP_REST_Request $request )
	{
		global $wpdb;
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );	
		
		$where = '1=1';
		if ( !empty($query_vars['search']) )
			$where .= " AND component LIKE LOWER ('".$query_vars['search']."%')";
		$items = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS DISTINCT component AS name FROM ".usam_get_table_db('product_components')." WHERE $where LIMIT 50");		
		return ['items' => $items, 'count' => (int)$wpdb->get_var( 'SELECT FOUND_ROWS()' ) ];
	}
		
	public static function get_products( WP_REST_Request $request )
	{	
		global $type_price;
		$parameters = self::get_parameters( $request );		
		$query_vars = self::get_query_vars( $parameters, $parameters );	
					
		$paged = !empty($parameters['paged'])?absint($parameters['paged']):1;
		$per_page = !empty($parameters['count'])?absint($parameters['count']):20;
		$per_page = $per_page > 10000 ? 10000 : $per_page;				
		self::$query_vars = ['post_type' => 'usam-product', 'paged' => $paged, 'posts_per_page' => $per_page, 'meta_query' => [], 'tax_query' => [], 'stocks_cache' => false, 'prices_cache' => false];
		if ( !empty($parameters['tax_query']) )
			self::$query_vars['tax_query'] = $parameters['tax_query'];
		$image = false;
		if( !empty($parameters['add_fields']) )
		{
			if ( in_array('attributes', $parameters['add_fields']) )
				self::$query_vars['product_attribute_cache'] = true;
			if ( in_array('desired', $parameters['add_fields']) )
				self::$query_vars['user_list_cache'] = true;
			if ( in_array('storages', $parameters['add_fields']) || in_array('external_storages', $parameters['add_fields']) || in_array('stock_units', $parameters['add_fields'])  || in_array('total_balance', $parameters['add_fields']))
				self::$query_vars['stocks_cache'] = true;
			if ( in_array('prices', $parameters['add_fields']) || in_array('price', $parameters['add_fields']) || in_array('price_currency', $parameters['add_fields']) || in_array('old_price', $parameters['add_fields']) || in_array('old_price_currency', $parameters['add_fields']) || in_array('external_prices', $parameters['add_fields']) || in_array('discount_price', $parameters['add_fields']) )
				self::$query_vars['prices_cache'] = true;
			if ( in_array('sku', $parameters['add_fields']) || in_array('barcode', $parameters['add_fields']))		
				self::$query_vars['product_meta_cache'] = true;
			if( in_array('thumbnail', $parameters['add_fields']) || in_array('medium_image', $parameters['add_fields'])  || in_array('small_image', $parameters['add_fields']) || in_array('full_image', $parameters['add_fields']) )
			{
				$image = true;
				self::$query_vars['post_meta_cache'] = true;	
			}
			
			if ( in_array('images', $parameters['add_fields']) )		
				self::$query_vars['product_images_cache'] = true;				
			if ( in_array('category_name', $parameters['add_fields']) || in_array('brand_name', $parameters['add_fields']) || in_array('category', $parameters['add_fields']) )		
				self::$query_vars['update_post_term_cache'] = true;		
		}
		$type_price = !empty($parameters['type_price']) ? $parameters['type_price'] : usam_get_customer_price_code();
		if ( !empty($query_vars['search']) )
			self::$query_vars['s'] = trim(stripslashes($query_vars['search']));	
		if ( !empty($parameters['productmeta']) )
			self::$query_vars['productmeta_query'] = $parameters['productmeta'];
		if ( !empty($parameters['associated_product']) )
			self::$query_vars['associated_product'] = $parameters['associated_product'];			
		if ( !empty($parameters['pricemeta']) )
			self::$query_vars['price_meta_query'] = $parameters['pricemeta'];			
		if ( isset($parameters['orderby']) )
		{
			if ( $parameters['orderby'] == 'default' )
				self::$query_vars = usam_get_default_catalog_sort( self::$query_vars, 'array' );
			else
				self::$query_vars['orderby'] = sanitize_text_field($parameters['orderby']);
		}		
		if ( isset($parameters['order']) )
		{
			if ( $parameters['order'] == 'default' )
				self::$query_vars['order'] = get_option( 'usam_product_order' );
			else
				self::$query_vars['order'] = strtoupper($parameters['order']) == 'DESC'?'DESC':'ASC';
		}		
		if ( isset($parameters['post__in']) )
			self::$query_vars['post__in'] = $parameters['post__in'];
		
		if ( current_user_can('universam_api') || current_user_can('edit_product')  )
		{			
			if ( !empty($parameters['status']) )
				self::$query_vars['post_status'] = is_array($parameters['status'])?$parameters['status']:explode(',',sanitize_text_field($parameters['status']));
			else
			{
				$statuses = get_post_statuses();
				self::$query_vars['post_status'] = array_keys($statuses);
			} 
		}
		else
			self::$query_vars['post_status'] = 'publish';
		if ( isset($parameters['seller']) )
		{
			$user_id = get_current_user_id();
			$user_ids[] = $user_id;
			if ( current_user_can('seller_company') )
			{
				global $wpdb;				
				$company_id = $wpdb->get_var("SELECT company_id FROM ".USAM_TABLE_COMPANY_PERSONAL_ACCOUNTS." WHERE user_id='$user_id'");
				if ( $company_id )
					$user_ids = usam_get_company_personal_accounts( $company_id );
			}
			self::$query_vars['author__in'] = $user_ids; 
		}
		if ( !empty($parameters['user_list']) )
		{
			$contact_id = usam_get_contact_id();	
			self::$query_vars['user_list'] = ['list' => $parameters['user_list'], 'contact_id' => $contact_id];
		}		
		self::get_digital_interval_for_query($parameters, ['rating', 'views'], 'postmeta_query');
		if ( !empty($parameters['product_type']) )
		{
			$product_type = is_array($parameters['product_type'])?$parameters['product_type']:explode(',',sanitize_text_field($parameters['product_type']));
			self::$query_vars['tax_query'][] = ['taxonomy' => 'usam-product_type', 'field' => 'slug', 'terms' => $product_type];
		}		
		if ( !empty($parameters['category']) )
			self::$query_vars['tax_query'][] = ['taxonomy' => 'usam-category', 'field' => 'id', 'terms' => array_map('intval', (array)$parameters['category']), 'operator' => 'IN'];					
		if ( !empty($parameters['brands']) )
			self::$query_vars['tax_query'][] = ['taxonomy' => 'usam-brands','field' => 'id', 'terms' => array_map('intval', (array)$parameters['brands']), 'operator' => 'IN'];	
		if ( !empty($parameters['category_sale']) )
			self::$query_vars['tax_query'][] = ['taxonomy' => 'usam-category_sale', 'field' => 'id', 'terms' => array_map('intval', (array)$parameters['category_sale']), 'operator' => 'IN'];
		if ( !empty($parameters['from_price']) )
		{
			self::$query_vars['type_price'] = $type_price;
			self::$query_vars['from_price'] = (float)$parameters['from_price'];			
		}
		if ( !empty($parameters['to_price']) )
		{
			self::$query_vars['to_price'] = (float)$parameters['to_price'];
			self::$query_vars['type_price'] = $type_price;
		}
		$non_empty = isset($parameters['non_empty']) ? $parameters['non_empty'] : false;
		if ( !empty($parameters['from_stock']) )
			self::$query_vars['stock_meta_query'][] = ['key' => 'stock', 'type' => 'numeric', 'value' => (float)$parameters['from_stock'], 'compare' => '>='];
		if ( !empty($parameters['to_stock']) )
			self::$query_vars['stock_meta_query'][] = ['key' => 'stock', 'type' => 'numeric',	'value' => (float)$parameters['to_stock'], 'compare' => '<='];		
		if ( !empty($parameters['from_views']) )
			self::$query_vars['postmeta_query'][] = ['key' => 'views', 'type' => 'numeric', 'value' => (int)$parameters['from_views'], 'compare' => '>='];
		if ( !empty($parameters['to_views']) )
			self::$query_vars['postmeta_query'][] = ['key' => 'views', 'type' => 'numeric', 'value' => (int)$parameters['to_views'], 'compare' => '<='];
		if ( isset($parameters['post_parent']) )
			self::$query_vars['post_parent'] = absint($parameters['post_parent']);		
		$wp_query = new WP_Query;
		$items = $wp_query->query( self::$query_vars );			
		$count = $wp_query->found_posts;
		
		if( $image )
			update_post_thumbnail_cache( $wp_query );		
		if ( !empty($items) )
		{			
			if( isset($parameters['fields']) && $parameters['fields'] == 'id=>name' )
			{
				$products = [];			
				foreach ( $items as $item )
				{
					$products[$item->ID] = esc_html($item->post_title);
				}
				$items = $products;
			}
			elseif ( isset($parameters['fields']) && $parameters['fields'] == 'autocomplete' )
			{	
				foreach ( $items as &$item )
				{
					$x = new stdClass();				
					$x->name = stripcslashes($item->post_title);
					$x->id = $item->ID;	
					if( !empty($parameters['add_fields']) )
					{
						foreach ( $parameters['add_fields'] as &$add_field )
							$x->$add_field = usam_get_product_property($item->ID, $add_field );
					}
					$item = $x;
				}				
			}
			else
			{	
				if( !empty($parameters['add_fields']) )
				{
					if ( in_array('storages', $parameters['add_fields']) || in_array('external_storages', $parameters['add_fields']) || in_array('storages_data', $parameters['add_fields']) )
						$storages = usam_get_storages();	
					if ( in_array('prices', $parameters['add_fields']) || in_array('external_prices', $parameters['add_fields']))
						$prices = usam_get_prices(['type' => 'all']);
				}
				foreach( $items as $n => $item )
				{				
				//	$seller_id = usam_get_id_seller( $items[$n]->post_author );
				// usam_update_product_meta( $item->ID, 'seller_id',  $seller_id);				
					
					unset($items[$n]->guid);
					unset($items[$n]->to_ping);
					unset($items[$n]->pinged);
					unset($items[$n]->ping_status);
					unset($items[$n]->post_mime_type);
					unset($items[$n]->post_password);
					unset($items[$n]->post_content_filtered);
					//post_content
					$items[$n]->post_title = esc_html($items[$n]->post_title);
					$items[$n]->url = get_permalink( $item->ID );
					$result = [];	 	
					if ( !empty($parameters['add_fields']) )
					{
						foreach( $parameters['add_fields'] as $k )
						{							
							if ( $k == 'brand' )
							{
								$terms = wp_get_post_terms($item->ID, 'usam-brands');
								if ( !empty($terms[0]) )
								{
									$items[$n]->$k = $terms[0];
									if ( current_user_can('universam_api') )
										$items[$n]->$k->external_code = usam_get_term_metadata($terms[0]->term_id, 'external_code');
								}
								else
									$items[$n]->$k = new stdClass();
							}												
							elseif( $k == 'category' )
							{
								$terms = wp_get_post_terms($item->ID, 'usam-category');							
								$items[$n]->$k = [];
								foreach ( $terms as $term )
								{
									if ( current_user_can('universam_api') )
										$term->external_code = usam_get_term_metadata($term->term_id, 'external_code');
									$items[$n]->$k[] = $term;
								}
							}
							elseif( $k == 'brand_name' )
							{
								$terms = wp_get_post_terms($item->ID, 'usam-brands');
								$items[$n]->$k = !empty($terms[0]) ? $terms[0]->name : new stdClass();
							}												
							elseif ( $k == 'category_name' )
							{
								$terms = wp_get_post_terms($item->ID, 'usam-category');							
								$items[$n]->$k = !empty($terms[0]) ? $terms[0]->name : new stdClass();
							}
							elseif ( $k == 'storages' )
							{									
								$data = [];
								foreach ( $storages as $storage )
								{
									$stock = usam_get_product_stock($item->ID, 'storage_'.$storage->id );
									if ( $non_empty && $stock > 0 || !$non_empty )
										$data[$storage->id] = $stock;
								}
								$items[$n]->$k = $data;
							}	
							elseif ( $k == 'storages_data' )
							{									
								$data = [];
								foreach ( $storages as $storage )
								{
									$stock = usam_get_product_stock($item->ID, 'storage_'.$storage->id );
									if ( $non_empty && $stock > 0 || !$non_empty )
										$data[$storage->id] = ['title' => $storage->title, 'stock' => $stock, 'stock_units' => usam_get_formatted_quantity_product_unit_measure( $stock, usam_get_product_meta( $item->ID, 'unit_measure' ) ), 'code' => $storage->code];
								}
								$items[$n]->$k = $data;
							}						
							elseif ( $k == 'external_storages' )
							{					
								$data = [];
								foreach ( $storages as $storage )
								{
									if ( $storage->code )
									{
										$stock = usam_get_product_stock($item->ID, 'storage_'.$storage->id );
										if ( $non_empty && $stock > 0 || !$non_empty )
											$data[$storage->code] = $stock;
									}
								}
								$items[$n]->$k = $data;	
							}									
							elseif( $k == 'prices' )
							{					
								$data = [];
								foreach ( $prices as $price )
									$data[$price['code']] = usam_get_product_price($item->ID, $price['code'] );
								$items[$n]->$k = $data;
							}
							elseif ( $k == 'bonus' )
								$items[$n]->$k = usam_get_product_bonuses( $item->ID, $type_price );
							elseif ( $k == 'external_prices' )
							{					
								$data = [];
								foreach ( $prices as $price )
								{
									if ( $price['external_code'] )
										$data[$price['external_code']] = usam_get_product_price($item->ID, $price['code'] );
								}
								$items[$n]->$k = $data;
							}
							elseif ( $k == 'status_name' )
							{					
								$items[$n]->$k = get_post_status_object( $item->post_status )->label;
							}
							elseif ( $k == 'author' )
							{					
								$items[$n]->$k = self::author_data( $item->post_author );
							}
							elseif ( $k == 'attributes' )
							{					
								$terms = usam_get_product_attributes_display( $item->ID );	
								$items[$n]->$k = [];
								foreach ( $terms as $term )
								{
									$term['external_code'] = (string)usam_get_term_metadata($term['term_id'], 'external_code');	
									$items[$n]->$k[$term['slug']] = $term;
								}	 
							}
							elseif ( $k == 'edit_attributes' )
							{							
								$terms = usam_get_attributes( $item->ID );							
								$attribute_ids = [];
								foreach ( $terms as $term )
								{
									if ( usam_get_term_metadata($term->term_id, 'field_type') )							
										$attribute_ids[] = $term->term_id;
								}						
								$attribute_values = [];
								foreach( usam_get_product_attribute_values(['attribute_id' => $attribute_ids, 'orderby' => 'value']) as $option )
								{
									$attribute_values[$option->attribute_id][] = ['id' => $option->id, 'name' => $option->value, 'code' => $option->code];
								}								
								$items[$n]->attributes = [];							
								foreach( $terms as $term )
								{
									$items[$n]->attributes[$term->slug] = usam_format_product_attributes_api( $item->ID, clone $term, $attribute_values );							
									if ( isset($attribute_values[$term->term_id]) )
										unset($attribute_values[$term->term_id]);
								}						
							}	
							elseif ( $k == 'discount_price' && !empty($parameters['rule_id']) )
							{
								$rule_id = absint($parameters['rule_id']);
								$product_id = usam_get_post_id_main_site( $item->ID );
								$items[$n]->$k = (float)usam_get_product_metaprice( $product_id, 'fix_price_'.$rule_id );						
							}					
							elseif ( $k == 'images' )
							{
								$items[$n]->$k = [];
								$attachments = usam_get_product_images( $item->ID );	
								if ( !empty($attachments) ) 
								{ 
									$thumbnail_id = get_post_thumbnail_id( $item->ID );
									foreach ( $attachments as $attachment ) 
									{
										$attachment->full = wp_get_attachment_image_url($attachment->ID, 'fill' );									
										$attachment->medium_image = wp_get_attachment_image_url($attachment->ID, 'medium-single-product' );
										$attachment->small_image = wp_get_attachment_image_url($attachment->ID, 'small-product-thumbnail' );
										$attachment->thumbnail = $thumbnail_id == $attachment->ID;
										$items[$n]->$k[] = $attachment;
									}	
								}					
							}
							elseif ( $k == 'thumbnail' )							
								$items[$n]->$k = usam_get_product_thumbnail_src( $item->ID, 'product-thumbnails' );
							elseif ( $k == 'medium_image' )							
								$items[$n]->$k = usam_get_product_thumbnail_src( $item->ID, 'medium-single-product' );							
							elseif ( $k == 'small_image' )
								$items[$n]->$k = usam_get_product_thumbnail_src( $item->ID, 'small-product-thumbnail');						
							elseif ( $k == 'price' )
								$items[$n]->$k = usam_get_product_price($item->ID, $type_price );						
							elseif ($k == 'old_price' )
								$items[$n]->$k = usam_get_product_old_price($item->ID, $type_price );						
							elseif ( $k == 'total_balance' || $k == 'stock' )
								$items[$n]->$k = usam_get_product_stock($item->ID, $k );		
							elseif ( $k == 'stock_units' )
							{
								$stock = usam_product_remaining_stock( $item->ID, "stock" );		
								if( $stock >= USAM_UNLIMITED_STOCK )
									$items[$n]->$k = '&#8734;';		
								else		
									$items[$n]->$k = usam_get_formatted_quantity_product_unit_measure( $stock, usam_get_product_meta( $item->ID, 'unit_measure' ) );
							}
							elseif ( $k == 'unit_measure' )
							{
								$unit_measure = usam_get_product_meta($item->ID, 'unit_measure');			
								$items[$n]->$k = !empty($unit_measure)?$unit_measure:'thing';	
							}
							else
								$items[$n]->$k = usam_get_product_property($item->ID, $k );						
						}
					}
				} 
			}	
			$items = apply_filters( 'usam_api_products', $items, $parameters );
			$results = ['count' => $count, 'items' => $items];		
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
	
	public static function get_product( WP_REST_Request $request )
	{
		$product_id = $request->get_param( 'product_id' );	
		$parameters = self::get_parameters( $request );
		$product = get_post( $product_id );	
		if ( $product )
		{
			$type_price = !empty($parameters['type_price']) ? $parameters['type_price'] : usam_get_customer_price_code();
			$product->url = get_permalink( $product->ID ); 
			if ( !empty($parameters['add_fields']) )
			{
				foreach( $parameters['add_fields'] as $k )
				{		
					if ( in_array($k, ['sku','code', 'under_order', 'contractor']) )
						$product->$k = usam_get_product_meta($product->ID, $k);					
					elseif ( $k == 'sale_type' )
					{
						if ( !empty($product->post_parent) )
							$product->sale_type = usam_get_product_type_sold( $product->post_parent );
						else
							$product->sale_type = usam_get_product_type_sold( $product->ID );
					}
					elseif ( $k == 'codes' )
					{
						$codes = ['sku' => __( 'Артикул', 'usam'), 'code' => __( 'Внешний код', 'usam')];
						if ( !usam_check_current_user_role('administrator') )
							unset($codes['code']);
						if ( usam_check_current_user_role('administrator') && usam_get_product_meta($product->ID, 'code_main_site') )
							$codes['code_main_site'] = __( 'ID товара главного сайта', 'usam');				
						if( usam_check_product_type_sold('product', $product->ID) )
							$codes['barcode'] = __( 'Штрих-код', 'usam');
						$codes = apply_filters( 'usam_product_metabox_codes', $codes, $product->ID );	
						foreach ( $codes as $key => $title )
						{
							$product->$key = (string)usam_get_product_meta($product->ID, $key);
							if ( $key == 'sku' )
								$product->$key = $product->$key ? $product->$key : self::sku_generator( $product->ID );
							elseif ( $key == 'barcode' )
								$product->$key = $product->$key ? $product->$key : self::barcode_generator( $product->ID );							
						}
						$product->code_names = $codes;
					}					
					elseif ( $k == 'dimensions' )
					{
						$measurement_fields = ['weight', 'length', 'width', 'height', 'volume'];
						foreach ( $measurement_fields as $key )						
							$product->$key = usam_exp_to_dec(usam_string_to_float( usam_get_product_meta($product->ID, $key) ));	
						$dimension_units = usam_get_dimension_units();	
						$dimension_unit = get_option('usam_dimension_unit'); 	
						$product->name_weight_unit = usam_get_name_weight_units();			
						$product->name_length_unit = $dimension_units[$dimension_unit]['short'];					
					}
					elseif ( $k == 'total_balance' || $k == 'stock' || $k == 'reserve' )
					{
						$product->$k = usam_get_product_stock($product->ID, $k );
						$product->not_limited = $product->$k == USAM_UNLIMITED_STOCK;		
					}			
					elseif ( $k == 'stock_control' )
					{
						$discounts = usam_get_discount_rules(['fields' => 'id=>data', 'add_fields' => ['type_price'], 'discount_products' => $product->ID]);
						$product->$k = [];
						foreach ( $discounts as $discount ) 
							if( $discount->type_price == $type_price )
								$product->$k[] = $discount;
					}
					elseif ( $k == 'components' )
						$product->$k = usam_get_product_components( $product->ID );
					elseif ( $k == 'license_agreement' )
						$product->$k = usam_get_product_meta($product->ID, 'license_agreement');
					elseif ( $k == 'thumbnail' )
						$product->$k = usam_get_product_thumbnail_src( $product->ID, 'product-thumbnails' );
					elseif ( $k == 'small_image' )
						$product->$k = usam_get_product_thumbnail_src( $product->ID, 'small-product-thumbnail');					
					elseif ( $k == 'price' )
						$product->$k = usam_get_product_price($product->ID, $type_price );
					elseif ( $k == 'price_currency' )
						$product->$k = usam_get_formatted_price(usam_get_product_price($product->ID, $type_price ));
					elseif ($k == 'old_price' )
						$product->$k = usam_get_product_old_price($product->ID, $type_price );
					elseif ($k == 'old_price_currency' )
						$product->$k = usam_get_formatted_price(usam_get_product_old_price($product->ID, $type_price ));
					elseif ( $k == 'bonus' )
						$product->$k = usam_get_product_bonuses( $product->ID, $type_price );
					elseif ( $k == 'discounts' )
					{
						$discounts = usam_get_discount_rules(['fields' => 'id=>data', 'add_fields' => ['type_price'], 'discount_products' => $product->ID]);
						$product->$k = [];
						foreach ( $discounts as $discount ) 
							if( $discount->type_price == $type_price )
								$product->$k[] = $discount;
					}
					elseif ( $k == 'images' )
					{
						$product->$k = [];
						$attachments = usam_get_product_images( $product->ID );		
						if ( !empty($attachments) ) 
						{ 
							$thumbnail_id = get_post_thumbnail_id( $product->ID );
							foreach ( $attachments as $attachment ) 
							{
								$attachment->full = wp_get_attachment_image_url($attachment->ID, 'fill' );
								$attachment->small_image = wp_get_attachment_image_url($attachment->ID, 'small-product-thumbnail' );
								$attachment->medium_image = wp_get_attachment_image_url($attachment->ID, 'medium-single-product' );
								$attachment->thumbnail = $thumbnail_id == $attachment->ID;
								$product->$k[] = $attachment;
							}			
						}					
					}					
					elseif ( $k == 'variations' )
					{
						$product->$k = usam_get_products(['post_parent' => $product->ID, 'taxonomy' => 'usam-variation', 'orderby' => 'menu_order post_title', 'order' => "ASC", 'numberposts' => -1]);
					}
					elseif ($k == 'category' )
					{
						$terms = wp_get_post_terms($product->ID, 'usam-category');							
						$product->$k = [];
						foreach ( $terms as $term )
						{
							if ( current_user_can('universam_api') )
								$term->external_code = usam_get_term_metadata($term->term_id, 'external_code');
							$product->$k[] = $term;
						}
					}
					elseif ($k == 'not_limited' )
					{
						$stock = usam_product_remaining_stock($product->ID, 'stock');
						$product->$k = $stock >= USAM_UNLIMITED_STOCK;
					}					
					elseif ( $k == 'discount_price' && !empty($parameters['rule_id']) )
					{
						$rule_id = absint($parameters['rule_id']);
						$product_id = usam_get_post_id_main_site( $product->ID );
						$product->$k = (float)usam_get_product_metaprice( $product_id, 'fix_price_'.$rule_id );						
					}
					elseif ( $k == 'edit_attributes' )
					{
						$product->attributes = [];
						$terms = usam_get_attributes( $product->ID );							
						$attribute_ids = [];
						foreach ( $terms as $term )
						{
							if ( usam_get_term_metadata($term->term_id, 'field_type') )							
								$attribute_ids[] = $term->term_id;
						}						
						$attribute_values = [];
						foreach( usam_get_product_attribute_values(['attribute_id' => $attribute_ids, 'orderby' => 'value']) as $option )
						{
							$attribute_values[$option->attribute_id][] =  ['id' => $option->id, 'name' => $option->value, 'code' => $option->code];
						}								
						foreach ( $terms as $term )
						{							
							$product->attributes[$term->slug] = usam_format_product_attributes_api( $product->ID, $term, $attribute_values );
							if ( isset($attribute_values[$term->term_id]) )
								unset($attribute_values[$term->term_id]);
						}
						
					}					
					elseif ( $k == 'attributes' )
					{
						$terms = usam_get_attributes( $product->ID );	
						$product->attributes = [];
						foreach ( $terms as $term )
						{
							$term->mandatory = usam_get_term_metadata($term->term_id, 'mandatory');
							$term->field_type = usam_get_term_metadata($term->term_id, 'field_type');
							if ( $term->field_type == 'COLOR_SEVERAL' || $term->field_type == 'M')
								$term->value = usam_get_product_attribute($product->ID, $term->slug, false);
							elseif ( $term->field_type == 'O' )
								$term->value = usam_get_product_attribute($product->ID, $term->slug, false);
							else
								$term->value = (string)usam_get_product_attribute($product->ID, $term->slug);
							$product->attributes[] = $term;
						}					
					}	
					elseif ( $k == 'unit_measure' )
					{
						$unit_measure = usam_get_product_meta($product->ID, 'unit_measure');			
						$product->$k = !empty($unit_measure)?$unit_measure:'thing';		
					}
					else
						$product->$k = usam_get_product_property($product->ID, $k );	
				}
			}
		}	
		return $product;
	}
	
	public static function delete_products( WP_REST_Request $request  ) 
	{
		$parameters = self::get_parameters( $request );	
		$parameters['post_type'] = 'usam-product';
		if( !isset($parameters['post_status']) )
			$parameters['post_status'] = 'all';
		$i = usam_get_total_products( $parameters );
		return usam_create_system_process( __('Удалить товары. Вызвано из API','usam'), $parameters, 'delete_post', $i, 'delete_post_'.time() );
	}	
	
	public static function get_filters( WP_REST_Request $request  ) 
	{	
		global $wpdb;
			
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		
		$default = ['attr' => 1, 'shop' => 1,'individual_price' => 1, 'scat' => 'no_hierarchy', 'prices' => 1];
		$returned = array_merge($default, array_map('sanitize_title', $parameters['returned']));		
		$results = ['attributes' => [], 'categories' => [], 'categories_level' => [], 'storages' => [], 'companies' => []];	
		$query_vars = !empty($parameters['query'])?$parameters['query']:[];	
		if ( isset($query_vars['pagename']) && $query_vars['pagename'] == 'search' && empty($query_vars['s']) )
			return $results;		
		if ( !empty($query_vars['keyword']) )
			$query_vars['s'] = $query_vars['keyword'];			
		if ( isset($query_vars['pagename']) )
		{			
			$query_vars = array_merge($query_vars, usam_get_query_vars_system_page($query_vars['pagename']));
			unset($query_vars['pagename']);	
		}	
		if ( isset($query_vars['name']) )
			unset($query_vars['name']);
		
		$query_vars['nopaging'] = true;
		$sold = get_option( 'usam_display_sold_products', 'sort');	
		if ($sold != 'show')
			$query_vars['in_stock'] = $sold == 'hide'?true:false;
		elseif ( isset($query_vars['in_stock']) )
			unset($query_vars['in_stock']);
		$query_vars['orderby'] = 'ID';
		$query_vars['order'] = 'ASC';
		$query_vars['fields'] = 'ids';
		$query_vars['cache_results'] = false;
		$query_vars['update_post_meta_cache'] = false;
		$query_vars['product_meta_cache'] = false;
		$query_vars['update_post_term_cache'] = false;
		$query_vars['post_meta_cache'] = false;		
		$query_vars['prices_cache'] = false;
		$query_vars['stocks_cache'] = false;
		$query_vars['discount_cache'] = false;		
		$query_vars['no_found_rows'] = true;	
		if ( !empty($query_vars['tax_query']) )
			unset($query_vars['tax_query']);	
				
		$product_ids = false;
		if( $returned['attr'] )
		{
		//	$query = $query_vars;
		//	$query['fields'] = 'attribute_variant';
			$product_ids = usam_get_products( $query_vars );		
			if ( !empty($product_ids) )
			{		
				$filter_values = $wpdb->get_results("SELECT v.id, v.attribute_id, v.code, v.value FROM ".usam_get_table_db('product_filters')." AS f LEFT JOIN ".usam_get_table_db('product_attribute_options')." AS v ON (f.filter_id = v.id) WHERE product_id IN(".implode(",", $product_ids).") AND v.value IS NOT NULL");
				$product_filters = array();				
				$filters = array();
				$attributes = array();		
				foreach ($filter_values as $key => $filter)
				{
					if ( isset($filters[$filter->id]) )
					{
						$filters[$filter->id]++;
						unset($filter_values[$key]);
					}
					else
						$filters[$filter->id] = 1;
				}		
				$attribute_ids = array();
				foreach ($filter_values as $key => $filter)
				{					
					$attribute_ids[$filter->attribute_id] = $filter->attribute_id;	
					$count = isset($filters[$filter->id])?$filters[$filter->id]:0;											
					$product_filters[$filter->attribute_id][] = ['id' => $filter->id, 'attribute_id' => $filter->attribute_id, 'name' => stripcslashes($filter->value), 'code' => $filter->code, 'count' => $count];
					unset($filter_values[$key]);
				}				
				$terms = get_terms(['fields' => 'id=>name', 'taxonomy' => 'usam-product_attributes', 'hide_empty' => 0, 'orderby' => 'sort', 'include' => $attribute_ids, 'usam_meta_query' => [['key' => 'filter','value' => 1, 'compare' => '=']], 'term_meta_cache' => true]);
				foreach ($terms as $term_id => $name)
				{
					if ( empty($product_filters[$term_id]) )
						continue;					
					$type = usam_get_term_metadata($term_id, 'field_type');		
					wp_cache_delete($term_id, "usam_term_meta");						
					$attribute = ['id' => $term_id, 'name' => stripcslashes($name), 'type' => $type];
					if ( $type == 'O' || $type == 'N' )
					{	
						$numbers = [];
						foreach( $product_filters[$term_id] as $filter )
							$numbers[] = usam_string_to_float($filter['name']);
						if ( $numbers )
						{
							$attribute['min_price'] = min($numbers);
							$attribute['max_price'] = max($numbers);
						}
					}
					else
					{
						usort($product_filters[$term_id], function($a, $b){ return ( strcasecmp($a['name'], $b['name'])); });
						$attribute['filters'] = $product_filters[$term_id];
					}
					$results['attributes'][] = $attribute;
				}							
			}
		}			
		if ( $returned['scat'] )
		{				
			if ( $returned['scat'] == 'hierarchy' )
			{
				$args = ['taxonomy' => 'usam-category'];
				if ( !empty($query_vars['usam-category']) )
				{
					$term = get_term_by( 'slug', $query_vars['usam-category'], 'usam-category');
					if ( isset($term->term_id) )
						$args['child_of'] = (int)$term->term_id;
				}
				elseif ( !empty($query_vars['s']) )
				{ // Найти главные категории
					if ( !$product_ids )
						$product_ids = usam_get_products( $query_vars );
					if ( !empty($product_ids) )
					{
						$object_terms = wp_get_object_terms($product_ids, 'usam-category', ['fields' => 'all_with_object_id', 'orderby' => 'name', 'update_term_meta_cache' => false]);	
						$parent_ids = [];
						$ids = [];
						foreach( $object_terms as $term )
						{
							$parents_ids = usam_get_ancestors($term->term_id, $term->taxonomy);						
							$ids = array_merge( $ids, $parents_ids );	
						}
						$args['include'] = $ids;
					}
				}
				else
					$args['child_of'] = 0;
	
				$terms = get_terms( $args );	
				$parent_ids = [];
				foreach( $terms as $term )
				{
					$parent_ids[$term->term_id] = $term->parent;
				}
				foreach( $terms as $term )
				{
					$term->term_id = (int)$term->term_id;
					$parent = isset($parent_ids[$term->parent])?(int)$term->parent:0;
					$results['categories'][] = ['id' => $term->term_id, 'name' => stripcslashes($term->name), 'parent' => $parent, 'url' => get_term_link($term->term_id, 'usam-category'), 'count' => 1];
				}
			}
			else
			{
				if ( !$product_ids )
					$product_ids = usam_get_products( $query_vars );			
				if ( !empty($product_ids) )
				{
					$object_terms = wp_get_object_terms($product_ids, 'usam-category', ['fields' => 'all_with_object_id', 'orderby' => 'name', 'update_term_meta_cache' => false]);					
					unset($product_ids);
					if ( !is_wp_error($object_terms) )		
					{					
						$parent_ids = [];
						foreach( $object_terms as $term )
						{							
							$parent_ids[$term->term_id] = $term->parent;
						}
						foreach( $object_terms as $key => $term )
						{
							$term->term_id = (int)$term->term_id;
							if ( isset($results['categories'][$term->term_id]) )
								$results['categories'][$term->term_id]['count']++;
							else
							{
								$parent = isset($parent_ids[$term->parent])?(int)$term->parent:0;								
								$results['categories'][$term->term_id] = ['id' => $term->term_id, 'parent' => $parent, 'name' => stripcslashes($term->name), 'count' => 1];
							}
							unset($object_terms[$key]);
						}		
						if ( count($results['categories']) < 2 )
							$results['categories'] = array();
						else
							usort($results['categories'], function($a, $b){ return ( strcasecmp($a['name'], $b['name'])); });
					}	
				}
			}
		}
		if ( $returned['prices'] )
		{
			$type_price = isset($parameters['type_price'])?sanitize_text_field($parameters['type_price']):'';					
			$query = $query_vars;
			$query["orderby"]  = 'price';		
			$query["type_price"] = $type_price;
			$query["order"]    = 'DESC';			
			$query['posts_per_page'] = 3;
			$query['nopaging'] = false;						
			if ( !empty($query['price_meta_query']) )
				unset($query['price_meta_query']);	
			$posts = usam_get_products( $query );		
			if ( !empty($posts) && count($posts) > 2 )
			{
				$max_price = usam_get_product_price($posts[0], $type_price);	
				$query["order"] = 'ASC';
				$query['posts_per_page'] = 1;
				$posts = usam_get_products( $query );	
				if ( empty($posts[0]) )
					$min_price = 0;
				else
					$min_price = usam_get_product_price($posts[0], $type_price);					
				$results['prices'] = ['min_price' => round($min_price), 'max_price' => round($max_price)];			
			}
		}
		if ( $returned['shop'] )
		{
			$storages = usam_get_storages(['issuing' => 1]);
			if ( count($storages) > 1 )
			{
				$results['storages'][] = ['name' => __('По умолчанию', 'usam'), 'id' => 0];
				foreach( $storages as $storage )
				{
					$results['storages'][] = ['name' => stripcslashes($storage->title), 'id' => $storage->id];
				}
			}
		}
		if ( $returned['individual_price'] )
		{
			$companies = usam_get_companies_customer( );
			if ( count($companies) > 1 )
			{
				foreach( $companies as $company )
				{
					$results['companies'][] = ['name' => stripcslashes($company->name), 'id' => $company->id];
				}
			}
		}
		return apply_filters( 'usam_api_filters', $results, $query_vars, $parameters );
	}
	
	public static function get_filter_categories( WP_REST_Request $request )
	{					
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$show_active = !empty($parameters['show_active'])?$parameters['show_active']:1;
		$take_menu = !empty($parameters['take_menu'])?$parameters['take_menu']:1;
		
		$anonymous_function = function( $t ) { 
			$t->query_vars['orderby'] = 'sort';
		}; 
		add_action('parse_term_query', $anonymous_function, 10, 2);	
		return usam_get_walker_terms_list([], ['taxonomy' => 'usam-category', 'show_active' => $show_active, 'take_menu' => $take_menu, 'class_ul' => 'usam_categories_list show_active_category']);
	}
	
	public static function insert_products( WP_REST_Request $request )
	{
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();			
				
		$columns = $parameters['items'][0];
		$is_attributes = false;
		$is_storage = false;
		$is_price = false;
		$is_brand = false;
		$is_category = false;
		foreach($columns as $column => $value)
		{
			if ( $column == 'attributes' )
				$is_attributes = true;
			elseif ( $column == 'storages' )
				$is_storage = true;
			elseif ( $column == 'prices' )
				$is_price = true;
			elseif ( $column == 'brand' )
				$is_brand = true;
			elseif ( $column == 'category' )
				$is_category = true;
		}				
		usam_start_import_products();
	
		$codes = [];	
		foreach($parameters['items'] as $number => $row)
		{ 				
			if ( !empty($row['code']) )
				$codes[] = $row['code'];	
		}			
		$codes = usam_get_product_ids_by_code( $codes );					
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
		$related_products = [];
		$product_ids = [];
		$units = usam_get_list_units();	
		foreach($parameters['items'] as $number => $row)		
		{ 			
			$product = USAM_Products_API::product_data_formatting( $row );			
			extract( $product );	
			
			$product_id = 0;
			if ( isset($row['id']) )
				$product_id = (int)$row['id'];
			elseif ( isset($row['ID']) )
				$product_id = (int)$row['ID'];
			elseif ( !empty($product['productmeta']['code']) )
				$product_id = usam_get_product_id_by_code( $product['productmeta']['code'] );	
			elseif( !empty($product['productmeta']['code_main_site']) )
				$product_id = usam_get_product_id_by_meta( 'code_main_site', $product['productmeta']['code_main_site'] );//проверка существует ли дубликат этого товара
			if ( !$product_id )
			{
				$_product = new USAM_Product( $product );				
				$product_id = $_product->insert_product( $attributes );	
			}
			elseif ( $product_id )
			{
				if ( !empty($product['thumbnail']) )
				{	
					$thumbnail_id = get_post_thumbnail_id( $product_id );	
					if ( $thumbnail_id )
					{
						$attachment_hash = pathinfo($product['thumbnail'], PATHINFO_FILENAME);
						$post_attachment_path = get_attached_file($thumbnail_id, true);		
						if ( file_exists($post_attachment_path) ) 
						{
							$post_attachment_hash = pathinfo($post_attachment_path, PATHINFO_FILENAME);
							if ( $post_attachment_hash != $attachment_hash )
								wp_delete_attachment( $thumbnail_id, true );
							else
								unset($product['thumbnail']);
						}
					}				
				}			
				$_product = new USAM_Product( $product_id );	
				$_product->set( $product );				
				$_product->update_product( $attributes );				
			}	
			if ( $product_id )				
			{						
				if ( self::check_wpdb_error() )
					break;	
				if ( !empty($product['productmeta']['code']) )
					$product_ids[$product['productmeta']['code']] = $product_id;
				elseif ( !empty($product['productmeta']['code_main_site']) )
					$product_ids[$product['productmeta']['code_main_site']] = $product_id;
				else
					$product_ids[] = $product_id;	
				if ( !empty($product['thumbnail']) || !empty($product['media_url']) )
					$_product->insert_media();
				
				if( !empty($row['crosssell']) )				
					$related_products[$product_id]['crosssell'] = $row['crosssell'];
				if( !empty($row['similar']) )
					$related_products[$product_id]['similar'] = $row['similar'];
				if ( !empty($row['codes_additional_unit']) )
				{
					$product_additional_units = array();				
					$p_unit_measure = usam_get_product_meta($product_id, 'unit_measure');
					$p_unit = usam_get_product_meta( $product_id, 'unit' );
					foreach( $row['codes_additional_unit'] as $key => $additional_unit )
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
							$add_unit = isset($row['additional_units'][$key])?$row['additional_units'][$key] : 1;	
							if ( $p_unit_measure != $additional_unit_measure || $add_unit != $p_unit )
								$product_additional_units[] = ['unit' => $add_unit, 'unit_measure' => $additional_unit_measure];
						}
					}		
					usam_update_product_metadata( $product_id, 'additional_units', $product_additional_units );
				}				
				usam_clean_product_cache( $product_id );
			}
			unset($parameters['items'][$number]);
		}
		unset($row);
		USAM_Products_API::update_related_products( $related_products );
		
		usam_end_import_products();
		
		return $product_ids;
	}	
	
	private static function product_data_formatting( $row )
	{			
		static $storages = null, $product_attributes = null, $terms = null; 
					 
		$product = [];					
		if ( isset($row['post_title']) )
			$product['post_title'] = trim(sanitize_text_field(stripcslashes($row['post_title'])));
		if ( isset($row['post_name']) )
			$product['post_name'] = $row['post_name'];
		if ( isset($row['post_excerpt']) )
			$product['post_excerpt'] = trim(sanitize_textarea_field(stripslashes($row['post_excerpt'])));
		if ( isset($row['post_content']) )
			$product['post_content'] = trim(sanitize_textarea_field(stripslashes($row['post_content'])));
		if ( isset($row['menu_order']) )
			$product['menu_order'] = absint($row['menu_order']);
		if ( isset($row['post_date']) )
			$product['post_date'] = $row['post_date'];
		if ( isset($row['post_status']) )
		{				
			$stati = get_post_stati();
			if ( isset($stati[$row['post_status']]) )
			{
				$change_post_status = true;
				$product['post_status'] = $row['post_status'];
				if ( $product['post_status'] == 'publish' )
				{
					if ( !current_user_can('publish_products') )
						$product['post_status'] = 'pending';
				}						
			}
		}		
		if ( isset($row['post_author']) )
			$product['post_author'] = absint($row['post_author']);
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
		{
			if ( !empty($row['seller_id']) ) 
				$product['productmeta']['seller_id'] = $row['seller_id'];				
			elseif ( !empty($row['post_author']) )
				$product['productmeta']['seller_id'] = usam_get_id_seller( $row['post_author'] );
			elseif ( current_user_can('seller_company') || current_user_can('seller_contact') )
				$product['productmeta']['seller_id'] = usam_get_id_seller();	 
		}
		if ( isset($row['comment_status']) )
			$product['comment_status'] = $row['comment_status'];			
		if (!empty($row['weight']) )
		{			
			$product['productmeta']['weight'] = usam_string_to_float($row['weight']);
			if ( !empty($row['weight_unit']) )
				$product['productmeta']['weight'] = usam_convert_weight( $product['productmeta']['weight'], $row['weight_unit'] );	
		}	
		foreach( ['sku','virtual','code','barcode', 'code_main_site'] as $meta )
		{				
			if ( isset($row[$meta]) )
				$product['productmeta'][$meta] = trim(sanitize_text_field($row[$meta]));
		}			
		if ( isset($row['contractor']) && is_numeric($row['contractor']) )
			$product['productmeta']['contractor'] = $row['contractor'];	
		foreach( ['views','rating','rating_count'] as $meta )
		{				
			if ( isset($row[$meta]) )
				$product['postmeta'][$meta] = (int)preg_replace('~\D+~','',$row[$meta]);
		}		
		if ( isset($row['unit']) )
			$product['productmeta']['unit'] = usam_string_to_float($row['unit']);
		if ( isset($row['under_order']) )
			$product['productmeta']['under_order'] = (int)$row['under_order'];	
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
		
		if ( !empty($row['thumbnail']) )
			$product['thumbnail'] = $row['thumbnail'];	
		if ( !empty($row['images']) )
			$product['media_url'] = $row['images'];		
		if ( !empty($row['image_gallery']) )
			$product['image_gallery'] = $row['image_gallery'];		
		if ( !empty($row['not_limited']) )
		{
			if ( $storages === null )
				$storages = usam_get_storages(['fields' => 'code=>data', 'active' => 'all']);	
			foreach( $storages as $code => $storage )
			{
				$product['product_stock']['storage_'.$storage->id] = USAM_UNLIMITED_STOCK;
			}
		}	
		elseif( !empty($row['storages']) )
		{
			if ( $storages === null )
				$storages = usam_get_storages(['fields' => 'code=>data', 'active' => 'all']);
			foreach( $storages as $code => $storage )
			{
				$meta_key = 'storage_'.$storage->id;
				if ( isset($row['storages'][$code]) )
					$product['product_stock'][$meta_key] = $row['storages'][$code];	
				elseif ( isset($row['storages'][$storage->id]) )
					$product['product_stock'][$meta_key] = $row['storages'][$storage->id];						
			}			
		}	
		elseif( isset($row['stock']) )
		{
			if ( $storages === null )
				$storages = usam_get_storages(['shipping' => 1, 'active' => 'all']);
			foreach( $storages as $storage )
			{
				$product['product_stock']['storage_'.$storage->id] = $row['stock'];	
				$row['stock'] = 0;
			}			
		}			
		if( !empty($row['prices']) )
		{
			$prices = usam_get_prices();
			foreach( $prices as $tprice )
			{
				foreach( $row['prices'] as $code => $api_price )
				{
					if ( !empty($tprice['external_code']) && $code == $tprice['external_code'] )
						$product['prices']['price_'.$tprice['code']] = $api_price;
					else						
					{					
						foreach( ['price', 'underprice'] as $key )
						{								
							if ( $code == $key.'_'.$tprice['code'] )
								$product['prices'][$key.'_'.$tprice['code']] = $api_price; 
						}
					}
				}	
			}
		}
		elseif ( !empty($row['price']) )
		{
			$type_price = usam_get_customer_price_code();
			$product['prices'] = ['price_'.$type_price => $row['price']];
		}		
		$taxonomies = get_taxonomies(['object_type' => ['usam-product']]);
		foreach( $taxonomies as $taxonomy ) 
		{			
			if ( isset($row[$taxonomy]) )
				$product['tax_input'][$taxonomy] = array_map('intval', $row[$taxonomy]);
		}		
		if ( (isset($row['category']) || isset($row['brand'])) && $terms === null )
		{
			$terms = [];			
			$taxonomy = [];
			if( isset($row['category']) )
				$taxonomy[] = 'usam-category';
			if( isset($row['brand']) )
				$taxonomy[] = 'usam-brands';
			$ids = get_terms(['fields' => 'ids', 'taxonomy' => $taxonomy, 'hide_empty' => 0]);
			foreach ( $ids as $id )
			{
				$external_code = usam_get_term_metadata($id, 'external_code');
				if ( $external_code )
					$terms[$external_code] = $id;
			}
		}				
		if ( !empty($row['category']) )
		{
			if ( is_array($row['category']) )
				foreach( $row['category'] as $code )
				{
					if ( isset($terms[$code]) )
						$product['tax_input']['usam-category'][] = $terms[$code];
				}
			else	
				$product['tax_input']['usam-category'] = [ $row['category'] ];				
		}				
		if ( !empty($row['brand']) && isset($terms[$row['brand']]) )
			$product['tax_input']['usam-brands'][] = $terms[$row['brand']];			
		$attribute_values = [];				
		if ( !empty($row['attributes_external_code']) )
		{			
			if ( $product_attributes === null )
			{			
				$_terms = get_terms(['taxonomy' => 'usam-product_attributes', 'hide_empty' => 0, 'term_meta_cache' => true]);
				foreach( $_terms as $term )
				{
					$external_code = usam_get_term_metadata($term->term_id, 'external_code');
					if ( $external_code )
						$product_attributes[$external_code] = $term->slug;
				}			
			}			
			foreach( $row['attributes_external_code'] as $code => $attribute )
			{
				$code = mb_strtolower(sanitize_text_field($code));
				if ( isset($product_attributes[$code]) )
					$attribute_values[$product_attributes[$code]] = $attribute;
			}		
		}			
		elseif ( !empty($row['attributes']) )
		{			
			foreach ( $row['attributes'] as $code => $attribute )
			{
				$code = sanitize_text_field($code);				
				$attribute_values[$code] = $attribute;
			}		
		}	  
		return ['product' => $product, 'attributes' => $attribute_values];
	}
	
	public static function insert_product( WP_REST_Request $request )
	{
		$product = $request->get_json_params();	
		if ( !$product )
			$product = $request->get_body_params();	
 
		if( !empty($product['code_main_site']) )
		{
			$product_id = usam_get_product_id_by_meta( 'code_main_site', $product['code_main_site'] );//проверка существует ли дубликат этого товара
			if( $product_id )
				return $product_id;
		}
		$product = USAM_Products_API::product_data_formatting( $product );
		extract( $product );	
	 
		$_product = new USAM_Product( $product );
		$product_id = $_product->insert_product( $attributes );		
		if( !empty($product['image_gallery']) )
		{			
			$_product->insert_media();
			if( empty($product['thumbnail_id']) )
				$product['thumbnail_id'] = current($product['image_gallery']);
		}
		elseif( !empty($product['media_url']) )
			$_product->insert_media();
		if ( !empty($product['thumbnail_id']) )
			set_post_thumbnail( $product_id, $product['thumbnail_id'] );
		return $product_id;
	}
	
	public static function update_product( WP_REST_Request $request )
	{
		$product = $request->get_json_params();	
		if ( !$product )
			$product = $request->get_body_params();		
		
		$product_id = $request->get_param( 'product_id' );		
		$post = get_post( $product_id );	
		if ( empty($post) )
			return new WP_Error( 'no_product', 'Invalid product id', ['status' => 404]);
		if ( !current_user_can('publish_products') )
		{						
			$user_id = get_current_user_id();
			if ( $post->post_author != $user_id )
				return new WP_Error( 'no_product', 'Вы не можете редактировать этот товар', ['status' => 404]);
		}		
		$product = USAM_Products_API::product_data_formatting( $product );		
		extract( $product );		
		$_product = new USAM_Product( $product_id );
		$_product->set( $product );
		$_product->update_product( $attributes );
		if ( !empty($product['image_gallery']) )
		{			
			$_product->insert_media();
			if ( empty($product['thumbnail_id']) && !get_post_thumbnail_id( $product_id ) )
				$product['thumbnail_id'] = current($product['image_gallery']);
		}	
		elseif ( !empty($product['media_url']) )
			$_product->insert_media();
		if ( !empty($product['thumbnail_id']) )
			set_post_thumbnail( $product_id, $product['thumbnail_id'] );
		return $product_id;
	}	
	
	public static function delete_product( WP_REST_Request $request )
	{
		$product_id = $request->get_param( 'product_id' );
		$post = get_post( $product_id );		
		if ( empty($post) )
			return new WP_Error( 'no_product', 'Invalid product id', ['status' => 404]);
		if ( !current_user_can('publish_products') )
		{						
			$user_id = get_current_user_id();
			if ( $post->post_author != $user_id )
				return new WP_Error( 'no_product', 'Вы не можете удалить этот товар', ['status' => 404]);
		}	
		return wp_delete_post( $product_id, true );
	}	
	
	public static function upload_product_images( WP_REST_Request $request ) 
	{			
		$product_id = $request->get_param( 'product_id' );
			
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';	
		
		$parameters = $request->get_file_params();	
		$attachment_id = media_handle_sideload($parameters['file'], $product_id );
		if( is_wp_error($attachment_id) )
			return $attachment_id;
		
		$attachment = ['ID' => $attachment_id];
		$attachment['full'] = wp_get_attachment_image_url($attachment_id, 'fill' );
		$attachment['small_image'] = wp_get_attachment_image_url($attachment_id, 'small-product-thumbnail' );
		$attachment['medium_image'] = wp_get_attachment_image_url($attachment_id, 'medium-single-product' );		
		return $attachment;
	}
	
	public static function image_editor( WP_REST_Request $request ) 
	{			
		$id = $request->get_param( 'id' );		
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		if ( !current_user_can('publish_products') )
		{
			$post = get_post( $id );			
			$user_id = get_current_user_id();
			if ( $post->post_author != $user_id )
				return false;
		}				
		$filepath = get_attached_file( $id ); 
		$image = wp_get_image_editor( $filepath );	
		$save = false;
		if ( ! is_wp_error($image) ) 					
		{
			$data = wp_get_attachment_metadata( $id );
			if ( !empty($parameters['width']) && !empty($parameters['height']) )
			{				
				$image->resize( $parameters['width'], $parameters['height'], false );	
				$save = true;
			}
			if ( !empty($parameters['rotate']) )
			{
				$image->rotate( $parameters['rotate'] );				
				if ( !empty($data['sizes']) )
				{
					$path_parts = pathinfo($filepath);
					foreach ( $data['sizes'] as $size => $meta_size ) 
					{
						$meta_filepath = $path_parts['dirname']. '/' . $meta_size['file'];	
						if ( file_exists($meta_filepath) )
						{
							$sizeimage = wp_get_image_editor( $meta_filepath );
							$sizeimage->rotate( $parameters['rotate'] );
							$sizeimage->save( $meta_filepath );
						}
					}
				}				
				$save = true;
			}
			if ( $save )
				$image->save( $filepath );
		}		
		return $save;
	}	
			
	private static function update_related_products( $related_products )
	{								
		if ( !empty($related_products) )
		{
			usam_get_associated_products( array_keys($related_products) );		
			if ( self::check_wpdb_error() )
				return false;			
			$all_codes = array();
			foreach($related_products as $product_id => $lists)		
			{ 
				foreach($lists as $list => $codes)
					$all_codes = array_merge($all_codes, $codes);
			}	
			$all_codes = usam_get_product_ids_by_code( $all_codes, 'code' );
			if ( self::check_wpdb_error() )
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
	
	public static function get_attribute_values( WP_REST_Request $request )
	{
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );		
		if( isset($query_vars['attribute_external_code']) )	
			$query_vars['cache_term_meta'] = true;
				
		$query = new USAM_Product_Attribute_Values_Query( $query_vars );	
		$items = $query->get_results();		
		if ( !empty($items) )
		{			
			$fields = isset($parameters['fields'])?$parameters['fields']:'';
			if( is_string($fields) && $fields == 'autocomplete' )
			{	
				foreach ( $items as &$item )
				{
					$x = new stdClass();				
					$x->name = stripcslashes($item->value);
					$x->id = $item->id;
					$item = $x;
				}				
			}
			elseif( isset($query_vars['attribute_external_code']) )
			{
				foreach($items as &$item)	
					$item->external_code = usam_get_term_metadata($item->attribute_id, 'external_code');
			}			
			$count = $query->get_total();
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}
		
	public static function combine_attribute_values( WP_REST_Request $request )
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		$term = get_term( $parameters['attribute_id'], 'usam-product_attributes' );	
		if( $term )
		{
			$key = array_search($parameters['main'], $parameters['ids']);
			if( $key !== false )
				unset($parameters['ids'][$key]);
			$ids = implode( ",", $parameters['ids'] );			
			global $wpdb;
			$wpdb->query("UPDATE ".usam_get_table_db('product_attribute')." SET meta_value='{$parameters['main']}' WHERE `meta_value` IN ($ids) AND meta_key='$term->slug'");		
			$wpdb->query("UPDATE ".usam_get_table_db('product_filters')." SET filter_id='{$parameters['main']}' WHERE `filter_id` IN ($ids)");
			$wpdb->query("DELETE FROM ".usam_get_table_db('product_attribute_options')." WHERE `id` IN ($ids)");
		}		
		return true;
	}
	
	public static function get_popular_search_terms( WP_REST_Request $request )
	{
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );		
		
		if ( !current_user_can('universam_api') )
		{
			if ( empty($query_vars['fields']) )
				$query_vars['fields'] = ['phrase', 'number_results', 'date_insert'];
		}		
		require_once( USAM_FILE_PATH . '/includes/search/search_query.class.php' );
		$query = new USAM_Searching_Results( $query_vars );
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			$count = $query->get_total();
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;
	}	
	
	public static function get_balances_stock( WP_REST_Request $request )
	{	
		$product_id = $request->get_param( 'product_id' );
		$parameters = self::get_parameters( $request );	
		$args = ['fields' => 'id', 'number' => 20, 'cache_meta' => true, 'cache_location' => true, 'owner' => ''];
		if ( !empty($parameters['location']) )
			$args['location_id'] = [0, usam_get_customer_location()];
		if ( !empty($parameters['issuing']) )
			$args['issuing'] = 1;
		$storages = usam_get_storages( $args );
		$results = [];
		if ( $storages )
		{
			foreach ( $storages as $storage_id )
			{
				$in_stock = usam_get_stock_in_storage($storage_id, $product_id) > 0?1:0;		
				if ( $in_stock || isset($parameters['in_stock']) && $parameters['in_stock'] )
					$results[] = ['id' => $storage_id, 'address' => usam_get_storage_metadata( $storage_id, 'address'), 'in_stock' => $in_stock, 'stock' => usam_get_stock_in_storage($storage_id, $product_id, 'short')];
			}
		}
		return $results;
	}
	
	/** обновление цены, артикула продукта в вариациях через скрипт
	 */
	public static function get_product_variation( WP_REST_Request $request )
	{
		$product_id = $request->get_param( 'product_id' );
		$parameters = self::get_parameters( $request );
		
		$response['product_id'] = $product_id;	
		
		$variation_ids = array_map('intval', (array)$parameters['variations']);					
		$variation_product_id = usam_get_id_product_variation( $product_id, $variation_ids );		
		$response['variation_product_id'] = $variation_product_id;	
		if ( !empty($variation_product_id) ) 
		{				
			$stock = usam_product_remaining_stock( $variation_product_id );
			if ( $stock === 0 )
				return ['errors' => [__('Извините, но этой вариации нет в наличии.', 'usam')]];	
			
			$old_price = usam_get_product_old_price( $variation_product_id );		
			if ( $old_price == 0 )
			{
				$old_price = 0;
				$discount = '';	
				$old_price_currency = '';					
			}
			else
			{
				$old_price_currency = usam_get_formatted_price( $old_price );
				$you_save_amount = usam_get_product_discount( $variation_product_id );
				$you_save_percentage = usam_get_percent_product_discount( $variation_product_id );
				$discount = usam_get_formatted_price( $you_save_amount ) . "! (" . $you_save_percentage . "%)";
			}				
			$price = usam_get_product_price( $variation_product_id );						
			$sku = usam_get_product_meta( $variation_product_id, 'sku' );			
			$response += [
				'price'             => usam_get_formatted_price( $price ),
				'oldprice'          => $old_price_currency,
				'numeric_old_price' => $old_price,
				'discount'          => $discount,											
				'numeric_price'     => $price,
				'sku'    			=>  $sku,
				'variation_product_id' => $variation_product_id,
			];
			$thumbnail = usam_get_product_thumbnail( $variation_product_id, 'product-thumbnails' );
			if ( !empty($thumbnail['src']) )
				$response['thumbnail'] = $thumbnail;
		}
		return $response;		
	}	
	
	public static function get_generated_sku( WP_REST_Request $request )
	{
		$product_id = $request->get_param( 'product_id' );
		return self::sku_generator( $product_id );
	}
	
	public static function get_generated_barcode( WP_REST_Request $request )
	{
		$product_id = $request->get_param( 'product_id' );
		return self::barcode_generator( $product_id );
	}
	
	private static function barcode_generator( $product_id )
	{	
		$country = 460;
		$country_strlen = strlen((string)$country);
		$strlen = strlen((string)$product_id);
		$strlen_product = 12-$country_strlen;
		if ( $strlen <= $strlen_product )	
			$barcode = str_repeat( 0, $strlen_product-$strlen ).$product_id;
		else
			$barcode = substr($product_id, 0, $strlen_product); 
		
		require_once( USAM_FILE_PATH . '/admin/includes/barcode/barcode.php' );
		$b = new USAM_Barcode();
		$barcode = $b->generator_ean13( $country.$barcode, 'EAN');
		return apply_filters( 'usam_generated_barcode', $barcode, $product_id );
	}
	
	private static function sku_generator( $product_id )
	{	
		$sku = $product_id.''.usam_rand_string( 4, "abcdefghijklmnopqrstuvwxyz1234567890");
		$strlen = strlen($sku);
		if ( $strlen > 10 )
			$sku = substr($sku, 0, 10);
		return apply_filters( 'usam_generated_sku', $sku, $product_id );
	}	
	
	public static function get_product_tabs( WP_REST_Request $request )
	{	
		$parameters = self::get_parameters( $request );
		require_once( USAM_FILE_PATH . '/includes/product/custom_product_tabs_query.class.php' );
		$tabs = usam_get_custom_product_tabs( $parameters );
		return $tabs;
	}	
	
	public static function get_product_tab( WP_REST_Request $request )
	{
		$id = $request->get_param( 'id' );
		
		require_once( USAM_FILE_PATH . '/includes/product/custom_product_tab.class.php' );
		return usam_get_custom_product_tab( $id );
	}
	
	public static function insert_product_tab( WP_REST_Request $request )
	{
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		
		require_once( USAM_FILE_PATH . '/includes/product/custom_product_tab.class.php' );
		return usam_insert_custom_product_tab( $parameters );
	}
	
	public static function delete_product_tab( WP_REST_Request $request )
	{
		$id = $request->get_param( 'id' );
		
		require_once( USAM_FILE_PATH . '/includes/product/custom_product_tab.class.php' );
		return usam_delete_custom_product_tab( $id );
	}
	
	public static function update_product_tab( WP_REST_Request $request )
	{
		$id = $request->get_param( 'id' );
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		
		require_once( USAM_FILE_PATH . '/includes/product/custom_product_tab.class.php' );	
		return usam_update_custom_product_tab( $id, $parameters );
	}	
	
	public static function update_product_rating( WP_REST_Request $request )
	{
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		
		$product_id = $request->get_param( 'product_id' );		
		$post = get_post( $product_id );	
		if ( empty($post) )
			return new WP_Error( 'no_product', 'Invalid product id', ['status' => 404]);
		if ( current_user_can('publish_products') || $post->post_status == 'publish' )
		{						
			return usam_update_post_rating($product_id, $parameters['rating'] );	
		}	
		return new WP_Error( 'forbidden', 'Forbidden', ['status' => 403]);
	}	
	
	public static function get_reputation_items( WP_REST_Request $request )
	{	
		$parameters = self::get_parameters( $request );		
		$query_vars = self::get_query_vars( $parameters, $parameters );	
							
		require_once( USAM_FILE_PATH . '/includes/product/products_on_internet_query.class.php' );		
		
		$query = new USAM_Products_Internet_Query( $query_vars );
		$items = $query->get_results();					
		if ( !empty($items) )
		{			
		/*	foreach ( $items as &$item )
			{							
				if( isset($parameters['add_fields']) )
				{	
			
				}	
			}	*/
			$results = ['count' => $query->get_total(), 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];
		return $results;	
	}	
	
	public static function update_product_reputation_item( WP_REST_Request $request )
	{
		$parameters = $request->get_json_params();	
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$id = $request->get_param( 'id' );		
		require_once( USAM_FILE_PATH . '/includes/product/products_on_internet.class.php' );
		return usam_update_product_internet( $id, $parameters );	
	}
	
	public static function delete_product_reputation_item( WP_REST_Request $request )
	{		
		$id = $request->get_param( 'id' );
		require_once( USAM_FILE_PATH . '/includes/product/products_on_internet.class.php' );		
		return usam_delete_product_internet( $id );
	}		
	
	public static function get_term_categories( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );		
		self::$query_vars['taxonomy'] = 'usam-category';		
		return self::get_terms( self::$query_vars );
	}
	
	public static function save_categories( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$results = [];
		if ( !empty($parameters['items']) )
		{
			foreach( $parameters['items'] as $args )
			{
				$result = usam_new_term( $args, 'usam-category' );
				$data = ['term_id' => $result['term_id']];				
				if ( !empty($args['external_code']) )
					$data[$args['external_code']] = $term_id;
				$results[] = $data;
			}
		}		
		return $results;	
	}
	
	public static function update_product_category( WP_REST_Request $request ) 
	{	
		$term_id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$args = [];
		foreach( ['name', 'slug', 'description', 'parent', 'alias_of'] as $key )
		{
			if ( isset($parameters[$key]) )
				$args[$key] = $parameters[$key];
		}		
		if ( $args )
			wp_update_term($term_id, 'usam-product_attribute', $args);
		foreach( ['display_type', 'product_sort_by', 'sort', 'external_code'] as $meta_key )
		{
			if ( isset($parameters[$meta_key]) )
				usam_update_term_metadata($term_id, $meta_key, $parameters[$meta_key]);
		}
		return true;
	}	
	
	public static function delete_categories( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		return self::delete_terms( $parameters, 'usam-category');	
	}	
	
	public static function get_term_category_sales( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );		
		self::$query_vars['taxonomy'] = 'usam-category_sale';
		return self::get_terms( self::$query_vars );		
	}
	
	public static function save_category_sales( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$results = [];
		if ( !empty($parameters['items']) )
		{
			foreach( $parameters['items'] as $args )
			{
				$result = usam_new_term( $args, 'usam-category_sale' );				
				$data = ['term_id' => $result['term_id']];				
				if ( !empty($args['external_code']) )
					$data[$args['external_code']] = $term_id;
				$results[] = $data;
			}
		}		
		return $results;	
	}	
	
	public static function delete_category_sales( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		return self::delete_terms( $parameters, 'usam-category_sale');	
	}
	
	public static function get_term_brands( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );		
		self::$query_vars['taxonomy'] = 'usam-brands';
		return self::get_terms( self::$query_vars );		
	}		
	
	public static function save_brands( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$results = [];
		if ( !empty($parameters['items']) )
		{
			foreach( $parameters['items'] as $args )
			{
				$result = usam_new_term( $args, 'usam-brands' );				
				$data = ['term_id' => $result['term_id']];				
				if ( !empty($args['external_code']) )
					$data[$args['external_code']] = $term_id;
				$results[] = $data;
			}
		}		
		return $results;	
	}			
	
	public static function update_brand( WP_REST_Request $request ) 
	{	
		$term_id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$args = [];
		foreach( ['name', 'slug', 'description', 'parent', 'alias_of'] as $key )
		{
			if ( isset($parameters[$key]) )
				$args[$key] = $parameters[$key];
		}		
		if ( $args )
			wp_update_term($term_id, 'usam-brands', $args);
		foreach( ['display_type', 'product_sort_by', 'sort', 'external_code'] as $meta_key )
		{
			if ( isset($parameters[$meta_key]) )
				usam_update_term_metadata($term_id, $meta_key, $parameters[$meta_key]);
		}
		return true;
	}		
	
	public static function delete_brands( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		return self::delete_terms( $parameters, 'usam-brands');	
	}
	
	public static function get_term_variations( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );		
		self::$query_vars['taxonomy'] = 'usam-variation';		
		return self::get_terms( self::$query_vars );
	}
	
	public static function delete_variations( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		return self::delete_terms( $parameters, 'usam-variation');	
	}
	
	public static function save_variations( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$results = [];
		if ( !empty($parameters['items']) )
		{
			foreach( $parameters['items'] as $args )
			{
				$result = usam_new_term( $args, 'usam-variation' );
				$data = ['term_id' => $result['term_id']];				
				if ( !empty($args['external_code']) )
					$data[$args['external_code']] = $term_id;
				$results[] = $data;
			}
		}		
		return $results;	
	}
		
	public static function get_term_selections( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );		
		self::$query_vars['taxonomy'] = 'usam-selection';		
		return self::get_terms( self::$query_vars );	
	}
	
	public static function delete_selections( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		return self::delete_terms( $parameters, 'usam-selection');	
	}
	
	public static function save_selections( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$results = [];
		if ( !empty($parameters['items']) )
		{
			foreach( $parameters['items'] as $args )
			{
				$result = usam_new_term( $args, 'usam-selection' );
				$data = ['term_id' => $result['term_id']];				
				if ( !empty($args['external_code']) )
					$data[$args['external_code']] = $term_id;
				$results[] = $data;
			}
		}		
		return $results;	
	}
	
	public static function get_term_catalogs( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );		
		self::$query_vars['taxonomy'] = 'usam-catalog';
		
		return self::get_terms( self::$query_vars );	
	}	
	
	public static function delete_catalogs( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		return self::delete_terms( $parameters, 'usam-catalog');	
	}
	
	public static function save_catalogs( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$results = [];
		if ( !empty($parameters['items']) )
		{
			foreach( $parameters['items'] as $args )
			{
				$result = usam_new_term( $args, 'usam-catalog' );
				$data = ['term_id' => $result['term_id']];				
				if ( !empty($args['external_code']) )
					$data[$args['external_code']] = $term_id;
				$results[] = $data;
			}
		}		
		return $results;	
	}	
	
	public static function get_term_product_tags( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );		
		self::$query_vars['taxonomy'] = 'product_tag';
		
		return self::get_terms( self::$query_vars );	
	}
	
	public static function delete_product_tags( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		return self::delete_terms( $parameters, 'product_tag');	
	}
	
	public static function save_product_tags( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$results = [];
		if ( !empty($parameters['items']) )
		{
			foreach( $parameters['items'] as $args )
			{
				$result = usam_new_term( $args, 'product_tag' );
				$data = ['term_id' => $result['term_id']];				
				if ( !empty($args['external_code']) )
					$data[$args['external_code']] = $term_id;
				$results[] = $data;
			}
		}		
		return $results;	
	}
			
	public static function get_term_product_attributes( WP_REST_Request $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );				
		self::$query_vars['taxonomy'] = 'usam-product_attributes';
		
		if ( !empty($args['field_type']) )
		{
			self::$query_vars['usam_meta_query'] = [['key' => 'field_type', 'value' => (array)$args['field_type'], 'compare' => 'IN']];			 
			unset($args['field_type']);
		}	
		self::$query_vars['relationships_cache'] = 'usam-category';
		self::$query_vars['term_meta_cache'] = true;	
		$results = self::get_terms( self::$query_vars );	
		$add_fields = !empty(self::$query_vars['add_fields'])?(array)self::$query_vars['add_fields']:[];	 
		if ( !empty($results['items']) && (!isset(self::$query_vars['fields']) || self::$query_vars['fields'] !== 'autocomplete') )
		{
			if ( in_array('options', $add_fields) )
			{
				$attribute_ids = [];
				foreach( $results['items'] as $item )
					$attribute_ids[] = $item->term_id;
				$attribute_values = [];
				foreach( usam_get_product_attribute_values(['attribute_id' => $attribute_ids, 'orderby' => 'value']) as $option )
				{
					$attribute_values[$option->attribute_id][] =  ['id' => $option->id, 'name' => $option->value, 'code' => $option->code];
				}
			}			
			foreach( $results['items'] as &$item )
			{	 
				$item->field_type = usam_get_term_metadata($item->term_id, 'field_type');
				$item->external_code = usam_get_term_metadata($item->term_id, 'external_code');
				$item->mandatory = usam_get_term_metadata($item->term_id, 'mandatory');
				$item->filter = usam_get_term_metadata($item->term_id, 'filter');
				$item->search = usam_get_term_metadata($item->term_id, 'search');
				$item->sorting_products = usam_get_term_metadata($item->term_id, 'sorting_products');
				$item->compare_products = usam_get_term_metadata($item->term_id, 'compare_products');
				$item->important = usam_get_term_metadata($item->term_id, 'important');
				$item->do_not_show_in_features = usam_get_term_metadata($item->term_id, 'do_not_show_in_features');
				$item->related_categories = usam_get_related_terms( $item->term_id );
				if ( in_array('options', $add_fields) )
				{							
					$item->options = isset($attribute_values[$item->term_id]) ? $attribute_values[$item->term_id] : [];				
				}
			}
		}		
		return $results;	
	}
	
	public static function save_product_attributes( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();		
		$results = [];
		if ( !empty($parameters['items']) )
		{
			foreach( $parameters['items'] as $args )
			{				
				if ( isset($args['status']) && !current_user_can('publish_products') && !current_user_can('universam_api') )
					unset($args['status']);
				$result = usam_new_term( $args, 'usam-product_attributes' );				
				extract( $result );	 
				if ( $insert || current_user_can('publish_products') || current_user_can('universam_api') )
				{
					foreach( ['status', 'mandatory', 'filter', 'search', 'sorting_products', 'compare_products', 'important', 'do_not_show_in_features'] as $meta_key )
					{
						if ( isset($args[$meta_key]) )
							usam_update_term_metadata($term_id, $meta_key, $args[$meta_key]);
					}	
					if ( isset($args['field_type']) )
					{
						if ( !empty($args['variants']) && !in_array($args['field_type'],['M', 'S', 'N', 'COLOR', 'COLOR_SEVERAL', 'BUTTONS', 'AUTOCOMPLETE']) )
							$args['field_type'] = 'S';						
						usam_update_term_metadata($term_id, 'field_type', $args['field_type']);					
					}					
				}
				if ( !empty($args['variants']) )
				{
					foreach( $args['variants'] as $variant ) 
					{
						$variant['attribute_id'] = $term_id;
						if ( !empty($variant['value']) )
						{
							$variant['value'] = stripslashes( $variant['value'] );
							usam_insert_product_attribute_variant( $variant );
						}
					}		
				}				
				if ( isset($args['category']) )
				{
					if ( !is_array($args['category']) )
						$args['category'] = (array)$args['category'];					
					foreach( $args['category'] as $category_id )
						usam_set_taxonomy_relationships($term_id, $category_id, 'usam-category' );	
				}				
				$data = ['term_id' => $result['term_id']];				
				if ( !empty($args['external_code']) )
					$data[$args['external_code']] = $term_id;
				$results[] = $data;		
			}
		}		
		return $results;	
	}
	
	public static function insert_product_attribute( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();	
			
		if ( !current_user_can('publish_products') )
			$parameters['status'] = 'hidden';	
				
		$result = usam_new_term( $parameters, 'usam-product_attributes', true );		 
		$term_id = 0;
		extract( $result );					
		if ( $insert )
		{
			foreach( ['mandatory', 'filter', 'search', 'sorting_products', 'compare_products', 'important', 'do_not_show_in_features'] as $meta_key )
			{
				if ( isset($parameters[$meta_key]) )
					usam_update_term_metadata($term_id, $meta_key, $parameters[$meta_key]);
			}							
			if ( isset($parameters['field_type']) )
			{
				if ( !empty($parameters['variants']) && !in_array($parameters['field_type'],['M', 'S', 'N', 'COLOR', 'COLOR_SEVERAL', 'BUTTONS', 'AUTOCOMPLETE']) )
					$parameters['field_type'] = 'S';						
				usam_update_term_metadata($term_id, 'field_type', $parameters['field_type']);					
			}
			if ( !empty($parameters['variants']) )
			{
				foreach( $parameters['variants'] as $variant ) 
				{
					$variant['attribute_id'] = $term_id;
					if ( !empty($variant['value']) )
					{
						$variant['value'] = stripslashes( $variant['value'] );
						usam_insert_product_attribute_variant( $variant );
					}
				}		
			}
		}		
		return $term_id;	
	}	
	
	public static function update_product_attribute( WP_REST_Request $request ) 
	{	
		$term_id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$args = [];
		foreach( ['name', 'slug', 'description', 'parent', 'alias_of'] as $key )
		{
			if ( isset($parameters[$key]) )
				$args[$key] = $parameters[$key];
		}		
		if ( $args )
			wp_update_term($term_id, 'usam-product_attribute', $args);
		foreach( ['mandatory', 'filter', 'search', 'sorting_products', 'compare_products', 'important', 'do_not_show_in_features'] as $meta_key )
		{
			if ( isset($parameters[$meta_key]) )
				usam_update_term_metadata($term_id, $meta_key, $parameters[$meta_key]);
		}	 
		if ( isset($parameters['category']) )
		{
			foreach( $parameters['category'] as $category_id )
				usam_set_taxonomy_relationships($term_id, $category_id, 'usam-category' );	
		}
		return true;
	}	
	
	public static function delete_product_attributes( WP_REST_Request $request ) 
	{
		$parameters = self::get_parameters( $request );		
		return self::delete_terms( $parameters, 'usam-product_attributes');			
	}		
	
	public static function delete_terms( $parameters, $taxonomy ) 
	{				
		if ( isset($parameters['external_code']) )
			self::$query_vars['usam_meta_query'] = [['key' => 'external_code', 'value' => array_map('sanitize_text_field', $parameters['external_code']), 'compare' => 'IN']];			
		if ( isset($parameters['include']) )
			self::$query_vars['include'] = $parameters['include'];	
		self::$query_vars['taxonomy'] = $taxonomy;
		self::$query_vars['hide_empty'] = 0;
			
		$terms = get_terms( self::$query_vars );			
		$i = 0;
		foreach( $terms as $k => $term )
		{
			$result = wp_delete_term( $term->term_id, $term->taxonomy );
			if ( $result && !is_wp_error( $result ) )
				$i++;
			unset($terms[$k]);
		}
		return $i;
	}

	public static function get_list_terms( $request ) 
	{	
		$parameters = self::get_parameters( $request );		
		self::$query_vars = self::get_query_vars( $parameters, $parameters );	
		return self::get_terms( self::$query_vars );	
	}	
	
	public static function get_categories( WP_REST_Request $request, $parameters = [] ) 
	{	
		return self::get_autocomplete_terms( 'usam-category' );	
	}	
	
	public static function get_category_sales( WP_REST_Request $request, $parameters = [] ) 
	{	
		return self::get_autocomplete_terms( 'usam-category_sale' );	
	}
	
	public static function get_catalogs( WP_REST_Request $request, $parameters = [] ) 
	{	
		return self::get_autocomplete_terms( 'usam-catalog' );	
	}
	
	public static function get_selections( WP_REST_Request $request, $parameters = [] ) 
	{	
		return self::get_autocomplete_terms( 'usam-selection' );	
	}	

	public static function get_brands( WP_REST_Request $request, $parameters = [] ) 
	{	
		return self::get_autocomplete_terms( 'usam-brands' );	
	}	
	
	public static function get_product_tags( WP_REST_Request $request, $parameters = [] )  
	{	
		return self::get_autocomplete_terms( 'product_tag' );	
	}
	
	public static function get_variations( WP_REST_Request $request, $parameters = [] )  
	{		
		return self::get_autocomplete_terms( 'usam-variation' );	
	}
	
	public static function get_autocomplete_terms( $taxonomy ) 
	{	
		$terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => 0, 'orderby' => 'name']);		
		return self::handler_terms( $terms );
	}
	
	public static function handler_terms( $terms, $parent = 0 ) 
	{			
		static $results = [];
		foreach ( $terms as $key => $term )
		{
			if ( $parent == $term->parent )
			{
				$ancestors = usam_get_ancestors( $term->term_id, $term->taxonomy );	
				$name = str_repeat("   ", count($ancestors)).$term->name;
				$results[] = ['id' => $term->term_id, 'name' => $name, 'slug' => $term->slug, 'parent' => $term->parent];
				unset($terms[$key]);				
				$results += self::handler_terms( $terms, $term->term_id );
			}
		}
		if ( $parent === 0 )
		{
			$r = $results;
			$results = [];
			return $r;
		}
		return $results;
	}
	
	public static function delete_term( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );	
		$taxonomy = $request->get_param( 'taxonomy' );		
		$parameters = self::get_parameters( $request );
		$result = wp_delete_term( $id, $taxonomy );
	}
	
	public static function get_term( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );	
		$parameters = self::get_parameters( $request );
		$term = get_term($id, $request->get_param( 'taxonomy' )	);
	
		if( is_wp_error($term) )
			return new WP_Error( 'no_taxonomy', 'Invalid taxonomy', ['status' => 404]);		
		if( $term )
		{
			if( !empty($parameters['add_fields']) )
			{				
				foreach( $parameters['add_fields'] as $k )
				{					
					if( $k == 'metas' )
					{											
						$term->meta = new stdClass();
						$term->postmeta = new stdClass();
						$term->meta_filter = new stdClass();
						$keys = ['title','description','opengraph_title','opengraph_description', 'noindex', 'nofollow', 'exclude_sitemap'];	
						foreach( $keys as $key )
							$term->meta->$key = (string)get_term_meta($id, 'meta_'.$key, true);		
						$term->meta->shortcode = usam_get_seo_shortcode('term');		
						foreach( $keys as $key )
							$term->postmeta->$key = (string)get_term_meta($id, 'postmeta_'.$key, true);	
						$term->postmeta->shortcode = usam_get_seo_shortcode('product');
						foreach( $keys as $key )
							$term->meta_filter->$key = (string)get_term_meta($id, 'meta_filter_'.$key, true);			
						$term->meta_filter->shortcode = usam_get_seo_shortcode('product_filter');		
					}					
					elseif( $k == 'images' )
					{						
						$term->images = [];	
						$images = get_term_meta($term->term_id, 'images', true);
						if( $images )
						{
							$attachments = (array)usam_get_posts(['post_type' => 'attachment', 'post__in' => $images, 'orderby' => 'post__in', 'order' => 'ASC']);			
							if ( !empty($attachments) ) 
							{ 
								foreach( $attachments as $attachment ) 
								{
									$attachment->full = wp_get_attachment_image_url($attachment->ID, 'fill' );
									$attachment->small_image = wp_get_attachment_image_url($attachment->ID, 'small-product-thumbnail' );
									$term->$k[] = $attachment;
								}	
							}
						}	
						$term->representative_image = (int)get_term_meta($term->term_id, 'representative_image', true);	
						$term->thumbnail = (int)get_term_meta($term->term_id, 'thumbnail', true);	
					}					
				}					
			}
		}	
		return $term;		
	}	
	
	public static function save_term( WP_REST_Request $request ) 
	{	
		$id = $request->get_param( 'id' );			
		$term = get_term($id, $request->get_param( 'taxonomy' )	);
		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
	
		if( is_wp_error($term) )
			return new WP_Error( 'no_taxonomy', 'Invalid taxonomy', ['status' => 404]);		
		if( $term )
		{
			if( !empty($parameters['status']) )
				usam_update_term_metadata($id, 'status', $parameters['status'] );
		}	
		return $term;		
	}
			
	protected static function get_taxonomy( WP_REST_Request $request, $parameters = [] ) 
	{				
		$results = [];
		foreach (['category', 'brands', 'category_sale', 'catalog'] as $tax_slug )
		{		
			$tax_obj = get_taxonomy( 'usam-'.$tax_slug );	
			$results[] = ['id' => 'usam-'.$tax_slug, 'name' => $tax_obj->labels->menu_name];
		} 
		return $results;
	}
	
	public static function get_taxonomies( WP_REST_Request $request ) 
	{				
		$parameters = self::get_parameters( $request );	
		if ( !empty($parameters['object_type']) )
			$parameters['object_type'] = array_map('sanitize_text_field', (array)$parameters['object_type']);
		
		$output = 'objects';
		if ( !empty($parameters['output']) )
		{
			$output = $parameters['output'];
			unset($parameters['output']);
		}	
		return array_values(get_taxonomies( $parameters, $output ));
	}	

	public static function get_showcases( WP_REST_Request $request ) 
	{		
		$parameters = self::get_parameters( $request );	
		$query_vars = self::get_query_vars( $parameters, $parameters );
									
		$query_vars['orderby'] = !empty($query_vars['orderby'])?$query_vars['orderby']:'date_insert';
		require_once( USAM_FILE_PATH .'/includes/exchange/showcases_query.class.php' );
		$query = new USAM_Showcases_Query( $query_vars );			
		$items = $query->get_results();	
		if ( !empty($items) )
		{
			foreach( $items as $key => &$item )
			{
						
			}
			$count = $query->get_total();		
			$results = ['count' => $count, 'items' => $items];
		}
		else
			$results = ['count' => 0, 'items' => []];			
		return $results;
	}

	public static function get_showcase( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		require_once( USAM_FILE_PATH .'/includes/exchange/showcase.class.php' );
		return usam_get_showcase( $id );
	}	
	
	public static function insert_showcase( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH .'/includes/exchange/showcase.class.php' );			
		$id = usam_insert_showcase( $parameters );				
		return $id;
	}

	public static function update_showcase( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH .'/includes/exchange/showcase.class.php' );
		$result = usam_update_showcase( $id, $parameters );			
		return true;
	}	
	
	public static function delete_showcase( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );
		require_once( USAM_FILE_PATH .'/includes/exchange/showcase.class.php' );
		return usam_delete_showcase( $id );
	}	
	
	public static function showcases_check_available_products( WP_REST_Request $request ) 
	{			
		$i = usam_get_total_products();								
		return usam_create_system_process( __("Пометить товары для выгрузки в витрины", "usam"), [], ['USAM_Showcase_Handler', 'check_available_products'], $i, 'check_available_products' );
	}	

	public static function showcases_update_prices_products( WP_REST_Request $request ) 
	{			
		$i = usam_get_total_products(['productmeta_query' => [['key' => 'code_showcase_', 'compare_key' => 'LIKE']]]);								
		return usam_create_system_process( __("Обновить цены в витринах", "usam"), [], ['USAM_Showcase_Handler', 'update_prices_products'], $i, 'update_prices_products' );
	}		
	
	public static function showcases_synchronization_products( WP_REST_Request $request ) 
	{			
		$i = usam_get_total_products();								
		return usam_create_system_process( __("Синхранизировать товары в витринах", "usam"), [], ['USAM_Showcase_Handler', 'synchronization_products'], $i, 'showcases_synchronization_products' );
	}	
	
	public static function showcase_delete_not_synchronization_products( WP_REST_Request $request ) 
	{			
		$id = $request->get_param( 'id' );
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		require_once( USAM_FILE_PATH .'/includes/exchange/showcase.class.php' );
		$showcase = usam_get_showcase( $id );	
		$api = new USAM_Showcase_API( $id );
		$results = $api->send_request( 'products', 'POST', ['count' => 2]);	
		if( !empty($results['count']) ) 
			return usam_create_system_process( sprintf(__("Удалить не синхранизированые товары в %s", "usam"), $showcase['name']), $id, ['USAM_Showcase_Handler', 'delete_not_synchronization_products'], $results['count'], 'showcase_delete_not_synchronization_products' );
		return false;
	}

	public static function showcase_remove_products_link( WP_REST_Request $request ) 
	{			
		$id = $request->get_param( 'id' );
		global $wpdb;
		return $wpdb->query("DELETE FROM `".USAM_TABLE_PRODUCT_META."` WHERE meta_key='code_showcase_$id'"); 
	}

	public static function get_product_day( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		$data = usam_get_data($id, 'usam_product_day_rules');			
		return $data;
	}

	private static function get_data_product_day( $data ) 
	{	
		if( isset($data['type_prices']) )
			$data['type_prices'] = $data['type_prices'];	
		else
		{
			$prices = usam_get_prices( );	
			foreach ( $prices as $type_price ) 
				$data['type_prices'][] = $type_price['code'];
		}					
		if( isset($data['conditions']['pricemin']) )
			$data['conditions']['pricemin'] = usam_string_to_float($data['conditions']['pricemin']);
		if( isset($data['conditions']['pricemax']) )
			$data['conditions']['pricemax'] = usam_string_to_float($data['conditions']['pricemax']);	
		if( isset($data['conditions']['minstock']) )		
			$data['conditions']['minstock'] = usam_string_to_float($data['conditions']['minstock']);
		if( isset($data['conditions']['value']) )
			$data['conditions']['value']    = absint($data['conditions']['value']);		
		if( isset($data['conditions']['c']) )
			$data['conditions']['c']        = absint($data['conditions']['c']);		
		$taxonomies = get_taxonomies(['object_type' => ['usam-product'], 'public' => true, 'show_in_rest' => true], 'objects');		
		foreach($taxonomies as $taxonomy)
			$data['conditions'][$taxonomy->name] = isset($data['conditions'][$taxonomy->name])?array_map('intval', $data['conditions'][$taxonomy->name]):[];	
		return $data;
	}
	
	
	private static function set_products_product_day( $id, $products, $products_day = [] )
	{		
		$add_products = [];
		$update_products = [];
		$processed = [];
		$result = false;
		foreach( $products as $new_product ) 
		{		
			$add = true;	
			$new_product['rule_id'] = $id;		
			foreach( $products_day as $k => $product ) 
			{
				if( isset($new_product['id']) && ctype_digit($new_product['id'])  )
				{
					usam_update_product_day( $new_product['id'], $new_product );			
					unset($products_day[$k]);
					$add = false;
					break;
				}				
			}
			if ( $add )			
				usam_insert_product_day( $new_product ); 
			
		}					
		foreach($products_day as $product)	
			usam_delete_product_day( $product->id );
			
		$pday = new USAM_Work_Product_Day();
		$pday->refill_the_queue_by_rule_id( $id );
		$pday->set_product_day_by_rule_id( $id );	
		
		return $result;
	}	
	
	public static function insert_product_day( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
				
		$parameters = USAM_Products_API::get_data_product_day( $parameters );
		$parameters['date_insert'] = date( "Y-m-d H:i:s" );											
		$id = usam_add_data( $parameters, 'usam_product_day_rules' );	
		if( !empty($parameters['products']) )
			USAM_Products_API::set_products_product_day( $id, $parameters['products'] );
		return $id;
	}

	public static function update_product_day( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
				
		$parameters = USAM_Products_API::get_data_product_day( $parameters );	
		
		usam_edit_data( $parameters, $id, 'usam_product_day_rules' );
		if( isset($parameters['products']) )
		{
			require_once(USAM_FILE_PATH.'/includes/product/products_day_query.class.php');
			$products_day = usam_get_products_day(['rule_id' => $id, 'status' => [0, 1]]);
			USAM_Products_API::set_products_product_day( $id, $parameters['products'], $products_day );
		}
		return true;
	}	
	
	public static function delete_product_day( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );
		global $wpdb;
		require_once(USAM_FILE_PATH.'/includes/product/products_day_query.class.php');
				
		usam_delete_data( [$id], 'usam_product_day_rules' );			
		
		$products_ids = usam_get_products_day(['rule_id' => $id, 'status' =>  array( 1 ), 'fields' => 'product_id']);		
		if ( !empty($products_ids) )
		{
			usam_recalculate_price_products_ids( $products_ids );					
			wp_cache_delete( 'usam_active_products_day' );	
		}
		$result = $wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCT_DAY." WHERE rule_id=$id" );				
		return true;
	}	
}
?>