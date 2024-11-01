<?php
/**
 * выводит результаты поиска
 */
class USAM_Search_Shortcodes 
{	
	public static function get_products( $args = [], $post_type = '' ) 
	{
		global $wp_query;				
		if ( !empty($_REQUEST['scat']) )
		{
			if ( !is_array($_REQUEST['scat']) )
				$scat = explode('-', $_REQUEST['scat']);
			else
				$scat = $_REQUEST['scat'];
			$scat = array_map('intval', $scat);
		}			
		if ( $post_type == '' )
			$post_type = get_option('usam_website_type', 'store' ) == 'crm' ? 'post' : 'product';
		if ( !empty($args['s']) )
		{  				
			$default = ['s' => $args['s'], 'orderby' => 'predictive', 'order' => 'ASC', 'post_status' => 'publish', 'posts_per_page' => 10];
			if ( $post_type == 'post' )
			{
				$args['post_type'] = 'post';
				if ( !empty($scat) )
					$default['tax_query'] = [ ['taxonomy' => 'category', 'field' => 'id', 'terms' => $scat, 'operator' => 'IN' ] ];		
			}
			else
			{				
				if ( !empty($scat) )
					$default['tax_query'] = [ ['taxonomy' => 'usam-category', 'field' => 'id', 'terms' => $scat, 'operator' => 'IN' ] ];
				$sold = get_option( 'usam_display_sold_products', 'sort');		
				if ( $sold != 'show' )
				{
					if ( $sold == 'hide' )
						$default['in_stock'] = true;
					else
						$default['in_stock'] = false;	
				}
				$args['post_type'] = 'usam-product';
				$default['prices_cache'] = true;
				$default['stocks_cache'] = true;
				$default['update_post_term_cache'] = true;
				$default['product_meta_cache'] = true;				
			}
			$args['post_parent'] = 0;
			$args['no_found_rows'] = true;	
			$query_vars = array_merge( $default, $args );		
			$wp_query = new WP_Query;	
			$products = $wp_query->query( $query_vars );				
			if ( $post_type != 'post' )
				usam_product_thumbnail_cache( $wp_query );
		}
		else
			$products = [];	
		return $products;
	}
	
	public static function get_product_sku( $product_id ) 
	{
		if ( get_option('usam_search_sku_enable', true) )
		{
			$sku = usam_get_product_meta( $product_id, 'sku', true );
			$out = "<div class = 'search_results__row_sku'>".__('Артикул', 'usam').": <span>$sku</span></div>";
		}
		else
			$out = "";
		return $out;
	}
	
	public static function get_product_stock_title( $product_id ) 
	{
		if ( get_option('usam_search_in_stock_enable', true) )
		{
			if ( usam_product_has_stock( $product_id ) ) 
				$title = __('В наличии', 'usam');
			else
				$title = __('Продан', 'usam');
			$out = "<div class = 'search_results__row_stock_title'>$title</div>";
		}
		else
			$out = "";
		return $out;
	}	
	
	
	public static function get_product_prices( $product_id ) 
	{
		if ( get_option('usam_search_price_enable', true) )
		{					
			$out = "<div class='prices'><span class = 'old_price'>".usam_get_product_price_currency( $product_id, true )."</span><span class = 'price'>".usam_get_product_price_currency( $product_id )."</span></div>";
		}
		else
			$out = "";
		return $out;
	}
	
	public static function get_product_tags( $product_id ) {}	
	
	public static function strip_shortcodes ($content='')
	{
		$content = preg_replace( '|\[(.+?)\](.+?\[/\\1\])?|s', '', $content);		
		return $content;
	}	
	
	public static function get_product_description( $product ) 
	{
		$text_lenght = get_option('usam_search_text_lenght', 100);
		$description = usam_limit_words( strip_tags( USAM_Search_Shortcodes::strip_shortcodes( strip_shortcodes( $product->post_excerpt ) ) ),$text_lenght,'...');
		return $description;
	}	
	
	public static function get_product_categories( $product_id ) 
	{	
		if ( get_option('usam_search_categories_enable', true) )
		{
			$categories = get_the_terms( $product_id , 'usam-category' );	
			if ( empty($categories) )
				return $out = "";
			
			$i = 0;
			$sumbol = ' &raquo; ';
			$category = current( $categories );	
			$id = (int)$category->term_id;
			$link_cat = "<a href='".get_term_link($id, "usam-category")."'>".$category->name."</a>";		
			$terms = usam_get_ancestors( $category->term_id, 'usam-category' );		
			foreach ($terms as $term) 
			{				
				$term_data = get_term_by('id', $term, 'usam-category');
				$link_cat = "<a href='".get_term_link($term, "usam-category")."'>".$term_data->name."</a>".$sumbol.$link_cat;	
				$i++;			
			}		
			$out = "<div class = 'search_results__row_cat'>$link_cat</div>";
		}
		else
			$out = "";
		return $out;		
	}
	
	public static function get_product_category($product_id, $show = true) 
	{	
		if ( $show )
		{
			$categories = get_the_terms( $product_id , 'usam-category' );			
			$category = current( $categories );	
			$id = (int)$category->term_id;
			$link_cat = "<a href='".get_term_link($id, "usam-category")."'>".$category->name."</a>";
			$out = "<div class = 'search_results__row_cat'>$link_cat</div>";
		}
		else
			$out = "";
		return $out;		
	}
}
?>