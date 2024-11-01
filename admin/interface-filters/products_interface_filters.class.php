<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class Products_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		$filters = array();	
		foreach ( ['category', 'brands', 'category_sale', 'selection'] as $tax_slug )
		{		
			$tax_obj = get_taxonomy( 'usam-'.$tax_slug );	
			$filters[$tax_slug] = ['title' => $tax_obj->labels->menu_name, 'type' => 'checklists'];
		} 
		foreach ( ['catalog', 'variation'] as $tax_slug )
		{		
			$tax_obj = get_taxonomy( 'usam-'.$tax_slug );	
			$filters[$tax_slug] = ['title' => $tax_obj->labels->menu_name, 'type' => 'checklists', 'show' => false	]; 
		} 	
		$filters += [
			'product_type' => ['title' => __('Тип товара', 'usam'), 'type' => 'select', 'show' => false],
			'contractors' => ['title' => __('Поставщик товара', 'usam'), 'type' => 'checklists', 'show' => false],
			'parsing_sites' => ['title' => __('Парсинг', 'usam'), 'type' => 'checklists', 'query' => ['site_type' => 'supplier', 'fields' => 'id=>name', 'active' => 'all'], 'show' => false],
			'webspy_link' => ['title' => __('Внешняя ссылка', 'usam'), 'type' => 'string_meta', 'show' => false],
			'exchange_rules' => ['title' => __('Шаблон импорта', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'product_import']],
			'platform_export' => ['title' => __('Экспортированы в платформы', 'usam'), 'type' => 'checklists', 'show' => false],
			'discount' => ['title' => __('Установленные скидки', 'usam'), 'type' => 'checklists', 'query' => ['discount_set' => 1, 'type_rule' => ['product','fix_price']]],			
			'sku' => ['title' => __('Артикул', 'usam'), 'type' => 'string_meta'],
			'code' => ['title' => __('Внешний код', 'usam'), 'type' => 'string_meta', 'show' => false],
			'title' => ['title' => __('Название товара', 'usam'), 'type' => 'string', 'show' => false],
			'content' => ['title' => __('Описание товара', 'usam'), 'type' => 'string', 'show' => false],
			'excerpt' => ['title' => __('Краткое описание', 'usam'), 'type' => 'string', 'show' => false],
			'name' => ['title' => __('Slug', 'usam'), 'type' => 'string', 'show' => false],
			'post_author' => ['title' => __('Автор', 'usam'), 'type' => 'checklists', 'show' => false],
			'ps' => ['title' => __('Выбор свойств', 'usam'), 'type' => 'select', 'show' => false],
			'price' => ['title' => __('Цена', 'usam'), 'type' => 'numeric'],
			'oldprice' => ['title' => __('Старая цена', 'usam'), 'type' => 'numeric', 'show' => false],
			'weight' => ['title' => __('Вес', 'usam'), 'type' => 'numeric', 'show' => false],
			'views' => ['title' => __('Просмотры', 'usam'), 'type' => 'numeric'],
			'comment' => ['title' => __('Комментарии', 'usam'), 'type' => 'numeric'],			
			'rating' => ['title' => __('Рейтинг', 'usam'), 'type' => 'numeric'],
			'compare' => ['title' => __('В сравнении', 'usam'), 'type' => 'numeric'],
			'desired' => ['title' => __('В избранном', 'usam'), 'type' => 'numeric'],
			'subscription' => ['title' => __('Подписки', 'usam'), 'type' => 'numeric'],				
			'basket' => ['title' => __('В корзине', 'usam'), 'type' => 'numeric'],
			'purchased' => ['title' => __('Продано', 'usam'), 'type' => 'numeric'],
			'barcode' => ['title' => __('Штрихкод', 'usam'), 'type' => 'numeric', 'show' => false],
			'post_id' => ['title' => __('ID товара', 'usam'), 'type' => 'numeric', 'show' => false],
			'stock' => ['title' => __('Доступный остаток', 'usam'), 'type' => 'numeric'],
			'total_balance' => ['title' => __('Общий остаток', 'usam'), 'type' => 'numeric'],			
		];
		$terms = usam_get_product_attributes();				
		$attribute_ids = [];
		foreach ( $terms as $term )
		{
			if ( usam_get_term_metadata($term->term_id, 'field_type') )							
				$attribute_ids[] = $term->term_id;
		}						
		$attribute_values = [];
		foreach( usam_get_product_attribute_values(['attribute_id' => $attribute_ids, 'orderby' => 'value']) as $option )
		{
			$attribute_values[$option->attribute_id][] =  ['id' => $option->id, 'name' => $option->value, 'code' => $option->code];
		}								
		foreach( $terms as $property )
		{							
			$property->type = 'string_meta';
			$property->show = false;
			$property->field_type = usam_get_term_metadata($property->term_id, 'field_type');
			$property->title = $property->name;	
			$property->value = $this->get_filter_value( 'v_attr_'.$property->term_id );
			if ( $property->field_type == 'COLOR_SEVERAL' || $property->field_type == 'M')
				$property->value = array_map('intval', (array)$property->value);
			else
				$property->value = sanitize_text_field($property->value);	
			if ( $property->field_type == 'AUTOCOMPLETE' )
			{
				$property->request = 'attribute_values';			
				$property->request_parameters = ['attribute_id' => $property->term_id];
			}
			$property->options = isset($attribute_values[$property->term_id]) ? $attribute_values[$property->term_id] : [];	
			$filters['attr_'.$property->term_id] = (array)$property;	
			if ( isset($attribute_values[$property->term_id]) )
				unset($attribute_values[$property->term_id]);
		}
		$storages = usam_get_storages();
		if ( count($storages) <= 200 )
		{
			foreach ( $storages as $storage ) 
				$filters['storage_'.$storage->id] = ['title' => $storage->title, 'type' => 'numeric', 'show' => false];
		}
		return $filters;		
	}
	
	public function get_platform_export_options() 
	{
		return apply_filters('usam_filter_options_exporting_product_platforms', []);
	}	
	
	public function get_ps_options() 
	{
		return [
			['id' => 'image_yes', 'name' => __('Показать с миниатюрами', 'usam')], 
			['id' => 'image_no', 'name' => __('Показать без миниатюр', 'usam')], 
			['id' => 'webspy_yes', 'name' => __('Товары с ссылками на поставщика', 'usam')], 
			['id' => 'webspy_no', 'name' => __('Товары без ссылок на поставщика', 'usam')], 
			['id' => 'excerpt_yes', 'name' => __('Показать с описанием', 'usam')], 
			['id' => 'excerpt_no', 'name' => __('Показать без описание', 'usam')], 
			['id' => 'variant_prod', 'name' => __('Товары имеющие вариации', 'usam')]
		];
	}
	
	public function get_product_type_options() 
	{
		return [['id' => 'simple', 'name' => __('Простой', 'usam')], ['id' => 'variable', 'name' => __('Вариационный', 'usam')]];
	}
	
	public function get_sort()
	{
		return ['date-desc' => __('По дате &#8595;', 'usam'), 'date-asc' => __('По дате &#8593;', 'usam'), 'modified-desc' => __('По дате изменения &#8595;', 'usam'), 'modified-asc' => __('По дате изменения &#8593;', 'usam'), 'price-desc' => __('По цене &#8595;', 'usam'), 'price-asc' => __('По цене &#8593;', 'usam'), 'id-desc' => __('Сначала новые', 'usam'), 'id-asc' => __('Сначала старые', 'usam'), 'title-asc' => __('По названию А-Я', 'usam'), 'title-desc' => __('По названию Я-А', 'usam'), 'sticky-desc' => __('Добавленные в список &#8595;', 'usam'), 'sticky-asc' => __('Добавленные в список &#8593;', 'usam'), 'category-desc' => __('По категории &#8595;', 'usam'), 'category-asc' => __('По категории &#8593;', 'usam'), 'sku-desc' => __('По артикулу &#8595;', 'usam'), 'sku-asc' => __('По артикулу &#8593;', 'usam'), 'percent-desc' => __('Процент скидки &#8595;', 'usam'), 'percent-asc' => __('Процент скидки &#8593;', 'usam'), 'comment-desc' => __('По комментариям &#8595;', 'usam'), 'comment-asc' => __('По комментариям &#8593;', 'usam'), 'views-desc' => __('По просмотрам &#8595;', 'usam'), 'views-asc' => __('По просмотрам &#8593;', 'usam'), 'rating-desc' => __('По рейтингу &#8595;', 'usam'), 'rating-asc' => __('По рейтингу &#8593;', 'usam'), 'stock-desc' => __('По остатку &#8595;', 'usam'), 'stock-asc' => __('По остатку &#8593;', 'usam'), 'purchased-desc' => __('По продажам &#8595;', 'usam'), 'purchased-asc' => __('По продажам &#8593;', 'usam'), 'desired-desc' => __('Количество в избранном &#8595;', 'usam'), 'desired-asc' => __('Количество в избранном &#8593;', 'usam'), 'compare-desc' => __('Количество в сравнении &#8595;', 'usam'), 'compare-asc' => __('Количество в сравнении &#8593;', 'usam'), 'basket-desc' => __('Количество в корзине &#8595;', 'usam'), 'basket-asc' => __('Количество в корзине &#8593;', 'usam')];	
	}	
}
?>