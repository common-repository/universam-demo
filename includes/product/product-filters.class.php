<?php
/**
 * Фильтры товаров.
 */ 
 
class USAM_Product_Filters
{	
	function __construct() 
	{			
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
		{
			add_filter( 'wp_insert_post_data',  [__CLASS__, 'insert_post_data'], 10, 4);				
		}
		
		add_action('before_delete_post',  [__CLASS__, 'delete_product'], 11, 2);
		add_action( 'set_object_terms', [__CLASS__, 'recalculate_price_product_set_terms'], 10, 6 );
	}
	
	public static function insert_post_data( $data, $postarr, $unsanitized_postarr, $update )
	{		
		if ( !empty($postarr['ID']) )
		{
			$post = get_post( $postarr['ID'] );
			$data['post_author'] = $post->post_author;
		}
		return $data;
	}
	
	// Удалить информацию о товаре при удалении самого товара
	public static function delete_product( $product_id, $post )
	{
		global $wpdb; 
		if ( usam_is_multisite() )
		{		
			if ( is_main_site() )
			{  // Если главный сайт
				$sites = get_sites(['site__not_in' => [0,1]]);	
				if ( $sites )
				{
					foreach( $sites as $site )
					{						
						switch_to_blog( $site->blog_id );
						$id = $wpdb->get_var("SELECT multisite_post_id FROM ".usam_get_table_db('linking_posts_multisite')." WHERE ID = {$product_id}");
						if ( $id )
							wp_delete_post( $id, true );
					}	
					switch_to_blog( 1 );				
				}
			}
			else
				$wpdb->query("DELETE FROM ".usam_get_table_db('linking_posts_multisite')." WHERE multisite_post_id = '$product_id'");
		}
		if ( !usam_is_multisite() || is_main_site() )
		{	
			$wpdb->query( "DELETE FROM ".USAM_TABLE_USER_POSTS." WHERE product_id='$product_id'" );//список постов или товаров
		}
		if( $post->post_type !== 'usam-product' ) 
			return;
		
		$posts = $wpdb->get_results("SELECT * FROM `".$wpdb->posts."` WHERE `post_parent`=$product_id");		
		if ( !empty($posts) )	
		{ 
			foreach ($posts as $post) 
			{
				if ( $post->post_type == 'attachment' )
					wp_delete_attachment( $post->ID, true );
				else
					wp_delete_post( $post->ID, true );
			}
		}
		$wpdb->query( "DELETE FROM ".usam_get_table_db('product_attribute')." WHERE product_id='$product_id'" );
		$wpdb->query( "DELETE FROM ".usam_get_table_db('product_filters')." WHERE product_id='$product_id'" );
		$wpdb->query( "DELETE FROM ".usam_get_table_db('posts_search')." WHERE post_search_id='$product_id'" );	
		if ( !usam_is_multisite() || is_main_site() )
		{
			$wpdb->query( "DELETE FROM ".USAM_TABLE_POST_META." WHERE post_id='$product_id'" );	
			$wpdb->query( "DELETE FROM ".USAM_TABLE_ASSOCIATED_PRODUCTS." WHERE product_id='$product_id' OR associated_id='$product_id'" );	
			$wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS." WHERE product_id='$product_id'" );		
			$wpdb->query( "DELETE FROM ".USAM_TABLE_STOCK_BALANCES." WHERE product_id='$product_id'" );
			$wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCT_META." WHERE product_id='$product_id'" );
			$wpdb->query( "DELETE FROM ".USAM_TABLE_PRODUCT_PRICE." WHERE product_id='$product_id'" );
		}
		/*
		foreach ( (array) $taxonomies as $taxonomy ) {
			$term_ids = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'ids' ) );
			$term_ids = array_map( 'intval', $term_ids );
			wp_remove_object_terms( $object_id, $term_ids, $taxonomy );
		}
		
		wp_delete_object_term_relationships( $postid, get_object_taxonomies( $post->post_type ) );
		
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->term_relationships WHERE object_id = %d AND term_taxonomy_id IN ($in_tt_ids)", $object_id ) );	*/
	}

	public static function recalculate_price_product_set_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids )
	{ 	
		if ( $taxonomy == 'usam-brands' || $taxonomy == 'usam-category' || $taxonomy == 'usam-category_sale' )
		{ 
			usam_edit_product_prices( $object_id );
		}
		if ( $taxonomy == 'usam-catalog' )
		{		
			foreach(['usam-category', 'usam-brands', 'usam-category_sale'] as $tax )
			{
				$term_ids = usam_get_product_term_ids( $object_id, $tax );		
				foreach( $term_ids as $term_id )
				{				
					$terms_category = usam_get_array_metadata( $term_id, 'term', 'catalog' );	
					$delete_ids = array_diff($terms_category, $terms);		
					foreach( $delete_ids as $delete_id )
					{
						usam_delete_term_metadata( $term_id, 'catalog', $delete_id );				
					}	
					$update_ids = array_diff($terms, $terms_category);		
					foreach( $update_ids as $id )
					{
						usam_add_term_metadata( $term_id, 'catalog', $id, false );				
					}					
				}
			}
		}	
	}
}
new USAM_Product_Filters();