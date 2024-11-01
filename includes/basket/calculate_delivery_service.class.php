<?php
/**
 * Класс получения способов доставки по указанным параметрам
 * locations - местоположения пользователя
 * type_payer - тип платильщика
 * roles - роль пользователя
 * price - стоимость корзины
 * weight - вес корзины 
 */
class USAM_Calculate_Delivery_Services
{
	private $delivery_services = [];
	private $delivery_service_disabled = [];
	private $delivery_service;

	public function __construct( $args = [] )
	{				
		$args = array_merge(['price' => 0, 'number_products' => 0], $args );
		$meta_query = [];
		if ( !empty($args['locations']) )
		{
			$args['locations'][] = 0;
			$meta_query[] = ['relation' => 'OR', ['key' => 'locations','value' => $args['locations'], 'compare' => 'IN'],['key' => 'locations', 'compare' => "NOT EXISTS"]];		
		}
		if ( !empty($args['type_payer']) )
		{
			$type_payer = [$args['type_payer'],0];		
			$meta_query[] = ['relation' => 'OR',['key' => 'type_payer','value' => $type_payer, 'compare' => 'IN'],['key' => 'type_payer', 'compare' => "NOT EXISTS"]];
		}
		if ( !empty($args['roles']) )
			$meta_query[] = ['relation' => 'OR',['key' => 'roles','value' => $args['roles'], 'compare' => 'IN'],['key' => 'roles', 'compare' => "NOT EXISTS"]]; 		
		$active = !empty($args['active']) ? $args['active'] : 1;			
		$delivery_service = usam_get_delivery_services(['cache_results' => true, 'cache_meta' => true,'orderby' => 'sort', 'meta_query' => $meta_query, 'active' => $active]);		
		if ( !empty($delivery_service) )
		{
			foreach( $delivery_service as $service )
			{		
				if ( usam_is_multisite() && !is_main_site() )
				{
					$blog_id = get_current_blog_id();
					foreach (['name', 'description'] as $key)
					{
						$value = usam_get_delivery_service_metadata($service->id,  $key.'_'.$blog_id );
						if ( $value )
							$service->$key = $value;
					}
				}
				$service->storage_owner = (string)usam_get_delivery_service_metadata($service->id, 'storage_owner');	
				$disabled = $service->active;						
				$price_from = (float)usam_get_delivery_service_metadata($service->id, 'price_from');
				$price_to = (float)usam_get_delivery_service_metadata($service->id, 'price_to');
				$products_from = (float)usam_get_delivery_service_metadata($service->id, 'products_from');
				$products_to = (float)usam_get_delivery_service_metadata($service->id, 'products_to');
				if( ($price_from==0 || $price_from <= $args['price'] ) && ($price_to==0 || $price_to >= $args['price'] ) && ($products_from==0 || $products_from <= $args['number_products'] ) && ( $products_to==0 || $products_to >= $args['number_products'] ) )
				{	
					$weight_from = usam_get_delivery_service_metadata($service->id, 'weight_from');
					$weight_to = usam_get_delivery_service_metadata($service->id, 'weight_to');
					if ( ( empty($weight_from) || $weight_from <= $args['weight'] ) && ( empty($weight_to) || $weight_to >= $args['weight'] ) )
						$disabled = false;
				} 				
				if ( $disabled )				
					$this->delivery_service_disabled[] = $service;
				else					
					$this->delivery_services[] = $service;
			}
		}
	}
	
	function get_delivery_services_disabled() 
	{		
		return $this->delivery_service_disabled;
	}
	
	function get_delivery_services() 
	{		
		return $this->delivery_services;
	}
}