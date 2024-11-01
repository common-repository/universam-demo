<?php
// Сохранение карточки товара
class USAM_Product
{	
	private $product_id = 0;
	private $product_new = false;   // это новый товар
	private $data = [];
	private $changed_data = [];
	private $calculation_meta_key = [];
	private $product_type = 'simple';	
	private $number_downloaded_urls = 0;
	private static $type_prices = [];
	
	public function __construct( $product_id = 0 ) 
	{					
		self::$type_prices = usam_get_prices(['type' => 'all', 'orderby' => 'type']);			
		if ( is_array($product_id) )
		{
			$this->set( $product_id ); 
		}
		else
		{
			$this->product_id = (int)$product_id;				
			if ( $this->product_id != 0 )	
			{
				$product_type_terms = get_the_terms( $this->product_id, 'usam-product_type' );		
				if ( !empty($product_type_terms) ) 
					$this->product_type = $product_type_terms[0]->slug;			
			}				
		}
	}	

	public function set( $properties ) 
	{		
		$properties = apply_filters( 'usam_product_set_properties', $properties, $this );			
		if ( $properties !== null )
		{
			$properties = (array)$properties;				
			$properties = $this->sanitise_product_forms( $properties );	
		}				
		if ( !empty($properties['product_type']) )	
		{		
			$this->product_type = $properties['product_type'];	
			unset($properties['product_type']);
		} 		
		if ( $this->product_id == 0 )
		{	//Новый товар
			$default_data = self::get_default_product( $this->product_id );
			$this->data = $this->merge_meta_deep( $default_data, $properties, $default_data );			
			$this->product_new = true;			
		}
		else			
		{	//Изменение существующего товара						
			if ( $properties !== null )
			{			
				$properties = (array)$properties;											
				if ( isset($properties['meta']) )
				{								
					$default_data = self::get_default_product( $this->product_id );
					$new_product_data = array();	
					foreach( $properties['meta'] as $key => $meta )
					{ 
						if ( isset($default_data['meta'][$key]) )
							$new_product_data[$key] = $this->merge_meta_deep( $default_data['meta'][$key], $meta, $default_data['meta'][$key] );
						else // Например атрибуты товаров
							$new_product_data[$key] = $meta;							
					}	
					$properties['meta'] = $new_product_data;
					$this->data = $this->sanitise_product_forms( $properties );						
				}
				else
					$this->data = $properties;				
			}		
		}			
	}
	
	private function get_product_metas( )
	{
		$meta = get_post_meta( $this->product_id );
		$product_metas = array();
		if ( !empty($meta))
		{			
			$merge_meta_deep = true;
			foreach ( $meta as $meta_key => $meta_value )
			{
				$key = str_replace( USAM_META_PREFIX, "", $meta_key );			
				if ( count($meta_value) == 1 )
					$product_metas[$key] = maybe_unserialize($meta_value[0]);
				else
				{
					foreach ( $meta_value as $value )
					{
						$value = maybe_unserialize($value);
						if ( is_array($value) )
							$product_metas[$key] = maybe_unserialize($value);	
						else
							$product_metas[$key][] = maybe_unserialize($value);	
					}
				}
			}						
		}			
		return $product_metas;
	}
		
	public function set_terms( )
	{ 
		$result = false;
		if ( !empty($this->data['tax_input']) )
		{ 
			global $wpdb;
			foreach( $this->data['tax_input'] as $taxonomy => $new_terms )
			{				
				if ( $this->product_new == false )
				{
					$current_ids = array();
					$product_terms = get_the_terms( $this->product_id, $taxonomy);
					if ( !empty($product_terms) )
					{
						foreach( get_the_terms( $this->product_id, $taxonomy) as $term )
						{
							$current_ids[] = $term->term_id;
						}
						sort($current_ids);
						sort($new_terms);
					}	
					if ($current_ids != $new_terms)
					{
						wp_set_object_terms( $this->product_id, $new_terms, $taxonomy );
						$result = true;
					}
				}
				else
				{
					$new_tt_ids = array();
					foreach ( $new_terms as $term )
					{
						$term_info = term_exists( $term, $taxonomy );
						if ( !$term_info ) 
							continue;	
						if ( $wpdb->insert($wpdb->term_relationships, ['object_id' => $this->product_id, 'term_taxonomy_id' => $term_info['term_taxonomy_id']]) )
							$result = true;
						$new_tt_ids[] = $term_info['term_taxonomy_id'];
					}
					if ( $new_tt_ids ) 
						wp_update_term_count( $new_tt_ids, $taxonomy );
					wp_cache_delete( $this->product_id, $taxonomy . '_relationships' );
					wp_cache_delete( 'last_changed', 'terms' );
				}
			}
		}
		return $result;
	}
			
	// Рассчитать и сохранить все свойства товара
	public function save_product_meta( )
	{		
		$result = false;
		if ( $this->product_id != 0 )
		{		
			if ( !usam_is_multisite() || is_main_site() )
			{
				if ( !empty($this->data['product_stock']) )		
					$result = $this->save_stocks();	    // Рассчитать остаток	
				if ( !empty($this->data['prices']) )
					$result = $this->save_prices();			
				if ( !empty($this->data['productmeta']) )
				{       				
					if ( $this->product_type == 'variation' ) 
					{
						if( isset($this->data['productmeta']['virtual']) )
							unset($this->data['productmeta']['virtual']);
					}		
				}						
			}
			$result = $this->update_product_metas( );
			//Комплектация
			if ( !empty($this->data['components']) )
			{							
				$ids = array();
				$components = usam_get_product_components( $this->product_id );
				foreach((array)$this->data['components'] as $key => $new_component)
				{								
					if ( trim($new_component['component']) == '' )
						unset($this->data['components'][$key]);	
					elseif ( !empty($new_component['id']) )
					{
						$ids[] = $new_component['id'];
						usam_update_product_component( $new_component['id'], $new_component );
					}
					else
					{
						$new_component['product_id'] = $this->product_id;
						usam_add_product_component( $new_component );	
					}
				}					
				foreach($components as $component)
				{
					if ( !in_array($component->id, $ids) )
						usam_delete_product_component($component->id);
				}
				$result = true;
			}				
			if ( $this->product_type != 'variation' ) 
			{
				if ( isset($this->data['variations']) )
				{					 
					usam_edit_product_variations( $this->product_id, $this->data );
					$this->product_type = 'variable';
					$result = true;
				}
			}
			else
			{
				if ( apply_filters( 'update_price_primary_product_variations', true ) )
				{	// Найти главный товар и обновить его цену
					if ( !empty($this->data['post_parent']) )
						$product_id = $this->data['post_parent'];
					else
					{
						$product = get_post( $this->product_id );
						$product_id = $product->post_parent;
					}	
					if ( !empty($product_id) )
					{				
						$_product = new USAM_Product( $product_id );
						$_product->save_prices( );	
						$_product->save_stocks( );	
						$result = true;
					}					
				}				
			}
			do_action('usam_edit_product_meta', $this->product_id, $this->data );
		}
		return $result;
	}
		
	// Обновление меты товара	 
	public function update_product_metas( )
	{			
		$result = false;
		if ( $this->product_id )
		{				
			if ( !empty($this->data['meta']) )	
			{
				$metas = $this->get_product_metas();		
				$metas_update = array();			
				foreach( $this->data['meta'] as $key => $value )			
				{
					if ( isset($metas[$key]) )
					{
						if ( is_array($value) )
						{
							$meta = $this->get_differences( $metas[$key], $value );						
							if ( !empty($meta) )
								$metas_update[$key] = $value;
						}
						elseif ( $metas[$key] != $value )
							$metas_update[$key] = $value;
					}
					else
						$metas_update[$key] = $value;
				}				
				if ( !empty($metas_update) )
				{
					foreach( $metas_update as $meta_key => $meta_value)
					{						
						if ( $this->product_new == false )
							update_post_meta($this->product_id, USAM_META_PREFIX.$meta_key, $meta_value);
						else
							add_post_meta($this->product_id, USAM_META_PREFIX.$meta_key, $meta_value);
					} 		
					$result = true;					
				}				
			}			
			if ( !empty($this->data['postmeta']) )
			{				
				foreach( $this->data['postmeta'] as $meta_key => $meta_value)
				{					
					if ( $this->product_new == false )
						usam_update_post_meta($this->product_id, $meta_key, $meta_value);	
					else
						usam_add_post_meta($this->product_id, $meta_key, $meta_value);	
				} 
				$result = true;
			}		
			if ( !empty($this->data['productmeta']) )
			{
				foreach( $this->data['productmeta'] as $meta_key => $meta_value)
				{		
					if ( $this->product_new == false )
						usam_update_product_meta($this->product_id, $meta_key, $meta_value);
					else
						usam_add_product_meta($this->product_id, $meta_key, $meta_value);
				} 
				$result = true;
			}		
		}
		return $result;
	}
	
