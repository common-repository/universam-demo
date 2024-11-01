<?php 
/*
	Title: Транспортная компания DPD
	Points: Да
	SELFPICKUP: Да
	Name: DPD
	Location code: Да
	Order: Да
 */
class USAM_Shipping_dpd extends USAM_Shipping
{	
	protected $load_locations = true;
	protected $test = false;		
	protected $API_URL = "http://ws.dpd.ru/services/";
		
	public function __construct( $id )
	{
		parent::__construct( $id );
		
		if ( $this->test )
			$this->API_URL = "http://wstest.dpd.ru/services/";	// тестовая	
	}
	
	public function match_locations( $page ) 
	{		
		$locations = usam_get_locations( );		
		$client = new SoapClient ("{$this->API_URL}geography2?wsdl");
		$params['auth'] = array('clientNumber'=> $this->deliver['login'], 'clientKey'=> $this->deliver['pass'] );
		$arRequest['request'] = $params; 			
		try 
		{ 			
			$result = $client->getCitiesCashPay($arRequest); //обращаемся к функции getCitiesCashPay  и получаем список городов.
		}
		catch(Exception $e) 
		{ 
			$this->set_error( __('Невозможно получить местоположения.','usam') );
			return false;
		}			
		if ( !empty($result->return) )
		{			
			foreach( $result->return as $item ) 
			{					
				foreach( $locations as $key => $location ) 
				{					
					if ( $location->name == $item->cityName )
					{		
						usam_update_location_metadata( $location->id, $this->deliver['handler'], $item->cityId );
						unset($locations[$key]);						
						break;	
					}
				}
			}			
			return true;
		}	
		return false;
	}	
	
	public function set_delivery_warehouses( $paged = 0 )
	{
		$storages = usam_get_storages(['fields' => 'code=>data', 'owner' => $this->deliver['handler'], 'cache_meta' => true]);
		$client = new SoapClient ("{$this->API_URL}geography2?wsdl");
		$params['auth'] = array('clientNumber'=> $this->deliver['login'], 'clientKey'=> $this->deliver['pass'] );
		$arRequest['request'] = $params; 	
		try 
		{ 			
			$result = $client->getParcelShops( $arRequest ); 
		}
		catch(Exception $e) 
		{ 
			$this->set_error( __('Невозможно получить пункты выдачи.','usam') );
			return false;
		}			
		if ( !empty($result->return->parcelShop) )
		{					
			foreach ( $result->return->parcelShop as $item ) 
			{				
				$abbr = isset($item->address->streetAbbr)?$item->address->streetAbbr:'';
				$house = isset($item->address->houseNo)?$item->address->houseNo:'';
				$address = $abbr.' '.$item->address->street.' '.$house;
				if ( isset($storages[$item->code]) )
				{
					$storage_id = $storages[$item->code]->id;
					if ( !$storages[$item->code]->location_id )
					{
						$location_id = (int)usam_get_location_id_by_meta( $this->deliver['handler'], $item->address->cityId );					
						usam_update_storage($storage_id, ['location_id' => $location_id]);	
					}
					unset($storages[$item->code]);
				}
				else 
				{	
					$location_id = (int)usam_get_location_id_by_meta( $this->deliver['handler'], $item->address->cityId );			
					$storage_id = $this->insert_storage(['title' => $address, 'code' => $item->code, 'location_id' => $location_id]);	
					usam_update_storage_metadata( $storage_id, 'index', $item->address->index);					
				}
				if ( $storage_id )
				{
					usam_update_storage_metadata( $storage_id, 'address', $address);
					usam_update_storage_metadata( $storage_id, 'longitude', $item->geoCoordinates->longitude);
					usam_update_storage_metadata( $storage_id, 'latitude', $item->geoCoordinates->latitude);
					$schedule = !empty($item->schedule)?$item->schedule[0]->timetable->weekDays.' '.$item->schedule[0]->timetable->workTime:'';
					usam_update_storage_metadata( $storage_id, 'schedule', $schedule); //График работы		
				}
			}
			foreach( $storages as $storage ) 
			{
				usam_delete_storage( $storage->id );
			}
		}	
		return false;
	}	
	
