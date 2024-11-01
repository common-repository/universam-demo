<?php
/**
 * Загрузка модулей VUE
 */

class USAM_VUE_Modules
{		
	private static $register = [];	
	
	public function __construct() 
	{			
		add_action( 'admin_footer', [$this, 'print'], 4 );
		add_action( 'wp_footer', [$this, 'print'], 4 );
	}
	
	function print() 
	{  
		foreach (self::$register as $module) 
		{
			$file = USAM_FILE_PATH.'/admin/templates/vue-templates/'.$module.'.php';
			if (file_exists($file)) 
				require_once( $file );
		}
	}
	
	function registered( $handle ) 
	{  
		if ( !in_array($handle, self::$register) )
			self::$register[] = $handle;
	}
}
?>