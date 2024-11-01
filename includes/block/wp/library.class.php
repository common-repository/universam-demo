<?php
namespace usam\Blocks\WP;

Library::init();
class Library 
{
	public static function init()
	{
		Library::register_blocks();
		add_action( 'enqueue_block_editor_assets', [__CLASS__, 'block_editor_scripts'] );
	}
	
	public static function block_editor_scripts()
	{ 
		\USAM_Assets::theme();
	}
		
	public static function register_blocks() 
	{
		$wp_blocks = [
			'Files',
			'Slider',	
			'HTML_blocks',	
			'Buy_Product',		
			'Selected_Products',
			'Webform_Link',
			'Webform',
			'Reviews',
			'Point_Receipt_Products',
			'Order_Statuses',
			'Search_Order',	
			'Colors',
			'Stock_Points',
			'Add_Product',
			'Product_Filter',
			'Selected_Filters',
			'Order_Payment_Button',	
			'Banners',	
			'Phone',	
			'Search',		
			'Basket',
			'ProductTag',	
			'Selection',		
			'Region_Selection',	
			'Stock_Level',
			'Promotion_Timer',
			'Share',
		//	'Map',
		/*	'AllReviews',		
			'FeaturedCategory',
			'FeaturedProduct',
			'HandpickedProducts',
			'ProductBestSellers',
			'ProductCategories',
			'ProductCategory',
			'ProductNew',
			'ProductOnSale',
			'ProductsByAttribute',
			'ProductTopRated',
			'ReviewsByProduct',
			'ReviewsByCategory',*/
		];			
		foreach ( $wp_blocks as $class ) 
		{
			require_once( USAM_FILE_PATH . "/includes/block/wp/block-types/".strtolower($class)."_block.class.php" );
			$class    = 'usam\Blocks\WP' . '\\' . $class;
			$instance = new $class();
			$instance->register_block_type();			
		}				
	}	
}
