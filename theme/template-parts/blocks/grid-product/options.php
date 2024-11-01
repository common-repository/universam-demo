<?php 
// Подключаемые опции блока
$block = [
	'code' => 'products_grid',
	'name' => __('Популярные товары', 'usam'),    // Название блока для вывода посетителям
	'html_name' => __('Товары плиткой', 'usam'), // Имя блока для администратора
	'device' => 0,  // На каких устройствах показывать, 0 на всех
	'active' => 1,  // Активен сразу после добавления
	'options' => [ // список опций блока
		['field_type' => 'select', 'name' => __('Тег названия', 'usam'), 'code' => 'tag_name', 'options' => ['h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'div' => 'div'], 'value' => 'h3'],
		['field_type' => 'textarea', 'name' => __('Описание', 'usam'), 'code' => 'description', 'value' => ''],
		['field_type' => 'select', 'name' => __('Какие товары выводить', 'usam'), 'code' => 'compilation', 'options' => usam_get_product_selections(), 'value' => 'popularity'],
		['field_type' => 'BUTTONS', 'name' => __('Карусель', 'usam'), 'code' => 'carousel', 'value' => 0],
		['field_type' => 'text', 'name' => __('Количество товаров', 'usam'), 'code' => 'number', 'value' => 10],
		['field_type' => 'text', 'name' => __('Количество в строке', 'usam'), 'code' => 'columns', 'value' => 5],
	]
];
?>