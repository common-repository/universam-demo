<?php 
/*
  Title: Транспортная компания SAT
  Name: SAT
  SELFPICKUP: Нет
  Location code: Да
  Order: Нет
 */
class USAM_Shipping_sat extends USAM_Shipping
{	
	protected $version = "1.0";
	protected $API_URL = "http://urm.sat.ua/openws/hs/api/";
				
	protected function get_location( $location_id ) 
	{
		$id = usam_get_location_metadata( $location_id, $this->deliver['handler'] );
		if ( !$id )
		{
			$location = usam_get_location( $location_id );			
			if ( !empty($location['name']) )
			{			
				$type_location = usam_get_type_location($location['code'], 'code');
				$type_location['name'] = mb_strtolower($type_location['name']);
				$args = $this->get_request_args( array(), 'GET', false );
				$results = $this->send_request( $args, 'main/json/getTowns?searchString='.$location['name'] );				
				if ( !empty($results['data']) )
				{
					foreach( $results['data'] as $data) 		
					{
						if ( $location['name'] == $data['description'] && $type_location['name'] == $data['district'] )
						{
							$id = $data['ref'];
							break;
						}						
					}					
					if ( $id )
						usam_update_location_metadata( $location_id, $this->deliver['handler'], $id );
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
			$location = usam_get_location( $args['location'] );	
			if ( empty($location) )
				$this->set_error( __('Невозможно рассчитать доставку. Не указано местоположение.','usam') );	
			else
				$this->set_error( sprintf(__('Невозможно рассчитать доставку. Не указан код SAT для города %s.','usam'),$location['name']) );
			return false;	
		}		
		$from_city_id = $this->get_handler_location();
		if ( $from_city_id == false )
			return false;	
		$services = array();
		foreach( $this->deliver['services'] as $service) 
		{
			$services[] = array("service" => $service, "count" => 1);
		}		
		$data = array('ID' => $args['basket_id'], 'townSender' => $from_city_id, 'townRecipient' => $to_city_id, 'weight' => $args['weight'], 'cargoType' => $this->deliver['tariffs'], 'addServices' => $services);	
		$args = $this->get_request_args( $data, 'POST', false );
		$result = $this->send_request( $args, 'calc/json' );
		if ( !empty($result['data']) )
			return $result['data'][0]['cost'];
		else		
			return false;
	}
	
	public function get_delivery_history( $barcode )
	{ 
		if ( empty($this->deliver['token']) )
		{ 
			$this->set_error( __('Не указан ключ','usam') );
			return array();
		} 
		if ( empty($barcode) )
		{
			$this->set_error( __('Не указан номер почтового отправления.','usam') );
			return array();
		}
		$args = $this->get_request_args( array(), 'GET');
		$results = $this->send_request( $args, "tracking/json?number=$barcode&apiKey=".$this->deliver['token'] );		
		$history = array();		
		if ( !empty($results['data'][0]) )
		{
			if ( !empty($results['data'][0]["states"]) )
			{
				foreach ( $results['data'][0]["states"] as $result )
				{
					$history['operations'][] = array( 'date' => usam_local_date( date("Y-m-d H:i:s",strtotime($result['date'])) ), 'description' => $result['status'], 'name' => $result['town']);	
				}	
			}		
			$history['recipient'] = '';
			$history['sender_name'] = '';
			$history['status_description'] = $results["data"][0]['currentStatus'];
			$history['issued'] = $results["data"][0]['currentStatus'] == 'Доставлен получателю'?1:0;
			$history['tariff_code'] = '';
			$history['weight'] = !empty($results["data"][0]['weight'])?$results["data"][0]['weight']:'';
			$history['payment'] = '';		
		}
		return $history;
	}	
	
	private function get_request_args( $params = array(), $method = 'POST' ) 
	{
		$headers["Content-Type"] = 'application/json;charset=UTF-8';		
		$args = array(
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,			
		); 
		if ( !empty($params) )
			$args['body'] = json_encode($params); 
		return $args;
	}

	private function getCargoTypes( )
	{
		$args = $this->get_request_args( array(), 'GET' );
		$result = $this->send_request( $args, 'main/json/getCargoTypes' );	
		$results = [];
		if ( !empty($result['data']) )
		{
			foreach( $result['data'] as $type ) 
				$results[] = ['id' => $type['ref'], 'name' => $type['type'].' - '.$type['description']];
			return $results;
		}
		return $results;
	}
	
	private function getAdditionalServices( )
	{
		$args = $this->get_request_args( array(), 'GET' );
		$result = $this->send_request( $args, 'main/json/getAdditionalServices' );	
		$results = [];
		if ( !empty($result['data']) )
		{
			foreach( $result['data'] as $type ) 
				$results[] = ['id' => $type['ref'], 'name' => $type['description']];
		}
		return $results;
	}
	
	public function get_options( ) 
	{
		return [
			['field_type' => 'text', 'name' => __('API ключ', 'usam'), 'code' => 'token', 'default' => ''],			
			['field_type' => 'select', 'name' => __('Типы грузов', 'usam'), 'code' => 'tariffs', 'default' => 'Стандарт', 'multiple' => 0, 'options' => $this->getCargoTypes()],			
			['field_type' => 'select', 'name' => __('Дополнительные услуги', 'usam'), 'code' => 'services', 'default' => '', 'multiple' => 0, 'options' => $this->getAdditionalServices()],
		];
	}
}
?>