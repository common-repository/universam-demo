<?php 
/*
  Title: Транспортная компания Boxberry
  Name: Boxberry
  Points: Да
  SELFPICKUP: Да
  Location code: Да
  Order: Нет
 */
class USAM_Shipping_boxberry extends USAM_Shipping
{	
	protected $version = "";
	protected $load_locations = true;	
	protected $API_URL = "https://api.boxberry.ru/json.php";
			
	public function match_locations( $page ) 
	{			
		if ( !$this->is_token() )
			return false;			
		$location_ids = usam_get_locations(['fields' => 'id', 'code' => ['city','region']]);							
		if ( !empty($location_ids) )	
		{
			require_once( USAM_FILE_PATH . '/includes/directory/country_query.class.php'  );
			$countries = usam_get_countries(['fields' => 'numerical', 'conditions' => ['key' => 'location_id', 'value' => 0, 'compare' => '!=']]);
			$args = ['method' => 'ListCitiesFull'];
			if ( $countries )
				$args['CountryCode'] = $countries[$paged];		
			$args = $this->get_request_args( $args );
			$results = $this->send_request( $args );			
			if ( !empty($results['err']) )
			{
				$this->set_error( $results['err'] );
				return false;
			}			
			foreach( $results as $result ) 
			{
				foreach( $location_ids as $location_id ) 
				{
					if ( !usam_get_location_metadata($location_id, $this->deliver['handler']) )
					{
						$KLADR = usam_get_location_metadata( $location_id, 'KLADR' );			
						if ( $KLADR == $result['Kladr'] )
							usam_update_location_metadata($location_id, $this->deliver['handler'], $result['Code']);	
					}
				}
			}
		}
		return false;
	}	
	
	public function set_delivery_warehouses( $paged = 0 )
	{ 
		if ( !$this->is_token() )
			return false;		
		$storages = usam_get_storages(['fields' => 'code=>data', 'owner' => $this->deliver['handler'], 'cache_meta' => true]);
		require_once( USAM_FILE_PATH . '/includes/directory/country_query.class.php'  );
		$countries = usam_get_countries(['fields' => 'numerical', 'numerical' => [643, 398, 112, 417, 051], 'conditions' => ['key' => 'location_id', 'value' => 0, 'compare' => '!=']]);
		
		$args = ['method' => 'ListPoints'];
		if ( $countries )
			$args['CountryCode'] = $countries[$paged];
		$args = $this->get_request_args( $args, 'GET' );	
		$results = $this->send_request( $args );	
		if ( !empty($results) )
		{
			foreach ($results as $item) 
			{	
				if ( !empty($item['err']) )		
				{					
					$this->set_error( $item['err'] );
					break;
				}
				if ( isset($storages[$item['Code']]) )
				{
					$storage_id = $storages[$item['Code']]->id;
					if ( !$storages[$item['Code']]->location_id )
					{
						$location_id = usam_get_location_id_by_meta( $this->deliver['handler'], $item['CityCode'] );
						if ( !$location_id )
							$location_id = usam_get_locations(['search' => $item['CityName'], 'fields' => 'id', 'number' => 1, 'code' => 'city']);					
						if ( $location_id )		
							usam_update_storage($storage_id, ['location_id' => $location_id]);							
					}				
				}
				else 
				{					
					$location_id = (int)usam_get_location_id_by_meta( $this->deliver['handler'], $item['CityCode'] );				
					if ( !$location_id )
						$location_id = usam_get_locations( ['search' => $item['CityName'], 'fields' => 'id', 'number' => 1, 'code' => 'city'] );	
					if ( $location_id )	
						$storage_id = $this->insert_storage(['title' => $item['Name'], 'code' => $item['Code'], 'location_id' => $location_id]);
				}
				if ( $storage_id )
				{
					$GPS = explode(',',$item['GPS']);	
					usam_update_storage_metadata( $storage_id, 'longitude', $GPS[1]);
					usam_update_storage_metadata( $storage_id, 'latitude', $GPS[0]);
					usam_update_storage_metadata( $storage_id, 'phone', $item['Phone']);
					usam_update_storage_metadata( $storage_id, 'address', $item['Address']);					
					usam_update_storage_metadata( $storage_id, 'schedule', $item['WorkShedule']); //График работы	
				}
			}
		}
		if ( count($countries) >= $paged )
			return false;
		return true;	
	}	
			
	/**
	 * Расчет стоимости доставки
	 */
	public function get_shipping_cost( $args ) 
	{ 
		$data = ['weight' => usam_convert_weight( $args['weight'], 'g', 'kg' ), 'method' => 'DeliveryCosts'];			
		$storage_id = !empty($this->deliver['storage_id'])?$this->deliver['storage_id']:0;
		
	//	$storage = usam_get_storage( $storage_id ); 		
	//	$data['targetstart'] = $storage['code'];		
		if ( $this->deliver['delivery_option'] )
		{
			$storage = usam_get_storage( $args['storage'] ); 			
			if( $storage )
				$data['target'] = $storage['code'];	 
		} 
		if ( empty($data['target']) )
		{
			if ( $args['index'] )
				$data['zip'] = $args['index'];				
			else
				$data['zip'] = usam_get_location_metadata( $args['location'], 'index' ); 
			if ( !$data['zip'] )
				return false;		
		}
		$args = $this->get_request_args( $data );		
		$result = $this->send_request( $args ); 
		if ( !empty($result['price']) ) 
			return $result;
		elseif ( !empty($result['err']) )
		{
			$this->set_error( $result['err'] );
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
			$this->set_error( __('Не указан номер почтового отправления.','usam') );
			return array();
		}
		$args = $this->get_request_args(['ImId' => $barcode, 'method' => 'ListStatusesFull']);
		$results = $this->send_request( $args );
		$history = array();		
		if ( !empty($results['statuses']) )
		{			
			foreach ( $results['statuses'] as $result )
			{
				$history['operations'][] = array( 'date' => usam_local_date( date("Y-m-d H:i:s",strtotime($result['Date'])) ), 'description' => $result['Comment'], 'name' => $result['Name']);	
				$history['status_description'] = $result['Name'];
				$history['issued'] = $result['Name'] == 'Выдано'?1:0;
			}
			$history['recipient'] = '';
			$history['sender_name'] = '';			
			$history['tariff_code'] = '';
			$history['weight'] = !empty($results['Weight'])?$results['Weight']:'';
			$history['payment'] = $results['PaymentMethod'];		
		}
		elseif ( !empty($result['err']) )
		{
			$this->set_error( $result['err'] );
			return false;
		}
		return $history;
	}	
	
	private function get_request_args( $params = array(), $method = 'GET' ) 
	{
		//$headers["Content-Type"] = 'application/json;charset=UTF-8';		
		$params['token'] = $this->deliver['token'];	
		$args = array(
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
		//	'headers' => $headers,		
		); 			
		$args['body'] = $params; 
		return $args;
	}
	
	public function get_options( ) 
	{
		return [
			['field_type' => 'text', 'name' => __('Токен', 'usam'), 'code' => 'token', 'default' => ''],
		];
	}
}
?>