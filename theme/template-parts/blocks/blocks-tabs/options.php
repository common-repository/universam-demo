<?php 
// Подключаемые опции блока
$block = [
	'code' => 'blocks_tabs',
	'name' => __('Товары', 'usam'),    // Название блока для вывода посетителям
	'html_name' => __('Блоки во вкладках', 'usam'), // Имя блока для администратора
	'device' => 0,  // На каких устройствах показывать, 0 на всех
	'active' => 1,  // Активен сразу после добавления
	'options' => [ // список опций блока
		['field_type' => 'select', 'name' => __('Тег названия', 'usam'), 'code' => 'tag_name', 'options' => ['h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'div' => 'div'], 'value' => 'h3'],
		['field_type' => 'textarea', 'name' => __('Описание', 'usam'), 'code' => 'description', 'value' => ''],
		['field_type' => 'htmlblocks', 'name' => __('HTML блоки', 'usam'), 'code' => 'ids', 'value' => [], 'multiple' => 1],		
	],		
];
?>