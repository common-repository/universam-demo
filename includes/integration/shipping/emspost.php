<?php 
/*
	Title: Транспортная компания Почта России
	Points: Нет
	SELFPICKUP: Нет
	Name: Почта России
	Location code: Да
	Order: Нет
 */ 
class USAM_Shipping_emspost extends USAM_Shipping
{	
	protected $API_URL = "https://otpravka-api.pochta.ru/";
		
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
		$barcode = mb_strtoupper($barcode);
		$this->API_URL = "https://tracking.russianpost.ru/rtm34?wsdl";		
		$client2 = new SoapClient($this->API_URL, array('trace' => 1, 'soap_version' => SOAP_1_2));	
		$params = [
			'OperationHistoryRequest' => ['Barcode' => $barcode, 'MessageType' => '0','Language' => 'RUS'],
			'AuthorizationHeader' => ['login'=> $this->deliver['login'], 'password'=> $this->deliver['pass']]
		];
		$result = $client2->getOperationHistory(new SoapParam($params,'OperationHistoryRequest'));
		$history = ['issued' => 0, 'date_delivery' => 0];
		foreach ($result->OperationHistoryData->historyRecord as $record) 
		{
			$date = str_replace('T',' ', $record->OperationParameters->OperDate);
			$date = usam_local_date($date);
			$name = isset($record->OperationParameters->OperAttr->Name)?$record->OperationParameters->OperAttr->Name:'';			
			$type_name = isset($record->ItemParameters->ComplexItemName)?$record->ItemParameters->ComplexItemName:'';
			$history['operations'][] = ['date' => $date, 'description' => $record->AddressParameters->OperationAddress->Description, 'name' => $name, 'type_name' => $type_name];		
			if ( $name == 'Вручение адресату' )
			{
				$history['issued'] = 1;
				$history['date_delivery'] = $date;
			}
		}		
		$history['recipient'] = '';
		$history['sender_name'] = '';
		$history['status_description'] = $name;	
		$history['tariff_code'] = '';
		$history['weight'] = '';
		$history['payment'] = '';		
		return $history;
	}
			
	public function get_shipping_cost( $basket_args )
	{		
		if ( empty($basket_args['weight']) )
			$basket_args['weight'] = 1;
		
	//	$country = usam_get_country( $basket_args['location'], 'location_id' );
	//	$numerical_country = empty($country['numerical'])?643:$country['numerical'];		
					
		$storage_id = !empty($this->deliver['storage_id'])?$this->deliver['storage_id']:0;
		$index = usam_get_storage_metadata($storage_id, 'index');
//$index = 101000;		
		if ( !$index )
		{			
			$this->set_error( __('Невозможно рассчитать доставку. Не указан индекс склада отгрузки.','usam') );
			return false;
		}		
		if ( $this->deliver['token'] )
		{		
			$weight = usam_convert_weight( $basket_args['weight'], 'g', 'kg' );
			$params = array(
			//	'completeness-checking' => true,
				'courier' => (bool)$this->deliver['courier'],	
			//	'entries-type' => 'SALE_OF_GOODS',	
				'fragile' => (bool)$this->deliver['fragile'],	
				'mail-category' => $this->deliver['category'],	
				'mail-type' => $this->deliver['type'],	
				'sms-notice-recipient' => $this->deliver['sms'],	
				'mass' => $weight,	
				'notice-payment-method' => 'CASHLESS',	
		//		'payment-method' => 'CASHLESS',	
				'transport-type' => $this->deliver['transport_type'],			
				'index-from' => $index,	
				'index-to' => $basket_args['index'],	
		//		'mail-direct' => $numerical_country,			
		//		'with-order-of-notice' => false,
		//		'with-simple-notice' => false,	
			);	
			if ( $this->deliver['category'] != 'ORDINARY' )
				$params['declared-value'] = $basket_args['subtotal'];
			
			$headers["Content-Type"] = 'application/json';
			$headers["Accept"] = 'application/json;charset=UTF-8';
			$headers["Authorization"] = 'AccessToken '.$this->deliver['token'];	
			$headers["X-User-Authorization"] = 'Basic '.base64_encode($this->deliver['login'].':'.$this->deliver['pass']);	
			$headers["Cache-Control"] = 'no-cache';	
			
			$args = array(
				'method' => 'POST',			
				'httpversion' => '1.1',
				'blocking' => true,
				'headers' => $headers,	
				'body' => json_encode($params),
			);		
			$result = $this->send_request( $args, "1.0/tariff" );	
			if ( isset($result["total-rate"]) )
			{
				return $result["total-rate"]/100;
			}
			elseif ( isset($result["desc"]) )
			{
				$this->set_error( $result["desc"] );
				return false;
			}
		}
		else
		{ 						
			$weight = usam_convert_weight( $basket_args['weight'], 'g', 'kg' );
			$params = array( 'from' => $index, 'to' => $basket_args['index'], 'weight' => $weight, 'closed' => 1 );			
			if ( $this->deliver['type'] == 'POSTAL_PARCEL' )				
			{
				if ( $this->deliver['category'] == 'ORDINARY' )
					$params['object'] = 4030;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE' )
					$params['object'] = 4020;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY' )
					$params['object'] = 4040;		
				$params['pack'] = 10;					
			}
			elseif ( $this->deliver['type'] == 'BANDEROL' || $this->deliver['type'] == 'BANDEROL_CLASS_1' )
			{
				if ( $this->deliver['category'] == 'ORDINARY' )
					$params['object'] = 3000;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE' )
					$params['object'] = 3020;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY' )
					$params['object'] = 3040;			
			}
			elseif ( $this->deliver['type'] == 'ONLINE_COURIER' )
			{
				$params['pack'] = 10;
				if ( $this->deliver['category'] == 'ORDINARY' )
					$params['object'] = 24030;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE' )
					$params['object'] = 24020;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY' )
					$params['object'] = 24040;						
			}
			elseif ( $this->deliver['type'] == 'LETTER' )
			{
				if ( $this->deliver['category'] == 'ORDINARY' )
					$params['object'] = 2000;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE' )
					$params['object'] = 2020;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY' )
					$params['object'] = 2040;						
			}	
			elseif ( $this->deliver['type'] == 'LETTER_CLASS_1' )			
			{
				if ( $this->deliver['category'] == 'ORDINARY' )
					$params['object'] = 15010;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE' )
					$params['object'] = 15020;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY' )
					$params['object'] = 15040;						
			}	
			elseif ( $this->deliver['type'] == 'SMALL_PACKET' )
			{
				$params['object'] = 5001;					
			}	
			elseif ( $this->deliver['type'] == 'EMS' )
			{
				$params['pack'] = 10;
				if ( $this->deliver['category'] == 'ORDINARY' )
					$params['object'] = 7030;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE' )
					$params['object'] = 7020;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY' )
					$params['object'] = 7040;						
			}
			elseif ( $this->deliver['type'] == 'EMS_OPTIMAL' )
			{
				if ( $this->deliver['category'] == 'ORDINARY' )
					$params['object'] = 34030;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE' )
					$params['object'] = 34020;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY' )
					$params['object'] = 34040;		
				$params['pack'] = 10;					
			}	
			elseif ( $this->deliver['type'] == 'PARCEL_CLASS_1' )
			{
				$params['object'] = 47030;
				$params['pack'] = 10;
			}
			elseif ( $this->deliver['type'] == 'VGPO_CLASS_1' )
			{
				$params['object'] = 46010;		
				$params['pack'] = 10;
			}
			elseif ( $this->deliver['type'] == 'BUSINESS_COURIER' )
			{
				if ( $this->deliver['category'] == 'ORDINARY' )
					$params['object'] = 30030;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE' )
					$params['object'] = 30020;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY' )
					$params['object'] = 31020;	
				$params['pack'] = 10;					
			}	
			elseif ( $this->deliver['type'] == 'BUSINESS_COURIER_ES' )
			{
				if ( $this->deliver['category'] == 'ORDINARY' )
					$params['object'] = 31030;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE' )
					$params['object'] = 30020;	
				elseif ( $this->deliver['category'] == 'WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY' )
					$params['object'] = 31020;	
				$params['pack'] = 10;					
			}				
			elseif ( $this->deliver['type'] == 'EASY_RETURN' )
			{
				$params['object'] = 51030;					
				$params['pack'] = 10;
			}
			else
			{
				$params['pack'] = 10;
				$params['object'] = 27030;	
			}

			$params['sumoc'] = $basket_args['subtotal'];
			$params['service'] = '';
			if ( $this->deliver['sms'] )	
				$params['service'] .= '20,21';
			if ( $this->deliver['fragile'] )	
				$params['service'] .= '4';			

			if ( $this->deliver['transport_type'] == 'AVIA' || $this->deliver['transport_type'] == 'EXPRESS' )	
				$params['isavia'] = 1;
			elseif ( $this->deliver['transport_type'] == 'SURFACE' )
				$params['isavia'] = 0;

			$this->API_URL = "https://tariff.pochta.ru/";	
			$args = array('method' => 'GET', 'body' => $params );								
			$result = $this->send_request( $args, "tariff/v1/calculate?json" );		
			if ( isset($result["paynds"]) )
			{
				return $result["paynds"] / 100;
			}			
		}
		return $result;
	}	
	
	public function get_options( ) 
	{
		return [
			['field_type' => 'text', 'name' => __('Логин', 'usam'), 'code' => 'login', 'default' => ''],
			['field_type' => 'text', 'name' => __('Пароль', 'usam'), 'code' => 'pass', 'default' => ''],
			['field_type' => 'text', 'name' => __('Токен', 'usam'), 'code' => 'token', 'default' => ''],
			['field_type' => 'BUTTONS', 'name' => __('Курьер', 'usam'), 'code' => 'courier', 'default' => '1'],
			['field_type' => 'BUTTONS', 'name' => __('Отметка хрупкое', 'usam'), 'code' => 'fragile', 'default' => 0,],
			['field_type' => 'BUTTONS', 'name' => __('SMS уведомление', 'usam'), 'code' => 'sms', 'default' => 0],
			['field_type' => 'select', 'name' => __('Категория', 'usam'), 'code' => 'category', 'default' => 'ORDINARY', 'multiple' => 0, 'options' => [
				['id' => 'ORDINARY', 'name' => __('Обыкновенное', 'usam')], 
				['id' => 'WITH_DECLARED_VALUE', 'name' => __('С объявленной ценностью', 'usam')], 
				['id' => 'WITH_DECLARED_VALUE_AND_CASH_ON_DELIVERY', 'name' => __('С объявленной ценностью и наложенным платежом', 'usam')], 	
			]],
			['field_type' => 'select', 'name' => __('Вид РПО', 'usam'), 'code' => 'tariff_codes', 'default' => 'ONLINE_COURIER', 'options' => [
				['id' => 'ONLINE_PARCEL', 'name' => __('Посылка онлайн', 'usam')], 
				['id' => 'POSTAL_PARCEL', 'name' => __('Посылка нестандартная', 'usam')],
				['id' => 'ONLINE_COURIER', 'name' => __('Курьер', 'usam')],
				['id' => 'EMS', 'name' => __('Отправление EMS', 'usam')],
				['id' => 'EMS_OPTIMAL', 'name' => __('Оптимальное EMS', 'usam')],
				['id' => 'EMS_RT', 'name' => __('EMS РТ', 'usam')],
				['id' => 'LETTER', 'name' => __('Письмо', 'usam')],
				['id' => 'LETTER_CLASS_1', 'name' => __('Письмо 1-го класса', 'usam')],
				['id' => 'BANDEROL', 'name' => __('Бандероль', 'usam')],
				['id' => 'BUSINESS_COURIER', 'name' => __('Бизнес курьер', 'usam')],
				['id' => 'BUSINESS_COURIER_ES', 'name' => __('Бизнес курьер экпресс', 'usam')],
				['id' => 'PARCEL_CLASS_1', 'name' => __('Посылка 1-го класса', 'usam')],
				['id' => 'BANDEROL_CLASS_1', 'name' => __('Бандероль 1-го класса', 'usam')],
				['id' => 'VGPO_CLASS_1', 'name' => __('ВГПО 1-го класса', 'usam')],
				['id' => 'SMALL_PACKET', 'name' => __('Мелкий пакет', 'usam')],
				['id' => 'COMBINED', 'name' => __('Комбинированное', 'usam')],
				['id' => 'EASY_RETURN', 'name' => __('Легкий возврат', 'usam')],
			]],	
			['field_type' => 'select', 'name' => __('Вид транспортировки', 'usam'), 'code' => 'transport_type', 'default' => 'SURFACE', 'multiple' => 0, 'options' => [
				['id' => 'SURFACE', 'name' => __('Наземный', 'usam')], 
				['id' => 'AVIA', 'name' => __('Авиа', 'usam')], 
				['id' => 'COMBINED', 'name' => __('Комбинированный', 'usam')], 	
				['id' => 'EXPRESS', 'name' => __('Системой ускоренной почты', 'usam')], 	
			]],
		];
	}
}
?>