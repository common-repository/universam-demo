<?php
new USAM_SEO_Frontend();
class USAM_SEO_Frontend
{
	private $is_analytics_disabled = false;
	private $is_theme_tracking     = false;
	private $advanced_code         = false;
	private $tracking_id           = '';

	public function __construct()
	{			
		add_action( 'wp_footer', [&$this, 'load'], 1 );		
		add_action( 'wp_head', [&$this, 'wp_head'], 3 );
		add_filter( 'wp_robots', [&$this, 'robots_tag'], 100);
		add_filter( 'pre_get_document_title', [$this, 'filter_title'], 100);	
		add_filter( 'wp_title', [&$this, 'filter_title' ], 100 );			
		add_filter( 'wp_sitemaps_posts_query_args', [&$this, 'sitemaps_posts_query_args'], 10, 2 );
	//	add_filter( 'wp_sitemaps_taxonomies_query_args', [&$this, 'sitemaps_taxonomies_query_args'], 10, 2 );
	}
	
	function sitemaps_taxonomies_query_args( $args, $taxonomy )
	{
		
		return $args;
	}
	
	function sitemaps_posts_query_args( $args, $post_type )
	{
		$args['postmeta_query'] = ['relation' => 'OR', ['key' => 'exclude_sitemap', 'value' => 0, 'compare' => '='], ['key' => 'exclude_sitemap', 'compare' => 'NOT EXISTS']];
		return $args;
	}
	
	public function get_noindex_pages( ) 
	{
		return ['pay_order', 'compare', 'wish-list', 'basket', 'checkout', 'transaction-results', 'your-account', 'your-subscribed', 'search', 'tracking'];
	}
	
	public function get_nofollow_pages( ) 
	{
		return ['pay_order', 'basket', 'checkout', 'transaction-results', 'your-account', 'your-subscribed', 'tracking'];
	}
	
	public function is_noindex(  ) 
	{
		global $post;
		if ( !empty($post) && in_array($post->post_name, $this->get_noindex_pages()) )
			return true;	
		$meta_tag = $this->get_class_meta_tag();
		if( $meta_tag )			
			return $meta_tag->get_noindex();
		return false;
	}
	
	public function is_nofollow(  ) 
	{
		global $post;	
		if ( !empty($post) && in_array($post->post_name, $this->get_nofollow_pages()) )
			return true;	
		$meta_tag = $this->get_class_meta_tag();
		if( $meta_tag )		
			return $meta_tag->get_nofollow();
		return false;
	}
	
	public function robots_tag( $robots ) 
	{
		global $post;		
		if ( $this->is_noindex() )
			$robots['noindex'] = true;
		else
			$robots['index'] = true;
		if ( $this->is_nofollow() )
			$robots['nofollow'] = true;	
		else
			$robots['follow'] = true;	
		return $robots;
	}
	//yandex
	public function load()
	{						
		global $post;
		if ( empty($post) || ( $post->post_name != 'your-account' && $post->post_name != 'login') )
		{	
			if ( !usam_check_is_employee() && !current_user_can('store_section')  )
			{  	
				if ( (bool)get_option('usam_google_analytics_active', false) && !usam_is_bot() )
				{		
					require_once( USAM_FILE_PATH . '/includes/seo/google/analytics.class.php' );
					USAM_Google_Analytics::print_script();
				}
				if ( (bool)get_option('usam_yandex_metrika_active', false) && !usam_is_bot() || usam_check_bot('yandex') )
				{
					require_once( USAM_FILE_PATH . '/includes/seo/yandex/metrika_counter.class.php' );
					USAM_Yandex_Metrika_Counter::print_script();
				}
				if ( get_option('usam_vk_pixel_active', false) && !usam_is_bot() )
				{					
					USAM_VKontakte::pixel();
				}
				if ( get_option('usam_facebook_pixel_active', false) && !usam_is_bot() )
				{					
					require_once( USAM_FILE_PATH.'/includes/feedback/facebook_pixel.class.php' );
					USAM_Facebook_Pixel::pixel();					
				}
				if ( get_option('usam_mytarget_counter_active', false) && !usam_is_bot() )
				{					
					require_once( USAM_FILE_PATH . '/includes/seo/mytarget_counter.class.php' );
					USAM_MyTarget_Counter::print_script();					
				}
			}
		}
	}
	