	public function create_order( $document_id )
	{
		global $wpdb;
		
		$params = array ( 'auth' => array ('clientNumber'=> $this->deliver['login'], 'clientKey' => $this->deliver['pass']),
						  'header' => array ('datePickup' => date( "Y-m-d" ), 'pickupTimePeriod' => $this->deliver['pickupTimePeriod'] ),
						);				
		$document = usam_get_shipped_document( $document_id );
		$name = usam_get_order_metadata( $document['order_id'], 'company' );
		if ( $name )			
		{
			$name = usam_get_order_metadata( $document['order_id'], 'company' );
			$fio = usam_get_order_metadata( $document['order_id'], 'contact_person' );
			$shippingaddress = usam_get_order_metadata( $document['order_id'], 'company_shippingaddress' );			
		}
		else
		{
			$names = array();
			$names[] = usam_get_order_metadata( $document['order_id'], 'billingfirstname' );
			$names[] = usam_get_order_metadata( $document['order_id'], 'billinglastname' );				
			$fio = implode(",",$names);
			$shippingaddress = usam_get_order_metadata( $document['order_id'], 'shippingaddress' );		
		}			
		$location_id = usam_get_order_metadata( $document['order_id'], 'shippinglocation' );
		if ( empty($location_id) )
			$location_id = usam_get_order_metadata( $document['order_id'], 'company_shippinglocation' );
		
		if ( $location_id == 0 )
			return false;	
		$locations = usam_get_address_locations( $location_id );
		
		$weight = 0;	
		$volume = 0;	
		$totalprice = 0;
		
		$products = usam_get_products_order( $document['order_id'] );	
		$order_products = array();
		foreach ( $products as $product ) 
		{
			$order_products[$product->product_id] = $product;
		}
		$products = usam_get_products_shipped_document( $document_id );
		foreach ( $products as $product ) 
		{						
			$product_weight = usam_get_product_weight( $product->product_id, 'kg', false );
			if ( !empty($product_weight) )
			{
				$weight += $product_weight * $product->quantity;
				$volume += usam_get_product_volume( $product->product_id ) * $product->quantity;
				$totalprice += $product->price * $product->quantity;
				$params['order']['unitLoad'] = array( 'descript' => $order_products[$product->product_id]->name, 'count' => $product->quantity );
			}
		}				
		$params['order']['orderNumberInternal'] = $document_id;
		$params['order']['serviceCode'] = $this->deliver['delivery'];
		
		if ( $this->deliver['delivery_option'] == 1 && $this->deliver['selfPickup'] == 'terminal' )		
			$params['order']['serviceVariant'] = 'ТТ';
		elseif ( $this->deliver['delivery_option'] == 0 && $this->deliver['selfPickup'] == 'terminal' )
			$params['order']['serviceVariant'] = 'ТД';
		elseif ( $this->deliver['delivery_option'] == 1 && $this->deliver['selfPickup'] != 'terminal' )
			$params['order']['serviceVariant'] = 'ДТ';	
		else
			$params['order']['serviceVariant'] = 'ДД';
		
		$phone = usam_get_buyers_phone( $document['order_id'] );		
		$extraService = array();		
		if ( !empty($this->deliver['sms_notification_recipient']) && !empty($phone))
		{
			$extraService[] = array( 'esCode' => 'SMS', 'param' => array( 'name' => 'phone', 'value' => $phone ) );		
		}
		if ( !empty($this->deliver['email_notification_recipient']) )
		{
			$email = usam_get_buyers_email( $document['order_id'] );
			if ( !empty($phone) )
				$extraService[] = array( 'esCode' => 'EML', 'param' => array( 'name' => 'email', 'value' => $email ) );		
		}
		if ( !empty($this->deliver['email_notification_sender']) )
		{
			$email = usam_get_buyers_email( $document['order_id'] );
			if ( !empty($phone) )
				$extraService[] = array( 'esCode' => 'ЭСД', 'param' => array( 'name' => 'email', 'value' => $this->deliver['email_notification_sender'] ) );		
		}
		if ( !empty($extraService) )
			$params['order']['extraService'] = $extraService;
		
		$params['order']['cargoNumPack'] = 1;
		$params['order']['cargoWeight'] = $weight;
		$params['order']['cargoVolume'] = $volume;
		$params['order']['cargoRegistered'] = $this->deliver['cargoRegistered'];		
		$params['order']['cargoValue'] = $totalprice;	
		$params['order']['cargoCategory'] = $this->deliver['category'];		
		$params['order']['paymentType'] = $this->deliver['payment_type'];
		$company = usam_shop_requisites();
		$company_name = !empty($company['full_company_name'])?$company['full_company_name']:$company['name'];
	
		$params['order']['receiverAddress'] = array( 'name' => $name, 'countryName' => $locations['country'], 'city' => $locations['city'], 'street' => $shippingaddress, 'contactFio' => $fio, 'contactPhone' => $phone );	

		if ( $document['storage_pickup'] )
		{
			$storage = usam_get_storage( $document['storage_pickup'] );			
			$params['order']['receiverAddress']['terminalCode'] = $storage['code'];
		}
		$storage_id = !empty($this->deliver['storage_id'])?$this->deliver['storage_id']:0;
		$storage = usam_get_storage( $storage_id ); 	
		$locations = usam_get_address_locations( $storage['location_id'] );	
		
		$user = get_user_by('id', $document['courier'] );
		$fio = isset($user->display_name)?"$user->display_name":'';
		
		$params['header']['senderAddress'] = array( 'name' => $company_name, 'countryName' => $locations['country'], 'city' => $locations['city'], 'street' => $storage['address'], 'contactFio' => $fio, 'contactPhone' => $company['phone'] );		
		
		if ( $storage )
			$params['order']['receiverAddress']['terminalCode']	= $storage['code'];
						
		$arRequest['orders'] = $params;			
		$client = new SoapClient ("{$this->API_URL}order2?wsdl");		
		$result = $client->createOrder($arRequest);	
		try 
		{ 			
			$result = $client->createOrder($arRequest);	
		}
		catch(Exception $e) 
		{ 
			$this->set_error( __('Невозможно загрузить заказ.','usam') );
			return false;
		}
		return $result->return;
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
		$arRequest['request'] = $this->set_auth(['dpdOrderNr' => $barcode]);			
		$client = new SoapClient ("{$this->API_URL}tracing?wsdl");
		$result = $client->getStatesByDPDOrder( $arRequest );	
		try 
		{ 			
			$result = $client->getStatesByDPDOrder( $arRequest );	
		}
		catch(Exception $e) 
		{ 
			$this->set_error( __('Невозможно получить историю заказа.','usam') );
			return false;
		}				
		return $history;
	}
	
