<?php
require_once( USAM_FILE_PATH . '/includes/parser/spider.class.php' );
class USAM_Spider_Site_Supplier extends USAM_Spider
{			
	protected function insert_product( $data )
	{	
		global $wpdb;
		static $main_attr_id = null;		
		$type_import = usam_get_parsing_site_metadata( self::$id, 'type_import' );
		$product_id = false;
				
		if ( !empty($data['sku']) )
			$data['sku'] = preg_replace('/\s/', '', $data['sku']);	
		if( empty(self::$parsing_site['existence_check']) || self::$parsing_site['existence_check'] === 'url' )
		{
			if( self::$parsing_site['view_product'] != 'list' && !empty($data['url']) )
				$product_id = (int)$wpdb->get_var($wpdb->prepare("SELECT product_id FROM ".USAM_TABLE_PRODUCT_META." WHERE meta_key='webspy_link' AND meta_value='%s' LIMIT 1", $data['url']));
		}
		elseif( self::$parsing_site['existence_check'] === 'code' )
		{
			if( !empty($data['code']) )
				$product_id = (int)$wpdb->get_var($wpdb->prepare("SELECT product_id FROM ".USAM_TABLE_PRODUCT_META." WHERE meta_key='code' AND meta_value='%s' LIMIT 1", $data['code']));
			else
				return false;
		}
		elseif( self::$parsing_site['existence_check'] === 'sku' )
		{			
			if( !empty($data['sku']) )
				$product_id = (int)$wpdb->get_var($wpdb->prepare("SELECT product_id FROM ".USAM_TABLE_PRODUCT_META." WHERE meta_key='sku' AND meta_value='%s' LIMIT 1", $data['sku']));
			else
				return false;
		}
		if ( $product_id && self::$parsing_site['type_import'] === 'insert' )
			return $product_id;
		elseif ( !$product_id && self::$parsing_site['type_import'] !== 'insert' && self::$parsing_site['type_import'] !== '' )
			return false;			
		if ( self::$parsing_site['type_import'] == 'images' )
		{
			if( get_post_thumbnail_id( $product_id ) )
				return false;
		}	
		elseif( self::$parsing_site['type_import'] == 'description' )
		{			
			$post = get_post( $product_id );
			if( !empty($post->post_excerpt) )
				return false;
		}
		elseif( self::$parsing_site['type_import'] == 'url' )
		{			
			$url = usam_get_product_meta( $product_id, 'webspy_link' );
			if( !empty($url) && str_contains($url, self::$parsing_site['domain']) )
				return false;
		}			
		$attributes = [];
		if( !empty($data['attribute_name']) && !empty($data['attribute_value'])  )
		{				
			if ( $main_attr_id === null )
			{
				$main_group_attr = get_term_by('name', __('Основные','usam'), 'usam-product_attributes');	
				if ( empty($main_group_attr) )
				{
					$terms = get_terms(['parent' => 0, 'taxonomy' => 'usam-product_attributes', "hide_empty" => 0, 'number' => 1]);
					if ( !empty($terms) )
						$main_attr_id = (int)$terms[0]->term_id;
					else
					{
						$term = wp_insert_term( __('Основные','usam'), 'usam-product_attributes', ['parent' => 0]);
						if ( is_wp_error($term) || !isset($term['term_id']) )	
							$main_attr_id = 0;
						else
							$main_attr_id = (int)$term['term_id'];
					}
				}
				else
					$main_attr_id = (int)$main_group_attr->term_id;
			}
			if( $main_attr_id )
			{	
				$terms = get_terms(['name' => $data['attribute_name'], 'taxonomy' => 'usam-product_attributes', 'term_meta_cache' => true, "hide_empty" => 0]);
				foreach ($data['attribute_name'] as $key => $attribute_name)
				{
					$term_id = null;
					$slug = null;
					foreach ($terms as $term) 
					{
						if ( $term->name === $attribute_name )
						{
							$term_id = $term->term_id;
							$slug = $term->slug;
							break;
						}
					}
					if ( !$term_id )
					{		
						$slug = str_replace('-', '_', sanitize_title( $attribute_name ));
						$term = wp_insert_term($attribute_name, 'usam-product_attributes', ['parent' => $main_attr_id, 'slug' => $slug]);
						if ( is_wp_error($term) )
							continue;
						else
						{
							$term_id = $term['term_id'];
							usam_update_term_metadata($term['term_id'], 'field_type', 'T' );
						}
					}				
					if ( isset($data['attribute_value'][$key]) )
					{
						if ( is_array($data['attribute_value'][$key]) )
							foreach( $data['attribute_value'][$key] as $k => $v )
								$data['attribute_value'][$key][$k] = $this->process_data( $v );
						else
							$data['attribute_value'][$key] = $this->process_data( $data['attribute_value'][$key] );
												
						if( is_array($data['attribute_value'][$key]) && count($data['attribute_value'][$key]) > 1 )
						{
							usam_update_term_metadata($term_id, 'field_type', 'M' );
							$array = true;
						}
						elseif ( usam_attribute_stores_values( $term_id ) ) 						
							$array = true;
						else
							$array = false;							
						if ( $array ) 
						{	
							$attributes[$slug] = [];	
							if( !is_array($data['attribute_value'][$key]) )							
								$data['attribute_value'][$key] = (array)$data['attribute_value'][$key];			
							foreach( $data['attribute_value'][$key] as $attribute ) 
							{								
								$attribute_variant_id = usam_insert_product_attribute_variant(['code' => '', 'value' => stripslashes($attribute), 'attribute_id' => $term_id]);
								$attributes[$slug][] = $attribute_variant_id;
							}		
						}
						else
							$attributes[$slug] = is_array($data['attribute_value'][$key]) ? implode(', ',$data['attribute_value'][$key]) : $data['attribute_value'][$key];
					}
				}
			}
		}			
		$product_id = apply_filters_ref_array( 'usam_webspy_product_id', [ $product_id, & $attributes, & $data ] );			
		if ( !$product_id && empty($data['title']) )
			return false;
				
		$product = [ 'productmeta' => [] ];
		if ( !empty($data['title']) )
			$product['post_title'] = $this->process_data( $data['title'] );
		if ( !empty($data['content']) )
			$product['post_content'] = $data['content'];
		if ( !empty($data['excerpt']) )
			$product['post_excerpt'] = $this->process_data( $data['excerpt'] );
		if ( !empty($data['thumbnail']) )
			$product['thumbnail'] = $data['thumbnail'];	
		if( !empty($data['images']) )
			$product['media_url'] = array_unique($data['images']);
		if ( !empty($data['weight']) )
			$product['productmeta']['weight'] = $data['weight'];
		if ( !empty($data['sku']) )
			$product['productmeta']['sku'] = $data['sku'];					
		$contractor = usam_get_parsing_site_metadata( self::$id, 'contractor' );
		if ( $contractor )
			$product['productmeta']['contractor'] = $contractor;			
		if ( !empty($data['price']) )
			$product['prices']['price_'.self::$parsing_site['type_price']] = usam_string_to_float($data['price']);		
		if ( !empty($data['brand']) )
		{				
			$brand = $this->set_term( $data['brand'], 'usam-brands', ['parent' => 0]);
			if ( $brand )
				$product['tax_input']['usam-brands'] = [ $brand ];
		}
		if ( !empty($this->link_processing['category']) )
			$product['tax_input']['usam-category'] = array( $this->link_processing['category'] );					
		elseif ( !empty($data['category']) )
		{
			$parent = 0;				
			foreach( $data['category'] as $key => $category ) 
			{
				$_category = mb_strtolower($category);					
				if( empty($_category) || $_category == 'главная' || $_category == 'интернет-магазин' || $_category == 'home' || $category == $data['title'] )
					unset($data['category'][$key]);
				else				
					$parent = $this->set_term( $category, 'usam-category', ['parent' => $parent]);
			}				
			$product['tax_input']['usam-category'] = [ $parent ];	
		}		
		if( !empty(self::$parsing_site['store']) )
			$product['product_stock']['storage_'.self::$parsing_site['store']] = USAM_UNLIMITED_STOCK;			
		$product = apply_filters( 'usam_webspy_insert_product', $product, $product_id );		
		$product['productmeta']['webspy_link'] = $data['url'];		
		$product['productmeta']['webspy_rule_'.self::$id] = date("Y-m-d H:i:s");		
		if ( $product_id )
		{
			if ( self::$parsing_site['type_import'] !== 'insert' )
			{
				$date_update = usam_get_product_meta( $product_id, 'webspy_rule_'.self::$id );
				if( $date_update && $date_update > self::$parsing_site['start_date'] )
					return $product_id;			
									
				$_product = new USAM_Product( $product_id );
				$_product->set( $product );				
				$_product->update_product( $attributes );
				$this->products_update++;	
				usam_update_parsing_site_metadata( self::$id, 'products_update', $this->products_update );
			}
			else
				return $product_id; 
		}
		elseif ( !self::$parsing_site['type_import'] || self::$parsing_site['type_import'] == 'insert' )
		{			
			$post_status = usam_get_parsing_site_metadata( self::$id, 'post_status' );	
			$product['post_status'] = $post_status?$post_status : 'draft';			
			if ( !empty($data['url']) )
			{
				$url_args = explode('?', $data['url']);		
				$post_name = explode('/', trim($url_args[0], '/'));	
				$product['post_name'] = array_pop( $post_name );
			}
			$_product = new USAM_Product( $product );							
			$product_id = $_product->insert_product( $attributes );
			$this->products_added++;
			usam_update_parsing_site_metadata( self::$id, 'products_added', $this->products_added );
		}			
		if ( $product_id )
		{					
			if ( isset($data['similar']) && is_array($data['similar']) )
			{			
				$product_ids = usam_get_product_ids_by_code( $data['similar'], 'similar' );
				usam_add_associated_products( $product_id, $product_ids, 'similar' );
			}
			if ( !empty($product['thumbnail']) || !empty($product['media_url']) )
				$_product->insert_media();				
			if ( !empty($data['variations']) )
			{
				$combination = [];
				$parent = (int)usam_get_parsing_site_metadata( self::$id, 'parent_variation' );
				if ( !$parent && !empty($data['variations_name']) )
					$parent = $this->set_term( $data['variations_name'], 'usam-variation', ['parent' => 0]);	
				$combination[] = $parent;					
				$childs = usam_get_products(['post_parent' => $product_id, 'post_status' => 'all', 'numberposts' => -1, 'cache_product' => false]);
				$product_children = [];				
				foreach ($data['variations'] as $value ) 
				{	
					$child_product = $product;
					$child_product['post_parent'] = $product_id;	
					$child_product['post_status'] = 'publish';						
					
					$variation_ids = [];						
					$variation_id = $this->set_term( $value['name'], 'usam-variation', ['parent' => $parent]);
					if ( $variation_id )
						$variation_ids[] = $variation_id;
					else
						continue;
											
					if ( !empty($value['sku']) )
						$child_product['productmeta']['sku'] = $value['sku'];	
					if ( !empty($value['price']) )
						$child_product['prices']['price_'.self::$parsing_site['type_price']] = usam_string_to_float($value['price']);
					$child_product['post_title'] .= " (".$value['name'].")";
						
					$variation_product_id = usam_get_id_product_variation( $product_id, $variation_ids );	
					if( $variation_product_id == false )
					{						
						$_product = new USAM_Product( $child_product );							
						$variation_product_id = $_product->insert_product();	
					}
					else
					{
						$_product = new USAM_Product( $variation_product_id );	
						$_product->set( $child_product );				
						$_product->update_product( );
					}					
					if( $variation_product_id )
					{
						$combination = array_merge( $variation_ids, $combination );	
						$product_children[] = $variation_product_id;
						wp_set_object_terms( $variation_product_id, $variation_ids, 'usam-variation');	
						wp_set_object_terms( $variation_product_id, 'variation', 'usam-product_type');							
					}					
				}	
				if( !empty($childs) )
				{
					$childs_ids = wp_list_pluck( $childs, 'ID' );
					$old_ids_to_delete = array_diff($childs_ids, $product_children);
					if( !empty($old_ids_to_delete) ) 
					{	
						foreach($old_ids_to_delete as $object_ids)
							wp_delete_post($object_ids);
					}
				} 
				if ( $combination )
				{
					$combination = array_map('intval', $combination );
					wp_set_object_terms( $product_id, $combination, 'usam-variation');
					wp_set_object_terms( $product_id, 'variable', 'usam-product_type');
				}
			}
			do_action('usam_after_import_product', $product_id, $product, $attributes );	
			usam_clean_product_cache( $product_id );
		}				
		return $product_id;
	}
	
