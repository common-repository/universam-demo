<?php
namespace usam\Blocks\Elementor;

abstract class Widget extends \Elementor\Widget_Base
{		
	public function get_custom_help_url() 
	{
		return 'https://docs.wp-universam.ru/';		
	}

	public function get_categories() 
	{
		return [ 'usam' ];
	}
}