	public function set_auth( $params = array() )
	{
		$params['auth'] = array('clientNumber'=> $this->deliver['login'], 'clientKey'=> $this->deliver['pass'] );
		return $params;
	}
		
	public function get_dpd_cost( $args )
	{					
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
			
		$current_location_id = $this->get_location_departure();
		$from_location = usam_get_location( $current_location_id );
		$to_location = usam_get_location($args['location']);	
		
		$params = $this->set_auth( array('delivery' => array ('cityId' => $to_city_id, 'cityName' => $to_location['name']) ) );
		if ( $this->deliver['delivery_option'] == 0 ) //если отправляем до дома то ставим значение false		
			$params['selfDelivery'] = false;// Доставка ДО дома
		else
			$params['selfDelivery'] = true;// Доставка ДО терминала
		
		// где забирают товар
		$params['pickup'] = array('cityId' => $from_city_id, 'cityName' => $from_location['name']); 		
		if ( $this->deliver['selfPickup'] == 'terminal' ) // если вы сами довозите до терминала то true если вы отдаёте от двери то false	
			$params['selfPickup'] = true;//до терминала
		else
			$params['selfPickup'] = false;// если вы отдаёте от двери	
		
		$params['declaredValue'] = $args['subtotal']; 
		$params['weight'] = $args['weight']; 
		$params['volume'] = $args['volume']; 

		$arRequest['request'] = $params;			
		if ( extension_loaded('soap') ) 
		{
			$client = new SoapClient ("{$this->API_URL}calculator2?wsdl");
		}
		else
		{
			$this->set_error( __('Невозможно рассчитать доставку. Не включен Soap Client','usam') );
			return false;
		}
		try 
		{ 			
			$result = $client->getServiceCost2($arRequest);	
		}
		catch(Exception $e) 
		{ 
			$this->set_error( __('Невозможно рассчитать доставку.','usam') );
			return false;
		}
		$price = false;
		foreach ( $result->return as $item ) 
		{			
			if (  $this->deliver['delivery'] == $item->serviceCode )
			{
				$price = $item->cost;
				break;
			}
		} 
		return $price;
	}
				
