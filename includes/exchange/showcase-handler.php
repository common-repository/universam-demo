<?php
new USAM_Showcase_Handler();
class USAM_Showcase_Handler
{
	function __construct() 
	{
		add_action('usam_product_showcase', [&$this, 'product_showcase'], 10, 3 );
		add_action('usam_type_prices-update', [&$this, 'update_type_prices'], 10, 2 );
		add_action('usam_type_prices-delete', [&$this, 'delete_type_prices']);
		
		add_action('usam_storage_insert', [&$this, 'storage_insert']);
		add_action('usam_storage_update', [&$this, 'storage_update']);		
		add_action('usam_storage_before_delete', [&$this, 'storage_delete']);	
		
		add_action('usam_update_product', [&$this, 'update_product'], 10, 3 );
		add_action('usam_insert_product', [&$this, 'insert_product'], 10, 3 );
		add_action('usam_edit_product', [&$this, 'manager_edit_product'], 10, 3 );
		
		add_action('before_delete_post', [&$this, 'delete_post'], 10, 2);
		add_action('usam_edit_product_prices', [&$this, 'edit_product_prices'], 10, 2);
		
		add_action( 'pre_delete_term', [&$this, 'term_delete'], 10, 2);		

		add_action( 'usam_showcase_update', [&$this, 'showcase_update'], 10, 2);
		add_action( 'usam_showcase_insert', [&$this, 'showcase_insert']);
		
		add_action( 'usam_ten_minutes_cron_task', [&$this, 'cron'] );
	}
	
	public static function get_showcases()
	{	
		$object_type = 'usam_showcases';	
		$cache = wp_cache_get( $object_type );		
		if ( $cache === false )			
		{				
			require_once( USAM_FILE_PATH .'/includes/exchange/showcases_query.class.php' );
			$cache = usam_get_showcases();	
			wp_cache_set( $object_type, $cache );						
		}	
		return $cache;	
	}
	
	public function cron()
	{	
		if( !usam_check_process_is_running('update_products_showcases') )
		{
			$i = usam_get_total_products(['productmeta_query' => [['key' => 'update_showcase_', 'compare_key' => 'LIKE']]]);		
			if( $i )
				usam_create_system_process( __("Выгрузка товаров в витрины", "usam" ), [], ['USAM_Showcase_Handler', 'update_products'], $i, 'update_products_showcases' );	
		}
	}
	
	public static function update_products( $data, $number, $event )
	{
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		$showcases = self::get_showcases();
		$products = usam_get_products(['paged' => $event['launch_number'], 'posts_per_page' => 100, 'productmeta_query' => [['key' => 'update_showcase_', 'compare_key' => 'LIKE']]]);		
		if( $products ) 
		{
			foreach( $showcases as $showcase )		
			{
				$api = new USAM_Showcase_API( $showcase );
				$items = [];
				foreach( $products as $key => $product )
				{					
					$status = usam_get_product_meta( $product->ID, 'update_showcase_'.$showcase->id );				
					usam_delete_product_meta( $product->ID, 'update_showcase_'.$showcase->id );
					if( $status == 'all' )
						$items[] = self::get_data_product( $product, $showcase );
					elseif( $status == 'prices' )					
					{
						$update = self::get_prices( $product->ID, $showcase );
						$api->save_product_prices( $product->ID, $update );	
					}					
				}
				$api->save_products( $items );
			}
		}
		return ['done' => count($products)];	
	}
	
	public static function synchronization_products( $data, $number, $event )
	{
		require_once( USAM_FILE_PATH .'/includes/exchange/showcases_query.class.php' );
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		
		$products = usam_get_products(['paged' => $event['launch_number'], 'post_status' => ['draft', 'pending', 'publish'], 'posts_per_page' => 500, 'update_post_term_cache' => true, 'stocks_cache' => false, 'prices_cache' => false,]);		
		$showcases = usam_get_showcases(['products' => 1]);	
		$done = 0;
		if( $showcases )
		{
			$sku = [];
			foreach( $products as $key => $product )
				$sku[] = usam_get_product_meta( $product->ID, 'sku' );
			$product_showcases = [];
			foreach( $showcases as $showcase )
			{
				$api = new USAM_Showcase_API( $showcase );
				$results = $api->send_request( 'products', 'POST', ['count' => 500, 'add_fields' => ['sku'], 'productmeta_query' => [['key' => 'sku', 'compare' => 'IN', 'value' => $sku]]]);	
				if( !empty($results['items']) )					
					foreach( $results['items'] as $result )
					{
						$product_id = usam_get_product_id_by_sku( $result['sku'] );
						if( $product_id )
						{
							usam_update_product_meta( $product_id, 'code_showcase_'.$showcase->id, $result['ID'] );
							$product_showcases[$product_id][] = $showcase->id;						
						}
					}
			}
			foreach( $product_showcases as $product_id => $ids )
				usam_save_array_metadata( $product_id, 'product', 'showcases', $ids );
		}
		return ['done' => count($products)];	
	}
	
