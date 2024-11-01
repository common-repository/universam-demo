<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Виджеты
include_once( USAM_FILE_PATH . '/includes/widgets/product_filter_widget.php' );
include_once( USAM_FILE_PATH . '/includes/widgets/shop_tools_widget.php' );
include_once( USAM_FILE_PATH . '/includes/widgets/product_discount_widget.php' );
include_once( USAM_FILE_PATH . '/includes/widgets/product_specials_widget.php' );
include_once( USAM_FILE_PATH . '/includes/widgets/latest_products_widget.php' );
include_once( USAM_FILE_PATH . '/includes/widgets/product_groups_widget.php' );
include_once( USAM_FILE_PATH . '/includes/widgets/personal_account_widget.php' );
include_once( USAM_FILE_PATH . '/includes/widgets/company_info_widget.php' );

/**
 * Register Widgets.
 */
function usam_register_widgets() 
{
	register_widget( 'USAM_Widget_Company_Info' );	
	register_widget( 'USAM_Widget_Filter_Products' );
	register_widget( 'USAM_Widget_Shop_Tools' );	
	register_widget( 'USAM_Widget_Latest_Products' );
	register_widget( 'USAM_Widget_Products_Discount' );
	register_widget( 'USAM_Widget_Product_Groups' );
	register_widget( 'USAM_Widget_Product_Specials' );
	register_widget( 'USAM_Widget_Personal_Account' );
	
	register_sidebar([
		'name'          => __('Виджеты на главной странице сверху', 'usam'),
		'id'            => 'top-page-home',
		'description'   => __('Здесь можно добавить любые виджеты, которые вы хотите показать на главной странице.', 'usam'),
		'before_widget' => '',
		'after_widget'  => '',
		'before_title'  => '',
		'after_title'   => '',
	]);
	
	register_sidebar([
		'name'          => __('Виджеты в шапке списка товаров', 'usam'),
		'id'            => 'top-page-products',
		'description'   => __('Здесь можно добавить фильтры.', 'usam'),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '',
		'after_title'   => '',
	]);	
	
	register_sidebar( array(
		'name'          => __('Виджеты для инструментов просмотра товаров', 'usam'),
		'id'            => 'top-shop-tools',
		'description'   => __('Здесь можно добавить инструменты просмотра каталога.', 'usam'),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '',
		'after_title'   => '',
	));	
	register_sidebar( array(
		'name' => __('Боковая панель для каталога', 'usam'),
		'id' => 'product',					
		'description'   => __('Боковая панель для каталога', 'usam'),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget' => '</section>',
		'before_title' => '<h4 class="widget-title">',
		'after_title' => '</h4>',
	));
	register_sidebar([
		'name'          => __('Виджеты в товаре при просмотре списком', 'usam'),
		'id'            => 'list-product',
		'description'   => __('Здесь можно добавить, например, остаток по товару', 'usam'),
		'before_widget' => '',
		'after_widget'  => '',
		'before_title'  => '',
		'after_title'   => '',
	]);
	register_sidebar( array(
		'name' => __('Боковая панель на странице поиска', 'usam'),
		'id' => 'search',					
		'description'   => __('Разместите виджеты, которые будут отображаться на странице поиска', 'usam'),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget' => '</section>',
		'before_title' => '<h4 class="widget-title">',
		'after_title' => '</h4>',
	));
	register_sidebar( array(
		'name' => __('Виджеты в карточке товара рядом с кнопкой купить', 'usam'),
		'id' => 'single-product-buttons',					
		'description'   => __('Вы можете разместить виджеты, которые будут отображаться рядом с кнопкой Купить в карточке товара', 'usam'),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget' => '</section>',
		'before_title' => '<h4 class="widget-title">',
		'after_title' => '</h4>',
	));	
	register_sidebar( array(
		'name' => __('Виджеты в карточке товара под кнопками купить', 'usam'),
		'id' => 'widgets-single-product',					
		'description'   => __('Вы можете разместить виджеты, которые будут отображаться под кнопками покупок в товаре', 'usam'),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget' => '</section>',
		'before_title' => '<h4 class="widget-title">',
		'after_title' => '</h4>',
	));
	register_sidebar( array(
		'name' => __('Виджеты в карточке товара внизу основного блока', 'usam'),
		'id' => 'single-product-2',					
		'description'   => __('Вы можете разместить виджеты, которые будут внизу основного блока, который рядом с картинкой', 'usam'),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget' => '</section>',
		'before_title' => '<h4 class="widget-title">',
		'after_title' => '</h4>',
	));	
	register_sidebar( array(
		'name' => __('Виджеты в карточке товара когда, товар продан', 'usam'),
		'id' => 'single-product-sold',					
		'description'   => __('Вы можете разместить виджеты, которые будут отображаться когда он продан', 'usam'),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget' => '</section>',
		'before_title' => '<h4 class="widget-title">',
		'after_title' => '</h4>',
	));	
	register_sidebar( array(
		'name' => __('Виджеты в списке товаров когда, товар продан', 'usam'),
		'id' => 'product-sold',					
		'description'   => __('Вы можете разместить виджеты, которые будут отображаться в списке товаров, когда он продан', 'usam'),
		'before_widget' => '',
		'after_widget' => '',
		'before_title' => '',
		'after_title' => '',
	));	
	if( get_option('usam_registration_upon_purchase') == 'suggest' )
	{
		register_sidebar([
			'name' => __('Виджеты при оформлении заказа Войти или зарегистрироваться', 'usam'),
			'id' => 'checkout-suggest-login',					
			'description'   => __('Вы можете разместить виджеты, которые будут в блоке Войти или зарегистрироваться', 'usam'),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget' => '</section>',
			'before_title' => '<h4 class="widget-title">',
			'after_title' => '</h4>',
		]);			
	}
}
add_action( 'widgets_init', 'usam_register_widgets' );
