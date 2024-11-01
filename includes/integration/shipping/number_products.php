<?php 
/*
	Title: 
	Points: Нет
	SELFPICKUP: Да
	Name: Модуль рассчитывает стоимость за количество товаров
 */
class USAM_Shipping_number_products extends USAM_Shipping
{	
	public function get_shipping_cost( $args )
	{	
		$number = 0;
		foreach( $args['products'] as $product )
			$number += $product->quantity;
		
		if ( $number <= 1 )
			return $this->deliver['first_price'];
		else
			return $this->deliver['first_price'] + ($number-1)*$this->deliver['price'];
	}

	public function get_options( ) 
	{
		return [
			['field_type' => 'text', 'name' => __('Цена за первый товар', 'usam'), 'code' => 'first_price', 'default' => 0],
			['field_type' => 'text', 'name' => __('Стоимость за слушающую единицу товара', 'usam'), 'code' => 'price', 'default' => 0],
		];
	}
}
?>