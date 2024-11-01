<?php
// Описание: Шаблон страницы "Отзывы клиентов"

global $post, $wp_query; 
if ( isset($wp_query->query['id']) )
	$query['page_id'] = $wp_query->query['id'];
else
{	
	$query['page_id'] = [ $post->ID ];
	if ( usam_is_system_page( 'reviews' ) )
		$query['page_id'][] = 0;
}
$customer_reviews = new USAM_Customer_Reviews_Theme(); 
echo $customer_reviews->show_button_reviews_form( 'top' );
echo $customer_reviews->show_reviews_form( 'top' );	
echo $customer_reviews->output_reviews_show( $query );
echo $customer_reviews->show_button_reviews_form( 'bottom' );
echo $customer_reviews->show_reviews_form( 'bottom' );
echo $customer_reviews->aggregate_footer();
?>