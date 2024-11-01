<?php
/**
 * Общие функции для работы с таксоманиями
 */
 

//Получает ID родительских элементов указанного объекта
function usam_get_ancestors( $term_id, $taxonomy = 'usam-category' )
{
	$results = [];
	$category_children = get_option($taxonomy.'_children', []);
	foreach( $category_children as $id => $term_ids )
	{
		if( in_array($term_id, $term_ids) )
		{
			$results[] = $id;
			$ids = usam_get_ancestors( $id, $taxonomy );		
			$results = array_merge( $results, $ids );	
			break;
		}
	}
	return $results;
}

/**
* Получает изображение термина или возвращает ложь
*/
//Вывести основную картинку термина
function usam_term_image( $term_id, $size = 'full', $args = [], $lzy = true ) 
{
	if( empty($term_id) )
		return false;	
	
	if( is_string($args) )
		$args = ['alt' => $args];
	
	$default = ['id' => "thumb_term_{$term_id}", "class" => ""];
	$args = array_merge( $default, $args );
	
	$attachment_id = (int)get_term_meta($term_id, 'thumbnail', true);
	if( $lzy )
		$args["loading"] = "lazy";	
	$t = '';
	foreach( $args as $k => $v )
		if( $v )
			$t .= "$k='$v' ";		
	
	echo "<img src='".usam_get_term_image_url( $term_id, $size )."' $t>";
}

//Получить картинки термина
function usam_term_images( $term_id, $size = 'full', $type_image = 'images' ) 
{
	if( empty($term_id) )
		return [];	
	
	$urls = [];
	$images = get_term_meta($term_id, $type_image, true);
	if( $images )
	{
		$attachments = (array)usam_get_posts(['post_type' => 'attachment', 'post__in' => $images, 'update_post_meta_cache' => true, 'orderby' => 'post__in', 'order' => 'ASC']);			
		if ( !empty($attachments) ) 
		{ 
			foreach( $attachments as $attachment ) 
			{
				$attachment->url = wp_get_attachment_image_url( $attachment->ID, $size );
				$urls[] = $attachment;
			}	
		}
	}
	return $urls;
}

//Получить ссылку на картинку термина
function usam_get_term_image_url( $term_id, $size = 'full', $type_image = 'thumbnail' ) 
{
	if( empty($term_id) )
		return false;
		
	$attachment_id = (int)get_term_meta($term_id, $type_image, true);

	if ( empty($attachment_id)  )
		return usam_get_no_image_uploaded_file( $size );

	$image_attributes = wp_get_attachment_image_src( $attachment_id, $size );
	return !empty($image_attributes[0])?$image_attributes[0]:'';
}

//Получить ссылку на картинку категории по номеру товара
function usam_get_category_image_url_by_product_id( $product_id, $size = 'full', $type_image = 'thumbnail' ) 
{	
	$terms = get_the_terms( $product_id , 'usam-category' );
	if( !empty($terms) )				
		return usam_get_term_image_url( $terms[0]->term_id, $size, $type_image );
	return '';
}

function usam_default_product_attribute( $args ) 
{		
	return array_merge(['field_type' => 'T', 'mandatory' => 0, 'filter' => 0, 'search' => 0, 'sorting_products' => 0, 'compare_products' => 0, 'important' => 0, 'do_not_show_in_features' => 0, 'switch_to_selection' => 0], $args);
}
add_filter('usam_insert_term_usam-product_attributes', 'usam_default_product_attribute');
	

function usam_new_term( $args, $taxonomy, $just_add = false ) 
{		
	$insert = false;
	$term_id = 0;
	$parent_id = 0;
	$external_code = '';
	if ( !empty($args['term_id']) )
	{
		$term_id = (int)$args['term_id'];
		if ( !empty($args['external_code']) )
			$external_code = $args['external_code'];		
	}
	elseif ( !empty($args['external_code']) )
	{
		$term_id = usam_term_id_by_meta('external_code', $args['external_code'], $taxonomy);
		if ( !$term_id )
			$external_code = $args['external_code'];
	}
	$term_args = [];
	if ( !empty($args['slug']) )
		$term_args['slug'] = str_replace('-', '_', trim($args['slug']));	
	if ( $taxonomy != 'usam-brands' )
	{
		if ( !empty($args['parent_id']) )
			$parent_id = $args['parent_id'];			
		elseif ( !empty($args['parent_slug']) )
		{
			$parent_term = get_term_by( 'slug', $args['parent_slug'], 'usam-product_attributes' );
			if ( !empty($parent_term->term_id) )
				$parent_id = $parent_term->term_id;
		}
		elseif ( !empty($args['parent_external_code']) )
		{
			$parent_id = usam_term_id_by_meta('external_code', $args['parent_external_code'], $taxonomy);			
			unset($args['parent_external_code']);
		}	
	}		
	if ( $parent_id )
		$term_args['parent'] = $parent_id;	
	
	if ( !empty($args['description']) )
		$term_args['description'] = trim($args['description']);	
	
	if ( $term_id )
	{
		if ( !$just_add )
		{
			if ( !empty($args['name']) )
				$term_args['name'] = trim($args['name']);				
			$term = wp_update_term( $term_id, $taxonomy, $term_args);	

			if ( isset($args['sort']) )
				usam_update_term_metadata($term_id, 'sort', $args['sort']); 	
			if ( isset($args['status']) )
				usam_update_term_metadata($term_id, 'status', $args['status']); 			
		}
	}
	elseif ( !empty($args['name']) )
	{				
		$term = term_exists( $args['name'], $taxonomy, $parent_id );
		if( $term )
			return ['term_id' => $term['term_id'], 'insert' => false, 'args' => $args];
		else
		{		
			$new = [];
			if ( isset($args['sort']) )
				$new['sort'] = $args['sort'];
			if ( isset($args['status']) )
				$new['status'] = $args['status'];	
			if ( $new )
				add_filter( 'usam_default_term_data', function($a) use ($new ) { return $new; }, 10, 1 );	
		
			$name = trim($args['name']); 	
			if ( empty($term_args['slug']) )
				$term_args['slug'] = str_replace('-', '_', sanitize_title( $name ));
			$term = wp_insert_term( $name, $taxonomy, $term_args);
			if ( is_wp_error($term) || !isset($term['term_id']) )
				return ['term_id' => 0, 'insert' => false, 'args' => $args];	
			else
			{
				$insert = true;
				$args = apply_filters( 'usam_insert_term_'.$taxonomy, $args );	
			}
			$term_id = (int)$term['term_id'];
			if ( $new )
				remove_all_filters('usam_default_term_data');
		}
	}		
	if ( $external_code )
		usam_update_term_metadata($term_id, 'external_code', $external_code);		
	return ['term_id' => $term_id, 'insert' => $insert, 'args' => $args];	
}

