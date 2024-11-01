<?php
class USAM_Tab_Presentation extends USAM_Page_Tab
{
	protected $vue = true;	
	protected $views = ['simple'];
	public function __construct() 
	{					
		add_action('admin_footer', [$this, 'display_footer']);
		add_action('admin_enqueue_scripts', function() { 				
			wp_enqueue_media();			
			wp_enqueue_script( 'v-color' ); 					
		});
	}
		
	public function get_title_tab()
	{			
		return __('Настройка темы', 'usam');	
	}
	
	public function display_footer(  ) 
	{						
		require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
		$webforms_review = ['' => __('Не показывать','usam')];
		$webforms_order = [];
		foreach( usam_get_webforms() as $webform )
		{	
			if ( $webform->action == 'review' )
				$webforms_review[$webform->code] = $webform->title;
			else
				$webforms_order[$webform->code] = $webform->title;
		}			
		$views = usam_get_site_product_view();
		$settings = [];
		if ( count($views) > 1 )
		{
			$settings = [
				['block' => 'products', 'field_type' => 'checklist', 'name' => __('Варианты отображения каталога', 'usam'), 'code' => 'product_views', 'options' => $views, 'default' => ['grid', 'list']],			
				['block' => 'products', 'field_type' => 'select', 'multiple' => 0, 'name' => __('Вариант отображения по умолчанию', 'usam'), 'code' => 'product_view', 'options' => $views],	
			];
		}
		$settings = array_merge( $settings, [			
			['block' => 'products', 'field_type' => 'check', 'name' => __('Показать путь', 'usam'), 'code' => 'show_breadcrumbs', 'description' => __('Показать путь до страницы (хлебные крошки)', 'usam')],	
			['block' => 'products', 'field_type' => 'check', 'name' => __('Показывать список категорий', 'usam'), 'code' => 'display_categories',  'description' => __('Показывать список категорий', 'usam')],	
			['block' => 'products', 'field_type' => 'checklist', 'name' => __('Варианты сортировки', 'usam'), 'code' => 'sorting_options', 'options' => usam_get_product_sorting_options(), 'default' => ['name', 'price', 'popularity', 'date']],
			['block' => 'products', 'field_type' => 'select', 'name' => __('Сортировка по', 'usam'), 'code' => 'product_sort_by', 'options' => usam_get_product_sorting_options(), 'multiple' => 0, 'default' => ['name', 'price', 'popularity', 'date']],
			['block' => 'products', 'field_type' => 'select', 'name' => __('Отсутствующие товары', 'usam'), 'code' => 'display_sold_products', 'multiple' => 0, 'options' => ['show' => __('Показать', 'usam'), 'sort' => __('Показать в конце списка', 'usam'), 'hide' => __('Скрыть', 'usam')]],
			['block' => 'products', 'field_type' => 'check', 'name' => __('Показывать с нулевой ценой', 'usam'), 'code' => 'show_zero_price', 'description' => __('Показывать ли товары у которых цена равна нулю?', 'usam')], 
			['block' => 'products', 'field_type' => 'check', 'name' => __('Использовать разбиения на страницы', 'usam'), 'code' => 'product_pagination'],
			['block' => 'products', 'field_type' => 'text', 'name' => __('Количество продуктов на странице', 'usam'), 'code' => 'products_per_page'],		
			['block' => 'products', 'field_type' => 'checklist', 'name' => __('Пользовательские списки товаров', 'usam'), 'code' => 'users_product_lists', 'options' => usam_get_users_product_lists(), 'default' => ['compare', 'desired', 'subscription']],
			['block' => 'purchase_product', 'field_type' => 'select', 'name' => __('Кнопка "Под заказ"', 'usam'), 'code' => 'under_order_button', 'options' => $webforms_order, 'set_option' => 'global'],
			['block' => 'purchase_product', 'field_type' => 'select', 'name' => __('Кнопка "Добавить в корзину"', 'usam'), 'code' => 'hide_addtocart_button', 'options' => [0 => __('Показать', 'usam'), 1 => __('Скрыть', 'usam'), 2 => __('Скрыть для не авторизованных', 'usam')], 'set_option' => 'global'],
			['block' => 'purchase_product', 'field_type' => 'select', 'name' => __('При добавлении в корзину показать', 'usam'), 'code' => 'popup_adding_to_cart', 'options' => [0 => __('Ничего', 'usam'), 'sidebar' => __('Боковая панель', 'usam'), 'popup' => __('Всплывающие окно', 'usam'), 'info' => __('Информационное окно', 'usam')], 'default' => 'popup', 'set_option' => 'global'],
			['block' => 'purchase_product', 'field_type' => 'text', 'name' => __('Текст кнопки "Добавить в корзину"', 'usam'), 'code' => 'name_addtocart_button', 'default' => __('В корзину', 'usam'), 'set_option' => 'global'],
			
			['block' => 'view_product', 'field_type' => 'check', 'name' => __('Показать рейтинг товара', 'usam'), 'code' => 'show_product_rating'],	
			['block' => 'view_product', 'field_type' => 'check', 'name' => __('Добавить поле "количество"', 'usam'), 'code' => 'show_multi_add'],			
			['block' => 'view_product', 'field_type' => 'check', 'name' =>__('Показать название вариации', 'usam'), 'code' => 'show_name_variation'],
			['block' => 'view_product', 'field_type' => 'select', 'name'=>__('Блоки с описанием товара', 'usam'), 'code' => 'product_content_display', 'options' => ['tab' => __('Вкладками', 'usam'), 'list' => __('Списком', 'usam')], 'default' => 'tab'],
		]);			
		$catalogs = get_terms(['hide_empty' => 0, 'taxonomy' => 'usam-catalog', 'fields' => 'id=>name']);			
		if ( !empty($catalogs) )
		{
			$catalogs = ['0' => __('Все каталоги','usam')] + $catalogs;
			$settings[] = ['block' => 'products', 'field_type' => 'select', 'multiple' => 0, 'name' => __('Каталог по умолчанию', 'usam'), 'code' => 'default_catalog', 'options' => $catalogs];		 
		}
		$settings = array_merge( $settings, [
		//	['block' => 'products', 'field_type' => 'file', 'name' => __('Заглушка если картинки нет', 'usam'), 'code' => 'no_image_uploaded'],	
			['block' => 'category', 'field_type' => 'check', 'name' => __('Иерархический URL', 'usam'), 'code' => 'category_hierarchical_url',
			   'description' => '<strong>'.get_bloginfo('url').'/product-category/parent-cat/sub-cat/product-name</strong>'],
			['block' => 'category', 'field_type' => 'select', 'name' => __('Отображение в категориях', 'usam'), 'code' => 'show_subcatsprods_in_cat', 'options' => ['0' => __('Подкатегории, а в последней категории товары', 'usam'), '1' => __('Всегда товары', 'usam')]],
			['block' => 'category', 'field_type' => 'check', 'name' => __('Показать название категории', 'usam'), 'code' => 'display_category_name', 'description' => __('Показать название категории в блоке описания', 'usam')],
			['block' => 'category', 'field_type' => 'check', 'name' => __('Показать описание категории', 'usam'), 'code' => 'category_description', 'description' => __('Показать описание категории в блоке описания', 'usam')],	
			['block' => 'productlabel', 'field_type' => 'text', 'name' => __('Количество дней товар новинка', 'usam'), 'code' => 'number_days_product_new', 'default' => 14],	
			['block' => 'productlabel', 'field_type' => 'checklist', 'name' => __('Метки в товарах', 'usam'), 'code' => 'product_tags', 'options' => ['sold' => __('Запасы проданы', 'usam'), 'percent_action' => __('Метка процент акции', 'usam'), 'new' => __('Метка новинки', 'usam')], 'default' => ['sold', 'percent_action', 'new']],
			['block' => 'basket', 'field_type' => 'check', 'name' => __('Форма ввода купонов', 'usam'), 'code' => 'uses_coupons', 'description' => __('Показывать форму ввода кодов купонов', 'usam'), 'set_option' => 'global'],
			['block' => 'basket', 'field_type' => 'check', 'name' => __('Форма ввода бонусов', 'usam'), 'code' => 'uses_bonuses', 'description' => __('Показывать форму ввода бонусов', 'usam'), 'set_option' => 'global'],								
			['block' => 'personal_account', 'field_type' => 'checklist', 'name' => __('Кнопки действий заказа', 'usam'), 'code' => 'order_action_buttons', 'options' => ['copy' => __('Повторить заказ', 'usam'), 'add_to_cart' => __('В корзину', 'usam'), 'pay' => __('Оплатить', 'usam'), 'add_review' => __('Оставить отзыв', 'usam'), 'cancel_order' => __('Отменить заказ', 'usam')], 'default' => ['pay', 'add_to_cart']],
			['block' => 'personal_account', 'field_type' => 'check', 'name' => __('Активация профиля', 'usam'), 'code' => 'user_profile_activation', 'description' => __('Включение личного кабинета только после заполнения профиля', 'usam'), 'default' => 0],
			['block' => 'menu', 'field_type' => 'categories', 'name' => __('Категория', 'usam'), 'code' => 'default_menu_category', 'default' => 0, 'description' => __('Выберите, категорию для начала меню', 'usam')],
			['block' => 'menu', 'field_type' => 'check', 'name' => __('Кешировать меню', 'usam'), 'code' => 'cache_menu', 'default' => 0],								
			['block' => 'page_review', 'code' => 'goto_show_button', 'field_type' => 'select', 'name' => __('Форма отзыв', 'usam'), 'option' => 'reviews', 'options' => $webforms_review, 'default' => ''],
			['block' => 'page_review', 'code' => 'per_page', 'field_type' => 'text', 'name' => __('Количество отзывов на странице', 'usam'), 'option' => 'reviews', 'default' => 20],
			['block' => 'page_review', 'code' => 'form_location', 'field_type' => 'select', 'name' => __('Расположение формы отзыва', 'usam'), 'option' => 'reviews', 'options' => ['top' => __('Над отзывами', 'usam'), 'bottom' => __('Под отзывами', 'usam')], 'default' => 'top'],
			['block' => 'page_review', 'code' => 'show_hcard_on', 'field_type' => 'select', 'name' => __('Включить вывод Бизнес hCard на', 'usam'), 'option' => 'reviews', 'options' => [1 => __('всех постах и страницах и товарах', 'usam'), 2 => __('главной и странице отзывов', 'usam'), 3 => __('только на странице отзывов', 'usam'), 4 => __('только в товарах', 'usam'), 0 => __('нигде', 'usam')],  'description' => __('Это включит Микроформат hCard, который включает в себя вашу контактную информацию бизнеса. Это рекомендуется включить для всех сообщений, страниц и товаров.','usam')],
			['block' => 'page_review', 'code' => 'show_hcard', 'field_type' => 'select', 'name' => __('Видимость Бизнес hCard', 'usam'), 'option' => 'reviews', 'options' => [0 => __('Скрыть для всех посетителей', 'usam'), 1 => __('Показывать для всех посетителей', 'usam')],  'description' => __('На экране появится hCard для посетителей (и поисковые системы). Поисковые системы, как правило, игнорируют микроформат информации, которая скрыта, так что, как правило, хорошая идея, чтобы установить это "показ".','usam')],		
		]);	  	
		foreach( usam_get_site_style() as $k => $item )
			$settings[] = ['block' => 'style', 'code' => $k, 'field_type' => $item['type'], 'name' => $item['label'], 'default' => $item['default'], 'set_option' => 'theme_mod'];			
			
		if ( get_option('usam_website_type', 'store' ) !== 'crm' )
		{
			$settings = array_merge( $settings, [			
				['block' => 'search_product_property', 'name' => __('По артикулу', 'usam'), 'field_type' => 'select', 'code' => 'sku', 'option' => 'search_product_property', 'default' => 'like', 'options' => ['' => __('отключено', 'usam'), '=' => __('по полному совпадению', 'usam'), 'like' => __('по частичному совпадению', 'usam')]],
				['block' => 'search_product_property','name' => __('По штрих-коду', 'usam'), 'field_type' => 'check', 'code' => 'barcode', 'option' => 'search_product_property', 'default' => 1],
				['block' => 'search_product_property','name' => __('По описанию', 'usam'), 'field_type' => 'check', 'code' => 'post_content', 'option' => 'search_product_property', 'default' => 0],
				['block' => 'search_product_property', 'name' => __('По дополнительному описанию', 'usam'), 'field_type' => 'check', 'code' => 'post_excerpt', 'option' => 'search_product_property', 'default' => 0],
			]);	
		}
		$settings = array_merge( $settings, [			
			['block' => 'search_results_page', 'name' => __('Количество символов описания', 'usam'), 'default' => 100, 'field_type' => 'text', 'code' => 'search_text_lenght'],	
			['block' => 'search_results_page', 'name' => __('Показать артикул', 'usam'), 'field_type' => 'check', 'code' => 'search_sku_enable'],
			['block' => 'search_results_page', 'name' => __('Показать В наличии', 'usam'), 'field_type' => 'check', 'code' => 'search_in_stock_enable'],
			['block' => 'search_results_page', 'name' => __('Показать цену', 'usam'), 'field_type' => 'check', 'code' => 'search_price_enable'],		
			['block' => 'search_results_page', 'name' => __('Показать категорию товара', 'usam'), 'field_type' => 'check', 'code' => 'search_categories_enable'],				
			['block' => 'search_settings_widget', 'name' => __('Текст окна поиска', 'usam'), 'default' => 50, 'field_type' => 'text', 'code' => 'search_box_text'],
			['block' => 'search_settings_widget', 'name' => __('Количество результатов', 'usam'), 'default' => 5, 'field_type' => 'text', 'code' => 'search_result_items'],	
			['block' => 'search_settings_widget', 'name' => __('Длина описания', 'usam'), 'default' => 100, 'field_type' => 'text', 'code' => 'search_text_lenght'],
			['block' => 'search_settings_widget', 'name' => __('Длина названия', 'usam'), 'default' => 50, 'field_type' => 'text', 'code' => 'search_length_name_items'],
			['block' => 'search_settings_widget', 'name' => __('Глобальный поиск', 'usam'), 'default' => 1, 'field_type' => 'check', 'code' => 'search_global_search', 'description' => __( "Установить глобальный поиск или поиск в текущей категории продукта или текущего тега продукта. Отметьте, чтобы активировать глобальный поиск", 'usam')],	
		]);		
		$tabs = [			
			'style' => ['name' => __('Цвета и стили', 'usam'),
				'blocks' => [
					['code' => 'style', 'name' => __('Цвета и стили темы', 'usam')],	
				]
			],
			'category' => ['name' => __('Категории товаров', 'usam'),
				'blocks' => [
					['code' => 'category', 'name' => __('Настройки страниц и категорий товаров', 'usam')],	
				]
			],
			'productlabel' => ['name' => __('Стикеры', 'usam'),
				'blocks' => [
					['code' => 'productlabel', 'name' => __('Настройка', 'usam')],	
				]
			],
			'product' =>  ['name' => __('Карточка товара', 'usam'),
				'blocks' => [
					['code' => 'purchase_product', 'name' => __('Настройка покупки товара', 'usam')],	
					['code' => 'view_product', 'name' => __('Настройка просмотра товара', 'usam')],
				]				
			],
			'basket' =>  ['name' => __('Корзина', 'usam'),
				'blocks' => [
					['code' => 'basket', 'name' => __('Настройки корзины', 'usam')],	
				]			
			],
			'personal_account' =>  ['name' => __('Личный кабинет', 'usam'),
				'blocks' => [
					['code' => 'personal_account', 'name' => __('Настройки личного кабинета', 'usam')],	
				]			
			],
			'menu' =>  ['name' => __('Меню', 'usam'),
				'blocks' => [
					['code' => 'menu', 'name' => __('Настройки меню', 'usam')],	
				]			
			],	
			'review' =>  ['name' => __('Отзывы', 'usam'),
				'blocks' => [
					['code' => 'page_review', 'name' => __('Страница отзывов', 'usam')],	
				]			
			],	
			'search' =>  ['name' => __('Поиск', 'usam'),
				'blocks' => [
					['code' => 'search_product_property', 'name' => __('Выбор свойств, участвующих в поиске', 'usam')],	
					['code' => 'search_results_page', 'name' => __('Страница результатов поиска', 'usam')],
					['code' => 'search_settings_widget', 'name' => __('Раскрывающиеся поле поиска', 'usam')],
				]				
			],					
		];			
		$htmlblocks = usam_get_html_blocks(['active' => 'all']);
		$home_blocks = usam_get_home_blocks();		
		$tabs = apply_filters( 'usam_theme_settings_tabs', $tabs );		
		$settings = $this->get_value_options( apply_filters( 'usam_theme_settings', $settings ) );	
		?>
		<script>			
			var htmlblocks = <?php echo json_encode( $htmlblocks ); ?>;	
			var home_blocks = <?php echo json_encode( $home_blocks ); ?>;	
			var settings = <?php echo json_encode( $settings ); ?>;			
			var tabs = <?php echo json_encode( $tabs ); ?>;
			var no_image_uploaded = <?php echo json_encode( ['url' => usam_get_no_image_uploaded_file().'?v='.time(), 'error' => false] ); ?>;
			var default_category = <?php echo json_encode( get_option( 'usam_default_category' ) ); ?>;			
		</script>
		<?php
	}
	
