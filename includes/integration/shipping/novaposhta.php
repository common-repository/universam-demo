<?php 
/*
	Title: Транспортная компания Новая почта
	Points: Да
	SELFPICKUP: Да
	Name: Новая почта
	Location code: Да
	Order: Нет
 */
class USAM_Shipping_novaposhta extends USAM_Shipping
{		
	protected $API_URL = "https://api.novaposhta.ua/v2.0/json/";
	protected $load_locations = true;
	
	public function get_delivery_warehouses( $paged, $language )
	{		
		$params = ['modelName' => 'AddressGeneral', 'calledMethod' => 'getWarehouses', 'methodProperties' => ['Language' => $language, 'Page' => $paged, 'Limit' => 500]];	
		$args = $this->get_request_args( $params );		
		$result = $this->send_request( $args );
		
		$warehouses = array();			
		if ( !empty($result['data']) )
			return $result['data'];
		return array();
	}

	public function set_delivery_warehouses( $paged = 0 )
	{				
		//$language = usam_get_contact_language();
	//	if ( !$language )
		//	$language = 'ua';
		$language = 'ru';		
		$delivery_warehouses = $this->get_delivery_warehouses( $paged, $language );	
		if ( !empty($delivery_warehouses) )
		{
			foreach ( $delivery_warehouses as $item ) 
			{		
				$location_id = 0;
				$storage = usam_get_storages(['code' => $item['Ref'], 'active' => 'all', 'owner' => $this->deliver['handler'], 'number' => 1]);			
				if ( $storage )
				{					
					$storage_id = $storage['id'];
					if ( !$storage['location_id'] )
					{						
						$location_id = usam_get_location_id_by_meta( $this->deliver['handler'], $item['CityRef'] );	
						if ( $location_id )
							usam_update_storage($storage_id, ['shipping' => 0, 'location_id' => $location_id]);	
					}
					else
						$location_id = $storage['location_id'];
				}
				else
				{									
					$location_id = (int)usam_get_location_id_by_meta( $this->deliver['handler'], $item['CityRef'] );					
					$title = $language == 'ru'? $item['DescriptionRu'] : $item['Description'];
					$storage_id = $this->insert_storage(['title' => $title, 'code' => $item['Ref'], 'location_id' => $location_id]);					
					usam_update_storage_metadata( $storage_id, 'longitude', $item['Longitude']);
					usam_update_storage_metadata( $storage_id, 'latitude', $item['Latitude']);					
					usam_update_storage_metadata( $storage_id, 'max_weight_allowed', $item['TotalMaxWeightAllowed']); //Максимальные габариты отправления для отправки
				//	usam_update_storage_metadata( $storage_id, 'max_dimensions_allowed', $item['ReceivingLimitationsOnDimensions']); //Максимальные габариты отправления для получения	
				}
				if ( $storage_id )
				{
					$schedule = 'пн '.$item['Schedule']['Monday'].', вт '.$item['Schedule']['Tuesday'].', ср '.$item['Schedule']['Wednesday'].', чт '.$item['Schedule']['Thursday'].', пт '.$item['Schedule']['Friday'];
					if ( !empty($item['Schedule']['Saturday']) )
						$schedule .= ', сб '.$item['Schedule']['Saturday'];
					if ( !empty($item['Schedule']['Sunday']) )
						$schedule .= ', вск '.$item['Schedule']['Sunday'];
					
					$address = $language == 'ru' ? $item['ShortAddressRu'] : $item['ShortAddress'];							
					if ( $location_id )
					{
						if (false !== ($pos = strpos($address, ',')))
							$address = substr($address, $pos+2, mb_strlen($address));
					}					
					usam_update_storage_metadata( $storage_id, 'address', $address);
					usam_update_storage_metadata( $storage_id, 'branch_number', $item['Number']);	
					usam_update_storage_metadata( $storage_id, 'schedule', $schedule); //График работы							
				}
			}
		}
		else
			return false;		
		return true;
	}	
	
	// Загрузить коды местоположений
	public function match_locations( $page ) 
	{		
		$locations = usam_get_locations( );
		$args = $this->get_request_args(['modelName' => 'Address', 'calledMethod' => 'getCities', 'methodProperties' => ['Page' => $page, 'Limit' => 500]]);
		$result = $this->send_request( $args );	
		if ( !empty($result['data']) )
		{				
			foreach ( $result['data'] as $item ) 
			{					
				foreach ( $locations as $key => $location ) 
				{
					if ($location->name == $item['DescriptionRu'] || $location->name == $item['Description'])
					{
						usam_update_location_metadata($location->id, $this->deliver['handler'], $item['Ref']);	
						break;
					}
				}
			}
			return true;
		}
		elseif ( !empty($result['errors']) )
			$this->set_error( $result['errors'] );
		return false;
	}	
	
	//Загрузить координаты
	public function match_locations0( $page ) 
	{	
		$locations = usam_get_locations( );
		$args = $this->get_request_args(['modelName' => 'AddressGeneral', 'calledMethod' => 'getSettlements', 'methodProperties' => ['Page' => $page, 'Limit' => 150]]);
		$types = usam_get_types_location( 'code' );
		$result = $this->send_request( $args );	
		if ( !empty($result['data']) )
		{			
			foreach ( $result['data'] as $item ) 
			{					
				foreach ( $locations as $key => $location ) 
				{			
					if( (mb_strtolower($types[$location->code]->name) == $item['SettlementTypeDescriptionRu']) && ($location->name == $item['DescriptionRu'] || $location->name == $item['Description']))
					{
						//usam_update_location_metadata($location->id, 'longitude', $item['Longitude']);
					//	usam_update_location_metadata($location->id, 'latitude', $item['Latitude']);	
						usam_update_location_metadata($location->id, $this->deliver['handler'], $item['SettlementType']);							
						unset($locations[$key]);
						break;
					}
				}
			}
			return true;
		}
		elseif ( !empty($result['errors']) )
			$this->set_error( $result['errors'] );		
		return false;
	}	
	
