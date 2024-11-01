<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class Slider extends Widget
{	
	public function get_name() 
	{
		return 'slider';
	}
	
	public function get_title() 
	{
		return esc_html__( 'Слайдер', 'usam' );
	}
	
	public function get_icon() {
		return 'eicon-post-slider';
	}

	public function get_keywords() 
	{
		return ['slider'];
	}
	
	protected function register_controls() 
	{	
		$this->start_controls_section('section_content', ['label' => __( 'Настройки', 'usam' )]);
		$options = [];
		foreach( \usam_get_sliders() as $slider)
		{
			$options[$slider->id] = $slider->name;
		}
		$this->add_control('slider_id', ['label'   => __('Слайдер', 'usam'), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => $options]);
		$this->end_controls_section();		
	}

	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();
		\usam_display_slider( $attributes['slider_id'] );
	}	
}