function usam_update_terms_thumbnail_cache( $terms )
{ 
	if ( empty($terms) )
		return '';
	
	$thumb_ids = array();
	foreach( $terms as $term )
	{
		$attachment_id = (int)get_term_meta($term->term_id, 'thumbnail', true);
		if ( $attachment_id )
			$thumb_ids[] = $attachment_id;
	} 
	if ( !empty($thumb_ids) ) 
		_prime_post_caches( $thumb_ids, false, true );
}

function usam_taxonomy_thumbnail( $term_id, $size = 'full', $title = '' ) 
{ 
	$attachment_id = (int)get_term_meta($term_id, 'thumbnail', true);
	
	if ( $attachment_id == 0  )
		return false;
	
	$image_attributes = wp_get_attachment_image_src( $attachment_id, $size );	
	
	if ( empty($image_attributes) )
		return false;
	
	$srcset = wp_get_attachment_image_srcset( $attachment_id, 'large' );
	$sizes = wp_get_attachment_image_sizes( $attachment_id, 'large' );
	
	$src = "<img src='".$image_attributes[0]."' width='".$image_attributes[1]."' height='".$image_attributes[2]."' alt='$title' srcset='$srcset' sizes='$sizes'>";		
	return $src;
}

function usam_terms_id_by_meta($key, $values, $taxonomy) 
{
	global $wpdb;
	
	$terms = array();
	$results = array();
	foreach( $values as $value )
	{
		$cache_key = "usam_term_$key-$value";
		$term_id = wp_cache_get( $cache_key );
		if ($term_id === false) 
			$terms[] = $value;
		else
			$results[$value] = $term_id;
	}
	if ( !empty($terms) )
	{
		$terms = $wpdb->get_results($wpdb->prepare("SELECT tm.term_id, tm.meta_value FROM ".USAM_TABLE_TERM_META." tm JOIN $wpdb->term_taxonomy t ON (tm.term_id = t.term_id) WHERE taxonomy='%s' AND meta_key = %s AND meta_value IN ('".implode("','", $terms)."')", $taxonomy, $key));
		foreach( $terms as $term )
		{
			$cache_key = "usam_term_$key-".$term->meta_value;
			wp_cache_set($cache_key, $term->term_id);
			$results[$term->meta_value] = $term->term_id;
		}		
	}
	return $results;
}

function usam_term_id_by_meta($key, $value, $taxonomy) 
{
	global $wpdb;

	if ($value === null) 
		return 0;
	
	$cache_key = "usam_term_$key-$value";
	$term_id = wp_cache_get( $cache_key );
	if ($term_id === false) 
	{	
		$term_id = (int)$wpdb->get_var($wpdb->prepare("SELECT tm.term_id FROM ".USAM_TABLE_TERM_META." tm JOIN $wpdb->term_taxonomy t ON tm.term_id = t.term_id WHERE taxonomy='%s' AND meta_key=%s AND meta_value=%s", $taxonomy, $key, $value));	
		wp_cache_set($cache_key, $term_id);
	}
	return $term_id;
}


// Описание название термина
function usam_product_taxonomy_name() 
{
	if ( is_tax() && get_option( 'usam_display_category_name', 0 ) )
	{
		$term = get_queried_object();
		if ( !empty($term->name) && $term->taxonomy == 'usam-brands' )
		{			
			?><h2 class ="product_term_title"><?php echo $term->name; ?></h2><?php
		}
	}
}

// Описание вывести термина
function usam_product_taxonomy_description() 
{
	if ( is_tax() && get_option( 'usam_category_description', 0 ) )
	{
		$term = get_queried_object();		
		if ( !empty($term->description) )
		{
			?><div class='product_term_description'><?php echo wpautop($term->description); ?></div><?php
		}
	}
}

