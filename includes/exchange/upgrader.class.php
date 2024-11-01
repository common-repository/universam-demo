<?php
// Класс обновления */
require_once( USAM_FILE_PATH . '/includes/licenses_query.class.php'  );
abstract class USAM_Upgrader 
{	
	protected static $info = array();
	
	protected function set_error( $error )
	{	
		if ( is_string($error)  )		
			$error_mesage = $error;	
		else		
			$error_mesage = sprintf( __('Обновление вызвал ошибку №%s. Текст ошибки: %s'), $error['error_code'], $error['error_message']);
		usam_log_file( $error_mesage );
	}	
	
	protected function get_licenses_db( )
	{
		$cache = wp_cache_get( 'usam_licenses' );
		if ( $cache === false )		
		{			
			$cache = usam_get_licenses(['software_type' => ['plugin', 'theme']]);	
			if ( empty($cache) )
				$cache = [];			
			wp_cache_set( 'usam_licenses', $cache );
		}		
		return $cache;		
	}

	public function get_info_update( $license ) 
	{				
		if ( isset(self::$info[$license->license]) )
			return self::$info[$license->license];
			
		$api = new USAM_Service_API();
		self::$info[$license->license] = $api->send_request(['license' => $license->license, 'query' => 'check_update']);
		if ( isset($license->id) && isset($info['license_end_date']) )
			usam_update_license($license->id, ['license_end_date' => self::$info['license_end_date'], 'status' => self::$info['status']]);
		return self::$info[$license->license];
	}
			
	public function check_info( $result, $action, $args ) 
	{		
		foreach( $this->get_licenses() as $license )	
		{			
			if ( isset($args->slug) && sanitize_key( $args->slug ) == sanitize_key($license->software) ) 
			{						
				$api = new USAM_Service_API();
				$pluginInfo = $api->send_request(['license' => $license->license, 'software' => $license->software, 'query' => 'check_info']);
				if ( isset($pluginInfo['wordpress_plugin'] ) ) 
				{
					$info        = $pluginInfo['wordpress_plugin'];			
					$sections    = explode( '<h2 id="item-description__changelog">Changelog</h2>', $pluginInfo['description'] );
					$description = ( isset($sections[0] ) ) ? $sections[0] : '';
					$changelog   = ( isset($sections[1] ) ) ? $sections[1] : '';

					$plugin                  = new stdClass();
					$plugin->name            = $info['plugin_name'];
					$plugin->author          = $info['author'];
					$plugin->slug            = $license->software;
					$plugin->version         = $info['version'];
					$plugin->requires        = $info['required'];
					$plugin->tested          = $info['tested'];
					$plugin->rating          = ( (int) $pluginInfo['rating']['count'] < 3 ) ? 100.0 : 20 * (float) $pluginInfo['rating']['rating'];
					$plugin->num_ratings     = (int) $pluginInfo['rating']['count'];
					$plugin->active_installs = (int) $pluginInfo['number_of_sales'];
					$plugin->last_updated    = $pluginInfo['updated_at'];
					$plugin->added           = $pluginInfo['published_at'];
					$plugin->homepage        = $pluginInfo['homepage'];
					$plugin->sections        = array(
						'description' => $pluginInfo['description'],
						'changelog'   => $pluginInfo['changelog']
					);
					$plugin->download_link   = $pluginInfo['url'];
					$plugin->banners         = array(
						'high' => $pluginInfo['previews']['landscape_url']
					);
					return $plugin;
				} 
				else 
					return false;
			} 
		}
		return false;
	}	
			
// Получить файл плагина
	public function setUpdatePackage( $options ) 
	{ 
		foreach( $this->get_licenses() as $license )	
		{
			if ( !empty($options['hook_extra']['plugin']) && $options['hook_extra']['plugin'] === $license->plugin_slug ) 
			{ 
				$api = new USAM_Service_API();
				$result = $api->send_request(['license' => $license->license, 'software' => $license->software, 'query' => 'check_update']);
				if ( !is_wp_error( $result ) && isset($result['package']) && empty($response['error']) ) 
				{			
					$options['package'] = $result['package'];					
				}		
				return $options;				
			}
		}
		return $options;
	}
}