	/*
	 * Создание товара из переданных данных. Используется при импорте товара.
	 */
	public function insert_product( $attributes = array() )
	{
		$post_title = !empty($this->data['post_title'])?$this->data['post_title']:'';		
		$product_post_values = array(
			'post_author'    => !empty($this->data['user_id'])?$this->data['user_id']:get_current_user_id(),
			'post_content'   => !empty($this->data['post_content'])?$this->data['post_content']:'',
			'post_excerpt'   => !empty($this->data['post_excerpt'])?$this->data['post_excerpt']:'',
			'post_title'     => $post_title,
			'post_status'    => !empty($this->data['post_status'])?$this->data['post_status']:'draft',
			'post_parent'    => !empty($this->data['post_parent'])?$this->data['post_parent']:0,
			'post_type'      => "usam-product",
			'post_name'      => !empty($this->data['post_name'])?$this->data['post_name']:sanitize_title($post_title),
		); 
		remove_all_filters( 'save_post' );
		$product_id = wp_insert_post( $product_post_values );			
		if ( $product_id == 0 )
		{ 
			global $wpdb;
			return new WP_Error('db_insert_error', __('Не удалось вставить товар в базу данных', 'usam'), $wpdb->last_error);
		}	
		$this->product_id = $product_id;			
		$this->data = array_merge( $this->data, $product_post_values );			
		if ( isset($this->data["sticky"]) )
		{
			require_once(USAM_FILE_PATH.'/includes/customer/user_post.class.php');
			usam_insert_user_post(['user_list' => 'sticky', 'product_id' => $this->product_id]);
		}
		if ( !isset($this->data["tax_input"]) )
			$this->data["tax_input"] = array();
		
		$this->data['tax_input']['usam-product_type'] = array( $this->product_type );	
		if ( $this->data["post_parent"] && $this->product_type == 'variation' )
		{			
			$ids = wp_get_object_terms( $this->data["post_parent"], 'usam-category', ['fields' => 'ids']);
			$this->data['tax_input']['usam-category'] = $ids;				
		}	
		if ( empty($this->data['productmeta']) || empty($this->data['productmeta']['virtual']) )
			$this->data['productmeta']['virtual'] = 'product';
		
		$this->set_terms();			
		$this->save_product_meta();	
		if ( !empty($attributes) )
			$this->calculate_product_attributes( $attributes );	
		if ( !empty($this->data['productmeta']['sku']) )
			wp_cache_set($this->data['productmeta']['sku'], $product_id, "usam_product_sku");
		if ( !empty($this->data['productmeta']['code']) )
			wp_cache_set($this->data['productmeta']['code'], $product_id, "usam_product_code");
		
		do_action('usam_insert_product', $this->product_id, $this->data, $attributes );
		$this->product_new = false;
		return $product_id;
	}	
	
	public function update_product( $attributes = [] )
	{			
		$result = false;
		$update = $this->data;						
		$post = get_post($this->product_id);
		$save = false;
		if ( empty($post) )
			return $result;
		foreach( $update as $key => $value)
		{
			if( isset($post->$key) && $post->$key != $value )
				$save = true;
			else
				unset($update[$key]);
		}  		
		if ( $save )
		{						
			$update['ID'] = (int)$this->product_id;		
			$update['post_type'] = 'usam-product';
			$product_id = wp_update_post( $update ); 		
			if ( $product_id == 0 )				
			{
				global $wpdb;
				return new WP_Error('db_insert_error', __('Не удалось обновить товар', 'usam'), $wpdb->last_error);
			}
			else
				$result = true;
		}
		if ( isset($this->data["sticky"]) )	
			$this->sticky();
		if ( $this->set_terms() )
			$result = true;					
		if ( $this->save_product_meta() )
			$result = true;	
		if ( !empty($attributes) )
		{
			$this->calculate_product_attributes( $attributes, true );	
			$result = true;			
		}
		do_action('usam_update_product', $this->product_id, $this->data, $attributes );	
		return $result;
	}
	
	public function sticky( )
	{		
		require_once(USAM_FILE_PATH.'/includes/customer/user_post.class.php');
		require_once(USAM_FILE_PATH.'/includes/customer/user_posts_query.class.php');
		$user_products = usam_get_user_posts(['user_list' => 'sticky', 'product_id' => $this->product_id]);
		if ( $this->data["sticky"] )
		{
			if ( empty($user_products) )
				usam_insert_user_post(['user_list' => 'sticky', 'product_id' => $this->product_id]);
		}
		else
			foreach($user_products as $user_product)
				usam_delete_user_post( $user_product->id );
	}
		
	private function sanitize_attribute( $attribute_id, $attribute_value )
	{	
		if ( !is_array($attribute_value) )
			$attribute_value = trim($attribute_value);
		else
		{
			foreach( $attribute_value as $key => $value )
			{
				if ( $value === '' )
					unset($attribute_value[$key]);
				else
					$attribute_value[$key] = trim($value);
			}	
		}
		$field_type = usam_get_term_metadata($attribute_id, 'field_type');
		$results = [];
		switch ( $field_type ) 
		{
//Флажок	
			case 'C' ://Один	
				$results[] = $attribute_value;
			break;
			case 'COLOR_SEVERAL' :				
			case 'M' ://Несколько 		
				$results = $attribute_value;
			break;
//Выбор из варианов
			case 'N' ://Число     					
				$results[] = is_array($attribute_value) ? $attribute_value[0] : $attribute_value;
			break;					
			case 'S' ://Текст			
				$results[] = is_array($attribute_value) ? $attribute_value[0] : $attribute_value;
			break;
//Другое
			case 'D' ://Дата					
				$results[] = date_i18n("d-m-Y",  strtotime($attribute_value) );
			break;
			case 'F' ://Файл     					
				$results[] = $attribute_value;
			break;		
			case 'LDOWNLOAD' :				
			case 'LDOWNLOADBLANK' : 	
			case 'LBLANK' :				
			case 'L' : //Ссылка    
				$results[] = esc_url_raw( urldecode($attribute_value) );
			break;
//Базовый тип
			case 'O' ://Число					
				$results[] = is_array($attribute_value) ? usam_string_to_float($attribute_value[0]) : usam_string_to_float($attribute_value);
			break;
			case 'T' ://Текст
				$results[] = is_array($attribute_value) ? stripslashes($attribute_value[0]) : stripslashes($attribute_value);
			break;
			case 'DESCRIPTION' ://Текст				
				$results[] = is_array($attribute_value) ? stripslashes($attribute_value[0]) : stripslashes($attribute_value);
			break;
			case 'YOUTUBE' :
				if ( strripos($attribute_value, 'youtube') !== false ) 
				{    
					preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $attribute_value, $match);
					$results[] = !empty($match[1])?$match[1]:'';
				}
				else
					$results[] = $attribute_value;
			break;
			case 'TIME' :										
			default:
				$results[] = $attribute_value;
			break;
		}
		return $results;
	}
	
