<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Search extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'search';		
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
		ob_start();
			usam_include_template_file('site-search', 'template-parts');
		return ob_get_clean();
	}
	
	protected function get_attributes() {
		return [	
			
		];
	}
}