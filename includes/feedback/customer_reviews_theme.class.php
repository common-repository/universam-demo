<?php
/*
 * Description: Отзывы клиентов позволяет Вашим клиентам и посетителям оставить отзывы или рекомендации ваших услуг.
 * Version: 2.4.5
 * Revision Date: 25 мая 2017
 */

require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
class USAM_Customer_Reviews_Theme 
{        
    private $options = array();	
	public function __construct( $args = array() )
	{		             
		$default = get_option('usam_reviews',$args );
		$this->options = array_merge( $default, $args );		  
    }
		
    function is_active_page()
	{
        global $post;
        
        if ( !isset($post) || !isset($post->ID) || intval($post->ID) == 0 )
            return false; 
				
        if ( is_singular() && 'open' == $post->comment_status ) 
            return true;      
	
        return false;
    }     
	
	function is_review_page()
	{
        global $post;
        
        if ( empty($post->ID) )
            return false; 
				
        if ( has_shortcode($post->post_content, 'reviews') ) 
            return true;      
	
        return false;
    }   

    function aggregate_footer() 
	{        
        $aggregate_footer_output = '';
       
        if ($this->options['show_hcard_on'] != 0 ) 
		{           
            if ( $this->options['show_hcard_on'] == 4 && usam_is_product() ) 
                $show = true;
            elseif ( $this->options['show_hcard_on'] == 2 && ( is_home() || is_front_page() || $this->is_review_page() ) ) 
                $show = true;
            elseif ( $this->options['show_hcard_on'] == 1 && $this->is_active_page() ) 
                $show = true;
			 elseif ( $this->options['show_hcard_on'] == 3 && $this->is_review_page() ) 
                $show = true;
			else
				 $show = false;
           
            if ( $show ) 
			{             
				 $hide = "hide";           
				if ( $this->options['show_hcard'] == 1 )
					$hide = ""; 
				$business = usam_shop_requisites_shortcode();
				$aggregate_footer_output = '<div class="vcard '.$hide.'">';
                $aggregate_footer_output .= $business['full_company_name'].'<br />';  			
               
				$aggregate_footer_output .= '<span class="adr">';
				if ( !empty($business['legaladdress']) )
					$aggregate_footer_output .= '<span class="street-address">' . $business['legaladdress'] . '</span>&nbsp;';
				
				if ( !empty($business['legalcity']) ) 
					$aggregate_footer_output .='<span class="locality">' . $business['legalcity'] . '</span>,&nbsp;';
				
				if ( !empty($business['legalpostcode']) ) 
					$aggregate_footer_output .='<span class="postal-code">' . $business['legalpostcode'] . '</span>&nbsp;';
				   
				if ( !empty($business['legalcountry']) ) 				   
					$aggregate_footer_output .='<span class="country-name">' . $business['legalcountry'] . '</span>&nbsp;';
				$aggregate_footer_output .= '</span>';
                
                if ( !empty($business['email']) && !empty($business['phone']) )
                    $aggregate_footer_output .= '<br />';                
                if ( !empty($business['email']) ) 
                    $aggregate_footer_output .= '<a class="email" href="mailto:' . $business['email'] . '">' . $business['email'] . '</a>';
                if ( !empty($business['email']) && !empty($business['phone']) ) 
                    $aggregate_footer_output .= '&nbsp;&bull;&nbsp';                
                if ( !empty($business['phone']) ) 
                    $aggregate_footer_output .= '<span class="tel">' . $business['phone'] . '</span>';
                $aggregate_footer_output .= '</div>';
            }
        }
        return $aggregate_footer_output;
    }