	public static function delete_not_synchronization_products( $id, $number, $event )
	{
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');		
		require_once( USAM_FILE_PATH .'/includes/exchange/showcase.class.php' );
		$showcase = (object)usam_get_showcase( $id );
		$done = 0;		
		$api = new USAM_Showcase_API( $showcase );
		$results = $api->send_request( 'products', 'POST', ['paged' => $event['launch_number'], 'count' => 1000]);	
		if( !empty($results['items']) )	
		{
			$ids = [];
			foreach( $results['items'] as $result )
			{
				$done++;
				$product_id = usam_get_product_id_by_meta( 'code_showcase_'.$id, $result['ID'] );
				if( !$product_id || !self::check_product_unloading_showcase( $product_id, $showcase ) )
					$ids[] = $result['ID'];
			}
			if( !empty($ids) )
				$api->send_request( 'products', 'DELETE', ['post__in' => $ids] );
		}		
		return ['done' => $done];	
	}
		
	public static function check_available_products( $data, $number, $event )
	{		
		require_once( USAM_FILE_PATH .'/includes/exchange/showcases_query.class.php' );
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		$showcases = usam_get_showcases(['products' => 1]);	
		$done = 0;
		if( $showcases )
		{
			$products = usam_get_products(['paged' => $event['launch_number'], 'post_status' => ['draft', 'pending', 'publish'], 'posts_per_page' => 500, 'update_post_term_cache' => true, 'stocks_cache' => false, 'prices_cache' => false,]);					
			foreach( $products as $k => $product )
			{
				$done++;
				$product_showcases = [];
				foreach( $showcases as $showcase )
				{
					if( self::check_product_unloading_showcase( $product->ID, $showcase ) )	
					{
						$product_showcases[] = $showcase->id;
						usam_update_product_meta( $product->ID, 'update_showcase_'.$showcase->id, 'all' );
					}
					else
						self::delete_product_showcase( $product->ID, $showcase );
				}
				usam_save_array_metadata( $product->ID, 'product', 'showcases', $product_showcases );
				usam_clean_product_cache( $product->ID );
				unset($products[$k]);
			}
		}
		return ['done' => $done];	
	}
	
	public static function update_prices_products( $data, $number, $event )
	{
		require_once( USAM_FILE_PATH .'/includes/exchange/showcases_query.class.php' );
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		$showcases = usam_get_showcases(['products' => 1]);	
		$products = usam_get_products(['paged' => $event['launch_number'], 'posts_per_page' => 500, 'productmeta_query' => [['key' => 'code_showcase_', 'compare_key' => 'LIKE']]]);
		foreach( $showcases as $showcase )
		{
			$api = new USAM_Showcase_API( $showcase );
			foreach( $products as $key => $product )
			{
				$update = self::get_prices( $product->ID, $showcase );
				$api->save_product_prices( $product->ID, $update );	
			}
		}	
		return ['done' => count($products)];	
	}
	
	public static function get_prices( $product_id, $showcase )
	{
		static $showcase_prices;
		if( !isset($showcase_prices[$showcase->id]) )
		{
			$api = new USAM_Showcase_API( $showcase );
			$results = $api->get_type_prices();	
			if( !$results )
				return [];
			$showcase_prices[$showcase->id] = $results['items'];
		}		
		$prices = usam_get_prices();
		$update = [];				
		foreach( $prices as $tprice )
		{
			foreach(['price'] as $key )
			{				
				$value = usam_get_product_metaprice( $product_id, $key.'_'.$tprice['code'] );				
				if( isset($showcase_prices[$showcase->id][$tprice['code']]) )
					$value = usam_convert_currency( $value, $showcase_prices[$showcase->id][$tprice['code']]['currency'], $tprice['currency'] );	
				$update[$key.'_'.$tprice['code']] = $value;
			}
		//	$update['underprice_'.$tprice['code']] = usam_get_product_metaprice( $product_id, 'underprice_'.$tprice['code'] );
		}
		return $update;
	}
	