	private function process_data( $string )
	{
		$translate = usam_get_parsing_site_metadata( self::$id, 'translate' );	
		if ( $translate )
			$string = apply_filters( 'usam_translate', $string, $translate ); 
		return $string;
	}
	
	
	public function set_term( $value, $taxonomy, $args = [] )
	{		
		$term_id = 0;
		$term = get_term_by( 'name', $value, $taxonomy );				
		if ( !empty($term) )
		{
			if ( $args )
				wp_update_term($term->term_id, $taxonomy, $args);
			$term_id = (int)$term->term_id;
		}
		else
		{
			$term = wp_insert_term( $value, $taxonomy, $args );
			if ( !is_wp_error($term) ) 	
				$term_id = (int)$term['term_id'];
		}	
		return $term_id;
	}	
	
	public function get_js_content( $url )
	{		
	//	$url = 'http://voskovok.net/gems/input';
					
		$key = 'products';
		$params = array( 'GemsModel' => array( 'page' => $page ) );		
		$webcontent = self::send_request( $url, $params );		
		if ( !empty($webcontent[$key]) )		
		{								
			$html = usam_str_get_html( $webcontent[$key] );			
			if ( !empty($html) )		
			{
				$data = self::get_site_data( $html );		
				if ( !empty($data) )
				{						
					foreach ($data as $value)
					{
						self::insert_product( $value );	
					}	
					sleep(1);
					$page++;
					self::get_js_content( $url );					
				}		
			}			
		} 	
		return false;		
	}	
	/*	if ( false )
		{
			self::$page = 1;
			return self::get_js_content( $url );
		}
		else */
}
?>