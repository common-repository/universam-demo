<?php
//класс для создания виртуальных страниц  

class USAM_Virtual_Page
{ 
    private $slug = null;
    private $title = null;
    private $content = null;
    private $author = null;
    private $date = null;
	private $dategmt = null;
    private $type = null;
 
    public function __construct( $args ) 
	{
        if (!isset($args['slug']))
            throw new Exception('отсутствует ярлык для виртуальной страницы');
 
        $this->slug = $args['slug'];
        $this->title = isset($args['title']) ? $args['title'] : '';
        $this->content = isset($args['content']) ? $args['content'] : '';
        $this->author = isset($args['author']) ? $args['author'] : 1;
        $this->date = isset($args['date']) ? $args['date'] : current_time('mysql');
        $this->dategmt = isset($args['date']) ? $args['date'] : current_time('mysql', 1);
        $this->type = isset($args['type']) ? $args['type'] : 'page';
 
        add_filter('the_posts', array(&$this, 'virtualPage'));
    }
 
    public function virtualPage( $posts ) 
	{
        global $wp_query;	
		if ( ! in_the_loop() )
		{
			remove_filter( 'the_posts', array($this,'virtualPage') );
			remove_all_filters( 'the_content' );
			
			$post = new stdClass;

            $post->ID = -111;
            $post->post_author = $this->author;
            $post->post_date = $this->date;
            $post->post_date_gmt = $this->dategmt;
            $post->post_content = apply_shortcodes( $this->content );
            $post->post_title = $this->title;
            $post->post_excerpt = '';
            $post->post_status = 'publish';
            $post->comment_status = 'closed';
            $post->ping_status = 'closed';
            $post->post_password = '';
            $post->post_name = $this->slug;
            $post->to_ping = '';
            $post->pinged = '';
            $post->modified = $post->post_date;
            $post->modified_gmt = $post->post_date_gmt;
            $post->post_content_filtered = '';
            $post->post_parent = 0;
            $post->guid = get_home_url('/' . $this->slug);
            $post->menu_order = 0;
            $post->post_type = $this->type;
            $post->post_mime_type = '';
            $post->comment_count = 0;
			
			wp_cache_set( $post->ID, $post, 'posts' );
 
            $posts = [$post]; 			
	
            $wp_query->is_page = true;
            $wp_query->is_singular = true;
            $wp_query->is_home = false;
            $wp_query->is_archive = false;
            $wp_query->is_category = false;
            unset($wp_query->query['error']);
            $wp_query->query_vars['error'] = '';
            $wp_query->is_404 = false;			
        }
        return ($posts);
    }
}
?>