<?php 
/*
  Title: Транспортная компания СДЭК
  Points: Да
  SELFPICKUP: Да
  Name: СДЭК
  Location code: Да
  Order: Да
 */
class USAM_Shipping_cdek extends USAM_Shipping
{	
	protected $version = "2";	
	protected $load_locations = true;
	public $dateExecute;
	protected $API_URL = "https://api.cdek.ru/";
	
	public function __construct( $id )
	{
		parent::__construct( $id );			
		$this->dateExecute = date('Y-m-d');
	}
			
	private function get_offices( $params = array() ) 
	{				
		$args = $this->get_request_args( $params );
		$results = $this->send_request( $args, "offices" );		
		return $results;
	}	
				
	protected function get_location( $location ) 
	{
		$id = usam_get_location_metadata( $location, $this->deliver['handler'] );
		if( !$id )
		{
			$FIAS = usam_get_location_metadata( $location, 'FIAS' );	
			if( $FIAS )
			{				
				$results = $this->send_get_request( 'location/cities',['size' => 1, 'fias_guid' => $FIAS]);
				if ( !empty($results['errors']) )
				{
					$this->set_error( $results['errors'][0]['message'] );
					return false;
				}
				if( !empty($results[0]['code']) )
				{
					$id = $results[0]['code'];
					usam_update_location_metadata( $location, $this->deliver['handler'], $id );
				}
			}
		}
		return $id;
	}
	
	/**
	 * Расчет стоимости доставки
	 */
	public function get_shipping_cost( $args ) 
	{			
		if ( empty($args['location']) )
		{
			$this->set_error( __('Невозможно рассчитать доставку. Не указано местоположение.','usam') );
			return false;
		}					
		$to_city_id = $this->get_location( $args['location'] );
		if ( $to_city_id == false )		
		{
			$location = usam_get_location($args['location']);
			if ( empty($location) )
				$this->set_error( __('Невозможно рассчитать доставку. Не указано местоположение.','usam') );	
			else
				$this->set_error( sprintf(__('Невозможно рассчитать доставку. Не указан код СДЭК для города %s.','usam'),$location['name']) );
			return false;
		} 			
		$from_city_id = $this->get_handler_location();
		if ( $from_city_id == false )
			return false;	

		$index = usam_get_storage_metadata( $this->deliver['storage_id'], 'index' );
			
		$data = array();		
		$data['dateExecute'] = $this->dateExecute;		
		$data['authLogin'] = $this->deliver['login'];
		$data['secure'] = $this->get_Secure_Auth_Password();	
		$data['from_location'] = ['code' => $from_city_id, 'index' => $index];//город-отправитель
		$data['to_location'] = ['code' => $to_city_id, 'postal_code' => $args['index']];//город-получатель			
	//	$data['tariffId'] = $this->deliver['tariff_code']; //выбранный тариф
		$dimension_unit = get_option( 'usam_dimension_unit', '' );
		$j = 0;
		foreach ( $args['products'] as $product ) 		
		{					
			for ($i=1; $i<=$product->quantity; $i++)
			{	
				$length = usam_get_product_meta($product->product_id, 'length');
				if ( $length )						
					$data['packages'][$j]['length'] = round(usam_convert_dimension($length, $dimension_unit, 'cm'));
				else	
					$data['packages'][$j]['length'] = 8;
				$width = usam_get_product_meta($product->product_id, 'width');
				if ( $width )		
					$data['packages'][$j]['width'] = round(usam_convert_dimension($width, $dimension_unit, 'cm'));
				else	
					$data['packages'][$j]['width'] = 8;					
				$height = usam_get_product_meta($product->product_id, 'height');
				if ( $height )			
					$data['packages'][$j]['height'] = round(usam_convert_dimension($height, $dimension_unit, 'cm'));
				else	
					$data['packages'][$j]['height'] = 4;					
																		
				$weight = usam_get_product_weight( $product->product_id, 'g', false );
				if ( empty($weight) || $weight < 20 )
					$weight = 20;
				$data['packages'][$j]['weight'] = $weight;	
				$j++;							
			}
		}			
		$args = $this->get_request_args( $data, 'POST' );
		$results = $this->send_request( $args, 'calculator/tarifflist' );	
		$result = false;
		if ( !empty($results['tariff_codes']) )
		{
			usort($results['tariff_codes'], function($a, $b){ return ($a['delivery_sum'] - $b['delivery_sum']); });	
			foreach( $results['tariff_codes'] as $tariff ) 	
			{
				if ( !$this->deliver['tariff_codes'] || in_array($tariff['tariff_code'], $this->deliver['tariff_codes']) )
				{
					$result = ['price' => $tariff['delivery_sum']];
					break;
				}				
			}		
		}
		elseif ( !empty($results['errors']) )		
		{
			$this->set_error( $results['errors'][0] );
		}		
		return $result;
	}
		
