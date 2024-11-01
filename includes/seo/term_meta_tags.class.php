<?php
require_once( USAM_FILE_PATH . '/includes/seo/meta_tags.class.php' );	
class USAM_Term_Meta_Tags extends USAM_Meta_Tags
{	
	protected function get_args( ) 
	{
		global $post;			
		$term = $this->get_term();			 
		$args = parent::get_args();
		if ( $term )
		{
			$args['name'] = $term->name;
			$args['filter_name'] = '';
			$args['filter_attribute_name'] = '';
			if ( !empty($_GET['f']) )
			{
				$select_filters = $_GET['f'];
				if ( !is_array($select_filters) )
					$select_filters = usam_url_array_decode($select_filters);
				if( is_array($select_filters) )
					foreach( $select_filters as $key => $value )
					{
						if ( is_string($value) )
							$value = explode('-', $value);
						$attribute = get_term($key, 'usam-product_attributes');
						$args['filter_name'] = !empty($attribute->name)?$attribute->name:'';
						$args['filter_attribute_name'] = implode(', ', usam_get_product_attribute_values(['fields' => 'value', 'include' => $value]));
						break;
					}
			}		
		}		
		return apply_filters( 'usam_args_term_meta_tags', $args, $post );
	}
	
	public function get_open_graph_url( ) 
	{
		$term = $this->get_term();
		return !empty($term->url)?$term->url:'';
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
		global $wp_query, $post;	
		$tag_meta = '';
		$args = $this->get_args();			
		$term = $this->get_term();
		if( $term )
		{		
			if ( !empty($_GET['f']) )
			{
				$tag_meta = get_term_meta($term->term_id, 'meta_filter_'.$key, true);
				if ( !$tag_meta && !empty($this->options['terms']['product-filters']) )
				{
					if ( !empty($this->options['terms']['product-filters'][$key]) )
						$tag_meta = $this->options['terms']['product-filters'][$key];
				}
			}
			if ( !$tag_meta )				
				$tag_meta = get_term_meta($term->term_id, 'meta_'.$key, true);	
			if ( !$tag_meta )
			{
				if ( !empty($this->options['terms'][$term->taxonomy]) )
				{
					if ( !empty($this->options['terms'][$term->taxonomy][$key]) )
						$tag_meta = $this->options['terms'][$term->taxonomy][$key];
				}
			}			
		}				
		$current_page = absint( get_query_var('paged') );		
		if ( $current_page > 1 )
		{
			$args['page'] = sprintf( __('Страница %s','usam'), $current_page);
			$args['page_number'] = $current_page;
		}
		else
		{
			$args['page'] = '';	
			$args['page_number'] = '';
		}
		$shortcode = new USAM_Shortcode();		
		return trim($shortcode->process_args( $args, $tag_meta ));
	}	
}