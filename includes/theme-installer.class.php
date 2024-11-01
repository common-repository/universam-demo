<?php
// Установка темы сайта
final class USAM_Theme_Installer
{	
	public static $new_install = false;
	private static $errors = array();	
	
	public static function set_log_file()
	{		
		usam_log_file( self::$errors );
		self::$errors = array();
	}
	
	public static function install( $v = 1 ) 
	{
		global $wpdb;		
				
		set_time_limit(3600);
		self::htmlblocks();		
		
		set_theme_mod( 'db_version', $v );
	}	

	public static function htmlblocks() 
	{	
		$htmlblocks = get_template_directory().'/install/html-blocks.json';	
		if( file_exists($htmlblocks) )
		{
			$htmlblocks = json_decode(file_get_contents($htmlblocks), true);		
			if( $htmlblocks )
				update_option( 'usam_html_blocks', $htmlblocks );	
		}
	}	
	
}