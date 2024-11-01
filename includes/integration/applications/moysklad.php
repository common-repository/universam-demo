<?php
/*
	Name: Мой склад
	Description: Обмен товарами и заказами
	Group: storage
	Price: paid
	Icon: moysklad
*/
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_Moysklad extends USAM_Application
{	
	protected $API_URL = "https://api.moysklad.ru/api/remap/1.2";
	protected $expiration = 432000; //DAY_IN_SECONDS
	protected $limit = 50; 
	
	public function get_url( $function )
	{ 
		if ( stripos($function, $this->API_URL) === false )
			return "{$this->API_URL}/{$function}";
		else
			return $function;
	}
	
	private function get_metadata( $meta )
	{ 
		$meta['mediaType'] = 'application/json';
		return ['meta' => $meta];
	}
	
	public function update_products( $query_vars = [] )
	{ 
		$query_vars['posts_per_page'] = 10;
		$query_vars['orderby'] = 'ID';
		$query_vars['order'] = 'ASC';
		if ( !isset($query_vars['post_status']) )
			$query_vars['post_status'] = 'all';
		
		$products = usam_get_products( $query_vars );			
		$i = 0;
		$params = [];
		foreach( $products as $key => $product )		
		{			
			$insert[] = $this->get_moysklad_product( $product );
			$code_moysklad = usam_get_product_meta( $product->ID, 'code_moysklad' );
			if ( !empty($code_moysklad) )
				$insert = array_merge($insert, $this->get_metadata(['href' => $this->get_url('entity/product/'.$code_moysklad), 'metadataHref' => $this->get_url('entity/product/metadata'), 'type' => 'product']));
			$params[] =	$insert;		
			$i++;
		}
		if ( $params )
		{
			$args = $this->get_args( 'POST', $params );
			$results = $this->send_request( "entity/product", $args );	
			if ( $results )
			{ 
				foreach( $results as $result )
				{		
					if ( !empty($result['code']) )
						usam_update_product_meta($result['code'], 'code_moysklad', $result['id'] );	
				}				
			}	
		} 	
		return $i;		
	}		
	
	private function get_moysklad_product( $product )
	{ 
		if ( is_numeric($product) )
			$product = get_post( $product );
		static $prices = null;
		if ( $prices === null )
			$prices = usam_get_prices(['type' => 'all']);
		$sku = usam_get_product_meta( $product->ID, 'sku' );						
		$barcode = usam_get_product_meta( $product->ID, 'barcode' );
		$barcodes['ean13'] = $barcode;
		//Закупочная цена	
		$salePrices = [];
		$min_selling_price = get_option('usam_min_selling_price_product');
		foreach( $prices as $type_price )	
		{
			$price = usam_get_product_price( $product->ID, $type_price['code'] )*100;
			$currency = usam_get_currency( $type_price['currency'] );
			if ( !$currency['external_code'] )
				$this->update_currencies();
			if ( empty($type_price['code_moysklad']) )
				$this->update_type_prices();
			if ( $type_price['type'] == "R" )
			{				
				$salePrices[] = ["value" => (float)$price, 'currency' => $this->get_metadata(['href' => $this->get_url('entity/currency/'.$currency['external_code']), 'metadataHref' => $this->get_url('entity/currency/metadata'), 'type' => 'currency']), 'priceType' => $this->get_metadata(['href' => $this->get_url('context/companysettings/pricetype/'.$type_price['code_moysklad']), 'type' => 'pricetype'])];				
			}
			elseif ( $price )
			{
				$purchase = ["value" => (float)$price, 'currency' => $this->get_metadata(['href' => $this->get_url('entity/currency/'.$currency['external_code']), 'metadataHref' => $this->get_url('entity/currency/metadata'), 'type' => 'currency'])];
			}	
			if ( $min_selling_price == $type_price['code'] )
				$minprice = ["value" => (float)$price, 'currency' => $this->get_metadata(['href' => $this->get_url('entity/currency/'.$currency['external_code']), 'metadataHref' => $this->get_url('entity/currency/metadata'), 'type' => 'currency'])];			
		}					
		$insert = ['name' => (string)$product->post_title, /*'description' => $product->post_excerpt,*/ 'article' => (string)$sku, 'code' => (string)$product->ID];
		$volume = (int)usam_get_product_volume( $product->ID ); 
		if ( $volume > 0 )
			$insert['volume'] = $volume;
		
		$weight = (int)usam_get_product_weight( $product->ID ); 
		if ( $weight > 0 )
			$insert['weight'] = $weight;		
		
		$unit_external_code = usam_get_product_unit_name($product->ID, 'external_code'); 
		if ( !$unit_external_code )
		{
			$this->update_units();
			$unit_external_code = usam_get_product_unit_name($product->ID, 'external_code'); 
		}
		$insert['uom'] = $this->get_metadata(['href' => $this->get_url('entity/uom/'.$unit_external_code), 'metadataHref' => $this->get_url('entity/uom/metadata'), 'type' => 'uom']);			
		$size = 0;
		$attachments = usam_get_product_images( $product->ID, 5 );
		if ( $attachments )
		{
			foreach( $attachments as $attachment )
			{
				$filepath = get_attached_file($attachment->ID); 
				$filesize = filesize($filepath);
				$size += $filesize;
				if ( $size < 1000000 )
				{
					$imagedata = file_get_contents($filepath); 
					$insert['images'][] = ['filename' => basename($filepath), "content" => base64_encode($imagedata) ];
				}
			}
		}			
		$terms = get_the_terms( $product->ID , 'usam-category' ); 
		if ( $terms )
		{
			foreach( $terms as $category )
			{
				$external_code = usam_get_term_metadata($category->term_id, 'code_moysklad');
				if ( !$external_code )
				{
					$term = get_term( $category->term_id, 'usam-category' );	
					$params = ['name' => $term->name];				
					$parent_external_code = usam_get_term_metadata($term->parent, 'code_moysklad');
					if ( $parent_external_code )
					{
						$params['productFolder'] = $this->get_metadata(['href' => $this->get_url('entity/productfolder/'.$parent_external_code), 'type' => 'productfolder', 'metadataHref' => $this->get_url('entity/productfolder/metadata')]);			
					}
					else
						$params['productFolder'] = [];		
					$args = $this->get_args( 'POST', $params ); 			
					$result = $this->send_request( "entity/productfolder/", $args );
					if ( isset($result['id']) )
					{
						$external_code = $result['id'];
						usam_update_term_metadata( $category->term_id, 'code_moysklad', $result['id'] );
					}
				}
				if ( $external_code )
					$insert['productFolder'] = $this->get_metadata(['href' => $this->get_url('entity/productfolder/'.$external_code), 'type' => 'productfolder', 'metadataHref' => $this->get_url('entity/productfolder/metadata')]);
			}
		}
	//	if ( !empty($minprice) )
	//		$insert['minPrice'] = $minprice;			
		if ( !empty($purchase) )
			$insert['buyPrice'] = $purchase;
		if ( !empty($salePrices) )
			$insert['salePrices'] = $salePrices;	
//		$insert['syncId'] = (string)$product->ID;	
		$contractor = usam_get_product_meta($product->ID, 'contractor');
		if ( $contractor )
		{
			$external_code = usam_get_company_metadata( $contractor, 'code_moysklad');
			if ( $external_code )
			{
				$insert['supplier'] = $this->get_metadata(['href' => $this->get_url('entity/counterparty/'.$external_code), 'metadataHref' => $this->get_url('entity/counterparty/metadata'), 'type' => 'counterparty']);
			}				
		}			
		if ( $product->post_status == 'archive' )
			$insert['archived'] = true;	
		return $insert;
	}
	
	function edit_product($product_id, $post_data, $attributes)
	{			
		static $count = 0;
		$count++;
		$product = $this->get_moysklad_product( $product_id );	 
		$code_moysklad = usam_get_product_meta( $product_id, 'code_moysklad' );	
		if ( $code_moysklad )
		{
			$args = $this->get_args( 'PUT', $product );
			$result = $this->send_request( "entity/product/$code_moysklad", $args );		
		}
		else
		{
			$args = $this->get_args( 'POST', $product );
			$result = $this->send_request( "entity/product", $args );	
			if ( $result )
				usam_update_product_meta($product_id, 'code_moysklad', $result['id'] );	
		}
		if( $count % 5 == 0 )
			sleep(1);
	}
	
	function edit_product_prices($product_id, $post_data)
	{			
		static $count = 0;
		$count++;
		$product = $this->get_moysklad_product( $product_id );	
		$code_moysklad = usam_get_product_meta( $product_id, 'code_moysklad' );	
		if ( $code_moysklad )
		{
			$args = $this->get_args( 'PUT', $product );
			$result = $this->send_request( "entity/product/$code_moysklad", $args );
			if( $count % 5 == 0 )
				sleep(1);
		}		
	}
		
	public function delete_product( $product_id )
	{
		$code_moysklad = usam_get_product_meta( $product_id, 'code_moysklad' );
		$args = $this->get_args( 'DELETE' );
		$results = $this->send_request( "entity/product/".$code_moysklad, $args );		
	}
	
	//Обновление единиц измерения
	public function update_units( )
	{ 
		$moysklad = $this->get_units( );
		if ( $moysklad !== false )
		{
			$units = usam_get_list_units( );		
			$params = [];	
			$update = [];			
			foreach( $units as $unit )
			{			
				$update[$unit['code']] = $unit;	
				foreach( $moysklad['rows'] as $data )
				{								
					if ( $unit['external_code'] == $data['id'] || isset($unit['numerical']) && isset($data['code']) && $data['code'] == $unit['numerical'] || isset($unit['description']) && $data['description'] == $unit['title'] )
					{					
						$unit['external_code'] = $data['id'];		
						usam_edit_data($unit, $unit['id'], 'usam_units_measure', false );
						continue 2;
					}							
				}
				$numerical = isset($unit['numerical']) ? (string)$unit['numerical'] : '';
				$params[] = ['name' => $unit['title'], 'code' => $numerical, 'externalCode' => $unit['id']]; 
			}		
			if ( $params )
			{ 
				$args = $this->get_args( 'POST', $params );
				$results = $this->send_request( "entity/uom", $args );
				if ( $results )
				{
					foreach( $results as $result )
					{					
						if ( !empty($result['externalCode']) )
						{
							$unit = $update[$result['externalCode']];							
							$unit['external_code'] = $result['id'];							
							usam_edit_data($unit, $unit['id'], 'usam_units_measure', false );	
						}
					}
				}				
			}
		}
	}	
		
	public function update_currencies( )
	{ 
		$moysklad = $this->get_currencies( );
		if ( $moysklad !== false )
		{
			$currencies = usam_get_currencies( );		
			$params = [];
			foreach( $currencies as $currency )
			{		
				foreach( $moysklad['rows'] as $key => $data )
				{
					if ( isset($data['isoCode']) && $data['isoCode'] == $currency->code )
					{
						if ( $currency->external_code != $data['id'] )
							usam_update_currency($currency->code, ['external_code' => $data['id']] );
						unset($moysklad['rows'][$key]);
						continue 2;
					}
				}
				$name = $currency->symbol?$currency->symbol:$currency->name;
				$params[] = ['name' => $name, 'fullName' => $currency->name, 'code' => $currency->numerical, 'isoCode' => $currency->code]; 
			}		
			if ( $params )
			{
				$args = $this->get_args( 'POST', $params );
				$results = $this->send_request( "entity/currency", $args );	
				if ( $results )
				{					
					foreach( $results as $result )
					{					
						if ( !empty($result['isoCode']) )
							usam_update_currency($result['isoCode'], ['external_code' => $result['id']]);
					}				
				}				
			}
		}
	}
	
	public function currency_insert( $t )
	{ 
		$data = $t->get_data();		
		$name = $data['symbol']?$data['symbol']:$data['name'];
		$insert = ['name' => $name, 'fullName' => $data['name'], 'code' => $data['numerical'], 'isoCode' => $data['code']]; 
		$args = $this->get_args( 'POST', $insert );
		$results = $this->send_request( "entity/currency/", $args );
		if ( isset($results['id']) )
			usam_update_currency($data['code'], ['external_code' => $results['id']]);		
	}	
	
	public function currency_update( $t )
	{ 
		$data = $t->get_data();		
		$insert = ['name' => $name, 'fullName' => $data['name'], 'isoCode' => $data['code']]; 
		$args = $this->get_args( 'PUT', $insert );
		$results = $this->send_request( "entity/currency/".$data['external_code'], $args );
	}		
	
	public function currency_delete( $data )
	{ 		
		$args = $this->get_args( 'DELETE' );
		$results = $this->send_request( "entity/currency/".$data['external_code'], $args );	
	}
	
	public function update_variants( )
	{
		$moysklad = $this->get_variants();
		$terms = get_terms(['taxonomy' => 'usam-variation', 'hide_empty' => 0]);	
		if ( $terms )
		{
			$params = [];
			$codes = [];
			foreach( $terms as $term )
			{			
				if ( $term->parent == 0 )					
					continue;
				$external_code = usam_get_term_metadata($term->term_id, 'code_moysklad');
				$insert = ['name' => $term->name];
				$codes[$external_code] = $term->term_id;			
				if ( $external_code )
				{					
					$insert['id'] = $external_code;			
					$insert = array_merge($insert, $this->get_metadata(['href' => $this->get_url('entity/variant/metadata/characteristics/'.$external_code), 'type' => 'attributemetadata']));
					foreach( $moysklad['characteristics'] as $key => $data )
					{							
						if ( $data['id'] == $external_code )		
						{
							unset($moysklad['characteristics'][$key]);
							continue 2; //Начать цикл terms с нового элемента
						}
					}
				}			
				foreach( $moysklad['characteristics'] as $key => $data )
				{	
					if ( $data['name'] == $term->name )							
					{								
						usam_update_term_metadata( $term->term_id, 'code_moysklad', $data['id'] );
						unset($moysklad['characteristics'][$key]);
						continue 2; //Начать цикл terms с нового элемента
					}
				} 
				$params[] = $insert;
			}
		}					
		if ( !empty($moysklad['characteristics']) )
		{
			foreach( $moysklad['characteristics'] as $key => $data )
			{
				$term = wp_insert_term($data['name'], 'usam-variation', ['parent' => 0]);	
				if ( !is_wp_error($term) ) 	
					usam_update_term_metadata( $term['term_id'], 'code_moysklad', $data['id'] );
			}			
		}
	}
			
	public function update_type_prices( )
	{ 
		$moysklad = $this->get_type_prices( );
		if ( $moysklad !== false )
		{			
			$type_prices = usam_get_prices(['type' => 'all', 'orderby' => 'id', 'order' => 'ASC']);
			$params = [];
			$update = [];		
			foreach( $type_prices as $type_price )
			{
				$update[$type_price['code']] = $type_price;	
				$insert = ['name' => $type_price['title'], 'externalCode' => $type_price['code']];
				foreach( $moysklad as $key => $data )
				{				
					if ( !empty($type_price['code_moysklad']) && $type_price['code_moysklad'] == $data['id'] )
					{
						$insert = array_merge($insert, $this->get_metadata(['href' => $this->get_url('context/companysettings/pricetype/'.$data['id']), 'type' => 'pricetype']));
						unset($moysklad[$key]);				
						break;
					}
					elseif ( $type_price['title'] == $data['name'] )
					{
						$insert = array_merge($insert, $this->get_metadata(['href' => $this->get_url('context/companysettings/pricetype/'.$data['id']), 'type' => 'pricetype']));						
						$type_price['code_moysklad'] = $data['id'];							
						usam_edit_data($type_price, $type_price['id'], 'usam_type_prices');	
						unset($moysklad[$key]);	
						break;
					}
				}
				$params[] = $insert; 
			} 						
			foreach( $moysklad as $key => $data )
			{							
				$params[] = $data; 				
				$id = usam_insert_type_price(['code_moysklad' => $data['id'], 'title' => $data['name']]);				
			}		
			if ( $params )
			{ 
				$args = $this->get_args('POST', $params);
				$results = $this->send_request( "context/companysettings/pricetype", $args );	
				if ( $results )
				{
					foreach( $results as $result )
					{						
						if ( !empty($result['externalCode']) && isset($update[$result['externalCode']]) )
						{							
							$type_price = $update[$result['externalCode']];							
							$type_price['code_moysklad'] = $result['id'];							
							usam_edit_data($type_price, $type_price['id'], 'usam_type_prices');	
						}
					}
				}				
			}			
		}
	}
	
	public function save_type_price( )
	{ 
		$type_prices = usam_get_prices(['type' => 'all', 'orderby' => 'id', 'order' => 'ASC']);
		$params = [];			
		$update = [];
		foreach( $type_prices as $type_price )
		{
			$insert = ['name' => $type_price['title'], 'externalCode' => $type_price['code']];
			if ( $type_price['code_moysklad'] )
			{
				$insert['id'] = $type_price['code_moysklad'];
				$insert = array_merge($insert, $this->get_metadata(['href' => $this->get_url('context/companysettings/pricetype/'.$type_price['code_moysklad']), 'type' => 'pricetype']));
			}
			else
				$update[$type_price['code']] = $type_price;	
			$params[] = $insert;
		} 
		if ( $params )
		{ 
			$args = $this->get_args('POST', $params);
			$results = $this->send_request( "context/companysettings/pricetype", $args );
			if ( $results )
			{
				foreach( $results as $result )
				{						
					if ( !empty($result['externalCode']) && isset($update[$result['externalCode']]) )
					{							
						$type_price = $update[$result['externalCode']];							
						$type_price['code_moysklad'] = $result['id'];							
						usam_edit_data($type_price, $type_price['id'], 'usam_type_prices');	
					}
				}
			}	
		}
	}	
	
	public function type_price_delete( $id )
	{ 
		$this->save_type_price();
	}
	
	public function type_price_update( $id )
	{ 
		$this->save_type_price();
	}
	
	public function type_price_insert( $id )
	{ 
		$this->save_type_price();
	}
	
	public function update_document_status( $type = 'customerorder' )
	{	
		$args = $this->get_args( );
		$moysklad = $this->send_request( "entity/{$type}/metadata", $args );	
		if ( $moysklad === false )
			return false;
		if ( $type == 'customerorder')
			$object_type = 'order';
		$statuses = usam_get_object_statuses(['type' => $object_type]);	
		$params = [];
		foreach( $statuses as $status )
		{			
			if ( $status->close == 1 )
			{
				if ( $status->internalname == 'closed' )
					$stateType = 'Successful';
				else
					$stateType = 'Unsuccessful';
			}
			else
				$stateType = 'Regular';	
			
			if ( $status->color )
			{
				$rgb = sscanf($status->color, "#%02x%02x%02x");
				$color = $rgb[0].$rgb[1].$rgb[2];
			}
			else
				$color = 15106326;			
			$insert = ['name' => $status->name, 'stateType' => $stateType, 'entityType' => $type, 'color' => (int)$color];			
			foreach( $moysklad['states'] as $key => $data )
			{				
				if ( isset($data['name']) && $data['name'] == $status->name )
				{							
					unset($moysklad['states'][$key]);			
					unset($insert['color']);						
					$insert = array_merge($insert, $this->get_metadata(['href' => $this->get_url('entity/'.$type.'/metadata/states/'.$data['id']), 'type' => 'state', 'metadataHref' => $this->get_url('entity/'.$type.'/metadata')]));
					usam_update_object_status_metadata( $status->id, 'moysklad_id', $data['id'] );			
					break;
				}           
			} 					 
			$params[] = $insert;
		}		 
		if ( $moysklad['states'] )
		{
			foreach( $moysklad['states'] as $key => $data )
			{				
				$args = $this->get_args('DELETE'); 	
				$results = $this->send_request( "entity/{$type}/metadata/states/". $data['id'], $args );				
			} 	
		}		
		if ( $params )
		{		
			$args = $this->get_args( 'POST', $params ); 			
			$results = $this->send_request( "entity/{$type}/metadata/states", $args );					 
			foreach( $results as $key => $data )
			{				
				foreach( $statuses as $status )
				{
					if ( isset($data['name']) && $data['name'] == $status->name )
					{							
						usam_update_object_status_metadata( $status->id, 'moysklad_id', $data['id'] );
						break;
					}
				}				
			} 			
		}		
		return true;		
	}
	
	public function update_stores( )
	{
		$moysklad = $this->get_stores( );
		if ( $moysklad )
		{			
			$storages = usam_get_storages(['cache_meta' => true]);		
			$params = [];
			foreach( $storages as $storage )
			{		
				$code = usam_get_storage_metadata($storage->id, 'code_moysklad');
				$insert = ['name' => $storage->title, 'pathName' => __("Основной склад","usam"), 'code' => (string)$storage->id, 'externalCode' => (string)$storage->id];
				foreach( $moysklad['rows'] as $key => $data )
				{
					if ( isset($data['id']) && $data['id'] == $code )
					{		
						if ( $data['name'] != $storage->title )
						{
							if ( usam_get_application_metadata( $this->id, 'type_update' ) == 'site' )
							{ //Выгрузить обновление в мой склад								
								$insert = array_merge($data, $insert);
								break;
							}
							else
								usam_update_storage($this->id, ['title' => $data['name']]);						
						}
						unset($moysklad['rows'][$key]);
						continue 2;
					}
					elseif ( $data['name'] == $storage->title )
					{								
						usam_update_storage_metadata($storage->id, 'code_moysklad', $data['id']);
						if (  !usam_get_storage_metadata($storage->id, 'address') )
							usam_update_storage_metadata($storage->id, 'address', $data['address']);
						unset($moysklad['rows'][$key]);
						continue 2;
					}
				} 				
				if ( empty($insert['id']) )
				{// если не обновляется склад
					$location = usam_get_location( $storage->location_id );
					$city = isset($location['name'])?$location['name']:'';
					$index = usam_get_storage_metadata( $storage->id, 'index');
					$insert['addressFull'] = ['city' => $city, 'postalCode' => $index];
				}
				$params[] = $insert;  
			}		
			foreach( $moysklad['rows'] as $key => $data )
			{						
				$id = usam_insert_storage(['active' => 1, 'shipping' => 1, 'title' => $data['name']]);	
				usam_update_storage_metadata($id, 'address', $data['address']);
				usam_update_storage_metadata($id, 'code_moysklad', $data['id']);
			}
			if ( $params )
			{			
				$args = $this->get_args( 'POST', $params );
				$results = $this->send_request( "entity/store", $args );	
				if ( $results )
				{				
					foreach( $results as $result )
					{					
						if ( !empty($result['externalCode']) )
							usam_update_storage_metadata($result['externalCode'], 'code_moysklad', $result['id']);
					}				
				}				
			}
		}
	}
	
	public function storage_insert( $t )
	{ 
		$data = $t->get_data();		
		$insert = ['name' => $data['title'], 'pathName' => __("Основной склад","usam"), 'code' => (string)$data['id'], 'externalCode' => (string)$data['id']];  
		$args = $this->get_args( 'POST', $insert );
		$results = $this->send_request( "entity/store/", $args );
		if ( isset($results['id']) )
			usam_update_storage_metadata($id, 'code_moysklad', $results['id']);		
	}	
	
	public function storage_update( $t )
	{ 
		$data = $t->get_data();		
		$code_moysklad = usam_get_storage_metadata($data['id'], 'code_moysklad');	
		$location = usam_get_location( $data['location_id'] );
		$city = isset($location['name'])?$location['name']:'';
		$index = usam_get_storage_metadata( $data['id'], 'index');
		$insert = ['name' => $data['title'], 'pathName' => __("Основной склад","usam"), 'addressFull' => ['city' => $city, 'postalCode' => $index]];  
		$args = $this->get_args( 'PUT', $insert );
		$results = $this->send_request( "entity/store/".$code_moysklad, $args );
	}		
	
	public function storage_delete( $data )
	{ 
		$code_moysklad = usam_get_storage_metadata($data['id'], 'code_moysklad');		
		$args = $this->get_args( 'DELETE' );
		$results = $this->send_request( "entity/store/".$code_moysklad, $args );	
	}
	
	protected function log_errors( $resp, $function )
	{
		if ( isset($resp['errors'] ) ) 
		{		
			foreach( $resp['errors'] as $error )			
				$this->set_error( $error['error'], $function );				
			return true;
		}	
		return false;
	}
	
	public function update_balances( $args )
	{ 		
		$moysklad = $this->get_products_balances(['offset' => ($args['paged'] - 1) *$this->limit, 'limit' => $this->limit, 'stockType' => 'quantity']);
		$count = 0;
		if ( $moysklad )	
		{			
			set_time_limit(1800);
			$ids = usam_get_storages(['fields' => 'id']);	
			$codes = [];
			$stock = [];
			foreach( $ids as $id )
			{
				$code = usam_get_storage_metadata($id, 'code_moysklad');
				if ( !$code )
				{
					$this->update_stores();
					$code = usam_get_storage_metadata($id, 'code_moysklad');
				}
				$codes[$code] = 'storage_'.$id;
				$stock['storage_'.$id] = 0;
			}
			if ( !empty($moysklad['rows']) )
			{
				foreach( $moysklad['rows'] as $data )
				{
					$href = explode('?', $data['meta']['href']);
					$str = explode('/', $href[0]);				
					$code_moysklad = end($str);				
					$product_id = usam_get_product_id_by_meta( 'code_moysklad', $code_moysklad );
					if ( $product_id )
					{
						$product_stock = $stock;
						foreach( $data['stockByStore'] as $store_data )
						{						
							$href = explode('?', $store_data['meta']['href']);
							$str = explode('/', $href[0]);				
							$code_moysklad = end($str);		
							if ( isset($codes[$code_moysklad]) )
							{
								$product_stock[$codes[$code_moysklad]] = $store_data['stock'];
							}					
						}	
						$_product = new USAM_Product( $product_id );
						$_product->set(['product_stock' => $product_stock]);
						$_product->save_stocks();
						usam_update_product_meta( $product_id, 'balance_update', date("Y-m-d H:i:s") );
					}				
				}
			}
			$count = count($moysklad['rows']);
		}
		return $count;
	}
	
//Обновление категорий
	public function update_products_groups( )
	{	
		$moysklad = $this->get_products_groups( );
		if ( $moysklad === false )
			return false;
		
		$terms = get_terms(['taxonomy' => 'usam-category', 'hide_empty' => 0]);	
		if ( $terms )
		{
			$params = [];
			foreach( $terms as $term_key => $term )
			{			
				$external_code = usam_get_term_metadata($term->term_id, 'code_moysklad');
				$insert = ['name' => $term->name, 'code' => $term->slug, 'externalCode' => (string)$term->term_id];			
				foreach( $moysklad['rows'] as $key => $data )
				{				
					if ( isset($data['id']) && $data['id'] == $external_code )
					{							
						unset($moysklad['rows'][$key]);
						if ( $data['name'] == $term->name && isset($data['code']) && $data['code'] == $term->slug )
							continue 2; //если название и код не изменился то обновлять не надо
						else
						{
							if ( usam_get_application_metadata( $this->id, 'type_update' ) == 'site' )
							{
								$insert = array_merge($data, $insert);
								break;
							}
							else
							{
								wp_update_term($term->term_id, 'usam-category', ['name' => $data['name'], 'slug' => $data['code']]);
								continue 2;
							}
						}	
					}
					elseif ( $data['name'] == $term->name )
					{							
						usam_update_term_metadata( $term->term_id, 'code_moysklad', $data['id'] );
						unset($moysklad['rows'][$key]);
						if ( $data['code'] != $term->slug )
						{
							if ( usam_get_application_metadata( $this->id, 'type_update' ) == 'site' )
							{
								$data['code'] = $term->slug;
								$data['externalCode'] = (string)$term->term_id;
								$insert = $data;								
								break;
							}
							else
								wp_update_term($term->term_id, 'usam-category', ['slug' => $data['code']]);
						}
						continue 2;			
					}
				} 				
				$params[] = $insert;
			}
			if ( usam_get_application_metadata( $this->id, 'type_update' ) == 'site' )
			{				
				if ( $params )
				{		
					$args = $this->get_args( 'POST', $params ); 			
					$results = $this->send_request( "entity/productfolder", $args );		
					if ( $results )
					{				
						foreach( $results as $result )
						{				
							if ( !empty($result['externalCode']) )
							{						
								usam_update_term_metadata( $result['externalCode'], 'code_moysklad', $result['id'] );
							}
						}
						unset($results);				
					}			
				}
				//Создание иерархии категорий в Мой склад
				$params = [];		
				foreach( $terms as $key => $term )
				{				
					$insert = [];				
					$parent_external_code = usam_get_term_metadata($term->parent, 'code_moysklad');
					if ( $parent_external_code )
						$insert['productFolder'] = $this->get_metadata(['href' => $this->get_url('entity/productfolder/'.$parent_external_code), 'type' => 'productfolder', 'metadataHref' => $this->get_url('entity/productfolder/metadata')]);
					else
					{
						unset($terms[$key]);
						continue;
					}
					$external_code = usam_get_term_metadata($term->term_id, 'code_moysklad');
					$params[] = array_merge($insert, $this->get_metadata(['href' => $this->get_url('entity/productfolder/'.$external_code), 'type' => 'productfolder', 'metadataHref' => $this->get_url('entity/productfolder/metadata')]));
					unset($terms[$key]);
				}		
				if ( $params )
				{		
					$args = $this->get_args( 'POST', $params ); 			
					$this->send_request( "entity/productfolder", $args );	
				}
			}
		}
		$params = [];		
		if ( $moysklad['rows'] )
		{
			$ids = [];
			foreach( $moysklad['rows'] as $key => $data )
			{				
				$term = wp_insert_term($data['name'], 'usam-category');	
				if ( !is_wp_error($term) ) 	
				{
					usam_update_term_metadata( $term['term_id'], 'code_moysklad', $data['id'] );
					if ( $data['pathName'] )
					{
						$params = $this->get_args( 'GET' );
						$group = $this->send_request( $data['productFolder']['meta']['href'], $params );
						$ids[$term['term_id']] = $group['id'];
					}
				}
			}
			foreach( $ids as $id => $guid)
			{// создать иерархию после добавления
				$term_id = usam_term_id_by_meta('code_moysklad', $guid, 'usam-category');
				$result = wp_update_term($id, 'usam-category', ['parent' => $term_id]);
			}			
		}		
		return true;		
	}
	
	public function delete_category( $term_id, $tt_id, $term, $object_ids )
	{
		$code_moysklad = usam_get_term_metadata($term_id, 'code_moysklad');
		$params = [];
		$params[] = $this->get_metadata(['href' => $this->get_url('entity/productfolder/'.$code_moysklad), 'type' => 'productfolder', 'metadataHref' => $this->get_url('entity/productfolder/metadata')]);
		$args = $this->get_args( 'POST', $params );
		$results = $this->send_request( "entity/productfolder/delete", $args );			
	}
	
	public function insert_category( $term_id, $tt_id )
	{
		$term = get_term( $term_id, 'usam-category' );	
		$params = ['name' => $term->name];				
		$parent_external_code = usam_get_term_metadata($term->parent, 'code_moysklad');
		if ( $parent_external_code )
		{
			$params['productFolder'] = $this->get_metadata(['href' => $this->get_url('entity/productfolder/'.$parent_external_code), 'type' => 'productfolder', 'metadataHref' => $this->get_url('entity/productfolder/metadata')]);			
		}
		else
			$params['productFolder'] = [];		
		$args = $this->get_args( 'POST', $params ); 			
		$result = $this->send_request( "entity/productfolder/", $args );
		return $result['id'];
	}
	
	public function update_category( $term_id, $tt_id )
	{
		static $count = 0;
		$count++;	
			
		$term = get_term( $term_id, 'usam-category' );	
		$params = ['name' => $term->name];				
		$parent_external_code = usam_get_term_metadata($term->parent, 'code_moysklad');
		$external_code = usam_get_term_metadata($term->term_id, 'code_moysklad');		
		if ( $parent_external_code )
		{
			$params['productFolder'] = $this->get_metadata(['href' => $this->get_url('entity/productfolder/'.$parent_external_code), 'type' => 'productfolder', 'metadataHref' => $this->get_url('entity/productfolder/metadata')]);			
		}
		else
			$params['productFolder'] = [];		
		$args = $this->get_args( 'PUT', $params ); 			
		$this->send_request( "entity/productfolder/".$external_code, $args );
		
		if( $count % 5 == 0 )
			sleep(1);
	}
	
	public function get_data_product( $data )
	{ 			
		static $storage_codes = null;		
		if ( $storage_codes === null )
		{
			$storages = usam_get_storages(['cache_meta' => true]);			
			$storage_codes = [];
			foreach( $storages as $storage )
			{
				$code = usam_get_storage_metadata($storage->id, 'code_moysklad');
				$storage_codes[$code] = 'storage_'.$storage->id;			
			}
		}		
		$product = ['post_title' => $data['name'], 'prices' => []];		
		if ( isset($data['archived']) && $data['archived'] )
			$product['post_status'] = 'archive';
		if ( isset($data['description']) )
			$product['post_excerpt'] = $data['description'];			
		$product['productmeta'] = ['code_moysklad' => $data['id'], 'virtual' => 'product'];		
		if ( isset($data['weight']) )
			$product['productmeta']['weight'] = $data['weight'];
		if ( isset($data['volume']) )
			$product['productmeta']['volume'] = $data['volume'];
		if ( isset($data['article']) )
			$product['productmeta']['sku'] = $data['article'];
		
		if ( !empty($data['barcodes']) )
		{
			foreach( $data['barcodes'] as $barcodes )
			{
				if ( isset($barcodes['ean13']) )
				{
					$product['productmeta']['barcode'] = $barcodes['ean13'];
					break;
				}
				elseif ( isset($barcodes['ean8']) )
				{
					$product['productmeta']['barcode'] = $barcodes['ean8'];
					break;
				}
			}
		}
		if ( isset($data['images']) && $data['images']['meta']['size'] > 0 )
		{
			$params = $this->get_args( 'GET' );
			$images = $this->send_request( $data['images']['meta']['href'], $params );	
			if ( !empty($images['rows']) )
			{						
				$product['images'] = [];					
				foreach( $images['rows'] as $image )
					$product['images'][$image['filename']] = ['url' => $image['meta']['downloadHref'], 'title' => $image['title'], 'name' => $image['filename']];
			}
		}
		if ( !empty($data['salePrices']) )
		{
			$prices = usam_get_prices(['type' => 'all']);
			foreach( $data['salePrices'] as $moysklad_price )
			{
				foreach( $prices as $type_price )	
				{						
					if ( $type_price['code_moysklad'] == $moysklad_price['priceType']['id'] )
					{
						$product['prices']['price_'.$type_price['code']] = $moysklad_price['value']/100;
						continue;
					}					
				}
			}
		}	
		$args = $this->get_args( 'GET', ['filter' => 'product='.$data['meta']['href'], 'stockType' => 'quantity'] );
		$stock_results = $this->send_request( "report/stock/bystore", $args );	
		if ( isset($stock_results['rows']) )
		{
			$product_stock = [];
			foreach( $storage_codes as $key => $value )
			{
				$product_stock[$value] = 0;
			}
			if ( !empty($stock_results['rows']) )
			{					
				
				foreach( $stock_results['rows'][0]['stockByStore'] as $store_data )
				{
					$code_moysklad = str_replace("{$this->API_URL}/entity/store/", "", $store_data['meta']['href']);
					if ( isset($storage_codes[$code_moysklad]) )
						$product_stock[$storage_codes[$code_moysklad]] = $store_data['stock'];
				}
			}
			$product['product_stock'] = $product_stock;
		}
		if ( isset($data['uom']) )
		{
			$code_moysklad = str_replace("{$this->API_URL}/entity/uom/", "", $data['uom']['meta']['href']);
			$unit_measure = usam_get_unit_measure( $code_moysklad, 'external_code' );		
			if ( isset($unit_measure['code']) )
				$product['productmeta']['unit_measure'] = $unit_measure['code'];
		}			
		if ( isset($data['supplier']) )
		{
			$code_moysklad = str_replace("{$this->API_URL}/entity/counterparty/", "", $data['supplier']['meta']['href']);
			$company_id = usam_get_company_id_by_meta('code_moysklad', $code_moysklad);
			if ( !$company_id )
				$company_id = $this->add_company_to_website( $code_moysklad, 'contractor' );
			$product['productmeta']['contractor'] = $company_id;
		}				
		if ( isset($data['productFolder']) )
		{
			$params = $this->get_args( 'GET' );
			$group = $this->send_request( $data['productFolder']['meta']['href'], $params );
			if ( isset($group['id']) )
			{
				$term_id = usam_term_id_by_meta('code_moysklad', $group['id'], 'usam-category');
				if ( $term_id )
					$product['tax_input'] = ['usam-category' => [$term_id]];	
			}	
		}			
		$attributes = [];		
		if ( isset($data['country']) )
		{
			$params = $this->get_args( 'GET' );
			$country = $this->send_request( $data['country']['meta']['href'], $params );
			if ( isset($country['name']) )
				$attributes['country'] = $country['name'];
		}	
		if ( isset($data['code']) )
			$attributes['code'] = $data['code'];
	/*	if ( !empty($data['attributes']) )
		{				
			foreach( $data['attributes'] as $attribute )
			{
				$term_id = usam_term_id_by_meta('code_moysklad', $attribute['id'], 'usam-product_attributes');
				$term = get_term( $term_id, 'usam-product_attributes' );
				if ( $attribute['type'] == 'boolean' )
					$attributes[$term->slug] = (bool)$attribute['value'];
				else
					$attributes[$term->slug] = $attribute['value'];
			}
		}*/			
		return ['product' => $product, 'attributes' => $attributes];
	}	

	public function set_product_images( $product_id, $images )
	{
		$post_attachments = usam_get_product_images( $product_id );		
		wp_cache_delete($product_id, "usam_product_images");
		$attachment_ids = [];	
		$images_sort = $images;			
		foreach ($post_attachments as $post_attachment) 
		{
			$post_attachment_path = get_attached_file($post_attachment->ID, true);	
			if ( file_exists($post_attachment_path) ) 
			{
				$post_attachment_hash = basename($post_attachment_path);				
				if ( isset($images[$post_attachment_hash]) )
				{		
					unset($images[$post_attachment_hash]);	
					$attachment_ids[$post_attachment_hash] = $post_attachment->ID;
					continue;
				}				
			}			
			wp_delete_attachment($post_attachment->ID, true); 
		}
		require_once( ABSPATH . 'wp-admin/includes/file.php');	
		require_once( ABSPATH . 'wp-admin/includes/media.php');					
		require_once( ABSPATH . 'wp-admin/includes/image.php');				
		$headers = ["Authorization:Bearer {$this->token}", "Accept-Encoding: gzip"]; 		
		foreach( $images as $image )
		{							
			$tmp = $this->download_url( $image['url'], $headers );
			if( !is_wp_error( $tmp ) ) 
			{		
				$image['name'] = trim($image['name']);
				$image['title'] = trim($image['title']);
				$attachment_id = media_handle_sideload(['name' => $image['name'], 'tmp_name' => $tmp], 0, $image['title'] );	
				if( !is_wp_error($attachment_id) ) 
					$attachment_ids[$image['name']] = $attachment_id;
			}
			else
				$this->set_error( $tmp );	
			sleep(1);
		}	
		$thumbnail = true;
		foreach( $images_sort as $menu_order => $image )
		{
			if ( isset($attachment_ids[$image['name']]) )
			{
				wp_update_post(['ID' => $attachment_ids[$image['name']], 'post_parent' => $product_id, 'menu_order' => $menu_order]);
				if ( $thumbnail )
				{
					$thumbnail = false;
					$thumbnail_id = get_post_thumbnail_id( $product_id );				
					if ( $thumbnail_id != $attachment_ids[$image['name']] )
						set_post_thumbnail( $product_id, $attachment_ids[$image['name']] );
				}
			}
		}		
	}
	
	public function set_product( $data, $type = 'product' )
	{
		$product = $this->get_data_product( $data );
		extract( $product );
		
		$product['productmeta']['virtual'] = $type;		
		if ( empty($data['id']) )
			return false;
		
		$product_id = usam_get_product_id_by_meta('code_moysklad', $data['id']);						
		if ( empty($product['post_status']) )
		{
			$post_status = usam_get_application_metadata( $this->id, 'post_status' );
			$product['post_status'] = $post_status ? $post_status :'draft';
		}	
		if ( $product_id )
		{			
			$_product = new USAM_Product( $product_id );		
			$_product->set( $product );				
			$_product->update_product( $attributes );	
		}
		elseif ( !empty($product['post_title']) )
		{			
			$_product = new USAM_Product( $product );	
			$product_id = $_product->insert_product( $attributes );	
		}			
		if ( $product_id )		
		{
			if ( !empty($product['images']) )
					$images = $this->set_product_images( $product_id, $product['images'] );	
			if ( !empty($data['variantsCount']) )		
			{							
				$combination = $this->moysklad_product_variants_loading( $product_id, $data['id'] );
				if ( $combination )
				{
					wp_set_object_terms( $product_id, $combination, 'usam-variation');				
					wp_set_object_terms( $product_id, 'variable', 'usam-product_type' );	
				}
			}	
			if ( !empty($data['useParentVat']) )		
			{							
				$args = $this->get_args();
				$results = $this->send_request( "entity/bundle/".$data['id']."/components", $args );				
				if ( $results )
				{
					foreach( $results['rows'] as $row )
					{
						$id = usam_get_product_id_by_meta('code_moysklad', $row['id']);
						if ( $id )
							wp_update_post(['ID' => $id, 'post_parent' => $product_id]); 	
					}
				}
			}	
		}
		return $product_id;
	}
			
	public function moysklad_products_loading( $args )
	{ 
		$moysklad = $this->get_products(['offset' => ($args['paged'] - 1) *$this->limit, 'limit' => $this->limit, 'filter' => 'archived=false']);
		if ( !$moysklad )			
			return 0;

		remove_action('usam_edit_product', [&$this, 'edit_product'], 10, 3);	
		remove_action('usam_insert_product', [&$this, 'edit_product'], 10, 3);				
		remove_action('usam_update_product', [&$this, 'edit_product'], 10, 3);	
		remove_action('usam_edit_product_prices', [&$this, 'edit_product_prices'], 10, 2);	
			
		usam_start_import_products();
			
		$codes = [];
		foreach( $moysklad['rows'] as $key => $data )
		{
			if ( $data['archived'] )
			{
				unset($moysklad['rows'][$key]);
				continue;	
			}
			$codes[] = $data['id'];			
		}	
		usam_get_product_ids_by_code( $codes, 'code_moysklad' );		
		foreach( $moysklad['rows'] as $key => $data )
		{					
			$product_id = $this->set_product( $data );
			unset($moysklad['rows'][$key]);
			sleep(1);			
		}				
		usam_end_import_products();		
		return count($moysklad['rows']);
	}		
	
	public function moysklad_product_variants_loading( $product_id, $moysklad_product_id )
	{ 		
		$moysklad = $this->get_product_variants(['filter' => 'productid='.$moysklad_product_id ]);
		if ( !$moysklad )			
			return 0;
			
		$codes = [];
		foreach( $moysklad['rows'] as $data )
		{			
			$codes[] = $data['id'];			
		}	
		usam_get_product_ids_by_code( $codes, 'code_moysklad' );		
		$results = [];
		foreach( $moysklad['rows'] as $data )
		{	
			$product = $this->get_data_product( $data );
			extract( $product );
			$combination = [];
			foreach( $data['characteristics'] as $variant )
			{
				$term_id = usam_term_id_by_meta('code_moysklad', $variant['id'], 'usam-variation');
				if ( $term_id )
				{										
					$term = get_term_by( 'name', $variant['value'], 'usam-variation' );
					if ( $term )	
						$combination[] = $term->term_id;
					else
					{						
						$term = wp_insert_term($variant['value'], 'usam-variation', ['parent' => $term_id]);
						if( !is_wp_error($term) )						
							$combination[] = $term['term_id'];
						else
							continue;
					}	
					$combination[] = $term_id;						
				}
			}
			$variation_product_id = usam_get_product_id_by_meta('code_moysklad', $data['id']);
			$product['post_parent'] = $product_id;	
			$product['product_type'] = 'variation';
			$product['post_status'] = 'publish';
			if ( $variation_product_id )
			{				
				$_product = new USAM_Product( $variation_product_id );
				$_product->set( $product );
				$_product->update_product( $attributes );				
			}
			elseif ( !empty($product['post_title']) )
			{	
				$_product_v = new USAM_Product( $product );			
				$variation_product_id = $_product_v->insert_product( $attributes );								
			}			
			if( $variation_product_id )
			{
				$combination = array_map('intval', $combination );
				wp_set_object_terms($variation_product_id, $combination, 'usam-variation');		
				if ( !empty($product['images']) )
					$images = $this->set_product_images( $product_id, $product['images'] );
			}
			$results = array_merge( $results, $combination );
		}
		return $results;
	}		
			
	public function moysklad_sets_loading( $args )
	{ 		
		$moysklad = $this->get_sets(['offset' => ($args['paged'] - 1) *$this->limit, 'limit' => $this->limit, 'filter' => 'archived=false']);
		if ( !$moysklad )			
			return 0;

		remove_action('usam_edit_product', [&$this, 'edit_product'], 10, 3);	
		remove_action('usam_insert_product', [&$this, 'edit_product'], 10, 3);				
		remove_action('usam_update_product', [&$this, 'edit_product'], 10, 3);	
		remove_action('usam_edit_product_prices', [&$this, 'edit_product_prices'], 10, 2);
		
		$codes = [];
		foreach( $moysklad['rows'] as $key => $data )
		{
			if ( $data['archived'] )
			{
				unset($moysklad['rows'][$key]);
				continue;	
			}
			$codes[] = $data['id'];			
		}	
		usam_get_product_ids_by_code( $codes, 'code_moysklad' );		
		foreach( $moysklad['rows'] as $data )
		{					
			$product_id = $this->set_product( $data );
			sleep(1);			
		}		
		return count($moysklad['rows']);
	}		
	
	public function moysklad_service_loading( $args )
	{ 
		$moysklad = $this->get_service(['offset' => ($args['paged'] - 1) *$this->limit, 'limit' => $this->limit, 'filter' => 'archived=false']);
		if ( !$moysklad )			
			return 0;

		remove_action('usam_edit_product', [&$this, 'edit_product'], 10, 3);	
		remove_action('usam_insert_product', [&$this, 'edit_product'], 10, 3);				
		remove_action('usam_update_product', [&$this, 'edit_product'], 10, 3);	
		remove_action('usam_edit_product_prices', [&$this, 'edit_product_prices'], 10, 2);
		
		$codes = [];
		foreach( $moysklad['rows'] as $key => $data )
		{
			if ( $data['archived'] )
			{
				unset($moysklad['rows'][$key]);
				continue;	
			}
			$codes[] = $data['id'];			
		}	
		usam_get_product_ids_by_code( $codes, 'code_moysklad' );		
		foreach( $moysklad['rows'] as $data )
		{					
			$product_id = $this->set_product( $data, 'service' );
			sleep(1);			
		}		
		return count($moysklad['rows']);
	}

	public function get_properties_company( )
	{	
		return ['company_name' => 'legalTitle', 'full_company_name' => 'legalTitle','ppc' => 'kpp', 'inn' => 'inn', 'ogrn' => 'ogrn', 'okpo' => 'okpo', 'email' => 'email', 'phone' => 'phone', 'gm' => 'director', 'accountant' => 'chiefAccountant'];
	}

	public function get_company_by_moysklad( $company )
	{	
		if ( !is_array($company) )
			$company = usam_get_company( $company );
		$insert = ['companyType' => 'legal', 'name' => $company['name'], 'externalCode' => (string)$company['id']];		
		foreach( $this->get_properties_company() as $meta_key => $key)
		{
			$meta_value = usam_get_company_metadata( $company['id'], $meta_key);
			if ( $meta_value )
				$insert[$key] = (string)$meta_value;
		}				
		$date_registration = usam_get_company_metadata( $company['id'], 'date_registration');
		if ( $date_registration )
			$insert['certificateDate'] = date( "Y-m-d H:i:s",strtotime($date_registration));
		$location_id = usam_get_company_metadata( $company['id'], 'legallocation');
		if ( $location_id )
		{
			$locations = usam_get_address_locations( $location_id, 'code=>data' );	
			if ( $locations )
			{
				if ( !empty($locations['city']) )
					$insert['legalAddressFull']['city'] = $locations['city']->name;
				
				$external_code = usam_get_location_metadata($locations['country']->id, 'moysklad');
				if ( $external_code )
					$insert['legalAddressFull']['country'] = $this->get_metadata(['href' => $this->get_url('entity/country/'.$external_code), 'type' => 'country', 'metadataHref' => $this->get_url('entity/entity/country')]);
				$external_code = usam_get_location_metadata($locations['region']->id, 'moysklad');
				if ( $external_code )
					$insert['legalAddressFull']['region'] = $this->get_metadata(['href' => $this->get_url('entity/region/'.$external_code), 'type' => 'region', 'metadataHref' => $this->get_url('entity/entity/region')]);						
			}
			$insert['legalAddressFull']['postalCode'] = (string)usam_get_company_metadata( $company['id'], 'legalpostcode');
			$insert['legalAddressFull']['street'] = (string)usam_get_company_metadata( $company['id'], 'legaladdress');
		//	$insert['legalAddressFull']['addInfo'] = 'addInfo';					
		}
		$location_id = usam_get_company_metadata( $company['id'], 'contactlocation');
		if ( $location_id )
		{
			$locations = usam_get_address_locations( $location_id, 'code=>data' );	
			if ( $locations )
			{
				if ( !empty($locations['city']) )
					$insert['actualAddressFull']['city'] = $locations['city']->name;
				$external_code = usam_get_location_metadata($locations['country']->id, 'moysklad');
				if ( $external_code )
					$insert['actualAddressFull']['country'] = $this->get_metadata(['href' => $this->get_url('entity/country/'.$external_code), 'type' => 'country', 'metadataHref' => $this->get_url('entity/entity/country')]);
				$external_code = usam_get_location_metadata($locations['region']->id, 'moysklad');
				if ( $external_code )
					$insert['actualAddressFull']['region'] = $this->get_metadata(['href' => $this->get_url('entity/region/'.$external_code), 'type' => 'region', 'metadataHref' => $this->get_url('entity/entity/region')]);
			}
			$insert['actualAddressFull']['postalCode'] = (string)usam_get_company_metadata( $company['id'], 'contactpostcode');
			$insert['actualAddressFull']['street'] = (string)usam_get_company_metadata( $company['id'], 'contactaddress');
		//	$insert['actualAddressFull']['addInfo'] = 'addInfo';					
		}
		return $insert;
	}
	
	public function get_data_company( $data )
	{
		$company = ['name' => $data['name']];
		$metas = [];		
		foreach( $this->get_properties_company() as $meta_key => $key)
		{						
			if ( !empty($data[$key]) )
				$metas[$meta_key] = $data[$key];
		}
		if ( !empty($data['certificateDate']) )
			$metas['date_registration'] = $data['certificateDate'];
		if ( !empty($data['legalAddressFull']) )
		{
			if ( !empty($data['legalAddressFull']['city']) )
				$metas['legallocation'] = usam_get_locations(['search' => $data['legalAddressFull']['city'], 'number' => 1, 'fields' => 'id', 'code' => 'city']);						
			if ( !empty($data['legalAddressFull']['postalCode']) )
				$metas['legalpostcode'] = $data['legalAddressFull']['postalCode'];
			if ( !empty($data['legalAddressFull']['street']) )
				$metas['legaladdress'] = $data['legalAddressFull']['street'];
		}
		if ( !empty($data['actualAddressFull']) )
		{
			if ( !empty($data['actualAddressFull']['city']) )
				$metas['contactlocation'] = usam_get_locations(['search' => $data['actualAddressFull']['city'], 'number' => 1, 'fields' => 'id', 'code' => 'city']);						
			if ( !empty($data['actualAddressFull']['postalCode']) )
				$metas['contactpostcode'] = $data['actualAddressFull']['postalCode'];
			if ( !empty($data['actualAddressFull']['street']) )
				$metas['contactaddress'] = $data['actualAddressFull']['street'];
		}
		return ['company' => $company, 'metas' => $metas];
	}
	
	public function add_company_to_website( $data, $company_type = 'customer' )
	{
		if ( is_string($data) )
		{			
			$type = $company_type == 'own' ? 'organization' : 'counterparty';			
			$args = $this->get_args( 'GET' );
			$data = $this->send_request( "entity/{$type}/{$data}", $args );	
		}
		$company = $this->get_data_company( $data );
		extract( $company );
		if ( !empty($data['id']) )
		{
			$company['type'] = $company_type;
			$company_id = usam_insert_company( $company, $metas );  
			if ( $company_id )
			{
				usam_insert_bank_account(['company_id' => $company_id, 'number' => 888888888]);
				usam_update_company_metadata($company_id, 'code_moysklad', $data['id']);				
				return $company_id;
			}
		}
		return 0;
	}
	
	public function insert_company( $t )
	{ 
		$data = $t->get_data();		
		
		$type = $data['type'] == 'own' ? 'organization' : 'counterparty';
		$insert = $this->get_company_by_moysklad( $data );
		$args = $this->get_args( 'POST', $insert );
		$results = $this->send_request( "entity/$type/", $args ); 
		if ( isset($results['id']) )
			usam_update_company_metadata($results['externalCode'], 'code_moysklad', $results['id']);
	}	
	
	public function update_company( $t )
	{ 	
		$data = $t->get_data();		
		$insert = $this->get_company_by_moysklad( $data );
		$type = $data['type'] == 'own' ? 'organization' : 'counterparty';
		if ( $insert )
		{
			$code_moysklad = usam_get_company_metadata($data['id'], 'code_moysklad' );			
			if ( $code_moysklad )
			{
				$args = $this->get_args( 'PUT', $insert );
				$results = $this->send_request( "entity/$type/".$code_moysklad, $args );
			}
			else
			{
				$args = $this->get_args( 'POST', $insert );
				$results = $this->send_request( "entity/$type/", $args ); 
				if ( isset($results['id']) )
					usam_update_company_metadata($results['externalCode'], 'code_moysklad', $results['id']);
			}
		}
	}		
	
	public function company_delete( $id )
	{ 	
		$args = $this->get_args( 'DELETE' );
		$data = usam_get_company( $id );
		$type = $data['type'] == 'own' ? 'organization' : 'counterparty';
		$code_moysklad = usam_get_company_metadata($id, 'code_moysklad' ); 
		if ( $code_moysklad )
			$results = $this->send_request( "entity/$type/".$code_moysklad, $args );	
	}
	
	public function add_company_moysklad( $id, $type = 'counterparty' )
	{	 
		$params = $this->get_company_by_moysklad( $id );
		$args = $this->get_args( 'POST', $params );
		$results = $this->send_request( "entity/".$type, $args );	
		if ( !empty($results['id']) )
		{
			usam_update_company_metadata($id, 'code_moysklad', $results['id']);
			return $results['id'];
		}
		return false;
	}
	
	public function update_companies( $query_vars = [], $type = 'counterparty' )
	{
		if ( $type == 'counterparty' )
		{
			$company_type = 'customer';
			$query_vars['type__not_in'] = 'own';			
			$moysklad = $this->get_contractors(['filter' => 'companyType=legal;entrepreneur']);	
		}
		else
		{
			$company_type = 'own';
			$query_vars['type'] = $company_type;
			$moysklad = $this->get_organizations();	
		}				
		if ( $moysklad )
		{
			$query_vars['number'] = 10;
			$companies = usam_get_companies( $query_vars );	
			$params = [];
			foreach( $companies as $company )
			{		
				$insert = $this->get_company_by_moysklad( (array)$company );
				if ( !empty($moysklad['rows']) )
				{
					$code = usam_get_company_metadata( $company->id, 'code_moysklad');		
					foreach( $moysklad['rows'] as $key => $data )
					{	
						if ( isset($data['id']) && $data['id'] == $code )
						{		
							$insert['meta'] = $data['meta'];
							unset($moysklad['rows'][$key]);
							break;						
						}
					} 	
				}
				$params[] = $insert;
			}		
			if ( !empty($moysklad['rows']) )
			{	
				foreach( $moysklad['rows'] as $key => $data )
				{
					$company_id = $this->add_company_to_website( $data, $company_type );
					if ( $company_id )
					{
						$data = array_merge($data, $this->get_metadata(['href' => $this->get_url("entity/{$type}/".$data['id']), 'type' => $type, 'metadataHref' => $this->get_url('entity/'.$type)]));
						$data['externalCode'] = (string)$company_id;
						$params[] = $data;
					}					
				}
			}
			if ( $params )
			{			
				$args = $this->get_args( 'POST', $params );
				$results = $this->send_request( "entity/".$type, $args );
				if ( $results )
				{				
					foreach( $results as $result )
					{					
						if ( !empty($result['externalCode']) )
							usam_update_company_metadata($result['externalCode'], 'code_moysklad', $result['id']);
					}				
				}	
			}
		}		
	}
	
	public function get_properties_contact( )
	{
		return ['email' => 'email', 'phone' => 'phone', 'mobilephone' => 'phone', 'lastname' => 'legalLastName', 'firstname' => 'legalFirstName', 'patronymic' => 'legalMiddleNam'];
	}	
	
	public function get_data_contact( $data, $contact_type = 'customer' )
	{
		$contact = ['contact_source' => $contact_type];
		foreach( $this->get_properties_contact() as $meta_key => $key)
		{						
			if ( !empty($data[$key]) )
				$contact[$meta_key] = $data[$key];
		}		
		return $contact;		
	}
	
	public function insert_contact( $data, $contact_type = 'customer' )
	{
		if ( is_string($data) )
		{				
			$args = $this->get_args( 'GET' );
			$data = $this->send_request( "entity/counterparty/{$data}", $args );	
		}
		$contact = $this->get_data_contact( $data, $contact_type );	
		$contact_id = usam_insert_contact( $contact );
		if ( $contact_id )
		{
			usam_add_contact_metadata($contact_id, 'code_moysklad', $data['id']);				
			return $contact_id;
		}
		return 0;
	}
	
	public function get_contact_data_by_moysklad( $contact )
	{
		$name = !empty($contact['appeal']) ? $contact['appeal'] : trim(usam_get_contact_metadata( $contact['id'], 'full_name' ));
		$insert = ['name' => $name, 'externalCode' => (string)$contact['id']];		
		foreach($this->get_properties_contact() as $meta_key => $key)
		{
			$meta_value = (string)usam_get_contact_metadata( $contact['id'], $meta_key);
			if ( $meta_value )
				$insert[$key] = (string)$meta_value;
			elseif( in_array($meta_key, ['firstname', 'lastname', 'patronymic'] ) )
				$insert[$key] = 'none';
		}
		return $insert;
	}
			
	public function get_contact_by_moysklad( $contact )
	{	
		if ( !is_array($contact) )
			$contact = usam_get_contact( $contact );			
		$insert = $this->get_contact_data_by_moysklad( $contact );
		$insert['companyType'] = 'individual';		
		$location_id = usam_get_contact_metadata( $contact['id'], 'location');
		if ( $location_id )
		{
			$locations = usam_get_address_locations( $location_id, 'code=>data' );	
			if ( $locations )
			{
				if ( !empty($locations['city']) )
					$insert['legalAddressFull']['city'] = $locations['city']->name;
				
				$external_code = usam_get_location_metadata($locations['country']->id, 'moysklad');
				if ( $external_code )
					$insert['legalAddressFull']['country'] = $this->get_metadata(['href' => $this->get_url('entity/country/'.$external_code), 'type' => 'country', 'metadataHref' => $this->get_url('entity/entity/country')]);
				if ( !empty($locations['region']) )
				{
					$external_code = usam_get_location_metadata($locations['region']->id, 'moysklad');
					if ( $external_code )
						$insert['legalAddressFull']['region'] = $this->get_metadata(['href' => $this->get_url('entity/region/'.$external_code), 'type' => 'region', 'metadataHref' => $this->get_url('entity/entity/region')]);						
				}
			}
			$insert['legalAddressFull']['postalCode'] = (string)usam_get_contact_metadata( $contact['id'], 'postcode');
			$insert['legalAddressFull']['street'] = (string)usam_get_contact_metadata( $contact['id'], 'address');
		}
		return $insert;		
	}
		
	public function get_employee_by_moysklad( $contact )
	{	
		if ( !is_array($contact) )
			$contact = usam_get_contact( $contact );		
		$insert = $this->get_contact_data_by_moysklad( $contact );		
		return $insert;		
	}
	
	public function add_contact_moysklad( $id, $contact_type = 'contact' )
	{		
		
		if ( $contact_type == 'employee' )
		{
			$type = 'employee';
			$params = $this->get_employee_by_moysklad( $id );
		}
		else
		{
			$type = 'counterparty';
			$params = $this->get_contact_by_moysklad( $id );
		}
		$args = $this->get_args( 'POST', $params );	
		$results = $this->send_request( "entity/".$type, $args );	 
		if ( !empty($results['id']) )
		{
			usam_update_contact_metadata($id, 'code_moysklad', $results['id']);
			return $results['id'];
		}
		return false;
	}
		
	public function update_contacts( $query_vars = [], $type = 'counterparty' )
	{
		if ( $type == 'counterparty' )
		{
			$moysklad = $this->get_organizations(['filter' => 'companyType=individual']);
		}
		else
		{			
			$moysklad = $this->get_employees(['filter' => 'companyType=individual']);
		}		
		if ( $moysklad )
		{		
			$query_vars['number'] = 10;
			if ( $type == 'employee' )
				$query_vars['source'] = 'employee';
			$contacts = usam_get_contacts( $query_vars );
			$params = [];
			foreach( $contacts as $contact )
			{		
				$insert = $this->get_contact_by_moysklad( (array)$contact );				
				if ( !empty($moysklad['rows']) )
				{
					$code = usam_get_contact_metadata( $contact->id, 'code_moysklad');
					foreach( $moysklad['rows'] as $key => $data )
					{		
						if ( isset($data['id']) && $data['id'] == $code )
						{		
							$insert['meta'] = $data['meta'];			
							unset($moysklad['rows'][$key]);
							break;						
						}					
					} 		
				}
				$params[] = $insert;
			}				
			if ( !empty($moysklad['rows']) )
			{	
				foreach( $moysklad['rows'] as $key => $data )
				{
					$contact_id = $this->insert_contact( $data );
					if ( $contact_id )
					{											
						$data['externalCode'] = (string)$contact_id;
						$params[] = $data;
					}					
				}
			}			
			if ( $params )
			{			
				$args = $this->get_args( 'POST', $params );
				$results = $this->send_request("entity/".$type, $args);
				if ( $results )
				{				
					foreach( $results as $result )
					{					
						if ( !empty($result['externalCode']) )
							usam_update_contact_metadata($result['externalCode'], 'code_moysklad', $result['id']);
					}				
				}				
			}
		}		
	}		

	// Получить склады
	public function get_stores( )
	{ 
		$args = $this->get_args( );
		$result = $this->send_request( "entity/store", $args );	
		return $result;
	}	
	
	//Получить характеристики
	public function get_variants( )
	{ 
		$args = $this->get_args( );
		$result = $this->send_request( "entity/variant/metadata", $args );	
		return $result;
	}	
	
		//Получить характеристики
	public function get_product_variants( $params = [] )
	{ 
		$args = $this->get_args( 'GET', $params );
		$result = $this->send_request( "entity/variant", $args );	
		return $result;
	}
	
	public function get_units( )
	{ 
		$args = $this->get_args( );
		$result = $this->send_request( "entity/uom", $args );	
		return $result;
	}
		
	//Получить валюты
	public function get_currencies( )
	{ 
		$args = $this->get_args( );
		$result = $this->send_request( "entity/currency", $args );		
		return $result;
	}
	
	// Получить контрагентов
	public function get_contractors( $params = [] )
	{ 
		$args = $this->get_args( 'GET', $params );
		$result = $this->send_request( "entity/counterparty", $args );	
		return $result;	
	}
	
// Получить юридические лица(свой компании)
	public function get_organizations( $params = [] )
	{ 
		$args = $this->get_args( 'GET', $params );
		$result = $this->send_request( "entity/organization", $args );	
		return $result;	
	}
	
	//Получить сотрудников
	public function get_employees( )
	{ 
		$args = $this->get_args( );
		$result = $this->send_request( "entity/employee", $args );		
		return $result;
	}
	
	//Получить услуги
	public function get_service( $params = [] )
	{ 
		$args = $this->get_args( 'GET', $params );
		$result = $this->send_request( "entity/service", $args );		
		return $result;
	}
	
	//Получить товары
	public function get_products( $params = [] )
	{ 
		$args = $this->get_args( 'GET', $params );
		$result = $this->send_request( "entity/product", $args );		
		return $result;
	}	
	
	//Получить товары
	public function get_sets( $params = [] )
	{ 
		$args = $this->get_args( 'GET', $params );
		$result = $this->send_request( "entity/bundle", $args );		
		return $result;
	}
		
	// Получить группу товаров
	public function get_products_groups( $params = [] )
	{ 
		$args = $this->get_args( 'GET', $params );
		$result = $this->send_request( "entity/productfolder", $args );	
		return $result;	
	}
		
	//Получить типы цен
	public function get_type_prices( )
	{ 
		$args = $this->get_args( );
		$result = $this->send_request( "context/companysettings/pricetype", $args );	
		return $result;	
	}	
		
	public function get_products_balances( $params = [] )
	{ 
		$args = $this->get_args( 'GET', $params );
		$result = $this->send_request( "report/stock/bystore", $args );	
		return $result;	
	}	
		
	protected function get_args( $method = 'GET', $params = [] )
	{ 
		$headers["Content-type"] = 'application/json';
		$headers["Authorization"] = "Bearer $this->token";
		$headers["Accept-Encoding"] = "gzip";
		$args = [
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers
		];	
		if ( $params )
		{
			if ( $method == 'GET' )
				$args['body'] = $params;
			else
				$args['body'] = json_encode($params);
		}
		return $args;
	}	
	
	public function order_save( $order_id )
	{ 	
		$data = usam_get_order( $order_id );	 	
		$insert = $this->get_order_by_moysklad( $data ); 
		if ( $insert )
		{
			$code_moysklad = usam_get_order_metadata($data['id'], 'code_moysklad' );
			if ( $code_moysklad )
			{				
				$args = $this->get_args( 'PUT', $insert );	
				$results = $this->send_request( "entity/customerorder/".$code_moysklad, $args );	 
			}
			else
			{
				$args = $this->get_args( 'POST', $insert );  
				$results = $this->send_request( "entity/customerorder/", $args );	 
				if ( isset($results['id']) )
					usam_update_order_metadata($results['code'], 'code_moysklad', $results['id']);
			}
		}
	}		
	
	public function order_delete( $order, $id )
	{ 	
		$args = $this->get_args( 'DELETE' );
		$code_moysklad = usam_get_order_metadata($id, 'code_moysklad' );
		if ( $code_moysklad )
			$results = $this->send_request( "entity/customerorder/".$code_moysklad, $args );	
	}
			
	public function update_order( $query_vars = [] )
	{		
		$query_vars['number'] = 10;		
		$query_vars['cache_meta'] = true;
		$query_vars['cache_order_products'] = true;
		$query_vars['cache_order_shippeds'] = true;
		$orders = usam_get_orders( $query_vars );		
		$i = 0;
		$params = [];	
		$codes = [];
		foreach( $orders as $key => $order )		
		{		
			$insert = $this->get_order_by_moysklad( (array)$order );
			if ( $insert )
			{
				$code = usam_get_order_metadata( $order->id, 'code_moysklad');
				if ( $code )
					$codes[] = $code;
				$params[] = $insert;
				$i++;
			}
		}	
		if ( $params )
		{  
			$args = $this->get_args( 'POST', $params );
			$results = $this->send_request( "entity/customerorder", $args );		
			if ( $results )
			{
				foreach( $results as $result )
				{		
					if ( !empty($result['code']) )
					{
						if ( in_array(!$result['code'], $codes) )
							usam_update_order_metadata($result['code'], 'code_moysklad', $result['id']);
						usam_update_order_metadata($result['code'], 'exchange', 1);
						usam_update_order_metadata($result['code'], 'date_exchange', date("Y-m-d H:i:s"));	
					}
				}
			}		
		} 	
		return $i;
	}		
	
	private function get_order_by_moysklad( $order )
	{ 
		static $prices = null;
		if ( $prices === null )
			$prices = usam_get_prices(['type' => 'all']);	
				
		$insert = ['name' => (string)$order['number'], 'code' => (string)$order['id'], 'externalCode' => (string)$order['id'], 'moment' => $order['date_insert'], 'sum' => $order['totalprice']*100];
		$insert['applicable'] = $order['status'] == 'closed'?true:false;	
		$code = '';
		if ( $order['bank_account_id'] )
		{ //Продавец
			$bank_account = usam_get_bank_account( $order['bank_account_id'] );		
			if ( $bank_account )
			{
				$code = usam_get_company_metadata( $bank_account['company_id'], 'code_moysklad');
				if ( !$code )
				{
					$this->update_companies(['include' => [$bank_account['company_id']]], 'organization');
					$code = usam_get_company_metadata( $bank_account['company_id'], 'code_moysklad');
				}
			}			
		}		
		if ( $code )
			$insert['organization'] = $this->get_metadata(['href' => $this->get_url('entity/organization/'.$code), 'type' => 'organization']);		
		else
			return [];
		$code = '';
		
		if ( $order['company_id'] )
		{
			$code = usam_get_company_metadata( $order['company_id'], 'code_moysklad');
			if ( !$code )
				$code = $this->add_company_moysklad( $order['company_id'] );
		}
		elseif ( $order['contact_id'] )
		{
			$code = usam_get_contact_metadata( $order['contact_id'], 'code_moysklad');	
			if ( !$code )
				$code = $this->add_contact_moysklad( $order['contact_id'] );
		} 
		if ( $code )
			$insert['agent'] = $this->get_metadata(['href' => $this->get_url('entity/counterparty/'.$code), 'type' => 'counterparty']);//покупатель
		else
			return [];
		$status = usam_get_object_status_by_code( $order['status'], 'order' );		 
		$insert['state'] = usam_get_object_status_metadata( $status['id'], 'moysklad_id' );
		$code = '';
		if ( $order['manager_id'] )
		{
			$code = usam_get_contact_metadata( $order['manager_id'], 'code_moysklad');
			if ( !$code )
				$code = $this->add_contact_moysklad( $order['manager_id'], 'employee' );		
			if ( $code )				
				$insert['owner'] = $this->get_metadata(['href' => $this->get_url('entity/employee/'.$code), 'type' => 'employee']); //Сотрудник
		}			
		$code = usam_get_order_metadata($order['id'], 'code_moysklad');
		if ( $code )
			$insert = array_merge($insert, $this->get_metadata(['href' => $this->get_url('entity/customerorder/'.$code), 'type' => 'customerorder', 'metadataHref' => $this->get_url('entity/customerorder')]));		
		
		$products = usam_get_products_order( $order['id'] ); 
		if ( $products )
		{
			$codes = [];
			foreach( $products as $product )
			{
				$code_moysklad = usam_get_product_meta( $product->product_id, 'code_moysklad' );
				if ( !$code_moysklad )
					$codes[] = $product->product_id;		
			}		
			if ( $codes )
			{ 
				$this->update_products(['post__in' => $codes]);
			}
			foreach( $products as $product )
			{
				$code_moysklad = usam_get_product_meta( $product->product_id, 'code_moysklad' );
				if ( $code_moysklad )
				{
					$price = (float)$product->price*100;
					$old_price = (float)$product->old_price*100;
					$insert['positions'][] = ['vat' => 0, 'quantity' => (float)$product->quantity, 'reserve' => (float)$product->quantity, 'price' => $price, 'discount' => $old_price-$price, 'assortment' => $this->get_metadata(['href' => $this->get_url('entity/product/'.$code_moysklad), 'type' => 'product'])];
				}
			}	
		}			
		return $insert;
	}	
	
	private function rest_api_create_product( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $args );
		$product_id = $this->set_product( $data );
	}
	
	private function rest_api_update_product( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $args );
		
		$product_id = $this->set_product( $data );
	}
	
	private function rest_api_delete_product( $href ) 
	{	
		$code_moysklad = str_replace("{$this->API_URL}/entity/product/", "", $href);
		$product_id = usam_get_product_id_by_meta( 'code_moysklad', $code_moysklad );
		if ( $product_id )
		{	
			wp_delete_post( $product_id, true );
		}
	}
	
	private function rest_api_create_service( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $args );
		$product_id = $this->set_product( $data, 'service' );
	}
	
	private function rest_api_update_service( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $args );
		
		$product_id = $this->set_product( $data, 'service' );
	}
	
	private function rest_api_delete_service( $href ) 
	{	
		$code_moysklad = str_replace("{$this->API_URL}/entity/service/", "", $href);
		$product_id = usam_get_product_id_by_meta( 'code_moysklad', $code_moysklad );
		if ( $product_id )
		{	
			wp_delete_post( $product_id, true );
		}
	}
	
	private function rest_api_create_bundle( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $args );
		$product_id = $this->set_product( $data );
	}
	
	private function rest_api_update_bundle( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $args );
		
		$product_id = $this->set_product( $data );
	}
	
	private function rest_api_delete_bundle( $href ) 
	{	
		$code_moysklad = str_replace("{$this->API_URL}/entity/bundle/", "", $href);
		$product_id = usam_get_product_id_by_meta( 'code_moysklad', $code_moysklad );
		if ( $product_id )
		{	
			wp_delete_post( $product_id, true );
		}
	}
	
	private function insert_term( $data ) 
	{				
		$args = ['parent' => 0];
		if ( $data['pathName'] )
		{
			$params = $this->get_args( 'GET' );
			$group = $this->send_request( $data['productFolder']['meta']['href'], $params );
			if ( isset($group['id']) )
				$args['parent'] = (int)usam_term_id_by_meta('code_moysklad', $group['id'], 'usam-category');		
		}		
		$term = wp_insert_term($data['name'], 'usam-category', $args);	
		if ( !is_wp_error($term) ) 	
		{
			usam_update_term_metadata( $term['term_id'], 'code_moysklad', $data['id'] );
			return $term['term_id'];			
		}
		return false;
	}
		
	private function rest_api_create_productfolder( $href ) 
	{	
		$params = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $params );			
		$this->insert_term( $data );
	}
	
	private function rest_api_update_productfolder( $href ) 
	{	
		$params = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $params );
		$args = ['parent' => 0, 'name' => $data['name']];
		$term_id = usam_term_id_by_meta('code_moysklad', $data['id'], 'usam-category');
		if ( $term_id )
		{	
			if ( !empty($data['pathName']) )
			{
				$params = $this->get_args( 'GET' );
				$group = $this->send_request( $data['productFolder']['meta']['href'], $params );
				if ( isset($group['id']) )
					$args['parent'] = (int)usam_term_id_by_meta('code_moysklad', $group['id'], 'usam-category');		
			}	
			$term = wp_update_term($term_id, 'usam-category', $args);	
		}
		else
			$this->insert_term( $data );
	}
	
	private function rest_api_delete_productfolder( $href ) 
	{	
		$code_moysklad = str_replace("{$this->API_URL}/entity/productfolder/", "", $href);
		$term_id =  usam_term_id_by_meta('code_moysklad', $code_moysklad, 'usam-category');
		if ( $term_id )
		{	
			wp_delete_term( $term_id, 'usam-category' );
		}
	}	
	
	private function get_order( $document ) 
	{
		$products = [];
		$insert = [];
		if ( !empty($document['state']) )
		{ 
			$code_moysklad = str_replace("{$this->API_URL}/entity/customerorder/metadata/states/", "", $document['state']['meta']['href']);
			$insert['status'] = usam_get_status_code_by_meta( 'moysklad_id', $code_moysklad, 'order' );
		}	
		if ( !empty($document['organization']) )
		{
			$code_moysklad = str_replace("{$this->API_URL}/entity/organization/", "", $document['organization']['meta']['href']);
			$company_id = usam_get_company_id_by_meta('code_moysklad', $code_moysklad);
			if ( !$company_id )
				$company_id = $this->add_company_to_website( $code_moysklad, 'own' );
			$bank_accounts = usam_get_company_bank_accounts( $company_id );				
			if ( !empty($bank_accounts[0]) )
				$insert['bank_account_id'] = $bank_accounts[0]->id; 
		}
		if ( !empty($document['agent']) )
		{
			if ( $document['agent']['meta']['type'] == 'organization' )
			{				
				$company_id = usam_get_company_id_by_meta('code_moysklad', $code_moysklad);
				if ( !$company_id )
					$company_id = $this->add_company_to_website( $code_moysklad );					
				if ( $company_id )
				{
					$insert['company_id'] = $company_id; 	
					$payers = usam_get_group_payers(['type' => 'company']);	
					$metas = usam_get_company_metas( $company_id );						
				}
			}
			else
			{
				$code_moysklad = str_replace("{$this->API_URL}/entity/counterparty/", "", $document['agent']['meta']['href']);				
				$contact = usam_get_contacts(['meta_query' => [['key' => 'code_moysklad', 'compare' => '=', 'value' => $code_moysklad]], 'number' => 1, 'cache_results' => true]);
				if ( $contact )
				{
					$insert['contact_id'] = $contact['id']; 
					$payers = usam_get_group_payers(['type' => 'contact']);						
					$metas = usam_get_contact_metas( $contact['id'] );						
				}
			}				
			if ( !empty($metas) )
				$customer_data = usam_get_webform_data_from_CRM( $metas, 'order', $payers[0]['id'] );					
		}
		if ( !empty($document['owner']) )
		{
			$code_moysklad = str_replace("{$this->API_URL}/entity/employee/", "", $document['agent']['meta']['href']);				
			$insert['manager_id'] = usam_get_contacts(['meta_query' => [['key' => 'code_moysklad', 'compare' => '=', 'value' => $code_moysklad]], 'number' => 1, 'fields' => 'id']);
		}	
		if ( !empty($document['positions']) )
		{					
			$args = $this->get_args( 'GET' );
			$results = $this->send_request( $document['positions']['meta']['href'], $args );	
			if ( !empty($results['rows']) )
			{
				$ids = usam_get_storages(['fields' => 'id']);	
				$codes = [];
				foreach( $ids as $id )
				{
					$code = usam_get_storage_metadata($id, 'code_moysklad');
					if ( !$code )
					{
						$this->update_stores();
						$code = usam_get_storage_metadata($id, 'code_moysklad');
					}
					$codes[$code] = 'storage_'.$id;
				}
				$add_products = [];
				foreach( $results['rows'] as $result )
				{							
					$code_moysklad = str_replace("{$this->API_URL}/entity/product/", "", $result['assortment']['meta']['href']);							
					$product_id = usam_get_product_id_by_meta( 'code_moysklad', $code_moysklad );
					if ( $product_id )
					{							
						$price = $result['price']/100;	
						$products[] = ['product_id' => $product_id, 'price' => $price-$price*$result['discount']/100, 'old_price' => $price, 'quantity' => $result['quantity']];
					}										
				}				
			}
		}		
		return ['order' => $insert, 'products' => $products]; 
	}
	
	private function rest_api_create_customerorder( $href ) 
	{					
		$args = $this->get_args( 'GET' );
		$document = $this->send_request( $href, $args );
				
		$this->update_product_stock_document( $document );	
		
		$result = $this->get_order( $document ); // return 	order					
		extract( $result );				
		$order_id = usam_get_order_id_by_meta('code_moysklad', $document['id']);	
		$metas = [];
		if ( !$order_id )
		{			
			if ( empty($products) )
				return $products;
			
			$order['source'] = 'moysklad';	
			$metas['code_moysklad'] = $document['id'];
			$metas['exchange'] = 1;
			$metas['date_exchange'] = date("Y-m-d H:i:s");
			$order_id = usam_insert_order( $order, $products, $metas );	
			if ( $order_id )
			{		
				if ( !empty($customer_data) )
					usam_add_order_customerdata( $order_id, $customer_data );
			}
		}
		else
		{	
			usam_update_order( $order_id, $order, null, $metas );
			
			$products_order = usam_get_products_order( $order_id );
			$update = [];
			foreach( $products_order as $key => $product_order )
			{
				foreach( $products as $key2 => $product )
				{
					if ( $product_order->product_id == $product['product_id'] )
					{
						$product['id'] = $product_order->id;
						$update[] = $product;
						unset($products[$key]);
						unset($products_order[$key2]);
						break;
					}
				}
			}		
			$_order = new USAM_Order( $order_id );	
			$_order->add_products( $products );
			$_order->update_products( $update );		
			if ( !empty($products_order) )
				$_order->delete_products( $products_order );
		}			
	}
	
	private function rest_api_update_customerorder( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$document = $this->send_request( $href, $args );			
		
		$this->update_product_stock_document( $document );
		
		$result = $this->get_order( $document );	
		extract( $result );
		
		$order_id = usam_get_order_id_by_meta('code_moysklad', $document['id']);
		if( !$order_id )
		{			
			if ( empty($products) )
				return $products;
			
			$order['source'] = 'moysklad';	
			$metas['code_moysklad'] = $document['id'];
			$metas['exchange'] = 1;
			$metas['date_exchange'] = date("Y-m-d H:i:s");
			$order_id = usam_insert_order( $order, $products, $metas );	
			if( $order_id )
			{		
				if ( !empty($customer_data) )
					usam_add_order_customerdata( $order_id, $customer_data );
			}
		}
		else
		{	
			usam_update_order( $order_id, $order, null, $metas );
			
			$products_order = usam_get_products_order( $order_id );
			$update = [];
			foreach( $products_order as $key => $product_order )
			{
				foreach( $products as $key2 => $product )
				{
					if ( $product_order->product_id == $product['product_id'] )
					{
						$product['id'] = $product_order->id;
						$update[] = $product;
						unset($products[$key]);
						unset($products_order[$key2]);
						break;
					}
				}
			}		
			$_order = new USAM_Order( $order_id );
			$_order->add_products( $products );
			$_order->update_products( $update );		
			if( !empty($products_order) )
				$_order->delete_products( $products_order );
		}			
	}	
	
	private function rest_api_delete_customerorder( $href ) 
	{			
		$code_moysklad = str_replace("{$this->API_URL}/entity/customerorder/", "", $href);	
		$order_id = usam_get_orders(['meta_query' => [['key' => 'code_moysklad', 'compare' => '=', 'value' => $code_moysklad]], 'number' => 1, 'fields' => 'id']);	
		if ( $order_id )
		{
			usam_delete_orders(['include' => $order_id]);
		}		
		$this->update_product_stock_document( $href );
	}
		
	private function rest_api_create_organization( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $args ); 
		if ( !empty($data['externalCode']) && is_numeric($data['externalCode']) ) // защита от многократного создания
		{
			if ( usam_get_company( $data['externalCode'] ) )
				return false;
		}
		$this->add_company_to_website( $data, 'own' );
	}
	
	private function rest_api_update_organization( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $args );		
		$id = usam_get_company_id_by_meta( 'code_moysklad', $data['id'] );
		if ( $id )
		{			
			$company = $this->get_data_company( $data );		
			extract( $company );
			$company['type'] = 'own';
			usam_update_company( $id, $company );
			usam_update_company_metas( $id, $metas );
		}
		else
			$this->add_company_to_website( $data, 'own' );
	}
	
	private function rest_api_delete_organization( $href ) 
	{	
		$code_moysklad = str_replace("{$this->API_URL}/entity/organization/", "", $href);
		$id = usam_get_company_id_by_meta( 'code_moysklad', $code_moysklad );
		if ( $id )
		{	
			usam_delete_company( $id );
		}
	}	
		
	private function rest_api_create_counterparty( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $args );	
		if ( $data )
		{					
			if ( $data['companyType'] == 'individual' )
			{
				if ( !empty($data['externalCode']) && is_numeric($data['externalCode']) ) // защита от многократного создания
				{
					if ( usam_get_contact( $data['externalCode'] ) )
						return false;
				}
				$this->insert_contact( $data );
			}
			else
			{
				if ( !empty($data['externalCode']) && is_numeric($data['externalCode']) ) // защита от многократного создания
				{
					if ( usam_get_company( $data['externalCode'] ) )
						return false;
				}
				$this->add_company_to_website( $data, 'own' );
			}
		}
	}
	
	private function rest_api_update_counterparty( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $args );
		if ( $data )
		{			
			if ( $data['companyType'] == 'individual' )
			{
				$id = usam_get_contact_id_by_meta( 'code_moysklad', $data['id'] );
				if ( $id )
				{
					$contact = $this->get_data_contact( $data );	
					usam_update_contact( $id, $contact );
				}
				else
					$this->insert_contact( $data );
			}
			else
			{
				$id = usam_get_company_id_by_meta( 'code_moysklad', $data['id'] );
				if ( $id )
				{
					$company = $this->get_data_company( $data );		
					extract( $company );
					usam_update_company( $id, $company );
					usam_update_company_metas( $id, $metas );
				}
				else
					$this->add_company_to_website( $data );
			}
		}		
	}
	
	private function rest_api_delete_counterparty( $href ) 
	{	
		$args = $this->get_args( 'GET' );
		$data = $this->send_request( $href, $args );		
		if ( $data )
		{
			if ( $data['companyType'] == 'individual' )
			{
				$id = usam_get_contact_id_by_meta( 'code_moysklad', $data['id'] );
				if ( $id )
					usam_delete_contact( $id );
			}
			else
			{
				$id = usam_get_company_id_by_meta( 'code_moysklad', $data['id'] );
				if ( $id )
					usam_delete_company( $id );
			}
		}
	}
		
