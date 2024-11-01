<?php
/**
 * Запрос для получения выборки товаров
 */
class USAM_Products_Query
{	
	private $query_vars = [];
	private $product_id = 0;
	
	function __construct( $args = [] )
	{					
		if ( !empty($args) )
		{		
			if( !empty($args['product_id']) )
			{
				$this->product_id = (int)$args['product_id'];
				unset($args['product_id']);
			}		
			$this->query_vars = $args;
			$this->query_vars['posts_per_page'] = !empty($this->query_vars['posts_per_page']) ? $this->query_vars['posts_per_page'] : 7;
			return $this->query();
		}
    }		

// из каждой категории по 1 случайному товару	
	private function query_cat()
	{		
		global $wpdb;
		$type_price = usam_get_customer_price_code();
		$all_cats_ids = get_terms(['taxonomy' => 'usam-category', 'status' => 'publish', 'hide_empty' => true, 'number' => $this->query_vars['posts_per_page'], 'fields' => 'ids', 'orderby' => 'count', 'order' => 'DESC']);
		$product_ids = array();
		foreach ($all_cats_ids as $category_id)
		{ 					
			$product_ids[] = $wpdb->get_var("SELECT ID FROM $wpdb->posts 			
			INNER JOIN ".USAM_TABLE_PRODUCT_PRICE." AS pm ON ($wpdb->posts.ID = pm.post_id AND pm.meta_key = 'price_$type_price')
			INNER JOIN ".USAM_TABLE_STOCK_BALANCES." AS stock ON (stock.product_id = p.ID AND stock.meta_key = 'stock')
			INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id) 
			INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) 
			WHERE $wpdb->term_taxonomy.taxonomy = 'usam-category' AND $wpdb->term_taxonomy.term_id IN ('$category_id') AND post_type = 'usam-product' AND pm.meta_value>0 AND stock.meta_value>0 AND post_status = 'publish' ORDER BY views LIMIT 1");						
		}
		if ( empty($product_ids) )
			return false;	
		return ['post__in' => $product_ids];
	}	
	
	// из категории по 1 самому популярному посту	
	private function query_product_views_cat()
	{		
		global $wpdb;	
		
		$product_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts 
			INNER JOIN ".USAM_TABLE_POST_META." AS pm ON (ID = pm.post_id AND pm.meta_key = 'views')
			INNER JOIN ".USAM_TABLE_STOCK_BALANCES." AS stock ON (stock.product_id = p.ID AND stock.meta_key = 'stock')
			INNER JOIN $wpdb->term_relationships AS tr ON ($wpdb->posts.ID = tr.object_id) 
			INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'usam-category') 
			WHERE post_type = 'usam-product' AND post_status = 'publish' AND stock.meta_value>0 GROUP BY tt.term_id ORDER BY CAST(pm.meta_value AS SIGNED) DESC LIMIT {$this->query_vars['posts_per_page']}");
		
		if ( empty($product_ids) )
			return false;	
		return ['post__in' => $product_ids, 'orderby' => 'post__in'];		
	}

	// Найти случайные товары
	private function query_rand()
	{			
		return array('orderby'  => 'rand' );	
	}	
	
	// Избранные товары
	private function query_sticky()
	{					
		return ['orderby' => 'views', 'user_list' => 'sticky'];	
	}			
	
// Популярные товары по количеству просмотров
	private function query_popularity()
	{				
		return ['orderby' => 'views', 'order' => 'DESC'];
	}
	
// Новинки	
	private function query_news()
	{				
		return array('orderby' => 'ID', 'order' => 'DESC');	
	}	
//Купленные товары в месте с выбранным товаром	
	private function query_also_bought()
	{				
		global $wpdb;	
		
		if ( empty($this->product_id) )
			return false;	
			
		$order_ids = $wpdb->get_col( "SELECT order_id FROM ".USAM_TABLE_PRODUCTS_ORDER." WHERE product_id='{$this->product_id}' ORDER BY order_id DESC LIMIT 20" );		
		if ( empty($order_ids) )
			return false;
		
		$product_ids = $wpdb->get_col( "SELECT DISTINCT product_id FROM ".USAM_TABLE_PRODUCTS_ORDER." WHERE product_id!={$this->product_id} AND order_id IN (".implode(",",$order_ids).") ORDER BY price DESC LIMIT 30" );			
		
		if ( empty($product_ids) )
			return false;	
	
		return ['post__in' => array_values($product_ids), 'order' => 'DESC', 'orderby' => 'post__in'];	
	}	
	