	public function edit_product_prices( $product_id, $prices )
	{
		$ids = usam_get_array_metadata($product_id, 'product', 'showcases', 'number');	
		foreach( $ids as $id )
		{
			$status = usam_get_product_meta( $product_id, 'update_showcase_'.$id );	
			if( !$status )
				usam_update_product_meta( $product_id, 'update_showcase_'.$id, 'prices' );
		}
	}
	
	public function delete_post( $product_id, $post )
	{
		$ids = usam_get_array_metadata($product_id, 'product', 'showcases', 'number');
		foreach( $ids as $id )
		{
			$uuid = usam_get_product_meta( $product_id, 'code_showcase_'.$id );
			if( $uuid )
			{					
				require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
				$api = new USAM_Showcase_API( $id );
				$api->send_request( 'product/'.$uuid, 'DELETE' );
			}
		}
	}
	
	private static function check_product_unloading_showcase( $product_id, $showcase )
	{	
		$result = true;		
		if( $showcase->settings )						
		{
			static $taxonomies = null;
			if( $taxonomies === null )
				$taxonomies = get_taxonomies(['object_type' => ['usam-product']]);
			$contractor = (int)usam_get_product_meta( $product_id, 'contractor' );	
			if( !empty($showcase->settings['contractors']) && (!$contractor || !in_array($contractor, $showcase->settings['contractors'])) )
				$result = false;
			else
			{
				foreach( $taxonomies as $taxonomy ) 
				{
					if( !empty($showcase->settings[$taxonomy]) )
					{
						$terms = get_the_terms( $product_id, $taxonomy );
						$ids = [];
						foreach( $terms as $term ) 
							$ids[] = $term->term_id;
						if( !$ids || !array_diff($ids, $showcase->settings[$taxonomy]) )
						{
							$result = false;
							break;						
						}
					}
				}		
			}
		}
		return $result;
	}
	
	public function update_product( $product_id, $data, $attributes )
	{				
		$ids = usam_get_array_metadata($product_id, 'product', 'showcases', 'number');
		foreach( $ids as $id )
			usam_update_product_meta( $product_id, 'update_showcase_'.$id, 'all' );
	}
	
	public function manager_edit_product( $product_id, $data, $attributes )
	{				
		$ids = usam_get_array_metadata($product_id, 'product', 'showcases', 'number');
		foreach( $ids as $id )
			usam_update_product_meta( $product_id, 'update_showcase_'.$id, 'all' );
	}
	
	public function insert_product( $product_id, $data, $attributes )
	{			
		require_once( USAM_FILE_PATH .'/includes/exchange/showcases_query.class.php' );
		$showcases = usam_get_showcases(['products' => 1]);	
		if( $showcases )
		{
			$ids = [];
			foreach( $showcases as $showcase )
			{
				if( self::check_product_unloading_showcase( $product_id, $showcase ) )			
					$ids[] = $showcase->id;							
			}			
			usam_save_array_metadata( $product_id, 'product', 'showcases', $ids );
			foreach( $ids as $id )
				usam_update_product_meta( $product_id, 'update_showcase_'.$id, 'all' );			
		}
	}	
	
	public function product_showcase( $product_id, $add, $delete )
	{	
		$ids = array_merge( $add, $delete );			
		if( $ids )
		{
			require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
			$showcases = self::get_showcases();	
			foreach( $showcases as $showcase )
			{
				if( in_array($showcase->id, $add) )
					usam_update_product_meta( $product_id, 'update_showcase_'.$showcase->id, 'all' );
				elseif( in_array($showcase->id, $delete) )
					self::delete_product_showcase( $product_id, $showcase );
			} 
		}
	}
	
	private static function delete_product_showcase( $product_id, $showcase )
	{
		$uuid = usam_get_product_meta( $product_id, 'code_showcase_'.$showcase->id );
		if( $uuid )
		{		
			$api = new USAM_Showcase_API( $showcase );
			$result = $api->send_request( 'product/'.$uuid, 'DELETE' );
			if( $result )
			{
				usam_delete_product_meta( $product_id, 'code_showcase_'.$showcase->id );
				usam_delete_product_meta( $product_id, 'date_update_showcase_'.$showcase->id );		
				usam_delete_product_meta( $product_id, 'update_showcase_'.$showcase->id );		
			}
		}		
	}
	