function usam_get_walker_terms_list( $args_terms, $args_list ) 
{	
	$default = [								
		'taxonomy'      => 'usam-category',		
		'select'        => [],					
		'checked_ontop' => false, 
		'echo'          => false,
		'before' => '', 
		'after' => '',				
		'link_before' => '', 
		'link_after' => '',	
		'show_active' => 1,		
		'take_menu' => 0,	
		'split' => 0,			
		'class_ul' => '',
		'active_term' => ''			
	];		
	$args_list = array_merge($default, (array)$args_list);	
	$args_terms['taxonomy'] = $args_list['taxonomy'];		
	if ( $args_list['active_term'] )
	{			
		$term_data = get_term_by( 'slug', $args_list['active_term'] , 'usam-category' );		
		if ( $term_data )
		{			
			$args_terms['child_of'] = isset($args_terms['child_of'])?$args_terms['child_of']:get_option( 'usam_default_menu_category' ); 	
			$ancestor_ids = (array)usam_get_ancestors( $term_data->term_id, 'usam-category' );
			array_unshift( $ancestor_ids, $term_data->term_id );
			
			if ( !empty($args_list['take_menu']) )
			{
				$args = [
					'post_type'   => 'nav_menu_item',
					'post_status' => 'publish',
					'post_parent' => 0,
					'nopaging'    => true,
					'numberposts' => 1,
					'meta_query' => array(
						['key' => '_menu_item_object_id', 'value' => $ancestor_ids, 'compare' => 'IN',	'type' => 'numeric'],
						['key' => '_menu_item_object',	'value' => 'usam-category',	'compare' => '=']
					), 
					'cache_results' => false, 
					'update_post_meta_cache' => true,
					'update_post_term_cache' => false
				];			
				$posts = get_posts( $args );
				if ( !empty($posts) )
					$args_terms['child_of'] = get_post_meta( $posts[0]->ID , '_menu_item_object_id', true );
			}
			unset($args_list['take_menu']);
			$active_category = $term_data->term_id;	
			$args_list['select'] = [ $active_category ];	
			unset($args_list['show_active']);
		}
	}	
	require_once( USAM_FILE_PATH . '/includes/taxonomy/class-walker-product_category-list.php' );		
	$terms = usam_get_terms( $args_terms );	

	$walker = new Walker_Product_Category_List();
	$html = '<ul class="main_categories '.$args_list['class_ul'].'">'.call_user_func_array([$walker, 'walk'], [$terms, 0, $args_list]).'</ul>';
	return $html;
}

function usam_set_taxonomy_relationships( $term_id1, $term_id2, $taxonomy ) 
{
	global $wpdb;
	$term_id1 = (int)$term_id1;
	$term_id2 = (int)$term_id2;
	
	$sql = "INSERT INTO `".usam_get_table_db('taxonomy_relationships')."` (`term_id1`,`term_id2`,`taxonomy`) VALUES ('%d','%d','%s') ON DUPLICATE KEY UPDATE `term_id2`='%d'";					
	$insert = $wpdb->query( $wpdb->prepare($sql, $term_id1, $term_id2, $taxonomy, $term_id2 ) );	
	return $insert;
}

function usam_get_taxonomy_relationships_by_id( $term_id, $taxonomy = 'usam-category', $colum = 2 ) 
{
	global $wpdb;
	
	if ( is_array($term_id) )
		$in = implode("','",$term_id);
	else			
		$in = "$term_id";
	
	if ( $colum == 2 )
	{
		$colum = 'term_id2';
		$fled = 'term_id1';
	}
	else
	{
		$colum = 'term_id1';
		$fled = 'term_id2';
	}	
	if ( !taxonomy_exists($taxonomy) )
		return array();	
	
	$result = $wpdb->get_col( "SELECT DISTINCT $fled FROM ".usam_get_table_db('taxonomy_relationships')." WHERE $colum IN ('$in') AND taxonomy = '$taxonomy'" );
	return $result;
}

function usam_get_taxonomy_relationships( $taxonomy ) 
{
	global $wpdb;	
	
	$results = wp_cache_get( 'usam_taxonomy_relationships_'.$taxonomy );
	if ( $results === false )
	{
		$results = $wpdb->get_results( "SELECT term_id1, term_id2 FROM ".usam_get_table_db('taxonomy_relationships')." WHERE taxonomy = '".$taxonomy."'" );
		wp_cache_set( 'usam_taxonomy_relationships_'.$taxonomy, $results );				
	}		
	return $results;
}


function usam_delete_taxonomy_relationships( $delete ) 
{
	global $wpdb;	
				
	$result = $wpdb->delete( usam_get_table_db('taxonomy_relationships'), $delete );
	return $result;
}

//Получить id терминов товара, указанного в product_id
function usam_get_product_term_ids( $product_id, $term_name = 'usam-category' ) 
{
	$product = get_post( $product_id );		
	if ( empty($product) )
		return [];	
	
	if ( $product->post_parent )							
		$terms = get_the_terms( $product->post_parent, $term_name );
	else
		$terms = get_the_terms( $product_id, $term_name );	
	if ( is_wp_error( $terms ) )
		return [];			
	
	$ids = [];
	if ( !empty( $terms ))
	{ 		
		foreach( $terms as $term )
		{			
			$ids[] = $term->term_id;
			$ancestors = usam_get_ancestors( $term->term_id, $term_name );
			$ids = array_merge( $ids, $ancestors );			
		}	
	}
	return $ids;
}

