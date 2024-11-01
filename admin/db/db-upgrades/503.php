<?php
global $wp_roles, $wpdb;
USAM_Install::create_or_update_tables([USAM_TABLE_COMMENTS,USAM_TABLE_WEBFORMS]);
$sold = get_option( 'usam_display_sold_products', 'sort');
if ( $sold )
	update_option( 'usam_display_sold_products', 'sort');
else
	update_option( 'usam_display_sold_products', 'show');

global $wp_rewrite;
$wp_rewrite->flush_rules();

$fields = array( 			
	['name' => __('Название отзыва','usam'), 'code' => 'review_title', 'field_type' => 'text', 'group' => 'main', 'mandatory' => 0, 'active' => 1, 'connection' => ''],
	['name' => __('Отзыв','usam'), 'code' => 'review', 'field_type' => 'textarea', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => ''],
	['name' => __('Ваша оценка','usam'), 'code' => 'rating','field_type' => 'rating', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => ''],
	['name' => __('Я согласен c обработкой персональных данных и публикацией отзыва на сайте','usam'), 'code' => 'consent_publication_review', 'field_type' => 'one_checkbox', 'group' => 'main', 'mandatory' => 1, 'active' => 1, 'connection' => 'consent'],
);
foreach ( $fields as $key => $field )
{
	$field['sort'] = $key+1;
	$field['type'] = 'webform';
	$id = usam_insert_property( $field );
}

$reviews = get_option( 'usam_reviews');
if ( $reviews )
{	
	$reviews['per_page'] = isset($reviews['reviews_per_page'])?$reviews['reviews_per_page']:10;
	unset($reviews['fields']);
	unset($reviews['reviews_per_page']);	
	$reviews['goto_show_button'] = 'review';
	update_option( 'usam_reviews', $reviews);
}