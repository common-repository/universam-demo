<?php
/**
 * Регистрирует таксомании
 */
class USAM_Post_types
{
	function __construct( ) 
	{			
		add_action( 'init', array('USAM_Post_types', 'register_taxonomies'), 1 );		
		
		add_action( 'init', array($this, 'create_new_post_status') );	
		add_filter( 'display_post_states', array($this, 'display_status'), 10, 2 );
	}	
	
	/**	Описание: Создать новые статусы	 */
	function create_new_post_status()
	{
		register_post_status( 'archive', [
			'label'                     => __('Архив', 'usam'),
			'public'                    => false, // Показывать ли посты с этим статусом в лицевой части сайта.
			'exclude_from_search'       => false, //Исключить ли посты с этим статусом из результатов поиска.
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Архив <span class="count">(%s)</span>', 'Архив <span class="count">(%s)</span>' ),
		]);
		register_post_status( 'defect', [
			'label'                     => __('Брак', 'usam'),
			'public'                    => false, // Показывать ли посты с этим статусом в лицевой части сайта.
			'exclude_from_search'       => false, //Исключить ли посты с этим статусом из результатов поиска.
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Брак <span class="count">(%s)</span>', 'Брак <span class="count">(%s)</span>' ),
		]);
		register_post_status( 'hidden', [
			'label'                     => __('Скрытый', 'usam'),
			'public'                    => true, // Показывать ли посты с этим статусом в лицевой части сайта.
			'exclude_from_search'       => false, //Исключить ли посты с этим статусом из результатов поиска.
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'publicly_queryable'        => true,			
			'label_count'               => _n_noop( 'Скрытые <span class="count">(%s)</span>', 'Скрытые <span class="count">(%s)</span>' ),
		]);	
	}
	
	function display_status( $states, $post )
	{
		$arg = get_query_var( 'post_status' );
		if( $arg != 'archive' )
		{
			if( $post->post_status == 'archive' )
				return [__('Архив', 'usam')];
		}
		return $states;
	}	

