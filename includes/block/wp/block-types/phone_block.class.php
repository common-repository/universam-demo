<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Phone extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'phone';		
		
	public function render( $attributes = [], $content = '' ) 
	{
		return usam_get_shop_phone();
	}
	
	protected function get_attributes() {
		return [];
	}
}