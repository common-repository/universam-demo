<?php 
/*
  Title: Транспортная компания SafeRoute
  Name: SafeRoute
  SELFPICKUP: Нет
  Location code: Да
  Order: Нет
 */
class USAM_Shipping_saferoute extends USAM_Shipping
{	
	protected $version = "2";	
	protected $API_URL = "https://api.saferoute.ru/";	
			
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
		$FIAS = usam_get_location_metadata( $args['location'], 'FIAS' );		
		if ( !$FIAS )		
		{
			$location = usam_get_location($args['location']);
			$this->set_error( sprintf(__('Невозможно рассчитать доставку. Не указан код ФИАС населенного пункта %s.','usam'), $location['name']) );
			return false;	
		}
		if ( !$args['weight'] )
			$args['weight'] = 0.5;			
		if ( !$args['volume'] )
			$args['volume'] = 0.000125;	
		$dimensions = round(pow($args['volume'], 1/3));		
		$data = ['reception' => ['cityFias' => $FIAS], 'weight' => $args['weight'], 'volume' => $args['volume'], 'dimensions' => ['width' => $dimensions, 'height' => $dimensions, 'length' => $dimensions]];
		$dimension_unit = get_option('usam_dimension_unit', '');		
		$i = 0;
		foreach ( $args['products'] as $key => $product ) 		
		{					
			$data['products'][$i]['vendorCode'] = usam_get_product_meta($product->product_id, 'barcode'); //если дефис вызывает ошибку
			$data['products'][$i]['priceDeclared'] = $product->price;
			if ( $this->deliver['priceCod'] )
				$data['products'][$i]['priceCod'] = $product->price;
	//		$data['products']['discount'] = $product->old_price - $product->price;			
			$length = usam_get_product_meta($product->product_id, 'length');
			if ( !empty($length) )						
				$data['products'][$i]['dimensions']['length'] = usam_convert_dimension($length, $dimension_unit, 'cm');
			else	
				$data['products'][$i]['dimensions']['length'] = 5;
			$width = usam_get_product_meta($product->product_id, 'width');
			if ( !empty($width) )		
				$data['products'][$i]['dimensions']['width'] = usam_convert_dimension($width, $dimension_unit, 'cm');
			else	
				$data['products'][$i]['dimensions']['width'] = 5;					
			$height = usam_get_product_meta($product->product_id, 'height');
			if ( !empty($height) )			
				$data['products'][$i]['dimensions']['height'] = usam_convert_dimension($height, $dimension_unit, 'cm');
			else	
				$data['products'][$i]['dimensions']['height'] = 5;	
			
			$data['products'][$i]['volume'] = usam_convert_dimension($data['products'][$i]['dimensions']['height'], 'cm', 'm')*usam_convert_dimension($data['products'][$i]['dimensions']['width'], 'cm', 'm')*usam_convert_dimension($data['products'][$i]['dimensions']['length'], 'cm', 'm');
																	
			$weight = usam_get_product_weight( $product->product_id, 'kg', false );
			if ( empty($weight) )
				$weight = 0.5;			
			$data['products'][$i]['weight'] = $weight;
			$data['products'][$i]['count'] = (float)$product->quantity;
			$i++;
		}
		if ( count($data['products']) == 1 && $data['products'][0]['count'] == 1 )
		{
			$data['dimensions']['width'] = $data['products'][0]['dimensions']['width'];
			$data['dimensions']['height'] = $data['products'][0]['dimensions']['height'];
			$data['dimensions']['length'] = $data['products'][0]['dimensions']['length'];
		}
		if ( $this->deliver['services'] )
			$data['servicesRequired'] = $this->deliver['services'];
		$args = $this->get_request_args( $data, 'POST', false );
		$results = $this->send_request( $args, 'calculator' );
			
		$min = 99999;
		$delivery_company = [];
		foreach ( $results as $deliveries ) 
		{
			foreach( $deliveries as $result ) 
			{
				if ( !empty($result['totalPrice']) && $min > $result['totalPrice'])
				{
					$delivery_company = $result;
					$min = $result['totalPrice'];
				}
			}
		}		
		if ( $delivery_company )
			return ['name' => $delivery_company['deliveryCompanyName'], 'price' => $delivery_company['totalPrice'], 'logo' => $delivery_company['deliveryCompanyLogo']];
		else		
			return false;
	}	
	
	protected function get_token_args( )
	{ 					
		if ( $this->deliver['email'] == '' || $this->deliver['pass'] == '' )
			return false;
		
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'body' => ['email' => $this->deliver['email'], 'password' => $this->deliver['pass']],
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
			$result = $this->send_request( $args, $function );		
			if ( isset($result['token']) )
			{ 
				$expiration = !empty($results['expires_in'])?$results['expires_in']:$this->expiration;
				set_transient( 'shipping_access_token_'.$this->id, $result['token'], $expiration );
				return $result['token'];
			}
		}
		return false;
	}
	
	private function get_request_args( $params = array(), $method = 'POST' ) 
	{
		$token = $this->get_token( "auth/login" );
		$headers["Content-Type"] = 'application/json;charset=UTF-8';	
		$headers["Shop-Id"] = $this->deliver['login'];	
		$headers["Authorization"] = 'Bearer ' . $token;	
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
	
	
	public function get_options( ) 
	{
		$checklist = [4 => 'Осмотр вложения', 6 => 'Частичный выкуп', 7 => 'Примерка', 15 => 'Сортировка на доставку', 18 => 'Бесконтактная доставка', 19 => 'Поставка в маркетплейс', 21 => 'Дропшиппинг', 22 => 'Температурный режим', 1 => 'Контроль состояния упаковки', 2 => 'Упаковать в пакет', 3 => 'Упаковать в коробку', 5 => 'Обрешётка', 8 => 'Пупырчатая плёнка', 9 => 'Комплектование', 10 => 'Комплектование за доп. артикулы', 11 => 'Комплектование за доп. товары', 12 => 'Составная сборка', 13 => 'Вложение в заказ, брендирование', 14 => 'Раскомплектование', 16 => 'Почтовая подготовка', 17 => 'Хранение', 20 => 'Сортировка на возврат'];
		return [
			['field_type' => 'text', 'name' => __('ID магазина', 'usam'), 'code' => 'login', 'default' => ''],	
			['field_type' => 'text', 'name' => __('E-mail', 'usam'), 'code' => 'email', 'default' => ''],			
			['field_type' => 'text', 'name' => __('Пароль', 'usam'), 'code' => 'pass', 'default' => ''],		
			['field_type' => 'BUTTONS', 'name' => __('Наложенный платёж', 'usam'), 'code' => 'priceCod', 'default' => '0'],
			['field_type' => 'select', 'name' => __('Список возможных услуг по доставке и упаковке', 'usam'), 'code' => 'services', 'default' => '', 'multiple' => 1, 'options' => $checklist],
		];
	}	
}
?>