new USAM_Taxonomy_Filter();
class USAM_Taxonomy_Filter
{
	private static $taxonomies = ["category", "usam-brands", "usam-category", "usam-category_sale", 'usam-variation', 'usam-product_attributes', 'usam-catalog', 'usam-selection', 'usam-gallery'];
	function __construct( ) 
	{		
		foreach ( self::$taxonomies as $taxonomy ) 
		{
			add_action( "created_$taxonomy", [__CLASS__, 'created_term'], 10, 2 ); 		
		}		
		add_action( "pre_delete_term",[__CLASS__, 'delete_term'], 10, 2 ); 
		add_action( 'delete_usam-product_attributes', [__CLASS__, 'delete_product_attribute'], 10, 4 ); 		
		add_action( 'terms_clauses', [__CLASS__, 'terms_clauses'], 10, 3 ); 	
		add_filter( 'wp_update_term_data', [__CLASS__, 'update_term_data'], 10, 4 ); 
	//	add_filter( 'wp_insert_term_data', [__CLASS__, 'insert_term_data'], 10, 3 ); 	
		add_filter( 'get_terms', [__CLASS__, 'get_terms'], 10, 4 );		
		add_action( 'init', [__CLASS__, 'custom_category_descriptions_allow_html'] );
		add_action( "usam_updated_term_meta", [__CLASS__, 'updated_term_meta'], 10, 3 );		
		add_filter('term_link', [__CLASS__, 'term_link'], 10, 3 );
		add_filter('pre_wp_nav_menu', [__CLASS__, 'pre_wp_nav_menu'], 10, 2 );	
		add_filter('wp_nav_menu', [__CLASS__, 'wp_nav_menu'], 10, 2 );	
		add_action( 'created_usam-product_attributes', [__CLASS__, 'created_product_attribute'], 10, 3 );	
		
	//	remove_action( 'transition_post_status', '_update_term_count_on_transition_post_status', 10, 3 );		
	//	add_action( 'transition_post_status', [__CLASS__, 'update_term_count_on_transition_post_status'], 10, 3 );	
	}
	
	public static function update_term_count_on_transition_post_status( $new_status, $old_status, $post ) 
	{
		if( $new_status !== $old_status )
			usam_update_term_count( $post );
	}
	
	public static function pre_wp_nav_menu( $nav_menu, $args ) 
	{
		remove_filter( 'term_link', ['USAM_Taxonomy_Filter', 'term_link'], 10 );
		return $nav_menu;
	}	
	
	public static function wp_nav_menu( $nav_menu, $args ) 
	{
		add_filter('term_link', ['USAM_Taxonomy_Filter', 'term_link'], 10, 3 );
		return $nav_menu;
	}
	
	public static function term_link( $termlink, $term, $taxonomy ) 
	{
		if ( $term->taxonomy == 'usam-category' )
		{
			global $wp_query;			
			$permalinks  = get_option( 'usam_permalinks' );
			foreach (['category_sale' => 'category_sale', 'selection' => 'selection', 'brand' => 'brands'] as $k => $tax ) 
			{		
				if ( isset($wp_query->query['usam-'.$tax]) && $taxonomy != 'usam-'.$tax )
				{ 
					if ( !empty($wp_query->query['usam-'.$tax]) )
					{
						$base_url = empty($permalinks[$k.'_base']) ? $k : $permalinks[$k.'_base'];
						$termlink = home_url().'/'.$base_url.'/'.$wp_query->query['usam-'.$tax].'/'.$term->slug;
					}
					else
						$termlink = $term_link;
				}	
			}
		}
		return $termlink;
	}
	
	public static function updated_term_meta( $object_id, $meta_key, $meta_value ) 
	{
		global $wpdb;
		if ( $meta_key == 'status' )
		{
			$term = get_term( $object_id );
			if ( !empty($term->taxonomy) )
			{
				$exclude_ids = get_term_children($object_id, $term->taxonomy);
				if ( $exclude_ids )
					$wpdb->query("UPDATE ".USAM_TABLE_TERM_META." SET meta_value='$meta_value' WHERE `meta_key`='status' AND `meta_value`!='$meta_value' AND term_id IN (".implode(',', $exclude_ids).")");
			}
		}
	}
		
	public static function custom_category_descriptions_allow_html() 
	{
		remove_filter( 'term_description', 'wp_kses_data' );
		remove_filter( 'pre_term_description', 'wp_filter_kses' );
		add_filter( 'term_description', 'wp_kses_post' );
		add_filter( 'pre_term_description', 'wp_filter_post_kses' );
	}
	
	public static function insert_term_data( $data, $taxonomy, $args )
	{
		if ( $taxonomy == 'usam-product_attributes' )
			$term['slug'] = str_replace('-', '_', $data['slug'] );
		return $data;
	}
		
	public static function update_term_data( $data, $term_id, $taxonomy, $args )
	{
		if ( $taxonomy == 'usam-product_attributes' && isset($data['slug']) )
		{
			$data['slug'] = str_replace('-', '_', $data['slug']);
			$term = get_term_by('id', $term_id, 'usam-product_attributes');	
			if( isset($term->slug) && $data['slug'] != $term->slug )
			{
				global $wpdb;		
				$wpdb->update( usam_get_table_db('product_attribute'), ['meta_key' => $data['slug']], ['meta_key' => $term->slug]);
			
				$rules = usam_get_crosssell_conditions();
				if ( !empty($rules) )
				{
					$save = false;
					foreach( $rules as $key => $rule )
					{
						if (isset($rule['conditions']))
						{
							foreach( $rule['conditions'] as $k => $condition )
							{
								if ($condition['type'] == 'attr' && $condition['value'] == $term->slug)
								{							
									$rules[$key]['conditions'][$k]['value'] = $data['slug'];
									$save = true;
								}
							}
						}				
					}
					if ( $save )
						update_site_option('usam_crosssell_conditions',  maybe_serialize($rules) );
				}
			}
		}	
		return $data;
	}
	