/* Расчет свойств товара */
	public function calculate_product_attributes( $new_product_attributes, $append = false )
	{  
		if ( !$this->product_id || empty($new_product_attributes) )
			return false;	
							
		$terms = array();				
		foreach( usam_get_attributes( $this->product_id ) as $term )
			$terms[$term->slug] = $term->term_id;	
		$attributes_bd = [];
		foreach((array)usam_get_product_attribute( $this->product_id ) as $attribute)
		{
			$attributes_bd[$attribute->meta_key][] = $attribute->meta_value;
		}			
		if ( isset($terms['brand']) )
			$new_product_attributes['contractor'] = usam_get_product_property($this->product_id, 'brand');
		if ( isset($terms['contractor']) )
			$new_product_attributes['contractor'] = usam_get_product_property($this->product_id, 'contractor'); 
		$product_attributes = []; 			
		foreach( $new_product_attributes as $slug => $attribute_value)
		{				
			if ( !isset($terms[$slug]) )
				continue;				
			if ( is_string($attribute_value) && $attribute_value === '' || $attribute_value === false )
				continue;
			
			$attribute_value = $this->sanitize_attribute( $terms[$slug], $attribute_value );			
			$field_type = usam_get_term_metadata($terms[$slug], 'field_type');							
			foreach( $attribute_value as $attribute)				
			{ 							
				if ( !isset($attributes_bd[$slug]) || !in_array($attribute, $attributes_bd[$slug]) )
				{ 			
					if ( !is_numeric($attribute) && usam_attribute_stores_values( $field_type ) )
						$attribute = usam_insert_product_attribute_variant(['value' => $attribute, 'attribute_id' => $terms[$slug]]);
					if ( $field_type == 'M' || $field_type == 'COLOR_SEVERAL' )
						usam_add_product_attribute($this->product_id, $slug, $attribute, false );
					else	
						usam_update_product_attribute($this->product_id, $slug, $attribute );	
				}	
				else
				{			
					$k = array_search($attribute, $attributes_bd[$slug]);	
					if ( $k !== false )
						unset($attributes_bd[$slug][$k]);	
				}
				$product_attributes[$terms[$slug]][] = $attribute;
			}			
		}  		
		if ( $this->product_new == false )
		{  // Удалим не нужные свойства товаров			
			if ( $append )
			{
				foreach( $attributes_bd as $slug => $attributes)
				{						
					if ( isset($terms[$slug]) )
					{
						$field_type = usam_get_term_metadata($terms[$slug], 'field_type');
						if ( $field_type == 'M' || $field_type == 'COLOR_SEVERAL' )
						{
							foreach( $attributes as $attribute)		
								usam_delete_product_attribute($this->product_id, $slug, $attribute );
						}
					}
				}
			}
			else
			{
				foreach( $attributes_bd as $slug => $attributes)
				{						
					foreach( $attributes as $attribute)		
					{   							
						usam_delete_product_attribute($this->product_id, $slug, $attribute );	
					}
				}
			}
		} 
		if ( $append )
		{	 
			foreach ( $terms as $slug => $term_id)
			{
				if ( !isset($product_attributes[$term_id]) )
				{
					$metas = usam_get_product_attribute($this->product_id, $slug, false);
					$results = [];
					if ( is_array($metas) )
						foreach( $metas as $meta )
							$product_attributes[$term_id][] = $meta->meta_value;	 
				}
			}	
		}			
		$this->calculate_product_searches( $product_attributes );
		$this->calculate_product_filters( $product_attributes );
	}
	
	public function calculate_product_searches( $product_attributes )
	{						
		global $wpdb;	
		if ( empty($product_attributes) )
			return true;		
		
		$search_keys = [];
		$attribute_ids = array_keys($product_attributes);
		$attribute_values = $this->get_product_attribute_values( $attribute_ids );
		$values = [];
		foreach( $attribute_values as $attr_values )
		{
			foreach( $attr_values as $value )
			{
				$values[$value->id] = $value->value;
			}			
		}	
		foreach( $product_attributes as $attribute_id => $attributes )
		{			
			$search = usam_get_term_metadata($attribute_id, 'search');		
			if ( $search )
			{
				$search_metadata = usam_get_post_search_metadata( $this->product_id, 'attribute_'.$attribute_id, false );					
				$field_type = usam_get_term_metadata($attribute_id, 'field_type');	
				foreach( $attributes as $attribute)				
				{ 		
					$search_values = [];					
					if( is_numeric($attribute) && isset($values[$attribute]) )
						$search_values[] = $values[$attribute];
					elseif( !is_array($attribute) )
						$search_values = explode(',',$attribute);	
					else
						$search_values = $attribute;		
					foreach( $search_values as $search_value)
					{						
						$search_value = trim($search_value);		
						if ( empty($search_metadata) )
						{
							$meta_id = usam_add_post_search_metadata($this->product_id, 'attribute_'.$attribute_id, $search_value );	
							if ( $meta_id )
								$search_keys[] = $meta_id;
						}
						else
						{
							$there_is_record = false;
							foreach( $search_metadata as $meta)
							{
								if ( $meta->meta_value == $search_value )
								{
									$there_is_record = $meta->meta_id;
									$search_keys[] = $meta->meta_id;
									break;									
								}
							}						
	
							if ( $there_is_record === false )
							{
								$meta_id = usam_add_post_search_metadata($this->product_id, 'attribute_'.$attribute_id, $search_value );
								if ( $meta_id )
									$search_keys[] = $meta_id;
							}
						}
					}		
				}	
			}
		} 	
		if ( !empty($search_keys) )
		{
			$wpdb->query("DELETE FROM ".usam_get_table_db('posts_search')." WHERE post_search_id=$this->product_id AND meta_key LIKE 'attribute%' AND meta_id NOT IN (".implode(',',$search_keys).")");	
		}
	}	
	
	public function calculate_product_filters( $product_attributes )
	{	
		global $wpdb;			
		if ( $this->product_type == 'variation' ) 
			return true;
		if ( empty($product_attributes) )
			return true;			
		$filter_delete = [];
		$attribute_ids = array_keys($product_attributes);
		if ( $this->product_new == false )
			$filter_delete = $filters_bd = usam_get_product_filters( $this->product_id );
		else
			$filters_bd = [];					
		$filter_values = $this->get_product_attribute_values( $attribute_ids );
		$filters_new = [];		
		foreach( $product_attributes as $attribute_id => $attributes )
		{		
			$term_filter = usam_get_term_metadata($attribute_id, 'filter');				
			foreach( $attributes as $attribute)				
			{ 									
				if ( !empty($term_filter) )			
				{												
					if ( usam_attribute_stores_values( $attribute_id ) )
					{								
						$attribute = is_array($attribute) ? $attribute : array($attribute);							
						foreach( $attribute as $filter_id)	
						{
							if( $filter_id && is_numeric($filter_id) )
							{							
								if ( !in_array($filter_id, $filters_bd) )
									$wpdb->insert( usam_get_table_db('product_filters'), ['filter_id' => $filter_id, 'product_id' => $this->product_id], ['%d', '%d']);	
								$k = array_search($filter_id, $filter_delete);
								if ( $k !== false )
									unset($filter_delete[$k]);
							}
						}
					}	
					elseif ( $attribute !== '' )		
					{				
						$filter_id = 0;
						$add = true;
						if ( isset($filter_values[$attribute_id]) )
						{
							foreach ($filter_values[$attribute_id] as $filter)	
							{	
								if ( stripcslashes($filter->value) == $attribute )
								{
									$k = array_search($filter->id,$filter_delete);
									if ( $k !== false )
										unset($filter_delete[$k]);
									$add = false;
									if ( !in_array($filter->id, $filters_bd) )
									{										
										$filter_id = $filter->id;
									}
									break;
								}
							}
						}
						if ( $add )
						{
							$wpdb->insert( usam_get_table_db('product_attribute_options'), ['attribute_id' => $attribute_id, 'value' => $attribute], ['%d', '%s']);
							$filter_id = $wpdb->insert_id;
						}
						if ( $filter_id )
							$wpdb->insert( usam_get_table_db('product_filters'), ['filter_id' => $filter_id, 'product_id' => $this->product_id], ['%d', '%d']);
					}					
				}				
			}			
		}
		if ( $this->product_new == false && !empty($filter_delete) )
		{	// Удалим не нужные фильтры товаров	
			$wpdb->query("DELETE FROM ".usam_get_table_db('product_filters')." WHERE product_id=$this->product_id AND filter_id IN (".implode(',',$filter_delete).")");			
			$results = $wpdb->get_col("SELECT filter_id FROM ".usam_get_table_db('product_filters')." WHERE product_id!=$this->product_id AND filter_id IN (".implode(',',$filter_delete).")");				
			$filter_delete = array_diff($filter_delete, $results);
			if ( !empty($filter_delete) )
			{
				$values = usam_get_product_attribute_values(['include' => $filter_delete, 'fields' => ['id', 'attribute_id']]);	
				foreach ($values as $filter)	
				{
					if ( usam_attribute_stores_values($filter->attribute_id) )
					{
						$k = array_search($filter->id, $filter_delete);
						if ( $k !== false )
							unset($filter_delete[$k]);
					}
				}
				if ( !empty($filter_delete) )
					$wpdb->query("DELETE FROM ".usam_get_table_db('product_attribute_options')." WHERE id IN (".implode(',',$filter_delete).")");	
			}
		}
	}
	
	private function get_product_attribute_values( $attribute_ids )
	{
		$filter_values = [];
		foreach( usam_get_product_attribute_values(['attribute_id' => $attribute_ids]) as $filter )
		{					
			$filter_values[$filter->attribute_id][] = $filter;	
		}
		return $filter_values;
	}
	