    function pagination($total_results, $per_page) 
	{
        global $post, $wp_query; 
		if ( !$per_page )
			return '';
	
		$per_page = (int)$per_page;
			
        $out = '';
        $range = 2;
        $showitems = ($range * 2) + 1;

		global $wp_query;
		$paged = !empty($wp_query->query_vars['paged'])?$wp_query->query_vars['paged']:1; 
        if ($paged == 0) { $paged = 1; }             		
        if ($total_results > $per_page) 
		{ 
			$pages = ceil($total_results / $per_page);
			$url = trailingslashit(get_permalink($post->ID));
			if ( !empty($wp_query->query['pagename']) && $wp_query->query['pagename'] == 'reviews' && !empty($wp_query->query['id']) )
				$url .= $wp_query->query['id'].'/';
			
            $out .= '<div class="usam_navigation"><div class="pagination">';
            if ($paged > 2 && $paged > $range + 1 && $showitems < $pages)
                $out .= '<a href="' . $url . '">&laquo;</a>';
            if ($paged > 1 && $showitems < $pages) {
                $out .= '<a href="' . $url . 'page/' . ($paged - 1) . '">&lsaquo;</a>';
            }
            for ($i = 1; $i <= $pages; $i++) 
			{
                if ($i == $paged) 
                    $out .= '<span class="current">' . $paged . '</span>';  
				elseif (!($i >= $paged + $range + 1 || $i <= $paged - $range - 1) || $pages <= $showitems) 
				{
                    if ($i == 1)        
                        $out .= '<a href="' . $url . '" class="usam_inactive">' . $i . '</a>';
					else 
                        $out .= '<a href="' . $url . 'page/' . $i . '" class="usam_inactive">' . $i . '</a>';
                }
            }
            if ($paged < $pages && $showitems < $pages) {
                $out .= '<a href="' . $url . 'page/' . ($paged + 1) . '">&rsaquo;</a>';
            }
            if ($paged < $pages - 1 && $paged + $range - 1 < $pages && $showitems < $pages) {
                $out .= '<a href="' . $url . 'page/' . $pages . '">&raquo;</a>';
            }
            $out .= '</div></div>';
            return $out;
        }
    }
        
    function output_reviews_show( $args = [] )
	{     		 			
		global $wp_query;
		$page = !empty($wp_query->query_vars['paged'])?$wp_query->query_vars['paged']:1; 
		$pagination = isset($args['pagination'])?$args['pagination']:1;
		$per_page = isset($args['per_page'])?$args['per_page']:$this->options['per_page'];
		$summary_rating = isset($args['summary_rating'])?$args['summary_rating']:true;
		$query = ['status' => 2, 'paged' => $page, 'number' => $per_page, 'order' => 'DESC', 'orderby' => 'date_insert', 'cache_meta' => true, 'cache_attachments' => true];		
		if ( isset($args['page_id']) )
			$query['page_id'] = $args['page_id'];
		if ( isset($args['user_id']) )
			$query['user_id'] = $args['user_id'];

		$_reviews = new USAM_Customer_Reviews_Query( $query );	
		$reviews = $_reviews->get_results();
		$total_reviews = $_reviews->get_total();
	
		ob_start();	
		include usam_get_template_file_path( 'reviews' );		
		$reviews_content = ob_get_clean();
		if ( $pagination )
			$reviews_content .= $this->pagination( $total_reviews, $per_page );	
      	
        return $reviews_content;
    }
  	
	function show_reviews_form( $which ) 
	{ 
		if ( $this->options['form_location'] != $which || empty($this->options['goto_show_button']) ) 
			return '';		
		
		ob_start();	
		include usam_get_template_file_path( 'form-add-reviews' );
		return ob_get_clean();	
    }
	
	function show_button_reviews_form( $which ) 
	{ 
		if ( $this->options['form_location'] != $which || empty($this->options['goto_show_button']) ) 
			return '';		
		
		ob_start();	
		?>
		<div class="add_review_button_box">
			<a id="add_item_button" class="button" href="javascript:void(0);"><?php _e('Добавить отзыв','usam'); ?></a>
		</div>
		<?php
		return ob_get_clean();	
    }
}	
?>