	private static function get_data_product( $post, $showcase )
	{
		if( is_numeric($post) )
			$post = get_post( $post );
		$product = ['post_title' => $post->post_title, 'post_content' => $post->post_content, 'post_excerpt' => $post->post_excerpt, 'post_status' => $post->post_status, 'post_password' => $post->post_password, 'post_name' => $post->post_name, 'post_parent' => $post->post_parent, 'menu_order' => $post->menu_order];
		
		foreach( ['unit_measure', 'unit', 'virtual', 'code', 'sku', 'weight', 'volume', 'height', 'width', 'length', 'barcode', 'contractor'] as $key )
			$product[$key] = usam_get_product_meta( $post->ID, $key );	
		 
		$product['code_main_site'] = $post->ID; //Защита от дубликатов
		$product['prices'] = self::get_prices( $post->ID, $showcase );	
		$attributes = get_terms(['hide_empty' => 0, 'taxonomy' => 'usam-product_attributes', 'update_term_meta_cache' => false]);
		foreach( $attributes as $attribute )
		{
			$field_type = usam_get_term_metadata($attribute->term_id, 'field_type');
			if ( usam_attribute_stores_values( $field_type ) )
			{
				$attribute_values = usam_get_attribute_values( $attribute->term_id );
				$v = usam_get_product_attribute($post->ID, $attribute->slug, false);
				if( $v )
				{
					foreach( $v as $attr )
					{
						foreach( $attribute_values as $option )
						{
							if ( $option->id == $attr->meta_value )	
							{						
								if ( $field_type == 'M' || $field_type == 'COLOR_SEVERAL' )
								{
									$product['attributes'][$attribute->slug][] = $option->value;
									break;
								}
								else
								{
									$product['attributes'][$attribute->slug] = $option->value;
									break 2;
								}							
							}
						}
					}
				}				
			}
			else
				$product['attributes'][$attribute->slug] = usam_get_product_attribute( $post->ID, $attribute->slug );
		}					
		$storages = usam_get_storages(['fields' => 'code=>data', 'active' => 'all']);
		$product['storages'] = [];
		foreach( $storages as $code => $storage )
		{
			$code = usam_get_storage_metadata( $storage->id, 'code_showcase_'.$showcase->id );
			if( $code )
				$product['storages'][$code] = usam_get_product_stock($post->ID, 'storage_'.$storage->id);				
		}			
		$product['images'] = usam_get_product_images_urls( $post->ID );
		
		$taxonomies = get_taxonomies(['object_type' => ['usam-product']]);
		foreach( $taxonomies as $taxonomy ) 
		{  
			$product_terms = get_the_terms( $post->ID, $taxonomy);
			$ids = [];  
			if( is_array( $product_terms ) )
			{
				foreach( $product_terms as $term )
				{
					$code = usam_get_term_metadata( $term->term_id, 'code_showcase_'.$showcase->id );	 
					if( $code )
						$ids[] = $code;				
				}
			}
			$product[$taxonomy] = $ids;
		}				
		return $product;
	}
		
	public function update_type_prices( $id, $update )
	{
		$showcases = self::get_showcases();	
		foreach( $showcases as $showcase )
		{
			$this->synchronization_type_prices( $showcase );
		}
	}	
	
	public function delete_type_prices( $item )
	{
		$showcases = self::get_showcases();	
		foreach( $showcases as $showcase )
		{
			$this->synchronization_type_prices( $showcase );
		}
	}		
	
	public function storage_delete( $item )
	{
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		$showcases = self::get_showcases();	
		foreach( $showcases as $showcase )
		{
			$uuid = usam_get_storage_metadata( $item['id'], 'code_showcase_'.$showcase->id );
			$api = new USAM_Showcase_API( $showcase );
			$api->send_request( 'storage/'.$uuid, 'DELETE' );
		}
	}
	
	public function storage_insert( $t )
	{
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		$showcases = self::get_showcases();	
		$data = $t->get_data();
		foreach( $showcases as $showcase )
		{
			$api = new USAM_Showcase_API( $showcase );
			$uuid = $api->send_request( 'storage', 'POST', $data );
			if( $uuid )
				usam_update_storage_metadata( $data['id'], 'code_showcase_'.$showcase->id, $uuid);
		}
	}
	
