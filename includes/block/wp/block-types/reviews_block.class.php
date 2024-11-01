<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Reviews extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'reviews';		
	public function register_block_type() 
	{ 
		register_block_type(
			$this->namespace . '/' . $this->block_name,
			array(
				'render_callback' => array( $this, 'render' ),
				'editor_script'   => "usam-{$this->block_name}-block",		
				'attributes'      => $this->get_attributes(), 
			)
		);
	}
	
	public function render( $attributes = [], $content = '' ) 
	{ 
		$output = '';
		if ( !empty($attributes['selected']) )
		{ 			
			$args = array('hide_response' => $attributes['hide_response'], 'pagination' => 0, 'summary_rating' => $attributes['summary_rating']);
			
			if ( $attributes['page_id'] )
				$args['page_id'] = $attributes['page_id'];			
			if ( $attributes['per_page'] )
				$args['per_page'] = $attributes['per_page'];
			
			$customer_reviews = new \USAM_Customer_Reviews_Theme();
			return $customer_reviews->output_reviews_show( $args );
		}	
		return $output;
	}
	
	protected function get_attributes() {
		return array(		
			'hide_response' => $this->get_schema_number( 1 ),
			'summary_rating' => $this->get_schema_number( ),
			'page_id' => $this->get_schema_number( ),
			'per_page' => $this->get_schema_number( 20 ),	
			'selected'   => $this->get_schema_number( 1 ),	
		);
	}
}