	public function get_delivery_history( $barcode )
	{ 				
		if ( empty($this->deliver['login']) ||  empty($this->deliver['pass']) )
		{ 
			$this->set_error( __('Не указан логин или пароль.','usam') );
			return [];
		} 
		if ( empty($barcode) )
		{
			$this->set_error( __('Не указан номер почтового отправления.','usam') );
			return [];
		}		
		$args = $this->get_request_args( [], 'GET' );
		if ( stripos($barcode, '-') === false)
			$results = $this->send_request( $args, "orders?cdek_number=".$barcode );
		else
			$results = $this->send_request( $args, "orders/$barcode" );			
		$history = ['issued' => 0, 'date_delivery' => 0];
		if ( !empty($results["statuses"]) )
		{
			$history = [];
			foreach ( $results["statuses"] as $result ) 		
			{
				$date = usam_local_date( date("Y-m-d H:i:s",strtotime($result['date_time'])) );
				$to_city_id = $this->get_location( $result['office_id'] );
				if ( $to_city_id == false )	
					$location = usam_get_location( $to_city_id );
				$history['operations'][] = ['date' => $date, 'description' => $result['name'], 'name' => $result['city'], 'type_name' => $result['CODE'] ];	
				if ( $result['CODE'] == 'DELIVERED' ) //заказ получен
				{
					$history['issued'] = 1;
					$history['date_delivery'] = $date;
				}
			}		
			
			$history['recipient'] = $results['recipient']['name'];
			$history['sender_name'] = results['sender']['name'];
			$history['status_description'] = '';	
			$history['tariff_code'] = $results['tariff_code'];
			$history['weight'] = '';
			$history['payment'] = '';
		}
		return $history;
	}	
	
	//deliveryrecipientcost Доп. сбор за доставку, которую ИМ берет с получателя (в указанной валюте).
	//DeliveryRecipientVATRate	Ставка НДС, включенная в доп. сбор за доставку (подробнее см. приложение 4). Если значение не передано, то берется значение по умолчанию "Без НДС"
	public function create_order( $document_id )
	{
		global $wpdb;
		
		$date = date('c');		
		$document = usam_get_shipped_document( $document_id );
		
		$location_id = usam_get_order_metadata( $document['order_id'], 'shippinglocation' );
		if ( empty($location_id) )
			$location_id = usam_get_order_metadata( $document['order_id'], 'company_shippinglocation' );
		
		if ( $location_id == 0 )
			return false;
		
		$storage = usam_get_storage( $document['storage'] ); 		
		$to_city_id = $this->get_location( $location_id );
		$from_city_id = $this->get_location( $storage['location_id'] );	
		
		$phones = array();
		$phone = usam_get_order_customerdata( $document['order_id'], 'mobile_phone' );	
		if ( $phone )
			$phones[] = array( 'number' => $phone );
		$phone = usam_get_order_customerdata( $document['order_id'], 'phone' );	
		if ( $phone )
			$phones[] = array( 'number' => $phone );
		$email = usam_get_order_customerdata( $document['order_id'], 'email' );					
		$name = usam_get_order_metadata( $document['order_id'], 'company' );
		if ( $name )			
		{
			$recipient['company'] = usam_get_order_metadata( $document['order_id'], 'company' );
			$recipient['name'] = usam_get_order_metadata( $document['order_id'], 'contact_person' );
			$shippingaddress = usam_get_order_metadata( $document['order_id'], 'company_shippingaddress' );			
		}
		else
		{
			$names = array();
			$names[] = usam_get_order_metadata( $document['order_id'], 'billingfirstname' );
			$names[] = usam_get_order_metadata( $document['order_id'], 'billinglastname' );				
			$recipient['name'] = implode(",",$names);
			$shippingaddress = usam_get_order_metadata( $document['order_id'], 'shippingaddress' );
		}	
		$recipient['email'] = $email;
		$recipient['phones'] = $phones;
				
		$locations = usam_get_address_locations( $storage['location_id'] );	
		$from_location = array( 'code' => $from_city_id, 'city' => $locations['city'], 'address' => $storage['address'] );		
		$locations = usam_get_address_locations( $location_id );		
		$to_location = array( 'code' => $to_city_id, 'city' => $locations['city'], 'address' => $shippingaddress );		
		
		$note = usam_get_shipped_document_metadata($document['id'], 'note');
		$params = ['type' => $this->deliver['type_contract'], 'tariff_code' => $this->deliver['tariff_code'], 'number' => $document['id'], 'comment' => $note, 'recipient' => $recipient, 'to_location' => $to_location, 'from_location' => $from_location, 'packages' => ['number' => $document['id'], 'comment' => "Упаковка", 'weight' => 0, 'items' => []]];		
						
		$weight = 0;	
		$volume = 0;	
		
		$products = usam_get_products_order( $document['order_id'] );	
		$order_products = array();
		foreach ( $products as $product ) 
		{
			$order_products[$product->product_id] = $product;
		}
		$products = usam_get_products_shipped_document( $document_id );			
		foreach ( $products as $product ) 
		{						
			$product_weight = usam_get_product_weight( $product->product_id, 'g', false );	
			if ( !empty($product_weight) )
			{
				$weight += $product_weight * $product->quantity;
				$volume += usam_get_product_volume( $product->product_id ) * $product->quantity;	
				$sku = usam_get_product_meta( $product->product_id, 'sku' );
				$params['packages']['items'][] = array( 'name' => $order_products[$product->product_id]->name, 'ware_key' => $sku, 'cost' => $product->price, 'amount' => $product->quantity, 'weight' => $product_weight, 'url' => usam_product_url( $product->product_id ) );
			}
		}	
		$params['packages']['weight'] = $weight;					
		$args = $this->get_request_args( $params );
		$results = $this->send_request( $args, "orders" );	
		return $results;
	}
	