/* Расчет остатка */	
	public function save_stocks( )
	{			
		$result = false;
		if ( $this->product_type == 'variable' )
			$result = $this->calculation_of_inventory_product_variations( );	
		else			
			$result = $this->calculation_of_inventory_product();

		if ( !empty($this->data['product_stock']) )
		{
			$stock = usam_get_product_stock($this->product_id, 'stock');
			if ( $stock === 0.00 && $this->data['product_stock']['stock'] > 0.00 )
				do_action( 'usam_product_arrived', $this->product_id, $this->data['product_stock']['stock'] );
			
			$result = true;			
			foreach( $this->data['product_stock'] as $meta_key => $meta_value)
			{						
				if ( $this->product_new == false )
					usam_update_product_stock($this->product_id, $meta_key, $meta_value);
				elseif ( $meta_value != 0.00 )
					usam_add_product_stock($this->product_id, $meta_key, $meta_value);	
			}
			$max_stock = usam_get_product_stock($this->product_id, 'max_stock');	
			if( $max_stock < $this->data['product_stock']['stock'] && $this->data['product_stock']['stock'] > 0 )		
				usam_update_product_stock($this->product_id, 'max_stock', $this->data['product_stock']['stock']);
		} 	
		return $result;
	}
	
	public function calculation_of_inventory_product_variations( )
	{
		global $wpdb;			
		$metas = $wpdb->get_results("SELECT pm.meta_value, pm.meta_key FROM {$wpdb->posts} AS p INNER JOIN ".USAM_TABLE_STOCK_BALANCES." AS pm ON pm.product_id=p.ID WHERE p.post_type='usam-product' AND p.post_parent={$this->product_id}");		
		$all_stock = 0;
		$total_balance = 0;
		$sales_area = usam_get_sales_areas();	
		$storages = self::get_storages( );	
		foreach ( $sales_area as $sale_area )
			$this->data['product_stock']['stock_'.$sale_area['id']] = 0;
		foreach ($storages as $storage)
		{
			$this->data['product_stock']['storage_'.$storage->id] = 0;
			$this->data['product_stock']['reserve_'.$storage->id] = 0;
		}
		foreach ( $metas as $meta )
		{
			$meta->meta_value = (float)$meta->meta_value;
			foreach ( $sales_area as $sale_area )
			{						
				if ( $meta->meta_key == 'stock_'.$sale_area['id'] )
				{					
					$this->data['product_stock']['stock_'.$sale_area['id']] += $meta->meta_value;				
					if ( $this->data['product_stock']['stock_'.$sale_area['id']] > USAM_UNLIMITED_STOCK )
						$this->data['product_stock']['stock_'.$sale_area['id']] = USAM_UNLIMITED_STOCK;
					break;
				}		
			}
			foreach ($storages as $storage)
			{		
				if ( $meta->meta_key == 'storage_'.$storage->id )
				{					
					$this->data['product_stock']['storage_'.$storage->id] += $meta->meta_value;
					if ( $this->data['product_stock']['storage_'.$storage->id] > USAM_UNLIMITED_STOCK )
						$this->data['product_stock']['storage_'.$storage->id] = USAM_UNLIMITED_STOCK;
					break;
				}
				elseif ( $meta->meta_key == 'reserve_'.$storage->id )
				{					
					$this->data['product_stock']['reserve_'.$storage->id] += $meta->meta_value;
					break;
				}
			}
			if ( $meta->meta_key == 'stock' )
				$all_stock += $meta->meta_value;		
			
			if ( $meta->meta_key == 'total_balance' )
				$total_balance += $meta->meta_value;
		}	
		$this->data['product_stock']['stock'] = $all_stock > USAM_UNLIMITED_STOCK ? USAM_UNLIMITED_STOCK : $all_stock;
		$this->data['product_stock']['total_balance'] = $total_balance > USAM_UNLIMITED_STOCK ? USAM_UNLIMITED_STOCK : $total_balance;
		return true;
	}
	
	private function calculation_of_inventory_product( )
	{			
		$sales_area = usam_get_sales_areas();
		$storages = self::get_storages( );	
		$selected_sales_areas = ['stock' => array(), 'reserve' => array()];
		$all_stock = 0;
		$total_balance = 0;			
		foreach ($storages as $storage)
		{		
			if ( !isset($this->data['product_stock']['storage_'.$storage->id]) )
				$this->data['product_stock']['storage_'.$storage->id] = (float)usam_get_product_stock( $this->product_id, 'storage_'.$storage->id );
			$result = true;
			if ( $storage->shipping == 1 )
			{
				$reserve = usam_get_reserve_in_storage( $storage->id, $this->product_id );
				$total_balance += $this->data['product_stock']['storage_'.$storage->id];
				$all_stock += $this->data['product_stock']['storage_'.$storage->id]-$reserve;	
				if ( !empty($sales_area) )
				{
					foreach ( $sales_area as $sale_area )
					{								
						if ( usam_get_storage_metadata( $storage->id, 'sale_area_'.$sale_area['id'] ) )
						{	
							if ( isset($selected_sales_areas['stock'][$sale_area['id']]) )
								$selected_sales_areas['stock'][$sale_area['id']] += $this->data['product_stock']['storage_'.$storage->id];	
							else
								$selected_sales_areas['stock'][$sale_area['id']] = $this->data['product_stock']['storage_'.$storage->id];
							$selected_sales_areas['reserve'][$sale_area['id']] = $reserve;
						}
					}	
				}
			}
		}				
		foreach ( $selected_sales_areas['stock'] as $sale_area_id => $stock )
		{					
			$value = $stock - $selected_sales_areas['reserve'][$sale_area_id];
			$this->data['product_stock']['stock_'.$sale_area_id] = $value > USAM_UNLIMITED_STOCK ? USAM_UNLIMITED_STOCK : $value;
		}
		$this->data['product_stock']['stock'] = $all_stock > USAM_UNLIMITED_STOCK ? USAM_UNLIMITED_STOCK : $all_stock;
		$this->data['product_stock']['total_balance'] = $total_balance > USAM_UNLIMITED_STOCK ? USAM_UNLIMITED_STOCK : $total_balance;		
		return true;
	}
	
	private function sort_type_price( $type_prices, $id = '0' )
	{				
		$results = array();
		foreach ( $type_prices as $key => $price )
		{					
			$base_type = empty($price['base_type'])?'0':(string)$price['base_type'];
			if ( $base_type == $id )
			{
				$results[] = $price;				
				$results = array_merge( $results, $this->sort_type_price( $type_prices, $price['code']) );
			}
		}					
		return $results;
	}
			
