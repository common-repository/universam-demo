<?php 
/*
	Title: Транспортная компания УкрПошта	
	Points: Нет
	SELFPICKUP: Нет
	Name: УкрПошта
	Location code: Нет
	Order: Нет
 */
class USAM_Shipping_ukrposhta extends USAM_Shipping
{		
//	protected $API_URL = "https://www.ukrposhta.ua/ecom/0.0.1/";
	protected $API_URL = "https://dev.ukrposhta.ua/ecom/0.0.1/";
	protected $load_locations = true;
		
	protected function send_request( $params, $function = '' )
	{				
		$url = $this->get_url( $function );	
		$data = wp_remote_post( $url, $params );			
		if ( is_wp_error($data) )
		{
			$message = $data->get_error_message();
			$this->set_error( $message );	
			return false;
		} 						
		$resp = json_decode($data['body'],true);	
		if ( isset($resp['error'] ) ) 
		{		
			$this->handle_request_errors( $resp['error'] );	
			return false;
		}				
		return $resp;		
	}	
	
	/**
	 * Расчет стоимости доставки
	 */
	public function get_shipping_cost( $args ) 
	{
		if ( empty($args['index']) )
		{
			$this->set_error( __('Невозможно рассчитать доставку. Не указан индекс.','usam') );
			return false;
		}				
		$length = 0;
		foreach ( $args['products'] as $product ) 		
		{					
			$product_length = usam_get_product_meta($product->product_id, 'length');
			if ( $length < $product_length )
				$length = $product_length;
		}		
		$storage_id = !empty($this->deliver['storage_id'])?$this->deliver['storage_id']:0;
		$index = usam_get_storage_metadata($storage_id, 'index'); 	
		$data = ['weight' => $args['weight'], 'length' => $length, 'addressFrom' => ['postcode' => $index], 'addressTo' => ['postcode' => $args['index']], 'type' => strtoupper($this->deliver['service_type']), 'deliveryType' => strtoupper($this->deliver['deliver_type']), 'sms' => (bool)$this->deliver['sms'], 'withDeliveryNotification' => (bool)$this->deliver['withDeliveryNotification']];

		$args = $this->get_request_args( $data );	
		$result = $this->send_request( $args, "domestic/delivery-price" );
		if ( !empty($result['deliveryPrice"']) )
			return $result['deliveryPrice"'];
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
		$args = $this->get_request_args(['token' => $this->deliver['token'], 'GET']);		
		$result = $this->send_request( $args, "transfers/shipment-postpays/$barcode/with-recipient");
		$history = array();			
		if ( !empty($result['recipientName']) )
		{		
			$history['operations'][] = ['date' => usam_local_date( date("Y-m-d H:i:s",strtotime($result['lastStatusTime'])) ), 'description' => '', 'name' => $result['lastStatusNameRu']];
			
			$history['recipient'] = $result["recipientName"];
			$history['sender_name'] = '';
			$history['status_description'] = $result['lastStatusNameRu'];
			$history['issued'] = $result['lastStatusNameRu'] == 'дослан'?1:0;
			$history['tariff_code'] = '';
			$history['weight'] = '';
			$history['payment'] = $result['sum'];	
		}
		return $history;
	}	
		
	private function get_request_args( $params = array(), $method = 'POST', $add_token = true ) 
	{
		if ( $add_token )
			$headers['Authorization'] = "Bearer ".$this->deliver['authorization_bearer'];	
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
	
	
	public function get_options( ) 
	{
		return [
			['field_type' => 'text', 'name' => __('Токен', 'usam'), 'code' => 'token', 'default' => ''],
			['field_type' => 'text', 'name' => 'Authorization bearer', 'code' => 'authorization_bearer', 'default' => ''],			
			['field_type' => 'select', 'name' => __('Тип услуги', 'usam'), 'code' => 'service_type', 'default' => 'standart', 'multiple' => 0, 'options' => [
				['id' => 'standart', 'name' => __('Стандарт', 'usam')], 
				['id' => 'express', 'name' => __('Экспресс', 'usam')], 
			]],			
			['field_type' => 'select', 'name' => __('Тип доставки', 'usam'), 'code' => 'deliver_type', 'default' => 'W2D', 'multiple' => 0, 'options' => [
				['id' => 'W2D', 'name' => __('склад-дверь', 'usam')], 
				['id' => 'W2W', 'name' => __('склад-склад', 'usam')], 
				['id' => 'D2W', 'name' => __('дверь-склад', 'usam')], 	
				['id' => 'D2D', 'name' => __('дверь-дверь', 'usam')], 	
			]],
			['field_type' => 'BUTTONS', 'name' => __('SMS уведомление', 'usam'), 'code' => 'sms', 'default' => 0],
			['field_type' => 'BUTTONS', 'name' => __('Отправка с уведомлением о вручении', 'usam'), 'code' => 'withDeliveryNotification', 'default' => 1],
		];
	}
}
?>