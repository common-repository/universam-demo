<?php 
// Подключаемые опции блока
$block = [
	'code' => 'category_grid',
	'name' => __('Категории', 'usam'),    // Название блока для вывода посетителям
	'html_name' => __('Категории', 'usam'), // Имя блока для администратора
	'device' => 0,  // На каких устройствах показывать, 0 на всех
	'active' => 1,  // Активен сразу после добавления
	'options' => [ // список опций блока
		['field_type' => 'select', 'name' => __('Тег названия', 'usam'), 'code' => 'tag_name', 'options' => ['h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'div' => 'div'], 'value' => 'h3'],
		['field_type' => 'textarea', 'name' => __('Описание', 'usam'), 'code' => 'description', 'value' => ''],	
		['field_type' => 'BUTTONS', 'name' => __('Термин для вывода', 'usam'), 'code' => 'tax', 'options' => [['id' => 'usam-category', 'name' => __('Категории', 'usam')], ['id' => 'usam-selection', 'name' => __('Подборки', 'usam')], ['id' => 'usam-brands', 'name' => __('Бренды', 'usam')], ['id' => 'usam-category_sale', 'name' => __('Акции', 'usam')], ['id' => 'usam-catalog', 'name' => __('Каталоги', 'usam')]], 'value' => 'usam-category'],
		['field_type' => 'changed_route', 'name' => __('Термины', 'usam'), 'code' => 'ids', 'route' => 'categories', 'value' => [], 'multiple' => 1, 'options' => ['usam-category' => 'categories', 'usam-selection' => 'selections', 'usam-brands' => 'brands', 'usam-category_sale' => 'category_sales', 'usam-catalog' => 'catalogs'], 'option' => 'tax'],			
		['field_type' => 'select', 'name' => __('Вариант', 'usam'), 'code' => 'variant', 'options' => ['v1' => 'v1', 'v2' => 'v2', 'v3' => 'v3', 'v4' => 'v4', 'v5' => 'v5', 'v6' => 'v6', 'v7' => 'v7'], 'value' => 'v1'],		
		['field_type' => 'BUTTONS', 'name' => __('Карусель', 'usam'), 'code' => 'carousel', 'value' => 0],		
		['field_type' => 'text', 'name' => __('Отображаемые элементы в карусели', 'usam'), 'code' => 'columns', 'value' => '6'],
	],
	'content_style' => [ // Стиль элементов
		['field_type' => 'select', 'name' => __('Эффект для фото', 'usam'), 'code' => 'effect', 'options' => [
			['id' => '', 'name' => __('Нет эффектов', 'usam')],
			['id' => 'blackout', 'name' => __('Затемнение фотографии', 'usam')],			
		]], 
		['field_type' => 'text', 'name' => __('Отступы между категориями', 'usam'), 'code' => 'gap', 'value' => '10px'],
		['field_type' => 'text', 'name' => __('Размер шрифта текста', 'usam'), 'code' => 'font-size', 'value' => ''],
		['field_type' => 'select', 'name' => __('Толщина текста', 'usam'), 'code' => 'font-weight', 'options' => ['400' => '400', '500' => '500', '600' => '600', '700' => '700'], 'value' => '600'],
		['field_type' => 'color', 'name' => __('Цвет текста', 'usam'), 'code' => 'color', 'value' => ''],
		['field_type' => 'select', 'name' => __('Стиль текста', 'usam'), 'code' => 'text-transform', 'options' => [
			['id' => '', 'name' => __('По умолчанию', 'usam')], 
			['id' => 'uppercase', 'name' => __('Верхний регистр', 'usam')],
			['id' => 'lowercase', 'name' => __('Нижний регистр', 'usam')],
			['id' => 'capitalize', 'name' => __('Первый символ заглавным', 'usam')],
		], 'value' => 'uppercase'],
		['field_type' => 'select', 'name' => __('Стиль текста', 'usam'), 'code' => 'text-align', 'options' => [
			['id' => '', 'name' => __('Выравнивание', 'usam')], 
			['id' => 'left', 'name' => __('Влево', 'usam')],
			['id' => 'center', 'name' => __('Центр', 'usam')],
			['id' => 'right', 'name' => __('Вправо', 'usam')],
		], 'value' => 'center'],
	],		
];
?>