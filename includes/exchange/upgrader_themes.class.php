<?php
// Класс обновления */
require_once( USAM_FILE_PATH . '/includes/exchange/upgrader.class.php');
class USAM_Upgrader_Themes extends USAM_Upgrader
{
	public function __construct( ) 
	{ 		
		add_action( 'pre_set_site_transient_update_themes', [&$this, 'check_update'], 21, 1 );
	}
	
	private function get_licenses() 
	{
		$licenses = $this->get_licenses_db();
		foreach( $licenses as $key => $license )	
		{
			if ( $license->software_type == 'theme' )
			{
				$licenses[$key]->plugin_slug = $license->software;
				$theme = wp_get_theme( $license->plugin_slug );
				if ( $theme->exists() )
				{
					$licenses[$key]->version     = $theme->get( 'Version' );
					$licenses[$key]->name        = $theme->get( 'Name' );
					continue;
				}
			}
			unset($licenses[$key]);
		}
		$themes = ['Domino'];
		foreach( $themes as $theme_slug )
		{
			$theme = wp_get_theme( $theme_slug );
			if ( $theme->exists() )
			{
				$license = get_option('usam_license');	
				$plugin              = new stdClass();			
				$plugin->software    = $theme_slug;
				$plugin->license     = 'FREE';
				$plugin->version     = $theme->get( 'Version' );
				$plugin->name        = $theme->get( 'Name' );
				$plugin->plugin_slug = $theme_slug;			
				$licenses[] = $plugin;
			}		
		}
		return $licenses;
	}
	
	public function check_update( $transient ) 
	{		
		if ( empty($transient->checked) )
			return $transient;
    
		foreach( $this->get_licenses() as $license )	
		{
			if ( !empty($transient->response) && isset($transient->response[$license->plugin_slug]) ) 		
				return $transient;			
						
			$info = $this->get_info_update( $license );
			if ( empty($info) )
				return $transient;		
			
			$plugin             = array();				
			$plugin['id']       = $license->software;
			$plugin['slug']     = $license->software;
			$plugin['url']      = $info['url'];
			$plugin['plugin']   = $license->plugin_slug;			
			$theme = wp_get_theme( $license->software );
			if ( !is_wp_error( $info ) && !empty($info['version']) && $theme->exists() ) 
			{			
				$plugin['id']   = $license->software;					
				$currentVersion = $theme->get( 'Version' );					
				if ( version_compare( $currentVersion, $info['version'], '<' ) )
				{ 								
					$plugin['new_version'] = $info['version'];	
					$plugin['package']     = $info['package'];
					$transient->response[$license->plugin_slug] = $plugin;				
					unset( $transient->no_update[$license->plugin_slug] ); 
				}
				else
				{
					$transient->no_update[$license->plugin_slug] = $plugin;
					unset( $transient->response[$license->plugin_slug] );
				}
			}
			else
			{
				$transient->no_update[$license->plugin_slug] = $plugin;
				unset( $transient->response[$license->plugin_slug] );
			}	
		}
		return $transient;
	}
}