	public function get_shipping_cost( $args )
	{
		if ( empty($args['weight']) )
		{
			$this->set_error( __('Невозможно рассчитать доставку. Не указан вес товаров.','usam') );
			return false;
		}	
		if ( empty($args['location']) )
		{
			$this->set_error( __('Невозможно рассчитать доставку. Не указано местоположение.','usam') );
			return false;
		}		
		$args['volume'] = 0;
		foreach ( $args['products'] as $product ) 		
			$args['volume'] += usam_get_product_volume( $product->product_id ) * $product->quantity;	

		if ( empty($args['volume']) )
		{
			$this->set_error( __('Невозможно рассчитать доставку. Не указаны размеры товаров.','usam') );
			return false;
		}						
		return $this->get_dpd_cost( $args );	
	}	
	
	public function get_options( ) 
	{
		return [
			['field_type' => 'text', 'name' => __('Логин', 'usam'), 'code' => 'login', 'default' => ''],
			['field_type' => 'text', 'name' => __('Пароль', 'usam'), 'code' => 'pass', 'default' => ''],	
			['field_type' => 'select', 'name' => __('Как доставлять в DPD', 'usam'), 'code' => 'selfPickup', 'default' => 'storage', 'multiple' => 0, 'options' => [
				['id' => 'terminal', 'name' => __('в терминал', 'usam')], 
				['id' => 'storage', 'name' => __('DPD забирает у вас', 'usam')],
			]],
			['field_type' => 'select', 'name' => __('Интервалы времени приёма груза', 'usam'), 'code' => 'pickupTimePeriod', 'default' => '9-18', 'multiple' => 0, 'options' => [
				['id' => '9-18', 'name' => '9-18'], 
				['id' => '9-13', 'name' => '9-13'],
				['id' => '13-18', 'name' => '13-18'],
			]],		
			['field_type' => 'select', 'name' => __('Варианты доставки клиенту', 'usam'), 'code' => 'delivery', 'default' => 'ECN', 'multiple' => 0, 'options' => [
				['id' => 'ECN', 'name' => 'DPD ECONOMY'], 
				['id' => 'BZP', 'name' => 'DPD 18:00'], 
				['id' => 'PCL', 'name' => 'DPD Online Classic'], 
			]],
			['field_type' => 'select', 'name' => __('Варианты оплаты', 'usam'), 'code' => 'payment_type', 'default' => 'ОУП', 'multiple' => 0, 'options' => [
				['id' => 'ОУП', 'name' => __('оплата у получателя наличными', 'usam')], 
				['id' => 'ОУО', 'name' => __('оплата у отправителя наличными', 'usam')], 
			]],
			['field_type' => 'text', 'name' => __('Содержимое отправки', 'usam'), 'code' => 'category', 'default' => 'Товары'],
			['field_type' => 'BUTTONS', 'name' => __('SMS уведомление получателя о приёме посылки', 'usam'), 'code' => 'sms_notification_recipient', 'default' => ''],
			['field_type' => 'BUTTONS', 'name' => __('E-mail уведомление получателя о приёме посылки', 'usam'), 'code' => 'email_notification_recipient', 'default' => ''],
			['field_type' => 'BUTTONS', 'name' => __('E-mail уведомление о доставке груза', 'usam'), 'code' => 'email_notification_sender', 'default' => ''],
			['field_type' => 'BUTTONS', 'name' => __('Ценный груз', 'usam'), 'code' => 'cargoRegistered', 'default' => 0],
		];
	}
}
?>