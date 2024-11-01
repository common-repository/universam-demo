<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class html_blocks extends Widget
{	
	public function get_name() 
	{
		return 'HTML_blocks';
	}
	
	public function get_title() 
	{
		return esc_html__( 'HTML блоки', 'usam' );
	}
	
	public function get_icon() {
		return 'eicon-product-related';
	}

	public function get_keywords() 
	{
		return ['product', 'related', 'popularity', 'history views', 'also bought'];
	}
	
	protected function register_controls() 
	{	
		$this->start_controls_section('section_view', ['label' => __( 'Настройки', 'usam' )]);				
		$items = get_option( 'usam_html_blocks' );	
		$options = [];
		foreach ($items as $item ) 
		{
			$options[$item['id']] = $item['id'].' - '.$item['html_name'];
		}		
		$this->add_control('id', ['label' => __('HTML блок', 'usam'), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => $options]);
		$this->end_controls_section();
	}

	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();
		$block = \usam_get_html_block( $attributes['id'] );
		if( !empty($block) )
		{
			include( \usam_get_template_file_path( 'html-blocks', 'template-parts' ) );
		}
	}	
}