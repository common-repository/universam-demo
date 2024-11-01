<?php 
/*
	Title: Модуль рассчитывает стоимость по расстоянию
	Points: Нет
	SELFPICKUP: Нет
	Name: Модуль рассчитывает стоимость по расстоянию
 */
class USAM_Shipping_distance extends USAM_Shipping
{		
	protected function set_error( $error )
	{		
		$this->error  =  sprintf( __('Приложение %s вызвало ошибку №%s. Текст ошибки: %s'), $error['request_params'][1]['value'], $error['error_code'], $error['error_msg']);
	}
		
	public function calculate_shipping( $from, $to, $weight )
	{
		
	}	
	
	public function get_shipping_order( $document_id )
	{
		
	}	
	
	public function get_shipping_cost( $args )
	{		
		if ( empty($args['location']) )
		{ 
			$this->error = __('Невозможно рассчитать доставку. Не указано местоположение.','usam');
			return false;
		}				
		if ( empty($this->deliver['storage_id']) )
		{			
			$this->error = __('Невозможно рассчитать доставку. Не указан склад.','usam');
			return false;
		}	
		$storage = usam_get_storage( $this->deliver['storage_id'] );
		if ( empty($storage['location_id']) )
			return false;
		$distance = usam_get_locations_distance( $storage['location_id'], $args['location'] );
		if ( empty($distance)  )
		{			
			$name = usam_get_full_locations_name( $args['location'] );			
			$address = usam_get_customer_checkout( 'shippingaddress' );
			$to = $name.','.$address;			
					
			$name = usam_get_full_locations_name( $storage['location_id'] );
			$from = $name.','.htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'address'));		
			
			$distance = usam_get_distance_by_name( $from, $to );			
			if ( empty($distance) )
			{			
				return false;
			}			
			usam_set_locations_distance( $storage['location_id'], $args['location'], $distance );
		}	
		$price = round($distance/1000*$this->deliver['price'],2);			
		return $price;
	}

	public function get_options( ) 
	{
		return [
			['field_type' => 'text', 'name' => __('Стоимость за километр', 'usam'), 'code' => 'price', 'default' => 0],
		];
	}
}
?>