<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class Product_Images extends Widget
{	
	public function get_name() 
	{
		return 'product_images';
	}
	
	public function get_title() 
	{
		return __( 'Изображения товара', 'usam' );
	}
	
	public function get_icon() {
		return 'eicon-image-box';
	}

	public function get_keywords() 
	{
		return ['Product Images'];
	}
	
	protected function register_controls() 
	{	
	
	}	

	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();
		usam_single_image();
	}	
}