<?php
require_once( USAM_FILE_PATH . '/includes/seo/meta_tags.class.php' );	
class USAM_Post_Meta_Tags extends USAM_Meta_Tags
{		
	protected function get_args( ) 
	{
		global $wp_query, $post;
		$args = parent::get_args();
		if ( !is_home() )
		{
			if ( $post->post_type == 'usam-product' )
			{
				$args['price'] = usam_get_product_price( $post->ID );
				$args['sku'] = usam_get_product_meta( $post->ID, 'sku' );
				$args['price_currency'] = usam_get_product_price_currency( $post->ID );
				$categories = get_the_terms($post->ID, 'usam-category');
				$args['category'] = !empty($categories[0])?$categories[0]->name:'';
				$brands = get_the_terms($post->ID, 'usam-brands');
				$args['brand'] = !empty($brands[0])?$brands[0]->name:'';						
			}
			$args['post_title'] = get_the_title( $post->ID );	
			$args['post_excerpt'] = usam_limit_words($post->post_excerpt, 250, false);
			if ( strpos($post->post_content, '[point_delivery]') !== false && isset($wp_query->query['id']) )
			{
				$location = usam_get_location( $wp_query->query['id'] );				
				if ( isset($location['name']) )
					$args['city'] = htmlspecialchars($location['name']);
			}
		}		
		return apply_filters( 'usam_args_post_meta_tags', $args, $post );
	}
	
	public function get_open_graph_article_modified_time( ) 
	{
		global $post;
		return date('c', strtotime($post->post_modified_gmt));
	}
	
	public function get_open_graph_published_time( ) 
	{
		global $post;
		return date('c', strtotime($post->post_date_gmt));
	}	
	
	public function get_open_graph_url( ) 
	{
		global $post;
		return get_permalink($post->ID);
	}	
	
	public function get_title( ) 
	{
		return $this->get_tag_meta( 'title' );
	}
	
	public function get_description( ) 
	{			
		return $this->get_tag_meta( 'description' );
	}	
	
	public function get_open_graph_title( ) 
	{	
		return $this->get_tag_meta( 'opengraph_title' );
	}
	
	public function get_open_graph_description( ) 
	{	
		return $this->get_tag_meta( 'opengraph_description' );
	}
				
	public function get_tag_meta( $key ) 
	{		
		global $post;
		$tag_meta = '';	
		$args = $this->get_args();
		$post_name = is_home() ? 'home' : $post->post_name;
		if ( !empty($this->options['pages'][$post_name]) && !empty($this->options['pages'][$post_name][$key]) )
		{
			$tag_meta = $this->options['pages'][$post_name][$key];							
		}
		elseif ( is_singular() )	
		{			
			$tag_meta = get_post_meta($post->ID, 'meta_'.$key, true);				
			if ( !$tag_meta )
			{
				if ( $post->post_type == 'usam-product' )
				{				
					if ( !$tag_meta )
					{
						$categories = get_the_terms($post->ID, 'usam-category_sale');					
						if ( !empty($categories) )
							foreach( $categories as $category )
							{ 	
								$tag_meta = get_term_meta($category->term_id, 'postmeta_'.$key, true);	
								if ( $tag_meta )
									break;
							}
						if ( !$tag_meta )
						{ 
							$categories = get_the_terms($post->ID, 'usam-category');							
							if ( !empty($categories) )
							{
								$object_ids = [];
								foreach( $categories as $category )
								{
									$object_ids[] = $category->term_id;
									$ancestors = usam_get_ancestors( $category->term_id );
									foreach( $ancestors as $term_id )
										$object_ids[] = $term_id;
								}
								update_meta_cache( 'term', $object_ids );						
								foreach( $categories as $category )
								{ 
									$tag_meta = get_term_meta($category->term_id, 'postmeta_'.$key, true);	
									if ( $tag_meta )
										break;
									
									$ancestors = usam_get_ancestors( $category->term_id );
									foreach( $ancestors as $term_id )
									{										
										$tag_meta = get_term_meta($term_id, 'postmeta_'.$key, true);	
										if ( $tag_meta )
											break;
									}
								}
							}
						} 
					}
				}
				else if ( $post->post_type == 'post' )
				{
					$categories = get_the_terms($post->ID, 'category' );
					if ( !empty($categories) )
						foreach( $categories as $category )
						{
							$tag_meta = get_term_meta($category->term_id, 'postmeta_'.$key, true);	
							if ( $tag_meta )
								break;
						}
				}			
			}
			if ( !$tag_meta && !empty($this->options['post_types'][$post->post_type]) && !empty($this->options['post_types'][$post->post_type][$key]) )
				$tag_meta = $this->options['post_types'][$post->post_type][$key];	
		}		
		$shortcode = new USAM_Shortcode();		
		return $shortcode->process_args( $args, $tag_meta );
	}	
}