	public function storage_update( $t )
	{
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		$showcases = self::get_showcases();	
		$data = $t->get_data();		
		foreach( $showcases as $showcase )
		{
			$uuid = usam_get_storage_metadata( $data['id'], 'code_showcase_'.$showcase->id );
			if( $uuid )
			{
				$api = new USAM_Showcase_API( $showcase );
				$api->send_request( 'storage/'.$uuid, 'POST', $data );
			}
			else
				$this->storage_insert( $t );
		}
	}	
	
	public function term_delete( $term, $taxonomy )
	{
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		$showcases = self::get_showcases();			
		foreach( $showcases as $showcase )
		{
			$id = usam_get_term_metadata( $term->term_id, 'code_showcase_'.$showcase->id );
			$api = new USAM_Showcase_API( $showcase );
			$api->send_request( 'term/'.$id, 'DELETE' );
		}
	}
	
	public function showcase_update( $t, $changed_data )
	{				
		$data = $t->get_data();	
		if( !empty($changed_data['status']) && $data['status'] === 'active' )
			$this->showcase_synchronization( $t );	
	}
	
	public function showcase_insert( $t )
	{				
		$data = $t->get_data();
		if( $data['status'] === 'active' )
			$this->showcase_synchronization( $t );	
	}
		
	public function showcase_synchronization( $t )
	{									
		set_time_limit(3000);
		$showcase = (object)$t->get_data();	
		$this->synchronization_storages( $showcase );	
		sleep(1);		
		$this->synchronization_type_prices( $showcase );
		sleep(1);
		$this->synchronization_terms( $showcase );		
		if( $showcase->products )
		{
			$i = usam_get_total_products();								
			usam_create_system_process( __("Пометить товары для выгрузки в витрины", "usam"), [], ['USAM_Showcase_Handler', 'check_available_products'], $i, 'check_available_products' );
		}
	}
		
	public function synchronization( )
	{
		set_time_limit(3000);
		$showcases = self::get_showcases();	
		foreach( $showcases as $showcase )
		{
			$this->synchronization_storages( $showcase );		
			$this->synchronization_type_prices( $showcase );			
			$this->synchronization_terms( $showcase );
		}
	}
	
	public function synchronization_storages( $showcase )
	{	
		$storages = usam_get_storages(['active' => 'all']);
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		$api = new USAM_Showcase_API( $showcase );
		$showcase_data = $api->send_request( 'storages', 'GET', ['count' => 10000] );
		foreach( $showcase_data['items'] as $k => $item )
		{
			foreach( $storages as $k2 => $storage ) 
			{
				$code_showcase = usam_get_storage_metadata( $storage->id, 'code_showcase_'.$showcase->id );
				if( $code_showcase == $item['id'] || !empty($item['code']) && !empty($storage->code) && $item['code'] == $storage->code )
				{
					if( $code_showcase !== $item['id'] )
						usam_update_storage_metadata( $storage->id, 'code_showcase_'.$showcase->id, $item['id']);		
					$update = (array)$storage;
					foreach( ['phone', 'email', 'schedule', 'address', 'longitude', 'latitude'] as $meta_key )
						$update[$meta_key] = (string)usam_get_storage_metadata( $storage->id, $meta_key);
				//	$result = array_diff($array1, $array2);
					$api->send_request( 'storage/'.$item['id'], 'POST', $update );
					unset($storages[$k2]);
					unset($showcase_data['items'][$k]);
				}
			}
		}			
		foreach( $storages as $item )
		{
			$uuid = $api->send_request( 'storage', 'POST', $item );		
			if( $uuid )
				usam_update_storage_metadata( $item->id, 'code_showcase_'.$showcase->id, $uuid);
		}
		foreach( $showcase_data['items'] as $item )
		{
			$api->send_request( 'storage/'.$item['id'], 'DELETE' );	
		}
	}
	
	public function synchronization_type_prices( $showcase )
	{
		$prices = usam_get_prices();
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		$api = new USAM_Showcase_API( $showcase );
		$showcase_data = $api->get_type_prices();
		foreach( $showcase_data['items'] as $k => $price )
		{
			foreach( $prices as $k2 => $price2 )
			{		
				if( $price2['code'] === $price['code'] )
				{							
					unset($prices[$k2]);
					unset($showcase_data['items'][$k]);
				}
			}
		}			
		foreach( $prices as $price )
		{
			$price['available'] = 0;
			$uuid = $api->send_request( 'type_price', 'POST', $price );
		}
		foreach( $showcase_data['items'] as $item )
		{
			$uuid = $api->send_request('type_price/'.$item['id'], 'DELETE' );
		}
	}
	