		/**
	 * Зашифрованный пароль для передачи на сервер
	 */
	private function get_Secure_Auth_Password() {
		return md5($this->dateExecute . '&' . $this->deliver['pass']);
	}
	
	private function get_request_args( $params = [], $method = 'POST', $add_token = true ) 
	{
		if ( $add_token )
		{
			$token = $this->get_token( "oauth/token" );
			if ( !$token )
				return false;				
			$headers["Authorization"] = 'Bearer '.$token;
		}
		$headers["Content-Type"] = 'application/json;charset=UTF-8';		
		$args = array(
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers
		); 
		if ( $params )
			$args['body'] = json_encode($params);		
		return $args;
	}
	
	protected function get_token_args( )
	{ 					
		if ( $this->deliver['login'] == '' || $this->deliver['pass'] == '' )
			return false;
		
		$args = [
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'body' => ['grant_type' => 'client_credentials', 'client_id' => $this->deliver['login'], 'client_secret' => $this->deliver['pass']],
		];	
		return $args;
	}	
		
	private function set_webhook() 
	{				
		$params = array( 'url' => home_url()."/api/shipping?id=".$this->deliver['id']."", 'type' => 'ORDER_STATUS' );	
		$args = $this->get_request_args( $params );
		$results = $this->send_request( $args, "webhooks" );		
		return $results;
	}
	
	public function match_locations( $page ) 
	{			
		if ( empty($this->deliver['login']) ||  empty($this->deliver['pass']) )
		{ 
			$this->set_error( __('Не указан логин или пароль.','usam') );
			return false;
		} 			
		$locations = usam_get_locations(['page' => $page, 'code' => 'city', 'number' => 100, 'cache_meta' => true]);		
		if ( !empty($locations) )	
		{
			foreach( $locations as $key => $location ) 
			{
				if ( !usam_get_location_metadata($location->id, $this->deliver['handler']) )
				{
					$FIAS = usam_get_location_metadata( $location->id, 'FIAS' );			
					if ( $FIAS )
					{			
						$results = $this->send_get_request( 'location/cities',['size' => 1, 'fias_guid' => $FIAS]);	
						if ( !empty($results['errors']) )
						{
							$this->set_error( $results['errors'][0]['message'] );
							return false;
						}
						if ( !empty($results[0]['code']) )
						{					
							usam_update_location_metadata($location->id, $this->deliver['handler'], $results[0]['code']);	
							if ( !usam_get_location_metadata($location->id, 'longitude') )
							{
								usam_update_location_metadata($location->id, 'longitude', $results[0]['longitude']);	
								usam_update_location_metadata($location->id, 'latitude', $results[0]['latitude']);		
							}
						}
						sleep(1);
					}			
				}
			}
			return true;
		}
		return false;
	}	
		
