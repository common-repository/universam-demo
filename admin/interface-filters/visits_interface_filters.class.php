<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class Visits_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		$filters = array();
		foreach ( ['category'] as $tax_slug )
		{		
			$tax_obj = get_taxonomy( 'usam-'.$tax_slug );	
			$filters[$tax_slug] = ['title' => $tax_obj->labels->menu_name, 'type' => 'select'];
		} 
		foreach ( ['category', 'brands', 'category_sale', 'catalog', 'selection', 'variation'] as $tax_slug )
		{		
			$tax_obj = get_taxonomy( 'usam-'.$tax_slug );	
			$filters[$tax_slug] = ['title' => $tax_obj->labels->menu_name, 'type' => 'checklists']; 
		} 	
		$filters += [
			'bot' => ['title' => __('Боты / Люди', 'usam'), 'type' => 'select'],
			'visits' => ['title' => __('Количество визитов', 'usam'), 'type' => 'numeric'],
			'views' => ['title' => __('Количество просмотров', 'usam'), 'type' => 'numeric'],			
		];	
		return $filters;
	}
	
	public function get_bot_options() 
	{	
		return [['id' => 'all',  'name' => __('Все', 'usam')], ['id' => 'people',  'name' => __('Люди', 'usam')], ['id' => 'bot',  'name' => __('Роботы', 'usam')]];	
	}
}
?>