	public function synchronization_terms( $showcase )
	{ 
		$terms = get_terms(['hide_empty' => 0, 'update_term_meta_cache' => true]);			
		$a = ["usam-category" => "categories", "usam-brands" => "brands", 'usam-variation' => "variations", 'usam-product_attributes' => "product_attributes", 'usam-catalog' => "catalogs", 'usam-selection' => "selections", "product_tag" => "product_tags"];
		$t = [];
		foreach( $terms as $term )
		{
			$t[$term->taxonomy][] = $term;
		}			
		require_once(USAM_FILE_PATH.'/includes/exchange/showcase-api.class.php');
		$api = new USAM_Showcase_API( $showcase );
		foreach( $t as $taxonomy => $terms )				
		{
			$all_terms = $terms;
			if( !isset($a[$taxonomy]) )
				continue;		
			
			$data = [];						
			$showcase_data = $api->send_request( $a[$taxonomy], 'GET', ['count' => 10000, 'status' => 'all'] );		
			if( $showcase_data === false )
				continue;				
		
			if( !empty($showcase_data) )
			{				
				foreach( $terms as $k2 => $item2 )
				{								
					$code = (int)usam_get_term_metadata( $item2->term_id, 'code_showcase_'.$showcase->id );
					foreach( $showcase_data['items'] as $k => $item )
					{				
						if( ($code == $item['term_id'] || $item['name'] === $item2->name) && $item['taxonomy'] === $item2->taxonomy )
						{
							if( $code != $item['term_id'] )
								usam_update_term_metadata( $item2->term_id, 'code_showcase_'.$showcase->id, (int)$item['term_id'] );
																					
							$update = (array)$item2;
							$update['term_id'] = $item['term_id'];
							unset($update['term_group']);
							unset($update['term_taxonomy_id']);			
							$metas = usam_get_term_metadata( $item2->term_id );
							if( $metas )
							{
								foreach( $metas as $meta )
								{
									if( !$meta->meta_key !== 'code_showcase_'.$showcase->id )
										$update[$meta->meta_key] = maybe_unserialize( $meta->meta_value );
								}
							}								
							unset($terms[$k2]);
							unset($showcase_data['items'][$k]);			
							$data[] = $update;
							break;
						}
					}
				}
			}	
			if( !empty($showcase_data['items']) )
			{				
				do
				{
					$data = [];
					foreach( $showcase_data['items'] as $k => $item )
					{
						$data[] = $item['term_id'];
						unset($showcase_data['items'][$k]);	
						if( $k > 1000 )
							break;
					}
					$api->send_request( $a[$taxonomy], 'DELETE', ['include' => $data] );
				}
				while( !empty($showcase_data['items']) );
			}	
			if( $terms )
			{								
				do
				{
					$data = [];			
					$ids = [];						
					foreach( $terms as $i => $item )
					{			
						$ids[] = $item->term_id;
						$insert = (array)$item;					
						unset($insert['term_id']);
						unset($insert['parent']);
						unset($insert['term_group']);
						unset($insert['term_taxonomy_id']);		
						$metas = usam_get_term_metadata( $item->term_id );					
						if( $metas )
						{
							foreach( $metas as $meta )
								$insert[$meta->meta_key] = maybe_unserialize( $meta->meta_value );
						}
						$data[] = $insert;
						unset($terms[$i]);	
						if( $i > 1000 )
							break;
					}	
					$uuid = $api->send_request( $a[$taxonomy], 'PUT', ['items' => $data] );
					if ( $uuid )
					{			
						foreach( $ids as $k => $term_id )
						{
							if( !empty($uuid[$k]) )						
								usam_update_term_metadata( $term_id, 'code_showcase_'.$showcase->id, (int)$uuid[$k]['term_id'] );
						}
					}					
				}
				while( !empty($terms) );				
			}			
			$data = [];
			foreach( $all_terms as $k => $item )
			{								
				$parent = $item->parent ? (int)usam_get_term_metadata( $item->parent, 'code_showcase_'.$showcase->id ) : 0;
				$code = (int)usam_get_term_metadata( $item->term_id, 'code_showcase_'.$showcase->id );
				$data[] = ['term_id' => $code, 'parent_id' => $parent];
			}
			if( $data )			
				$api->send_request( $a[$taxonomy], 'PUT', ['items' => $data] );			
		}		
	}
}
?>