/* Расчет цены */
	public function save_prices( )
	{				 
		global $wpdb;
		$product_id = usam_get_post_id_main_site( $this->product_id );
		$result = false;
	
		if ( $this->product_type == 'variable' ) 
			$result = $this->calculate_price_variations( );		
		else
		{
			$purchasing_code = '';
			$type_prices = array();	
			foreach ( self::$type_prices as $value )
			{			
				if ( !isset($this->data['prices']['price_'.$value['code']]) )
					$this->data['prices']['price_'.$value['code']] = usam_get_product_metaprice($product_id, 'price_'.$value['code']);	
				$this->data['prices']['price_'.$value['code']] = (float)$this->data['prices']['price_'.$value['code']];	
					
				if ( !isset($this->data['prices']['underprice_'.$value['code']]) )
					$this->data['prices']['underprice_'.$value['code']] = usam_get_product_metaprice($product_id, 'underprice_'.$value['code']);
				$this->data['prices']['underprice_'.$value['code']] = (int)$this->data['prices']['underprice_'.$value['code']];					
				
				if ( !isset($this->data['prices']['old_price_'.$value['code']]) )
					$this->data['prices']['old_price_'.$value['code']] = usam_get_product_metaprice($product_id, 'old_price_'.$value['code'] );	
				$this->data['prices']['old_price_'.$value['code']] = (float)$this->data['prices']['old_price_'.$value['code']];	
					
				$price = $this->data['prices']["price_".$value['code']];
				if ( $value['type'] == "R" )
					$type_prices[] = $value; // Соберем только розничные цены
				elseif ( $value['type'] == "P" && $price && $purchasing_code == '' )
				{					
					$purchasing_code = $value['code'];
					$this->data['prices']["price_".$purchasing_code] = round((float)$this->data['prices']["price_".$purchasing_code], $value['rounding']);	
				}
			}
			$type_prices = $this->sort_type_price( $type_prices );
			$discount_rules = wp_cache_get( 'usam_product_discount_rules' );	
			if ( $discount_rules === false )
			{
				$discount_rules = usam_get_discount_rules(['active' => 1, 'acting_now' => 1, 'orderby' => 'priority', 'order' => 'ASC', 'type_rule' => ['product','fix_price'], 'cache_meta' => true]);	
				wp_cache_set( 'usam_product_discount_rules', $discount_rules );				
			}
			if ( $this->product_new == false )
				$discounts = usam_get_current_product_discount( $product_id );
			else
				$discounts = array();								
			foreach ( $type_prices as $value )
			{	
				$this->calculate_price( $value, $purchasing_code ); 
				$discount_ids = $this->calculate_discount_price( $value, $discounts, $discount_rules );										
				if ( !empty($discounts[$value['code']]) )
				{					
					$ids = array_diff($discounts[$value['code']], $discount_ids);					
					if ( !empty($ids) )
					{
						$in = '';
						if ( !empty($discount_ids) )
							$in = "AND discount_id NOT IN (".implode(",",$discount_ids).")";	
						$wpdb->query("DELETE FROM `".USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS."` WHERE product_id='".$product_id."' AND code_price='".$value['code']."' $in");	
					}
				}
			}	
		}		 
		if ( !empty($this->data['prices']) )
		{			
			$prices = apply_filters( "usam_save_product_prices", $this->data['prices'], $product_id );
			foreach( $prices as $meta_key => $meta_value)
			{						
				if ( $this->product_new == false )
					usam_update_product_metaprice($product_id, $meta_key, $meta_value);	
				elseif ( $meta_value != 0.00 )
					usam_add_product_metaprice($product_id, $meta_key, $meta_value);	
			}	
			$result = true;
		}
		return $result;
	}
	
	// Вычисление цены и скидки	
	private function calculate_price( $type_price, $purchasing_code )
	{				
		$code_price = $type_price['code'];			
		$underprice = 0;
		$price = 0;
		$use_surcharge = true;
		if ( empty($type_price['base_type']) )		
		{ 			
			if ( $purchasing_code )
				$price = usam_convert_currency( $this->data['prices']["price_".$purchasing_code], usam_get_currency_price_by_code( $purchasing_code ), $type_price['currency'] );	
			else
			{				
				$use_surcharge = false;
				if ( !empty($this->data['prices']["old_price_".$code_price]) )
					$price = (float)$this->data['prices']["old_price_".$code_price];
				else
					$price = (float)$this->data['prices']["price_".$code_price];
			} 
		}
		elseif ( isset($this->data['prices']["price_".$type_price['base_type']]) )
		{ 			
			$price = usam_convert_currency( $this->data['prices']["price_".$type_price['base_type']], usam_get_currency_price_by_code($type_price['base_type']), $type_price['currency'] );	
		}			
		if ( $use_surcharge )
		{
			$rules = maybe_unserialize( get_site_option('usam_underprice_rules') );				
			if ( !empty($rules) )
			{		
				// Если не установлено подберем подходящую наценку				
				$rule_id = 0;				
				if( !empty($this->data['prices']['underprice_'.$code_price]) )
				{
					$rule = usam_get_data($this->data['prices']["underprice_".$code_price], 'usam_underprice_rules');
					if ( empty($rule['category']) && empty($rule['brands']) && empty($rule['category_sale']) && empty($rule['catalogs']))
					{
						$rule_id = $this->data['prices']['underprice_'.$code_price];
					}
				}				
				if ( !$rule_id )
				{					
					$product_id = usam_get_post_id_main_site( $this->product_id );
					foreach ( $rules as $id => $rule )
					{
						if ( empty($rule['type_prices']) || in_array($code_price, $rule['type_prices']) )
						{
							if ( empty($rule['category']) && empty($rule['brands']) && empty($rule['category_sale']) && empty($rule['catalogs']) && empty($rule['contractors']))
								continue;
													
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
							if ( $result_term == false )
								continue;	
							if ( !empty($rule['contractors']) )
							{
								$result_term = false;
								$contractor = usam_get_product_meta($product_id, 'contractor');
								if ( in_array($contractor, $rule['contractors']) )
									$result_term = true;
							}						
							if ( !empty($rule['category']) )
							{
								$result_term = false;
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
							else
							{ 
								$rule_id = $rule['id'];	
								break; // Идеальное совпадение, можно завершить
							}								
						}
					} 										
				}
				if ( $rule_id )
				{	
					$rule = usam_get_data($rule_id, 'usam_underprice_rules');
					if ( !empty($rule['value']) )
					{
						$underprice = (float)$rule['value'];
						$this->data['prices']["underprice_".$code_price] = $rule_id;					
					}
				}
				else
				{			
					$this->data['prices']["underprice_".$code_price] = $rule_id;					
				}
			}		
			if ( empty($this->data['prices']["underprice_".$code_price]) )
			{ 
				$this->data['prices']["underprice_".$code_price] = 0;
				$underprice = isset($type_price['underprice'])?(float)$type_price['underprice']:0; 				
			} 	
			$price = $price + $price * $underprice / 100;	
		}		
		$rounding = isset($type_price['rounding'])?(int)$type_price['rounding']:2;
		$price = round($price, $rounding);	
		$this->data['prices']["price_".$code_price] = $price;			
	}	
		
	// Вычисление цены и скидки	
	private function calculate_discount_price( $type_price, $discounts, $discount_rules )
	{		
		global $wpdb;
			
		$product_id = usam_get_post_id_main_site( $this->product_id );
		$code_price = $type_price['code'];			
		$price_key = "price_".$code_price;
		$old_price_key = "old_price_".$code_price;	
		$price = $discount_price = $this->data['prices'][$price_key];		
		$discount_ids = [];		
		$product = usam_get_active_products_day_by_codeprice( $code_price, $product_id );	
		if ( !empty($product) )
		{	
			if ( $product->dtype == 'f' )									
				$discount_price = $discount_price - $product->discount;				
			elseif ( $product->dtype  == 'p' )
				$discount_price = $discount_price - $discount_price*$product->discount/ 100;
			else
				$discount_price = $product->discount;
		}
		else
		{			
			if ( !empty($discount_rules) )
			{		
				$current_time = time();					
				foreach ( $discount_rules as $key => $rule )
				{						
					$type_prices = usam_get_discount_rule_metadata($rule->id, 'type_prices');												
					if ( !empty($type_prices) && !in_array($code_price, $type_prices) )
						continue;				
					
					if ( $rule->type_rule == 'fix_price' )
					{							
						$discount_key = 'fix_price_'.$rule->id;
						if ( !isset($this->data['prices'][$discount_key]) )
							$fix_price = (float)usam_get_product_metaprice($product_id, $discount_key );
						else
							$fix_price = $this->data['prices'][$discount_key];	
						if ( $fix_price > 0 )
						{	
							$discount_price = $fix_price;
							$sql = "INSERT INTO `".USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS."` (`product_id`,`code_price`,`discount_id`) VALUES ('%d','%s','%d') ON DUPLICATE KEY UPDATE `discount_id`='%d'";					
							$insert = $wpdb->query( $wpdb->prepare($sql, $product_id, $code_price, $rule->id, $rule->id ) );			
							$discount_ids[] = $rule->id;		
							break;
						}
					}						
					elseif ( $rule->type_rule == 'product' )
					{						
						$conditions = usam_get_discount_rule_metadata( $rule->id, 'conditions');						
						if ( $this->compare_logic( $conditions ) ) 
						{  							
							if ( $rule->dtype == 'f' )							
								$discount_price = $discount_price - $rule->discount;				
							elseif ( $rule->dtype == 'p' )
								$discount_price = $discount_price - $discount_price*$rule->discount/ 100;
							else
								$discount_price = $rule->discount;												
							
							$sql = "INSERT INTO `".USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS."` (`product_id`,`code_price`,`discount_id`) VALUES ('%d','%s','%d') ON DUPLICATE KEY UPDATE `discount_id`='%d'";					
							$insert = $wpdb->query( $wpdb->prepare($sql, $product_id, $code_price, $rule->id, $rule->id ) );	
							$discount_ids[] = $rule->id;
							if ( !empty($rule->end) )
								break;								
						}
					}					
				}
			} 	
		}		
		$discount_price = usam_round_price( $discount_price, $code_price );	
		if ( $discount_price < $price )		
		{
			$this->data['prices'][$old_price_key] = $price;
			$this->data['prices'][$price_key] = $discount_price;
		}
		else
			$this->data['prices'][$old_price_key] = 0;	
		return $discount_ids;
	}
	
	public function calculate_price_variations( )
	{		
		global $wpdb;
		
		$product_id = usam_get_post_id_main_site( $this->product_id );
		$sql = "SELECT pm.meta_value AS price, pm.meta_key AS meta_key FROM {$wpdb->posts} AS p INNER JOIN ".USAM_TABLE_PRODUCT_PRICE." AS pm ON (pm.product_id=p.id) WHERE p.post_type='usam-product' AND p.post_parent='{$product_id}'"; 
		$price_variations = $wpdb->get_results( $sql );	
		foreach ( self::$type_prices as $type_price )
		{	// Если сохраняют главный товар вариаций		
			if ( !empty($price_variations) )
			{			
				$price = $old_price = $max = 999999999999999999;
				foreach( $price_variations as $key => $value )
				{	
					$value->price = isset($value->price)?(float)$value->price:0;
					if ( $value->meta_key == 'price_'.$type_price['code'] )
					{
						if ( $value->price > 0 && $value->price < $price )
						{
							$price = $value->price;     // поиск минимальной цены
						}
						unset($price_variations[$key]);
					}
					elseif ( $value->meta_key == 'old_price_'.$type_price['code'] )
					{
						if ( $value->price > 0 && $value->price < $old_price )
							$old_price = $value->price;// поиск минимальной цены на продажу
						unset($price_variations[$key]);
					}
				}			
				if ( $price == $max )
					$price = 0;	
				if ( $old_price == $max )
					$old_price = 0;	
			}
			else
			{	
				$price = 0;
				$old_price = 0;		
			}			
			$this->data['prices']['old_price_'.$type_price['code']] = $old_price;	
			$this->data['prices']['price_'.$type_price['code']] = $price;		
		}	
		return true;
	}	

	public function compare_logic( $conditions ) 
	{				
		$result = true;	
		$allow_operation = true;	
		if ( empty($conditions) )
			return $result;
		foreach( $conditions as $c )
		{				
			if ( !isset($c['logic_operator']) )
			{
				if ( !$allow_operation )
					continue;
				
				switch( $c['type'] )
				{					
					case 'group':
						$result = $this->compare_logic( $c['rules'] );
					break;
					case 'simple':
						$result = $this->check_property( $c ); 
					break;
				}
				if ( !$result )
				{							
					$allow_operation = false;
				}
			}				
			else
			{ // Если условие И, ИЛИ
				if ( $c['logic_operator'] == 'AND' )
				{ // Если и
							
				}
				else					
				{ // Если или		
					if ( $result )
					{	// Если условия истина до ближайшего оператора ИЛИ то завершить цикл
						break;
					}
					else	
					{						
						$allow_operation = true;
					}
				}				
			}
		}		
		return $result;
	}	

	private function get_term_id_main_site( $c ) 
	{	
		if ( usam_is_multisite() && is_main_site() )
		{
			if ( is_array($c['value']) )
			{
				foreach( $c['value'] as $key => $v )
					$c['value'][$key] = usam_get_term_id_main_site( $v );
			}
			else
				$c['value'] = usam_get_term_id_main_site( $c['value'] );	
		}
		return $c;
	}
	
	private function check_property( $c ) 
	{	
		$c = apply_filters( 'usam_product_discount_compare_logic_before', $c );
		$result = false;
		
		$compare = new USAM_Compare();
		
		$productmeta = isset($this->data['productmeta'])?$this->data['productmeta']:[];
		if ($c['property'] == 'name')
		{
			$title = !empty($this->data['post_title']) ? $this->data['post_title']: get_the_title( $this->product_id );
			$result = $compare->compare_string($c['logic'], $title, $c['value'] );
		}
		elseif ($c['property'] == 'sku')
		{
			$sku = !isset($productmeta['sku'])?usam_get_product_meta($this->product_id, 'sku'):$productmeta['sku'];
			$result = $compare->compare_string($c['logic'], mb_strtoupper($sku), mb_strtoupper($c['value']) );
		}			
		elseif ($c['property'] == 'barcode')
		{					
			$barcode = !isset($productmeta['barcode'])?usam_get_product_meta($this->product_id, 'barcode'):$productmeta['barcode'];
			$result = $compare->compare_number($c['logic'], $barcode, $c['value'] );
		}
		elseif ($c['property'] == 'category')
		{		
			$c = $this->get_term_id_main_site( $c );
			$result = $compare->compare_terms( $this->product_id, 'usam-category', $c );
		}
		elseif ($c['property'] == 'brands')
		{		
			$c = $this->get_term_id_main_site( $c );
			$result = $compare->compare_terms( $this->product_id, 'usam-brands', $c );
		}
		elseif ($c['property'] == 'category_sale')
		{		
			$c = $this->get_term_id_main_site( $c );
			$result = $compare->compare_terms( $this->product_id, 'usam-category_sale', $c );
		}		
		elseif ( stristr($c['property'], 'attribute-') !== false)
		{			
			$attribute_key = str_replace("attribute-", "", $c['property']);		 
			$attribute = usam_get_product_attribute($this->product_id, $attribute_key);
			$result = $compare->compare_string($c['logic'], $attribute, $c['value'] );
		}
		else 
			$result = true;	
		$result = apply_filters( 'usam_product_discount_compare_logic_after', $result, $c );								
		return $result;		
	}
	
	public function get_file_title( $file_name )
	{
		$file_title = !empty($this->data['post_title'])?$this->data['post_title']:$file_name;
		return array( 'file_post' => array('post_title' => $file_title, 'post_parent' => $this->product_id), 'file_name' => strtolower($file_name));
	}
		
	public function get_number_downloaded_urls()
	{
		return $this->number_downloaded_urls;
	}
	
	public function media_handle_sideload( $url )
	{					
		$file_array = [];
		require_once( ABSPATH . 'wp-admin/includes/file.php');	
		require_once( ABSPATH . 'wp-admin/includes/media.php');
		require_once( ABSPATH . 'wp-admin/includes/image.php');
		$path_parts = pathinfo($url);
		if ( file_exists($url) && stripos($url, 'ftp://') !== false )
		{							
			$file = $this->get_file_title( $path_parts['filename'] );
			$file_array = ['name' => $file['file_name'].'.'.$path_parts['extension'], 'tmp_name' => $url, 'error' => 0, 'size' => filesize($url)];
		}
		else
		{			
			$tmp = download_url( $url );
			$this->number_downloaded_urls++;
			if( !is_wp_error($tmp) )
			{ 				
				$fileextension = '';
				if ( function_exists('image_type_to_extension') )
					$fileextension = image_type_to_extension( @exif_imagetype( $url ) );
				if ( !$fileextension && isset($path_parts['extension']) )
					$fileextension = '.'.$path_parts['extension'];
				$file = $this->get_file_title( $path_parts['filename'] );	
				$file_array = ['name' => $file['file_name'].$fileextension, 'tmp_name' => $tmp];
			}
			else	
				error_log( sprintf('%s: %s', __METHOD__, $tmp->get_error_message().'.' ) );
		}
		$attachment_id = false;			
		if ( $file_array )
		{			
			$attachment_id = media_handle_sideload($file_array, $this->product_id, $file['file_post']['post_title'], $file['file_post'] );		
			if( is_wp_error($attachment_id) )
			{				
				error_log( sprintf('%s: %s', __METHOD__, $attachment_id->get_error_message().'.' ) );
				$attachment_id = false;
			}
		//	if( file_exists($tmp) )
		//		@unlink( $tmp );
		}
		return $attachment_id;
	}
	
	public function insert_media( )
	{			
		$attachment_ids = [];	
		if ( !empty($this->data['thumbnail']) && is_string($this->data['thumbnail']) )
		{		
			$thumbnail_id = get_post_thumbnail_id( $this->product_id );				
			if ( $thumbnail_id )
			{
				$attachment_hash = pathinfo($this->data['thumbnail'], PATHINFO_FILENAME);
				$post_attachment_path = get_attached_file($thumbnail_id, true);	
				if ( file_exists($post_attachment_path) ) 
				{
					$post_attachment_hash = pathinfo($post_attachment_path, PATHINFO_FILENAME);
					if ( $post_attachment_hash != $attachment_hash )
						wp_delete_attachment( $thumbnail_id, true );
					else
						unset($this->data['thumbnail']);
				}
			}				
			if ( !empty($this->data['thumbnail']) )
			{
				$thumbnail_id = $this->media_handle_sideload( $this->data['thumbnail'] );
				if ( is_numeric($thumbnail_id))
				{
					$attachment_ids[] = $thumbnail_id;
					if ( !empty($this->data['image_gallery']) )
						$this->data['image_gallery'][] = $thumbnail_id;
					else
						$this->data['image_gallery'] = [ $thumbnail_id ];
					set_post_thumbnail( $this->product_id, $thumbnail_id );
				}
			}
		} 
		if ( !empty($this->data['media_url']) )
		{					
			$post_attachments = usam_get_product_images( $this->product_id );	
			if( !empty($post_attachments) )
			{
				foreach($this->data['media_url'] as $k => $media_url)
				{					
					$attachment_hash = pathinfo($media_url, PATHINFO_FILENAME);
					foreach($post_attachments as $i => $attachment)
					{
						$post_attachment_path = get_attached_file($attachment->ID, true);	
						if ( file_exists($post_attachment_path) ) 
						{
							$post_attachment_hash = pathinfo($post_attachment_path, PATHINFO_FILENAME);
							if ( $post_attachment_hash == $attachment_hash )
							{
								$ok = true;		
								unset($this->data['media_url'][$k]);	
								unset($post_attachments[$i]);								
								break;
							}
						}
					}
					if ( $post_attachment_hash != $attachment_hash )
						wp_delete_attachment( $attachment->ID, true );
				}
				foreach($post_attachments as $attachment)	
					wp_delete_attachment( $attachment->ID, true );					
			}		
			foreach( $this->data['media_url'] as $k => $url )
			{
				if ( empty($this->data['thumbnail']) || $this->data['thumbnail'] !== $url )
				{
					$thumbnail_id = $this->media_handle_sideload( $url );
					if ( $k == 0 && empty($this->data['thumbnail']) )
						set_post_thumbnail( $this->product_id, $thumbnail_id );
					$attachment_ids[] = $thumbnail_id;
				}				
			}
			do_action('usam_add_images_links', $this->product_id, $this->data );	
		}	
		else
		{			
			if ( !empty($this->data['image_gallery']) )
				$attachment_ids = array_values( $this->data['image_gallery'] );					
					
			$update = false;
			$posts = [];
			if ( $this->product_new == false )
			{
				$attachments = usam_get_product_images( $this->product_id );				
				foreach ( $attachments as $attachment )	
				{
					$posts[$attachment->ID] = $attachment;	
					if ( empty($this->data['image_gallery']) || !in_array($attachment->ID, $this->data['image_gallery']) )
						if( wp_update_post(['ID' => $attachment->ID, 'post_parent' => 0]) )		
							$update = true;					
				}
			}						
			if ( !empty($this->data['image_gallery']) )
			{ 					
				foreach ( $this->data['image_gallery'] as $menu_order => $attachment_id )	
				{					
					$attachment_id = absint($attachment_id);
					if( !$attachment_id	)
						continue;
					
					if( $menu_order === 0 )
					{
						$thumbnail_id = get_post_thumbnail_id( $this->product_id );	
						if ( $thumbnail_id !== $attachment_id )
							set_post_thumbnail( $this->product_id, $attachment_id );
					}	
					if( (empty($posts[$attachment_id]) || $posts[$attachment_id]->menu_order != $menu_order) )
					{							
						if( wp_update_post(['ID' => $attachment_id, 'post_parent' => $this->product_id, 'menu_order' => $menu_order]) )
							$update = true;
					}
				}					
			}		
			if( $update )
				do_action('usam_update_product_images', $this->product_id, $this->data );
			wp_cache_delete( $this->product_id, 'usam_product_images' );		
		}				
		return $attachment_ids;
	}		
	/**
	* Функция обработки полей формы продукта. Защищает от не стандартного заполнения формы.
	*/
	private function sanitise_product_forms( $post_data = null )
	{			
		if ( isset($post_data['thumbnail']) )
			$post_data['thumbnail'] = str_replace("\\", '/', $post_data['thumbnail']);
			
		$post_data = stripslashes_deep( $post_data );				
		if ( !empty($post_data['tax_input']) )
		{
			$tax_input = array();
			foreach( $post_data['tax_input'] as $taxonomy => $terms )	
			{
				if ( is_array($terms) )
				{
					foreach( $terms as $key => $term_id )
					{
						if ( is_numeric($term_id) && $term_id > 0 )
							$tax_input[$taxonomy][] = (int)$term_id;			
					}
				}
			}
			$post_data['tax_input'] = $tax_input;
		}				
		foreach ( self::$type_prices as $value )
		{			
			$price_key = "price_".$value['code'];
			$old_price_key = "old_price_".$value['code'];	
			if ( isset($post_data['prices'][$price_key]) )
				$post_data['prices'][$price_key] = abs(usam_string_to_float( $post_data['prices'][$price_key] ));
			
			if ( isset($post_data['prices'][$old_price_key]) )
				$post_data['prices'][$old_price_key] = abs(usam_string_to_float( $post_data['prices'][$old_price_key] ));
		}					
		if( isset($post_data['productmeta']['sku']) )
		{
			if( $post_data['productmeta']['sku'] == __('Нет данных', 'usam') )
				$post_data['productmeta']['sku'] = '';
			else
				$post_data['productmeta']['sku'] = trim(str_replace(array(' ','&nbsp;'),'',$post_data['productmeta']['sku']));
		}			
		if( isset($post_data['productmeta']['virtual']) )
			$post_data['productmeta']['virtual'] = sanitize_title($post_data['productmeta']['virtual']);
	
		if ( isset($post_data['productmeta']) ) 
		{
			if ( isset($post_data['productmeta']['width']) ) 
				$post_data['productmeta']['width'] = abs(usam_string_to_float( $post_data['productmeta']['width'] ));
			if ( isset($post_data['productmeta']['height']) ) 
				$post_data['productmeta']['height'] = abs(usam_string_to_float( $post_data['productmeta']['height'] ));				
			if ( isset($post_data['productmeta']['length']) ) 
				$post_data['productmeta']['length'] = abs(usam_string_to_float( $post_data['productmeta']['length'] ));
			if ( !empty($post_data['productmeta']['width']) && !empty($post_data['productmeta']['height']) && !empty($post_data['productmeta']['length']) )
				$post_data['productmeta']['volume'] = $post_data['productmeta']['width']*$post_data['productmeta']['height']*$post_data['productmeta']['length'];				
			if ( isset($post_data['productmeta']['unit']) ) 			
				$post_data['productmeta']['unit'] = abs(usam_string_to_float( $post_data['productmeta']['unit'] ));				
			if( isset($post_data['productmeta']['weight']) )
				$post_data['productmeta']['weight'] = usam_string_to_float($post_data['productmeta']['weight']);		
			if( isset($post_data['productmeta']['volume']) )
				$post_data['productmeta']['volume'] = usam_string_to_float($post_data['productmeta']['volume']);
			
			if( isset($post_data['productmeta']['volume']) )
				$post_data['productmeta']['volume'] = usam_string_to_float($post_data['productmeta']['volume']);			
		}		
		if ( isset($post_data['meta']) && isset($post_data['meta']['product_metadata']) ) 
		{		
			if ( isset($post_data['meta']['product_metadata']['bonuses']) ) 
			{
				foreach ( $post_data['meta']['product_metadata']['bonuses'] as $k => $bonuses )
				{
					$post_data['meta']['product_metadata']['bonuses'][$k] = array_merge(['value' => 0, 'type' => 'p'], $post_data['meta']['product_metadata']['bonuses'][$k]);
				}
			}
		}
		$storages = self::get_storages( );
		foreach ( $storages as $storage )
		{		
			if ( isset($post_data['product_stock']['storage_'.$storage->id]) )
			{		
				if ( isset($post_data['product_stock']['reserve_'.$storage->id]) )
					$post_data['product_stock']['reserve_'.$storage->id] = usam_string_to_float($post_data['product_stock']['reserve_'.$storage->id]);	
				if ( $post_data['product_stock']['storage_'.$storage->id] === '' )
					$post_data['product_stock']['storage_'.$storage->id] = USAM_UNLIMITED_STOCK;		
				else
					$post_data['product_stock']['storage_'.$storage->id] = usam_string_to_float($post_data['product_stock']['storage_'.$storage->id]);	
			}			
		}			
		return $post_data;
	}
	
	private static function get_storages()	
	{
		$storages = usam_get_storages( array('cache_meta' => true) );
		return $storages;
	}
		
	/**
	* Функция получения значений товара по умолчанию
	*/
	public static function get_default_product( $product_id )
	{				
		$default_product = array( 
			'post_title' => '',
			'post_content' => '',
			'post_excerpt' => '',	
			'post_parent' => 0,		
			'product_stock' => [],
			'prices' => [],			
			'postmeta' => ['views' => 0],
			'productmeta' => ['unit_measure' => 'thing', 'unit' => 1, 'virtual' => 'product', 'code' => '', 'sku' => ''], //'weight' => '', 'volume' => 0, 'height' => 0, 'width'=> 0,'length'=> 0, 'barcode' => '' 
		);	
		return $default_product;
	}
	
	// Слить значения по умолчанию с заданными значениями
	private function merge_meta_deep( $original, $updated, $default ) 
	{		
		if ( isset($updated) )
		{
			if ( is_array($original) )
			{			
				if (!empty($original))
				{	
					$keys = array_merge( array_keys( $original ), array_keys( $updated ) );							
				}
				else
					$keys = array_keys( $updated );	

				foreach ( $keys as $key ) 
				{
					if ( ! isset($updated[$key] ) )
						continue;							
					if ( isset($original[$key]) && is_array( $original[$key] ) && is_array($updated[$key]) && !empty($default[$key]) && is_array($default[$key]))
						$original[$key] = $this->merge_meta_deep( $original[$key], $updated[$key], $default[$key] );
					else
					{	
					//	if ( (isset($original[$key]) && !is_array($original[$key])) || !isset($original[$key]) )						
							$original[$key] = $updated[$key];
					}
				}
			}
			else		
				$original = $updated;
		}
		return $original;
	}
	
	private function remove_unnecessary_keys( $original, $updated ) 
	{
		$new = array();
		$keys = array_keys( $original );		
		foreach ( $keys as $key ) 
		{	
			if ( is_array($updated[$key]) )
				$new[$key] = $this->remove_unnecessary_keys( $original[$key], $updated[$key] );
			else
			{
				if ( isset($updated[$key]) )
					$new[$key] = $updated[$key];
				else
					$new[$key] = $original[$key];
			}
		}
		return $new;
	}
		
	private function get_differences( $original, $updated ) 
	{
		$new = array();		
		foreach ( $updated as $key => $value ) 
		{	
			if ( isset($original[$key]) )
			{				
				if ( is_array($value) )
				{
					if ( count($value) == count($original[$key]) )
					{
						$results = $this->get_differences( $original[$key], $value );	
						if ( !empty($results) )
							$new[$key] = $results;
					}
					else
						$new[$key] = $value;
				}
				elseif ( $original[$key] != $value )
					$new[$key] = $value;
			}
			else
				$new[$key] = $value;
		}
		return $new;
	}	
}

