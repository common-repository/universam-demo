<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class competitors_products_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( )
	{				
		return [
			'parsing_sites' => ['title' => __('Конкурент', 'usam'), 'type' => 'checklists', 'query' => ['site_type' => 'competitor', 'fields' => 'id=>name', 'active' => 'all']],
			'price' => ['title' => __('Цена', 'usam'), 'type' => 'numeric'], 	
			'growth' => ['title' => __('Рост цены конкурента', 'usam'), 'type' => 'numeric'],	
			'decline' => ['title' => __('Снижение цены конкурента', 'usam'), 'type' => 'numeric'],
			'difference' => ['title' => __('Разница между вашей ценой', 'usam'), 'type' => 'numeric'],	
		];
	}	
}
?>