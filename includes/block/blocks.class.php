<?php
namespace usam;
namespace usam\Blocks;

Blocks::init();
class Blocks 
{
	public static function init()
	{
		add_action( 'init', [__CLASS__, 'register_blocks'] );		
	}	
	
	public static function register_blocks() 
	{
		require_once(USAM_FILE_PATH.'/includes/block/wp/library.class.php');
		
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		if ( \is_plugin_active('elementor/elementor.php') )
		{
			require_once(USAM_FILE_PATH.'/includes/block/elementor/library.class.php');
		}		
	}	
}