function usam_recalculate_price_products_ids( $ids, $title = '' )
{
	if ( !empty($ids) )
	{	
		if ( !is_array($ids) )
			$ids = array($ids);
		
		$args = ['post__in' => $ids];
		return usam_recalculate_price_products( $args, $title );
	}
	else
		return 0;
}

function usam_recalculate_price_products( $args_query = [], $title = ''  )
{		
	if ( empty($args_query) )
	{
		$name = 'recalculate_price';
		$title = empty($title)?__("Пересчет цен","usam" ):$title;
		$events = usam_get_system_process(['id_like' => 'recalculate_price_']);
		if ( !empty($events) )
			foreach ( $events as $id => $event ) 
				usam_delete_system_process( $id );				
	}
	else
	{
		static $i = 0;
		$i++;
		$name = 'recalculate_price_'.$i;		
		$title = empty($title)?__("Частичный пересчет цен","usam" ):$title;
	} 	
	$count = usam_get_total_products( $args_query );	
	usam_create_system_process( $title, $args_query, 'recalculate_price_products', $count, $name );		
	return $count;
}

// Изменить цену товара
function usam_edit_product_prices( $product_id, $prices = [] )
{				
	$_product = new USAM_Product( $product_id );	
	if ( !empty($prices) )
		$_product->set(['prices' => $prices]);	

	$result = $_product->save_prices( );
	do_action('usam_edit_product_prices', $product_id, ['prices' => $prices] );	
	return $result;
}