	/**
	 * В этой функции регистрируем наши типы потов
	 */
	public static function register_taxonomies() 
	{
		$types = get_option('usam_types_products_sold', ['product', 'services'] );
		if ( !empty($types) )
		{
			$permalinks = get_option( 'usam_permalinks' );
			// Товары
			$labels = [
				'name'               => __('Товары'                    , 'usam'),
				'singular_name'      => __('Товар'                     , 'usam'),
				'add_new'            => __('Добавить товар'            , 'usam'),
				'add_new_item'       => __('Добавить новый товар'      , 'usam'),
				'edit_item'          => __('Изменить товар'            , 'usam'),
				'new_item'           => __('Новый товар'               , 'usam'),
				'view_item'          => __('Посмотреть товар'          , 'usam'),
				'search_items'       => __('Поиск товаров'             , 'usam'),
				'not_found'          => __('Товары не найдены'         , 'usam'),
				'not_found_in_trash' => __('Товары не найдены в корзине', 'usam'),
				'menu_name'          => __('Товары'                     , 'usam'),
				'featured_image'        => __( 'Изображение товара', 'usam' ),
				'set_featured_image'    => __( 'Задать изображение товара', 'usam' ),
				'remove_featured_image' => __( 'Удалить изображение товара', 'usam' ),
				'use_featured_image'    => __( 'Использовать в качестве изображения товара', 'usam' ),
				'insert_into_item'      => __( 'Insert into product', 'usam' ),
				'uploaded_to_this_item' => __( 'Загружено в товар', 'usam' ),
				'filter_items_list'     => __( 'Фильтровать товары', 'usam' ),
				'items_list_navigation' => __( 'Навигация по товарам', 'usam' ),
				'items_list'            => __( 'Список товаров', 'usam' ),
				'parent_item_colon'  => '',
			 ];
			$args = [
				'capability_type'      => 'product',
				'supports'             => ['title', 'editor'],
				'hierarchical'         => true, //Будут ли записи этого типа иметь древовидную структуру (как постоянные страницы). true - да, будут древовидными, false - нет, будут связаны тексономией (категориями)
				'exclude_from_search'  => false,
				'public'               => true,
				'show_ui'              => true,
				'show_in_nav_menus'    => true,
				'menu_icon'            => "dashicons-cart",
				'labels'               => $labels,
				'query_var'            => true,
				'show_in_rest'         => true,
				'rewrite'              => [
					'slug'       => untrailingslashit( empty($permalinks['product_base']) ? '' : $permalinks['product_base'] ),
					'with_front' => false,
					'feeds' => true
				]				
			];
			$args = apply_filters( 'usam_register_post_types_products_args', $args );
			register_post_type( 'usam-product', $args );
			
			// Свойства товаров
			$labels = [
				'name'              => _x( 'Свойства товаров', 'название таксоманий' , 'usam'),
				'singular_name'     => _x( 'Свойство товара'         , 'название одной таксомании', 'usam'),
				'search_items'      => __('Поиск свойства' , 'usam'),
				'all_items'         => __('Все свойства'    , 'usam'),
				'parent_item'       => __('Группа'  , 'usam'),
				'parent_item_colon' => __('Группа:', 'usam'),
				'edit_item'         => __('Изменить свойство'    , 'usam'),
				'update_item'       => __('Обновить свойство'  , 'usam'),
				'add_new_item'      => __('Добавить новое свойство' , 'usam'),
				'new_item_name'     => __('Имя нового свойства товаров', 'usam'),
				'not_found'         => __('Свойств товаров не найдено', 'usam'),
			];
			$args = [
				'labels'       => $labels,
				'hierarchical' => true,
				'show_in_rest' => false,
				'show_in_quick_edit' => false,
				'rewrite'      => false,
				'capabilities' => [
					'manage_terms' => 'manage_product_attribute',
					'edit_terms' => 'edit_product_attribute',
					'delete_terms' => 'delete_product_attribute',
					'assign_terms' => 'edit_product',
				]
			];			
			$args = apply_filters( 'usam_register_taxonomies_product_attributes_args', $args );	
			register_taxonomy( 'usam-product_attributes', 'usam-product', $args );
					
			$labels = [
				'name'              => __('Типы товаров', 'usam'),
				'singular_name'     => __('Тип товара', 'usam'),
				'menu_name'         => __('Типы товаров', 'usam'),
			];	
			$args = [
				'hierarchical' => false,
				'query_var'    => 'product_type',
				'rewrite'      => false,
				'labels'       => $labels,
				'public'       => false,			
			];			
			$args = apply_filters( 'usam_register_taxonomies_product_type_args', $args );	
			register_taxonomy( 'usam-product_type', 'usam-product', $args );
			
			// Вариации товаров
			$labels = [
				'name'              => _x( 'Вариации', 'название таксоманий' , 'usam'),
				'singular_name'     => _x( 'Вариация', 'название одной таксомании', 'usam'),
				'search_items'      => __('Поиск вариации', 'usam'),
				'all_items'         => __('Все вариации', 'usam'),
				'parent_item'       => __('Параметры вариации', 'usam'),
				'parent_item_colon' => __('Параметры вариаций:', 'usam'),
				'edit_item'         => __('Изменить вариацию', 'usam'),
				'update_item'       => __('Обновить вариацию', 'usam'),
				'add_new_item'      => __('Добавить новую вариацию', 'usam'),
				'new_item_name'     => __('Имя новой вариации', 'usam'),
				'not_found'         => __('Вариаций не найдено', 'usam'),
			];
			$args = [
				'hierarchical' => true,
				'query_var'    => 'variations',
				'rewrite'      => false,
				'public'       => true,
				'show_in_quick_edit' => false,	
				'labels'       => $labels
			];
			$args = apply_filters( 'usam_register_taxonomies_product_variation_args', $args );	
			register_taxonomy( 'usam-variation', 'usam-product', $args );
			// Категории продукции, является иерархическими и можно использовать постоянные
			$labels = [
				'name'              => _x( 'Категории товаров'       , 'название таксоманий' , 'usam'),
				'singular_name'     => _x( 'Категория товаров'         , 'название одной таксомании', 'usam'),
				'search_items'      => __('Поиск категории товаров', 'usam'),
				'all_items'         => __('Все категории товаров'   , 'usam'),
				'parent_item'       => __('Родительская категория товаров'  , 'usam'),
				'parent_item_colon' => __('Родительская категория товаров:' , 'usam'),
				'edit_item'         => __('Изменить категорию'    , 'usam'),
				'update_item'       => __('Обновить категорию товаров'  , 'usam'),
				'add_new_item'      => __('Добавить новую категорию товаров' , 'usam'),
				'new_item_name'     => __('Имя новой категории товаров', 'usam'),
				'menu_name'         => __('Категории', 'usam'),
			];
			$args = [
				'labels'       => $labels,
				'show_in_quick_edit' => false,
				'hierarchical' => true,
				'show_in_rest' => true,
				'rewrite'      => [
					'slug'         => empty($permalinks['category_base']) ? 'product-category' : $permalinks['category_base'],
					'with_front'   => false,
					'hierarchical' => (bool)get_option('usam_category_hierarchical_url', 0),
				],
				'capabilities' => [
					'manage_terms' => 'manage_product_category',
					'edit_terms' => 'edit_product_category',
					'delete_terms' => 'delete_product_category',
					'assign_terms' => 'edit_product',
				]
			];
			$args = apply_filters( 'usam_register_taxonomies_product_category_args', $args );
			register_taxonomy( 'usam-category', 'usam-product', $args );			
			
			// Бренды товаров
			$labels = [
				'name'              => _x( 'Бренды'        , 'taxonomy general name' , 'usam'),
				'singular_name'     => _x( 'Бренд'         , 'taxonomy singular name', 'usam'),
				'search_items'      => __('Поиск бренда' , 'usam'),
				'all_items'         => __('Все бренды'    , 'usam'),
				'parent_item'       => __('Родитель бренда'  , 'usam'),
				'parent_item_colon' => __('Родители брендов:', 'usam'),
				'edit_item'         => __('Редактирование бренда'    , 'usam'),
				'update_item'       => __('Обновление бренда'  , 'usam'),
				'add_new_item'      => __('Добавить новый бренд' , 'usam'),
				'new_item_name'     => __('Добавить имя бренда', 'usam'),
				'not_found'         => __('Брендов не найдено', 'usam'),			
			];		
			$args = [
				'hierarchical' => true,
				'show_in_rest' => true,
				'show_in_quick_edit' => false,
				'rewrite'      => [
					'slug'         => empty($permalinks['brand_base']) ? 'brand' : $permalinks['brand_base'],
					'with_front'   => false,
					'hierarchical' => true,
				],					
				'labels'       => $labels
			];		
			$args = apply_filters( 'usam_register_taxonomies_product_brands_args', $args );
			register_taxonomy( 'usam-brands', 'usam-product', $args );
			
			// категории для скидок
			$labels = [
				'name'              => __('Акции магазина', 'usam'),
				'singular_name'     => __('Акция магазина', 'usam'),
				'search_items'      => __('Поиск акции магазина' , 'usam'),
				'all_items'         => __('Все акции магазина'    , 'usam'),
				'parent_item'       => __('Родитель акции магазина'  , 'usam'),
				'parent_item_colon' => __('Родители акций магазина:', 'usam'),
				'edit_item'         => __('Редактирование акции магазина'    , 'usam'),
				'update_item'       => __('Обновление акции магазина'  , 'usam'),
				'add_new_item'      => __('Добавить новую' , 'usam'),
				'new_item_name'     => __('Добавить имя акции магазина', 'usam'),
				'popular_items'     =>  null,
			];
			$args = [		
				'hierarchical' => true,	
				'show_in_quick_edit' => false,
				'show_in_rest' => true,
				'rewrite'      => [
					'slug'         => empty($permalinks['category_sale_base']) ? 'category_sale' : $permalinks['category_sale_base'],
					'with_front'   => false,
					'hierarchical' => false,
				],		
				'update_count_callback' => 'usam_update_post_category_sale_count',			
				'labels'       => $labels
			];			
			$args = apply_filters( 'usam_register_taxonomies_product_category_sale_args', $args );
			register_taxonomy( 'usam-category_sale', 'usam-product', $args );	
			
			//Подборки
			$labels = [
				'name'              => __('Подборки товаров', 'usam'),
				'singular_name'     => __('Подборка', 'usam'),
				'search_items'      => __('Поиск подборки' , 'usam'),
				'all_items'         => __('Все подборки'    , 'usam'),
				'parent_item'       => __('Родитель подборки'  , 'usam'),
				'parent_item_colon' => __('Родители подборок:', 'usam'),
				'edit_item'         => __('Редактирование подборки', 'usam'),
				'update_item'       => __('Обновление подборки'  , 'usam'),
				'add_new_item'      => __('Добавить подборку' , 'usam'),
				'new_item_name'     => __('Добавить имя подборки', 'usam'),
				'popular_items'     =>  null,
			];
			$args = [	
				'hierarchical' => true,	
				'show_in_rest' => true,
				'show_in_quick_edit' => false,
				'rewrite'      => array(
					'slug'         => empty($permalinks['selection_base']) ? 'selection' : $permalinks['selection_base'],
					'with_front'   => false,
					'hierarchical' => false,
				),	
				'capabilities' => [
					'manage_terms' => 'manage_product_selection',
					'edit_terms' => 'edit_product_selection',
					'delete_terms' => 'delete_product_selection',
					'assign_terms' => 'edit_product',
				],		
			//	'update_count_callback' => 'usam_update_post_selection_count',			
				'labels'       => $labels
			];			
			$args = apply_filters( 'usam_register_taxonomies_product_selection_args', $args );
			register_taxonomy( 'usam-selection', 'usam-product', $args );	
			
			// Теги продуктов
			$labels = [
				'name'          => _x( 'Метки товаров'       , 'название таксоманий' , 'usam'),
				'singular_name' => _x( 'Метка товаров'       , 'название одной таксомании', 'usam'),
				'search_items'  => __('Поиск меток товаров' , 'usam'),
				'all_items'     => __('Все теги товаров'    , 'usam'),
				'edit_item'     => __('Изменить тег'            , 'usam'),
				'update_item'   => __('Обновить тег'          , 'usam'),
				'add_new_item'  => __('Добавить новый тег товаров' , 'usam'),
				'new_item_name' => __('Имя нового тега товаров', 'usam'),
			];
			$args = [
				'hierarchical' => false,
				'show_in_rest' => true,
				'labels' => $labels,
				'rewrite' => [
					'slug' => empty($permalinks['tag_base']) ? 'product-tag' : $permalinks['tag_base'],
					'with_front' => false,
					'hierarchical' => false,
				]
			];
			$args = apply_filters( 'usam_register_taxonomies_product_tag_args', $args );
			register_taxonomy( 'product_tag', 'usam-product', $args );
			
			// Каталоги товаров
			$labels = array(
				'name'              => _x( 'Каталоги'        , 'taxonomy general name' , 'usam'),
				'singular_name'     => _x( 'Каталог'         , 'taxonomy singular name', 'usam'),
				'search_items'      => __('Поиск каталога' , 'usam'),
				'all_items'         => __('Все каталоги'    , 'usam'),
				'parent_item'       => __('Родитель каталога'  , 'usam'),
				'parent_item_colon' => __('Родители каталогов:', 'usam'),
				'edit_item'         => __('Редактирование каталога'    , 'usam'),
				'update_item'       => __('Обновление каталога'  , 'usam'),
				'add_new_item'      => __('Добавить новый каталог' , 'usam'),
				'new_item_name'     => __('Добавить имя каталога', 'usam'),
				'not_found'         => __('Каталогов не найдено', 'usam'),			
			);		
			$args = [
				'hierarchical' => true,
				'show_in_rest' => true,
				'rewrite'      => [
					'slug'         => empty($permalinks['catalog_base']) ? 'catalog' : $permalinks['catalog_base'],
					'with_front'   => false,
					'hierarchical' => true,
				],	
				'capabilities' => [
					'manage_terms' => 'manage_product_catalog',
					'edit_terms' => 'edit_product_catalog',
					'delete_terms' => 'delete_product_catalog',
					'assign_terms' => 'edit_product',
				],	
				'labels'       => $labels
			];		
			register_taxonomy( 'usam-catalog', 'usam-product', apply_filters( 'usam_register_taxonomies_product_catalog_args', $args ) );
		}
		if ( usam_check_type_product_sold('electronic_product') || usam_check_type_product_sold('service') || usam_check_type_product_sold('subscription') )
		{
			// Лицензионные договоры
			$labels = [
				'name'               => __('Лицензионные договоры'     , 'usam'),
				'singular_name'      => __('Лицензионный договор'      , 'usam'),
				'add_new'            => __('Добавить', 'usam'),
				'add_new_item'       => __('Добавить новый лицензионный договор', 'usam'),
				'edit_item'          => __('Изменить лицензионный договор', 'usam'),
				'new_item'           => __('Новый лицензионный договор'  , 'usam'),
				'view_item'          => __('Посмотреть лицензионный договор', 'usam'),
				'search_items'       => __('Поиск лицензионного договора', 'usam'),
				'not_found'          => __('Лицензионные договоры не найдены'         , 'usam'),
				'not_found_in_trash' => __('Лицензионный договор не найден в корзине', 'usam'),
				'menu_name'          => __('Соглашения', 'usam'),
				'parent_item_colon'  => '',
			];
			$args = [
				'capability_type'      => 'agreement',
				'supports'             => ['title', 'editor', 'thumbnail'],
				'hierarchical'         => true, //Будут ли записи этого типа иметь древовидную структуру (как постоянные страницы). true - да, будут древовидными, false - нет, будут связаны тексономией (категориями)
				'exclude_from_search'  => false,
				'public'               => true,
				'show_ui'              => true,
				'show_in_nav_menus'    => true,
				'menu_icon'            => "dashicons-cart",
				'labels'               => $labels,
				'query_var'            => true,
				'rewrite'              => [
					'slug'       => untrailingslashit( empty($permalinks['agreement']) ? 'agreement' : $permalinks['agreement'] ),
					'with_front' => false,
					'feeds' => true
				]
			];
			register_post_type( 'usam-agreement', apply_filters( 'usam_register_post_types_agreement_args', $args ) );		
		}
		$labels = [
			'name'              => _x( 'Галереи'        , 'taxonomy general name' , 'usam'),
			'singular_name'     => _x( 'Галерея'         , 'taxonomy singular name', 'usam'),
			'search_items'      => __('Поиск галереи' , 'usam'),
			'all_items'         => __('Все галереи'    , 'usam'),
			'parent_item'       => __('Родитель галереи'  , 'usam'),
			'parent_item_colon' => __('Родители галерей:', 'usam'),
			'edit_item'         => __('Редактирование галереи'    , 'usam'),
			'update_item'       => __('Обновление галереи'  , 'usam'),
			'add_new_item'      => __('Добавить новый галерею' , 'usam'),
			'new_item_name'     => __('Добавить имя галереи', 'usam'),
			'not_found'         => __('Галереи не найдено', 'usam'),			
		];		
		$args = [
			'hierarchical' => true,
			'show_in_rest' => true,
			'show_in_quick_edit' => false,
			'rewrite'      => [
				'slug'         => empty($permalinks['gallery_base']) ? 'gallery' : $permalinks['gallery_base'],
				'with_front'   => false,
				'hierarchical' => true,
			],	
			'labels'       => $labels
		];		
		$args = apply_filters( 'usam_register_taxonomies_gallery_args', $args );
		register_taxonomy( 'usam-gallery', 'attachment', $args );
		
		do_action( 'usam_register_post_types_after' );
		do_action( 'usam_register_taxonomies_after' );
	}	
}
new USAM_Post_types();
?>