<?php
namespace usam\Blocks\Elementor;
use Elementor\Controls_Manager;

require_once( USAM_FILE_PATH . "/includes/block/elementor/block.class.php" );
class search extends Widget
{	
	public function get_name() 
	{
		return 'search';
	}
	
	public function get_title() 
	{
		return 'search';
	}
	
	public function get_icon() {
		return 'eicon-product-search';
	}

	public function get_keywords() 
	{
		return ['search'];
	}
	
	protected function render() 
	{ 
		$attributes = $this->get_settings_for_display();		
		usam_include_template_file('site-search', 'template-parts');
	}	
}