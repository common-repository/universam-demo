<?php
/**
 * Класс для вариаций товара
 *
 * Это код обрабатывает добавление, редактирование и отображение вариации у продуктов
 */
class USAM_Variations 
{	
	private static $_instance = null;
	
	private static $variation_groups = []; // variation groups: i.e. colour, size
	private static $variation_group_count = 0;
	private static $current_variation_group = -1;
	private static $variation_group;
	
	private static $first_variations = []; 
	private static $variations = []; //variations inside variation groups: i.e. (red, green, blue) or (S, M, L, XL)
	private static $variation_count = 0;
	private static $current_variation = -1;
	private static $variation = [];
	private static $all_associated_variations = [];
	private static $product_id = 0;

	public static function init( $product_id ) 
	{			
		$product_terms = get_the_terms( $product_id, 'usam-variation' );
		if ( $product_terms )
		{
			usort($product_terms, function($a, $b){  
				static $variations = null;		
				if ( $variations === null )
					$variations = get_terms(['fields' => 'ids', "taxonomy" => "usam-variation", "hide_empty" => 0, 'orderby' => 'sort']);
				$items = array_flip($variations);
				return $items[$a->term_id] - $items[$b->term_id]; 
			});
		}
		self::$variation_group_count = 0;
		self::$current_variation_group = -1;
		self::$product_id = $product_id;
		self::$variation_groups = [];
		self::$first_variations = [];
		self::$all_associated_variations = [];		

		if ( empty($product_terms) )
			return false;		
			
		foreach($product_terms as $product_term) 
		{
			if ($product_term->parent > 0)
				self::$all_associated_variations[$product_term->parent][] = $product_term;
		}	
		self::$variation_groups = get_terms(['taxonomy' => 'usam-variation', 'include' => array_keys(self::$all_associated_variations), 'orderby' => 'sort']);	
		self::$variation_groups = apply_filters( 'usam_variation_groups', self::$variation_groups, $product_id );
		self::$all_associated_variations = apply_filters( 'usam_all_associated_variations', self::$all_associated_variations, self::$variation_groups, $product_id );

		$parent_ids = array_keys(self::$all_associated_variations);
		foreach( (array)$parent_ids as $parent_id )
		{				
			ksort(self::$all_associated_variations[$parent_id]);		
			self::$all_associated_variations[$parent_id] = array_values(self::$all_associated_variations[$parent_id]);
		}
		foreach((array)self::$variation_groups as $variation_group) 
		{
			$variation_id = $variation_group->term_id;
			if ( isset(self::$all_associated_variations[$variation_id][0]) )
				self::$first_variations[] = self::$all_associated_variations[$variation_id][0]->term_id;
		}		
		self::$variation_group_count = count(self::$variation_groups);
			
		$thumb_ids = array();	
		foreach( $product_terms as $product_term ) 
		{
			if ( $product_term->term_id )
			{
				$attachment_id = (int)get_term_meta($product_term->term_id, 'thumbnail', true);
				if ( !empty($attachment_id) )
					$thumb_ids[] = $attachment_id;
			}
		}
		if ( !empty($thumb_ids) )
			_prime_post_caches( $thumb_ids, false, true );
		
	}
	
	public static function instance( ) 
	{ 
		if ( is_null( self::$_instance ) )
		{
			self::$_instance = new self( );
		}
		return self::$_instance;
	}	
	
	public function next_variation_group() 
	{
		self::$current_variation_group++;
		self::$variation_group = self::$variation_groups[self::$current_variation_group];			
		return self::$variation_group;
	}


	public function the_variation_group() 
	{
		self::$variation_group = $this->next_variation_group();		
		$this->get_variations();
	}

	public function have_variation_groups() 
	{
		if (self::$current_variation_group + 1 < self::$variation_group_count)			
			return true;		
		else if (self::$current_variation_group + 1 == self::$variation_group_count && self::$variation_group_count > 0)
			$this->rewind_variation_groups();	
		
		return false;
	}

	public function rewind_variation_groups() 
	{
		self::$current_variation_group = -1;
		if (self::$variation_group_count > 0) {
			self::$variation_group = self::$variation_groups[0];
		}
	}

	public function get_variations() 
	{		
		self::$variations = isset(self::$all_associated_variations[self::$variation_group->term_id])?self::$all_associated_variations[self::$variation_group->term_id]:[];
		self::$variation_count = count(self::$variations);		
	}

	public function next_variation() 
	{
		self::$current_variation++;
		self::$variation = self::$variations[self::$current_variation];				
		return self::$variation;
	}

	public function have_variations() 
	{  		
		if (self::$current_variation + 1 < self::$variation_count) 
		{		
			return true;
		} 
		else if (self::$current_variation + 1 == self::$variation_count && self::$variation_count > 0) 
		{
			$this->rewind_variations();
		}		
		return false;
	}

	public function rewind_variations() 
	{
		self::$current_variation = -1;
		if (self::$variation_count > 0) {
			self::$variation = self::$variations[0];
		}
	}
	
	public function get_all_associated_variations() 
	{
		return self::$all_associated_variations;
	}
	
	public function get_variation_group() 
	{
		return self::$variation_group;
	}
	
	public function get_variation() 
	{
		return self::$variation;
	}
	
	public function get_variation_groups()
	{
		return self::$variation_groups;		
	}
	
