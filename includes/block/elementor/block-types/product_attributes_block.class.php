<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class Product_Attributes extends Widget
{	
	public function get_name() 
	{
		return 'product_attributes';
	}
	
	public function get_title() 
	{
		return esc_html__( 'Характеристики товара', 'usam' );
	}
	
	public function get_icon() {
		return 'eicon-product-meta';
	}

	public function get_keywords() 
	{
		return ['product','attributes'];
	}
	
	protected function register_controls() 
	{	
		$this->start_controls_section('section_content', ['label' => __( 'Настройки', 'usam' )]);
		$this->add_control('group', ['label' => __('Показывать группы', 'usam'), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes']);
		$this->add_control('important', ['label' => __('Только важные', 'usam'), 'type' => Controls_Manager::SWITCHER, 'default' => 'no']);
		$this->end_controls_section();		
	}

	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();		
		$product_id = get_the_ID();
		echo usam_display_product_attributes( $product_id, $attributes['group']=='no'?false:true, $attributes['important']=='no'?false:true );
	}	
}