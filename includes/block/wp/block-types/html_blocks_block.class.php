<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class html_blocks extends Block
{
	protected $enqueued_assets = true;	
	protected $block_name = 'html-blocks';				
	public function render( $attributes = [], $content = '' ) 
	{ 
		ob_start();	
		if ( !empty($attributes['items']) )
		{ 
			foreach( $attributes['items'] as $id )
			{			
				$block = usam_get_html_block( $id );
				if( !empty($block) )
				{
					include( usam_get_template_file_path( 'html-blocks', 'template-parts' ) );
				}
			}
		}	
		return ob_get_clean();
	}
	
	protected function get_attributes() {
		return [
			'items' => $this->get_schema_array( ),
			'settings' => $this->get_schema_number( 1 ),
		];
	}
}