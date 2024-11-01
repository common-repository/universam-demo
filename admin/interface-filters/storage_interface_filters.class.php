<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class storage_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{						
		return [
			'sale_area' => ['title' => __('Мультирегиональность', 'usam'), 'type' => 'checklists'], 
			'active' => ['title' => __('Активность', 'usam'), 'type' => 'checklists'], 
			'issuing' => ['title' => __('Выдача', 'usam'), 'type' => 'checklists'], 
			'location' => ['title' => __('Местоположение', 'usam'), 'type' => 'autocomplete', 'request' => 'locations'], 
			'pickup_points' => ['title' => __('Собственник', 'usam'), 'type' => 'checklists'], 
		];	
	}	
	
	public function get_issuing_options() 
	{	
		return [['id' => 1, 'name' => __('Да', 'usam')], ['id' => 0, 'name' => __('Нет', 'usam')]];
	}
	
	protected function get_pickup_points_options() 
	{	
		$results = [[ 'id' => '', 'name' =>  __('Ваши', 'usam') ]];
		foreach (usam_get_data_integrations( 'shipping', ['name' => 'Name', 'points' => 'Points'] ) as $key => $gateway)
		{
			if ($gateway['points'] && $gateway['points'] == 'Да')
				$results[] = ['id' => $key, 'name' => $gateway['name'] ];
		}
		return $results;
	}
}
?>