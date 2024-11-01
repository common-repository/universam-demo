<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Order_Payment_Button extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'order-payment-button';			
	
	public function render( $attributes = [], $content = '' ) 
	{ 
		return '<a href="" class="usam_modal button quick_order_payment" data-modal = "quick_order_payment">'.usam_get_svg_icon( 'basket' ).__('Оплатить заказ', 'usam').'</a>';
	}
	
	protected function get_attributes() {
		return [];
	}
}