	public function get_product_id()
	{
		return self::$product_id;		
	}
}

/**
 * HTML отключить выбор опций и переключателей если нет запаса на складе
 */
function usam_the_variation_out_of_stock()
{
	$stock = false;

	$variations = new USAM_Variations( );
	$variation = $variations->get_variation();
	if ( !empty($variation->slug) )
	{
		$product_id = get_the_ID();
		$query = new WP_Query(['variations' => $variation->slug, 'post_status' => 'publish', 'post_type' => 'usam-product', 'post_parent' => $product_id]);
		if ( empty($query->posts[0]) ) 
		{	// Никогда не должно происходить
			return FALSE;			
		}
		if ( usam_product_has_stock( $query->posts[0]->ID ) ) 
			$stock = true;
	}
	return $stock;
}

/**
 * Существует ли еще вариация, если сущетвует, то получить её
 */
function usam_have_variation_groups() 
{	
	$variations = new USAM_Variations( );
	return $variations->have_variation_groups( );
}

/**
 * получить следующую вариацию
 */
function usam_the_variation_group() 
{
	$variations = new USAM_Variations( );
	$variations->the_variation_group( );
}

/**
 * Узнать существует ли следущий вариант вариации
 */
function usam_have_variations() 
{
	$variations = new USAM_Variations( );
	return $variations->have_variations( );
}

/**
 * получить следующий вариант вариации
 */
function usam_the_variation() 
{ 
	$variations = new USAM_Variations( );
	$variations->next_variation( );
}

/**
 * Получить группу вариации
 */
function usam_get_variation_groupp() 
{
	$variations = new USAM_Variations( );
	$variation_group = $variations->get_variation_group( );
	return $variation_group;
}

/**
 * Получить название вариации
 */
function usam_the_vargrp_name() 
{
	$variations = new USAM_Variations( );
	$variation_group = $variations->get_variation_group( );
	return $variation_group->name;
}

/**
 * Получить ID формы вариации
 */
function usam_vargrp_form_id() 
{
	$variations = new USAM_Variations( );
	$variation_group = $variations->get_variation_group( );
	
	$product_id = get_the_ID();
	$props_id = "variation_select_{$product_id}_{$variation_group->term_id}";
	return $props_id;
}

/**
 * Получить ID вариации
 */
function usam_vargrp_id() 
{
	$variations = new USAM_Variations( );
	$variation_group = $variations->get_variation_group( );
	return isset($variation_group->term_id)?$variation_group->term_id:0;
}

/**
 * получить название варианта вариации
 */
function usam_the_variation_name() 
{
	$variations = new USAM_Variations( );
	$variation = $variations->get_variation( );
	return isset($variation->name) ? esc_html($variation->name) : '';
}

/**
 * получить ID варианта вариации
 */
function usam_the_variation_id()
{
	$variations = new USAM_Variations( );
	$variation = $variations->get_variation( );	
	return isset($variation->term_id)?$variation->term_id:0;
}

function usam_get_product_id_group_variations()
{
	$variations = new USAM_Variations( );
	return $variations->get_product_id( );	
}

/**
 *  Получить id вариации продукта по id термена и id продукта
 */
function usam_get_child_object_in_terms_var($parent_id, $term_ids, $taxonomies, $args = array() ) 
{
	global $wpdb;
	$wpdb->show_errors = true;
	$parent_id = absint($parent_id);

	if ( !is_array( $term_ids) )
		$term_ids = array($term_ids);

	if ( !is_array($taxonomies) )
		$taxonomies = array($taxonomies);

	foreach ( (array) $taxonomies as $taxonomy ) 
	{
		if ( ! taxonomy_exists($taxonomy) )
			return new WP_Error('invalid_taxonomy', __('Недопустимая таксономия', 'usam'));		
	}
	$defaults = array('order' => 'ASC');
	$args = wp_parse_args( $args, $defaults );
	extract($args, EXTR_SKIP);
	$order = ( 'desc' == strtolower($order) ) ? 'DESC' : 'ASC';
	$term_ids = array_map('intval', $term_ids);
	$taxonomies = "'" . implode("', '", $taxonomies) . "'";
	
	$object_sql = "SELECT posts.ID FROM {$wpdb->posts} AS posts";	
	foreach ( $term_ids as $key => $term_id ) 
	{
		$object_sql .= " INNER JOIN {$wpdb->term_relationships} AS tr$key ON posts.ID = tr$key.object_id INNER JOIN {$wpdb->term_taxonomy} AS tt$key ON (tr$key.term_taxonomy_id = tt$key.term_taxonomy_id AND tt$key.taxonomy IN ({$taxonomies}) AND tt$key.term_id=$term_id AND tt$key.parent > 0)";
	}
	$object_sql .= " WHERE posts.post_parent = {$parent_id}";
	
	$product_id = $wpdb->get_var( $object_sql );
	return $product_id;
}

function usam_get_id_product_variation($product_id, $terms ) 
{
	$variant_product_id = usam_get_child_object_in_terms_var( $product_id, $terms, 'usam-variation' );
	$term_ids = wp_get_object_terms( $variant_product_id, 'usam-variation', ['fields' => 'ids']);
	if ( array_diff($term_ids, $terms) )
		return 0;
	return $variant_product_id;
}
?>