<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Order_Statuses extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'order-statuses';		
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
		require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');
		$order_statuses = usam_get_object_statuses( array( 'cache_results' => true, 'type' => 'order', 'visibility' => 1 ) );	
		$output = '';
		foreach ( $order_statuses as $key => $status )	
		{
			$output .= "<p><strong>«".$status->name."»</strong> - ".$status->description."</p>";
		}
		return $output;
	}
	
	protected function get_attributes() {
		return array( );
	}
}