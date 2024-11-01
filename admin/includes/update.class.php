<?php
/**
 * Обновление магазина
 */ 	
final class USAM_Update
{		
	private static function maybe_upgrade( $display = true ) 
	{ 
		$current_db_ver = (int)get_option( 'usam_db_version', 0 ); 
		if ( $current_db_ver == USAM_DB_VERSION )
			return false;		
				
		$upgrade_dir = USAM_FILE_PATH . '/admin/db/db-upgrades/';
		if (file_exists($upgrade_dir)) 
		{			
			$dh = opendir( $upgrade_dir );			
			$update_version = array();
			while ( ($upgrade_file = readdir( $dh )) !== false ) 
			{					
				if ( ($upgrade_file != "..") && ($upgrade_file != ".") && !stristr( $upgrade_file, "~" ) )
				{
					$ver_update = basename( $upgrade_file , '.php' );			
					if ( $current_db_ver < $ver_update && USAM_DB_VERSION >= $ver_update )
					{ 	
						$update_version[] = $ver_update;						
					}
				}
			}	
			if ( !empty($update_version ) )
			{				
				asort($update_version);
				foreach ( $update_version as $version ) 
				{
					if ( $display )
						echo sprintf( __('Установка обновления №%s', 'usam'), $version).'<br><br>';
					
					require_once( $upgrade_dir . $version.'.php' );	
					update_option( 'usam_db_version', $version );
				}
				
			}			
		}
		flush_rewrite_rules( );
	}	
	
	public static function start_update( $display = true ) 
	{		
		if ( ! current_user_can( 'update_plugins' ) )
			return false;
		
		require_once(USAM_FILE_PATH.'/includes/installer.class.php');
		
		set_time_limit(0);		
		
		usam_installing();		
		self::maybe_upgrade( $display );
				
		usam_installing( true );		
	}
}

function usam_installing( $is_installing = null )
{
	static $installing = null;

	if ( is_null( $installing ) )
	{
		$installing = defined( 'USAM_INSTALLING' ) && USAM_INSTALLING;
	}
	if ( ! is_null( $is_installing ) )
	{
		$old_installing = $installing;
		$installing = $is_installing;
		return (bool) $old_installing;
	}
	return (bool) $installing;
}