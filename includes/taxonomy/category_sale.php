<?php
/**
 * Функции для отображения категорий скидок
 */
 
/**
* Получить ссылку на категорию
*/
function usam_get_category_sale( $args = array() )
{
	$args['status'] = 'publish';
	$args['term_meta_cache'] = 1;	
	
	if ( !isset($args['hide_empty']) )
		$args['hide_empty'] = false;
	
	if ( !isset($args['orderby']) )
		$args['orderby'] = 'id'; 
	
	if ( !isset($args['order']) )
		$args['order'] = 'DESC';
	
	$args['taxonomy'] = 'usam-category_sale';	
	$terms = get_terms( $args ); 	
	$category_sale = array();
	$time = time();
	foreach( $terms as $term ) 
	{
		$start_date = usam_get_term_metadata($term->term_id, 'start_date_stock');	
		$end_date = usam_get_term_metadata($term->term_id, 'end_date_stock');							
		if ( ( !empty($start_date) && strtotime($start_date) > $time ) || (!empty($end_date) && strtotime($end_date) < $time) ) 
			continue;
		
		$area = usam_get_term_metadata($term->term_id, 'sale_area');		
		if ( !usam_in_customer_sales_area($area) )
		{
			continue;
		}
		$category_sale[] = $term;
	}
	usam_update_terms_thumbnail_cache( $category_sale );
	return $category_sale;
}

function usam_update_post_category_sale_count( $terms, $taxonomy )
{
	if ( apply_filters( 'usam_update_post_category_sale_count', true ) )
		_update_post_term_count( $terms, $taxonomy );
}
?>