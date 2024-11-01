<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class Product_Property extends Widget
{		
	public function get_name() 
	{
		return 'product_property';
	}

	public function get_title() 
	{
		return esc_html__( 'Свойство товара', 'usam' );
	}
	
	public function get_icon() {
		return 'eicon-product-meta';
	}

	public function get_keywords() 
	{
		return ['product','property'];
	}

	//public function get_script_depends() {}

	//public function get_style_depends() {}
	
	protected function register_controls() 
	{	
		$this->start_controls_section('section_content', ['label' => __( 'Настройки', 'usam' )]);		
		$this->add_control('property', ['label'   => __('Свойство товара', 'usam'), 'type' => Controls_Manager::SELECT, 'default' => 'sku', 'options' => usam_get_columns_product_import()]);
		$this->end_controls_section();
	}

	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();					
		$product_id = get_the_ID();
		echo usam_get_product_property($product_id, $attributes['property']);
	}	
}