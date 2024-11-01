<?php
namespace usam\Blocks\Elementor;

Library::init();
class Library 
{	
	public static function init()
	{  
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( \is_plugin_active('elementor/elementor.php') )
		{
			add_action( 'elementor/widgets/register', [__CLASS__, 'register_blocks'] );
			add_action( 'elementor/elements/categories_registered', [__CLASS__, 'add_elementor_widget_categories'] );	
			add_action( 'elementor/controls/register', [__CLASS__, 'register_new_controls'] );						
		}
	}	
	
	public static function register_blocks( $widgets_manager ) 
	{ 
		$elementor_blocks = [			
			'Selected_Products',
			'breadcrumbs',
			'Product_Images',	
			'Product_Property',	
			'Product_Attributes',
			'html_blocks',			
			'Buy_Product',		
			'Webform_Link',	
			'Webform',	
			'Reviews',
			'Slider',
			'Categories',
			'Search',
			'Basket'
		];			
		foreach ( $elementor_blocks as $class ) 
		{
			require_once( USAM_FILE_PATH . "/includes/block/elementor/block-types/".strtolower($class)."_block.class.php" );	
			$class    = 'usam\Blocks\Elementor' . '\\' . $class;
			$widgets_manager->register( new $class() );
		}	
	}
	
	public static function add_elementor_widget_categories( $elements_manager ) 
	{
		$elements_manager->add_category('usam',['title' => esc_html__( 'Универсам', 'usam' ), 'icon' => 'fa fa-plug']);
	//	'active' => false,		
	}
	
	public static function register_new_controls( $controls_manager ) 
	{
		$items = [			
			'Interval_Control',
		];			
		foreach ( $items as $class ) 
		{
			require_once( USAM_FILE_PATH . "/includes/block/elementor/controls/".strtolower($class).".class.php" );	
			$class    = 'usam\Blocks\Elementor' . '\\' . $class;
			$controls_manager->register( new $class() );
		}	
	}
}