function usam_get_total_products( $args = [] )
{		
	$args['fields'] = 'ids';	
	$args['orderby'] = 'ID';
	$args['update_post_meta_cache'] = false;	
	$args['update_post_term_cache'] = false;
	$args['cache_results'] = false;
	$args['product_meta_cache'] = false;
	$args['prices_cache'] = false;
	$args['stocks_cache'] = false;
	$args['posts_per_page'] = -1;
	$args['nopaging'] = true;	
	if ( isset($args['paged']) )
		unset($args['paged']);
	$products = usam_get_products( $args );	
	return count($products);
}

// Получить товары
function usam_get_products( $args = array(), $thumbnail_cache = false )
{	
	$default_args = [
		'posts_per_page'  => '-1',
		'orderby'         => 'date',
		'order'           => 'DESC',		
		'meta_key'        => '',
		'meta_value'      => '',
		'include'         => [],
		'exclude'         => [],		
	//	'post_parent'     => 0,
		'prices_cache'    => true,
		'stocks_cache'    => true,
		'suppress_filters' => false,
		'product_meta_cache' => true,
		'update_post_meta_cache' => false,
		'post_status'     => ['private', 'draft', 'pending', 'publish', 'future']
	];			
	$args = array_merge( $default_args, (array)$args );
	if ( $thumbnail_cache )	
		$args['update_post_meta_cache'] = true;
	if ( !empty($args['from_views']) )
	{
		$args['postmeta_query'][] = ['key' => 'views', 'type' => 'numeric', 'value' => $args['from_views'], 'compare' => '>='];
		unset($args['from_views']);
	}	
	if ( !empty($args['to_views']) )
	{
		$args['postmeta_query'][] = ['key' => 'views', 'type' => 'numeric', 'value' => $args['to_views'], 'compare' => '<='];
		unset($args['to_views']);
	}
	$args['ignore_sticky_posts'] = true;	
	$args['post_type'] = 'usam-product';
	$args['no_found_rows'] = true;	
	
	if ( isset($args['cache_product']) && !$args['cache_product'] )	
	{
		$args['cache_results'] = false;
		$args['update_post_meta_cache'] = false;
		$args['update_post_term_cache'] = false;
		$args['stocks_cache'] = false;
		$args['prices_cache'] = false;
		$args['product_meta_cache'] = false;
	}	
	$query = new WP_Query;
	$results = $query->query( $args );		
	if ( $thumbnail_cache )
		usam_product_thumbnail_cache( $query );	
		
	return $results;
}

