<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Buy_Product extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'buy-product';
	
	public function render( $attributes = [], $content = '' ) 
	{ 
		$output = '';
		if ( !empty($attributes['product_id']) )
		{ 
			ob_start();	
			\usam_addtocart_button( $attributes['product_id'] );			
			$output = ob_get_clean();
		}	
		return $output;
	}
	
	protected function get_attributes() {
		return array(		
			'product_id' => $this->get_schema_number(),
			'align'      => $this->get_schema_align(),			
		);
	}
}