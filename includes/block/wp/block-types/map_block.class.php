<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Map extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'map';		
		
	public function enqueue_editor( ) 
	{
		wp_enqueue_script("yandex_maps");
	}
	
	public function render( $attributes = [], $content = '' ) 
	{ 		
		require_once( USAM_FILE_PATH . '/includes/theme/theme.functions.php'   );
		$points = [['latitude' => $attributes['latitude'], 'longitude' => $attributes['longitude'], 'title' => $attributes['title'], 'map_description' => $attributes['description'] ]];
		return usam_get_map(['points' => $points, 'longitude' => $attributes['longitude'], 'latitude' => $attributes['latitude']]);	
	}
			
	protected function get_attributes() 
	{
		return array(		
			'active' => $this->get_schema_number(),
			'zoom' => $this->get_schema_number( 13 ),				
			'description' => $this->get_schema_string(  ),
			'title' => $this->get_schema_string( ),			
			'latitude' => $this->get_schema_number( ),
			'longitude' => $this->get_schema_number( ),	
		);
	}
}