	public static function get_primary_table( )
	{
		if ( usam_is_multisite() && !is_main_site() )
			return 'multi';
		else
			return 't';
	}
	
	public static function terms_clauses( $clauses, $taxonomies, $args )
	{
		global $wpdb;
		$array = array_intersect(self::$taxonomies, $taxonomies);
		if ( !empty($array) )
		{	
			if ( !empty($args['in_stock']) )
			{				
			/*	$code = usam_get_customer_balance_code();		
				if ( stripos($clauses['join'], $wpdb->term_relationships.' AS tr') === false )
					$clauses['join'] .= " LEFT JOIN ".$wpdb->term_relationships." AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id";
				$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_STOCK_BALANCES." AS stock ON (tr.object_id = stock.product_id)";
				$clauses['where'] .= " AND stock.meta_key='$code'";
				if ( $args['in_stock'] )
				{// Исключить из показа								
					$clauses['where'] .= " AND stock.meta_value>0";
				}	*/
			}
		}
		$primary_table = USAM_Taxonomy_Filter::get_primary_table();
		if ( !empty($args['orderby']) )
		{ 		
			if ( !is_array($args['orderby']) )
			{
				$sorts = array($args['orderby'] => $args['order']);
			}
			else
				$sorts = $args['orderby'];			
			
			$sort = [];
			foreach ( $sorts as $orderby => $order )
			{
				switch ( $orderby ) 
				{
					case 'sort' :			
						if ( stripos($clauses['where'], 'sort.meta_key') === false )
						{
							if ( usam_is_multisite() && !is_main_site() )
								$clauses['join'] .= " LEFT JOIN ".usam_get_table_db('linking_terms_multisite')." AS multi ON (t.term_id = multi.multisite_term_id)";							
							$clauses['join'] .= " LEFT JOIN ".USAM_TABLE_TERM_META." AS sort ON ({$primary_table}.term_id = sort.term_id AND sort.meta_key='".$orderby."')";						
						}
						$sort[] = "CAST(sort.meta_value AS SIGNED) ".$order;
					break;									
				}		
			}
			if ( !empty($sort) )
			{
				$clauses['orderby'] = 'ORDER BY '.implode(", ",$sort);	
				$clauses['order'] = '';
			}
		}		
		if ( !empty($args['seller_id']) )
		{ 
			if ( usam_is_multisite() && !is_main_site() && stripos($clauses['where'], 'multi.multisite_term_id') === false)
				$clauses['join'] .= " LEFT JOIN ".usam_get_table_db('linking_terms_multisite')." AS multi ON (t.term_id = multi.multisite_term_id)";		
			$clauses['join'] .= " INNER JOIN $wpdb->term_taxonomy ON (t.term_id = $wpdb->term_taxonomy.term_id)";
			$clauses['join'] .= " INNER JOIN $wpdb->term_relationships ON ($wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id) ";
			$clauses['join'] .= " INNER JOIN ".USAM_TABLE_PRODUCT_META." ON ($wpdb->term_relationships.object_id = ".USAM_TABLE_PRODUCT_META.".product_id AND meta_key='seller_id' AND meta_value='".$args['seller_id']."')";
			$clauses['distinct'] = 'DISTINCT';
		}		
		if ( !empty($args['status']) && $args['status'] !== 'all' )
		{ 
			if ( usam_is_multisite() && !is_main_site() && stripos($clauses['where'], 'multi.multisite_term_id') === false)
				$clauses['join'] .= " LEFT JOIN ".usam_get_table_db('linking_terms_multisite')." AS multi ON (t.term_id = multi.multisite_term_id)";
			$status = implode( "','", (array)$args['status']);
			$clauses['join'] .= " INNER JOIN ".USAM_TABLE_TERM_META." AS status ON ({$primary_table}.term_id = status.term_id AND status.meta_key='status' AND status.meta_value IN ('".$status."'))";
		}
		if ( isset($args['relationships']) )
		{ 			
			if ( empty($args['relationships']) )
				$args['relationships'] = [0];
			if ( usam_is_multisite() && !is_main_site() && stripos($clauses['where'], 'multi.multisite_term_id') === false)
				$clauses['join'] .= " LEFT JOIN ".usam_get_table_db('linking_terms_multisite')." AS multi ON (t.term_id = multi.multisite_term_id)";		
			$clauses['join'] .= " INNER JOIN ".usam_get_table_db('taxonomy_relationships')." AS relationships ON ({$primary_table}.term_id = relationships.term_id2 AND relationships.term_id1 IN (".implode(",", $args['relationships'])."))";
			$clauses['distinct'] = 'DISTINCT';
		}
		if ( isset($args['connection']) )
		{ 				
			if ( usam_is_multisite() && !is_main_site() && stripos($clauses['where'], 'multi.multisite_term_id') === false)
				$clauses['join'] .= " LEFT JOIN ".usam_get_table_db('linking_terms_multisite')." AS multi ON (t.term_id = multi.multisite_term_id)";	
			if (  stripos($clauses['join'], "$wpdb->term_taxonomy AS tt ") === false)
				$clauses['join'] .= " INNER JOIN $wpdb->term_taxonomy AS tt ON (t.term_id = tt.term_id)";
			$clauses['join'] .= " INNER JOIN $wpdb->term_relationships AS tr_connection ON (tt.term_taxonomy_id=tr_connection.term_taxonomy_id)";		
			$clauses['join'] .= " INNER JOIN $wpdb->posts AS posts ON (tr_connection.object_id=posts.ID AND posts.post_status='publish')";				
			$i = 0;
			foreach ( $args['connection'] as $taxonomy => $term_ids )
			{
				$i++;
				if( $taxonomy == 'usam-category' )
				{					
					$ids = [];
					foreach ( $term_ids as $id )
					{
						$ids = array_merge( $ids, get_term_children( $id, $taxonomy ) );						
						$ids[] = $id;
					}
					$term_ids = $ids;
				}
				$clauses['join'] .= " INNER JOIN $wpdb->term_relationships AS tr_connection$i ON (tr_connection.object_id = tr_connection$i.object_id)";
				$clauses['join'] .= " INNER JOIN $wpdb->term_taxonomy AS tt_connection$i ON (tt_connection$i.term_taxonomy_id = tr_connection$i.term_taxonomy_id AND tt_connection$i.term_id IN (".implode(",", $term_ids)."))";
			}			
			$clauses['distinct'] = 'DISTINCT';
		}
		if ( !empty($args['usam_meta_query']) )
		{
			if ( usam_is_multisite() && !is_main_site() && stripos($clauses['where'], 'multi.multisite_term_id') === false)
				$clauses['join'] .= " LEFT JOIN ".usam_get_table_db('linking_terms_multisite')." AS multi ON (t.term_id = multi.multisite_term_id)";	
			require_once( USAM_FILE_PATH . '/includes/query/meta_query.class.php' );
			$meta_query = new USAM_Meta_Query( $args['usam_meta_query'] );	
			if ( !empty($meta_query->queries) ) 
			{
				$c = $meta_query->get_sql( 'term', USAM_TABLE_TERM_META, $primary_table, 'term_id' );
				$clauses['join'] .= ' '.$c['join'];
				$clauses['where'] .= ' '.$c['where']; 
				$clauses['distinct'] = 'DISTINCT'; // необходим для catalog				
			}		
		}
		return $clauses;
	}
	
