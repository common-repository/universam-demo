<?php 
/* Меню для личного кабинета */

$tabs = [];
if ( get_option('usam_website_type', 'store' ) == 'marketplace' && (current_user_can('seller_company') || current_user_can('seller_contact') ) )
{
	$tabs[] = ['title' => __('Заказы', 'usam'), 'slug' => 'seller-orders', 'vue' => true];
}
else
{	
	$tabs[] = ['title' => __('Заказы', 'usam'), 'slug' => 'my-orders', 'vue' => true];
}
if ( usam_check_type_product_sold( 'electronic_product' ) )
{
	$tabs[] = ['title' => __('Купленные файлы', 'usam'), 'slug' => 'my-downloadable'];			
	$user_id = get_current_user_id();	
	$files = usam_get_files(['user_file' => $user_id, 'number' => 1]);
	if ( !empty($files) )
		$tabs[] = ['title' => __('Файлы', 'usam'), 'slug' => 'my-file'];
}
$rules = usam_get_bonuses_rules();
if ( $rules )
	$tabs[] = ['title' => __('Мои бонусы', 'usam'), 'slug' => 'my-bonus'];
$tabs[] = ['title' => __('Мои отзывы', 'usam'), 'slug' => 'my-comments'];
$tabs[] = ['title' => __('Мои компании', 'usam'), 'slug' => 'my-company', 'vue' => true];
if ( current_user_can('view_contacting') )
	$tabs[] = ['title' => __('Обращения', 'usam'), 'slug' => 'my-contacting', 'vue' => true];
if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
{
	if( current_user_can('seller_company') || current_user_can('seller_contact') )
	{
		$tabs[] = ['title' => __('Товары', 'usam'), 'slug' => 'products', 'vue' => true];
	}		
}
else
{
	if ( usam_availability_check_price_list() )
	{
		$tabs[] = ['title' => __('Прайс-лист', 'usam'), 'slug' => 'price-list'];
	}	
	if ( usam_show_referral_menu() )
	{
		$tabs[] = ['title' => __('Партнерская ссылка', 'usam'), 'slug' => 'my-referral', 'vue' => true];	
	}
}
$tabs[] = ['title' => __('Мой профиль', 'usam'), 'slug' => 'my-profile', 'vue' => true];
?>