<?php 
class USAM_SEO_API extends USAM_API
{			
	public static function get_metas( ) 
	{	
		$default = ['title' => '', 'description' => '','opengraph_title' => '', 'opengraph_description' => '', 'noindex' => 0, 'nofollow' => 0, 'exclude_sitemap' => ''];
		$metas = get_option('usam_metas', []);
		$metas = array_merge(['pages' => [''], 'post_types' => [], 'terms' => []], $metas );			
		$pages = usam_virtual_page();
		$tags = [];	
		$shortcode = usam_get_seo_shortcode('post');
		foreach(['home', 'point-delivery', 'map'] as $page ) 
		{			
			$meta = !empty($metas['pages'][$page])?$metas['pages'][$page]:[];
			$meta['slug'] = $page; 
			$meta['shortcode'] = $shortcode;
			if( $page == 'home' )
				$meta['name'] = __("Главная страница","usam");
			else
				$meta['name'] = $pages[$page]['title'];		
			$tags['pages'][] = array_merge($default, $meta );	
		}	
		$taxonomies = get_taxonomies(['public' => true], 'objects');
		$meta = !empty($metas['terms']['product-filters'])?$metas['terms']['product-filters']:[];
		$meta = array_merge($default, $meta );		
		$shortcode = usam_get_seo_shortcode('product_filter');
		$tags['terms'][] = array_merge($meta, ['slug' => 'product-filters', 'name' => __("Фильтры товаров","usam"), 'shortcode' => $shortcode]);			
		foreach( $taxonomies as $tax ) 
		{
			$shortcode =  usam_get_seo_shortcode('term');
			$meta = !empty($metas['terms'][$tax->name])?$metas['terms'][$tax->name]:[];		
			$meta['slug'] = $tax->name;
			$meta['name'] = $tax->labels->name;
			$meta['shortcode'] = $shortcode;
			$tags['terms'][] = array_merge($default, $meta );		
		}
		$post_types = get_post_types(['public' => true], 'objects');
		foreach( $post_types as $post_type ) 
		{
			$shortcode = $post_type->name=='usam-product'?usam_get_seo_shortcode('product'):usam_get_seo_shortcode('post');
			$meta = !empty($metas['post_types'][$post_type->name])?$metas['post_types'][$post_type->name]:[];			
			$meta['slug'] = $post_type->name;
			$meta['name'] = $post_type->label;
			$meta['shortcode'] = $shortcode;
			$tags['post_types'][] = array_merge($default, $meta );
		}		
		return $tags;
	}	
	
	public static function save_metas( WP_REST_Request $request ) 
	{	
		$parameters = $request->get_params();
		$keys = ['title', 'description', 'opengraph_title', 'opengraph_description', 'noindex', 'nofollow', 'exclude_sitemap'];		
		$metas = get_option('usam_metas', []);	
		foreach( ['pages', 'terms', 'post_types'] as $type ) 
		{
			foreach( $parameters['metas'][$type] as $key => $values ) 
			{
				foreach( $values as $k => $meta ) 
					if( in_array($k, $keys) )
						$metas[$type][$key][$k] = $meta;
			}
		}	
		return update_option('usam_metas', $metas);
	}	
	
	public static function get_post_metatags( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_params();
		$result = [];
		foreach(['title', 'description', 'opengraph_title', 'opengraph_description', 'shortcode', 'noindex', 'nofollow'] as $k)
			$result[$k] = (string)get_post_meta($parameters['id'], 'meta_'.$k, true);	
		$result['exclude_sitemap'] = (string)usam_get_post_meta($parameters['id'], 'exclude_sitemap');
		$result['shortcode'] = usam_get_seo_shortcode('product');
		return $result;
	}
	
	public static function get_robots( WP_REST_Request $request ) 
	{
		$filename = ABSPATH.'robots.txt';
		if ( file_exists($filename) )
			return file_get_contents(ABSPATH.'robots.txt');
		return '';
	}
	
	public static function get_default_robots( WP_REST_Request $request ) 
	{
		$file = file_get_contents(USAM_FILE_PATH .'/includes/seo/robots.txt');
		$file .= "\n\nSitemap: ".get_bloginfo('url')."/sitemap_index.xml\nHost: ".get_bloginfo('url');
		return $file;
	}
	
	public static function save_robots( WP_REST_Request $request ) 
	{
		$parameters = $request->get_params();
		return file_put_contents(ABSPATH.'robots.txt', $parameters['robots']);
	}	
	
	public static function insert_keyword( WP_REST_Request $request ) 
	{		
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH . '/includes/seo/keyword.class.php' );		
		return usam_insert_keyword( $parameters );
	}
	
	public static function get_keyword( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		require_once( USAM_FILE_PATH . '/includes/seo/keyword.class.php' );
		return usam_get_keyword( $id );
	}

	public static function update_keyword( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );	
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();
		
		require_once( USAM_FILE_PATH . '/includes/seo/keyword.class.php' );	
		return usam_update_keyword( $id, $parameters );
	}
		
	public static function delete_keyword( WP_REST_Request $request ) 
	{		
		$id = $request->get_param( 'id' );			
		require_once( USAM_FILE_PATH . '/includes/seo/keyword.class.php' );
		return usam_delete_keyword( $id );
	}	
}
?>