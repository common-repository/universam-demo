<?php
// Класс обновления */
require_once( USAM_FILE_PATH . '/includes/exchange/upgrader.class.php');
class USAM_Upgrader_Plugin extends USAM_Upgrader
{
	public function __construct( ) 
	{ 	
		add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check_update' ) ); // вызывает wp_update_plugins
		add_filter( 'plugins_api', array( &$this, 'check_info' ), 10, 3 );
		add_filter( 'upgrader_package_options', array( &$this, 'setUpdatePackage' ) );
	}	
	
	protected function get_licenses( )
	{
		require_once ABSPATH . 'wp-admin/includes/plugin.php';		
		$licenses = $this->get_licenses_db();
		$plugins = get_plugins();
		foreach( $licenses as $key => $license )	
		{			
			if ( $license->software_type == 'plugin' )
			{
				$licenses[$key]->plugin_slug = $license->software.'/index.php';							
				if ( isset($plugins[$licenses[$key]->plugin_slug]) )
				{				
					$licenses[$key]->icons = [];
					$data = $plugins[$licenses[$key]->plugin_slug];
					$licenses[$key]->version = $data['Version'];
					continue;
				}
			}
			unset($licenses[$key]);
		}		
		if ( !usam_is_license_type('FREE') )
		{
			$license = get_option('usam_license');	
			$plugin              = new stdClass();			
			$plugin->software    = 'universam';
			$plugin->license     = !empty($license['license'])?$license['license']:'';
			$plugin->plugin_slug = USAM_PLUGINSLUG;		
			$plugin->version = USAM_VERSION;	
			$plugin->icons = ['1x' => 'https://ps.w.org/universam-demo/assets/icon-128x128.png'];
			$licenses[] = $plugin;
		}		
		return $licenses;
	}
	
	public function check_update( $transient ) 
	{		
		foreach( $this->get_licenses() as $license )	
		{			
			$info = $this->get_info_update( $license );	
			
			$plugin               = new stdClass();				
			$plugin->id           = $license->software;
			$plugin->slug         = $license->software;
			$plugin->plugin       = $license->plugin_slug;		
			$plugin->icons        = $license->icons;	
		
			if ( !is_wp_error($info) && !empty($info['version']) && version_compare($license->version, $info['version'], '<' ) )
			{ 						
				$plugin->new_version = $info['version'];	
				$plugin->package     = $info['package'];	
				if ( isset($transient->response[$license->plugin_slug]) )
					$plugin->icons = $transient->response[$license->plugin_slug]->icons;
				$transient->response[$license->plugin_slug] = $plugin;		
				if( isset($transient->no_update[$license->plugin_slug]) )
					unset( $transient->no_update[$license->plugin_slug] );
			}
			else
			{
				$transient->no_update[$license->plugin_slug] = (object) $plugin;
				if( isset($transient->response[$license->plugin_slug]) )
					unset($transient->response[$license->plugin_slug]);
			}
		}
		return $transient;
	}
}
