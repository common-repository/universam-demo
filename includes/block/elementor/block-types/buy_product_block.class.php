<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class Buy_Product extends Widget
{	
	public function get_name() 
	{
		return 'buy_product';
	}
	
	public function get_title() 
	{
		return esc_html__( 'Кнопка в корзину', 'usam' );
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
		//$this->start_controls_section('section_content', ['label' => __( 'Настройки', 'usam' )]);		
	//$this->end_controls_section();		
	}

	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();
		//\usam_addtocart_button( $attributes['product_id'] );
		\usam_addtocart_button(  );	
	}	
}