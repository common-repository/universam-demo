<?php 
// Подключаемые опции блока
$block = [
	'code' => 'brands_grid',
	'name' => __('Посмотрите как это выглядит', 'usam'),    // Название блока для вывода посетителям
	'html_name' => __('Товары на фото', 'usam'), // Имя блока для администратора
	'device' => 0,  // На каких устройствах показывать, 0 на всех
	'active' => 1,  // Активен сразу после добавления
	'options' => [ // список опций блока
		['field_type' => 'select', 'name' => __('Тег названия', 'usam'), 'code' => 'tag_name', 'options' => ['h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'div' => 'div'], 'value' => 'h3'],
		['field_type' => 'textarea', 'name' => __('Описание', 'usam'), 'code' => 'description', 'value' => ''],
		['field_type' => 'route', 'name' => __('Какие баннеры выводить', 'usam'), 'route' => 'banners', 'code' => 'id', 'value' => 0],
	],	
	'content_style' => [ // Стиль элементов
		['field_type' => 'text', 'name' => __('Отступы между элементами', 'usam'), 'code' => 'gap', 'value' => '20px'],
	],	
	
];
?>