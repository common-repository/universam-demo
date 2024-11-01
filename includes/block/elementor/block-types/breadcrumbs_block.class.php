<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class breadcrumbs extends Widget
{	
	public function get_name() 
	{
		return 'breadcrumbs';
	}
	
	public function get_title() 
	{
		return 'breadcrumbs';
	}
	
	public function get_icon() {
		return 'eicon-product-breadcrumbs';
	}

	public function get_keywords() 
	{
		return ['breadcrumbs'];
	}
	
	protected function register_controls() 
	{	
		$this->start_controls_section('section_content', ['label' => __( 'Настройки', 'usam' )]);
		$this->add_control('show_home_page', ['label' => __('Показывать главную страницу', 'usam'), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
		$this->end_controls_section();
	}

	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();		
		require_once( USAM_FILE_PATH . '/includes/theme/breadcrumbs.class.php' );
		usam_output_breadcrumbs( $attributes );
	}	
}