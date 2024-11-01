<?php 
// Подключаемые опции блока
$block = [
	'code' => 'page',                             // Код блока
	'name' => '',    // Название блока для вывода посетителям
	'html_name' => __('Вывод содержимого выбранной страницы', 'usam'), // Имя блока для администратора
	'device' => 0,  // На каких устройствах показывать, 0 на всех
	'active' => 1,  // Активен сразу после добавления
	'options' => [ // список опций блока
		['field_type' => 'select', 'name' => __('Тег названия', 'usam'), 'code' => 'tag_name', 'options' => ['h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'div' => 'div'], 'value' => 'h3'],
		['field_type' => 'textarea', 'name' => __('Описание', 'usam'), 'code' => 'description', 'value' => ''],
		['field_type' => 'autocomplete', 'name' => __('Страница', 'usam'), 'code' => 'page_id', 'default' => [], 'request' => 'pages', 'query' => ['post_status' => ['publish', 'draft'] ]],
	]
];
?>