	public static function get_terms( $terms, $taxonomy, $query_vars, $term_query )
	{ 		
		if ( isset($query_vars['term_meta_cache']) || !empty($query_vars['images_cache']) || isset($query_vars['relationships_cache']) )
		{
			$ids = array();
			if( isset($query_vars['fields']) && $query_vars['fields']==='id=>name' )
				$ids = array_keys($terms);
			else
			{					
				foreach( $terms as $term ) 
				{
					if( isset($term->term_id) )
						$ids[] = $term->term_id;
					elseif( is_numeric($term) )
						$ids[] = $term;
				}
			}	
			if ( $ids )
			{			
				if ( !empty($query_vars['term_meta_cache']) )
					usam_update_cache( $ids, [USAM_TABLE_TERM_META => 'term_meta'], 'term_id' );
				if ( !empty($query_vars['images_cache']) )
				{					
					$attachments = (array)usam_get_posts(['post_type' => 'attachment', 'orderby' => 'menu_order', 'order' => 'ASC', 'update_post_meta_cache' => true, 'tax_query' => [['taxonomy' => 'usam-gallery', 'field' => 'id', 'terms' => $ids, 'operator' => 'IN']]]);
					foreach ( $attachments as $k => $attachment ) 
					{
						unset($attachments[$k]->guid);
						unset($attachments[$k]->to_ping);
						unset($attachments[$k]->pinged);
						unset($attachments[$k]->ping_status);
						unset($attachments[$k]->comment_count);
						unset($attachments[$k]->post_password);
						unset($attachments[$k]->post_content_filtered);
						$attachments[$k]->full = wp_get_attachment_image_url($attachment->ID, 'full' );	
						$attachments[$k]->thumbnail = wp_get_attachment_image_url($attachment->ID, 'thumbnail' );
					}
					$cache = array();
					foreach	( $attachments as $attachment ) 
					{
						$gallery = get_the_terms( $attachment->ID, 'gallery' );
						foreach	( $gallery as $g ) 
							$cache[$g->term_id][] = $attachment;
					}		
					foreach	( $ids as $id ) 
					{
						if ( !isset($cache[$id]) )
							$cache[$id] = array();	
						wp_cache_set( $id, $cache[$id], 'usam_gallery_images' );
					}
				}				
				if ( !empty($query_vars['relationships_cache']) )
				{
					$tax = $query_vars['relationships_cache'];
					$cache_key = 'usam_terms_relationships_'.$tax;					
					$cache_ids = [];
					foreach	( $ids as $term_id ) 
					{
						$cache = wp_cache_get( $term_id, $cache_key );
						if( $cache === false )
							$cache_ids[] = $term_id;	
					}
					if ( $cache_ids )
					{ 
						global $wpdb;
						$results = $wpdb->get_results( "SELECT DISTINCT term_id1, term_id2 FROM ".usam_get_table_db('taxonomy_relationships')." WHERE term_id1 IN (".implode(",",$cache_ids).") AND taxonomy = '$tax'" );	
						$_terms = get_terms(['taxonomy' => $tax, 'hide_empty' => 0, 'orderby' => 'name', 'relationships' => $cache_ids]);
						$relationships = [];
						foreach	( $results as $result )
							$relationships[$result->term_id1][] = $result->term_id2;						
						foreach	( $cache_ids as $cache_id )
						{							
							$cache = [];
							if ( isset($relationships[$cache_id]) )
								foreach	( $_terms as $term ) 
								{
									if ( in_array($term->term_id, $relationships[$cache_id]) )
										$cache[] = $term;
								} 
							wp_cache_set( $cache_id, $cache, $cache_key );
						}
					}
				}					
			}
		}
		return $terms;		
	}
	
