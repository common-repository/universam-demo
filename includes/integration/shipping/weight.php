<?php 
/*
	Title: 
	Points: Нет
	SELFPICKUP: Да
	Name: Модуль рассчитывает стоимость по весу товаров
 */
class USAM_Shipping_weight extends USAM_Shipping
{		
	public function get_shipping_cost( $args )
	{		
		return $args['weight']*$this->deliver['price'];
	}		
	
	public function get_options() 
	{
		return [
			['field_type' => 'text', 'name' => __('Цена за кг', 'usam'), 'code' => 'price', 'default' => 0],
		];
	}
}
?>