function usam_product_thumbnail_cache( $wp_query = null ) 
{
	if ( ! $wp_query )
		$wp_query = $GLOBALS['wp_query'];

	if ( $wp_query->thumbnails_cached )
		return;

	$thumb_ids = array();
	foreach ( $wp_query->posts as $post )
	{		
		if ( is_numeric( $post ) )
			$product_id = $post;
		else
			$product_id = $post->ID;		
		if ( $id = get_post_thumbnail_id( $product_id ) )
			$thumb_ids[] = $id;
	} 
	if ( !empty ( $thumb_ids ) ) {
		_prime_post_caches( $thumb_ids, false, true );
	}
	$wp_query->thumbnails_cached = true;
}

function usam_update_system_products_attribute( $args, $attribute )
{				
	$i = 0;	
	wp_defer_term_counting( true );
	
	if ( empty($args['prices']) )
		$args['prices_cache'] = false;
	if ( empty($args['product_stock']) )
		$args['stocks_cache'] = false;		
	if ( !empty($args['meta']) )
		$args['update_post_meta_cache'] = true;	
	if ( !empty($args['tax_input']) )
		$args['update_post_term_cache'] = true;	
	
	$products = usam_get_products( $args );	
	foreach( $products as $key => $product )
	{											
		$update = $attribute;
		if ( !empty($update['meta']['product_metadata']) )
		{
			$metadata = get_post_meta( $product->ID, USAM_META_PREFIX.'product_metadata', true );	
			if ( !empty($metadata) )
			{
				foreach( $metadata as $k => $value )
				{					
					if ( isset($update['meta']['product_metadata'][$k]))
						$update['meta']['product_metadata'][$k] = array_merge( $metadata[$k], $update['meta']['product_metadata'][$k] );
					else
						$update['meta']['product_metadata'][$k] = $metadata[$k];
				}
			}	  
		}			
		$_product = new USAM_Product( $product->ID );			
		$_product->set( $update );
		$_product->update_product();	
		$i++; 
		usam_clean_product_cache( $product->ID );
		unset($products[$key]);
	}
	wp_defer_term_counting( false );
	return $i;
}

function usam_calculate_product_filters( $attribute_id )
{
	$args = ['post_status' => 'publish', 'tax_query' => []];					
	$category_ids = usam_get_taxonomy_relationships_by_id( $attribute_id, 'usam-category', 1 );	
	if( !empty($category_ids) )
	{
		$ids = array();
		foreach( $category_ids as $category_id )
		{	
			$ids[] = $category_id;
			$term_ids = get_terms(['child_of' => $category_id, 'taxonomy' => 'usam-category', 'fields' => 'ids']);
			$ids = array_merge( $ids, $term_ids );		
		}							
		$ids = array_map('intval', $ids);
		$args['tax_query'][] = array('taxonomy' => 'usam-category', 'field' => 'id', 'terms' => $ids );
	}							
	$i = usam_get_total_products( $args );					
	if ( $i )
	{
		$term = get_term( $attribute_id, 'usam-product_attributes' );			
		usam_create_system_process( sprintf(__("Расчет товарных фильтров для &#8220;%s&#8221;", "usam"),$term->name), $args, 'calculate_product_filters', $i, 'calculate_product_filters_'.$attribute_id);
	}
	return $i;
}

function usam_get_posts( $args, $thumbnail_cache = false )
{
	$default_args = [
		'posts_per_page' => '-1',
		'post_status'    => 'any'
	];			
	$args = array_merge( $default_args, (array)$args );
	$args['suppress_filters'] = false;
	$args['ignore_sticky_posts'] = true; //Игнорировать прилепленные посты
	$args['no_found_rows']   = true; //true - не подсчитывать количество найденных строк.
	if ( $thumbnail_cache )	
		$args['update_post_meta_cache'] = true;	
	$query = new WP_Query;
	$posts = $query->query( $args );
	
	if ( $thumbnail_cache )
		usam_product_thumbnail_cache( $query );	
	
	return $posts;
}