	public static function delete_term( $term_id, $taxonomy )
	{
		global $wpdb;
		
		$wpdb->delete( USAM_TABLE_TERM_META, ['term_id' => $term_id]);
		
		$attachment_id = (int)get_term_meta($term_id, 'thumbnail', true);
		if ( $attachment_id )
			wp_delete_attachment($attachment_id, true);			
		if ( usam_is_multisite() )
		{			
			if ( is_main_site() )
			{ // Если главный сайт
				$sites = get_sites(['site__not_in' => [0,1]]);	
				if ( $sites )
				{
					foreach( $sites as $site )
					{						
						switch_to_blog( $site->blog_id );
						$id = $wpdb->get_var( "SELECT multisite_term_id FROM ".usam_get_table_db('linking_terms_multisite')." WHERE term_id = {$term_id}");	
						if ( $id )
						{
							wp_delete_term( $id, $taxonomy );
							$wpdb->query("DELETE FROM ".usam_get_table_db('linking_terms_multisite')." WHERE multisite_term_id='$id'");
						}
					}
					switch_to_blog( 1 );
				}
			}
			else
				$wpdb->query("DELETE FROM ".usam_get_table_db('linking_terms_multisite')." WHERE multisite_term_id='$term_id'");				
		}
	}
	
	public static function delete_product_attribute( $term_id, $tt_id, $term, $object_ids )
	{				
		global $wpdb;
		
		$product_attribute_values = usam_get_product_attribute_values( array( 'attribute_id' => $term_id ) );
		$filter_ids = array();
		foreach( $product_attribute_values as $option )	
			$filter_ids[] = $option->id;
		if ( !empty($filter_ids) )
			$wpdb->query("DELETE FROM `".usam_get_table_db('product_filters')."` WHERE filter_id IN (".implode(',',$filter_ids).")"); 		
		$wpdb->query("DELETE FROM `".usam_get_table_db('product_attribute_options')."` WHERE attribute_id='$term_id'"); 
		$wpdb->query("DELETE FROM `".usam_get_table_db('product_attribute')."` WHERE meta_key='$term->slug'"); 
	}
	
	public static function created_product_attribute( $term_id, $tt_id, $args )
	{				
		wp_cache_delete("usam_product_attributes");
		wp_cache_delete("usam_product_attributes_slug");
		wp_cache_delete("usam_product_filters");		
	}
	
	/**
	 * Сохраняет данные
	 */
	public static function created_term( $term_id, $tt_id )
	{						
		$data = apply_filters( 'usam_default_term_data', ['sort' => 999, 'status' => 'publish']);	
		if ( !empty($data) )			
		{
			foreach( $data as $key => $value )			
				usam_add_term_metadata( $term_id, $key, $value, true );				
		}	
	}	
}

function usam_get_gallery_images( $id )
{
	$cache_key = "usam_gallery_images";
	$attachments = wp_cache_get( $id, $cache_key );	
	if ($attachments === false) 
	{	
		$attachments = (array)usam_get_posts(['post_type' => 'attachment', 'orderby' => 'menu_order', 'order' => 'ASC', 'update_post_meta_cache' => true, 'tax_query' => [['taxonomy' => 'usam-gallery', 'field' => 'id', 'terms' => [$id], 'operator' => 'IN']]]);		
		foreach ( $attachments as $k => $attachment ) 
		{
			unset($attachments[$k]->guid);
			unset($attachments[$k]->to_ping);
			unset($attachments[$k]->pinged);
			unset($attachments[$k]->ping_status);
			unset($attachments[$k]->comment_count);
			unset($attachments[$k]->post_password);
			unset($attachments[$k]->post_content_filtered);
			$attachments[$k]->full = wp_get_attachment_image_url($attachment->ID, 'full' );	
			$attachments[$k]->thumbnail = wp_get_attachment_image_url($attachment->ID, 'thumbnail' );
		}	
		wp_cache_set($id, $attachments, $cache_key);
	}		
	return $attachments;
}

function usam_unique_term_name($name, $taxonomy, $parent = null) 
{
	global $wpdb;
	$name = htmlspecialchars($name);
	$sql = "SELECT * FROM $wpdb->terms NATURAL JOIN $wpdb->term_taxonomy WHERE name = %s AND taxonomy = %s AND parent = %d LIMIT 1";
	$number = 1;	
	do
	{
		$new_name = $number==1?$name:"$name ($number)";
		$number++;
		$term = $wpdb->get_row($wpdb->prepare($sql, $new_name, $taxonomy, $parent));
		usam_check_wpdb_error();
		if (!$term) 
			return $new_name;
	}
	while (true);
}

