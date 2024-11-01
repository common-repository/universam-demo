<?php 
/*
	Title: Транспортная компания Автолюкс
	Points: Нет
	SELFPICKUP: Нет
	Name: Автолюкс Глобал Пост
	Location code: Да
	Order: Нет
 */
class USAM_Shipping_autolux extends USAM_Shipping
{	
	protected $load_locations = true;
	protected $test = false;	
	protected $API_URL = "https://api.autolux-post.com.ua/";
		
	protected function get_token_args( )
	{ 					
		$params = array( 'email' => $this->deliver['login'], 'password' => $this->deliver['pass']);
		$headers["Content-Type"] = "application/x-www-form-urlencoded"; 		
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => $params 
		);			
		return $args;
	}	
	
	public function get_request_args( $params = array(), $method = 'POST' )
	{ 							
		if ( $method == 'POST' )
			$params['access_token'] = $this->get_token('authentication/login');
		$headers["Content-Type"] = "Content-Type: application/json"; 		
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
	
	public function match_locations( $page) 
	{		
		$locations = usam_get_locations( );		
		
		$args = $this->get_request_args( array(), 'GET' );
		$results = $this->send_request( $args, 'office/offices_by_territorial_units' );			
		if ( !empty($results) )
		{			
			foreach ( $results as $item ) 
			{					
				foreach ( $locations as $key => $location ) 
				{ 					
					if ( $location->name == $item['name_ru'] || $location->name == $item['name_ua'] )
					{ 
						usam_update_location_metadata( $location->id, $this->deliver['handler'], $item['id'] );
						unset($locations[$key]);
						break;
					}
				}
			}
		}	
		return false;
	}		
	
	public function get_delivery_history( $barcode )
	{ 
		if ( empty($this->deliver['login']) ||  empty($this->deliver['pass']) )
		{ 
			$this->set_error( __('Не указан логин или пароль.','usam') );
			return array();
		}
		if ( empty($barcode) )
		{
			$this->set_error( __('Не указан номер почтового отправления.','usam') );
			return array();
		}		
		$args = $this->get_request_args( array(), 'GET' );
		$results = $this->send_request( $args, 'shipment/search?shipment_id='.$barcode );	
		$history = array();		
		
		foreach ( $results["invoices"] as $result ) 		
		{
			$location = usam_get_locations( array( 'meta_value' => $result['office_id'], 'meta_key' => $this->deliver['handler'], 'number' => 1 ) );	
			$location_name = isset($location->name)?$location->name:'';			
			$history['operations'][] = array( 'date' => usam_local_date( date("Y-m-d H:i:s",strtotime($result['create_date']['date'])) ), 'description' => $result['service'], 'name' => $location_name, 'type_name' => '' );	
		}		
		$history['recipient'] = '';
		$history['sender_name'] = '';
		$history['tariff_code'] = '';	
		return $history;
	}
					
	public function get_shipping_cost( $args )
	{
		if ( empty($args['weight']) )
		{
			$this->set_error( __('Невозможно рассчитать доставку. Не указан вес товаров.','usam') );
			return false;
		}	
		$to_city_id = $this->get_location( $args['location'] );			
		if ( $to_city_id == false )		
		{
			$location = usam_get_location($args['location']);
			if ( empty($location) )
				$this->set_error( __('Невозможно рассчитать доставку. Не указано местоположение.','usam') );	
			else
				$this->set_error( sprintf(__('Невозможно рассчитать доставку. Не указан код Автолюкс для города %s.','usam'),$location['name']) );
			return false;
		} 			
		$from_city_id = $this->get_handler_location();
		if ( $from_city_id == false )
			return false;
		
		$args['volume'] = 0;
		foreach ( $args['products'] as $product ) 		
			$args['volume'] += usam_get_product_volume( $product->product_id ) * $product->quantity;	

		if ( empty($args['volume']) )
		{
			$this->set_error( __('Невозможно рассчитать доставку. Не указаны размеры товаров.','usam') );
			return false;
		}						
		$phone = usam_get_customer_checkout('billingmobilephone');
		if ( !$phone )
			$phone = usam_get_customer_checkout('company_phone');
			
		$receivers = array("persons" => array("phone" => $phone));
		$params = ['box_quantity' => 1, 'volume' => $args['volume'], 'weight' => $args['weight'], 'office_to_id' => $to_city_id, 'office_from_id' => $from_city_id, 'receivers' => $receivers, 'preview' => true];
		if( $this->deliver['insurance'] ) //insurance страхование
			$params['insurance'] = $args['subtotal'];
			
		$params['senders'] = ['person_id' => usam_get_delivery_service_metadata($this->deliver['id'], 'autolux_person_id'), 'company_id' => usam_get_delivery_service_metadata($this->deliver['id'], 'autolux_company_id')];
						
		/*
"volume": 0,
"width": 691,
"weight": 1,
"length": 0,
"height": 379,
"service_30": 0,
"service_10": 0,
"shipment_type_id": 1,
"receivers": {
"persons": [
{
"phone": "0505867374",
"first_name": "Татьяна",
"last_name": "Драгомирецкая"
}
]
},
"use_discount": true,
"service_11": 330,
"door_to_door": {
"delivery_address": "Киев, ул. Маяковского, 30",
"take_address": ""
},
"description": "взуття",
}*/

		
		$args = $this->get_request_args( $params );
		$result = $this->send_request( $args, 'shipment' );	
		$sum = 0;
		if ( !empty($result['invoices']) )
		{
			foreach ( $result['invoices'] as $invoice ) 	
				$sum += $invoice['sum_total'];
		}
		return $sum;	
	}	
	
	private function get_ratio( )
	{
		$args = $this->get_request_args( array(), 'GET' );
		$result = $this->send_request( $args, 'ratio' );	
		$results = [];
		if ( !empty($result['status']) )
		{
			foreach( $result['status'] as $type ) 
				$results[] = ['id' => $type['id'], 'name' => $type['description']];
		}
		return $results;			
	}
	
	public function get_url( $function ) 
	{		
		if ( $this->version )
			$url = "{$this->API_URL}v{$this->version}/{$function}";
		else
			$url = "{$this->API_URL}{$function}";
		
		if ( $function != 'authentication/login' && $function != 'shipment' )
			$url .= "?access_token=".$this->get_token('authentication/login');		
		return $url;
	}
	
	private function set_company_id( )
	{
		$args = $this->get_request_args( array(), 'GET' );
		$result = $this->send_request( $args, 'authentication/check_access_token' );
		if ( !empty($result['person']) )
		{
			usam_update_delivery_service_metadata($this->deliver['id'], 'autolux_person_id', $result['person']['id']);
			usam_update_delivery_service_metadata($this->deliver['id'], 'autolux_company_id', $result['office']['company_id']);
		}
	}	
	
	public function get_options( ) 
	{
		return [
			['field_type' => 'text', 'name' => __('Логин', 'usam'), 'code' => 'login', 'default' => ''],			
			['field_type' => 'text', 'name' => __('Пароль', 'usam'), 'code' => 'pass', 'default' => ''],
			['field_type' => 'BUTTONS', 'name' => __('Страхование', 'usam'), 'code' => 'insurance', 'default' => '0'],
			['field_type' => 'select', 'name' => __('Варианты тарифов', 'usam'), 'code' => 'tariffs', 'default' => '', 'multiple' => 1, 'options' => $this->get_ratio()],
		];
	}
}
?>