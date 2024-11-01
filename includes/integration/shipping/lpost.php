<?php 
/*
  Title: Транспортная компания Л-пост
  Name: Л-пост
  SELFPICKUP: Нет
  Location code: Да
  Order: Нет
 */
class USAM_Shipping_lpost extends USAM_Shipping
{	
	protected $API_URL = "https://api.l-post.ru/";
	//protected $API_URL = "https://apitest.l-post.ru/";
	protected $load_locations = true;	
	protected $expiration = 3600;
		
	protected function get_token_args( )
	{ 					
		if ( $this->deliver['secret'] == '' )
			return false;
		
		$headers["Cache-Control"] = 'no-cache';	
		$headers["Content-Type"] = 'application/x-www-form-urlencoded';	
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'body' => ['method' => 'Auth', 'secret' => $this->deliver['secret']],
		);	
		return $args;
	}	
	
	protected function get_token( $function = '' )
	{ 					
		$access_token = get_transient( 'shipping_access_token_'.$this->id );			
		if ( !empty($access_token) )
			return $access_token;	
						
		$args = $this->get_token_args();
		if ( !empty($args) )
		{
			$result = $this->send_request( $args );
			if ( isset($result['token']) )
			{ 
				//$expiration = !empty($result['valid_till'])?(strtotime($result['valid_till'])- time()):$this->expiration;
				set_transient( 'shipping_access_token_'.$this->id, $result['token'], $this->expiration );		
				return $result['token'];
			}
		}
		return false;
	}
	
	public function set_delivery_warehouses( $paged = 0 )
	{ 				
		$storages = usam_get_storages(['fields' => 'code=>data', 'owner' => $this->deliver['handler'], 'active' => 'all', 'cache_meta' => true]);
		$args = $this->get_request_args(['isCourier' => 0, 'method' => "GetPickupPoints", 'json' => []], 'GET');	
		$results = $this->send_request( $args );	
		if ( !empty($results['JSON_TXT']) )
		{			
			$results = json_decode($results['JSON_TXT'],true);
			$days = ['воскресенье' => 'вск', 'вторник' => 'вт', 'понедельник' => 'пн', 'пятница' => 'пт', 'среда' => 'ср', 'суббота' => 'сб', 'четверг' => 'чт'];
			foreach ($results['PickupPoint'] as $item) 
			{				
				$storage_id = 0;
				if ( isset($storages[$item['ID_PickupPoint']]) )
				{
					$storage_id = $storages[$item['ID_PickupPoint']]->id;
					if ( !$storages[$item['ID_PickupPoint']]->location_id )
					{
						$location_id = usam_get_locations( ['search' => $item['CityName'], 'fields' => 'id', 'number' => 1, 'code' => 'city'] );	
						if ( $location_id )		
							usam_update_storage($storage_id, ['location_id' => $location_id]);	
					}				
				}
				else 
				{
					$location_id = usam_get_locations( ['search' => $item['CityName'], 'fields' => 'id', 'number' => 1, 'code' => 'city'] );	
					if ( $location_id )	
						$storage_id = $this->insert_storage(['title' => $item['CityName'].' '.$item['Address'], 'code' => $item['ID_PickupPoint'], 'location_id' => $location_id]);
				}
				if ( $storage_id )
				{		
					if ( !empty($item['Longitude']) )
						usam_update_storage_metadata( $storage_id, 'longitude', $item['Longitude']);
					if ( !empty($item['Latitude']) )
						usam_update_storage_metadata( $storage_id, 'latitude', $item['Latitude']);
					usam_update_storage_metadata( $storage_id, 'address', $item['Address']);	
					
					usam_update_storage_metadata( $storage_id, 'cash', $item['IsCash']?1:0);	// Оплата наличными
					usam_update_storage_metadata( $storage_id, 'card', $item['IsCard']?1:0);	// Оплата картой
					$work_time = [];			
					foreach ($item['PickupPointWorkHours'] as $day) 
					{
						$work_time[] = $days[$day['Day']].' '.date('H:i', strtotime($day['From']) ).'-'.date('H:i', strtotime($day['To']) );
					}		
					usam_update_storage_metadata( $storage_id, 'schedule', implode(", ",$work_time)); //График работы			
				}
			}
		}
		return false;	
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
		$weight = round(usam_convert_weight($args['weight'], 'g'));
		if ( $weight > 30000 )
			$weight = 30000;
		$volume = round(usam_convert_volume($args['volume'], 'cm'));
		if ( $volume < 23 )
			$volume = 23;
		$data = ['Weight' => $weight, 'Volume' => $volume];			
		$services = [];
		foreach( $this->deliver['services'] as $service) 
		{
			$services[] = ["service" => $service, "count" => 1];
		}
		if ( $services )
			$data['addServices'] = $services;	
		
		if ( $this->deliver['delivery_option'] )
		{
			$storage = usam_get_storage( $args['storage'] ); 	
			if( $storage )
			{
				$data['ID_PickupPoint'] = (int)$storage['code'];		
				$longitude = usam_get_storage_metadata( $storage['id'], 'longitude' );
				$latitude = usam_get_storage_metadata( $storage['id'], 'latitude' );
				if ( $longitude && $latitude )
				{
					$data['Latitude'] = $latitude;
					$data['Longitude'] = $longitude;
				}
				else
				{
					$longitude = usam_get_location_metadata( $args['location'], 'longitude' );
					$latitude = usam_get_location_metadata( $args['location'], 'latitude' );
					if ( $longitude && $latitude )
					{
						$data['Latitude'] = $latitude;
						$data['Longitude'] = $longitude;
					}
					else
						return false;
				}
			}	
		}		
		else
		{
			$longitude = usam_get_location_metadata( $args['location'], 'longitude' );
			$latitude = usam_get_location_metadata( $args['location'], 'latitude' );
			if ( $longitude && $latitude )
			{
				$data['Latitude'] = $latitude;
				$data['Longitude'] = $longitude;
			}
			else
			{				
				$address = $this->get_address_order( $args );
				if ( $address )
					$data['Address'] = $address;	
				else
					return false;
			}
		}
		//$data['ID_Sklad'] = $storage['code'];	
		$args = $this->get_request_args(['method' => 'GetServicesCalc', 'json' => $data], 'GET');
		$results = $this->send_request( $args );
		if ( !empty($results['JSON_TXT']) )
		{		
			$results = json_decode($results['JSON_TXT'],true);	
			return $results['JSON_TXT'][0]['DeliveryCost'];
		}
		else		
			return false;
	}
	
	private function get_status( $status )
	{ 
		$statuses = ['CREATED' => 'Создано', 'ACT_CREATED' => 'Создан акт', 'READY_FOR_RETURN' => 'Готово к отгрузке на склад Лабиринт-Пост', 'SENT_TO_WAREHOUSE' => 'Отправлено на склад Лабиринт-Пост', 'ARRIVED_AT_THE_WAREHOUSE' => 'Прибыло на склад', 'ACCEPTED_BY_PLACES' => 'Принято по местам', 'SENT_TO_PICKUP_POINT' => 'Отправлено в Пункт доставки', 'PLACED_IN_PICKUP_POINT' => 'Размещено в пункте доставки', 'RECEIVED' => 'Выдано получателю', 'DONE' => 'Выполнено', 'CANCELLED' => 'Аннулировано', 'ARCHIVE' => 'Архив'];
		if ( isset($statuses[$status]) )
			return $statuses[$status];
		else
			return '';
	}
	
	public function get_delivery_history( $barcode )
	{ 
		if ( empty($this->deliver['apiKey']) )
		{ 
			$this->set_error( __('Не указан ключ','usam') );
			return array();
		} 
		if ( empty($barcode) )
		{
			$this->set_error( __('Не указан номер почтового отправления.','usam') );
			return array();
		}
		$args = $this->get_request_args(['method' => 'GetServicesCalc', 'json' => ['ID_Order' => $barcode]], 'GET');
		$results = $this->send_request( $args );		
		$history = array();		
		if ( !empty($results['JSON_TXT']) )
		{		
			$results = json_decode($results['JSON_TXT'],true);	
			if ( !empty($results["States"]) )
			{
				foreach ( $results["States"] as $result )
				{
					$history['operations'][] = array( 'date' => usam_local_date( date("Y-m-d H:i:s",strtotime($result['DateChange'])) ), 'description' => '', 'name' => $this->get_statuses( $result['StateDelivery']));	
				}	
			}			
			$history['recipient'] = '';
			$history['sender_name'] = '';
			$history['status_description'] = $history['operations'][0]['name'];
			$history['issued'] = $results["States"][0]['StateDelivery'] == 'RECEIVED'?1:0;
			$history['tariff_code'] = '';
			$history['weight'] = '';
			$history['payment'] = '';		
		}
		return $history;
	}	
	
	private function get_request_args( $params = [], $method = 'POST' ) 
	{
		$token = $this->get_token( );
		if ( !$token )
		{
			$this->set_error( __('Не указан секретный ключ или не удалось получить токен','usam') );
			return false;
		}		
		$params['token'] = $token;
		$params['ver'] = 1;		
		if ( isset($params['json']) )
			$params['json'] = json_encode($params['json']); 		
		$headers["Content-Type"] = 'application/x-www-form-urlencoded';		
		$args = array(
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,		
			'body' => $params,				
		); 
		return $args;
	}
	
	public function get_options( ) 
	{
		return [
			['field_type' => 'text', 'name' => __('Ключ', 'usam'), 'code' => 'secret', 'default' => ''],
		];
	}
}
?>