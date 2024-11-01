<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class banners extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'banners';		
		
	public function render( $attributes = [], $content = '' ) 
	{  
		ob_start();
		if ( !empty($attributes['id']) )		
			echo \usam_get_theme_banner( $attributes['id'], $attributes, false );
		return ob_get_clean();
	}
	
	protected function get_attributes() {
		return array(		
			'id'      => $this->get_schema_number( 0 ),
			'circle'  => $this->get_schema_string( USAM_SVG_ICON_URL.'#circle-usage' ),
			'selected_circle'  => $this->get_schema_string( USAM_SVG_ICON_URL.'#selected_circle-usage' )
		);
	}
}