	public function get_value_options( $options )
	{			
		foreach( $options as $k => $option )
		{
			$options[$k]['option'] = !empty($option['option'])?$option['option']:'';
			$name_option = $options[$k]['option']?$option['option']:$option['code'];
			$options[$k]['set_option'] = !empty($option['set_option'])?$option['set_option']:'';
			if( $options[$k]['set_option'] == 'theme_mod' )
				$value = get_theme_mod( $name_option, $option['default'] );			
			elseif( usam_is_multisite() && $options[$k]['set_option'] == 'global' )
				$value = get_site_option( 'usam_'.$name_option, isset($option['default'])?$option['default']:'' );	
			else						
				$value = get_option( 'usam_'.$name_option, isset($option['default'])?$option['default']:'' );				
			if( !empty($option['option']) )
				$value = isset($value[$option['code']]) ? $value[$option['code']] : $option['default'];
			$options[$k]['value'] = $value;
		}		
		return $options;
	}	
		
	public function display() 
	{		
		?>
		<div class="hertical_section">
			<div class="hertical_section_tabs">		
				<div class="section_tab" v-if="home_blocks.length" @click="tab='homeblocks'" :class="{'active':tab=='homeblocks'}"><?php _e( 'Блоки на главной странице', 'usam'); ?></div>
				<div class="section_tab" @click="tab='productpage'" :class="{'active':tab=='productpage'}"><?php _e( 'Cтраницы с товарами', 'usam'); ?></div>	
				<div class="section_tab" v-for="(tabData, k) in tabs" @click="tab=k" :class="{'active':tab==k}">{{tabData.name}}</div>				
			</div>
			<div class="hertical_section_content" v-show="tab=='homeblocks'">			
				<usam-box :id="'homeblocks'" :handle="false">
					<template v-slot:title>
						<?php _e( 'Блоки на главной странице', 'usam'); ?>
						<selector v-model="filters.active" :items="[{id:0, name:'<?php _e('Все', 'usam'); ?>'},{id:1, name:'<?php _e('Только активные', 'usam'); ?>'}]"></selector>						
					</template>
					<template v-slot:body>
						<div class="home_page">
							<div class="home_page__item" v-for="(block, k) in home_blocks" v-if="filters.active && block.active || !filters.active" @dragover="allowDrop($event, k)" @dragstart="drag($event, k)" @dragend="dragEnd($event, k)">				
								<div class="home_page__item_name_text" draggable="true" title="<?php _e('Перетащите, чтобы поменять местами блоки', 'usam'); ?>">{{block.title}}</div>
								<div class="home_page__option">																			
									<div class="home_page__option_active">		
										<selector v-model="block.active"></selector>
										<select v-model="block.device">						
											<option value="0"><?php _e( 'На всех устройствах', 'usam'); ?></option>
											<option value="mobile"><?php _e( 'Только на мобильных', 'usam'); ?></option>
											<option value="desktop"><?php _e( 'Только на компьютерах', 'usam'); ?></option>
										</select>
									</div>	
									<div class="home_page__option_names">
										<input v-model="block.display_title" class="home_page__option_names_title" type='text' placeholder="<?php _e('Название блока на сайте', 'usam'); ?>"/>
										<input v-model="block.description" class="home_page__option_names_description" type='text' placeholder="<?php _e('Описание блока на сайте', 'usam'); ?>"/>
										<div class="home_page__option_names_template">{{block.template}}.php</div>
									</div>
								</div>	
								<div class='home_page__settings edit_form'>
									<div class ="edit_form__item" v-for="(property, k) in block.options">
										<div class ="edit_form__item_name">{{property.name}}</div>
										<div class="edit_form__item_option">
											<?php require( USAM_FILE_PATH.'/admin/templates/template-parts/type-option.php' ); ?>
										</div>
									</div>									
								</div>							
							</div>
						</div>						
					</template>
				</usam-box>	
			</div>
			<div class="hertical_section_content" v-show="tab=='productpage'">	
				<usam-box :id="'products'" :handle="false" :title="'<?php _e( 'Настройки страниц и категорий товаров', 'usam'); ?>'">
					<template v-slot:body>
						<div class='edit_form'>
							<div class ="edit_form__item" v-for="(property, k) in getProperties('products')">
								<div class ="edit_form__item_name">{{property.name}}</div>
								<div class="edit_form__item_option">
									<?php require( USAM_FILE_PATH.'/admin/templates/template-parts/type-option.php' ); ?>
								</div>
							</div>
							<div class ="edit_form__item">
								<div class ="edit_form__item_name"><?php esc_html_e( 'Заглушка если картинки нет', 'usam'); ?>:</div>
								<div class="edit_form__item_option usam_attachments usam_attachments_hideplaceholder" @drop="fDrop" @dragover="aDrop">
									<div class='usam_attachments__file'>
										<div class='attachment_icon'>	
											<progress-circle v-if="no_image_uploaded.load" :percent="no_image_uploaded.percent"></progress-circle>							
											<div v-else class='image_container'><img :src="no_image_uploaded.url" alt="no_image_uploaded"></div>
										</div>
									</div>				
									<div class ='attachments__placeholder' @click="fAttach">
										<div class="attachments__placeholder__text"><?php esc_html_e( 'Перетащите или нажмите, чтобы изменить файл', 'usam'); ?></div>
										<div class="attachments__placeholder__select"><span class="dashicons dashicons-paperclip"></span><?php esc_html_e( 'Изменить файл', 'usam'); ?></div>					
									</div>
									<input type='file' @change="fChange" multiple>
								</div>
							</div>
						</div>
					</template>
				</usam-box>	
				<usam-box :id="'productpage'" :handle="false" :title="'<?php _e( 'Страница товаров', 'usam'); ?>'">
					<template v-slot:body>
						<?php $current_default = esc_attr( get_option( 'usam_default_category' ) ); ?>
						<div class='usam_setting_table edit_form'>
							<div class ="edit_form__item">
								<div class ="edit_form__item_name"><label><?php esc_html_e( 'На странице показать', 'usam'); ?>:</label></div>
								<div class ="edit_form__item_option">
									<select v-model="default_category">	
										<option value='all'><?php esc_html_e('Показать товары', 'usam') ?></option>
										<option value='list'><?php esc_html_e('Показывать Категории / Товары', 'usam') ?></option>
										<option value='catalogs-products'><?php esc_html_e('Показывать Каталоги / Товары', 'usam') ?></option>
										<option value='brands-products'><?php esc_html_e('Показывать Бренды / Товары', 'usam') ?></option>
										<option value='brands-category-products'><?php esc_html_e('Показывать Бренды / Категории / Товары', 'usam') ?></option>
										<optgroup label='<?php esc_html_e( 'Категории товаров', 'usam') ?>'>	
											<?php
											$args = array(
														'descendants_and_self' => 0,
														'selected_cats'        => array( $current_default ),
														'popular_cats'         => false,
														'walker'               => new Walker_Category_Select(),
														'taxonomy'             => 'usam-category',
														'checked_ontop'        => false, 
														'echo'                 => true,
													);
											wp_terms_checklist( 0, $args ); 
										?>
										<optgroup label='<?php esc_html_e( 'Бренды товаров', 'usam') ?>'>	
										<?php
											$args = array(
														'descendants_and_self' => 0,
														'selected_cats'        => array( $current_default ),
														'popular_cats'         => false,
														'walker'               => new Walker_Category_Select(),
														'taxonomy'             => 'usam-brands',
														'checked_ontop'        => false, 
														'echo'                 => true,
													);
											wp_terms_checklist( 0, $args ); 
											?>
										</optgroup>
									</select>
								</div>						
							</div>			
						</div>							
					</template>
				</usam-box>	
			</div>				
			<div class="hertical_section_content" v-show="tab==tabcode" v-for="(tabData, tabcode) in tabs" :key="tabcode">					
				<usam-box :id="block.code" :handle="false" v-for="block in tabData.blocks" :key="block.code">
					<template v-slot:title>
						{{block.name}}
					</template>
					<template v-slot:body>											
						<div class='edit_form'>
							<div class ="edit_form__item" v-for="(property, k) in getProperties(block.code)" :key="property.id">
								<div class ="edit_form__item_name">{{property.name}}</div>
								<div class="edit_form__item_option">
									<?php require( USAM_FILE_PATH.'/admin/templates/template-parts/type-option.php' ); ?>
									<p class="description" v-html="property.description"></p>
								</div>
							</div>
						</div>
					</template>
				</usam-box>					
			</div>
		</div>		
		<?php	
	}
}