	public function set_delivery_warehouses( $paged = 0 )
	{ 
		if ( empty($this->deliver['login']) || empty($this->deliver['pass']) )
		{ 
			$this->set_error( __('Не указан логин или пароль.','usam') );
			return false;
		} 			
		$storages = usam_get_storages(['fields' => 'code=>data', 'active' => 'all', 'owner' => $this->deliver['handler'], 'cache_meta' => true]);
		$args = $this->get_request_args( [], 'GET' );	
		$results = $this->send_request( $args, "deliverypoints" );			
		if ( !empty($results) )
		{
			foreach ($results as $item) 
			{						
				if ( isset($storages[$item['code']]) )
				{
					$storage_id = $storages[$item['code']]->id;
					if ( !$storages[$item['code']]->location_id )
					{
						$location_id = usam_get_location_id_by_meta( $this->deliver['handler'], $item['location']['city_code'] );
						if ( !$location_id )
							$location_id = usam_get_locations(['search' => $item['location']['city'], 'fields' => 'id', 'number' => 1, 'code' => 'city']);					
						if ( $location_id )		
							usam_update_storage($storage_id, ['location_id' => $location_id]);	
					}				
				}
				else 
				{					//$item['type'] Тип ПВЗ: PVZ — склад СДЭК, POSTAMAT — постамат СДЭК
					$location_id = (int)usam_get_location_id_by_meta( $this->deliver['handler'], $item['location']['city_code'] );				
					if ( !$location_id )
						$location_id = usam_get_locations( ['search' => $item['location']['city'], 'fields' => 'id', 'number' => 1, 'code' => 'city'] );	
					if ( $location_id )	
						$storage_id = $this->insert_storage(['title' => $item['name'], 'code' => $item['code'], 'location_id' => $location_id]);
				}
				if ( $storage_id )
				{
					usam_update_storage_metadata( $storage_id, 'longitude', $item['location']['longitude']);
					usam_update_storage_metadata( $storage_id, 'latitude', $item['location']['latitude']);
					usam_update_storage_metadata( $storage_id, 'address', $item['location']['address']);					
					usam_update_storage_metadata( $storage_id, 'schedule', $item['work_time']); //График работы		
				}
			}
		}
		return false;	
	}	
	
