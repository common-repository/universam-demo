<?php 
/*
	Title: Транспортная компания Яндекс Доставка
	Name: Яндекс Доставка
	Points: Нет
	SELFPICKUP: Нет
	Location code: Да
	Order: Нет
 */
class USAM_Shipping_yandex extends USAM_Shipping
{	
	protected $version = "1";
	protected $API_URL = "https://b2b.taxi.yandex.net/b2b/cargo/integration/";
						
	/**
	 * Расчет стоимости доставки
	 */
	public function get_shipping_cost( $args ) 
	{						
		$data['requirements'] = ['cargo_loaders' => 0, 'cargo_options' => [$this->deliver['cargo_options']], 'cargo_type' => $this->deliver['cargo_type'], 'pro_courier' => (bool)$this->deliver['pro_courier'], 'taxi_class' => $this->deliver['taxi_class'] ];
		$storage = usam_get_storage( $args['storage'] ); 	
		if( $storage )
		{	
			$longitude = usam_get_storage_metadata( $storage['id'], 'longitude' );
			$latitude = usam_get_storage_metadata( $storage['id'], 'latitude' );
			if ( $longitude && $latitude )
				$data['route_points'][]['coordinates'] = [(float)$longitude, (float)$latitude];
		}		
		if ( !isset($data['route_points']) )
		{
			$current_location_id = get_option( 'usam_shop_location' );
			$longitude = usam_get_location_metadata( $current_location_id, 'longitude' );
			$latitude = usam_get_location_metadata( $current_location_id, 'latitude' );
			if ( $longitude && $latitude )
				$data['route_points'][]['coordinates'] = [(float)$longitude, (float)$latitude];
			else
				return false;
		}
		$address = $this->get_address_order( $args );
		if ( !$address )
			$data['route_points'][]['coordinates'] = usam_get_geocode( $address );		
		else
		{
			$longitude = usam_get_location_metadata( $args['location'], 'longitude' );
			$latitude = usam_get_location_metadata( $args['location'], 'latitude' );
			if ( $longitude && $latitude )
				$data['route_points'][]['coordinates'] = [(float)$longitude, (float)$latitude];
			else			
				return false;
		}			
		$data['skip_door_to_door'] = (bool)$this->deliver['skip_door_to_door'];
		$i = 0;
		foreach ( $args['products'] as $key => $product ) 		
		{					
			$data['items'][$i]['quantity'] = (int)$product->quantity;				
			$length = usam_get_product_meta($product->product_id, 'length');
			if ( !empty($length) )						
				$data['items'][$i]['size']['length'] = usam_convert_dimension($length, $dimension_unit, 'm');
			else	
				$data['items'][$i]['size']['length'] = 5;
			$width = usam_get_product_meta($product->product_id, 'width');
			if ( !empty($width) )		
				$data['items'][$i]['size']['width'] = usam_convert_dimension($width, $dimension_unit, 'm');
			else	
				$data['items'][$i]['size']['width'] = 5;					
			$height = usam_get_product_meta($product->product_id, 'height');
			if ( !empty($height) )			
				$data['items'][$i]['size']['height'] = usam_convert_dimension($height, $dimension_unit, 'm');
			else	
				$data['items'][$i]['size']['height'] = 5;																		
			$weight = usam_get_product_weight( $product->product_id, 'kg', false );
			if ( empty($weight) )
				$weight = 0.5;			
			$data['items'][$i]['weight'] = $weight;
			$i++;
		}		
		$args = $this->get_request_args( $data );
		$result = $this->send_request( $args, 'check-price' );			
		if ( !empty($result['price']) ) 
			return $result;
		elseif ( !empty($result['message']) )
		{
			$this->set_error( $result['message'] );
			return false;
		}
		else				
			return false;
	}
	
	protected function is_token()
	{
		if ( empty($this->deliver['token']) )
		{ 
			$this->set_error( __('Не указан ключ','usam') );
			return false;
		} 
		return true;
	}
		
	public function get_delivery_history( $barcode )
	{ 
		if ( !$this->is_token() )
			return array();
		
		if ( empty($barcode) )
		{
			$this->set_error( __('Не указан номер','usam') );
			return array();
		}
		$args = $this->get_request_args(['cursor' => $barcode]);
		$results = $this->send_request( $args, 'claims/journal' );
		$history = array();		
		if ( !empty($results['events']) )
		{			
			$history['recipient'] = '';
			$history['status_description'] = '';
			$history['sender_name'] = '';			
			$history['tariff_code'] = '';
			$history['weight'] = '';
			$history['issued'] = 0;
			$history['payment'] = '';		
			foreach ( $results['events'] as $result )
			{
				$history['operations'][] = array( 'date' => usam_local_date( $result['updated_ts'] ), 'name' => $result['new_status']);					
				$history['issued'] = $result['new_status'] == 'delivered_finish'?1:0;
			}						
		}
		elseif ( !empty($result['message']) )
		{
			$this->set_error( $result['message'] );
			return false;
		}
		return $history;
	}	
	
	private function get_request_args( $params = array(), $method = 'POST' ) 
	{
		//$headers["Content-Type"] = 'application/json;charset=UTF-8';		
		$headers["Authorization"] = 'Bearer '.$this->deliver['token'];
		$headers["Accept-Language"] = 'ru';
		$args = array(
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,	
		); 			
		$args['body'] = json_encode($params); 
		return $args;
	}
		
	public function get_options( ) 
	{
		return [
			['field_type' => 'text', 'name' => __('Токен', 'usam'), 'code' => 'token', 'default' => ''],
			['field_type' => 'select', 'name' => __('Вариант транспорта', 'usam'), 'code' => 'cargo_options', 'default' => 'auto_courier', 'options' => [
				['id' => 'auto_courier', 'name' => __('Курьер только на машине', 'usam')], 
				['id' => 'thermobag', 'name' => __('Курьер с термосумкой', 'usam')]
			]],
			['field_type' => 'select', 'name' => __('Тип (размер) кузова для грузового тарифа', 'usam'), 'code' => 'cargo_type', 'default' => 'van', 'options' => [
				['id' => 'van', 'name' => __('Маленький кузов', 'usam')], 
				['id' => 'lcv_m', 'name' => __('Средний кузов', 'usam')], 
				['id' => 'lcv_l', 'name' => __('Большой кузов', 'usam')]
			]],
			['field_type' => 'select', 'name' => __('Опция "Профи', 'usam'), 'code' => 'pro_courier', 'default' => '0', 'options' => [
				['id' => '0', 'name' => __('Нет', 'usam')], 
				['id' => '1', 'name' => __('Да', 'usam')], 
			]],
			['field_type' => 'select', 'name' => __('Класс автомобиля для доставки', 'usam'), 'code' => 'taxi_class', 'default' => 'courier', 'options' => [
				['id' => 'courier', 'name' => 'courier'], 
				['id' => 'express', 'name' => 'express'], 
				['id' => 'cargo', 'name' => 'cargo'], 
			]],
			['field_type' => 'select', 'name' => __('Вариант доставки', 'usam'), 'code' => 'skip_door_to_door', 'default' => '0', 'options' => [
				['id' => '0', 'name' => __('Доставка от двери до двери', 'usam')], 
				['id' => '1', 'name' => __('Курьер доставит заказ только на улицу, до подъезда', 'usam')], 
			]]		
		];
	}
}
?>