//Последние проданные товары
	private function query_last_purchased()
	{				
		return ['purchased' => true, 'order' => 'DESC', 'orderby' => 'purchased'];		
	}	
//Лидиры продаж за месяц	
	private function query_leaders_sells_month()
	{	
		return ['leaders_sells_month' => true];		
	}
		
// История просмотра товара	
	private function query_history_views()
	{			
		$contact_id = usam_get_contact_id();
		if ( empty($contact_id) )
			return false;
		$query = ['groupby' => 'post_id', 'fields' => 'post_id', 'post-type' => 'usam-product', 'contact_id' => $contact_id, 'number' => $this->query_vars['posts_per_page'] + 4, 'order' => 'DESC'];
		if( $this->product_id )
			$query['post_id__not_in'] = $this->product_id;
		require_once( USAM_FILE_PATH . '/includes/analytics/pages_viewed_query.class.php' );
		$product_ids = usam_get_pages_viewed( $query );
		if ( empty($product_ids) )
			return false;		
	
		return ['post__in' => array_values($product_ids), 'order' => 'DESC', 'orderby' => 'post__in'];	
	}	
	
	private function query_personal_offer()
	{			
		$contact_id = usam_get_contact_id();
		if ( empty($contact_id) )
			return false;
		require_once( USAM_FILE_PATH . '/includes/analytics/pages_viewed_query.class.php' );
		$product_ids = usam_get_pages_viewed(['groupby' => 'post_id', 'post_id__not_in' => $this->product_id, 'fields' => 'post_id', 'post-type' => 'usam-product', 'contact_id' => $contact_id, 'number' => $this->query_vars['posts_per_page'] + 4, 'order' => 'DESC']);
		if ( empty($product_ids) )
			return false;	
	
		$ids = array_values($product_ids);
		$terms = wp_get_object_terms( $ids, 'usam-category', ['fields' => 'ids']);
		return ['tax_query' => [['taxonomy' => 'usam-category', 'field' => 'id', 'terms' => $terms, 'operator' => 'IN']], 'orderby' => 'views'];
	}	

	// Товары в одной коллекции
	private function query_collection()
	{	
		if ( empty($this->product_id) )
			return false;		
		
		$collection = usam_get_product_attribute( $this->product_id, 'collection' );				
		if ( empty($collection))		
			return false;	
		
		$query_vars = ['orderby' => ['meta_value_num' => 'DESC'], 'attributes_query' => [['key' => 'collection', 'value' => $collection, 'compare' => '=']]];
		if ( $this->product_id )
			$query_vars['post__not_in'] = [$this->product_id];			
		return $query_vars;
	}
	
	// Товары на скидке
	private function query_sale()
	{				
		$type_price = usam_get_customer_price_code();		
		return ['price_meta_query' => [[ 'key' => 'old_price_'.$type_price, 'value' => 0, 'compare' => '>']]];	
	}
	
	// upsell для текущего товара
	private function query_upsell()
	{	
		$type_price = usam_get_customer_price_code();		
		$categories = get_the_terms( $this->product_id, 'usam-category');
		$terms = array();
		if( $categories != '' )
		{			 
			foreach ($categories as $cat_item)
				$terms[] = $cat_item->slug;
		} 
		if ( empty($terms) )
			return false;	
		
		$price = usam_get_product_price( $this->product_id );
		
		if ( empty($price) )
			return false;	
		
		$query_vars = array (				
			'tax_query' => [['taxonomy' => 'usam-category', 'field' => 'slug','terms' => $terms]],
			'price_meta_query' => [['key' => 'price_'.$type_price, 'value' => $price, 'compare' => '>']],
			'order' => 'ASC',		
			'type_price' => $type_price,			
			'orderby'   => 'price',
		);
		if ( $this->product_id )
			$query_vars['post__not_in'] = [$this->product_id];		
		return $query_vars;
	}
	
	// в одной категории с текущим товаром
	private function query_same_category()
	{			
		$categories = get_the_terms( $this->product_id, 'usam-category');
		$terms = array();
		if( $categories != '' )
		{			 
			foreach ($categories as $cat_item)
				$terms[] = $cat_item->slug;
		}
		if ( empty($terms) )
			return false;	
		
		$query_vars = array (				
			'tax_query' => array(
				array( 'taxonomy' => 'usam-category',	'field' => 'slug','terms' => $terms	)
			),	
			'order' => 'DESC',				
			'orderby'   => 'views',
		);
		if ( $this->product_id )
			$query_vars['post__not_in'] = [$this->product_id];		
		return $query_vars;
	}	
	
	// crosssell для текущего товара
	private function query_related_products()
	{	
		if ( empty($this->product_id) )
			return false;	
	
		return ['associated_product' => [['list' => 'crosssell', 'product_id' => $this->product_id]]];	
	}
	
	//Аналоги
	private function query_similar()
	{	
		if ( empty($this->product_id) )
			return false;	
	
		return ['associated_product' => [['list' => 'similar', 'product_id' => $this->product_id]]];	
	}	
	
	//Товары дня
	private function query_day()
	{		
		$product_ids = usam_get_active_products_day_id_by_codeprice();				
		if ( empty($product_ids) )
			return false;				
		return ['post__in' => $product_ids];
	}
	
	//Товары добавленные в активную категорию скидок
	private function query_category_sale()
	{			
		$terms = usam_get_category_sale(); 
		$query_vars = [];
		if ( $terms )
		{
			$ids = [];
			foreach($terms as $term) 		
			{
				$ids[] = $term->term_id;
			}
			$query_vars['tax_query'] = [['taxonomy' => 'usam-category_sale', 'field' => 'id', 'terms' => $ids]];			
		} 
		return $query_vars;	
	}
		
	// с одинаковым тегом для текущего товара
	private function query_product_tag()
	{	
		$product_tag = wp_get_object_terms( $this->product_id, 'product_tag');		
		$terms = array();
		if( $product_tag != '' )
		{		
			foreach ($product_tag as $tag_item) {
				$terms[] = $tag_item->slug;
			}
		}
		if( empty($terms) )
			return false;	
		
		$query_vars = [		
			'tax_query' => [
				['taxonomy' => 'product_tag', 'field' => 'slug', 'terms' => $terms]
			],	
			'order'        => 'DESC',				
			'orderby'      => 'views',
		];	
		if ( $this->product_id )
			$query_vars['post__not_in'] = [$this->product_id];			
		return $query_vars;
	}	

	public function query()
	{	
		if( !empty($this->query_vars['compilation']) )
		{
			$type_query = $this->query_vars['compilation'];
			if ( is_numeric($type_query) )
			{
				require_once( USAM_FILE_PATH.'/admin/includes/admin_product_query.class.php' );
				$this->query_vars = USAM_Admin_Product_Query::get_filter(['filter_id' => $type_query]);				
			}
			else
			{
				unset($this->query_vars['compilation']);
				$controller = 'query_'.$type_query;
				if ( method_exists( $this, $controller ) )
				{			
					$query_vars = $this->$controller();
					if ( empty($query_vars) )
						return false;				
					$this->query_vars = array_merge( $this->query_vars, $query_vars );	
				}
			}
		}		
		$this->query_vars['post_status'] = 'publish';
		$this->query_vars['post_parent'] = 0;
		$this->query_vars['post_type'] = 'usam-product';
		$this->query_vars['update_post_meta_cache'] = true;
		$this->query_vars['product_meta_cache'] = true;
		$this->query_vars['update_post_term_cache'] = true; // Варианты кешировать
		$this->query_vars['cache_results'] = true;	
		$this->query_vars['no_found_rows'] = true;
		$this->query_vars['in_stock'] = true;
		$this->query_vars['prices_cache'] = true;
		$this->query_vars['stocks_cache'] = true;
		$this->query_vars['discount_cache'] = true; //Для получения меток на товары
		$this->query_vars = apply_filters( 'usam_query_product_groups', $this->query_vars );	
		
		if ( empty($this->query_vars['meta_query']) )
			$this->query_vars['meta_query'] = array();
		
		if ( !get_option('usam_show_zero_price', 1) && empty($this->query_vars['price_meta_query']) )
		{
			$type_price = usam_get_customer_price_code();
			$this->query_vars['price_meta_query'] = [['key' => 'price_'.$type_price, 'value' => '0', 'compare' => '!=']];
		}		
		$wp_query = new WP_Query( $this->query_vars );		

		update_post_thumbnail_cache( $wp_query );
		return $wp_query;
	}
} 
?>