function usam_terms_checklist( $params = array() )
{
	$defaults = [
		'descendants_and_self' => 0,
		'selected_cats'        => false,
		'popular_cats'         => false,
		'walker'               => null,
		'taxonomy'             => 'usam-category',
		'checked_ontop'        => true,
		'echo'                 => true,
		'disabled'             => false,
	];
	$parsed_args = wp_parse_args( $params, $defaults );

	if ( empty( $parsed_args['walker'] ) || ! ( $parsed_args['walker'] instanceof Walker ) ) {
		$walker = new Walker_Category_Select;
	} else {
		$walker = $parsed_args['walker'];
	}
	$taxonomy             = $parsed_args['taxonomy'];
	$descendants_and_self = (int) $parsed_args['descendants_and_self'];
	$args = array( 'taxonomy' => $taxonomy );
	$args['list_only'] = !empty( $parsed_args['list_only'] );

	$args['selected_cats'] = is_array($parsed_args['selected_cats']) ? $parsed_args['selected_cats']:array();
	$args['popular_cats'] = is_array($parsed_args['popular_cats']) ? $parsed_args['popular_cats']:array();
	$args['disabled'] = is_array($parsed_args['disabled']) ? $parsed_args['disabled']:array();

	if ( $descendants_and_self ) 
	{
		$categories = (array) get_terms(['taxonomy' => $taxonomy, 'status' => 'publish', 'child_of' => $descendants_and_self, 'hierarchical' => 0, 'hide_empty' => 0, 'update_term_meta_cache' => false]);
		$self = get_term( $descendants_and_self, $taxonomy );
		array_unshift( $categories, $self );
	} 
	else
		$categories = (array) get_terms(['taxonomy' => $taxonomy, 'status' => 'publish', 'get'  => 'all', 'update_term_meta_cache' => false]);
	
	$output = '';
	if ( $parsed_args['checked_ontop'] ) 
	{		
		$checked_categories = array();
		$keys               = array_keys( $categories );
		foreach ( $keys as $k ) 
		{
			if ( in_array( $categories[ $k ]->term_id, $args['selected_cats'] ) ) {
				$checked_categories[] = $categories[ $k ];
				unset( $categories[ $k ] );
			}
		}
		$output .= $walker->walk( $checked_categories, 0, $args );
	}
	$output .= $walker->walk( $categories, 0, $args );

	if ( $parsed_args['echo'] ) {
		echo $output;
	}
	return $output;
}


function usam_get_terms( $args = [] )
{
	$default = ["taxonomy" => "usam-category", "update_term_meta_cache" => 0, 'orderby' => 'sort', 'status' => 'publish'];
	$args = array_merge( $default, $args );		
	$catalog = usam_get_active_catalog();		
	if ( $catalog )
		$args['usam_meta_query'][] = ['key' => 'catalog', 'value' => $catalog->term_id, 'compare' => '='];
	return get_terms( $args );	
}

function usam_get_related_terms( $term_id, $taxonomy = 'usam-category' )
{
	$cache_key = 'usam_terms_relationships_'.$taxonomy;
	$terms = wp_cache_get( $term_id, $cache_key );
	if( $terms === false )
	{
		$terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => 0, 'orderby' => 'name', 'status' => 'publish', 'relationships' => [$term_id]]);
		wp_cache_set( $term_id, $terms, $cache_key );
	}	
	return $terms;
}

function usam_add_term_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	if ( !usam_is_multisite() || is_main_site() )
		return usam_add_metadata('term', $object_id, $meta_key, $meta_value, USAM_TABLE_TERM_META, $prev_value, 'meta' );
	else
		return true;
}

function usam_get_term_metadata( $object_id, $meta_key = '', $single = true) 
{
	$object_id = usam_get_term_id_main_site( $object_id );
	return usam_get_metadata('term', $object_id, USAM_TABLE_TERM_META, $meta_key, $single, 'meta' );
}

function usam_update_term_metadata($object_id, $meta_key, $meta_value, $prev_value = false ) 
{
	$object_id = usam_get_term_id_main_site( $object_id );
	return usam_update_metadata('term', $object_id, $meta_key, $meta_value, USAM_TABLE_TERM_META, $prev_value, 'meta' );
}

function usam_delete_term_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) 
{ 	
	$object_id = usam_get_term_id_main_site( $object_id );
	return usam_delete_metadata('term', $object_id, $meta_key, USAM_TABLE_TERM_META, $meta_value, $delete_all, 'meta' );
}

function usam_get_term_uuid( $term_id ) 
{	
	$uuid = usam_get_term_metadata( $term_id, 'uuid' );
	if ( !$uuid )
	{
		$uuid = wp_generate_uuid4();
		usam_update_term_metadata( $term_id, 'uuid', $uuid);
	}
	return $uuid;
}

function usam_get_statuses_terms( )
{
	$statuses = ['hidden' => __('Скрыто','usam'), 'publish' => __('Опубликовано','usam')];
	return $statuses;
}

function usam_get_term_status_name( $key_status ) 
{
	$statuses = usam_get_statuses_terms();	
	if ( isset($statuses[$key_status]) )
		return $statuses[$key_status];
	else
		return '';
}
/*
function usam_update_term_count( $status )
{
	static $calculate = true, $posts = [];	
	global $wpdb;
	
	if ( $status === '' )
		return false;
	
	if ( is_object($status) )
		$posts[$status->post_type][] = $status->ID;
	else
		$calculate = $status;
	
	if ( $calculate )
	{
		foreach( $posts as $post_type => $ids )
		{
			$taxonomies = (array) get_object_taxonomies( $post_type );
			$terms = wp_get_object_terms( $ids, $taxonomies, ['fields' => 'all'] );						
			$tt = [];	 
			foreach( $terms as $term )			
				$tt[$term->taxonomy][] = $term->term_id;	  
			foreach( $tt as $taxonomy => $tt_ids)
				wp_update_term_count( $tt_ids, $taxonomy );
			unset($posts[$post_type]);
		}
	}	
	return $posts;
}*/
?>