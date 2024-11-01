<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Slider extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'slider';		
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
		if ( !empty($attributes['id']) )
		{ 
			ob_start();
			\usam_display_slider( $attributes['id'] );			
			$output = ob_get_clean();			
		}
		return $output;
	}
	
	protected function get_attributes() {
		return array(		
			'id'      => $this->get_schema_number( ),
			'circle'  => $this->get_schema_string( USAM_SVG_ICON_URL.'#circle-usage' ),
			'selected_circle'  => $this->get_schema_string( USAM_SVG_ICON_URL.'#selected_circle-usage' )
		);
	}
}