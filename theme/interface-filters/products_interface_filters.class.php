<?php
require_once( USAM_FILE_PATH . '/includes/theme/theme_interface_filters.class.php' );
class Products_Interface_Filters extends USAM_Theme_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		$filters = array();
		foreach ( ['category'] as $tax_slug )
		{		
			$tax_obj = get_taxonomy( 'usam-'.$tax_slug );	
			$filters[$tax_slug] = ['title' => $tax_obj->labels->menu_name, 'type' => 'select'];
		} 
		foreach ( ['category' => 'categories', 'brands' => 'brands', 'selection' => 'selections'] as $tax_slug => $request )
		{		
			$tax_obj = get_taxonomy( 'usam-'.$tax_slug );	
			$filters[$tax_slug] = ['title' => $tax_obj->labels->menu_name, 'type' => 'autocomplete', 'request' => $request]; 
		} 					
		$filters += [		
			'sku' => ['title' => __('Артикул', 'usam'), 'type' => 'string_meta'],
			'title' => ['title' => __('Название товара', 'usam'), 'type' => 'string'],
			'content' => ['title' => __('Описание товара', 'usam'), 'type' => 'string'],
			'excerpt' => ['title' => __('Краткое описание', 'usam'), 'type' => 'string'],	
			'price' => ['title' => __('Цена', 'usam'), 'type' => 'numeric'],
			'oldprice' => ['title' => __('Старая цена', 'usam'), 'type' => 'numeric'],
			'weight' => ['title' => __('Вес', 'usam'), 'type' => 'numeric'],
			'views' => ['title' => __('Просмотры', 'usam'), 'type' => 'numeric'],
			'rating' => ['title' => __('Рейтинг', 'usam'), 'type' => 'numeric'],
			'barcode' => ['title' => __('Штрихкод', 'usam'), 'type' => 'numeric'],		
			'stock' => ['title' => __('Доступный остаток', 'usam'), 'type' => 'numeric'],
			'total_balance' => ['title' => __('Общий остаток', 'usam'), 'type' => 'numeric'],			
		];
		$terms = usam_get_product_attributes();
		foreach ( $terms as $term )
		{
			$field_type = usam_get_term_metadata($term->term_id, 'field_type');
			switch ( $field_type ) 
			{
				case 'T' :
					$filters['attr_'.$term->term_id] = ['title' => $term->name, 'type' => 'string_meta', 'show' => false];
				break;
			}
		}		
		return $filters;		
	}	
	
	public function get_product_type_options() 
	{	
		return [['id' => 'simple', 'name' => __('Простой', 'usam')], ['id' => 'variable', 'name' => __('Вариационный', 'usam')]];
	}
	
	public function get_sort()
	{
		return ['date-desc' => __('По дате &#8595;', 'usam'), 'date-asc' => __('По дате &#8593;', 'usam'), 'modified-desc' => __('По дате изменения &#8595;', 'usam'), 'modified-asc' => __('По дате изменения &#8593;', 'usam'),'id-desc' => __('Сначала новые', 'usam'), 'id-asc' => __('Сначала старые', 'usam'), 'title-asc' => __('По названию А-Я', 'usam'), 'title-desc' => __('По названию Я-А', 'usam'), 'category-desc' => __('По категории &#8595;', 'usam'), 'category-asc' => __('По категории &#8593;', 'usam'), 'sku-desc' => __('По артикулу &#8595;', 'usam'), 'sku-asc' => __('По артикулу &#8593;', 'usam'), 'percent-desc' => __('Процент скидки &#8595;', 'usam'), 'percent-asc' => __('Процент скидки &#8593;', 'usam'), 'comment-desc' => __('По комментариям &#8595;', 'usam'), 'comment-asc' => __('По комментариям &#8593;', 'usam'), 'views-desc' => __('По просмотрам &#8595;', 'usam'), 'views-asc' => __('По просмотрам &#8593;', 'usam'), 'rating-desc' => __('По рейтингу &#8595;', 'usam'), 'rating-asc' => __('По рейтингу &#8593;', 'usam'), 'stock-desc' => __('По остатку &#8595;', 'usam'), 'stock-asc' => __('По остатку &#8593;', 'usam'), 'purchased-desc' => __('По продажам &#8595;', 'usam'), 'purchased-asc' => __('По продажам &#8593;', 'usam')];	
	}	
}
?>