//Оприходование		
	private function rest_api_create_enter( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
	private function rest_api_update_enter( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
	private function rest_api_delete_enter( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
//Приемка	
	private function rest_api_create_supply( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
	private function rest_api_update_supply( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
	
	private function rest_api_delete_supply( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
//Перемещение
	private function rest_api_create_move( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
	private function rest_api_update_move( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
	
	private function rest_api_delete_move( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
//Отгрузка	
	private function rest_api_create_demand( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
	private function rest_api_update_demand( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
	
	private function rest_api_delete_demand( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
//Списание	
	private function rest_api_create_loss( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
	private function rest_api_update_loss( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
	
	private function rest_api_delete_loss( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
	
//Возврат покупателя	
	private function rest_api_create_salesreturn( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
	private function rest_api_update_salesreturn( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
	
	private function rest_api_delete_salesreturn( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
//Возврат поставщику	
	private function rest_api_create_purchasereturn( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
	private function rest_api_update_purchasereturn( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
	
	private function rest_api_delete_purchasereturn( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
//Розничная продажа
	private function rest_api_create_retaildemand( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
	private function rest_api_update_retaildemand( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
	
	private function rest_api_delete_retaildemand( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
//Розничный возврат
	private function rest_api_create_retailsalesreturn( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}
	
	private function rest_api_update_retailsalesreturn( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
	
	private function rest_api_delete_retailsalesreturn( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
	
	private function rest_api_webhookstock( $href ) 
	{	
		$this->update_product_stock_document( $href );
	}	
	
	private function update_product_stock_document( $href ) 
	{
		if ( is_string($href) )
		{
			$args = $this->get_args( 'GET' );
			$document = $this->send_request( $href, $args );		
		}
		else
			$document = $href;		
		if ( !empty($document['positions']) )
		{
			$args = $this->get_args( 'GET' );
			$results = $this->send_request( $document['positions']['meta']['href'], $args );		
			if ( !empty($results['rows']) )
			{				
				$ids = [];
				foreach( $results['rows'] as $result )
				{							
					$code_moysklad = str_replace("{$this->API_URL}/entity/product/", "", $result['assortment']['meta']['href']);
					$product_id = usam_get_product_id_by_meta( 'code_moysklad', $code_moysklad );						
					if ( $product_id )
						$ids[$product_id] = $code_moysklad;
				}				
				if( $ids )
				{
					$this->update_product_stock( $ids ); //Обновить остатки
					wp_schedule_single_event( time() + 1000, 'usam_moysklad_update_product_stock', [ $ids ] ); // Не всегда Мойсклад возвращает измененные остатки, это страховка
				}
			}
		}
	}
	
	public function update_product_stock( $ids ) 
	{		
		if ( !empty($ids) )
		{
			$storages = usam_get_storages(['cache_meta' => true]);	
			$codes = [];
			$stock = [];
			foreach( $storages as $storage )
			{
				$code = usam_get_storage_metadata($storage->id, 'code_moysklad');
				if ( !$code )
				{
					$this->update_stores();
					$code = usam_get_storage_metadata($storage->id, 'code_moysklad');
				}
				$codes[$code] = 'storage_'.$storage->id;
				$stock['storage_'.$storage->id] = 0;
			}
			foreach( $ids as $product_id => $code_moysklad )
			{											
				$product_stock = $this->get_product_stock_from_moysklad( $code_moysklad, $codes );
				$product_stock = array_merge( $stock, $product_stock );				
				$_product = new USAM_Product( $product_id );
				$_product->set(['product_stock' => $product_stock]);						
				$_product->save_stocks();
				usam_update_product_meta( $product_id, 'balance_update', date("Y-m-d H:i:s") );
			}
		}
	}
	
	public function get_product_stock_from_moysklad( $code_moysklad, $codes ) 
	{
		$product_stock = [];
		$args = $this->get_args( 'GET', ['filter' => 'product='.$this->get_url('entity/product/'.$code_moysklad), 'stockType' => 'quantity']);		
		$stock_results = $this->send_request( "report/stock/bystore", $args );						
		if ( !empty($stock_results['rows']) )
		{
			foreach( $stock_results['rows'][0]['stockByStore'] as $store_data )
			{
				$store_code_moysklad = str_replace("{$this->API_URL}/entity/store/", "", $store_data['meta']['href']);
				if ( isset($codes[$store_code_moysklad]) )
					$product_stock[$codes[$store_code_moysklad]] = $store_data['stock'];
			}							
		}	
		return $product_stock;
	}
			
	public function rest_api( $request ) 
	{		
		$params = $request->get_json_params();	
		if ( !empty($params['events']) )
		{
			remove_action( 'delete_usam-category', array( &$this, 'delete_category' ), 10 , 4 ); 
			remove_action( "edited_usam-category", array( &$this, 'update_category' ), 10 , 2 ); 	
			
			remove_action( "usam_storage_before_delete", array( &$this, 'storage_delete' ), 10 , 1 ); 
			remove_action( "usam_storage_insert", array( &$this, 'storage_insert' ), 10 , 1 ); 	
			remove_action( "usam_storage_update", array( &$this, 'storage_update' ), 10 , 1 ); 
							
			remove_action( "usam_type_price_delete", array( &$this, 'type_price_delete' ), 10 , 1 ); 	
			remove_action( "usam_type_price_update", array( &$this, 'type_price_update' ), 10 , 1 ); 
			remove_action( "usam_type_price_insert", array( &$this, 'type_price_insert' ), 10 , 1 ); 

			remove_action('usam_edit_product', [&$this, 'edit_product'], 10, 3);	
			remove_action('usam_insert_product', [&$this, 'edit_product'], 10, 3);				
			remove_action('usam_update_product', [&$this, 'edit_product'], 10, 3);	
			remove_action('usam_edit_product_prices', [&$this, 'edit_product_prices'], 10, 2);	
			
			remove_action('before_delete_post',  array( &$this, 'delete_product' ), 10, 1 ); 	
			remove_action( "usam_currency_before_delete", array( &$this, 'currency_delete' ), 10, 1 ); 
			remove_action( "usam_currency_insert", array( &$this, 'currency_insert' ), 10, 1 ); 	
			remove_action( "usam_currency_update", array( &$this, 'currency_update' ), 10, 1 ); 	

			remove_action( "usam_order_before_delete", [&$this, 'order_delete'], 10, 2 ); 			
			remove_action( "usam_document_order_save", [&$this, 'order_save'], 10, 1 ); 	
			remove_action( "usam_basket_add_order", [&$this, 'order_save'], 10, 1 );				
			foreach( $params['events'] as $event )
			{
				$method = 'rest_api_'.strtolower($event['action']).'_'.$event['meta']['type'];
				if ( method_exists($this, $method) )
				{				
					$this->$method( $event['meta']['href'] );
				}
			}
		}
	} 
	
	public function order_status_settings_edit_form( $t, $data ) 
	{
		$args = $this->get_args( );
		$statuses = $this->send_request( "entity/customerorder/metadata", $args );	
		if ( $statuses === false )
			return false;		
		?>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php _e('Соответствия статусам Мойсклад', 'usam'); ?>:</div>
			<div class ="edit_form__item_option">					
				<select name='moysklad_id'>						
					<option value=''><?php _e('Не выбранно' , 'usam'); ?></option>
					<?php
					foreach( $statuses['states'] as $status ) 
					{										
						?><option <?php selected($status['id'], $data['moysklad_id']); ?> value='<?php echo $status['id']; ?>'><?php echo $status['name']; ?></option><?php
					}
					?>
				</select>
			</div>
		</div>
		<?php
	}
	
	public function order_status_edit_form_data( $data, $t ) 
	{
		$data['moysklad_id'] = usam_get_object_status_metadata( $data['id'], 'moysklad_id' );
		return $data;
	}	
	
	public function object_status_save( $t ) 
	{
		if( isset($_POST['moysklad_id']) && $t->get('type') == 'order' )
			usam_update_object_status_metadata( $t->get('id'), 'moysklad_id', sanitize_title($_POST['moysklad_id']) );		
	}
	
	public function admin_init() 
	{
		if( current_user_can( 'setting_document' ) )			
			add_action( 'usam_object_status_save', [&$this, 'object_status_save']);
		
		add_action( 'usam_order_status_settings_edit_form',  [&$this, 'order_status_settings_edit_form'], 10, 2 );	
		add_filter( 'usam_order_status_edit_form_data', [&$this, 'order_status_edit_form_data'], 10, 2 );
	}
		
	public function service_load( ) 
	{ 
		if ( usam_is_license_type('SMALL_BUSINESS') || usam_is_license_type('BUSINESS') || usam_is_license_type('ENTERPRISE'))
		{				
			add_action( 'admin_init', [&$this, 'admin_init']);
			add_action( 'usam_moysklad_update_product_stock',  [&$this, 'update_product_stock'] );
			add_action('rest_api_init', array($this,'register_routes') );
			if ( !defined('REST_REQUEST') || !REST_REQUEST )
			{
				if ( usam_get_application_metadata( $this->id, 'type_update' ) == 'site' )
				{
					add_action( 'delete_usam-category', [&$this, 'delete_category'], 10 , 4 ); 
					add_action( "edited_usam-category", [&$this, 'update_category'], 10 , 2 ); 	
					
					add_action( "usam_storage_before_delete", [&$this, 'storage_delete'], 10 , 1 ); 
					add_action( "usam_storage_insert", [&$this, 'storage_insert'], 10 , 1 ); 	
					add_action( "usam_storage_update", [&$this, 'storage_update'], 10 , 1 ); 
									
					add_action( "usam_type_price_delete", array( &$this, 'type_price_delete' ), 10 , 1 ); 	
					add_action( "usam_type_price_update", array( &$this, 'type_price_update' ), 10 , 1 ); 
					add_action( "usam_type_price_insert", array( &$this, 'type_price_insert' ), 10 , 1 ); 

					add_action('usam_edit_product', [&$this, 'edit_product'], 10, 3);	
					add_action('usam_insert_product', [&$this, 'edit_product'], 10, 3);
					add_action('usam_update_product', [&$this, 'edit_product'], 10, 3);	
					add_action('usam_edit_product_prices', [&$this, 'edit_product_prices'], 10, 2);									
					add_action('before_delete_post',  array( &$this, 'delete_product' ), 10 , 1 ); 				
				}
				add_action( "usam_currency_before_delete", array( &$this, 'currency_delete' ), 10 , 1 ); 
				add_action( "usam_currency_insert", array( &$this, 'currency_insert' ), 10 , 1 ); 	
				add_action( "usam_currency_update", array( &$this, 'currency_update' ), 10 , 1 ); 	

				add_action( "usam_order_before_delete", [&$this, 'order_delete'], 10 , 2 ); 
				add_action( "usam_document_order_save", [&$this, 'order_save']);		
				add_action( "usam_basket_add_order", [&$this, 'order_save']);
					
				add_action('usam_company_insert', [&$this, 'insert_company']);		
				add_action('usam_company_update', [&$this, 'update_company']);		
				add_action( "usam_company_before_delete", [&$this, 'company_delete']);		
				add_filter( 'usam_product_metabox_codes', [&$this, 'product_metabox_codes'], 10, 2 );			
			}	
		}
	}	

	public function product_metabox_codes( $codes )
	{ 	
		$codes['code_moysklad']	= __( 'Код Мой склад', 'usam');
		return $codes;
	}
		
	public function webhook_registration( )
	{ 
		$args = $this->get_args();
		$webhooks = $this->send_request( "entity/webhook", $args );		
		if ( !empty($webhooks['rows']) )
		{
			$params = [];
			foreach( $webhooks['rows'] as $data )
			{			
				$params[] = ['meta' => $data['meta']];
			}
			$args = $this->get_args( 'POST', $params );
			$results = $this->send_request( "entity/webhook/delete", $args );			
		}		
		/*$args = $this->get_args();	
		$webhooks = $this->send_request( "entity/webhookstock", $args );		
		if ( !empty($webhooks['rows']) )
		{ 
			foreach( $webhooks['rows'] as $data )
			{			
				$args = $this->get_args('DELETE'); 	
				$results = $this->send_request( "entity/webhookstock/". $data['id'], $args );
			}			
		}*/
		if ( $this->option['active'] )
		{
			$url = get_rest_url(null,$this->namespace.'/moysklad/'.$this->id);
			$actions = ["customerorder", "move", "demand", "enter", "supply", "loss", "salesreturn", "purchasereturn", "retaildemand", "retailsalesreturn", "retailsalesreturn", "product", "service", "bundle", "counterparty", "productfolder", "organization", "counterparty"];
			$params = [];
			foreach( $actions as $action )
			{
				foreach( ["CREATE", "UPDATE", "DELETE"] as $method )
				{
					$params[] = ["url" => $url, "action" => $method, "entityType" => $action];
				}
			}				
			$args = $this->get_args( 'POST', $params );	
			$result = $this->send_request( "entity/webhook", $args );
			
			/*$args = $this->get_args( 'POST', ['url' => $url, 'enabled' => true, 'reportType' => "bystore", "stockType" => "stock"] );				
			$result = $this->send_request( "entity/webhookstock", $args);*/
		}
	}
	
	public function register_routes( ) 
	{ 
		register_rest_route( $this->namespace, $this->route, array(		
			array(
				'permission_callback' => false,
				'methods'  => 'POST',
				'callback' => 'usam_application_rest_api',	
				'args' => array()
			),			
		));	
	}
				
	public function display_form( ) 
	{			
		$type_update = usam_get_application_metadata( $this->id, 'type_update' );
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='messenger_secret_key'><?php esc_html_e( 'Токен', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['access_token'], ['name' => 'access_token', 'id' => 'messenger_secret_key'] ); ?>
				</div>
			</div>				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_type_update'><?php esc_html_e('Тип обмена', 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<select id='option_catalog_schedule' name='metadata[type_update]'>						
						<option value='site' <?php selected( $type_update, 'site'); ?>><?php esc_html_e('Данные выгружаются с сайта в Мой склад', 'usam'); ?></option>		
						<option value='moysklad' <?php selected( $type_update, 'moysklad'); ?>><?php esc_html_e('Данные выгружаются из Мой склад', 'usam'); ?></option>
					</select>
				</div>
			</div>	
		</div>
		<?php
	}	
	
	public function display_synchronize( ) 
	{			
		?>
		<div class="edit_form">				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='synchronize_categories'><?php _e( 'Синхронизировать категории','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' id='synchronize_categories' name='synchronize_categories' value="1"/>
				</div>
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='synchronize_prices'><?php _e( 'Синхронизировать цены','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' id='synchronize_prices' name='synchronize_prices' value="1"/>
				</div>
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='synchronize_stores'><?php _e( 'Синхронизировать склады','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' id='synchronize_stores' name='synchronize_stores' value="1"/>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='synchronize_document_status'><?php _e( 'Синхронизировать статусы документов','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' id='synchronize_document_status' name='synchronize_document_status' value="1"/>
				</div>
			</div>				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='synchronize_crm'><?php _e( 'Синхронизировать CRM','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' id='synchronize_crm' name='synchronize_crm' value="1"/>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='synchronize_directory'><?php _e( 'Синхронизировать справочники','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' id='synchronize_directory' name='synchronize_directory' value="1"/>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='load_products_moysklad'><?php _e( 'Загрузить товары в Мой склад','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' id='load_products_moysklad' name='load_products_moysklad' value="1"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='load_products_site'><?php _e( 'Загрузить товары на сайт','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' id='load_products_site' name='load_products_site' value="1"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='update_balances'><?php _e( 'Загрузить остатки из Мой склад','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' id='update_balances' name='update_balances' value="1"/>
				</div>
			</div>			
		</div>
		<?php
	}
	
	function display_default_values()
	{
		$post_status = usam_get_application_metadata( $this->id, 'post_status' );
		$post_status = $post_status ? $post_status :'draft';
		?>
		<div class='edit_form'>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_status'><?php esc_html_e( 'Статус товара' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<select id='option_status' name='metadata[post_status]'>	
						<?php
						foreach ( get_post_stati(array('show_in_admin_status_list' => true), 'objects') as $key => $status ) 
						{										
							?><option <?php selected( $post_status, $key ); ?> value='<?php echo $key; ?>'><?php echo $status->label; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>			
		</div>
		<?php
	}	
	
	public function display_form_left( ) 
	{
		usam_add_box( 'usam_synchronize', __('Начальная синхронизация','usam'), [$this, 'display_synchronize'] );
		usam_add_box( 'usam_product_default_values', __('Настройки значения по умолчанию','usam'), [$this, 'display_default_values']);
	}
		
	public function save_form( ) 
	{ 	
		if ( $this->is_token() )
		{	
			$this->webhook_registration( );			
			if ( $this->option['active'] )
			{				
				if ( isset($_POST['synchronize_prices']) )
					$this->update_type_prices();
				if ( isset($_POST['synchronize_stores']) )
					$this->update_stores();					
				if ( isset($_POST['synchronize_document_status']) )
					$this->update_document_status();					
				if ( isset($_POST['synchronize_categories']) )
					$this->update_products_groups();
				if ( isset($_POST['synchronize_crm']) )
				{
					$this->update_companies([], 'organization');
					$this->update_companies();
					$this->update_contacts();					
				}
				if ( isset($_POST['synchronize_directory']) )
				{
					$this->update_units();
					$this->update_currencies();							
				}
				if ( isset($_POST['load_products_site']) )
				{						
					$this->update_units();			
					$this->update_variants();	
					
					$moysklad = $this->get_products(['limit' => 1, 'filter' => 'archived=false']);			
					if ( !empty($moysklad['meta']) && $moysklad['meta']['size'] )
						usam_create_system_process( __("Загрузка каталога из &laquo;Мой склад&raquo;", "usam" ), $this->id, [&$this, 'loading_catalog_from_site'], $moysklad['meta']['size'], 'moysklad_application_catalog-'.$this->id );					
					$moysklad = $this->get_sets(['limit' => 1, 'filter' => 'archived=false']);
					if ( !empty($moysklad['meta']) && $moysklad['meta']['size'] )
						usam_create_system_process( __("Загрузка комплектов из &laquo;Мой склад&raquo;", "usam" ), $this->id,  [&$this, 'loading_sets_from_site'], $moysklad['meta']['size'], 'moysklad_application_sets-'.$this->id );
					$moysklad = $this->get_service(['limit' => 1, 'filter' => 'archived=false']);
					if ( !empty($moysklad['meta']) && $moysklad['meta']['size'] )
						usam_create_system_process( __("Загрузка услуг из &laquo;Мой склад&raquo;", "usam" ), $this->id, [&$this, 'loading_service_from_site'], $moysklad['meta']['size'], 'moysklad_application_service-'.$this->id );		
				}
				if ( isset($_POST['update_balances']) )
				{
					$moysklad = $this->get_products(['limit' => 1, 'filter' => 'archived=false']);			
					if ( !empty($moysklad['meta']) && $moysklad['meta']['size'] )					
						usam_create_system_process( __("Обновить остатки из &laquo;Мой склад&raquo;", "usam" ), $this->id, [&$this, 'update_balances_moysklad'], $moysklad['meta']['size'], 'moysklad_update_balances-'.$this->id );	
				}
				if ( isset($_POST['load_products_moysklad']) )
				{
					$i = usam_get_total_products( );		
					usam_create_system_process( __("Загрузка каталога в &laquo;Мой склад&raquo;", "usam" ), $this->id, [&$this, 'loading_catalog_from_moysklad'], $i, 'moysklad_application_catalog-'.$this->id );					
				}				
			}			
		}
	}
	
	public function loading_catalog_from_moysklad( $id, $number, $event )
	{	
		$done = $this->update_products(['paged' => $event['launch_number']]);		
		return ['done' => $done];
	}	
	
	public function update_balances_moysklad( $id, $number, $event )
	{	
		$done = $this->update_balances(['paged' => $event['launch_number']]);		
		return ['done' => $done];
	}		

	public function loading_catalog_from_site( $id, $number, $event )
	{		
		$done = $this->moysklad_products_loading(['paged' => $event['launch_number']]);
		return ['done' => $done];	
	}

	public function loading_sets_from_site( $id, $number, $event )
	{		
		$done = $this->moysklad_sets_loading(['paged' => $event['launch_number']]);
		return ['done' => $done];
	}

	public function loading_service_from_site( $id, $number, $event )
	{		
		$done = $this->moysklad_service_loading(['paged' => $event['launch_number']]);
		return ['done' => $done];	
	}
}
?>