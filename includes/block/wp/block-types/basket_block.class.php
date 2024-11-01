<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Basket extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'basket';			
	
	public function render( $attributes = [], $content = '' ) 
	{ 
		ob_start();		
		require_once(USAM_FILE_PATH.'/includes/block/template-parts/basket.php');
		return ob_get_clean();
	}
	
	protected function get_attributes() {
		return [
			'basket_view' => $this->get_schema_string( 'table' ),
			'signature' => $this->get_schema_number( 1 )				
		];
	}
}