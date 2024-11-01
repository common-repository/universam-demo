<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Colors extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'colors';		
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
		$style = $attributes['option'] == 'circle'?'border-radius:50%;':'';
		$output = '<div class="color_option" style="display:flex;align-items:center;justify-content:center;color:'.$attributes['text_color'].';background-color:'.$attributes['color'].';width:'.$attributes['size'].'px;height:'.$attributes['size'].'px;'.$style.'">'.($attributes['show_color_code']?$attributes['color']:'').'</div>';		
		return $output;
	}
	
	protected function get_attributes() {
		return array(		
			'color'     => $this->get_schema_string( ),		
			'text_color'=> $this->get_schema_string( ),	
			'option'    => $this->get_schema_string('circle' ),
			'size'      => $this->get_schema_number( 100 ),		
			'show_color_code'  => $this->get_schema_number( ),
			'type_options' => $this->get_schema_array( array( array('key' => 'circle', 'name' => __("Круг","usam")), array('key' => 'square', 'name' => __("Квадрат","usam")) )),	
			'bol' => $this->get_schema_array( array( array('key' => 0, 'name' => __("Нет","usam")), array('key' => 1, 'name' => __("Да","usam")) )),	
		);
	}
}