<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Region_Selection extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'region-selection';		
	
	public function render( $attributes = [], $content = '' ) 
	{ 
		ob_start();			
		$location = usam_get_location( usam_get_customer_location( ) );	
		if ( !empty($location) )
		{
			?><a class="usam_modal widget_regions_search" data-modal = "regions_search"><?php usam_svg_icon( 'marker', 'widget_regions_search__icon' ); ?><span class="widget_regions_search__location_name"><?php echo $location['name']; ?></span></a><?php
		}
		$output = ob_get_clean();
		return $output;
	}
	
	protected function get_attributes() {
		return [];
	}
}