<?php 
// Подключаемые опции блока
$block = [
	'code' => 'products_grid',
	'name' => __('Новости', 'usam'),    // Название блока для вывода посетителям
	'html_name' => __('Посты плиткой', 'usam'), // Имя блока для администратора
	'device' => 0,  // На каких устройствах показывать, 0 на всех
	'active' => 1,  // Активен сразу после добавления
	'options' => [ // список опций блока
		['field_type' => 'select', 'name' => __('Тег названия', 'usam'), 'code' => 'tag_name', 'options' => ['h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'div' => 'div'], 'value' => 'h3'],
		['field_type' => 'textarea', 'name' => __('Описание', 'usam'), 'code' => 'description', 'value' => ''],
		['field_type' => 'route', 'name' => __('Категории', 'usam'), 'code' => 'ids', 'route' => 'categories', 'value' => [], 'multiple' => 1],
		['field_type' => 'BUTTONS', 'name' => __('Карусель', 'usam'), 'code' => 'carousel', 'value' => 0],
		['field_type' => 'text', 'name' => __('Количество постов', 'usam'), 'code' => 'number', 'value' => 3],
		['field_type' => 'text', 'name' => __('Количество в строке', 'usam'), 'code' => 'columns', 'value' => 3],	
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