	protected function get_location( $location_id ) 
	{ 
		$id = usam_get_location_metadata( $location_id, $this->deliver['handler'] );
		if ( !$id )
		{			
			$location = usam_get_location( $location_id );
			$params = array('modelName' => 'Address', 'calledMethod' => 'getCities', 'methodProperties' => ['FindByString' => $location['name']]);	
			$args = $this->get_request_args( $params );
			$result = $this->send_request( $args );	
			if ( !empty($result['errors']) )
			{
				$this->set_error( $result['errors'] );
				return false;
			}
			if ( !empty($result['data'][0]['Ref']) )
			{
				$id = $result['data'][0]['Ref'];
				usam_update_location_metadata( $location_id, $this->deliver['handler'], $id );
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
				$this->set_error( sprintf(__('Невозможно рассчитать доставку. Не указан код для города %s.','usam'),$location['name']) );
			return false;
		} 			
		$from_city_id = $this->get_handler_location();		
		if ( $from_city_id == false )	
			return false;
			
		$data = array('modelName' => 'InternetDocument', 'calledMethod' => 'getDocumentPrice');	
		$data['methodProperties'] = ['CitySender' => $from_city_id, 'CityRecipient' => $to_city_id, 'Weight' => $args['weight'], 'ServiceType' => $this->deliver['service_type'], 'Cost' => $args['subtotal'], 'CargoType' => $this->deliver['cargo_type'], 'SeatsAmount' => 1];
		$args = $this->get_request_args( $data );	
		$result = $this->send_request( $args );
		if ( !empty($result['data']) )
			return $result['data'][0]['Cost'];
		else		
			return false;
	}
		
	public function get_delivery_history( $barcode )
	{ 
		if ( empty($barcode) )
		{
			$this->set_error( __('Не указан номер почтового отправления.','usam') );
			return array();
		}
		$params = array('modelName' => 'TrackingDocument', 'calledMethod' => 'getStatusDocuments', 'methodProperties' => array('Documents' => array( array('DocumentNumber' => $barcode) )));	
		$args = $this->get_request_args( $params );		
		$result = $this->send_request( $args );
		$history = array();			
		if ( !empty($result['data']) )
		{			
			if ( $result['data'][0]['StatusCode'] != 3 )
			{			
				$history['operations'][] = array( 'date' => usam_local_date( date("Y-m-d H:i:s",strtotime($result['data'][0]['ScheduledDeliveryDate'])) ), 'description' => $result['data'][0]['WarehouseRecipient'], 'name' => $result['data'][0]['CityRecipient'], 'type_name' => $result['data'][0]['Status'] );
				
				$history['recipient'] = $result["data"][0]['RecipientFullName'];
				$history['sender_name'] = $result["data"][0]['RecipientFullName'];
				$history['status_description'] = $result["data"][0]['Status'];
				$history['issued'] = $result["data"][0]['Status'] == 9?1:0;
				$history['tariff_code'] = '';
				$history['weight'] = $result["data"][0]['VolumeWeight'];
				$history['payment'] = $result["data"][0]['DocumentCost'];				
			}
		}
		return $history;
	}	
		
	private function get_request_args( $params = array(), $method = 'POST', $add_token = true ) 
	{
		if ( $add_token )
			$params['apiKey'] = $this->deliver['pass'];	
		$headers["Content-Type"] = 'application/json;charset=UTF-8';		
		$args = array(
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => json_encode($params) 
		); 
		return $args;
	}
	
	public function get_cargo_types( )
	{ 
		$params = array('modelName' => "Common", 'calledMethod' => "getCargoTypes"); 
		$args = $this->get_request_args( $params );		
		$result = $this->send_request( $args );
		$results = [];
		if ( !empty($result['data']) )
		{			
			foreach( $result['data'] as $type ) 
				$results[] = ['id' => $type['Ref'], 'name' => $type['Description']];
		}
		return $results;
	}	
				
	public function get_options( ) 
	{
		$cargo_types = $this->get_cargo_types();
		return [
			['field_type' => 'text', 'name' => __('API ключ', 'usam'), 'code' => 'pass', 'default' => ''],			
			['field_type' => 'select', 'name' => __('Тип услуги', 'usam'), 'code' => 'service_type', 'default' => 'DoorsWarehouse', 'multiple' => 0, 'options' => [
				['id' => 'DoorsWarehouse', 'name' => __('дверь-склад', 'usam')], 
				['id' => 'WarehouseDoors', 'name' => __('склад-дверь', 'usam')], 
				['id' => 'DoorsDoors', 'name' => __('дверь-дверь', 'usam')], 	
				['id' => 'WarehouseWarehouse', 'name' => __('склад-склад', 'usam')],
			]],			
			['field_type' => 'select', 'name' => __('Тип груза', 'usam'), 'code' => 'cargo_type', 'default' => 'Parcel', 'multiple' => 0, 'options' => $cargo_types],
		];
	}
}
?>