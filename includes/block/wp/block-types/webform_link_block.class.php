<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Webform_Link extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'webform-link';		
		
	public function render( $attributes = [], $content = '' ) 
	{ 
		$output = '';
		if ( !empty($attributes['code']) )
		{ 
			require_once( USAM_FILE_PATH . '/includes/theme/theme.functions.php'   );
			require_once( USAM_FILE_PATH .'/includes/feedback/webform.php' );
			$output = "<div class='usam_block_webform_button usam_block_webform_button_".$attributes['code']."'>".\usam_get_webform_link( $attributes['code'], 'button' )."</div>";
		}	
		return $output;
	}
	
	protected function get_attributes() {
		return array(		
			'code'      => $this->get_schema_string( ),
			'circle'  => $this->get_schema_string( USAM_SVG_ICON_URL.'#circle-usage' ),
			'selected_circle'  => $this->get_schema_string( USAM_SVG_ICON_URL.'#selected_circle-usage' )
		);
	}
}