<?php 
/*
	Title: 
	Points: Нет
	SELFPICKUP: Да
	Name: Стоимость доставки задается в свойствах заказа
 */
class USAM_Shipping_product_attributes extends USAM_Shipping
{		
	public function get_shipping_cost( $args )
	{	
		$price = 0;
		foreach( $args['products'] as $product )
		{
			$p = (float)usam_get_product_attribute( $product->product_id, $this->deliver['attribute'] );
			$price += $p*$product->quantity;
		}
		return $price;
	}		
	
	public function get_options( ) 
	{
		$attributes = [];
		$product_attributes = usam_get_product_attributes();	
		foreach( $product_attributes as $term )
		{
			$field_type = usam_get_term_metadata($term->term_id, 'field_type');
			if ( $term->parent != 0 && ($field_type == 'T' || $field_type == 'O') )
				$attributes[] = ['id' => $term->slug, 'name' => $term->name];
		}
		return [
			['field_type' => 'select', 'name' => __('Свойство, содержащее стоимость доставки', 'usam'), 'options' => $attributes, 'code' => 'attribute', 'default' => ''],
		];
	}
}
?>