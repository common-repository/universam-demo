<?php
/**
 * Функции для отображения категорий
 */ 
 
function usam_is_in_category() 
{
	global $wp_query;
	$is_in_category = false;
	if(!empty($wp_query->query_vars['usam-category']))
		$is_in_category = true;
	elseif( !empty($_GET['usam-category']) )
		$is_in_category = true;	
	return $is_in_category;
}

/**
 * Поиск ID категории по её альтернативному названию (slug)
 */
function usam_category_id($category_slug = '')
{
	if(empty($category_slug))
		$category_slug = get_query_var( 'usam-category' );
	elseif(array_key_exists('usam-category', $_GET))
		$category_slug = sanitize_title($_GET['usam-category']);

	if(!empty($category_slug))
	{
		$category = get_term_by('slug', $category_slug, 'usam-category');
		if(!empty($category->term_id))
			return $category->term_id;
		else
			return false;		
	} 
	else 
		return false;	
}

/**
* Вывести описание категории
*/
function usam_category_description($category_id = null)
{
	if( $category_id === null )
		$category_id = usam_category_id();
	if ( !$category_id )
		return '';
	$category = get_term_by('id', $category_id, 'usam-category');
	return  $category->description;
}

function usam_category_name($category_id = null) 
{
	if($category_id < 1)
		$category_id = usam_category_id();
	$category = get_term_by('id', $category_id, 'usam-category');
	return $category->name;
}
?>