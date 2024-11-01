<?php
require_once( USAM_FILE_PATH . '/includes/theme/theme_interface_filters.class.php' );
class Orders_Interface_Filters extends USAM_Theme_Interface_Filters
{			
	protected function get_filters( )
	{				
		$filters = [
		//	'seller' => ['title' => __('Продавец', 'usam'), 'type' => 'checklists'], 
			'date_insert' => ['title' => __('Дата заказа', 'usam'), 'type' => 'date'],	
			'sum' => ['title' => __('Сумма', 'usam'), 'type' => 'numeric'], 
			'prod' => ['title' => __('Количество товаров', 'usam'), 'type' => 'numeric'], 
			'brands' => ['title' => __('Бренды', 'usam'), 'type' => 'checklists'], 
			'category' => ['title' => __('Категории', 'usam'), 'type' => 'checklists']
		];
		return $filters;
	}
	
	public function get_sort( ) 
	{
		return ['date-desc' => __('Сначала новые', 'usam'), 'date-asc' => __('Сначала старые', 'usam'), 'totalprice-asc' => __('По сумме &#8593;', 'usam'), 'totalprice-desc' => __('По сумме &#8595;', 'usam')];	
	}
}
?>
