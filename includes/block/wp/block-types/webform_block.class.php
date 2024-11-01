<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Webform extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'webform';		
	
	public function render( $attributes = [], $content = '' ) 
	{   
		$output = '';
		if ( !empty($attributes['code']) )
		{
			require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
			require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
			remove_filter( 'the_content', 'wptexturize' ); // иначе искажается html
			$output = \usam_get_webform_template( $attributes['code'] ); 
		}	
		return $output;
	}	
	
	protected function get_attributes() {
		return [		
			'code'      => $this->get_schema_string( ),		
			'circle'  => $this->get_schema_string( USAM_SVG_ICON_URL.'#circle-usage' ),
			'selected_circle'  => $this->get_schema_string( USAM_SVG_ICON_URL.'#selected_circle-usage' )
		];
	}
}