	public function get_class_meta_tag( ) 
	{		
		static $meta_tag = null;
		if( $meta_tag === null )
		{
			if ( is_singular() || is_home() )
			{
				require_once( USAM_FILE_PATH . '/includes/seo/post_meta_tags.class.php' );
				$meta_tag = new USAM_Post_Meta_Tags();	
			}
			elseif ( is_tax() )
			{ 
				require_once( USAM_FILE_PATH . '/includes/seo/term_meta_tags.class.php' );	
				$meta_tag = new USAM_Term_Meta_Tags();					
			}		
			else
				$meta_tag = false;
		}
		return $meta_tag;
	}
	
	public function filter_title( $title ) 
	{	
		$meta_tag = $this->get_class_meta_tag();
		if ( $meta_tag )
		{
			$tag_title = $meta_tag->get_title();		
			if ( $tag_title )
			{
				$title = $tag_title;
				remove_all_filters( 'pre_get_document_title' );
				remove_all_filters( 'wp_title' );
			}
		}
		return esc_html( $title );
	}
	
	public function wp_head( ) 
	{	
		if ( !$this->is_noindex() )
		{			
			if( !is_user_logged_in() || current_user_can('view_seo') )
			{
				$this->seo_meta();			
				$this->webmaster_tools_authentication();					
			}
		}
	}
	
	public function seo_meta( ) 
	{			
		global $post;
		$meta_tag = $this->get_class_meta_tag(); 
		if ( $meta_tag )
		{ 
			$description = $meta_tag->get_description();
			if ( $description )
				echo '<meta name="description" content="' .esc_html($description).'"/>'."\n";
			
			if ( is_singular() )
			{
				$title = $meta_tag->get_open_graph_title();
				if ( $title )
				{
					$output = '<meta property="og:type" content="article" />'."\n";
					$output .= '<meta property="og:title" content="' . esc_attr( $title ) . '" />'."\n";
					$url = $meta_tag->get_open_graph_url();
					if ( $url )
						$output .= '<meta property="og:url" content="' . $url . '" />'."\n";
					$description = $meta_tag->get_open_graph_description();
					if ( $description )
						$output .= '<meta property="og:description" content="' . esc_attr( $description ) . '" />'."\n";
					$output .= '<meta property="og:site_name" content="'.$meta_tag->get_site_name().'"/>';				
					$output .= '<meta property="og:locale" content="' . $meta_tag->get_open_graph_locale() . '" />'."\n";
					$time = $meta_tag->get_open_graph_article_modified_time();
					if ( $time )
						$output .= '<meta property="article:modified_time" content="' . esc_attr( $time ) . '" />'."\n";	
					$time = $meta_tag->get_open_graph_published_time();
					if ( $time )
						$output .= '<meta property="article:published_time" content="' . esc_attr( $time ) . '" />'."\n";		
					if ( has_post_thumbnail() ) 
					{
						$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium' );
						$output .= '<meta property="og:image" content="' . $src[0] . '" />'."\n";
						if ( is_ssl() )
							$output .= '<meta property="og:image:secure_url" content="' . $src[0] . '" />'."\n";
						$output .= '<meta property="og:image:width" content="'.$src[1].'" />';
						$output .= '<meta property="og:image:height" content="'.$src[2].'" />';
					}
					echo $output;
				}			
			}		
			$schema = $meta_tag->get_schema();
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( $schema && ( !is_plugin_active('wordpress-seo/wp-seo.php') && !is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') ))
				echo '<script type="application/ld+json" class="usam-schema">'.$schema.'</script>'."\n";
		}
	}
		
	public function webmaster_tools_authentication( ) 
	{			
		$bing = get_option('usam_bing');	
		if ( !empty($bing['verify']) ) {
			echo '<meta name="msvalidate.01" content="' . esc_attr( $bing['verify'] ) . "\" />\n";
		}		
		$google = get_option('usam_google');		
		if ( !empty($google['verify']) ) {
			echo '<meta name="google-site-verification" content="' . esc_attr( $google['verify'] ) . "\" />\n";
		}
		$pinterest = get_option('usam_pinterest');
		if ( !empty($pinterest['verify']) ) {
			echo '<meta name="p:domain_verify" content="' . esc_attr( $pinterest['verify'] ) . "\" />\n";
		} 
		$yandex = get_option('usam_yandex');
		if ( !empty($yandex['webmaster']['verify']) ) {
			echo '<meta name="yandex-verification" content="' . esc_attr( $yandex['webmaster']['verify'] ) . "\" />\n";
		}
	}	
}