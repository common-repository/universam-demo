<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Point_Receipt_Products extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'point-receipt-products';		
	public function register_block_type() 
	{ 
		register_block_type($this->namespace.'/'.$this->block_name,	array('render_callback' => array( $this, 'render' ), 'editor_script'   => "usam-{$this->block_name}-block",	 'attributes' => $this->get_attributes() ));		
	}
	
	public function enqueue_editor( ) 
	{
		wp_enqueue_script("yandex_maps");
	}
	
	public function render( $attributes = [], $content = '' ) 
	{ 		
		require_once( USAM_FILE_PATH . '/includes/theme/theme.functions.php'   );
		ob_start();	
		include_once( usam_get_template_file_path( 'content-page-point_delivery' ) );
		return ob_get_clean();
	}
			
	protected function get_attributes() 
	{		
		return array(		
			'zoom' => $this->get_schema_number( 13 )		
		);
	}
}