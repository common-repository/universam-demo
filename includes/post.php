<?php
//Таблица для хранения данных(все поля индексные)
function usam_add_post_meta($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	if ( !usam_is_multisite() || is_main_site() )
		return usam_add_metadata('post', $object_id, $meta_key, $meta_value, USAM_TABLE_POST_META, $prev_value );
	else
		return true;
}

function usam_get_post_meta( $object_id, $meta_key = '', $id_main_site = true, $single = true) 
{	
	if ( $id_main_site )
		$object_id = usam_get_post_id_main_site( $object_id );	
	$value = usam_get_metadata('post', $object_id, USAM_TABLE_POST_META, $meta_key, $single );
	if ( $single )
	{
		if ($meta_key == 'rating_count' || $meta_key == 'rating' || $meta_key == 'views' || $meta_key == 'like' || $meta_key == 'comment' || $meta_key == 'compare' || $meta_key == 'desired' || $meta_key == 'subscription' || $meta_key == 'basket' || $meta_key == 'purchased')			
			$value = (int)$value;	
	}
	return $value;
}

function usam_update_post_meta($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	if ( !usam_is_multisite() || is_main_site() )
		return usam_update_metadata('post', $object_id, $meta_key, $meta_value, USAM_TABLE_POST_META, $prev_value );
	else
		return true;
}

function usam_delete_post_meta( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{	
	if ( !usam_is_multisite() || is_main_site() )
		return usam_delete_metadata('post', $object_id, $meta_key, USAM_TABLE_POST_META, $meta_value, $delete_all );
	else
		return true;
}

//Фразы для поиска товаров и постов
function usam_add_post_search_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	return usam_add_metadata('post_search', $object_id, $meta_key, $meta_value, usam_get_table_db('posts_search'), $prev_value );
}

function usam_get_post_search_metadata( $object_id, $meta_key = '', $single = true) 
{	
	return usam_get_metadata('post_search', $object_id, usam_get_table_db('posts_search'), $meta_key, $single );
}

function usam_update_post_search_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{ 
	return usam_update_metadata('post_search', $object_id, $meta_key, $meta_value, usam_get_table_db('posts_search'), $prev_value );
}

function usam_delete_post_search_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 
	return usam_delete_metadata('post_search', $object_id, $meta_key, usam_get_table_db('posts_search'), $meta_value, $delete_all );
}

function usam_update_post_rating($product_id, $add_rating, $take_away_rating = 0 ) 
{
	$rating_count = $rating_new = 0;
	if ( $product_id )
	{
		$old_rating_count = $rating_count = usam_get_post_meta( $product_id, 'rating_count' );	
		$old_rating = usam_get_post_meta( $product_id, 'rating' );
		
		$rating_sum = $old_rating*$rating_count;							
		if ( $take_away_rating )
		{
			--$rating_count;				
			$rating_sum -= $take_away_rating;
		}
		if ( $add_rating )
		{
			++$rating_count;				
			$rating_sum += $add_rating;		
		}		
		$rating_new = $rating_count ? round($rating_sum / $rating_count): 0;				
		
		usam_update_post_meta( $product_id, 'rating_count', $rating_count );	
		usam_update_post_meta( $product_id, 'rating', $rating_new );	
		
		do_action( 'usam_update_post_rating', $product_id, $rating_new, $rating_count, $old_rating, $old_rating_count );
	}
	return ['rating_count' => $rating_count, 'rating' => $rating_new];
}

function usam_get_social_post_id($post_id, $profile) 
{ 
	return (int)usam_get_post_meta( $post_id, 'post_id_'.$profile['type_social'].'_'.$profile['code'] );		
}

function usam_get_social_post_publish_date($post_id, $profile) 
{
	return usam_local_date( usam_get_post_meta( $post_id, 'publish_date_'.$profile['type_social'].'_'.$profile['code'] ) );		
}
?>