	protected function send_get_request( $function, $params = [] )
	{				
		$token = $this->get_token( "oauth/token" );
		if ( !$token )
			return false;			
		
		$url = $this->get_url( $function );
		if ( $params )
			$url .= '?'.http_build_query($params, null, '&');	
		
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
		//	CURLOPT_POSTFIELDS => json_encode( $params ),
		//	CURLOPT_POSTFIELDS => $params,
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$token,
				'Content-Type: application/json;charset=UTF-8'
			],
		]
		);
		$data = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);			
		$resp = json_decode($data,true);
		if( isset($resp['error'] ) ) 
		{		
			$this->set_error( $resp['error'] );	
			return false;
		}				
		return $resp;		
	}
	
	public function get_options( ) 
	{
		return [
			['field_type' => 'text', 'name' => __('Account/Идентификатор', 'usam'), 'code' => 'login', 'default' => ''],
			['field_type' => 'text', 'name' => __('Secure password/Пароль', 'usam'), 'code' => 'pass', 'default' => ''],
			['field_type' => 'select', 'name' => __('Тип договора', 'usam'), 'code' => 'type_contract', 'default' => '1', 'multiple' => 0, 'options' => [
				['id' => 1, 'name' => __('Договор с интернет-магазином', 'usam')], 
				['id' => 2, 'name' => __('Другие договоры', 'usam')]]
			],
			['field_type' => 'select', 'name' => __('Код тарифа', 'usam'), 'code' => 'tariff_codes', 'default' => [136, 137, 138, 139, 366, 368, 233, 234, 378, 1, 361, 363, 3, 5, 10, 11, 12], 'multiple' => 1, 'options' => [
				['id' => 136, 'name' => __('Экономичная доставка склад-склад до 30 кг', 'usam')], 
				['id' => 137, 'name' => __('Экономичная доставка склад-дверь до 30 кг', 'usam')], 
				['id' => 138, 'name' => __('Экономичная доставка дверь-склад до 30 кг', 'usam')], 
				['id' => 139, 'name' => __('Экономичная доставка дверь-дверь до 30 кг', 'usam')], 
				['id' => 366, 'name' => __('Экономичная доставка дверь-постамат до 30 кг', 'usam')], 
				['id' => 368, 'name' => __('Экономичная доставка склад-постамат до 30 кг', 'usam')], 
				['id' => 233, 'name' => __('Экономичная доставка склад-дверь до 50 кг', 'usam')], 
				['id' => 234, 'name' => __('Экономичная доставка склад-склад до 50 кг', 'usam')], 
				['id' => 378, 'name' => __('Экономичная доставка склад-постамат до 50 кг', 'usam')], 
				['id' => 1, 'name' => __('Экспресс лайт дверь-дверь до 30 кг', 'usam')], 
				['id' => 361, 'name' => __('Экспресс лайт дверь-постамат до 30 кг', 'usam')], 
				['id' => 363, 'name' => __('Экспресс лайт склад-постамат до 30 кг', 'usam')], 
				['id' => 3, 'name' => __('Супер-экспресс до 18 дверь-дверь до 30 кг', 'usam')], 
				['id' => 5, 'name' => __('Экономичный экспресс склад-склад', 'usam')], 
				['id' => 10, 'name' => __('Экспресс лайт склад-склад до 30 кг', 'usam')], 
				['id' => 11, 'name' => __('Экспресс лайт склад-дверь до 30 кг', 'usam')], 
				['id' => 12, 'name' => __('Экспресс лайт дверь-склад до 30 кг', 'usam')], 
				['id' => 15, 'name' => __('Экспресс тяжеловесы склад-склад до 30 кг', 'usam')], 
				['id' => 16, 'name' => __('Экспресс тяжеловесы склад-дверь до 30 кг', 'usam')], 
				['id' => 17, 'name' => __('Экспресс тяжеловесы дверь-склад до 30 кг', 'usam')], 
				['id' => 18, 'name' => __('Экспресс тяжеловесы дверь-дверь до 30 кг', 'usam')], 
				['id' => 57, 'name' => __('Супер-экспресс до 9 дверь-дверь, до 30 кг', 'usam')], 
				['id' => 58, 'name' => __('Супер-экспресс до 10 дверь-дверь, до 30 кг', 'usam')], 
				['id' => 59, 'name' => __('Супер-экспресс до 12 дверь-дверь, до 30 кг', 'usam')], 
				['id' => 60, 'name' => __('Супер-экспресс до 14 дверь-дверь, до 30 кг', 'usam')], 
				['id' => 61, 'name' => __('Супер-экспресс до 16 дверь-дверь, до 30 кг', 'usam')], 
				['id' => 62, 'name' => __('Магистральный экспресс склад-склад, до 30 кг', 'usam')], 
				['id' => 63, 'name' => __('Магистральный супер-экспресс склад-склад', 'usam')], 
				['id' => 118, 'name' => __('Экономичный экспресс дверь-дверь', 'usam')], 
				['id' => 119, 'name' => __('Экономичный экспресс склад-дверь', 'usam')], 
				['id' => 120, 'name' => __('Экономичный экспресс дверь-склад', 'usam')], 
				['id' => 121, 'name' => __('Магистральный экспресс дверь-дверь', 'usam')], 
				['id' => 122, 'name' => __('Магистральный экспресс склад-дверь', 'usam')], 
				['id' => 123, 'name' => __('Магистральный супер-экспресс дверь-дверь', 'usam')], 
				['id' => 125, 'name' => __('Магистральный супер-экспресс склад-дверь', 'usam')], 
				['id' => 126, 'name' => __('Магистральный супер-экспресс дверь-склад', 'usam')], 
				['id' => 291, 'name' => __('Международный CDEK Express склад-склад', 'usam')], 
				['id' => 293, 'name' => __('Международный CDEK Express дверь-дверь', 'usam')], 
				['id' => 294, 'name' => __('Международный SDEK Express склад-дверь', 'usam')], 
				['id' => 295, 'name' => __('Международный CDEK Express дверь-склад', 'usam')], 
			]],	
		];
	}
}
?>